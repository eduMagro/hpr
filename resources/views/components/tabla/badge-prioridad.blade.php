{{-- Badge de prioridad (1=Baja, 2=Media, 3=Alta) --}}
@props([
    'prioridad' => 1
])

@php
    $configs = [
        1 => ['class' => 'bg-gray-200 text-gray-800', 'label' => 'Baja'],
        2 => ['class' => 'bg-yellow-200 text-yellow-800', 'label' => 'Media'],
        3 => ['class' => 'bg-red-200 text-red-800', 'label' => 'Alta'],
    ];

    $config = $configs[$prioridad] ?? $configs[1];
@endphp

<span class="px-2 py-1 rounded text-xs font-semibold {{ $config['class'] }}">
    {{ $config['label'] }}
</span>
