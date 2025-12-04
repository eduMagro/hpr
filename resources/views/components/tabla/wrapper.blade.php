{{-- Contenedor principal de la tabla con estilos consistentes --}}
@props([
    'minWidth' => '1000px',
    'maxHeight' => '100%',
])

<div class="w-full h-full">
    <div class="block w-full min-w-full align-middle bg-white border border-gray-200 shadow-lg rounded-xl overflow-hidden h-full"
        style="max-height: calc({{ $maxHeight }})">
        <div class="h-full overflow-y-auto overflow-x-auto">
            <table class="w-full h-full table-auto text-sm text-gray-800" style="min-width: {{ $minWidth }}">
                {{ $slot }}
            </table>
        </div>
    </div>
</div>
