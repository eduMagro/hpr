<x-app-layout>
    <x-slot name="title">Subpaquetes - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('paquetes.index') }}" class="text-blue-600">
                {{ __('Planillas') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('paquetes.index') }}" class="text-blue-600">
                {{ __('Paquetes') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('etiquetas.index') }}" class="text-blue-600">
                {{ __('Etiquetas') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('elementos.index') }}" class="text-blue-600">
                {{ __('Elementos') }}
            </a>
            <span class="mx-2">/</span>

            {{ __('Lista de Subpaquetes') }}

        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- TABLA DE SUBPAQUETES -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">ID
                            <form method="GET" action="{{ route('subpaquetes.index') }}" class="mt-2">
                                <input type="text" name="id"
                                    class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </form>
                        </th>

                        <th class="px-4 py-3 border">Peso (kg)</th>
                        <th class="px-4 py-3 border">Dimensiones</th>
                        <th class="px-4 py-3 border">Cantidad</th>
                        <th class="px-4 py-3 border">Descripción</th>
                        <th class="px-4 py-3 border">Planilla
                            <form method="GET" action="{{ route('subpaquetes.index') }}" class="mt-2">
                                <input type="text" name="planilla"
                                    class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </form>
                        </th>
                        <th class="px-4 py-3 border">Paquete
                            <form method="GET" action="{{ route('subpaquetes.index') }}" class="mt-2">
                                <input type="text" name="paquete"
                                    class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </form>
                        </th>
                        <th class="px-4 py-3 border">Elemento
                            <form method="GET" action="{{ route('subpaquetes.index') }}" class="mt-2">
                                <input type="text" name="elemento"
                                    class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                            </form>
                        </th>
                        <th class="px-4 py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($subpaquetes as $subpaquete)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->id }}</td>

                            <td class="px-4 py-3 text-center border">{{ number_format($subpaquete->peso, 2) }}</td>
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->dimensiones ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $subpaquete->cantidad ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">
                                {{ $subpaquete->descripcion ?? 'Sin descripción' }}</td>
                            <td class="px-4 py-3 text-center border">
                                @if ($subpaquete->planilla)
                                    <a href="{{ route('planillas.index', ['id' => $subpaquete->planilla->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $subpaquete->planilla->codigo_limpio }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border">
                                @if ($subpaquete->paquete)
                                    <a href="{{ route('paquetes.index', ['id' => $subpaquete->paquete->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $subpaquete->paquete->id }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border">
                                @if ($subpaquete->elemento)
                                    <a href="{{ route('elementos.index', ['id' => $subpaquete->elemento->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        ID {{ $subpaquete->elemento->id }} - FIGURA
                                        {{ $subpaquete->elemento->figura }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <a href="#" class="text-blue-500 hover:underline abrir-modal-dibujo"
                                    data-id="{{ $subpaquete->id }}" data-dimensiones="{{ $subpaquete->dimensiones }}"
                                    data-peso="{{ $subpaquete->peso }}">
                                    Ver
                                </a>
                                <a href="{{ route('subpaquetes.edit', $subpaquete->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <x-boton-eliminar :action="route('subpaquetes.destroy', $subpaquete->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-gray-500">No hay subpaquetes registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Modal -->
        <div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
            <div class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:text-red-900">
                    ✖
                </button>
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">{{ $subpaquetes->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const modal = document.getElementById("modal-dibujo");
                const cerrarModal = document.getElementById("cerrar-modal");
                const canvas = document.getElementById("canvas-dibujo");
                const ctx = canvas.getContext("2d");

                // Márgenes internos para delimitar el área de dibujo
                const marginX = 50; // Margen horizontal
                const marginY = 25; // Margen vertical

                // Función para extraer longitudes y ángulos de un string de dimensiones
                function extraerDimensiones(dimensiones) {
                    const longitudes = [];
                    const angulos = [];
                    if (!dimensiones) return {
                        longitudes,
                        angulos
                    };

                    const tokens = dimensiones.split(/\s+/).filter(token => token.length > 0);

                    tokens.forEach(token => {
                        if (token.includes("d")) {
                            angulos.push(parseFloat(token.replace("d", "")) || 0);
                        } else {
                            longitudes.push(parseFloat(token) || 50);
                        }
                    });

                    return {
                        longitudes,
                        angulos
                    };
                }

                // Función para calcular el bounding box de la figura
                function calcularBoundingBox(longitudes, angulos) {
                    let x = 0,
                        y = 0,
                        angle = 0;
                    let minX = 0,
                        maxX = 0,
                        minY = 0,
                        maxY = 0;

                    longitudes.forEach((longitud, i) => {
                        x += longitud * Math.cos(angle * Math.PI / 180);
                        y += longitud * Math.sin(angle * Math.PI / 180);

                        minX = Math.min(minX, x);
                        maxX = Math.max(maxX, x);
                        minY = Math.min(minY, y);
                        maxY = Math.max(maxY, y);

                        angle += angulos[i] || 0;
                    });

                    return {
                        minX,
                        maxX,
                        minY,
                        maxY
                    };
                }

                // Función para dibujar la figura en un canvas
                function dibujarFigura(canvasId, dimensionesStr, peso) {
                    const canvas = document.getElementById(canvasId);
                    if (!canvas) {
                        console.warn(`Canvas no encontrado: ${canvasId}`);
                        return;
                    }

                    const ctx = canvas.getContext("2d");
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Extraer dimensiones
                    const {
                        longitudes,
                        angulos
                    } = extraerDimensiones(dimensionesStr);

                    if (longitudes.length === 0) {
                        console.warn("No hay dimensiones válidas para dibujar.");
                        return;
                    }

                    // Calcular bounding box para determinar escala y ajuste
                    const {
                        minX,
                        maxX,
                        minY,
                        maxY
                    } = calcularBoundingBox(longitudes, angulos);
                    const figWidth = maxX - minX || 50; // Evitar división por cero
                    const figHeight = maxY - minY || 50;

                    // Determinar escala para ajustar la figura al canvas
                    const availableWidth = canvas.width - 2 * marginX;
                    const availableHeight = canvas.height - 2 * marginY;
                    const scale = Math.min(availableWidth / figWidth, availableHeight / figHeight);

                    // Centro del área de dibujo
                    const centerX = canvas.width / 2;
                    const centerY = canvas.height / 2;

                    // Ajustar la figura al centro del canvas
                    ctx.save();
                    ctx.translate(centerX, centerY);
                    ctx.scale(scale, scale);
                    ctx.translate(-minX - figWidth / 2, -minY - figHeight / 2);

                    // Dibujar la figura
                    ctx.strokeStyle = "#0000FF";
                    ctx.lineWidth = 2 / scale;
                    ctx.lineCap = "round";
                    ctx.lineJoin = "round";
                    ctx.beginPath();

                    let x = 0,
                        y = 0,
                        angle = 0;
                    ctx.moveTo(x, y);
                    longitudes.forEach((longitud, i) => {
                        x += longitud * Math.cos(angle * Math.PI / 180);
                        y += longitud * Math.sin(angle * Math.PI / 180);
                        ctx.lineTo(x, y);
                        angle += angulos[i] || 0;
                    });
                    ctx.stroke();
                    ctx.restore();

                    // Mostrar cantidad
                    ctx.font = "14px Arial";
                    ctx.fillStyle = "#FF0000";
                    ctx.fillText(`${peso} Kg`, canvas.width - 50, canvas.height - 10);
                }

                // Manejar clic en enlaces "Ver"
                document.querySelectorAll(".abrir-modal-dibujo").forEach((link) => {
                    link.addEventListener("click", function(event) {
                        event.preventDefault(); // Evita la recarga de la página

                        const dimensiones = this.getAttribute("data-dimensiones");
                        const cantidad = this.getAttribute("data-cantidad") || 1;

                        modal.classList.remove("hidden");
                        dibujarFigura("canvas-dibujo", dimensiones, cantidad);
                    });
                });

                // Cerrar el modal
                cerrarModal.addEventListener("click", function() {
                    modal.classList.add("hidden");
                });

                // Cerrar modal al hacer clic fuera de él
                modal.addEventListener("click", function(e) {
                    if (e.target === modal) {
                        modal.classList.add("hidden");
                    }
                });
            });
        </script>

</x-app-layout>
