    <x-app-layout>
        <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>
        <x-slot name="header">
            <h2 class="text-lg font-semibold text-gray-800">
                <a href="{{ route('planillas.index') }}" class="text-blue-600">
                    {{ __('Planillas') }}
                </a>
                <span class="mx-2">/</span>
                <a href="{{ route('paquetes.index') }}" class="text-blue-600">
                    {{ __('Paquetes') }}
                </a>
                <span class="mx-2">/</span>
                {{ __('Lista de Etiquetas') }}
                <span class="mx-2">/</span>
                <a href="{{ route('elementos.index') }}" class="text-blue-600">
                    {{ __('Elementos') }}
                </a>
                <span class="mx-2">/</span>
                <a href="{{ route('subpaquetes.index') }}" class="text-blue-600">
                    {{ __('Subpaquetes') }}
                </a>
            </h2>

        </x-slot>

        <div class="w-full px-6 py-4">
            <!-- Formulario de Filtros -->
            <form method="GET" action="{{ route('etiquetas.index') }}" class="mb-4 grid grid-cols-8 gap-4">
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="estado" id="estado"
                        class="w-40 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="completada" {{ request('estado') == 'completada' ? 'selected' : '' }}>Completada
                        </option>
                        <option value="fabricando" {{ request('estado') == 'fabricando' ? 'selected' : '' }}>Fabricando
                        </option>
                        <option value="montaje" {{ request('estado') == 'montaje' ? 'selected' : '' }}>Montaje</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Filtrar</button>
            </form>

            <!-- Tabla con formularios de búsqueda -->
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-left text-sm uppercase">
                            <th class="px-4 py-3 border">ID
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="id"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="px-4 py-3 border">Planilla
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="codigo_planilla"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="px-4 py-3 border">Ensamblador 1</th>
                            <th class="px-4 py-3 border">Ensamblador 2</th>
                            <th class="px-4 py-3 border">Soldador 1</th>
                            <th class="px-4 py-3 border">Soldador 2</th>
                            <th class="px-4 py-3 border">Paquete</th>
                            <th class="px-4 py-3 border">Número de Etiqueta
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="numero_etiqueta"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="px-4 py-3 border">Nombre
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="nombre"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="px-4 py-3 border">Ubicación</th>
                            <th class="px-4 py-3 border">Peso (kg)</th>
                            <th class="px-4 py-3 border">Fecha Inicio</th>
                            <th class="px-4 py-3 border">Fecha Finalización</th>
                            <th class="px-4 py-3 border">Estado</th>
                            <th class="px-4 py-3 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse ($etiquetas as $etiqueta)
                            <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->id }}</td>
                                <td class="px-4 py-3 text-center border">
                                    @if ($etiqueta->planilla_id)
                                        <a href="{{ route('planillas.index', ['planilla_id' => $etiqueta->planilla_id]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->planilla->codigo_limpio }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->ensamblador1 ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->ensamblador2 ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->soldador1 ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->soldador2 ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->paquete_id ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->numero_etiqueta }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->nombre }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->ubicacion->nombre ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->peso_kg }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->fecha_inicio ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->fecha_finalizacion ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center border">{{ $etiqueta->estado }}</td>
                                <td class="px-4 py-3 text-center border">
                                
                                        <button onclick="mostrarDibujo({{ $etiqueta->id }})" class="text-blue-500 hover:underline">
                                            Ver
                                        </button>
                                    <a href="{{ route('etiquetas.edit', $etiqueta->id) }}"
                                        class="text-yellow-500 hover:underline">Editar</a>
                                    <x-boton-eliminar :action="route('etiquetas.destroy', $etiqueta->id)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-4 text-gray-500">No hay etiquetas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-center">
                {{ $etiquetas->links() }}
            </div>
        </div>
<!-- Modal con Canvas para Dibujar las Dimensiones -->
<div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
    <div class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-center">Dibujo de la Etiqueta</h2>
        
        <!-- Contenedor desplazable -->
        <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
            <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
        </div>

        <div class="mt-4 text-right">
            <button id="cerrar-modal" class="px-4 py-2 bg-red-500 text-white rounded w-full sm:w-auto">Cerrar</button>
        </div>
    </div>
</div>

<script>
    window.etiquetasConElementos = @json($etiquetasJson);

    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("modal-dibujo");
        const cerrarModal = document.getElementById("cerrar-modal");
        const canvas = document.getElementById("canvas-dibujo");
        const ctx = canvas.getContext("2d");

        // Márgenes y espaciado
        const marginX = 50;
        const marginY = 25;
        const gapSpacing = 10;
        const minSlotHeight = 100;

        function extraerDimensiones(dimensiones) {
            const longitudes = [], angulos = [];
            dimensiones.split(/\s+/).forEach(token => {
                if (token.includes("d")) {
                    angulos.push(parseFloat(token.replace("d", "")) || 0);
                } else {
                    longitudes.push(parseFloat(token) || 50);
                }
            });
            return { longitudes, angulos };
        }

        function calcularBoundingBox(longitudes, angulos) {
            let currentX = 0, currentY = 0, currentAngle = 0;
            let minX = 0, maxX = 0, minY = 0, maxY = 0;

            longitudes.forEach((longitud, i) => {
                currentX += longitud * Math.cos((currentAngle * Math.PI) / 180);
                currentY += longitud * Math.sin((currentAngle * Math.PI) / 180);
                minX = Math.min(minX, currentX);
                maxX = Math.max(maxX, currentX);
                minY = Math.min(minY, currentY);
                maxY = Math.max(maxY, currentY);
                currentAngle += angulos[i] || 0;
            });

            return { minX, maxX, minY, maxY };
        }

        function dibujarElementos(elementos) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const numElementos = elementos.length;
            const canvasWidth = canvas.width;
            const canvasHeight = marginY * 2 + numElementos * minSlotHeight + (numElementos - 1) * gapSpacing;
            
            // Ajustar la altura del canvas según la cantidad de elementos
            canvas.height = canvasHeight;

            const availableWidth = canvasWidth - 2 * marginX;
            const availableSlotHeight = (canvasHeight - 2 * marginY - (numElementos - 1) * gapSpacing) / numElementos;

            elementos.forEach((elemento, index) => {
                const { longitudes, angulos } = extraerDimensiones(elemento.dimensiones || "");
                const centerX = marginX + availableWidth / 2;
                const centerY = marginY + availableSlotHeight / 2 + index * (availableSlotHeight + gapSpacing);

                if (longitudes.length === 1) {
                    dibujarLinea(ctx, centerX, centerY, availableWidth, longitudes);
                } else {
                    dibujarFigura(ctx, centerX, centerY, availableWidth, availableSlotHeight, longitudes, angulos);
                }

                // Etiqueta del elemento (ID)
                ctx.font = "14px Arial";
                ctx.fillStyle = "#FF0000";
                ctx.fillText(`#${elemento.id}`, marginX + availableWidth - 10, centerY + availableSlotHeight / 2 - 5);
            });
        }

        function dibujarLinea(ctx, centerX, centerY, availableWidth, length) {
            const scale = availableWidth / Math.abs(length);
            const lineLength = Math.abs(length) * scale;

            ctx.strokeStyle = "#0000FF";
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(centerX - lineLength / 2, centerY);
            ctx.lineTo(centerX + lineLength / 2, centerY);
            ctx.stroke();

            // Etiqueta de dimensión
            ctx.font = "12px Arial";
            ctx.fillStyle = "red";
            ctx.fillText(length.toString(), centerX, centerY - 10);
        }

        function dibujarFigura(ctx, centerX, centerY, availableWidth, availableHeight, longitudes, angulos) {
    const { minX, maxX, minY, maxY } = calcularBoundingBox(longitudes, angulos);
    const figWidth = maxX - minX;
    const figHeight = maxY - minY;

    const rotate = figWidth < figHeight;
    const scale = Math.min(availableWidth / (rotate ? figHeight : figWidth), availableHeight / (rotate ? figWidth : figHeight));
    const figCenterX = (minX + maxX) / 2;
    const figCenterY = (minY + maxY) / 2;

    ctx.save();
    ctx.translate(centerX, centerY);
    if (rotate) ctx.rotate(-Math.PI / 2);
    ctx.scale(scale, scale);
    ctx.translate(-figCenterX, -figCenterY);

    ctx.strokeStyle = "#0000FF";
    ctx.lineWidth = 2 / scale;
    ctx.beginPath();
    
    let currentX = 0, currentY = 0, currentAngle = 0;
    ctx.moveTo(currentX, currentY);

    longitudes.forEach((longitud, i) => {
        const newX = currentX + longitud * Math.cos((currentAngle * Math.PI) / 180);
        const newY = currentY + longitud * Math.sin((currentAngle * Math.PI) / 180);
        ctx.lineTo(newX, newY);

        // Dibujar la acotación en cada segmento
        const midX = (currentX + newX) / 2;
        const midY = (currentY + newY) / 2;
        const angleRad = Math.atan2(newY - currentY, newX - currentX);

        ctx.save();
        ctx.translate(midX, midY);
        ctx.rotate(angleRad);
        ctx.font = `${12 / scale}px Arial`;
        ctx.fillStyle = "red";
        ctx.fillText(longitud.toFixed(1), 0, -5 / scale); // Mostrar la dimensión
        ctx.restore();

        currentX = newX;
        currentY = newY;
        currentAngle += angulos[i] || 0;
    });

    ctx.stroke();
    ctx.restore();
}


        function mostrarDibujo(etiquetaId) {
            const etiqueta = window.etiquetasConElementos.find(e => e.id == etiquetaId);

            if (etiqueta && etiqueta.elementos.length > 0) {
                dibujarElementos(etiqueta.elementos);
            } else {
                console.warn("No hay elementos con dimensiones para dibujar.");
            }

            modal.classList.remove("hidden");
        }

        cerrarModal.addEventListener("click", function () {
            modal.classList.add("hidden");
        });

        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });

        window.mostrarDibujo = mostrarDibujo;
    });
</script>

    </x-app-layout>
