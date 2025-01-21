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
        <div class="grid grid-cols-1 sm:grid-cols-6 gap-6">
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
                                    <div
                                        style="width: 100px; height: 100px; background-color: #ddd; position: relative; overflow: hidden;">
                                        <div class="cuadro verde"
                                            style="width: 100%; 
                                                       height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
                                                       background-color: green; 
                                                       position: absolute; 
                                                       bottom: 0;">
                                        </div>
                                        <span style="position: absolute; top: 10px; left: 10px; color: white;">
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
                            Etiqueta: {{ $etiqueta->nombre ?? 'Sin nombre' }}
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
                                        <strong>Estado: </strong><span
                                            id="estado-{{ $elemento->id }}">{{ $elemento->estado }}</span>
                                    </p>

                                    <hr class="my-2">

                                    <p class="text-gray-500 text-sm">
                                        <strong>ID: </strong> {{ $elemento->id }}
                                    </p>
                                    <hr class="my-2">
                                    <p class="text-gray-500 text-sm"><strong>Fecha Inicio:</strong> <span
                                            id="inicio-{{ $elemento->id }}">{{ $elemento->fecha_inicio ?? 'No asignada' }}</span><strong>Fecha
                                            Finalización:</strong> <span
                                            id="final-{{ $elemento->id }}">{{ $elemento->fecha_finalizacion ?? 'No asignada' }}</span>
                                        <span id="emoji-{{ $elemento->id }}"></span>
                                    </p>
                                    <p class="text-gray-500 text-sm"></p>
                                    <hr class="my-2">
                                    <p class="text-gray-500 text-sm">
                                        <strong>Peso:</strong> {{ $elemento->peso_kg }} <strong>Diámetro:</strong>
                                        {{ $elemento->diametro_mm }} <strong>Longitud:</strong>
                                        {{ $elemento->longitud_cm }}<strong>Número de piezas:</strong>
                                        {{ $elemento->barras ?? 'No asignado' }} <strong>Tipo de Figura:</strong>
                                        {{ $elemento->figura ?? 'No asignado' }}
                                    </p>
                                    <hr class="my-2">

                                    <p class="text-gray-500 text-sm">

                                    </p>
                                    <hr class="my-2">

                                    <p class="text-gray-500 text-sm">

                                    </p>
                                    <hr class="my-2">

                                    <p class="text-gray-500 text-sm">

                                    </p>
                                    <hr class="my-2">

                                    <p class="text-gray-500 text-sm">

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
            <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-1 md:sticky md:top-20">
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
                        <h3 class="font-bold text-xl mb-2">Escanear QR</h3>
                        <input type="text" id="qrInput" class="w-full border p-2 rounded"
                            placeholder="Escanea un QR..." autofocus>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const etiquetasConElementos = @json($elementosAgrupadosScript);

            console.log("etiquetasConElementos:", etiquetasConElementos);

            etiquetasConElementos.forEach((etiqueta, etiquetaIndex) => {
                etiqueta.elementos.forEach((elemento, elementoIndex) => {
                    const canvasId = `canvas-${elemento.id}`;
                    const canvas = document.getElementById(canvasId);

                    if (canvas && elemento.dimensiones) {
                        const instrucciones = generarInstrucciones(elemento.dimensiones);


                        // Obtener el número de iteración del elemento dentro de su etiqueta
                        const loopNumber = `${etiquetaIndex + 1}.${elementoIndex + 1}`;

                        ajustarCanvasAlFigura(canvas, instrucciones,
                            loopNumber); // Ajustar tamaño del canvas a la figura
                        dibujarFigura(canvas, instrucciones); // Dibujar la figura específica
                    }
                });
            });
        });

        function ajustarCanvasAlFigura(canvas, instrucciones, loopNumber) {
            let x = 0,
                y = 0;
            let angle = 0;
            const points = [{
                x,
                y
            }];

            // Calcular puntos de la figura considerando seno y coseno
            instrucciones.forEach((inst, index) => {
                console.log(
                    `-----------------------  Elemento ${loopNumber}, Instrucción ${index + 1} -----------------`
                );

                // Incrementar el ángulo acumulado en radianes
                if (inst.angulo !== 0) {
                    angle += inst.angulo * (Math.PI / 180); // Convertir grados a radianes
                    console.log(`Ángulo acumulado (radianes): ${angle}`);
                }

                // Calcular los desplazamientos en X e Y basados en el ángulo actual
                if (inst.longitud !== 0) {
                    const deltaX = inst.longitud * Math.cos(angle);
                    const deltaY = inst.longitud * Math.sin(angle);
                    x += deltaX; // Sumar desplazamiento en X
                    y += deltaY; // Sumar desplazamiento en Y
                    points.push({
                        x,
                        y
                    });

                    console.log(`Desplazamiento (longitud): ${inst.longitud}`);
                    console.log(`Delta X: ${deltaX}`);
                    console.log(`Delta Y: ${deltaY}`);
                    console.log(`Nueva posición: X=${x}, Y=${y}`);
                }
            });


            // Determinar límites de la figura
            const minX = Math.min(...points.map(p => p.x));
            const maxX = Math.max(...points.map(p => p.x));
            const minY = Math.min(...points.map(p => p.y));
            const maxY = Math.max(...points.map(p => p.y));

            const figureWidth = maxX - minX;
            const figureHeight = maxY - minY;

            // Configurar dimensiones del canvas
            const margin = 20; // Margen adicional mínimo
            const canvasWidth = Math.max(figureWidth + margin * 2, canvas.clientWidth);
            const canvasHeight = Math.max(figureHeight + margin * 2, canvas.clientHeight);

            canvas.width = canvasWidth;
            canvas.height = canvasHeight;

            // Calcular márgenes para centrar la figura
            const margenLateral = (canvas.width - figureWidth) / 2;
            const margenVertical = (canvas.height - figureHeight) / 2;

            // Guardar desplazamiento para centrar la figura
            canvas.startX = margenLateral - minX;
            canvas.startY = margenVertical - minY;

            // Logs para depuración

            console.log(` - Canvas dimensions: ${canvasWidth}x${canvasHeight}`);
            console.log(`Margins: X=${margenLateral}, Y=${margenVertical}`);
            console.log(`Figure Width: ${figureWidth}, Height: ${figureHeight}`);
            console.log(`Min/Max X: ${minX}, ${maxX} | Min/Max Y: ${minY}, ${maxY}`);
        }

        function generarInstrucciones(dimensiones) {
            if (!dimensiones || typeof dimensiones !== "string") {
                console.warn("Error: dimensiones no definidas o no es una cadena válida.");
                return [];
            }
            const valores = dimensiones.split("\t");
            let longitudes = valores.map(valor => valor.includes("d") ? 0 : parseFloat(valor));

            const instrucciones = [];
            valores.forEach((valor, index) => {
                if (valor.includes("d")) {
                    const angulo = parseFloat(valor.replace("d", ""));
                    instrucciones.push({
                        longitud: 0,
                        angulo
                    });
                } else {
                    const longitud = longitudes[index];
                    instrucciones.push({
                        longitud,
                        angulo: 0
                    });
                }
            });

            return instrucciones;
        }


        function dibujarFigura(canvas, instrucciones) {
            const ctx = canvas.getContext("2d");
            if (!ctx) {
                console.error("Error: No se pudo obtener el contexto 2D del canvas");
                return;
            }

            let x = canvas.startX || 0;
            let y = canvas.startY || 0;
            let angle = 0;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.beginPath();
            ctx.moveTo(x, y);

            instrucciones.forEach(inst => {
                if (inst.longitud !== 0) {
                    x += inst.longitud * Math.cos(angle);
                    y += inst.longitud * Math.sin(angle);
                    ctx.lineTo(x, y);
                }
                if (inst.angulo !== 0) {
                    angle += inst.angulo * (Math.PI / 180);
                }
            });

            ctx.strokeStyle = "rgba(0, 0, 0, 0.5)";
            ctx.stroke();
        }
    </script>
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const qrInput = document.getElementById("qrInput");

            if (!qrInput) {
                console.error("Error: No se encontró el input #qrInput en el DOM.");
                return;
            }

            qrInput.addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    let elementoId = this.value.trim();

                    if (!elementoId || isNaN(elementoId) || elementoId <= 0) {
                        alert("❌ ID inválido. Intenta de nuevo.");
                        return;
                    }

                    actualizarElemento(elementoId);
                    this.value = ""; // Limpiar input tras lectura
                }
            });
        });

        function actualizarElemento(id) {
            console.log(`📡 Enviando solicitud para actualizar el elemento con ID: ${id}`);

            let url = `/actualizar-elemento/${id}`;

            fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    },
                    body: JSON.stringify({
                        id: id
                    }),
                })
                .then(response => {
                    console.log("📩 Respuesta HTTP recibida:", response);

                    if (!response.ok) {
                        throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    }

                    return response.json();
                })
                .then(data => {
                    console.log("📊 Datos recibidos del servidor:", data);

                    if (data.success) {
                        actualizarDOM(id, data);
                    } else {
                        console.error("❌ Error en la respuesta de la API:", data.error);
                        alert(`Error: ${data.error}`);
                    }
                })
                .catch(error => {
                    console.error("🚨 Error en la petición:", error);
                    alert(`Hubo un error en la actualización: ${error.message}`);
                });
        }

        function actualizarDOM(id, data) {
            console.log(`🔄 Actualizando el DOM para el elemento ID: ${id}`);

            let estadoElement = document.getElementById(`estado-${id}`);
            let inicioElement = document.getElementById(`inicio-${id}`);
            let finalElement = document.getElementById(`final-${id}`);
            let emojiElement = document.getElementById(`emoji-${id}`);

            if (!estadoElement) console.warn(`⚠️ No se encontró el elemento #estado-${id}`);
            if (!inicioElement) console.warn(`⚠️ No se encontró el elemento #inicio-${id}`);
            if (!finalElement) console.warn(`⚠️ No se encontró el elemento #final-${id}`);

            if (estadoElement) estadoElement.textContent = data.estado;
            if (inicioElement) inicioElement.textContent = data.fecha_inicio || "No asignada";
            if (finalElement) finalElement.textContent = data.fecha_finalizacion || "No asignada";
            if (emojiElement) emojiElement.textContent = data.emoji || ""; // Insertar el emoji
        }
    </script>
</x-app-layout>
