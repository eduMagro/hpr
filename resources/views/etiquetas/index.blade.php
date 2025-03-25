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
            </h2>

        </x-slot>

        <div class="w-full p-4 sm:p-2">
            <!-- Formulario de Filtros -->
            <form method="GET" action="{{ route('etiquetas.index') }}"
                class="mb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-8 gap-2 md:gap-4 p-2">

                <!-- Estado -->
                <div class="flex flex-col">
                    <label for="estado" class="text-sm font-medium text-gray-700">Estado</label>
                    <select name="estado" id="estado"
                        class="w-full md:w-40 px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente
                        </option>
                        <option value="fabricando" {{ request('estado') == 'fabricando' ? 'selected' : '' }}>Fabricando
                        </option>
                        <option value="completada" {{ request('estado') == 'completada' ? 'selected' : '' }}>Completada
                        </option>
                    </select>
                </div>

                <!-- Botón Filtrar -->
                <div class="flex flex-col justify-end">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded w-full md:w-auto">
                        Filtrar
                    </button>
                </div>
            </form>

            <!-- Tabla con formularios de búsqueda -->
            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-left text-xs text-center uppercase">
                            <th class="p-2 border">ID
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="id"
                                        class="w-full p-2 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="p-2 border">Planilla
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="codigo_planilla"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="p-2 border">Ensamblador 1</th>
                            <th class="p-2 border">Ensamblador 2</th>
                            <th class="p-2 border">Soldador 1</th>
                            <th class="p-2 border">Soldador 2</th>
                            {{-- <th class="px-4 py-3 border">Paquete</th> --}}
                            <th class="p-2 border">Número de Etiqueta
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="numero_etiqueta"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            <th class="p-2 border">Nombre
                                <form method="GET" action="{{ route('etiquetas.index') }}" class="mt-2">
                                    <input type="text" name="nombre"
                                        class="w-full px-2 py-1 text-gray-900 rounded-md border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                        placeholder="Buscar">
                                </form>
                            </th>
                            {{-- <th class="px-4 py-3 border">Ubicación</th> --}}
                            <th class="p-2 border">Inicio Fabricación</th>
                            <th class="p-2 border">Final Fabricación</th>
                            <th class="p-2 border">Inicio Ensamblado</th>
                            <th class="p-2 border">Peso (kg)</th>
                            <th class="p-2 border">Final Ensamblado</th>
                            <th class="p-2 border">Inicio Soldadura</th>
                            <th class="p-2 border">Final Soldadura</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse ($etiquetas as $etiqueta)
                            <tr tabindex="0" x-data="{ editando: false, etiqueta: @js($etiqueta) }" @click="if (!editando) { editando = true }"
                                @keydown.enter.stop="guardarCambios(etiqueta); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">
                                <td class="p-2 text-center border">{{ $etiqueta->id }}</td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->planilla_id)
                                        <a href="{{ route('planillas.index', ['planilla_id' => $etiqueta->planilla_id]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->planilla->codigo_limpio }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador1]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensambladorRelacion->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador2]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador2Relacion->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 text-center border">{{ $etiqueta->soldador1 ?? 'N/A' }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->soldador2 ?? 'N/A' }}</td>
                                {{-- <td class="px-4 py-3 text-center border">{{ $etiqueta->paquete_id ?? 'N/A' }}</td> --}}
                                <td class="p-2 text-center border">{{ $etiqueta->numero_etiqueta }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->nombre }}</td>
                                {{-- <td class="px-4 py-3 text-center border">{{ $etiqueta->ubicacion->nombre ?? 'N/A' }}
                                </td> --}}
                                <td class="p-2 text-center border">{{ $etiqueta->peso_kg }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->fecha_inicio ?? 'N/A' }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->fecha_finalizacion ?? 'N/A' }}
                                <td class="p-2 text-center border">
                                    {{ $etiqueta->fecha_inicio_ensamblado ?? 'N/A' }}</td>
                                <td class="p-2 text-center border">
                                    {{ $etiqueta->fecha_finalizacion_ensamblado ?? 'N/A' }}
                                <td class="p-2 text-center border">
                                    {{ $etiqueta->fecha_inicio_soldadura ?? 'N/A' }}</td>
                                <td class="p-2 text-center border">
                                    {{ $etiqueta->fecha_finalizacion_soldadura ?? 'N/A' }}
                                </td>
                                <td class="p-2 text-center border">{{ ucfirst($etiqueta->estado) }}</td>
                                <td class="p-2 text-center border flex flex-col gap-2">

                                    <button onclick="mostrarDibujo({{ $etiqueta->id }})"
                                        class="text-blue-500 hover:underline">
                                        Ver
                                    </button>
                                    <button @click.stop="editando = !editando">
                                        <span x-show="!editando">✏️</span>
                                        <span x-show="editando" class="mr-2">✖</span>
                                        <span x-show="editando" @click.stop="guardarCambios(etiqueta)">✅</span>
                                    </button>
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
                {{ $etiquetas->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
            </div>
            <!-- Modal con Canvas para Dibujar las Dimensiones -->
            <div id="modal-dibujo"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
                <div
                    class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
                    <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                        ✖
                    </button>

                    <h2 class="text-xl font-semibold mb-4 text-center">Elementos de la Etiqueta</h2>
                    <!-- Contenedor desplazable -->
                    <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                        <canvas id="canvas-dibujo" width="800" height="600"
                            class="border max-w-full h-auto"></canvas>
                    </div>
                </div>
            </div>
            <script src="{{ asset('js/etiquetasJs/figurasEtiqueta.js') }}" defer></script>
            <script>
                window.etiquetasConElementos = @json($etiquetasJson);
            </script>
            <script>
                function guardarCambios(etiqueta) {

                    fetch(`/etiquetas/${etiqueta.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(etiqueta)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Etiqueta actualizada",
                                    text: data.message + ' ' + data.data,
                                    showConfirmButton: true
                                }).then(() => {
                                    window.location.reload(); // Recarga la página tras el mensaje
                                });
                            } else {
                                let errorMsg =
                                    data.message || "Ha ocurrido un error inesperado.";
                                // Si existen errores de validación, concatenarlos
                                if (data.errors) {
                                    errorMsg = Object.values(data.errors).flat().join(
                                        "<br>"); // O puedes usar '\n' para saltos de línea
                                }
                                Swal.fire({
                                    icon: "error",
                                    title: "Error al actualizar",
                                    html: errorMsg,
                                    confirmButtonText: "OK",
                                    showCancelButton: true,
                                    cancelButtonText: "Reportar Error"
                                }).then((result) => {
                                    if (result.dismiss === Swal.DismissReason.cancel) {
                                        notificarProgramador(errorMsg);
                                    }
                                }).then(() => {
                                    window.location.reload(); // Recarga la página tras el mensaje
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: "error",
                                title: "Error de conexión",
                                text: "No se pudo actualizar la etiqueta. Inténtalo nuevamente.",
                                confirmButtonText: "OK"
                            });
                        });
                }
            </script>
    </x-app-layout>
