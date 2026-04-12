@props([
    'sidebar' => false,
])

@if($sidebar)
    <a href="{{ route('admin.territory-map.edit') }}" wire:navigate {{ $attributes }} class="flex justify-center w-full">
        <img src="/img/logo.png" alt="{{ config('app.name') }}" class="h-8" />
    </a>
@else
    <a href="{{ route('admin.territory-map.edit') }}" {{ $attributes }}>
        <img src="/img/logo.png" alt="{{ config('app.name') }}" class="h-8" />
    </a>
@endif
