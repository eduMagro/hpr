<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    .font-outfit {
        font-family: 'Outfit', sans-serif;
    }

    .card-hover {
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .label-pill {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #64748b;
    }
</style>

<div class="w-full sm:col-span-8 font-outfit">

    <div class="mb-8 flex flex-wrap justify-center gap-4 px-2 md:w-1/2">
        <button onclick="abrirModalMovimientoLibre()"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-2xl shadow-lg transition-all flex items-center gap-2 max-sm:w-full">
            <i data-lucide="shuffle" class="w-5 h-5"></i> Mover Materia Prima
        </button>
        <button onclick="abrirModalMoverPaquete()"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-2xl shadow-lg transition-all flex items-center gap-2 max-sm:w-full">
            <i data-lucide="package-plus" class="w-5 h-5"></i> Mover Paquete
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- COLUMNA PENDIENTES --}}
        <div>
            <div class="flex items-center justify-between mb-4 px-2">
                <h2 class="text-xl font-bold flex items-center gap-2 text-slate-800">
                    <i data-lucide="list-todo" class="w-6 h-6 text-red-500"></i>
                    Tareas Pendientes
                </h2>
                <span id="contador-tareas-pendientes"
                    class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold ring-4 ring-red-50">
                    {{ $movimientosPendientes->count() }} tareas
                </span>
            </div>

            <div class="space-y-4">
                @if ($movimientosPendientes->isEmpty())
                    <div class="bg-white rounded-3xl p-8 text-center border border-slate-100 shadow-sm">
                        <i data-lucide="check-circle-2" class="w-12 h-12 text-slate-200 mx-auto mb-3"></i>
                        <p class="text-slate-500 font-medium">No hay movimientos pendientes.</p>
                    </div>
                @else
                    @foreach ($movimientosPendientes as $mov)
                        @if (strtolower($mov->tipo) === 'entrada' && $mov->pedido)
                            @php
                                $proveedor =
                                    $mov->pedido->fabricante?->nombre ??
                                    ($mov->pedido->distribuidor?->nombre ?? 'No especificado');
                                $productoBase = $mov->productoBase;
                                $descripcionProducto = $productoBase
                                    ? sprintf(
                                        '%s Ø%s%s',
                                        ucfirst($productoBase->tipo),
                                        $productoBase->diametro,
                                        $productoBase->tipo === 'barra' && $productoBase->longitud
                                            ? ' x ' . $productoBase->longitud . 'm'
                                            : '',
                                    )
                                    : 'Producto no especificado';
                                $cantidadPedido = $mov->pedidoProducto?->cantidad ?? 'N/A';
                                $codigoLinea = $mov->pedidoProducto?->codigo ?? 'N/A';
                                $productoBaseId = $mov->producto_base_id ?? ($mov->productoBase?->id ?? '');
                                $urlRecepcion = "/pedidos/{$mov->pedido->id}/recepcion/{$productoBaseId}?movimiento_id={$mov->id}&maquina_id={$maquina->id}";
                            @endphp

                            <div id="movimiento-pendiente-{{ $mov->id }}"
                                class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 card-hover">
                                <div class="flex justify-between items-start mb-6">
                                    <span
                                        class="px-3 py-1 bg-blue-50 text-blue-700 rounded-xl text-[10px] font-bold uppercase tracking-widest">Entrada
                                        Material</span>
                                    <div class="flex items-center gap-1 text-slate-400">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span class="text-xs font-medium">{{ $mov->created_at->format('H:i') }}</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-y-6 gap-x-4 mb-8">
                                    <div class="col-span-1">
                                        <p class="label-pill mb-1">Cód. Línea</p>
                                        <p class="font-bold text-slate-900 border-l-2 border-blue-200 pl-2">
                                            {{ $codigoLinea }}</p>
                                    </div>
                                    <div class="col-span-1">
                                        <p class="label-pill mb-1">Solicita</p>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                {{ strtoupper(substr($mov->solicitadoPor->nombre ?? 'N', 0, 1) . substr($mov->solicitadoPor->apellidos ?? 'A', 0, 1)) }}
                                            </div>
                                            <p class="font-semibold text-slate-700 text-sm truncate">
                                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="label-pill mb-2">Producto y Proveedor</p>
                                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                            <p class="font-bold text-blue-900 text-lg mb-1">{{ $descripcionProducto }}
                                            </p>
                                            <p class="text-xs text-slate-500 font-medium mb-3">{{ $proveedor }}</p>
                                            <div class="flex items-center gap-4 text-sm font-medium text-slate-600">
                                                <span class="flex items-center gap-1"><i data-lucide="weight"
                                                        class="w-4 h-4 text-blue-500"></i> {{ $cantidadPedido }}
                                                    kg</span>
                                                @if ($mov->pedidoProducto && $mov->pedidoProducto->coladas->isNotEmpty())
                                                    <span class="flex items-center gap-1"><i data-lucide="layers"
                                                            class="w-4 h-4 text-blue-500"></i>
                                                        {{ $mov->pedidoProducto->coladas->count() }} Coladas</span>
                                                @endif
                                            </div>
                                            @if ($mov->pedidoProducto && $mov->pedidoProducto->coladas->isNotEmpty())
                                                <div
                                                    class="mt-3 pt-3 border-t border-slate-200/60 flex flex-wrap gap-1">
                                                    @foreach ($mov->pedidoProducto->coladas as $coladaItem)
                                                        <span
                                                            class="bg-white px-2 py-0.5 rounded text-[9px] font-bold text-slate-500 shadow-sm border border-slate-100">
                                                            {{ $coladaItem->colada }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ $urlRecepcion }}"
                                    class="w-full bg-slate-900 hover:bg-black text-white px-6 py-4 rounded-2xl font-bold text-sm transition-all shadow-lg flex items-center justify-center gap-2">
                                    <i data-lucide="play-circle" class="w-5 h-5"></i> INICIAR RECEPCIÓN
                                </a>
                            </div>
                        @elseif (str_contains(strtolower($mov->tipo), 'recarga'))
                            {{-- RECARGA MATERIA PRIMA PREMIUM --}}
                            @php
                                $productoBase = $mov->productoBase;
                                $descProducto = $productoBase
                                    ? sprintf(
                                        '%s Ø%s%s',
                                        ucfirst($productoBase->tipo),
                                        $productoBase->diametro,
                                        $productoBase->tipo === 'barra' && $productoBase->longitud
                                            ? ' x ' . $productoBase->longitud . 'm'
                                            : '',
                                    )
                                    : 'Materia Prima';
                                $maquinaDestino =
                                    $mov->maquinaDestino?->nombre ?? ($mov->maquina_destino ?? 'No especificada');
                            @endphp

                            <div id="movimiento-pendiente-{{ $mov->id }}"
                                class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 card-hover">
                                <div class="flex justify-between items-start mb-6">
                                    <span
                                        class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-xl text-[10px] font-bold uppercase tracking-widest">Recarga
                                        de Máquina</span>
                                    <div class="flex items-center gap-1 text-slate-400">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span class="text-xs font-medium">{{ $mov->created_at->format('H:i') }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-y-6 gap-x-4 mb-8">
                                    <div class="col-span-1">
                                        <p class="label-pill mb-1">Destino</p>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                                                <i data-lucide="monitor" class="w-3.5 h-3.5"></i>
                                            </div>
                                            <p
                                                class="font-bold text-slate-900 border-l-2 border-emerald-200 pl-2 leading-none py-1">
                                                {{ $maquinaDestino }}</p>
                                        </div>
                                    </div>
                                    <div class="col-span-1">
                                        <p class="label-pill mb-1">Solicita</p>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                {{ strtoupper(substr($mov->solicitadoPor->nombre ?? 'N', 0, 1)) }}
                                            </div>
                                            <p class="font-semibold text-slate-700 text-sm truncate">
                                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="label-pill mb-2">Detalles del Material</p>
                                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                            <p class="font-bold text-emerald-900 text-lg mb-1">{{ $descProducto }}</p>
                                            <p class="text-[11px] text-slate-500 font-medium leading-relaxed italic">
                                                {{ $mov->descripcion }}</p>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    onclick="abrirModalRecargaMateriaPrima({{ json_encode($mov->id) }}, {{ json_encode($mov->tipo) }}, {{ json_encode(optional($mov->producto)->codigo) }}, {{ json_encode($mov->maquina_destino) }}, {{ json_encode($mov->producto_base_id) }}, {{ json_encode($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? []) }}, {{ json_encode(optional($mov->maquinaDestino)->nombre ?? 'Máquina desconocida') }}, {{ json_encode(optional($mov->productoBase)->tipo ?? '') }}, {{ json_encode(optional($mov->productoBase)->diametro ?? '') }}, {{ json_encode(optional($mov->productoBase)->longitud ?? '') }})"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="zap" class="w-5 h-5"></i> CONFIRMAR RECARGA
                                </button>
                            </div>
                        @elseif (str_contains(strtolower($mov->tipo), 'salida'))
                            {{-- VISTA PREMIUM PARA SALIDAS --}}
                            @php
                                $d = $mov->descripcion;
                                // Regex para extraer datos clave del texto (mantenemos el parseo por si se necesita para otros campos)
                                $pattern =
                                    '/^([^\.]+)\. Se solicita carga del camión \((.*?)\) - \((.*?)\) para \[(.*?)\], tiene que estar listo a las (.*)$/';
                                $match = [];
                                $isStructured = preg_match($pattern, $d, $match);

                                if ($isStructured) {
                                    $codigoSalida = $match[1];
                                    $camion = $match[2];
                                    $transp = $match[3];
                                    $destino = $match[4];
                                }

                                // Cálculo de paquetes asociados
                                $paquetesCount = 0;
                                if ($mov->salida_id && $mov->salida) {
                                    $paquetesCount = $mov->salida->paquetes->count();
                                } elseif ($mov->salida_almacen_id && $mov->salidaAlmacen) {
                                    // Para salidas de almacén, sumamos las líneas de los albaranes relacionados
                                    $paquetesCount = $mov->salidaAlmacen->albaranes->flatMap->lineas->count();
                                }
                            @endphp

                            <div id="movimiento-pendiente-{{ $mov->id }}"
                                class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 card-hover">
                                <div class="flex justify-between items-start mb-6">
                                    <span
                                        class="px-3 py-1 bg-purple-50 text-purple-700 rounded-xl text-[10px] font-bold uppercase tracking-widest">Salida
                                        de Material</span>
                                    <div class="flex items-center gap-1 text-slate-400">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span class="text-xs font-medium">{{ $mov->created_at->format('H:i') }}</span>
                                    </div>
                                </div>

                                @if ($isStructured)
                                    <div class="space-y-4 mb-8">
                                        {{-- Fila 1: Código y Cantidad de Paquetes --}}
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                                <p class="label-pill mb-1">Referencia</p>
                                                <p class="font-bold text-slate-900 leading-none text-sm">
                                                    {{ $codigoSalida }}</p>
                                            </div>
                                            <div class="bg-blue-50 rounded-2xl p-3 border border-blue-100">
                                                <p class="label-pill mb-1 text-blue-600">Paquetes</p>
                                                <div
                                                    class="flex items-center gap-1.5 font-black text-blue-800 leading-none">
                                                    <i data-lucide="package" class="w-4 h-4"></i>
                                                    <span class="text-base">{{ $paquetesCount }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Fila 2: Camión --}}
                                        <div class="bg-purple-50 rounded-2xl p-4 border border-purple-100">
                                            <p class="label-pill mb-2 text-purple-600">Transporte</p>
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center text-purple-600">
                                                    <i data-lucide="truck" class="w-6 h-6"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-purple-900 leading-tight">
                                                        {{ $camion }}</p>
                                                    <p class="text-[10px] font-bold text-purple-400 uppercase">
                                                        {{ $transp }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Fila 3: Destino --}}
                                        <div class="px-4 py-3 bg-slate-50 rounded-2xl border border-slate-100">
                                            <p class="label-pill mb-1">Destino / Proyecto</p>
                                            <div class="flex items-start gap-2">
                                                <i data-lucide="map-pin" class="w-4 h-4 text-slate-400 mt-0.5"></i>
                                                <p class="font-semibold text-slate-700 text-xs leading-relaxed">
                                                    {{ $destino }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mb-8">
                                        <p class="label-pill mb-1">Descripción / Referencia</p>
                                        <h3 class="text-lg font-bold text-slate-900 leading-tight">
                                            {{ $mov->descripcion }}</h3>
                                    </div>
                                @endif

                                <div class="flex items-center gap-2 mb-6 text-slate-500 text-sm font-medium">
                                    <div
                                        class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-400">
                                        {{ strtoupper(substr($mov->solicitadoPor->nombre ?? 'N', 0, 1)) }}
                                    </div>
                                    <span>Solicita:
                                        {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</span>
                                </div>

                                <button
                                    onclick="@if (strtolower($mov->tipo) === 'salida') ejecutarSalida({{ json_encode($mov->id) }}, {{ json_encode($mov->salida_id) }}) @else ejecutarSalidaAlmacen({{ json_encode($mov->id) }}) @endif"
                                    class="w-full bg-purple-600 hover:bg-purple-700 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg shadow-black/10 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="truck" class="w-5 h-5"></i> EJECUTAR SALIDA
                                </button>
                            </div>
                        @else
                            {{-- OTROS TIPOS DE PENDIENTES --}}
                            <div id="movimiento-pendiente-{{ $mov->id }}"
                                class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 card-hover">
                                <div class="flex justify-between items-start mb-6">
                                    @php
                                        $badgeClass = 'bg-slate-50 text-slate-700';
                                        $icon = 'activity';
                                        if (str_contains(strtolower($mov->tipo), 'bajada')) {
                                            $badgeClass = 'bg-amber-50 text-amber-700';
                                            $icon = 'package';
                                        } elseif (str_contains(strtolower($mov->tipo), 'recarga')) {
                                            $badgeClass = 'bg-emerald-50 text-emerald-700';
                                            $icon = 'zap';
                                        } elseif (str_contains(strtolower($mov->tipo), 'salida')) {
                                            $badgeClass = 'bg-purple-50 text-purple-700';
                                            $icon = 'truck';
                                        } elseif (str_contains(strtolower($mov->tipo), 'preparación')) {
                                            $badgeClass = 'bg-orange-50 text-orange-700';
                                            $icon = 'wrench';
                                        }
                                    @endphp
                                    <span
                                        class="px-3 py-1 {{ $badgeClass }} rounded-xl text-[10px] font-bold uppercase tracking-widest">{{ $mov->tipo }}</span>
                                    <div class="flex items-center gap-1 text-slate-400">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span class="text-xs font-medium">{{ $mov->created_at->format('H:i') }}</span>
                                    </div>
                                </div>

                                <div class="mb-8">
                                    <p class="label-pill mb-1">Descripción / Referencia</p>
                                    <h3 class="text-lg font-bold text-slate-900 leading-tight">{{ $mov->descripcion }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-3 text-slate-500 text-sm font-medium">
                                        <div
                                            class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-400">
                                            {{ strtoupper(substr($mov->solicitadoPor->nombre ?? 'N', 0, 1)) }}
                                        </div>
                                        <span>Solicita:
                                            {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                {{-- BOTONES DE ACCIÓN REALES --}}
                                @if (strtolower($mov->tipo) === 'bajada de paquete')
                                    <button
                                        onclick="abrirModalBajadaPaquete({{ json_encode(['id' => $mov->id, 'paquete_id' => $mov->paquete_id, 'ubicacion_origen' => $mov->ubicacion_origen, 'descripcion' => $mov->descripcion]) }})"
                                        class="w-full bg-amber-500 hover:bg-amber-600 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                                        <i data-lucide="arrow-down-circle" class="w-5 h-5"></i> EJECUTAR BAJADA
                                    </button>
                                @elseif (strtolower($mov->tipo) === 'recarga materia prima')
                                    <button
                                        onclick="abrirModalRecargaMateriaPrima({{ json_encode($mov->id) }}, {{ json_encode($mov->tipo) }}, {{ json_encode(optional($mov->producto)->codigo) }}, {{ json_encode($mov->maquina_destino) }}, {{ json_encode($mov->producto_base_id) }}, {{ json_encode($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? []) }}, {{ json_encode(optional($mov->maquinaDestino)->nombre ?? 'Máquina desconocida') }}, {{ json_encode(optional($mov->productoBase)->tipo ?? '') }}, {{ json_encode(optional($mov->productoBase)->diametro ?? '') }}, {{ json_encode(optional($mov->productoBase)->longitud ?? '') }})"
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                                        <i data-lucide="zap" class="w-5 h-5"></i> CONFIRMAR RECARGA
                                    </button>
                                @elseif (strtolower($mov->tipo) === 'salida' || strtolower($mov->tipo) === 'salida almacén')
                                    <button
                                        onclick="@if (strtolower($mov->tipo) === 'salida') ejecutarSalida({{ json_encode($mov->id) }}, {{ json_encode($mov->salida_id) }}) @else ejecutarSalidaAlmacen({{ json_encode($mov->id) }}) @endif"
                                        class="w-full bg-purple-600 hover:bg-purple-700 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                                        <i data-lucide="truck" class="w-5 h-5"></i> EJECUTAR SALIDA
                                    </button>
                                @elseif (strtolower($mov->tipo) === 'preparación paquete')
                                    <button onclick="abrirModalPreparacionPaquete(@json($mov->id))"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                                        <i data-lucide="package" class="w-5 h-5"></i> PREPARAR
                                    </button>
                                @elseif (strtolower($mov->tipo) === 'preparación elementos')
                                    @php
                                        $planillaIdMatch = [];
                                        preg_match(
                                            '/\[planilla_id:(\d+)\]/',
                                            $mov->descripcion ?? '',
                                            $planillaIdMatch,
                                        );
                                        $planillaIdFabricar = $planillaIdMatch[1] ?? null;
                                    @endphp
                                    @if ($planillaIdFabricar)
                                        <a href="{{ route('maquinas.show', ['maquina' => $maquina->id, 'fabricar_planilla' => $planillaIdFabricar]) }}"
                                            class="w-full bg-orange-600 hover:bg-orange-700 text-white px-6 py-4 rounded-2xl font-bold text-sm shadow-lg transition-all flex items-center justify-center gap-2">
                                            <i data-lucide="hammer" class="w-5 h-5"></i> FABRICAR ELEMENTOS
                                        </a>
                                    @endif
                                @endif
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>

        {{-- COLUMNA COMPLETADOS --}}
        <div>
            <div class="flex items-center justify-between mb-4 px-2">
                <h2 class="text-xl font-bold flex items-center gap-2 text-slate-800">
                    <i data-lucide="history" class="w-6 h-6 text-emerald-500"></i>
                    Historial Reciente
                </h2>
                <div id="paginador-movimientos-completados" class="flex items-center gap-1"></div>
            </div>

            <div class="space-y-4" id="contenedor-movimientos-completados">
                @if ($movimientosCompletados->isEmpty())
                    <div class="bg-white rounded-3xl p-8 text-center border border-slate-100 shadow-sm">
                        <i data-lucide="archive" class="w-12 h-12 text-slate-200 mx-auto mb-3"></i>
                        <p class="text-slate-500 font-medium">No hay movimientos completados.</p>
                    </div>
                @else
                    @foreach ($movimientosCompletados as $mov)
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/60 relative group movimiento-completado"
                            data-movimiento-id="{{ $mov->id }}">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900">{{ ucfirst($mov->tipo) }}</h3>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                            {{ $mov->updated_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                <button
                                    onclick="eliminarMovimientoGrua({{ $mov->id }}, '{{ $mov->producto_consumido_id ? optional($mov->productoConsumido)->codigo ?? '' : '' }}')"
                                    class="opacity-0 group-hover:opacity-100 p-2 text-slate-300 hover:text-red-500 transition-all">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <div class="bg-slate-50 rounded-2xl p-4 mb-4 border border-slate-100/50">
                                <div class="text-sm text-slate-600 leading-relaxed font-medium italic">
                                    {!! $mov->descripcion_html !!}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 {{ $mov->producto_consumido_id ? 'mb-4' : '' }}">
                                <div
                                    class="flex items-center gap-2 px-3 py-2 bg-blue-50/50 rounded-xl border border-blue-100/50">
                                    <div
                                        class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[8px] font-bold">
                                        {{ strtoupper(substr($mov->solicitadoPor->nombre ?? 'N', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-[8px] text-blue-600 font-bold uppercase">Solicitó</p>
                                        <p class="text-[10px] font-bold text-slate-700 truncate w-20">
                                            {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 px-3 py-2 bg-emerald-50/50 rounded-xl border border-emerald-100/50">
                                    <div
                                        class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[8px] font-bold">
                                        {{ strtoupper(substr($mov->ejecutadoPor->nombre ?? 'E', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-[8px] text-emerald-600 font-bold uppercase">Ejecutó</p>
                                        <p class="text-[10px] font-bold text-slate-700 truncate w-20">
                                            {{ optional($mov->ejecutadoPor)->nombre_completo ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            @if ($mov->producto_consumido_id)
                                <div
                                    class="bg-slate-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center shadow-md">
                                            <i data-lucide="refresh-cw" class="w-5 h-5 text-white"></i>
                                        </div>
                                        <div>
                                            <p
                                                class="text-[10px] font-black text-amber-800 uppercase leading-none mb-1">
                                                Aviso Trazabilidad</p>
                                            <p class="text-xs font-semibold text-slate-700">Producto <span
                                                    class="bg-white px-1 rounded font-mono border border-slate-200">{{ optional($mov->productoConsumido)->codigo ?? 'N/A' }}</span>
                                                consumido</p>
                                        </div>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-400 opacity-60">RECUPERABLE</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        reiniciarPaginacionCompletados();
    });
</script>


<script>
    function reiniciarPaginacionCompletados() {
        const itemsPorPagina = 5;
        const items = Array.from(document.querySelectorAll('.movimiento-completado'));
        const paginador = document.getElementById('paginador-movimientos-completados');
        if (!paginador) return;

        const totalPaginas = Math.ceil(items.length / itemsPorPagina);

        function mostrarPagina(pagina) {
            const inicio = (pagina - 1) * itemsPorPagina;
            const fin = inicio + itemsPorPagina;
            items.forEach((item, index) => {
                item.style.display = (index >= inicio && index < fin) ? 'block' : 'none';
            });
            actualizarPaginador(pagina);
        }

        function actualizarPaginador(paginaActual) {
            paginador.innerHTML = '';
            if (totalPaginas <= 1) return;

            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `px-3 py-1 rounded-xl border text-xs font-bold transition-all ${
                    i === paginaActual
                        ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                        : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50'
                }`;
                btn.onclick = () => mostrarPagina(i);
                paginador.appendChild(btn);
            }
        }

        if (items.length > 0) {
            mostrarPagina(1);
        } else {
            paginador.innerHTML = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        reiniciarPaginacionCompletados();
    });

    // Escuchar evento de movimiento de paquete para actualizar la lista
    window.addEventListener('movimiento:paquete-creado', async function() {
        // Cerrar modal si está abierto
        const modal = document.getElementById('modal-mover-paquete');
        if (modal && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }

        // Actualizar lista de movimientos completados via AJAX
        try {
            const naveId = {{ $maquina->obra_id ?? 1 }};
            const response = await fetch(`/maquinas/movimientos-completados/${naveId}`);
            const data = await response.json();

            if (data.success && data.movimientos) {
                actualizarListaMovimientosCompletados(data.movimientos);
            }
        } catch (error) {
            console.error('Error al actualizar movimientos:', error);
        }
    });

    // Función para actualizar la lista de movimientos completados
    function actualizarListaMovimientosCompletados(movimientos) {
        const contenedor = document.getElementById('contenedor-movimientos-completados');
        if (!contenedor) return;

        const lista = contenedor.querySelector('ul');
        const mensajeVacio = contenedor.querySelector('p.text-gray-600');

        // Si hay movimientos, actualizar la lista
        if (movimientos.length > 0) {
            if (mensajeVacio) mensajeVacio.remove();

            // Crear o actualizar lista
            let ul = lista;
            if (!ul) {
                ul = document.createElement('ul');
                ul.className = 'space-y-3';
                contenedor.appendChild(ul);
            }

            // Limpiar lista existente
            ul.innerHTML = '';

            // Agregar nuevos movimientos
            movimientos.forEach(mov => {
                const div = document.createElement('div');
                div.className =
                    'bg-white rounded-3xl p-6 shadow-sm border border-slate-200/60 relative group movimiento-completado';
                div.dataset.movimientoId = mov.id;

                div.innerHTML = `
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <i data-lucide="check-circle" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900">${mov.tipo}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">${mov.fecha_completado}</p>
                            </div>
                        </div>
                        <button onclick="eliminarMovimientoGrua(${mov.id}, '${mov.producto_consumido_codigo || ''}')"
                            class="opacity-0 group-hover:opacity-100 p-2 text-slate-300 hover:text-red-500 transition-all">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 mb-4 border border-slate-100/50">
                        <div class="text-sm text-slate-600 leading-relaxed font-medium italic">
                            ${mov.descripcion_html}
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2 px-3 py-2 bg-blue-50/50 rounded-xl border border-blue-100/50">
                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[8px] font-bold">
                                ${mov.solicitado_por.substring(0,1).toUpperCase()}
                            </div>
                            <div>
                                <p class="text-[8px] text-blue-600 font-bold uppercase">Solicitó</p>
                                <p class="text-[10px] font-bold text-slate-700 truncate w-20">${mov.solicitado_por}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-emerald-50/50 rounded-xl border border-emerald-100/50">
                            <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[8px] font-bold">
                                ${mov.ejecutado_por.substring(0,1).toUpperCase()}
                            </div>
                            <div>
                                <p class="text-[8px] text-emerald-600 font-bold uppercase">Ejecutó</p>
                                <p class="text-[10px] font-bold text-slate-700 truncate w-20">${mov.ejecutado_por}</p>
                            </div>
                        </div>
                    </div>
                `;
                ul.appendChild(div);
            });
            lucide.createIcons();

            // Re-inicializar paginación
            reiniciarPaginacionCompletados();
        }
    }

    // Función para reiniciar la paginación después de actualizar
    function reiniciarPaginacionCompletados() {
        const itemsPorPagina = 5;
        const items = Array.from(document.querySelectorAll('.movimiento-completado'));
        const paginador = document.getElementById('paginador-movimientos-completados');
        const totalPaginas = Math.ceil(items.length / itemsPorPagina);

        function mostrarPagina(pagina) {
            const inicio = (pagina - 1) * itemsPorPagina;
            const fin = inicio + itemsPorPagina;
            items.forEach((item, index) => {
                item.style.display = (index >= inicio && index < fin) ? 'block' : 'none';
            });
            actualizarPaginador(pagina);
        }

        function actualizarPaginador(paginaActual) {
            paginador.innerHTML = '';
            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `px-3 py-1 rounded border text-sm ${
                    i === paginaActual
                        ? 'bg-green-600 text-white'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                }`;
                btn.onclick = () => mostrarPagina(i);
                paginador.appendChild(btn);
            }
        }

        if (items.length > 0) {
            mostrarPagina(1);
        }
    }
</script>
<script>
    function ejecutarSalida(movimientoId, salidaId) {
        // Abrir modal de ejecutar salida con sistema de escaneo
        abrirModalEjecutarSalida(movimientoId, salidaId);
    }
</script>
<script>
    /* =========================================================
 Salidas de almacén – UI de ejecución (rutas WEB)
 Ahora trabajamos con líneas de albarán (albaranes_venta_lineas)
========================================================= */

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const codigosEscaneados = new Set();
    const productosEscaneadosPorLinea = {}; // { [linea_id]: Array<{codigo, peso_kg, cantidad}> }
    const
        metaLineas = {}; // { [linea_id]: { objetivoKg, objetivoUd, asignadoKg, asignadoUd, label, diametro, longitud, cliente, pedido } }
    window._salidaActualId = null;

    // ====== Utilidad fetch ======
    async function fetchJSON(url, options = {}) {
        const res = await fetch(url, options);
        let data = null;
        try {
            data = await res.json();
        } catch {}
        return {
            ok: res.ok,
            status: res.status,
            data
        };
    }

    // ====== Helpers UI ======
    function mostrarErrorInline(lineaId, mensaje) {
        const box = document.getElementById(`error-ln-${lineaId}`);
        if (!box) return;
        box.textContent = mensaje || 'Ha ocurrido un error.';
        box.classList.remove('hidden');
        const input = document.querySelector(`input[data-ln="${lineaId}"]`);
        if (input) {
            const clear = () => {
                box.classList.add('hidden');
                box.textContent = '';
                input.removeEventListener('input', clear);
            };
            input.addEventListener('input', clear);
        }
    }

    function limpiarErrorInline(lineaId) {
        const box = document.getElementById(`error-ln-${lineaId}`);
        if (!box) return;
        box.classList.add('hidden');
        box.textContent = '';
    }

    function mostrarOkPequenyo(lineaId) {
        const input = document.querySelector(`input[data-ln="${lineaId}"]`);
        if (!input) return;
        let badge = document.getElementById(`ok-ln-${lineaId}`);
        if (!badge) {
            badge = document.createElement('div');
            badge.id = `ok-ln-${lineaId}`;
            badge.className = 'text-xs text-green-600 mt-1';
            input.insertAdjacentElement('afterend', badge);
        }
        badge.textContent = '✓ añadido';
        badge.style.opacity = 1;
        setTimeout(() => {
            badge.style.opacity = 0;
        }, 900);
    }

    function etiquetaPB(diam, long) {
        const d = (diam ?? '').toString().trim();
        const l = (long ?? '').toString().trim();
        return `Ø${d || '—'}${l ? ` · ${l}m` : ''}`;
    }

    // ====== Progreso por línea ======
    function actualizarProgresoLinea(lineaId) {
        const meta = metaLineas[lineaId] || {};
        const asign = meta.objetivoKg != null ? (meta.asignadoKg || 0) : (meta.asignadoUd || 0);
        const obj = meta.objetivoKg != null ? meta.objetivoKg : meta.objetivoUd;
        const pct = obj && obj > 0 ? Math.min(100, Math.round((asign / obj) * 100)) : 0;

        const bar = document.getElementById(`bar-${lineaId}`);
        const chip = document.getElementById(`estado-chip-${lineaId}`);
        const head = document.getElementById(`asignado-head-${lineaId}`);

        if (bar) bar.style.width = pct + '%', bar.style.backgroundColor = pct >= 100 ? '#10B981' : (pct > 0 ?
            '#FBBF24' : '#E5E7EB');
        if (chip) {
            let texto = 'pendiente',
                cls = 'bg-gray-100 text-gray-700';
            if (pct >= 100) {
                texto = 'completo';
                cls = 'bg-green-100 text-green-700';
            } else if (pct > 0) {
                texto = 'parcial';
                cls = 'bg-amber-100 text-amber-700';
            }
            chip.className = `text-xs px-2 py-0.5 rounded ${cls}`;
            chip.textContent = texto;
        }
        if (head) head.textContent = meta.objetivoKg != null ? `${asign} kg` : `${asign} ud`;
    }

    function recomputarTotalesLinea(lineaId) {
        const meta = metaLineas[lineaId] || {};
        const lista = productosEscaneadosPorLinea[lineaId] || [];
        let sumKg = 0,
            sumUd = 0;
        for (const it of lista) {
            if (typeof it.peso_kg === 'number' && it.peso_kg > 0) sumKg += it.peso_kg;
            else if (typeof it.cantidad === 'number' && it.cantidad > 0) sumUd += it.cantidad;
        }
        if (meta.objetivoKg != null) metaLineas[lineaId].asignadoKg = sumKg;
        if (meta.objetivoUd != null) metaLineas[lineaId].asignadoUd = sumUd;
    }

    // ====== Lista escaneados ======
    function renderizarListaPorLinea(lineaId) {
        const cont = document.getElementById(`productos-escaneados-${lineaId}`);
        const lista = productosEscaneadosPorLinea[lineaId] || [];
        if (!cont) return;
        if (!lista.length) {
            cont.innerHTML = `<span class="text-gray-500">Sin productos escaneados.</span>`;
            recomputarTotalesLinea(lineaId);
            actualizarProgresoLinea(lineaId);
            return;
        }
        lista.sort((a, b) => String(a.codigo).localeCompare(String(b.codigo)));
        const totalKg = lista.reduce((acc, p) => acc + (p.peso_kg ? Number(p.peso_kg) : 0), 0);
        const totalUd = lista.reduce((acc, p) => acc + (p.cantidad ? Number(p.cantidad) : 0), 0);

        cont.innerHTML = `
      <div class="mt-2 border rounded overflow-auto max-h-56">
        <table class="w-full text-xs">
          <thead class="bg-gray-50 sticky top-0 z-10">
            <tr>
              <th class="text-left px-2 py-1 border-b">Código</th>
              <th class="text-right px-2 py-1 border-b">Stock</th>
              <th class="text-right px-2 py-1 border-b">${totalKg > 0 ? 'Asignado (kg)' : 'Asignado (ud)'}</th>
              <th class="text-left px-2 py-1 border-b">Acciones</th>
            </tr>
          </thead>
          <tbody>
            ${lista.map((p, i) => `
              <tr class="${i % 2 ? 'bg-gray-50/30' : ''}">
                <td class="px-2 py-1 border-b">${p.codigo}</td>
                <td class="px-2 py-1 border-b text-right">${(p.peso_stock ?? 0).toLocaleString()} kg</td>
                <td class="px-2 py-1 border-b text-right">${p.peso_kg ?? p.cantidad}</td>
                <td class="px-2 py-1 border-b">
                  <button class="text-red-600 hover:underline" onclick="eliminarEscaneado('${p.codigo}', ${lineaId})">Quitar</button>
                </td>
              </tr>`).join('')}
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td class="px-2 py-1 border-t font-semibold">Total</td>
              <td></td>
              <td class="px-2 py-1 border-t text-right font-semibold">${totalKg > 0 ? totalKg + ' kg' : totalUd + ' ud'}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>`;
        recomputarTotalesLinea(lineaId);
        actualizarProgresoLinea(lineaId);
    }

    // ====== Refrescar desde backend ======
    async function refrescarAsignadosDesdeBackend(salidaId, lineaId = null) {
        const {
            ok,
            data
        } = await fetchJSON(`/salidas-almacen/${salidaId}/asignados`);
        if (!ok || !data?.success) return;
        const mapa = data.asignados || {};
        codigosEscaneados.clear();
        Object.keys(productosEscaneadosPorLinea).forEach(k => delete productosEscaneadosPorLinea[k]);
        Object.entries(mapa).forEach(([ln, arr]) => {
            const id = Number(ln);
            productosEscaneadosPorLinea[id] = Array.isArray(arr) ? arr : [];
            (productosEscaneadosPorLinea[id]).forEach(it => codigosEscaneados.add(it.codigo));
        });
        if (lineaId != null) {
            renderizarListaPorLinea(lineaId);
        } else {
            Object.keys(productosEscaneadosPorLinea).forEach(id => renderizarListaPorLinea(Number(id)));
        }
    }

    // ====== Eliminar escaneado ======
    async function eliminarEscaneado(codigo, lineaId) {
        const salidaId = window._salidaActualId;
        if (!salidaId) {
            mostrarErrorInline(lineaId, 'No se reconoce la salida actual.');
            return;
        }
        const res = await fetch(`/salidas-almacen/${salidaId}/detalle/${encodeURIComponent(codigo)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data?.success) {
            mostrarErrorInline(lineaId, data?.message || 'No se pudo eliminar.');
            return;
        }
        await refrescarAsignadosDesdeBackend(salidaId, lineaId);
    }

    // ====== Abrir modal ======
    async function ejecutarSalidaAlmacen(movimientoId) {
        const {
            ok,
            data
        } = await fetchJSON(`/salidas-almacen/${movimientoId}/productos`);
        if (!ok || !data?.success) {
            Swal.fire('⚠️ Error', data?.message || 'No se pudo obtener info', 'warning');
            return;
        }
        const lineas = data.lineas || [];
        const salidaId = data.salida_id;
        window._salidaActualId = salidaId;

        let html =
            `<p class="mb-3 text-sm text-red-600">${data.observaciones || 'Escanea productos para completar la salida:'}</p>`;
        lineas.forEach(ln => {
            const key = ln.id;
            html += `
          <div class="p-2 border rounded bg-white mb-2">
            <div class="flex items-center justify-between">
              <div>
                <strong>Ø${ln.diametro ?? '—'} ${ln.longitud ? '· ' + ln.longitud + 'm' : ''}</strong>
                <div class="text-xs text-gray-600">Pedido: ${ln.pedido_codigo || '—'} · Cliente: ${ln.cliente || '—'}</div>
                <div class="text-xs text-gray-600">
                  Objetivo: ${ln.peso_objetivo_kg ? ln.peso_objetivo_kg+' kg' : ln.unidades_objetivo+' ud'}
                  · Asignado: <span id="asignado-head-${key}">${ln.asignado_kg || ln.asignado_ud || 0}</span>
                </div>
              </div>
              <span id="estado-chip-${key}" class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">pendiente</span>
            </div>
            <div class="w-full h-1.5 bg-gray-200 rounded mt-2"><div id="bar-${key}" class="h-1.5 rounded" style="width:0%"></div></div>
            <input type="text" class="w-full mt-2 border px-2 py-1 rounded text-sm" placeholder="Escanea producto..." data-ln="${key}" onkeydown="if(event.key==='Enter'){event.preventDefault(); agregarProductoEscaneado(this, ${key}, ${salidaId});}">
            <div id="error-ln-${key}" class="text-xs text-red-600 mt-1 hidden"></div>
            <div id="productos-escaneados-${key}" class="text-xs text-gray-700 mt-1"></div>
          </div>`;
        });

        await Swal.fire({
            title: 'Salida Almacén',
            html,
            showCancelButton: true,
            confirmButtonText: 'Finalizar salida',
            confirmButtonColor: '#38a169',
            cancelButtonText: 'Cancelar',
            width: '50rem',
            didOpen: () => {
                lineas.forEach(ln => {
                    const key = ln.id;
                    metaLineas[key] = {
                        objetivoKg: ln.peso_objetivo_kg ?? null,
                        objetivoUd: ln.unidades_objetivo ?? null,
                        asignadoKg: ln.asignado_kg ?? 0,
                        asignadoUd: ln.asignado_ud ?? 0,
                        label: etiquetaPB(ln.diametro, ln.longitud),
                        diametro: ln.diametro ?? null,
                        longitud: ln.longitud ?? null
                    };
                    productosEscaneadosPorLinea[key] = ln.asignados || [];
                    (ln.asignados || []).forEach(it => codigosEscaneados.add(it.codigo));
                    actualizarProgresoLinea(key);
                    renderizarListaPorLinea(key);
                });
                refrescarAsignadosDesdeBackend(salidaId);
            },
            preConfirm: async () => {
                const resp = await fetchJSON(
                    `/salidas-almacen/completar-desde-movimiento/${movimientoId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({})
                    });
                if (!resp.ok || !resp.data?.success) {
                    Swal.showValidationMessage(resp.data?.message || 'No se pudo completar.');
                    return false;
                }
                return resp.data;
            }
        }).then(r => {
            if (r.isConfirmed) {
                Swal.fire('', r.value.message || 'Salida completada.', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        });
    }

    // ====== Escaneo / Validación ======
    async function agregarProductoEscaneado(input, lineaId, salidaId) {
        const codigoQR = input.value.trim();
        if (!codigoQR) return;
        limpiarErrorInline(lineaId);
        if (codigosEscaneados.has(codigoQR)) {
            mostrarErrorInline(lineaId, 'Este producto ya ha sido escaneado.');
            input.select();
            return;
        }
        const {
            ok,
            data,
            status
        } = await fetchJSON('/productos/validar-para-salida', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                codigo: codigoQR,
                salida_almacen_id: salidaId,
                albaran_linea_id: lineaId
            })
        });
        if (!ok || !data?.success || !data.producto) {
            mostrarErrorInline(lineaId, data?.message || 'Error validando.');
            input.select();
            return;
        }
        codigosEscaneados.add(codigoQR);
        await refrescarAsignadosDesdeBackend(salidaId, lineaId);
        input.value = '';
        input.focus();
        mostrarOkPequenyo(lineaId);
    }

    // ====== Exponer ======
    window.ejecutarSalidaAlmacen = ejecutarSalidaAlmacen;
    window.agregarProductoEscaneado = agregarProductoEscaneado;
    window.eliminarEscaneado = eliminarEscaneado;
    window.mostrarErrorInline = mostrarErrorInline;
    window.limpiarErrorInline = limpiarErrorInline;
</script>
{{-- Modal de Preparación de Paquete --}}
<div id="modalPreparacionPaquete"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden m-4">
        <div class="flex justify-between items-center p-4 border-b bg-blue-600 text-white">
            <h2 class="text-xl font-bold">
                📦 Preparar Paquete <span id="modalPaqueteCodigo"></span>
            </h2>
            <button onclick="cerrarModalPreparacionPaquete()"
                class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        <div id="modalPreparacionContenido" class="p-4 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Cargando etiquetas...</span>
            </div>
        </div>
        <div class="flex justify-end gap-3 p-4 border-t bg-gray-50">
            <button onclick="cerrarModalPreparacionPaquete()"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded font-semibold">
                Cancelar
            </button>
            <button id="btnCompletarPreparacion" onclick="completarPreparacionDesdeModal()"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold">
                ✅ Marcar como preparado
            </button>
        </div>
    </div>
</div>

<script>
    // ====== PREPARACIÓN PAQUETE (elementos sin elaborar) ======
    let movimientoActualId = null;

    async function abrirModalPreparacionPaquete(movimientoId) {
        movimientoActualId = movimientoId;
        const modal = document.getElementById('modalPreparacionPaquete');
        const contenido = document.getElementById('modalPreparacionContenido');
        const codigoSpan = document.getElementById('modalPaqueteCodigo');

        // Mostrar modal con loading
        modal.classList.remove('hidden');
        contenido.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Cargando etiquetas...</span>
            </div>
        `;

        try {
            const response = await fetch(`/movimientos/${movimientoId}/etiquetas-paquete`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                codigoSpan.textContent = `#${data.paquete.codigo}`;

                if (data.total_etiquetas === 0) {
                    contenido.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-lg">No hay etiquetas con elementos sin elaborar en este paquete.</p>
                        </div>
                    `;
                } else {
                    contenido.innerHTML = `
                        <p class="text-sm text-gray-600 mb-4">
                            ${data.total_etiquetas} etiqueta(s) con elementos sin elaborar:
                        </p>
                        <div class="flex flex-wrap gap-4 justify-center">
                            ${data.html}
                        </div>
                    `;
                }
            } else {
                contenido.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p class="text-lg">${data.message || 'Error al cargar las etiquetas.'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            contenido.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p class="text-lg">Error de conexión al cargar las etiquetas.</p>
                </div>
            `;
        }
    }

    function cerrarModalPreparacionPaquete() {
        document.getElementById('modalPreparacionPaquete').classList.add('hidden');
        movimientoActualId = null;
    }

    async function completarPreparacionDesdeModal() {
        if (!movimientoActualId) return;

        const result = await Swal.fire({
            title: '¿Marcar como preparado?',
            text: 'El paquete quedará listo para su entrega.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar como preparado',
            confirmButtonColor: '#10B981',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/movimientos/${movimientoActualId}/completar-preparacion`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                cerrarModalPreparacionPaquete();
                await Swal.fire({
                    title: '¡Preparado!',
                    text: data.message || 'El paquete ha sido marcado como preparado.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'No se pudo completar la preparación.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Ha ocurrido un error de conexión.', 'error');
        }
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarModalPreparacionPaquete();
    });

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalPreparacionPaquete')?.addEventListener('click', (e) => {
        if (e.target.id === 'modalPreparacionPaquete') cerrarModalPreparacionPaquete();
    });

    window.abrirModalPreparacionPaquete = abrirModalPreparacionPaquete;
    window.cerrarModalPreparacionPaquete = cerrarModalPreparacionPaquete;
    window.completarPreparacionDesdeModal = completarPreparacionDesdeModal;
</script>

<script>
    // ====== ELIMINAR MOVIMIENTO COMPLETADO CON RECUPERACIÓN DE PRODUCTO ======
    async function eliminarMovimientoGrua(movimientoId, codigoProductoConsumido) {
        // Preparar mensaje de confirmación según si hay producto consumido o no
        let mensajeHtml = '<p class="text-left">¿Estás seguro de eliminar este movimiento?</p>';
        let tituloAlerta = 'Eliminar Movimiento';
        const tieneProductoConsumido = codigoProductoConsumido && codigoProductoConsumido !== '';

        if (tieneProductoConsumido) {
            mensajeHtml = `
                <div class="text-left">
                    <p class="mb-3">¿Estás seguro de eliminar este movimiento?</p>
                    <div class="bg-orange-100 border-l-4 border-orange-500 p-3 rounded">
                        <p class="text-orange-700 font-semibold">⚠️ Atención:</p>
                        <p class="text-orange-700 text-sm mt-1">
                            Se recuperará el producto <strong>${codigoProductoConsumido}</strong> que fue consumido
                            automáticamente cuando se realizó este movimiento.
                        </p>
                        <p class="text-orange-700 text-sm mt-1">
                            El producto volverá al estado <strong>"fabricando"</strong> en la máquina correspondiente.
                        </p>
                    </div>
                </div>
            `;
            tituloAlerta = '⚠️ Eliminar Movimiento';
        }

        const result = await Swal.fire({
            title: tituloAlerta,
            html: mensajeHtml,
            icon: tieneProductoConsumido ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            confirmButtonColor: '#DC2626',
            cancelButtonText: 'Cancelar',
            cancelButtonColor: '#6B7280',
            reverseButtons: true
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch(`/movimientos/${movimientoId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Construir mensaje de éxito
                let mensajeExito = data.message || 'Movimiento eliminado correctamente.';

                if (data.producto_consumido_recuperado && data.producto_recuperado) {
                    const codigoProducto = data.producto_recuperado.codigo || 'desconocido';
                    mensajeExito +=
                        `<br><br><span class="text-green-700">✅ Producto <strong>${codigoProducto}</strong> recuperado exitosamente.</span>`;
                }

                await Swal.fire({
                    title: '¡Eliminado!',
                    html: mensajeExito,
                    icon: 'success',
                    timer: 2500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });

                // Eliminar el elemento de la lista visualmente
                const elemento = document.querySelector(`[data-movimiento-id="${movimientoId}"]`);
                if (elemento) {
                    elemento.style.transition = 'opacity 0.3s, transform 0.3s';
                    elemento.style.opacity = '0';
                    elemento.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        elemento.remove();
                        // Reiniciar paginación
                        reiniciarPaginacionCompletados();

                        // Si no quedan más movimientos, mostrar mensaje vacío
                        const contenedor = document.getElementById('contenedor-movimientos-completados');
                        const items = contenedor?.querySelectorAll('.movimiento-completado');
                        if (items && items.length === 0) {
                            const lista = contenedor.querySelector('ul');
                            if (lista) lista.remove();
                            const pVacio = document.createElement('p');
                            pVacio.className = 'text-gray-600 text-sm';
                            pVacio.textContent = 'No hay movimientos completados.';
                            contenedor.querySelector('h3').insertAdjacentElement('afterend', pVacio);
                        }
                    }, 300);
                } else {
                    // Si no se encuentra el elemento, recargar la página
                    location.reload();
                }
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'No se pudo eliminar el movimiento.',
                    icon: 'error'
                });
            }
        } catch (error) {
            console.error('Error al eliminar movimiento:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ha ocurrido un error de conexión.',
                icon: 'error'
            });
        }
    }

    window.eliminarMovimientoGrua = eliminarMovimientoGrua;

    // Función para refrescar el historial dinámicamente
    async function refrescarHistorialGrua() {
        try {
            const response = await fetch(window.location.href);
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const nuevoContenido = doc.getElementById('contenedor-movimientos-completados');
            const nuevoContador = doc.getElementById('contador-tareas-pendientes');

            if (nuevoContenido) {
                const actual = document.getElementById('contenedor-movimientos-completados');
                actual.innerHTML = nuevoContenido.innerHTML;
                if (window.lucide) lucide.createIcons();
                if (typeof reiniciarPaginacionCompletados === 'function') {
                    reiniciarPaginacionCompletados();
                }
            }

            if (nuevoContador) {
                const contadorActual = document.getElementById('contador-tareas-pendientes');
                if (contadorActual) contadorActual.innerHTML = nuevoContador.innerHTML;
            }
        } catch (error) {
            console.error('Error al refrescar historial:', error);
        }
    }
    window.refrescarHistorialGrua = refrescarHistorialGrua;
</script>
