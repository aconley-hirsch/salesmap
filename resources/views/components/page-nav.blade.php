@props(['current'])

<nav class="flex items-center gap-1 px-2 sm:px-6 mb-1" aria-label="Page navigation">
    <a href="{{ route('territory-map') }}"
       @class([
           'px-4 py-2 rounded-lg text-sm font-semibold transition-all',
           'bg-ecoGreen text-midnightSignal' => $current === 'territory-map',
           'text-paleSky/70 hover:text-white hover:bg-white/10' => $current !== 'territory-map',
       ])>
        Territory Map
    </a>
    <a href="{{ route('key-contacts') }}"
       @class([
           'px-4 py-2 rounded-lg text-sm font-semibold transition-all',
           'bg-ecoGreen text-midnightSignal' => $current === 'key-contacts',
           'text-paleSky/70 hover:text-white hover:bg-white/10' => $current !== 'key-contacts',
       ])>
        Key Contacts
    </a>
</nav>
