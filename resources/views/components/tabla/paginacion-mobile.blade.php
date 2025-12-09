@props([
    'paginador' => null, // Paginador de Laravel (opcional)
    'currentPage' => 1,
    'totalPages' => 1,
    'totalResults' => 0,
    'firstItem' => 0,
    'lastItem' => 0,
    'route' => '',
    'requestParams' => [],
])

@php
    // Si se pasa un paginador de Laravel, usarlo
    if ($paginador) {
        $currentPage = $paginador->currentPage();
        $totalPages = $paginador->lastPage();
        $totalResults = $paginador->total();
        $firstItem = $paginador->firstItem() ?? 0;
        $lastItem = $paginador->lastItem() ?? 0;
    }
@endphp

@if ($totalResults > 0)
    <div class="space-y-2 py-2 pb-4">
        {{-- Información de resultados --}}
        <div class="text-center">
            <p class="text-xs text-gray-700">
                Mostrando
                <span class="font-semibold">{{ $firstItem }}</span>
                -
                <span class="font-semibold">{{ $lastItem }}</span>
                de
                <span class="font-semibold">{{ $totalResults }}</span>
                resultados
            </p>
        </div>

        {{-- Navegación de páginas --}}
        <div class="flex justify-center items-center gap-2">
            @php
                $current = $currentPage;
                $last = $totalPages;
                $range = 1; // Mostrar 1 página antes y después en móvil
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

            {{-- Botón anterior --}}
            @if ($currentPage > 1)
                @php
                    $prevUrl = $paginador
                        ? $paginador->previousPageUrl()
                        : route($route, array_merge($requestParams, ['mpage' => $currentPage - 1]));
                @endphp
                <a href="{{ $prevUrl }}"
                    class="px-3 py-1.5 text-xs text-gray-700 font-semibold bg-white border border-gray-200 rounded hover:bg-gray-100">
                    &laquo;
                </a>
            @else
                <span class="px-3 py-1.5 text-xs text-gray-400 bg-gray-50 border border-gray-100 rounded">
                    &laquo;
                </span>
            @endif

            {{-- Páginas --}}
            @php $prevPage = 0; @endphp
            @foreach ($pages as $page)
                {{-- Mostrar puntos suspensivos si hay un salto --}}
                @if ($prevPage > 0 && $page > $prevPage + 1)
                    <span class="px-1 text-xs text-gray-400">&hellip;</span>
                @endif

                {{-- Página actual --}}
                @if ($page == $current)
                    <span
                        class="px-3 py-1.5 text-xs font-bold bg-blue-500 text-white rounded shadow border border-blue-400">
                        {{ $page }}
                    </span>
                @else
                    @php
                        $pageUrl = $paginador
                            ? $paginador->url($page)
                            : route($route, array_merge($requestParams, ['mpage' => $page]));
                    @endphp
                    <a href="{{ $pageUrl }}"
                        class="px-3 py-1.5 text-xs text-gray-700 bg-white border border-gray-200 rounded hover:bg-gray-100">
                        {{ $page }}
                    </a>
                @endif

                @php $prevPage = $page; @endphp
            @endforeach

            {{-- Botón siguiente --}}
            @if ($currentPage < $totalPages)
                @php
                    $nextUrl = $paginador
                        ? $paginador->nextPageUrl()
                        : route($route, array_merge($requestParams, ['mpage' => $currentPage + 1]));
                @endphp
                <a href="{{ $nextUrl }}"
                    class="px-3 py-1.5 text-xs text-gray-700 font-semibold bg-white border border-gray-200 rounded hover:bg-gray-100">
                    &raquo;
                </a>
            @else
                <span class="px-3 py-1.5 text-xs text-gray-400 bg-gray-50 border border-gray-100 rounded">
                    &raquo;
                </span>
            @endif
        </div>
    </div>
@endif
