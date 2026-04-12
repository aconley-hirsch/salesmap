<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Users</h1>
            <p class="text-paleSky/70 text-sm mt-1">Create and manage users. New users receive a setup link to create their password.</p>
        </div>
    </div>

    {{-- Setup URL display --}}
    @if($setupUrl)
        <div class="bg-ecoGreen/15 border border-ecoGreen/40 rounded-xl p-5 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white">Setup link for {{ $setupUrlUserName }}</p>
                    <p class="text-paleSky/70 text-xs mt-1">Click to copy. Share this link with the user — it will ask them to create a password.</p>
                    <div class="mt-3"
                         x-data="{ copied: false }"
                    >
                        <button
                            type="button"
                            x-on:click="navigator.clipboard.writeText('{{ $setupUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="w-full text-left px-4 py-2.5 bg-midnightSignal/50 border border-white/10 rounded-lg text-ecoGreen text-sm font-mono break-all hover:bg-midnightSignal/70 transition-colors cursor-pointer group"
                        >
                            <span class="flex items-start gap-2">
                                <svg class="w-4 h-4 shrink-0 mt-0.5 text-paleSky/40 group-hover:text-ecoGreen transition-colors" x-show="!copied" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                </svg>
                                <svg class="w-4 h-4 shrink-0 mt-0.5 text-ecoGreen" x-show="copied" x-cloak fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : '{{ $setupUrl }}'"></span>
                            </span>
                        </button>
                    </div>
                </div>
                <button type="button" wire:click="dismissUrl"
                        class="text-paleSky/40 hover:text-white p-1 shrink-0" title="Dismiss">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Create user form --}}
    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 mb-6">
        <h2 class="text-lg font-semibold text-white mb-4">Create User</h2>

        <form wire:submit="createUser" class="flex flex-col sm:flex-row items-end gap-3">
            <div class="flex-1 w-full">
                <label class="block text-sm text-paleSky/70 mb-1">Name *</label>
                <input type="text" wire:model="name"
                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                       placeholder="John Doe" />
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex-1 w-full">
                <label class="block text-sm text-paleSky/70 mb-1">Email *</label>
                <input type="email" wire:model="email"
                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                       placeholder="jdoe@hirschsecure.com" />
                @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pb-0.5">
                <label class="flex items-center gap-2 text-sm text-paleSky/70 cursor-pointer whitespace-nowrap">
                    <input type="checkbox" wire:model="isAdmin"
                           class="rounded border-white/20 bg-white/5 text-[#00A599] focus:ring-[#00A599]/50" />
                    Admin
                </label>

                <button type="submit"
                        class="px-5 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                    Create
                </button>
            </div>
        </form>
    </div>

    {{-- User list --}}
    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl overflow-hidden">
        @foreach($this->users as $user)
            <div wire:key="user-{{ $user->id }}"
                 class="flex items-center gap-4 px-5 py-3 border-b border-white/5 last:border-b-0 group hover:bg-white/5 transition-colors">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                     style="background: {{ $user->is_admin ? '#00A599' : '#7f7f7f' }}">
                    {{ $user->initials() }}
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-white truncate">{{ $user->name }}</span>
                        @if($user->is_admin)
                            <span class="px-1.5 py-0.5 text-[10px] font-medium bg-[#00A599]/20 text-[#00A599] rounded">Admin</span>
                        @endif
                        @if($user->mustSetPassword())
                            <span class="px-1.5 py-0.5 text-[10px] font-medium bg-amber-500/20 text-amber-400 rounded">Pending</span>
                        @endif
                    </div>
                    <span class="text-xs text-paleSky/50 truncate block">{{ $user->email }}</span>
                </div>

                @if($user->id !== auth()->id())
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        <button type="button" wire:click="toggleAdmin({{ $user->id }})"
                                class="p-1.5 rounded-lg text-paleSky/50 hover:text-[#00A599] hover:bg-white/10 transition-colors"
                                title="{{ $user->is_admin ? 'Remove admin' : 'Make admin' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </button>
                        <button type="button" wire:click="resetPassword({{ $user->id }})"
                                wire:confirm="Reset password for {{ $user->name }}? They will need a new setup link."
                                class="p-1.5 rounded-lg text-paleSky/50 hover:text-amber-400 hover:bg-white/10 transition-colors"
                                title="Reset password">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                        </button>
                        <button type="button"
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="Delete {{ $user->name }}? This cannot be undone."
                                class="p-1.5 rounded-lg text-paleSky/50 hover:text-red-400 hover:bg-white/10 transition-colors"
                                title="Delete user">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                @else
                    <span class="text-[10px] text-paleSky/30 italic">You</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
