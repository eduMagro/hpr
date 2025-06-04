    <x-app-layout>
        <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>
        @php
            $rutaActual = request()->route()->getName();
        @endphp

        @if (auth()->user()->rol !== 'operario')
            <div class="w-full" x-data="{ open: false }">
                <!-- Menú móvil -->
                <div class="sm:hidden relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                        Opciones
                    </button>

                    <div x-show="open" x-transition @click.away="open = false"
                        class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                        x-cloak>

                        <a href="{{ route('planillas.index') }}"
                            class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'planillas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                            📄 Planillas
                        </a>

                        <a href="{{ route('paquetes.index') }}"
                            class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'paquetes.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                            📦 Paquetes
                        </a>

                        <a href="{{ route('etiquetas.index') }}"
                            class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'etiquetas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                            🏷️ Etiquetas
                        </a>

                        <a href="{{ route('elementos.index') }}"
                            class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'elementos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                            🔩 Elementos
                        </a>
                    </div>
                </div>

                <!-- Menú escritorio -->
                <div class="hidden sm:flex sm:mt-0 w-full">
                    <a href="{{ route('planillas.index') }}"
                        class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ $rutaActual === 'planillas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                        📄 Planillas
                    </a>

                    <a href="{{ route('paquetes.index') }}"
                        class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'paquetes.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                        📦 Paquetes
                    </a>

                    <a href="{{ route('etiquetas.index') }}"
                        class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'etiquetas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                        🏷️ Etiquetas
                    </a>

                    <a href="{{ route('elementos.index') }}"
                        class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ $rutaActual === 'elementos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                        🔩 Elementos
                    </a>
                </div>
            </div>
        @endif


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
                            <th class="p-2 border">SubEtiqueta
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
                            <th class="p-2 border">Op 1</th>
                            <th class="p-2 border">Op 2</th>
                            <th class="p-2 border">Ens 1</th>
                            <th class="p-2 border">Ens 2</th>
                            <th class="p-2 border">Sol 1</th>
                            <th class="p-2 border">Sol 2</th>
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
                            <th class="p-2 border">Peso (kg)</th>
                            {{-- <th class="px-4 py-3 border">Ubicación</th> --}}
                            <th class="p-2 border">Inicio Fabricación</th>
                            <th class="p-2 border">Final Fabricación</th>
                            <th class="p-2 border">Inicio Ensamblado</th>

                            <th class="p-2 border">Final Ensamblado</th>
                            <th class="p-2 border">Inicio Soldadura</th>
                            <th class="p-2 border">Final Soldadura</th>
                            <th class="p-2 border">Estado</th>
                            <th class="p-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse ($etiquetas as $etiqueta)
                            <tr tabindex="0" x-data="{
                                editando: false,
                                etiqueta: @js($etiqueta),
                                original: JSON.parse(JSON.stringify(@js($etiqueta)))
                            }"
                                @click="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                etiqueta = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                                @keydown.enter.stop="guardarCambios(etiqueta); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">
                                <!-- ID (no editable) -->
                                <td class="p-2 text-center border">{{ $etiqueta->id }}</td>
                                <td class="p-2 text-center border">{{ $etiqueta->etiqueta_sub_id }}</td>

                                <!-- Planilla (no editable) -->
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

                                <!-- Opeario 1 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->operario1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->operario1]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->operario1->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Operario 2 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->opeario2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->opeario2]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->opeario2->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <!-- Ensamblador 1 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador1)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador1]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador1->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Ensamblador 2 (no editable) -->
                                <td class="p-2 text-center border">
                                    @if ($etiqueta->ensamblador2)
                                        <a href="{{ route('users.index', ['users_id' => $etiqueta->ensamblador2]) }}"
                                            class="text-blue-500 hover:underline">
                                            {{ $etiqueta->ensamblador2->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <!-- Soldador1 (no editable) -->
                                <td class="p-2 text-center border">{{ $etiqueta->soldador1->name ?? 'N/A' }}</td>

                                <!-- Soldador2 (no editable) -->
                                <td class="p-2 text-center border">{{ $etiqueta->soldador2->name ?? 'N/A' }}</td>

                                <!-- Número de Etiqueta (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.numero_etiqueta"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.numero_etiqueta"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Nombre (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.nombre"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.nombre"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Peso (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.peso"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="etiqueta.peso"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Inicio (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Finalización (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_finalizacion"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Inicio Ensamblado (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio_ensamblado"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_inicio_ensamblado"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Finalización Ensamblado (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion_ensamblado"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_finalizacion_ensamblado"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Inicio Soldadura (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_inicio_soldadura"></span>
                                    </template>
                                    <input x-show="editando" type="date" x-model="etiqueta.fecha_inicio_soldadura"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Fecha Finalización Soldadura (editable) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="etiqueta.fecha_finalizacion_soldadura"></span>
                                    </template>
                                    <input x-show="editando" type="date"
                                        x-model="etiqueta.fecha_finalizacion_soldadura"
                                        class="form-control form-control-sm" @click.stop>
                                </td>

                                <!-- Estado (editable mediante select) -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span
                                            x-text="etiqueta.estado ? etiqueta.estado.charAt(0).toUpperCase() + etiqueta.estado.slice(1) : ''"></span>
                                    </template>
                                    <select x-show="editando" x-model="etiqueta.estado" class="form-select w-full"
                                        @click.stop>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="fabricando">Fabricando</option>
                                        <option value="completada">Completada</option>
                                    </select>
                                </td>

                                <!-- Acciones (no editable) -->
                                <td class="p-2 text-center border flex flex-col gap-2">
                                    <button onclick="mostrarDibujo({{ $etiqueta->id }})"
                                        class="text-blue-500 hover:underline">
                                        Ver
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

                                window.location.reload(); // Recarga la página tras el mensaje
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
