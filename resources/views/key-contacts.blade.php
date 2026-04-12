<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Key Contacts'])
</head>
<body>
<div class="min-h-screen bg-gradient-to-b from-midnightSignal to-deepTeal font-montserrat text-paleSky p-3 sm:p-6">

    <x-header current="key-contacts" />

    {{-- Contact groups --}}
    <div class="lg:w-2/3 mx-auto px-2 sm:px-6 pb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($groups as $groupName => $contacts)
                @if($groupName === 'Shared Inboxes')
                    @continue
                @endif

                <div>
                    <h2 class="text-sm font-semibold text-ecoGreen uppercase tracking-wider mb-4 pb-2 border-b border-ecoGreen/30">
                        {{ $groupName }}
                    </h2>

                    <div class="space-y-4">
                        @foreach($contacts as $contact)
                            <div class="group">
                                <div class="text-[15px] font-semibold text-white leading-snug">
                                    {{ $contact->name }}
                                </div>
                                @if($contact->title)
                                    <div class="text-[13px] text-paleSky/70 mt-0.5">
                                        {{ $contact->title }}
                                    </div>
                                @endif
                                @if($contact->email)
                                    <a href="mailto:{{ $contact->email }}"
                                       class="text-[13px] text-[#00A599] hover:text-ecoGreen hover:underline mt-0.5 block">
                                        {{ $contact->email }}
                                    </a>
                                @endif
                                @if($contact->phone)
                                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $contact->phone) }}"
                                       class="text-[12px] text-paleSky/50 hover:text-paleSky mt-0.5 block">
                                        {{ $contact->phone }}
                                    </a>
                                @endif
                                @if($contact->notes)
                                    <div class="text-[11px] text-paleSky/40 italic mt-1">
                                        {{ $contact->notes }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Shared Inboxes --}}
        @if(isset($groups['Shared Inboxes']) && $groups['Shared Inboxes']->count())
            <div class="mt-8 pt-6 border-t border-white/10">
                <div class="flex flex-wrap items-center gap-3">
                    @foreach($groups['Shared Inboxes'] as $inbox)
                        <a href="mailto:{{ $inbox->email }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-[#00A599]/15 hover:bg-[#00A599]/25 border border-[#00A599]/40 text-[#00A599] hover:text-ecoGreen text-sm font-medium rounded-full transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            {{ $inbox->email }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@fluxScripts
</body>
</html>
