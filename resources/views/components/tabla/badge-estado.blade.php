{{-- Badge de estado con colores predefinidos --}}
@props([
    'estado' => '',
    'colores' => []
])

@php
    // Colores por defecto para estados comunes
    $defaultColores = [
        'pendiente' => 'bg-yellow-200 text-yellow-800',
        'completado' => 'bg-green-200 text-green-800',
        'completada' => 'bg-green-200 text-green-800',
        'cancelado' => 'bg-gray-200 text-gray-800',
        'en_curso' => 'bg-blue-200 text-blue-800',
        'fabricando' => 'bg-blue-200 text-blue-800',
        'activo' => 'bg-green-200 text-green-800',
        'inactivo' => 'bg-gray-200 text-gray-800',
        'enviado' => 'bg-green-200 text-green-800',
        'en_reparto' => 'bg-purple-200 text-purple-800',
        'asignado_a_salida' => 'bg-blue-200 text-blue-800',
        'montaje' => 'bg-purple-200 text-purple-800',
    ];

    // Merge colores personalizados con defaults
    $allColores = array_merge($defaultColores, $colores);

    // Obtener clase de color para el estado
    $colorClass = $allColores[strtolower($estado)] ?? 'bg-gray-100 text-gray-700';
@endphp

<span class="px-2 py-1 rounded text-xs font-semibold {{ $colorClass }}">
    {{ ucfirst($estado) }}
</span>
