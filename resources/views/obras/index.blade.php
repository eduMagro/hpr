<x-app-layout>
    <x-slot name="title">Obras - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Obras') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <div class="flex flex-wrap gap-4 mb-4">
            <button class="btn btn-secondary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>
            <a href="{{ route('obras.create') }}" class="btn btn-primary">
                Agregar Obra
            </a>
        </div>

        <div id="filtrosBusqueda" class="collapse">
            <form method="GET" action="{{ route('obras.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar en código, cliente, obra..." value="{{ request('buscar') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="cod_obra" class="form-control" placeholder="Código de Obra" value="{{ request('cod_obra') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="cliente" class="form-control" placeholder="Cliente" value="{{ request('cliente') }}">
                    </div>
                    <div class="col-md-12 d-flex justify-content-between">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('obras.index') }}" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Resetear Filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[800px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">Código Obra</th>
                        <th class="px-4 py-3 border">Nombre Obra</th>
                        <th class="px-4 py-3 border">Cliente</th>
                        <th class="px-4 py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($obras as $obra)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                            <td class="px-4 py-3 text-center border">{{ $obra->cod_obra }}</td>
                            <td class="px-4 py-3 text-center border">{{ $obra->obra }}</td>
                            <td class="px-4 py-3 text-center border">{{ $obra->cliente }}</td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('obras.show', $obra->id) }}" class="text-green-500 hover:underline">Ver</a>
                                <a href="{{ route('obras.edit', $obra->id) }}" class="text-blue-500 hover:underline">Editar</a>
                                <x-boton-eliminar :action="route('obras.destroy', $obra->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay obras disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-center">{{ $obras->appends(request()->except('page'))->links() }}</div>
    </div>
</x-app-layout>
