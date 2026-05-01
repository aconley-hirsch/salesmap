<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.sales-team.index') }}" wire:navigate
           class="text-paleSky/50 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $member ? 'Edit' : 'Add' }} Sales Team Member</h1>
            <p class="text-paleSky/70 text-sm mt-1">{{ $member ? 'Update member details and territory assignments' : 'Create a new team member with territory assignments' }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Member Details Card --}}
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Member Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-paleSky/70 mb-1">Name *</label>
                    <input type="text" wire:model="form.name"
                           class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                           placeholder="John Doe" />
                    @error('form.name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-paleSky/70 mb-1">Role *</label>
                    <select wire:model.live="roleType"
                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]">
                        @foreach($this->roleTypes as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-paleSky/70 mb-1">Email</label>
                    <input type="email" wire:model="form.email"
                           class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                           placeholder="jdoe@hirschsecure.com" />
                    @error('form.email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-paleSky/70 mb-1">Phone</label>
                    <input type="text" wire:model="form.phone"
                           class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                           placeholder="555.123.4567" />
                    @error('form.phone') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-6">
                    <button type="button" wire:click="$toggle('form.is_active')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[#00A599]/50 {{ $form['is_active'] ? 'bg-[#92D400]' : 'bg-white/20' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $form['is_active'] ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                    <span class="text-sm text-paleSky/80">Active</span>
                </div>
            </div>
        </div>

        {{-- Map Color Card --}}
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
            <h2 class="text-lg font-semibold text-white mb-1">Map Color</h2>
            <p class="text-sm text-paleSky/50 mb-4">Colors with a dot are used by other {{ \App\Enums\RoleType::from($roleType)->label() }} members.</p>

            <div class="flex flex-wrap gap-2"
                 x-data="{ hovered: null }"
                 x-on:mouseleave="hovered = null">
                @foreach(\App\Livewire\Admin\SalesTeamMemberForm::PALETTE as $swatch)
                    @php
                        $isSelected = strtolower($color) === strtolower($swatch);
                        $usedBy = $this->usedColors[strtolower($swatch)] ?? null;
                    @endphp
                    <div class="relative" wire:key="swatch-{{ $loop->index }}">
                        <button
                            type="button"
                            wire:click="selectColor('{{ $swatch }}')"
                            x-on:mouseenter="hovered = '{{ $loop->index }}'"
                            class="relative w-9 h-9 rounded-lg transition-all {{ $isSelected ? 'ring-2 ring-white ring-offset-2 ring-offset-[#0a2a3d] scale-110' : 'hover:scale-110' }} {{ $usedBy ? 'ring-2 ring-red-500' : '' }}"
                            style="background: {{ $swatch }}">
                            @if($isSelected)
                                <span class="absolute inset-0 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white drop-shadow-md" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                            @endif
                        </button>
                        <div
                            x-show="hovered === '{{ $loop->index }}'"
                            x-transition.opacity
                            x-cloak
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-xs text-white bg-zinc-900 rounded-lg shadow-lg whitespace-nowrap z-50 pointer-events-none">
                            {{ $usedBy ? 'Used by ' . $usedBy : 'Available' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Territory Assignments Card --}}
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Territories</h2>

            {{-- Existing assignments --}}
            @if(count($assignments) > 0)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($assignments as $index => $assignment)
                        <div wire:key="assignment-{{ $index }}" class="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-1.5">
                            <span class="text-sm text-white font-medium">{{ $assignment['territory_code'] }}</span>
                            @if($assignment['region'])
                                <span class="text-xs text-paleSky/60 italic">{{ $assignment['region'] }}</span>
                            @endif
                            <button type="button" wire:click="removeAssignment({{ $index }})"
                                    class="text-paleSky/40 hover:text-red-400 transition-colors ml-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-paleSky/50 mb-4">No territories assigned yet.</p>
            @endif

            {{-- Add assignment --}}
            <div class="border-t border-white/10 pt-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-paleSky/50 mb-1">Territory</label>
                        <select wire:model="newTerritoryCode"
                                class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:ring-1 focus:ring-[#00A599]">
                            <option value="">Select...</option>
                            @foreach($this->territoryChoices as $code => $name)
                                <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                            @endforeach
                        </select>
                        @error('newTerritoryCode') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs text-paleSky/50 mb-1">Region (for split territories)</label>
                        <input type="text" wire:model="newRegion"
                               class="w-36 px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white text-sm placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                               placeholder="e.g. Northern CA" />
                    </div>

                    <button type="button" wire:click="addAssignment"
                            class="px-4 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white text-sm font-medium rounded-lg transition-colors">
                        Add
                    </button>
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="px-6 py-2.5 bg-[#00A599] hover:bg-[#00A599]/80 text-white font-medium rounded-lg transition-colors">
                {{ $member ? 'Update Member' : 'Create Member' }}
            </button>
            <a href="{{ route('admin.sales-team.index') }}" wire:navigate
               class="px-6 py-2.5 bg-white/10 hover:bg-white/20 text-paleSky font-medium rounded-lg transition-colors">
                Cancel
            </a>
            @if($member)
                <div class="flex-1"></div>
                <button type="button"
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete {{ $member->name }} and all their territory assignments?"
                        class="px-6 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-medium rounded-lg transition-colors">
                    Delete Member
                </button>
            @endif
        </div>
    </form>
</div>
