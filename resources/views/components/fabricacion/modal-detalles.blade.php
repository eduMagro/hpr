{{-- Modal para mostrar detalles de fabricación de una etiqueta --}}
<div id="modalDetallesFabricacion" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalDetallesFabricacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetallesFabricacionLabel">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalle de Fabricación - Etiqueta #<span id="etiqueta-id-display">--</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Loading spinner --}}
                <div id="loading-detalles" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando detalles de fabricación...</p>
                </div>

                {{-- Contenido de detalles --}}
                <div id="contenido-detalles" style="display: none;">
                    {{-- Información General --}}
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-info me-2"></i>Información General</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Etiqueta:</strong> <span id="info-etiqueta-id">--</span></p>
                                    <p><strong>Máquina:</strong> <span id="info-maquina">--</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Fecha:</strong> <span id="info-fecha">--</span></p>
                                    <p><strong>Elementos fabricados:</strong> <span id="info-total-elementos">--</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Asignación de Coladas --}}
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Asignación de Coladas</h6>
                        </div>
                        <div class="card-body">
                            <div id="asignacion-coladas-content">
                                {{-- Se llenará con JavaScript --}}
                            </div>
                        </div>
                    </div>

                    {{-- Consumo de Stock --}}
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Consumo de Stock por Diámetro</h6>
                        </div>
                        <div class="card-body">
                            <div id="consumo-stock-content">
                                {{-- Se llenará con JavaScript --}}
                            </div>
                        </div>
                    </div>

                    {{-- Warnings (si existen) --}}
                    <div id="warnings-container" class="card mb-3" style="display: none;">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Advertencias</h6>
                        </div>
                        <div class="card-body">
                            <div id="warnings-content"></div>
                        </div>
                    </div>

                    {{-- Estadísticas de Asignación --}}
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Estadísticas de Asignación</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-box text-success fa-2x mb-2"></i>
                                        <h4 class="mb-0" id="stats-simple">0</h4>
                                        <small class="text-muted">Asignaciones Simples (1 producto)</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-boxes text-warning fa-2x mb-2"></i>
                                        <h4 class="mb-0" id="stats-doble">0</h4>
                                        <small class="text-muted">Asignaciones Dobles (2 productos)</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="fas fa-layer-group text-danger fa-2x mb-2"></i>
                                        <h4 class="mb-0" id="stats-triple">0</h4>
                                        <small class="text-muted">Asignaciones Triples (3 productos)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Error message --}}
                <div id="error-detalles" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="error-message">Error al cargar los detalles</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Carga y muestra los detalles de fabricación de una etiqueta
 */
window.mostrarDetallesFabricacion = function(etiquetaId, month = null) {
    console.log('Abriendo modal para etiqueta:', etiquetaId);

    // Verificar que el modal existe
    const modalElement = document.getElementById('modalDetallesFabricacion');
    if (!modalElement) {
        console.error('Modal no encontrado en el DOM');
        alert('Error: Modal no encontrado');
        return;
    }

    // Abrir modal usando jQuery (compatible con Bootstrap 4 y 5)
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#modalDetallesFabricacion').modal('show');
    } else if (typeof bootstrap !== 'undefined') {
        // Fallback a Bootstrap 5
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.error('Ni jQuery ni Bootstrap están disponibles');
        alert('Error: Sistema de modales no disponible');
        return;
    }

    // Actualizar ID en el título
    document.getElementById('etiqueta-id-display').textContent = etiquetaId;

    // Mostrar loading, ocultar contenido y errores
    document.getElementById('loading-detalles').style.display = 'block';
    document.getElementById('contenido-detalles').style.display = 'none';
    document.getElementById('error-detalles').style.display = 'none';

    // Preparar URL con parámetros
    const url = new URL('{{ route("api.fabricacion.detalles") }}', window.location.origin);
    url.searchParams.append('etiqueta_id', etiquetaId);
    if (month) {
        url.searchParams.append('month', month);
    }

    // Hacer petición AJAX
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar detalles');
            }

            // Ocultar loading, mostrar contenido
            document.getElementById('loading-detalles').style.display = 'none';
            document.getElementById('contenido-detalles').style.display = 'block';

            // Rellenar información general
            const asignacion = data.data.asignacion_coladas;
            if (asignacion) {
                document.getElementById('info-etiqueta-id').textContent = asignacion.etiqueta_id;
                document.getElementById('info-maquina').textContent = asignacion.maquina || 'N/A';
                document.getElementById('info-fecha').textContent = asignacion.timestamp;
                document.getElementById('info-total-elementos').textContent = asignacion.total_elementos;

                // Estadísticas
                document.getElementById('stats-simple').textContent = asignacion.stats.simple;
                document.getElementById('stats-doble').textContent = asignacion.stats.doble;
                document.getElementById('stats-triple').textContent = asignacion.stats.triple;

                // Asignación de coladas
                renderAsignacionColadas(asignacion.elementos);

                // Warnings
                if (asignacion.warnings) {
                    document.getElementById('warnings-container').style.display = 'block';
                    document.getElementById('warnings-content').innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stock fragmentado detectado</div>';
                } else {
                    document.getElementById('warnings-container').style.display = 'none';
                }
            }

            // Consumo de stock
            const consumo = data.data.consumo_stock;
            if (consumo) {
                renderConsumoStock(consumo.diametros);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('loading-detalles').style.display = 'none';
            document.getElementById('error-detalles').style.display = 'block';
            document.getElementById('error-message').textContent = error.message;
        });
}

/**
 * Renderiza la asignación de coladas
 */
window.renderAsignacionColadas = function(elementos) {
    const container = document.getElementById('asignacion-coladas-content');
    if (!elementos || elementos.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay datos de asignación disponibles</p>';
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm table-hover">';
    html += '<thead class="table-light"><tr>';
    html += '<th>Elemento</th>';
    html += '<th>Diámetro</th>';
    html += '<th>Peso</th>';
    html += '<th>Tipo</th>';
    html += '<th>Coladas Asignadas</th>';
    html += '</tr></thead><tbody>';

    elementos.forEach(elem => {
        const tipoBadge = {
            'simple': '<span class="badge bg-success">Simple</span>',
            'doble': '<span class="badge bg-warning">Doble</span>',
            'triple': '<span class="badge bg-danger">Triple</span>'
        }[elem.tipo_asignacion] || '<span class="badge bg-secondary">N/A</span>';

        html += '<tr>';
        html += `<td><strong>#${elem.elemento_id}</strong></td>`;
        html += `<td>Ø${elem.diametro}mm</td>`;
        html += `<td>${elem.peso.toFixed(2)} kg</td>`;
        html += `<td>${tipoBadge}</td>`;
        html += '<td>';

        // Listar coladas
        if (elem.coladas && elem.coladas.length > 0) {
            html += '<ul class="list-unstyled mb-0">';
            elem.coladas.forEach((colada, index) => {
                html += `<li class="mb-1">`;
                html += `<span class="badge bg-primary me-1">${index + 1}</span> `;
                html += `P${colada.producto_id} - Colada: <strong>${colada.n_colada}</strong> `;
                html += `<span class="text-muted">(${colada.peso_consumido.toFixed(2)} kg)</span>`;
                html += `</li>`;
            });
            html += '</ul>';
        } else {
            html += '<span class="text-muted">Sin coladas</span>';
        }

        html += '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * Renderiza el consumo de stock
 */
window.renderConsumoStock = function(diametros) {
    const container = document.getElementById('consumo-stock-content');
    if (!diametros || Object.keys(diametros).length === 0) {
        container.innerHTML = '<p class="text-muted">No hay datos de consumo disponibles</p>';
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm">';
    html += '<thead class="table-light"><tr>';
    html += '<th>Diámetro</th>';
    html += '<th>Total Consumido</th>';
    html += '<th>Productos Utilizados</th>';
    html += '<th>Detalle</th>';
    html += '</tr></thead><tbody>';

    for (const [diametro, info] of Object.entries(diametros)) {
        html += '<tr>';
        html += `<td><strong>Ø${diametro}mm</strong></td>`;
        html += `<td><span class="badge bg-info">${info.total_kg.toFixed(2)} kg</span></td>`;
        html += `<td>${info.num_productos} producto(s)</td>`;
        html += '<td>';

        if (info.productos && info.productos.length > 0) {
            html += '<ul class="list-unstyled mb-0 small">';
            info.productos.forEach(prod => {
                html += `<li>P${prod.producto_id}: ${prod.consumido.toFixed(2)} kg</li>`;
            });
            html += '</ul>';
        }

        html += '</td>';
        html += '</tr>';
    }

    html += '</tbody></table></div>';
    container.innerHTML = html;
}
</script>
@endpush

<style>
#modalDetallesFabricacion .card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#modalDetallesFabricacion .table th {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #495057;
}

#modalDetallesFabricacion .modal-body {
    background-color: #f8f9fa;
}
</style>
