// ── Admin Territory Map ──
// Renders a single role's assignments and dispatches click events to Livewire.

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

let svg, defs, g, pathGen
let stateFeatures = []
let mapData = { people: {}, states: {}, colors: {} }
let pendingData = null

async function init() {
  const wrap = document.getElementById('adminMapWrap')
  if (!wrap) return

  // Reset module state — the previous body (if any) was swapped out by wire:navigate
  // and the prior d3 selections point at detached nodes. If an SVG is already in
  // the new wrap (full page reload), bail out.
  if (wrap.querySelector('svg')) return

  svg = null
  defs = null
  g = null
  pathGen = null

  const width = 960, height = 600
  svg = d3.select(wrap)
    .append('svg')
    .attr('viewBox', `0 0 ${width} ${height}`)
    .style('width', '100%')
    .style('height', 'auto')
    .style('background', 'transparent')

  defs = svg.append('defs')
  g = svg.append('g')

  const us = await d3.json('https://cdn.jsdelivr.net/npm/us-atlas@3/states-10m.json')
  stateFeatures = topojson.feature(us, us.objects.states).features
  const projection = d3.geoAlbersUsa().fitSize([width, height], topojson.feature(us, us.objects.states))
  pathGen = d3.geoPath().projection(projection)

  g.selectAll('path.state-path')
    .data(stateFeatures)
    .join('path')
    .attr('class', 'state-path')
    .attr('d', pathGen)
    .attr('data-state', d => fipsToState[String(d.id).padStart(2, '0')] || '')
    .attr('stroke', '#0a1628')
    .attr('stroke-width', 1)
    .style('cursor', 'pointer')
    .on('mouseenter', (e, d) => onStateEnter(e, d))
    .on('mousemove', e => moveTooltip(e))
    .on('mouseleave', () => hideTooltip())
    .on('click', (e, d) => onStateClick(e, d))

  g.selectAll('text.state-label')
    .data(stateFeatures)
    .join('text')
    .attr('class', d => {
      const st = fipsToState[String(d.id).padStart(2, '0')]
      return 'state-label' + (['DC', 'DE', 'CT', 'RI', 'NH', 'VT', 'MA', 'NJ', 'MD', 'HI'].includes(st) ? ' small' : '')
    })
    .attr('x', d => pathGen.centroid(d)[0])
    .attr('y', d => pathGen.centroid(d)[1] + 4)
    .attr('text-anchor', 'middle')
    .attr('fill', '#fff')
    .attr('font-size', d => {
      const st = fipsToState[String(d.id).padStart(2, '0')]
      return ['DC', 'DE', 'CT', 'RI', 'NH', 'VT', 'MA', 'NJ', 'MD', 'HI'].includes(st) ? 9 : 11
    })
    .attr('font-weight', 600)
    .attr('font-family', 'system-ui, sans-serif')
    .attr('paint-order', 'stroke')
    .attr('stroke', 'rgba(0,0,0,0.6)')
    .attr('stroke-width', '2.5px')
    .style('pointer-events', 'none')
    .text(d => fipsToState[String(d.id).padStart(2, '0')] || '')

  // If a render call was queued before init finished, apply the latest data now.
  // pendingData is set if update() was called before g existed; __adminMapPending
  // is set if Alpine x-init ran before this module finished loading.
  const queued = pendingData || window.__adminMapPending
  if (queued) {
    pendingData = null
    window.__adminMapPending = null
    update(queued)
  }
}

function update(data) {
  if (!data) return

  // After wire:navigate, our g reference still points at the previous (now
  // detached) SVG node. Detect that case and fall through to the pending-queue
  // path so init() can pick up the data once it builds a fresh SVG.
  if (g && !document.body.contains(g.node())) {
    svg = null
    defs = null
    g = null
    pathGen = null
  }

  if (!g) {
    pendingData = data
    return
  }

  mapData = data
  recolor()
}

function recolor() {
  if (!g) return
  defs.selectAll('linearGradient.split-grad').remove()

  g.selectAll('path.state-path').attr('fill', function () {
    const st = this.getAttribute('data-state')
    const val = mapData.states ? mapData.states[st] : null
    if (!val) return '#1e2f48'

    if (Array.isArray(val)) {
      const vertical = ['TN'].includes(st)
      const gradId = 'admin-split-' + st
      const bbox = this.getBBox()
      const grad = defs.append('linearGradient')
        .attr('class', 'split-grad')
        .attr('id', gradId)
        .attr('gradientUnits', 'userSpaceOnUse')
        .attr('x1', bbox.x)
        .attr('y1', bbox.y)
        .attr('x2', vertical ? bbox.x + bbox.width : bbox.x)
        .attr('y2', vertical ? bbox.y : bbox.y + bbox.height)
      grad.append('stop').attr('offset', '50%').attr('stop-color', mapData.colors[val[0].key] || '#444')
      grad.append('stop').attr('offset', '50%').attr('stop-color', mapData.colors[val[1].key] || '#444')
      return `url(#${gradId})`
    }

    return mapData.colors[val.key] || '#444'
  })
}

function onStateEnter(e, d) {
  const st = fipsToState[String(d.id).padStart(2, '0')]
  if (!st) return
  const val = mapData.states ? mapData.states[st] : null
  let body = `<div class="font-bold text-white mb-1">${stateNames[st] || st}</div>`
  if (!val) {
    body += `<div class="text-paleSky/60">Unassigned</div>`
  } else if (Array.isArray(val)) {
    val.forEach(entry => {
      const p = mapData.people[entry.key]
      if (p) body += `<div>${p.name}${entry.region ? ` <span class="text-paleSky/60">(${entry.region})</span>` : ''}</div>`
    })
  } else {
    const p = mapData.people[val.key]
    if (p) body += `<div>${p.name}</div>`
  }
  showTooltip(e, body)
}

function showTooltip(e, html) {
  const tt = document.getElementById('adminMapTooltip')
  if (!tt) return
  tt.innerHTML = html
  tt.classList.remove('hidden')
  moveTooltip(e)
}

function moveTooltip(e) {
  const tt = document.getElementById('adminMapTooltip')
  if (!tt || tt.classList.contains('hidden')) return
  const wrap = document.getElementById('adminMapWrap')
  if (!wrap) return
  const wrapRect = wrap.getBoundingClientRect()
  const x = e.clientX - wrapRect.left + 12
  const y = e.clientY - wrapRect.top + 12
  tt.style.left = x + 'px'
  tt.style.top = y + 'px'
}

function hideTooltip() {
  const tt = document.getElementById('adminMapTooltip')
  if (tt) tt.classList.add('hidden')
}

function onStateClick(e, d) {
  const st = fipsToState[String(d.id).padStart(2, '0')]
  if (!st) return
  hideTooltip()
  if (window.Livewire) {
    window.Livewire.dispatch('state-clicked', { stateCode: st })
  }
}

window.AdminTerritoryMap = { update, init }

function bootIfPresent() {
  if (document.getElementById('adminMapWrap')) {
    init().catch(err => console.error('Admin territory map init failed', err))
  }
}

// First page load
document.addEventListener('livewire:initialized', bootIfPresent)
// Subsequent SPA navigations via wire:navigate — fires on every navigation
document.addEventListener('livewire:navigated', bootIfPresent)
// Fallback in case the script loads after both events have already fired
if (document.readyState !== 'loading') {
  bootIfPresent()
}
