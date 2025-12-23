<div>
    {{-- Filtros y Herramientas --}}
    <div
        class="bg-slate-800 p-4 rounded-3xl border border-slate-200 mb-8 flex flex-wrap items-center gap-4 shadow-inner">
        <div class="flex-1 min-w-[250px]">
            <div class="relative group">
                <span
                    class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 group-focus-within:text-slate-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="codigo" placeholder="ID de Pedido o Referencia..."
                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10 rounded-2xl text-sm font-black text-slate-700 transition-all placeholder:text-slate-400">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-200">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Fabricante</span>
                <select wire:model.live="fabricante_id"
                    class="text-xs font-black text-slate-700 bg-transparent border-0 focus:ring-0 py-1 pl-1 pr-8 cursor-pointer">
                    <option value="">Todos</option>
                    @foreach ($fabricantes as $fab)
                        <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-200">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Destino</span>
                <select wire:model.live="obra_id"
                    class="text-xs font-black text-slate-700 bg-transparent border-0 focus:ring-0 py-1 pl-1 pr-8 cursor-pointer">
                    <option value="">Cualquier Lugar</option>
                    @foreach ($obras as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-200">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest pl-1">Estado</span>
                <select wire:model.live="estado"
                    class="text-xs font-black text-slate-700 bg-transparent border-0 focus:ring-0 py-1 pl-1 pr-8 cursor-pointer">
                    <option value="">Cualquier Estado</option>
                    <option value="activo">Activo</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="parcial">Parcial</option>
                    <option value="completado">Completado</option>
                    <option value="facturado">Facturado</option>
                </select>
            </div>

            <button wire:click="limpiarFiltros"
                class="p-3 bg-white hover:bg-slate-100 text-slate-400 hover:text-slate-900 border border-slate-200 rounded-2xl transition-all shadow-sm active:scale-95"
                title="Limpiar filtros">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Tabla de resultados --}}
    {{-- Tabla de resultados --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-slate-800 border-b border-slate-200">
                    <th class="px-6 py-5 text-left font-black text-slate-50 uppercase tracking-widest text-[10px] w-48">
                        Línea</th>
                    <th class="px-6 py-5 text-left font-black text-slate-50 uppercase tracking-widest text-[10px]">
                        Producto</th>
                    <th class="px-6 py-5 text-left font-black text-slate-50 uppercase tracking-widest text-[10px]">
                        Entrega</th>
                    <th
                        class="px-6 py-5 text-right font-black text-slate-50 uppercase tracking-widest text-[10px] w-32">
                        Pedido</th>
                    <th
                        class="px-6 py-5 text-right font-black text-slate-50 uppercase tracking-widest text-[10px] w-32">
                        Recep.</th>
                    <th
                        class="px-6 py-5 text-center font-black text-slate-50 uppercase tracking-widest text-[10px] w-40">
                        F. Entrega</th>
                    <th
                        class="px-6 py-5 text-right font-black text-slate-50 uppercase tracking-widest text-[10px] w-32">
                        Estado</th>
                    <th
                        class="px-6 py-5 text-right font-black text-slate-50 uppercase tracking-widest text-[10px] w-44">
                        Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($lineasAgrupadas as $pedidoId => $lineasDelPedido)
                    @php
                        $firstLinea = $lineasDelPedido->first();
                        $pedido = $firstLinea ? $firstLinea->pedido : null;
                        $estadoPedido = strtolower(trim($pedido->estado ?? ''));
                        $pedidoCancelado = $estadoPedido === 'cancelado';
                        $pedidoCompletado = $estadoPedido === 'completado';
                        $pedidoFacturado = $estadoPedido === 'facturado';
                    @endphp

                    {{-- CABECERA DEL PEDIDO (GRUPO) --}}
                    <tr class="bg-slate-100 border-t-2 relative group/row">
                        <td colspan="6" class="px-6 py-6 border-l-4 border-slate-900 transition-all duration-300">
                            <div class="flex items-center gap-10">
                                <div class="flex flex-col">
                                    <span
                                        class="text-lg font-black text-slate-900 tracking-tight">{{ $pedido?->codigo ?? '—' }}</span>
                                </div>

                                <div class="flex flex-col">
                                    <span
                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Entorno
                                        de Suministro</span>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-sm font-black text-slate-800">{{ $pedido?->fabricante?->nombre ?? 'Directo' }}</span>
                                        @if ($pedido?->distribuidor)
                                            <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-tighter">{{ $pedido->distribuidor->nombre }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <span
                                        class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Fecha
                                        Emisión</span>
                                    <span
                                        class="text-xs font-black text-slate-600 tracking-tight uppercase">{{ $pedido?->fecha_pedido_formateada ?? '—' }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- ESTADO DEL PEDIDO (GRUPO) --}}
                        <td class="px-6 py-6 text-right align-middle">
                            <div class="flex flex-col items-end">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider
                                    {{ $pedidoCancelado ? 'bg-slate-200 text-slate-600' : '' }}
                                    {{ $pedidoCompletado ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : '' }}
                                    {{ $pedidoFacturado ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : '' }}
                                    {{ !$pedidoCancelado && !$pedidoCompletado && !$pedidoFacturado ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : '' }}">
                                    {{ $pedido?->estado ?? 'pendiente' }}
                                </span>
                            </div>
                        </td>

                        {{-- ACCIONES DEL PEDIDO (GRUPO) --}}
                        <td class="px-6 py-6 text-right align-middle">
                            <div class="flex items-center justify-end gap-3">
                                @if ($pedido && !$pedidoCancelado && !$pedidoCompletado)
                                    <form method="POST" action="{{ route('pedidos.cancelar', $pedido->id) }}"
                                        onsubmit="return confirm('¿Cancelar pedido {{ $pedido->codigo }}?')">
                                        @csrf @method('PUT')
                                        <button type="submit"
                                            class="px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest bg-white border border-slate-200 rounded-xl hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all duration-300 shadow-sm">
                                            Anular
                                        </button>
                                    </form>
                                @endif
                                @if ($pedido)
                                    <form method="POST" action="{{ route('pedidos.destroy', $pedido->id) }}"
                                        onsubmit="return confirm('¿ELIMINAR pedido {{ $pedido->codigo }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="p-2.5 text-rose-400 hover:text-white hover:bg-rose-500 border border-transparent hover:border-rose-600 rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-rose-100"
                                            title="Eliminar Pedido completo">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- LÍNEAS DEL PEDIDO --}}
                    @foreach ($lineasDelPedido as $linea)
                        @php
                            $estadoLinea = strtolower(trim($linea->estado));
                            $claseLinea = match ($estadoLinea) {
                                'facturado' => 'bg-green-500/50',
                                'completado' => 'bg-indigo-400/50',
                                'activo' => 'bg-slate-50',
                                'cancelado' => 'opacity-50 grayscale bg-slate-50',
                                default => '',
                            };
                        @endphp

                        <tr wire:key="linea-{{ $linea->id }}" class="group {{ $claseLinea }}">
                            <td class="px-6 py-5 align-middle border-l-4 border-slate-200 group-hover:border-blue-400">
                                @if ($linea->codigo)
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-1">
                                            <span
                                                class="text-[10px] font-black text-slate-700 tracking-tighter">{{ explode('–', $linea->codigo)[0] ?? '' }}</span>
                                            <span
                                                class="px-1.5 py-0.5 bg-slate-100 text-slate-900 text-[10px] font-black rounded-md">–{{ explode('–', $linea->codigo)[1] ?? '' }}</span>
                                        </div>
                                        @if ($pedido)
                                            <a href="{{ route('entradas.index', ['pedido_codigo' => $pedido->codigo, 'pedido_producto_id' => $linea->id]) }}"
                                                class="inline-flex items-center gap-1.5 text-[9px] font-black text-slate-700 hover:text-slate-900 hover:underline hover:underline-offset-4 transition-colors uppercase tracking-widest group/link">
                                                Historial Entradas
                                                <svg class="w-3 h-3 transform group-hover/link:translate-x-0.5 transition-transform"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="producto-view-{{ $linea->id }} flex items-center gap-3">
                                    <span
                                        class="px-2 py-1 bg-slate-900 text-white text-[9px] font-black rounded-lg uppercase tracking-widest shadow-sm">
                                        {{ $linea->tipo }}
                                    </span>
                                    <div class="flex items-baseline gap-1">
                                        <span
                                            class="text-base font-black text-slate-900 tracking-tight">Ø{{ $linea->diametro }}</span>
                                        @if ($linea->tipo === 'barra' && $linea->longitud && $linea->longitud !== '—')
                                            <span class="text-xs font-bold text-slate-400">/
                                                {{ $linea->longitud }}m</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Edición Producto --}}
                                <div class="producto-edit-{{ $linea->id }} hidden">
                                    <select
                                        class="producto-base-select text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-lg p-2 w-full shadow-sm focus:ring-2 focus:ring-slate-900"
                                        data-linea-id="{{ $linea->id }}">
                                        @foreach ($productosBase->groupBy('tipo') as $tipo => $prods)
                                            <optgroup label="{{ strtoupper($tipo) }}"
                                                class="font-black text-slate-400">
                                                @foreach ($prods as $p)
                                                    <option value="{{ $p->id }}"
                                                        {{ $linea->producto_base_id == $p->id ? 'selected' : '' }}>
                                                        Ø{{ $p->diametro }}{{ $p->longitud ? ' x ' . $p->longitud . 'm' : '' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="lugar-entrega-view-{{ $linea->id }} flex items-center gap-2">
                                    <div
                                        class="w-1.5 h-1.5 rounded-full {{ $linea->obra_id ? 'bg-slate-900' : 'bg-amber-400' }}">
                                    </div>
                                    <span
                                        class="text-sm font-black text-slate-600 truncate max-w-[150px] tracking-tight"
                                        title="{{ $linea->obra?->obra ?? $linea->obra_manual }}">
                                        {{ $linea->obra?->obra ?? ($linea->obra_manual ?? 'Sin Destino') }}
                                    </span>
                                </div>

                                {{-- Edición Lugar --}}
                                <div class="lugar-entrega-edit-{{ $linea->id }} hidden">
                                    <div class="flex flex-col gap-2">
                                        <select
                                            class="obra-hpr-select text-[11px] font-black border-slate-200 rounded-lg p-2 shadow-sm"
                                            data-linea-id="{{ $linea->id }}">
                                            <option value="">Destino Interno...</option>
                                            @foreach ($navesHpr as $nave)
                                                <option value="{{ $nave->id }}"
                                                    {{ $linea->obra_id == $nave->id ? 'selected' : '' }}>
                                                    {{ $nave->obra }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text"
                                            class="obra-manual-input text-[11px] font-black border-slate-200 rounded-lg p-2 shadow-sm"
                                            placeholder="Dirección Manual..." value="{{ $linea->obra_manual ?? '' }}"
                                            data-linea-id="{{ $linea->id }}">
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-right">
                                <span
                                    class="text-base font-black text-slate-900 tracking-tighter">{{ number_format($linea->cantidad ?? 0, 0, ',', '.') }}</span>
                                <span class="text-[10px] font-black text-slate-700 uppercase ml-0.5">kg</span>
                            </td>

                            <td class="px-6 py-5 text-right">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-base font-black tracking-tighter text-slate-800">
                                            {{ number_format($linea->cantidad_recepcionada ?? 0, 0, ',', '.') }}
                                        </span>
                                        <span class="text-[10px] font-black text-slate-700 uppercase">kg</span>
                                    </div>
                                    @php
                                        $porcentaje =
                                            $linea->cantidad > 0
                                                ? ($linea->cantidad_recepcionada / $linea->cantidad) * 100
                                                : 0;
                                    @endphp
                                    <div
                                        class="w-20 h-2 bg-slate-200 border {{ $porcentaje >= 100 ? 'border-emerald-700' : 'border-slate-700' }} rounded-full mt-1.5 overflow-hidden">
                                        <div class="h-full {{ $porcentaje >= 100 ? 'bg-emerald-500' : 'bg-indigo-400' }} rounded-full"
                                            style="width: {{ min(100, $porcentaje) }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <div class="inline-flex flex-col items-center">
                                    <span
                                        class="px-2.5 py-1 bg-slate-100 text-slate-700 text-[11px] font-black rounded-lg tracking-tight shadow-sm border border-slate-200/50">
                                        {{ $linea->fecha_estimada_entrega_formateada ?? 'S/F' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-right">
                                <div class="flex flex-col items-end">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider
                                         {{ $linea->estado === 'cancelado' ? 'bg-slate-200 text-slate-600' : '' }}
                                         {{ $linea->estado === 'completado' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : '' }}
                                         {{ $linea->estado === 'facturado' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : '' }}
                                         {{ in_array($linea->estado, ['activo', 'pendiente', 'parcial']) ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : '' }}">
                                        {{ $linea->estado }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @php
                                        $estado = strtolower(trim($linea->estado));
                                        $esFinalizado = in_array($estado, ['completado', 'facturado', 'cancelado']);

                                        $obraLinea = $linea->obra;
                                        $esEntregaDirecta =
                                            !empty($linea->obra_manual) ||
                                            ($obraLinea ? !$obraLinea->es_nave_paco_reyes : false);
                                        $esNavePaco = $obraLinea && $obraLinea->es_nave_paco_reyes;
                                    @endphp

                                    {{-- BOTONES DE ACCIÓN --}}
                                    @if ($pedido)
                                        <div
                                            class="botones-estado-{{ $linea->id }} flex items-center justify-end gap-2 {{ $esFinalizado ? 'hidden' : '' }}">
                                            @if ($esEntregaDirecta)
                                                <form method="POST"
                                                    action="{{ route('pedidos.editarCompletarLineaManual', [$pedido->id, $linea->id]) }}"
                                                    onsubmit="return confirmarCompletarLinea(this)">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-10 h-10 flex items-center justify-center bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl transition-all duration-300 shadow-sm border border-emerald-100"
                                                        title="Finalizar/Recepcionar">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($estado === 'activo')
                                                <form method="POST"
                                                    action="{{ route('pedidos.lineas.editarDesactivar', [$pedido->id, $linea->id]) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="w-10 h-10 flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl transition-all duration-300 shadow-sm border border-rose-100"
                                                        title="Deshabilitar Suministro">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @elseif ($esNavePaco)
                                                <form method="POST"
                                                    action="{{ route('pedidos.lineas.editarActivar', [$pedido->id, $linea->id]) }}">
                                                    @csrf @method('PUT')
                                                    <button type="submit"
                                                        class="w-10 h-10 flex items-center justify-center bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white rounded-xl transition-all duration-300 shadow-sm border border-amber-100"
                                                        title="Activar para Producción">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5"
                                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            <button type="button" onclick="abrirEdicionLinea({{ $linea->id }})"
                                                class="btn-editar-linea-{{ $linea->id }} w-10 h-10 flex items-center justify-center bg-slate-100 text-slate-800 hover:bg-slate-900 hover:text-white rounded-xl transition-all duration-300 shadow-sm border border-slate-200"
                                                title="Configurar Línea">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- BOTONES DE EDICIÓN --}}
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                onclick="guardarLinea({{ $linea->id }}, {{ $pedido->id }})"
                                                class="btn-guardar-linea-{{ $linea->id }} hidden w-10 h-10 items-center justify-center bg-slate-900 text-white rounded-xl shadow-lg shadow-slate-200 hover:bg-black transition-all">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                onclick="cancelarEdicionLinea({{ $linea->id }})"
                                                class="btn-cancelar-edicion-{{ $linea->id }} hidden w-10 h-10 items-center justify-center bg-slate-200 text-slate-600 rounded-xl hover:bg-slate-300 transition-all">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-4xl">🔎</span>
                                <h3 class="text-sm font-bold text-slate-800">No se encontraron líneas</h3>
                                <p class="text-xs text-slate-400">Intenta ajustar los filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="mt-8">
        {{ $lineas->links() }}
    </div>
</div>

{{-- SCRIPTS JAVASCRIPT --}}
@push('scripts')
    <script>
        function abrirEdicionLinea(lineaId) {
            // Ocultar vista normal y mostrar edición
            document.querySelector(`.lugar-entrega-view-${lineaId}`).classList.add('hidden');
            document.querySelector(`.lugar-entrega-edit-${lineaId}`).classList.remove('hidden');
            document.querySelector(`.producto-view-${lineaId}`).classList.add('hidden');
            document.querySelector(`.producto-edit-${lineaId}`).classList.remove('hidden');

            // Ocultar botones de estado y mostrar botones de edición
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);
            if (botonesEstado) botonesEstado.classList.add('hidden');

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            if (btnEditar) btnEditar.classList.add('hidden');

            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            if (btnGuardar) {
                btnGuardar.classList.remove('hidden');
                btnGuardar.classList.add('flex');
            }

            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            if (btnCancelar) {
                btnCancelar.classList.remove('hidden');
                btnCancelar.classList.add('flex');
            }

            // Configurar listeners para limpiar otras opciones cuando se selecciona una
            const obraHprSelect = document.querySelector(`.obra-hpr-select[data-linea-id="${lineaId}"]`);
            const obraExternaSelect = document.querySelector(`.obra-externa-select[data-linea-id="${lineaId}"]`);
            const obraManualInput = document.querySelector(`.obra-manual-input[data-linea-id="${lineaId}"]`);

            if (obraHprSelect && obraExternaSelect && obraManualInput) {
                // Limpiar listeners previos clonando y reemplazando
                const newObraHpr = obraHprSelect.cloneNode(true);
                const newObraExterna = obraExternaSelect.cloneNode(true);
                const newObraManual = obraManualInput.cloneNode(true);

                obraHprSelect.parentNode.replaceChild(newObraHpr, obraHprSelect);
                obraExternaSelect.parentNode.replaceChild(newObraExterna, obraExternaSelect);
                obraManualInput.parentNode.replaceChild(newObraManual, obraManualInput);

                // Agregar nuevos listeners
                newObraHpr.addEventListener('change', function() {
                    if (this.value) {
                        newObraExterna.value = '';
                        newObraManual.value = '';
                    }
                });

                newObraExterna.addEventListener('change', function() {
                    if (this.value) {
                        newObraHpr.value = '';
                        newObraManual.value = '';
                    }
                });

                newObraManual.addEventListener('input', function() {
                    if (this.value.trim()) {
                        newObraHpr.value = '';
                        newObraExterna.value = '';
                    }
                });
            }
        }

        function cancelarEdicionLinea(lineaId) {
            // Mostrar vista normal y ocultar edición
            document.querySelector(`.lugar-entrega-view-${lineaId}`).classList.remove('hidden');
            document.querySelector(`.lugar-entrega-edit-${lineaId}`).classList.add('hidden');
            document.querySelector(`.producto-view-${lineaId}`).classList.remove('hidden');
            document.querySelector(`.producto-edit-${lineaId}`).classList.add('hidden');

            // Mostrar botones de estado y ocultar botones de edición
            const botonesEstado = document.querySelector(`.botones-estado-${lineaId}`);
            if (botonesEstado) botonesEstado.classList.remove('hidden');

            const btnEditar = document.querySelector(`.btn-editar-linea-${lineaId}`);
            if (btnEditar) btnEditar.classList.remove('hidden');

            const btnGuardar = document.querySelector(`.btn-guardar-linea-${lineaId}`);
            if (btnGuardar) {
                btnGuardar.classList.add('hidden');
                btnGuardar.classList.remove('flex');
            }

            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            if (btnCancelar) {
                btnCancelar.classList.add('hidden');
                btnCancelar.classList.remove('flex');
            }
        }

        async function guardarLinea(lineaId, pedidoId) {
            // Obtener valores de obra
            const obraHpr = document.querySelector(`.obra-hpr-select[data-linea-id="${lineaId}"]`).value;
            const obraExterna = document.querySelector(`.obra-externa-select[data-linea-id="${lineaId}"]`).value;
            const obraManual = document.querySelector(`.obra-manual-input[data-linea-id="${lineaId}"]`).value.trim();

            // Obtener valor de producto
            const productoBase = document.querySelector(`.producto-base-select[data-linea-id="${lineaId}"]`);
            const productoBaseId = productoBase ? productoBase.value : '';

            // Validar que se haya seleccionado un producto
            if (!productoBaseId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Debes seleccionar un producto'
                    });
                } else {
                    alert('Debes seleccionar un producto');
                }
                return;
            }

            // Determinar qué obra usar
            let obraId = null;
            let obraManualFinal = null;

            if (obraHpr && obraHpr !== 'sin_obra') {
                obraId = obraHpr;
            } else if (obraExterna && obraExterna !== 'sin_obra') {
                obraId = obraExterna;
            } else if (obraManual) {
                obraManualFinal = obraManual;
            }
            // Si ambos selects tienen "sin_obra" o están vacíos, obraId y obraManualFinal quedan null

            // Preparar datos para enviar como JSON
            const datos = {
                linea_id: lineaId,
                obra_id: obraId,
                obra_manual: obraManualFinal,
                producto_base_id: productoBaseId
            };

            try {
                const response = await fetch(`/pedidos/${pedidoId}/actualizar-linea`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(datos)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Actualizar las celdas de vista con los nuevos datos
                    const lugarEntregaView = document.querySelector(`.lugar-entrega-view-${lineaId}`);
                    const productoView = document.querySelector(`.producto-view-${lineaId}`);

                    if (lugarEntregaView && data.data?.lugar_entrega) {
                        lugarEntregaView.textContent = data.data.lugar_entrega;
                    }

                    if (productoView && data.data?.producto) {
                        productoView.textContent = data.data.producto;
                    }

                    // Cancelar el modo edición
                    cancelarEdicionLinea(lineaId);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: 'Línea actualizada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al guardar la línea'
                        });
                    } else {
                        alert(data.message || 'Error al guardar la línea');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión al guardar'
                    });
                } else {
                    alert('Error de conexión al guardar');
                }
            }
        }

        function confirmarCancelacionLinea(pedidoId, lineaId) {
            if (confirm('¿Estás seguro de que deseas cancelar esta línea?')) {
                document.querySelector(`.form-cancelar-linea[data-pedido-id="${pedidoId}"][data-linea-id="${lineaId}"]`)
                    .submit();
            }
        }

        function confirmarCompletarLinea(form) {
            return confirm('¿Estás seguro de que deseas completar esta línea?');
        }
    </script>
@endpush
