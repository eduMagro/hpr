/**
 * Manejo dinámico de clases del grid para cambio de tamaño de etiquetas
 */

export function initGridClasses() {
    // Esperar a que Alpine esté listo
    document.addEventListener('alpine:init', () => {
        // console.log('🎯 Inicializando control de clases del grid');

        // Función para actualizar clases
        window.updateGridClasses = function (showLeft, showRight) {
            const grid = document.getElementById('grid-maquina');
            if (!grid) {
                // console.error('❌ No se encontró #grid-maquina');
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

            // FORZAR REPAINT del navegador
            grid.style.display = 'none';
            void grid.offsetHeight; // Trigger reflow
            grid.style.display = '';

            // Forzar recalcular estilos en todas las etiquetas
            const etiquetas = grid.querySelectorAll('.etiqueta-card');
            etiquetas.forEach(etiqueta => {
                void etiqueta.offsetHeight;
            });

            console.log('🎨 Repaint forzado');
        };

        // Escuchar eventos personalizados
        window.addEventListener('toggleLeft', () => {
            const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'false');
            const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
            window.updateGridClasses(showLeft, showRight);
        });

        window.addEventListener('solo', () => {
            window.updateGridClasses(false, false);
        });

        window.addEventListener('toggleRight', () => {
            const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'false');
            const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
            window.updateGridClasses(showLeft, showRight);
        });

        // Aplicar clases iniciales
        setTimeout(() => {
            const showLeft = JSON.parse(localStorage.getItem('showLeft') ?? 'false');
            const showRight = JSON.parse(localStorage.getItem('showRight') ?? 'true');
            window.updateGridClasses(showLeft, showRight);
        }, 100);
    });
}

// Auto-inicializar cuando el DOM esté listo o tras navegación Livewire
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGridClasses);
} else {
    initGridClasses();
}
document.addEventListener('livewire:navigated', initGridClasses);
