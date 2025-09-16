@php
    use Illuminate\Support\Str;

    // 👇 se muestra este submenú solo si estamos en la sección Salidas Almacén
    $isSeccionAlmacen = request()->routeIs('salidas-almacen.*');

    // 🔗 Submenú específico de Salidas Almacén
    $subLinksAlmacen = [
        ['route' => 'salidas-almacen.index', 'label' => '📦 Listado Almacén'],
        ['route' => 'salidas-almacen.create', 'label' => '➕ Nueva salida Almacén'],
    ];

    // reutilizamos mismas variables de color y ruta actual para mantener ecosistema limpio
    $rutaActual = request()->route()->getName();
    $colorBase = $colorBase ?? 'blue';
    $colores = $colores ?? [
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

@if ($isSeccionAlmacen)
    <div class="w-full mt-2" x-data="{ open: false }">
        <!-- Menú móvil: submenú Salidas Almacén -->
        <div class="sm:hidden relative">
            <button @click="open = !open"
                class="w-1/2 {{ $colores['bg'] }} {{ $colores['bgHover'] }} {{ $colores['txt'] }} font-semibold px-4 py-2 shadow transition">
                Salidas Almacén
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                class="absolute z-30 mt-0 w-1/2 bg-white border {{ $colores['borde'] }} rounded-b-lg shadow-xl overflow-hidden divide-y {{ $colores['borde'] }}"
                x-cloak>
                @foreach ($subLinksAlmacen as $link)
                    @php
                        // Aquí usamos igualdad exacta para que solo se active el item exacto (index o create)
                        $active = $rutaActual === $link['route'];
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

        <!-- Menú escritorio: submenú Salidas Almacén -->
        <div class="hidden sm:flex sm:mt-0 w-full">
            @foreach ($subLinksAlmacen as $link)
                @php
                    $active = $rutaActual === $link['route'];
                @endphp
                <a href="{{ route($link['route']) }}"
                    class="flex-1 text-center px-4 py-2 font-semibold transition
                      {{ $active
                          ? $colores['bgActivo'] . ' ' . $colores['txt']
                          : $colores['bg'] . ' ' . $colores['bgHover'] . ' ' . $colores['txt'] }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
