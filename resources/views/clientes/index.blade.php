<x-app-layout>
    <x-slot name="title">Clientes - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Clientes') }}
        </h2>
    </x-slot>


    <div x-data="{ modalObra: false }" class="container mx-auto px-4 py-6">
        <div class="mb-4 flex items-center space-x-4">
            <!-- Botón para abrir el modal -->
            <button @click="modalObra = true" class="btn btn-primary">➕ Nuevo Cliente</button>
            <!-- Botón para desplegar los filtros -->
            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>
        </div>
        <!-- FORMULARIO DE FILTROS -->
        <div id="filtrosBusqueda" class="collapse mt-3">
            <form method="GET" action="{{ route('clientes.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <!-- Filtro: Empresa -->
                        <input type="text" name="empresa" class="form-control" placeholder="Buscar por empresa"
                            value="{{ request('empresa') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Obra -->
                        <input type="text" name="obra" class="form-control" placeholder="Buscar por obra"
                            value="{{ request('obra') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Código -->
                        <input type="text" name="codigo" class="form-control" placeholder="Buscar por código"
                            value="{{ request('codigo') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Teléfono -->
                        <input type="text" name="telefono" class="form-control" placeholder="Buscar por teléfono"
                            value="{{ request('telefono') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Email -->
                        <input type="text" name="email" class="form-control" placeholder="Buscar por email"
                            value="{{ request('email') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Ciudad -->
                        <input type="text" name="ciudad" class="form-control" placeholder="Buscar por ciudad"
                            value="{{ request('ciudad') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Provincia -->
                        <input type="text" name="provincia" class="form-control" placeholder="Buscar por provincia"
                            value="{{ request('provincia') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: País -->
                        <input type="text" name="pais" class="form-control" placeholder="Buscar por país"
                            value="{{ request('pais') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: CIF/NIF -->
                        <input type="text" name="cif_nif" class="form-control" placeholder="Buscar por CIF/NIF"
                            value="{{ request('cif_nif') }}">
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Estado Activo -->
                        <select name="activo" class="form-control">
                            <option value="">-- Filtrar por Estado --</option>
                            <option value="1" {{ request('activo') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('activo') == '0' ? 'selected' : '' }}>Inactivo
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <!-- Filtro: Número de registros por página -->
                        <select name="per_page" class="form-control">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10 registros
                            </option>
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 registros
                            </option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 registros
                            </option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100
                                registros</option>
                        </select>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-between mt-3">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="{{ route('clientes.index') }}" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Resetear Filtros
                    </a>
                </div>
            </form>
        </div>
        <!-- TABLA DE CLIENTES -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 px-2 border text-center">ID</th>
                        <th class="px-2 py-3 text-center border">Empresa</th>
                        <th class="px-2 py-3 text-center border">Código</th>
                        <th class="px-2 py-3 text-center border">Teléfono</th>
                        <th class="px-2 py-3 text-center border">Email</th>
                        <th class="px-2 py-3 text-center border">Dirección</th>
                        <th class="px-2 py-3 text-center border">Ciudad</th>
                        <th class="px-2 py-3 text-center border">Provincia</th>
                        <th class="px-2 py-3 text-center border">País</th>
                        <th class="px-2 py-3 text-center border">CIF/NIF</th>
                        <th class="px-2 py-3 text-center border">Activo</th>
                        <th class="px-2 py-3 text-center border">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($clientes as $cliente)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                            x-data="{ editando: false, cliente: @js($cliente) }">

                            <td class="px-2 py-3 text-center border" x-text="cliente.id"></td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.empresa"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.empresa"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.codigo"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.codigo"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.contacto1_telefono"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.contacto1_telefono"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.contacto1_email"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.contacto1_email"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.direccion"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.direccion"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.ciudad"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.ciudad"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.provincia"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.provincia"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.pais"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.pais"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="cliente.cif_nif"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="cliente.cif_nif"
                                    class="form-control form-control-sm">
                            </td>
                            <td class="px-2 py-3 text-center border">
                                <template x-if="!editando">
                                    <span class="{{ $cliente->activo ? 'text-green-500' : 'text-red-500' }}">
                                        {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </template>
                                <select x-show="editando" x-model="cliente.activo"
                                    class="form-control form-control-sm">
                                    <option value="1">Sí</option>
                                    <option value="0">No</option>
                                </select>
                            </td>
                            <td class="py-3 border flex flex-row gap-2 justify-center items-center text-center">
                                <a href="{{ route('clientes.show', $cliente->id) }}"
                                    class="text-green-500 hover:underline">Ver</a>
                                <span> | </span>
                                <button @click.stop="editando = !editando">
                                    <span x-show="!editando">✏️</span>
                                    <span x-show="editando" class="mr-2">✖</span>
                                    <span x-show="editando" @click.stop="guardarCambios(cliente)">✅</span>
                                </button>
                                <span> | </span>
                                <x-boton-eliminar :action="route('clientes.destroy', $cliente->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-gray-500">No hay clientes
                                registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-center">{{ $clientes->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <!-- Modal para crear cliente -->

        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-show="modalObra"
            x-transition>
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
                <h3 class="text-xl font-semibold mb-4">Crear Nuevo Cliente</h3>
                <form action="{{ route('clientes.store') }}" method="POST">
                    @csrf
                    <!-- Contenedor de dos columnas -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-3">
                            <label>Nombre de la Empresa</label>
                            <input type="text" name="empresa" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Código</label>
                            <input type="text" name="codigo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Dirección</label>
                            <input type="text" name="direccion" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Ciudad</label>
                            <input type="text" name="ciudad" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Provincia</label>
                            <input type="text" name="provincia" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>País</label>
                            <input type="text" name="pais" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>CIF/NIF</label>
                            <input type="text" name="cif_nif" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Activo</label>
                            <select name="activo" class="form-control">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary" @click="modalObra = false">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <script>
        function guardarCambios(cliente) {
            console.log('hola');
            fetch(`/clientes/${cliente.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(cliente)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Cliente actualizado",
                            text: "Los cambios se han guardado exitosamente.",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let errores = Object.values(data.error).flat().join('\n'); // Convierte el objeto en texto
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de validación',
                            text: errores
                        });
                    }
                })
                .catch(error => {
                    console.error("❌ Error en la solicitud fetch:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar el usuario. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }
    </script>
</x-app-layout>
