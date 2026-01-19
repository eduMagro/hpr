<!-- ═══════════════════════════════════════════════════════════════════════════════
     CONFIGURACIÓN DE ALERTAS DE APROBACIÓN DE PLANILLAS
═══════════════════════════════════════════════════════════════════════════════ -->
@php
    $usuariosParaAlertas = $todosUsuarios->filter(function($u) {
        return $u->rol === 'oficina';
    })->map(function($u) {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'primer_apellido' => $u->primer_apellido,
            'segundo_apellido' => $u->segundo_apellido,
            'nombre_completo' => trim($u->name . ' ' . ($u->primer_apellido ?? '')),
            'rol' => $u->rol,
            'imagen' => $u->imagen,
        ];
    })->values()->toArray();
@endphp
<div class="mt-12 bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200"
     x-data="alertasPlanillaConfig()"
     x-init="cargarConfiguracion()">
    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 px-6 py-4">
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            Configuracion de Alertas de Aprobacion de Planillas
        </h3>
        <p class="text-blue-100 text-sm mt-1">Define quienes recibiran notificaciones cuando se aprueben planillas.</p>
    </div>

    <div class="p-6">
        <!-- Estado de carga -->
        <div x-show="cargando" class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="ml-2 text-gray-600">Cargando configuracion...</span>
        </div>

        <!-- Contenido principal -->
        <div x-show="!cargando" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Lista de usuarios disponibles -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Usuarios Disponibles
                    </h4>

                    <!-- Buscador -->
                    <div class="mb-3">
                        <input type="text"
                               x-model="busqueda"
                               placeholder="Buscar usuario..."
                               class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Lista de usuarios -->
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 max-h-80 overflow-y-auto space-y-2">
                        <template x-for="usuario in usuariosFiltrados" :key="usuario.id">
                            <label class="flex items-center gap-3 p-2 bg-white rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition-all cursor-pointer">
                                <input type="checkbox"
                                       :value="usuario.id"
                                       :checked="usuariosSeleccionados.includes(usuario.id)"
                                       @change="toggleUsuario(usuario.id)"
                                       class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-semibold text-sm overflow-hidden">
                                    <template x-if="usuario.imagen">
                                        <img :src="'/perfil/imagen/' + usuario.imagen" class="w-full h-full object-cover" :alt="usuario.name">
                                    </template>
                                    <template x-if="!usuario.imagen">
                                        <span x-text="usuario.name.charAt(0).toUpperCase()"></span>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate" x-text="usuario.nombre_completo"></p>
                                    <p class="text-xs text-gray-500 uppercase" x-text="usuario.rol"></p>
                                </div>
                            </label>
                        </template>

                        <div x-show="usuariosFiltrados.length === 0" class="text-center py-4 text-gray-500">
                            <p x-show="busqueda">No se encontraron usuarios con ese nombre</p>
                            <p x-show="!busqueda">No hay usuarios disponibles</p>
                        </div>
                    </div>
                </div>

                <!-- Usuarios seleccionados -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Destinatarios Configurados
                        <span class="ml-auto text-sm font-normal text-gray-500">
                            (<span x-text="usuariosSeleccionados.length"></span> seleccionados)
                        </span>
                    </h4>

                    <div class="bg-green-50 p-3 rounded-lg border border-green-200 min-h-[200px]">
                        <template x-if="usuariosSeleccionados.length === 0">
                            <div class="flex flex-col items-center justify-center h-40 text-gray-500">
                                <svg class="w-12 h-12 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <p class="text-sm">Selecciona usuarios de la lista</p>
                                <p class="text-xs text-gray-400 mt-1">Recibiran alertas al aprobar planillas</p>
                            </div>
                        </template>

                        <div class="flex flex-wrap gap-2">
                            <template x-for="userId in usuariosSeleccionados" :key="userId">
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-green-300 rounded-full text-sm">
                                    <span x-text="getNombreUsuario(userId)" class="font-medium text-gray-700"></span>
                                    <button type="button"
                                            @click="toggleUsuario(userId)"
                                            class="text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                        <strong>Nota:</strong> El usuario que aprueba la planilla NO recibira su propia alerta.
                    </div>
                </div>
            </div>

            <!-- Boton guardar -->
            <div class="mt-6 flex justify-end">
                <button type="button"
                        @click="guardarConfiguracion()"
                        :disabled="guardando"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg flex items-center gap-2 transition-all">
                    <svg x-show="!guardando" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    <svg x-show="guardando" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="guardando ? 'Guardando...' : 'Guardar Configuracion'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function alertasPlanillaConfig() {
    return {
        cargando: true,
        guardando: false,
        busqueda: '',
        todosUsuarios: @json($usuariosParaAlertas),
        usuariosSeleccionados: [],

        get usuariosFiltrados() {
            if (!this.busqueda) return this.todosUsuarios;
            const termino = this.busqueda.toLowerCase();
            return this.todosUsuarios.filter(u =>
                u.nombre_completo.toLowerCase().includes(termino) ||
                u.rol?.toLowerCase().includes(termino)
            );
        },

        async cargarConfiguracion() {
            try {
                const response = await fetch('{{ route("configuracion.alertas-planilla.get") }}');
                const data = await response.json();
                if (data.success) {
                    this.usuariosSeleccionados = data.destinatarios_ids || [];
                }
            } catch (error) {
                console.error('Error cargando configuracion:', error);
            } finally {
                this.cargando = false;
            }
        },

        toggleUsuario(userId) {
            const index = this.usuariosSeleccionados.indexOf(userId);
            if (index > -1) {
                this.usuariosSeleccionados.splice(index, 1);
            } else {
                this.usuariosSeleccionados.push(userId);
            }
        },

        getNombreUsuario(userId) {
            const usuario = this.todosUsuarios.find(u => u.id === userId);
            return usuario ? usuario.nombre_completo : 'Usuario #' + userId;
        },

        async guardarConfiguracion() {
            this.guardando = true;
            try {
                const response = await fetch('{{ route("configuracion.alertas-planilla.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        usuarios: this.usuariosSeleccionados
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Configuracion guardada',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.message || 'Error al guardar');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo guardar la configuracion.'
                });
            } finally {
                this.guardando = false;
            }
        }
    }
}
</script>
