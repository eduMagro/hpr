<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>

    {{-- Botones de fichaje: disponibles para todos los roles --}}
    <div class="container mx-auto px-4 pb-4">
        <div class="flex justify-center items-center gap-4">
            <button onclick="registrarFichaje('entrada')"
                class="py-3 px-8 bg-green-600 hover:bg-green-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando max-md:w-full">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Entrada</span>
            </button>

            <button onclick="registrarFichaje('salida')"
                class="py-3 px-8 bg-red-600 hover:bg-red-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando max-md:w-full">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Salida</span>
            </button>
        </div>
    </div>

    @if(isset($episPorFirmar) && $episPorFirmar->count() > 0)
        <div class="container mx-auto px-4 pb-6" x-data="firmaEpisManager()">
            <!-- Alert de Aviso (Sin listado) -->
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-r shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-4 w-full">
                        <h3 class="text-lg font-bold text-red-800 dark:text-red-300">
                            Firma de EPIs requerida
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                            Tienes <strong>{{ $episPorFirmar->count() }}</strong> entrega(s) de EPIs pendientes de tu firma. Por favor, revísalas y firma la recepción.
                        </p>
                        <div class="mt-3">
                            <button type="button" @click="openModal()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                Revisar y firmar ahora
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Firma (Fullscreen) -->
            <div x-show="showModal" x-cloak class="fixed z-[1000] inset-0" aria-labelledby="modal-title"
                role="dialog" aria-modal="true">
                <!-- Backdrop -->
                <div x-show="showModal" x-transition.opacity
                    class="fixed inset-0 bg-white dark:bg-gray-900"></div>

                <!-- Modal Container Fullscreen -->
                <div x-show="showModal" x-transition
                    class="fixed inset-0 flex flex-col bg-white dark:bg-gray-900">
                    
                    <!-- Contenido Scrollable -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="max-w-2xl mx-auto px-4 py-6">
                            <!-- Título -->
                            <div class="text-center mb-6">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900/50 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Confirmación de Recepción de EPIs</h2>
                                <p class="text-gray-500 dark:text-gray-400 mt-2">Revisa los equipos entregados y firma para confirmar</p>
                            </div>

                            <!-- Listado de EPIs -->
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Equipos entregados</h3>
                                <div class="space-y-3">
                                    @foreach($episPorFirmar as $epiUser)
                                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                            <div class="h-16 w-16 flex-shrink-0 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden shadow-sm">
                                                @if($epiUser->epi->imagen_path)
                                                    <img src="{{ route('epis.imagen', $epiUser->epi) }}" alt="{{ $epiUser->epi->nombre }}" class="h-full w-full object-cover">
                                                @else
                                                    <svg class="h-8 w-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-base font-semibold text-gray-900 dark:text-white">
                                                    {{ $epiUser->epi->nombre }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $epiUser->epi->codigo ?? 'Sin código' }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-lg font-bold bg-blue-600 text-white">
                                                    {{ $epiUser->cantidad }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Check confirmación -->
                            <div class="mb-6">
                                <label class="flex items-start gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all"
                                    :class="confirmed ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <div class="flex items-center justify-center h-6 w-6 mt-0.5">
                                        <input type="checkbox" x-model="confirmed" class="h-5 w-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:bg-gray-700">
                                    </div>
                                    <div class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Confirmo la recepción</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Declaro haber recibido los EPIs listados.</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Firma -->
                            <div x-show="confirmed" x-effect="if(confirmed) initCanvas()" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Tu firma</h3>
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-800 relative touch-none select-none w-full" style="height: 200px;">
                                    <canvas id="signature-pad" class="absolute inset-0 w-full h-full cursor-crosshair rounded-xl"></canvas>
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" x-show="!hasSignature && !drawing">
                                        <span class="text-gray-400 dark:text-gray-500">Dibuja tu firma aquí</span>
                                    </div>
                                </div>
                                <div class="flex justify-end mt-2">
                                    <button @click="clearSignature()" type="button" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Borrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Fijo -->
                    <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-4 py-4 safe-area-bottom">
                        <div class="max-w-2xl mx-auto flex flex-col sm:flex-row gap-3">
                            <button type="button" @click="submitSignature()"
                                class="flex-1 inline-flex justify-center items-center rounded-xl px-6 py-3 bg-blue-600 text-white font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                :disabled="!confirmed || !hasSignature || saving">
                                <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="saving ? 'Guardando...' : 'Firmar y Confirmar'"></span>
                            </button>
                            <button type="button" @click="closeModal()"
                                class="flex-1 sm:flex-none inline-flex justify-center items-center rounded-xl px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function firmaEpisManager() {
                    return {
                        showModal: false,
                        canvas: null,
                        ctx: null,
                        drawing: false,
                        hasSignature: false,
                        confirmed: false,
                        saving: false,

                        init() {
                        },

                        openModal() {
                            this.showModal = true;
                            this.$nextTick(() => this.initCanvas());
                        },

                        closeModal() {
                            this.showModal = false;
                            this.confirmed = false;
                            this.hasSignature = false;
                        },

                        initCanvas() {
                            if (!this.confirmed) return;
                            
                            this.$nextTick(() => {
                                this.canvas = document.getElementById('signature-pad');
                                if (!this.canvas) return;
                                
                                this.ctx = this.canvas.getContext('2d');

                                const rect = this.canvas.parentElement.getBoundingClientRect();
                                this.canvas.width = rect.width;
                                this.canvas.height = rect.height;
                                
                                // Configurar estilo de línea
                                this.ctx.lineWidth = 2;
                                this.ctx.lineCap = 'round';
                                this.ctx.lineJoin = 'round';
                                this.ctx.strokeStyle = '#000000';

                                // Event listeners
                                this.canvas.onmousedown = (e) => this.startDrawing(e);
                                this.canvas.onmousemove = (e) => this.draw(e);
                                this.canvas.onmouseup = () => this.stopDrawing();
                                this.canvas.onmouseleave = () => this.stopDrawing();
                                
                                this.canvas.ontouchstart = (e) => { e.preventDefault(); this.startDrawing(e); };
                                this.canvas.ontouchmove = (e) => { e.preventDefault(); this.draw(e); };
                                this.canvas.ontouchend = () => this.stopDrawing();

                                // Scroll automático al canvas tras la transición
                                setTimeout(() => {
                                    this.canvas.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }, 350);
                            });
                        },
                        
                        resizeCanvas() {
                            // Ajustar a resolución real para evitar borrosidad
                            const rect = this.canvas.parentElement.getBoundingClientRect();
                            this.canvas.width = rect.width;
                            this.canvas.height = rect.height;
                            
                            // Re-aplicar estilos tras resize
                            if(this.ctx) {
                                this.ctx.lineWidth = 2;
                                this.ctx.lineCap = 'round';
                                this.ctx.strokeStyle = '#000000';
                            }
                        },

                        getPos(e) {
                            const rect = this.canvas.getBoundingClientRect();
                            let clientX = e.clientX;
                            let clientY = e.clientY;

                            if (e.touches && e.touches.length > 0) {
                                clientX = e.touches[0].clientX;
                                clientY = e.touches[0].clientY;
                            }

                            return {
                                x: clientX - rect.left,
                                y: clientY - rect.top
                            };
                        },

                        startDrawing(e) {
                            this.drawing = true;
                            this.hasSignature = true;
                            const pos = this.getPos(e);
                            this.ctx.beginPath();
                            this.ctx.moveTo(pos.x, pos.y);
                        },

                        draw(e) {
                            if (!this.drawing) return;
                            const pos = this.getPos(e);
                            this.ctx.lineTo(pos.x, pos.y);
                            this.ctx.stroke();
                        },

                        stopDrawing() {
                            this.drawing = false;
                            this.ctx.closePath();
                        },

                        clearSignature() {
                            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                            this.hasSignature = false;
                        },

                        async submitSignature() {
                            if (!this.hasSignature) {
                                alert('Por favor firma primero.');
                                return;
                            }

                            this.saving = true;
                            const dataUrl = this.canvas.toDataURL('image/png');

                            try {
                                const res = await fetch("{{ route('epis.firmar') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ firma: dataUrl })
                                });

                                const data = await res.json();
                                if (res.ok) {
                                    Swal.fire('Firmado', 'Has firmado correctamente la recepción.', 'success').then(() => {
                                        window.location.reload();
                                    });
                                    this.closeModal();
                                } else {
                                    throw new Error(data.message || 'Error al guardar');
                                }
                            } catch (e) {
                                alert(e.message);
                            } finally {
                                this.saving = false;
                            }
                        }
                    }
                }
            </script>
        </div>
    @endif

    <div class="container mx-auto sm:px-4">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" :sesiones="$sesiones" />
    </div>

    @php
        $contratosIncorporacion = collect([]);
        $hasIncorporacion = false;
        if ($user->incorporacion) {
            $hasIncorporacion = true;
            $contratosIncorporacion = $user->incorporacion
                ->documentos()
                ->where('tipo', 'contrato_trabajo')
                ->get()
                ->map(function ($doc) use ($user) {
                    $doc->download_url = route('incorporaciones.verArchivo', [
                        'incorporacion' => $user->incorporacion->id,
                        'archivo' => $doc->archivo,
                    ]);
                    return $doc;
                });
        }
    @endphp

    <div class="container mx-auto px-4 pb-4" x-data="documentosManager({{ $user->id }})"
        @open-docs-modal.window="openModal()">
        <!-- Modal -->
        <div x-show="showModal" x-cloak class="fixed max-h-screen inset-0 z-[900]" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-top bg-white dark:bg-gray-900 rounded-lg text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full !max-h-[90vh] !overflow-y-auto">
                    <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Contratos y
                                    Documentos</h3>
                                <!-- Fecha de Incorporación -->
                                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de
                                        Incorporación</label>
                                    <template x-if="hasIncorporacion">
                                        <div>
                                            <div class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 sm:text-sm"
                                                x-text="fechaIncorporacion ? formatDate(fechaIncorporacion) : 'No definida'">
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vinculada a la incorporación (No
                                                modificable).</p>
                                        </div>
                                    </template>
                                    <template x-if="!hasIncorporacion">
                                        <div>
                                            @if (auth()->user()->rol === 'oficina')
                                                <div class="flex gap-2">
                                                    <input type="date" x-model="fechaIncorporacion"
                                                        class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                                                    <button @click="updateFechaIncorporacion()"
                                                        class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 text-sm">Guardar</button>
                                                </div>
                                            @else
                                                <div class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 sm:text-sm"
                                                    x-text="fechaIncorporacion ? formatDate(fechaIncorporacion) : 'No definida'">
                                                </div>
                                            @endif
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Usada para el cálculo de vacaciones.
                                            </p>
                                        </div>
                                    </template>
                                </div>
                                <!-- Listado de Contratos de Incorporación -->
                                <div class="mt-6 mb-6">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Contratos (Incorporación)</h4>
                                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                                        <template x-if="!hasIncorporacion">
                                            <p class="p-4 text-sm text-gray-500 dark:text-gray-400 italic">No hay incorporación vinculada.
                                            </p>
                                        </template>
                                        <template x-if="hasIncorporacion && contratos.length === 0">
                                            <p class="p-4 text-sm text-gray-500 dark:text-gray-400 italic">No hay contratos subidos.</p>
                                        </template>
                                        <ul class="divide-y divide-gray-200 dark:divide-gray-700 max-h-40 overflow-y-auto"
                                            x-show="hasIncorporacion && contratos.length > 0">
                                            <template x-for="doc in contratos" :key="doc.id">
                                                <li class="p-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex justify-between items-center">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-blue-800 dark:text-blue-400 truncate cursor-pointer"
                                                            @click="window.open(doc.download_url, '_blank')"
                                                            x-text="'Contrato de Trabajo'"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400"
                                                            x-text="'Subido: ' + formatDate(doc.created_at)"></p>
                                                    </div>
                                                    <div class="ml-4 flex-shrink-0">
                                                        <a :href="doc.download_url" target="_blank"
                                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" title="Ver">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="closeModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function documentosManager(userId) {
                return {
                    showModal: false,
                    userId: userId,
                    fechaIncorporacion: '{{ $user->incorporacion && $user->incorporacion->fecha_incorporacion ? $user->incorporacion->fecha_incorporacion->format('Y-m-d') : ($user->fecha_incorporacion ? $user->fecha_incorporacion->format('Y-m-d') : '') }}',
                    contratos: @json($contratosIncorporacion),
                    hasIncorporacion: @json($hasIncorporacion),
                    openModal() {
                        this.showModal = true;
                    },
                    closeModal() {
                        this.showModal = false;
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '';
                        if (dateStr.includes('T')) {
                            const date = new Date(dateStr);
                            return date.toLocaleDateString('es-ES');
                        }
                        const parts = dateStr.split('-');
                        if (parts.length === 3) {
                            return `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                        return dateStr;
                    },
                    async updateFechaIncorporacion() {
                        try {
                            const url = "{{ route('usuarios.updateFechaIncorporacion', ':id') }}".replace(':id', this
                                .userId);
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    fecha_incorporacion: this.fechaIncorporacion || null
                                })
                            });
                            const data = await response.json();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualizado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false,
                                    timerProgressBar: true
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
                    }
                }
            }
        </script>
    </div>

    <!-- Bottom Modal para Vacaciones (compacto) -->
    <div id="vacation-bottom-modal"
        class="fixed bottom-0 left-0 right-0 z-[9999] transform translate-y-full transition-transform duration-300 ease-in-out shadow-[0_-4px_20px_rgba(0,0,0,0.3)] pb-[env(safe-area-inset-bottom)]">
        <div class="bg-gray-800 text-white px-4 py-3 flex justify-center items-center border-t border-gray-600">
            <div id="vacation-bottom-content" class="flex flex-wrap items-center justify-center gap-2">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>

    {{-- Calendario a ancho completo --}}
    <div class="calendario-full-width">
        <div class="">
            <div id="calendario" class="fc-calendario"
                data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
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
            margin: auto;
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

        /* Encabezados de dias */
        .fc .fc-col-header {
            background: #f8fafc;
        }

        .dark .fc .fc-col-header {
            background: #1f2937;
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

        .dark .fc .fc-col-header-cell {
            color: #9ca3af;
            border-color: #374151 !important;
        }

        /* Celdas de dias */
        .fc .fc-daygrid-day {
            transition: background-color 0.15s ease;
            border-color: #e2e8f0 !important;
        }

        .dark .fc .fc-daygrid-day {
            border-color: #374151 !important;
            background: #111827;
        }

        .fc .fc-daygrid-day:hover {
            background-color: #f1f5f9;
        }

        .dark .fc .fc-daygrid-day:hover {
            background-color: #1f2937;
        }

        .fc .fc-daygrid-day-number {
            font-weight: 600;
            color: #334155;
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        .dark .fc .fc-daygrid-day-number {
            color: #d1d5db;
        }

        .fc .fc-day-today {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        }

        .dark .fc .fc-day-today {
            background: linear-gradient(135deg, #1e3a5f 0%, #312e81 100%) !important;
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

        /* Dias de otros meses */
        .fc .fc-day-other .fc-daygrid-day-number {
            color: #94a3b8;
        }

        .dark .fc .fc-day-other .fc-daygrid-day-number {
            color: #4b5563;
        }

        .dark .fc .fc-day-other {
            background: #0d1117;
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

        /* Asegurar que el texto de eventos sea visible */
        .fc .fc-event-title,
        .fc .fc-event-time {
            color: inherit;
        }

        /* En modo dark, forzar colores claros en eventos con fondo oscuro */
        .dark .fc .fc-event {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .fc .fc-daygrid-event-dot {
            display: none;
        }

        /* Mas eventos link */
        .fc .fc-daygrid-more-link {
            color: #6366f1;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .dark .fc .fc-daygrid-more-link {
            color: #818cf8;
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

        .dark .fc .fc-scroller::-webkit-scrollbar-track {
            background: #1f2937;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .dark .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        /* Seleccion de rango */
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
            border-left: 3px solid rgba(99, 102, 241, 0.8);
            box-shadow: -4px 0 8px rgba(99, 102, 241, 0.4);
        }

        .fc .bg-select-endpoint-right {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: 3px solid rgba(99, 102, 241, 0.8);
            box-shadow: 4px 0 8px rgba(99, 102, 241, 0.4);
        }

        .fc .fc-daygrid-day-bg {
            overflow: visible;
        }

        /* que los bg events no intercepten el mouse */
        .fc .bg-select-range,
        .fc .bg-select-endpoint {
            pointer-events: none !important;
        }

        /* === FICHAJES === */
        .fc .fichaje-evento {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 1px 2px !important;
            min-height: auto !important;
        }

        .fc .fichaje-evento:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        /* Contenedor principal de fichajes */
        .fichajes-container {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
            font-size: 0.62rem;
            line-height: 1;
            width: 100%;
        }

        /* Cuando hay dos jornadas, ponerlas lado a lado */
        .fichajes-container.dos-jornadas {
            flex-direction: row;
            justify-content: space-between;
        }

        /* Cada jornada */
        .jornada {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .jornada-1 {
            justify-content: flex-start;
        }

        .jornada-2 {
            justify-content: flex-end;
        }

        /* Ocultar label de jornada (1ª, 2ª) */
        .jornada-label {
            display: none;
        }

        /* Hora de entrada - verde */
        .hora-entrada {
            background: #dcfce7;
            color: #166534;
            padding: 2px 4px;
            border-radius: 3px;
            border-left: 2px solid #22c55e;
            font-weight: 600;
        }

        .dark .hora-entrada {
            background: #14532d;
            color: #86efac;
        }

        /* Hora de salida - rojo */
        .hora-salida {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 4px;
            border-radius: 3px;
            border-left: 2px solid #ef4444;
            font-weight: 600;
        }

        .dark .hora-salida {
            background: #7f1d1d;
            color: #fca5a5;
        }

        /* Contenedor de eventos */
        .fc .fc-daygrid-day-events {
            display: flex !important;
            flex-direction: column !important;
            gap: 1px !important;
            min-height: 40px !important;
            padding-top: 1px !important;
        }

        .fc .fc-daygrid-event-harness {
            margin-top: 0 !important;
        }

        /* Asegurar que fichajes aparezcan despues de turnos */
        .fc .fc-daygrid-event-harness:has(.fichaje-evento) {
            order: 10 !important;
        }

        /* === ESTILOS MINIMALISTAS PARA EVENTOS === */
        .evento-simple {
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Borde lateral sutil */
        .fc .fc-event:not(.fichaje-evento) {
            border-left: 3px solid rgba(0, 0, 0, 0.2) !important;
            border-right: none !important;
            border-top: none !important;
            border-bottom: none !important;
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

        .dark .fc .fc-list-day-cushion {
            background: #1f2937 !important;
            color: #d1d5db;
        }

        .fc .fc-list-event:hover td {
            background: #f1f5f9 !important;
        }

        .dark .fc .fc-list-event:hover td {
            background: #374151 !important;
        }

        .dark .fc .fc-list-event td {
            background: #111827;
            color: #d1d5db;
        }

        /* Bordes redondeados del contenedor */
        .fc .fc-view-harness {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: none;
        }

        .dark .fc .fc-view-harness {
            border-color: #374151;
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

            /* Fichajes en movil */
            .fichajes-container {
                font-size: 0.5rem !important;
            }

            .fichajes-container.dos-jornadas {
                flex-direction: column !important;
                gap: 1px !important;
            }

            .hora-entrada,
            .hora-salida {
                padding: 1px 2px !important;
            }

            .jornada-label {
                font-size: 0.45rem !important;
                padding: 0 2px !important;
            }
        }

        /* SweetAlert personalizado para gestion de turnos */
        .swal-calendario-popup {
            border-radius: 12px !important;
            overflow: hidden;
        }

        .dark .swal-calendario-popup {
            background: #1f2937 !important;
            color: #d1d5db !important;
        }

        .swal-calendario-popup .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
        }

        .dark .swal-calendario-popup .swal2-html-container {
            color: #d1d5db !important;
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

        .dark .swal-calendario-popup select optgroup {
            color: #9ca3af;
        }

        .swal-calendario-popup select option {
            padding: 8px;
            font-size: 14px;
        }

        .dark .swal-calendario-popup select {
            background: #374151;
            color: #d1d5db;
            border-color: #4b5563;
        }

        .dark .swal-calendario-popup select option {
            background: #374151;
            color: #d1d5db;
        }

        .dark .swal-calendario-popup .swal2-title {
            color: #f3f4f6 !important;
        }

        .dark .swal-calendario-popup .swal2-input,
        .dark .swal-calendario-popup .swal2-textarea {
            background: #374151 !important;
            color: #d1d5db !important;
            border-color: #4b5563 !important;
        }
    </style>

    {{-- Usar script desde public (no migrado a Vite aun) --}}
    <script src="{{ asset('js/calendario/calendario.js') }}?v={{ time() }}"></script>

    <script>
        // Sistema de fichaje con protección contra duplicados
        window._fichajePendiente = false;

        function bloquearBotonesFichaje(bloquear, botonActivo = null, textoOriginal = '') {
            const botones = document.querySelectorAll('[onclick^="registrarFichaje"]');
            botones.forEach(btn => {
                btn.disabled = bloquear;
                if (bloquear) {
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                    if (btn === botonActivo) {
                        btn.querySelector('.texto').textContent = 'Procesando...';
                    }
                } else {
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    if (btn === botonActivo && textoOriginal) {
                        btn.querySelector('.texto').textContent = textoOriginal;
                    }
                }
            });
        }

        function registrarFichaje(tipo) {
            // Prevenir múltiples fichajes simultáneos
            if (window._fichajePendiente) {
                Swal.fire({
                    icon: 'info',
                    title: 'Fichaje en proceso',
                    text: 'Espera a que termine el fichaje actual.',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            window._fichajePendiente = true;
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            bloquearBotonesFichaje(true, boton);

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const latitud = position.coords.latitude;
                    const longitud = position.coords.longitude;
                    procesarFichaje(tipo, latitud, longitud, boton, textoOriginal);
                },
                function (error) {
                    window._fichajePendiente = false;
                    bloquearBotonesFichaje(false, boton, textoOriginal);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicacion',
                        text: `${error.message}`
                    });
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
                            // Caso especial: Turno partido requiere confirmación
                            if (data.requiere_confirmacion_turno_partido) {
                                Swal.fire({
                                    title: 'Turno Partido',
                                    text: data.mensaje,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Sí, hacer turno partido',
                                    cancelButtonText: 'No, cancelar'
                                }).then((confirmResult) => {
                                    if (confirmResult.isConfirmed) {
                                        // Reenviar con confirmación
                                        const payloadConfirmado = {
                                            user_id: "{{ auth()->id() }}",
                                            tipo: tipo,
                                            latitud: latitud,
                                            longitud: longitud,
                                            confirmar_turno_partido: true
                                        };

                                        fetch("{{ url('/fichar') }}", {
                                            method: "POST",
                                            headers: {
                                                "Content-Type": "application/json",
                                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                            },
                                            body: JSON.stringify(payloadConfirmado)
                                        })
                                            .then(r => r.json())
                                            .then(dataConfirmado => {
                                                window._fichajePendiente = false;
                                                bloquearBotonesFichaje(false, boton, textoOriginal);

                                                if (dataConfirmado.success) {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: dataConfirmado.success,
                                                        text: `📍 Lugar: ${dataConfirmado.obra_nombre}`,
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
                                                        text: dataConfirmado.error
                                                    });
                                                }
                                            })
                                            .catch(err => {
                                                window._fichajePendiente = false;
                                                bloquearBotonesFichaje(false, boton, textoOriginal);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: 'No se pudo comunicar con el servidor'
                                                });
                                            });
                                    } else {
                                        // Usuario canceló turno partido
                                        window._fichajePendiente = false;
                                        bloquearBotonesFichaje(false, boton, textoOriginal);
                                    }
                                });
                                return;
                            }

                            window._fichajePendiente = false;
                            bloquearBotonesFichaje(false, boton, textoOriginal);

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
                            window._fichajePendiente = false;
                            bloquearBotonesFichaje(false, boton, textoOriginal);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo comunicar con el servidor'
                            });
                        });
                } else {
                    // Usuario canceló
                    window._fichajePendiente = false;
                    bloquearBotonesFichaje(false, boton, textoOriginal);
                }
            });
        }
    </script>

</x-app-layout>