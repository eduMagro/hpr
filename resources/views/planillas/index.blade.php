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
        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('planillas.create') }}" class="btn btn-primary">
                Importar Planilla
            </a>
        </div>
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('planillas.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="codigo" class="form-control mb-3" placeholder="Buscar por Código de Planilla"
                value="{{ request('codigo') }}">

            <input type="text" name="name" class="form-control mb-3" placeholder="Buscar por nombre de usuario"
                value="{{ request('name') }}">

            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
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
