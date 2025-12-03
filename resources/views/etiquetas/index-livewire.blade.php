<x-app-layout>
    <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>

    <div class="w-full p-4 sm:p-2">
        <!-- Desktop: tabla Livewire -->
        <div class="hidden md:block">
            @livewire('etiquetas-table')
        </div>

        <!-- Móvil: tarjetas -->
        <div class="block md:hidden space-y-4">
            <div class="bg-gradient-to-r from-gray-900 to-gray-700 text-white rounded-2xl p-4 shadow-lg">
                <p class="text-sm font-semibold uppercase tracking-wide text-gray-300">Etiquetas</p>
                <p class="text-xs text-gray-200 mt-1">Filtra por código, subetiqueta, planilla o estado.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-3">
                <form method="GET" action="{{ route('etiquetas.index') }}" class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">Código</label>
                            <input type="text" name="codigo" value="{{ request('codigo') }}" placeholder="Buscar..."
                                class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold text-gray-700">SubEtiqueta</label>
                            <input type="text" name="etiqueta_sub_id" value="{{ request('etiqueta_sub_id') }}"
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
                                <option value="ensamblando" @selected(request('estado') === 'ensamblando')>Ensamblando</option>
                                <option value="soldando" @selected(request('estado') === 'soldando')>Soldando</option>
                                <option value="completada" @selected(request('estado') === 'completada')>Completada</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('etiquetas.index') }}"
                            class="text-xs text-gray-600 hover:text-gray-900">Limpiar</a>
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

                $query = \App\Models\Etiqueta::with(['planilla.cliente', 'planilla.obra', 'paquete']);

                if (request('codigo')) {
                    $query->where('codigo', 'like', '%' . request('codigo') . '%');
                }
                if (request('etiqueta_sub_id')) {
                    $query->where('etiqueta_sub_id', 'like', '%' . request('etiqueta_sub_id') . '%');
                }
                if (request('codigo_planilla')) {
                    $query->whereHas('planilla', function ($q) {
                        $q->where('codigo', 'like', '%' . request('codigo_planilla') . '%');
                    });
                }
                if (request('estado')) {
                    $query->where('estado', request('estado'));
                }

                $etiquetasMobile = $query
                    ->latest()
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                $hayMasEtiquetas = $etiquetasMobile->count() > $perPage;
                if ($hayMasEtiquetas) {
                    $etiquetasMobile = $etiquetasMobile->take($perPage);
                }
            @endphp

            <div class="space-y-2">
                @forelse ($etiquetasMobile as $etiqueta)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white px-3 py-2 flex items-center justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] text-gray-300">SubEtiqueta</p>
                                <h3 class="text-sm font-semibold tracking-tight truncate">
                                    {{ $etiqueta->etiqueta_sub_id }}</h3>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold bg-white/10 border border-white/20">
                                    {{ strtoupper($etiqueta->estado ?? '—') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Planilla</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($etiqueta->planilla)->codigo_limpio ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Peso</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ number_format($etiqueta->peso ?? 0, 2) }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Paquete</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($etiqueta->paquete)->codigo ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Cliente</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional(optional($etiqueta->planilla)->cliente)->empresa ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-1 text-[10px] font-semibold pt-1">
                                <button type="button"
                                    class="px-2 py-1 rounded-lg bg-amber-200 text-amber-900 hover:bg-amber-300"
                                    onclick="abrirModalEditarEtiqueta({
                                        id: '{{ $etiqueta->id }}',
                                        codigo: '{{ $etiqueta->codigo }}',
                                        sub: '{{ $etiqueta->etiqueta_sub_id }}',
                                        nombre: @js($etiqueta->nombre),
                                        peso: '{{ $etiqueta->peso }}',
                                        estado: '{{ $etiqueta->estado }}',
                                        planilla: '{{ optional($etiqueta->planilla)->codigo_limpio ?? '' }}'
                                    })">
                                    Modificar
                                </button>
                                @if ($etiqueta->planilla_id)
                                    <a href="{{ route('planillas.show', $etiqueta->planilla_id) }}"
                                        class="px-2 py-1 rounded-lg bg-blue-200 text-blue-900 hover:bg-blue-300">
                                        Planilla
                                    </a>
                                @endif
                                @if (isset($etiqueta->paquete->codigo))
                                    <a href="{{ route('paquetes.index', ['codigo' => $etiqueta->paquete->codigo]) }}"
                                        class="px-2 py-1 rounded-lg bg-emerald-200 text-emerald-900 hover:bg-emerald-300">
                                        Paquete
                                    </a>
                                @endif
                                <form action="{{ route('etiquetas.destroy', $etiqueta->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Eliminar esta etiqueta? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2 py-1 rounded-lg bg-red-200 text-red-800 hover:bg-red-300">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-600">
                        No hay etiquetas disponibles.
                    </div>
                @endforelse

                @if ($etiquetasMobile->count() > 0)
                    <div class="flex justify-between items-center gap-2 pt-2">
                        @if ($mobilePage > 1)
                            <a href="{{ route('etiquetas.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage - 1])) }}"
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

                        @if ($hayMasEtiquetas)
                            <a href="{{ route('etiquetas.index', array_merge(request()->except('mpage'), ['mpage' => $mobilePage + 1])) }}"
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

    <!-- Modal editar etiqueta -->
    <div id="modal-editar-etiqueta"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm px-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div
                class="bg-gradient-to-r from-gray-900 to-gray-700 text-white px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-300">Editar etiqueta</p>
                    <h3 class="text-sm font-semibold" id="titulo-modal-etiqueta">—</h3>
                </div>
                <button type="button" class="text-white/80 hover:text-white" onclick="cerrarModalEditarEtiqueta()">
                    ✕
                </button>
            </div>
            <form id="form-modal-etiqueta" method="POST" action="" class="p-4 space-y-3">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold text-gray-600 uppercase">Código</label>
                        <input id="modal-etiqueta-codigo" name="codigo" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-gray-800 focus:outline-none" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold text-gray-600 uppercase">SubEtiqueta</label>
                        <input id="modal-etiqueta-sub" name="etiqueta_sub_id" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-gray-800 focus:outline-none" />
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold text-gray-600 uppercase">Nombre</label>
                    <input id="modal-etiqueta-nombre" name="nombre" type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-gray-800 focus:outline-none" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold text-gray-600 uppercase">Peso (kg)</label>
                        <input id="modal-etiqueta-peso" name="peso" type="number" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-gray-800 focus:outline-none" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold text-gray-600 uppercase">Estado</label>
                        <select id="modal-etiqueta-estado" name="estado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-gray-800 focus:outline-none">
                            <option value="pendiente">Pendiente</option>
                            <option value="fabricando">Fabricando</option>
                            <option value="ensamblando">Ensamblando</option>
                            <option value="soldando">Soldando</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold text-gray-600 uppercase">Planilla</label>
                    <input id="modal-etiqueta-planilla" type="text" readonly
                        class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-xs text-gray-700" />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="cerrarModalEditarEtiqueta()"
                        class="px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-3 py-2 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            if (window.etiquetasModalIniciado) return;
            window.etiquetasModalIniciado = true;

            const modal = () => document.getElementById('modal-editar-etiqueta');
            const form = () => document.getElementById('form-modal-etiqueta');

            window.abrirModalEditarEtiqueta = (datos) => {
                const m = modal();
                if (!m) return;

                document.getElementById('modal-etiqueta-codigo').value = datos.codigo || '';
                document.getElementById('modal-etiqueta-sub').value = datos.sub || '';
                document.getElementById('modal-etiqueta-nombre').value = datos.nombre || '';
                document.getElementById('modal-etiqueta-peso').value = datos.peso || '';
                document.getElementById('modal-etiqueta-estado').value = datos.estado || 'pendiente';
                document.getElementById('modal-etiqueta-planilla').value = datos.planilla || '—';
                document.getElementById('titulo-modal-etiqueta').innerText = datos.codigo || 'Etiqueta';

                const base = "{{ url('/etiquetas') }}";
                form().action = `${base}/${datos.id}`;

                m.classList.remove('hidden');
                m.classList.add('flex');
            };

            window.cerrarModalEditarEtiqueta = () => {
                const m = modal();
                if (!m) return;
                m.classList.add('hidden');
                m.classList.remove('flex');
            };
        })();
    </script>
</x-app-layout>
