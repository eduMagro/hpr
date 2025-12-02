@props(['paginador', 'perPageName' => 'per_page'])

<div class="m-4 text-center">
    <form method="GET" id="perPageForm"
        class="inline-flex items-center justify-center gap-2 text-sm bg-white shadow-sm px-3 py-2 rounded-lg border border-gray-200">
        <label for="perPage" class="text-gray-700">Mostrar</label>
        <select name="{{ $perPageName }}" id="perPage"
            class="border border-gray-300 rounded px-3 py-2 text-sm text-gray-800 bg-white focus:ring-2 focus:ring-gray-700 focus:border-gray-800"
            onchange="document.getElementById('perPageForm').submit()">
            @foreach ([10, 25, 50, 100] as $option)
                <option value="{{ $option }}" @selected(request($perPageName, $paginador->perPage()) == $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-gray-700">por página</span>
        @foreach (request()->except($perPageName, $paginador->getPageName()) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
</div>

@if ($paginador->hasPages())
    <div class="mt-6 space-y-3 text-center">
        <div class="text-sm text-gray-700">
            Mostrando
            <span class="font-semibold">{{ $paginador->firstItem() }}</span>
            a
            <span class="font-semibold">{{ $paginador->lastItem() }}</span>
            de
            <span class="font-semibold">{{ $paginador->total() }}</span>
            resultados
        </div>

        <div class="flex justify-center">
            <div
                class="inline-flex items-center gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow border border-gray-200">
                @if ($paginador->currentPage() > 2)
                    <a href="{{ $paginador->url(1) }}"
                        class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200"
                        title="Primera pagina">
                        &laquo;&laquo;
                    </a>
                @endif

                @if ($paginador->onFirstPage())
                    <span class="px-2 py-1 text-xs text-gray-400 cursor-not-allowed">&laquo;</span>
                @else
                    <a href="{{ $paginador->previousPageUrl() }}"
                        class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200">
                        &laquo;
                    </a>
                @endif

                @php
                    $current = $paginador->currentPage();
                    $last = $paginador->lastPage();
                    $start = max($current - 1, 1);
                    $end = min($current + 1, $last);
                @endphp

                @if ($start > 1)
                    <a href="{{ $paginador->url(1) }}"
                        class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200">1</a>
                    @if ($start > 2)
                        <span class="px-1 text-xs text-gray-400 select-none">&hellip;</span>
                    @endif
                @endif

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $current)
                        <span
                            class="px-3 py-1 text-xs font-bold bg-gray-800 text-white rounded shadow border border-gray-800">{{ $page }}</span>
                    @else
                        <a href="{{ $paginador->url($page) }}"
                            class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200">{{ $page }}</a>
                    @endif
                @endfor

                @if ($end < $paginador->lastPage())
                    @if ($end < $paginador->lastPage() - 1)
                        <span class="px-1 text-xs text-gray-400 select-none">&hellip;</span>
                    @endif
                    <a href="{{ $paginador->url($paginador->lastPage()) }}"
                        class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200">{{ $paginador->lastPage() }}</a>
                @endif

                @if ($paginador->hasMorePages())
                    <a href="{{ $paginador->nextPageUrl() }}"
                        class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200">&raquo;</a>
                @else
                    <span class="px-2 py-1 text-xs text-gray-400 cursor-not-allowed">&raquo;</span>
                @endif

                @if ($paginador->currentPage() < $paginador->lastPage() - 1)
                    <a href="{{ $paginador->url($paginador->lastPage()) }}"
                        class="px-3 py-1 text-xs text-gray-700 hover:bg-gray-100 rounded transition border border-transparent hover:border-gray-200"
                        title="Ultima pagina">
                        &raquo;&raquo;
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
