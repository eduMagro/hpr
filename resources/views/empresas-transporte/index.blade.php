<x-app-layout>
    <x-slot name="title">Transporte - {{ config('app.name') }}</x-slot>

    <div x-data="{ openEmpresaModal: false }" class="max-w-6xl mx-auto p-6 space-y-6">
        <div class="bg-gradient-to-r from-gray-900 to-gray-700 text-white rounded-2xl p-3 sm:p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-gray-300">Empresas de
                        transporte</p>
                    <p class="text-[11px] sm:text-xs text-gray-200">Gestiona empresas y camiones en un panel gris y
                        limpio.</p>
                </div>
                <button @click="openEmpresaModal = true"
                    class="inline-flex items-center gap-1 sm:gap-2 px-3 py-2 bg-gray-100 text-gray-900 rounded-lg shadow hover:bg-white hover:-translate-y-0.5 transition-transform duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-xs sm:text-sm font-semibold">Añadir empresa</span>
                </button>
            </div>
        </div>

        <!-- Modal nueva empresa -->
        <div x-show="openEmpresaModal" x-transition x-cloak
            class="fixed inset-0 flex items-center justify-center z-50 bg-black/50 backdrop-blur-sm px-4">
            <div class="bg-white p-6 rounded-xl w-full max-w-md shadow-2xl border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Añadir Empresa</h2>
                    <button type="button" @click="openEmpresaModal = false" class="text-gray-500 hover:text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form action="{{ route('empresas-transporte.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="space-y-1">
                        <label for="nombre" class="block text-xs font-semibold text-gray-700 uppercase">Nombre</label>
                        <input type="text" id="nombre" name="nombre"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                            required>
                    </div>
                    <div class="space-y-1">
                        <label for="direccion"
                            class="block text-xs font-semibold text-gray-700 uppercase">Dirección</label>
                        <input type="text" id="direccion" name="direccion"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                            required>
                    </div>
                    <div class="space-y-1">
                        <label for="telefono"
                            class="block text-xs font-semibold text-gray-700 uppercase">Teléfono</label>
                        <input type="text" id="telefono" name="telefono"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                            required>
                    </div>
                    <div class="space-y-1">
                        <label for="email" class="block text-xs font-semibold text-gray-700 uppercase">Email</label>
                        <input type="email" id="email" name="email"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                            required>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="openEmpresaModal = false"
                            class="px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold shadow hover:bg-gray-800">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            @foreach ($empresasTransporte as $empresa)
                <div x-data="{ openCamionModal: false }"
                    class="group p-3 sm:p-4 bg-white border border-gray-200 rounded-xl shadow-sm transition duration-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-semibold text-gray-900 editable" contenteditable="true"
                                data-id="{{ $empresa->id }}" data-field="nombre">
                                {{ $empresa->nombre }}
                            </h2>
                            <p class="text-[11px] sm:text-xs text-gray-500">Empresa de transporte</p>
                        </div>
                        <button @click="openCamionModal = true"
                            class="inline-flex items-center justify-center h-8 w-8 sm:h-9 sm:w-9 rounded-lg bg-gray-900 text-white shadow hover:bg-gray-800 hover:-translate-y-0.5 transition-transform duration-100"
                            title="Añadir camión">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-2 sm:mt-3 space-y-1 text-[12px] sm:text-sm text-gray-700">
                        <p class="flex justify-start gap-1">
                            <span class="text-gray-500">Teléfono:</span>
                            <span class="editable font-semibold text-gray-800" contenteditable="true"
                                data-id="{{ $empresa->id }}" data-field="telefono">
                                {{ $empresa->telefono }}
                            </span>
                        </p>
                        <p class="flex justify-start gap-1">
                            <span class="text-gray-500">Email:</span>
                            <span class="editable font-semibold text-gray-800" contenteditable="true"
                                data-id="{{ $empresa->id }}" data-field="email">
                                {{ $empresa->email }}
                            </span>
                        </p>
                    </div>

                    <div class="mt-4">
                        <h3 class="text-[11px] sm:text-xs font-semibold uppercase text-gray-600 mb-2">Camiones</h3>
                        <ul class="space-y-2">
                            @foreach ($empresa->camiones as $camion)
                                <li
                                    class="p-2 sm:p-3 bg-gray-50 border border-gray-200 rounded-lg transition hover:bg-gray-100 hover:border-gray-300">
                                    <p class="text-gray-800 text-[12px] sm:text-sm">
                                        <span class="text-gray-500">Modelo:</span>
                                        <span class="editable font-semibold" contenteditable="true"
                                            data-id="{{ $camion->id }}" data-field="modelo">
                                            {{ $camion->modelo }}
                                        </span>
                                    </p>
                                    <p class="text-gray-800 text-[12px] sm:text-sm">
                                        <span class="text-gray-500">Capacidad:</span>
                                        <span class="editable font-semibold" contenteditable="true"
                                            data-id="{{ $camion->id }}" data-field="capacidad">
                                            {{ $camion->capacidad }}
                                        </span>
                                        <span class="text-gray-500">kg</span>
                                    </p>
                                    <p class="text-gray-800 text-[12px] sm:text-sm">
                                        <span class="text-gray-500">Estado:</span>
                                        <span class="editable font-semibold uppercase" contenteditable="true"
                                            data-id="{{ $camion->id }}" data-field="estado">
                                            {{ $camion->estado }}
                                        </span>
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Modal nuevo camión -->
                    <div x-show="openCamionModal" x-transition x-cloak class="fixed inset-0 z-[99999]">
                        <!-- Overlay -->
                        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openCamionModal = false">
                        </div>

                        <!-- Content -->
                        <div
                            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white p-6 rounded-xl shadow-2xl border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Añadir Camión</h2>
                                <button type="button" @click="openCamionModal = false"
                                    class="text-gray-500 hover:text-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <form action="{{ route('camiones.store') }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                                <div class="space-y-1">
                                    <label for="modelo"
                                        class="block text-xs font-semibold text-gray-700 uppercase">Modelo</label>
                                    <input type="text" id="modelo" name="modelo"
                                        class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                                        required>
                                </div>

                                <div class="space-y-1">
                                    <label for="capacidad"
                                        class="block text-xs font-semibold text-gray-700 uppercase">Capacidad
                                        (kg)</label>
                                    <input type="number" id="capacidad" name="capacidad"
                                        class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                                        required>
                                </div>

                                <div class="space-y-1">
                                    <label for="estado"
                                        class="block text-xs font-semibold text-gray-700 uppercase">Estado</label>
                                    <select id="estado" name="estado"
                                        class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-gray-800 focus:outline-none"
                                        required>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>

                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" @click="openCamionModal = false"
                                        class="px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                        class="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold shadow hover:bg-gray-800">
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
        document.addEventListener("DOMContentLoaded", () => {
            const editables = document.querySelectorAll('.editable');

            editables.forEach(el => {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        el.blur();
                    }
                });

                el.addEventListener('blur', () => {
                    const id = el.dataset.id;
                    const field = el.dataset.field;
                    const value = el.textContent.trim();

                    if (!value) return;

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
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    text: data.message ||
                                        'Campo actualizado correctamente.',
                                    confirmButtonColor: '#111827'
                                }).then(() => window.location.reload());
                            } else {
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
        });
    </script>
</x-app-layout>
