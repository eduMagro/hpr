<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>

    <div class="w-full md:p-4 sm:p-2">
        <!-- Desktop -->
        <div class="hidden md:block">
            @livewire('elementos-table')
        </div>

        <!-- Móvil -->
        <div class="block md:hidden space-y-4">
            <div class="bg-gradient-to-r from-gray-900 to-gray-700 text-white rounded-2xl p-4 shadow-lg">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-300">Elementos</p>
                <p class="text-xs text-gray-200 mt-1">Consulta y filtra los elementos de producción.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-3">
                <form method="GET" action="{{ route('elementos.index') }}" class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Código</label>
                            <input type="text" name="codigo" value="{{ request('codigo') }}" placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Subetiqueta</label>
                            <input type="text" name="subetiqueta" value="{{ request('subetiqueta') }}"
                                placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Planilla</label>
                            <input type="text" name="codigo_planilla" value="{{ request('codigo_planilla') }}"
                                placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Estado</label>
                            <select name="estado"
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700">
                                <option value="">Todos</option>
                                <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendiente</option>
                                <option value="fabricando" @selected(request('estado') === 'fabricando')>Fabricando</option>
                                <option value="fabricado" @selected(request('estado') === 'fabricado')>Fabricado</option>
                                <option value="montaje" @selected(request('estado') === 'montaje')>Montaje</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('elementos.index') }}" class="text-xs text-gray-600 hover:text-gray-900">Limpiar</a>
                        <button type="submit"
                            class="bg-gray-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-gray-800">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            @php
                $mobilePage = max(1, (int) request('mpage', 1));
                $perPage = 10;

                $query = \App\Models\Elemento::with(['planilla', 'maquina', 'etiquetaRelacion']);

                if (request('codigo')) {
                    $query->where('codigo', 'like', '%' . request('codigo') . '%');
                }
                if (request('subetiqueta')) {
                    $query->where('etiqueta_sub_id', 'like', '%' . request('subetiqueta') . '%');
                }
                if (request('codigo_planilla')) {
                    $query->whereHas('planilla', function ($q) {
                        $q->where('codigo', 'like', '%' . request('codigo_planilla') . '%');
                    });
                }
                if (request('estado')) {
                    $query->where('estado', request('estado'));
                }

                $elementosMobile = $query
                    ->latest()
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                $hayMasElementos = $elementosMobile->count() > $perPage;
                if ($hayMasElementos) {
                    $elementosMobile = $elementosMobile->take($perPage);
                }
            @endphp

            <div class="space-y-2">
                @forelse ($elementosMobile as $elemento)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white px-3 py-2 flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] text-gray-300">Código</p>
                                <h3 class="text-sm font-semibold tracking-tight truncate">
                                    {{ $elemento->codigo ?? '—' }}</h3>
                                <p class="text-[9px] text-gray-300 mt-0.5 truncate">
                                    Subetiqueta: {{ $elemento->etiqueta_sub_id ?? '—' }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold bg-white/10 border border-white/20">
                                    {{ strtoupper($elemento->estado ?? '—') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Planilla</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($elemento->planilla)->codigo ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Figura</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ $elemento->figura ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Barras</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $elemento->barras ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Peso</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $elemento->peso ? number_format($elemento->peso, 2) . ' kg' : '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Longitud</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $elemento->longitud ? number_format($elemento->longitud / 100, 2) . ' m' : '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Máquina</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($elemento->maquina)->nombre ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-1 text-[10px] font-semibold pt-1">
                                <a href="{{ route('elementos.show', $elemento->id) }}"
                                    class="px-2 py-1 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">
                                    Detalle
                                </a>
                                @if ($elemento->planilla_id)
                                    <a href="{{ route('planillas.show', $elemento->planilla_id) }}"
                                        class="px-2 py-1 rounded-lg bg-blue-200 text-blue-900 hover:bg-blue-300">
                                        Planilla
                                    </a>
                                @endif
                                @if ($elemento->etiqueta_id)
                                    <a href="{{ route('etiquetas.index', ['codigo' => optional($elemento->etiquetaRelacion)->codigo]) }}"
                                        class="px-2 py-1 rounded-lg bg-slate-200 text-slate-900 hover:bg-slate-300">
                                        Etiqueta
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-600">
                        No hay elementos disponibles.
                    </div>
                @endforelse

                @if ($elementosMobile->count() > 0)
                    <div class="flex justify-between items-center gap-2 pt-2">
                        @if ($mobilePage > 1)
                            <a href="{{ route('elementos.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage - 1])) }}"
                                class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold border border-gray-200 hover:bg-gray-200">
                                ← Anterior
                            </a>
                        @else
                            <span
                                class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-400 text-xs font-semibold border border-gray-100">
                                ← Anterior
                            </span>
                        @endif

                        <span class="text-xs text-gray-600">Página {{ $mobilePage }}</span>

                        @if ($hayMasElementos)
                            <a href="{{ route('elementos.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage + 1])) }}"
                                class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-xs font-semibold border border-gray-200 hover:bg-gray-200">
                                Siguiente →
                            </a>
                        @else
                            <span
                                class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-400 text-xs font-semibold border border-gray-100">
                                Siguiente →
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
