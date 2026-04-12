<div class="p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Key Contacts</h1>
            <p class="text-paleSky/70 text-sm mt-1">Manage the contacts displayed on the public Key Contacts page.</p>
        </div>

        <button type="button" wire:click="create"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white font-medium rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Contact
        </button>
    </div>

    {{-- Inline form --}}
    @if($showForm)
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">{{ $editingId ? 'Edit Contact' : 'New Contact' }}</h2>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-paleSky/70 mb-1">Name *</label>
                        <input type="text" wire:model="form.name"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                               placeholder="John Doe" />
                        @error('form.name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-paleSky/70 mb-1">Title</label>
                        <input type="text" wire:model="form.title"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                               placeholder="National Sales Director" />
                    </div>

                    <div>
                        <label class="block text-sm text-paleSky/70 mb-1">Email</label>
                        <input type="email" wire:model="form.email"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                               placeholder="jdoe@hirschsecure.com" />
                        @error('form.email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-paleSky/70 mb-1">Phone</label>
                        <input type="text" wire:model="form.phone"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                               placeholder="555.123.4567" />
                    </div>

                    <div>
                        <label class="block text-sm text-paleSky/70 mb-1">Group *</label>
                        <select wire:model="form.group_name"
                                class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-[#00A599]">
                            @foreach($this->groupNames as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                            <option value="__new">+ New Group…</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-paleSky/70 mb-1">Sort Order</label>
                        <input type="number" wire:model="form.sort_order" min="0"
                               class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-[#00A599]" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-paleSky/70 mb-1">Notes</label>
                    <textarea wire:model="form.notes" rows="2"
                              class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-[#00A599]"
                              placeholder="e.g. Treasury, USSS, FBI, IRS…"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="px-5 py-2 bg-[#00A599] hover:bg-[#00A599]/80 text-white font-medium rounded-lg transition-colors">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="$set('showForm', false)"
                            class="px-5 py-2 bg-white/10 hover:bg-white/20 text-paleSky font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Grouped list --}}
    @forelse($this->groups as $groupName => $contacts)
        <div class="mb-6">
            <h3 class="text-xs uppercase tracking-wider text-ecoGreen font-semibold mb-3">{{ $groupName }}</h3>

            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl overflow-hidden">
                @foreach($contacts as $contact)
                    <div wire:key="contact-{{ $contact->id }}"
                         class="flex items-center gap-4 px-5 py-3 border-b border-white/5 last:border-b-0 group hover:bg-white/5 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-white truncate">{{ $contact->name }}</span>
                                @if($contact->title)
                                    <span class="text-xs text-paleSky/50 truncate hidden sm:inline">{{ $contact->title }}</span>
                                @endif
                            </div>
                            @if($contact->email)
                                <span class="text-xs text-[#00A599] truncate block">{{ $contact->email }}</span>
                            @endif
                            @if($contact->notes)
                                <span class="text-[11px] text-paleSky/40 italic truncate block">{{ $contact->notes }}</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                            <button type="button" wire:click="edit({{ $contact->id }})"
                                    class="p-1.5 rounded-lg text-paleSky/50 hover:text-[#00A599] hover:bg-white/10 transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                            </button>
                            <button type="button"
                                    wire:click="delete({{ $contact->id }})"
                                    wire:confirm="Delete {{ $contact->name }}?"
                                    class="p-1.5 rounded-lg text-paleSky/50 hover:text-red-400 hover:bg-white/10 transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-12 text-center">
            <p class="text-sm text-paleSky/60">No key contacts yet. Add your first one!</p>
        </div>
    @endforelse
</div>
