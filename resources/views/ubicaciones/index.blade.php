@php
    $detalles = \App\Models\Producto::with('productoBase')
        ->get()
        ->mapWithKeys(function ($p) {
            return [
                $p->codigo => [
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'tipo' => optional($p->productoBase)->tipo,
                    'diametro' => optional($p->productoBase)->diametro,
                    'longitud' => optional($p->productoBase)->longitud,
                    'colada' => $p->n_colada,
                    'peso_inicial' => $p->peso_inicial,
                ],
            ];
        });

    $ubicacionSectorMap = [];
    foreach ($ubicacionesPorSector as $sectorClave => $ubicacionesSector) {
        foreach ($ubicacionesSector as $ubi) {
            $ubicacionSectorMap[$ubi->id] = $sectorClave;
        }
    }

    $ubicacionIds = collect($ubicacionesPorSector)->flatten()->pluck('id')->filter()->values();
@endphp

<script data-navigate-reload>
    /* Mapas globales base (no reactivos) */
    window.productosAsignados = @json(\App\Models\Producto::whereNotNull('ubicacion_id')->pluck('ubicacion_id', 'codigo'));
    window.detallesProductos = @json($detalles);
    window.productosEstados = @json(\App\Models\Producto::pluck('estado', 'codigo'));
    window.ubicacionSectorMap = @json($ubicacionSectorMap);
    window.ubicacionIdsInventario = @json($ubicacionIds);
</script>

<script data-navigate-reload>
    const RUTA_ALERTA = @json(route('alertas.store'));

    const ensureSwal = () => new Promise((resolve) => {
        if (window.Swal) return resolve(window.Swal);
        let loader = document.getElementById('swal-inline-loader');
        if (!loader) {
            loader = document.createElement('script');
            loader.id = 'swal-inline-loader';
            loader.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js';
            loader.onload = () => resolve(window.Swal || null);
            loader.onerror = () => resolve(null);
            document.head.appendChild(loader);
        } else {
            loader.addEventListener('load', () => resolve(window.Swal || null), {
                once: true
            });
            loader.addEventListener('error', () => resolve(null), {
                once: true
            });
        }
    });

    const swalToast = {
        fire: (options = {}) => ensureSwal().then((swal) => {
            if (!swal) return null;
            return swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            }).fire(options);
        })
    };

    const swalDialog = (options = {}) => ensureSwal().then((swal) => {
        if (!swal) return Promise.resolve({
            isConfirmed: false
        });
        return swal.fire({
            position: 'center',
            timerProgressBar: true,
            ...options
        });
    });

    window.AUDIO_INV_URLS = {
        ok: "{{ asset('sonidos/scan-ok.wav') }}",
        error: "{{ asset('sonidos/scan-error.mp3') }}",
        invalido: "{{ asset('sonidos/scan-error.mp3') }}",
        otra: "{{ asset('sonidos/scan-error.mp3') }}",
        sinUbic: "{{ asset('sonidos/scan-error.mp3') }}",
        consumido: "{{ asset('sonidos/scan-error.mp3') }}",
    };

    /* ────── Alpine factory per location ────── */
    window.inventarioUbicacion = function(productosEsperados, nombreUbicacion) {
        const ubicacionSafe = nombreUbicacion ?? '';
        const listaBase = Array.isArray(productosEsperados) ? productosEsperados.filter(Boolean) : [];

        /* key used to persist scans for this location */
        const claveLS = `inv-${ubicacionSafe}`;
        const claveSospechosos = `sospechosos-${ubicacionSafe}`;

        return {
            /* props ---------------------------------------------------------------- */
            productosEsperados: [...listaBase],
            nombreUbicacion: ubicacionSafe,
            originalEsperados: [],

            escaneados: [], // códigos OK
            sospechosos: [], // códigos inesperados
            ultimoCodigo: null, // para el flash visual

            /* copia REACTIVA del mapa global para x-show */
            asignados: {},

            /* audio */
            audioOk: null,
            audioError: null,
            audioPedo: null,
            audioEstaEnOtraUbi: null,
            audioNoTieneUbicacion: null,
            audioConsumido: null,

            /* texto a voz para avisos de error */
            hablar(texto) {
                if (!texto || !window.speechSynthesis || typeof SpeechSynthesisUtterance === 'undefined') return;
                try {
                    const utterance = new SpeechSynthesisUtterance(texto);
                    utterance.lang = 'es-ES';
                    utterance.rate = 1;
                    utterance.pitch = 1;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(utterance);
                } catch (err) {
                    console.warn('[INV] No se pudo reproducir TTS', err);
                }
            },
            anunciarFallo(mensaje) {
                if (!mensaje) return;
                // dejamos que suene primero el beep de error
                setTimeout(() => this.hablar(mensaje), 400);
            },

            /* lifecycle ----------------------------------------------------------- */
            init() {
                /* snapshot inicial para diferenciar "servidos por Blade" vs. añadidos dinámicos */
                this.originalEsperados = [...this.productosEsperados];

                /* 1️⃣ recuperar progreso previo */
                this.escaneados = JSON.parse(localStorage.getItem(claveLS) || '[]');
                this.sospechosos = JSON.parse(localStorage.getItem(claveSospechosos) || '[]');
                this.$nextTick(() => this.$refs.inputQR?.focus());

                /* audio */
                const cargarAudios = () => {
                    const ensureAudio = (id, url) => {
                        let el = document.getElementById(id);
                        if (!el) {
                            el = document.createElement('audio');
                            el.id = id;
                            el.src = url;
                            el.preload = 'auto';
                            el.style.display = 'none';
                            document.body.appendChild(el);
                        }
                        return el;
                    };

                    const urls = window.AUDIO_INV_URLS || {};
                    this.audioOk = ensureAudio('sonido-ok', urls.ok);
                    this.audioError = ensureAudio('sonido-error', urls.error);
                    this.audioPedo = ensureAudio('sonido-pedo', urls.invalido);
                    this.audioEstaEnOtraUbi = ensureAudio('sonido-estaEnOtraUbi', urls.otra);
                    this.audioNoTieneUbicacion = ensureAudio('sonido-noTieneUbicacion', urls.sinUbic);
                    this.audioConsumido = ensureAudio('sonido-consumido', urls.consumido);
                    console.log('[INV] Audio refs', {
                        ok: !!this.audioOk,
                        error: !!this.audioError,
                        invalido: !!this.audioPedo,
                        otra: !!this.audioEstaEnOtraUbi,
                        sinUbic: !!this.audioNoTieneUbicacion,
                        consumido: !!this.audioConsumido
                    });
                };
                cargarAudios();

                /* copia REACTIVA del mapa global actual */
                this.asignados = {
                    ...(window.productosAsignados || {})
                };

                this.estados = {
                    ...(window.productosEstados || {})
                };

                /* Escuchar reasignaciones globales */
                window.addEventListener('producto-reasignado', (e) => {
                    const {
                        codigo,
                        nuevaUbicacion
                    } = e.detail;

                    // limpiar en esta instancia
                    this.sospechosos = this.sospechosos.filter(c => c !== codigo);
                    this.escaneados = this.escaneados.filter(c => c !== codigo);
                    this.productosEsperados = this.productosEsperados.filter(c => c !== codigo);

                    // actualizar mapa reactivo local (clave para x-show)
                    this.asignados[codigo] = nuevaUbicacion;

                    // si la nueva ubicación es esta, añadirlo
                    if (this.nombreUbicacion && nuevaUbicacion !== null && nuevaUbicacion !== undefined &&
                        this.nombreUbicacion.toString() === nuevaUbicacion.toString()) {
                        if (!this.productosEsperados.includes(codigo)) this.productosEsperados.push(codigo);
                        if (!this.escaneados.includes(codigo)) this.escaneados.push(codigo);
                    }

                    // persistir
                    localStorage.setItem(`inv-${this.nombreUbicacion}`, JSON.stringify(this.escaneados));
                    localStorage.setItem(`sospechosos-${this.nombreUbicacion}`, JSON.stringify(this
                        .sospechosos));
                });
            },

            /* helpers ------------------------------------------------------------- */
            reproducirOk() {
                if (!this.audioOk) this.audioOk = document.getElementById('sonido-ok');
                if (!this.audioOk) {
                    console.warn('[INV] No se encontró sonido scan-ok');
                    return;
                }
                this.audioOk.currentTime = 0;
                console.log('[INV] Reproduciendo sonido: scan-ok');
                this.audioOk.play().catch(() => {});
            },
            reproducirError() {
                if (!this.audioError) this.audioError = document.getElementById('sonido-error');
                if (!this.audioError) {
                    console.warn('[INV] No se encontró sonido scan-error');
                    return;
                }
                this.audioError.currentTime = 0;
                console.log('[INV] Reproduciendo sonido: scan-error');
                this.audioError.play().catch(() => {});
                this.anunciarFallo('Producto no esperado en esta ubicación.');
            },
            reproducirPedo() {
                if (!this.audioPedo) this.audioPedo = document.getElementById('sonido-pedo');
                if (!this.audioPedo) {
                    console.warn('[INV] No se encontró sonido scan-error (invalido)');
                    return;
                }
                this.audioPedo.currentTime = 0;
                console.log('[INV] Reproduciendo sonido: scan-error (invalido)');
                this.audioPedo.play().catch(() => {});
                this.anunciarFallo('El código escaneado no es válido.');
            },
            reproducirEstaEnOtraUbi() {
                if (!this.audioEstaEnOtraUbi) this.audioEstaEnOtraUbi = document.getElementById(
                    'sonido-estaEnOtraUbi');
                if (!this.audioEstaEnOtraUbi) {
                    console.warn('[INV] No se encontró sonido scan-error (otra ubicacion)');
                    return;
                }
                this.audioEstaEnOtraUbi.currentTime = 0;
                console.log('[INV] Reproduciendo sonido: scan-error (otra ubicacion)');
                this.audioEstaEnOtraUbi.play().catch(() => {});
                this.anunciarFallo('El producto está asignado a otra ubicación.');
            },
            reproducirNoTieneUbicacion() {
                if (!this.audioNoTieneUbicacion) this.audioNoTieneUbicacion = document.getElementById(
                    'sonido-noTieneUbicacion');
                if (!this.audioNoTieneUbicacion) {
                    console.warn('[INV] No se encontró sonido scan-error (sin ubicacion)');
                    return;
                }
                this.audioNoTieneUbicacion.currentTime = 0;
                console.log('[INV] Reproduciendo sonido: scan-error (sin ubicacion)');
                this.audioNoTieneUbicacion.play().catch(() => {});
                this.anunciarFallo('El producto no tiene ubicación registrada.');
            },
            reproducirConsumido() {
                if (!this.audioConsumido) this.audioConsumido = document.getElementById('sonido-consumido');
                if (!this.audioConsumido) {
                    console.warn('[INV] No se encontró sonido scan-error (consumido)');
                    return;
                }
                this.audioConsumido.currentTime = 0;
                console.log('[INV] Reproduciendo sonido: scan-error (consumido)');
                this.audioConsumido.play().catch(() => {});
                this.anunciarFallo('El producto ya está consumido.');
            },

            progreso() {
                if (!this.productosEsperados.length) return 0;
                return (this.escaneados.length / this.productosEsperados.length) * 100;
            },

            procesarQR(codigo) {
                codigo = (codigo || '').trim();
                console.log('[INV] procesarQR', {
                    codigo,
                    ubicacion: this.nombreUbicacion,
                    esperados: this.productosEsperados.length
                });

                // ❌ Si no empieza por MP, descartamos
                if (!codigo.toUpperCase().startsWith('MP')) {
                    this.reproducirPedo();
                    this.ultimoCodigo = codigo;
                    setTimeout(() => (this.ultimoCodigo = null), 1200);
                    return;
                }
                if (!codigo) return;

                const ubicacionAsignada = (window.productosAsignados || {})[codigo];
                const estadoProducto = (window.productosEstados || {})[codigo];

                if (this.productosEsperados.includes(codigo)) {
                    if (!this.escaneados.includes(codigo)) {
                        this.escaneados.push(codigo);
                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados)); // 2️⃣ persist
                        this.reproducirOk();
                    }

                    // 🧹 Si estaba en inesperados, lo quitamos
                    const indexSospechoso = this.sospechosos.indexOf(codigo);
                    if (indexSospechoso !== -1) {
                        this.sospechosos.splice(indexSospechoso, 1);
                        localStorage.setItem(claveSospechosos, JSON.stringify(this.sospechosos));
                    }
                } else {
                    // Siempre añadimos a sospechosos si aún no estaba
                    if (!this.sospechosos.includes(codigo)) {
                        this.sospechosos.push(codigo);
                        localStorage.setItem(claveSospechosos, JSON.stringify(this.sospechosos));
                    }

                    // Reproducimos sonido según caso
                    if (estadoProducto === 'consumido') {
                        this.reproducirConsumido();
                    } else if (ubicacionAsignada !== undefined && ubicacionAsignada !== null && this
                        .nombreUbicacion &&
                        ubicacionAsignada.toString() !== this.nombreUbicacion.toString()) {
                        this.reproducirEstaEnOtraUbi();
                    } else if (ubicacionAsignada === undefined) {
                        this.reproducirNoTieneUbicacion();
                    } else {
                        this.reproducirError();
                    }
                }

                /* 3️⃣ flash highlight */
                this.ultimoCodigo = codigo;
                setTimeout(() => (this.ultimoCodigo = null), 1200);

                // notificar cambio de inventario (para recalcular estados visuales)
                if (this.nombreUbicacion) {
                    window.dispatchEvent(new CustomEvent('inventario-actualizado', {
                        detail: {
                            ubicacionId: this.nombreUbicacion
                        }
                    }));
                }
            },

            resetear() {
                swalDialog({
                    icon: 'warning',
                    title: '¿Limpiar esta ubicación?',
                    text: 'Se perderán los escaneos guardados.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, borrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc2626'
                }).then(result => {
                    if (result.isConfirmed) {
                        this.escaneados = [];
                        this.sospechosos = [];
                        this.ultimoCodigo = null;
                        localStorage.removeItem(claveLS);
                        localStorage.removeItem(claveSospechosos);

                        if (this.nombreUbicacion) {
                            window.dispatchEvent(new CustomEvent('inventario-actualizado', {
                                detail: {
                                    ubicacionId: this.nombreUbicacion
                                }
                            }));
                        }
                    }
                });
            },

            productoEscaneado(codigo) {
                return this.escaneados.includes(codigo);
            },

            productosAnadidos() {
                return this.productosEsperados.filter(c => !this.originalEsperados.includes(c));
            },

            reportarErrores() {
                const faltantes = this.productosEsperados.filter(c => !this.escaneados.includes(c));
                const inesperados = [...this.sospechosos];
                window.notificarProgramadorInventario({
                    ubicacion: this.nombreUbicacion,
                    faltantes,
                    inesperados
                });
            },

            // ⬇️ Reasignar producto
            reasignarProducto(codigo) {
                fetch("{{ route('productos.editarUbicacionInventario', ['codigo' => '___CODIGO___']) }}".replace(
                        '___CODIGO___', codigo), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            ubicacion_id: this.nombreUbicacion
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // actualizar global y local reactivo
                            window.productosAsignados[codigo] = this.nombreUbicacion;
                            this.asignados[codigo] = this.nombreUbicacion;

                            swalToast.fire({
                                icon: 'success',
                                title: 'Reasignado',
                                text: `El producto ${codigo} fue reasignado a esta ubicación.`
                            });

                            // 🚀 Emitimos evento global para que todas las ubicaciones se actualicen
                            window.dispatchEvent(new CustomEvent('producto-reasignado', {
                                detail: {
                                    codigo,
                                    nuevaUbicacion: this.nombreUbicacion
                                }
                            }));
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    })
                    .catch(err => {
                        swalToast.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.message
                        });
                    });
            }
        };
    };
</script>

<script>
    window.notificarProgramadorInventario = function({
        ubicacion,
        faltantes,
        inesperados
    }) {
        const erroresHtml = `
            <p><strong>Ubicación:</strong> ${ubicacion}</p>
            <p><strong>Faltantes:</strong> ${faltantes.length ? faltantes.join(', ') : '—'}</p>
            <p><strong>Inesperados:</strong> ${inesperados.length ? inesperados.join(', ') : '—'}</p>
        `;

        swalDialog({
            icon: 'warning',
            title: '¿Quieres reportar los errores al programador?',
            html: erroresHtml,
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626'
        }).then(result => {
            if (result.isConfirmed) {
                fetch(RUTA_ALERTA, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            tipo: 'inventario',
                            mensaje: `
Ubicación: ${ubicacion}
Faltantes: ${faltantes.join(', ') || '—'}
Inesperados: ${inesperados.join(', ') || '—'}
                        `.trim(),
                            enviar_a_departamentos: ['Programador']
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok || data.success === false) {
                            throw new Error(data.message ||
                                'Error desconocido al enviar la alerta.');
                        }
                        swalToast.fire({
                            icon: 'success',
                            title: 'Reporte enviado',
                            text: 'Gracias por notificar. El equipo ha sido avisado.'
                        });
                    })
                    .catch(error => {
                        swalToast.fire({
                            icon: 'error',
                            title: 'Error al enviar',
                            text: error.message
                        });
                        console.error('❌ Error en notificación:', error);
                    });
            }
        });
    };
</script>

<script data-navigate-once>
    // Inicializar Alpine store para inventario (robusto ante navegaciones Livewire)
    const initInventarioStore = () => {
        if (!window.Alpine) return;
        if (Alpine.store('inv')) return; // evitar redefinir

        Alpine.store('inv', {
            modoInventario: false,
            modalInventario: false,
            ubicacionActual: null,
            codigoActual: null,
            productosActuales: [],

            toggleModoInventario() {
                this.modoInventario = !this.modoInventario;
            },

            abrirModalInventario(ubicacionId, productos, codigo) {
                this.ubicacionActual = ubicacionId;
                this.productosActuales = Array.isArray(productos) ? productos.filter(Boolean) : [];
                this.codigoActual = codigo || null;
                this.modalInventario = true;
            },

            cerrarModalInventario() {
                this.modalInventario = false;
            }
        });
    };

    // Si Alpine ya está cargado (navegación SPA), inicializar de inmediato
    if (window.Alpine) {
        initInventarioStore();
    }

    // Y asegurar la inicialización en el ciclo normal
    document.addEventListener('alpine:init', initInventarioStore);
    // Reforzar tras navegaciones Livewire
    document.addEventListener('livewire:navigated', initInventarioStore);

    // Forzar cierre de modal de inventario tras recargas/navegaciones/HMR
    const forceCloseInvModal = () => {
        if (window.Alpine && Alpine.store('inv')) {
            Alpine.store('inv').modalInventario = false;
        }
        if (window.$store && window.$store.inv) {
            window.$store.inv.modalInventario = false;
        }
    };
    document.addEventListener('DOMContentLoaded', forceCloseInvModal);
    document.addEventListener('livewire:navigated', forceCloseInvModal);
</script>

<x-app-layout>
    <x-menu.ubicaciones :obras="$obras" :obra-actual-id="$obraActualId" color-base="emerald" />

    <div x-data="{
        openModal: false,
        openSectors: {},
        estadoUbicaciones: {},
        estadoSectores: {},
        borrarTodosEscaneos() {
            const ids = Array.isArray(window.ubicacionIdsInventario) ? window.ubicacionIdsInventario : [];
            if (!ids.length) {
                swalToast.fire({
                    icon: 'info',
                    title: 'Sin ubicaciones',
                    text: 'No hay ubicaciones cargadas para limpiar.'
                });
                return;
            }
    
            swalDialog({
                icon: 'warning',
                title: '¿Borrar todos los escaneos?',
                text: 'Se limpiarán los escaneos y sospechosos de todas las ubicaciones.',
                showCancelButton: true,
                confirmButtonText: 'Sí, borrar todo',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626'
            }).then(result => {
                if (!result.isConfirmed) return;
    
                ids.forEach(id => {
                    localStorage.removeItem(`inv-${id}`);
                    localStorage.removeItem(`sospechosos-${id}`);
                });
    
                window.dispatchEvent(new CustomEvent('inventario-actualizado', {}));
    
                swalToast.fire({
                    icon: 'success',
                    title: 'Escaneos borrados',
                    text: 'Todos los registros de inventario fueron limpiados.',
                });
            });
        },
        toggleAll() {
            const values = Object.values(this.openSectors);
            const allOpen = values.length && values.every(Boolean);
            Object.keys(this.openSectors).forEach(k => this.openSectors[k] = !allOpen);
        },
        abrirInventario(ubicacionId, productos, codigo) {
            const inv = ($store && $store.inv) ? $store.inv : null;
            if (!inv || !inv.modoInventario) return;
            inv.abrirModalInventario(ubicacionId, productos, codigo);
        },
        registrarEstado(ubicacionId, sector, estado) {
            this.estadoUbicaciones[ubicacionId] = estado;
            this.recalcularSector(sector);
        },
        recalcularSector(sector) {
            if (!window.ubicacionSectorMap) return;
            const estados = Object.entries(this.estadoUbicaciones)
                .filter(([id]) => window.ubicacionSectorMap[id] == sector)
                .map(([, est]) => est);
            let final = 'ok';
            if (estados.includes('consumido')) final = 'consumido';
            else if (estados.includes('ambar')) final = 'ambar';
            else if (estados.includes('rojo')) final = 'rojo';
            else if (estados.includes('pendiente')) final = 'pendiente';
            this.estadoSectores[sector] = final;
        },
        init() {
            window.addEventListener('ubicacion-estado', (e) => {
                const { ubicacionId, sector, estado } = e.detail || {};
                if (!ubicacionId || !sector || !estado) return;
                this.registrarEstado(ubicacionId, sector, estado);
            });
            document.addEventListener('livewire:navigated', () => {
                this.openModal = false;
                if ($store && $store.inv) {
                    $store.inv.modalInventario = false;
                }
            });
            this.openModal = false;
        }
    }" x-init="openModal = false" class="max-w-7xl mx-auto py-6 space-y-6">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-4 lg:p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">Ubicaciones |
                        {{ $nombreAlmacen }}</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Ubicaciones</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Crea, navega y revisa las ubicaciones del
                        almacén con acceso rápido al inventario.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button @click="toggleAll()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white rounded-lg shadow text-sm font-semibold">
                        <span
                            x-text="Object.values(openSectors).length && Object.values(openSectors).every(Boolean) ? 'Cerrar todo' : 'Abrir todo'"></span>
                    </button>
                    <button @click="$store.inv.toggleModoInventario()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white font-semibold shadow transition-all"
                        :class="$store.inv.modoInventario ?
                            'bg-gradient-to-tr from-red-600 to-red-500 hover:from-red-700 hover:to-red-600' :
                            'bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900'">
                        <span class="text-lg">📦</span>
                        <span x-text="$store.inv.modoInventario ? 'Salir de inventario' : 'Hacer inventario'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div x-show="$store.inv && $store.inv.modoInventario" x-cloak
            class="border border-red-200 dark:border-red-700 rounded-xl shadow-sm bg-white dark:bg-gray-900 hover:border-red-400 hover:shadow-md transition">
            <button @click="borrarTodosEscaneos()"
                class="w-full flex items-center justify-between px-4 py-2.5 text-left">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-red-600 text-white font-bold text-lg shadow-sm">🗑️</span>
                    <div class="text-left">
                        <p class="text-base font-semibold text-red-700 dark:text-red-300">Borrar escaneos de todas las
                            ubicaciones</p>
                        <p class="text-xs text-red-600 dark:text-red-400">Limpia escaneados y sospechosos guardados en
                            este dispositivo.</p>
                    </div>
                </div>
            </button>
        </div>

        {{-- Sectores (scroll en desktop para evitar scroll global) --}}
        <div class="space-y-4 lg:overflow-y-auto lg:pr-1"
            :class="$store.inv && $store.inv.modoInventario ?
                'lg:max-h-[calc(100vh-450px)]' :
                'lg:max-h-[calc(100vh-385px)]'">
            @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
                <div x-init="if (openSectors['{{ $sector }}'] === undefined) openSectors['{{ $sector }}'] = false"
                    class="border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm bg-white dark:bg-gray-900 overflow-hidden">
                    <button @click="openSectors['{{ $sector }}'] = !openSectors['{{ $sector }}']"
                        class="w-full flex items-center justify-between px-5 py-4 bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white">
                        @php
                            $totalMP = $ubicaciones->reduce(
                                fn($carry, $ubicacion) => $carry + $ubicacion->productos->count(),
                                0,
                            );
                        @endphp
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center justify-center h-10 w-10 rounded-full text-white font-bold"
                                :class="!($store.inv && $store.inv.modoInventario) ? 'bg-gray-600' : (
                                    estadoSectores['{{ $sector }}'] === 'ok' ? 'bg-green-600' :
                                    estadoSectores['{{ $sector }}'] === 'consumido' ? 'bg-blue-600' :
                                    estadoSectores['{{ $sector }}'] === 'ambar' ? 'bg-amber-500' :
                                    estadoSectores['{{ $sector }}'] === 'rojo' ? 'bg-red-600' :
                                    'bg-gray-600'
                                )">S{{ $sector }}</span>
                            <div class="flex flex-col gap-1 items-start">
                                <p class="text-lg font-semibold">Sector {{ $sector }}</p>
                                <p class="text-xs text-white/80">Material en sector: {{ $totalMP }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-white/70">{{ count($ubicaciones) }} ubicaciones</span>
                            <svg :class="openSectors['{{ $sector }}'] ? 'rotate-180' : ''"
                                class="w-5 h-5 transition-transform" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <div x-show="openSectors['{{ $sector }}']" x-collapse
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4">
                        @foreach ($ubicaciones as $ubicacion)
                            <div class="bg-slate-50 dark:bg-gray-800 border rounded-xl p-4 flex flex-col gap-2 shadow-sm hover:shadow-md transition"
                                x-data="{
                                    productos: @js($ubicacion->productos->pluck('codigo')->values()),
                                    ubicId: '{{ $ubicacion->id }}',
                                    sector: '{{ $sector }}',
                                    estado: 'pendiente',
                                    init() {
                                        this.calcularEstado();
                                        window.addEventListener('inventario-actualizado', () => this.calcularEstado());
                                    },
                                    calcularEstado() {
                                        const esperados = this.productos || [];
                                        const escaneados = JSON.parse(localStorage.getItem(`inv-${this.ubicId}`) || '[]');
                                        const sospechosos = JSON.parse(localStorage.getItem(`sospechosos-${this.ubicId}`) || '[]');
                                        let estado = esperados.length === 0 ? 'ok' : (escaneados.length === esperados.length ? 'ok' : 'pendiente');
                                
                                        for (const codigo of sospechosos) {
                                            const estadoProd = (window.productosEstados || {})[codigo];
                                            const ubicAsign = (window.productosAsignados || {})[codigo];
                                            if (estadoProd === 'consumido') {
                                                estado = 'consumido';
                                                break;
                                            }
                                            if (ubicAsign && ubicAsign.toString() !== this.ubicId.toString()) {
                                                estado = 'ambar';
                                                break;
                                            }
                                            if (!ubicAsign) {
                                                estado = 'rojo';
                                                break;
                                            }
                                        }
                                
                                        this.estado = estado;
                                        window.dispatchEvent(new CustomEvent('ubicacion-estado', {
                                            detail: { ubicacionId: this.ubicId, sector: this.sector, estado }
                                        }));
                                    }
                                }"
                                :class="[
                                    $store.inv && $store.inv.modoInventario ?
                                    'cursor-pointer hover:ring-2 hover:ring-blue-400' : '',
                                    $store.inv && $store.inv.modoInventario ?
                                    (estado === 'ok' ? 'border-green-400' :
                                        estado === 'consumido' ? 'border-blue-400' :
                                        estado === 'ambar' ? 'border-amber-400' :
                                        estado === 'rojo' ? 'border-red-500' :
                                        'border-gray-200 dark:border-gray-700') :
                                    'border-gray-200 dark:border-gray-700'
                                ]"
                                @click="abrirInventario('{{ $ubicacion->id }}', @js($ubicacion->productos->pluck('codigo')->values()), {{ json_encode($ubicacion->codigo ?? 'SIN-CODIGO') }})">
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('ubicaciones.show', $ubicacion->id) }}" wire:navigate
                                            class="text-sm font-semibold text-gray-900 dark:text-white hover:text-blue-700 dark:hover:text-blue-300 transition"
                                            @click.stop="if($store.inv && $store.inv.modoInventario) { $event.preventDefault(); }">
                                            {{ $ubicacion->codigo ?? 'SIN-CODIGO' }} | {{ $ubicacion->id }}
                                        </a>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">
                                            {{ $ubicacion->descripcion }}
                                        </p>
                                    </div>

                                    <span
                                        class="text-xs px-2 py-1 rounded-full bg-gradient-to-tr from-gray-900 to-gray-700 text-white font-semibold">Material:
                                        {{ $ubicacion->productos->count() }}</span>
                                </div>

                                @php
                                    $tieneProductos = $ubicacion->productos->isNotEmpty();
                                    $tienePaquetes = $ubicacion->paquetes->isNotEmpty();
                                @endphp

                                @if (!$tieneProductos && !$tienePaquetes)
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 max-md:hidden">Ubicación sin
                                        material.</p>
                                @else
                                    @if ($tieneProductos)
                                        <div class="w-full mt-1 space-y-1">
                                            @foreach ($ubicacion->productos as $producto)
                                                <div
                                                    class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-400 rounded-md px-2 py-1 text-center">
                                                    <p
                                                        class="text-[11px] text-gray-800 dark:text-gray-100 font-semibold">
                                                        {{ $producto->codigo ?? $producto->id }} | Ø
                                                        {{ $producto->productoBase->diametro ?? ($producto->diametro ?? 'N/D') }}
                                                        mm
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div
            class="border-2 border-dashed border-blue-200 dark:border-blue-700/70 rounded-2xl shadow-sm bg-white/70 dark:bg-gray-900/70 hover:border-blue-500 hover:shadow-md transition">
            <button @click="openModal = true" class="w-full flex items-center justify-between px-5 py-3 text-left">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-600 text-white font-bold text-xl shadow-sm">+</span>
                    <div class="text-left">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">Nueva ubicación</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Añade rápidamente otra ubicación dentro del
                            almacén.</p>
                    </div>
                </div>
            </button>
        </div>

        <!-- Modal crear ubicación -->
        <template x-teleport="body">
            <div x-show="openModal" x-transition x-cloak
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur overflow-y-auto">
                <div @click.away="openModal = false"
                    class="bg-white dark:bg-gray-900 w-full max-w-lg p-6 rounded-xl shadow-2xl mx-4 my-4 border border-gray-200 dark:border-gray-800">
                    <h2 class="text-center text-lg font-bold mb-4 text-gray-800 dark:text-white">
                        Crear Nueva Ubicación ({{ $nombreAlmacen }})
                    </h2>

                    <form method="POST" action="{{ route('ubicaciones.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="almacen" value="{{ $obraActualId }}">

                        <x-tabla.select name="sector" label="📍 Sector" :options="collect(range(1, 20))
                            ->mapWithKeys(
                                fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                            )
                            ->toArray()"
                            placeholder="Ej. 01, 02, 03..." />

                        <x-tabla.select name="ubicacion" label="📦 Ubicación" :options="collect(range(1, 100))
                            ->mapWithKeys(
                                fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                            )
                            ->toArray()"
                            placeholder="Ej. 01 a 100" />

                        <x-tabla.input name="descripcion" label="📝 Descripción"
                            placeholder="Ej. Entrada de barras largas" />

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="openModal = false"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800">Cancelar</button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal de Inventario -->
        <template x-teleport="body">
            <div x-show="$store.inv.modalInventario" x-transition x-cloak
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm overflow-y-auto pt-0"
                @keydown.escape.window="$store.inv.cerrarModalInventario()" wire:ignore>
                <template x-if="$store.inv.modalInventario">
                    <div class="relative bg-white dark:bg-gray-900 max-w-5xl sm:h-auto sm:max-h-[90vh] rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col min-h-0 m-4 h-[98vh] w-[98vw]"
                        @click.away="$store.inv.cerrarModalInventario()" x-data="inventarioUbicacion($store.inv.productosActuales, $store.inv.ubicacionActual)"
                        :key="$store.inv.ubicacionActual">

                        <!-- Header del modal -->
                        <div
                            class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white px-6 py-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold">
                                    Inventario - Ubicación
                                    <span x-text="$store.inv.codigoActual || nombreUbicacion || '—'"></span>
                                    <span class="text-white/70 text-sm ml-2">ID: <span
                                            x-text="nombreUbicacion || '—'"></span></span>
                                </h2>
                                <p class="text-sm text-white/80 mt-1">Escanea los productos de esta ubicación</p>
                            </div>
                            <button @click="$store.inv.cerrarModalInventario()"
                                class="text-white hover:text-gray-300 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Input QR -->
                        <div
                            class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <input type="text"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm"
                                placeholder="Escanea el código QR aquí..."
                                x-on:keydown.enter.prevent="procesarQR($event.target.value); $event.target.value = ''"
                                x-ref="inputQR" inputmode="none" autocomplete="off">
                        </div>

                        <!-- Barra de progreso -->
                        <div
                            class="px-6 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Progreso</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400"
                                    x-text="`${escaneados.length} / ${productosEsperados.length} escaneados`"></span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 transition-all duration-300"
                                    :style="`width: ${progreso()}%`"></div>
                            </div>
                        </div>

                        <!-- Contenido scrollable -->
                        <div
                            class="flex-1 overflow-y-auto sm:px-6 sm:py-4 grid grid-cols-1 lg:grid-cols-2 gap-2 min-h-0">
                            <!-- Tabla de productos esperados -->
                            <div
                                class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 shadow-sm flex flex-col">
                                <div class="flex-1 overflow-y-auto max-h-[420px]">
                                    <!-- Vista desktop -->
                                    <div class="hidden sm:block">
                                        <table
                                            class="w-full text-xs divide-y divide-gray-200 dark:divide-gray-700 table-auto">
                                            <thead class="bg-gray-100 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">#
                                                    </th>
                                                    <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">
                                                        Código
                                                    </th>
                                                    <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">
                                                        Colada
                                                    </th>
                                                    <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">
                                                        Tipo
                                                    </th>
                                                    <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">
                                                        Detalles</th>
                                                    <th class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">
                                                        Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                <template x-for="(codigo, idx) in productosEsperados"
                                                    :key="codigo">
                                                    <tr
                                                        :class="{
                                                            'bg-green-50 dark:bg-green-900/20': productoEscaneado(
                                                                codigo),
                                                            'ring-2 ring-green-500 shadow-md scale-[1.01] transition-all duration-300': ultimoCodigo ===
                                                                codigo
                                                        }">
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100"
                                                            x-text="idx + 1"></td>
                                                        <td class="px-3 py-2 font-mono text-gray-900 dark:text-gray-100"
                                                            x-text="codigo"></td>
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100"
                                                            x-text="window.detallesProductos[codigo]?.colada || '—'">
                                                        </td>
                                                        <td class="px-3 py-2 capitalize text-gray-900 dark:text-gray-100"
                                                            x-text="window.detallesProductos[codigo]?.tipo || '—'">
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                mm
                                                            </span>
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud || '—'"></span>
                                                                m
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                                            <span x-show="productoEscaneado(codigo)"
                                                                class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">✓
                                                                OK</span>
                                                            <span x-show="!productoEscaneado(codigo)"
                                                                class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pendiente</span>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <!-- Productos añadidos dinámicamente -->
                                                <template x-for="codigo in productosAnadidos()"
                                                    :key="'added-' + codigo">
                                                    <tr class="bg-blue-50 dark:bg-blue-900/20">
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">+</td>
                                                        <td class="px-3 py-2 font-mono text-gray-900 dark:text-gray-100"
                                                            x-text="codigo"></td>
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100"
                                                            x-text="window.detallesProductos[codigo]?.colada || '—'">
                                                        </td>
                                                        <td class="px-3 py-2 capitalize text-gray-900 dark:text-gray-100"
                                                            x-text="window.detallesProductos[codigo]?.tipo || '—'">
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                mm
                                                            </span>
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud || '—'"></span>
                                                                m
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                                            <span
                                                                class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">✓
                                                                OK</span>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Vista mobile -->
                                    <div class="sm:hidden space-y-2 p-3">
                                        <template x-for="(codigo, idx) in productosEsperados" :key="codigo">
                                            <div class="border rounded-lg p-3"
                                                :class="{
                                                    'bg-green-50 dark:bg-green-900/20 border-green-200': productoEscaneado(
                                                        codigo),
                                                    'bg-white dark:bg-gray-800 border-gray-200': !productoEscaneado(
                                                        codigo),
                                                    'ring-2 ring-green-500': ultimoCodigo === codigo
                                                }">
                                                <div class="flex justify-between items-center">
                                                    <div>
                                                        <p class="font-mono font-semibold text-sm text-gray-900 dark:text-gray-100"
                                                            x-text="codigo"></p>
                                                        <div class="flex gap-2 text-[10px]">
                                                            <p class="text-gray-600 dark:text-gray-400 capitalize"
                                                                x-text="window.detallesProductos[codigo]?.tipo || '—'">
                                                            </p>
                                                            <p class="text-gray-500 dark:text-gray-500">
                                                                <span
                                                                    x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                    Ø <span
                                                                        x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                    mm
                                                                </span>
                                                                <span
                                                                    x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                    Ø <span
                                                                        x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                    mm /
                                                                    <span
                                                                        x-text="window.detallesProductos[codigo]?.longitud || '—'"></span>
                                                                    m
                                                                </span>
                                                            </p>
                                                            <p class="text-gray-500 dark:text-gray-500">~</p>
                                                            <p class="text-gray-500 dark:text-gray-500">
                                                                Col: <span
                                                                    x-text="window.detallesProductos[codigo]?.colada || '—'"></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <span x-show="productoEscaneado(codigo)"
                                                        class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">✓
                                                        OK</span>
                                                    <span x-show="!productoEscaneado(codigo)"
                                                        class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pend.</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Productos añadidos dinámicamente (mobile) -->
                                        <template x-for="codigo in productosAnadidos()"
                                            :key="'added-mobile-' + codigo">
                                            <div
                                                class="border rounded-lg p-2 bg-blue-50 dark:bg-blue-900/20 border-blue-200">
                                                <div class="flex justify-between items-center">
                                                    <div>
                                                        <p class="font-mono font-semibold text-gray-900 dark:text-gray-100"
                                                            x-text="codigo"></p>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 capitalize"
                                                            x-text="window.detallesProductos[codigo]?.tipo || '—'"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                                            Colada: <span
                                                                x-text="window.detallesProductos[codigo]?.colada || '—'"></span>
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                mm
                                                            </span>
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || '—'"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud || '—'"></span>
                                                                m
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <span
                                                        class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">✓
                                                        OK</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Productos inesperados -->
                            <div
                                class="border border-red-200 dark:border-red-700 rounded-lg bg-white dark:bg-gray-800 shadow-sm flex flex-col">
                                <div
                                    class="px-4 py-3 border-b border-red-200 dark:border-red-700 flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">Productos
                                        inesperados
                                    </h3>
                                    <span class="text-xs text-red-500" x-text="sospechosos.length"></span>
                                </div>
                                <div class="flex-1 overflow-y-auto max-h-[420px] p-3">
                                    <template x-for="(codigo, idx) in sospechosos" :key="'sosp-' + codigo">
                                        <li class="flex items-center justify-between gap-3 rounded-lg border px-4 py-3 mb-2 list-none"
                                            :class="idx % 2 === 0 ?
                                                'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700' :
                                                'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700'"
                                            x-data="{
                                                ubic: null,
                                                hasId: false,
                                                misma: false,
                                                estado: null,
                                                esConsumido: false,
                                                colada: null
                                            }" x-init="ubic = (asignados && Object.prototype.hasOwnProperty.call(asignados, codigo)) ? asignados[codigo] : null;
                                            hasId = (ubic !== null && ubic !== '' && ubic !== undefined);
                                            misma = (hasId && nombreUbicacion !== null && nombreUbicacion !== undefined && ubic.toString() === nombreUbicacion.toString());
                                            estado = (estados && Object.prototype.hasOwnProperty.call(estados, codigo)) ? (estados[codigo] ?? null) : null;
                                            esConsumido = (estado === 'consumido');
                                            colada = (window.detallesProductos && Object.prototype.hasOwnProperty.call(window.detallesProductos, codigo)) ?
                                                (window.detallesProductos[codigo]?.colada ?? null) :
                                                null;">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="inline-block h-2.5 w-2.5 rounded-full flex-shrink-0"
                                                    :class="esConsumido ? 'bg-blue-500/80' : (hasId ? 'bg-amber-500/80' :
                                                        'bg-red-500/80')"></span>
                                                <span class="font-mono text-sm font-semibold"
                                                    :class="esConsumido ? 'text-blue-600' : (hasId ? 'text-amber-600' :
                                                        'text-red-800')"
                                                    x-text="codigo"></span>
                                                <span
                                                    class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                    <span x-show="esConsumido">Consumido</span>
                                                    <span x-show="!esConsumido && hasId && !misma">Ubic: <span
                                                            x-text="ubic"></span></span>
                                                    <span x-show="!esConsumido && !hasId">Sin registrar</span>
                                                </span>
                                                <span x-show="colada"
                                                    class="text-[11px] text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                                                    Colada: <span x-text="colada"></span>
                                                </span>
                                            </div>
                                            <button x-show="!esConsumido && hasId && !misma"
                                                @click="reasignarProducto(codigo)"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold flex-shrink-0">
                                                Asignar aquí
                                            </button>
                                            <span x-show="!esConsumido && !hasId"
                                                class="text-gray-500 text-xs flex-shrink-0">
                                                No asignable
                                            </span>
                                        </li>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Footer con botones -->
                        <div
                            class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button @click="resetear()"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-lg text-[10px] shadow">
                                Limpiar escaneos
                            </button>
                            <button @click="reportarErrores()"
                                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg text-[10px] shadow">
                                Reportar errores
                            </button>
                            <button @click="$store.inv.cerrarModalInventario()"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-[10px] shadow">
                                Cerrar
                            </button>
                        </div>
                    </div>
            </div>
        </template>
    </div>

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- Vite: qr-bundle -->
    @vite(['resources/js/qr/qr-bundle.js'])

    <!-- Elementos de audio para feedback -->
    <audio id="sonido-ok" src="{{ asset('sonidos/scan-ok.mp3') }}" preload="auto"></audio>
    <audio id="sonido-error" src="{{ asset('sonidos/scan-error.mp3') }}" preload="auto"></audio>
    <audio id="sonido-pedo" src="{{ asset('sonidos/scan-error.mp3') }}" preload="auto"></audio>
    <audio id="sonido-estaEnOtraUbi" src="{{ asset('sonidos/scan-error.mp3') }}" preload="auto"></audio>
    <audio id="sonido-noTieneUbicacion" src="{{ asset('sonidos/scan-error.mp3') }}" preload="auto"></audio>
    <audio id="sonido-consumido" src="{{ asset('sonidos/scan-error.mp3') }}" preload="auto"></audio>
</x-app-layout>
