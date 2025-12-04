{{-- Panel lateral de informaci�n del elemento --}}
<div id="panel-info-elemento" class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50 flex flex-col">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex justify-between items-center">
        <h3 class="text-lg font-semibold">Informaci�n del Elemento</h3>
        <button onclick="cerrarPanelInfoElemento()" class="text-white hover:text-gray-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Contenido --}}
    <div id="panel-info-contenido" class="flex-1 overflow-y-auto p-4">
        <div class="text-center text-gray-500 py-8">
            Selecciona un elemento para ver su informaci�n
        </div>
    </div>
</div>

{{-- Overlay oscuro --}}
<div id="panel-info-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="cerrarPanelInfoElemento()"></div>

{{-- Modal para ver dimensiones --}}
<div id="modal-ver-dimensiones" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col">
        {{-- Header del modal --}}
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-lg font-semibold">Dimensiones del Elemento</h3>
            <button onclick="cerrarModalVerDimensiones()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Contenido del modal --}}
        <div id="modal-dimensiones-contenido" class="flex-1 overflow-y-auto p-6">
            <div class="text-center text-gray-500 py-8">
                Cargando dimensiones...
            </div>
        </div>

        {{-- Footer del modal --}}
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button onclick="cerrarModalVerDimensiones()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
// Funci�n para mostrar el panel de informaci�n del elemento
window.mostrarPanelInfoElemento = async function(elementoId) {
    try {
        // Buscar el elemento en los datos cargados
        let elemento = null;
        if (window.elementosAgrupadosScript) {
            for (const grupo of window.elementosAgrupadosScript) {
                const found = grupo.elementos?.find(e => e.id == elementoId);
                if (found) {
                    elemento = found;
                    break;
                }
            }
        }

        if (!elemento) {
            console.error('Elemento no encontrado:', elementoId);
            return;
        }

        // Construir el HTML del panel
        const html = `
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">C�digo</div>
                    <div class="text-lg font-semibold text-gray-900">${elemento.codigo || 'N/A'}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-600 mb-1">Di�metro</div>
                        <div class="text-base font-medium text-gray-900">${elemento.diametro || 'N/A'}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-600 mb-1">Barras</div>
                        <div class="text-base font-medium text-gray-900">${elemento.barras || 0}</div>
                    </div>
                </div>

                ${elemento.dimensiones ? `
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xs text-gray-600 mb-1">Dimensiones</div>
                    <div class="text-sm font-mono text-gray-900 break-all">${elemento.dimensiones}</div>
                </div>
                ` : ''}

                ${elemento.coladas && (elemento.coladas.colada1 || elemento.coladas.colada2 || elemento.coladas.colada3) ? `
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="text-xs text-gray-600 mb-2">Coladas</div>
                    <div class="space-y-1">
                        ${elemento.coladas.colada1 ? `<div class="text-sm text-gray-900">" ${elemento.coladas.colada1}</div>` : ''}
                        ${elemento.coladas.colada2 ? `<div class="text-sm text-gray-900">" ${elemento.coladas.colada2}</div>` : ''}
                        ${elemento.coladas.colada3 ? `<div class="text-sm text-gray-900">" ${elemento.coladas.colada3}</div>` : ''}
                    </div>
                </div>
                ` : ''}

                <div class="pt-4 border-t border-gray-200 space-y-2">
                    <button onclick="abrirModalVerDimensiones(${elementoId})"
                        class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Ver Dimensiones Detalladas
                    </button>

                    <button onclick="abrirModalDividirElemento(${elementoId}, ${elemento.barras || 0})"
                        class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
                        </svg>
                        Dividir Elemento
                    </button>
                </div>
            </div>
        `;

        // Actualizar el contenido del panel
        document.getElementById('panel-info-contenido').innerHTML = html;

        // Mostrar el panel
        document.getElementById('panel-info-elemento').classList.remove('translate-x-full');
        document.getElementById('panel-info-overlay').classList.remove('hidden');

    } catch (error) {
        console.error('Error al mostrar panel de informacion:', error);
    }
};

// Funcion para cerrar el panel
window.cerrarPanelInfoElemento = function() {
    document.getElementById('panel-info-elemento').classList.add('translate-x-full');
    document.getElementById('panel-info-overlay').classList.add('hidden');
};

// Funcion para abrir el modal de ver dimensiones
window.abrirModalVerDimensiones = function(elementoId) {
    try {
        // Buscar el elemento
        let elemento = null;
        if (window.elementosAgrupadosScript) {
            for (const grupo of window.elementosAgrupadosScript) {
                const found = grupo.elementos?.find(e => e.id == elementoId);
                if (found) {
                    elemento = found;
                    break;
                }
            }
        }

        if (!elemento || !elemento.dimensiones) {
            alert('No hay dimensiones disponibles para este elemento');
            return;
        }

        // Parsear las dimensiones
        const dimensionesStr = elemento.dimensiones;
        const tokens = dimensionesStr.split(/\s+/).filter(Boolean);

        let dimensionesHTML = '<div class="space-y-3">';
        dimensionesHTML += `<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="text-sm font-medium text-blue-900">Dimensiones raw:</div>
            <div class="text-sm font-mono text-blue-700 mt-1 break-all">${dimensionesStr}</div>
        </div>`;

        dimensionesHTML += '<div class="grid gap-2">';

        tokens.forEach((token, index) => {
            let tipo = '';
            let valor = '';
            let icono = '';
            let colorClass = '';

            if (token.endsWith('r')) {
                tipo = 'Radio';
                valor = token.slice(0, -1) + ' mm';
                icono = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><line x1="12" y1="12" x2="12" y2="3" stroke-width="2"/></svg>`;
                colorClass = 'bg-green-50 border-green-200 text-green-900';
            } else if (token.endsWith('d')) {
                tipo = 'Angulo';
                valor = token.slice(0, -1) + '&deg;';
                icono = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 3 L12 12 L21 12" stroke-width="2"/><path d="M12 12 A9 9 0 0 0 21 12" stroke-width="2"/></svg>`;
                colorClass = 'bg-orange-50 border-orange-200 text-orange-900';
            } else {
                tipo = 'Longitud';
                valor = token + ' mm';
                icono = `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><line x1="4" y1="12" x2="20" y2="12" stroke-width="2"/><line x1="4" y1="8" x2="4" y2="16" stroke-width="2"/><line x1="20" y1="8" x2="20" y2="16" stroke-width="2"/></svg>`;
                colorClass = 'bg-blue-50 border-blue-200 text-blue-900';
            }

            dimensionesHTML += `
                <div class="flex items-center gap-3 p-3 border rounded-lg ${colorClass}">
                    <div class="flex-shrink-0">${icono}</div>
                    <div class="flex-1">
                        <div class="text-xs font-medium opacity-75">${tipo}</div>
                        <div class="text-base font-semibold">${valor}</div>
                    </div>
                    <div class="text-xs opacity-50">#${index + 1}</div>
                </div>
            `;
        });

        dimensionesHTML += '</div></div>';

        // Actualizar el modal
        document.getElementById('modal-dimensiones-contenido').innerHTML = dimensionesHTML;
        document.getElementById('modal-ver-dimensiones').classList.remove('hidden');

        // Cerrar el panel lateral
        cerrarPanelInfoElemento();

    } catch (error) {
        console.error('Error al abrir modal de dimensiones:', error);
        alert('Error al cargar las dimensiones');
    }
};

// Funcion para cerrar el modal de dimensiones
window.cerrarModalVerDimensiones = function() {
    document.getElementById('modal-ver-dimensiones').classList.add('hidden');
};

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('modal-ver-dimensiones').classList.contains('hidden')) {
            cerrarModalVerDimensiones();
        } else if (!document.getElementById('panel-info-overlay').classList.contains('hidden')) {
            cerrarPanelInfoElemento();
        }
    }
});
</script>
