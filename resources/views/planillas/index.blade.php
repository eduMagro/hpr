<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Planillas') }}

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
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- FORMULARIO DE BÚSQUEDA AVANZADA -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-4">
            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse"
                data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>

            <!-- Formulario de importación -->
            <form method="post" action="{{ route('planillas.import') }}" enctype="multipart/form-data"
                class="form-cargando flex items-center gap-x-2">
                @csrf
                <input type="file" name="file" id="file" class="form-control file:mr-2">

                <button type="submit" class="btn btn-primary btn-cargando flex items-center gap-x-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="texto">IMPORTAR</span>
                </button>
            </form>
        </div>

        <div id="filtrosBusqueda" class="collapse">
            <form method="GET" action="{{ route('planillas.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">

                    <!-- Filtros de texto -->
                    <div class="col-md-6">
                        <input type="text" name="buscar" class="form-control"
                            placeholder="Buscar en código, cliente, obra..." value="{{ request('buscar') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="codigo" class="form-control" placeholder="Código de Planilla"
                            value="{{ request('codigo') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="cod_obra" class="form-control" placeholder="Código de Obra"
                            value="{{ request('cod_obra') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Nombre de Usuario"
                            value="{{ request('name') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="cliente" class="form-control" placeholder="Nombre de Cliente"
                            value="{{ request('cliente') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="nom_obra" class="form-control" placeholder="Nombre de Obra"
                            value="{{ request('nom_obra') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="ensamblado" class="form-control" placeholder="Estado de Ensamblado"
                            value="{{ request('ensamblado') }}">
                    </div>

                    <!-- Filtros de selección -->
                    <div class="col-md-4">
                        <select name="estado" class="form-control">
                            <option value="">Todos los estados de fabricación</option>
                            <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                                Pendiente</option>
                            <option value="fabricando" {{ request('estado') == 'fabricando' ? 'selected' : '' }}>
                                Fabricando</option>
                            <option value="completada" {{ request('estado') == 'completada' ? 'selected' : '' }}>
                                Completada</option>
                            <option value="montaje" {{ request('estado') == 'montaje' ? 'selected' : '' }}>Montaje
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="sort_by" class="form-control">
                            <option value="">Elige un ítem para ordenar</option>
                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>
                                Fecha
                                Creación</option>
                            <option value="codigo" {{ request('sort_by') == 'codigo' ? 'selected' : '' }}>Código
                            </option>
                            <option value="cliente" {{ request('sort_by') == 'cliente' ? 'selected' : '' }}>Cliente
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="order" class="form-control">
                            <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>Ascendente
                            </option>
                            <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>Descendente
                            </option>
                        </select>
                    </div>

                    <!-- Filtros de fechas -->
                    <div class="col-md-4">
                        <label for="fecha_inicio">Desde:</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                            value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="fecha_finalizacion">Hasta:</label>
                        <input type="date" name="fecha_finalizacion" class="form-control"
                            value="{{ request('fecha_finalizacion') }}">
                    </div>

                    <!-- Registros por página -->
                    <div class="col-md-4">
                        <label for="per_page">Mostrar:</label>
                        <select name="per_page" class="form-control">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="col-12 d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('planillas.index') }}" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Resetear Filtros
                        </a>
                    </div>

                </div>
            </form>
        </div>


        <!-- TABLA DE PLANILLAS -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">Código</th>
                        <th class="p-2 border">Código Cliente</th>
                        <th class="p-2 border">Código Obra</th>
                        <th class="p-2 border">Sección</th>
                        <th class="p-2 border">Descripción</th>
                        <th class="p-2 border">Ensamblado</th>
                        <th class="p-2 border">Comentario</th>
                        <th class="p-2 border">Peso Fabricado</th>
                        <th class="p-2 border">Peso Total</th>
                        <th class="p-2 border">Estado</th>
                        <th class="p-2 border">Fecha Inicio</th>
                        <th class="p-2 border">Fecha Finalización</th>
                        <th class="p-2 border">Fecha Importación</th>
                        <th class="p-2 border">Fecha Entrega</th>
                        <th class="p-2 border">Usuario</th>
                        <th class="p-2 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($planillas as $planilla)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            planilla: @js($planilla),
                            original: JSON.parse(JSON.stringify(@js($planilla)))
                        }"
                            @click="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                planilla = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                            @keydown.enter.stop="guardarCambios(planilla); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            <!-- Código -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.codigo_limpio"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.codigo"
                                    class="form-input w-full">
                            </td>

                            <!-- Código Cliente -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('clientes.index', ['id' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->cliente->empresa ?? 'N/A' }}
                                    </a>
                                </template>
                                <select x-show="editando" x-model="planilla.cliente_id" class="form-input w-full">
                                    <option value="">Seleccionar máquina</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}">{{ $cliente->empresa }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Código Obra -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('clientes.show', ['cliente' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->obra->obra ?? 'N/A' }}
                                    </a>
                                </template>
                                <select x-show="editando" x-model="planilla.obra_id" class="form-input w-full">
                                    <option value="">Seleccionar obra</option>
                                    @foreach ($obras as $obra)
                                        <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Sección -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.seccion ?? 'No definida'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.seccion"
                                    class="form-input w-full">
                            </td>

                            <!-- Descripción -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.descripcion ?? 'Sin descripción'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.descripcion"
                                    class="form-input w-full">
                            </td>

                            <!-- Ensamblado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.ensamblado ?? 'Sin datos'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.ensamblado"
                                    class="form-input w-full">
                            </td>

                            <!-- Comentario -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.comentario ?? ' '"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.comentario"
                                    class="form-input w-full">
                            </td>

                            <!-- Peso Fabricado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.suma_peso_completados || 0"></span> kg
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.suma_peso_completados"
                                    class="form-input w-full">
                            </td>

                            <!-- Peso Total -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.peso_total_kg"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.peso_total_kg"
                                    class="form-input w-full">
                            </td>

                            <!-- Estado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.estado.toUpperCase()"></span>
                                </template>
                                <select x-show="editando" x-model="planilla.estado" class="form-select w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </td>

                            <!-- Fecha Inicio -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_inicio"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.fecha_inicio"
                                    class="form-input w-full" placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Fecha Finalización -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_finalizacion"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.fecha_finalizacion"
                                    class="form-input w-full" placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Fecha Importación -->
                            <td class="p-2 text-center border">
                                <span x-text="new Date(planilla.created_at).toLocaleDateString()"></span>
                            </td>

                            <!-- Fecha Entrega -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_estimada_entrega"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.fecha_estimada_entrega"
                                    class="form-input w-full" placeholder="DD/MM/YYYY">
                            </td>

                            <!-- Usuario -->
                            <td class="p-2 text-center border">
                                <span x-text="planilla.user?.name ?? 'Desconocido'"></span>
                            </td>

                            <!-- Acciones (solo se muestran las opciones de ver y eliminar) -->
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="text-green-500 hover:underline">Ver</a>
                                <br>
                                <x-boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-4 text-gray-500">No hay planillas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>


            </table>
        </div>

        <div class="mt-4 flex justify-center">{{ $planillas->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <script>
            function guardarCambios(planilla) {

                fetch(`/planillas/${planilla.id}`, {
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
                            text: "No se pudo actualizar la planilla. Inténtalo nuevamente.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>


</x-app-layout>
