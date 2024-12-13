<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Importar Planillas') }}
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

    <div class="container mx-auto px-4 py-6">
            <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
            <div class="mb-4">
                <a href="{{ route('planillas.create') }}" class="btn btn-primary">
                    Importar Planilla
                </a>
            </div>

       <!-- Grid para tarjetas -->
       <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse ($planillas as $planilla)
            <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                <h2 class="font-semibold text-lg mb-2">{{  $planilla->codigo }}</h2>
                <p class="text-gray-600 mb-2"><strong>Código Obra: </strong> {{ $planilla->cod_obra }}</p>
                <p class="text-gray-600 mb-2"><strong>Cliente: </strong>{{ $planilla->cliente }}</p>
                <p class="text-gray-600 mb-2"><strong>Nombre Obra: </strong>{{ $planilla->nom_obra }}</p>
                <p class="text-gray-600 mb-2"><strong>Sección: </strong>{{ $planilla->seccion }}</p>
                <p class="text-gray-600 mb-2"><strong>Descripción: </strong>{{ $planilla->descripcion }}</p>
                <p class="text-gray-600 mb-2"><strong>Población: </strong>{{ $planilla->poblacion }}</p>
                <p class="text-gray-600 mb-2"><strong>Código Planilla: </strong>{{ $planilla->codigo }}</p>
                <p class="text-gray-600 mb-2"><strong>Peso Total: </strong>{{ $planilla->peso_total }}</p>
                <p class="text-gray-600 mb-2"><strong>Fecha importación: </strong> {{ $planilla->created_at }}</p>
                <hr style="border: 1px solid #ccc; margin: 10px 0;">
                <div class="mt-4 flex justify-between">
                    <!-- Enlace para editar -->
                    <a href="{{ route('planillas.edit', $planilla->id) }}"
                        class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                    <!-- Enlace para ver -->
                    <a href="{{ route('planillas.show', $planilla->id) }}"
                        class="text-blue-500 hover:text-blue-700 text-sm">Ver</a>
                   
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-4">No hay usuarios disponibles.</div>
        @endforelse
    </div>
    <!-- Paginación -->
    @if (isset($planillas) && $planillas instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {{ $planillas->appends(request()->except('page'))->links() }}
    @endif


</x-app-layout>