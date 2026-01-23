<x-app-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Permisos y Configuracion</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Gestiona permisos de usuarios y personalidad del asistente</p>
                </div>
                <a href="{{ route('asistente.index') }}" wire:navigate
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al Asistente
                </a>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="cambiarTab('permisos')" id="tab-permisos"
                                class="tab-btn border-blue-500 text-blue-600 dark:text-blue-400 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Permisos de Usuarios
                        </button>
                        <button onclick="cambiarTab('configuracion')" id="tab-configuracion"
                                class="tab-btn border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Personalidad
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Tab: Permisos -->
            <div id="content-permisos" class="tab-content">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Usuario
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Puede usar Asistente
                                        </th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Puede modificar BD
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($usuarios as $usuario)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $usuario->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $usuario->rol ?? 'Sin rol' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
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
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">Informacion de Permisos:</h3>
                    <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                        <li><strong>Puede usar Asistente:</strong> Permite al usuario acceder al asistente virtual y hacer consultas SELECT.</li>
                        <li><strong>Puede modificar BD:</strong> Permite al usuario ejecutar INSERT, UPDATE, DELETE y CREATE TABLE.</li>
                    </ul>
                </div>
            </div>

            <!-- Tab: Configuracion de Personalidad -->
            <div id="content-configuracion" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Selector de Modo -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <span class="text-2xl mr-2">🎭</span>
                            Modo de Personalidad
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Selecciona como quieres que Ferrallin interactue con los usuarios.
                        </p>

                        <div class="space-y-3" id="modos-personalidad">
                            <!-- Amigable -->
                            <label class="modo-option flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                   data-modo="amigable">
                                <input type="radio" name="modo_personalidad" value="amigable" class="mt-1 mr-3" checked>
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">😊</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">Amigable</span>
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full">Recomendado</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Cercano, usa emojis, explica con paciencia. Ideal para la mayoria de usuarios.</p>
                                </div>
                            </label>

                            <!-- Profesional -->
                            <label class="modo-option flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                   data-modo="profesional">
                                <input type="radio" name="modo_personalidad" value="profesional" class="mt-1 mr-3">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">🎯</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">Profesional</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Formal, directo, sin emojis. Para entornos corporativos serios.</p>
                                </div>
                            </label>

                            <!-- Tecnico -->
                            <label class="modo-option flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                   data-modo="tecnico">
                                <input type="radio" name="modo_personalidad" value="tecnico" class="mt-1 mr-3">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">🔧</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">Tecnico</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Detallado, muestra SQL, explica el razonamiento. Para desarrolladores.</p>
                                </div>
                            </label>

                            <!-- Conciso -->
                            <label class="modo-option flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                   data-modo="conciso">
                                <input type="radio" name="modo_personalidad" value="conciso" class="mt-1 mr-3">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">📱</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">Conciso</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Respuestas cortas y directas. Ideal para movil o usuarios experimentados.</p>
                                </div>
                            </label>

                            <!-- Despota -->
                            <label class="modo-option flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-500"
                                   data-modo="despota">
                                <input type="radio" name="modo_personalidad" value="despota" class="mt-1 mr-3">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="text-xl mr-2">😤</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">Despota</span>
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded-full">Modo divertido</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Seco, impaciente, sin rodeos. Responde pero no le hace gracia.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Preview y Opciones Adicionales -->
                    <div class="space-y-6">
                        <!-- Preview -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <span class="text-2xl mr-2">👁️</span>
                                Vista Previa
                            </h3>
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Usuario: "Hola, ¿cuantos kilos hay pendientes?"</div>
                                <div class="bg-white dark:bg-gray-600 rounded-lg p-3 mt-2">
                                    <div class="flex items-start gap-2">
                                        <span class="text-xl">🤖</span>
                                        <p id="preview-respuesta" class="text-sm text-gray-800 dark:text-gray-200">
                                            ¡Hola! 👋 Tienes <strong>2,450 kg</strong> pendientes de fabricar. ¿Necesitas mas detalles por maquina?
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones adicionales -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <span class="text-2xl mr-2">⚙️</span>
                                Opciones Adicionales
                            </h3>

                            <div class="space-y-4">
                                <label class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Usar emojis</span>
                                    <input type="checkbox" id="usar_emojis" checked
                                           class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                </label>

                                <label class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Mostrar SQL ejecutado</span>
                                    <input type="checkbox" id="mostrar_sql" checked
                                           class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                </label>

                                <label class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Explicar pasos detalladamente</span>
                                    <input type="checkbox" id="explicar_detalle"
                                           class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                </label>
                            </div>
                        </div>

                        <!-- Instrucciones personalizadas -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <span class="text-2xl mr-2">✏️</span>
                                Instrucciones Adicionales
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                Añade instrucciones especificas para personalizar aun mas el comportamiento.
                            </p>
                            <textarea id="instrucciones_adicionales" rows="3"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm"
                                      placeholder="Ej: Siempre menciona que los datos son del sistema ERP. Usa 'usted' en lugar de 'tu'."></textarea>
                        </div>

                        <!-- Boton Guardar -->
                        <button onclick="guardarConfiguracion()"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Configuracion
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Previews por modo
        const previews = {
            amigable: '¡Hola! 👋 Tienes <strong>2,450 kg</strong> pendientes de fabricar. ¿Necesitas mas detalles por maquina?',
            profesional: 'Total pendiente: 2,450 kg distribuidos en 3 maquinas activas. Consulte el desglose si lo requiere.',
            tecnico: 'Resultado: 2,450 kg pendientes.<br><code class="text-xs bg-gray-200 dark:bg-gray-600 px-1 rounded">SELECT SUM(peso) FROM elementos WHERE estado=\'pendiente\'</code><br>Distribucion: MSR20 (1,200kg), Cortadora (800kg), Dobladora (450kg).',
            conciso: '2,450 kg pendientes.',
            despota: '2,450 kg. ¿Algo mas o puedo seguir con lo mio?'
        };

        // Cambiar tabs
        function cambiarTab(tab) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // Resetear estilos de tabs
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                el.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            });

            // Mostrar contenido seleccionado
            document.getElementById('content-' + tab).classList.remove('hidden');
            // Activar tab seleccionado
            const activeTab = document.getElementById('tab-' + tab);
            activeTab.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
            activeTab.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        }

        // Actualizar preview al cambiar modo
        document.querySelectorAll('input[name="modo_personalidad"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('preview-respuesta').innerHTML = previews[this.value];

                // Actualizar estilos de opciones
                document.querySelectorAll('.modo-option').forEach(opt => {
                    opt.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                    opt.classList.add('dark:border-gray-600');
                });
                this.closest('.modo-option').classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                this.closest('.modo-option').classList.remove('dark:border-gray-600');

                // Ajustar checkboxes segun modo
                if (this.value === 'profesional' || this.value === 'despota' || this.value === 'conciso') {
                    document.getElementById('usar_emojis').checked = false;
                } else {
                    document.getElementById('usar_emojis').checked = true;
                }

                if (this.value === 'tecnico') {
                    document.getElementById('explicar_detalle').checked = true;
                }
            });
        });

        // Marcar opcion inicial como seleccionada visualmente
        document.querySelector('.modo-option[data-modo="amigable"]').classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');

        // Guardar configuracion
        async function guardarConfiguracion() {
            const modo = document.querySelector('input[name="modo_personalidad"]:checked').value;
            const config = {
                modo: modo,
                usar_emojis: document.getElementById('usar_emojis').checked,
                mostrar_sql: document.getElementById('mostrar_sql').checked,
                explicar_detalle: document.getElementById('explicar_detalle').checked,
                instrucciones_adicionales: document.getElementById('instrucciones_adicionales').value
            };

            try {
                const response = await fetch('/api/asistente/configuracion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(config)
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'La configuracion de personalidad se ha actualizado.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.error || 'Error al guardar');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo guardar la configuracion'
                });
            }
        }

        // Cargar configuracion actual
        async function cargarConfiguracion() {
            try {
                const response = await fetch('/api/asistente/configuracion');
                const data = await response.json();

                if (data.success && data.config) {
                    const config = data.config;

                    // Seleccionar modo
                    const modoRadio = document.querySelector(`input[name="modo_personalidad"][value="${config.modo || 'amigable'}"]`);
                    if (modoRadio) {
                        modoRadio.checked = true;
                        modoRadio.dispatchEvent(new Event('change'));
                    }

                    // Checkboxes
                    document.getElementById('usar_emojis').checked = config.usar_emojis !== false;
                    document.getElementById('mostrar_sql').checked = config.mostrar_sql !== false;
                    document.getElementById('explicar_detalle').checked = config.explicar_detalle === true;
                    document.getElementById('instrucciones_adicionales').value = config.instrucciones_adicionales || '';
                }
            } catch (error) {
                console.error('Error cargando configuracion:', error);
            }
        }

        // Actualizar permiso (funcion existente)
        function actualizarPermiso(checkbox) {
            const userId = checkbox.dataset.userId;
            const permission = checkbox.dataset.permission;
            const isChecked = checkbox.checked;

            checkbox.disabled = true;

            const otherCheckbox = document.querySelector(
                `input[data-user-id="${userId}"][data-permission="${permission === 'puede_usar_asistente' ? 'puede_modificar_bd' : 'puede_usar_asistente'}"]`
            );
            const otherPermission = otherCheckbox.dataset.permission;
            const otherIsChecked = otherCheckbox.checked;

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
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    checkbox.checked = !isChecked;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'No se pudo actualizar el permiso'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                checkbox.checked = !isChecked;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexion'
                });
            })
            .finally(() => {
                checkbox.disabled = false;
            });
        }

        // Cargar configuracion al inicio
        document.addEventListener('DOMContentLoaded', cargarConfiguracion);
    </script>
    @endpush
</x-app-layout>
