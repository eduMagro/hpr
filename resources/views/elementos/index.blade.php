<x-app-layout>
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
            <a href="{{ route('etiquetas.index') }}" class="text-blue-600">
                {{ __('Etiquetas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Lista de Elementos') }}
            <span class="mx-2">/</span>
            <a href="{{ route('subpaquetes.index') }}" class="text-blue-600">
                {{ __('Subpaquetes') }}
            </a>
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- Formulario de filtrado -->
        <form method="GET" action="{{ route('elementos.index') }}" class="mb-4 grid grid-cols-8 gap-4">
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="estado" class="border p-2 rounded">
                    <option value="">Todos</option>
                    <option value="Completado" {{ request('estado') == 'Completado' ? 'selected' : '' }}>Completado
                    </option>
                    <option value="Fabricando" {{ request('estado') == 'Fabricando' ? 'selected' : '' }}>Fabricando
                    </option>
                    <option value="Montaje" {{ request('estado') == 'Montaje' ? 'selected' : '' }}>Montaje</option>
                </select>
            </div>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="border p-2 rounded">
            <input type="date" name="fecha_finalizacion" value="{{ request('fecha_finalizacion') }}"
                class="border p-2 rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Filtrar</button>
        </form>

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full table-fixed border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 border text-center">ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="id"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Planilla
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="codigo_planilla"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Trabajador 1
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="usuario1"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Trabajador 2</th>
                        <th class="py-3 border text-center">Etiqueta
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="etiqueta"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Paquete ID

                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="paquete_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>

                        <th class="py-3 border text-center">Máquina 1
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="maquina"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Máquina 2
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="maquina2"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Máquina 3
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="maquina3"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">M. Prima 1
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="producto1"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">M. Prima 2
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="producto2"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Ubicación
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="ubicacion_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Figura
                            <form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="figura"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Peso (kg)</th>
                        <th class="py-3 border text-center">Diámetro (mm)</th>
                        <th class="py-3 border text-center">Longitud (m)</th>
                        <th class="py-3 border text-center">Fecha Inicio</th>
                        <th class="py-3 border text-center">Fecha Finalización</th>
                        <th class="py-3 border text-center">Estado</th>
                        <th class="py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200"
                            x-data="{ editando: false, elemento: @js($elemento) }">
                            <!-- ID -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.id"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PLANILLA -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('planillas.index', ['id' => $elemento->planilla->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->planilla->codigo_limpio }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.planilla.codigo_limpio"
                                    class="form-input w-full">
                            </td>
                            <!-- USUARIO 1 -->
                            <td class="px-4 py-3 text-center border" x-data="{ usuario1Nombre: elemento.user?.name ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="usuario1Nombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario1Nombre"
                                    class="form-input w-full">
                            </td>

                            <!-- USUARIO 2 -->
                            <td class="px-4 py-3 text-center border" x-data="{ usuario2Nombre: elemento.user2?.name ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="usuario2Nombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="usuario2Nombre"
                                    class="form-input w-full">
                            </td>
                            <!-- ETIQUETA -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->etiquetaRelacion->id ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.etiquetaRelacion.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PAQUETE -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('paquetes.index', ['id' => $elemento->paquete_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->paquete_id ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.paquete_id"
                                    class="form-input w-full">
                            </td>
                            <!-- MAQUINA 1 -->
                            <td class="px-4 py-3 text-center border" x-data="{ maquinaNombre: elemento.maquina?.nombre ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="maquinaNombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="maquinaNombre"
                                    class="form-input w-full">
                            </td>
                            <!-- MAQUINA 2 -->
                            <td class="px-4 py-3 text-center border" x-data="{ maquina2Nombre: elemento.maquina_2?.nombre ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="maquina2Nombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="maquina2Nombre"
                                    class="form-input w-full">
                            </td>

                            <!-- MAQUINA 3 -->
                            <td class="px-4 py-3 text-center border" x-data="{ maquina3Nombre: elemento.maquina_3?.nombre ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="maquina3Nombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="maquina3Nombre"
                                    class="form-input w-full">
                            </td>

                            <!-- PRODUCTO 1 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.producto?.id ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto.id"
                                    class="form-input w-full">
                            </td>
                            <!-- PRODUCTO 2 -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.producto2?.id ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto2.id"
                                    class="form-input w-full">
                            </td>
                            <!-- UBICACION -->
                            <td class="px-4 py-3 text-center border" x-data="{ ubicacionNombre: elemento.ubicacion?.nombre ?? '' }">
                                <template x-if="!editando">
                                    <span x-text="ubicacionNombre || 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="ubicacionNombre"
                                    class="form-input w-full">
                            </td>

                            <!-- FIGURA -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.figura"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.figura"
                                    class="form-input w-full">
                            </td>
                            <!-- PESO_KG -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.peso_kg"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.peso_kg"
                                    class="form-input w-full">
                            </td>
                            <!-- DIAMETRO_MM -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.diametro_mm"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.diametro_mm"
                                    class="form-input w-full">
                            </td>
                            <!-- LONGITUD_M -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.longitud_m"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.longitud_m"
                                    class="form-input w-full">
                            </td>
                            <!-- FECHA_INICIO -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.fecha_inicio ?? 'No asignado'"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="elemento.fecha_inicio"
                                    class="form-input w-full">
                            </td>
                            <!-- FECHA_FINALIZACION -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.fecha_finalizacion ?? 'No asignado'"></span>
                                </template>
                                <input x-show="editando" type="date" x-model="elemento.fecha_finalizacion"
                                    class="form-input w-full">
                            </td>
                            <!-- ESTADO -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.estado"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.estado"
                                    class="form-input w-full">
                            </td>

                            <!-- Botones -->
                            <td class="px-4 py-3 text-center border">
                                <a href="#" class="text-blue-500 hover:underline abrir-modal-dibujo"
                                    data-id="{{ $elemento->id }}" data-dimensiones="{{ $elemento->dimensiones }}"
                                    data-peso="{{ $elemento->peso_kg }}">
                                    Ver
                                </a><br>
                                <button @click.stop="editando = !editando">
                                    <span x-show="!editando">✏️</span>
                                    <span x-show="editando">✖</span>
                                    <span x-show="editando" @click.stop="guardarCambios(elemento)">✅</span>
                                </button><br>
                                <x-boton-eliminar :action="route('elementos.destroy', $elemento->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="25" class="text-center py-4 text-gray-500">No hay elementos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-center">
            {{ $elementos->links() }}
        </div>
        <!-- Modal -->
        <div id="modal-dibujo"
            class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
            <div class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:text-red-900">
                    ✖
                </button>
                <h2 class="text-lg font-semibold mb-3">Elemento #{{ $elemento->id }}</h2>
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>
    </div>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script>
        function guardarCambios(elemento) {
            fetch(`/elementos/${elemento.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(elemento)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Elemento actualizado",
                            text: "El elemento se ha actualizado con éxito.",
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            text: data.message || "Ha ocurrido un error inesperado.",
                            confirmButtonText: "OK"
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar el elemento. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }
    </script>

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
                ctx.font = "12px Arial";
                ctx.fillStyle = "#FF0000";
                ctx.fillText(`${peso}`, canvas.width - 50, canvas.height - 10);
            }

            // Manejar clic en enlaces "Ver"
            document.querySelectorAll(".abrir-modal-dibujo").forEach((link) => {
                link.addEventListener("click", function(event) {
                    event.preventDefault(); // Evita la recarga de la página

                    const dimensiones = this.getAttribute("data-dimensiones");
                    const cantidad = this.getAttribute("data-peso") || N / A;

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
