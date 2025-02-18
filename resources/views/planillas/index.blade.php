<x-app-layout>
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
            <span class="mx-2">/</span>
            <a href="{{ route('subpaquetes.index') }}" class="text-blue-600">
                {{ __('Subpaquetes') }}
            </a>
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <!-- Enlaces de acción -->
        <div class="flex flex-wrap gap-4 mb-4">
            <a href="{{ route('planillas.create') }}" class="btn btn-primary">
                Importar Planilla
            </a>
            <a href="{{ route('paquetes.index') }}" class="btn btn-primary">
                Ver Paquetes
            </a>
            <a href="{{ route('etiquetas.index') }}" class="btn btn-primary">
                Ver Etiquetas
            </a>
            <a href="{{ route('elementos.index') }}" class="btn btn-primary">
                Ver Elementos
            </a>
        </div>

        <!-- FORMULARIO DE BÚSQUEDA AVANZADA -->
        <button class="btn btn-secondary mb-3" type="button" data-bs-toggle="collapse"
            data-bs-target="#filtrosBusqueda">
            🔍 Filtros Avanzados
        </button>

        <div id="filtrosBusqueda" class="collapse">
            <form method="GET" action="{{ route('planillas.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">
                    <!-- Búsqueda global -->
                    <div class="col-md-6">
                        <input type="text" name="buscar" class="form-control"
                            placeholder="Buscar en código, cliente, obra..." value="{{ request('buscar') }}">
                    </div>
                    <!-- Código de Planilla -->
                    <div class="col-md-3">
                        <input type="text" name="codigo" class="form-control" placeholder="Código de Planilla"
                            value="{{ request('codigo') }}">
                    </div>
                    <!-- Código de Obra -->
                    <div class="col-md-3">
                        <input type="text" name="cod_obra" class="form-control" placeholder="Código de Obra"
                            value="{{ request('cod_obra') }}">
                    </div>
                    <!-- Nombre de Usuario -->
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Nombre de Usuario"
                            value="{{ request('name') }}">
                    </div>
                    <!-- Cliente -->
                    <div class="col-md-4">
                        <input type="text" name="cliente" class="form-control" placeholder="Nombre de Cliente"
                            value="{{ request('cliente') }}">
                    </div>
                    <!-- Nombre de Obra -->
                    <div class="col-md-4">
                        <input type="text" name="nom_obra" class="form-control" placeholder="Nombre de Obra"
                            value="{{ request('nom_obra') }}">
                    </div>
                    <!-- Estado Ensamblado -->
                    <div class="col-md-4">
                        <input type="text" name="ensamblado" class="form-control" placeholder="Estado de Ensamblado"
                            value="{{ request('ensamblado') }}">
                    </div>
                    <!-- Rango de Fechas -->
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
                    <!-- Ordenar por -->
                    <div class="col-md-4">
                        <label for="sort_by">Ordenar por:</label>
                        <select name="sort_by" class="form-control">
                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Fecha
                                Creación</option>
                            <option value="codigo" {{ request('sort_by') == 'codigo' ? 'selected' : '' }}>Código
                            </option>
                            <option value="cliente" {{ request('sort_by') == 'cliente' ? 'selected' : '' }}>Cliente
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="order">Orden:</label>
                        <select name="order" class="form-control">
                            <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>Ascendente
                            </option>
                            <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>Descendente
                            </option>
                        </select>
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
                    <div class="col-12 d-flex justify-content-between">
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
            <table class="w-full min-w-[1200px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">Código</th>
                        <th class="px-4 py-3 border">Código Cliente</th>
                        <th class="px-4 py-3 border">Cliente</th>
                        <th class="px-4 py-3 border">Código Obra</th>
                        <th class="px-4 py-3 border">Obra</th>
                        <th class="px-4 py-3 border">Sección</th>
                        <th class="px-4 py-3 border">Descripción</th>
                        <th class="px-4 py-3 border">Ensamblado</th>
                        <th class="px-4 py-3 border">Peso Total</th>
                        <th class="px-4 py-3 border">Estado</th>
                        <th class="px-4 py-3 border">Fecha Inicio</th>
                        <th class="px-4 py-3 border">Fecha Finalización</th>
                        <th class="px-4 py-3 border">Fecha Importación</th>
                        <th class="px-4 py-3 border">Usuario</th>
                        <th class="px-4 py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($planillas as $planilla)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                            x-data="{ editando: false, planilla: @js($planilla) }">
                
                            <!-- Código -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.codigo_limpio"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.codigo_limpio" class="form-input w-full">
                            </td>
                
                            <!-- Código Cliente -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.cod_cliente ?? 'No asignado'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.cod_cliente" class="form-input w-full">
                            </td>
                
                            <!-- Cliente -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.cliente ?? 'Desconocido'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.cliente" class="form-input w-full">
                            </td>
                
                            <!-- Código Obra -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.cod_obra ?? 'No asignado'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.cod_obra" class="form-input w-full">
                            </td>
                
                            <!-- Obra -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.nom_obra ?? 'No especificado'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.nom_obra" class="form-input w-full">
                            </td>
                
                            <!-- Sección -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.seccion ?? 'No definida'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.seccion" class="form-input w-full">
                            </td>
                
                            <!-- Descripción -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.descripcion ?? 'Sin descripción'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.descripcion" class="form-input w-full">
                            </td>
                
                            <!-- Estado Ensamblado -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.ensamblado ?? 'Sin datos'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.ensamblado" class="form-input w-full">
                            </td>
                
                            <!-- Peso Total -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.peso_total_kg"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.peso_total_kg" class="form-input w-full">
                            </td>
                
                            <!-- Estado -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.estado"></span>
                                </template>
                                <select x-show="editando" x-model="planilla.estado" class="form-select w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </td>
                
                            <!-- Fecha Inicio -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_inicio"></span>
                                </template>
                                <input x-show="editando" type="datetime" x-model="planilla.fecha_inicio" class="form-input w-full">
                            </td>
                
                            <!-- Fecha Finalización -->
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_finalizacion"></span>
                                </template>
                                <input x-show="editando" type="datetime" x-model="planilla.fecha_finalizacion" class="form-input w-full">
                            </td>
                
                            <!-- Fecha Importación -->
                            <td class="px-4 py-3 text-center border">
                                <span x-text="new Date(planilla.created_at).toLocaleDateString()"></span>
                            </td>
                
                            <!-- Usuario -->
                            <td class="px-4 py-3 text-center border">
                                <span x-text="planilla.user?.name ?? 'Desconocido'"></span>
                            </td>
                
                            <!-- Botones -->
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="text-green-500 hover:underline">Ver</a><br>
                                    <button @click.stop="editando = !editando">
                                        <span x-show="!editando">✏️</span>
                                        <span x-show="editando" >✖</span>
										 <span x-show="editando" @click.stop="guardarCambios(planilla)" >✅</span>
                                    </button><br>
                                <x-boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="15" class="text-center py-4 text-gray-500">No hay planillas disponibles.</td></tr>
                    @endforelse
                </tbody>
                
                
            </table>
        </div>

        <div class="mt-4 flex justify-center">{{ $planillas->appends(request()->except('page'))->links() }}</div>
    </div>
    <script src="//unpkg.com/alpinejs" defer></script>
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
