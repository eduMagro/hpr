<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight"> <a href="{{ route('planillas.index') }}"
                class="text-blue-500">
                {{ __('Planillas') }}
            </a><span> / </span>Elementos de Planilla <strong>{{ $planilla->codigo_limpio }}</strong>
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
    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Índice de Elementos -->
            <div class="p-6 flex flex-col items-center w-full">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">Índice de Elementos</h3>
                <ul class="list-none w-full">
                    @if ($etiquetasConElementos->isEmpty())
                        <div class="text-center text-gray-600">
                            No hay elementos asociados a etiquetas para esta planilla.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6">
                            @foreach ($etiquetasConElementos as $etiqueta)
                                <div class="bg-white p-2 rounded-lg shadow-md">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                        Etiqueta: {{ $etiqueta->nombre ?? 'Sin nombre' }}
                                        ID: {{ $etiqueta->id }}
                                        (Número: {{ $etiqueta->numero_etiqueta ?? 'Sin número' }})
                                    </h3>

                                    <ul class="list-disc pl-10">
                                        @if ($etiqueta->elementos->isEmpty())
                                            <li>
                                                No hay elementos asociados a esta etiqueta.
                                            </li>
                                        @else
                                            @foreach ($etiqueta->elementos as $elemento)
                                                <li>

                                                    <a href="#elemento-{{ $elemento->id }}"
                                                        class="text-blue-500 hover:underline">
                                                        {{ $loop->iteration }}
                                                    </a>

                                                    <span class="text-gray-500 text-sm">
                                                        #{{ $elemento->id }},
                                                        Máquina: {{ $elemento->maquina->codigo }},
                                                        {{ $elemento->peso_kg }},
                                                        {{ $elemento->diametro_mm }},
                                                        {{ $elemento->longitud_cm }},
                                                        Estado: {{ $elemento->estado ?? 'Sin estado' }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </ul>
            </div>
            <!-- Información de Máquinas Únicas -->
            <div class="bg-white shadow-md rounded-lg p-6 flex flex-col items-center w-full">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">Máquinas Asociadas</h3>
                <div class="grid grid-cols-1 gap-6 w-full">
                    @php
                        // Extraer máquinas únicas desde las etiquetas y sus elementos
                        $maquinas = $etiquetasConElementos
                            ->flatMap(function ($etiqueta) {
                                return $etiqueta->elementos->pluck('maquina');
                            })
                            ->unique('id')
                            ->filter();
                    @endphp


                    @forelse ($maquinas as $maquina)
                        <div class="bg-gray-100 border p-4 shadow-md rounded-lg flex flex-col items-center w-full">
                            <h3 class="font-bold text-xl break-words mb-2">{{ $maquina->codigo }}</h3>
                            <p><strong>Nombre Máquina:</strong> {{ $maquina->nombre }}</p>
                            <p><strong>Diámetros aceptados:</strong>
                                {{ $maquina->diametro_min . ' - ' . $maquina->diametro_max }}</p>
                            <p><strong>Pesos bobinas:</strong>
                                {{ $maquina->peso_min && $maquina->peso_max ? $maquina->peso_min . ' - ' . $maquina->peso_max : 'Barras' }}
                            </p>

                            <!-- Productos asociados con la máquina -->
                            <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                            @if ($maquina->productos->isEmpty())
                                <p>No hay productos en esta máquina.</p>
                            @else
                                <ul class="list-disc pl-6 break-words w-full">
                                    @foreach ($maquina->productos as $producto)
                                        <li class="mb-2 flex items-center justify-between">
                                            <span>
                                                ID{{ $producto->id }} - Tipo: {{ $producto->tipo }} -
                                                D{{ $producto->diametro }}
                                                - L{{ $producto->longitud ?? '??' }}
                                            </span>
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
                                                    <span
                                                        style="position: absolute; top: 10px; left: 10px; color: white;">
                                                        {{ $producto->peso_stock }} /
                                                        {{ $producto->peso_inicial }} kg
                                                    </span>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <p>No hay máquinas asociadas a los elementos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- GRID PARA ETIQUETAS (Cada etiqueta inicia en una nueva fila) -->
        <div class="grid grid-cols-1 gap-6">
            @forelse ($etiquetasConElementos as $etiqueta)
                <div class="bg-yellow-200 p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">
                        {{ $etiqueta->nombre ?? 'Sin nombre' }}
                        #{{ $etiqueta->id }}
                        (Número: {{ $etiqueta->numero_etiqueta ?? 'Sin número' }})
                    </h2>
                    <!-- Datos adicionales de la etiqueta -->
                    <div class="mb-4 bg-yellow-100 p-2 rounded-lg">
                        <p><strong>Peso:</strong> {{ $etiqueta->peso ?? 'Desconocido' }} kg</p>
                        <p><strong>Estado:</strong> {{ $etiqueta->estado ?? 'Sin estado' }}</p>
                        <p><strong>Inicio:</strong> {{ $etiqueta->fecha_inicio ?? 'No iniciado' }}</p>
                        <p><strong>Finalización:</strong> {{ $etiqueta->fecha_finalizacion ?? 'No finalizado' }}</p>
                    </div>

                    <!-- GRID PARA ELEMENTOS (2 columnas dentro de cada etiqueta) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @forelse ($etiqueta->elementos as $elemento)
                            <div id="elemento-{{ $elemento->id }}"
                                class="bg-white p-2 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                                {{ $loop->iteration }}. <strong>#</strong>{{ $elemento->id }}
                                <!-- ID -->
                                <p class="text-gray-500 text-sm">

                                </p>
                                <hr class="my-2">
                                <!-- Estado -->
                                <p class="text-gray-500 text-sm">
                                    <strong>Máquina asignada:</strong>
                                    {{ $elemento->maquina->nombre ?? 'No asignado' }} <strong>Estado:</strong>
                                    {{ $elemento->estado ?? 'Sin estado' }} <strong>Tipo de Figura:</strong>
                                    {{ $elemento->figura ?? 'No asignado' }} <strong>Marca:</strong>
                                    {{ $elemento->marca ?? 'No asignado' }}
                                </p>
                                <hr class="my-2">
                                <p class="text-gray-500 text-sm">
                                    <strong>Peso:</strong> {{ $elemento->peso ?? 'No asignado' }}
                                    <strong>Diámetro:</strong> {{ $elemento->diametro ?? 'No asignado' }}
                                    <strong>Longitud:</strong> {{ $elemento->longitud ?? 'No asignado' }}
                                    <strong>Dobles por Barra:</strong> {{ $elemento->dobles_barra ?? 'No asignado' }}
                                    <strong>Número de piezas:</strong> {{ $elemento->barras ?? 'No asignado' }}
                                    <strong>Fila:</strong> {{ $elemento->fila ?? 'No asignado' }}
                                </p>
                                <hr class="my-2">

                                <p class="text-gray-500 text-sm">
                                    <strong>Tiempo estimado de fabricación:</strong>
                                    @if (isset($elemento->tiempo_fabricacion))
                                        @php
                                            $horas = intdiv($elemento->tiempo_fabricacion, 3600);
                                            $minutos = intdiv($elemento->tiempo_fabricacion % 3600, 60);
                                            $segundos = $elemento->tiempo_fabricacion % 60;
                                        @endphp
                                        @if ($horas > 0)
                                            {{ $horas }} horas
                                        @endif
                                        @if ($minutos > 0)
                                            {{ $minutos }} minutos
                                        @endif
                                        {{ $segundos }} segundos.
                                    @else
                                        Sin tiempo definido.
                                    @endif
                                </p>
                                <hr class="my-2">

                                <p class="text-gray-500 text-sm">
                                    <strong>Dimensiones:</strong> {{ $elemento->dimensiones ?? 'Sin dimensiones' }}
                                </p>
                                <!-- Canvas para dibujo -->
                                <canvas id="canvas-{{ $elemento->id }}" data-loop="{{ $loop->iteration }}"></canvas>
                            </div>
                        @empty
                            <p class="text-gray-600">No hay elementos asociados a esta etiqueta.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="col-span-4 text-center py-4 text-gray-600">
                    No hay etiquetas disponibles.
                </div>
            @endforelse
        </div>

        <!-- PAGINACIÓN -->
        @if ($etiquetasConElementos instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-6">
                {{ $etiquetasConElementos->appends(request()->except('page'))->links() }}
            </div>
        @endif

        <a href="{{ route('planillas.index') }}" class="btn btn-primary m-3">Volver a Planillas</a>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const etiquetasConElementos = @json($etiquetasConElementos);

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

            // Coordenadas iniciales ajustadas
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const enlaces = document.querySelectorAll('a[href^="#elemento-"]');

            enlaces.forEach(enlace => {
                enlace.addEventListener("click", function(e) {
                    e.preventDefault(); // Prevenir el comportamiento predeterminado

                    const destinoId = this.getAttribute("href").substring(1); // Obtener el ID
                    const destino = document.getElementById(destinoId);

                    if (destino) {
                        window.scrollTo({
                            top: destino.offsetTop -
                                100, // Ajustar posición para margen superior
                            behavior: "smooth" // Desplazamiento suave
                        });
                    }
                });
            });
        });
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
</x-app-layout>
