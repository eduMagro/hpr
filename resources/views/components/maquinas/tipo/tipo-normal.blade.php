    <!-- --------------------------------------------------------------- COLUMNA IZQUIERDA --------------------------------------------------------------- -->
    <div class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
        <!-- MATERIA PRIMA EN LA MAQUINA -->
        <ul class="list-none p-1 break-words">
            @foreach ($productosBaseCompatibles as $productoBase)
                @php
                    $productoExistente = $maquina->productos->firstWhere('producto_base_id', $productoBase->id);
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
                                <input type="hidden" name="producto_id" value="{{ $productoExistente->id }}">
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
        <!-- BOTONES DEBAJO DE LA MATERIA PRIMA EN LA MAQUINA -->
        <div class="flex flex-col gap-2 p-4">
            @if ($elementosAgrupados->isNotEmpty())
                <div id="datos-lote" data-lote='@json($elementosAgrupados->keys()->values())'></div>

                <div x-data="{ cargando: false }">
                    <button type="button"
                        @click="
        cargando = true;
        let datos = document.getElementById('datos-lote').dataset.lote;
        let lote = JSON.parse(datos);
        Promise.resolve(imprimirEtiquetas(lote))
            .finally(() => cargando = false);
    "
                        :disabled="cargando"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 font-semibold text-white shadow
           bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">

                        <svg x-show="cargando" class="h-4 w-4 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" role="status"
                            aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8v4l3.536-3.536A9 9 0 103 12h4z" />
                        </svg>

                        <span x-show="!cargando">🖨️ Imprimir Lote</span>
                        <span x-show="cargando">Cargando…</span>
                    </button>

                </div>
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
    <!-- --------------------------------------------------------------- COLUMNA CENTRAL --------------------------------------------------------------- -->
    <div class="bg-white border shadow-md w-full rounded-lg sm:col-span-4
            flex flex-col items-center gap-4">
        @forelse ($elementosAgrupados as $etiquetaSubId => $elementos)
            @php
                $firstElement = $elementos->first();
                $etiqueta =
                    $firstElement->etiquetaRelacion ?? Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();
                $planilla = $firstElement->planilla ?? null;
                $tieneElementosEnOtrasMaquinas =
                    isset($otrosElementos[$etiqueta?->id]) && $otrosElementos[$etiqueta?->id]->isNotEmpty();
            @endphp

            <div>
                <x-etiqueta.etiqueta :etiqueta="$etiqueta" :planilla="$planilla" :maquina-tipo="$maquina->tipo" />
            </div>
        @empty
            <div class="col-span-2 text-center py-4 text-gray-600">
                No hay etiquetas disponibles para esta máquina.
            </div>
        @endforelse
    </div>
    <!-- --------------------------------------------------------------- COLUMNA DERECHA --------------------------------------------------------------- -->

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
            <input type="number" name="paquete_id" id="paquete_id" required class="w-full border p-2 rounded mb-2"
                placeholder="Ingrese ID del paquete">
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
        <script>
            function imprimirLoteConCarga(el) {
                el.__x.$data.cargando = true; // accedemos al x-data si queremos
                let datos = document.getElementById('datos-lote').dataset.lote;
                let lote = JSON.parse(datos);
                imprimirEtiquetasLote(lote);
                setTimeout(() => {
                    el.__x.$data.cargando = false;
                }, 2000);
            }
        </script>


    </div>
    <!-- --------------------------------------------------------------- MODALES --------------------------------------------------------------- -->
    <x-maquinas.modales.cambio-maquina :maquina="$maquina" :maquinas="$maquinas" />
    <x-maquinas.modales.dividir-elemento />
