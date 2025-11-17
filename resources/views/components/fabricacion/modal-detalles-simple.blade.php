{{-- Modal Simple sin dependencias de Bootstrap/jQuery --}}
<div id="modalDetallesFabricacion" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50" style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        {{-- Header --}}
        <div class="flex items-center justify-between p-4 bg-blue-600 text-white rounded-t-md">
            <h3 class="text-xl font-semibold">
                <i class="fas fa-info-circle mr-2"></i>
                Detalle de Fabricación - Etiqueta #<span id="etiqueta-id-display">--</span>
            </h3>
            <button type="button" onclick="cerrarModalDetalles()" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-4 max-h-96 overflow-y-auto">
            {{-- Loading --}}
            <div id="loading-detalles" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-3 text-gray-600">Cargando detalles de fabricación...</p>
            </div>

            {{-- Contenido --}}
            <div id="contenido-detalles" style="display: none;">
                {{-- Información General --}}
                <div class="mb-4 bg-gray-50 p-4 rounded">
                    <h6 class="font-semibold mb-2 text-gray-700">
                        <i class="fas fa-info mr-2"></i>Información General
                    </h6>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p><strong>Etiqueta:</strong> <span id="info-etiqueta-id">--</span></p>
                            <p><strong>Máquina:</strong> <span id="info-maquina">--</span></p>
                        </div>
                        <div>
                            <p><strong>Fecha:</strong> <span id="info-fecha">--</span></p>
                            <p><strong>Elementos:</strong> <span id="info-total-elementos">--</span></p>
                        </div>
                    </div>
                </div>

                {{-- Asignación de Coladas --}}
                <div class="mb-4 bg-green-50 p-4 rounded">
                    <h6 class="font-semibold mb-2 text-green-800">
                        <i class="fas fa-boxes mr-2"></i>Asignación de Coladas
                    </h6>
                    <div id="asignacion-coladas-content" class="text-sm"></div>
                </div>

                {{-- Consumo de Stock --}}
                <div class="mb-4 bg-blue-50 p-4 rounded">
                    <h6 class="font-semibold mb-2 text-blue-800">
                        <i class="fas fa-chart-bar mr-2"></i>Consumo de Stock por Diámetro
                    </h6>
                    <div id="consumo-stock-content" class="text-sm"></div>
                </div>

                {{-- Warnings --}}
                <div id="warnings-container" class="mb-4 bg-yellow-50 p-4 rounded" style="display: none;">
                    <h6 class="font-semibold mb-2 text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Advertencias
                    </h6>
                    <div id="warnings-content"></div>
                </div>

                {{-- Estadísticas --}}
                <div class="bg-gray-50 p-4 rounded">
                    <h6 class="font-semibold mb-3 text-gray-700">
                        <i class="fas fa-chart-pie mr-2"></i>Estadísticas de Asignación
                    </h6>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-white p-3 rounded border border-green-200">
                            <i class="fas fa-box text-green-600 text-2xl mb-2"></i>
                            <h4 class="text-2xl font-bold" id="stats-simple">0</h4>
                            <small class="text-gray-600">Simples</small>
                        </div>
                        <div class="bg-white p-3 rounded border border-yellow-200">
                            <i class="fas fa-boxes text-yellow-600 text-2xl mb-2"></i>
                            <h4 class="text-2xl font-bold" id="stats-doble">0</h4>
                            <small class="text-gray-600">Dobles</small>
                        </div>
                        <div class="bg-white p-3 rounded border border-red-200">
                            <i class="fas fa-layer-group text-red-600 text-2xl mb-2"></i>
                            <h4 class="text-2xl font-bold" id="stats-triple">0</h4>
                            <small class="text-gray-600">Triples</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Error --}}
            <div id="error-detalles" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded" style="display: none;">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span id="error-message">Error al cargar los detalles</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end p-4 border-t">
            <button type="button" onclick="cerrarModalDetalles()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Abre el modal de detalles de fabricación
 */
window.mostrarDetallesFabricacion = function(etiquetaId, month = null) {
    console.log('Abriendo modal para etiqueta:', etiquetaId);

    const modal = document.getElementById('modalDetallesFabricacion');
    if (!modal) {
        console.error('Modal no encontrado');
        alert('Error: Modal no encontrado');
        return;
    }

    // Mostrar modal
    modal.style.display = 'block';
    modal.classList.remove('hidden');

    // Actualizar ID en el título
    document.getElementById('etiqueta-id-display').textContent = etiquetaId;

    // Mostrar loading
    document.getElementById('loading-detalles').style.display = 'block';
    document.getElementById('contenido-detalles').style.display = 'none';
    document.getElementById('error-detalles').style.display = 'none';

    // Construir URL usando la ruta de Laravel
    let url = '{{ route("api.fabricacion.detalles") }}' + '?etiqueta_id=' + etiquetaId;
    if (month) {
        url += '&month=' + month;
    }

    console.log('Llamando a API:', url);

    // Hacer petición
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON. Verifica que estés autenticado y la ruta sea correcta.');
            }

            return response.json();
        })
        .then(data => {
            console.log('Data recibida:', data);

            if (!data.success) {
                throw new Error(data.message || 'Error al cargar detalles');
            }

            // Ocultar loading, mostrar contenido
            document.getElementById('loading-detalles').style.display = 'none';
            document.getElementById('contenido-detalles').style.display = 'block';

            // Rellenar datos
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
                    document.getElementById('warnings-content').innerHTML = '<div class="text-yellow-700"><i class="fas fa-exclamation-triangle mr-2"></i>Stock fragmentado detectado</div>';
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
};

/**
 * Cierra el modal
 */
window.cerrarModalDetalles = function() {
    const modal = document.getElementById('modalDetallesFabricacion');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
};

/**
 * Cerrar modal al hacer click fuera
 */
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalDetallesFabricacion');
    if (event.target === modal) {
        cerrarModalDetalles();
    }
});

/**
 * Cerrar modal con tecla ESC
 */
window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalDetalles();
    }
});

/**
 * Renderiza la asignación de coladas
 */
window.renderAsignacionColadas = function(elementos) {
    const container = document.getElementById('asignacion-coladas-content');
    if (!elementos || elementos.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No hay datos de asignación disponibles</p>';
        return;
    }

    let html = '<div class="overflow-x-auto"><table class="min-w-full text-xs border">';
    html += '<thead class="bg-gray-100"><tr>';
    html += '<th class="border px-2 py-1">Elemento</th>';
    html += '<th class="border px-2 py-1">Diámetro</th>';
    html += '<th class="border px-2 py-1">Peso</th>';
    html += '<th class="border px-2 py-1">Tipo</th>';
    html += '<th class="border px-2 py-1">Coladas Asignadas</th>';
    html += '</tr></thead><tbody>';

    elementos.forEach(elem => {
        const tipoBadge = {
            'simple': '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Simple</span>',
            'doble': '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Doble</span>',
            'triple': '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Triple</span>'
        }[elem.tipo_asignacion] || '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">N/A</span>';

        html += '<tr class="hover:bg-gray-50">';
        html += `<td class="border px-2 py-1"><strong>#${elem.elemento_id}</strong></td>`;
        html += `<td class="border px-2 py-1">Ø${elem.diametro}mm</td>`;
        html += `<td class="border px-2 py-1">${elem.peso.toFixed(2)} kg</td>`;
        html += `<td class="border px-2 py-1">${tipoBadge}</td>`;
        html += '<td class="border px-2 py-1">';

        if (elem.coladas && elem.coladas.length > 0) {
            html += '<ul class="list-none space-y-1">';
            elem.coladas.forEach((colada, index) => {
                html += `<li>`;
                html += `<span class="inline-block w-4 h-4 bg-blue-500 text-white text-xs rounded-full text-center leading-4">${index + 1}</span> `;
                html += `P${colada.producto_id} - <strong>Colada: ${colada.n_colada}</strong> `;
                html += `<span class="text-gray-500">(${colada.peso_consumido.toFixed(2)} kg)</span>`;
                html += `</li>`;
            });
            html += '</ul>';
        } else {
            html += '<span class="text-gray-400">Sin coladas</span>';
        }

        html += '</td></tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
};

/**
 * Renderiza el consumo de stock
 */
window.renderConsumoStock = function(diametros) {
    const container = document.getElementById('consumo-stock-content');
    if (!diametros || Object.keys(diametros).length === 0) {
        container.innerHTML = '<p class="text-gray-500">No hay datos de consumo disponibles</p>';
        return;
    }

    let html = '<div class="overflow-x-auto"><table class="min-w-full text-xs border">';
    html += '<thead class="bg-gray-100"><tr>';
    html += '<th class="border px-2 py-1">Diámetro</th>';
    html += '<th class="border px-2 py-1">Total Consumido</th>';
    html += '<th class="border px-2 py-1">Productos</th>';
    html += '<th class="border px-2 py-1">Detalle</th>';
    html += '</tr></thead><tbody>';

    for (const [diametro, info] of Object.entries(diametros)) {
        html += '<tr class="hover:bg-gray-50">';
        html += `<td class="border px-2 py-1"><strong>Ø${diametro}mm</strong></td>`;
        html += `<td class="border px-2 py-1"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">${info.total_kg.toFixed(2)} kg</span></td>`;
        html += `<td class="border px-2 py-1">${info.num_productos} producto(s)</td>`;
        html += '<td class="border px-2 py-1">';

        if (info.productos && info.productos.length > 0) {
            html += '<ul class="list-none text-xs">';
            info.productos.forEach(prod => {
                html += `<li>P${prod.producto_id}: ${prod.consumido.toFixed(2)} kg</li>`;
            });
            html += '</ul>';
        }

        html += '</td></tr>';
    }

    html += '</tbody></table></div>';
    container.innerHTML = html;
};

console.log('✅ Modal de trazabilidad cargado (JavaScript puro, sin dependencias)');
</script>
