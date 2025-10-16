@props(['obras', 'obraActualId' => null])

@php
    $colores = [
        'bg' => 'bg-blue-600',
        'bgHover' => 'hover:bg-blue-700',
        'bgActivo' => 'bg-blue-800',
        'txt' => 'text-white',
        'txtBase' => 'text-blue-700',
        'txtHover' => 'hover:text-blue-900',
        'bgLite' => 'bg-blue-100',
        'borde' => 'border-gray-200',
        'activoTxt' => 'text-blue-800',
        'hoverLite' => 'hover:bg-blue-50',
    ];

    // Función que devuelve la ruta hacia el controlador index con el ID de la obra
    function rutaNave($obra)
    {
        return route('ubicaciones.index', ['obra' => $obra->id]);
    }
@endphp

@if ($obras->isNotEmpty())
    <div class="w-full" x-data="{ open: false }">

        {{-- ===== Menú móvil ===== --}}
        <div class="sm:hidden relative mb-4">
            <button @click="open = !open"
                class="w-1/2 {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold px-4 py-2 shadow transition">
                Naves
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                class="absolute z-30 mt-0 w-1/2 bg-white border {{ $colores['borde'] }} rounded-b-lg shadow-xl overflow-hidden divide-y {{ $colores['borde'] }}"
                x-cloak>
                @foreach ($obras as $obra)
                    @php
                        $active = $obra->id == $obraActualId;
                        $ruta = rutaNave($obra);
                    @endphp
                    <a href="{{ $ruta }}"
                        class="relative block px-2 py-3 text-sm font-medium transition
                            {{ $active
                                ? $colores['bgLite'] . ' ' . $colores['activoTxt'] . ' font-semibold'
                                : $colores['txtBase'] . ' ' . $colores['hoverLite'] . ' ' . $colores['txtHover'] }}">
                        🏭 {{ $obra->obra }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ===== Menú escritorio ===== --}}
        <div class="hidden sm:flex w-full mb-4">
            @foreach ($obras as $obra)
                @php
                    $active = $obra->id == $obraActualId;
                    $ruta = rutaNave($obra);
                @endphp
                <a href="{{ $ruta }}"
                    class="relative flex-1 text-center px-4 py-2 font-semibold transition
                        {{ $active
                            ? $colores['bgActivo'] . ' ' . $colores['txt']
                            : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
                    {{ $obra->obra }}
                </a>
            @endforeach
        </div>
    </div>
@endif
