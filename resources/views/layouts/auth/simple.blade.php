<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased font-montserrat">
        <div class="relative min-h-svh flex items-center justify-center">
            {{-- Background image --}}
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('/img/login-bg.jpg')"></div>

            {{-- Vertical band — full height, centered --}}
            <div class="relative z-10 w-full max-w-md min-h-svh flex flex-col items-center justify-center gap-8 px-8 bg-midnightSignal/70 backdrop-blur-md border-x border-white/10">
                {{-- Logo --}}
                <a href="{{ route('territory-map') }}">
                    <img src="/img/logo.png" alt="{{ config('app.name') }}" class="h-12" />
                </a>

                {{-- Form --}}
                <div class="w-full">
                    {{ $slot }}
                </div>

                {{-- Copyright --}}
                <p class="text-xs text-paleSky/40">Copyright &copy; {{ date('Y') }} Hirsch Secure, Inc.</p>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
