<x-app-layout>
    <style>
        .ia-spinner {
            width: 16px;
            height: 16px;
            border: 3px solid #e5e7eb;
            border-top-color: #4f46e5;
            border-radius: 9999px;
            animation: ia-spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes ia-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .simulacion-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .processing-circle {
            width: 60px;
            height: 60px;
            display: inline-flex;
        }

        .processing-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.9), rgba(59, 130, 246, 0.9));
            color: white;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .processing-overlay.hidden {
            display: none !important;
        }

        .preview-zoom {
            position: relative;
            overflow: hidden;
        }

        .preview-zoom .preview-overlay {
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        #previewModal {
            display: none;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        #previewModal.show {
            display: flex;
            opacity: 1;
            pointer-events: auto;
        }

        #previewModal .preview-modal-content img {
            max-height: 80vh;
        }

        @media (max-width: 767px) {
            #previewModal .preview-modal-content {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
            }

            #previewModal .preview-modal-content img {
                height: 100%;
                object-fit: contain;
            }
        }

        /* ========================================== */
        /* CSS PARA VISTA MÓVIL PASO A PASO */
        /* ========================================== */

        /* Container de pasos móvil */
        #stepWrapper {
            display: flex;
            width: 400%;
            /* 4 vistas x 100% */
            transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Cada vista ocupa 25% del wrapper (100% del viewport) */
        #stepWrapper>div {
            width: 25%;
            /* Relativo al wrapper de 400% */
            flex-shrink: 0;
        }

        /* Animaciones para botones móviles */
        .mobile-btn-slide-in {
            animation: slideInFromBottom 300ms ease-out;
        }

        #mobile-back-btn {
            z-index: 50;
            cursor: pointer;
            position: relative;
        }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Overlay para modal en móvil */
        .mobile-modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Modal simple para edición (Vista 2) */
        .mobile-edit-modal {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: flex-end;
        }

        .mobile-edit-modal.show {
            display: flex;
        }

        .mobile-edit-modal-content {
            background: white;
            border-radius: 1.5rem 1.5rem 0 0;
            max-height: 90vh;
            width: 100%;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mobile-edit-modal.show .mobile-edit-modal-content {
            transform: translateY(0);
        }

        /* Prevenir zoom en iOS */
        input,
        select,
        textarea,
        button {
            font-size: 16px;
        }
    </style>
    <x-slot name="title">Revisión asistida de albaranes</x-slot>

    <!-- Vista Desktop Eliminada - Solo versión Móvil activa -->


    <!-- Header superior -->
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-4 py-3">
            <!-- Botón retroceder -->
            <button id="mobile-back-btn" type="button"
                class="hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Título del paso actual -->
            <h2 id="mobile-step-title" class="text-lg font-semibold text-gray-900">
                Subir Albarán
            </h2>

            <!-- Indicador de paso (1/3, 2/3, etc) -->
            <span class="text-sm text-gray-500">
                <span id="mobile-current-step">1</span>/3
            </span>
        </div>

        <!-- Barra de progreso -->
        <div class="h-1 bg-gray-200">
            <div id="mobile-progress-bar"
                class="h-full bg-gradient-to-tr from-indigo-600 to-indigo-700 transition-all duration-300"
                style="width: 33.33%"></div>
        </div>
    </div>

    <!-- Step wrapper con 3 vistas -->
    <div id="stepWrapper"
        class="flex transition-transform duration-300 ease-in-out h-[calc(100vh-162px)] overflow-y-auto"
        style="width: 300%; transform: translateX(0%)">

        <!-- ===== VISTA 1: SUBIR FOTO ===== -->
        <div id="step-1" class="flex-shrink-0 px-4 py-6" style="width: 33.33%;">
            <!-- (Contenido de VISTA 1 sin cambios) -->
            <div class="max-w-lg mx-auto space-y-3">
                <p class="text-sm text-gray-600">Selecciona el proveedor y sube una foto del albarán</p>

                <!-- Formulario móvil -->
                <form id="ocrForm-mobile" class="space-y-4">
                    @csrf
                    <!-- Selector de proveedor -->
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">Proveedor</span>
                        <select name="proveedor" id="proveedor-mobile" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                            <option value="">Selecciona fabricante</option>
                            <option value="siderurgica">Siderúrgica Sevillana (SISE)</option>
                            <option value="megasa">Megasa</option>
                            <option value="balboa">Balboa</option>
                            <option value="otro">Otro / No listado</option>
                        </select>
                    </label>

                    <!-- Input de archivo (Dual: Cámara o Galería) -->
                    <label class="block mb-2 text-sm font-medium text-gray-700">Foto del albarán</label>
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <!-- Opción Cámara -->
                        <label
                            class="relative flex flex-col items-center justify-center p-4 border-2 border-dashed border-indigo-200 rounded-xl bg-indigo-50/50 hover:bg-indigo-50 transition cursor-pointer text-center group">
                            <div class="p-2 bg-indigo-100 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-indigo-900">Usar Cámara</span>
                            <input type="file" name="imagenes[]" id="camera-mobile" accept="image/*"
                                capture="environment" class="hidden" onchange="handleMobileFileSelection(this)">
                        </label>

                        <!-- Opción Galería -->
                        <label
                            class="relative flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50/50 hover:bg-gray-100 transition cursor-pointer text-center group">
                            <div class="p-2 bg-gray-200 rounded-full mb-2 group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 8l-3-3m3 3l3-3" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-gray-700">Subir Archivo</span>
                            <input type="file" name="imagenes[]" id="imagenes-mobile"
                                accept="image/*,application/pdf" class="hidden"
                                onchange="handleMobileFileSelection(this)">
                        </label>
                    </div>

                    <!-- Feedback de selección -->
                    <div id="mobile-file-feedback"
                        class="hidden p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 flex-shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-sm text-green-800 font-medium truncate"
                                id="mobile-file-name">NombreArchivo.jpg</span>
                        </div>
                        <button type="button" onclick="clearMobileSelection()"
                            class="text-gray-400 hover:text-red-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Botón procesar -->
                    <div class="space-y-3">
                        <button type="button" id="processBtn-mobile" onclick="procesarAlbaranMobile()"
                            class="relative w-full px-4 py-3 bg-indigo-600 text-white rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transition overflow-hidden flex items-center justify-center">
                            <span id="processBtnLabel-mobile">Procesar albarán</span>
                            <span id="processing-mobile" class="processing-overlay hidden">
                                <svg class="processing-circle" width="60" height="60" viewBox="0 0 50 50">
                                    <g fill="none" stroke="#ffffff" stroke-width="2">
                                        <path d="M15 10h15l5 5v20H15V10">
                                            <animate attributeName="stroke-dasharray" values="0,100;100,0"
                                                dur="2s" repeatCount="indefinite"></animate>
                                        </path>
                                        <path d="M30 10v5h5">
                                            <animate attributeName="opacity" values="0;1;0" dur="2s"
                                                repeatCount="indefinite"></animate>
                                        </path>
                                        <path d="M20 20h10M20 25h10M20 30h10">
                                            <animate attributeName="stroke-dasharray" values="0,60;60,0"
                                                dur="2s" repeatCount="indefinite"></animate>
                                        </path>
                                    </g>
                                </svg>
                                <span>Procesando</span>
                            </span>
                        </button>

                        <!-- Botón continuar (solo si ya tenemos datos) -->
                        <button type="button" id="mobile-step1-continue-btn" data-mobile-next
                            class="hidden w-full px-4 py-3 bg-gray-200 text-gray-800 rounded-lg font-medium border border-gray-300 transition">
                            Continuar con datos actuales
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== VISTA 2: CONFIRMAR DATOS (EDICIÓN DIRECTA) ===== -->
        <div id="step-2" class="flex-shrink-0 px-4 py-6" style="width: 33.33%;">
            <!-- (Contenido de VISTA 2 sin cambios) -->
            <div class="max-w-lg mx-auto space-y-4">
                <h3 class="text-xl font-semibold text-gray-900">Revisar y Confirmar Datos</h3>

                {{-- <!-- Preview de imagen --> --}}

                <!-- Estado de las IA -->
                <div id="mobile-status-banner"
                    class="hidden items-center gap-2 rounded-lg bg-indigo-50 border border-indigo-200 px-3 py-2 text-sm text-indigo-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z" />
                    </svg>
                    <span id="mobile-status-text" class="font-medium"></span>
                </div>

                <!-- Formulario de Edición (Integrado) -->
                <form id="mobile-step2-form" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="block">
                            <span class="text-sm font-medium text-gray-700">Tipo de compra</span>
                            <input type="hidden" id="edit-tipo-compra">
                            <div class="mt-1 flex rounded-lg border border-gray-300 overflow-hidden shadow-sm">
                                <button type="button" id="btn-tipo-directo"
                                    class="flex-1 py-2 text-sm font-bold transition-colors bg-white text-gray-600 hover:bg-gray-50"
                                    onclick="setMobileTipoCompra('directo')">
                                    DIRECTO
                                </button>
                                <div class="w-px bg-gray-300"></div>
                                <button type="button" id="btn-tipo-distribuidor"
                                    class="flex-1 py-2 text-sm font-bold transition-colors bg-white text-gray-600 hover:bg-gray-50"
                                    onclick="setMobileTipoCompra('distribuidor')">
                                    DISTRIBUIDOR
                                </button>
                            </div>
                        </label>
                        </label>

                        <!-- Contenedor Fabricante (Solo visible en DIRECTO) -->
                        <label class="block" id="container-fabricante-mobile">
                            <span class="text-sm font-medium text-gray-700">Fabricante</span>

                            <!-- Select de fabricantes -->
                            <select id="edit-proveedor-select" class="mt-1 w-full rounded-lg border-gray-300">
                                <option value="">Selecciona fabricante</option>
                                @if (isset($fabricantes))
                                    @foreach ($fabricantes as $fab)
                                        <option value="{{ $fab }}">{{ $fab }}</option>
                                    @endforeach
                                @endif
                                <option value="otro">Otro / No listado</option>
                            </select>

                            <!-- Hidden input para compatibilidad -->
                            <input type="hidden" id="edit-proveedor">
                        </label>

                        <!-- Contenedor Distribuidor (Solo visible en DISTRIBUIDOR) -->
                        <label class="block hidden" id="container-distribuidor-mobile">
                            <span class="text-sm font-medium text-gray-700">Distribuidor</span>
                            <!-- Select de Distribuidores -->
                            <select id="edit-distribuidor-select" class="mt-1 w-full rounded-lg border-gray-300">
                                <option value="">Selecciona distribuidor</option>
                                @if (isset($distribuidores))
                                    @foreach ($distribuidores as $dist)
                                        <option value="{{ $dist }}">{{ $dist }}</option>
                                    @endforeach
                                @endif
                                <option value="otro">Otro / No listado</option>
                            </select>
                            <!-- Hidden input si fuera necesario -->
                            <input type="hidden" id="edit-distribuidor-input">
                        </label>

                        <label class="block hidden">
                            <span class="text-sm font-medium text-gray-700">Albarán</span>
                            <input type="text" id="edit-albaran" class="mt-1 w-full rounded-lg border-gray-300">
                        </label>
                        <label class="block hidden">
                            <span class="text-sm font-medium text-gray-700">Fecha</span>
                            <input type="date" id="edit-fecha" class="mt-1 w-full rounded-lg border-gray-300">
                        </label>
                        <label class="block hidden">
                            <span class="text-sm font-medium text-gray-700">Pedido HPR</span>
                            <input type="text" id="edit-pedido-cliente"
                                class="mt-1 w-full rounded-lg border-gray-300">
                        </label>
                        <label class="block hidden">
                            <span class="text-sm font-medium text-gray-700">Pedido Código</span>
                            <input type="text" id="edit-pedido-codigo"
                                class="mt-1 w-full rounded-lg border-gray-300">
                        </label>
                        <div class="grid grid-cols-2 gap-3 hidden">
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Peso (kg)</span>
                                <input type="number" id="edit-peso-total" step="0.01"
                                    class="mt-1 w-full rounded-lg border-gray-300">
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-gray-700">Bultos</span>
                                <input type="number" id="edit-bultos-total"
                                    class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50 cursor-not-allowed"
                                    readonly>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-semibold text-gray-900">Productos escaneados</h4>
                            <button type="button" onclick="agregarProductoMobile()"
                                class="px-3 py-1 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-semibold hover:bg-indigo-200">
                                + Añadir producto
                            </button>
                        </div>
                        <!-- Aquí se inyectarán las coladas con sus checks en Step 2 -->
                        <div id="mobile-edit-products" class="space-y-4"></div>
                    </div>

                    <!-- Botón de acción -->
                    <div class="pt-4">
                        <button type="button" id="mobile-step2-confirm-btn" onclick="guardarYContinuarStep2()"
                            class="w-full px-4 py-3 bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white rounded-lg font-bold text-lg shadow-md hover:shadow-lg transition">
                            <span id="mobile-step2-confirm-label">Confirmar y Continuar</span>
                            <span id="mobile-step2-confirm-loading" class="hidden ml-2 align-middle">
                                <span class="ia-spinner inline-block align-middle"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== VISTA 3: PEDIDO SELECCIONADO Y ACTIVACIÓN ===== -->
        <div id="step-3" class="flex-shrink-0 px-4 py-6" style="width: 33.33%;">
            <div class="max-w-md mx-auto space-y-3">
                <h3 class="text-xl font-semibold text-gray-900">Confirmar Pedido y Activar</h3>
                <div id="mobile-pedido-db-banner" class="hidden rounded-lg border px-3 py-2 text-sm"></div>
                <p class="text-sm text-gray-600">Verifica el pedido y confirma la activación</p>

                <!-- Card del pedido -->
                <div id="mobile-pedido-card" class="bg-white rounded-lg p-4">
                    <p class="text-gray-500">Cargando...</p>
                </div>

                <!-- Botón para ver otros pedidos -->
                <button type="button" id="mobile-ver-otros-pedidos"
                    class="w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">
                    Ver otros pedidos compatibles
                </button>

                <div class="h-4"></div>

                <!-- Botón de Activación Final -->
                <button type="button" id="mobile-btn-activar" onclick="activarAlbaranMobile()"
                    class="w-full px-4 py-3 bg-emerald-600 text-white rounded-lg font-bold text-lg shadow-lg hover:shadow-xl hover:bg-emerald-700 transition">
                    <span id="mobile-activar-label">Confirmar y Activar Albarán</span>
                    <span id="mobile-activar-loading" class="hidden ml-2 align-middle">
                        <span class="ia-spinner inline-block align-middle"></span>
                    </span>
                </button>
            </div>
        </div>

    </div> <!-- Fin stepWrapper -->
    </div> <!-- Fin mobileStepContainer -->

    <!-- ========================================== -->
    <!-- MODAL DE EDICIÓN BOTTOM SHEET (MÓVIL) -->
    <!-- ========================================== -->


    <!-- ========================================== -->
    <!-- MODAL DE PEDIDOS (MÓVIL) -->
    <!-- ========================================== -->
    <div id="mobilePedidosModal" class="mobile-edit-modal">
        <div class="mobile-modal-overlay" onclick="cerrarModalPedidosMobile()"></div>
        <div class="mobile-edit-modal-content border-t border-gray-200">
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900">Seleccionar Pedido</h3>
                    <div id="mobilePedidosModalScanned" class="text-xs text-gray-500 truncate"></div>
                </div>
                <button type="button" onclick="cerrarModalPedidosMobile()"
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Lista de pedidos -->
            <div id="mobile-lista-pedidos" class="p-4 space-y-3 overflow-y-auto" style="max-height: 60vh;">
                <p class="text-gray-500 text-center">Cargando pedidos...</p>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 px-4 py-3">
                <button type="button" onclick="cerrarModalPedidosMobile()"
                    class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-medium">
                    Cerrar
                </button>
            </div>
        </div>
    </div>



    <!-- Desktop Scripts and Modals Removed -->
    <div id="previewModal"
        class="fixed inset-0 z-50 items-center justify-center bg-black/70 opacity-0 pointer-events-none transition-opacity duration-200">
        <div class="preview-modal-content relative w-full max-w-3xl bg-black rounded-2xl shadow-2xl overflow-hidden">
            <button id="previewModalClose"
                class="absolute top-3 right-3 z-20 text-white bg-black/40 hover:bg-black/60 rounded-full p-1">
                <span class="sr-only">Cerrar vista previa</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img id="previewModalImg" class="w-full h-auto object-contain" alt="Previsualización ampliada">
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('previewModal');
            const modalImg = document.getElementById('previewModalImg');
            const closeBtn = document.getElementById('previewModalClose');
            const openPreview = (src) => {
                if (!src) return;
                modalImg.src = src;
                modal.classList.add('show');
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                document.body.classList.add('overflow-hidden');
            };
            const closePreview = () => {
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                modalImg.src = '';
                modal.classList.remove('show');
                document.body.classList.remove('overflow-hidden');
            };
            closeBtn?.addEventListener('click', closePreview);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closePreview();
                }
            });
            document.querySelectorAll('.preview-zoom').forEach((el) => {
                el.addEventListener('click', () => {
                    openPreview(el.dataset.preview);
                });
            });

            const mobilePreviewContainer = document.getElementById('mobile-preview-container');
            mobilePreviewContainer?.addEventListener('click', () => {
                const previewImg = document.getElementById('mobile-preview-img');
                if (previewImg && previewImg.src) {
                    openPreview(previewImg.src);
                }
            });

            window.openPreviewModal = openPreview;
        });

        // ========================================
        // SISTEMA DE NAVEGACIÓN MÓVIL
        // ========================================
        window.mobileStepManager = {
            currentStep: 1,
            maxStep: 1,
            totalSteps: 3,
            dataCache: {},
            initialized: false,

            init: function() {
                if (this.initialized) return;
                this.initialized = true;

                // Cargar cache de memoria si existe
                const savedCache = localStorage.getItem('lastScanMobileCache');
                if (savedCache) {
                    try {
                        this.dataCache = JSON.parse(savedCache);
                        // console.log('Cache recuperado de localStorage:', this.dataCache);
                        if (this.dataCache.resultado) {
                            poblarVista2ConDatos(this.dataCache.resultado);
                            if (this.dataCache.simulacion) {
                                poblarVista3ConPedido(this.dataCache.simulacion);
                                // El paso 4 antiguo (coladas) ya no existe, saltamos esa poblacion
                                if (this.dataCache.lineaSeleccionada) {
                                    actualizarVista3ConLineaSeleccionada(this.dataCache.lineaSeleccionada);
                                }
                            }
                            this.maxStep = this.dataCache.lastStep || 2;
                        }
                    } catch (e) {
                        console.error('Error cargando cache:', e);
                        localStorage.removeItem('lastScanMobileCache');
                    }
                }

                this.attachEventListeners();
                this.updateNavigation();
                this.updateProgressBar();

                // Si hay un paso guardado, ir a él
                if (this.dataCache.lastStep && this.dataCache.lastStep > 1) {
                    this.goToStep(this.dataCache.lastStep, true);
                }
            },

            goToStep: function(stepNumber, forceAdvance = false) {
                if (stepNumber < 1 || stepNumber > this.totalSteps) return;

                // Permitir: retroceder, avanzar 1 paso, o avance forzado desde AJAX
                const isGoingBack = stepNumber < this.currentStep;
                const isNextStep = stepNumber === this.currentStep + 1;
                const canNavigate = isGoingBack || isNextStep || forceAdvance || stepNumber <= this.maxStep;

                if (!canNavigate) {
                    return;
                }

                const wrapper = document.getElementById('stepWrapper');
                if (!wrapper) return;

                // El ancho de cada paso ahora es 33.33% (100/3)
                const translatePercentage = -(stepNumber - 1) * 33.33;

                wrapper.style.transform = `translateX(${translatePercentage}%)`;
                window.requestAnimationFrame(() => {
                    const mobileContainer = document.getElementById('mobileStepContainer');
                    if (mobileContainer) {
                        mobileContainer.scrollTop = 0;
                    }
                    // Asegura que cualquier scroll previo no se herede al siguiente paso
                    wrapper.scrollTop = 0;
                    document.documentElement.scrollTop = 0;
                    document.body.scrollTop = 0;
                    window.scrollTo({
                        top: 0,
                        behavior: 'auto'
                    });
                });
                this.currentStep = stepNumber;

                // Actualizar maxStep si avanzamos
                if (stepNumber > this.maxStep) {
                    this.maxStep = stepNumber;
                }

                this.updateNavigation();
                this.updateProgressBar();

                // Guardar progreso en memoria
                this.dataCache.lastStep = this.currentStep;
                localStorage.setItem('lastScanMobileCache', JSON.stringify(this.dataCache));

                if (stepNumber === 1) {
                    const continueBtn = document.getElementById('mobile-step1-continue-btn');
                    if (continueBtn) {
                        if (this.maxStep >= 2) {
                            continueBtn.classList.remove('hidden');
                        } else {
                            continueBtn.classList.add('hidden');
                        }
                    }
                }

                // Si vamos al paso 2, la sincronizacion de datos se maneja en 'poblarVista2ConDatos' y 'setMobileTipoCompra'
                // No forzamos sobrescritura manual aquí para evitar conflictos con la lógica de select/input
                if (stepNumber === 2) {
                    // Logic moved to initialization to be smarter
                }
            },

            next: function(forceAdvance = false) {
                if (this.currentStep < this.totalSteps) {
                    if (!this.validateCurrentStep()) {
                        return;
                    }
                    this.goToStep(this.currentStep + 1, forceAdvance);
                }
            },

            back: function() {
                // console.log('Retrocediendo paso...', this.currentStep);
                if (this.currentStep > 1) {
                    this.goToStep(this.currentStep - 1);
                }
            },

            updateNavigation: function() {
                // Mostrar/ocultar botón retroceder
                const backBtn = document.getElementById('mobile-back-btn');
                const title = document.getElementById('mobile-step-title');
                if (backBtn) {
                    if (this.currentStep > 1) {
                        backBtn.classList.remove('hidden');
                        backBtn.style.display = 'flex';
                        backBtn.style.visibility = 'visible';
                        backBtn.style.opacity = '1';
                    } else {
                        backBtn.classList.add('hidden');
                        backBtn.style.display = 'none';
                    }
                }

                // Actualizar título del header
                // Pasos: 1. Subir/OCR, 2. Revisar(incluye coladas), 3. Pedido/Activar
                const titles = ['Subir Albarán', 'Revisar y Confirmar', 'Pedido / Activar'];
                const titleElement = document.getElementById('mobile-step-title');
                if (titleElement && titles[this.currentStep - 1]) {
                    titleElement.textContent = titles[this.currentStep - 1];
                }

                // Actualizar indicador de paso
                const currentStepElement = document.getElementById('mobile-current-step');
                if (currentStepElement) {
                    currentStepElement.textContent = this.currentStep;
                }
            },

            updateProgressBar: function() {
                const progressBar = document.getElementById('mobile-progress-bar');
                if (progressBar) {
                    const percentage = (this.currentStep / this.totalSteps) * 100;
                    progressBar.style.width = `${percentage}%`;
                }
            },

            attachEventListeners: function() {
                // Botón retroceder
                const backBtn = document.getElementById('mobile-back-btn');
                if (backBtn) {
                    backBtn.addEventListener('click', () => this.back());
                }

                // Botones de continuar en cada vista
                document.querySelectorAll('[data-mobile-next]').forEach(btn => {
                    btn.addEventListener('click', () => this.next());
                });
            },

            validateCurrentStep: function() {
                const cache = this.dataCache || {};

                // Paso 1: Validar proveedor e imagen
                if (this.currentStep === 1) {
                    const proveedor = document.getElementById('proveedor-mobile')?.value;
                    const imagenes = document.getElementById('imagenes-mobile')?.files;
                    const camera = document.getElementById('camera-mobile')?.files;
                    if (!proveedor) {
                        toastMobile('warning', 'Selecciona un proveedor');
                        return false;
                    }
                    // Si ya tenemos datos en cache, permitimos avanzar (el botón continuar se encarga)
                    // pero si estamos en el paso 1 debemos tener al menos el proveedor
                    const hasDataInCache = cache.resultado && cache.parsed;
                    if (!hasDataInCache) {
                        if ((!imagenes || imagenes.length === 0) && (!camera || camera.length === 0)) {
                            toastMobile('warning', 'Sube una foto del albarán');
                            return false;
                        }
                    }
                }

                // Paso 2: si tipo_compra es distribuidor, exigir distribuidor seleccionado
                if (this.currentStep === 2) {
                    const tipo = (document.getElementById('edit-tipo-compra')?.value || '').toString()
                        .toLowerCase();
                    if (!tipo) {
                        toastMobile('warning', 'Selecciona el tipo de compra');
                        return false;
                    }
                    if (tipo === 'distribuidor') {
                        const dist = (document.getElementById('edit-distribuidor-select')?.value || '').toString()
                            .trim();
                        if (!dist) {
                            toastMobile('warning', 'Selecciona un distribuidor');
                            return false;
                        }
                    }

                    // Verificar coladas seleccionadas en step 2 ??
                    // Realmente checkearemos esto al salir del paso o al guardar
                    const totalCheckboxes = document.querySelectorAll('.mobile-colada-check:checked');
                    if (totalCheckboxes.length === 0) {
                        // Podríamos avisar, pero a veces quizá suben 0 coladas? No, deberían tener al menos una.
                        // toastMobile('warning', 'Selecciona al menos una colada para descargar.');
                        // return false; 
                    }
                }

                // Paso 3: exigir linea seleccionada
                if (this.currentStep === 3) {
                    if (!cache.lineaSeleccionada) {
                        toastMobile('warning', 'Selecciona un pedido para continuar');
                        return false;
                    }
                }

                return true;
            },
        };

        // Inicializar el sistema móvil al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si estamos en vista móvil
            const isMobile = window.innerWidth < 768;
            // console.log('Window width:', window.innerWidth, 'isMobile:', isMobile);

            if (window.mobileStepManager && isMobile) {
                // console.log('Inicializando mobileStepManager...');
                window.mobileStepManager.init();

                // Event listener para botón de activación
                const btnActivar = document.getElementById('mobile-btn-activar');
                if (btnActivar) {
                    btnActivar.addEventListener('click', confirmarActivacionMobile);
                    // console.log('Event listener de activación añadido');
                }
            } else if (!isMobile) {
                // console.log('Vista desktop detectada - sistema móvil no inicializado');
            }
        });

        // Re-inicializar si cambia el tamaño de ventana
        window.addEventListener('resize', function() {
            const isMobile = window.innerWidth < 768;
            if (window.mobileStepManager && isMobile && !window.mobileStepManager.initialized) {
                // console.log('Cambiado a móvil - inicializando...');
                window.mobileStepManager.init();
                window.mobileStepManager.initialized = true;
            }
        });

        /**
         * Confirmar y ejecutar activación
         */
        function confirmarActivacionMobile() {
            const cache = window.mobileStepManager.dataCache;
            const lineaId = cache.lineaSeleccionada?.id;

            if (!lineaId) {
                toastMobile('error', 'Error: No hay línea de pedido seleccionada.');
                setMobileActionLoading('mobile-btn-activar', 'mobile-activar-loading', false);
                return;
            }

            const url = `{{ url('/albaranes/scan/activar') }}/${lineaId}`;

            // Preparar payload
            // Las coladas seleccionadas vienen de Step 2 (guardadas en cache.coladasSeleccionadas)
            const coladas = cache.coladasSeleccionadas || [];

            const payload = {
                coladas: coladas
                    .map((c) => ({
                        colada: c.colada || null,
                        bulto: Number(c.bultos ?? 0),
                    }))
                    .filter((c) => c.colada !== null || (c.bulto !== null && c.bulto !== 0)),
                ocr_log_id: cache?.ocr_log_id ?? cache?.resultado?.ocr_log_id ?? null,
                json_resultante: cache?.parsed ? {
                    albaran: cache.parsed.albaran ?? null,
                    tipo_compra: cache.parsed.tipo_compra ?? null,
                    fecha: cache.parsed.fecha ?? null,
                    pedido_codigo: cache.parsed.pedido_codigo ?? null,
                    pedido_cliente: cache.parsed.pedido_cliente ?? null,
                    peso_total: cache.parsed.peso_total ?? null,
                    bultos_total: cache.parsed.bultos_total ?? null,
                    productos: cache.parsed.productos ?? null,
                } : null,
                id_pedido_productos_recomendado: cache?.recommendedId ?? cache?.simulacion?.linea_propuesta?.id ?? null,
            };

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data?.success) {
                        const message = data?.message || 'No se pudo activar la línea.';
                        throw new Error(message);
                    }

                    // Enviar aprendizaje a la IA (feedback)
                    enviarAprendizajeIA(cache);

                    if (window.Swal?.fire) {
                        toastMobile('success', 'Activado correctamente');
                    }

                    // Resetear y volver a Vista 1
                    window.mobileStepManager.dataCache = {};
                    window.mobileStepManager.currentStep = 1;
                    window.mobileStepManager.maxStep = 1;
                    window.mobileStepManager.goToStep(1, true);
                    localStorage.removeItem('lastScanMobileCache');

                    // Limpiar inputs del paso 1
                    if (window.clearMobileSelection) {
                        window.clearMobileSelection();
                    } else {
                        const form = document.getElementById('ocrForm-mobile');
                        if (form) form.reset();
                    }

                    const proveedorMobile = document.getElementById('proveedor-mobile');
                    if (proveedorMobile) {
                        proveedorMobile.value = '';
                    }

                    document.getElementById('mobile-preview-container')?.classList.add('hidden');
                    document.getElementById('mobile-status-banner')?.classList.add('hidden');
                    document.getElementById('mobile-pedido-db-banner')?.classList.add('hidden');

                    setMobileActionLoading('mobile-btn-activar', 'mobile-activar-loading', false);
                })
                .catch((err) => {
                    toastMobile('error', err?.message || 'Error al activar');
                    setMobileActionLoading('mobile-btn-activar', 'mobile-activar-loading', false);
                });
        }

        /**
         * Envía feedback del usuario a la IA para aprendizaje continuo.
         */
        function enviarAprendizajeIA(cache) {
            const recommendedId = cache.recommendedId || cache.simulacion?.linea_propuesta?.id;
            const selectedId = cache.lineaSeleccionada?.id;

            if (!selectedId) return;

            const payload = {
                ocr_log_id: cache?.ocr_log_id ?? cache?.resultado?.ocr_log_id,
                payload_ocr: cache.parsed, // JSON extraído
                recomendaciones_ia: cache.simulacion?.lineas_pendientes || [], // Todos los candidatos rankeados
                pedido_seleccionado_id: selectedId,
                es_discrepancia: recommendedId && selectedId != recommendedId,
                motivo_usuario: cache.motivoDiscrepanciaIA || null
            };

            fetch("{{ route('albaranes.scan.aprendizaje.guardar') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // console.log('Aprendizaje IA guardado');
                    }
                })
                .catch(err => console.error('Error al guardar aprendizaje IA:', err));
        }

        // ========================================
        // FUNCIONES AJAX PARA MÓVIL
        // ========================================

        const mobileImageInput = document.getElementById('imagenes-mobile');
        const mobileCameraInput = document.getElementById('camera-mobile');
        const mobileProcessBtn = document.getElementById('processBtn-mobile');
        const processingIndicator = document.getElementById('processing-mobile');
        const processLabel = document.getElementById('processBtnLabel-mobile');
        const activeButtonClasses = ['bg-blue-600', 'hover:bg-blue-700', 'text-white'];
        const disabledButtonClasses = ['bg-gray-300', 'text-gray-500'];
        let isProcessingMobile = false;

        const setMobileButtonAppearance = (enabled) => {
            if (!mobileProcessBtn) return;
            activeButtonClasses.forEach((cls) => mobileProcessBtn.classList.remove(cls));
            disabledButtonClasses.forEach((cls) => mobileProcessBtn.classList.remove(cls));
            if (enabled) {
                mobileProcessBtn.disabled = false;
                mobileProcessBtn.classList.add(...activeButtonClasses);
            } else {
                mobileProcessBtn.disabled = true;
                mobileProcessBtn.classList.add(...disabledButtonClasses);
            }
        };

        const refreshMobileButton = () => {
            if (isProcessingMobile) return;
            const hasFile = (mobileImageInput?.files?.length > 0) || (mobileCameraInput?.files?.length > 0);
            setMobileButtonAppearance(hasFile);
        };

        // Handlers para selección de archivo móvil
        window.handleMobileFileSelection = function(input) {
            const isCamera = input.id === 'camera-mobile';
            const otherInputId = isCamera ? 'imagenes-mobile' : 'camera-mobile';
            const otherInput = document.getElementById(otherInputId);

            // Limpiar el otro input para evitar confusiones
            if (otherInput) otherInput.value = '';

            const file = input.files[0];
            const feedback = document.getElementById('mobile-file-feedback');
            const nameSpan = document.getElementById('mobile-file-name');

            if (file) {
                feedback.classList.remove('hidden');
                nameSpan.textContent = file.name;
            } else {
                feedback.classList.add('hidden');
            }

            refreshMobileButton();
        };

        window.clearMobileSelection = function() {
            if (mobileImageInput) mobileImageInput.value = '';
            if (mobileCameraInput) mobileCameraInput.value = '';
            document.getElementById('mobile-file-feedback').classList.add('hidden');

            // Limpiar también la memoria del último escaneo
            localStorage.removeItem('lastScanMobileCache');
            window.mobileStepManager.dataCache = {};
            window.mobileStepManager.maxStep = 1;

            refreshMobileButton();
        };

        mobileImageInput?.addEventListener('change', refreshMobileButton);
        mobileCameraInput?.addEventListener('change', refreshMobileButton);
        refreshMobileButton();

        const setProcessingState = (processing) => {
            isProcessingMobile = processing;
            if (!mobileProcessBtn) return;
            if (processing) {
                mobileProcessBtn.classList.remove(...activeButtonClasses);
                mobileProcessBtn.classList.add('bg-blue-800', 'hover:bg-blue-900', 'ring-2', 'ring-blue-400/70');
                mobileProcessBtn.disabled = true;
                processingIndicator?.classList.remove('hidden');
                // processLabel?.classList.add('hidden');
            } else {
                mobileProcessBtn.classList.remove('bg-blue-800', 'hover:bg-blue-900', 'ring-2', 'ring-blue-400/70');
                processingIndicator?.classList.add('hidden');
                // processLabel?.classList.remove('hidden');
                refreshMobileButton();
            }
        };

        const renderMobileStatus = (messages = []) => {
            const banner = document.getElementById('mobile-status-banner');
            const text = document.getElementById('mobile-status-text');
            if (!banner || !text) return;
            if (!messages || messages.length === 0) {
                banner.classList.add('hidden');
                text.textContent = '';
                return;
            }
            banner.classList.remove('hidden');
            text.textContent = messages[messages.length - 1];
        };

        /**
         * Procesar albarán via AJAX (móvil)
         */
        async function procesarAlbaranMobile() {
            const form = document.getElementById('ocrForm-mobile');
            const formData = new FormData(form);

            // Validar que se haya seleccionado proveedor y archivo
            const proveedor = document.getElementById('proveedor-mobile').value;
            const archivoInput = document.getElementById('imagenes-mobile');
            const cameraInput = document.getElementById('camera-mobile');
            const archivo = (archivoInput?.files[0]) || (cameraInput?.files[0]);

            if (!proveedor) {
                toastMobile('warning', 'Por favor selecciona un proveedor');
                return;
            }

            if (!archivo) {
                toastMobile('warning', 'Por favor selecciona una imagen o toma una foto');
                return;
            }

            setProcessingState(true);

            try {
                const response = await fetch("{{ route('albaranes.scan.procesar.ajax') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                console.log('Respuesta AJAX Completa:', data);

                if (data.success) {
                    const rawResultado = data.resultados?.[0] ?? data.data ?? data;
                    const parsedPayload =
                        rawResultado.parsed ||
                        rawResultado.data?.data ||
                        rawResultado.data ||
                        rawResultado || {};
                    const resultadoParaVista = {
                        ...rawResultado,
                        parsed: parsedPayload,
                    };

                    console.log('Datos recibidos correctamente. AI Response:', parsedPayload);

                    // Guardar datos en cache
                    const resultado = data.resultados[0];
                    if (resultado.error || !resultado.parsed) {
                        alert(resultado.error || 'No se pudo extraer datos del albarán.');
                        renderMobileStatus(resultado.status_messages || []);
                        return;
                    }

                    window.mobileStepManager.dataCache = {
                        resultado: resultadoParaVista,
                        distribuidores: data.distribuidores,
                        ocr_log_id: resultadoParaVista?.ocr_log_id ?? null,
                    };

                    renderMobileStatus(data.resultados[0]?.status_messages || []);

                    // console.log('Cache guardado:', window.mobileStepManager.dataCache);

                    // Poblar Vista 2 con los datos recibidos
                    // console.log('Poblando Vista 2...');
                    poblarVista2ConDatos(resultadoParaVista);

                    // Avanzar a Vista 2 (forzar avance para permitir pasar de maxStep)
                    // console.log('Avanzando a Vista 2...');
                    window.mobileStepManager.next(true);

                    // Guardar progreso en memoria después de procesar
                    localStorage.setItem('lastScanMobileCache', JSON.stringify(window.mobileStepManager.dataCache));
                } else if (!data.success) {
                    console.error('Error en respuesta:', data);
                    alert('Error al procesar el albar【n. Por favor, intenta de nuevo.');
                } else {
                    console.warn('Respuesta sin resultados detectados:', data);
                    alert('No se detectaron datos en el albar【n. Reintenta con otra imagen.');
                }

            } catch (error) {
                console.error('Error en petición AJAX:', error);
                alert('Error de conexión. Por favor, verifica tu conexión a internet.');
            } finally {
                setProcessingState(false);
            }
        }

        /**
         * Poblar Vista 2 con datos recibidos (Modo Edición Directa)
         */
        /**
         * Poblar Vista 2 con datos recibidos (Modo Edición Directa)
         */
        function poblarVista2ConDatos(resultado) {
            const parsed = resultado.parsed || resultado.data || resultado || {};
            const sim = resultado.simulacion || {};
            renderMobileStatus(resultado.status_messages || parsed._ai_status || []);

            // Preview de imagen
            const previewContainer = document.getElementById('mobile-preview-container');
            const previewImg = document.getElementById('mobile-preview-img');
            if (previewImg && resultado.preview) {
                previewImg.src = resultado.preview;
                if (previewContainer) previewContainer.classList.remove('hidden');
            }

            // Poblar Inputs del Formulario (Inputs Generales)
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };

            const source = parsed.data ?? parsed;

            // Función helper para leer propiedades anidadas o directas
            const sourceValue = (prop) => {
                let val = source[prop];
                if (val === undefined || val === null) val = parsed[prop];
                // Soporte camelCase si falla snake_case
                if (val === undefined || val === null) {
                    const camel = prop.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
                    val = source[camel] ?? parsed[camel];
                }
                return val ?? '';
            };

            // Normalización robusta de productos
            const productosDetectados = source.productos ?? source.products ?? parsed.productos ?? [];
            if (productosDetectados.length) {
                parsed.productos = productosDetectados.map(prod => ({
                    descripcion: prod.descripcion ?? '',
                    diametro: prod.diametro ?? '',
                    longitud: prod.longitud ?? '',
                    calidad: prod.calidad ?? '',
                    line_items: Array.isArray(prod.line_items || prod.lineItems) ?
                        (prod.line_items || prod.lineItems).map(item => ({
                            colada: item.colada ?? '',
                            bultos: item.bultos ?? '',
                            peso_kg: item.peso_kg ?? item.pesoNeto ?? item.peso ?? '',
                            descargar: true // Por defecto marcados
                        })) : [],
                }));
            }

            // NOTA: No llamamos a actualizarBultosTotalesMobile() aquí porque calcularía 0
            // al leer inputs que aún no existen. Confiamos en los datos del OCR primero.

            console.log('Datos para poblar vista 2:', source);

            setVal('edit-tipo-compra', sourceValue('tipo_compra'));

            // Proveedor/Fabricante: siempre partir del proveedor seleccionado en Step 1
            const proveedorStep1 = document.getElementById('proveedor-mobile') || document.getElementById('proveedor');
            const proveedorSeleccionado = proveedorStep1?.options?.[proveedorStep1.selectedIndex]?.text || proveedorStep1
                ?.value || '';
            const provTexto = sourceValue('proveedor_texto') || sourceValue('proveedorTexto') || sourceValue('proveedor');
            setVal('edit-proveedor', proveedorSeleccionado || provTexto);

            // Inicializar Select de Fabricante (Solo si no estuviéramos en modo distribuidor, pero inicializamos todo)
            const selectProv = document.getElementById('edit-proveedor-select');
            const inputProv = document.getElementById('edit-proveedor');
            const currentProv = proveedorSeleccionado || provTexto || '';

            if (selectProv && currentProv) {
                // Intentar match exacto o aproximado
                let found = false;
                // 1. Exacto
                for (let i = 0; i < selectProv.options.length; i++) {
                    if (selectProv.options[i].value === currentProv) {
                        selectProv.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                // 2. Texto aproximado
                if (!found) {
                    const lower = currentProv.toLowerCase();
                    for (let i = 0; i < selectProv.options.length; i++) {
                        if (selectProv.options[i].text.toLowerCase() === lower || selectProv.options[i].text.toLowerCase()
                            .includes(lower)) {
                            selectProv.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                }

                // Lógica 'otro'
                if (!found) {
                    selectProv.value = 'otro';
                    if (inputProv) inputProv.value = currentProv; // Guardar valor original
                } else {
                    if (inputProv) inputProv.value = selectProv.value;
                }
            } else if (selectProv) {
                selectProv.value = '';
            }

            // Inicializar Select Distribuidor
            const selectDist = document.getElementById('edit-distribuidor-select');
            const distRecomendado = sourceValue('distribuidor_recomendado');

            if (selectDist && distRecomendado) {
                // Intentar seleccionar distribuidor recomendado si existe
                // Si no, default
                selectDist.value = distRecomendado;
                // Si falla asignación directa (no está en la lista), ver si hay match
                if (!selectDist.value) {
                    // Buscar match
                    for (let i = 0; i < selectDist.options.length; i++) {
                        if (selectDist.options[i].text.toLowerCase().includes(distRecomendado.toLowerCase())) {
                            selectDist.selectedIndex = i;
                            break;
                        }
                    }
                }
            }

            // Setear inputs auxiliares por si acaso
            setVal('edit-distribuidor-input', distRecomendado);

            // Determinar tipo inicial
            let initialTipo = 'directo';
            const rawTipo = sourceValue('tipo_compra');
            if (rawTipo) {
                initialTipo = rawTipo.toLowerCase();
            } else if (distRecomendado) {
                initialTipo = 'distribuidor';
            }

            // Inicializar botones y estados UI
            setMobileTipoCompra(initialTipo);

            setVal('edit-albaran', sourceValue('albaran'));
            setVal('edit-fecha', sourceValue('fecha'));
            setVal('edit-pedido-cliente', sourceValue('pedido_cliente') || sourceValue('pedidoCliente'));
            setVal('edit-pedido-codigo', sourceValue('pedido_codigo') || sourceValue('pedidoCodigo'));
            setVal('edit-peso-total', sourceValue('peso_total') || sourceValue('pesoTotal'));
            setVal('edit-bultos-total', sourceValue('bultos_total') || sourceValue('bultosTotal') || '0');



            // Guardar en cache inicial
            window.mobileStepManager.dataCache.parsed = parsed;
            window.mobileStepManager.dataCache.simulacion = sim;

            // Renderizar lista de productos editable
            renderMobileEditProducts();

            // Poblar Vista 3 (pedido)
            poblarVista3ConPedido(sim);

            // Poblar Vista 4 (coladas)
            poblarVista4ConColadas(sim);
        }

        /**
         * Poblar Vista 3 con información del pedido
         */
        function poblarVista3ConPedido(simulacion) {
            const container = document.getElementById('mobile-pedido-card');
            if (!container) return;

            const lineaPropuesta = simulacion.linea_propuesta;
            window.mobileStepManager.dataCache.recommendedId = lineaPropuesta?.id || null;
            const fabricanteNombre = (lineaPropuesta?.fabricante || '').toString().trim();
            const distribuidorNombre = (lineaPropuesta?.distribuidor || '').toString().trim();
            const pedidoHprEscaneado = (window.mobileStepManager?.dataCache?.parsed?.pedido_cliente || '').toString()
                .trim();

            if (!lineaPropuesta) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-gray-500">No se encontró pedido sugerido</p>
                        <p class="text-sm text-gray-400 mt-1">Puedes seleccionar uno manualmente</p>
                    </div>
                `;
                return;
            }

            // Tipo de recomendación
            let badgeClass = 'bg-blue-100 text-blue-700';
            let badgeText = 'RECOMENDADO';

            if (lineaPropuesta.tipo_recomendacion === 'exacta') {
                badgeClass = 'bg-green-100 text-green-700';
                badgeText = 'COINCIDENCIA EXACTA';
            } else if (lineaPropuesta.tipo_recomendacion === 'parcial') {
                badgeClass = 'bg-yellow-100 text-yellow-700';
                badgeText = 'COINCIDENCIA PARCIAL';
            }

            let codeMatchBadge = '';
            if (lineaPropuesta.coincide_codigo) {
                codeMatchBadge = `
                    <div class="absolute top-3 right-3 bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm">
                        <span class="flex items-center gap-1">★ CÓDIGO</span>
                    </div>
                `;
                container.classList.add('relative', 'ring-2', 'ring-emerald-500', 'bg-emerald-50/30');
            } else {
                container.classList.remove('relative', 'ring-2', 'ring-emerald-500', 'bg-emerald-50/30');
            }


            container.innerHTML = `
                ${codeMatchBadge}
                <div class="space-y-3">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${badgeClass}">
                        ${badgeText}
                    </span>

                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Linea pedido (BD):</span>
                            <span class="font-semibold text-gray-900">${lineaPropuesta.codigo_linea || '—'}</span>
                        </div>
                        ${pedidoHprEscaneado ? `
                                                                                                                                                                                                                                                                                                                    <div class="hidden">
                                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Pedido HPR (escaneado):</span>
                                                                                                                                                                                                                                                                                                                        <span class="font-semibold text-gray-900">${pedidoHprEscaneado}</span>
                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                ` : ''}
                        <div class="hidden">
                            <span class="text-gray-500">Pedido (BD):</span>
                            <span class="font-semibold text-gray-900">${lineaPropuesta.pedido_codigo || '—'}</span>
                        </div>
                        ${fabricanteNombre ? `
                                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Fabricante:</span>
                                                                                                                                                                                                                                                                                                                        <span class="font-medium text-gray-900">${fabricanteNombre}</span>
                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                ` : ''}
                        ${distribuidorNombre ? `
                                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Distribuidor:</span>
                                                                                                                                                                                                                                                                                                                        <span class="font-medium text-gray-900">${distribuidorNombre}</span>
                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                ` : ''}
                        <div>
                            <span class="text-gray-500">Producto:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.producto || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Obra:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.obra || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Cantidad pendiente:</span>
                            <span class="font-medium text-gray-900">${lineaPropuesta.cantidad_pendiente || 0} kg</span>
                        </div>
                        ${renderScoreRow(lineaPropuesta)}
                    </div>
                </div>
            `;

            // Guardar línea seleccionada en cache
            window.mobileStepManager.dataCache.lineaSeleccionada = lineaPropuesta;
        }

        function obtenerColadasValidadas() {
            const cache = window.mobileStepManager.dataCache;
            const productos = cache.parsed?.productos || [];
            const normalizadas = [];

            productos.forEach(producto => {
                (producto.line_items || []).forEach(item => {
                    if (!item) return;
                    const codigo = (item.colada || item.codigo || '').toString().trim();
                    if (!codigo) return;
                    const bultos = Number(item.bultos ?? item.cantidad ?? 0);
                    const peso = Number(item.peso_kg ?? item.peso ?? 0);
                    normalizadas.push({
                        colada: codigo,
                        bultos: Number.isNaN(bultos) ? 0 : bultos,
                        peso_kg: Number.isNaN(peso) ? 0 : peso
                    });
                });
            });

            if (normalizadas.length) {
                return normalizadas;
            }

            const parsedLineItems = cache.parsed?.line_items || [];
            if (parsedLineItems.length) {
                return parsedLineItems.map(item => {
                    const bultos = Number(item.bultos ?? 0);
                    const peso = Number(item.peso_kg ?? item.peso ?? 0);
                    return {
                        colada: item.colada || '',
                        bultos: Number.isNaN(bultos) ? 0 : bultos,
                        peso_kg: Number.isNaN(peso) ? 0 : peso
                    };
                }).filter(entry => entry.colada);
            }

            return [];
        }

        /**
         * Poblar Vista 4 con coladas a recepcionar
         */
        function poblarVista4ConColadas(simulacion) {
            const container = document.getElementById('mobile-coladas-container');
            if (!container) return;

            const cache = window.mobileStepManager.dataCache;
            const validadas = obtenerColadasValidadas();
            const simulatedRaw = simulacion?.bultos_simulados || [];
            const simulated = simulatedRaw.map(item => {
                const bultos = Number(item.bultos ?? 0);
                const peso = Number(item.peso_kg ?? item.peso ?? 0);
                return {
                    colada: item.colada || '',
                    bultos: Number.isNaN(bultos) ? 0 : bultos,
                    peso_kg: Number.isNaN(peso) ? 0 : peso
                };
            });
            const coladasDisponibles = validadas.length ? validadas : simulated;

            if (coladasDisponibles.length === 0) {
                container.innerHTML = '<p class="p-4 text-gray-500 text-center">No hay coladas validadas para recibir</p>';
                return;
            }

            const seleccionadasPrevias = cache.coladasSeleccionadas || [];

            container.innerHTML = coladasDisponibles.map((bulto, index) => {
                const pesoDisponibilidad = bulto.peso_kg ? `${bulto.peso_kg.toLocaleString('es-ES')} kg` :
                    'Peso no disponible';
                const isChecked = seleccionadasPrevias.length === 0 || seleccionadasPrevias.some(sel => sel
                    .colada === bulto.colada);
                return `
                    <label class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" class="mobile-colada-checkbox w-5 h-5 text-indigo-600 rounded"
                               data-colada="${bulto.colada || 'ƒ?"'}"
                               data-bultos="${bulto.bultos || 0}"
                               data-peso="${bulto.peso_kg || 0}"
                               onchange="actualizarTotalesColadas()"
                               ${isChecked ? 'checked' : ''}>
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">Colada ${bulto.colada || '-'}</span>
                                <span class="text-indigo-600 font-semibold text-lg">${bulto.bultos || 0} bultos</span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span>${pesoDisponibilidad}</span>
                                <span class="text-xs uppercase tracking-wide text-gray-400">Disponible</span>
                            </div>
                        </div>
                    </label>
                `;
            }).join('');

            // Actualizar totales iniciales (todas marcadas)
            actualizarTotalesColadas();

        }
        /**
         * Actualizar totales de coladas seleccionadas
         */
        function actualizarTotalesColadas() {
            const checkboxes = document.querySelectorAll('.mobile-colada-checkbox:checked');

            let totalBultos = 0;
            let totalPeso = 0;

            checkboxes.forEach(cb => {
                totalBultos += parseInt(cb.dataset.bultos) || 0;
                totalPeso += parseFloat(cb.dataset.peso) || 0;
            });

            // FALLBACK PESO TOTAL: Si todos los checks están marcados y el peso sumado es 0 (o inconsistente),
            // usar el peso_total detectado en el albarán.
            const totalCheckboxes = document.querySelectorAll('.mobile-colada-checkbox');
            const cache = window.mobileStepManager.dataCache;
            if (checkboxes.length > 0 && checkboxes.length === totalCheckboxes.length) {
                const pesoAlbaran = parseFloat(cache.parsed?.peso_total || 0);
                if (pesoAlbaran > 0 && (totalPeso <= 0 || Math.abs(totalPeso - pesoAlbaran) > 0.1)) {
                    totalPeso = pesoAlbaran;
                }
            }

            // Actualizar UI
            const bultosEl = document.getElementById('mobile-bultos-seleccionados');
            const pesoEl = document.getElementById('mobile-peso-seleccionado');

            if (bultosEl) bultosEl.textContent = totalBultos;
            if (pesoEl) pesoEl.textContent = totalPeso.toLocaleString('es-ES') + ' kg';

            // Guardar en cache
            const coladasSeleccionadas = [];
            checkboxes.forEach(cb => {
                coladasSeleccionadas.push({
                    colada: cb.dataset.colada,
                    bultos: parseInt(cb.dataset.bultos) || 0,
                    peso_kg: parseFloat(cb.dataset.peso) || 0
                });
            });

            window.mobileStepManager.dataCache.coladasSeleccionadas = coladasSeleccionadas;
            window.mobileStepManager.dataCache.totalesColadas = {
                bultos: totalBultos,
                peso: totalPeso
            };

            // Poblar Vista 5 con resumen
            actualizarIndicadoresColadas(totalPeso);
            poblarVista5ConResumen();
        }

        function actualizarIndicadoresColadas(totalPeso = 0) {
            const cache = window.mobileStepManager.dataCache;
            const linea = cache.lineaSeleccionada || {};
            const cantidadPendiente = Number(linea.cantidad_pendiente || 0);
            const kilosSeleccionados = totalPeso;
            const kilosRestantes = Math.max(cantidadPendiente - kilosSeleccionados, 0);
            const estado = cantidadPendiente > 0 ?
                (kilosSeleccionados >= cantidadPendiente ? 'Completo' : 'Parcial') :
                'Pendiente';

            const kgRestantesEl = document.getElementById('mobile-kg-restantes');
            const estadoEl = document.getElementById('mobile-estado-pedido');
            const pesoPendienteEl = document.getElementById('mobile-peso-pendiente');
            const textoKg = `${kilosRestantes.toLocaleString('es-ES')} kg`;
            const textoPendiente = `${cantidadPendiente.toLocaleString('es-ES')} kg`;

            if (kgRestantesEl) kgRestantesEl.textContent = textoKg;
            if (estadoEl) estadoEl.textContent = estado;
            if (pesoPendienteEl) pesoPendienteEl.textContent = textoPendiente;

            const resumenKgEl = document.getElementById('mobile-resumen-kg-seleccionados');
            const resumenKgRestantesEl = document.getElementById('mobile-resumen-kg-restantes');
            const resumenEstadoEl = document.getElementById('mobile-resumen-estado');
            if (resumenKgEl) resumenKgEl.textContent = `${kilosSeleccionados.toLocaleString('es-ES')} kg`;
            if (resumenKgRestantesEl) resumenKgRestantesEl.textContent = textoKg;
            if (resumenEstadoEl) resumenEstadoEl.textContent = estado;
        }

        const renderMobileProductColadas = (productos = []) => {
            const container = document.getElementById('mobile-product-groups');
            if (!container) return;
            if (!productos.length) {
                container.innerHTML = '<p class="text-xs text-gray-500">Respuesta AJAX: sin productos detectados.</p>';
                return;
            }

            const formatPeso = (valor) => {
                if (valor === undefined || valor === null || valor === '') {
                    return 'kg ?';
                }
                const numerico = Number(valor);
                if (Number.isNaN(numerico)) {
                    return 'kg ?';
                }
                return `${numerico.toLocaleString('es-ES')} kg`;
            };

            container.innerHTML = productos.map((producto) => {
                const lineItems = producto.line_items || [];
                const listItems = lineItems.map((colada) => `
                    <li class="flex justify-between text-[0.75rem] text-gray-700">
                        <span>${colada.colada || '—'}</span>
                        <span>${colada.bultos || 0} bultos</span>
                        <span class="text-gray-500">${formatPeso(colada.peso_kg ?? colada.peso)}</span>
                    </li>
                `).join('');

                return `
                    <div class="bg-white border border-gray-100 rounded-lg p-3 space-y-2 text-[0.8rem]">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold uppercase">${producto.descripcion || producto.producto || 'Producto'}</span>
                            <span class="text-gray-400">${producto.calidad || ''}</span>
                        </div>
                        <div class="flex gap-4 text-gray-500">
                            <span>Ø${producto.diametro || '—'}</span>
                            <span>${producto.longitud ? `${producto.longitud} m` : 'Longitud —'}</span>
                        </div>
                        <ul class="space-y-1 pt-2 border-t border-gray-100">
                            ${listItems}
                        </ul>
                    </div>
                `;
            }).join('');
        };

        /**
         * Poblar Vista 5 con resumen final
         */
        function poblarVista5ConResumen() {
            const cache = window.mobileStepManager.dataCache;
            const parsed = cache.parsed || {};
            const linea = cache.lineaSeleccionada || {};
            const coladas = cache.coladasSeleccionadas || [];
            const totales = cache.totalesColadas || {};

            // Actualizar datos del albarán
            const albaranEl = document.getElementById('mobile-resumen-albaran');
            const pedidoEl = document.getElementById('mobile-resumen-pedido');
            const coladasEl = document.getElementById('mobile-resumen-coladas');

            if (albaranEl) {
                albaranEl.textContent = `${parsed.albaran || '—'} (${parsed.fecha || '—'})`;
            }

            if (pedidoEl) {
                pedidoEl.textContent = `${linea.pedido_codigo || '—'} - ${linea.producto || '—'}`;
            }

            if (coladasEl) {
                if (coladas.length > 0) {
                    const resumen =
                        `${coladas.length} colada(s) - ${totales.bultos || 0} bultos - ${(totales.peso || 0).toLocaleString('es-ES')} kg`;
                    coladasEl.textContent = resumen;
                } else {
                    coladasEl.textContent = 'No hay coladas seleccionadas';
                }
            }
        }

        /**
         * Abrir modal de edición
         */
        /**
         * Abrir modal de edición
         */
        /**
         * Sincroniza los valores de los inputs de productos al cache
         */
        function syncMobileProductsFromDOMToCache() {
            const cache = window.mobileStepManager.dataCache;
            if (!cache.parsed) return;

            const productos = [];
            document.querySelectorAll('.mobile-edit-product').forEach((productEl) => {
                const producto = {
                    descripcion: productEl.querySelector('[data-product-field="descripcion"]')?.value || '',
                    diametro: productEl.querySelector('[data-product-field="diametro"]')?.value || null,
                    longitud: productEl.querySelector('[data-product-field="longitud"]')?.value || null,
                    calidad: productEl.querySelector('[data-product-field="calidad"]')?.value || '',
                    line_items: []
                };

                productEl.querySelectorAll('.mobile-edit-colada').forEach((coladaEl) => {
                    producto.line_items.push({
                        colada: coladaEl.querySelector('[data-colada-field="colada"]')?.value || '',
                        bultos: coladaEl.querySelector('[data-colada-field="bultos"]')?.value || '',
                        peso_kg: coladaEl.querySelector('[data-colada-field="peso"]')?.value || '',
                        descargar: coladaEl.querySelector('[data-colada-field="descargar"]')
                            ?.checked || false
                    });
                });

                productos.push(producto);
            });

            cache.parsed.productos = productos;

            actualizarBultosTotalesMobile();
        }

        function calcularBultosTotalesMobile() {
            const inputs = document.querySelectorAll('[data-colada-field=\"bultos\"]');
            let total = 0;
            inputs.forEach((input) => {
                total += parseInt(input.value || '0', 10) || 0;
            });
            return total;
        }

        function actualizarBultosTotalesMobile() {
            const total = calcularBultosTotalesMobile();
            const input = document.getElementById('edit-bultos-total');
            if (input) {
                input.value = total;
            }
            // Fix: Usar referencia correcta a dataCache
            const currentCache = window.mobileStepManager?.dataCache;
            if (currentCache && currentCache.parsed) {
                currentCache.parsed.bultos_total = total;
            }
        }

        // Función para mostrar/ocultar input "otro" fabricante



        /**
         * Guardar datos de Step 2 y Continuar
         */
        async function guardarYContinuarStep2() {
            const cache = window.mobileStepManager.dataCache;
            setMobileActionLoading('mobile-step2-confirm-btn', 'mobile-step2-confirm-loading', true);

            try {
                // Actualizar cache con valores de inputs generales
                if (!cache.parsed) cache.parsed = {};

                cache.parsed.tipo_compra = document.getElementById('edit-tipo-compra').value || null;
                cache.parsed.tipo_compra = document.getElementById('edit-tipo-compra').value || null;

                // Capturar fabricante correctamente:
                // Si select != 'otro' y tiene valor, usamos ese. Si es 'otro', usamos el input.
                const selectProv = document.getElementById('edit-proveedor-select');
                const inputProv = document.getElementById('edit-proveedor');
                let proveedorFinal = '';

                if (selectProv && selectProv.value && selectProv.value !== 'otro') {
                    proveedorFinal = selectProv.value;
                } else if (inputProv) {
                    proveedorFinal = inputProv.value;
                }
                cache.parsed.proveedor_texto = proveedorFinal || null;

                // Determinar cuál distribuidor tomar
                const tipo = cache.parsed.tipo_compra;
                if (tipo === 'distribuidor') {
                    cache.parsed.distribuidor_recomendado = document.getElementById('edit-distribuidor-select')
                        .value || null;
                } else if (tipo === 'directo') {
                    cache.parsed.distribuidor_recomendado = null; // Desaparece
                } else {
                    // Fallback o caso no contemplado, limpiamos
                    cache.parsed.distribuidor_recomendado = null;
                }

                cache.parsed.albaran = document.getElementById('edit-albaran').value;
                cache.parsed.fecha = document.getElementById('edit-fecha').value;
                cache.parsed.pedido_cliente = document.getElementById('edit-pedido-cliente').value;
                cache.parsed.pedido_codigo = document.getElementById('edit-pedido-codigo').value;
                cache.parsed.peso_total = document.getElementById('edit-peso-total').value;
                cache.parsed.bultos_total = document.getElementById('edit-bultos-total').value;

                // Sincronizar productos
                syncMobileProductsFromDOMToCache();

                // Generar lista de coladas seleccionadas para descarga (Step 4 necesita esto)
                const todasLasColadas = [];
                let totalBultos = 0;
                let totalPeso = 0;
                (cache.parsed.productos || []).forEach(prod => {
                    (prod.line_items || []).forEach(item => {
                        if (item.descargar !== false) {
                            const b = Number(item.bultos || 0);
                            const p = Number(item.peso_kg || 0);
                            todasLasColadas.push({
                                colada: item.colada,
                                bultos: b,
                                peso_kg: p
                            });
                            totalBultos += b;
                            totalPeso += p;
                        }
                    });
                });
                cache.coladasSeleccionadas = todasLasColadas;
                cache.totalesColadas = {
                    bultos: totalBultos,
                    peso: totalPeso
                };

                // Actualizar resultado con los cambios
                if (cache.resultado) {
                    cache.resultado.parsed = cache.parsed;
                }

                // La búsqueda en BD se hace usando el valor de "Pedido cliente" (input edit-pedido-cliente)
                await verificarPedidoCodigoEnBdMobile(cache.parsed.pedido_cliente);

                // Recalcular recomendación y lista con los datos editados antes de entrar a Step 3
                await recalcularSimulacionMobile();
                if (cache.simulacion) {
                    poblarVista3ConPedido(cache.simulacion);
                }

                // Recalcular cosas si fuera necesario, o simplemente avanzar
                window.mobileStepManager.next();
            } finally {
                setMobileActionLoading('mobile-step2-confirm-btn', 'mobile-step2-confirm-loading', false);
            }
        }

        function normalizarCodigoPedidoMobile(value) {
            return (value || '').toString().toLowerCase().replace(/\s+/g, '');
        }

        function formatearKg(value) {
            const numberValue = Number(value);
            if (!Number.isFinite(numberValue)) return '—';
            return `${numberValue.toLocaleString('es-ES', { maximumFractionDigits: 2 })} kg`;
        }

        // Validar si el texto actual es un numero decimal
        function isNumeric(str) {
            if (typeof str != "string") return false;
            return !isNaN(str) && !isNaN(parseFloat(str));
        }

        // Función simplificada para manejo de cambio de tipo
        window.setMobileTipoCompra = function(tipo) {
            const input = document.getElementById('edit-tipo-compra');
            if (input) input.value = tipo;

            // Actualizar botones UI
            const btnDirecto = document.getElementById('btn-tipo-directo');
            const btnDistribuidor = document.getElementById('btn-tipo-distribuidor');

            const activeClass = "bg-indigo-600 text-white shadow-inner";
            const inactiveClass = "bg-white text-gray-600 hover:bg-gray-50";

            if (btnDirecto) btnDirecto.className =
                `flex-1 py-2 text-sm font-bold transition-colors ${tipo === 'directo' ? activeClass : inactiveClass}`;
            if (btnDistribuidor) btnDistribuidor.className =
                `flex-1 py-2 text-sm font-bold transition-colors ${tipo === 'distribuidor' ? activeClass : inactiveClass}`;

            // Contenedores
            const containerDist = document.getElementById('container-distribuidor-mobile');
            const containerFab = document.getElementById('container-fabricante-mobile');

            // Inputs
            const selectDist = document.getElementById('edit-distribuidor-select');
            const inputDist = document.getElementById('edit-distribuidor-input'); // Backup hidden
            const selectProv = document.getElementById('edit-proveedor-select');
            const inputProv = document.getElementById('edit-proveedor'); // Hidden real value

            if (tipo === 'directo') {
                // MODO DIRECTO: Mostrar Fabricante, Ocultar Distribuidor
                if (containerDist) containerDist.classList.add('hidden');
                if (containerFab) containerFab.classList.remove('hidden');

                // Sincronizar Fabricante desde Step 1
                const proveedorStep1 = document.getElementById('proveedor-mobile') || document.getElementById(
                    'proveedor');
                if (proveedorStep1 && selectProv) {
                    const selectedText = proveedorStep1.options[proveedorStep1.selectedIndex]?.text;
                    const selectedVal = proveedorStep1.value;

                    // Intentar encontrar valor exacto en el select
                    let found = false;
                    // 1. Verificar si el value coincide (ej. 'megasa')
                    for (let i = 0; i < selectProv.options.length; i++) {
                        if (selectProv.options[i].value === selectedVal || selectProv.options[i].text ===
                            selectedText) {
                            selectProv.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        // Si no encuentra, marcar como 'otro' y asignar valor al hidden input
                        selectProv.value = 'otro';
                        if (inputProv) inputProv.value = selectedText; // Guardar el texto original aunque sea 'otro'
                    } else {
                        // Si encuentra, sincronizar input hidden
                        if (inputProv) inputProv.value = selectProv.value;
                    }
                } else if (inputProv && selectProv) {
                    // Sync general si no hay step 1 (raro)
                    inputProv.value = selectProv.value;
                }

                if (inputDist) inputDist.value = ''; // Limpiar distribuidor

            } else {
                // MODO DISTRIBUIDOR: Ocultar Fabricante, Mostrar Distribuidor
                if (containerDist) containerDist.classList.remove('hidden');
                if (containerFab) containerFab.classList.add('hidden');

                // Limpiar fabricante (backend lo ignorará o lo seteará null)
                // Opcional: Podríamos dejarlo si el usuario quiere guardar esa info, pero lo pedido es que desaparezca.
                if (inputProv) inputProv.value = '';
            }

            // Persistir cambios
            if (window.mobileStepManager?.dataCache?.parsed) {
                window.mobileStepManager.dataCache.parsed.tipo_compra = tipo;
                // Guardar en localStorage para persistencia tras refresh
                localStorage.setItem('lastScanMobileCache', JSON.stringify(window.mobileStepManager.dataCache));
            }
            // Recalcular simulación
            if (window.mobileStepManager?.dataCache?.simulacion) {
                recalcularSimulacionMobile().then(sim => {
                    if (sim) poblarVista3ConPedido(sim);
                });
            }
        };

        function formatScorePoints(score) {
            const n = Number(score);
            if (!Number.isFinite(n)) return '—';
            return Math.round(n).toLocaleString('es-ES');
        }

        function renderScoreRow(linea) {
            // No mostrar scores visualmente para simplificar la UI
            return "";
        }

        function setMobileActionLoading(buttonId, loadingId, isLoading) {
            const btn = document.getElementById(buttonId);
            const loading = document.getElementById(loadingId);
            if (!btn) return;
            btn.disabled = !!isLoading;
            if (loading) loading.classList.toggle('hidden', !isLoading);
            btn.classList.toggle('opacity-80', !!isLoading);
            btn.classList.toggle('cursor-not-allowed', !!isLoading);
        }

        function toastMobile(type, title) {
            if (window.Swal?.fire) {
                Swal.fire({
                    toast: true,
                    position: 'top',
                    icon: type,
                    title,
                    showConfirmButton: false,
                    timer: type === 'success' ? 2600 : 3200,
                    timerProgressBar: true,
                });
                return;
            }
            alert(title);
        }

        function renderPedidoDbBannerMobile(payload, originalInput) {
            const banner = document.getElementById('mobile-pedido-db-banner');
            if (!banner) return;

            const inputTrim = (originalInput || '').toString().trim();

            const setBanner = ({
                bg,
                border,
                text,
                html
            }) => {
                banner.className = `rounded-lg border px-3 py-2 text-sm ${bg} ${border} ${text}`;
                banner.innerHTML = html;
                banner.classList.remove('hidden');
            };

            if (!inputTrim) {
                setBanner({
                    bg: 'bg-red-50',
                    border: 'border-red-200',
                    text: 'text-red-900',
                    html: `<div class="flex items-center justify-between gap-2">
                        <span class="font-semibold">Pedido no verificable</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Sin código</span>
                    </div>`,
                });
                return;
            }

            if (!payload || payload.exists !== true) {
                if (payload?.reason === 'diametro_mismatch') {
                    const diametros = Array.isArray(payload?.diametros) ? payload.diametros.join(', ') : '';
                    setBanner({
                        bg: 'bg-red-50',
                        border: 'border-red-200',
                        text: 'text-red-900',
                        html: `<div class="flex items-center justify-between gap-2">
                            <span class="font-semibold">Pedido encontrado, pero no coincide el di\u00e1metro</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Incompatible</span>
                        </div>
                        <div class="mt-1 text-xs text-red-700">B\u00fasqueda: <span class="font-mono">${inputTrim}</span></div>
                        <div class="mt-1 text-xs text-red-700">Di\u00e1metros del albar\u00e1n: <span class="font-mono">${diametros || '—'}</span></div>`,
                    });
                    return;
                }
                setBanner({
                    bg: 'bg-red-50',
                    border: 'border-red-200',
                    text: 'text-red-900',
                    html: `<div class="flex items-center justify-between gap-2">
                        <span class="font-semibold">No hay líneas en BD</span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">No existe</span>
                    </div>
                    <div class="mt-1 text-xs text-red-700">Búsqueda: <span class="font-mono">${inputTrim}</span></div>`,
                });
                return;
            }

            const lineas = payload.lineas || [];
            const cache = window.mobileStepManager.dataCache || {};
            const selectedLineId = cache.lineaSeleccionada?.id;
            const bestLineId = payload.best_linea_id;
            const linea =
                (selectedLineId ? lineas.find(l => l.id === selectedLineId) : null) ||
                (bestLineId ? lineas.find(l => l.id === bestLineId) : null) ||
                lineas[0] || {};

            const estadoLinea = (linea.estado || '').toString().toLowerCase();
            const isCompleted = ['completado', 'completada', 'facturado', 'facturada', 'cancelado', 'cancelada'].includes(
                estadoLinea);

            const bg = isCompleted ? 'bg-yellow-50' : 'bg-green-50';
            const border = isCompleted ? 'border-yellow-200' : 'border-green-200';
            const text = isCompleted ? 'text-yellow-900' : 'text-green-900';
            const pillBg = isCompleted ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700';
            const pillText = linea.estado || 'OK';

            const pedido = linea.pedido || {};
            const producto = linea.producto || {};
            const cantidad = linea.cantidad;
            const recep = linea.cantidad_recepcionada;
            const diam = producto.diametro;
            const empresa = pedido.fabricante || pedido.distribuidor || '—';

            setBanner({
                bg,
                border,
                text,
                html: `<div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">Línea encontrada en BD</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold ${pillBg}">${pillText}</span>
                </div>
                <div class="mt-1 text-xs opacity-90">
                    Pedido: <span class="font-mono">${pedido.codigo || inputTrim}</span> · Línea: <span class="font-mono">${linea.codigo_linea || '—'}</span> · Ø${diam || '—'} · ${empresa}
                </div>
                <div class="mt-1 text-xs opacity-90">
                    Cant. línea: ${formatearKg(cantidad)} · Recep.: ${formatearKg(recep)} · Total pedido: ${formatearKg(pedido.peso_total)}
                </div>`,
            });
        }

        async function verificarPedidoCodigoEnBdMobile(pedidoCodigo) {
            const cache = window.mobileStepManager.dataCache;
            const codigo = (pedidoCodigo || '').toString();
            cache.lastPedidoLookupCodigo = codigo;

            const normalized = normalizarCodigoPedidoMobile(codigo);
            if (!normalized) {
                cache.pedidoDbInfo = null;
                renderPedidoDbBannerMobile(null, codigo);
                return;
            }

            try {
                const response = await fetch("{{ route('albaranes.scan.pedido.lookup') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        codigo,
                        diametros: (cache.parsed?.productos || []).map(p => p?.diametro).filter(
                            Boolean),
                    }),
                });

                const data = await response.json();
                cache.pedidoDbInfo = data;
                const best = (data?.lineas || []).find(l => l.id === data?.best_linea_id) || data?.lineas?.[0];
                console.log('Línea BD (estado real):', best?.estado ?? '(no encontrado)', best, data);
                renderPedidoDbBannerMobile(data, codigo);
            } catch (error) {
                cache.pedidoDbInfo = null;
                console.log('Línea BD (estado real): (error/no encontrado)', error);
                renderPedidoDbBannerMobile(null, codigo);
            }
        }

        async function recalcularSimulacionMobile() {
            const cache = window.mobileStepManager.dataCache;
            if (!cache?.parsed) return null;

            const proveedorStep1 = document.getElementById('proveedor-mobile') || document.getElementById('proveedor');
            const proveedor = proveedorStep1?.value || cache.parsed?.proveedor || null;

            try {
                const response = await fetch("{{ route('albaranes.scan.simular') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        proveedor,
                        parsed: cache.parsed,
                    }),
                });
                const data = await response.json();
                if (data?.success && data?.simulacion) {
                    cache.simulacion = data.simulacion;
                    if (cache.resultado) {
                        cache.resultado.simulacion = data.simulacion;
                    }
                    return data.simulacion;
                }
            } catch (e) {
                // no-op: mantenemos la simulación anterior
            }

            return null;
        }

        function attachColadaInputListener(container) {
            if (!container) return;
            if (container._coladaListenerAttached) return;
            container._coladaListenerAttached = true;
            container.addEventListener('input', (event) => {
                if (event.target.matches('[data-colada-field="bultos"]')) {
                    actualizarBultosTotalesMobile();
                }
            });
        }

        function setMobileDescription(index, val) {
            syncMobileProductsFromDOMToCache();
            const cache = window.mobileStepManager.dataCache;
            if (cache.parsed && cache.parsed.productos && cache.parsed.productos[index]) {
                cache.parsed.productos[index].descripcion = val;
                if (val === 'ENCARRETADO') {
                    cache.parsed.productos[index].longitud = '';
                }
                renderMobileEditProducts();
            }
        }

        function renderMobileEditProducts() {
            const container = document.getElementById('mobile-edit-products');
            const cache = window.mobileStepManager.dataCache;
            let productos = cache.parsed?.productos || [];

            // BACKWARD COMPATIBILITY: Si no viene "productos" pero sí "line_items" sueltos
            if (productos.length === 0 && cache.parsed && cache.parsed.line_items && cache.parsed.line_items.length > 0) {
                // Crear un producto ficticio con los datos generales
                const productoUnico = {
                    descripcion: cache.parsed.producto?.descripcion || '',
                    diametro: cache.parsed.producto?.diametro || '',
                    longitud: cache.parsed.producto?.longitud || '',
                    calidad: cache.parsed.producto?.calidad || '',
                    line_items: cache.parsed.line_items
                };
                productos = [productoUnico];
                // Actualizar cache para que las siguientes funciones trabajen bien
                cache.parsed.productos = productos;
            }

            if (!container) return;

            if (!productos.length) {
                container.innerHTML = '<p class="text-sm text-gray-500">No hay productos detectados aún.</p>';
                return;
            }

            const renderLineItems = (lineItems, productIndex) => {
                if (!lineItems.length) {
                    return '<p class="text-[0.75rem] text-gray-400">Sin coladas registradas.</p>';
                }

                return lineItems.map((colada, colIndex) => `
                    <div class="mobile-edit-colada grid grid-cols-8 gap-2 items-center" data-colada-index="${colIndex}">
                        <div class="col-span-1 flex flex-col items-center justify-end pb-2 h-full">
                             <input type="checkbox" 
                                class="w-5 h-5 text-indigo-600 rounded mobile-colada-check focus:ring-indigo-500 border-gray-300" 
                                data-colada-field="descargar"
                                ${colada.descargar !== false ? 'checked' : ''} 
                             >
                        </div>
                        <div class="col-span-4">
                            <span class="text-[0.65rem] text-gray-500">Colada</span>
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm px-2 py-1.5" data-colada-field="colada" value="${colada.colada || ''}">
                        </div>
                        <div class="col-span-2">
                            <span class="text-[0.65rem] text-gray-500">Bultos</span>
                            <input type="number" min="0" step="1" class="mt-1 w-full rounded-lg border-gray-300 text-sm px-2 py-1.5" data-colada-field="bultos" value="${colada.bultos || ''}">
                        </div>
                        <div class="col-span-0 hidden">
                            <span class="text-[0.65rem] text-gray-500">Peso (kg)</span>
                            <input type="number" step="0.01" class="mt-1 w-full rounded-lg border-gray-300 text-sm px-2 py-1.5" data-colada-field="peso" value="${colada.peso_kg || ''}">
                        </div>
                        <div class="col-span-1 flex justify-center pt-6">
                            <button type="button" class="text-red-500 text-lg font-bold hover:text-red-700" onclick="eliminarColadaMobile(${productIndex}, ${colIndex})">&times;</button>
                        </div>
                    </div>
                `).join('');
            };

            container.innerHTML = productos.map((producto, productIndex) => {
                const descUpper = (producto.descripcion || '').toUpperCase();
                const isEncarretado = descUpper.includes('ENCARRETADO') || descUpper.includes('ROLLO') || descUpper
                    .includes('BOBINA');
                const val = isEncarretado ? 'ENCARRETADO' : 'BARRAS';

                return `
                <div class="border border-gray-200 rounded-2xl p-4 bg-white space-y-3 mobile-edit-product" data-product-index="${productIndex}">
                    <div class="flex items-center justify-between">
                        <h5 class="text-sm font-semibold text-gray-900">Producto ${productIndex + 1}</h5>
                        <button type="button" class="text-xs text-red-500 font-bold" onclick="eliminarProductoMobile(${productIndex})">Eliminar</button>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                             <span class="text-[0.75rem] text-gray-500 block mb-1">Descripción</span>
                             <input type="hidden" data-product-field="descripcion" value="${val}">
                             <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                                <button type="button" 
                                    class="flex-1 py-2 text-xs font-bold transition-colors ${val === 'ENCARRETADO' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'}"
                                    onclick="setMobileDescription(${productIndex}, 'ENCARRETADO')">
                                    ENCARRETADO
                                </button>
                                <div class="w-px bg-gray-300"></div>
                                <button type="button" 
                                    class="flex-1 py-2 text-xs font-bold transition-colors ${val === 'BARRAS' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'}"
                                    onclick="setMobileDescription(${productIndex}, 'BARRAS')">
                                    BARRAS
                                </button>
                             </div>
                        </div>

                        <label class="text-[0.75rem] text-gray-500 block w-full">Diámetro
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300" data-product-field="diametro" value="${producto.diametro || ''}">
                        </label>
                        
                        <div class="${val === 'ENCARRETADO' ? 'hidden' : 'block'} w-full">
                            <label class="text-[0.75rem] text-gray-500 block w-full">Longitud
                                <input type="text" class="mt-1 w-full rounded-lg border-gray-300" data-product-field="longitud" value="${producto.longitud || ''}">
                            </label>
                        </div>

                        <label class="text-[0.75rem] text-gray-500 hidden">Calidad
                            <input type="text" class="mt-1 w-full rounded-lg border-gray-300" data-product-field="calidad" value="${producto.calidad || ''}">
                        </label>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Coladas</span>
                            <button type="button" class="text-xs font-semibold text-indigo-600" onclick="agregarColadaMobile(${productIndex})">+ Añadir colada</button>
                        </div>
                        <div class="space-y-2">
                            ${renderLineItems(producto.line_items || [], productIndex)}
                        </div>
                    </div>
                </div>
            `;
            }).join('');

            actualizarBultosTotalesMobile(productos);
            attachColadaInputListener(container);
        }

        function agregarProductoMobile() {
            syncMobileProductsFromDOMToCache();
            const cache = window.mobileStepManager.dataCache;
            if (!cache.parsed) cache.parsed = {};
            cache.parsed.productos = cache.parsed.productos || [];
            cache.parsed.productos.push({
                descripcion: '',
                diametro: '',
                longitud: '',
                calidad: '',
                line_items: []
            });
            renderMobileEditProducts();
        }

        function eliminarProductoMobile(index) {
            syncMobileProductsFromDOMToCache();
            const cache = window.mobileStepManager.dataCache;
            if (!cache.parsed?.productos) return;
            cache.parsed.productos.splice(index, 1);
            renderMobileEditProducts();
        }

        function agregarColadaMobile(productIndex) {
            syncMobileProductsFromDOMToCache();
            const cache = window.mobileStepManager.dataCache;
            const productos = cache.parsed?.productos || [];
            if (!productos[productIndex]) return;
            productos[productIndex].line_items = productos[productIndex].line_items || [];
            productos[productIndex].line_items.push({
                colada: '',
                bultos: '',
                peso_kg: '',
                descargar: true
            });
            renderMobileEditProducts();
        }

        function eliminarColadaMobile(productIndex, colIndex) {
            syncMobileProductsFromDOMToCache();
            const cache = window.mobileStepManager.dataCache;
            const productos = cache.parsed?.productos || [];
            if (!productos[productIndex]?.line_items) return;
            productos[productIndex].line_items.splice(colIndex, 1);
            renderMobileEditProducts();
        }


        // ========================================
        // FUNCIONES MODAL DE PEDIDOS (MÓVIL)
        // ========================================

        /**
         * Abrir modal de pedidos
         */
        function abrirModalPedidosMobile() {
            const modal = document.getElementById('mobilePedidosModal');
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
                document.body.style.overflow = 'hidden';
            }

            const scannedEl = document.getElementById('mobilePedidosModalScanned');
            const scanned = (window.mobileStepManager?.dataCache?.parsed?.pedido_cliente || '').toString().trim();
            if (scannedEl) {
                scannedEl.textContent = scanned ? `Pedido HPR (escaneado): ${scanned}` : '';
            }

            // Poblar lista de pedidos
            poblarListaPedidosMobile();
        }

        /**
         * Cerrar modal de pedidos
         */
        function cerrarModalPedidosMobile() {
            const modal = document.getElementById('mobilePedidosModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 300);
                document.body.style.overflow = '';
            }
        }

        /**
         * Poblar lista de pedidos con líneas pendientes
         */
        function normalizarDiametro(valor) {
            if (valor === undefined || valor === null) return null;
            const limpio = valor.toString().replace(/[^\d,.-]/g, '').replace(',', '.');
            const num = parseFloat(limpio);
            return Number.isFinite(num) ? num : null;
        }

        function obtenerDiametrosObjetivo() {
            const cache = window.mobileStepManager.dataCache;
            const parsed = cache.parsed || {};
            const set = new Set();

            const agregar = (valor) => {
                const diam = normalizarDiametro(valor);
                if (diam !== null) {
                    set.add(diam);
                }
            };

            (parsed.productos || []).forEach(producto => agregar(producto.diametro));
            agregar(parsed.diametro);
            if (parsed.producto && parsed.producto.diametro) {
                agregar(parsed.producto.diametro);
            }

            return set;
        }

        function filtrarLineasPorDiametro(lineas = [], diamSet) {
            if (!diamSet || diamSet.size === 0) {
                return lineas;
            }
            return lineas.filter(linea => {
                const diam = normalizarDiametro(linea.diametro);
                return diam !== null && diamSet.has(diam);
            });
        }

        function poblarListaPedidosMobile() {
            const container = document.getElementById('mobile-lista-pedidos');
            if (!container) return;

            const cache = window.mobileStepManager.dataCache;
            const recommendedId = cache.recommendedId || cache.simulacion?.linea_propuesta?.id || null;
            const simulacion = cache.simulacion || {};
            const lineasPendientes = simulacion.lineas_pendientes || [];
            const diametrosObjetivo = obtenerDiametrosObjetivo();
            const lineasFiltradas = diametrosObjetivo.size ?
                filtrarLineasPorDiametro(lineasPendientes, diametrosObjetivo) :
                lineasPendientes;
            const lineasParaMostrar = lineasFiltradas;
            cache.lineasFiltradas = lineasParaMostrar;
            const hayFiltroActivo = diametrosObjetivo.size > 0;

            if (lineasParaMostrar.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-gray-500 font-medium">No hay pedidos disponibles</p>
                        <p class="text-sm text-gray-400 mt-1">${hayFiltroActivo ? 'Ningún pedido coincide con el diámetro detectado' : 'No se encontraron líneas de pedido pendientes'}</p>
                    </div>
                `;
                return;
            }

            // Crear cards de pedidos
            container.innerHTML = lineasParaMostrar.map((linea, index) => {
                const fabricanteNombre = (linea?.fabricante || '').toString().trim();
                const distribuidorNombre = (linea?.distribuidor || '').toString().trim();
                const isSelected = cache.lineaSeleccionada && cache.lineaSeleccionada.id === linea.id;
                const isRecommended = recommendedId && linea.id === recommendedId;

                const badges = [];
                if (isSelected) {
                    badges.push(`
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Seleccionado
                        </span>
                    `);
                }
                if (isRecommended) {
                    badges.push(`
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                             ✨ IA RECOMENDADO
                        </span>
                    `);

                    if (linea.ia_razonamiento) {
                        badges.push(`
                            <div class="text-[10px] text-emerald-800 bg-emerald-50 p-2 rounded-lg border border-emerald-100 mt-1 italic">
                                <b>¿Por qué?</b> ${linea.ia_razonamiento}
                            </div>
                        `);
                    }
                }

                return `
                    <div class="bg-white border-2 ${isSelected ? 'border-indigo-600' : 'border-gray-200'} rounded-lg p-4 cursor-pointer hover:border-indigo-400 transition"
                         onclick="seleccionarPedidoMobile(${index})">
                        ${badges.length ? `<div class="flex items-center gap-2 mb-2">${badges.join('')}</div>` : ''}

                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Linea pedido (BD):</span>
                            <span class="ml-2 font-semibold text-gray-900">${linea.codigo_linea || '—'}</span>
                        </div>
                        <div class="hidden">
                            <span class="text-gray-500">Pedido (BD):</span>
                            <span class="ml-2 text-gray-900">${linea.pedido_codigo || '—'}</span>
                        </div>
                            ${fabricanteNombre ? `
                                                                                                                                                                                                                                                                                                                        <div>
                                                                                                                                                                                                                                                                                                                            <span class="text-gray-500">Fabricante:</span>
                                                                                                                                                                                                                                                                                                                            <span class="ml-2 text-gray-900">${fabricanteNombre}</span>
                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                    ` : ''}
                            ${distribuidorNombre ? `
                                                                                                                                                                                                                                                                                                                        <div>
                                                                                                                                                                                                                                                                                                                            <span class="text-gray-500">Distribuidor:</span>
                                                                                                                                                                                                                                                                                                                            <span class="ml-2 text-gray-900">${distribuidorNombre}</span>
                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                    ` : ''}
                            <div>
                                <span class="text-gray-500">Producto:</span>
                                <span class="ml-2 text-gray-900">${linea.producto || '—'}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">F. Entrega:</span>
                                <span class="ml-2 text-gray-900">${linea.fecha_entrega_fmt || linea.fecha_entrega || '—'}</span>
                            </div>
                            ${linea.obra ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <span class="text-gray-500">Obra:</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <span class="ml-2 text-gray-900">${linea.obra}</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ` : ''}
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                <div>
                                        <span class="text-gray-500">Pendiente:</span>
                                        <span class="ml-2 font-bold text-gray-900">${linea.cantidad_pendiente || 0} kg</span>
                                    </div>
                                    ${linea.score ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 font-bold">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    Score: ${formatScorePoints(linea.score)} pts.
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        /**
         * Seleccionar pedido alternativo
         */
        function seleccionarPedidoMobile(index) {
            const cache = window.mobileStepManager.dataCache;
            const simulacion = cache.simulacion || {};
            const lineasPendientes = cache.lineasFiltradas || simulacion.lineas_pendientes || [];

            if (index < 0 || index >= lineasPendientes.length) return;

            const lineaSeleccionada = lineasPendientes[index];
            const recommendedId = cache.recommendedId || cache.simulacion?.linea_propuesta?.id || null;

            // Si hay discrepancia, pediremos el motivo al confirmar (Step 3 a 4) 
            // o podrías pedirlo aquí mismo en un modal.
            // Para una experiencia fluida, lo pediremos en un modal si NO es el recomendado.
            if (recommendedId && lineaSeleccionada.id != recommendedId) {
                // Preparamos los datos para el modal de motivo
                document.getElementById('motivo-pedido-actual').textContent = lineaSeleccionada.pedido_codigo ||
                    'Pedido ID: ' + lineaSeleccionada.id;
                document.getElementById('motivo-cambio-modal').classList.remove('hidden');
                document.getElementById('motivo-cambio-modal').classList.add('flex');

                // Guardamos temporalmente el ID seleccionado para usarlo al confirmar el motivo
                window._tempPendingSelectionIndex = index;
                return; // Pausamos selección hasta que dé el motivo
            }

            confirmarSeleccionPedidoMobile(lineaSeleccionada);
        }

        function confirmarSeleccionPedidoMobile(linea) {
            const cache = window.mobileStepManager.dataCache;
            cache.lineaSeleccionada = linea;
            cache.motivoDiscrepanciaIA = window._lastUserReason || null;
            window._lastUserReason = null; // Limpiar

            actualizarVista3ConLineaSeleccionada(linea);
            if (cache.pedidoDbInfo) {
                renderPedidoDbBannerMobile(cache.pedidoDbInfo, cache.lastPedidoLookupCodigo || cache.parsed
                    ?.pedido_cliente || '');
            }
            cerrarModalPedidosMobile();
        }

        function aceptarMotivoCambio() {
            const motivo = document.getElementById('motivo-text-area').value;
            if (!motivo || motivo.trim().length < 5) {
                alert('Por favor, indica un motivo breve (mínimo 5 caracteres) para ayudar a la IA a aprender.');
                return;
            }

            window._lastUserReason = motivo;
            const index = window._tempPendingSelectionIndex;
            const cache = window.mobileStepManager.dataCache;
            const lineasPendientes = cache.lineasFiltradas || cache.simulacion?.lineas_pendientes || [];
            const linea = lineasPendientes[index];

            document.getElementById('motivo-cambio-modal').classList.add('hidden');
            document.getElementById('motivo-cambio-modal').classList.remove('flex');
            document.getElementById('motivo-text-area').value = '';

            confirmarSeleccionPedidoMobile(linea);
        }

        function cancelarMotivoCambio() {
            document.getElementById('motivo-cambio-modal').classList.add('hidden');
            document.getElementById('motivo-cambio-modal').classList.remove('flex');
            document.getElementById('motivo-text-area').value = '';
            window._tempPendingSelectionIndex = null;
        }

        /**
         * Actualizar Vista 3 con línea seleccionada
         */
        function actualizarVista3ConLineaSeleccionada(linea) {
            const container = document.getElementById('mobile-pedido-card');
            if (!container) return;

            const cache = window.mobileStepManager.dataCache || {};
            const recommendedId = cache.recommendedId || cache.simulacion?.linea_propuesta?.id;
            const isRecommended = recommendedId && linea.id === recommendedId;
            const fabricanteNombre = (linea?.fabricante || '').toString().trim();
            const distribuidorNombre = (linea?.distribuidor || '').toString().trim();
            const pedidoHprEscaneado = (cache?.parsed?.pedido_cliente || '').toString().trim();

            // Etiqueta según si es recomendado o elección del usuario
            let badgeClass = 'bg-gray-100 text-gray-700';
            let badgeText = 'SELECCIÓN DEL USUARIO';

            if (isRecommended) {
                badgeClass = 'bg-blue-100 text-blue-700';
                badgeText = 'RECOMENDADO';

                if (linea.tipo_recomendacion === 'exacta') {
                    badgeClass = 'bg-green-100 text-green-700';
                    badgeText = 'COINCIDENCIA EXACTA';
                } else if (linea.tipo_recomendacion === 'parcial') {
                    badgeClass = 'bg-yellow-100 text-yellow-700';
                    badgeText = 'COINCIDENCIA PARCIAL';
                }
            }

            container.innerHTML = `
                <div class="space-y-3">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${badgeClass}">
                        ${badgeText}
                    </span>

                    <div class="space-y-2 text-sm">
                        <div class>
                            <span class="text-gray-500">Linea pedido (BD):</span>
                            <span class="ml-2 font-semibold text-gray-900">${linea.codigo_linea || '—'}</span>
                        </div>
                        ${pedidoHprEscaneado ? `
                                                                                                                                                                                                                                                                                                                    <div class="hidden">
                                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Pedido HPR (escaneado):</span>
                                                                                                                                                                                                                                                                                                                        <span class="ml-2 font-semibold text-gray-900">${pedidoHprEscaneado}</span>
                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                ` : ''}
                        <div class="hidden">
                            <span class="text-gray-500">Pedido (BD):</span>
                            <span class="ml-2 font-semibold text-gray-900">${linea.pedido_codigo || '—'}</span>
                        </div>
                        ${fabricanteNombre ? `
                                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Fabricante:</span>
                                                                                                                                                                                                                                                                                                                        <span class="ml-2 text-gray-900">${fabricanteNombre}</span>
                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                ` : ''}
                        ${distribuidorNombre ? `
                                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                                        <span class="text-gray-500">Distribuidor:</span>
                                                                                                                                                                                                                                                                                                                        <span class="ml-2 text-gray-900">${distribuidorNombre}</span>
                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                ` : ''}
                        <div>
                            <span class="text-gray-500">Producto:</span>
                            <span class="ml-2 text-gray-900">${linea.producto || '—'}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">F. Entrega:</span>
                            <span class="ml-2 text-gray-900">${linea.fecha_entrega_fmt || linea.fecha_entrega || '—'}</span>
                        </div>
                        ${linea.obra ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <span class="text-gray-500">Obra:</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <span class="ml-2 text-gray-900">${linea.obra}</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ` : ''}
                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-gray-500">Cantidad Pendiente:</span>
                            <span class="ml-2 font-bold text-gray-900">${linea.cantidad_pendiente || 0} kg</span>
                        </div>
                        ${renderScoreRow(linea.score)}
                    </div>
                </div>
            `;
        }

        // Añadir event listener al botón "Ver otros pedidos"
        document.addEventListener('DOMContentLoaded', function() {
            const btnVerOtrosPedidos = document.getElementById('mobile-ver-otros-pedidos');
            if (btnVerOtrosPedidos) {
                btnVerOtrosPedidos.addEventListener('click', abrirModalPedidosMobile);
            }
        });
    </script>

    <script>
        (function() {
            const breakpointClasses = ['py-6', 'px-0', 'sm:px-6', 'lg:px-8'];

            const syncAppContentPadding = () => {
                const appContent = document.getElementById('app_content');
                if (!appContent) return;
                const isMobileView = window.innerWidth < 1024;
                if (isMobileView) {
                    appContent.classList.remove(...breakpointClasses);
                    appContent.classList.add('h-[calc(100vh-57px)]');
                } else {
                    breakpointClasses.forEach((klass) => appContent.classList.add(klass));
                }
            };

            window.addEventListener('resize', syncAppContentPadding);
            document.addEventListener('DOMContentLoaded', syncAppContentPadding);
        })();
    </script>

    <script>
        document.getElementById('mainlayout').classList.remove('py-4');
    </script>
</x-app-layout>
