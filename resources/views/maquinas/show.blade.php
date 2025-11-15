<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <strong>{{ $maquina->nombre }}</strong>,
                {{ $usuario1->name }}
                @if ($usuario2)
                    y {{ $usuario2->name }}
                @endif
            </h2>

            <div class="flex flex-wrap items-center gap-4">
                @if ($maquina->tipo !== 'grua' && $maquina->tipo !== 'dobladora_manual' && $maquina->tipo !== 'cortadora_manual')
                    {{-- Selectores de posiciones de planillas --}}
                    <div class="flex items-center gap-2 bg-white rounded-md px-3 py-1.5 border border-gray-300 shadow-sm">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">
                            📋 Planillas:
                        </label>

                        <select id="posicion_1" name="posicion_1"
                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                            onchange="cambiarPosicionesPlanillas()">
                            <option value="">-- Pos. 1 --</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}" {{ request('posicion_1') == $pos ? 'selected' : '' }}>
                                    Pos. {{ $pos }}
                                </option>
                            @endforeach
                        </select>

                        <span class="text-gray-400">+</span>

                        <select id="posicion_2" name="posicion_2"
                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                            onchange="cambiarPosicionesPlanillas()">
                            <option value="">-- Pos. 2 --</option>
                            @foreach ($posicionesDisponibles as $pos)
                                <option value="{{ $pos }}" {{ request('posicion_2') == $pos ? 'selected' : '' }}>
                                    Pos. {{ $pos }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <script>
                        function cambiarPosicionesPlanillas() {
                            const pos1 = document.getElementById('posicion_1').value;
                            const pos2 = document.getElementById('posicion_2').value;

                            // Construir URL con parámetros
                            const params = new URLSearchParams(window.location.search);
                            if (pos1) {
                                params.set('posicion_1', pos1);
                            } else {
                                params.delete('posicion_1');
                            }
                            if (pos2) {
                                params.set('posicion_2', pos2);
                            } else {
                                params.delete('posicion_2');
                            }

                            // Actualizar URL sin recargar
                            const newUrl = window.location.pathname + '?' + params.toString();
                            window.history.pushState({}, '', newUrl);

                            // Recargar solo el contenido de planillas
                            fetch(newUrl, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.text())
                            .then(html => {
                                // Crear un documento temporal para parsear el HTML
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');

                                // Obtener el nuevo grid de máquina
                                const nuevoGrid = doc.getElementById('grid-maquina');
                                if (nuevoGrid) {
                                    const gridActual = document.getElementById('grid-maquina');
                                    if (gridActual) {
                                        // Ocultar el grid actual
                                        gridActual.style.opacity = '0';
                                        gridActual.style.visibility = 'hidden';

                                        // Después de la animación, reemplazar el contenido
                                        setTimeout(() => {
                                            gridActual.innerHTML = nuevoGrid.innerHTML;
                                            console.log('📦 Contenido reemplazado via AJAX');

                                            // Actualizar variables globales
                                            const scripts = doc.querySelectorAll('script');
                                            console.log('📜 Scripts encontrados:', scripts.length);

                                            scripts.forEach(script => {
                                                const content = script.textContent || script.innerText;
                                                if (content.includes('window.elementosAgrupadosScript') ||
                                                    content.includes('window.etiquetasData') ||
                                                    content.includes('window.pesosElementos') ||
                                                    content.includes('window.SUGERENCIAS')) {
                                                    console.log('⚙️ Ejecutando script con variables globales');
                                                    eval(content);
                                                }
                                            });

                                            console.log('📊 elementosAgrupadosScript:', window.elementosAgrupadosScript);

                                            // Actualizar data sources si existe la función
                                            if (window.setDataSources && window.elementosAgrupadosScript) {
                                                console.log('🔄 Actualizando data sources');
                                                window.setDataSources({
                                                    sugerencias: window.SUGERENCIAS || {},
                                                    elementosAgrupados: window.elementosAgrupadosScript || []
                                                });
                                            }

                                            // Re-renderizar SVGs
                                            if (window.elementosAgrupadosScript && window.renderizarGrupoSVG) {
                                                console.log('🎨 Iniciando renderizado de', window.elementosAgrupadosScript.length, 'grupos');
                                                window.elementosAgrupadosScript.forEach((grupo, gidx) => {
                                                    console.log('🖼️ Renderizando grupo', gidx, grupo);
                                                    window.renderizarGrupoSVG(grupo, gidx);
                                                });
                                                console.log('✅ Renderizado completado');
                                            } else {
                                                console.error('❌ No se puede renderizar:', {
                                                    elementosAgrupadosScript: !!window.elementosAgrupadosScript,
                                                    renderizarGrupoSVG: !!window.renderizarGrupoSVG
                                                });
                                            }

                                            // Mostrar el grid con animación
                                            requestAnimationFrame(() => {
                                                requestAnimationFrame(() => {
                                                    setTimeout(() => {
                                                        gridActual.style.opacity = '1';
                                                        gridActual.style.visibility = 'visible';

                                                        // Mostrar etiquetas
                                                        document.querySelectorAll('.proceso').forEach(el => {
                                                            el.style.opacity = '1';
                                                        });
                                                    }, 150);
                                                });
                                            });
                                        }, 300);
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error al cambiar planillas:', error);
                                // Si falla, hacer refresh normal
                                window.location.href = newUrl;
                            });
                        }
                    </script>

                    {{-- Controles de vista para máquinas tipo normal --}}
                    <div class="flex items-center gap-2" x-data="{
                        showLeft: JSON.parse(localStorage.getItem('showLeft') ?? 'false'),
                        showRight: JSON.parse(localStorage.getItem('showRight') ?? 'true'),
                        toggleLeft() {
                            this.showLeft = !this.showLeft;
                            localStorage.setItem('showLeft', JSON.stringify(this.showLeft));
                            window.dispatchEvent(new CustomEvent('toggleLeft'));
                        },
                        solo() {
                            this.showLeft = false;
                            this.showRight = false;
                            localStorage.setItem('showLeft', 'false');
                            localStorage.setItem('showRight', 'false');
                            window.dispatchEvent(new CustomEvent('solo'));
                        },
                        toggleRight() {
                            this.showRight = !this.showRight;
                            localStorage.setItem('showRight', JSON.stringify(this.showRight));
                            window.dispatchEvent(new CustomEvent('toggleRight'));
                        }
                    }">
                        <button @click="toggleLeft()"
                            class="px-3 py-1.5 rounded-md text-sm font-medium border transition-all duration-200"
                            :class="showLeft ? 'bg-white border-gray-300 text-gray-700 shadow-sm' : 'bg-blue-500 border-blue-600 text-white hover:bg-blue-600'"
                            title="Mostrar/Ocultar materia prima">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                                </svg>
                                <span x-text="showLeft ? 'Ocultar' : 'Materia'"></span>
                            </span>
                        </button>

                        <button @click="solo()"
                            class="px-3 py-1.5 rounded-md text-sm font-medium bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-200 shadow-sm"
                            title="Ver solo planillas">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                Solo Planillas
                            </span>
                        </button>

                        <button @click="toggleRight()"
                            class="px-3 py-1.5 rounded-md text-sm font-medium border transition-all duration-200"
                            :class="showRight ? 'bg-white border-gray-300 text-gray-700 shadow-sm' : 'bg-blue-500 border-blue-600 text-white hover:bg-blue-600'"
                            title="Mostrar/Ocultar paquetes">
                            <span class="flex items-center gap-1">
                                <span x-text="showRight ? 'Ocultar' : 'Paquetes'"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </button>
                    </div>

                    {{-- Botón Exportar BVBs para MSR20 --}}
                    @if (strtoupper($maquina->nombre) === 'MSR20')
                        <a href="{{ route('maquinas.exportar-bvbs', $maquina->id) }}"
                            class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg text-sm font-medium shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
                            title="Exportar BVBs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar BVBs
                        </a>
                    @endif
                @endif

                @if ($turnoHoy)
                    <form method="POST" action="{{ route('turno.cambiarMaquina') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="asignacion_id" value="{{ $turnoHoy->id }}">

                        <select name="nueva_maquina_id" class="border rounded px-2 py-1 text-sm">
                            @foreach ($maquinas as $m)
                                <option value="{{ $m->id }}" {{ $m->id == $turnoHoy->maquina_id ? 'selected' : '' }}>
                                    {{ $m->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                            Cambiar máquina
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="w-full sm:px-4">
        <!-- Grid principal -->
        <div class="w-full">
            @if ($maquina->tipo === 'grua')
                <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                    {{-- <x-maquinas.tipo.tipo-grua :movimientosPendientes="$movimientosPendientes" :ubicaciones="$ubicaciones" :paquetes="$paquetes" /> --}}
                    <x-maquinas.tipo.tipo-grua :maquina="$maquina" :movimientos-pendientes="$movimientosPendientes" :movimientos-completados="$movimientosCompletados"
                        :ubicaciones-disponibles-por-producto-base="$ubicacionesDisponiblesPorProductoBase" />
                    @include('components.maquinas.modales.grua.modales-grua')
                @elseif ($maquina->tipo === 'dobladora_manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-dobladora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @elseif ($maquina->tipo === 'cortadora_manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-cortadora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @else
                    <x-maquinas.tipo.tipo-normal :maquina="$maquina" :maquinas="$maquinas" :elementos-agrupados="$elementosAgrupados"
                        :productos-base-compatibles="$productosBaseCompatibles" :producto-base-solicitados="$productoBaseSolicitados" :planillas-activas="$planillasActivas" :elementos-por-planilla="$elementosPorPlanilla" :es-barra="$esBarra"
                        :longitudes-por-diametro="$longitudesPorDiametro" :diametro-por-etiqueta="$diametroPorEtiqueta" :elementos-agrupados-script="$elementosAgrupadosScript" :posiciones-disponibles="$posicionesDisponibles" :posicion1="$posicion1"
                        :posicion2="$posicion2" />

                    @include('components.maquinas.modales.normal.modales-normal')
            @endif

        </div>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

        <script>
            window.SUGERENCIAS = @json($sugerenciasPorElemento ?? []);
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
            window.rutaDividirElemento = "{{ route('elementos.dividir') }}";
            window.etiquetasData = @json($etiquetasData);
            window.pesosElementos = @json($pesosElementos);
            window.maquinaId = @json($maquina->id);
            window.tipoMaquina = @json($maquina->tipo_material); // 👈 Añadido
            window.ubicacionId = @json(optional($ubicacion)->id);
            console.log('etiquetasData', window.etiquetasData);
        </script>

        <!-- ✅ Vite: Bundle de máquinas -->
        @vite(['resources/js/maquinaJS/maquina-bundle.js'])
        <script src="{{ asset('js/maquinaJS/sl28/cortes.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
        {{-- Al final del archivo Blade --}}

        <script>
            // Bloquea el menú contextual solo dentro de .proceso (tu tarjeta de etiqueta)
            document.addEventListener('contextmenu', function(e) {
                if (e.target.closest('.proceso')) {
                    e.preventDefault();
                }
            }, {
                capture: true
            });

            // Validación de posiciones de planillas en el header
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('form-posiciones-planillas-header');
                if (!form) return;

                const select1 = form.querySelector('select[name="posicion_1"]');
                const select2 = form.querySelector('select[name="posicion_2"]');
                if (!select1 || !select2) return;

                function validar() {
                    const pos1 = select1.value;
                    const pos2 = select2.value;

                    if (pos1 && pos2 && pos1 === pos2) {
                        select2.value = '';
                        Swal.fire({
                            icon: 'warning',
                            title: 'Posiciones duplicadas',
                            text: 'No puedes seleccionar la misma posición dos veces',
                            confirmButtonColor: '#3085d6',
                        });
                        return false;
                    }
                    return true;
                }

                select1.addEventListener('change', validar);
                select2.addEventListener('change', validar);
                form.addEventListener('submit', (e) => !validar() && e.preventDefault());
            });
        </script>

</x-app-layout>
