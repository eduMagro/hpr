<x-app-layout>
    <x-slot name="title">Transporte - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Empresas de Transporte"
        subtitle="Gestión de transportistas y camiones"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'
    />

    <div x-data="{ openEmpresaModal: false }" class="container mx-auto p-6">

        <!-- Botón para abrir el modal de añadir empresa -->
        <div class="flex justify-between mb-6">
            <button @click="openEmpresaModal = true"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                ➕ Añadir Empresa
            </button>
        </div>

        <!-- Modal para añadir nueva empresa -->
        <div x-show="openEmpresaModal" x-transition x-cloak
            class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
            <div class="bg-white p-6 rounded-lg w-96">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Añadir Empresa</h2>
                <form action="{{ route('empresas-transporte.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="nombre" class="block text-gray-700">Nombre</label>
                        <input type="text" id="nombre" name="nombre"
                            class="w-full p-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div class="mb-4">
                        <label for="direccion" class="block text-gray-700">Dirección</label>
                        <input type="text" id="direccion" name="direccion"
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
                        <button type="button" @click="openEmpresaModal = false"
                            class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contenedor de empresas en grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($empresasTransporte as $empresa)
                <div x-data="{ openCamionModal: false }" class="mb-8 p-4 bg-white rounded-lg shadow-sm">
                    <h2 class="text-xl font-semibold text-blue-600 editable" contenteditable="true"
                        data-id="{{ $empresa->id }}" data-field="nombre">
                        {{ $empresa->nombre }}
                    </h2>
                    <p class="text-gray-600">
                        <strong>Teléfono:</strong>
                        <span class="editable" contenteditable="true" data-id="{{ $empresa->id }}"
                            data-field="telefono">
                            {{ $empresa->telefono }}
                        </span>
                    </p>
                    <p class="text-gray-600">
                        <strong>Email:</strong>
                        <span class="editable" contenteditable="true" data-id="{{ $empresa->id }}" data-field="email">
                            {{ $empresa->email }}
                        </span>
                    </p>

                    <h3 class="mt-4 text-lg font-medium text-gray-700">Camiones</h3>
                    <ul class="space-y-4">
                        @foreach ($empresa->camiones as $camion)
                            <li class="p-4 bg-gray-100 rounded-lg shadow-md">
                                <p class="text-gray-800">
                                    <strong>Modelo:</strong>
                                    <span class="editable" contenteditable="true" data-id="{{ $camion->id }}"
                                        data-field="modelo">
                                        {{ $camion->modelo }}
                                    </span>
                                </p>
                                <p class="text-gray-800">
                                    <strong>Capacidad:</strong>
                                    <span class="editable" contenteditable="true" data-id="{{ $camion->id }}"
                                        data-field="capacidad">
                                        {{ $camion->capacidad }}
                                    </span> kg
                                </p>
                                <p class="text-gray-800">
                                    <strong>Estado:</strong>
                                    <span class="editable font-semibold" contenteditable="true"
                                        data-id="{{ $camion->id }}" data-field="estado">
                                        {{ $camion->estado }}
                                    </span>
                                </p>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Botón para abrir el modal de añadir camión -->
                    <button @click="openCamionModal = true"
                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        ➕ Añadir Camión
                    </button>

                    <!-- Modal para añadir camión -->
                    <div x-show="openCamionModal" x-transition x-cloak
                        class="fixed inset-0 flex items-center justify-center z-50 bg-gray-800 bg-opacity-50">
                        <div class="bg-white p-6 rounded-lg w-96">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Añadir Camión</h2>
                            <form action="{{ route('camiones.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                                <div class="mb-4">
                                    <label for="modelo" class="block text-gray-700">Modelo</label>
                                    <input type="text" id="modelo" name="modelo"
                                        class="w-full p-2 border border-gray-300 rounded-lg" required>
                                </div>

                                <div class="mb-4">
                                    <label for="capacidad" class="block text-gray-700">Capacidad (kg)</label>
                                    <input type="number" id="capacidad" name="capacidad"
                                        class="w-full p-2 border border-gray-300 rounded-lg" required>
                                </div>

                                <div class="mb-4">
                                    <label for="estado" class="block text-gray-700">Estado</label>
                                    <select id="estado" name="estado"
                                        class="w-full p-2 border border-gray-300 rounded-lg" required>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>

                                <div class="flex justify-between">
                                    <button type="button" @click="openCamionModal = false"
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
                </div>
            @endforeach
        </div>
    </div>
    <script>
        function initEmpresasTransportePage() {
            // Prevenir doble inicialización
            if (document.body.dataset.empresasTransportePageInit === 'true') return;

            console.log('🔍 Inicializando página de empresas de transporte...');

            const editables = document.querySelectorAll('.editable');

            editables.forEach(el => {
                // Evitar que se inserten saltos de línea al presionar Enter.
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        el.blur(); // Finaliza la edición y dispara el evento blur
                    }
                });

                // Al salir del campo, se envía la actualización.
                el.addEventListener('blur', () => {
                    const id = el.dataset.id;
                    const field = el.dataset.field;
                    const value = el.textContent.trim();

                    // Puedes incluir una validación básica según el campo
                    if (!value) {
                        console.warn(`El campo ${field} no puede estar vacío.`);
                        return;
                    }

                    // Envía la actualización al servidor vía fetch
                    fetch("{{ route('empresas-transporte.editarField') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id,
                                field,
                                value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Respuesta del backend:', data);
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    text: data.message ||
                                        'Campo actualizado correctamente.',
                                    confirmButtonColor: '#28a745'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                console.error('Error al actualizar:', data.error ||
                                    'Respuesta sin éxito');
                                Swal.fire({
                                    icon: 'error',
                                    text: data.error || 'Error al actualizar el campo.',
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error en la solicitud:', error);
                            Swal.fire({
                                icon: 'error',
                                text: 'Error en la solicitud. Revisa la consola.',
                            });
                        });
                });
            });

            // Marcar como inicializado
            document.body.dataset.empresasTransportePageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initEmpresasTransportePage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initEmpresasTransportePage);
        document.addEventListener('DOMContentLoaded', initEmpresasTransportePage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.empresasTransportePageInit = 'false';
        });
    </script>
</x-app-layout>
