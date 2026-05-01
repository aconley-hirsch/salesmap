// ── Admin Territory Map ──
// Renders a single role's assignments and dispatches territory clicks to Livewire.

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

let svg, defs, g, pathGen
let mapData = { people: {}, territories: {}, colors: {} }
let pendingData = null
let currentScope = 'US'
let usFeatures = []
let canadaFeatures = []

async function init() {
  const wrap = document.getElementById('adminMapWrap')
  if (!wrap) return
  if (wrap.querySelector('svg')) return

  svg = null
  defs = null
  g = null
  pathGen = null

  const width = 960, height = 600
  svg = d3.select(wrap).append('svg')
    .attr('viewBox', `0 0 ${width} ${height}`)
    .style('width', '100%')
    .style('height', 'auto')
    .style('background', 'transparent')

  defs = svg.append('defs')
  g = svg.append('g')

  const [us, canada] = await Promise.all([
    d3.json('https://cdn.jsdelivr.net/npm/us-atlas@3/states-10m.json'),
    d3.json('/data/canada-provinces-50m.geojson'),
  ])

  usFeatures = topojson.feature(us, us.objects.states).features.map(feature => ({
    ...feature,
    properties: {
      ...feature.properties,
      code: fipsToTerritory[String(feature.id).padStart(2, '0')],
    },
  })).filter(feature => feature.properties.code)
  canadaFeatures = canada.features
  renderScope()

  const queued = pendingData || window.__adminMapPending
  if (queued) {
    pendingData = null
    window.__adminMapPending = null
    update(queued)
  }
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

  recolor()
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
    .attr('stroke', '#0a1628')
    .attr('stroke-width', 1)
    .style('cursor', 'pointer')
    .on('mouseenter', (e, d) => onTerritoryEnter(e, d.properties.code))
    .on('mousemove', e => moveTooltip(e))
    .on('mouseleave', () => hideTooltip())
    .on('click', (e, d) => onTerritoryClick(d.properties.code))

  g.selectAll('text.territory-label')
    .data(features)
    .join('text')
    .attr('class', d => 'territory-label state-label' + (smallLabels.includes(d.properties.code) ? ' small' : ''))
    .attr('x', d => pathGen.centroid(d)[0])
    .attr('y', d => pathGen.centroid(d)[1] + 4)
    .attr('text-anchor', 'middle')
    .attr('fill', '#fff')
    .attr('font-size', d => smallLabels.includes(d.properties.code) ? 9 : 11)
    .attr('font-weight', 600)
    .attr('font-family', 'system-ui, sans-serif')
    .attr('paint-order', 'stroke')
    .attr('stroke', 'rgba(0,0,0,0.6)')
    .attr('stroke-width', '2.5px')
    .style('pointer-events', 'none')
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
    .attr('stroke', '#0a1628')
    .attr('stroke-width', 1)
    .style('cursor', 'pointer')
    .on('mouseenter', (e, d) => onTerritoryEnter(e, d.code))
    .on('mousemove', e => moveTooltip(e))
    .on('mouseleave', () => hideTooltip())
    .on('click', (e, d) => onTerritoryClick(d.code))

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

function update(data) {
  if (!data) return
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

  g.selectAll('.territory-shape').attr('fill', function () {
    const territory = this.getAttribute('data-territory')
    const val = mapData.territories ? mapData.territories[territory] : null
    if (!val) return '#1e2f48'

    if (Array.isArray(val)) {
      const gradId = 'admin-split-' + territory.replace(/[^a-z0-9]/gi, '-')
      const bbox = this.getBBox()
      const angle = Number(val.find(entry => entry.splitAngle != null)?.splitAngle ?? angleForDirection(val.find(entry => entry.splitDirection)?.splitDirection))
      const vector = gradientVector(bbox, angle)
      const grad = defs.append('linearGradient')
        .attr('class', 'split-grad')
        .attr('id', gradId)
        .attr('gradientUnits', 'userSpaceOnUse')
        .attr('x1', vector.x1)
        .attr('y1', vector.y1)
        .attr('x2', vector.x2)
        .attr('y2', vector.y2)
      const percents = normalizedSplitPercents(val)
      let offset = 0
      val.forEach((entry, index) => {
        const color = mapData.colors[entry.key] || '#444'
        grad.append('stop').attr('offset', offset + '%').attr('stop-color', color)
        offset += percents[index]
        grad.append('stop').attr('offset', offset + '%').attr('stop-color', color)
      })
      return `url(#${gradId})`
    }

    return mapData.colors[val.key] || '#444'
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

function onTerritoryEnter(e, territory) {
  const val = mapData.territories ? mapData.territories[territory] : null
  let body = `<div class="font-bold text-white mb-1">${territoryNames[territory] || territory}</div>`
  if (!val) {
    body += '<div class="text-paleSky/60">Unassigned</div>'
  } else if (Array.isArray(val)) {
    val.forEach(entry => {
      const person = mapData.people[entry.key]
      if (person) body += `<div>${person.name}${entry.region ? ` <span class="text-paleSky/60">(${entry.region})</span>` : ''}</div>`
    })
  } else {
    const person = mapData.people[val.key]
    if (person) body += `<div>${person.name}</div>`
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
  tt.style.left = (e.clientX - wrapRect.left + 12) + 'px'
  tt.style.top = (e.clientY - wrapRect.top + 12) + 'px'
}

function hideTooltip() {
  const tt = document.getElementById('adminMapTooltip')
  if (tt) tt.classList.add('hidden')
}

function onTerritoryClick(territory) {
  if (!territory) return
  hideTooltip()
  if (window.Livewire) {
    window.Livewire.dispatch('territory-clicked', { territoryCode: territory })
  }
}

function setScope(scope) {
  currentScope = scope
  hideTooltip()
  renderScope()
}

window.AdminTerritoryMap = { update, init, setScope }

function bootIfPresent() {
  if (document.getElementById('adminMapWrap')) {
    init().catch(err => console.error('Admin territory map init failed', err))
  }
}

document.addEventListener('livewire:initialized', bootIfPresent)
document.addEventListener('livewire:navigated', bootIfPresent)
if (document.readyState !== 'loading') {
  bootIfPresent()
}
