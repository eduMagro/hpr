<x-app-layout>
    <x-slot name="title">Trazabilidad de Fabricación - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <i class="fas fa-search me-2"></i>
                Trazabilidad de Fabricación (Coladas)
            </h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                    <i class="fas fa-question-circle me-1"></i>
                    Ayuda
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Estadísticas Generales --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas del Mes: {{ $mes_actual }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-tags text-primary fa-2x mb-2"></i>
                                        <h3 class="mb-0">{{ $estadisticas['total_etiquetas'] ?? 0 }}</h3>
                                        <small class="text-muted">Etiquetas Fabricadas</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-cube text-success fa-2x mb-2"></i>
                                        <h3 class="mb-0">{{ $estadisticas['total_elementos'] ?? 0 }}</h3>
                                        <small class="text-muted">Elementos Totales</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-layer-group text-info fa-2x mb-2"></i>
                                        <h3 class="mb-0">{{ $estadisticas['coladas_utilizadas']->count() ?? 0 }}</h3>
                                        <small class="text-muted">Coladas Diferentes</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                                        <h3 class="mb-0">{{ $estadisticas['warnings'] ?? 0 }}</h3>
                                        <small class="text-muted">Warnings</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Distribución de asignaciones --}}
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Distribución de Asignaciones</h6>
                                    <div class="progress" style="height: 30px;">
                                        @php
                                            $total = ($estadisticas['asignaciones_simples'] ?? 0) +
                                                    ($estadisticas['asignaciones_dobles'] ?? 0) +
                                                    ($estadisticas['asignaciones_triples'] ?? 0);
                                            $pctSimple = $total > 0 ? (($estadisticas['asignaciones_simples'] ?? 0) / $total) * 100 : 0;
                                            $pctDoble = $total > 0 ? (($estadisticas['asignaciones_dobles'] ?? 0) / $total) * 100 : 0;
                                            $pctTriple = $total > 0 ? (($estadisticas['asignaciones_triples'] ?? 0) / $total) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar bg-success" style="width: {{ $pctSimple }}%">
                                            Simple: {{ $estadisticas['asignaciones_simples'] ?? 0 }} ({{ number_format($pctSimple, 1) }}%)
                                        </div>
                                        <div class="progress-bar bg-warning" style="width: {{ $pctDoble }}%">
                                            Doble: {{ $estadisticas['asignaciones_dobles'] ?? 0 }} ({{ number_format($pctDoble, 1) }}%)
                                        </div>
                                        <div class="progress-bar bg-danger" style="width: {{ $pctTriple }}%">
                                            Triple: {{ $estadisticas['asignaciones_triples'] ?? 0 }} ({{ number_format($pctTriple, 1) }}%)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Herramientas de Búsqueda --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-tag me-2"></i>Buscar por Etiqueta</h6>
                        </div>
                        <div class="card-body">
                            <form id="form-buscar-etiqueta" onsubmit="buscarPorEtiqueta(event)">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="input-etiqueta-id"
                                           placeholder="ID de Etiqueta" required>
                                    <select class="form-select" id="select-mes-etiqueta" style="max-width: 150px;">
                                        @foreach($meses as $mes)
                                            <option value="{{ $mes }}" {{ $mes === $mes_actual ? 'selected' : '' }}>
                                                {{ $mes }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-search me-1"></i>Buscar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-box me-2"></i>Buscar por Colada</h6>
                        </div>
                        <div class="card-body">
                            <form id="form-buscar-colada" onsubmit="buscarPorColada(event)">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="input-colada"
                                           placeholder="Número de Colada" required>
                                    <select class="form-select" id="select-mes-colada" style="max-width: 150px;">
                                        @foreach($meses as $mes)
                                            <option value="{{ $mes }}" {{ $mes === $mes_actual ? 'selected' : '' }}>
                                                {{ $mes }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-search me-1"></i>Buscar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resultados de Búsqueda por Colada --}}
            <div id="resultados-colada" class="row mb-4" style="display: none;">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Elementos con Colada: <strong id="colada-buscada">--</strong>
                            </h6>
                            <button class="btn btn-sm btn-light" onclick="cerrarResultadosColada()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="tabla-resultados-colada"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coladas más utilizadas --}}
            @if(isset($estadisticas['coladas_utilizadas']) && $estadisticas['coladas_utilizadas']->count() > 0)
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Coladas Utilizadas Este Mes</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($estadisticas['coladas_utilizadas'] as $colada)
                                    <span class="badge bg-primary p-2 cursor-pointer"
                                          onclick="buscarColadaDirecta('{{ $colada }}')">
                                        <i class="fas fa-box me-1"></i>{{ $colada }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Incluir el modal de detalles --}}
    <x-fabricacion.modal-detalles />

    {{-- Modal de Ayuda --}}
    <div class="modal fade" id="modalAyuda" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-question-circle me-2"></i>Ayuda - Trazabilidad</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6><i class="fas fa-info-circle me-2"></i>¿Qué es la Trazabilidad de Coladas?</h6>
                    <p>Este sistema permite rastrear qué productos (con sus coladas) se utilizaron para fabricar cada elemento, proporcionando trazabilidad completa del material usado.</p>

                    <h6 class="mt-4"><i class="fas fa-search me-2"></i>Buscar por Etiqueta</h6>
                    <p>Ingresa el ID de una etiqueta para ver todos los detalles de su fabricación:</p>
                    <ul>
                        <li>Qué elementos se fabricaron</li>
                        <li>Qué productos (coladas) se usaron para cada elemento</li>
                        <li>Cuánto peso se consumió de cada producto</li>
                        <li>Estadísticas de asignación (simple/doble/triple)</li>
                    </ul>

                    <h6 class="mt-4"><i class="fas fa-box me-2"></i>Buscar por Colada</h6>
                    <p>Ingresa un número de colada para ver todos los elementos que se fabricaron con ese material.</p>

                    <h6 class="mt-4"><i class="fas fa-exclamation-triangle me-2"></i>Tipos de Asignación</h6>
                    <ul>
                        <li><span class="badge bg-success">Simple</span> - Un solo producto cubrió todo el peso del elemento</li>
                        <li><span class="badge bg-warning">Doble</span> - Se necesitaron 2 productos (stock fragmentado)</li>
                        <li><span class="badge bg-danger">Triple</span> - Se necesitaron 3 productos (máxima fragmentación)</li>
                    </ul>

                    <h6 class="mt-4"><i class="fas fa-calendar me-2"></i>Selector de Mes</h6>
                    <p>Los logs se organizan por mes. Selecciona el mes que deseas consultar en los selectores de búsqueda.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    /**
     * Buscar detalles de una etiqueta
     */
    function buscarPorEtiqueta(event) {
        event.preventDefault();
        const etiquetaId = document.getElementById('input-etiqueta-id').value;
        const mes = document.getElementById('select-mes-etiqueta').value;

        if (etiquetaId) {
            mostrarDetallesFabricacion(etiquetaId, mes);
        }
    }

    /**
     * Buscar elementos por colada
     */
    function buscarPorColada(event) {
        event.preventDefault();
        const colada = document.getElementById('input-colada').value;
        const mes = document.getElementById('select-mes-colada').value;

        if (!colada) return;

        // Mostrar loading
        document.getElementById('resultados-colada').style.display = 'block';
        document.getElementById('colada-buscada').textContent = colada;
        document.getElementById('tabla-resultados-colada').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Buscando...</p></div>';

        // Hacer petición
        const url = new URL('{{ route("api.fabricacion.buscar.colada") }}', window.location.origin);
        url.searchParams.append('colada', colada);
        url.searchParams.append('month', mes);

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error en la búsqueda');
                }

                renderResultadosColada(data.data.elementos);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('tabla-resultados-colada').innerHTML =
                    `<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>${error.message}</div>`;
            });
    }

    /**
     * Buscar colada directamente (desde badges)
     */
    function buscarColadaDirecta(colada) {
        document.getElementById('input-colada').value = colada;
        document.getElementById('form-buscar-colada').dispatchEvent(new Event('submit'));
    }

    /**
     * Renderizar resultados de búsqueda por colada
     */
    function renderResultadosColada(elementos) {
        const container = document.getElementById('tabla-resultados-colada');

        if (!elementos || elementos.length === 0) {
            container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No se encontraron elementos con esta colada</div>';
            return;
        }

        let html = `<p class="mb-3"><strong>${elementos.length}</strong> elemento(s) encontrado(s)</p>`;
        html += '<div class="table-responsive"><table class="table table-hover">';
        html += '<thead class="table-light"><tr>';
        html += '<th>Etiqueta</th>';
        html += '<th>Elemento</th>';
        html += '<th>Diámetro</th>';
        html += '<th>Peso</th>';
        html += '<th>Fecha</th>';
        html += '<th>Acción</th>';
        html += '</tr></thead><tbody>';

        elementos.forEach(item => {
            html += '<tr>';
            html += `<td>#${item.etiqueta_id}</td>`;
            html += `<td>#${item.elemento.elemento_id}</td>`;
            html += `<td>Ø${item.elemento.diametro}mm</td>`;
            html += `<td>${item.elemento.peso.toFixed(2)} kg</td>`;
            html += `<td>${item.timestamp}</td>`;
            html += `<td><button class="btn btn-sm btn-primary" onclick="mostrarDetallesFabricacion(${item.etiqueta_id})">`;
            html += `<i class="fas fa-eye me-1"></i>Ver Detalles</button></td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    /**
     * Cerrar resultados de búsqueda por colada
     */
    function cerrarResultadosColada() {
        document.getElementById('resultados-colada').style.display = 'none';
    }
    </script>
    @endpush

    <style>
    .cursor-pointer {
        cursor: pointer;
    }

    .cursor-pointer:hover {
        opacity: 0.8;
        transform: scale(1.05);
        transition: all 0.2s;
    }

    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }

    .progress {
        font-size: 0.85rem;
        font-weight: 600;
    }
    </style>
</x-app-layout>
