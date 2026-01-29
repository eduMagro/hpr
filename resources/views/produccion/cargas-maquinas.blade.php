<x-app-layout>
    <x-slot name="title">Cargas de Máquinas</x-slot>
    @php
        $menu = \App\Services\MenuService::getContextMenu('maquinas');
    @endphp
    <x-navigation.context-menu :items="$menu['items']" :colorBase="$menu['config']['colorBase']" :style="$menu['config']['style']" :mobileLabel="$menu['config']['mobileLabel']" />

    <x-page-header
        title="Cargas de Máquinas"
        subtitle="Análisis de carga de trabajo por turno y máquina"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'
    />

    <!-- Botones de navegación -->
    <div class="mb-6 border-b border-gray-200">
        <div class="flex space-x-2">
            <a href="{{ route('produccion.verMaquinas') }}" wire:navigate
                class="px-6 py-3 font-semibold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-2 border-transparent transition-colors">
                Producción/Máquinas
            </a>
            <a href="{{ route('produccion.cargasMaquinas') }}" wire:navigate
                class="px-6 py-3 font-semibold text-blue-600 border-b-2 border-blue-600 bg-blue-50 transition-colors">
                Cargas Máquinas
            </a>
            <a href="{{ route('planificacion.index') }}" wire:navigate
                class="px-6 py-3 font-semibold text-gray-600 hover:text-blue-600 hover:bg-gray-50 border-b-2 border-transparent transition-colors">
                Planificación
            </a>
        </div>
    </div>

    <div class="py-6">
        <div class="max-w-full mx-auto px-2 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Filtros de fecha y turno -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg flex flex-wrap items-center gap-3">
                    <label class="text-sm font-semibold">
                        Desde:
                        <input type="date" id="fechaInicio"
                            value="{{ $filtro_fecha_inicio ?? now()->subDays(7)->format('Y-m-d') }}"
                            class="border rounded text-sm px-2 py-1 ml-1">
                    </label>
                    <label class="text-sm font-semibold">
                        Hasta:
                        <input type="date" id="fechaFin" value="{{ $filtro_fecha_fin ?? now()->format('Y-m-d') }}"
                            class="border rounded text-sm px-2 py-1 ml-1">
                    </label>
                    <label class="text-sm font-semibold">
                        Turno:
                        <select id="turnoFiltro" class="border rounded text-sm px-2 py-1 ml-1">
                            <option value="">Todos</option>
                            <option value="mañana" {{ ($filtro_turno ?? '') === 'mañana' ? 'selected' : '' }}>Mañana
                            </option>
                            <option value="tarde" {{ ($filtro_turno ?? '') === 'tarde' ? 'selected' : '' }}>Tarde
                            </option>
                            <option value="noche" {{ ($filtro_turno ?? '') === 'noche' ? 'selected' : '' }}>Noche
                            </option>
                        </select>
                    </label>
                    <button id="filtrarFechas"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded text-sm transition-colors">
                        Aplicar filtros
                    </button>
                    <span id="rango-aplicado" class="text-xs text-gray-600 ml-2"></span>
                </div>

                <!-- Gráficos por máquina -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($maquinas as $maquina)
                        <div class="bg-white shadow rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-sm font-semibold">{{ $maquina->nombre }}</h3>
                                <select data-id="{{ $maquina->id }}" data-nombre="{{ $maquina->nombre }}"
                                    class="estado-maquina border rounded text-sm px-2 py-1">
                                    <option value="activa" {{ $maquina->estado === 'activa' ? 'selected' : '' }}>🟢
                                        Activa</option>
                                    <option value="averiada" {{ $maquina->estado === 'averiada' ? 'selected' : '' }}>🔴
                                        Averiada</option>
                                    <option value="mantenimiento"
                                        {{ $maquina->estado === 'mantenimiento' ? 'selected' : '' }}>🛠️ Mantenimiento
                                    </option>
                                    <option value="pausa" {{ $maquina->estado === 'pausa' ? 'selected' : '' }}>⏸️
                                        Pausa</option>
                                </select>
                            </div>
                            <div class="relative" style="height: 180px;">
                                <canvas id="grafico-maquina-{{ $maquina->id }}"></canvas>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts externos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        window.initCargasMaquinasPage = function() {
            // Protección contra doble inicialización
            if (document.body.dataset.cargasMaquinasPageInit === 'true') return;
            console.log('Inicializando Cargas Maquinas Page');

            // Datos desde el backend (inyectados por Blade en el momento de renderizar este script)
            const cargaTurnoResumen = @json($cargaTurnoResumen);
            const planDetallado = @json($planDetallado);
            const realDetallado = @json($realDetallado);

            // Variables de estado
            const charts = {}; // Almacena instancias de Chart.js para limpieza
            const $desde = document.getElementById('fechaInicio');
            const $hasta = document.getElementById('fechaFin');
            const $turno = document.getElementById('turnoFiltro');
            const $rango = document.getElementById('rango-aplicado');
            const btnFiltrar = document.getElementById('filtrarFechas');

            // --- Funciones de Utilidad ---

            const parseDate = (val) => {
                if (!val) return null;
                const d = new Date(val);
                return isNaN(d.getTime()) ? null : d;
            };

            const pintarTextoRango = () => {
                if (!$rango) return;
                const turnoTxt = $turno?.value ? $turno.options[$turno.selectedIndex].text : 'Todos';
                $rango.textContent =
                    `Mostrando datos desde ${$desde.value} hasta ${$hasta.value} · Turno: ${turnoTxt}`;
            };

            const aplicarFiltro = (ev) => {
                ev?.preventDefault?.();

                const desde = parseDate($desde.value);
                const hasta = parseDate($hasta.value);
                if (!desde || !hasta) {
                    if ($rango) $rango.textContent = 'Selecciona un rango de fechas válido.';
                    return;
                }
                hasta.setHours(23, 59, 59, 999);

                const turnoSel = ($turno?.value || '').toLowerCase();
                pintarTextoRango();

                // 📊 Calcular datos filtrados y encontrar máximo global
                const datosFiltrados = {};
                let nuevoMaximoGlobal = 0;

                Object.entries(planDetallado).forEach(([maquinaId, turnosPlan]) => {
                    const etiquetas = turnoSel ? [turnoSel] : ['mañana', 'tarde', 'noche'];

                    const planificado = etiquetas.map(t =>
                        (turnosPlan[t] || [])
                        .filter(e => {
                            const f = parseDate(e.fecha);
                            return f && f >= desde && f <= hasta;
                        })
                        .reduce((suma, e) => suma + (Number(e.peso) || 0), 0)
                    );

                    const turnosReal = realDetallado[maquinaId] || {};
                    const real = etiquetas.map(t =>
                        (turnosReal[t] || [])
                        .filter(e => {
                            const f = parseDate(e.fecha);
                            return f && f >= desde && f <= hasta;
                        })
                        .reduce((suma, e) => suma + (Number(e.peso) || 0), 0)
                    );

                    datosFiltrados[maquinaId] = {
                        etiquetas,
                        planificado,
                        real
                    };

                    // Actualizar máximo global
                    nuevoMaximoGlobal = Math.max(nuevoMaximoGlobal, ...planificado, ...real);
                });

                // Añadir margen del 10%
                nuevoMaximoGlobal = Math.ceil(nuevoMaximoGlobal * 1.1);

                // 🎯 Actualizar todas las gráficas con la misma escala
                Object.entries(datosFiltrados).forEach(([maquinaId, datos]) => {
                    if (charts[maquinaId]) {
                        charts[maquinaId].data.labels = datos.etiquetas.map(s => s.charAt(0).toUpperCase() +
                            s.slice(1));
                        charts[maquinaId].data.datasets[0].data = datos.planificado;
                        charts[maquinaId].data.datasets[1].data = datos.real;
                        charts[maquinaId].options.scales.y.max = nuevoMaximoGlobal; // 🎯 Escala uniforme
                        charts[maquinaId].update();
                    }
                });
            };

            async function handleEstadoChange(e) {
                const maquinaId = this.dataset.id;
                const nuevoEstado = this.value;

                try {
                    const res = await fetch(`/maquinas/${maquinaId}/cambiar-estado`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            estado: nuevoEstado
                        })
                    });

                    const data = await res.json();

                    if (data.success) {
                        const colores = {
                            activa: '🟢',
                            averiada: '🔴',
                            mantenimiento: '🛠️',
                            pausa: '⏸️'
                        };
                        // Usamos alert nativo como estaba, o Swal si estuviera disponible
                        alert(
                        `${colores[nuevoEstado]} Máquina "${this.dataset.nombre}" cambiada a: ${nuevoEstado}`);
                    } else {
                        alert('❌ Error al cambiar estado: ' + (data.message || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('❌ Error al cambiar estado de la máquina');
                }
            }


            // --- Inicialización de Gráficos ---

            // 📊 Calcular el máximo global de TODOS los datos para escala uniforme inicial
            let maximoGlobal = 0;
            if (cargaTurnoResumen) {
                Object.values(cargaTurnoResumen).forEach(turnos => {
                    Object.values(turnos).forEach(datos => {
                        maximoGlobal = Math.max(maximoGlobal, datos.planificado ?? 0, datos.real ?? 0);
                    });
                });
            }
            // Añadir un 10% de margen superior
            maximoGlobal = Math.ceil(maximoGlobal * 1.1);

            if (cargaTurnoResumen) {
                Object.entries(cargaTurnoResumen).forEach(([maquinaId, turnos]) => {
                    const canvas = document.getElementById(`grafico-maquina-${maquinaId}`);
                    if (!canvas) return;
                    const ctx = canvas.getContext('2d');

                    // Destruir chart previo si existiera (por si acaso quedaron artifacts, aunque el canvas debería ser nuevo)
                    // Chart.getChart(canvas)?.destroy();

                    const labels = ["Mañana", "Tarde", "Noche"];
                    const planificado = labels.map(t => (turnos[t.toLowerCase()]?.planificado ?? 0));
                    const real = labels.map(t => (turnos[t.toLowerCase()]?.real ?? 0));

                    charts[maquinaId] = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                    label: 'Planificado (kg)',
                                    data: planificado,
                                    backgroundColor: 'rgba(255, 159, 64, 0.6)',
                                    borderColor: 'rgba(255, 159, 64, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Real (kg)',
                                    data: real,
                                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                title: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: maximoGlobal,
                                    title: {
                                        display: true,
                                        text: 'Kg'
                                    }
                                }
                            }
                        }
                    });
                });
            }

            // Inicializar texto
            pintarTextoRango();


            // --- Event Listeners ---

            if (btnFiltrar) btnFiltrar.addEventListener('click', aplicarFiltro);

            const inputsFiltro = [$desde, $hasta, $turno];
            inputsFiltro.forEach(el => {
                if (el) {
                    el.addEventListener('change', aplicarFiltro);
                    el.addEventListener('input', aplicarFiltro);
                }
            });

            const selectoresEstado = document.querySelectorAll('.estado-maquina');
            selectoresEstado.forEach(select => {
                select.addEventListener('change', handleEstadoChange);
            });


            // --- Cleanup ---
            document.body.dataset.cargasMaquinasPageInit = 'true';

            const cleanup = () => {
                if (btnFiltrar) btnFiltrar.removeEventListener('click', aplicarFiltro);
                inputsFiltro.forEach(el => {
                    if (el) {
                        el.removeEventListener('change', aplicarFiltro);
                        el.removeEventListener('input', aplicarFiltro);
                    }
                });

                selectoresEstado.forEach(select => {
                    select.removeEventListener('change', handleEstadoChange);
                });

                // Destruir charts para liberar memoria
                Object.values(charts).forEach(chart => chart.destroy());

                document.body.dataset.cargasMaquinasPageInit = 'false';
            };

            document.addEventListener('livewire:navigating', cleanup, {
                once: true
            });
        };

        // Registrar en sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(window.initCargasMaquinasPage);

        // Listeners iniciales
        if (typeof Livewire !== 'undefined') {
            document.addEventListener('livewire:navigated', window.initCargasMaquinasPage);
        }
        document.addEventListener('DOMContentLoaded', window.initCargasMaquinasPage);

        // Ejecución inmediata si ya cargó
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            window.initCargasMaquinasPage();
        }
    </script>
</x-app-layout>
