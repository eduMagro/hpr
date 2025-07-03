<script>
    window.productosAsignados = @json(\App\Models\Producto::whereNotNull('ubicacion_id')->pluck('ubicacion_id', 'codigo'));
</script>

<script>
    const RUTA_ALERTA = @json(route('alertas.store'));

    /* ────── Alpine factory per location ────── */
    window.inventarioUbicacion = function(productosEsperados, nombreUbicacion) {

        /* key used to persist scans for this location */
        const claveLS = `inv-${nombreUbicacion}`;

        return {
            /* props ---------------------------------------------------------------- */
            productosEsperados,
            nombreUbicacion,

            escaneados: [], // códigos OK
            sospechosos: [], // códigos inesperados
            ultimoCodigo: null, // para el flash visual

            /* audio */
            audioOk: null,
            audioError: null,
            audioPedo: null,

            /* lifecycle ----------------------------------------------------------- */
            init() {
                /* 1️⃣ recuperar progreso previo */
                this.escaneados = JSON.parse(localStorage.getItem(claveLS) || '[]');
                this.sospechosos = JSON.parse(localStorage.getItem(`sospechosos-${nombreUbicacion}`) || '[]');
                this.$nextTick(() => this.$refs.inputQR?.focus());
                this.audioOk = document.getElementById('sonido-ok');
                this.audioError = document.getElementById('sonido-error');
                this.audioPedo = document.getElementById('sonido-pedo');
                this.audioEstaEnOtraUbi = document.getElementById('sonido-estaEnOtraUbi');
                this.audioNoTieneUbicacion = document.getElementById('sonido-noTieneUbicacion');

            },

            /* helpers ------------------------------------------------------------- */
            reproducirOk() {
                if (!this.audioOk) return;
                this.audioOk.currentTime = 0;
                this.audioOk.play().catch(() => {});
            },
            reproducirError() {
                if (!this.audioError) return;
                this.audioError.currentTime = 0;
                this.audioError.play().catch(() => {});
            },
            reproducirPedo() {
                if (!this.audioPedo) return;
                this.audioPedo.currentTime = 0;
                this.audioPedo.play().catch(() => {});
            },
            reproducirEstaEnOtraUbi() {
                if (!this.audioEstaEnOtraUbi) return;
                this.audioEstaEnOtraUbi.currentTime = 0;
                this.audioEstaEnOtraUbi.play().catch(() => {});
            },
            reproducirNoTieneUbicacion() {
                if (!this.audioNoTieneUbicacion) return;
                this.audioNoTieneUbicacion.currentTime = 0;
                this.audioNoTieneUbicacion.play().catch(() => {});
            },


            progreso() {
                /* 0–1 decimal for width % bar */
                return (this.escaneados.length / this.productosEsperados.length) * 100;
            },

            procesarQR(codigo) {
                codigo = (codigo || '').trim();

                // ❌ Si no empieza por MP, descartamos
                if (!codigo.toUpperCase().startsWith('MP')) {
                    this.reproducirPedo();
                    this.ultimoCodigo = codigo;
                    setTimeout(() => (this.ultimoCodigo = null), 1200);
                    return;
                }
                if (!codigo) return;

                const ubicacionAsignada = (window.productosAsignados || {})[codigo];

                if (this.productosEsperados.includes(codigo)) {
                    if (!this.escaneados.includes(codigo)) {
                        this.escaneados.push(codigo);
                        localStorage.setItem(claveLS, JSON.stringify(this.escaneados)); // 2️⃣ persist

                        this.reproducirOk();
                    }
                } else {
                    // Siempre añadimos a sospechosos si aún no estaba
                    if (!this.sospechosos.includes(codigo)) {
                        this.sospechosos.push(codigo);
                        localStorage.setItem(`sospechosos-${nombreUbicacion}`, JSON.stringify(this.sospechosos));
                    }

                    // Reproducimos sonido según caso
                    const ubicacionAsignada = (window.productosAsignados || {})[codigo];
                    console.log('➡️ Código escaneado:', codigo);
                    console.log('➡️ Ubicación asignada:', ubicacionAsignada, typeof ubicacionAsignada);
                    console.log('➡️ Esta ubicación actual:', this.nombreUbicacion, typeof this.nombreUbicacion);

                    if (ubicacionAsignada !== undefined && ubicacionAsignada.toString() !== this.nombreUbicacion
                        .toString()) {
                        console.log('🔁 Está en otra ubicación');
                        this.reproducirEstaEnOtraUbi();
                    } else if (ubicacionAsignada === undefined) {
                        console.log('🚫 No tiene ubicación asignada');
                        this.reproducirNoTieneUbicacion();
                    } else {
                        console.log('❌ Producto inesperado');
                        this.reproducirError();
                    }



                }

                /* 3️⃣ flash highlight */
                this.ultimoCodigo = codigo;
                setTimeout(() => (this.ultimoCodigo = null), 1200);
            },

            resetear() {
                Swal.fire({
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
                        localStorage.removeItem(`sospechosos-${this.nombreUbicacion}`);
                    }
                });
            },

            productoEscaneado(codigo) {
                return this.escaneados.includes(codigo);
            },

            reportarErrores() {
                const faltantes = this.productosEsperados.filter(c => !this.escaneados.includes(c));
                const inesperados = [...this.sospechosos];

                window.notificarProgramadorInventario({
                    ubicacion: this.nombreUbicacion,
                    faltantes,
                    inesperados
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

        Swal.fire({
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
                            'Accept': 'application/json', // 🔐 importante para que Laravel devuelva JSON
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

                        Swal.fire({
                            icon: 'success',
                            title: 'Reporte enviado',
                            text: 'Gracias por notificar. El equipo ha sido avisado.',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    })
                    .catch(error => {
                        Swal.fire({
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

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600 hover:text-gray-800">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6">
        @foreach ($ubicacionesPorSector as $sector => $ubicaciones)
            <div x-data="{ abierto: false }" class="mt-6 border rounded-xl shadow">

                <!-- Encabezado del sector con botón para expandir -->
                <button @click="abierto = !abierto"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-800 text-white font-semibold text-left text-lg hover:bg-gray-700">
                    <span>Sector {{ $sector }}</span>
                    <svg :class="abierto ? 'rotate-90' : ''" class="w-4 h-4 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Contenido del sector -->
                <div x-show="abierto" x-transition>

                    @foreach ($ubicaciones as $ubicacion)
                        <!-- Componente Alpine independiente por ubicación -->
                        <div x-data='inventarioUbicacion(@json($ubicacion->productos->pluck('codigo')), "{{ $ubicacion->id }}")'
                            :key="{{ json_encode($ubicacion->id) }}"
                            class="bg-white shadow rounded-2xl overflow-hidden mt-4">
                            <!-- Cabecera -->
                            <div
                                class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-gray-800 text-white px-4 py-3 gap-3">
                                <div class="text-sm sm:text-base">
                                    <span><strong>{{ $ubicacion->id }} -- {{ $ubicacion->codigo }} --
                                            {{ $ubicacion->descripcion }}</strong></span>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-gray-200 text-gray-900 ml-2">
                                        {{ count($ubicacion->productos) }} prod. esperados
                                    </span>
                                </div>
                                <!-- Input de escaneo para ESTA ubicación -->
                                <input type="text"
                                    class="w-full sm:w-64 border border-gray-300 rounded-md px-3 py-2 text-xs text-gray-900 placeholder-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow"
                                    placeholder="Escanea aquí…"
                                    x-on:keydown.enter.prevent="procesarQR($event.target.value); $event.target.value = ''"
                                    x-ref="inputQR" inputmode="none" autocomplete="off">
                            </div>
                            <div class="h-2 bg-gray-200">
                                <div class="h-full bg-blue-500 transition-all duration-300"
                                    :style="`width: ${progreso()}%`">
                                </div>
                                <div class="text-xs text-right px-4 py-1 text-gray-500">
                                    <span
                                        x-text="`${escaneados.length} / ${productosEsperados.length} escaneados`"></span>
                                </div>

                            </div>
                            <!-- Tabla de productos (visible >= sm) -->
                            <div class="hidden sm:block overflow-x-auto">
                                <table class="min-w-full text-xs md:text-sm divide-y divide-gray-200">
                                    <thead class="bg-gray-100 text-gray-800">
                                        <tr>
                                            <th class="px-2 py-1 text-center w-12">#</th>
                                            <th class="px-2 py-1 text-center">Código</th>
                                            <th class="px-2 py-1 text-center">Producto</th>
                                            <th class="px-2 py-1 text-center">Tipo</th>
                                            <th class="px-2 py-1 text-center">Ø / Long.</th>
                                            <th class="px-2 py-1 text-center">Peso</th>
                                            <th class="px-2 py-1 text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($ubicacion->productos as $idx => $producto)
                                            <tr
                                                :class="{
                                                    'bg-green-50': productoEscaneado('{{ $producto->codigo }}'),
                                                    'ring-2 ring-green-500 shadow-md scale-[1.01] transition-all duration-300 ease-out': ultimoCodigo === '{{ $producto->codigo }}'
                                                }">

                                                <td class="px-2 py-1 text-center">{{ $idx + 1 }}</td>
                                                <td class="px-2 py-1 text-xs text-center">{{ $producto->codigo }}</td>
                                                <td class="px-2 py-1 text-center">{{ $producto->nombre }}</td>
                                                <td class="px-2 py-1 capitalize text-center">{{ $producto->tipo }}</td>
                                                <td class="px-2 py-1 text-center">
                                                    @if ($producto->tipo === 'encarretado')
                                                        Ø {{ $producto->diametro }} mm
                                                    @else
                                                        {{ $producto->longitud }} m
                                                    @endif
                                                </td>
                                                <td class="px-2 py-1 text-center">
                                                    {{ number_format($producto->peso_inicial, 1, ',', '.') }}</td>
                                                <td class="px-2 py-1 text-center">
                                                    <span x-show="productoEscaneado('{{ $producto->codigo }}')"
                                                        class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">OK</span>
                                                    <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                                        class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-800">Pend.</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Vista mobile (cards) -->
                            <div class="sm:hidden divide-y divide-gray-100 text-xs">
                                @foreach ($ubicacion->productos as $producto)
                                    <div class="flex justify-between items-center py-2 px-3"
                                        :class="productoEscaneado('{{ $producto->codigo }}') ? 'bg-green-50' : ''">
                                        <div class="flex-1">
                                            <p class="font-semibold">{{ $producto->codigo }}</p>
                                            <p class="text-gray-600">{{ $producto->nombre }}</p>
                                        </div>
                                        <div class="text-right ml-2">
                                            <span x-cloak x-show="productoEscaneado('{{ $producto->codigo }}')"
                                                class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-semibold">OK</span>
                                            <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                                class="inline-block px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-[10px] font-semibold">Pend.</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Productos inesperados -->
                            <div x-cloak class="px-4 py-3" x-show="sospechosos.length">
                                <h3 class="text-sm font-semibold text-red-600 mb-1">Productos inesperados:</h3>
                                <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5">
                                    <template x-for="codigo in sospechosos" :key="codigo">
                                        <li>
                                            <span x-text="codigo"></span>
                                            <template x-if="window.productosAsignados[codigo]">
                                                <span class="text-xs text-gray-500">
                                                    → asignado a ubicacion con ID =
                                                    <strong x-text="window.productosAsignados[codigo]"></strong>
                                                </span>
                                            </template>
                                        </li>
                                    </template>


                                </ul>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end gap-3 px-4 py-4">
                                <button @click="resetear()"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-3 py-1.5 rounded-md text-xs shadow">
                                    Limpiar escaneos
                                </button>

                                <button @click="reportarErrores()"
                                    class="bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1.5 rounded-md text-xs shadow">
                                    Reportar errores
                                </button>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div
            class="mt-10 flex flex-col sm:flex-row items-stretch sm:items-center justify-start sm:justify-between gap-4">

            <button onclick="limpiarTodos()"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded shadow text-center">
                Limpiar TODOS los escaneos
            </button>

        </div>

    </div>

    <audio id="sonido-ok" src="{{ asset('sonidos/ok.mp3') }}" preload="auto"></audio>
    <audio id="sonido-error" src="{{ asset('sonidos/error.mp3') }}" preload="auto"></audio>
    <audio id="sonido-pedo" src="{{ asset('sonidos/pedo.mp3') }}" preload="auto"></audio>
    <audio id="sonido-estaEnOtraUbi" src="{{ asset('sonidos/estaEnOtraUbi.mp3') }}" preload="auto"></audio>
    <audio id="sonido-noTieneUbicacion" src="{{ asset('sonidos/noTieneUbicacion.mp3') }}" preload="auto"></audio>

    <script>
        window.limpiarTodos = function() {
            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar todos los escaneos?',
                text: 'Se borrarán todos los registros escaneados.',
                showCancelButton: true,
                confirmButtonText: 'Sí, borrar todo',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626'
            }).then(result => {
                if (result.isConfirmed) {
                    const clavesABorrar = [];
                    for (let i = 0; i < localStorage.length; i++) {
                        const key = localStorage.key(i);
                        if (key?.startsWith('inv-') || key?.startsWith('sospechosos-')) {
                            clavesABorrar.push(key);
                        }
                    }

                    clavesABorrar.forEach(key => localStorage.removeItem(key));

                    Swal.fire({
                        icon: 'success',
                        title: 'Escaneos eliminados',
                        text: `${clavesABorrar.length} registros fueron eliminados.`,
                        timer: 3000,
                        showConfirmButton: false
                    });

                    setTimeout(() => location.reload(), 800);
                }
            });
        };
    </script>
</x-app-layout>
