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
                    'peso_stock' => $p->peso_stock,
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

    $productosPorUbicacion = [];
    foreach ($ubicacionesPorSector as $ubicacionesSector) {
        foreach ($ubicacionesSector as $ubi) {
            $productosPorUbicacion[$ubi->id] = [
                'ubicacion' => $ubi->codigo ?? 'SIN-CODIGO',
                'productos' => $ubi->productos->pluck('codigo')->filter()->values(),
            ];
        }
    }
@endphp

<script data-navigate-reload>
    /* Mapas globales base (no reactivos) */
    window.productosAsignados = @json(\App\Models\Producto::whereNotNull('ubicacion_id')->pluck('ubicacion_id', 'codigo'));
    window.detallesProductos = @json($detalles);
    window.productosEstados = @json(\App\Models\Producto::pluck('estado', 'codigo'));
    window.productosMaquinas = @json(\App\Models\Producto::pluck('maquina_id', 'codigo'));
    window.ubicacionSectorMap = @json($ubicacionSectorMap);
    window.ubicacionIdsInventario = @json($ubicacionIds);
    window.productosPorUbicacion = @json($productosPorUbicacion);
</script>

<script data-navigate-reload>
    const RUTA_ALERTA = @json(route('alertas.store'));
    const RUTA_CONSUMO_LOTE = @json(route('productos.consumirLote'));

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
            zIndex: 99999,
            ...options
        });
    });

    window.sliderConsumo = function(onComplete) {
        return {
            dragging: false,
            startX: 0,
            startHandleX: 0,
            handleX: 0,
            maxX: 0,
            completed: false,
            processing: false,
            onComplete,
            init() {
                this.recalcMax();
                window.addEventListener('modal-consumo-abierto', () => this.recalcMax());
            },
            getClientX(event) {
                if (!event) return null;
                if (event.touches && event.touches.length) return event.touches[0].clientX;
                if (event.changedTouches && event.changedTouches.length) return event.changedTouches[0].clientX;
                return event.clientX ?? null;
            },
            recalcMax() {
                const track = this.$refs.track;
                const handle = this.$refs.handle;
                if (!track || !handle) return;
                const margin = 8;
                this.maxX = Math.max((track.clientWidth - handle.clientWidth) - margin, 0);
                if (this.completed) {
                    this.handleX = this.maxX;
                } else if (this.handleX > this.maxX) {
                    this.handleX = 0;
                }
            },
            startDrag(event) {
                if (this.completed || this.processing) return;
                const x = this.getClientX(event);
                if (x === null) return;
                if (!this.maxX) this.recalcMax();
                this.dragging = true;
                this.startX = x;
                this.startHandleX = this.handleX;
            },
            onDrag(event) {
                if (!this.dragging) return;
                const x = this.getClientX(event);
                if (x === null) return;
                const delta = x - this.startX;
                const next = Math.min(Math.max(this.startHandleX + delta, 0), this.maxX);
                this.handleX = next;
            },
            async ejecutarAccion() {
                if (typeof this.onComplete !== 'function') return;
                try {
                    await this.onComplete();
                } catch (err) {
                    console.error('[INV] Error al ejecutar consumo', err);
                }
            },
            stopDrag() {
                if (!this.dragging) return;
                this.dragging = false;
                const threshold = this.maxX ? Math.max(this.maxX - 10, this.maxX * 0.9) : 0;
                if (this.handleX >= threshold && this.maxX > 0) {
                    this.handleX = this.maxX;
                    this.completed = true;
                    this.processing = true;
                    Promise.resolve(this.ejecutarAccion())
                        .finally(() => {
                            this.processing = false;
                            this.completed = false;
                            this.handleX = 0;
                        });
                } else {
                    this.handleX = 0;
                }
            }
        };
    };

    window.AUDIO_INV_URLS = {
        ok: "{{ asset('sonidos/scan-ok.wav') }}",
        error: "{{ asset('sonidos/scan-error.mp3') }}",
        invalido: "{{ asset('sonidos/scan-error.mp3') }}",
        otra: "{{ asset('sonidos/scan-error.mp3') }}",
        sinUbic: "{{ asset('sonidos/scan-error.mp3') }}",
        consumido: "{{ asset('sonidos/scan-error.mp3') }}",
    };

    /* ------ Alpine factory per location ------ */
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
            maquinas: {},

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

                /* 1?? recuperar progreso previo */
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

                this.maquinas = {
                    ...(window.productosMaquinas || {})
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

                // ? Si no empieza por MP, descartamos
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
                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados)); // 2?? persist
                        this.reproducirOk();
                    }

                    // ?? Si estaba en inesperados, lo quitamos
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
                        // this.confirmarRestablecerConsumido(codigo);
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

                /* 3?? flash highlight */
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

            confirmarRestablecerConsumido(codigo) {
                this.$store?.inv && (this.$store.inv.bloquearCierre = true);
                if (!this.nombreUbicacion) {
                    swalDialog({
                        icon: 'error',
                        title: 'Sin ubicación',
                        text: 'No se pudo detectar la ubicación actual para restablecer el producto.'
                    }).finally(() => {
                        this.$store?.inv && (this.$store.inv.bloquearCierre = false);
                    });
                    return;
                }

                swalDialog({
                    icon: 'question',
                    title: 'Producto consumido',
                    text: `El producto ${codigo} está marcado como consumido. ¿Restablecerlo en esta ubicación?`,
                    showCancelButton: true,
                    confirmButtonText: 'Restablecer aquí',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#16a34a'
                }).then(result => {
                    if (result.isConfirmed) {
                        this.solicitarPesoRestablecer(codigo).then(peso => {
                            if (peso === null || peso === undefined) {
                                this.$store?.inv && (this.$store.inv.bloquearCierre = false);
                                return;
                            }
                            this.restablecerConsumido(codigo, peso);
                        }).catch(() => {
                            this.$store?.inv && (this.$store.inv.bloquearCierre = false);
                        });
                    } else {
                        this.$store?.inv && (this.$store.inv.bloquearCierre = false);
                    }
                }).catch(() => {
                    this.$store?.inv && (this.$store.inv.bloquearCierre = false);
                });
            },

            solicitarPesoRestablecer(codigo) {
                const detalles = (window.detallesProductos || {})[codigo] || {};
                const pesoInicial = Number(detalles.peso_inicial || 0);
                const pesoActual = Number(detalles.peso_stock || pesoInicial || 0);
                const max = pesoInicial > 0 ? pesoInicial : (pesoActual || 0);
                const start = max ? Math.min(Math.max(pesoActual, 0), max) : 0;

                return swalDialog({
                    icon: 'question',
                    title: 'Peso en stock',
                    input: 'range',
                    inputAttributes: {
                        min: 0,
                        max: max || 0,
                        step: 0.1
                    },
                    inputValue: start,
                    inputLabel: `Stock: ${start} kg de ${max} kg`,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar peso',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        const swal = window.Swal;
                        const input = swal?.getInput?.();
                        const label = swal?.getInputLabel?.();
                        if (!input || !label) return;
                        const sync = () => {
                            const val = Number(input.value || 0);
                            label.textContent = `Stock: ${val} kg de ${max} kg`;
                        };
                        input.addEventListener('input', sync);
                        sync();
                    }
                }).then(res => res.isConfirmed ? Number(res.value || 0) : null);
            },

            restablecerConsumido(codigo, pesoStock) {
                fetch("{{ route('productos.restablecerInventario', ['codigo' => '___CODIGO___']) }}".replace(
                        '___CODIGO___', codigo), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            ubicacion_id: this.nombreUbicacion,
                            peso_stock: pesoStock
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok || data.success === false) {
                            throw new Error(data.message || 'Error desconocido al restablecer.');
                        }

                        window.productosEstados[codigo] = 'almacenado';
                        this.estados[codigo] = 'almacenado';
                        window.productosAsignados[codigo] = this.nombreUbicacion;
                        this.asignados[codigo] = this.nombreUbicacion;
                        if (!window.detallesProductos[codigo]) window.detallesProductos[codigo] = {};
                        window.detallesProductos[codigo].peso_stock = pesoStock;
                        if (window.productosMaquinas) window.productosMaquinas[codigo] = null;
                        this.maquinas[codigo] = null;

                        this.sospechosos = this.sospechosos.filter(c => c !== codigo);
                        if (!this.productosEsperados.includes(codigo)) this.productosEsperados.unshift(
                            codigo);
                        if (!this.originalEsperados.includes(codigo)) this.originalEsperados.unshift(
                            codigo);
                        if (!this.escaneados.includes(codigo)) this.escaneados.push(codigo);

                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados));
                        localStorage.setItem(claveSospechosos, JSON.stringify(this.sospechosos));

                        window.dispatchEvent(new CustomEvent('producto-reasignado', {
                            detail: {
                                codigo,
                                nuevaUbicacion: this.nombreUbicacion
                            }
                        }));
                        window.dispatchEvent(new CustomEvent('inventario-actualizado', {
                            detail: {
                                ubicacionId: this.nombreUbicacion
                            }
                        }));

                        return swalToast.fire({
                            icon: 'success',
                            title: 'Producto restablecido',
                            text: `El producto ${codigo} se marcó como almacenado en esta ubicación.`
                        });
                    })
                    .catch(err => {
                        swalDialog({
                            icon: 'error',
                            title: 'Error',
                            text: err.message
                        });
                    })
                    .finally(() => {
                        this.$store?.inv && (this.$store.inv.bloquearCierre = false);
                    });
            },

            asignarDesdeFabricando(codigo) {
                fetch("{{ route('productos.liberarMaquinaInventario', ['codigo' => '___CODIGO___']) }}".replace(
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
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok || data.success === false) {
                            throw new Error(data.message || 'Error al asignar desde máquina.');
                        }

                        window.productosEstados[codigo] = 'almacenado';
                        this.estados[codigo] = 'almacenado';
                        window.productosAsignados[codigo] = this.nombreUbicacion;
                        this.asignados[codigo] = this.nombreUbicacion;
                        if (window.productosMaquinas) window.productosMaquinas[codigo] = null;
                        this.maquinas[codigo] = null;

                        this.sospechosos = this.sospechosos.filter(c => c !== codigo);
                        if (!this.productosEsperados.includes(codigo)) this.productosEsperados.unshift(
                            codigo);
                        if (!this.originalEsperados.includes(codigo)) this.originalEsperados.unshift(
                            codigo);
                        if (!this.escaneados.includes(codigo)) this.escaneados.push(codigo);

                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados));
                        localStorage.setItem(claveSospechosos, JSON.stringify(this.sospechosos));

                        window.dispatchEvent(new CustomEvent('producto-reasignado', {
                            detail: {
                                codigo,
                                nuevaUbicacion: this.nombreUbicacion
                            }
                        }));
                        window.dispatchEvent(new CustomEvent('inventario-actualizado', {
                            detail: {
                                ubicacionId: this.nombreUbicacion
                            }
                        }));

                        return swalToast.fire({
                            icon: 'success',
                            title: 'Asignado',
                            text: `El producto ${codigo} fue liberado de máquina y asignado aquí.`
                        });
                    })
                    .catch(err => {
                        swalDialog({
                            icon: 'error',
                            title: 'Error',
                            text: err.message
                        });
                    });
            },

            // ?? Reasignar producto
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

                            // ?? Emitimos evento global para que todas las ubicaciones se actualicen
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
            <p><strong>Faltantes:</strong> ${faltantes.length ? faltantes.join(', ') : ''}</p>
            <p><strong>Inesperados:</strong> ${inesperados.length ? inesperados.join(', ') : ''}</p>
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
Faltantes: ${faltantes.join(', ') || ''}
Inesperados: ${inesperados.join(', ') || ''}
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
                        console.error('? Error en notificación:', error);
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
            bloquearCierre: false,

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

    window.paginaUbicaciones = function() {
        return {
            openModal: false,
            modalConsumo: false,
            openSectors: {},
            estadoUbicaciones: {},
            estadoSectores: {},
            showLeyenda: false,
            listaConsumos: [],
            consumoCargando: false,
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
            abrirConsumos() {
                const mapa = window.productosPorUbicacion || {};
                const acumulado = [];
                Object.entries(mapa).forEach(([ubicId, info]) => {
                    const escaneados = JSON.parse(localStorage.getItem(`inv-${ubicId}`) || '[]');
                    const productos = Array.isArray(info?.productos) ? info.productos.filter(Boolean) : [];
                    productos.forEach(codigo => {
                        const ok = escaneados.includes(codigo);
                        if (!ok) {
                            acumulado.push({
                                codigo,
                                ubicacion: info?.ubicacion || ubicId,
                                estado: 'Pend.'
                            });
                        }
                    });
                });

                this.listaConsumos = acumulado.sort((a, b) => {
                    const byUbic = (a.ubicacion || '').toString().localeCompare((b.ubicacion || '').toString());
                    if (byUbic !== 0) return byUbic;
                    return (a.codigo || '').toString().localeCompare((b.codigo || '').toString());
                });
                this.modalConsumo = true;
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('modal-consumo-abierto')));
            },
            async consumirPendientes() {
                if (!RUTA_CONSUMO_LOTE) {
                    swalToast.fire({
                        icon: 'error',
                        title: 'Ruta no disponible',
                        text: 'No se encontró la ruta de consumo masivo.'
                    });
                    return;
                }
                const codigos = Array.isArray(this.listaConsumos) ? this.listaConsumos.map(i => i.codigo).filter(Boolean) : [];
                if (!codigos.length) {
                    swalToast.fire({
                        icon: 'info',
                        title: 'Sin pendientes',
                        text: 'No hay materiales por consumir.'
                    });
                    return;
                }
    
                this.consumoCargando = true;
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                let data = {};
    
                try {
                    const resp = await fetch(RUTA_CONSUMO_LOTE, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            codigos
                        })
                    });
                    data = await resp.json().catch(() => ({}));
                    const ok = resp.ok && data && data.ok !== false;
    
                    if (Array.isArray(data?.consumidos) && data.consumidos.length) {
                        this.aplicarConsumoLocal(data.consumidos);
                    }
    
                    if (ok) {
                        swalToast.fire({
                            icon: 'success',
                            title: 'Materiales consumidos',
                            text: data?.message || 'Se marcaron como consumidos.'
                        });
                        this.modalConsumo = false;
                        this.listaConsumos = [];
                    } else {
                        swalToast.fire({
                            icon: 'error',
                            title: 'No se pudo consumir',
                            text: data?.message || 'Inténtalo de nuevo.'
                        });
                        this.abrirConsumos();
                    }
                } catch (err) {
                    console.error('[INV] Error consumiendo pendientes', err, data);
                    swalToast.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo completar el consumo.'
                    });
                    this.abrirConsumos();
                } finally {
                    this.consumoCargando = false;
                }
            },
            aplicarConsumoLocal(codigosConsumidos) {
                const lista = Array.isArray(codigosConsumidos) ? codigosConsumidos.filter(Boolean) : [];
                if (!lista.length) return;
                const setCodigos = new Set(lista);
    
                const asignados = window.productosAsignados || {};
                const estados = window.productosEstados || {};
                const mapa = window.productosPorUbicacion || {};
                window.productosAsignados = asignados;
                window.productosEstados = estados;
                window.productosPorUbicacion = mapa;
    
                Object.entries(mapa).forEach(([ubicId, info]) => {
                    if (!Array.isArray(info?.productos)) return;
                    info.productos = info.productos.filter(c => !setCodigos.has(c));
                });
    
                lista.forEach(codigo => {
                    if (asignados && Object.prototype.hasOwnProperty.call(asignados, codigo)) {
                        const ubic = asignados[codigo];
                        if (ubic !== undefined && ubic !== null) {
                            const key = `inv-${ubic}`;
                            const guardados = JSON.parse(localStorage.getItem(key) || '[]');
                            const filtrados = Array.isArray(guardados) ? guardados.filter(c => c !== codigo) : [];
                            localStorage.setItem(key, JSON.stringify(filtrados));
                        }
                        asignados[codigo] = null;
                    }
                    estados[codigo] = 'consumido';
                });
    
                window.dispatchEvent(new CustomEvent('inventario-actualizado', {}));
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
                else if (estados.includes('fabricando')) final = 'fabricando';
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
        };
    };
</script>

<x-app-layout>
    <x-menu.ubicaciones :obras="$obras" :obra-actual-id="$obraActualId" color-base="emerald" />

<div x-data="paginaUbicaciones()" x-init="openModal = false" class="max-w-7xl mx-auto space-y-4">
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

                <div class="flex justify-between md:flex-col gap-2 items-end h-fit">
                    <button @click="toggleAll()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 h-10 bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white rounded-lg shadow text-xs md:text-sm font-semibold w-[130px] md:w-[160px]">
                        <span
                            x-text="Object.values(openSectors).length && Object.values(openSectors).every(Boolean) ? 'Cerrar todo' : 'Abrir todo'"></span>
                    </button>

                    <div class="flex gap-3 items-center">
                        <!-- Botón Leyenda (Solo visible en modo inventario) -->
                        <div x-data="{ showLeyenda: false }" class="relative" x-show="$store.inv.modoInventario" x-cloak>
                            <button @click="showLeyenda = !showLeyenda"
                                class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xl shadow transition-all max-md:text-[12px]"
                                title="Leyenda de colores">
                                i
                            </button>

                            <div x-show="showLeyenda" @click.away="showLeyenda = false"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1"
                                class="absolute left-1/2 -translate-x-1/2 top-full w-64 max-w-[calc(100vw-2rem)] p-4 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 mt-4">

                                <!-- Flecha del bocadillo -->
                                <div
                                    class="absolute -top-2 left-1/2 -translate-x-1/2 w-4 h-4 bg-white dark:bg-gray-800 border-t border-l border-gray-200 dark:border-gray-700 transform rotate-45">
                                </div>

                                <h3 class="font-bold text-gray-900 dark:text-white mb-3 text-sm relative z-10">Leyenda
                                    de
                                    estados</h3>

                                <div class="space-y-2 text-xs text-gray-700 dark:text-gray-300 relative z-10">
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-green-600"></span> <span>OK</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-blue-600"></span> <span>Consumido</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-amber-500"></span> <span>Asignado a otra
                                            ubicación</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-red-600"></span> <span>Sin ubicación</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full bg-gray-500"></span> <span>Pendiente</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button @click="$store.inv.toggleModoInventario()"
                            class="inline-flex items-center gap-2 px-4 py-2 h-10 rounded-lg text-white font-semibold shadow transition-all text-xs md:text-sm w-[160px] justify-center"
                            :class="$store.inv.modoInventario ?
                                'bg-gradient-to-tr from-red-600 to-red-500 hover:from-red-700 hover:to-red-600' :
                                'bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900'">
                            <span class="text-xs md:text-sm">??</span>
                            <span class="text-xs md:text-sm"
                                x-text="$store.inv.modoInventario ? 'Salir de inventario' : 'Hacer inventario'"></span>
                        </button>

                    </div>
                </div>
            </div>
        </div>

        <div x-show="$store.inv && $store.inv.modoInventario" x-cloak
            class="border border-red-200 dark:border-red-700 rounded-xl shadow-sm bg-white dark:bg-gray-900 hover:border-red-400 hover:shadow-md transition">
            <button @click="borrarTodosEscaneos()"
                class="w-full flex items-center justify-between px-4 py-2.5 text-left">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-red-600 text-white font-bold text-lg shadow-sm">???</span>
                    <div class="text-left">
                        <p class="md:text-base text-xs font-semibold text-red-700 dark:text-red-300">Borrar escaneos de
                            todas las
                            ubicaciones</p>
                        <p class="md:text-sm text-[10px] text-red-600 dark:text-red-400">Limpia escaneados y sospechosos
                            guardados en
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
                        class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-tr from-gray-900 to-gray-700 hover:from-gray-800 hover:to-gray-900 text-white text-sm sm:text-base">
                        @php
                            $totalMP = $ubicaciones->reduce(
                                fn($carry, $ubicacion) => $carry + $ubicacion->productos->count(),
                                0,
                            );
                        @endphp
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center justify-center h-8 w-8 sm:h-10 sm:w-10 rounded-full text-white font-bold text-sm sm:text-base"
                                :class="!($store.inv && $store.inv.modoInventario) ? 'bg-gray-600' : (
                                    estadoSectores['{{ $sector }}'] === 'ok' ? 'bg-green-600' :
                                    estadoSectores['{{ $sector }}'] === 'fabricando' ? 'bg-purple-600' :
                                    estadoSectores['{{ $sector }}'] === 'consumido' ? 'bg-blue-600' :
                                    estadoSectores['{{ $sector }}'] === 'ambar' ? 'bg-amber-500' :
                                    estadoSectores['{{ $sector }}'] === 'rojo' ? 'bg-red-600' :
                                    'bg-gray-600'
                                )">S{{ $sector }}</span>
                            <div class="flex flex-col gap-1 items-start">
                                <p class="text-base sm:text-lg font-semibold leading-tight">Sector {{ $sector }}
                                </p>
                                <p class="text-[11px] sm:text-xs text-white/80">Material en sector: {{ $totalMP }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs sm:text-sm text-white/70">{{ count($ubicaciones) }} ubicaciones</span>
                            <svg :class="openSectors['{{ $sector }}'] ? 'rotate-180' : ''"
                                class="w-4 h-4 sm:w-5 sm:h-5 transition-transform" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <div x-show="openSectors['{{ $sector }}']" x-collapse
                        class="flex flex-wrap justify-center gap-2 p-2 md:gap-4 md:p-4">
                        @foreach ($ubicaciones as $ubicacion)
                            <div class="bg-slate-50 dark:bg-gray-800 border rounded-xl p-2 md:p-4 flex flex-col gap-2 shadow-sm hover:shadow-md transition min-w-[282px] max-md:w-full"
                                x-data="{
                                    productos: @js($ubicacion->productos->pluck('codigo')->values()),
                                    paquetes: @js($ubicacion->paquetes->pluck('codigo')->values()),
                                    ubicId: '{{ $ubicacion->id }}',
                                    sector: '{{ $sector }}',
                                    estado: 'pendiente',
                                    init() {
                                        this.calcularEstado();
                                        window.addEventListener('inventario-actualizado', () => this.calcularEstado());
                                        window.addEventListener('producto-reasignado', (e) => {
                                            const { codigo, nuevaUbicacion } = e.detail || {};
                                            if (!codigo) return;
                                            if (nuevaUbicacion !== undefined && nuevaUbicacion !== null && nuevaUbicacion.toString() === this.ubicId.toString()) {
                                                if (!this.productos.includes(codigo)) this.productos.push(codigo);
                                            } else {
                                                this.productos = this.productos.filter(c => c !== codigo);
                                            }
                                            this.calcularEstado();
                                        });
                                    },
                                    calcularEstado() {
                                        const esperados = this.productos || [];
                                        const escaneados = JSON.parse(localStorage.getItem(`inv-${this.ubicId}`) || '[]');
                                        const sospechosos = JSON.parse(localStorage.getItem(`sospechosos-${this.ubicId}`) || '[]');
                                        const estadosGlobal = window.productosEstados || {};
                                        const maquinasGlobal = window.productosMaquinas || {};
                                        let estado = esperados.length === 0 ? 'ok' : (escaneados.length === esperados.length ? 'ok' : 'pendiente');

                                        const hayFabricando = esperados.some(c => estadosGlobal[c] === 'fabricando' && maquinasGlobal[c]);
                                        if (hayFabricando) estado = 'fabricando';

                                        for (const codigo of sospechosos) {
                                            const estadoProd = (window.productosEstados || {})[codigo];
                                            const ubicAsign = (window.productosAsignados || {})[codigo];
                                            if (estadoProd === 'consumido') {
                                                estado = 'consumido';
                                                break;
                                            }
                                            if (estadoProd === 'fabricando' && maquinasGlobal[codigo]) {
                                                estado = 'fabricando';
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
                                        estado === 'fabricando' ? 'border-purple-400' :
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
                                        class="text-xs px-2 py-1 rounded-full bg-gradient-to-tr from-gray-900 to-gray-700 text-white font-semibold"
                                        x-text="`Material: ${ (productos?.length || 0) + (paquetes?.length || 0) }`"></span>
                                </div>

                                <p class="text-[11px] text-gray-500 dark:text-gray-400 max-md:hidden"
                                    x-show="!(productos && productos.length) && !(paquetes && paquetes.length)">
                                    Ubicación sin material.
                                </p>

                                <div class="w-full mt-1 space-y-1" x-show="productos && productos.length">
                                    <template x-for="prod in productos" :key="prod">
                                        <div
                                            class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-400 rounded-md px-2 py-1 text-center">
                                            <p class="text-[11px] text-gray-800 dark:text-gray-100 font-semibold">
                                                <span x-text="prod"></span> |
                                                Ø <span
                                                    x-text="window.detallesProductos?.[prod]?.diametro ?? 'N/D'"></span>
                                                mm |
                                                Cosl: <span
                                                    x-text="window.detallesProductos?.[prod]?.colada ?? ''"></span>
                                            </p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>


        <div class="flex max-md:flex-col gap-3">
            <div
                class="w-full h-14 md:h-16 flex flex-col items-center justify-center border border-blue-200 dark:border-blue-700/70 rounded-xl shadow-sm bg-white/80 dark:bg-gray-900/80 hover:border-blue-500 hover:shadow-md transition">
                <button @click="openModal = true"
                    class="w-full flex items-center justify-between px-4 py-2.5 text-left">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-blue-600 text-white font-bold text-lg shadow-sm">+</span>
                        <div class="text-left">
                            <p class="md:text-base text-xs font-semibold text-gray-900 dark:text-white">Nueva ubicación
                            </p>
                            <p class="md:text-sm text-[10px] text-gray-600 dark:text-gray-300">Añade rápidamente otra
                                ubicación.</p>
                        </div>
                    </div>
                </button>
            </div>

            <div x-show="$store.inv && $store.inv.modoInventario" x-cloak
                class="w-full h-14 md:h-16 flex flex-col items-center justify-center border border-orange-200 dark:border-orange-700/70 rounded-xl shadow-sm bg-white/80 dark:bg-gray-900/80 hover:border-orange-500 hover:shadow-md transition">
                <button @click="abrirConsumos()"
                    class="w-full flex items-center justify-between px-4 py-2.5 text-left">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-orange-600 text-white font-bold text-lg shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor"
                                stroke-width="1.3" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M12.185 21.5c4.059 0 7.065-2.84 7.065-6.75 0-2.337-1.093-3.489-2.678-5.158l-.021-.023c-1.44-1.517-3.139-3.351-3.649-6.557a6.14 6.14 0 00-1.911 1.76c-.787 1.144-1.147 2.633-.216 4.495.603 1.205.777 2.74-.277 3.794-.657.657-1.762 1.1-2.956.586-.752-.324-1.353-.955-1.838-1.79-.567.706-.954 1.74-.954 2.893 0 3.847 3.288 6.75 7.435 6.75zm2.08-19.873c-.017-.345-.296-.625-.632-.543-2.337.575-6.605 4.042-4.2 8.854.474.946.392 1.675.004 2.062-.64.64-1.874.684-2.875-1.815-.131-.327-.498-.509-.803-.334-1.547.888-2.509 2.86-2.509 4.899 0 4.829 4.122 8.25 8.935 8.25 4.812 0 8.565-3.438 8.565-8.25 0-2.939-1.466-4.482-3.006-6.102-1.61-1.694-3.479-3.476-3.479-7.021z" />
                            </svg>
                        </span>
                        <div class="text-left">
                            <p class="md:text-base text-xs font-semibold text-gray-900 dark:text-white">Consumir
                                materiales pendientes.
                            </p>
                            <p class="md:text-sm text-[10px] text-gray-600 dark:text-gray-300">Consumir rápidamente los
                                materiales pendientes.</p>
                        </div>
                    </div>
                </button>
            </div>

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

                        <x-tabla.select name="sector" label="?? Sector" :options="collect(range(1, 20))
                            ->mapWithKeys(
                                fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                            )
                            ->toArray()"
                            placeholder="Ej. 01, 02, 03..." />

                        <x-tabla.select name="ubicacion" label="?? Ubicación" :options="collect(range(1, 100))
                            ->mapWithKeys(
                                fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)],
                            )
                            ->toArray()"
                            placeholder="Ej. 01 a 100" />

                        <x-tabla.input name="descripcion" label="?? Descripción"
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

        <!-- Modal consumo materiales -->
        <template x-teleport="body">
            <div x-show="modalConsumo" x-transition x-cloak
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur overflow-y-auto">
                <div @click.away="modalConsumo = false"
                    class="bg-white dark:bg-gray-900 w-full max-w-4xl p-6 rounded-xl shadow-2xl mx-4 my-4 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                            Materiales pendientes de la nave
                        </h2>
                        <button @click="modalConsumo = false"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            ?
                        </button>
                    </div>

                    <div
                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="max-h-[70vh] overflow-y-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-800/60 text-gray-700 dark:text-gray-200">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold">Ubicación</th>
                                        <th class="px-4 py-2 text-left font-semibold">Código</th>
                                        <th class="px-4 py-2 text-left font-semibold">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <template x-if="!listaConsumos.length">
                                        <tr>
                                            <td colspan="3"
                                                class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                                No hay materiales cargados en la nave.
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-for="item in listaConsumos" :key="`${item.ubicacion}-${item.codigo}`">
                                        <tr>
                                            <td class="px-4 py-2 font-semibold text-gray-800 dark:text-gray-100"
                                                x-text="item.ubicacion"></td>
                                            <td class="px-4 py-2 font-mono text-gray-700 dark:text-gray-200"
                                                x-text="item.codigo"></td>
                                            <td class="px-4 py-2">
                                                <span x-text="item.estado"
                                                    :class="item.estado === 'OK' ?
                                                        'bg-green-100 text-green-700' :
                                                        'bg-amber-100 text-amber-700'"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-center" x-data="sliderConsumo(() => consumirPendientes())"
                        @pointermove.window="onDrag($event)" @pointerup.window="stopDrag()"
                        @touchmove.window="onDrag($event)" @touchend.window="stopDrag()"
                        @resize.window="recalcMax()" x-init="init()">
                        <div x-ref="track"
                            class="relative w-full max-w-2xl h-12 rounded-full bg-gradient-to-r from-red-600 via-orange-500 to-amber-400 shadow-xl overflow-hidden select-none touch-none">
                            <div class="absolute inset-0 bg-white/10 blur-md opacity-60 pointer-events-none"></div>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <p class="text-sm md:text-base font-semibold text-white tracking-wide select-none">
                                    Deslizar para consumir
                                </p>
                            </div>

                            <div x-ref="handle"
                                class="absolute top-1 left-1 h-10 w-10 rounded-full bg-white text-orange-600 flex items-center justify-center shadow-md cursor-pointer transition-transform duration-150 active:scale-95"
                                :class="processing ? 'opacity-70 cursor-not-allowed' : ''"
                                :style="`transform: translateX(${handleX}px);`" @pointerdown.prevent="startDrag($event)"
                                @touchstart.prevent="startDrag($event)">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor"
                                    stroke-width="1.3" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M12.185 21.5c4.059 0 7.065-2.84 7.065-6.75 0-2.337-1.093-3.489-2.678-5.158l-.021-.023c-1.44-1.517-3.139-3.351-3.649-6.557a6.14 6.14 0 00-1.911 1.76c-.787 1.144-1.147 2.633-.216 4.495.603 1.205.777 2.74-.277 3.794-.657.657-1.762 1.1-2.956.586-.752-.324-1.353-.955-1.838-1.79-.567.706-.954 1.74-.954 2.893 0 3.847 3.288 6.75 7.435 6.75zm2.08-19.873c-.017-.345-.296-.625-.632-.543-2.337.575-6.605 4.042-4.2 8.854.474.946.392 1.675.004 2.062-.64.64-1.874.684-2.875-1.815-.131-.327-.498-.509-.803-.334-1.547.888-2.509 2.86-2.509 4.899 0 4.829 4.122 8.25 8.935 8.25 4.812 0 8.565-3.438 8.565-8.25 0-2.939-1.466-4.482-3.006-6.102-1.61-1.694-3.479-3.476-3.479-7.021z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal de Inventario -->
        <template x-teleport="body">
            <div x-show="$store.inv.modalInventario" x-transition x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm overflow-y-auto pt-0"
                @keydown.escape.window="!$store.inv.bloquearCierre && $store.inv.cerrarModalInventario()" wire:ignore>
                <template x-if="$store.inv.modalInventario">
                    <div class="relative bg-white dark:bg-gray-900 max-w-5xl sm:h-auto sm:max-h-[90vh] rounded-xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col min-h-0 m-4 h-[98vh] w-[98vw]"
                        @click.away="!$store.inv.bloquearCierre && $store.inv.cerrarModalInventario()"
                        x-data="inventarioUbicacion($store.inv.productosActuales, $store.inv.ubicacionActual)" :key="$store.inv.ubicacionActual">

                        <!-- Header del modal -->
                        <div
                            class="bg-gradient-to-tr from-gray-900 to-gray-700 text-white px-6 py-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold">
                                    Inventario - Ubicación
                                    <span x-text="$store.inv.codigoActual || nombreUbicacion || ''"></span>
                                    <span class="text-white/70 text-sm ml-2">ID: <span
                                            x-text="nombreUbicacion || ''"></span></span>
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
                                class="border border-gray-200 dark:border-gray-700 md:rounded-lg bg-white dark:bg-gray-800 shadow-sm flex flex-col">
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
                                                            x-text="window.detallesProductos[codigo]?.colada || ''">
                                                        </td>
                                                        <td class="px-3 py-2 capitalize text-gray-900 dark:text-gray-100"
                                                            x-text="window.detallesProductos[codigo]?.tipo || ''">
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                mm
                                                            </span>
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud || ''"></span>
                                                                m
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                                            <span x-show="productoEscaneado(codigo)"
                                                                class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">?
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
                                                            x-text="window.detallesProductos[codigo]?.colada || ''">
                                                        </td>
                                                        <td class="px-3 py-2 capitalize text-gray-900 dark:text-gray-100"
                                                            x-text="window.detallesProductos[codigo]?.tipo || ''">
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                mm
                                                            </span>
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud || ''"></span>
                                                                m
                                                            </span>
                                                        </td>
                                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                                            <span
                                                                class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">?
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
                                                                x-text="window.detallesProductos[codigo]?.tipo || ''">
                                                            </p>
                                                            <p class="text-gray-500 dark:text-gray-500">
                                                                <span
                                                                    x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                    Ø <span
                                                                        x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                    mm
                                                                </span>
                                                                <span
                                                                    x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                    Ø <span
                                                                        x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                    mm /
                                                                    <span
                                                                        x-text="window.detallesProductos[codigo]?.longitud || ''"></span>
                                                                    m
                                                                </span>
                                                            </p>
                                                            <p class="text-gray-500 dark:text-gray-500">~</p>
                                                            <p class="text-gray-500 dark:text-gray-500">
                                                                Col: <span
                                                                    x-text="window.detallesProductos[codigo]?.colada || ''"></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <span x-show="productoEscaneado(codigo)"
                                                        class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">?
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
                                                            x-text="window.detallesProductos[codigo]?.tipo || ''"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                                            Col: <span
                                                                x-text="window.detallesProductos[codigo]?.colada || ''"></span>
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo === 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                mm
                                                            </span>
                                                            <span
                                                                x-show="window.detallesProductos[codigo]?.tipo !== 'encarretado'">
                                                                Ø <span
                                                                    x-text="window.detallesProductos[codigo]?.diametro || ''"></span>
                                                                mm /
                                                                <span
                                                                    x-text="window.detallesProductos[codigo]?.longitud || ''"></span>
                                                                m
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <span
                                                        class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">?
                                                        OK</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Productos inesperados -->
                            <div
                                class="border border-red-200 dark:border-red-700 md:rounded-lg bg-white dark:bg-gray-800 shadow-sm flex flex-col">
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
                                                esFabricando: false,
                                                maquinaId: null,
                                                colada: null
                                            }" x-init="ubic = (asignados && Object.prototype.hasOwnProperty.call(asignados, codigo)) ? asignados[codigo] : null;
                                            hasId = (ubic !== null && ubic !== '' && ubic !== undefined);
                                            misma = (hasId && nombreUbicacion !== null && nombreUbicacion !== undefined && ubic.toString() === nombreUbicacion.toString());
                                            estado = (estados && Object.prototype.hasOwnProperty.call(estados, codigo)) ? (estados[codigo] ?? null) : null;
                                            esConsumido = (estado === 'consumido');
                                            maquinaId = (window.productosMaquinas && Object.prototype.hasOwnProperty.call(window.productosMaquinas, codigo)) ? window.productosMaquinas[codigo] : null;
                                            esFabricando = (estado === 'fabricando' && maquinaId);
                                            colada = (window.detallesProductos && Object.prototype.hasOwnProperty.call(window.detallesProductos, codigo)) ?
                                                (window.detallesProductos[codigo]?.colada ?? null) :
                                                null;">
                                            <div class="flex flex-col items-start min-w-0">
                                                <div>

                                                    <span class="inline-block h-2.5 w-2.5 rounded-full flex-shrink-0"
                                                        :class="esConsumido ? 'bg-blue-500/80' : (esFabricando ?
                                                            'bg-purple-500/80' : (hasId ? 'bg-amber-500/80' :
                                                                'bg-red-500/80'))"></span>
                                                    <span class="font-mono text-sm font-semibold"
                                                        :class="esConsumido ? 'text-blue-600' : (esFabricando ?
                                                            'text-purple-600' : (hasId ? 'text-amber-600' :
                                                                'text-red-800'))"
                                                        x-text="codigo"></span>
                                                </div>
                                                <div
                                                    class="text-[10px] md:text-xs text-gray-600 px-2 py-0.5 rounded flex gap-2">

                                                    <span>
                                                        <span x-show="esConsumido">Consumido</span>
                                                        <span x-show="esFabricando">Fabricando</span>
                                                        <span
                                                            x-show="!esConsumido && !esFabricando && hasId && !misma">Ubic:
                                                            <span x-text="ubic"></span></span>
                                                        <span x-show="!esConsumido && !esFabricando && !hasId">Sin
                                                            registrar</span>
                                                    </span>
                                                    <p>~</p>
                                                    <span x-show="colada">
                                                        Col: <span x-text="colada"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <button x-show="esConsumido"
                                                @click="confirmarRestablecerConsumido(codigo)"
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold flex-shrink-0">
                                                Restablecer aquí
                                            </button>
                                            <button x-show="esFabricando" @click="asignarDesdeFabricando(codigo)"
                                                class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-md text-xs font-semibold flex-shrink-0">
                                                Asignar aquí
                                            </button>
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
