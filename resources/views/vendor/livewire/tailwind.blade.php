<div>
@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp
    @if ($paginator->hasPages())
        {{-- Selector de cantidad por página --}}
        <div class="m-4 text-center">
            <div class="inline-flex items-center justify-center gap-2 text-sm">
                <label for="perPageSelect" class="text-gray-600">Mostrar</label>
                <select wire:model.live="perPage"
                        id="perPageSelect"
                        class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-gray-600">por página</span>
            </div>
        </div>

        <div class="mt-6 space-y-3 text-center">
            {{-- Texto resumen --}}
            <div class="text-sm text-gray-600">
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
                <nav class="inline-flex items-center gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow-sm">
                    {{-- Botón Primera Página --}}
                    @if ($paginator->currentPage() > 2)
                        <button type="button"
                                wire:click="gotoPage(1, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50"
                                title="Primera página">
                            &laquo;&laquo;
                        </button>
                    @endif

                    {{-- Botón anterior --}}
                    @if ($paginator->onFirstPage())
                        <span class="px-2 py-1 text-xs text-gray-400 cursor-not-allowed">
                            &laquo;
                        </span>
                    @else
                        <button type="button"
                                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                            &laquo;
                        </button>
                    @endif

                    {{-- Elementos de paginación compactados --}}
                    @php
                        $start = max($paginator->currentPage() - 1, 1);
                        $end = min($paginator->currentPage() + 1, $paginator->lastPage());
                    @endphp

                    @if ($start > 1)
                        <button type="button"
                                wire:click="gotoPage(1, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                            1
                        </button>
                        @if ($start > 2)
                            <span class="px-1 text-xs text-gray-400 select-none">&hellip;</span>
                        @endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $paginator->currentPage())
                            <span class="px-2.5 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                                {{ $page }}
                            </span>
                        @else
                            <button type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    wire:loading.attr="disabled"
                                    class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                                {{ $page }}
                            </button>
                        @endif
                    @endfor

                    @if ($end < $paginator->lastPage())
                        @if ($end < $paginator->lastPage() - 1)
                            <span class="px-1 text-xs text-gray-400 select-none">&hellip;</span>
                        @endif
                        <button type="button"
                                wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                            {{ $paginator->lastPage() }}
                        </button>
                    @endif

                    {{-- Botón siguiente --}}
                    @if ($paginator->hasMorePages())
                        <button type="button"
                                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                            &raquo;
                        </button>
                    @else
                        <span class="px-2 py-1 text-xs text-gray-400 cursor-not-allowed">
                            &raquo;
                        </span>
                    @endif

                    {{-- Botón Última Página --}}
                    @if ($paginator->currentPage() < $paginator->lastPage() - 1)
                        <button type="button"
                                wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50"
                                title="Última página">
                            &raquo;&raquo;
                        </button>
                    @endif
                </nav>
            </div>
        </div>

        {{-- Indicador de carga --}}
        <div wire:loading class="text-center py-2">
            <span class="text-sm text-blue-600 font-medium">Cargando...</span>
        </div>
    @endif
</div>
