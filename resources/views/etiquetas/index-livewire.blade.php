<x-app-layout>
    <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>

    <div class="w-full md:p-4 sm:p-2">
        <!-- Desktop: tabla Livewire -->
        <div class="hidden md:block">
            @livewire('etiquetas-table')
        </div>

        <!-- Móvil: tarjetas -->
        <div class="block md:hidden space-y-2" x-data="{ filtrosAbiertos: false }">
            <div>
                <div class="bg-gradient-to-tr from-blue-700 to-blue-600 text-white p-3 shadow-lg cursor-pointer"
                    :class="filtrosAbiertos ? 'rounded-t-xl' : 'rounded-xl'" @click="filtrosAbiertos = !filtrosAbiertos">
                    <div class="flex items-center gap-2">
                        {{-- Ícono toggle filtros --}}
                        <div class="text-white">
                            <svg x-show="!filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg x-show="filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 15l7-7 7 7" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold tracking-wide">Etiquetas</p>
                    </div>
                </div>

                <div x-show="filtrosAbiertos" x-collapse
                    class="bg-white border border-gray-200 rounded-b-xl shadow-sm p-3">
                    <form method="GET" action="{{ route('etiquetas.index') }}" class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1 col-span-2">
                                <label class="text-[10px] font-semibold text-gray-700">Código / SubEtiqueta</label>
                                <input type="text" name="codigo" value="{{ request('codigo') }}"
                                    placeholder="Buscar por código o sub-etiqueta..."
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
                        <div class="flex items-center justify-end gap-2">
                            @include('components.tabla.limpiar-filtros', [
                                'href' => route('etiquetas.index'),
                            ])
                            <button type="submit"
                                class="bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-gray-800">
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $mobilePage = max(1, (int) request('mpage', 1));
                $perPage = 10;

                $query = \App\Models\Etiqueta::with(['planilla.cliente', 'planilla.obra', 'paquete']);

                if (request('codigo')) {
                    $query->where(function ($q) {
                        $q->where('codigo', 'like', '%' . request('codigo') . '%')->orWhere(
                            'etiqueta_sub_id',
                            'like',
                            '%' . request('codigo') . '%',
                        );
                    });
                }
                if (request('codigo_planilla')) {
                    $query->whereHas('planilla', function ($q) {
                        $q->where('codigo', 'like', '%' . request('codigo_planilla') . '%');
                    });
                }
                if (request('estado')) {
                    $query->where('estado', request('estado'));
                }

                // Obtener el total de resultados
                $totalResultados = $query->count();
                $totalPaginas = $totalResultados > 0 ? (int) ceil($totalResultados / $perPage) : 1;
                $mobilePage = min($mobilePage, $totalPaginas); // No permitir páginas mayores al total

                $etiquetasMobile = $query
                    ->latest()
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                $hayMasEtiquetas = $etiquetasMobile->count() > $perPage;
                if ($hayMasEtiquetas) {
                    $etiquetasMobile = $etiquetasMobile->take($perPage);
                }

                // Calcular firstItem y lastItem
                $firstItem = ($mobilePage - 1) * $perPage + 1;
                $lastItem = min($mobilePage * $perPage, $totalResultados);
            @endphp

            <div class="space-y-2">
                @forelse ($etiquetasMobile as $etiqueta)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-blue-700 to-blue-600 text-white px-3 py-2 flex items-center justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold tracking-tight truncate">
                                    {{ $etiqueta->etiqueta_sub_id }}</h3>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/10 border border-white/20">
                                    {{ strtoupper($etiqueta->estado ?? '—') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="flex flex-wrap justify-around gap-2 text-xs">
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
                                        class="px-2 py-1 rounded-lg bg-red-600 text-red-800 hover:bg-red-300">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path
                                                    d="M3 6.38597C3 5.90152 3.34538 5.50879 3.77143 5.50879L6.43567 5.50832C6.96502 5.49306 7.43202 5.11033 7.61214 4.54412C7.61688 4.52923 7.62232 4.51087 7.64185 4.44424L7.75665 4.05256C7.8269 3.81241 7.8881 3.60318 7.97375 3.41617C8.31209 2.67736 8.93808 2.16432 9.66147 2.03297C9.84457 1.99972 10.0385 1.99986 10.2611 2.00002H13.7391C13.9617 1.99986 14.1556 1.99972 14.3387 2.03297C15.0621 2.16432 15.6881 2.67736 16.0264 3.41617C16.1121 3.60318 16.1733 3.81241 16.2435 4.05256L16.3583 4.44424C16.3778 4.51087 16.3833 4.52923 16.388 4.54412C16.5682 5.11033 17.1278 5.49353 17.6571 5.50879H20.2286C20.6546 5.50879 21 5.90152 21 6.38597C21 6.87043 20.6546 7.26316 20.2286 7.26316H3.77143C3.34538 7.26316 3 6.87043 3 6.38597Z"
                                                    fill="#ffffff"></path>
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12404C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001ZM10.2463 12.1886C10.2051 11.7548 9.83753 11.4382 9.42537 11.4816C9.01321 11.525 8.71251 11.9119 8.75372 12.3457L9.25372 17.6089C9.29494 18.0427 9.66247 18.3593 10.0746 18.3159C10.4868 18.2725 10.7875 17.8856 10.7463 17.4518L10.2463 12.1886ZM14.5746 11.4816C14.9868 11.525 15.2875 11.9119 15.2463 12.3457L14.7463 17.6089C14.7051 18.0427 14.3375 18.3593 13.9254 18.3159C13.5132 18.2725 13.2125 17.8856 13.2537 17.4518L13.7537 12.1886C13.7949 11.7548 14.1625 11.4382 14.5746 11.4816Z"
                                                    fill="#ffffff"></path>
                                            </g>
                                        </svg>
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

                {{-- Paginación móvil --}}
                <x-tabla.paginacion-mobile :currentPage="$mobilePage" :totalPages="$totalPaginas" :totalResults="$totalResultados" :firstItem="$firstItem"
                    :lastItem="$lastItem" route="etiquetas.index" :requestParams="request()->except('mpage')" />
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
