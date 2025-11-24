{{-- Celda de acciones con botón reset y otros botones opcionales --}}
<th class="p-1 border text-center align-middle">
    <div class="flex justify-center gap-2 items-center h-full">
        {{-- Botón reset (siempre presente) --}}
        <button
            type="button"
            wire:click="limpiarFiltros"
            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
            title="Restablecer filtros"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
            </svg>
        </button>

        {{-- Slot para botones adicionales (ej: exportar Excel) --}}
        {{ $slot }}
    </div>
</th>
