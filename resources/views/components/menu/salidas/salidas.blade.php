@php
    use Illuminate\Support\Str;

    $links = [
        ['route' => 'planificacion.index', 'label' => '📅 Planificación Salidas'],
        ['route' => 'salidas-ferralla.index', 'label' => '⬆️ Listado Salidas'],
        ['route' => 'salidas-ferralla.create', 'label' => '➕ Nueva Salida mezclando obras'],
        ['route' => 'empresas-transporte.index', 'label' => '🚛 Empresas Transporte'],
    ];

    $rutaActual = request()->route()->getName();

    // Rutas que NO deben encender la pestaña general "Salidas"
    $excluirDeSeccion = [
        'salidas-ferralla.create',
        // si algún día tienes otro create:
        // 'salidas-almacen.create',
    ];

    // Estás en la sección Salidas (ferralla o almacén) y NO en una ruta excluida
    $estaEnSeccionMostrable =
        request()->routeIs('salidas-ferralla.*', 'salidas-almacen.*') && !request()->routeIs($excluirDeSeccion);

    // Pestaña representante de la sección
    $representanteSeccion = 'salidas-ferralla.index';

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
@endphp

<div class="w-full" x-data="{ open: false }">
    <!-- Menú móvil -->
    <div class="sm:hidden relative">
        <button @click="open = !open"
            class="w-1/2 {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold px-4 py-2 shadow transition">
            Opciones
        </button>

        <div x-show="open" x-transition @click.away="open = false"
            class="absolute z-30 mt-0 w-1/2 bg-white border {{ $colores['borde'] }} rounded-b-lg shadow-xl overflow-hidden divide-y {{ $colores['borde'] }}"
            x-cloak>
            @foreach ($links as $link)
                @php
                    $active =
                        $rutaActual === $link['route'] ||
                        ($estaEnSeccionMostrable && $link['route'] === $representanteSeccion);
                @endphp
                <a href="{{ route($link['route']) }}"
                    class="block px-2 py-3 text-sm font-medium transition
              {{ $active
                  ? $colores['bgLite'] . ' ' . $colores['activoTxt'] . ' font-semibold'
                  : $colores['txtBase'] . ' ' . $colores['hoverLite'] . ' ' . $colores['txtHover'] }}">
                    {{ $link['label'] }}
                </a>
            @endforeach

        </div>
    </div>

    <!-- Menú escritorio -->
    <div class="hidden sm:flex sm:mt-0 w-full">
        @foreach ($links as $link)
            @php
                $active =
                    $rutaActual === $link['route'] ||
                    ($estaEnSeccionMostrable && $link['route'] === $representanteSeccion);
            @endphp
            <a href="{{ route($link['route']) }}"
                class="relative flex-1 text-center px-4 py-2 font-semibold transition
              {{ $active
                  ? $colores['bgActivo'] . ' ' . $colores['txt']
                  : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
                {{ $link['label'] }}
            </a>
        @endforeach

    </div>
</div>
