<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista de Elementos') }}
        </h2>
    </x-slot>

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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <div class="container-fluid px-4 py-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Listado de Elementos</h3>
            <a href="{{ route('elementos.create') }}" class="btn btn-primary">Nuevo Elemento</a>
        </div>

        <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
            <table class="table table-striped table-bordered">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th>ID</th>
                        <th>Planilla</th>
                        <th>Usuario</th>
                        <th>Usuario 2</th>
                        <th>Etiqueta</th>
                        <th>Nombre</th>
                        <th>Máquina</th>
                        <th>Producto</th>
                        <th>Figura</th>
                        <th>Fila</th>
                        <th>Descripción Fila</th>
                        <th>Marca</th>
                        <th>Etiqueta</th>
                        <th>Barras</th>
                        <th>Dobles Barra</th>
                        <th>Peso (kg)</th>
                        <th>Dimensiones</th>
                        <th>Diámetro (mm)</th>
                        <th>Longitud (cm)</th>
                        <th>Longitud (m)</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Finalización</th>
                        <th>Tiempo Fabricación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($elementos as $elemento)
                        <tr>
                            <td>{{ $elemento->id }}</td>
                            <td>{{ $elemento->planilla->id ?? 'N/A' }}</td>
                            <td>{{ $elemento->user->name ?? 'N/A' }}</td>
                            <td>{{ $elemento->user2->name ?? 'N/A' }}</td>
                            <td>{{ $elemento->etiquetaRelacion->nombre ?? 'N/A' }}</td>
                            <td>{{ $elemento->nombre }}</td>
                            <td>{{ $elemento->maquina->nombre ?? 'N/A' }}</td>
                            <td>{{ $elemento->producto->nombre ?? 'N/A' }}</td>
                            <td>{{ $elemento->figura }}</td>
                            <td>{{ $elemento->fila }}</td>
                            <td>{{ $elemento->descripcion_fila }}</td>
                            <td>{{ $elemento->marca }}</td>
                            <td>{{ $elemento->etiqueta }}</td>
                            <td>{{ $elemento->barras }}</td>
                            <td>{{ $elemento->dobles_barra }}</td>
                            <td>{{ $elemento->peso_kg }}</td>
                            <td>{{ $elemento->dimensiones }}</td>
                            <td>{{ $elemento->diametro_mm }}</td>
                            <td>{{ $elemento->longitud_cm }}</td>
                            <td>{{ $elemento->longitud_m }}</td>
                            <td>{{ $elemento->fecha_inicio ?? 'No asignado' }}</td>
                            <td>{{ $elemento->fecha_finalizacion ?? 'No asignado' }}</td>
                            <td>{{ $elemento->tiempo_fabricacion_formato }}</td>
                            <td>{{ $elemento->estado }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('elementos.show', $elemento->id) }}"
                                    class="btn btn-info btn-sm">Ver</a>
                                <a href="{{ route('elementos.edit', $elemento->id) }}"
                                    class="btn btn-warning btn-sm">Editar</a>
                                <form action="{{ route('elementos.destroy', $elemento->id) }}" method="POST"
                                    class="d-inline-block"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este elemento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="25" class="text-center">No hay elementos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-center">
            {{ $elementos->links() }}
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
