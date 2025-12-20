@php
    $esOficina = Auth::check() && Auth::user()->rol === 'oficina';

    $config = [
        'locale' => 'es',
        'csrfToken' => csrf_token(),
        'routes' => [
            'eventosUrl' => route('users.verEventos-turnos', $user->id),
            'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
            'vacacionesStoreUrl' => route('vacaciones.solicitar'),
            'storeUrl' => route('asignaciones-turnos.store'),
            'destroyUrl' => route('asignaciones-turnos.destroy'),
        ],
        'enableListMonth' => true,
        'mobileBreakpoint' => 768,
        'permissions' => [
            'canRequestVacations' => !$esOficina,
            'canEditHours' => false,
            'canAssignShifts' => $esOficina, // si quieres permitir asignar turnos
            'canAssignStates' => $esOficina, // estados: vacaciones/baja/etc
        ],
        // Opcional: si quieres permitir asignar turnos por nombre
        'turnos' => $turnos->map(fn($t) => ['nombre' => $t->nombre])->values()->toArray(),
        'userId' => $user->id,
    ];
@endphp



<x-app-layout>
    <x-slot name="title">{{ $user->nombre_completo }}</x-slot>

    {{-- Botones de fichaje: disponibles para todos los roles --}}
    <div class="container mx-auto px-4 pt-6 pb-4">
        <div class="flex justify-center items-center gap-4">
            <button onclick="registrarFichaje('entrada')"
                class="py-3 px-8 bg-green-600 hover:bg-green-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Entrada</span>
            </button>

            <button onclick="registrarFichaje('salida')"
                class="py-3 px-8 bg-red-600 hover:bg-red-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Salida</span>
            </button>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />
    </div>

    @if ($esOficina)
        <div class="container mx-auto px-4 pb-4" x-data="documentosManager({{ $user->id }})">
            <div class="flex justify-end mb-2">
                <button @click="openModal()"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Contratos y Documentos</span>
                </button>
            </div>

            <!-- Modal -->
            <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
                role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                        @click="closeModal()"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Gestión de Contratos y Documentos
                                    </h3>

                                    <!-- Fecha de Incorporación -->
                                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de
                                            Incorporación</label>
                                        <div class="flex gap-2">
                                            <input type="date" x-model="fechaIncorporacion"
                                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                            <button @click="updateFechaIncorporacion()"
                                                class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 text-sm">Guardar</button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Usada para el cálculo de vacaciones.</p>
                                    </div>

                                    <!-- Subir Nuevo Documento -->
                                    <div class="mt-4">
                                        <h4 class="font-medium text-gray-900 mb-2">Subir Nuevo Documento</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Título</label>
                                                <input type="text" x-model="newDoc.titulo"
                                                    class="mt-1 shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                                    placeholder="Ej: Contrato Indefinido 2024">
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                                    <select x-model="newDoc.tipo"
                                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                        <option value="contrato">Contrato</option>
                                                        <option value="prorroga">Prórroga</option>
                                                        <option value="otros">Otros</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Vencimiento
                                                        (Opcional)</label>
                                                    <input type="date" x-model="newDoc.fecha_vencimiento"
                                                        class="mt-1 shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Archivo</label>
                                                <input type="file" x-ref="fileInput"
                                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                            </div>
                                            <div class="flex justify-end">
                                                <button @click="uploadDocument()" :disabled="uploading"
                                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                                                    <span x-show="uploading">Subiendo...</span>
                                                    <span x-show="!uploading">Subir Documento</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Listado de Documentos -->
                                    <div class="mt-6">
                                        <h4 class="font-medium text-gray-900 mb-2">Documentos Guardados</h4>
                                        <div class="overflow-hidden border border-gray-200 rounded-lg">
                                            <ul class="divide-y divide-gray-200 max-h-60 overflow-y-auto">
                                                <template x-for="doc in documentos" :key="doc.id">
                                                    <li class="p-3 hover:bg-gray-50 flex justify-between items-center">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-blue-600 truncate cursor-pointer"
                                                                @click="downloadDocument(doc)" x-text="doc.titulo">
                                                            </p>
                                                            <p class="text-xs text-gray-500">
                                                                <span x-text="doc.tipo.toUpperCase()"></span>
                                                                <span x-show="doc.fecha_vencimiento"
                                                                    x-text="' | Vence: ' + formatDate(doc.fecha_vencimiento)"></span>
                                                            </p>
                                                        </div>
                                                        <div class="ml-4 flex-shrink-0 flex gap-2">
                                                            <button @click="downloadDocument(doc)"
                                                                class="text-blue-600 hover:text-blue-900"
                                                                title="Descargar">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                </svg>
                                                            </button>
                                                            <button @click="deleteDocument(doc)"
                                                                class="text-red-600 hover:text-red-900"
                                                                title="Eliminar">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </li>
                                                </template>
                                                <template x-if="documentos.length === 0">
                                                    <li class="p-4 text-center text-sm text-gray-500">No hay documentos
                                                        subidos.</li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" @click="closeModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function documentosManager(userId) {
                return {
                    showModal: false,
                    userId: userId,
                    fechaIncorporacion: '{{ $user->fecha_incorporacion ? $user->fecha_incorporacion->format('Y-m-d') : '' }}',
                    uploading: false,
                    documentos: @json($user->documentosEmpleado()->orderBy('created_at', 'desc')->get()),
                    newDoc: {
                        titulo: '',
                        tipo: 'contrato',
                        fecha_vencimiento: ''
                    },
                    openModal() {
                        this.showModal = true;
                    },
                    closeModal() {
                        this.showModal = false;
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        const date = new Date(dateStr);
                        return date.toLocaleDateString('es-ES');
                    },
                    async updateFechaIncorporacion() {
                        try {
                            const response = await fetch(`/usuarios/${this.userId}/fecha-incorporacion`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    fecha_incorporacion: this.fechaIncorporacion
                                })
                            });

                            const data = await response.json();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualizado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                throw new Error(data.error || 'Error al actualizar');
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.message
                            });
                        }
                    },
                    async uploadDocument() {
                        const fileInput = this.$refs.fileInput;
                        if (!fileInput.files.length) {
                            Swal.fire({
                                icon: 'warning',
                                text: 'Selecciona un archivo primero.'
                            });
                            return;
                        }
                        if (!this.newDoc.titulo) {
                            Swal.fire({
                                icon: 'warning',
                                text: 'Escribe un título para el documento.'
                            });
                            return;
                        }

                        this.uploading = true;
                        const formData = new FormData();
                        formData.append('archivo', fileInput.files[0]);
                        formData.append('titulo', this.newDoc.titulo);
                        formData.append('tipo', this.newDoc.tipo);
                        if (this.newDoc.fecha_vencimiento) formData.append('fecha_vencimiento', this.newDoc
                            .fecha_vencimiento);

                        try {
                            const response = await fetch(`/documentos-empleado/${this.userId}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: formData
                            });
                            const data = await response.json();

                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Subido',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                this.documentos.unshift(data.documento); // Add to list
                                // Reset form
                                this.newDoc.titulo = '';
                                this.newDoc.fecha_vencimiento = '';
                                fileInput.value = '';
                            } else {
                                throw new Error(data.error || 'Error al subir');
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.message
                            });
                        } finally {
                            this.uploading = false;
                        }
                    },
                    async deleteDocument(doc) {
                        const confirm = await Swal.fire({
                            title: '¿Eliminar documento?',
                            text: "No podrás revertir esto.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Sí, eliminar'
                        });

                        if (confirm.isConfirmed) {
                            try {
                                const response = await fetch(`/documentos-empleado/${doc.id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                });
                                const data = await response.json();
                                if (data.success) {
                                    this.documentos = this.documentos.filter(d => d.id !== doc.id);
                                    Swal.fire('Eliminado!', 'El documento ha sido eliminado.', 'success');
                                } else {
                                    throw new Error(data.error);
                                }
                            } catch (error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: error.message
                                });
                            }
                        }
                    },
                    downloadDocument(doc) {
                        window.location.href = `/documentos-empleado/${doc.id}/descargar`;
                    }
                }
            }
        </script>
    @endif

    <div class="calendario-full-width">
        <div class="pb-4">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Calendario a ancho completo - salir del contenedor padre */
        .calendario-full-width {
            width: 100%;
        }

        .fc {
            width: 100% !important;
            max-width: 100% !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .fc-daygrid-day {
            min-width: 0 !important;
        }

        /* Header del calendario - mismo color que sidebar */
        .fc .fc-toolbar {
            padding: 1rem;
            background: #111827;
            /* gray-900 */
            border-radius: 12px 12px 0 0;
            margin-bottom: 0 !important;
        }

        .fc .fc-toolbar-title {
            color: white !important;
            font-weight: 700;
            font-size: 1.25rem;
            text-transform: capitalize;
        }

        .fc .fc-button {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-transform: capitalize;
        }

        .fc .fc-button:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px);
        }

        .fc .fc-button-active {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .fc .fc-button:disabled {
            opacity: 0.5;
        }

        /* Encabezados de días */
        .fc .fc-col-header {
            background: #f8fafc;
        }

        .fc .fc-col-header-cell {
            padding: 0.75rem 0;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-color: #e2e8f0 !important;
        }

        /* Celdas de días */
        .fc .fc-daygrid-day {
            transition: background-color 0.15s ease;
            border-color: #e2e8f0 !important;
        }

        .fc .fc-daygrid-day:hover {
            background-color: #f1f5f9;
        }

        .fc .fc-daygrid-day-number {
            font-weight: 600;
            color: #334155;
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        .fc .fc-day-today {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        }

        .fc .fc-day-today .fc-daygrid-day-number {
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
        }

        /* Días de otros meses */
        .fc .fc-day-other .fc-daygrid-day-number {
            color: #94a3b8;
        }

        /* Eventos */
        .fc .fc-event {
            border-radius: 6px;
            border: none !important;
            padding: 2px 6px;
            font-size: 0.75rem;
            font-weight: 500;
            margin: 1px 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .fc .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .fc .fc-daygrid-event-dot {
            display: none;
        }

        /* Más eventos link */
        .fc .fc-daygrid-more-link {
            color: #6366f1;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Scrollbar del calendario */
        .fc .fc-scroller::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .fc .fc-scroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Selección de rango */
        .fc .bg-select-range {
            background: rgba(99, 102, 241, 0.25) !important;
            border-radius: 4px;
        }

        .fc .bg-select-endpoint {
            background: rgba(99, 102, 241, 0.45) !important;
        }

        .fc .bg-select-endpoint-left {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .fc .bg-select-endpoint-right {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .fc .fc-daygrid-day-bg {
            overflow: visible;
        }

        /* que los bg events no intercepten el mouse */
        .fc .bg-select-range,
        .fc .bg-select-endpoint {
            pointer-events: none !important;
        }

        /* Vista lista */
        .fc .fc-list {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        .fc .fc-list-day-cushion {
            background: #f8fafc !important;
            padding: 0.75rem 1rem;
        }

        .fc .fc-list-event:hover td {
            background: #f1f5f9 !important;
        }

        /* Bordes redondeados del contenedor */
        .fc .fc-view-harness {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: none;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .fc .fc-toolbar-title {
                font-size: 1.1rem;
            }

            .fc .fc-button {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }

            .fc .fc-col-header-cell {
                font-size: 0.65rem;
                padding: 0.5rem 0;
            }

            .fc .fc-daygrid-day-number {
                font-size: 0.75rem;
                padding: 0.25rem;
            }

            .fc .fc-event {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
        }

        /* SweetAlert personalizado para gestión de turnos */
        .swal-calendario-popup {
            border-radius: 12px !important;
            overflow: hidden;
        }

        .swal-calendario-popup .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
        }

        .swal-calendario-popup .swal2-actions {
            margin-top: 20px;
            gap: 12px;
        }

        .swal-calendario-popup .swal2-confirm,
        .swal-calendario-popup .swal2-cancel {
            padding: 10px 24px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: transform 0.15s, box-shadow 0.15s !important;
        }

        .swal-calendario-popup .swal2-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
        }

        .swal-calendario-popup .swal2-cancel:hover {
            transform: translateY(-1px);
        }

        .swal-calendario-popup select optgroup {
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
        }

        .swal-calendario-popup select option {
            padding: 8px;
            font-size: 14px;
        }
    </style>

    {{-- Usar script desde public (no migrado a Vite aún) --}}
    <script src="{{ asset('js/calendario/calendario.js') }}?v={{ time() }}"></script>

    <script>
        function registrarFichaje(tipo) {
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            boton.disabled = true;
            boton.querySelector('.texto').textContent = 'Procesando...';
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitud = position.coords.latitude;
                    const longitud = position.coords.longitude;
                    procesarFichaje(tipo, latitud, longitud, boton, textoOriginal);
                },
                function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicacion',
                        text: `${error.message}`
                    });
                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                }, {
                    enableHighAccuracy: false,
                    timeout: 8000,
                    maximumAge: 60000
                }
            );
        }

        function procesarFichaje(tipo, latitud, longitud, boton, textoOriginal) {
            Swal.fire({
                title: 'Confirmar Fichaje',
                text: `Quieres registrar una ${tipo}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si, fichar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const payload = {
                        user_id: "{{ auth()->id() }}",
                        tipo: tipo,
                        latitud: latitud,
                        longitud: longitud,
                    };

                    fetch("{{ url('/fichar') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: data.success,
                                    text: `📍 Lugar: ${data.obra_nombre}`,
                                    showConfirmButton: false,
                                    timer: 3000
                                });

                                if (window.calendar) {
                                    window.calendar.refetchEvents();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.error
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo comunicar con el servidor'
                            });
                        });
                }

                boton.disabled = false;
                boton.querySelector('.texto').textContent = textoOriginal;
                boton.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>
</x-app-layout>
