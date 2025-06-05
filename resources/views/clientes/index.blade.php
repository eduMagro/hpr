<x-app-layout>
    <x-slot name="title">Clientes - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Clientes') }}
        </h2>
    </x-slot>


    <div x-data="{ modalObra: false }">
        <!-- Botón para abrir -->
        <x-tabla.boton-azul @click="modalObra = true">
            ➕ Nuevo Cliente
        </x-tabla.boton-azul>

        <!-- Modal -->
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-show="modalObra"
            x-transition x-cloak>
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-3xl">
                <h3 class="text-xl font-semibold mb-6 text-gray-800">Crear Nuevo Cliente</h3>

                <form action="{{ route('clientes.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Contenedor en dos columnas --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="empresa" class="block text-sm font-semibold text-gray-700 mb-1">Nombre de la
                                Empresa</label>
                            <x-tabla.input name="empresa" required />
                        </div>

                        <div>
                            <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-1">Código</label>
                            <x-tabla.input name="codigo" required />
                        </div>

                        <div>
                            <label for="telefono"
                                class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                            <x-tabla.input name="telefono" />
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <x-tabla.input type="email" name="email" />
                        </div>

                        <div>
                            <label for="direccion"
                                class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
                            <x-tabla.input name="direccion" />
                        </div>

                        <div>
                            <label for="ciudad" class="block text-sm font-semibold text-gray-700 mb-1">Ciudad</label>
                            <x-tabla.input name="ciudad" />
                        </div>

                        <div>
                            <label for="provincia"
                                class="block text-sm font-semibold text-gray-700 mb-1">Provincia</label>
                            <x-tabla.input name="provincia" />
                        </div>

                        <div>
                            <label for="pais" class="block text-sm font-semibold text-gray-700 mb-1">País</label>
                            <x-tabla.input name="pais" />
                        </div>

                        <div>
                            <label for="cif_nif" class="block text-sm font-semibold text-gray-700 mb-1">CIF/NIF</label>
                            <x-tabla.input name="cif_nif" />
                        </div>

                        <div>
                            <label for="activo" class="block text-sm font-semibold text-gray-700 mb-1">Activo</label>
                            <x-tabla.select name="activo" :options="['1' => 'Sí', '0' => 'No']" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-tabla.boton-azul type="button" @click="modalObra = false"
                            class="bg-gray-500 hover:bg-gray-600">
                            Cancelar
                        </x-tabla.boton-azul>
                        <x-tabla.boton-azul type="submit">Guardar</x-tabla.boton-azul>
                    </div>
                </form>
            </div>
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
        <x-tabla.paginacion :paginador="$clientes" />

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
