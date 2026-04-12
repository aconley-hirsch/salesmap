<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'US Sales Team Territory Map'])
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://d3js.org/topojson.v3.min.js"></script>
    @vite('resources/js/territory-map.js')
    {{-- D3-specific SVG styles that can't be expressed with Tailwind --}}
    <style>
        .state-path {
            stroke: #0a1628;
            stroke-width: 1;
            cursor: pointer;
            transition: opacity 0.15s, filter 0.15s;
        }
        .state-path:hover {
            opacity: 0.82;
            filter: brightness(1.25);
            stroke: #fff;
            stroke-width: 1.8;
        }
        .state-label {
            pointer-events: none;
            fill: #fff;
            font-size: 11px;
            font-weight: 600;
            font-family: system-ui;
            text-anchor: middle;
            paint-order: stroke;
            stroke: rgba(0, 0, 0, 0.6);
            stroke-width: 2.5px;
        }
        .state-label.small { font-size: 9px; }

        /* Locked legend item — clear visual selection state */
        .tm-legend-item.tm-locked {
            background: rgba(146, 212, 0, 0.15);
            outline: 1px solid rgba(146, 212, 0, 0.7);
        }

        /* Tooltip — fixed position, follows cursor via JS */
        #tmTooltip {
            position: fixed;
            pointer-events: none;
            z-index: 100;
            display: none;
        }

        /* Mobile: hide tooltip */
        @@media (max-width: 768px) {
            #tmTooltip { display: none !important; }
        }
    </style>
</head>
<body>
<div class="min-h-screen bg-gradient-to-b from-midnightSignal to-deepTeal font-montserrat text-paleSky p-3 sm:p-6"
     x-data="{
        currentView: 'rsm',
        search: '',
        detailOpen: false,
        setView(view) {
            this.currentView = view;
            this.search = '';
            this.detailOpen = false;
            window.TerritoryMap.clearDetailState();
            window.TerritoryMap.clearLock();
            window.TerritoryMap.setView(view);
            // Clear any active search highlight in the JS layer too
            window.TerritoryMap.onSearch('');
        },
        onSearch(val) {
            window.TerritoryMap.onSearch(val);
        },
        openDetail() {
            this.detailOpen = true;
        },
        closeDetail() {
            this.detailOpen = false;
            window.TerritoryMap.clearDetailState();
        },
        onEscape() {
            // Esc closes the detail pane if open, otherwise clears any locked highlight
            if (this.detailOpen) {
                this.closeDetail();
            } else {
                window.TerritoryMap.clearLock();
            }
        }
     }"
     x-on:open-detail.window="openDetail()"
     x-on:close-detail.window="closeDetail()"
     x-on:keydown.escape.window="onEscape()">

    <x-header title="Territory Map" subtitle="US SALES TEAM" />
    <x-page-nav current="territory-map" />

    {{-- Controls --}}
    <div class="flex gap-2 px-2 sm:px-6 py-3 flex-wrap items-center">
        <label class="text-xs text-paleSky/60 mr-1 hidden sm:inline">Color by:</label>

        @foreach($mapData['roles'] as $role)
            <button
                @click="setView('{{ $role['key'] }}')"
                :class="currentView === '{{ $role['key'] }}'
                    ? 'bg-ecoGreen text-midnightSignal border-ecoGreen font-semibold'
                    : 'bg-[#12213a] text-paleSky/80 border-[#2a3a4e] hover:bg-[#1a2d4a] hover:border-[#3a5a7e]'"
                class="px-3 sm:px-4 py-2 border rounded-lg text-xs sm:text-sm transition-all cursor-pointer">
                {{ $role['label'] }}
            </button>
        @endforeach

        <div class="flex-1"></div>

        <div class="relative w-full sm:w-auto order-first sm:order-last">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-paleSky/40" viewBox="0 0 24 24" fill="currentColor">
                <path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            <input
                type="text"
                x-model="search"
                x-on:input="onSearch(search)"
                placeholder="Search state or person..."
                class="w-full sm:w-56 pl-8 pr-3 py-2 bg-[#12213a] border border-[#2a3a4e] rounded-lg text-white text-sm placeholder-white/30 outline-none focus:border-ecoGreen"
            />
        </div>
    </div>

    {{-- Map + Legend --}}
    <div class="flex items-start px-2 sm:px-5 pb-5 gap-5 flex-wrap">
        <div class="flex-1 min-w-full lg:min-w-[650px]" id="tmMapWrap">
            {{-- Loading skeleton replaced by D3 once the map renders --}}
            <div id="tmMapPlaceholder" class="aspect-[8/5] w-full flex items-center justify-center bg-[#0a1828]/40 border border-[#1e3050] rounded-xl">
                <div class="flex flex-col items-center gap-3 text-paleSky/60">
                    <svg class="w-8 h-8 animate-spin text-ecoGreen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                    <p class="text-xs uppercase tracking-wider">Loading map…</p>
                </div>
            </div>
        </div>
        <div class="w-full lg:w-[300px] shrink-0 bg-[#101f35] rounded-xl p-5 border border-[#1e3050] max-h-[calc(100vh-200px)] overflow-y-auto lg:max-h-[calc(100vh-200px)]" id="tmLegend">
            {{-- Legend rendered by JS --}}
        </div>
    </div>

    {{-- Tooltip (positioned by JS) --}}
    <div id="tmTooltip" class="bg-[#12213a]/95 border border-ecoGreen rounded-xl px-5 py-4 min-w-[290px] shadow-2xl backdrop-blur-sm"></div>

    {{-- Detail pane (visibility owned by Alpine, content owned by JS) --}}
    <div
        id="tmDetailPane"
        x-show="detailOpen"
        x-on:click.outside="closeDetail()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full md:translate-x-full translate-y-full md:translate-y-0"
        x-transition:enter-end="translate-x-0 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0 translate-y-0"
        x-transition:leave-end="translate-x-full md:translate-x-full translate-y-full md:translate-y-0"
        x-cloak
        class="fixed z-[200] overflow-y-auto flex flex-col bg-[#101f35] shadow-[-4px_0_30px_rgba(0,0,0,0.5)]
               bottom-0 inset-x-0 max-h-[80vh] rounded-t-2xl border-t-2 border-ecoGreen
               md:top-0 md:right-0 md:bottom-auto md:left-auto md:w-[380px] md:h-full md:max-h-full md:rounded-none md:border-t-0 md:border-l-2"
    ></div>
</div>

<script>
    window.territoryMapData = @json($mapData);
</script>
@fluxScripts
</body>
</html>
