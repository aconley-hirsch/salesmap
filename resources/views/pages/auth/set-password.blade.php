<x-layouts::auth :title="__('Set your password')">
    <div class="flex flex-col gap-5">
        <div class="text-center">
            <h2 class="text-lg font-semibold text-white">Set Your Password</h2>
            <p class="text-sm text-paleSky/60 mt-1">Welcome, {{ auth()->user()->name }}. Please choose a password to secure your account.</p>
        </div>

        <form method="POST" action="{{ route('password.setup.store') }}" class="flex flex-col gap-4">
            @csrf

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
                        autofocus
                        autocomplete="new-password"
                        placeholder="New password"
                        class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/15 rounded-lg text-white text-sm placeholder-paleSky/40 focus:outline-none focus:ring-1 focus:ring-ecoGreen/50 focus:border-ecoGreen/50 transition-colors"
                    />
                </div>
                @error('password')
                    <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-paleSky/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </span>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/15 rounded-lg text-white text-sm placeholder-paleSky/40 focus:outline-none focus:ring-1 focus:ring-ecoGreen/50 focus:border-ecoGreen/50 transition-colors"
                    />
                </div>
            </div>

            <button type="submit"
                    class="w-full py-3 bg-ecoGreen hover:bg-ecoGreen/85 text-midnightSignal font-semibold text-sm rounded-lg transition-colors mt-1 cursor-pointer">
                Set Password
            </button>
        </form>
    </div>
</x-layouts::auth>
