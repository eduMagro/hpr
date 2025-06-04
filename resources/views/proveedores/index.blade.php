<x-app-layout>
    <x-slot name="title">Proveedores - {{ config('app.name') }}</x-slot>
    @php
        $rutaActual = request()->route()->getName();
    @endphp

    @if (auth()->user()->rol !== 'operario')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('entradas.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'entradas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📦 Entradas de Material
                    </a>

                    <a href="{{ route('pedidos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'pedidos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🛒 Pedidos de Compra
                    </a>

                    <a href="{{ route('pedidos_globales.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'pedidos_globales.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🌐 Pedidos Globales
                    </a>

                    <a href="{{ route('proveedores.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'proveedores.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🏭 Proveedores
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('entradas.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ $rutaActual === 'entradas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📦 Entradas de Material
                </a>

                <a href="{{ route('pedidos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'pedidos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🛒 Pedidos de Compra
                </a>

                <a href="{{ route('pedidos_globales.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'pedidos_globales.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🌐 Pedidos Globales
                </a>

                <a href="{{ route('proveedores.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ $rutaActual === 'proveedores.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🏭 Proveedores
                </a>
            </div>
        </div>
    @endif

    <div class="px-4 py-4">
        <div x-data="{ openProveedorModal: false }" class="container mx-auto p-4">
            <!-- Botón para abrir el modal de añadir empresa -->
            <div class="flex justify-between mb-6">
                <button @click="openProveedorModal = true"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    ➕ Añadir Proveedor
                </button>
            </div>

            <!-- Modal para añadir nueva empresa -->
            <div x-show="openProveedorModal" x-transition x-cloak
                class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
                <div class="bg-white p-4 rounded-lg w-96">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Añadir Proveedor</h2>
                    <form action="{{ route('proveedores.store') }}" method="POST">
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
                        @forelse ($proveedores as $proveedor)
                            <tr tabindex="0" x-data="{
                                editando: false,
                                proveedor: @js($proveedor),
                                original: JSON.parse(JSON.stringify(@js($proveedor)))
                            }"
                                @dblclick="if(!$event.target.closest('input')) {
                          if(!editando) {
                            editando = true;
                          } else {
                            proveedor = JSON.parse(JSON.stringify(original));
                            editando = false;
                          }
                        }"
                                @keydown.enter.stop="guardarCambios(proveedor); editando = false"
                                :class="{ 'bg-yellow-100': editando }"
                                class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                                <td class="px-3 py-2" x-text="proveedor.id"></td>

                                <!-- Nombre -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="proveedor.nombre"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="proveedor.nombre"
                                        class="form-input w-full">
                                </td>
                                <!-- NIF -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="proveedor.nif"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="proveedor.nif"
                                        class="form-input w-full">
                                </td>
                                <!-- TLFNO -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="proveedor.telefono"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="proveedor.telefono"
                                        class="form-input w-full">
                                </td>
                                <!-- EMAIL -->
                                <td class="p-2 text-center border">
                                    <template x-if="!editando">
                                        <span x-text="proveedor.email"></span>
                                    </template>
                                    <input x-show="editando" type="text" x-model="proveedor.email"
                                        class="form-input w-full">
                                </td>

                                <td class="px-3 py-2 space-x-2">
                                    <x-boton-eliminar :action="route('proveedores.destroy', $proveedor->id)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">No hay proveedores registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Lógica de guardado --}}
        <script>
            function guardarCambios(proveedor) {
                fetch(`/proveedores/${proveedor.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(proveedor)
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
                                title: "Error al actualizar",
                                html: errorMsg,
                                confirmButtonText: "OK"
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
