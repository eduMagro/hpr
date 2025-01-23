<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trabajando en Máquina') }}
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
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
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
            <!-- Información de la máquina -->
            <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-20">
                <h3 class="font-bold text-xl break-words">{{ $maquina->codigo }}</h3>
                <p><strong>Nombre Máquina:</strong> {{ $maquina->nombre }}</p>
                <p>
                    <button id="generateQR" onclick="generateAndPrintQR('{{ $maquina->codigo }}')"
                        class="btn btn-primary">QR</button>
                </p>
                <div id="qrCanvas" style="display:none;"></div>
                <p><strong>Diámetros aceptados:</strong>
                    {{ $maquina->diametro_min . ' - ' . $maquina->diametro_max }}</p>
                <p><strong>Pesos bobinas:</strong>
                    {{ $maquina->peso_min && $maquina->peso_max ? $maquina->peso_min . ' - ' . $maquina->peso_max : 'Barras' }}
                </p>

                <!-- Mostrar los productos en la máquina -->
                <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                @if ($maquina->productos->isEmpty())
                    <p>No hay productos en esta máquina.</p>
                @else
                    <ul class="list-disc pl-6 break-words">
                        @foreach ($maquina->productos as $producto)
                            <li class="mb-2 flex items-center justify-between">
                                <span>
                                    <strong>Diámetro: </strong>{{ $producto->diametro_mm }}
                                </span>
                                @if ($producto->tipo === 'barras')
                                    <span>
                                        <strong>Longitud:</strong> {{ $producto->longitud_cm }}
                                    </span>
                                @endif
                                <a href="{{ route('productos.show', $producto->id) }}"
                                    class="btn btn-sm btn-primary">Ver</a>
                                @if ($producto->tipo == 'encarretado')
                                    <div id="progreso-container-{{ $producto->id }}"
                                        style="width: 100px; height: 100px; background-color: #ddd; position: relative; overflow: hidden; border-radius: 8px;">
                                        <div id="progreso-barra-{{ $producto->id }}" class="cuadro verde"
                                            style="width: 100%; 
                                               height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
                                               background-color: green; 
                                               position: absolute; 
                                               bottom: 0;">
                                        </div>
                                        <span id="progreso-texto-{{ $producto->id }}"
                                            style="position: absolute; top: 10px; left: 10px; color: white;">
                                            {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                        </span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Planificación para la máquina agrupada por etiquetas -->
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
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            Etiqueta: {{ $etiqueta->nombre ?? 'Sin nombre' }} ID: {{ $etiqueta->id }}
                            (Número: {{ $etiqueta->numero_etiqueta ?? 'Sin número' }})
                        </h3>

                        <!-- GRID PARA ELEMENTOS -->
                        <div class="grid grid-cols-1 gap-4">
                            @foreach ($elementos as $elemento)
                                <div id="elemento-{{ $elemento->id }}"
                                    class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                                    {{ $loop->iteration }}.
                                    <button
                                        onclick="generateAndPrintQR('{{ $elemento->id }}', '{{ $elemento->descripcion_fila }}')"
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-md mb-4">
                                        <i class="fas fa-qrcode mr-2"></i> QR
                                    </button>

                                    <p class="text-gray-500 text-sm">
                                        <strong>ID: </strong> {{ $elemento->id }} <strong> Estado: </strong><span
                                            id="estado-{{ $elemento->id }}">{{ $elemento->estado }}</span>
                                    </p>
                                    <hr class="my-2">
                                    <p class="text-gray-500 text-sm"><strong>Fecha Inicio:</strong> <span
                                            id="inicio-{{ $elemento->id }}">{{ $elemento->fecha_inicio ?? 'No asignada' }}</span><strong>
                                            Fecha
                                            Finalización:</strong> <span
                                            id="final-{{ $elemento->id }}">{{ $elemento->fecha_finalizacion ?? 'No asignada' }}</span>
                                        <span id="emoji-{{ $elemento->id }}"></span>
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
            <!-- GRID PARA OTROS -->
            <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-20">
                <div class="flex flex-col gap-4">
                    <!-- Botón Reportar Incidencia -->
                    <button onclick="document.getElementById('modalIncidencia').classList.remove('hidden')"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                        🚨 Reportar Incidencia
                    </button>

                    <!-- Botón Realizar Chequeo de Máquina -->
                    <button onclick="document.getElementById('modalCheckeo').classList.remove('hidden')"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                        🛠️ Realizar Chequeo de Máquina
                    </button>
                    <!-- Input de lectura de QR -->
                    <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-1 md:sticky md:top-20">
                        <h3 class="font-bold text-xl mb-2">PROCESO</h3>
                        <input type="text" id="qrInput" class="w-full border p-2 rounded"
                            placeholder="Escanea un QR..." autofocus>
                    </div>
                    <!-- Sistema de inputs para crear paquetes -->
                    <div class="bg-gray-100 border p-4 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl mb-4">Crear Paquete</h3>

                        <!-- Input para leer etiquetas QR -->
                        <div class="mb-4">
                            <label for="qrEtiqueta" class="block text-gray-700 font-semibold">Escanear
                                QR:</label>
                            <input type="text" id="qrEtiqueta" class="w-full border p-2 rounded"
                                placeholder="Escanea un QR...">
                            <button onclick="agregarEtiqueta()"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-md mt-2 w-full">
                                ➕ Agregar
                            </button>
                        </div>

                        <!-- Listado dinámico de etiquetas -->
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-700 mb-2">Etiquetas agregadas:</h4>
                            <ul id="etiquetasList" class="list-disc pl-6 space-y-2">
                                <!-- Las etiquetas se agregarán aquí dinámicamente -->
                            </ul>
                        </div>

                        <!-- Botón para crear el paquete -->
                        <button onclick="crearPaquete()"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full">
                            📦 Crear Paquete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Reportar Incidencia (Oculto por defecto) -->
            <div id="modalIncidencia"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Reportar Incidencia</h2>
                    <textarea class="w-full border rounded p-2" rows="3" placeholder="Describe el problema..."></textarea>
                    <div class="flex justify-end mt-4">
                        <button onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                            class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                            Cancelar
                        </button>
                        <button class="px-4 py-2 bg-red-600 text-white rounded">Enviar</button>
                    </div>
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

    <script></script>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        function generateAndPrintQR(id, descripcion_fila) {
            // Limpiamos el contenedor del QR
            const qrContainer = document.getElementById('qrCanvas');
            qrContainer.innerHTML = ""; // Elimina cualquier QR previo

            // Generamos el QR con el ID
            const qrCode = new QRCode(qrContainer, {
                text: id.toString(),
                width: 200,
                height: 200,
            });

            // Esperamos hasta que el QR esté listo antes de imprimirlo
            const interval = setInterval(() => {
                const qrImg = qrContainer.querySelector('img');
                if (qrImg) {
                    clearInterval(interval); // Detenemos la espera

                    // Creamos una ventana para la impresión
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                      <html>
                          <head>
                              <title>Imprimir QR</title>
                              <style>
                                  body { display: flex; justify-content: center; align-items: center; flex-direction: column; }
                                  img { margin-bottom: 20px; }
                              </style>
                          </head>
                          <body>
                              <img src="${qrImg.src}" alt="Código QR" style="width:200px; height:200px;">
                              <p>${descripcion_fila}</p>
                              <script>
                                  window.print();
                                  setTimeout(() => window.close(), 1000); // Cierra la ventana después de imprimir
                              <\/script>
                          </body>
                      </html>
                  `);
                    printWindow.document.close();
                }
            }, 100); // Revisamos cada 100ms si el QR está listo
        }
    </script>
    <script src="{{ asset('js/maquinaJS/trabajo_maquina.js') }}"></script>
    <script>
        window.etiquetasConElementos = @json($elementosAgrupadosScript);
    </script>
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const etiquetas = [];

            function agregarEtiqueta() {
                const qrEtiqueta = document.getElementById('qrEtiqueta');
                const etiqueta = qrEtiqueta.value.trim();

                console.log("Valor escaneado:", qrEtiqueta.value); // Valor sin trim
                console.log("Valor procesado:", etiqueta); // Valor con trim

                if (!etiqueta) {
                    alert('Por favor, escanee un QR válido.');
                    return;
                }

                if (etiquetas.includes(etiqueta)) {
                    alert('Esta etiqueta ya ha sido agregada.');
                    qrEtiqueta.value = '';
                    return;
                }

                etiquetas.push(etiqueta);

                // Agregar la etiqueta al listado
                const etiquetasList = document.getElementById('etiquetasList');
                const listItem = document.createElement('li'); // Se estaba usando una variable no definida
                listItem.textContent = etiqueta;

                // Botón para eliminar la etiqueta
                const removeButton = document.createElement('button');
                removeButton.textContent = '❌';
                removeButton.className = 'ml-2 text-red-600 hover:text-red-800';
                removeButton.onclick = () => {
                    etiquetas.splice(etiquetas.indexOf(etiqueta), 1); // Eliminar del array
                    etiquetasList.removeChild(listItem); // Eliminar del DOM
                };

                listItem.appendChild(removeButton);
                etiquetasList.appendChild(listItem);

                qrEtiqueta.value = ''; // Limpiar el input
            }

            function crearPaquete() {
                if (etiquetas.length === 0) {
                    alert('No hay etiquetas para crear un paquete.');
                    return;
                }

                // Simular envío de datos al servidor
                console.log('Creando paquete con etiquetas:', etiquetas);
                alert('Paquete creado con éxito.');

                // Reiniciar el formulario
                etiquetas.length = 0;
                document.getElementById('etiquetasList').innerHTML = '';
            }

            // Asociar eventos a los botones
            document.getElementById('agregarEtiquetaBtn').addEventListener('click', agregarEtiqueta);
            document.getElementById('crearPaqueteBtn').addEventListener('click', crearPaquete);
        });
    </script>
</x-app-layout>
