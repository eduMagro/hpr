@php
    use Illuminate\Support\Str;

    $rutaActual = request()->route()->getName();
    $colorBase = $colorBase ?? 'blue';
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

    $tabs = [
        ['route' => 'salidas-almacen.index', 'label' => '📦 Salidas Almacén'],
        ['route' => 'pedidos-almacen-venta.index', 'label' => '📄 Lista Pedidos Almacén'],
        ['route' => 'pedidos-almacen-venta.create', 'label' => '📄 Crear Pedidos Almacén'],
        ['route' => 'clientes-almacen.index', 'label' => '👥 Clientes Almacén'],
    ];
@endphp

<div class="mb-4 border-b {{ $colores['borde'] }}">
    <nav class="flex space-x-4 overflow-x-auto text-sm">
        @foreach ($tabs as $tab)
            @php
                $esActivo = Str::startsWith($rutaActual, $tab['route']);
            @endphp
            <a href="{{ route($tab['route']) }}"
                class="px-3 py-2 font-medium whitespace-nowrap border-b-2
                    {{ $esActivo
                        ? "$colores[bg] $colores[txt] border-$colorBase-600"
                        : "text-gray-600 border-transparent hover:$colores[txtHover] hover:border-$colorBase-300" }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
