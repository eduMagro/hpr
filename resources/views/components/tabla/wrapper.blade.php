{{-- Contenedor principal de la tabla con estilos consistentes --}}
@props([
    'minWidth' => '1000px'
])

<div class="w-full overflow-x-auto">
    <div class="block w-full min-w-full align-middle bg-white border border-gray-200 shadow-lg rounded-xl">
        <table class="min-w-full text-sm text-gray-800" style="min-width: {{ $minWidth }}">
            {{ $slot }}
        </table>
    </div>
</div>
