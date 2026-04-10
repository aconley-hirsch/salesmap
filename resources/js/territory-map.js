// ── Territory Map (D3 rendering, data passed from server) ──

const stateNames = {
  AL: 'Alabama', AK: 'Alaska', AZ: 'Arizona', AR: 'Arkansas', CA: 'California',
  CO: 'Colorado', CT: 'Connecticut', DE: 'Delaware', DC: 'District of Columbia',
  FL: 'Florida', GA: 'Georgia', HI: 'Hawaii', ID: 'Idaho', IL: 'Illinois',
  IN: 'Indiana', IA: 'Iowa', KS: 'Kansas', KY: 'Kentucky', LA: 'Louisiana',
  ME: 'Maine', MD: 'Maryland', MA: 'Massachusetts', MI: 'Michigan', MN: 'Minnesota',
  MS: 'Mississippi', MO: 'Missouri', MT: 'Montana', NE: 'Nebraska', NV: 'Nevada',
  NH: 'New Hampshire', NJ: 'New Jersey', NM: 'New Mexico', NY: 'New York',
  NC: 'North Carolina', ND: 'North Dakota', OH: 'Ohio', OK: 'Oklahoma', OR: 'Oregon',
  PA: 'Pennsylvania', RI: 'Rhode Island', SC: 'South Carolina', SD: 'South Dakota',
  TN: 'Tennessee', TX: 'Texas', UT: 'Utah', VT: 'Vermont', VA: 'Virginia',
  WA: 'Washington', WV: 'West Virginia', WI: 'Wisconsin', WY: 'Wyoming',
}

const fipsToState = {
  '01': 'AL', '02': 'AK', '04': 'AZ', '05': 'AR', '06': 'CA', '08': 'CO',
  '09': 'CT', 10: 'DE', 11: 'DC', 12: 'FL', 13: 'GA', 15: 'HI', 16: 'ID',
  17: 'IL', 18: 'IN', 19: 'IA', 20: 'KS', 21: 'KY', 22: 'LA', 23: 'ME',
  24: 'MD', 25: 'MA', 26: 'MI', 27: 'MN', 28: 'MS', 29: 'MO', 30: 'MT',
  31: 'NE', 32: 'NV', 33: 'NH', 34: 'NJ', 35: 'NM', 36: 'NY', 37: 'NC',
  38: 'ND', 39: 'OH', 40: 'OK', 41: 'OR', 42: 'PA', 44: 'RI', 45: 'SC',
  46: 'SD', 47: 'TN', 48: 'TX', 49: 'UT', 50: 'VT', 51: 'VA', 53: 'WA',
  54: 'WV', 55: 'WI', 56: 'WY',
}

let people = {}
let allMaps = {}
let allColors = {}
let allRoles = []

let currentView = 'rsm'
let stateFeatures = []
let svg, defs, g, pathGen
let isPanning = false
let zoomStartTransform = null
let panTimer = null
let lockedHighlight = null
let detailPaneState = null
let isTouch = false

window.addEventListener('touchstart', () => { isTouch = true }, { once: true })

// ── Helpers ──
function el(tag, className, text) {
  const node = document.createElement(tag)
  if (className) node.className = className
  if (text != null) node.textContent = text
  return node
}

function getAllKeys(mapObj, st) {
  const v = mapObj[st]
  if (!v) return []
  return Array.isArray(v) ? v.map(e => e.key) : [v]
}

function getStatesForKey(mapObj, key) {
  const states = []
  for (const [st, v] of Object.entries(mapObj)) {
    if (Array.isArray(v)) {
      const entry = v.find(e => e.key === key)
      if (entry) states.push(st + ' (' + entry.region + ')')
    } else if (v === key) {
      states.push(st)
    }
  }
  return states
}

// Returns the per-role assignments for a state in a normalized shape so
// tooltip and detail pane can share the iteration:
//   [{ key, label, entries: [{ personKey, region }] }]
function getRolesForState(st) {
  const result = []
  allRoles.forEach(({ key, label }) => {
    const mapObj = allMaps[key]
    if (!mapObj) return
    const val = mapObj[st]
    if (!val) return
    const entries = Array.isArray(val)
      ? val.map(e => ({ personKey: e.key, region: e.region }))
      : [{ personKey: val, region: null }]
    result.push({ key, label, entries })
  })
  return result
}

// ── Init ──
function showMapError() {
  const wrap = document.getElementById('tmMapWrap')
  if (!wrap) return
  wrap.replaceChildren()
  const box = el('div', 'aspect-[8/5] w-full flex items-center justify-center bg-[#0a1828]/40 border border-[#1e3050] rounded-xl')
  const inner = el('div', 'flex flex-col items-center gap-2 text-paleSky/70 px-6 text-center')
  inner.appendChild(el('div', 'text-3xl', '\u26A0'))
  inner.appendChild(el('p', 'text-sm font-semibold', 'Map failed to load'))
  inner.appendChild(el('p', 'text-xs text-paleSky/50', 'Check your network connection and refresh the page.'))
  box.appendChild(inner)
  wrap.appendChild(box)
}

async function init() {
  const data = window.territoryMapData
  people = data.people
  allMaps = data.maps
  allColors = data.colors
  allRoles = data.roles || []

  const width = 960, height = 600
  svg = d3.select('#tmMapWrap')
    .append('svg')
    .attr('viewBox', `0 0 ${width} ${height}`)
    .style('width', '100%')
    .style('height', 'auto')
  defs = svg.append('defs')
  g = svg.append('g')

  const zoom = d3.zoom()
    .scaleExtent([1, 8])
    .on('start', e => { zoomStartTransform = e.transform; isPanning = false })
    .on('zoom', e => {
      if (zoomStartTransform && (e.transform.x !== zoomStartTransform.x || e.transform.y !== zoomStartTransform.y || e.transform.k !== zoomStartTransform.k)) {
        isPanning = true
      }
      g.attr('transform', e.transform)
    })
    .on('end', () => {
      clearTimeout(panTimer)
      panTimer = setTimeout(() => { isPanning = false; zoomStartTransform = null }, 150)
    })
  svg.call(zoom)
  svg.node().style.touchAction = 'none'

  svg.on('dblclick.zoom', () => {
    svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity)
  })

  let us
  try {
    us = await d3.json('https://cdn.jsdelivr.net/npm/us-atlas@3/states-10m.json')
  } catch (err) {
    console.error('Failed to load US atlas TopoJSON', err)
    svg.remove()
    showMapError()
    return
  }

  // Map data loaded — drop the loading placeholder
  const placeholder = document.getElementById('tmMapPlaceholder')
  if (placeholder) placeholder.remove()

  stateFeatures = topojson.feature(us, us.objects.states).features
  const projection = d3.geoAlbersUsa().fitSize([width, height], topojson.feature(us, us.objects.states))
  pathGen = d3.geoPath().projection(projection)

  g.selectAll('path.state-path')
    .data(stateFeatures)
    .join('path')
    .attr('class', 'state-path')
    .attr('d', pathGen)
    .attr('data-fips', d => String(d.id).padStart(2, '0'))
    .attr('data-state', d => fipsToState[String(d.id).padStart(2, '0')] || '')
    .on('mouseenter', (e, d) => { if (!isPanning) onStateEnter(e, d) })
    .on('mousemove', e => { if (!isPanning && !detailPaneState) moveTooltip(e) })
    .on('mouseleave', () => { if (!isPanning) hideTooltip() })
    .on('click', (e, d) => { if (!isPanning) onStateTap(e, d) })

  g.selectAll('text.state-label')
    .data(stateFeatures)
    .join('text')
    .attr('class', d => {
      const st = fipsToState[String(d.id).padStart(2, '0')]
      return 'state-label' + (['DC', 'DE', 'CT', 'RI', 'NH', 'VT', 'MA', 'NJ', 'MD', 'HI'].includes(st) ? ' small' : '')
    })
    .attr('x', d => pathGen.centroid(d)[0])
    .attr('y', d => pathGen.centroid(d)[1] + 4)
    .text(d => fipsToState[String(d.id).padStart(2, '0')] || '')

  colorMap()
  renderLegend()
}

function onStateEnter(e, d) {
  if (isTouch || detailPaneState) return
  const st = fipsToState[String(d.id).padStart(2, '0')]
  if (st) showTooltip(e, st)
}

function onStateTap(e, d) {
  e.stopPropagation()
  const st = fipsToState[String(d.id).padStart(2, '0')]
  if (!st) return
  hideTooltip()
  // Clicking a state ends any focus-on-one-rep selection so the user can see
  // the full map state context for the chosen state.
  if (lockedHighlight) {
    lockedHighlight = null
    resetHighlight()
    renderLegend()
  }
  if (detailPaneState === st) {
    closeDetailPane()
  } else {
    showDetailPane(st)
  }
}

// ── Coloring ──
function colorMap() {
  const mapObj = allMaps[currentView]
  const colors = allColors[currentView]
  if (!mapObj || !colors) return

  defs.selectAll('linearGradient.split-grad').remove()

  g.selectAll('path.state-path').attr('fill', function () {
    const st = this.getAttribute('data-state')
    const val = mapObj[st]
    if (!val) return '#222'

    if (Array.isArray(val)) {
      const vertical = ['TN'].includes(st)
      const gradId = 'split-' + st
      const bbox = this.getBBox()
      const grad = defs.append('linearGradient')
        .attr('class', 'split-grad')
        .attr('id', gradId)
        .attr('gradientUnits', 'userSpaceOnUse')
        .attr('x1', bbox.x)
        .attr('y1', bbox.y)
        .attr('x2', vertical ? bbox.x + bbox.width : bbox.x)
        .attr('y2', vertical ? bbox.y : bbox.y + bbox.height)

      // Distribute hard stops evenly across N regions
      const segment = 100 / val.length
      val.forEach((entry, i) => {
        const color = colors[entry.key] || '#333'
        grad.append('stop').attr('offset', (i * segment) + '%').attr('stop-color', color)
        grad.append('stop').attr('offset', ((i + 1) * segment) + '%').attr('stop-color', color)
      })
      return `url(#${gradId})`
    }
    return colors[val] || '#333'
  })
}

// ── Public API for Alpine ──
function setView(v) {
  lockedHighlight = null
  currentView = v
  colorMap()
  renderLegend()
  resetHighlight()
}

// ── Legend ──
function renderLegend() {
  const legend = document.getElementById('tmLegend')
  legend.replaceChildren()

  const mapObj = allMaps[currentView]
  const colors = allColors[currentView]
  if (!mapObj || !colors) return

  // Collect unique people referenced in this role's map
  const seen = new Set()
  const entries = []
  for (const [, val] of Object.entries(mapObj)) {
    const keys = Array.isArray(val) ? val.map(e => e.key) : [val]
    keys.forEach(key => {
      if (!seen.has(key)) {
        seen.add(key)
        entries.push({ key, states: getStatesForKey(mapObj, key) })
      }
    })
  }
  entries.sort((a, b) => (people[a.key]?.name || '').localeCompare(people[b.key]?.name || ''))

  const roleLabel = (allRoles.find(r => r.key === currentView) || {}).label || currentView
  legend.appendChild(el('h3', 'text-sm mb-3.5 text-ecoGreen uppercase tracking-wider', roleLabel))

  // Filter chip when a legend item is locked — gives the user an obvious way out
  if (lockedHighlight && people[lockedHighlight]) {
    const chip = el('button', 'w-full mb-3 flex items-center justify-between gap-2 px-3 py-2 bg-ecoGreen/15 border border-ecoGreen/60 rounded-md text-left hover:bg-ecoGreen/25 transition-colors')
    chip.setAttribute('type', 'button')
    chip.setAttribute('aria-label', 'Clear filter')
    const label = el('div', 'text-[12px] text-white')
    label.appendChild(document.createTextNode('Showing only '))
    label.appendChild(el('span', 'font-semibold', people[lockedHighlight].name))
    chip.appendChild(label)
    chip.appendChild(el('span', 'text-ecoGreen text-base leading-none', '\u00D7'))
    chip.addEventListener('click', () => clearLock())
    legend.appendChild(chip)
  }

  entries.forEach(({ key, states }) => {
    const p = people[key]
    if (!p) return
    const color = colors[key] || '#444'

    const item = el('div', 'flex items-start gap-2.5 py-1.5 px-2 rounded-md cursor-pointer transition-colors hover:bg-[#1a2d4a] mb-0.5 tm-legend-item')
    item.dataset.key = key
    if (lockedHighlight === key) item.classList.add('tm-locked')

    const swatch = el('div', 'w-3.5 h-3.5 rounded-full shrink-0 mt-0.5')
    swatch.style.background = color
    item.appendChild(swatch)

    const info = el('div', 'flex-1 min-w-0')
    info.appendChild(el('div', 'text-[13px] font-semibold text-white', p.name))
    if (p.email) info.appendChild(el('div', 'text-[11px] text-[#6688aa] mt-px', p.email))
    if (p.phone) info.appendChild(el('div', 'text-[11px] text-[#6688aa] mt-px', p.phone))
    info.appendChild(el('div', 'text-[11px] text-paleSky/30 mt-0.5', states.join(', ')))
    item.appendChild(info)

    // When something is locked, hover is disabled so the user's selection
    // isn't visually overridden every time the cursor crosses another row.
    item.addEventListener('mouseenter', () => {
      if (!lockedHighlight) highlightTerritory(key)
    })
    item.addEventListener('mouseleave', () => {
      if (!lockedHighlight) resetHighlight()
    })
    item.addEventListener('click', () => toggleHighlight(key))

    legend.appendChild(item)
  })
}

// ── Highlighting ──
function toggleHighlight(key) {
  if (lockedHighlight === key) {
    lockedHighlight = null
    resetHighlight()
    renderLegend()
  } else {
    lockedHighlight = key
    highlightTerritory(key)
    renderLegend()
  }
}

function clearLock() {
  if (!lockedHighlight) return
  lockedHighlight = null
  resetHighlight()
  renderLegend()
}

function highlightTerritory(key) {
  const mapObj = allMaps[currentView]
  g.selectAll('path.state-path').each(function () {
    const st = this.getAttribute('data-state')
    const keys = getAllKeys(mapObj, st)
    const match = keys.includes(key)
    d3.select(this)
      .style('opacity', match ? 1 : 0.2)
      .style('filter', match ? 'brightness(1.3)' : 'none')
      .style('stroke', match ? '#fff' : '#0a1628')
      .style('stroke-width', match ? 2 : 1)
  })
  document.querySelectorAll('.tm-legend-item').forEach(el => {
    el.classList.toggle('highlight', el.dataset.key === key)
  })
}

function resetHighlight() {
  if (lockedHighlight) return
  g.selectAll('path.state-path')
    .style('opacity', null)
    .style('filter', null)
    .style('stroke', null)
    .style('stroke-width', null)
  document.querySelectorAll('.tm-legend-item').forEach(el => el.classList.remove('highlight'))
}

// ── Tooltip ──
function showTooltip(e, st) {
  const tt = document.getElementById('tmTooltip')
  tt.replaceChildren()

  tt.appendChild(el('div', 'text-xl font-bold mb-3', `${stateNames[st] || st} (${st})`))

  getRolesForState(st).forEach(({ label, entries }) => {
    entries.forEach(entry => {
      const p = people[entry.personKey]
      if (!p) return
      const wrap = el('div', 'mb-2.5 last:mb-0')
      wrap.appendChild(el('div', 'text-[10px] uppercase tracking-widest text-ecoGreen mb-0.5', label))
      wrap.appendChild(el('div', 'text-[15px] font-semibold', p.name))
      tt.appendChild(wrap)
    })
  })

  tt.style.display = 'block'
  if (window.innerWidth > 768) moveTooltip(e)
}

function moveTooltip(e) {
  if (window.innerWidth <= 768) return
  const tt = document.getElementById('tmTooltip')
  const rect = tt.getBoundingClientRect()
  let x = e.clientX + 16, y = e.clientY + 16
  if (x + rect.width > window.innerWidth - 10) x = e.clientX - rect.width - 16
  if (y + rect.height > window.innerHeight - 10) y = e.clientY - rect.height - 16
  tt.style.left = x + 'px'
  tt.style.top = y + 'px'
}

function hideTooltip() {
  document.getElementById('tmTooltip').style.display = 'none'
}

// ── Detail pane ──
function getTerritoriesForPerson(personKey) {
  const territories = []
  allRoles.forEach(({ key, label }) => {
    const mapObj = allMaps[key]
    if (!mapObj) return
    for (const [st, val] of Object.entries(mapObj)) {
      if (Array.isArray(val)) {
        val.forEach(entry => {
          if (entry.key === personKey) territories.push({ st, label, region: entry.region })
        })
      } else if (val === personKey) {
        territories.push({ st, label })
      }
    }
  })
  return territories
}

function buildDetailElement(st) {
  const wrap = document.createDocumentFragment()

  // Header
  const header = el('div', 'px-6 pt-5 pb-4 border-b border-[#1e3050] flex justify-between items-start sticky top-0 bg-[#101f35] z-10')
  header.appendChild(el('div', 'text-[22px] font-bold', `${stateNames[st] || st} (${st})`))

  const closeBtn = el('button', 'text-[#8899aa] text-2xl cursor-pointer leading-none pl-3 hover:text-white border-none bg-transparent', '\u00D7')
  closeBtn.setAttribute('aria-label', 'Close details')
  closeBtn.addEventListener('click', () => window.dispatchEvent(new CustomEvent('close-detail')))
  header.appendChild(closeBtn)
  wrap.appendChild(header)

  // Body
  const body = el('div', 'px-6 pt-2 pb-6 flex-1')

  getRolesForState(st).forEach(({ label, entries }) => {
    const section = el('div', 'mb-5 last:mb-0')
    section.appendChild(el('div', 'text-[10px] uppercase tracking-widest text-ecoGreen mb-2 pb-1 border-b border-[#1e3050]', label))

    entries.forEach(entry => {
      const p = people[entry.personKey]
      if (!p) return

      const personWrap = el('div', 'mb-3.5 last:mb-0')

      if (entry.region) {
        personWrap.appendChild(el('div', 'text-[11px] text-ecoGreen italic mb-0.5', entry.region))
      }
      personWrap.appendChild(el('div', 'text-base font-semibold mb-1', p.name))

      if (p.email) {
        const emailDiv = el('div', 'text-[13px] text-[#8899aa] mt-1')
        emailDiv.appendChild(document.createTextNode('\u2709 '))
        const link = el('a', 'text-[#8899aa] no-underline hover:text-ecoGreen hover:underline', p.email)
        link.href = 'mailto:' + p.email
        emailDiv.appendChild(link)
        personWrap.appendChild(emailDiv)
      }
      if (p.phone) {
        const phoneDiv = el('div', 'text-[13px] text-[#8899aa] mt-1')
        phoneDiv.appendChild(document.createTextNode('\u260E '))
        const link = el('a', 'text-[#8899aa] no-underline hover:text-ecoGreen hover:underline', p.phone)
        link.href = 'tel:' + p.phone.replace(/[^+\d]/g, '')
        phoneDiv.appendChild(link)
        personWrap.appendChild(phoneDiv)
      }

      // Other territories
      const allTerritories = getTerritoriesForPerson(entry.personKey)
      const otherStates = [...new Set(allTerritories.filter(t => t.st !== st).map(t => t.st))]
      if (otherStates.length > 0) {
        const terr = el('div', 'mt-2')
        terr.appendChild(el('div', 'text-[10px] uppercase tracking-wider text-paleSky/30 mb-1', 'Other territories'))
        terr.appendChild(el('div', 'text-xs text-[#6688aa] leading-relaxed', otherStates.join(', ')))
        personWrap.appendChild(terr)
      }

      section.appendChild(personWrap)
    })

    body.appendChild(section)
  })

  wrap.appendChild(body)
  return wrap
}

function showDetailPane(st) {
  hideTooltip()
  const target = document.getElementById('tmDetailPane')
  if (target) target.replaceChildren(buildDetailElement(st))
  detailPaneState = st
  window.dispatchEvent(new CustomEvent('open-detail'))
}

function closeDetailPane() {
  detailPaneState = null
  window.dispatchEvent(new CustomEvent('close-detail'))
}

function clearDetailState() {
  detailPaneState = null
}

// ── Search (called from Alpine) ──
function showNoResults(query) {
  const legend = document.getElementById('tmLegend')
  if (!legend) return
  legend.replaceChildren()
  legend.appendChild(el('h3', 'text-sm mb-3.5 text-ecoGreen uppercase tracking-wider', 'No results'))
  const wrap = el('div', 'flex flex-col items-center text-center gap-2 py-4 text-paleSky/70')
  wrap.appendChild(el('div', 'text-2xl', '\u{1F50D}'))
  wrap.appendChild(el('p', 'text-sm', `No states or people match "${query}"`))
  wrap.appendChild(el('p', 'text-[11px] text-paleSky/40', 'Try a state name or a sales rep\u2019s first or last name.'))
  legend.appendChild(wrap)
}

function onSearch(val) {
  lockedHighlight = null
  val = val.trim()
  if (!val) { resetHighlight(); renderLegend(); return }

  const lower = val.toLowerCase()
  const mapObj = allMaps[currentView]
  const matchedKeys = new Set()

  for (const [st, name] of Object.entries(stateNames)) {
    if (st.toLowerCase().includes(lower) || name.toLowerCase().includes(lower)) {
      getAllKeys(mapObj, st).forEach(k => matchedKeys.add(k))
    }
  }
  for (const [key, p] of Object.entries(people)) {
    if (p.name.toLowerCase().includes(lower)) matchedKeys.add(key)
  }

  if (matchedKeys.size === 0) {
    // Distinguish "zero matches" from "empty search" by dimming everything
    g.selectAll('path.state-path')
      .style('opacity', 0.15)
      .style('filter', 'none')
      .style('stroke', '#0a1628')
      .style('stroke-width', 1)
    showNoResults(val)
    return
  }

  // Re-render the legend in case it was previously showing the no-results state
  renderLegend()

  g.selectAll('path.state-path').each(function () {
    const st = this.getAttribute('data-state')
    const keys = getAllKeys(mapObj, st)
    const match = keys.some(k => matchedKeys.has(k))
    d3.select(this)
      .style('opacity', match ? 1 : 0.2)
      .style('filter', match ? 'brightness(1.3)' : 'none')
      .style('stroke', match ? '#fff' : '#0a1628')
      .style('stroke-width', match ? 2 : 1)
  })
  document.querySelectorAll('.tm-legend-item').forEach(el => {
    el.classList.toggle('highlight', matchedKeys.has(el.dataset.key))
  })
}

// Expose API for Alpine to call
window.TerritoryMap = { setView, onSearch, clearDetailState, clearLock }

init().catch(err => {
  console.error('Territory map init failed', err)
  showMapError()
})
