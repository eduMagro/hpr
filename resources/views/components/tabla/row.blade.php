{{-- Fila estándar de datos --}}
@props([
    'class' => ''
])

<tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-gray-200 cursor-pointer text-sm transition-colors {{ $class }}">
    {{ $slot }}
</tr>
