// ── Territory Map (D3 rendering, data passed from server) ──

const territoryNames = {
  'US-AL': 'Alabama', 'US-AK': 'Alaska', 'US-AZ': 'Arizona', 'US-AR': 'Arkansas', 'US-CA': 'California',
  'US-CO': 'Colorado', 'US-CT': 'Connecticut', 'US-DE': 'Delaware', 'US-DC': 'District of Columbia',
  'US-FL': 'Florida', 'US-GA': 'Georgia', 'US-HI': 'Hawaii', 'US-ID': 'Idaho', 'US-IL': 'Illinois',
  'US-IN': 'Indiana', 'US-IA': 'Iowa', 'US-KS': 'Kansas', 'US-KY': 'Kentucky', 'US-LA': 'Louisiana',
  'US-ME': 'Maine', 'US-MD': 'Maryland', 'US-MA': 'Massachusetts', 'US-MI': 'Michigan', 'US-MN': 'Minnesota',
  'US-MS': 'Mississippi', 'US-MO': 'Missouri', 'US-MT': 'Montana', 'US-NE': 'Nebraska', 'US-NV': 'Nevada',
  'US-NH': 'New Hampshire', 'US-NJ': 'New Jersey', 'US-NM': 'New Mexico', 'US-NY': 'New York',
  'US-NC': 'North Carolina', 'US-ND': 'North Dakota', 'US-OH': 'Ohio', 'US-OK': 'Oklahoma', 'US-OR': 'Oregon',
  'US-PA': 'Pennsylvania', 'US-RI': 'Rhode Island', 'US-SC': 'South Carolina', 'US-SD': 'South Dakota',
  'US-TN': 'Tennessee', 'US-TX': 'Texas', 'US-UT': 'Utah', 'US-VT': 'Vermont', 'US-VA': 'Virginia',
  'US-WA': 'Washington', 'US-WV': 'West Virginia', 'US-WI': 'Wisconsin', 'US-WY': 'Wyoming',
  'CA-AB': 'Alberta', 'CA-BC': 'British Columbia', 'CA-MB': 'Manitoba', 'CA-NB': 'New Brunswick',
  'CA-NL': 'Newfoundland and Labrador', 'CA-NS': 'Nova Scotia', 'CA-NT': 'Northwest Territories',
  'CA-NU': 'Nunavut', 'CA-ON': 'Ontario', 'CA-PE': 'Prince Edward Island', 'CA-QC': 'Quebec',
  'CA-SK': 'Saskatchewan', 'CA-YT': 'Yukon', 'REG-EMEA': 'EMEA', 'REG-APAC': 'APAC',
}

const fipsToTerritory = {
  '01': 'US-AL', '02': 'US-AK', '04': 'US-AZ', '05': 'US-AR', '06': 'US-CA', '08': 'US-CO',
  '09': 'US-CT', 10: 'US-DE', 11: 'US-DC', 12: 'US-FL', 13: 'US-GA', 15: 'US-HI', 16: 'US-ID',
  17: 'US-IL', 18: 'US-IN', 19: 'US-IA', 20: 'US-KS', 21: 'US-KY', 22: 'US-LA', 23: 'US-ME',
  24: 'US-MD', 25: 'US-MA', 26: 'US-MI', 27: 'US-MN', 28: 'US-MS', 29: 'US-MO', 30: 'US-MT',
  31: 'US-NE', 32: 'US-NV', 33: 'US-NH', 34: 'US-NJ', 35: 'US-NM', 36: 'US-NY', 37: 'US-NC',
  38: 'US-ND', 39: 'US-OH', 40: 'US-OK', 41: 'US-OR', 42: 'US-PA', 44: 'US-RI', 45: 'US-SC',
  46: 'US-SD', 47: 'US-TN', 48: 'US-TX', 49: 'US-UT', 50: 'US-VT', 51: 'US-VA', 53: 'US-WA',
  54: 'US-WV', 55: 'US-WI', 56: 'US-WY',
}

const smallLabels = ['US-DC', 'US-DE', 'US-CT', 'US-RI', 'US-NH', 'US-VT', 'US-MA', 'US-NJ', 'US-MD', 'US-HI', 'CA-PE']
const globalRegions = [
  { code: 'REG-EMEA', name: 'EMEA' },
  { code: 'REG-APAC', name: 'APAC' },
]

let people = {}
let allTerritories = {}
let allColors = {}
let allRoles = []
let currentView = 'rsm'
let currentScope = 'US'
let usFeatures = []
let canadaFeatures = []
let svg, defs, g, pathGen
let isPanning = false
let zoomStartTransform = null
let panTimer = null
let lockedHighlight = null
let detailPaneTerritory = null
let isTouch = false

window.addEventListener('touchstart', () => { isTouch = true }, { once: true })

function el(tag, className, text) {
  const node = document.createElement(tag)
  if (className) node.className = className
  if (text != null) node.textContent = text
  return node
}

function getAllKeys(mapObj, territory) {
  const value = mapObj[territory]
  if (!value) return []
  return Array.isArray(value) ? value.map(entry => entry.key) : [value]
}

function getTerritoriesForKey(mapObj, key) {
  const territories = []
  for (const [territory, value] of Object.entries(mapObj)) {
    if (Array.isArray(value)) {
      const entry = value.find(item => item.key === key)
      if (entry) territories.push(entry.region ? territory + ' (' + entry.region + ')' : territory)
    } else if (value === key) {
      territories.push(territory)
    }
  }
  return territories
}

function getRolesForTerritory(territory) {
  const result = []
  allRoles.forEach(({ key, label }) => {
    const mapObj = allTerritories[key]
    if (!mapObj) return
    const value = mapObj[territory]
    if (!value) return
    const entries = Array.isArray(value)
      ? value.map(entry => ({ personKey: entry.key, region: entry.region }))
      : [{ personKey: value, region: null }]
    result.push({ key, label, entries })
  })
  return result
}

function showMapError() {
  const wrap = document.getElementById('tmMapWrap')
  if (!wrap) return
  wrap.replaceChildren()
  const box = el('div', 'aspect-[8/5] w-full flex items-center justify-center bg-[#0a1828]/40 border border-[#1e3050] rounded-xl')
  const inner = el('div', 'flex flex-col items-center gap-2 text-paleSky/70 px-6 text-center')
  inner.appendChild(el('p', 'text-sm font-semibold', 'Map failed to load'))
  inner.appendChild(el('p', 'text-xs text-paleSky/50', 'Check your network connection and refresh the page.'))
  box.appendChild(inner)
  wrap.appendChild(box)
}

async function init() {
  const data = window.territoryMapData
  people = data.people
  allTerritories = data.territories || {}
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
  svg.on('dblclick.zoom', () => svg.transition().duration(300).call(zoom.transform, d3.zoomIdentity))

  let us, canada
  try {
    ;[us, canada] = await Promise.all([
      d3.json('https://cdn.jsdelivr.net/npm/us-atlas@3/states-10m.json'),
      d3.json('/data/canada-provinces-50m.geojson'),
    ])
  } catch (err) {
    console.error('Failed to load territory map data', err)
    svg.remove()
    showMapError()
    return
  }

  const placeholder = document.getElementById('tmMapPlaceholder')
  if (placeholder) placeholder.remove()

  usFeatures = topojson.feature(us, us.objects.states).features.map(feature => ({
    ...feature,
    properties: {
      ...feature.properties,
      code: fipsToTerritory[String(feature.id).padStart(2, '0')],
    },
  })).filter(feature => feature.properties.code)

  canadaFeatures = canada.features
  renderScope()
  renderLegend()
}

function renderScope() {
  if (!g) return
  g.selectAll('*').remove()
  defs.selectAll('linearGradient.split-grad').remove()

  if (currentScope === 'Canada') {
    renderGeoScope(canadaFeatures, d3.geoMercator())
  } else if (currentScope === 'EMEA' || currentScope === 'APAC') {
    renderGlobalScope(globalRegions.find(region => region.name === currentScope))
  } else {
    renderGeoScope(usFeatures, d3.geoAlbersUsa())
  }

  colorMap()
  resetHighlight()
}

function renderGeoScope(features, projection) {
  const featureCollection = { type: 'FeatureCollection', features }
  pathGen = d3.geoPath().projection(projection.fitExtent([[18, 18], [930, 570]], featureCollection))

  g.selectAll('path.territory-shape')
    .data(features)
    .join('path')
    .attr('class', 'territory-shape state-path')
    .attr('d', pathGen)
    .attr('data-territory', d => d.properties.code)
    .on('mouseenter', (e, d) => { if (!isPanning) onTerritoryEnter(e, d.properties.code) })
    .on('mousemove', e => { if (!isPanning && !detailPaneTerritory) moveTooltip(e) })
    .on('mouseleave', () => { if (!isPanning) hideTooltip() })
    .on('click', (e, d) => { if (!isPanning) onTerritoryTap(e, d.properties.code) })

  g.selectAll('text.territory-label')
    .data(features)
    .join('text')
    .attr('class', d => 'territory-label state-label' + (smallLabels.includes(d.properties.code) ? ' small' : ''))
    .attr('x', d => pathGen.centroid(d)[0])
    .attr('y', d => pathGen.centroid(d)[1] + 4)
    .text(d => d.properties.code.replace(/^(US|CA)-/, ''))
}

function renderGlobalScope(region) {
  if (!region) return

  const block = { ...region, x: 150, y: 120, width: 660, height: 330 }
  g.selectAll('rect.global-region')
    .data([block])
    .join('rect')
    .attr('class', 'territory-shape global-region')
    .attr('data-territory', d => d.code)
    .attr('x', d => d.x)
    .attr('y', d => d.y)
    .attr('width', d => d.width)
    .attr('height', d => d.height)
    .attr('rx', 10)
    .on('mouseenter', (e, d) => { if (!isPanning) onTerritoryEnter(e, d.code) })
    .on('mousemove', e => { if (!isPanning && !detailPaneTerritory) moveTooltip(e) })
    .on('mouseleave', () => { if (!isPanning) hideTooltip() })
    .on('click', (e, d) => { if (!isPanning) onTerritoryTap(e, d.code) })

  g.selectAll('text.global-label')
    .data([block])
    .join('text')
    .attr('class', 'territory-label global-label')
    .attr('x', d => d.x + d.width / 2)
    .attr('y', d => d.y + d.height / 2 + 9)
    .attr('text-anchor', 'middle')
    .attr('fill', '#fff')
    .attr('font-size', 30)
    .attr('font-weight', 700)
    .attr('font-family', 'system-ui, sans-serif')
    .style('pointer-events', 'none')
    .text(d => d.name)
}

function onTerritoryEnter(e, territory) {
  if (isTouch || detailPaneTerritory) return
  if (territory) showTooltip(e, territory)
}

function onTerritoryTap(e, territory) {
  e.stopPropagation()
  if (!territory) return
  hideTooltip()
  if (lockedHighlight) {
    lockedHighlight = null
    resetHighlight()
    renderLegend()
  }
  if (detailPaneTerritory === territory) {
    closeDetailPane()
  } else {
    showDetailPane(territory)
  }
}

function colorMap() {
  const mapObj = allTerritories[currentView] || {}
  const colors = allColors[currentView] || {}

  defs.selectAll('linearGradient.split-grad').remove()
  g.selectAll('.territory-shape').attr('fill', function () {
    const territory = this.getAttribute('data-territory')
    const value = mapObj[territory]
    if (!value) return '#222'

    if (Array.isArray(value)) {
      const gradId = 'split-' + territory.replace(/[^a-z0-9]/gi, '-')
      const bbox = this.getBBox()
      const angle = Number(value.find(entry => entry.splitAngle != null)?.splitAngle ?? angleForDirection(value.find(entry => entry.splitDirection)?.splitDirection))
      const vector = gradientVector(bbox, angle)
      const grad = defs.append('linearGradient')
        .attr('class', 'split-grad')
        .attr('id', gradId)
        .attr('gradientUnits', 'userSpaceOnUse')
        .attr('x1', vector.x1)
        .attr('y1', vector.y1)
        .attr('x2', vector.x2)
        .attr('y2', vector.y2)
      const percents = normalizedSplitPercents(value)
      let offset = 0
      value.forEach((entry, index) => {
        const color = colors[entry.key] || '#333'
        grad.append('stop').attr('offset', offset + '%').attr('stop-color', color)
        offset += percents[index]
        grad.append('stop').attr('offset', offset + '%').attr('stop-color', color)
      })
      return `url(#${gradId})`
    }
    return colors[value] || '#333'
  })
}

function normalizedSplitPercents(entries) {
  const explicit = entries.map(entry => Number(entry.splitPercent || 0))
  const total = explicit.reduce((sum, value) => sum + value, 0)

  if (entries.length > 0 && total === 100 && explicit.every(value => value > 0)) {
    return explicit
  }

  const equal = Math.floor(100 / entries.length)
  const remainder = 100 - (equal * entries.length)

  return entries.map((_, index) => equal + (index === 0 ? remainder : 0))
}

function angleForDirection(direction) {
  if (direction === 'north_south') return 90
  if (direction === 'diagonal_down') return 45
  if (direction === 'diagonal_up') return 135
  return 0
}

function gradientVector(bbox, angle) {
  const radians = angle * Math.PI / 180
  const dx = Math.cos(radians)
  const dy = Math.sin(radians)
  const length = Math.abs(bbox.width * dx) + Math.abs(bbox.height * dy)
  const cx = bbox.x + bbox.width / 2
  const cy = bbox.y + bbox.height / 2

  return {
    x1: cx - dx * length / 2,
    y1: cy - dy * length / 2,
    x2: cx + dx * length / 2,
    y2: cy + dy * length / 2,
  }
}

function setView(view) {
  lockedHighlight = null
  currentView = view
  colorMap()
  renderLegend()
  resetHighlight()
}

function setScope(scope) {
  lockedHighlight = null
  detailPaneTerritory = null
  currentScope = scope
  closeDetailPane()
  renderScope()
  renderLegend()
}

function renderLegend() {
  const legend = document.getElementById('tmLegend')
  legend.replaceChildren()

  const mapObj = allTerritories[currentView] || {}
  const colors = allColors[currentView] || {}

  const seen = new Set()
  const entries = []
  for (const [, value] of Object.entries(mapObj)) {
    const keys = Array.isArray(value) ? value.map(entry => entry.key) : [value]
    keys.forEach(key => {
      if (!seen.has(key)) {
        seen.add(key)
        entries.push({ key, territories: getTerritoriesForKey(mapObj, key) })
      }
    })
  }
  entries.sort((a, b) => (people[a.key]?.name || '').localeCompare(people[b.key]?.name || ''))

  const roleLabel = (allRoles.find(role => role.key === currentView) || {}).label || currentView
  legend.appendChild(el('h3', 'text-sm mb-3.5 text-ecoGreen uppercase tracking-wider', roleLabel))

  if (lockedHighlight && people[lockedHighlight]) {
    const chip = el('button', 'w-full mb-3 flex items-center justify-between gap-2 px-3 py-2 bg-ecoGreen/15 border border-ecoGreen/60 rounded-md text-left hover:bg-ecoGreen/25 transition-colors')
    chip.setAttribute('type', 'button')
    chip.setAttribute('aria-label', 'Clear filter')
    const label = el('div', 'text-[12px] text-white')
    label.appendChild(document.createTextNode('Showing only '))
    label.appendChild(el('span', 'font-semibold', people[lockedHighlight].name))
    chip.appendChild(label)
    chip.appendChild(el('span', 'text-ecoGreen text-base leading-none', 'x'))
    chip.addEventListener('click', () => clearLock())
    legend.appendChild(chip)
  }

  entries.forEach(({ key, territories }) => {
    const person = people[key]
    if (!person) return
    const color = colors[key] || '#444'
    const item = el('div', 'flex items-start gap-2.5 py-1.5 px-2 rounded-md cursor-pointer transition-colors hover:bg-[#1a2d4a] mb-0.5 tm-legend-item')
    item.dataset.key = key
    if (lockedHighlight === key) item.classList.add('tm-locked')

    const swatch = el('div', 'w-3.5 h-3.5 rounded-full shrink-0 mt-0.5')
    swatch.style.background = color
    item.appendChild(swatch)

    const info = el('div', 'flex-1 min-w-0')
    info.appendChild(el('div', 'text-[13px] font-semibold text-white', person.name))
    if (person.email) info.appendChild(el('div', 'text-[11px] text-[#6688aa] mt-px', person.email))
    if (person.phone) info.appendChild(el('div', 'text-[11px] text-[#6688aa] mt-px', person.phone))
    info.appendChild(el('div', 'text-[11px] text-paleSky/30 mt-0.5', territories.join(', ')))
    item.appendChild(info)

    item.addEventListener('mouseenter', () => { if (!lockedHighlight) highlightTerritory(key) })
    item.addEventListener('mouseleave', () => { if (!lockedHighlight) resetHighlight() })
    item.addEventListener('click', () => toggleHighlight(key))
    legend.appendChild(item)
  })
}

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
  const mapObj = allTerritories[currentView] || {}
  g.selectAll('.territory-shape').each(function () {
    const territory = this.getAttribute('data-territory')
    const keys = getAllKeys(mapObj, territory)
    const match = keys.includes(key)
    d3.select(this)
      .style('opacity', match ? 1 : 0.2)
      .style('filter', match ? 'brightness(1.3)' : 'none')
      .style('stroke', match ? '#fff' : '#0a1628')
      .style('stroke-width', match ? 2 : 1)
  })
  document.querySelectorAll('.tm-legend-item').forEach(item => {
    item.classList.toggle('highlight', item.dataset.key === key)
  })
}

function resetHighlight() {
  if (lockedHighlight) return
  g.selectAll('.territory-shape')
    .style('opacity', null)
    .style('filter', null)
    .style('stroke', null)
    .style('stroke-width', null)
  document.querySelectorAll('.tm-legend-item').forEach(item => item.classList.remove('highlight'))
}

function showTooltip(e, territory) {
  const tt = document.getElementById('tmTooltip')
  tt.replaceChildren()
  tt.appendChild(el('div', 'text-xl font-bold mb-3', `${territoryNames[territory] || territory} (${territory})`))

  getRolesForTerritory(territory).forEach(({ label, entries }) => {
    entries.forEach(entry => {
      const person = people[entry.personKey]
      if (!person) return
      const wrap = el('div', 'mb-2.5 last:mb-0')
      wrap.appendChild(el('div', 'text-[10px] uppercase tracking-widest text-ecoGreen mb-0.5', label))
      wrap.appendChild(el('div', 'text-[15px] font-semibold', person.name))
      if (entry.region) wrap.appendChild(el('div', 'text-[11px] text-ecoGreen italic', entry.region))
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
  let x = e.clientX + 16
  let y = e.clientY + 16
  if (x + rect.width > window.innerWidth - 10) x = e.clientX - rect.width - 16
  if (y + rect.height > window.innerHeight - 10) y = e.clientY - rect.height - 16
  tt.style.left = x + 'px'
  tt.style.top = y + 'px'
}

function hideTooltip() {
  document.getElementById('tmTooltip').style.display = 'none'
}

function getTerritoriesForPerson(personKey) {
  const territories = []
  allRoles.forEach(({ key, label }) => {
    const mapObj = allTerritories[key]
    if (!mapObj) return
    for (const [territory, value] of Object.entries(mapObj)) {
      if (Array.isArray(value)) {
        value.forEach(entry => {
          if (entry.key === personKey) territories.push({ territory, label, region: entry.region })
        })
      } else if (value === personKey) {
        territories.push({ territory, label })
      }
    }
  })
  return territories
}

function buildDetailElement(territory) {
  const wrap = document.createDocumentFragment()
  const header = el('div', 'px-6 pt-5 pb-4 border-b border-[#1e3050] flex justify-between items-start sticky top-0 bg-[#101f35] z-10')
  header.appendChild(el('div', 'text-[22px] font-bold', `${territoryNames[territory] || territory} (${territory})`))

  const closeBtn = el('button', 'text-[#8899aa] text-2xl cursor-pointer leading-none pl-3 hover:text-white border-none bg-transparent', 'x')
  closeBtn.setAttribute('aria-label', 'Close details')
  closeBtn.addEventListener('click', () => window.dispatchEvent(new CustomEvent('close-detail')))
  header.appendChild(closeBtn)
  wrap.appendChild(header)

  const body = el('div', 'px-6 pt-2 pb-6 flex-1')
  getRolesForTerritory(territory).forEach(({ label, entries }) => {
    const section = el('div', 'mb-5 last:mb-0')
    section.appendChild(el('div', 'text-[10px] uppercase tracking-widest text-ecoGreen mb-2 pb-1 border-b border-[#1e3050]', label))

    entries.forEach(entry => {
      const person = people[entry.personKey]
      if (!person) return
      const personWrap = el('div', 'mb-3.5 last:mb-0')
      if (entry.region) personWrap.appendChild(el('div', 'text-[11px] text-ecoGreen italic mb-0.5', entry.region))
      personWrap.appendChild(el('div', 'text-base font-semibold mb-1', person.name))

      if (person.email) {
        const emailDiv = el('div', 'text-[13px] text-[#8899aa] mt-1')
        const link = el('a', 'text-[#8899aa] no-underline hover:text-ecoGreen hover:underline', person.email)
        link.href = 'mailto:' + person.email
        emailDiv.appendChild(link)
        personWrap.appendChild(emailDiv)
      }
      if (person.phone) {
        const phoneDiv = el('div', 'text-[13px] text-[#8899aa] mt-1')
        const link = el('a', 'text-[#8899aa] no-underline hover:text-ecoGreen hover:underline', person.phone)
        link.href = 'tel:' + person.phone.replace(/[^+\d]/g, '')
        phoneDiv.appendChild(link)
        personWrap.appendChild(phoneDiv)
      }

      const otherTerritories = [...new Set(getTerritoriesForPerson(entry.personKey)
        .filter(item => item.territory !== territory)
        .map(item => item.territory))]
      if (otherTerritories.length > 0) {
        const territoryList = el('div', 'mt-2')
        territoryList.appendChild(el('div', 'text-[10px] uppercase tracking-wider text-paleSky/30 mb-1', 'Other territories'))
        territoryList.appendChild(el('div', 'text-xs text-[#6688aa] leading-relaxed', otherTerritories.join(', ')))
        personWrap.appendChild(territoryList)
      }

      section.appendChild(personWrap)
    })

    body.appendChild(section)
  })

  wrap.appendChild(body)
  return wrap
}

function showDetailPane(territory) {
  hideTooltip()
  const target = document.getElementById('tmDetailPane')
  if (target) target.replaceChildren(buildDetailElement(territory))
  detailPaneTerritory = territory
  window.dispatchEvent(new CustomEvent('open-detail'))
}

function closeDetailPane() {
  detailPaneTerritory = null
  window.dispatchEvent(new CustomEvent('close-detail'))
}

function clearDetailState() {
  detailPaneTerritory = null
}

function showNoResults(query) {
  const legend = document.getElementById('tmLegend')
  if (!legend) return
  legend.replaceChildren()
  legend.appendChild(el('h3', 'text-sm mb-3.5 text-ecoGreen uppercase tracking-wider', 'No results'))
  const wrap = el('div', 'flex flex-col items-center text-center gap-2 py-4 text-paleSky/70')
  wrap.appendChild(el('p', 'text-sm', `No territories or people match "${query}"`))
  wrap.appendChild(el('p', 'text-[11px] text-paleSky/40', 'Try a territory name or a sales rep name.'))
  legend.appendChild(wrap)
}

function onSearch(value) {
  lockedHighlight = null
  value = value.trim()
  if (!value) {
    resetHighlight()
    renderLegend()
    return
  }

  const lower = value.toLowerCase()
  const mapObj = allTerritories[currentView] || {}
  const matchedKeys = new Set()

  for (const [territory, name] of Object.entries(territoryNames)) {
    if (territory.toLowerCase().includes(lower) || name.toLowerCase().includes(lower)) {
      getAllKeys(mapObj, territory).forEach(key => matchedKeys.add(key))
    }
  }
  for (const [key, person] of Object.entries(people)) {
    if (person.name.toLowerCase().includes(lower)) matchedKeys.add(key)
  }

  if (matchedKeys.size === 0) {
    g.selectAll('.territory-shape')
      .style('opacity', 0.15)
      .style('filter', 'none')
      .style('stroke', '#0a1628')
      .style('stroke-width', 1)
    showNoResults(value)
    return
  }

  renderLegend()
  g.selectAll('.territory-shape').each(function () {
    const territory = this.getAttribute('data-territory')
    const keys = getAllKeys(mapObj, territory)
    const match = keys.some(key => matchedKeys.has(key))
    d3.select(this)
      .style('opacity', match ? 1 : 0.2)
      .style('filter', match ? 'brightness(1.3)' : 'none')
      .style('stroke', match ? '#fff' : '#0a1628')
      .style('stroke-width', match ? 2 : 1)
  })
  document.querySelectorAll('.tm-legend-item').forEach(item => {
    item.classList.toggle('highlight', matchedKeys.has(item.dataset.key))
  })
}

window.TerritoryMap = { setView, setScope, onSearch, clearDetailState, clearLock }

init().catch(err => {
  console.error('Territory map init failed', err)
  showMapError()
})
