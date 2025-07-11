@props(['paginador'])
{{-- SIEMPRE visible: selector de cantidad por página --}}
<div class="m-4 text-center">
    <form method="GET" id="perPageForm" class="inline-flex items-center justify-center gap-2 text-sm">
        <label for="perPage" class="text-gray-600">Mostrar</label>
        <select name="per_page" id="perPage" class="border border-gray-300 rounded px-2 py-1 text-sm"
            onchange="document.getElementById('perPageForm').submit()">
            @foreach ([10, 25, 50, 100] as $option)
                <option value="{{ $option }}" @selected(request('per_page', $paginador->perPage()) == $option)>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-gray-600">por página</span>

        {{-- Mantener otros filtros --}}
        @foreach (request()->except('per_page', 'page') as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
</div>
@if ($paginador->hasPages())
    <div class="mt-6 space-y-3 text-center">

        {{-- Texto resumen --}}
        <div class="text-sm text-gray-600">
            Mostrando
            <span class="font-semibold">{{ $paginador->firstItem() }}</span>
            a
            <span class="font-semibold">{{ $paginador->lastItem() }}</span>
            de
            <span class="font-semibold">{{ $paginador->total() }}</span>
            resultados
        </div>

        {{-- Paginación --}}
        <div class="flex justify-center">
            <div class="inline-flex flex-wrap gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow-sm">
                {{-- Botón anterior --}}
                @if ($paginador->onFirstPage())
                    <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">
                        << </span>
                        @else
                            <a href="{{ $paginador->previousPageUrl() }}"
                                class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">
                                << </a>
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
                        <span
                            class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginador->url($page) }}"
                            class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">
                            {{ $page }}
                        </a>
                    @endif

                    @php $prevPage = $page; @endphp
                @endforeach

                {{-- Botón siguiente --}}
                @if ($paginador->hasMorePages())
                    <a href="{{ $paginador->nextPageUrl() }}"
                        class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">>></a>
                @else
                    <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">>></span>
                @endif
            </div>
        </div>
    </div>
@endif
