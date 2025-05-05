<x-app-layout>
    <x-slot name="title">Obras - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Obras') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <div class="flex flex-wrap gap-4 mb-4">
            <button class="btn btn-secondary mb-3" type="button" data-bs-toggle="collapse"
                data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>
            <a href="{{ route('obras.create') }}" class="btn btn-primary">
                Agregar Obra
            </a>
        </div>
        @php
            $filtrosActivos = [];

            if (request('buscar')) {
                $filtrosActivos[] = 'Contiene <strong>“' . request('buscar') . '”</strong>';
            }
            if (request('cod_obra')) {
                $filtrosActivos[] = 'Código de obra: <strong>' . request('cod_obra') . '</strong>';
            }
            if (request('cliente')) {
                $filtrosActivos[] = 'Cliente: <strong>' . request('cliente') . '</strong>';
            }
            if (request('cod_cliente')) {
                $filtrosActivos[] = 'Código cliente: <strong>' . request('cod_cliente') . '</strong>';
            }
            if (request('completada') !== null) {
                $estado = request('completada') == '1' ? 'Sí' : 'No';
                $filtrosActivos[] = 'Completada: <strong>' . $estado . '</strong>';
            }

            if (request('sort')) {
                $sorts = [
                    'obra' => 'Nombre de Obra',
                    'cod_obra' => 'Código de Obra',
                    'cliente' => 'Cliente',
                    'cod_cliente' => 'Código Cliente',
                    'latitud' => 'Latitud',
                    'longitud' => 'Longitud',
                    'distancia' => 'Radio',
                ];
                $orden = request('order') == 'desc' ? 'descendente' : 'ascendente';
                $filtrosActivos[] =
                    'Ordenado por <strong>' .
                    ($sorts[request('sort')] ?? request('sort')) .
                    '</strong> en orden <strong>' .
                    $orden .
                    '</strong>';
            }

            if (request('per_page')) {
                $filtrosActivos[] = 'Mostrando <strong>' . request('per_page') . '</strong> registros por página';
            }
        @endphp

        @if (count($filtrosActivos))
            <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
            </div>
        @endif

        @php
            function ordenarColumna($columna, $titulo)
            {
                $currentSort = request('sort');
                $currentOrder = request('order');
                $isSorted = $currentSort === $columna;
                $nextOrder = $isSorted && $currentOrder === 'asc' ? 'desc' : 'asc';
                $icon = $isSorted ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down') : 'fas fa-sort';
                $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

                return '<a href="' .
                    $url .
                    '" class="text-white text-decoration-none">' .
                    $titulo .
                    ' <i class="' .
                    $icon .
                    '"></i></a>';
            }
        @endphp

        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[800px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-xs uppercase text-center">
                    <tr>
                        <th class="p-2 border">{!! ordenarColumna('obra', 'Nombre Obra') !!}</th>
                        <th class="p-2 border">{!! ordenarColumna('cod_obra', 'Código Obra') !!}</th>
                        <th class="p-2 border">{!! ordenarColumna('cliente', 'Cliente') !!}</th>
                        <th class="p-2 border">{!! ordenarColumna('cod_cliente', 'Código Cliente') !!}</th>
                        <th class="p-2 border">{!! ordenarColumna('latitud', 'Latitud') !!}</th>
                        <th class="p-2 border">{!! ordenarColumna('longitud', 'Longitud') !!}</th>
                        <th class="p-2 border">{!! ordenarColumna('distancia', 'Radio') !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                    <tr>
                        <form method="GET" action="{{ route('obras.index') }}">
                            <th class="p-1 border">
                                <input type="text" name="obra" value="{{ request('obra') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="cod_obra" value="{{ request('cod_obra') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="cliente" value="{{ request('cliente') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="cod_cliente" value="{{ request('cod_cliente') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="latitud" value="{{ request('latitud') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="longitud" value="{{ request('longitud') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="distancia" value="{{ request('distancia') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border text-center">
                                <button type="submit" class="btn btn-sm btn-info px-2"><i
                                        class="fas fa-search"></i></button>
                                <a href="{{ route('obras.index') }}" class="btn btn-sm btn-warning px-2"><i
                                        class="fas fa-undo"></i></a>
                            </th>
                        </form>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($obras as $obra)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                            x-data="{ editando: false, obra: @js($obra) }">

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
                                <input x-show="editando" type="text" x-model="obra.cod_obra"
                                    class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.cliente"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.cliente"
                                    class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.cod_cliente"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.cod_cliente"
                                    class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.latitud"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.latitud"
                                    class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.longitud"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.longitud"
                                    class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="obra.distancia"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="obra.distancia"
                                    class="form-input w-full">
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <a href="https://www.google.com/maps?q={{ $obra->latitud }},{{ $obra->longitud }}"
                                    target="_blank" class="text-blue-500 hover:underline">
                                    Mapa
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
        <div class="mt-4 flex justify-center">{{ $obras->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>


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
