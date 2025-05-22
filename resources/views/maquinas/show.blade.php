<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <strong>{{ $maquina->nombre }}</strong>,
            {{ $usuario1->name }} @if ($usuario2)
                y {{ $usuario2->name }}
            @endif
        </h2>
    </x-slot>

    <div class="mx-auto px-4 py-6">
        <!-- Grid principal -->
        <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
            <!-- --------------------------------------------------------------- Información de la máquina --------------------------------------------------------------- -->
            @if (strtolower($maquina->tipo) !== 'grua')
                <div class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-1 md:sticky md:top-4">
                    <h3 class="block w-full bg-gray-200 font-bold text-xl text-center break-words p-2 rounded-md">
                        {{ $maquina->codigo }}
                    </h3>

                    <!-- Mostrar los productos en la máquina -->
                    @if ($maquina->productos->isEmpty())
                        <p class="p-4 text-gray-500">No hay productos en esta máquina.</p>
                    @else
                        <ul class="list-none p-2 break-words">
                            @foreach ($maquina->productos->sortBy([['productoBase.diametro', 'asc'], ['peso_stock', 'asc']]) as $producto)
                                <li class="mb-4">
                                    <div class="flex items-center  gap-2 flex-wrap">
                                        <div class="flex flex-col">
                                            <span><strong>Ø</strong> {{ $producto->productoBase->diametro }} mm</span>

                                            @if (strtoupper($producto->productoBase->tipo) === 'BARRA')
                                                <span><strong>L:</strong> {{ $producto->productoBase->longitud }}
                                                    m</span>
                                            @endif
                                        </div>

                                        <div class="flex flex-col items-center">
                                            <button class="bg-gray-200 hover:bg-gray-300 text-black py-2 px-2 rounded"
                                                onclick="confirmarEliminacion('{{ route('productos.consumir', $producto->id) }}')">
                                                ❌
                                            </button>

                                            <form id="formulario-eliminar" action="" method="POST"
                                                style="display: none;">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                        </div>

                                        {{-- @php
                                            $porcentaje =
                                                ($producto->peso_stock / max($producto->peso_inicial, 1)) * 100;
                                        @endphp

                                        @if (strtoupper($producto->productoBase->tipo) === 'ENCARRETADO')
                                            <div id="progreso-container-{{ $producto->id }}"
                                                class="relative w-20 h-20 bg-gray-300 overflow-hidden rounded-lg">
                                                <div id="progreso-barra-{{ $producto->id }}"
                                                    class="absolute bottom-0 w-full"
                                                    style="height: {{ $porcentaje }}%; background-color: green;">
                                                </div>
                                                <span id="progreso-texto-{{ $producto->id }}"
                                                    class="absolute top-2 left-2 text-white text-xs font-semibold">
                                                    {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                                </span>
                                            </div>
                                        @elseif (strtoupper($producto->productoBase->tipo) === 'BARRA')
                                            <div id="progreso-container-{{ $producto->id }}"
                                                class="relative w-60 h-10 bg-gray-300 overflow-hidden rounded-lg">
                                                <div class="absolute right-0 h-full"
                                                    style="width: {{ $porcentaje }}%; background-color: green;">
                                                </div>
                                                <span id="progreso-texto-{{ $producto->id }}"
                                                    class="absolute top-2 left-2 text-white text-xs font-semibold">
                                                    {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                                </span>
                                            </div>
                                        @endif --}}

                                        <!-- Botón solicitar recambio -->
                                        <div class="mt-2 w-full">
                                            <form method="POST" action="{{ route('movimientos.crear') }}">
                                                @csrf
                                                <input type="hidden" name="tipo" value="recarga_materia_prima">
                                                <input type="hidden" name="maquina_id" value="{{ $maquina->id }}">
                                                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                                <input type="hidden" name="descripcion"
                                                    value="Recarga solicitada para máquina {{ $maquina->nombre }} (Ø{{ $producto->productoBase->diametro }} {{ strtolower($producto->productoBase->tipo) }}, {{ $producto->peso_stock }} kg)">
                                                <button class="btn btn-warning">Solicitar recambio</button>
                                            </form>
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="flex flex-col gap-2 p-4">
                        <!-- Botón Reportar Incidencia -->
                        <button onclick="document.getElementById('modalIncidencia').classList.remove('hidden')"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                            🚨 Reportar Incidencia
                        </button>

                        <!-- Botón Realizar Chequeo de Máquina -->
                        <button onclick="document.getElementById('modalCheckeo').classList.remove('hidden')"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                            🛠️ Chequeo de Máquina
                        </button>
                    </div>
                </div>
            @endif

            <!-- --------------------------------------------------------------- Planificación para la máquina agrupada por etiquetas --------------------------------------------------------------- -->
            <div class="bg-white border p-2 shadow-md w-full rounded-lg sm:col-span-5">
                @if (stripos($maquina->tipo, 'grua') !== false)
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 mt-4">
                        <h3 class="text-lg font-bold text-yellow-800 mb-2">📦 Movimientos Pendientes</h3>

                        @if ($movimientosPendientes->isEmpty())
                            <p class="text-gray-600">No hay movimientos pendientes actualmente.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($movimientosPendientes as $mov)
                                    <li class="p-3 border border-yellow-200 rounded shadow-sm bg-white">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                            <div class="flex-1">
                                                <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                                                <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                                                <p><strong>Solicitado por:</strong>
                                                    {{ optional($mov->solicitadoPor)->name ?? 'N/A' }}</p>
                                                <p><strong>Fecha:</strong> {{ $mov->created_at->format('d/m/Y H:i') }}
                                                </p>
                                            </div>

                                            <div class="flex-shrink-0">
                                                <button
                                                    onclick='abrirModalMovimiento(
                                                        @json($mov->id),
                                                        @json($mov->tipo),
                                                        "", // productoId se escanea luego
                                                        @json($mov->maquina_destino),
                                                        @json($mov->producto_base_id),
                                                        @json($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? [])
                                                    )'
                                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                                    ✅ Ejecutar
                                                </button>

                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <!-- Modal para ejecutar movimiento -->
                    <div id="modalMovimiento"
                        class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
                        <div class="bg-white p-6 rounded shadow-lg w-full max-w-xl">
                            <h2 class="text-xl font-bold mb-4 text-center">📦 Registrar Movimiento a Máquina</h2>

                            <!-- Ubicaciones sugeridas -->
                            <div id="ubicaciones-actuales" class="mb-4 hidden">
                                <label class="font-bold block mb-2">Ubicaciones con producto disponible</label>
                                <ul id="ubicaciones-lista" class="list-disc list-inside text-gray-700 text-sm"></ul>
                            </div>

                            <!-- Formulario -->
                            <form method="POST" action="{{ route('movimientos.store') }}"
                                id="form-ejecutar-movimiento">
                                @csrf
                                <input type="hidden" name="tipo" id="modal_tipo">
                                <input type="hidden" name="producto_base_id" id="modal_producto_base_id">
                                <input type="hidden" name="maquina_destino" id="modal_maquina_id">

                                <!-- Input QR producto -->
                                <div class="mb-4">
                                    <label for="producto_id" class="font-bold">Escanea el QR del producto que vas a
                                        mover</label>
                                    <input type="text" name="producto_id" id="modal_producto_id"
                                        class="form-control mt-1 border rounded px-3 py-2 w-full"
                                        placeholder="QR producto..." autofocus required>
                                </div>

                                <div class="flex justify-end gap-4 mt-6">
                                    <button type="button" onclick="cerrarModalMovimiento()"
                                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancelar</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Registrar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Scripts -->
                    <script>
                        function abrirModalMovimiento(id, tipo, productoId, maquinaId, productoBaseId, ubicacionesSugeridas) {
                            document.getElementById('modal_tipo').value = tipo;
                            document.getElementById('modal_maquina_id').value = maquinaId;
                            document.getElementById('modal_producto_id').value = ''; // limpiar QR anterior
                            document.getElementById('modal_producto_base_id').value = productoBaseId;

                            // Pintar ubicaciones sugeridas
                            const lista = document.getElementById('ubicaciones-lista');
                            lista.innerHTML = '';

                            if (ubicacionesSugeridas && ubicacionesSugeridas.length > 0) {
                                document.getElementById('ubicaciones-actuales').classList.remove('hidden');
                                ubicacionesSugeridas.forEach(u => {
                                    const li = document.createElement('li');
                                    li.textContent = u.nombre;
                                    lista.appendChild(li);
                                });
                            } else {
                                document.getElementById('ubicaciones-actuales').classList.add('hidden');
                            }

                            document.getElementById('modalMovimiento').classList.remove('hidden');
                            document.getElementById('modalMovimiento').classList.add('flex');
                        }

                        function cerrarModalMovimiento() {
                            document.getElementById('modalMovimiento').classList.add('hidden');
                            document.getElementById('modalMovimiento').classList.remove('flex');
                        }
                    </script>

                @endif



                @php
                    $idsReempaquetados = collect($elementosReempaquetados ?? []);

                    function debeSerExcluido($elemento)
                    {
                        $tienePaqueteDirecto = !is_null($elemento->paquete_id);
                        $estaFabricado = strtolower($elemento->estado) === 'fabricado';
                        return $tienePaqueteDirecto && $estaFabricado;
                    }

                    $elementosFiltrados = $elementosMaquina->filter(function ($elemento) use ($maquina) {
                        if (stripos($maquina->tipo, 'ensambladora') !== false) {
                            return $elemento->maquina_id_2 == $maquina->id && $elemento->maquina_id != $maquina->id;
                        }
                        if (stripos($maquina->nombre, 'soldadora') !== false) {
                            return !debeSerExcluido($elemento) &&
                                $elemento->maquina_id_3 == $maquina->id &&
                                strtolower(optional($elemento->etiquetaRelacion)->estado ?? '') === 'soldando';
                        }
                        return !debeSerExcluido($elemento);
                    });

                    $elementosAgrupados = $elementosFiltrados
                        ->groupBy('etiqueta_sub_id')
                        ->sortBy(fn($grupo) => optional($grupo->first()->planilla)->fecha_estimada_entrega);

                    $elementosAgrupadosScript = $elementosAgrupados
                        ->map(function ($grupo) {
                            return [
                                'etiqueta' => $grupo->first()->etiquetaRelacion ?? null,
                                'planilla' => $grupo->first()->planilla ?? null,
                                'elementos' => $grupo
                                    ->map(function ($elemento) {
                                        return [
                                            'id' => $elemento->id,
                                            'dimensiones' => $elemento->dimensiones,
                                            'estado' => $elemento->estado,
                                            'peso' => $elemento->peso_kg,
                                            'diametro' => $elemento->diametro_mm,
                                            'longitud' => $elemento->longitud_cm,
                                            'barras' => $elemento->barras,
                                            'figura' => $elemento->figura,
                                        ];
                                    })
                                    ->values(),
                            ];
                        })
                        ->values();
                @endphp

                @forelse ($elementosAgrupados as $etiquetaSubId => $elementos)
                    @php
                        $firstElement = $elementos->first();
                        $etiqueta =
                            $firstElement->etiquetaRelacion ??
                            \App\Models\Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();
                        $planilla = $firstElement->planilla ?? null;
                        $tieneElementosEnOtrasMaquinas =
                            isset($otrosElementos[$etiqueta?->id]) && $otrosElementos[$etiqueta?->id]->isNotEmpty();
                    @endphp
                    <div id="etiqueta-{{ $etiqueta->etiqueta_sub_id }}"
                        style="background-color: #fe7f09; border: 1px solid black;"
                        class="proceso boder shadow-xl mt-4">
                        <!-- Asegúrate de incluir Lucide o FontAwesome si usas uno de esos -->
                        <div class="relative">
                            <button {{-- onclick="generateAndPrintQR('{{ $etiqueta->etiqueta_sub_id }}', '{{ $etiqueta->planilla->codigo_limpio }}', 'ETIQUETA')" --}}
                                onclick="imprimirEtiqueta('{{ $etiqueta->etiqueta_sub_id }}')"
                                class="absolute top-2 right-2 text-blue-800 hover:text-blue-900 no-print">
                                <!-- Icono QR de Lucide -->
                                🖨️ <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h4v4H3V3zm14 0h4v4h-4V3zM3 17h4v4H3v-4zm14 4v-4h-4v2a2 2 0 002 2h2zm-6-4h2v2h-2v-2zm4-4h4v4h-4v-4zm0-6h4v4h-4V7zM7 7h4v4H7V7z" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-2">
                            <h2 class="text-lg font-semibold text-gray-900">
                                <span>{{ $planilla->obra->obra }}</span> -
                                <span>{{ $planilla->cliente->empresa }}</span><br>
                                <span> {{ optional($planilla)->codigo_limpio }}
                                </span> - S:{{ $planilla->seccion }}
                            </h2>
                            <h3 class="text-lg font-semibold text-gray-900">
                                <span class="text-blue-700">
                                    {{ $etiqueta->etiqueta_sub_id ?? 'N/A' }} </span>
                                {{ $etiqueta->nombre ?? 'Sin nombre' }} -

                                <span>Cal:B500SD</span>

                                - {{ $etiqueta->peso_kg ?? 'N/A' }}
                            </h3>
                            <!-- Contenedor oculto para generar el QR -->
                            <div id="qrContainer-{{ $etiqueta->id ?? 'N/A' }}" style="display: none;"></div>
                            <div class="p-2 no-print">
                                <p>
                                    <strong>Estado:</strong>
                                    <span
                                        id="estado-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id ?? 'N/A') }}">
                                        {{ $etiqueta->estado ?? 'N/A' }}
                                    </span>
                                    <strong>Fecha Inicio:</strong>
                                    <span
                                        id="inicio-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id ?? 'N/A') }}">
                                        {{ $maquina->tipo === 'ensambladora' ? $etiqueta->fecha_inicio_ensamblado ?? 'No asignada' : $etiqueta->fecha_inicio ?? 'No asignada' }}
                                    </span>
                                    <strong>Fecha Finalización:</strong>
                                    <span id="final-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id ?? 'N/A') }}">
                                        {{ $maquina->tipo === 'ensambladora' ? $etiqueta->fecha_finalizacion_ensamblado ?? 'No asignada' : $etiqueta->fecha_finalizacion ?? 'No asignada' }}
                                    </span>

                                </p>

                            </div>
                            <!-- 🔹 Elementos de la misma etiqueta en otras máquinas -->
                            @if (isset($otrosElementos[$etiqueta?->id]) && $otrosElementos[$etiqueta?->id]->isNotEmpty())
                                <h4 class="font-semibold text-red-700 p-2">⚠️ Hay elementos en otras
                                    máquinas</h4>
                            @endif
                        </div>
                        <div>
                            <!-- Contenedor para el canvas -->
                            <div id="canvas-container" style="width: 100%; border-top: 1px solid black;">
                                <canvas id="canvas-etiqueta-{{ $etiqueta->id ?? 'N/A' }}"></canvas>
                            </div>
                            <!-- Contenedor para el canvas de impresión -->
                            <div id="canvas-container-print"
                                style="width: 100%; border-top: 1px solid black; visibility: hidden; height: 0;">
                                <canvas id="canvas-imprimir-etiqueta-{{ $etiqueta->etiqueta_sub_id }}"></canvas>

                            </div>


                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-4 text-gray-600">
                        No hay etiquetas disponibles para esta máquina.
                    </div>
                @endforelse
            </div>
            <!-- Modal para cambio de máquina -->
            <div id="modalCambioMaquina"
                class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4">Motivo del cambio de máquina</h2>
                    <form id="formCambioMaquina" onsubmit="enviarCambioMaquina(event)">
                        <input type="hidden" id="cambio-elemento-id" name="elemento_id">
                        {{-- Motivo del cambio --}}
                        <label for="motivoSelect" class="block font-semibold mb-1">Motivo del cambio:</label>
                        <select id="motivoSelect" name="motivo" onchange="mostrarCampoOtro()"
                            class="w-full border p-2 rounded mb-4" required>
                            <option value="" disabled selected>Selecciona un motivo</option>
                            <option value="Fallo técnico en máquina actual">Fallo técnico en máquina actual
                            </option>
                            <option value="Máquina saturada o con mucha carga">Máquina saturada o con mucha
                                carga
                            </option>
                            <option value="Cambio de prioridad en producción">Cambio de prioridad en producción
                            </option>
                            <option value="Otros">Otros</option>
                        </select>
                        <div id="campoOtroMotivo" class="hidden mb-4">
                            <label for="motivoTexto" class="block font-semibold mb-1">Especifica otro
                                motivo:</label>
                            <input type="text" id="motivoTexto" class="w-full border p-2 rounded"
                                placeholder="Escribe tu motivo">
                        </div>
                        {{-- Selección de máquina destino --}}
                        <label for="maquinaDestino" class="block font-semibold mb-1">Máquina destino:</label>
                        <select id="maquinaDestino" name="maquina_id" class="w-full border p-2 rounded mb-4"
                            required>
                            <option value="" disabled selected>Selecciona una máquina</option>
                            @php $maquinaActualId = $maquina->id; @endphp

                            @foreach ($maquinas as $m)
                                @if (in_array($m->tipo, ['cortadora_dobladora', 'estribadora']) && $m->id !== $maquina->id)
                                    <option value="{{ $m->id }}">{{ $m->nombre }}
                                        ({{ $m->tipo }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div class="mt-4 text-right">
                            <button type="button" onclick="cerrarModalCambio()"
                                class="mr-2 px-4 py-1 bg-gray-300 rounded">Cancelar</button>
                            <button type="submit"
                                class="px-4 py-1 bg-green-600 text-white rounded hover:bg-green-700">Enviar</button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Modal para Dividir Elemento -->
            <div id="modalDividirElemento"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">✂️ Dividir Elemento</h2>

                    <form id="formDividirElemento" method="POST">
                        @csrf
                        <input type="hidden" name="elemento_id" id="dividir_elemento_id">

                        <label for="num_nuevos" class="block text-sm font-medium text-gray-700 mb-1">
                            ¿Cuántos nuevos grupos de elementos quieres crear?
                        </label>
                        <input type="number" name="num_nuevos" id="num_nuevos"
                            class="w-full border rounded p-2 mb-4" min="1" placeholder="Ej: 2">

                        <div class="flex justify-end mt-4">
                            <button type="button"
                                onclick="document.getElementById('modalDividirElemento').classList.add('hidden')"
                                class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                                Cancelar
                            </button>
                            <button type="button" onclick="enviarDivision()"
                                class="px-4 py-2 bg-purple-600 text-white rounded">
                                Dividir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- --------------------------------------------------------------- GRID PARA OTROS --------------------------------------------------------------- -->
            @if (strtolower($maquina->tipo) !== 'grua')
                <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
                    <div class="flex flex-col gap-4">
                        <!-- Input de lectura de QR -->
                        <div class="bg-white border p-2 shadow-md rounded-lg self-start sm:col-span-1 md:sticky">
                            <h3 class="font-bold text-xl">PROCESO ETIQUETA</h3>
                            <input type="text" id="procesoEtiqueta" class="w-full border p-2 rounded"
                                placeholder="Escanea un QR..." autofocus>
                            <div id="maquina-info" data-maquina-id="{{ $maquina->id }}"></div>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const input = document.getElementById("procesoEtiqueta");
                                if (input) {
                                    input.focus();
                                }
                            });
                        </script>

                        <!-- Sistema de inputs para crear paquetes -->
                        <div class="bg-gray-100 border p-2 mb-2 shadow-md rounded-lg">
                            <h3 class="font-bold text-xl">Crear Paquete</h3>
                            <div class="mb-2">
                                <label for="itemType" class="block text-gray-700 font-semibold">Selecciona el
                                    tipo:</label>
                                <select id="itemType" class="border rounded p-2 w-full">
                                    <option value="Etiqueta">Etiqueta</option>
                                    <option value="Elemento">Elemento</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="qrItem" class="block text-gray-700 font-semibold">Escanear
                                    QR:</label>
                                <input type="text" id="qrItem" class="border rounded p-2 w-full"
                                    placeholder="Escanea o ingresa un código QR">
                            </div>

                            <!-- Listado dinámico de items -->
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-700 mb-2">Items agregados:</h4>
                                <ul id="itemsList" class="list-disc pl-6 space-y-2">
                                    <!-- Los items se agregarán aquí dinámicamente -->
                                </ul>
                            </div>

                            <!-- Botón para crear el paquete -->
                            <button id="crearPaqueteBtn"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full">
                                📦 Crear Paquete
                            </button>

                        </div>
                    </div>
                    <!-- ---------------------------------------- ELIMINAR PAQUETE ------------------------------- -->
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <label for="paquete_id" class="block text-gray-700 font-semibold mb-2">
                            ID del Paquete a Eliminar:
                        </label>
                        <input type="number" name="paquete_id" id="paquete_id" required
                            class="w-full border p-2 rounded mb-2" placeholder="Ingrese ID del paquete">
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md mt-2">
                            🗑️ Eliminar Paquete
                        </button>
                    </form>

                    <script>
                        document.getElementById('deleteForm').addEventListener('submit', function(event) {
                            event.preventDefault(); // Evita el envío inmediato

                            const paqueteId = document.getElementById('paquete_id').value;

                            if (!paqueteId) {
                                Swal.fire({
                                    icon: "warning",
                                    title: "Campo vacío",
                                    text: "Por favor, ingrese un ID válido.",
                                    confirmButtonColor: "#3085d6",
                                });
                                return;
                            }

                            Swal.fire({
                                title: "¿Estás seguro?",
                                text: "Esta acción no se puede deshacer.",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#d33",
                                cancelButtonColor: "#3085d6",
                                confirmButtonText: "Sí, eliminar",
                                cancelButtonText: "Cancelar"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    this.action = "/paquetes/" + paqueteId; // Modifica la acción con el ID
                                    this.submit(); // Envía el formulario
                                }
                            });
                        });
                    </script>

                </div>
            @endif
            <!-- Modal Reportar Incidencia (Oculto por defecto) -->
            <div id="modalIncidencia"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">

                    <!-- Formulario -->
                    <form method="POST" action="{{ route('alertas.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="mensaje" class="block text-sm font-semibold">Mensaje:</label>
                            <textarea id="mensaje" name="mensaje" rows="3"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" required>{{ old('mensaje') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="categoria" class="block text-sm font-semibold">Destinatarios
                                particulares</label>
                            <select id="categoria" name="categoria" class="w-full border rounded-lg p-2">
                                <option value="">-- Seleccionar una Categoría --</option>
                                <option value="administracion">Administración</option>
                                <option value="mecanico">Mecánico</option>
                                <option value="programador">Programador</option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-2">
                            <button type="button"
                                onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                                class="bg-gray-400 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal Chequeo de Máquina (Oculto por defecto) -->
            <div id="modalCheckeo"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">🛠️ Chequeo de Máquina</h2>

                    <form id="formCheckeo">
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="limpieza">
                                🔹 Máquina limpia y sin residuos
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="herramientas">
                                🔹 Herramientas en su ubicación correcta
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="lubricacion">
                                🔹 Lubricación y mantenimiento básico realizado
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="seguridad">
                                🔹 Elementos de seguridad en buen estado
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="registro">
                                🔹 Registro de incidencias actualizado
                            </label>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end mt-4">
                            <button type="button"
                                onclick="document.getElementById('modalCheckeo').classList.add('hidden')"
                                class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                                Guardar Chequeo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        //--------------------------------------------------------------------------------------------------------

        function abrirModalCambioElemento(elementoId) {
            document.getElementById('modalCambioMaquina').classList.remove('hidden');
            document.getElementById('cambio-elemento-id').value = elementoId;
        }



        function cerrarModalCambio() {
            document.getElementById('modalCambioMaquina').classList.add('hidden');

            // Limpiar correctamente los campos
            document.getElementById('motivoSelect').value = '';
            document.getElementById('motivoTexto').value = '';
            document.getElementById('maquinaDestino').value = '';
            document.getElementById('campoOtroMotivo').classList.add('hidden');
        }


        function mostrarCampoOtro() {
            const select = document.getElementById('motivoSelect');
            const campoOtro = document.getElementById('campoOtroMotivo');

            if (select.value === 'Otros') {
                campoOtro.classList.remove('hidden');
            } else {
                campoOtro.classList.add('hidden');
            }
        }

        function enviarCambioMaquina(event) {
            event.preventDefault();

            const elementoId = document.getElementById('cambio-elemento-id').value;
            let motivo = document.getElementById('motivoSelect').value;
            if (motivo === 'Otros') {
                motivo = document.getElementById('motivoTexto').value.trim();
            }
            const maquinaId = document.getElementById('maquinaDestino').value;
            // Puedes ajustar la URL y los headers si usas Axios
            fetch(`/elementos/${elementoId}/solicitar-cambio-maquina`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        motivo,
                        maquina_id: maquinaId
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || 'Solicitud enviada');
                    cerrarModalCambio();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un problema al enviar la solicitud.');
                });
        }
        //--------------------------------------------------------------------------------------------------------
        function confirmarEliminacion(actionUrl) {
            Swal.fire({
                title: '¿Quieres deshecharlo?',
                text: "¡No podrás revertir esta acción!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Color del botón de confirmar
                cancelButtonColor: '#3085d6', // Color del botón de cancelar
                confirmButtonText: 'Sí, deshechar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formulario = document.getElementById('formulario-eliminar');
                    formulario.action = actionUrl; // Asigna la ruta de consumir
                    formulario.submit(); // Envía el formulario
                }
            });
        }

        const maquinaId = @json($maquina->id);
        const ubicacionId = @json(optional($ubicacion)->id); // Esto puede ser null si no se encontró

        window.etiquetasData =
            @json($etiquetasData); // Ej.: [{ codigo: "3718", elementos: [27906,27907,...], pesoTotal: 155.55 }, ...]
        window.pesosElementos = @json($pesosElementos); // Ej.: { "27906": "77.81", "27907": "3.87", ... }
        //--------------------------------------------------------------------------------------------------------
        // console.log("Datos precargados de etiquetas:", window.etiquetasData);
        // console.log("Pesos precargados de elementos:", window.pesosElementos);
    </script>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/maquinaJS/trabajoEtiqueta.js') }}"></script>
    <script src="{{ asset('js/imprimirQrS.js') }}"></script>
    <script>
        window.elementosAgrupadosScript = @json($elementosAgrupadosScript);
    </script>
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
    <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}" defer></script>

    <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
    {{-- Al final del archivo Blade --}}

    <script>
        function imprimirEtiqueta(etiquetaSubId) {
            const canvas = document.getElementById(`canvas-imprimir-etiqueta-${etiquetaSubId}`);
            if (!canvas) {
                alert("No se encontró el canvas de impresión limpio.");
                return;
            }

            // Aumentamos tamaño del canvas para impresión (doble escala)
            const scaleFactor = 2;
            const tempCanvas = document.createElement("canvas");
            tempCanvas.width = canvas.width * scaleFactor;
            tempCanvas.height = canvas.height * scaleFactor;
            const ctx = tempCanvas.getContext("2d");
            ctx.scale(scaleFactor, scaleFactor);
            ctx.drawImage(canvas, 0, 0);

            const canvasImg = tempCanvas.toDataURL("image/png");

            // Clonar contenedor de etiqueta
            const contenedor = document.getElementById(`etiqueta-${etiquetaSubId}`);
            const clone = contenedor.cloneNode(true);
            clone.classList.add("etiqueta-print");

            // Quitar botones u otros elementos no imprimibles
            clone.querySelectorAll(".no-print").forEach(el => el.remove());

            // Reemplazar canvas por imagen generada
            const img = new Image();
            img.src = canvasImg;
            img.style.width = "100%";
            img.style.height = "auto";
            const canvasContainer = clone.querySelector("canvas").parentNode;
            canvasContainer.innerHTML = "";
            canvasContainer.appendChild(img);

            // Crear QR en div temporal
            const tempQR = document.createElement("div");
            document.body.appendChild(tempQR);
            const qrSize = 140;

            new QRCode(tempQR, {
                text: etiquetaSubId.toString(),
                width: qrSize,
                height: qrSize
            });

            setTimeout(() => {
                const qrImg = tempQR.querySelector("img");
                if (qrImg) {
                    const qrWrapper = document.createElement("div");
                    qrWrapper.className = "qr-box";
                    qrWrapper.appendChild(qrImg);
                    clone.insertBefore(qrWrapper, clone.firstChild);
                }

                const style = `
<style>
    @media print {
        body {
            margin: 0;
            padding: 0;
        }
    }

    .etiqueta-print {
        width: 16cm;
        margin: 2cm auto;
        padding: 1.5cm;
        border: 2px solid #000;
        font-family: Arial, sans-serif;
        font-size: 15px;
        color: #000;
        box-sizing: border-box;
    }

    .etiqueta-print > * {
        padding: 6px;
        box-sizing: border-box;
    }

    .etiqueta-print h2 {
        font-size: 22px;
        margin-bottom: 8px;
    }

    .etiqueta-print h3 {
        font-size: 18px;
        margin-bottom: 6px;
    }

    .etiqueta-print p,
    .etiqueta-print span,
    .etiqueta-print strong {
        font-size: 15px;
    }

    .etiqueta-print img:not(.qr-print) {
        width: 100%;
        height: auto;
        display: block;
        margin-top: 14px;
    }

    .qr-box {
        float: right;
        margin-left: 14px;
        margin-bottom: 14px;
        border: 2px solid #000;
        padding: 6px;
    }

    .qr-box img {
        width: 140px;
        height: 140px;
    }

    .proceso {
        box-shadow: none;
        border: none;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }
</style>
`;

                const printWindow = window.open("", "_blank");
                printWindow.document.open();
                printWindow.document.write(`
<html>
<head>
    <title>Etiqueta ${etiquetaSubId}</title>
    ${style}
</head>
<body>
    ${clone.outerHTML}
    <script>
        window.print();
        setTimeout(() => window.close(), 500);
    <\/script>
</body>
</html>
`);
                printWindow.document.close();
                tempQR.remove();
            }, 300);
        }
    </script>


</x-app-layout>
