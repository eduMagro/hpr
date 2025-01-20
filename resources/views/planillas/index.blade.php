<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planillas') }}
        </h2>
    </x-slot>
    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
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
        <!-- Botón para crear una nueva entrada -->
        <div class="mb-4">
            <a href="{{ route('planillas.create') }}" class="btn btn-primary">
                Importar Planilla
            </a>
        </div>

        <!-- FORMULARIO DE BÚSQUEDA -->
        <form method="GET" action="{{ route('planillas.index') }}" class="mt-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="text" name="codigo" class="form-control" placeholder="Código de Planilla"
                        value="{{ request('codigo') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="text" name="name" class="form-control" placeholder="Nombre de Usuario"
                        value="{{ request('name') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="text" name="cliente" class="form-control" placeholder="Nombre de Cliente"
                        value="{{ request('cliente') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="text" name="cod_obra" class="form-control" placeholder="Código de Obra"
                        value="{{ request('cod_obra') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="text" name="nom_obra" class="form-control" placeholder="Nombre de Obra"
                        value="{{ request('nom_obra') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="text" name="ensamblado" class="form-control" placeholder="Estado de Ensamblado"
                        value="{{ request('ensamblado') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12">
                    <input type="date" name="created_at" class="form-control" placeholder="Fecha de Creación"
                        value="{{ request('created_at') }}">
                </div>
                <div class="col-md-4 col-sm-6 col-12 d-flex align-items-center">
                    <button type="submit" class="btn btn-info w-100">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
        </form>

        <!-- Grid para tarjetas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
                <div class="col-span-3 text-center py-4">No hay planillas importadas.</div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if (isset($planillas) && $planillas instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $planillas->appends(request()->except('page'))->links() }}
        @endif


</x-app-layout>
