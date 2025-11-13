@props(['paginador', 'perPageOptions' => [10, 25, 50, 100]])

{{-- SIEMPRE visible: selector de cantidad por página --}}
<div class="m-4 text-center">
    <div class="inline-flex items-center justify-center gap-2 text-sm">
        <label for="perPageSelect" class="text-gray-600">Mostrar</label>
        <select wire:model.live="perPage"
                id="perPageSelect"
                class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
        <span class="text-gray-600">por página</span>
    </div>
</div>

{{-- Texto resumen e información de paginación --}}
@if($paginador && ($paginador->hasPages() || $paginador->total() > 0))
    <div class="mt-6 space-y-3 text-center">

        {{-- Texto resumen --}}
        <div class="text-sm text-gray-600">
            Mostrando
            <span class="font-semibold">{{ $paginador->firstItem() ?? 0 }}</span>
            a
            <span class="font-semibold">{{ $paginador->lastItem() ?? 0 }}</span>
            de
            <span class="font-semibold">{{ $paginador->total() }}</span>
            resultados
        </div>

        {{-- Paginación solo si hay más de una página --}}
        @if($paginador->hasPages())
            <div class="flex justify-center">
                <div class="inline-flex flex-wrap gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow-sm">
                    {{-- Botón anterior --}}
                    @if ($paginador->onFirstPage())
                        <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">
                            &lt;&lt;
                        </span>
                    @else
                        <button wire:click="previousPage('{{ $paginador->getPageName() }}')"
                                wire:loading.attr="disabled"
                                class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                            &lt;&lt;
                        </button>
                    @endif

                    {{-- Lógica de paginación con recorte --}}
                    @php
                        $current = $paginador->currentPage();
                        $last = $paginador->lastPage();
                        $range = 2; // número de páginas antes y después del actual
                        $pages = [];

                        // Páginas siempre visibles
                        $pages[] = 1;

                        for ($i = $current - $range; $i <= $current + $range; $i++) {
                            if ($i > 1 && $i < $last) {
                                $pages[] = $i;
                            }
                        }

                        if ($last > 1) {
                            $pages[] = $last;
                        }

                        $pages = array_unique($pages);
                        sort($pages);
                    @endphp

                    @php $prevPage = 0; @endphp
                    @foreach ($pages as $page)
                        @if ($prevPage && $page > $prevPage + 1)
                            <span class="px-2 text-xs text-gray-400">…</span>
                        @endif

                        @if ($page == $current)
                            <span class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                                {{ $page }}
                            </span>
                        @else
                            <button wire:click="gotoPage({{ $page }}, '{{ $paginador->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                                {{ $page }}
                            </button>
                        @endif

                        @php $prevPage = $page; @endphp
                    @endforeach

                    {{-- Botón siguiente --}}
                    @if ($paginador->hasMorePages())
                        <button wire:click="nextPage('{{ $paginador->getPageName() }}')"
                                wire:loading.attr="disabled"
                                class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                            &gt;&gt;
                        </button>
                    @else
                        <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">&gt;&gt;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif

{{-- Indicador de carga --}}
<div wire:loading class="text-center py-2">
    <span class="text-sm text-blue-600 font-medium">Cargando...</span>
</div>
