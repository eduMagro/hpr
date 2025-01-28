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


    <div class="container mx-auto px-4 py-6">


        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Etiqueta</th>
                    <th>Máquina</th>
                    <th>Producto</th>
                    <th>Peso (kg)</th>
                    <th>Fecha Inicio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($elementos as $elemento)
                    <tr>
                        <td>{{ $elemento->id }}</td>
                        <td>{{ $elemento->nombre }}</td>
                        <td>{{ $elemento->etiquetaRelacion->nombre ?? 'N/A' }}</td>
                        <td>{{ $elemento->maquina->nombre ?? 'N/A' }}</td>
                        <td>{{ $elemento->producto->nombre ?? 'N/A' }}</td>
                        <td>{{ $elemento->peso_kg }}</td>
                        <td>{{ $elemento->fecha_inicio ?? 'No asignado' }}</td>
                        <td>
                            <a href="{{ route('elementos.show', $elemento->id) }}" class="btn btn-info btn-sm">Ver</a>
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
                        <td colspan="8" class="text-center">No hay elementos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="d-flex justify-content-center">
            {{ $elementos->links() }}
        </div>
    </div>
</x-app-layout>
