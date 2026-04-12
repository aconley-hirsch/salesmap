@props(['title' => null, 'subtitle' => null])

<header class="w-full text-sm mb-4">
    <nav class="flex items-center justify-between w-full">
        {{-- Menu --}}
        <div x-data="{ open: false }" class="relative">
            <button
                @click="open = !open"
                @click.outside="open = false"
                class="text-paleSky hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute left-0 mt-2 w-56 bg-[#0a2a3d] border border-white/20 rounded-xl shadow-xl shadow-black/30 overflow-hidden z-50"
            >
                <div class="py-2">
                    <a href="{{ route('territory-map') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                        </svg>
                        <span class="text-white">Territory Map</span>
                    </a>

                    <a href="{{ route('key-contacts') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <span class="text-white">Key Contacts</span>
                    </a>

                    @if (Route::has('login'))
                        <div class="border-t border-white/10 my-2"></div>
                        @auth
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                                <svg class="w-5 h-5 text-paleSky" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                <span class="text-white">Admin</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                                <svg class="w-5 h-5 text-paleSky" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                                <span class="text-white">Login</span>
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        {{-- Page Title --}}
        @if($title)
            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3 text-center sm:text-left">
                <h1 class="text-base sm:text-lg font-semibold text-white m-0 leading-tight">{{ $title }}</h1>
                @if($subtitle)
                    <span class="text-[10px] text-[#00A599] font-medium tracking-wider">{{ $subtitle }}</span>
                @endif
            </div>
        @endif

        {{-- Spacer to keep title centered --}}
        <div class="w-10"></div>
    </nav>
</header>
