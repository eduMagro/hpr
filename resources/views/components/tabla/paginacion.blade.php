@props(['paginador', 'perPageName' => 'per_page'])

<div class="m-4 text-center">
    <form method="GET" id="perPageForm" class="inline-flex items-center justify-center gap-2 text-sm">
        <label for="perPage" class="text-gray-600 dark:text-gray-400">Mostrar</label>
        <select name="{{ $perPageName }}" id="perPage"
            class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            onchange="document.getElementById('perPageForm').submit()">
            @foreach ([10, 25, 50, 100] as $option)
                <option value="{{ $option }}" @selected(request($perPageName, $paginador->perPage()) == $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-gray-600 dark:text-gray-400">por página</span>
        @foreach (request()->except($perPageName, $paginador->getPageName()) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
</div>

@if ($paginador->hasPages())
    <div class="mt-6 space-y-3 text-center">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando
            <span class="font-semibold">{{ $paginador->firstItem() }}</span>
            a
            <span class="font-semibold">{{ $paginador->lastItem() }}</span>
            de
            <span class="font-semibold">{{ $paginador->total() }}</span>
            resultados
        </div>

        <div class="flex justify-center">
            <nav class="inline-flex flex-wrap gap-1 bg-white dark:bg-gray-800 px-2 py-1 mb-6 rounded-md shadow-sm">
                {{-- Botón anterior --}}
                @if ($paginador->onFirstPage())
                    <span class="px-3 py-1 text-xs text-gray-400 dark:text-gray-500 cursor-not-allowed">&laquo;</span>
                @else
                    <a href="{{ $paginador->previousPageUrl() }}"
                        class="px-3 py-1 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700 rounded transition">
                        &laquo;
                    </a>
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
                        <span class="px-2 text-xs text-gray-400 dark:text-gray-500 select-none">&hellip;</span>
                    @endif

                    {{-- Página actual --}}
                    @if ($page == $current)
                        <span class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginador->url($page) }}"
                            class="px-3 py-1 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700 rounded transition">
                            {{ $page }}
                        </a>
                    @endif

                    @php $prevPage = $page; @endphp
                @endforeach

                {{-- Botón siguiente --}}
                @if ($paginador->hasMorePages())
                    <a href="{{ $paginador->nextPageUrl() }}"
                        class="px-3 py-1 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-700 rounded transition">&raquo;</a>
                @else
                    <span class="px-3 py-1 text-xs text-gray-400 dark:text-gray-500 cursor-not-allowed">&raquo;</span>
                @endif
            </nav>
        </div>
    </div>
@endif