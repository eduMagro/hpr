@props([
    // id de la obra activa (para mantenerla al cambiar de vista)
    'obraActualId' => null,

    // nombres de ruta para cada vista
    'routeIndex' => 'localizaciones.index',
    'routeCreate' => 'localizaciones.create',
])

@php
    // Usamos azul más oscuro que el menú de obras (blue-800/900/950)
    $colores = [
        'bg' => 'bg-blue-800',
        'bgHover' => 'hover:bg-blue-900',
        'bgActivo' => 'bg-blue-900',
        'txt' => 'text-white',
    ];

    $isIndex = request()->routeIs($routeIndex);
    $isCreate = request()->routeIs($routeCreate);

    // URLs preservando la obra seleccionada
    $urlIndex = route($routeIndex, ['obra' => $obraActualId]);
    $urlCreate = route($routeCreate, ['obra' => $obraActualId]);
@endphp

<div class="w-full mb-2" x-data="{ open: false }">
    {{-- Móvil: dropdown simple --}}
    <div class="sm:hidden relative mb-2">
        <button @click="open = !open"
            class="w-1/2 {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold px-4 py-2 shadow transition">
            Navegación
        </button>

        <div x-show="open" x-transition @click.away="open = false" x-cloak
            class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200">
            <a href="{{ $urlIndex }}"
                class="block px-3 py-3 text-sm font-medium transition {{ $isIndex ? 'bg-blue-50 text-blue-900' : 'hover:bg-blue-50 text-blue-700 hover:text-blue-900' }}">
                📋 Ver localizaciones
            </a>

            <a href="{{ $urlCreate }}"
                class="block px-3 py-3 text-sm font-medium transition {{ $isCreate ? 'bg-blue-50 text-blue-900' : 'hover:bg-blue-50 text-blue-700 hover:text-blue-900' }}">
                ➕ Asignar máquinas
            </a>
        </div>
    </div>

    {{-- Escritorio: 2 pestañas ocupando ancho --}}
    <div class="hidden sm:flex w-full mb-2">
        <a href="{{ $urlIndex }}"
            class="flex-1 text-center px-4 py-2 font-semibold transition
           {{ $isIndex ? $colores['bgActivo'] . ' ' . $colores['txt'] : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
            📋 Ver localizaciones
        </a>

        <a href="{{ $urlCreate }}"
            class="flex-1 text-center px-4 py-2 font-semibold transition
           {{ $isCreate ? $colores['bgActivo'] . ' ' . $colores['txt'] : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
            ➕ Asignar máquinas
        </a>
    </div>
</div>
