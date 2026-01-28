@php
    if (!isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
                   (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
                JS
        : '';
@endphp
<div>
    @if ($paginator->hasPages())
        {{-- Selector de cantidad por página --}}
        <div class="m-4 text-center">
            <div class="inline-flex items-center justify-center gap-2 text-sm">
                <label for="perPageSelect" class="text-gray-600 dark:text-gray-400">Mostrar</label>
                <select wire:model.live="perPage" id="perPageSelect"
                    class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 pr-7 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-gray-600 dark:text-gray-400">por página</span>
            </div>
        </div>

        <div class="mt-6 space-y-3 text-center">
            {{-- Texto resumen --}}
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Mostrando
                <span class="font-semibold">{{ $paginator->firstItem() ?? 0 }}</span>
                a
                <span class="font-semibold">{{ $paginator->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold">{{ $paginator->total() }}</span>
                resultados
            </div>

            {{-- Navegación de paginación --}}
            <div class="flex justify-center">
                <nav class="inline-flex flex-wrap gap-1 bg-white dark:bg-gray-800 px-2 py-1 mb-6 rounded-md shadow-sm">
                    {{-- Botón anterior --}}
                    @if ($paginator->onFirstPage())
                        <span class="px-3 py-1 text-xs text-gray-400 dark:text-gray-500 cursor-not-allowed">
                            &laquo;
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                            class="px-3 py-1 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700 rounded transition disabled:opacity-50">
                            &laquo;
                        </button>
                    @endif

                    {{-- Lógica de paginación con recorte --}}
                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
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
                            <span class="px-2 text-xs text-gray-400 dark:text-gray-500 select-none">&hellip;</span>
                        @endif

                        {{-- Página actual --}}
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                                {{ $page }}
                            </span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                                class="px-3 py-1 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700 rounded transition disabled:opacity-50">
                                {{ $page }}
                            </button>
                        @endif

                        @php $prevPage = $page; @endphp
                    @endforeach

                    {{-- Botón siguiente --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                            class="px-3 py-1 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700 rounded transition disabled:opacity-50">
                            &raquo;
                        </button>
                    @else
                        <span class="px-3 py-1 text-xs text-gray-400 dark:text-gray-500 cursor-not-allowed">
                            &raquo;
                        </span>
                    @endif
                </nav>
            </div>
        </div>

        {{-- Indicador de carga --}}
        <div wire:loading class="text-center py-2">
            <span class="text-sm text-blue-600 dark:text-blue-400 font-medium">Cargando...</span>
        </div>
    @endif
</div>