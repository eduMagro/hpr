@props(['paginador', 'perPageOptions' => [10, 25, 50, 100]])

{{-- Layout de 3 columnas: selector (izq), paginación (centro), slot extra (der) --}}
<div class="mt-4 w-full">
    <div class="flex items-start justify-between gap-4">
        {{-- Izquierda: Selector de cantidad por página --}}
        <div class="flex-shrink-0">
            <div class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-lg">
                <label for="perPageSelect" class="text-gray-700">Mostrar</label>
                <select wire:model.live="perPage" id="perPageSelect"
                    class="border border-gray-300 rounded-lg px-3 py-2 pr-10 text-sm text-gray-800 bg-white focus:ring-2 focus:ring-gray-700 focus:border-gray-800">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                <span class="text-gray-700">por página</span>
            </div>
        </div>

        {{-- Centro: Paginación y texto resumen juntos --}}
        @if ($paginador && $paginador->total() > 0)
            <div class="flex-grow flex flex-col items-center gap-2">
                {{-- Botones de paginación arriba --}}
                @if ($paginador->hasPages())
                    <nav
                        class="inline-flex flex-wrap gap-1 bg-white px-2 py-1 rounded-md shadow border border-gray-200">
                        {{-- Botón anterior --}}
                        @if ($paginador->onFirstPage())
                            <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">
                                &laquo;
                            </span>
                        @else
                            <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                                class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition disabled:opacity-50">
                                &laquo;
                            </button>
                        @endif

                        {{-- Lógica de paginación con recorte --}}
                        @php
                            $current = $paginador->currentPage();
                            $last = $paginador->lastPage();
                            $range = 2;
                            $pages = [];

                            // Siempre mostrar la primera página
                            $pages[] = 1;

                            // Páginas alrededor de la actual
                            for ($i = max(2, $current - $range); $i <= min($last - 1, $current + $range); $i++) {
                                $pages[] = $i;
                            }

                            // Siempre mostrar la última página
                            if ($last > 1) {
                                $pages[] = $last;
                            }

                            $pages = array_unique($pages);
                            sort($pages);
                        @endphp

                        @php $prevPage = 0; @endphp
                        @foreach ($pages as $page)
                            {{-- Mostrar puntos suspensivos si hay un salto --}}
                            @if ($prevPage > 0 && $page > $prevPage + 1)
                                <span class="px-2 text-xs text-gray-400 select-none">&hellip;</span>
                            @endif

                            {{-- Página actual --}}
                            @if ($page == $current)
                                <span
                                    class="px-3 py-1 text-xs font-bold bg-blue-500 text-white rounded shadow border border-blue-400">
                                    {{ $page }}
                                </span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }})"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition disabled:opacity-50">
                                    {{ $page }}
                                </button>
                            @endif

                            @php $prevPage = $page; @endphp
                        @endforeach

                        {{-- Botón siguiente --}}
                        @if ($paginador->hasMorePages())
                            <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                                class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition disabled:opacity-50">
                                &raquo;
                            </button>
                        @else
                            <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">&raquo;</span>
                        @endif
                    </nav>
                @endif

                {{-- Texto resumen debajo de la paginación - Desktop --}}
                <div class="hidden md:block text-sm text-gray-700">
                    Mostrando
                    <span class="font-semibold">{{ $paginador->firstItem() ?? 0 }}</span>
                    a
                    <span class="font-semibold">{{ $paginador->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold">{{ $paginador->total() }}</span>
                    resultados
                </div>

                {{-- Texto resumen debajo de la paginación - Móvil --}}
                <div class="md:hidden text-xs text-gray-700 text-center">
                    <span class="font-semibold">{{ $paginador->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold">{{ $paginador->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold">{{ $paginador->total() }}</span>
                </div>
            </div>
        @endif

        {{-- Derecha: Slot para contenido extra (ej: total peso) --}}
        <div class="flex-shrink-0">
            {{ $slot }}
        </div>
    </div>
</div>

{{-- Indicador de carga --}}
<div wire:loading class="text-center py-2">
    <span class="text-sm text-gray-700 font-medium">Cargando...</span>
</div>
