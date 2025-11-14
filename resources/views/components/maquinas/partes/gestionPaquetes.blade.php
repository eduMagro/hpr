{{-- ================================================================
    COMPONENTE: Gestión de Paquetes por Planilla
    Ubicación: Columna derecha de maquinas.show
    ================================================================ --}}

<div class="w-full bg-white border shadow-md rounded-lg self-start p-4" x-data="gestionPaquetes()">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-xl text-gray-800">📦 Paquetes de Planilla</h3>
        <button @click="cargarPaquetes()" class="text-blue-600 hover:text-blue-800 transition" title="Recargar paquetes">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>

    {{-- SELECTOR DE PLANILLA --}}
    <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-2">
            Seleccionar Planilla:
        </label>
        <select x-model="planillaSeleccionada" @change="cargarPaquetes()"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Seleccione una planilla --</option>
            @foreach ($planillasActivas as $planilla)
                <option value="{{ $planilla->id }}">
                    {{ $planilla->codigo }} - {{ $planilla->obra->obra ?? 'Sin obra' }}
                </option>
            @endforeach
        </select>
    </div>

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
                        <h4 class="font-bold text-lg text-gray-900" x-text="paquete.codigo"></h4>
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold" x-text="paquete.peso"></span> kg ·
                            <span x-text="paquete.cantidad_etiquetas"></span> etiquetas
                        </p>
                        <p class="text-xs text-gray-500" x-text="paquete.ubicacion"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Botón Ver elementos del paquete --}}
                        <button @click="verElementosPaquete(paquete.id)"
                            class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center transition"
                            title="Ver elementos del paquete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
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
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div
            class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-[800px] md:w-[900px] lg:w-[1000px] max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
            <button id="cerrar-modal-elementos"
                class="absolute top-2 right-2 text-red-600 hover:bg-red-100 w-8 h-8 flex items-center justify-center rounded">
                ✖
            </button>

            <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete <span
                    id="modal-paquete-codigo"></span></h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
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
            paquetes: [],
            paqueteExpandido: null,
            cargando: false,

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
                    const response = await fetch(`/api/planillas/${this.planillaSeleccionada}/paquetes`, {
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

            async añadirEtiquetaAPaquete(paqueteId, codigoEtiqueta) {
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
                            etiqueta_codigo: codigoEtiqueta.trim()
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Error al añadir etiqueta');
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: '✅ Etiqueta añadida',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Limpiar input
                    document.getElementById(`input-etiqueta-${paqueteId}`).value = '';

                    // Recargar paquetes
                    await this.cargarPaquetes();

                    // Disparar evento para actualizar DOM
                    window.dispatchEvent(new CustomEvent('paquete:actualizado', {
                        detail: {
                            paqueteId,
                            etiquetaCodigo: codigoEtiqueta
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
                        title: '✅ Etiqueta eliminada',
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

                codigoSpan.textContent = paquete.codigo;
                canvasContainer.innerHTML = '';

                // Crear contenedores para cada elemento
                elementos.forEach((elemento) => {
                    const elementoDiv = document.createElement('div');
                    elementoDiv.className = 'mb-4 p-3 border border-gray-200 rounded-lg bg-gray-50 modal-elemento-view';

                    // Título del elemento
                    const titulo = document.createElement('h3');
                    titulo.className = 'text-sm font-semibold text-gray-800 mb-2';
                    titulo.textContent = `Elemento: ${elemento.codigo}`;
                    elementoDiv.appendChild(titulo);

                    // Contenedor SVG
                    const svgDiv = document.createElement('div');
                    svgDiv.id = `elemento-${elemento.id}`;
                    svgDiv.className = 'elemento-svg-container';
                    svgDiv.style.width = '100%';
                    svgDiv.style.height = '200px';
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
            }
        }
    }

    // Cerrar modal de elementos
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modal-elementos-paquete');
        const btnCerrar = document.getElementById('cerrar-modal-elementos');

        if (btnCerrar && modal) {
            btnCerrar.addEventListener('click', () => {
                modal.classList.add('hidden');
            });

            // Cerrar al hacer clic fuera del contenido
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            // Cerrar con tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                }
            });
        }
    });
</script>

{{-- Cargar script de dibujo de figuras si no está ya cargado --}}
@once
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
@endonce
