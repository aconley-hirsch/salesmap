<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Manage Sales Team</h1>
            <p class="text-paleSky/70 text-sm mt-1">Manage sales team members and territory assignments</p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.territory-map.edit') }}"
                wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-paleSky font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                </svg>
                Territory Editor
            </a>

            <a
                href="{{ route('admin.sales-team.create') }}"
                wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Member
            </a>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex items-center gap-3 flex-1">
                <div class="w-10 h-10 rounded-lg bg-[#00A599]/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    class="flex-1 px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                />
            </div>
            <select
                wire:model.live="roleFilter"
                class="px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]">
                <option value="">All Roles</option>
                @foreach($this->roleTypes as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Members Grid --}}
    @if($this->members->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($this->members as $member)
                @php
                    $firstAssignment = $member->territoryAssignments->first();
                    $memberColor = $firstAssignment?->color ?? '#7f7f7f';
                    $memberRole = $firstAssignment?->role_type;
                    $states = $member->territoryAssignments->pluck('state_code')->unique()->sort()->values();
                @endphp
                <div wire:key="member-{{ $member->id }}"
                     class="group bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-5 hover:bg-white/15 hover:border-white/30 transition-all relative">

                    {{-- Color accent bar --}}
                    <div class="absolute top-0 left-5 right-5 h-1 rounded-b-full" style="background: {{ $memberColor }}"></div>

                    {{-- Header: Name + Actions --}}
                    <div class="flex items-start justify-between mb-3 pt-1">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0" style="background: {{ $memberColor }}">
                                {{ collect(explode(' ', $member->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('') }}
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h3 class="text-sm font-semibold text-white leading-tight">{{ $member->name }}</h3>
                                    @if($member->is_active)
                                        <span class="w-1.5 h-1.5 rounded-full bg-ecoGreen shrink-0"></span>
                                    @endif
                                </div>
                                @if($memberRole)
                                    <span class="text-[11px] text-[#00A599]">{{ $memberRole->label() }}</span>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('admin.sales-team.edit', $member->id) }}"
                           wire:navigate
                           class="p-1.5 rounded-lg text-paleSky/50 hover:text-[#00A599] hover:bg-white/10 transition-colors opacity-0 group-hover:opacity-100"
                           title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>
                    </div>

                    {{-- Contact info --}}
                    <div class="space-y-1 mb-3">
                        @if($member->email)
                            <div class="flex items-center gap-2 text-xs text-paleSky/60">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                <span class="truncate">{{ $member->email }}</span>
                            </div>
                        @endif
                        @if($member->phone)
                            <div class="flex items-center gap-2 text-xs text-paleSky/60">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                <span>{{ $member->phone }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Territories --}}
                    @if($states->count() > 0)
                        <div class="flex flex-wrap gap-1">
                            @foreach($states->take(12) as $state)
                                <span class="px-1.5 py-0.5 text-[10px] font-medium bg-white/10 text-paleSky/70 rounded">{{ $state }}</span>
                            @endforeach
                            @if($states->count() > 12)
                                <span class="px-1.5 py-0.5 text-[10px] font-medium bg-white/5 text-paleSky/40 rounded">+{{ $states->count() - 12 }}</span>
                            @endif
                        </div>
                    @else
                        <p class="text-[10px] text-paleSky/30 italic">No territories assigned</p>
                    @endif

                    {{-- Status indicator --}}
                    @if(!$member->is_active)
                        <div class="mt-3 pt-3 border-t border-white/10">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/40">Inactive</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($this->members->hasPages())
            <div class="mt-6">
                {{ $this->members->links() }}
            </div>
        @endif
    @else
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-12">
            <div class="flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-paleSky/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                @if($search)
                    <p class="text-sm text-paleSky/60">No members found matching "{{ $search }}"</p>
                @else
                    <p class="text-sm text-paleSky/60">No sales team members yet. Add your first one!</p>
                @endif
            </div>
        </div>
    @endif
</div>
