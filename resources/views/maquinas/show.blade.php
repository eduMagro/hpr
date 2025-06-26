<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-2 sm:mb-0">
                <strong>{{ $maquina->nombre }}</strong>,
                {{ $usuario1->name }}
                @if ($usuario2)
                    y {{ $usuario2->name }}
                @endif
            </h2>

            @if ($turnoHoy)
                <form method="POST" action="{{ route('turno.cambiarMaquina') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="asignacion_id" value="{{ $turnoHoy->id }}">

                    <select name="nueva_maquina_id" class="border rounded px-2 py-1 text-sm">
                        @foreach ($maquinas as $m)
                            <option value="{{ $m->id }}" {{ $m->id == $turnoHoy->maquina_id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                        Cambiar máquina
                    </button>
                </form>
            @endif
        </div>
    </x-slot>
    <div class="w-full sm:px-4 py-6">

        <!-- Grid principal -->
        <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
            <!-- --------------------------------------------------------------- Información de la máquina --------------------------------------------------------------- -->
            {{-- SI NO ES GRUA --}}
            @if (strtolower($maquina->tipo) !== 'grua')

                <div class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-1 md:sticky md:top-4">
                    <!-- Mostrar los productos en la máquina -->

                    <ul class="list-none p-1 break-words">
                        @foreach ($productosBaseCompatibles as $productoBase)
                            @php
                                $productoExistente = $maquina->productos->firstWhere(
                                    'producto_base_id',
                                    $productoBase->id,
                                );
                                // Omitir si está consumido
                                if ($productoExistente && $productoExistente->estado === 'consumido') {
                                    continue;
                                }
                                $pesoStock = $productoExistente->peso_stock ?? 0;
                                $pesoInicial = $productoExistente->peso_inicial ?? 0;
                                $porcentaje = $pesoInicial > 0 ? ($pesoStock / $pesoInicial) * 100 : 0;
                            @endphp

                            <li class="mb-1">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="text-sm">
                                        <span><strong>Ø</strong> {{ $productoBase->diametro }} mm</span>
                                        @if (strtoupper($productoBase->tipo) === 'BARRA')
                                            <span class="ml-2"><strong>L:</strong> {{ $productoBase->longitud }}
                                                m</span>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('movimientos.crear') }}">
                                        @csrf
                                        <input type="hidden" name="tipo" value="recarga_materia_prima">
                                        <input type="hidden" name="maquina_id" value="{{ $maquina->id }}">
                                        <input type="hidden" name="producto_base_id" value="{{ $productoBase->id }}">
                                        @if ($productoExistente)
                                            <input type="hidden" name="producto_id"
                                                value="{{ $productoExistente->id }}">
                                        @endif
                                        <input type="hidden" name="descripcion"
                                            value="Recarga solicitada para máquina {{ $maquina->nombre }} (Ø{{ $productoBase->diametro }} {{ strtolower($productoBase->tipo) }}, {{ $pesoStock }} kg)">
                                        <button
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium px-3 py-1 rounded transition">
                                            Solicitar
                                        </button>


                                    </form>
                                </div>

                                @if ($productoExistente)
                                    <div id="progreso-container-{{ $productoExistente->id }}"
                                        class="relative mt-2 {{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'w-20 h-20' : 'w-full max-w-sm h-4' }} bg-gray-300 overflow-hidden rounded-lg">
                                        <div class="absolute bottom-0 w-full"
                                            style="{{ strtoupper($productoBase->tipo) === 'ENCARRETADO' ? 'height' : 'width' }}: {{ $porcentaje }}%; background-color: green;">
                                        </div>
                                        <span
                                            class="absolute inset-0 flex items-center justify-center text-white text-xs font-semibold">
                                            {{ $pesoStock }} / {{ $pesoInicial }} kg
                                        </span>
                                    </div>
                                @endif

                                <hr class="my-1">
                            </li>
                        @endforeach

                    </ul>


                    <div class="flex flex-col gap-2 p-4">
                        @if ($elementosAgrupados->isNotEmpty())
                            <button onclick='imprimirEtiquetasLote(@json($elementosAgrupados->keys()->values()))'
                                class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded shadow">
                                🖨️
                            </button>
                        @endif
                        <!-- Botón Reportar Incidencia -->
                        <button onclick="document.getElementById('modalIncidencia').classList.remove('hidden')"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                            🚨
                        </button>
                        <!-- Botón Realizar Chequeo de Máquina -->
                        <button onclick="document.getElementById('modalCheckeo').classList.remove('hidden')"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                            🛠️
                        </button>
                    </div>
                </div>
                <div class="bg-white border p-2 shadow-md w-full rounded-lg sm:col-span-5">

                    @forelse ($elementosAgrupados as $etiquetaSubId => $elementos)
                        @php
                            $firstElement = $elementos->first();
                            $etiqueta =
                                $firstElement->etiquetaRelacion ??
                                Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();
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
                                        <span
                                            id="final-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id ?? 'N/A') }}">
                                            {{ $maquina->tipo === 'ensambladora' ? $etiqueta->fecha_finalizacion_ensamblado ?? 'No asignada' : $etiqueta->fecha_finalizacion ?? 'No asignada' }}
                                        </span>

                                    </p>

                                </div>
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

                <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
                    <div class="flex flex-col gap-4">
                        <!-- Input de lectura de QR -->


                        <input type="text" id="procesoEtiqueta" placeholder="ESCANEA ETIQUETA" autofocus
                            class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style="height:2cm; padding:0.75rem 1rem; font-size:1.5rem;" />

                        <div id="maquina-info" data-maquina-id="{{ $maquina->id }}"></div>


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

                                <input type="text" id="qrItem"
                                    class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    style="height:1cm; padding:0.75rem 1rem; font-size:1rem;"
                                    placeholder="AÑADIR ETIQUETA AL CARRO">
                            </div>

                            <!-- Listado dinámico de etiquetas -->
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-700 mb-2">Etiquetas en el carro:</h4>
                                <ul id="itemsList" class="list-disc pl-6 space-y-2">
                                    <!-- Se rellenan dinámicamente -->
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
            <!-- --------------------------------------------------------------- Planificación para la máquina agrupada por etiquetas --------------------------------------------------------------- -->
            {{-- SI ES GRUA --}}
            @if (stripos($maquina->tipo, 'grua') !== false)
                <div class="w-full sm:col-span-8">
                    {{-- 🟡 PENDIENTES --}}
                    <div class="mb-4 flex justify-center">
                        <button onclick="abrirModalMovimientoLibre()"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                            ➕ Crear Movimiento Libre
                        </button>
                    </div>

                    <div class="bg-red-200 border border-red-400 rounded-lg p-4 mt-4">
                        <h3 class="text-base sm:text-lg font-bold text-red-800 mb-3">📦 Movimientos Pendientes</h3>
                        @if ($movimientosPendientes->isEmpty())
                            <p class="text-gray-600 text-sm">No hay movimientos pendientes actualmente.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($movimientosPendientes as $mov)
                                    <li class="p-3 border border-red-200 rounded shadow-sm bg-white text-sm">
                                        <div class="flex flex-col gap-2">
                                            <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                                            <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                                            <p><strong>Solicitado por:</strong>
                                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                                            <p><strong>Fecha:</strong> {{ $mov->created_at->format('d/m/Y H:i') }}</p>

                                            @if (strtolower($mov->tipo) === 'bajada de paquete')
                                                @php
                                                    $datosMovimiento = [
                                                        'id' => $mov->id,
                                                        'paquete_id' => $mov->paquete_id,
                                                        'ubicacion_origen' => $mov->ubicacion_origen,
                                                        'descripcion' => $mov->descripcion,
                                                    ];
                                                @endphp

                                                <button type="button"
                                                    onclick='abrirModalBajadaPaquete(@json($datosMovimiento))'
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full sm:w-auto">
                                                    📦 Ejecutar bajada
                                                </button>
                                            @endif

                                            @if (strtolower($mov->tipo) === 'recarga materia prima')
                                                <button
                                                    onclick='abrirModalRecargaMateriaPrima(
        @json($mov->id),
        @json($mov->tipo),
        @json(optional($mov->producto)->codigo), // <-- aquí el código, no el ID
        @json($mov->maquina_destino),
        @json($mov->producto_base_id),
        @json($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? []),
        @json(optional($mov->maquinaDestino)->nombre ?? 'Máquina desconocida'),
        @json(optional($mov->productoBase)->tipo ?? ''),
        @json(optional($mov->productoBase)->diametro ?? ''),
        @json(optional($mov->productoBase)->longitud ?? '')
    )'
                                                    class="bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto">
                                                    ✅ Ejecutar recarga
                                                </button>
                                            @endif
                                            @if (strtolower($mov->tipo) === 'descarga materia prima' && $mov->pedido)
                                                <button
                                                    onclick='abrirModalPedidoDesdeMovimiento(@json($mov))'
                                                    style="background-color: orange; color: white;"
                                                    class="text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto border border-black">
                                                    🏗️ Ver pedido
                                                </button>
                                            @endif

                                        </div>
                                    </li>
                                @endforeach

                            </ul>
                        @endif
                    </div>

                    {{-- 🟢 COMPLETADOS --}}
                    <div class="bg-green-200 border border-green-300 rounded-lg p-4 mt-6"
                        id="contenedor-movimientos-completados">
                        <h3 class="text-base sm:text-lg font-bold text-green-800 mb-3">✅ Movimientos Completados
                            Recientemente</h3>

                        @if ($movimientosCompletados->isEmpty())
                            <p class="text-gray-600 text-sm">No hay movimientos completados.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($movimientosCompletados as $mov)
                                    <li
                                        class="p-3 border border-green-200 rounded shadow-sm bg-white text-sm movimiento-completado">
                                        <div class="flex flex-col gap-2">
                                            <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                                            <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                                            <p><strong>Solicitado por:</strong>
                                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                                            <p><strong>Ejecutado por:</strong>
                                                {{ optional($mov->ejecutadoPor)->nombre_completo ?? 'N/A' }}</p>
                                            <p><strong>Fecha completado:</strong>
                                                {{ $mov->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        {{-- Botón alineado a la derecha --}}
                                        <div class="flex justify-end mt-2">
                                            <x-tabla.boton-eliminar :action="route('movimientos.destroy', $mov->id)" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <!-- Paginador para completados -->
                    <div class="mt-4 flex justify-center gap-2" id="paginador-movimientos-completados"></div>
                    {{-- 🔄 MODAL MOVIMIENTO LIBRE --}}
                    <div id="modalMovimientoLibre"
                        class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
                        <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0">
                            <form method="POST" action="{{ route('movimientos.store') }}"
                                id="form-movimiento-libre">
                                @csrf
                                <input type="hidden" name="tipo" value="movimiento libre">

                                <!-- Código general (producto o paquete) -->
                                <div class="mb-4">

                                    <x-tabla.input-movil name="codigo_general" id="codigo_general"
                                        label="Escanear Código de Materia Prima o Paquete" placeholder="Escanear QR"
                                        value="{{ old('codigo_general') }}" inputmode="none" autocomplete="off" />
                                </div>

                                <!-- Ubicación destino -->
                                <div class="mb-4">

                                    <x-tabla.input-movil name="ubicacion_destino" placeholder="Escanear ubicación"
                                        value="{{ old('ubicacion_destino') }}" inputmode="none"
                                        autocomplete="off" />
                                </div>

                                <!-- Botones -->
                                <div class="flex justify-end gap-3 mt-6">
                                    <button type="button" onclick="cerrarModalMovimientoLibre()"
                                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const inputCodigo = document.getElementById('codigo_general');
                            const inputUbicacion = document.querySelector('input[name="ubicacion_destino"]');

                            inputCodigo.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter') {
                                    e.preventDefault(); // ⛔ Evita el envío del formulario
                                    inputUbicacion.focus(); // ✅ Salta al siguiente campo
                                }
                            });
                        });
                    </script>

                    {{-- 🔄 MODAL BAJADA PAQUETE --}}
                    <div id="modal-bajada-paquete"
                        class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
                        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                            <h2 class="text-xl font-bold mb-4">Reubicar paquete</h2>

                            <p class="mb-2 text-sm text-gray-700"><strong>Descripción:</strong> <span
                                    id="descripcion_paquete"></span></p>

                            <form method="POST" action="{{ route('movimientos.store') }}">
                                @csrf

                                {{-- <input type="hidden" name="tipo" value="bajada de paquete"> --}}
                                <input type="hidden" name="movimiento_id" id="movimiento_id">
                                <input type="hidden" name="paquete_id" id="paquete_id">
                                <input type="hidden" name="ubicacion_origen" id="ubicacion_origen">
                                <!-- Escanear paquete -->
                                <x-tabla.input-movil id="codigo_general" name="codigo_general"
                                    placeholder="ESCANEA PAQUETE" inputmode="none" autocomplete="off" />
                                <p id="estado_verificacion" class="text-sm mt-1"></p>

                                <!-- Ubicación destino -->
                                <x-tabla.input-movil id="ubicacion_destino" name="ubicacion_destino"
                                    placeholder="ESCANEA UBICACIÓN" required />

                                <!-- Botones -->
                                <div class="flex justify-end gap-3 mt-6">
                                    <button type="button" onclick="cerrarModalBajadaPaquete()"
                                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    {{-- 🔄 MODAL RECARGA MP --}}
                    <div id="modalMovimiento"
                        class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
                        <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md mx-0 sm:mx-0">

                            <h2 class="text-lg sm:text-xl font-bold mb-4 text-center text-gray-800">
                                RECARGAR MÁQUINA
                            </h2>

                            <!-- Información tipo tabla -->
                            <div class="grid grid-cols-2 gap-3 mb-4 text-sm sm:text-base text-gray-700">
                                <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                                    <p class="font-semibold text-gray-600 text-xs sm:text-sm"><i
                                            class="fas fa-industry"></i> {{-- fa-industry --}}
                                    </p>
                                    <p id="maquina-nombre-destino"
                                        class="text-green-700 font-bold text-lg sm:text-xl mt-1"></p>
                                </div>
                                <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                                    <p class="font-semibold text-gray-600 text-xs sm:text-sm">🧱 Tipo</p>
                                    <p id="producto-tipo" class="text-gray-800 font-bold text-xl mt-1"></p>
                                </div>
                                <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                                    <p class="font-semibold text-gray-600 text-xs sm:text-sm">⌀ Diámetro</p>
                                    <p id="producto-diametro" class="text-gray-800 font-bold text-lg sm:text-xl mt-1">
                                    </p>
                                </div>
                                <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                                    <p class="font-semibold text-gray-600 text-xs sm:text-sm">📏 Longitud</p>
                                    <p id="producto-longitud" class="text-gray-800 font-bold text-lg sm:text-xl mt-1">
                                    </p>
                                </div>
                            </div>

                            <!-- Ubicaciones sugeridas -->
                            <div id="ubicaciones-actuales" class="mb-4 hidden">
                                <div class="border-t pt-3">
                                    <label class="font-semibold block mb-2 text-gray-700 text-sm sm:text-base">
                                        📍 Ubicaciones con producto disponible
                                    </label>
                                    <ul id="ubicaciones-lista"
                                        class="list-disc list-inside text-gray-700 text-sm pl-4 space-y-1"></ul>
                                </div>
                            </div>

                            <!-- Formulario -->
                            <form method="POST" action="{{ route('movimientos.store') }}"
                                id="form-ejecutar-movimiento">
                                @csrf
                                <input type="hidden" name="tipo" id="modal_tipo">
                                <input type="hidden" name="producto_base_id" id="modal_producto_base_id">
                                <input type="hidden" name="maquina_destino" id="modal_maquina_id">

                                <x-tabla.input-movil type="text" name="codigo_general" id="modal_producto_id"
                                    placeholder="ESCANEA QR MATERIA PRIMA" inputmode="none" autocomplete="off"
                                    required />

                                <!-- Botones -->
                                <div class="flex justify-end gap-3 mt-6">
                                    <button type="button" onclick="cerrarModalRecargaMateriaPrima()"
                                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
                                </div>

                            </form>
                        </div>
                    </div>
                    {{-- 🔄 MODAL DESCARGA MATERIA PRIMA --}}
                    <div id="modal-ver-pedido"
                        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                        <div class="bg-white w-full max-w-2xl rounded shadow-lg p-6 relative">
                            <button onclick="cerrarModalPedido()"
                                class="absolute top-2 right-2 text-gray-500 hover:text-black text-xl">&times;</button>

                            <h2 class="text-xl font-bold mb-4">Pedido vinculado al movimiento</h2>

                            <div id="contenidoPedido" class="space-y-3 text-sm">
                                {{-- El contenido se rellena por JavaScript --}}
                            </div>
                        </div>
                    </div>
                    {{-- Scripts --}}
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
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
                        });

                        function abrirModalRecargaMateriaPrima(id, tipo, productoCodigo, maquinaId, productoBaseId, ubicacionesSugeridas,
                            maquinaNombre, tipoBase, diametroBase, longitudBase) {

                            document.getElementById('modal_tipo').value = tipo;
                            document.getElementById('modal_maquina_id').value = maquinaId;
                            document.getElementById('modal_producto_id').value = productoCodigo; // ← aquí va el código
                            document.getElementById('modal_producto_base_id').value = productoBaseId;

                            document.getElementById('maquina-nombre-destino').textContent = maquinaNombre;
                            document.getElementById('producto-tipo').textContent = tipoBase;
                            document.getElementById('producto-diametro').textContent = `${diametroBase} mm`;
                            document.getElementById('producto-longitud').textContent = `${longitudBase} mm`;

                            const lista = document.getElementById('ubicaciones-lista');
                            lista.innerHTML = '';

                            if (ubicacionesSugeridas && ubicacionesSugeridas.length > 0) {
                                document.getElementById('ubicaciones-actuales').classList.remove('hidden');
                                ubicacionesSugeridas.forEach(u => {
                                    const li = document.createElement('li');
                                    li.textContent = `${u.nombre} (Código: ${u.codigo})`;

                                    lista.appendChild(li);
                                });
                            } else {
                                document.getElementById('ubicaciones-actuales').classList.add('hidden');
                            }

                            document.getElementById('modalMovimiento').classList.remove('hidden');
                            document.getElementById('modalMovimiento').classList.add('flex');

                            // Focus en el campo QR
                            setTimeout(() => {
                                document.getElementById("modal_producto_id")?.focus();
                            }, 100);
                        }


                        function cerrarModalRecargaMateriaPrima() {
                            document.getElementById('modalMovimiento').classList.add('hidden');
                            document.getElementById('modalMovimiento').classList.remove('flex');
                        }

                        function abrirModalMovimientoLibre() {
                            document.getElementById('modalMovimientoLibre').classList.remove('hidden');
                            document.getElementById('modalMovimientoLibre').classList.add('flex');
                            setTimeout(() => {
                                document.getElementById("codigo_general")?.focus();
                            }, 100);
                        }

                        function cerrarModalMovimientoLibre() {
                            document.getElementById('modalMovimientoLibre').classList.add('hidden');
                            document.getElementById('modalMovimientoLibre').classList.remove('flex');
                        }

                        // Mostrar/ocultar campos según tipo
                        document.addEventListener('DOMContentLoaded', function() {
                            const tipoSelect = document.getElementById('tipo');
                            const productoSection = document.getElementById('producto-section');
                            const paqueteSection = document.getElementById('paquete-section');

                            tipoSelect.addEventListener('change', function() {
                                if (this.value === 'producto') {
                                    productoSection.classList.remove('hidden');
                                    paqueteSection.classList.add('hidden');
                                } else if (this.value === 'paquete') {
                                    productoSection.classList.add('hidden');
                                    paqueteSection.classList.remove('hidden');
                                }
                            });
                        });

                        let paqueteEsperadoId = null;

                        function abrirModalBajadaPaquete(data) {
                            document.getElementById('movimiento_id').value = data.id;
                            document.getElementById('paquete_id').value = data.paquete_id;
                            document.getElementById('ubicacion_origen').value = data.ubicacion_origen;
                            document.getElementById('descripcion_paquete').innerText = data.descripcion;

                            paqueteEsperadoId = data.paquete_id;
                            document.getElementById('codigo_general').value = '';
                            document.getElementById('estado_verificacion').innerText = '';
                            document.getElementById('codigo_general').classList.remove('border-green-500', 'border-red-500');

                            document.getElementById('modal-bajada-paquete').classList.remove('hidden');

                            // Esperar un poco para que se renderice el DOM
                            setTimeout(() => {
                                const input = document.getElementById('codigo_general');
                                if (input) input.focus();
                            }, 100);
                        }

                        function cerrarModalBajadaPaquete() {
                            document.getElementById('modal-bajada-paquete').classList.add('hidden');
                        }

                        function abrirModalPedidoDesdeMovimiento(movimiento) {
                            if (!movimiento || !movimiento.pedido) return;

                            const pedido = movimiento.pedido;

                            const productoBaseId = movimiento.producto_base_id;
                            const producto = movimiento.producto_base;
                            const tipo = producto?.tipo ?? '—';
                            const diametro = producto?.diametro ?? '—';
                            const longitud = producto?.longitud ?? '—';

                            const contenedor = document.getElementById('contenidoPedido');
                            const modal = document.getElementById('modal-ver-pedido');

                            const proveedor = pedido.fabricante_id && pedido.fabricante?.nombre ?
                                pedido.fabricante.nombre :
                                (pedido.distribuidor?.nombre ?? '—');

                            const pesoRedondeado = Math.round(pedido.peso_total || 0) + ' kg';

                            const fechaEntrega = pedido.fecha_entrega ?
                                new Date(pedido.fecha_entrega).toLocaleDateString('es-ES') :
                                '—';

                            contenedor.innerHTML = `
      <p><strong>Proveedor:</strong> ${proveedor}</p>
        <p><strong>Código Pedido:</strong> ${pedido.codigo}</p>
        <p><strong>Estado Pedido:</strong> ${pedido.estado}</p>
        <p><strong>Peso Total:</strong> ${pesoRedondeado}</p>
        <p><strong>Fecha Entrega:</strong> ${fechaEntrega}</p>

        <hr class="my-3" />

        <p><strong>Tipo Producto:</strong> ${tipo}</p>
        <p><strong>Diámetro:</strong> ${diametro} mm</p>
        <p><strong>Longitud:</strong> ${longitud} mm</p>

        <a href="/pedidos/${pedido.id}/recepcion/${productoBaseId}"
            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow inline-block mt-4">
            Ir a recepcionarlo
        </a>
    `;

                            modal.classList.remove('hidden');
                        }



                        function cerrarModalPedido() {
                            document.getElementById('modal-ver-pedido').classList.add('hidden');
                        }
                    </script>
                </div>
            @endif

            <!-- Modal para Cambiar Estado de la Máquina -->
            <div id="modalIncidencia"
                class="hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-50 flex items-center justify-center">
                <div
                    class="bg-white w-full max-w-md mx-auto rounded-xl shadow-lg overflow-hidden transform transition-all">
                    <form method="POST" action="{{ route('maquinas.cambiarEstado', $maquina->id) }}"
                        class="p-6 space-y-4">
                        @csrf

                        <!-- Título -->
                        <h2 class="text-xl font-bold text-gray-800 text-center">Cambiar estado de la máquina</h2>

                        <!-- Selección de estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Selecciona el
                                nuevo estado:</label>
                            <select id="estado" name="estado"
                                class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-400">
                                <option value="activa" {{ $maquina->estado === 'activa' ? 'selected' : '' }}>Activa
                                </option>
                                <option value="averiada" {{ $maquina->estado === 'averiada' ? 'selected' : '' }}>
                                    Averiada</option>
                                <option value="pausa" {{ $maquina->estado === 'pausa' ? 'selected' : '' }}>Pausa
                                </option>
                                <option value="mantenimiento"
                                    {{ $maquina->estado === 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button"
                                onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg">
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
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
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
                const qrSize = 100;

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
        width: 100px;
        height: 100px;
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
    window.onload = () => {
        const images = document.images;
        let loadedImages = 0;
        const totalImages = images.length;

        if (totalImages === 0) {
            window.print();
            setTimeout(() => window.close(), 1000);
            return;
        }

        for (const img of images) {
            if (img.complete) {
                loadedImages++;
            } else {
                img.onload = img.onerror = () => {
                    loadedImages++;
                    if (loadedImages === totalImages) {
                        window.print();
                        setTimeout(() => window.close(), 1000);
                    }
                };
            }
        }

        if (loadedImages === totalImages) {
            window.print();
            setTimeout(() => window.close(), 1000);
        }
    };
<\/script>


</body>
</html>
`);
                    printWindow.document.close();
                    tempQR.remove();
                }, 300);
            }

            async function imprimirEtiquetasLote(etiquetaIds) {
                const etiquetas = [];

                for (const id of etiquetaIds) {
                    const canvas = document.getElementById(`canvas-imprimir-etiqueta-${id}`);
                    const contenedor = document.getElementById(`etiqueta-${id}`);

                    if (!canvas || !contenedor) continue;

                    const scaleFactor = 2;
                    const tempCanvas = document.createElement("canvas");
                    tempCanvas.width = canvas.width * scaleFactor;
                    tempCanvas.height = canvas.height * scaleFactor;
                    const ctx = tempCanvas.getContext("2d");
                    ctx.scale(scaleFactor, scaleFactor);
                    ctx.drawImage(canvas, 0, 0);
                    const canvasImg = tempCanvas.toDataURL("image/png");

                    const clone = contenedor.cloneNode(true);
                    clone.classList.add("etiqueta-print");
                    clone.querySelectorAll(".no-print").forEach(el => el.remove());

                    const img = new Image();
                    img.src = canvasImg;
                    img.style.width = "100%";
                    img.style.height = "auto";
                    const canvasContainer = clone.querySelector("canvas").parentNode;
                    canvasContainer.innerHTML = "";
                    canvasContainer.appendChild(img);

                    const tempQR = document.createElement("div");
                    document.body.appendChild(tempQR);

                    await new Promise(resolve => {
                        new QRCode(tempQR, {
                            text: id.toString(),
                            width: 100,
                            height: 100
                        });

                        setTimeout(() => {
                            const qrImg = tempQR.querySelector("img");
                            if (qrImg) {
                                const qrWrapper = document.createElement("div");
                                qrWrapper.className = "qr-box";
                                qrWrapper.appendChild(qrImg);
                                clone.insertBefore(qrWrapper, clone.firstChild);
                            }

                            etiquetas.push(clone.outerHTML);
                            tempQR.remove();
                            resolve();
                        }, 300);
                    });
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
            margin: 1cm auto;
            padding: 1.5cm;
            border: 2px solid #000;
            font-family: Arial, sans-serif;
            font-size: 15px;
            color: #000;
            box-sizing: border-box;
            /* 🔴 Esta línea la tienes que eliminar: */
            /* page-break-after: always; */
             break-inside: avoid; /* 👈 Esto evita que se parta entre páginas */
        }

        .etiqueta-print + .etiqueta-print {
            margin-top: 1cm;
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
        </style>`;

                const printWindow = window.open("", "_blank");
                printWindow.document.open();
                printWindow.document.write(`
<html>
<head>
    <title>Etiquetas</title>
    ${style}
</head>
<body>
    ${etiquetas.join('')}
   <script>
    window.onload = () => {
        const images = document.images;
        let loaded = 0;
        const total = images.length;

        if (total === 0) {
            window.print();
            setTimeout(() => window.close(), 1000);
            return;
        }

        for (const img of images) {
            if (img.complete) {
                loaded++;
            } else {
                img.onload = img.onerror = () => {
                    loaded++;
                    if (loaded === total) {
                        window.print();
                        setTimeout(() => window.close(), 1000);
                    }
                };
            }
        }

        if (loaded === total) {
            window.print();
            setTimeout(() => window.close(), 1000);
        }
    };
<\/script>

</body>
</html>`);
                printWindow.document.close();
            }
        </script>


</x-app-layout>
