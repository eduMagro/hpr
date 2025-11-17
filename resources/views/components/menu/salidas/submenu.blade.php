{{-- resources/views/components/menu/submenu.blade.php --}}
@props([
    'items' => [],
    'rutaActual' => '',
    'colores' => [],
    'nombre' => '',
])

<div class="bg-white border-b {{ $colores['borde'] ?? 'border-gray-200' }} mb-4">
    <div class="px-4 py-2 flex items-center justify-between">
        <h3 class="font-semibold text-sm {{ $colores['txtBase'] ?? 'text-gray-700' }}">
            {{ $nombre }}
        </h3>
    </div>

    <nav class="flex flex-wrap gap-2 px-4 pb-3">
        @foreach ($items as $item)
            @php
                $activo = $rutaActual === $item['route'];
            @endphp

            <a href="{{ route($item['route']) }}" wire:navigate
                class="px-3 py-1.5 text-sm rounded-md border transition
                       {{ $activo
                           ? ($colores['bgActivo'] ?? 'bg-blue-800') . ' ' . ($colores['txt'] ?? 'text-white') . ' font-semibold'
                           : ($colores['bgLite'] ?? 'bg-gray-100') .
                               ' ' .
                               ($colores['txtBase'] ?? 'text-gray-700') .
                               ' ' .
                               ($colores['hoverLite'] ?? 'hover:bg-gray-50') }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</div>
