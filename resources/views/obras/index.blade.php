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
                    <div class="col-md-4">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar en código, cliente, obra..." value="{{ request('buscar') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="cod_obra" class="form-control" placeholder="Código de Obra" value="{{ request('cod_obra') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="cliente" class="form-control" placeholder="Cliente" value="{{ request('cliente') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="cod_cliente" class="form-control" placeholder="Código Cliente" value="{{ request('cod_cliente') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="latitud" class="form-control" placeholder="Latitud" value="{{ request('latitud') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="longitud" class="form-control" placeholder="Longitud" value="{{ request('longitud') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="distancia" class="form-control" placeholder="Radio" value="{{ request('distancia') }}">
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
                        <th class="px-4 py-3 border">Nombre Obra</th>
                        <th class="px-4 py-3 border">Código Obra</th>
                        <th class="px-4 py-3 border">Cliente</th>
                        <th class="px-4 py-3 border">Código Cliente</th>
                        <th class="px-4 py-3 border">Latitud</th>
                        <th class="px-4 py-3 border">Longitud</th>
                        <th class="px-4 py-3 border">Radio</th>
                        <th class="px-4 py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($obras as $obra)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer" x-data="{ editando: false, obra: @js($obra) }">
                          
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.obra"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.obra" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.cod_obra"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.cod_obra" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.cliente"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.cliente" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.cod_cliente"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.cod_cliente" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.latitud"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.latitud" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.longitud"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.longitud" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.distancia"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.distancia" class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <a href="https://www.google.com/maps?q={{ $obra->latitud }},{{ $obra->longitud }}" 
                                    target="_blank" 
                                    class="text-blue-500 hover:underline">
                                    Ver
                                 </a>
                                <button @click.stop="editando = !editando">
                                    <span x-show="!editando">✏️</span>
                                    <span x-show="editando">✖</span>
                                    <span x-show="editando" @click.stop="guardarCambios(obra)">✅</span>
                                </button>
                                <x-boton-eliminar :action="route('obras.destroy', $obra->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-gray-500">No hay obras disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-center">{{ $obras->appends(request()->except('page'))->links() }}</div>
    </div>

    <script src="//unpkg.com/alpinejs" defer></script>
    <script>
        function guardarCambios(obra) {
            fetch(`/obras/${obra.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(obra)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Obra actualizada",
                        text: "La obra se ha actualizado con éxito.",
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
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
                Swal.fire({
                    icon: "error",
                    title: "Error de conexión",
                    text: "No se pudo actualizar la obra. Inténtalo nuevamente.",
                    confirmButtonText: "OK"
                });
            });
        }
    </script>
</x-app-layout>
