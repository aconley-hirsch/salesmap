<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-5">
        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Email Address -->
            <div>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-paleSky/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="Username"
                        class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/15 rounded-lg text-white text-sm placeholder-paleSky/40 focus:outline-none focus:ring-1 focus:ring-ecoGreen/50 focus:border-ecoGreen/50 transition-colors"
                    />
                </div>
                @error('email')
                    <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-paleSky/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </span>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                        class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/15 rounded-lg text-white text-sm placeholder-paleSky/40 focus:outline-none focus:ring-1 focus:ring-ecoGreen/50 focus:border-ecoGreen/50 transition-colors"
                    />
                </div>
                @error('password')
                    <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full py-3 bg-ecoGreen hover:bg-ecoGreen/85 text-midnightSignal font-semibold text-sm rounded-lg transition-colors mt-1 cursor-pointer"
                    data-test="login-button">
                Login
            </button>
        </form>

        @if (Route::has('password.request'))
            <div class="text-center">
                <a href="{{ route('password.request') }}" class="text-xs text-paleSky/50 hover:text-paleSky transition-colors" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            </div>
        @endif
    </div>
</x-layouts::auth>
