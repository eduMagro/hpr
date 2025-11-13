<x-app-layout>
    <x-slot name="title">Proveedores - {{ config('app.name') }}</x-slot>

    <div class="px-4 py-4">
        <div x-data="{ openProveedorModal: false }" class="container mx-auto p-4">
            <!-- Botón para abrir el modal de añadir empresa -->
            <div class="flex justify-between mb-6">
                <button @click="openProveedorModal = true"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    ➕ Añadir Fabricante
                </button>
            </div>

            <!-- Modal para añadir nueva empresa -->
            <div x-show="openProveedorModal" x-transition x-cloak
                class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
                <div class="bg-white p-4 rounded-lg w-96">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Añadir Proveedor</h2>
                    <form action="{{ route('fabricantes.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="nombre" class="block text-gray-700">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="mb-4">
                            <label for="nif" class="block text-gray-700">NIF</label>
                            <input type="text" id="nif" name="nif"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="mb-4">
                            <label for="telefono" class="block text-gray-700">Teléfono</label>
                            <input type="text" id="telefono" name="telefono"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-gray-700">Email</label>
                            <input type="email" id="email" name="email"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" @click="openProveedorModal = false"
                                class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="w-full text-sm border-collapse border text-center">
                    <thead class="bg-blue-500 text-white uppercase text-xs">
                        <tr>
                            <th class="px-3 py-2 border">ID</th>
                            <th class="px-3 py-2 border">Nombre</th>
                            <th class="px-3 py-2 border">NIF</th>
                            <th class="px-3 py-2 border">Teléfono</th>
                            <th class="px-3 py-2 border">Email</th>
                            <th class="px-3 py-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fabricantes as $fabricante)
                            <tr tabindex="0" x-data="{
                                editando: false,
                                fabricante: @js($fabricante),
                                original: JSON.parse(JSON.stringify(@js($fabricante)))
                            }"
                                @dblclick="if(!$event.target.closest('input')) {
                          if(!editando) {
                            editando = true;
                          } else {
                            fabricante = JSON.parse(JSON.stringify(original));
                            editando = false;
                          }
                        }"
                                @keydown.enter.stop="guardarCambiosFabricante(fabricante); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                                <td class="px-3 py-2" x-text="fabricante.id"></td>

                                <!-- Nombre -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="fabricante.nombre"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="fabricante.nombre"
                                        class="form-input w-full">
                                </td>
                                <!-- NIF -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="fabricante.nif"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="fabricante.nif"
                                        class="form-input w-full">
                                </td>
                                <!-- TLFNO -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="fabricante.telefono"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="fabricante.telefono"
                                        class="form-input w-full">
                                </td>
                                <!-- EMAIL -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="fabricante.email"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="fabricante.email"
                                        class="form-input w-full">
                                </td>

                                <td class="px-3 py-2 space-x-2">
                                    <x-tabla.boton-eliminar :action="route('fabricantes.destroy', $fabricante->id)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">No hay fabricantes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-data="{ openDistribuidorModal: false }" class="container mx-auto p-4">
            <!-- Botón para abrir el modal de añadir empresa -->
            <div class="flex justify-between mb-6">
                <button @click="openDistribuidorModal = true"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    ➕ Añadir Distribuidor
                </button>
            </div>

            <!-- Modal para añadir nueva empresa -->
            <div x-show="openDistribuidorModal" x-transition x-cloak
                class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
                <div class="bg-white p-4 rounded-lg w-96">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Añadir Distribuidor</h2>
                    <form action="{{ route('distribuidores.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="nombre" class="block text-gray-700">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="mb-4">
                            <label for="nif" class="block text-gray-700">NIF</label>
                            <input type="text" id="nif" name="nif"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="mb-4">
                            <label for="telefono" class="block text-gray-700">Teléfono</label>
                            <input type="text" id="telefono" name="telefono"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-gray-700">Email</label>
                            <input type="email" id="email" name="email"
                                class="w-full p-2 border border-gray-300 rounded-lg" required>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" @click="openDistribuidorModal = false"
                                class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="w-full text-sm border-collapse border text-center">
                    <thead class="bg-blue-500 text-white uppercase text-xs">
                        <tr>
                            <th class="px-3 py-2 border">ID</th>
                            <th class="px-3 py-2 border">Nombre</th>
                            <th class="px-3 py-2 border">NIF</th>
                            <th class="px-3 py-2 border">Teléfono</th>
                            <th class="px-3 py-2 border">Email</th>
                            <th class="px-3 py-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($distribuidores as $distribuidor)
                            <tr tabindex="0" x-data="{
                                editando: false,
                                distribuidor: @js($distribuidor),
                                original: JSON.parse(JSON.stringify(@js($distribuidor)))
                            }"
                                @dblclick="if(!$event.target.closest('input')) {
                          if(!editando) {
                            editando = true;
                          } else {
                            distribuidor = JSON.parse(JSON.stringify(original));
                            editando = false;
                          }
                        }"
                                @keydown.enter.stop="guardarCambiosDistribuidor(distribuidor); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                                <td class="px-3 py-2" x-text="distribuidor.id"></td>

                                <!-- Nombre -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="distribuidor.nombre"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="distribuidor.nombre"
                                        class="form-input w-full">
                                </td>
                                <!-- NIF -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="distribuidor.nif"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="distribuidor.nif"
                                        class="form-input w-full">
                                </td>
                                <!-- TLFNO -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="distribuidor.telefono"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="distribuidor.telefono"
                                        class="form-input w-full">
                                </td>
                                <!-- EMAIL -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="distribuidor.email"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="distribuidor.email"
                                        class="form-input w-full">
                                </td>

                                <td class="px-3 py-2 space-x-2">
                                    <x-tabla.boton-eliminar :action="route('distribuidores.destroy', $distribuidor->id)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">No hay distribuidores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PARA FABRICANTES --}}
        <script>
            function guardarCambiosFabricante(fabricante) {
                fetch(`/fabricantes/${fabricante.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(fabricante)
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data = {};

                        if (contentType?.includes('application/json')) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            throw new Error("Respuesta inesperada del servidor: " + text.slice(0, 100));
                        }

                        if (!response.ok || !data.success) {
                            let errorMsg = data.message || "Error inesperado.";
                            if (data.errors) {
                                errorMsg = Object.values(data.errors).flat().join("<br>");
                            }
                            Swal.fire({
                                icon: "error",
                                title: "Error al actualizar fabricante",
                                html: errorMsg,
                                confirmButtonText: "OK"
                            });
                        } else {
                            Swal.fire({
                                icon: "success",
                                title: "Fabricante actualizado",
                                text: data.message,
                                timer: 1200,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>

        {{-- PARA DISTRIBUIDORES --}}
        <script>
            function guardarCambiosDistribuidor(distribuidor) {
                fetch(`/distribuidores/${distribuidor.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(distribuidor)
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data = {};

                        if (contentType?.includes('application/json')) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            throw new Error("Respuesta inesperada del servidor: " + text.slice(0, 100));
                        }

                        if (!response.ok || !data.success) {
                            let errorMsg = data.message || "Error inesperado.";
                            if (data.errors) {
                                errorMsg = Object.values(data.errors).flat().join("<br>");
                            }
                            Swal.fire({
                                icon: "error",
                                title: "Error al actualizar distribuidor",
                                html: errorMsg,
                                confirmButtonText: "OK"
                            });
                        } else {
                            Swal.fire({
                                icon: "success",
                                title: "Distribuidor actualizado",
                                text: data.message,
                                timer: 1200,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>

</x-app-layout>
