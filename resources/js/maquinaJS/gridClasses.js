/**
 * Manejo dinámico de clases del grid para cambio de tamaño de etiquetas
 */

export function initGridClasses() {
    console.log('🎯 Inicializando control de clases del grid');

    // Función para actualizar clases
    window.updateGridClasses = function(showLeft, showRight) {
            const grid = document.getElementById('grid-maquina');
            if (!grid) {
                console.error('❌ No se encontró #grid-maquina');
                return;
            }

            console.log('🔧 Actualizando clases:', {
                showLeft,
                showRight,
                clasesAnteriores: grid.className
            });

            // Aplicar clase si al menos una columna está visible
            if (showLeft || showRight) {
                grid.classList.add('columnas-laterales-visibles');
            } else {
                grid.classList.remove('columnas-laterales-visibles');
            }

            // Aplicar clase especial si AMBAS columnas están visibles
            if (showLeft && showRight) {
                grid.classList.add('ambas-columnas');
            } else {
                grid.classList.remove('ambas-columnas');
            }

            console.log('✅ Clases actualizadas:', grid.className);

            // FORZAR REPAINT del navegador de forma más suave
            // Solo forzar reflow sin ocultar el grid
            void grid.offsetHeight; // Trigger reflow

            // Forzar recalcular estilos en todas las etiquetas
            const etiquetas = grid.querySelectorAll('.etiqueta-card');
            etiquetas.forEach(etiqueta => {
                void etiqueta.offsetHeight;
            });

            console.log('🎨 Repaint forzado (optimizado)');
    };

    // Escuchar eventos personalizados
    window.addEventListener('toggleLeft', () => {
        const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
        const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
        window.updateGridClasses(showLeft, showRight);
    });

    window.addEventListener('solo', () => {
        window.updateGridClasses(false, false);
    });

    window.addEventListener('toggleRight', () => {
        const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
        const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
        window.updateGridClasses(showLeft, showRight);
    });

    // Aplicar clases iniciales tan pronto como el grid esté disponible
    function applyInitialClasses() {
        const grid = document.getElementById('grid-maquina');
        if (!grid) {
            console.log('⏳ Grid no encontrado, reintentando...');
            // Si el grid no existe, usar MutationObserver para detectar cuando se agregue
            const observer = new MutationObserver((mutations, obs) => {
                const grid = document.getElementById('grid-maquina');
                if (grid) {
                    console.log('✅ Grid detectado por MutationObserver');
                    obs.disconnect();
                    const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
                    const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
                    window.updateGridClasses(showLeft, showRight);
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
            return;
        }

        const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'true');
        const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
        window.updateGridClasses(showLeft, showRight);
        console.log('✅ Clases iniciales aplicadas inmediatamente');
    }

    applyInitialClasses();
}

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGridClasses);
} else {
    initGridClasses();
}
