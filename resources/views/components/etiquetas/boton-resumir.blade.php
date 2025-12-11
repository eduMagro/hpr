{{--
    Componente: Botón para resumir etiquetas

    Props:
    - planillaId: ID de la planilla (requerido)
    - maquinaId: ID de la máquina (opcional, filtra por máquina)
    - size: 'small', 'normal', 'large' (default: 'normal')
    - showText: mostrar texto junto al icono (default: true)
--}}

@props([
    'planillaId' => null,
    'maquinaId' => null,
    'size' => 'normal',
    'showText' => true,
])

@php
    $sizeClasses = match($size) {
        'small' => 'w-6 h-6 p-1',
        'large' => 'px-4 py-3 text-base',
        default => 'px-3 py-2 text-sm',
    };

    $iconSize = match($size) {
        'small' => 'w-4 h-4',
        'large' => 'w-5 h-5',
        default => 'w-4 h-4',
    };
@endphp

<button
    type="button"
    onclick="resumirEtiquetas({{ $planillaId ?? 'null' }}, {{ $maquinaId ?? 'null' }})"
    class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700
           text-white rounded-lg font-medium shadow-md hover:shadow-lg transition-all
           duration-200 flex items-center justify-center gap-1 {{ $sizeClasses }} {{ $attributes->get('class') }}"
    title="Resumir etiquetas con mismo diámetro y dimensiones">

    {{-- Icono de agrupar --}}
    <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
    </svg>

    @if($showText && $size !== 'small')
        <span>Resumir</span>
    @endif
</button>
