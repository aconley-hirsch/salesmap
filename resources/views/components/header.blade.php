@props(['title' => null, 'subtitle' => null])

<header class="w-full text-sm mb-4">
    <nav class="flex items-center justify-between w-full">
        {{-- Admin / Login --}}
        @if (Route::has('login'))
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    @click.outside="open = false"
                    class="text-paleSky hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
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
                    class="absolute left-0 mt-2 w-48 bg-[#0a2a3d] border border-white/20 rounded-xl shadow-xl shadow-black/30 overflow-hidden z-50"
                >
                    <div class="py-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                                <svg class="w-5 h-5 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                </svg>
                                <span class="text-white">Admin</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                                <svg class="w-5 h-5 text-[#00A599]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                </svg>
                                <span class="text-white">Login</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        @else
            <div class="w-10"></div>
        @endif

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
