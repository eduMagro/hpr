{{-- Celda estándar --}}
@props([
    'class' => ''
])

<td class="px-3 py-3 text-left align-middle {{ $class }}">
    {{ $slot }}
</td>
