<div class="p-6"
     x-data="{
        mapScope: 'US',
        memberColors: @js(collect($memberCards)->mapWithKeys(fn ($card) => [$card['id'] => $card['color']])),
        setMapScope(scope) {
            this.mapScope = scope;
            if (window.AdminTerritoryMap) window.AdminTerritoryMap.setScope(scope);
        },
        splitPreviewGradient() {
            const rows = ($wire.splitRows || []).filter((row) => row.member_id && row.region);
            if (rows.length === 0) return '#1e2f48';
            const explicit = rows.map((row) => Number(row.percent || 0));
            const total = explicit.reduce((sum, value) => sum + value, 0);
            const percents = total === 100 && explicit.every((value) => value > 0)
                ? explicit
                : rows.map((row, index) => Math.floor(100 / rows.length) + (index === 0 ? 100 - Math.floor(100 / rows.length) * rows.length : 0));
            let offset = 0;
            const stops = rows.flatMap((row, index) => {
                const color = this.memberColors[row.member_id] || '#444';
                const start = offset;
                offset += percents[index];
                return [`${color} ${start}%`, `${color} ${offset}%`];
            });
            return `linear-gradient(${90 + Number($wire.splitAngle || 0)}deg, ${stops.join(', ')})`;
        }
     }"
     x-init="
        const apply = (json) => {
            if (!json) return;
            try {
                const data = JSON.parse(json);
                window.__adminMapPending = data;
                if (window.AdminTerritoryMap) window.AdminTerritoryMap.update(data);
            } catch (e) {}
        };
        apply($wire.mapDataJson);
        $watch('$wire.mapDataJson', apply);
     ">

    {{-- D3 + TopoJSON loaded via @assets so Livewire injects them once into the head --}}
    @assets
        <script src="https://d3js.org/d3.v7.min.js"></script>
        <script src="https://d3js.org/topojson.v3.min.js"></script>
        @vite('resources/js/admin-territory-map.js')
    @endassets

    <style>
        #adminMapWrap .territory-shape {
            transition: opacity 0.15s, filter 0.15s;
        }
        #adminMapWrap .territory-shape:hover {
            opacity: 0.85;
            filter: brightness(1.3);
            stroke: #fff;
            stroke-width: 1.8;
        }
    </style>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Territory Map Editor</h1>
            <p class="text-paleSky/70 text-sm mt-1">Click a person to arm them, then click territories to assign. Changes save instantly.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.sales-team.index') }}" wire:navigate
               class="px-4 py-2 bg-white/10 hover:bg-white/20 text-paleSky text-sm font-medium rounded-lg transition-colors">
                Member Details
            </a>
            <button type="button" wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Member
            </button>
        </div>
    </div>

    {{-- Role tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @foreach($roleTypes as $role)
            <button type="button"
                    wire:click="setRole('{{ $role->value }}')"
                    class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors
                        {{ $activeRole === $role->value
                            ? 'bg-[#00A599] text-white border-[#00A599]'
                            : 'bg-white/5 text-paleSky/70 border-white/10 hover:bg-white/10 hover:text-white' }}">
                {{ $role->label() }}
            </button>
        @endforeach
    </div>

    {{-- Map tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-xs uppercase tracking-wider text-paleSky/50 font-semibold mr-1">Map</span>
        @foreach(['US' => 'United States', 'Canada' => 'Canada', 'EMEA' => 'EMEA', 'APAC' => 'APAC'] as $scope => $label)
            <button type="button"
                    x-on:click="setMapScope('{{ $scope }}')"
                    x-bind:class="mapScope === '{{ $scope }}'
                        ? 'bg-white text-[#0c1d31] border-white'
                        : 'bg-white/5 text-paleSky/70 border-white/10 hover:bg-white/10 hover:text-white'"
                    class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Map + Sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-5 items-start">

        {{-- Map column --}}
        <div class="bg-[#0c1d31] border border-white/10 rounded-xl p-3 relative">
            <div id="adminMapWrap" wire:ignore>
                {{-- D3 renders SVG here --}}
            </div>
            <div id="adminMapTooltip"
                 class="hidden absolute pointer-events-none z-10 bg-[#12213a]/95 border border-[#00A599] rounded-lg px-3 py-2 text-xs text-white shadow-xl"></div>

            @if($armedMemberId)
                @php
                    $armedCard = collect($memberCards)->firstWhere('id', $armedMemberId);
                @endphp
                @if($armedCard)
                    <div class="absolute top-3 left-3 flex items-center gap-2 bg-[#0a1828]/90 backdrop-blur-sm border border-white/20 rounded-lg pl-2 pr-3 py-1.5 text-xs text-white shadow-lg">
                        <span class="w-3 h-3 rounded-full" style="background: {{ $armedCard['color'] }}"></span>
                        <span>Armed: <strong>{{ $armedCard['name'] }}</strong></span>
                        <button type="button" wire:click="armMember({{ $armedMemberId }})"
                                class="ml-1 text-paleSky/60 hover:text-white" title="Disarm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
            @else
                <div class="absolute top-3 left-3 bg-[#0a1828]/90 backdrop-blur-sm border border-white/10 rounded-lg px-3 py-1.5 text-xs text-paleSky/70 shadow-lg">
                    Pick a person from the sidebar to start assigning.
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="bg-[#0c1d31] border border-white/10 rounded-xl p-4 max-h-[calc(100vh-220px)] overflow-y-auto">
            <h2 class="text-xs uppercase tracking-wider text-paleSky/50 font-semibold mb-3">
                {{ \App\Enums\RoleType::from($activeRole)->label() }}
            </h2>

            @if(count($memberCards) === 0)
                <p class="text-sm text-paleSky/50 italic">No members in this discipline yet. Use "Add Member" to create one.</p>
            @else
                <div class="space-y-1.5">
                    @foreach($memberCards as $card)
                        <button type="button"
                                wire:key="card-{{ $card['id'] }}"
                                wire:click="armMember({{ $card['id'] }})"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg border transition-colors text-left
                                    {{ $armedMemberId === $card['id']
                                        ? 'bg-white/10 border-white/30'
                                        : 'border-transparent hover:bg-white/5' }}">
                            <span class="w-4 h-4 rounded-full shrink-0" style="background: {{ $card['color'] }}"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-white font-medium truncate">{{ $card['name'] }}</div>
                                <div class="text-[11px] text-paleSky/50">{{ $card['territory_count'] }} {{ Str::plural('territory', $card['territory_count']) }}</div>
                            </div>
                            @if($armedMemberId === $card['id'])
                                <svg class="w-4 h-4 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Territory action modal --}}
    <flux:modal name="territory-state-modal" class="max-w-lg" wire:close="closeStatePopover">
        @if($modalTerritory)
            @php
                $armedCard = $armedMemberId ? collect($memberCards)->firstWhere('id', $armedMemberId) : null;
            @endphp

            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ \App\Support\Territories::name($modalTerritory) }} ({{ $modalTerritory }})</flux:heading>
                    <flux:subheading>{{ \App\Enums\RoleType::from($activeRole)->label() }}</flux:subheading>
                </div>

                {{-- Current assignments --}}
                <div>
                    <p class="text-xs uppercase tracking-wider text-paleSky/50 font-semibold mb-2">Currently assigned</p>
                    @if(count($modalAssignments) === 0)
                        <p class="text-sm text-paleSky/50 italic">No one assigned to this territory in this discipline.</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach($modalAssignments as $a)
                                <div class="flex items-center gap-3 px-3 py-2 bg-white/5 rounded-lg" wire:key="modal-a-{{ $a['id'] }}">
                                    <span class="w-3 h-3 rounded-full" style="background: {{ $a['color'] }}"></span>
                                    <div class="flex-1">
                                        <div class="text-sm text-white">{{ $a['name'] }}</div>
                                        @if($a['region'])
                                            <div class="text-[11px] text-paleSky/60 italic">{{ $a['region'] }}</div>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="unassignFromTerritory({{ $a['id'] }})"
                                            class="text-paleSky/40 hover:text-red-400 transition-colors" title="Unassign">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                @if(! $splitMode)
                    <div class="space-y-3 pt-2 border-t border-white/10">
                        <div>
                            <label class="block text-xs uppercase tracking-wider text-paleSky/50 font-semibold mb-1.5">Assign to</label>
                            <select wire:model.live="modalSelectedMemberId"
                                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-1 focus:ring-[#00A599]">
                                <option value="">Select a member…</option>
                                @foreach($allMembers as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" wire:click="assignWholeTerritory"
                                @disabled(! $modalSelectedMemberId)
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-[#00A599] hover:bg-[#00A599]/80 disabled:bg-white/10 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors">
                            Assign whole territory
                        </button>

                        <button type="button" wire:click="enterSplitMode"
                                class="w-full px-4 py-2 bg-white/5 hover:bg-white/10 text-paleSky text-sm font-medium rounded-lg transition-colors">
                            Split into regions…
                        </button>
                    </div>
                @else
                    <div class="space-y-3 pt-2 border-t border-white/10">
                        <p class="text-xs uppercase tracking-wider text-paleSky/50 font-semibold">Split assignments</p>

                        <div>
                            <label class="block text-xs uppercase tracking-wider text-paleSky/50 font-semibold mb-1.5">Split direction</label>
                            <select wire:model.live="splitDirection"
                                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-1 focus:ring-[#00A599]">
                                <option value="west_east">West to east</option>
                                <option value="north_south">North to south</option>
                                <option value="diagonal_down">Diagonal 45 degrees</option>
                                <option value="diagonal_up">Diagonal 135 degrees</option>
                                <option value="custom">Custom angle</option>
                            </select>
                            @error('splitDirection') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_180px] gap-3 items-center">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-1.5">
                                    <label class="block text-xs uppercase tracking-wider text-paleSky/50 font-semibold">Angle</label>
                                    <span class="text-xs text-paleSky/70">{{ $splitAngle }}&deg;</span>
                                </div>
                                <input type="range" min="0" max="180" step="5"
                                       wire:model.live="splitAngle"
                                       @disabled($splitDirection !== 'custom')
                                       class="w-full accent-[#00A599] disabled:opacity-40" />
                            </div>
                            <div class="h-24 rounded-lg border border-white/10 overflow-hidden bg-[#1e2f48]"
                                 x-bind:style="{ background: splitPreviewGradient() }"></div>
                        </div>

                        @foreach($splitRows as $i => $row)
                            <div class="grid grid-cols-[1fr_1fr_80px_auto] items-center gap-2" wire:key="split-{{ $i }}">
                                <select wire:model.live="splitRows.{{ $i }}.member_id"
                                        class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-1 focus:ring-[#00A599]">
                                    <option value="">Select member...</option>
                                    @foreach($allMembers as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" wire:model.live="splitRows.{{ $i }}.region"
                                       placeholder="Region label"
                                       class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]" />
                                <input type="number" min="1" max="100" wire:model.live="splitRows.{{ $i }}.percent"
                                       placeholder="%"
                                       class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]" />
                                @if(count($splitRows) > 2)
                                    <button type="button" wire:click="removeSplitRow({{ $i }})"
                                            class="text-paleSky/40 hover:text-red-400 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach

                        @error('splitRows') <p class="text-red-400 text-xs">{{ $message }}</p> @enderror

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" wire:click="addSplitRow"
                                    class="text-xs text-[#00A599] hover:underline">+ Add region</button>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="$set('splitMode', false)"
                                        class="px-3 py-1.5 text-sm text-paleSky/70 hover:text-white">Cancel</button>
                                <button type="button" wire:click="saveSplit"
                                        class="px-4 py-1.5 bg-[#00A599] hover:bg-[#00A599]/80 text-white text-sm font-medium rounded-lg">Save split</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </flux:modal>

    {{-- Quick-create member modal --}}
    <flux:modal name="territory-create-member" class="max-w-md">
        <form wire:submit="createMember" class="space-y-5">
            <div>
                <flux:heading size="lg">Add Sales Team Member</flux:heading>
                <flux:subheading>They'll be added to {{ \App\Enums\RoleType::from($activeRole)->label() }} when you click their first territory.</flux:subheading>
            </div>

            <div>
                <label class="block text-sm text-paleSky/70 mb-1">Name *</label>
                <input type="text" wire:model="newMember.name"
                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                       placeholder="John Doe" />
                @error('newMember.name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-paleSky/70 mb-1">Email</label>
                <input type="email" wire:model="newMember.email"
                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                       placeholder="jdoe@hirschsecure.com" />
                @error('newMember.email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-paleSky/70 mb-1">Phone</label>
                <input type="text" wire:model="newMember.phone"
                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                       placeholder="555.123.4567" />
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:modal.close>
                    <button type="button" class="px-4 py-2 text-sm text-paleSky/70 hover:text-white">Cancel</button>
                </flux:modal.close>
                <button type="submit" class="px-4 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white text-sm font-medium rounded-lg">
                    Create &amp; Arm
                </button>
            </div>
        </form>
    </flux:modal>
</div>
