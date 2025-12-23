<div>
    {{-- Filtros y Herramientas --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">🔍</span>
                <input type="text" wire:model.live.debounce.300ms="codigo" placeholder="Buscar por código de pedido..."
                    class="w-full pl-10 pr-4 py-2 bg-slate-50 border-0 focus:ring-2 focus:ring-blue-500 rounded-xl text-sm font-medium">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="fabricante_id"
                class="text-xs font-bold text-slate-600 bg-slate-50 border-0 focus:ring-2 focus:ring-blue-500 rounded-xl py-2 pl-3 pr-8">
                <option value="">Fabricante: Todos</option>
                @foreach ($fabricantes as $fab)
                    <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                @endforeach
            </select>

            <select wire:model.live="obra_id"
                class="text-xs font-bold text-slate-600 bg-slate-50 border-0 focus:ring-2 focus:ring-blue-500 rounded-xl py-2 pl-3 pr-8">
                <option value="">Lugar: Todos</option>
                @foreach ($obras as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>

            <select wire:model.live="estado"
                class="text-xs font-bold text-slate-600 bg-slate-50 border-0 focus:ring-2 focus:ring-blue-500 rounded-xl py-2 pl-3 pr-8">
                <option value="">Estado: Todos</option>
                <option value="activo">Activo</option>
                <option value="pendiente">Pendiente</option>
                <option value="parcial">Parcial</option>
                <option value="completado">Completado</option>
            </select>

            <button wire:click="limpiarFiltros"
                class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-xl transition-colors"
                title="Limpiar filtros">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Tabla de resultados --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-6 py-4 text-left font-bold text-slate-500 uppercase tracking-wider text-[10px]">Línea
                    </th>
                    <th class="px-6 py-4 text-left font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                        Producto</th>
                    <th class="px-6 py-4 text-left font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                        Entrega</th>
                    <th class="px-6 py-4 text-right font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                        Pedido</th>
                    <th class="px-6 py-4 text-right font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                        Recep.</th>
                    <th class="px-6 py-4 text-center font-bold text-slate-500 uppercase tracking-wider text-[10px]">F.
                        Entrega</th>
                    <th class="px-6 py-4 text-center font-bold text-slate-500 uppercase tracking-wider text-[10px]">
                        Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($lineasAgrupadas as $pedidoId => $lineasDelPedido)
                    @php
                        $firstLinea = $lineasDelPedido->first();
                        $pedido = $firstLinea ? $firstLinea->pedido : null;
                        $estadoPedido = strtolower(trim($pedido->estado ?? ''));
                        $pedidoCancelado = $estadoPedido === 'cancelado';
                        $pedidoCompletado = $estadoPedido === 'completado';
                    @endphp

                    {{-- CABECERA DEL PEDIDO (GRUPO) --}}
                    <tr class="bg-slate-100/50 border-t border-slate-200">
                        <td colspan="7" class="px-6 py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-6">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Cód.
                                            Pedido</span>
                                        <span
                                            class="text-sm font-extrabold text-slate-800">{{ $pedido?->codigo ?? '—' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Fabricante</span>
                                        <span
                                            class="text-sm font-semibold text-slate-700">{{ $pedido?->fabricante?->nombre ?? '—' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Distribuidor</span>
                                        <span
                                            class="text-xs font-medium text-slate-500">{{ $pedido?->distribuidor?->nombre ?? '—' }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Fecha</span>
                                        <span
                                            class="text-xs font-medium text-slate-500">{{ $pedido?->fecha_pedido_formateada ?? '—' }}</span>
                                    </div>
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                        {{ $pedidoCancelado ? 'bg-slate-200 text-slate-600' : '' }}
                                        {{ $pedidoCompletado ? 'bg-green-100 text-green-700 border border-green-200' : '' }}
                                        {{ !$pedidoCancelado && !$pedidoCompletado ? 'bg-blue-100 text-blue-700 border border-blue-200' : '' }}">
                                        {{ $pedido?->estado ?? 'pendiente' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($pedido && !$pedidoCancelado && !$pedidoCompletado)
                                        <form method="POST" action="{{ route('pedidos.cancelar', $pedido->id) }}"
                                            onsubmit="return confirm('¿Cancelar pedido {{ $pedido->codigo }}?')">
                                            @csrf @method('PUT')
                                            <button type="submit"
                                                class="text-slate-400 hover:text-slate-600 text-xs font-bold px-3 py-1 bg-white border border-slate-200 rounded-lg shadow-sm transition-all">
                                                Cancelar Pedido
                                            </button>
                                        </form>
                                    @endif
                                    @if ($pedido)
                                        <form method="POST" action="{{ route('pedidos.destroy', $pedido->id) }}"
                                            onsubmit="return confirm('¿ELIMINAR pedido {{ $pedido->codigo }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-red-400 hover:text-red-600 text-xs font-bold px-3 py-1 bg-white border border-slate-200 rounded-lg shadow-sm transition-all">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- LÍNEAS DEL PEDIDO --}}
                    @foreach ($lineasDelPedido as $linea)
                        @php
                            $estadoLinea = strtolower(trim($linea->estado));
                            $claseLinea = match ($estadoLinea) {
                                'facturado' => 'bg-green-50/50',
                                'completado' => 'bg-green-50/30',
                                'activo' => 'bg-amber-50/30',
                                'cancelado' => 'opacity-50 grayscale',
                                default => '',
                            };
                        @endphp

                        <tr wire:key="linea-{{ $linea->id }}"
                            class="group hover:bg-slate-50 transition-colors {{ $claseLinea }}">
                            <td class="px-6 py-4 align-middle">
                                @if ($linea->codigo)
                                    <div class="flex items-baseline gap-1">
                                        <span
                                            class="text-[10px] font-bold text-slate-400">{{ explode('–', $linea->codigo)[0] ?? '' }}</span>
                                        <span
                                            class="text-sm font-extrabold text-blue-600 leading-none">–{{ explode('–', $linea->codigo)[1] ?? '' }}</span>
                                    </div>
                                @endif
                                @if ($pedido)
                                    <a href="{{ route('entradas.index', ['pedido_codigo' => $pedido->codigo, 'pedido_producto_id' => $linea->id]) }}"
                                        class="text-[10px] font-bold text-slate-400 hover:text-blue-600 transition-colors uppercase tracking-tighter">
                                        Ver entradas →
                                    </a>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="producto-view-{{ $linea->id }} flex items-center gap-2">
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold">
                                        {{ ucfirst($linea->tipo) }}
                                    </span>
                                    <span class="text-sm font-extrabold text-slate-700">Ø{{ $linea->diametro }}</span>
                                    @if ($linea->tipo === 'barra' && $linea->longitud && $linea->longitud !== '—')
                                        <span class="text-xs font-medium text-slate-400">x
                                            {{ $linea->longitud }}m</span>
                                    @endif
                                </div>

                                {{-- Edición Producto --}}
                                <div class="producto-edit-{{ $linea->id }} hidden">
                                    <select
                                        class="producto-base-select text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-lg p-1 w-full"
                                        data-linea-id="{{ $linea->id }}">
                                        @foreach ($productosBase->groupBy('tipo') as $tipo => $prods)
                                            <optgroup label="{{ strtoupper($tipo) }}">
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

                            <td class="px-6 py-4">
                                <div class="lugar-entrega-view-{{ $linea->id }} text-sm font-semibold text-slate-600 truncate max-w-[150px]"
                                    title="{{ $linea->obra?->obra ?? $linea->obra_manual }}">
                                    {{ $linea->obra?->obra ?? ($linea->obra_manual ?? '—') }}
                                </div>

                                {{-- Edición Lugar --}}
                                <div class="lugar-entrega-edit-{{ $linea->id }} hidden">
                                    <div class="flex flex-col gap-1">
                                        <select
                                            class="obra-hpr-select text-[10px] font-bold border-slate-200 rounded-lg p-1"
                                            data-linea-id="{{ $linea->id }}">
                                            <option value="">Nave HPR...</option>
                                            @foreach ($navesHpr as $nave)
                                                <option value="{{ $nave->id }}"
                                                    {{ $linea->obra_id == $nave->id ? 'selected' : '' }}>
                                                    {{ $nave->obra }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text"
                                            class="obra-manual-input text-[10px] font-bold border-slate-200 rounded-lg p-1"
                                            placeholder="Manual..." value="{{ $linea->obra_manual ?? '' }}"
                                            data-linea-id="{{ $linea->id }}">
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <span
                                    class="text-sm font-bold text-slate-800">{{ number_format($linea->cantidad ?? 0, 0, ',', '.') }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">kg</span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <span
                                    class="text-sm font-bold {{ ($linea->cantidad_recepcionada ?? 0) >= ($linea->cantidad ?? 0) ? 'text-green-600' : 'text-slate-700' }}">
                                    {{ number_format($linea->cantidad_recepcionada ?? 0, 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">kg</span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-semibold text-slate-500 bg-slate-50 px-2 py-1 rounded-lg">
                                    {{ $linea->fecha_estimada_entrega_formateada ?? '—' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @php
                                        $estado = strtolower(trim($linea->estado));
                                        $esFinalizado = in_array($estado, ['completado', 'facturado', 'cancelado']);

                                        $obraLinea = $linea->obra;
                                        $esEntregaDirecta =
                                            !empty($linea->obra_manual) ||
                                            ($obraLinea ? !$obraLinea->es_nave_paco_reyes : false);
                                        $esNavePaco = $obraLinea && $obraLinea->es_nave_paco_reyes;
                                    @endphp

                                    @if (!$esFinalizado && $pedido)
                                        {{-- BOTONES DE ACCIÓN --}}
                                        <div class="botones-estado-{{ $linea->id }} flex items-center gap-1">
                                            @if ($esEntregaDirecta)
                                                <form method="POST"
                                                    action="{{ route('pedidos.editarCompletarLineaManual', [$pedido->id, $linea->id]) }}"
                                                    onsubmit="return confirmarCompletarLinea(this)">
                                                    @csrf
                                                    <button type="submit"
                                                        class="p-2 bg-green-50 text-green-600 hover:bg-green-100 rounded-xl transition-all shadow-sm"
                                                        title="Finalizar/Recepcionar">
                                                        🚚
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($estado === 'activo')
                                                <form method="POST"
                                                    action="{{ route('pedidos.lineas.editarDesactivar', [$pedido->id, $linea->id]) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="p-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl transition-all shadow-sm"
                                                        title="Desactivar">
                                                        ⏹️
                                                    </button>
                                                </form>
                                            @elseif ($esNavePaco)
                                                <form method="POST"
                                                    action="{{ route('pedidos.lineas.editarActivar', [$pedido->id, $linea->id]) }}">
                                                    @csrf @method('PUT')
                                                    <button type="submit"
                                                        class="p-2 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded-xl transition-all shadow-sm"
                                                        title="Activar">
                                                        ▶️
                                                    </button>
                                                </form>
                                            @endif

                                            <button type="button" onclick="abrirEdicionLinea({{ $linea->id }})"
                                                class="btn-editar-linea-{{ $linea->id }} p-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-xl transition-all shadow-sm"
                                                title="Editar">
                                                ✏️
                                            </button>
                                        </div>

                                        {{-- BOTONES DE EDICIÓN --}}
                                        <div class="flex items-center gap-1">
                                            <button type="button"
                                                onclick="guardarLinea({{ $linea->id }}, {{ $pedido->id }})"
                                                class="btn-guardar-linea-{{ $linea->id }} hidden p-2 bg-green-600 text-white rounded-xl shadow-lg shadow-green-100 hover:bg-green-700 transition-all">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                onclick="cancelarEdicionLinea({{ $linea->id }})"
                                                class="btn-cancelar-edicion-{{ $linea->id }} hidden p-2 bg-slate-200 text-slate-600 rounded-xl hover:bg-slate-300 transition-all">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $estado }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
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
            if (btnGuardar) btnGuardar.classList.remove('hidden');

            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            if (btnCancelar) btnCancelar.classList.remove('hidden');

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
            if (btnGuardar) btnGuardar.classList.add('hidden');

            const btnCancelar = document.querySelector(`.btn-cancelar-edicion-${lineaId}`);
            if (btnCancelar) btnCancelar.classList.add('hidden');
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
