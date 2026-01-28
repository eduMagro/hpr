{{-- ================================================================
COMPONENTE: Gestión de Paquetes por Planilla
Ubicación: Columna derecha de maquinas.show
================================================================ --}}

<div class="w-full self-start" x-data="gestionPaquetes()">

    {{-- HEADER --}}
    <div class="flex items-center justify-between p-4 pb-0">
        <h3 class="font-bold text-lg text-blue-600 dark:text-blue-400 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-boxes-icon lucide-boxes">
                <path
                    d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5.5l-5-3-4.03 2.42Z" />
                <path d="m7 16.5-4.74-2.85" />
                <path d="m7 16.5 5-3" />
                <path d="M7 16.5v5.17" />
                <path
                    d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5l-5 3Z" />
                <path d="m17 16.5-5-3" />
                <path d="m17 16.5 4.74-2.85" />
                <path d="M17 16.5v5.17" />
                <path
                    d="M7.97 4.42A2 2 0 0 0 7 6.13v4.37l5 3 5-3V6.13a2 2 0 0 0-.97-1.71l-3-1.8a2 2 0 0 0-2.06 0l-3 1.8Z" />
                <path d="M12 8 7.26 5.15" />
                <path d="m12 8 4.74-2.85" />
                <path d="M12 13.5V8" />
            </svg>
            <p>Paquetes de planilla</p>
        </h3>
        <button @click="cargarPaquetes()"
            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-600 flex items-center justify-center hover:-rotate-180 transition-all duration-[0.5s] ease-in-out"
            title="Recargar paquetes">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-refresh-ccw-icon lucide-refresh-ccw">
                <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                <path d="M3 3v5h5" />
                <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                <path d="M16 16h5v5" />
            </svg>
        </button>
    </div>

    {{-- INDICADOR DE FILTRO ACTIVO --}}
    <div x-show="paqueteFiltrado" x-transition
        class="mt-2 p-2 bg-blue-100 border border-blue-300 rounded-lg flex items-center justify-between">
        <span class="text-sm text-blue-800">
            🔍 Filtrando: <strong x-text="paqueteFiltrado?.codigo"></strong>
        </span>
        <button @click="limpiarFiltroPaquete()" class="text-blue-600 hover:text-blue-800 font-bold text-lg"
            title="Quitar filtro">
            ✕
        </button>
    </div>

    {{-- SELECTOR DE PLANILLA --}}
    <div class="p-4">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Seleccionar Planilla:
        </label>
        <select x-model="planillaSeleccionada" @change="cargarPaquetes()"
            class="select-planilla-gestion w-full border border-gray-300 rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 focus:border-blue-500 focus:outline-none pr-6">
            <option class="dark:text-gray-300 dark:bg-gray-700" value="">Seleccione una planilla</option>
            @foreach ($planillasActivas as $planilla)
                <option class="dark:text-gray-300 dark:bg-gray-700" value="{{ $planilla->id }}">
                    {{ $planilla->codigo }} - {{ $planilla->obra->obra ?? 'Sin obra' }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- FILTRO DE MAQUINA --}}
    <div>
        <label class="inline-flex items-center gap-2 cursor-pointer p-4">
            <input type="checkbox" x-model="soloEstaMaquina" @change="cargarPaquetes()"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Solo paquetes de esta maquina</span>
        </label>
    </div>

    <style>
        /* Fix para evitar desplazamiento del select de planillas en gestión de paquetes */
        .select-planilla-gestion {
            transition: none !important;
            transform: none !important;
            box-sizing: border-box !important;
            display: block !important;
        }

        .select-planilla-gestion:focus,
        .select-planilla-gestion:focus-visible,
        .select-planilla-gestion:active {
            box-shadow: none !important;
            outline: none !important;
            transform: none !important;
        }
    </style>

    {{-- LOADING --}}
    <div x-show="cargando" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="text-gray-600 mt-2">Cargando paquetes...</p>
    </div>

    {{-- LISTA DE PAQUETES --}}
    <div x-show="!cargando && paquetes.length > 0" class="space-y-4">
        <template x-for="paquete in paquetes" :key="paquete.id">
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">

                {{-- Info del paquete --}}
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="font-bold text-lg text-gray-900 cursor-pointer hover:text-blue-600 transition"
                            x-text="paquete.codigo" @click="filtrarEtiquetasPorPaquete(paquete)"
                            title="Click para filtrar etiquetas de este paquete"></h4>
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold" x-text="paquete.peso"></span> kg ·
                            <span x-text="paquete.cantidad_etiquetas"></span> etiquetas
                        </p>
                        <p class="text-xs text-gray-500" x-text="paquete.ubicacion"></p>
                        <p class="text-xs text-blue-600" x-show="paquete.usuario">
                            <span class="font-medium" x-text="paquete.usuario"></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Botón Imprimir QR --}}
                        <button @click="imprimirQRPaquete(paquete)"
                            class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center transition"
                            title="Imprimir QR del paquete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 10h4v-4h-4v4zm6 0h4v-4h-4v4z" />
                            </svg>
                        </button>
                        {{-- Botón Ver elementos del paquete --}}
                        <button @click="verElementosPaquete(paquete.id)"
                            class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center transition"
                            title="Ver elementos del paquete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        {{-- Botón Eliminar Paquete --}}
                        <button @click="eliminarPaquete(paquete.id)"
                            class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center transition"
                            title="Eliminar paquete completo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        {{-- Botón Expandir --}}
                        <button @click="expandirPaquete(paquete.id)"
                            class="text-gray-600 hover:text-blue-600 transition">
                            <svg class="w-5 h-5 transform transition-transform"
                                :class="{ 'rotate-180': paqueteExpandido === paquete.id }" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Etiquetas del paquete (expandible) --}}
                <div x-show="paqueteExpandido === paquete.id" x-transition class="mt-3 space-y-2">

                    {{-- Añadir etiqueta al paquete --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                        <label class="block text-sm font-semibold text-blue-900 mb-2">
                            🏷️ Añadir etiqueta:
                        </label>
                        <div class="flex gap-2">
                            <input type="text" :id="'input-etiqueta-' + paquete.id"
                                @keyup.enter="añadirEtiquetaAPaquete(paquete.id, $event.target.value)"
                                placeholder="Escanear QR o código"
                                class="flex-1 border border-blue-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <button
                                @click="añadirEtiquetaAPaquete(paquete.id, document.getElementById('input-etiqueta-' + paquete.id).value)"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold transition">
                                +
                            </button>
                        </div>
                    </div>

                    {{-- Lista de etiquetas --}}
                    <div class="max-h-64 overflow-y-auto">
                        <template x-for="etiqueta in paquete.etiquetas" :key="etiqueta.codigo">
                            <div
                                class="flex items-center justify-between bg-gray-50 rounded px-3 py-2 mb-2 hover:bg-gray-100 transition">
                                <div class="flex-1">
                                    <p class="font-semibold text-sm text-gray-900" x-text="etiqueta.codigo"></p>
                                    <p class="text-xs text-gray-600">
                                        <span x-text="etiqueta.peso"></span> kg ·
                                        <span x-text="etiqueta.elementos_count"></span> elementos
                                    </p>
                                </div>
                                <button @click="eliminarEtiquetaDePaquete(paquete.id, etiqueta.codigo)"
                                    class="text-red-600 hover:text-red-800 transition"
                                    title="Eliminar etiqueta del paquete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- MENSAJE SIN PAQUETES --}}
    <div x-show="!cargando && paquetes.length === 0 && planillaSeleccionada" class="text-center py-8 text-gray-500">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <p class="font-semibold">No hay paquetes para esta planilla</p>
        <p class="text-sm mt-1">Los paquetes creados aparecerán aquí</p>
    </div>

    {{-- Modal para visualizar elementos del paquete --}}
    <div id="modal-elementos-paquete"
        class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center p-4"
        style="z-index: 99999 !important;">
        <div class="bg-white p-3 sm:p-4 rounded-lg w-full sm:w-[500px] md:w-[600px] max-w-[90vw] max-h-[70vh] flex flex-col shadow-lg relative"
            style="z-index: 100000 !important;">
            <button id="cerrar-modal-elementos" onclick="cerrarModalElementosPaquete()"
                class="absolute top-2 right-2 text-red-600 hover:bg-red-100 w-7 h-7 flex items-center justify-center rounded text-lg"
                style="z-index: 100001 !important;">
                ✖
            </button>

            <h2 class="text-lg font-semibold mb-3 text-center pr-6">Elementos del paquete <span
                    id="modal-paquete-codigo"></span></h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 60vh; position: relative; z-index: 100000;">
                <div id="canvas-elementos-paquete" class="border max-w-full h-auto"></div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ocultar botones dentro del modal de elementos del paquete */
    #modal-elementos-paquete .btn-fabricar,
    #modal-elementos-paquete .btn-agregar-carro,
    #modal-elementos-paquete button[onclick*="imprimirEtiquetas"],
    #modal-elementos-paquete select[id*="modo-impresion"],
    #modal-elementos-paquete .no-print {
        display: none !important;
    }

    /* Asegurar que solo se muestren los SVG limpios */
    #modal-elementos-paquete .modal-elemento-view {
        position: relative;
    }

    #modal-elementos-paquete .modal-elemento-view button,
    #modal-elementos-paquete .modal-elemento-view select,
    #modal-elementos-paquete .modal-elemento-view .absolute {
        display: none !important;
    }
</style>

{{-- ================================================================
SCRIPT ALPINE.JS
================================================================ --}}
<script>
    function gestionPaquetes() {
        return {
            planillaSeleccionada: '',
            soloEstaMaquina: false,
            paquetes: [],
            paqueteExpandido: null,
            cargando: false,
            paqueteFiltrado: null,

            init() {
                // Auto-seleccionar la primera planilla si existe
                @if (isset($planillasActivas))
                    this.planillaSeleccionada = '{{ $planillasActivas[0]['id'] ?? '' }}';

                    this.cargarPaquetes();
                @endif

                // Escuchar evento de creación de paquetes
                window.addEventListener('paquete:creado', (e) => {
                    console.log('🎉 Evento paquete:creado recibido', e.detail);
                    this.cargarPaquetes();
                });

                // Escuchar evento de actualización de paquetes
                window.addEventListener('paquete:actualizado', (e) => {
                    console.log('🔄 Evento paquete:actualizado recibido', e.detail);
                    this.cargarPaquetes();
                });
            },

            async cargarPaquetes() {
                if (!this.planillaSeleccionada) {
                    this.paquetes = [];
                    return;
                }

                this.cargando = true;

                try {
                    // Construir URL con parámetros
                    let url = `/api/planillas/${this.planillaSeleccionada}/paquetes`;
                    const params = new URLSearchParams();

                    if (this.soloEstaMaquina && window.maquinaId) {
                        params.append('maquina_id', window.maquinaId);
                    }

                    if (params.toString()) {
                        url += '?' + params.toString();
                    }

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });

                    if (!response.ok) throw new Error('Error al cargar paquetes');

                    const data = await response.json();
                    this.paquetes = data.paquetes || [];

                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire('Error', 'No se pudieron cargar los paquetes', 'error');
                } finally {
                    this.cargando = false;
                }
            },

            expandirPaquete(paqueteId) {
                this.paqueteExpandido = this.paqueteExpandido === paqueteId ? null : paqueteId;
            },

            async añadirEtiquetaAPaquete(paqueteId, codigoEtiqueta, forzar = false) {
                if (!codigoEtiqueta || !codigoEtiqueta.trim()) {
                    await Swal.fire('Atención', 'Por favor ingrese un código de etiqueta', 'warning');
                    return;
                }

                try {
                    const response = await fetch(`/api/paquetes/${paqueteId}/añadir-etiqueta`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        },
                        body: JSON.stringify({
                            etiqueta_codigo: codigoEtiqueta.trim(),
                            forzar: forzar
                        })
                    });

                    const data = await response.json();

                    // 🔄 MANEJAR CASO: Requiere confirmación para mover entre paquetes
                    if (response.status === 409 && data.requiere_confirmacion) {
                        const confirmacion = await Swal.fire({
                            title: '¿Mover etiqueta?',
                            html: `
                                <p class="mb-3">La etiqueta <strong>${codigoEtiqueta}</strong> pertenece al paquete:</p>
                                <p class="text-lg font-bold text-blue-600 mb-3">${data.paquete_actual.codigo}</p>
                                <p class="mb-2">¿Deseas moverla al paquete:</p>
                                <p class="text-lg font-bold text-green-600">${data.paquete_destino.codigo}</p>
                            `,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#059669',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Sí, mover',
                            cancelButtonText: 'Cancelar'
                        });

                        if (confirmacion.isConfirmed) {
                            // Reintentar con forzar=true
                            return await this.añadirEtiquetaAPaquete(paqueteId, codigoEtiqueta, true);
                        }
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(data.message || 'Error al añadir etiqueta');
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Etiqueta añadida',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Limpiar input
                    document.getElementById(`input-etiqueta-${paqueteId}`).value = '';

                    // Actualizar visualmente la etiqueta añadida al paquete
                    if (window.SistemaDOM) {
                        window.SistemaDOM.actualizarEstadoEtiqueta(codigoEtiqueta.trim(), 'en-paquete');
                    }

                    // Recargar paquetes
                    await this.cargarPaquetes();

                    // Disparar evento para actualizar DOM
                    window.dispatchEvent(new CustomEvent('paquete:actualizado', {
                        detail: {
                            paqueteId,
                            etiquetaCodigo: codigoEtiqueta,
                            añadida: true
                        }
                    }));

                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire('Error', error.message, 'error');
                }
            },

            async eliminarEtiquetaDePaquete(paqueteId, codigoEtiqueta) {
                const confirmacion = await Swal.fire({
                    title: '¿Eliminar etiqueta?',
                    text: `Se eliminará ${codigoEtiqueta} del paquete`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                });

                if (!confirmacion.isConfirmed) return;

                try {
                    const response = await fetch(`/api/paquetes/${paqueteId}/eliminar-etiqueta`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        },
                        body: JSON.stringify({
                            etiqueta_codigo: codigoEtiqueta
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error al eliminar etiqueta');
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Etiqueta eliminada',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Recargar paquetes
                    await this.cargarPaquetes();

                    // Disparar evento para actualizar DOM
                    window.dispatchEvent(new CustomEvent('paquete:actualizado', {
                        detail: {
                            paqueteId,
                            etiquetaCodigo: codigoEtiqueta,
                            eliminada: true
                        }
                    }));

                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire('Error', error.message, 'error');
                }
            },

            async eliminarPaquete(paqueteId) {
                const paquete = this.paquetes.find(p => p.id === paqueteId);
                const codigoPaquete = paquete ? paquete.codigo : `ID ${paqueteId}`;

                // Obtener info de etiquetas antes de eliminar (guardar estado original)
                const etiquetasDelPaquete = paquete && paquete.etiquetas ?
                    paquete.etiquetas.map(e => ({
                        id: e.codigo || e.etiqueta_sub_id,
                        estado: e.estado
                    })) : [];

                const confirmacion = await Swal.fire({
                    title: '¿Eliminar paquete completo?',
                    html: `
                        <p>Se eliminará el paquete <strong>${codigoPaquete}</strong></p>
                        <p class="text-sm text-gray-600 mt-2">Las etiquetas quedarán libres manteniendo su estado (${etiquetasDelPaquete.length} etiquetas)</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar paquete',
                    cancelButtonText: 'Cancelar'
                });

                if (!confirmacion.isConfirmed) return;

                try {
                    const response = await fetch(`/api/paquetes/${paqueteId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error al eliminar paquete');
                    }

                    // Actualizar visualmente las etiquetas liberadas a su estado original (no 'pendiente')
                    // Solo necesitamos quitarles la clase 'en-paquete' y restaurar su estado real
                    if (window.SistemaDOM && etiquetasDelPaquete.length > 0) {
                        console.log('🎨 Actualizando estados de etiquetas liberadas...', etiquetasDelPaquete);
                        etiquetasDelPaquete.forEach(etiqueta => {
                            // Restaurar al estado original que tenía la etiqueta
                            const estadoReal = etiqueta.estado || 'pendiente';
                            window.SistemaDOM.actualizarEstadoEtiqueta(etiqueta.id, estadoReal);
                        });
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Paquete eliminado',
                        text: data.message,
                        timer: 3000,
                        showConfirmButton: false
                    });

                    // Recargar paquetes para refrescar la lista
                    await this.cargarPaquetes();

                    // Disparar evento para que otros componentes se enteren
                    window.dispatchEvent(new CustomEvent('paquete:eliminado', {
                        detail: {
                            paqueteId,
                            codigoPaquete,
                            etiquetasLiberadas: data.etiquetas_liberadas,
                            etiquetasIds: etiquetasDelPaquete.map(e => e.id)
                        }
                    }));

                } catch (error) {
                    console.error('Error al eliminar paquete:', error);
                    await Swal.fire('Error', error.message, 'error');
                }
            },

            imprimirQRPaquete(paquete) {
                if (typeof generateAndPrintQRPaquete !== 'function') {
                    console.error('La función generateAndPrintQRPaquete no está disponible');
                    Swal.fire('Error', 'No se pudo cargar la función de impresión QR', 'error');
                    return;
                }

                // Obtener etiquetas del paquete
                const etiquetas = paquete.etiquetas ? paquete.etiquetas.map(e => e.codigo).join(', ') : '';

                generateAndPrintQRPaquete({
                    codigo: paquete.codigo || '',
                    planilla: paquete.planilla_codigo || '',
                    cliente: paquete.cliente || '',
                    obra: paquete.obra || '',
                    descripcion: paquete.descripcion || '',
                    seccion: paquete.seccion || '',
                    ensamblado: paquete.ensamblado || '',
                    peso: paquete.peso || '0',
                    etiquetas: etiquetas
                });
            },

            async verElementosPaquete(paqueteId) {
                const paquete = this.paquetes.find(p => p.id === paqueteId);
                if (!paquete) {
                    await Swal.fire('Error', 'Paquete no encontrado', 'error');
                    return;
                }

                // Obtener elementos del paquete
                const elementos = [];
                if (paquete.etiquetas && paquete.etiquetas.length > 0) {
                    for (const etiqueta of paquete.etiquetas) {
                        if (etiqueta.elementos && etiqueta.elementos.length > 0) {
                            etiqueta.elementos.forEach(elemento => {
                                elementos.push({
                                    id: elemento.id,
                                    codigo: elemento.codigo || 'Sin código',
                                    dimensiones: elemento.dimensiones
                                });
                            });
                        }
                    }
                }

                if (elementos.length === 0) {
                    await Swal.fire('Sin elementos', 'Este paquete no tiene elementos para mostrar', 'info');
                    return;
                }

                // Abrir modal
                const modal = document.getElementById('modal-elementos-paquete');
                const canvasContainer = document.getElementById('canvas-elementos-paquete');
                const codigoSpan = document.getElementById('modal-paquete-codigo');

                if (!modal || !canvasContainer || !codigoSpan) return;

                // Mover el modal al body para evitar problemas de z-index
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }

                codigoSpan.textContent = paquete.codigo;
                canvasContainer.innerHTML = '';

                // Crear contenedores para cada elemento
                elementos.forEach((elemento) => {
                    const elementoDiv = document.createElement('div');
                    elementoDiv.className =
                        'mb-2 p-2 border border-gray-200 rounded bg-gray-50 modal-elemento-view';

                    // Título del elemento
                    const titulo = document.createElement('h3');
                    titulo.className = 'text-xs font-semibold text-gray-800 mb-1';
                    titulo.textContent = `Elemento: ${elemento.codigo}`;
                    elementoDiv.appendChild(titulo);

                    // Contenedor SVG
                    const svgDiv = document.createElement('div');
                    svgDiv.id = `elemento-${elemento.id}`;
                    svgDiv.className = 'elemento-svg-container';
                    svgDiv.style.width = '100%';
                    svgDiv.style.height = '120px';
                    svgDiv.style.border = '1px solid #e5e7eb';
                    svgDiv.style.borderRadius = '4px';
                    svgDiv.style.background = 'white';
                    svgDiv.style.position = 'relative';
                    elementoDiv.appendChild(svgDiv);

                    canvasContainer.appendChild(elementoDiv);
                });

                modal.classList.remove('hidden');

                // Dibujar elementos
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        elementos.forEach((elemento) => {
                            if (typeof window.dibujarFiguraElemento === 'function') {
                                window.dibujarFiguraElemento(`elemento-${elemento.id}`,
                                    elemento.dimensiones, null);
                            }
                        });
                    });
                });
            },

            /**
             * Filtra las etiquetas de la columna central para mostrar solo las del paquete seleccionado
             */
            filtrarEtiquetasPorPaquete(paquete) {
                if (!paquete || !paquete.etiquetas || paquete.etiquetas.length === 0) {
                    Swal.fire('Sin etiquetas', 'Este paquete no tiene etiquetas asociadas', 'info');
                    return;
                }

                this.paqueteFiltrado = paquete;

                // Obtener los códigos de las etiquetas del paquete
                const etiquetasDelPaquete = paquete.etiquetas.map(e => e.codigo || e.etiqueta_sub_id);
                console.log('🔍 Filtrando etiquetas del paquete:', paquete.codigo, etiquetasDelPaquete);

                // Obtener todas las etiquetas de la columna central
                const todasLasEtiquetas = document.querySelectorAll('.etiqueta-wrapper');

                todasLasEtiquetas.forEach(wrapper => {
                    const etiquetaSubId = wrapper.dataset.etiquetaSubId;
                    const paqueteId = wrapper.dataset.paqueteId;

                    // Mostrar solo si pertenece a este paquete
                    const perteneceAlPaquete = etiquetasDelPaquete.some(codigo =>
                        codigo === etiquetaSubId ||
                        String(paqueteId) === String(paquete.id)
                    );

                    if (perteneceAlPaquete) {
                        wrapper.style.display = '';
                        wrapper.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                    } else {
                        wrapper.style.display = 'none';
                    }
                });

                // Notificar al usuario
                Swal.fire({
                    icon: 'info',
                    title: 'Filtro aplicado',
                    text: `Mostrando ${etiquetasDelPaquete.length} etiqueta(s) del paquete ${paquete.codigo}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            },

            /**
             * Limpia el filtro y muestra todas las etiquetas
             */
            limpiarFiltroPaquete() {
                this.paqueteFiltrado = null;

                // Mostrar todas las etiquetas
                const todasLasEtiquetas = document.querySelectorAll('.etiqueta-wrapper');
                todasLasEtiquetas.forEach(wrapper => {
                    wrapper.style.display = '';
                    wrapper.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
                });

                console.log('🔄 Filtro de paquete limpiado');
            }
        }
    }

    // Función global para cerrar el modal de elementos del paquete
    window.cerrarModalElementosPaquete = function () {
        const modal = document.getElementById('modal-elementos-paquete');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    // Cerrar al hacer clic fuera del contenido (usando delegación de eventos)
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('modal-elementos-paquete');
        if (modal && e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // Cerrar con tecla ESC (usando delegación de eventos)
    document.addEventListener('keydown', (e) => {
        const modal = document.getElementById('modal-elementos-paquete');
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }
    });

    // Escuchar eventos de actualización y eliminación de paquetes para actualizar colores de etiquetas
    window.addEventListener('paquete:actualizado', actualizarColoresEtiquetas);
    window.addEventListener('paquete:eliminado', actualizarColoresEtiquetas);

    /**
     * Actualiza los colores/estados visuales de las etiquetas en el DOM
     * Sin recargar la página completa
     * Esta es una función de fallback - la lógica principal está en eliminarPaquete()
     */
    function actualizarColoresEtiquetas(event) {
        console.log('🎨 Evento de actualización recibido...', event.detail);

        // La actualización real de estados se hace en la función eliminarPaquete()
        // usando SistemaDOM.actualizarEstadoEtiqueta() con el estado original de cada etiqueta
        // Esta función solo se mantiene como listener para posibles extensiones futuras
    }
</script>

{{-- Cargar script de dibujo de figuras si no está ya cargado --}}
@once
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
@endonce