<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trabajando en Máquina') }}: <strong>{{ $maquina->nombre }}</strong>
        </h2>
    </x-slot>
    <style>
        canvas {
            width: 100%;
            max-width: 100%;
            border: 1px solid blue;
            border-radius: 4px;
            background-color: rgba(0, 123, 255, 0.1)
        }
    </style>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    @endif

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'success',
                    text: '{{ session('success') }}',
                    confirmButtonColor: '#28a745'
                });
            });
        </script>
    @endif

    <div class="container mx-auto px-4 py-6">
        <!-- Mostrar los compañeros -->
        <div class="mb-4">
            <div class="flex flex-col md:flex-row md:space-x-6">
                <!-- Usuario principal -->
                <div class="bg-white border p-3 rounded-lg shadow-md w-full md:w-1/2">
                    <h4 class="text-gray-600 font-semibold">Operario</h4>
                    <p class="text-gray-800 font-bold text-lg">{{ $usuario1->name }}</p>
                </div>

                <!-- Compañero seleccionado -->
                @if ($usuario2)
                    <div class="bg-white border p-3 rounded-lg shadow-md w-full md:w-1/2">
                        <h4 class="text-gray-600 font-semibold">Compañero seleccionado</h4>
                        <p class="text-gray-800 font-bold text-lg">{{ $usuario2->name }}</p>
                    </div>
                @else
                    <div class="bg-white border p-3 rounded-lg shadow-md w-full md:w-1/2">
                        <h4 class="text-gray-600 font-semibold">Compañero seleccionado</h4>
                        <p class="text-red-500 font-bold text-lg">No se ha seleccionado un compañero.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Grid principal -->
        <div class="grid grid-cols-1 sm:grid-cols-7 gap-6">
            <!-- --------------------------------------------------------------- Información de la máquina --------------------------------------------------------------- -->
            <div class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
                <h3 class="block w-full bg-gray-200 font-bold text-xl text-center break-words p-2 rounded-md">
                    {{ $maquina->codigo }}
                </h3>
                <!-- Mostrar los productos en la máquina -->
                @if ($maquina->productos->isEmpty())
                    <p>No hay productos en esta máquina.</p>
                @else
                    <ul class="list-none p-6 break-words">
                        @foreach ($maquina->productos as $producto)
                            <li class="mb-1">
                                <div class="flex items-start justify-between">
                                    <div class="flex flex-col">

                                        <span><strong>Diámetro:</strong> {{ $producto->diametro_mm }}</span>
                                        @if ($producto->tipo === 'barras')
                                            <span><strong>Longitud:</strong> {{ $producto->longitud_cm }}</span>
                                        @endif
                                        <a href="{{ route('productos.show', $producto->id) }}"
                                            class="btn btn-sm btn-primary mb-2">Ver</a>
                                    </div>

                                    @if ($producto->tipo == 'encarretado')
                                        <div id="progreso-container-{{ $producto->id }}"
                                            class="ml-4 relative w-20 h-20 bg-gray-300 overflow-hidden rounded-lg">
                                            <div id="progreso-barra-{{ $producto->id }}"
                                                class="absolute bottom-0 w-full bg-green-500"
                                                style="height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%;">
                                            </div>
                                            <span id="progreso-texto-{{ $producto->id }}"
                                                class="absolute top-2 left-2 text-white text-xs font-semibold">
                                                {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </li>
                            <hr>
                        @endforeach
                    </ul>
                @endif

            </div>

            <!-- --------------------------------------------------------------- Planificación para la máquina agrupada por etiquetas --------------------------------------------------------------- -->
            <div class="bg-white border p-4 shadow-md w-full rounded-lg sm:col-span-3">
                <h3 class="font-bold text-xl">Planificación prevista</h3>

                @php
                    // Agrupamos los elementos por etiqueta_id
                    $elementosAgrupados = $maquina->elementos->groupBy('etiqueta_id');

                    $elementosAgrupadosScript = $maquina->elementos
                        ->groupBy('etiqueta_id')
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
                                    ->values(), // Asegurar que sea un array y no una colección
                            ];
                        })
                        ->values(); // Convertimos en un array numérico en lugar de objeto

                @endphp

                @forelse ($elementosAgrupados as $etiquetaId => $elementos)
                    @php
                        $etiqueta = $elementos->first()->etiquetaRelacion ?? null; // Obtener la etiqueta del primer elemento para mostrarla. cambio el nombre de la relacion para que nocree conflicto con la columna etiquta
                        $planilla = $elementos->first()->planilla ?? null; // Obtener la etiqueta del primer elemento para mostrarla
                    @endphp

                    <div class="bg-yellow-100 p-6 rounded-lg shadow-md mt-4">
                        <h2 class="text-lg font-semibold text-gray-700">Planilla:
                            <strong> {{ $planilla->codigo_limpio }}</strong>
                        </h2>
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{ $etiqueta->id_et }} {{ $etiqueta->nombre ?? 'Sin nombre' }} {{ $etiqueta->marca }}
                            (Número: {{ $etiqueta->numero_etiqueta ?? 'Sin número' }})
                        </h3>
                        <!-- Contenedor oculto para generar el QR -->
                        <div id="qrContainer-{{ $etiqueta->id }}" style="display: none;"></div>
                        <p class="text-gray-500 text-sm"><strong>Fecha Inicio:</strong> <span
                                id="inicio-{{ $etiqueta->id }}">{{ $etiqueta->fecha_inicio ?? 'No asignada' }}</span><strong>
                                Fecha
                                Finalización:</strong> <span
                                id="final-{{ $etiqueta->id }}">{{ $etiqueta->fecha_finalizacion ?? 'No asignada' }}</span>
                            <span id="emoji-{{ $etiqueta->id }}"></span><br>
                            <strong> Estado: </strong><span
                                id="estado-{{ $etiqueta->id }}">{{ $etiqueta->estado }}</span>
                        </p>
                        <p class="text-gray-500 text-sm">
                            {{ $etiqueta->paquete_id ? '✅ ' . 'Paquete ID' . $etiqueta->paquete_id : 'SIN EMPAQUETAR' }}
                        </p>
                        <!-- 🔹 Elementos de la misma etiqueta en otras máquinas -->
                        @if (isset($otrosElementos[$etiqueta->id]) && $otrosElementos[$etiqueta->id]->isNotEmpty())
                            <h4 class="font-semibold text-red-700 mt-6">⚠️ Otros elementos de esta etiqueta están en
                                otras máquinas:</h4>
                            <div class="bg-red-100 p-4 rounded-lg shadow-md">
                                @foreach ($otrosElementos[$etiqueta->id] as $elementoOtro)
                                    <p class="text-gray-600">
                                        <strong>ID:</strong> {{ $elementoOtro->id_el }} |
                                        <strong>Máquina:</strong> {{ $elementoOtro->maquina->nombre }} |
                                        <strong>Peso:</strong> {{ $elementoOtro->peso_kg }} kg |
                                        <strong>Dimensiones:</strong> {{ $elementoOtro->dimensiones ?? 'No asignado' }}
                                    </p>
                                    <hr class="my-2">
                                @endforeach
                            </div>
                        @endif

                        <!-- GRID PARA ELEMENTOS -->
                        <div class="grid grid-cols-1 gap-4">
                            @foreach ($elementos as $elemento)
                                <div id="elemento-{{ $elemento->id }}"
                                    class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">


                                    <p class="text-gray-500 text-sm">
                                        <strong>{{ $loop->iteration }} </strong> {{ $elemento->id_el }}
                                    </p>


                                    <p class="text-gray-500 text-sm"></p>
                                    <hr class="my-2">
                                    <p class="text-gray-500 text-sm">
                                        <strong>Peso:</strong> {{ $elemento->peso_kg }} <strong>Diámetro:</strong>
                                        {{ $elemento->diametro_mm }} <strong> Longitud:</strong>
                                        {{ $elemento->longitud_cm }}<strong> Número de piezas:</strong>
                                        {{ $elemento->barras ?? 'No asignado' }} <strong> Tipo de Figura:</strong>
                                        {{ $elemento->figura ?? 'No asignado' }}
                                    </p>
                                    <hr class="my-2">
                                    <p class="text-gray-500 text-sm">
                                        <strong>Dimensiones:</strong> {{ $elemento->dimensiones ?? 'No asignado' }}
                                    </p>

                                    <!-- Canvas para dibujo -->
                                    <canvas id="canvas-{{ $elemento->id }}"
                                        data-loop="{{ $loop->iteration }}"></canvas>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-4 text-gray-600">
                        No hay elementos disponibles para esta máquina.
                    </div>
                @endforelse
            </div>
            <!-- --------------------------------------------------------------- GRID PARA OTROS --------------------------------------------------------------- -->
            <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
                <div class="flex flex-col gap-4">
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
                    <!-- Input de lectura de QR -->
                    <div class="bg-white border p-2 shadow-md rounded-lg self-start sm:col-span-1 md:sticky">
                        <h3 class="font-bold text-xl">PROCESO</h3>
                        <input type="text" id="qrInput" class="w-full border p-2 rounded"
                            placeholder="Escanea un QR..." autofocus>
                    </div>
                    <!-- Sistema de inputs para crear paquetes -->
                    <div class="bg-gray-100 border p-2 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl">Crear Paquete</h3>

                        <!-- Input para leer etiquetas QR -->
                        <div class="mb-4">
                            <!-- Input para leer etiquetas QR -->
                            <div class="mb-4">
                                <label for="qrEtiqueta" class="block text-gray-700 font-semibold">Escanear QR:</label>
                                <input type="text" id="qrEtiqueta" class="w-full border p-2 rounded"
                                    placeholder="Escanea un QR..." autofocus>
                            </div>
                        </div>

                        <!-- Listado dinámico de etiquetas -->
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-700 mb-2">Etiquetas agregadas:</h4>
                            <ul id="etiquetasList" class="list-disc pl-6 space-y-2">
                                <!-- Las etiquetas se agregarán aquí dinámicamente -->
                            </ul>
                        </div>

                        <!-- Botón para crear el paquete -->
                        <button id="crearPaqueteBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full">
                            📦 Crear Paquete
                        </button>

                    </div>
                </div>
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

            <!-- Modal Reportar Incidencia (Oculto por defecto) -->
            <div id="modalIncidencia"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <form method="POST" action="{{ route('alertas.store') }}">
                        @csrf

                        <!-- Destinatario (Opcional: puedes ocultarlo o asignarlo dinámicamente) -->
                        <label for="destinatario"
                            class="block text-sm font-medium text-gray-700 mb-1">Destinatario</label>
                        <select name="destinatario" id="destinatario" class="w-full border rounded p-2 mb-4"
                            required>
                            <option value="administracion">Administración</option>
                            <option value="mecanico">Mecánico</option>
                            <option value="desarrollador">Desarrollador</option>
                        </select>

                        <!-- Mensaje de incidencia -->
                        <label for="mensaje" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="mensaje" id="mensaje" class="w-full border rounded p-2 mb-4" rows="3"
                            placeholder="Describe el problema..." required></textarea>
                        <input type="hidden" name="user_id_2" value="{{ session('compañero_id') }}">



                        <div class="flex justify-end mt-4">
                            <button onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                                class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                                Cancelar
                            </button>
                            <button class="px-4 py-2 bg-red-600 text-white rounded">Enviar</button>
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

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/maquinaJS/trabajo_maquina.js') }}"></script>
    <script src="{{ asset('js/imprimirQr.js') }}"></script>
    <script>
        window.etiquetasConElementos = @json($elementosAgrupadosScript);
    </script>
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
    <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
</x-app-layout>
