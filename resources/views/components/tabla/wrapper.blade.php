{{-- Contenedor principal de la tabla con estilos consistentes --}}
@props([
    'minWidth' => '1000px',
])

<div class="w-full h-full">
    <div class="rounded-md bg-white shadow-lg-600 overflow-hidden h-full">
        <div class="h-full overflow-auto" style="max-height: 100%;">
            <table class="w-full table-auto text-sm text-gray-800" style="min-width: {{ $minWidth }}">
                {{ $slot }}
            </table>
        </div>
    </div>
</div>
