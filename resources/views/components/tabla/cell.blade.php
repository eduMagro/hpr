{{-- Celda estándar --}}
@props([
    'class' => ''
])

<td class="px-2 py-3 text-center border {{ $class }}">
    {{ $slot }}
</td>
