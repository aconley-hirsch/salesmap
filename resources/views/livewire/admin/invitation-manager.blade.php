<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Manage Invitations</h1>
            <p class="text-paleSky/70 text-sm mt-1">Send invitations to new users</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6 bg-[#92D400]/20 border border-[#92D400]/30 text-[#92D400] px-4 py-3 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Create Invitation Form --}}
    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 mb-6">
        <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-[#00A599]/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            Send New Invitation
        </h2>

        <form wire:submit="sendInvitation" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[10px] uppercase tracking-wider text-paleSky/60 mb-1">Name</label>
                <input
                    type="text"
                    wire:model="name"
                    placeholder="John Doe"
                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                />
                @error('name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-wider text-paleSky/60 mb-1">Email</label>
                <input
                    type="email"
                    wire:model="email"
                    placeholder="john@example.com"
                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                />
                @error('email') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                    </svg>
                    Send Invitation
                </button>
            </div>
        </form>
    </div>

    {{-- Search & Filter Card --}}
    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <div class="flex items-center gap-3 flex-1 w-full">
                <div class="w-10 h-10 rounded-lg bg-[#00A599]/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search invitations..."
                    class="flex-1 px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]"
                />
            </div>

            <select
                wire:model.live="statusFilter"
                class="w-full sm:w-auto px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-[#00A599] focus:border-[#00A599]">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="accepted">Accepted</option>
                <option value="expired">Expired</option>
                <option value="revoked">Revoked</option>
            </select>
        </div>
    </div>

    {{-- Invitations Table Card --}}
    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="px-6 py-4 text-left">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/60">Name</span>
                        </th>
                        <th class="px-6 py-4 text-left">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/60">Email</span>
                        </th>
                        <th class="px-6 py-4 text-left">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/60">Invited By</span>
                        </th>
                        <th class="px-6 py-4 text-center">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/60">Status</span>
                        </th>
                        <th class="px-6 py-4 text-left">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/60">Expires</span>
                        </th>
                        <th class="px-6 py-4 text-right">
                            <span class="text-[10px] uppercase tracking-wider text-paleSky/60">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($this->invitations as $invitation)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-white">{{ $invitation->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-paleSky/80">{{ $invitation->email }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-paleSky/80">{{ $invitation->inviter->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($invitation->isPending())
                                    <span class="px-2.5 py-1 text-xs font-medium bg-[#00A599]/20 text-[#00A599] rounded-full">Pending</span>
                                @elseif($invitation->isAccepted())
                                    <span class="px-2.5 py-1 text-xs font-medium bg-[#92D400]/20 text-[#92D400] rounded-full">Accepted</span>
                                @elseif($invitation->isRevoked())
                                    <span class="px-2.5 py-1 text-xs font-medium bg-white/10 text-paleSky/60 rounded-full">Revoked</span>
                                @elseif($invitation->isExpired())
                                    <span class="px-2.5 py-1 text-xs font-medium bg-red-500/20 text-red-400 rounded-full">Expired</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-paleSky/80">{{ $invitation->expires_at->format('M j, Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    @if($invitation->isPending())
                                        <button
                                            wire:click="resendInvitation({{ $invitation->id }})"
                                            class="text-paleSky/50 hover:text-[#00A599] transition-colors"
                                            title="Resend">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="revokeInvitation({{ $invitation->id }})"
                                            wire:confirm="Are you sure you want to revoke this invitation?"
                                            class="text-paleSky/50 hover:text-red-400 transition-colors"
                                            title="Revoke">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-paleSky/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                        </svg>
                                    </div>
                                    @if($search)
                                        <p class="text-sm text-paleSky/60">No invitations found matching "{{ $search }}"</p>
                                    @else
                                        <p class="text-sm text-paleSky/60">No invitations yet. Send your first one!</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->invitations->hasPages())
            <div class="px-6 py-4 border-t border-white/10">
                {{ $this->invitations->links() }}
            </div>
        @endif
    </div>
</div>
