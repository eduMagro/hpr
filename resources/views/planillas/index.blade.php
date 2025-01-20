<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planillas') }}
        </h2>
    </x-slot>

    <!-- Mostrar mensajes de error y éxito -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('abort'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: "{{ session('abort') }}",
            });
        </script>
    @endif

    <div class="container mx-auto px-3 py-6">
        <!-- Botón para crear nueva planilla -->
        <div class="mb-4">
            <a href="{{ route('planillas.create') }}" class="btn btn-primary">
                Importar Planilla
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

        <!-- Mostrar planillas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
            @forelse ($planillas as $planilla)
                <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <h2 class="font-semibold text-lg mb-2 text-center">{{ $planilla->codigo_limpio }}</h2>
                    <p class="text-gray-600 mb-2"><strong>Código Obra:</strong>
                        {{ $planilla->cod_obra ?? 'No asignado' }}</p>
                    <p class="text-gray-600 mb-2"><strong>Cliente:</strong> {{ $planilla->cliente ?? 'Desconocido' }}
                    </p>
                    <p class="text-gray-600 mb-2"><strong>Nombre Obra:</strong>
                        {{ $planilla->nom_obra ?? 'No especificado' }}</p>
                    <p class="text-gray-600 mb-2"><strong>Sección:</strong> {{ $planilla->seccion ?? 'No definida' }}
                    </p>
                    <p class="text-gray-600 mb-2"><strong>Descripción:</strong>
                        {{ $planilla->descripcion ?? 'Sin descripción' }}</p>
                    <p class="text-gray-600 mb-2"><strong>Ensamblado:</strong>
                        {{ $planilla->ensamblado ?? 'Sin datos' }}</p>
                    <p class="text-gray-600 mb-2"><strong>Peso Total:</strong> {{ $planilla->peso_total_kg }}</p>

                    <p class="text-gray-600 mb-2">
                        <strong>Tiempo Estimado Finalización:</strong>
                        {{ $planilla->tiempo_estimado_finalizacion_formato }}
                    </p>

                    <p class="text-gray-600 mb-2"><strong>Fecha Inicio:</strong>
                        {{ $planilla->fecha_inicio_formato }}
                    </p>
                    <p class="text-gray-600 mb-2"><strong>Fecha Finalización:</strong>
                        {{ $planilla->fecha_finalizacion_formato }}
                    </p>
                    <p class="text-gray-600 mb-2"><strong>Fecha Importación:</strong>
                        {{ $planilla->created_at->format('d/m/Y H:i') }}</p>

                    <hr style="border: 1px solid #ccc; margin: 10px 0;">

                    <p><small><strong>Usuario: </strong> {{ $planilla->user->name ?? 'Usuario desconocido' }} </small>
                    </p>
                    <hr style="border: 1px solid #ccc; margin: 10px 0;">

                    <div class="mt-4 flex justify-between items-center">
                        <x-boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                        <!-- Enlace para editar -->
                        <a href="{{ route('planillas.edit', $planilla->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                        <!-- Enlace para ver -->
                        <a href="{{ route('elementosEtiquetas', $planilla->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-4">No hay planillas disponibles.</div>
            @endforelse
        </div>

        <!-- Paginación -->
        <div class="flex justify-center mt-4">
            {{ $planillas->appends(request()->except('page'))->links('vendor.pagination.tailwind') }}
        </div>

    </div>
</x-app-layout>
