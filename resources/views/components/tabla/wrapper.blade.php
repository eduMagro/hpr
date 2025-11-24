{{-- Contenedor principal de la tabla con estilos consistentes --}}
@props([
    'minWidth' => '1000px'
])

<div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
    <table class="w-full border border-gray-300 rounded-lg" style="min-width: {{ $minWidth }}">
        {{ $slot }}
    </table>
</div>
