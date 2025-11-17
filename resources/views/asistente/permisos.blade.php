<x-app-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Permisos del Asistente Virtual</h1>
                    <p class="text-gray-600 mt-1">Gestiona qué usuarios pueden usar el asistente y modificar la base de datos</p>
                </div>
                <a href="{{ route('asistente.index') }}" wire:navigate
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al Asistente
                </a>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usuario
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Puede usar Asistente
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Puede modificar BD
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($usuarios as $usuario)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $usuario->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $usuario->rol ?? 'Sin rol' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $usuario->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500 transition"
                                                   data-user-id="{{ $usuario->id }}"
                                                   data-permission="puede_usar_asistente"
                                                   {{ $usuario->puede_usar_asistente ? 'checked' : '' }}
                                                   onchange="actualizarPermiso(this)">
                                        </label>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="form-checkbox h-5 w-5 text-red-600 rounded focus:ring-red-500 transition"
                                                   data-user-id="{{ $usuario->id }}"
                                                   data-permission="puede_modificar_bd"
                                                   {{ $usuario->puede_modificar_bd ? 'checked' : '' }}
                                                   onchange="actualizarPermiso(this)">
                                        </label>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">Información de Permisos:</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><strong>Puede usar Asistente:</strong> Permite al usuario acceder al asistente virtual y hacer consultas SELECT.</li>
                    <li><strong>Puede modificar BD:</strong> Permite al usuario ejecutar INSERT, UPDATE, DELETE y CREATE TABLE. ⚠️ Úsalo con precaución.</li>
                </ul>
            </div>

            <!-- Advertencia -->
            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Advertencia de Seguridad</h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            El permiso "Puede modificar BD" es muy poderoso. Solo otórgalo a usuarios de confianza que comprendan SQL.
                            Todas las operaciones quedan registradas en la auditoría.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function actualizarPermiso(checkbox) {
            const userId = checkbox.dataset.userId;
            const permission = checkbox.dataset.permission;
            const isChecked = checkbox.checked;

            // Deshabilitar checkbox mientras se actualiza
            checkbox.disabled = true;

            // Obtener el otro permiso
            const otherCheckbox = document.querySelector(
                `input[data-user-id="${userId}"][data-permission="${permission === 'puede_usar_asistente' ? 'puede_modificar_bd' : 'puede_usar_asistente'}"]`
            );
            const otherPermission = otherCheckbox.dataset.permission;
            const otherIsChecked = otherCheckbox.checked;

            // Construir datos
            const data = {
                [permission]: isChecked,
                [otherPermission]: otherIsChecked
            };

            fetch(`/api/asistente/permisos/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    // Revertir checkbox
                    checkbox.checked = !isChecked;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'No se pudo actualizar el permiso'
                        });
                    } else {
                        alert(data.error || 'No se pudo actualizar el permiso');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revertir checkbox
                checkbox.checked = !isChecked;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión'
                    });
                } else {
                    alert('Error de conexión');
                }
            })
            .finally(() => {
                // Rehabilitar checkbox
                checkbox.disabled = false;
            });
        }
    </script>
    @endpush
</x-app-layout>
