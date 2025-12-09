// Gestión del panel lateral de elementos
window.cargarElementosPlanilla = async function(planillaId, codigo) {
    const panel = document.getElementById('panel_elementos');
    const overlay = document.getElementById('panel_overlay');
    const lista = document.getElementById('panel_lista');
    const codigoEl = document.getElementById('panel_codigo');

    if (codigoEl) codigoEl.textContent = `Planilla: ${codigo}`;

    // Mostrar panel
    panel.classList.remove('translate-x-full');
    overlay.classList.remove('hidden');
    overlay.style.pointerEvents = 'all';

    try {
        const response = await fetch(`/planillas/${planillaId}/elementos`);
        const data = await response.json();

        if (data.elementos && data.elementos.length > 0) {
            lista.innerHTML = data.elementos.map(elem => `
                <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-semibold text-gray-800">${elem.codigo || 'N/A'}</span>
                        <span class="text-xs px-2 py-1 rounded ${elem.estado === 'completado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${elem.estado || 'pendiente'}
                        </span>
                    </div>
                    <div class="text-xs text-gray-600 space-y-1">
                        <div>📏 Longitud: ${elem.longitud || 'N/A'} m</div>
                        <div>⚖️ Peso: ${elem.peso || 'N/A'} kg</div>
                        <div>⌀ Diámetro: ${elem.diametro || 'N/A'} mm</div>
                    </div>
                </div>
            `).join('');
        } else {
            lista.innerHTML = '<p class="text-gray-500 text-center">No hay elementos en esta planilla</p>';
        }
    } catch (error) {
        console.error('Error al cargar elementos:', error);
        lista.innerHTML = '<p class="text-red-500 text-center">Error al cargar elementos</p>';
    }
};

// Cerrar panel
document.getElementById('cerrar_panel')?.addEventListener('click', () => {
    const panel = document.getElementById('panel_elementos');
    const overlay = document.getElementById('panel_overlay');

    panel.classList.add('translate-x-full');
    overlay.classList.add('hidden');
    overlay.style.pointerEvents = 'none';
});

// Cerrar al hacer clic en el overlay
document.getElementById('panel_overlay')?.addEventListener('click', () => {
    document.getElementById('cerrar_panel')?.click();
});
