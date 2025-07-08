@php

    $rutaActual = request()->route()->getName();
    $colorBase = 'blue';

    $colores = [
        'bg' => "bg-$colorBase-600",
        'bgHover' => "hover:bg-$colorBase-700",
        'bgActivo' => "bg-$colorBase-800",
        'txt' => 'text-white',
        'txtBase' => "text-$colorBase-700",
        'txtHover' => "hover:text-$colorBase-900",
        'bgLite' => "bg-$colorBase-100",
        'borde' => 'border-gray-200',
        'activoTxt' => "text-$colorBase-800",
        'hoverLite' => "hover:bg-$colorBase-50",
    ];

    // 👉 Items predefinidos del menú de planificación
    $items = [
        'produccion.maquinas' => '📋 Planificación Planillas',
        'produccion.trabajadores' => '📋 Planificación Trabajadores Almacén',
        'produccion.trabajadoresObra' => '⏱️ Planificación Trabajadores Obra',
    ];
@endphp

@if (auth()->check() && auth()->user()->rol === 'oficina')
    <div class="w-full" x-data="{ open: false }">
        <!-- Menú móvil -->
        <div class="sm:hidden relative mb-4">
            <button @click="open = !open"
                class="w-1/2 {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold px-4 py-2 shadow transition">
                Opciones
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                class="absolute z-30 mt-0 w-1/2 bg-white border {{ $colores['borde'] }} rounded-b-lg shadow-xl overflow-hidden divide-y {{ $colores['borde'] }}"
                x-cloak>
                @foreach ($items as $ruta => $texto)
                    <a href="{{ route($ruta) }}"
                        class="relative block px-2 py-3 text-sm font-medium transition
                            {{ request()->routeIs($ruta)
                                ? $colores['bgLite'] . ' ' . $colores['activoTxt'] . ' font-semibold'
                                : $colores['txtBase'] . ' ' . $colores['hoverLite'] . ' ' . $colores['txtHover'] }}">
                        {{ $texto }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Menú escritorio -->
        <div class="hidden sm:flex w-full mb-4">
            @foreach ($items as $ruta => $texto)
                <a href="{{ route($ruta) }}"
                    class="relative flex-1 text-center px-4 py-2 font-semibold transition
                        {{ request()->routeIs($ruta)
                            ? $colores['bgActivo'] . ' ' . $colores['txt']
                            : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
                    {{ $texto }}
                </a>
            @endforeach
        </div>
    </div>
@endif
