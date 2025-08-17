@php
    // 🔗 Aquí defines todos los enlaces una sola vez
    $links = [
        ['route' => 'produccion.verMaquinas', 'label' => 'Planificación Planillas'],
        ['route' => 'planillas.index', 'label' => '📄 Planillas'],
        ['route' => 'paquetes.index', 'label' => '📦 Paquetes'],
        ['route' => 'etiquetas.index', 'label' => '🏷️ Etiquetas'],
        ['route' => 'elementos.index', 'label' => '🔩 Elementos'],
    ];

    $rutaActual = request()->route()->getName();
@endphp

<div class="w-full" x-data="{ open: false }">
    <!-- Menú móvil -->
    <div class="sm:hidden relative">
        <button @click="open = !open"
            class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
            Opciones
        </button>

        <div x-show="open" x-transition @click.away="open = false"
            class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
            x-cloak>
            @foreach ($links as $link)
                @php $active = $rutaActual === $link['route']; @endphp
                <a href="{{ route($link['route']) }}"
                    class="block px-2 py-3 transition text-sm font-medium
                          {{ $active ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                    {!! $link['label'] !!}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Menú escritorio -->
    <div class="hidden sm:flex sm:mt-0 w-full">
        @foreach ($links as $i => $link)
            @php
                $active = $rutaActual === $link['route'];
                $first = $i === 0;
                $last = $i === count($links) - 1;
            @endphp
            <a href="{{ route($link['route']) }}"
                class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                      {{ $first ? 'first:rounded-l-lg' : '' }}
                      {{ $last ? 'last:rounded-r-lg' : '' }}
                      {{ $active ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                {!! $link['label'] !!}
            </a>
        @endforeach
    </div>
</div>
