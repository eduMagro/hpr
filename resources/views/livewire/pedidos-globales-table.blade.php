<div class="w-full">
    {{-- Header con Stats --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-6">
        <div class="flex gap-3">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 max-md:w-full md:min-w-[120px]">
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total</div>
                <div class="text-2xl font-black text-slate-900">{{ $pedidosGlobales->total() }}</div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 max-md:w-full md:min-w-[120px]">
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Pendientes</div>
                <div class="text-2xl font-black text-amber-500">{{ $totalPendientes ?? 0 }}</div>
            </div>
            <div
                class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-4 shadow-lg shadow-emerald-200 max-md:w-full md:min-w-[120px] text-white">
                <div class="text-[9px] font-black text-emerald-100 uppercase tracking-widest mb-0.5">Completados</div>
                <div class="text-2xl font-black">{{ $totalCompletados ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Barra de Filtros --}}
    <div
        class="bg-gradient-to-r from-slate-800 to-slate-700 p-3 lg:p-4 rounded-2xl mb-6 flex flex-wrap items-center gap-2 lg:gap-4 shadow-lg overflow-hidden">
        {{-- Buscador principal --}}
        <div class="w-full lg:flex-1 lg:min-w-[250px]">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input wire:model.live.debounce.300ms="codigo" type="text" placeholder="Buscar por código..."
                    class="w-full pl-10 pr-3 py-2 bg-white/95 backdrop-blur border-0 focus:ring-4 focus:ring-white/30 rounded-xl text-sm font-semibold text-slate-700 placeholder:text-slate-400 shadow-sm">
            </div>
        </div>

        {{-- Filtros secundarios --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 bg-white/95 px-2 py-1.5 rounded-lg shadow-sm">
                <span
                    class="text-[8px] font-black text-slate-400 uppercase tracking-widest hidden sm:inline">Fab.</span>
                <input wire:model.live.debounce.300ms="fabricante" type="text"
                    class="w-16 sm:w-20 text-xs font-bold text-slate-700 bg-transparent border-0 focus:ring-0 p-0"
                    placeholder="Fabricante">
            </div>

            <div class="flex items-center gap-1.5 bg-white/95 px-2 py-1.5 rounded-lg shadow-sm">
                <span
                    class="text-[8px] font-black text-slate-400 uppercase tracking-widest hidden sm:inline">Dist.</span>
                <input wire:model.live.debounce.300ms="distribuidor" type="text"
                    class="w-16 sm:w-20 text-xs font-bold text-slate-700 bg-transparent border-0 focus:ring-0 p-0"
                    placeholder="Distribuidor">
            </div>

            <div class="flex items-center gap-1.5 bg-white/95 px-2 py-1.5 rounded-lg shadow-sm">
                <span
                    class="text-[8px] font-black text-slate-400 uppercase tracking-widest hidden sm:inline">Estado</span>
                <select wire:model.live="estado"
                    class="text-xs font-bold text-slate-700 bg-transparent border-0 focus:ring-0 cursor-pointer p-0">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en curso">En curso</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>

            {{-- Botones de Ordenación (ocultos en móvil) --}}
            <div class="hidden lg:flex items-center gap-1 bg-white/10 px-2 py-1 rounded-xl">
                <span class="text-[8px] font-black text-white/60 uppercase tracking-widest px-1">Ordenar:</span>
                <button wire:click="sortBy('codigo')"
                    class="px-2 py-1 text-[9px] font-black uppercase rounded-lg transition-all {{ $sort === 'codigo' ? 'bg-white text-slate-800' : 'text-white/90 hover:bg-white/20' }}">
                    Código
                </button>
                <button wire:click="sortBy('cantidad_total')"
                    class="px-2 py-1 text-[9px] font-black uppercase rounded-lg transition-all {{ $sort === 'cantidad_total' ? 'bg-white text-slate-800' : 'text-white/90 hover:bg-white/20' }}">
                    Cantidad
                </button>
                <button wire:click="sortBy('estado')"
                    class="px-2 py-1 text-[9px] font-black uppercase rounded-lg transition-all {{ $sort === 'estado' ? 'bg-white text-slate-800' : 'text-white/90 hover:bg-white/20' }}">
                    Estado
                </button>
            </div>

            <button wire:click="limpiarFiltros"
                class="p-2 bg-white/20 hover:bg-white/40 text-white rounded-lg transition-all active:scale-95"
                title="Limpiar filtros">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                </svg>
            </button>
        </div>
    </div>

    {{-- =========================
         LISTA DE CARDS (SIN MAQUILA)
       ========================= --}}
    <div class="space-y-3">
        @forelse ($pedidosGlobales as $pedido)
            @php
                $estadoLower = strtolower($pedido->estado ?? 'pendiente');
                $borderColor = match ($estadoLower) {
                    'pendiente' => 'from-amber-400 to-orange-500',
                    'en curso' => 'from-indigo-500 to-blue-500',
                    'completado' => 'from-emerald-500 to-teal-600',
                    'cancelado' => 'from-slate-400 to-slate-500',
                    default => 'from-slate-400 to-slate-500',
                };
                $codigoColor = match ($estadoLower) {
                    'pendiente' => 'text-amber-700',
                    'en curso' => 'text-indigo-900',
                    'completado' => 'text-emerald-700',
                    'cancelado' => 'text-slate-500',
                    default => 'text-slate-900',
                };
                $badgeClasses = match ($estadoLower) {
                    'pendiente' => 'from-amber-400 to-orange-500 shadow-amber-200',
                    'en curso' => 'from-indigo-500 to-blue-500 shadow-indigo-200',
                    'completado' => 'from-emerald-500 to-teal-600 shadow-emerald-200',
                    'cancelado' => 'from-slate-400 to-slate-500 shadow-slate-200',
                    default => 'from-slate-400 to-slate-500 shadow-slate-200',
                };
                $progreso = $pedido->progreso ?? 0;
                $dashOffset = 175.9 - (175.9 * $progreso) / 100;
            @endphp

            <div x-data="{
                editando: false,
                pedido: @js($pedido),
                original: JSON.parse(JSON.stringify(@js($pedido)))
            }"
                @dblclick="if(!$event.target.closest('input,select,button,form')) {
                    editando = !editando;
                    if (!editando) pedido = JSON.parse(JSON.stringify(original));
                }"
                @keydown.enter.stop="guardarCambiosPedidoGlobal(pedido); editando = false"
                wire:key="pedido-{{ $pedido->id }}"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-lg transition-all duration-300 group"
                :class="{ 'ring-2 ring-amber-400 border-amber-300': editando }">
                <div class="flex">
                    {{-- Barra de estado lateral --}}
                    <div class="w-1.5 bg-gradient-to-b {{ $borderColor }}"></div>

                    {{-- Contenido principal --}}
                    <div class="flex-1 p-4">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            {{-- Info principal --}}
                            <div class="flex items-center gap-5 flex-wrap">
                                <div class="min-w-[90px]">
                                    <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Código
                                    </div>
                                    <div class="text-base font-black {{ $codigoColor }}" x-text="pedido.codigo"></div>
                                </div>

                                <div class="h-8 w-px bg-slate-200 hidden lg:block"></div>

                                {{-- Fabricante/Distribuidor unificado --}}
                                <div class="min-w-[140px]">
                                    <div class="text-[8px] font-black uppercase tracking-widest">
                                        <span
                                            class="{{ $pedido->fabricante_id ? 'text-indigo-600' : 'text-slate-300' }}">Fabricante</span>
                                        <span class="text-slate-300">/</span>
                                        <span
                                            class="{{ $pedido->distribuidor_id ? 'text-indigo-600' : 'text-slate-300' }}">Distribuidor</span>
                                    </div>
                                    <template x-if="!editando">
                                        <div class="text-sm font-bold text-slate-800"
                                            x-text="pedido.fabricante?.nombre ?? pedido.distribuidor?.nombre ?? '—'">
                                        </div>
                                    </template>
                                    <template x-if="editando">
                                        <div class="flex flex-col gap-1">
                                            <select x-model="pedido.fabricante_id"
                                                class="w-full text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 bg-white">
                                                <option value="">Fabricante...</option>
                                                @foreach ($fabricantes as $fab)
                                                    <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                                                @endforeach
                                            </select>
                                            <select x-model="pedido.distribuidor_id"
                                                class="w-full text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 bg-white">
                                                <option value="">Distribuidor...</option>
                                                @foreach ($distribuidores as $dist)
                                                    <option value="{{ $dist->id }}">{{ $dist->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </template>
                                </div>

                                {{-- Precio Referencia --}}
                                <div class="min-w-[80px]">
                                    <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Precio
                                        Ref.</div>
                                    <template x-if="!editando">
                                        <div class="text-sm font-black text-emerald-600"
                                            x-text="pedido.precio_referencia_euro ?? '—'"></div>
                                    </template>
                                    <input x-show="editando" x-model="pedido.precio_referencia" type="number"
                                        step="0.01" min="0"
                                        class="w-20 text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 text-right">
                                </div>
                            </div>

                            {{-- Cantidades y progreso --}}
                            <div class="flex items-center gap-6 flex-wrap">
                                {{-- Cantidad Total --}}
                                <div class="text-center min-w-[100px]">
                                    <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                                        Contrato</div>
                                    <template x-if="!editando">
                                        <div class="text-lg font-black text-slate-900">
                                            <span
                                                x-text="Number(pedido.cantidad_total).toLocaleString('es-ES')"></span>
                                            <span class="text-[10px] font-bold text-slate-400">kg</span>
                                        </div>
                                    </template>
                                    <input x-show="editando" x-model="pedido.cantidad_total" type="number"
                                        step="0.01"
                                        class="w-24 text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 text-right">
                                </div>

                                {{-- Cantidad Restante --}}
                                <div class="text-center min-w-[100px]">
                                    <div class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                                        Restante</div>
                                    <div
                                        class="text-lg font-black {{ $progreso >= 100 ? 'text-emerald-500' : 'text-amber-500' }}">
                                        {{ number_format($pedido->cantidad_restante, 0, ',', '.') }}
                                        <span class="text-[10px] font-bold text-slate-400">kg</span>
                                    </div>
                                </div>

                                {{-- Progreso circular --}}
                                <div class="relative w-14 h-14">
                                    <svg class="w-14 h-14 -rotate-90" viewBox="0 0 64 64">
                                        <circle cx="32" cy="32" r="28" class="stroke-slate-200"
                                            stroke-width="5" fill="none" />
                                        <circle cx="32" cy="32" r="28"
                                            class="{{ $estadoLower === 'completado' ? 'stroke-emerald-500' : ($estadoLower === 'en curso' ? 'stroke-indigo-500' : 'stroke-amber-400') }}"
                                            stroke-width="5" fill="none" stroke-linecap="round"
                                            stroke-dasharray="175.9" stroke-dashoffset="{{ $dashOffset }}" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-black text-slate-900">{{ $progreso }}%</span>
                                    </div>
                                </div>

                                {{-- Estado --}}
                                <div>
                                    <template x-if="!editando">
                                        <span
                                            class="px-3 py-1.5 bg-gradient-to-r {{ $badgeClasses }} text-white text-[9px] font-black uppercase tracking-wider rounded-full shadow-lg">
                                            {{ $pedido->estado }}
                                        </span>
                                    </template>
                                    <select x-show="editando" x-model="pedido.estado"
                                        class="text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 bg-white">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="en curso">En curso</option>
                                        <option value="completado">Completado</option>
                                        <option value="cancelado">Cancelado</option>
                                    </select>
                                </div>

                                {{-- Acciones --}}
                                <div class="flex items-center gap-2">
                                    {{-- Botones modo edición --}}
                                    <template x-if="editando">
                                        <div class="flex items-center gap-1">
                                            <button @click="guardarCambiosPedidoGlobal(pedido); editando = false"
                                                class="p-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-all"
                                                title="Guardar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="pedido = JSON.parse(JSON.stringify(original)); editando = false"
                                                class="p-2 bg-slate-200 hover:bg-slate-300 text-slate-600 rounded-lg transition-all"
                                                title="Cancelar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>

                                    {{-- Botones modo normal --}}
                                    <template x-if="!editando">
                                        <div
                                            class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editando = true"
                                                class="p-2 bg-slate-100 hover:bg-indigo-100 text-slate-600 hover:text-indigo-600 rounded-lg transition-all"
                                                title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST"
                                                action="{{ route('pedidos_globales.destroy', $pedido->id) }}"
                                                onsubmit="return confirm('¿Eliminar pedido global {{ $pedido->codigo }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-600 rounded-lg transition-all"
                                                    title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Fecha de creación --}}
                        <div class="mt-2 flex items-center gap-2 text-[10px] font-medium text-slate-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Creado: {{ $pedido->fecha_creacion_formateada }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                <div class="text-4xl mb-3">📦</div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">No hay pedidos globales</h3>
                <p class="text-sm text-slate-500">No se encontraron registros con los filtros aplicados</p>
            </div>
        @endforelse
    </div>

    {{-- Totales --}}
    @if ($pedidosGlobales->count() > 0)
        <div class="mt-6 bg-gradient-to-r from-slate-800 to-slate-700 rounded-2xl p-5 shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-6">
                    <div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total
                            Contratado</div>
                        <div class="text-2xl font-black text-white">
                            {{ number_format($totalesPrincipal['cantidad_total'] ?? 0, 0, ',', '.') }}
                            <span class="text-sm font-bold text-slate-400">kg</span>
                        </div>
                    </div>
                    <div class="h-10 w-px bg-slate-600"></div>
                    <div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Restante
                        </div>
                        <div class="text-2xl font-black text-amber-400">
                            {{ number_format($totalesPrincipal['cantidad_restante'] ?? 0, 0, ',', '.') }}
                            <span class="text-sm font-bold text-slate-400">kg</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Paginación --}}
    <div class="mt-6">
        {{ $pedidosGlobales->links() }}
    </div>

    {{-- =========================
         SECCIÓN MAQUILA
       ========================= --}}
    @if ($pedidosMaquila->count() > 0)
        <div class="mt-10">
            <div class="flex items-center gap-4 mb-4">
                <h2 class="text-xl font-black text-slate-800">Pedido Global</h2>
                <span
                    class="px-2.5 py-1 bg-purple-100 text-purple-700 text-[10px] font-black uppercase tracking-wider rounded-full">
                    {{ $pedidosMaquila->count() }} registros
                </span>
            </div>

            <div class="space-y-3">
                @foreach ($pedidosMaquila as $pedido)
                    @php
                        $estadoLower = strtolower($pedido->estado ?? 'pendiente');
                        $progresoMaq = $pedido->progreso ?? 0;
                        $dashOffsetMaq = 175.9 - (175.9 * $progresoMaq) / 100;
                    @endphp

                    <div x-data="{
                        editando: false,
                        pedido: @js($pedido),
                        original: JSON.parse(JSON.stringify(@js($pedido)))
                    }"
                        @dblclick="if(!$event.target.closest('input,select,button,form')) {
                            editando = !editando;
                            if (!editando) pedido = JSON.parse(JSON.stringify(original));
                        }"
                        @keydown.enter.stop="guardarCambiosPedidoGlobal(pedido); editando = false"
                        wire:key="maquila-{{ $pedido->id }}"
                        class="bg-white rounded-2xl border border-purple-200 shadow-sm overflow-hidden hover:shadow-lg transition-all duration-300 group"
                        :class="{ 'ring-2 ring-purple-400 border-purple-300': editando }">
                        <div class="flex">
                            <div class="w-1.5 bg-gradient-to-b from-purple-500 to-pink-500"></div>

                            <div class="flex-1 p-4">
                                <div class="flex items-center justify-between gap-4 flex-wrap">
                                    <div class="flex items-center gap-5 flex-wrap">
                                        <div class="min-w-[90px]">
                                            <div
                                                class="text-[8px] font-black text-slate-400 uppercase tracking-widest">
                                                Código</div>
                                            <div class="text-base font-black text-purple-700" x-text="pedido.codigo">
                                            </div>
                                        </div>

                                        <div class="h-8 w-px bg-slate-200 hidden lg:block"></div>

                                        <div class="min-w-[140px]">
                                            <div class="text-[8px] font-black uppercase tracking-widest">
                                                <span
                                                    class="{{ $pedido->fabricante_id ? 'text-purple-600' : 'text-slate-300' }}">Fabricante</span>
                                                <span class="text-slate-300">/</span>
                                                <span
                                                    class="{{ $pedido->distribuidor_id ? 'text-purple-600' : 'text-slate-300' }}">Distribuidor</span>
                                            </div>
                                            <template x-if="!editando">
                                                <div class="text-sm font-bold text-slate-800"
                                                    x-text="pedido.fabricante?.nombre ?? pedido.distribuidor?.nombre ?? '—'">
                                                </div>
                                            </template>
                                            <template x-if="editando">
                                                <div class="flex flex-col gap-1">
                                                    <select x-model="pedido.fabricante_id"
                                                        class="w-full text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 bg-white">
                                                        <option value="">Fabricante...</option>
                                                        @foreach ($fabricantes as $fab)
                                                            <option value="{{ $fab->id }}">{{ $fab->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <select x-model="pedido.distribuidor_id"
                                                        class="w-full text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 bg-white">
                                                        <option value="">Distribuidor...</option>
                                                        @foreach ($distribuidores as $dist)
                                                            <option value="{{ $dist->id }}">{{ $dist->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="min-w-[80px]">
                                            <div
                                                class="text-[8px] font-black text-slate-400 uppercase tracking-widest">
                                                Precio Ref.</div>
                                            <template x-if="!editando">
                                                <div class="text-sm font-black text-purple-600"
                                                    x-text="pedido.precio_referencia_euro ?? '—'"></div>
                                            </template>
                                            <input x-show="editando" x-model="pedido.precio_referencia"
                                                type="number" step="0.01" min="0"
                                                class="w-20 text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 text-right">
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-6 flex-wrap">
                                        <div class="text-center min-w-[100px]">
                                            <div
                                                class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                                                Contrato</div>
                                            <template x-if="!editando">
                                                <div class="text-lg font-black text-slate-900">
                                                    <span
                                                        x-text="Number(pedido.cantidad_total).toLocaleString('es-ES')"></span>
                                                    <span class="text-[10px] font-bold text-slate-400">kg</span>
                                                </div>
                                            </template>
                                            <input x-show="editando" x-model="pedido.cantidad_total" type="number"
                                                step="0.01"
                                                class="w-24 text-xs font-bold border border-slate-200 rounded-lg px-2 py-1 text-right">
                                        </div>

                                        <div class="text-center min-w-[100px]">
                                            <div
                                                class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">
                                                Restante</div>
                                            <div
                                                class="text-lg font-black {{ $progresoMaq >= 100 ? 'text-emerald-500' : 'text-purple-500' }}">
                                                {{ number_format($pedido->cantidad_restante, 0, ',', '.') }}
                                                <span class="text-[10px] font-bold text-slate-400">kg</span>
                                            </div>
                                        </div>

                                        <div class="relative w-14 h-14">
                                            <svg class="w-14 h-14 -rotate-90" viewBox="0 0 64 64">
                                                <circle cx="32" cy="32" r="28" class="stroke-slate-200"
                                                    stroke-width="5" fill="none" />
                                                <circle cx="32" cy="32" r="28"
                                                    class="stroke-purple-500" stroke-width="5" fill="none"
                                                    stroke-linecap="round" stroke-dasharray="175.9"
                                                    stroke-dashoffset="{{ $dashOffsetMaq }}" />
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <span
                                                    class="text-xs font-black text-slate-900">{{ $progresoMaq }}%</span>
                                            </div>
                                        </div>

                                        <span
                                            class="px-3 py-1.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[9px] font-black uppercase tracking-wider rounded-full shadow-lg shadow-purple-200">
                                            {{ $pedido->estado }}
                                        </span>

                                        <div class="flex items-center gap-2">
                                            <template x-if="editando">
                                                <div class="flex items-center gap-1">
                                                    <button
                                                        @click="guardarCambiosPedidoGlobal(pedido); editando = false"
                                                        class="p-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-all"
                                                        title="Guardar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="pedido = JSON.parse(JSON.stringify(original)); editando = false"
                                                        class="p-2 bg-slate-200 hover:bg-slate-300 text-slate-600 rounded-lg transition-all"
                                                        title="Cancelar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>

                                            <template x-if="!editando">
                                                <div
                                                    class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button @click="editando = true"
                                                        class="p-2 bg-slate-100 hover:bg-purple-100 text-slate-600 hover:text-purple-600 rounded-lg transition-all"
                                                        title="Editar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                    </button>
                                                    <form method="POST"
                                                        action="{{ route('pedidos_globales.destroy', $pedido->id) }}"
                                                        onsubmit="return confirm('¿Eliminar pedido maquila {{ $pedido->codigo }}?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="p-2 bg-slate-100 hover:bg-rose-100 text-slate-600 hover:text-rose-600 rounded-lg transition-all"
                                                            title="Eliminar">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center gap-2 text-[10px] font-medium text-slate-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Creado: {{ $pedido->fecha_creacion_formateada }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Totales Maquila --}}
            <div class="mt-6 bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl p-5 shadow-xl">
                <div class="flex items-center gap-6">
                    <div>
                        <div class="text-[9px] font-black text-purple-200 uppercase tracking-widest mb-0.5">Total
                            Contratado</div>
                        <div class="text-2xl font-black text-white">
                            {{ number_format($totalesMaquila['cantidad_total'] ?? 0, 0, ',', '.') }}
                            <span class="text-sm font-bold text-purple-200">kg</span>
                        </div>
                    </div>
                    <div class="h-10 w-px bg-purple-400/50"></div>
                    <div>
                        <div class="text-[9px] font-black text-purple-200 uppercase tracking-widest mb-0.5">Restante
                        </div>
                        <div class="text-2xl font-black text-pink-200">
                            {{ number_format($totalesMaquila['cantidad_restante'] ?? 0, 0, ',', '.') }}
                            <span class="text-sm font-bold text-purple-200">kg</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Script para guardar cambios --}}
    <script>
        function initPedidosGlobalesPage() {
            window.guardarCambiosPedidoGlobal = function(pedido) {
                fetch(`{{ route('pedidos_globales.update', '') }}/${pedido.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(pedido)
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            let mensaje = (data && data.message) ? data.message : 'Error desconocido';
                            if (data && data.errors) {
                                mensaje = Object.values(data.errors).flat().join('\n');
                            }
                            throw new Error(mensaje);
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Pedido global actualizado correctamente.',
                            confirmButtonColor: '#16a34a',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'No se pudo actualizar el pedido global.'
                        });
                    });
            };
        }

        // Inicialización para carga normal y navegación SPA
        document.addEventListener('DOMContentLoaded', initPedidosGlobalesPage);
        document.addEventListener('livewire:navigated', initPedidosGlobalesPage);
    </script>
</div>
