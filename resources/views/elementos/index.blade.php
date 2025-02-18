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
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
               <table class="w-full border border-gray-300 rounded-lg">
                        <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">ID
						 <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
						</th>
                        <th class="px-4 py-3 border">Planilla
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
                        <th class="px-4 py-3 border">Usuario
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
                        <th class="px-4 py-3 border">Usuario 2</th>
                        <th class="px-4 py-3 border">Etiqueta
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
                        <th class="px-4 py-3 border">Nombre</th>
                        <th class="px-4 py-3 border">Máquina 1
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
                        <th class="px-4 py-3 border">Máquina 2
						  <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="maquina2"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
						</th>
                        <th class="px-4 py-3 border">Máquina 3
						  <form method="GET" action="{{ route('elementos.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="maquina3"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-3 border">M. Prima 1
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
                        <th class="px-4 py-3 border">M. Prima 2
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
                        <th class="px-4 py-3 border">Paquete ID
						
						<form method="GET" action="{{ route('elementos.index') }}"
                                class="mt-2 flex space-x-2">
                                <input type="text" name="paquete_id"
                                    class="w-20 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form></th>
                        <th class="px-4 py-3 border">Ubicación
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
                        <th class="px-4 py-3 border">Figura
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
                        <th class="px-4 py-3 border">Peso (kg)</th>
                        <th class="px-4 py-3 border">Diámetro (mm)</th>
                        <th class="px-4 py-3 border">Longitud (m)</th>
                        <th class="px-4 py-3 border">Fecha Inicio</th>
                        <th class="px-4 py-3 border">Fecha Finalización</th>
                        <th class="px-4 py-3 border">Estado</th>
                        <th class="px-4 py-3 border">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200"  x-data="{ editando: false, planilla: @js($elemento) }">
                            <td class="px-4 py-3 text-center border">{{ $elemento->id }}</td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('planillas.index', ['id' => $elemento->planilla->id]) }}" class="text-blue-500 hover:underline">
                                    {{ $elemento->planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->user2->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion->id]) }}" class="text-blue-500 hover:underline">
                                    {{ $elemento->etiquetaRelacion->id ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('paquetes.index', ['id' => $elemento->paquete_id]) }}" class="text-blue-500 hover:underline">
                                    {{ $elemento->paquete_id ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->nombre }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->maquina->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->maquina_2->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->maquina_3->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->producto->id ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->producto2->id ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->ubicacion->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->figura }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->peso_kg }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->diametro_mm }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->longitud_m }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->fecha_inicio ?? 'No asignado' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->fecha_finalizacion ?? 'No asignado' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $elemento->estado }}</td>
                            <!-- Botones -->
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('elementos.show', $elemento->id) }}"
                                    class="text-green-500 hover:underline">Ver</a><br>
                                    <button @click.stop="editando = !editando">
                                        <span x-show="!editando">✏️</span>
                                        <span x-show="editando" >✖</span>
										 <span x-show="editando" @click.stop="guardarCambios(elemento)" >✅</span>
                                    </button><br>
                                <x-boton-eliminar :action="route('elementos.destroy', $elemento->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="25" class="text-center py-4 text-gray-500">No hay elementos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-center">
            {{ $elementos->links() }}
        </div>
        <!-- Modal -->
<div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
    <div class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative">

        <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:text-red-900">
            ✖
        </button>
		<h2 class="text-lg font-semibold mb-3">Elemento #{{ $elemento->id }}</h2>
        <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
    </div>
</div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
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
                if (!dimensiones) return { longitudes, angulos };
        
                const tokens = dimensiones.split(/\s+/).filter(token => token.length > 0);
        
                tokens.forEach(token => {
                    if (token.includes("d")) {
                        angulos.push(parseFloat(token.replace("d", "")) || 0);
                    } else {
                        longitudes.push(parseFloat(token) || 50);
                    }
                });
        
                return { longitudes, angulos };
            }
        
            // Función para calcular el bounding box de la figura
            function calcularBoundingBox(longitudes, angulos) {
                let x = 0, y = 0, angle = 0;
                let minX = 0, maxX = 0, minY = 0, maxY = 0;
        
                longitudes.forEach((longitud, i) => {
                    x += longitud * Math.cos(angle * Math.PI / 180);
                    y += longitud * Math.sin(angle * Math.PI / 180);
        
                    minX = Math.min(minX, x);
                    maxX = Math.max(maxX, x);
                    minY = Math.min(minY, y);
                    maxY = Math.max(maxY, y);
        
                    angle += angulos[i] || 0;
                });
        
                return { minX, maxX, minY, maxY };
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
                const { longitudes, angulos } = extraerDimensiones(dimensionesStr);
        
                if (longitudes.length === 0) {
                    console.warn("No hay dimensiones válidas para dibujar.");
                    return;
                }
        
                // Calcular bounding box para determinar escala y ajuste
                const { minX, maxX, minY, maxY } = calcularBoundingBox(longitudes, angulos);
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
        
                let x = 0, y = 0, angle = 0;
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
                link.addEventListener("click", function (event) {
                    event.preventDefault(); // Evita la recarga de la página
        
                    const dimensiones = this.getAttribute("data-dimensiones");
                    const cantidad = this.getAttribute("data-peso") || N/A;
        
                    modal.classList.remove("hidden");
                    dibujarFigura("canvas-dibujo", dimensiones, cantidad);
                });
            });
        
            // Cerrar el modal
            cerrarModal.addEventListener("click", function () {
                modal.classList.add("hidden");
            });
        
            // Cerrar modal al hacer clic fuera de él
            modal.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.classList.add("hidden");
                }
            });
        });
        
                </script>
                 <script src="//unpkg.com/alpinejs" defer></script>
                 <script>
                   function guardarCambios(elemento) {
                 fetch(`/elementos/${elemento.id}`, {
                     method: 'PUT',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                     },
                     body: JSON.stringify(planilla)
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         Swal.fire({
                             icon: "success",
                             title: "Planilla actualizada",
                             text: "La planilla se ha actualizado con éxito.",
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
                         text: "No se pudo actualizar la planilla. Inténtalo nuevamente.",
                         confirmButtonText: "OK"
                     });
                 });
             }
             
                 </script>
                 
</x-app-layout>
