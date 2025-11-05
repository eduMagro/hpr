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
                    <button @click="expandirPaquete(paquete.id)" class="text-gray-600 hover:text-blue-600 transition">
                        <svg class="w-5 h-5 transform transition-transform"
                            :class="{ 'rotate-180': paqueteExpandido === paquete.id }" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
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
</div>

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
            }
        }
    }
</script>
