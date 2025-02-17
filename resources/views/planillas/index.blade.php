<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planillas') }}
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
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="w-full min-w-[1200px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">

                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Código</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Código Cliente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Cliente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Código Obra</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Obra</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Sección</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Descripción</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Ensamblado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Peso Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Tiempo Estimado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Estado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Fecha Inicio</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Fecha Finalización</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Fecha Importación</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Usuario</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($planillas as $planilla)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $planilla->codigo_limpio }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->cod_cliente ?? 'No asignado' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->cliente ?? 'Desconocido' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->cod_obra ?? 'No asignado' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->nom_obra ?? 'No especificado' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->seccion ?? 'No definida' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->descripcion ?? 'Sin descripción' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->ensamblado ?? 'Sin datos' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->peso_total_kg }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->tiempo_estimado_finalizacion_formato }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->estado }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->fecha_inicio }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->fecha_finalizacion }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $planilla->user->name ?? 'Usuario desconocido' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex space-x-2 justify-center">
                                    <x-boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                                    <a href="{{ route('planillas.edit', $planilla->id) }}"
                                        class="text-blue-600 hover:text-blue-900">Editar</a>
                                    <a href="{{ route('elementos.show', $planilla->id) }}"
                                        class="text-green-600 hover:text-green-900">Ver</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                No hay planillas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="flex justify-center mt-4 ml-4">
            {{ $planillas->appends(request()->except('page'))->links() }}
        </div>

    </div>
</x-app-layout>
