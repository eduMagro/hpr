/**
 * Manejo dinámico de clases del grid para cambio de tamaño de etiquetas
 */

export function initGridClasses() {
    // Esperar a que Alpine esté listo
    document.addEventListener("alpine:init", () => {
        // Función para actualizar clases
        window.updateGridClasses = function (showLeft, showRight) {
            const grid = document.getElementById("grid-maquina");
            if (!grid) return;

            // Aplicar clase si al menos una columna está visible
            if (showLeft || showRight) {
                grid.classList.add("columnas-laterales-visibles");
            } else {
                grid.classList.remove("columnas-laterales-visibles");
            }

            // Aplicar clase especial si AMBAS columnas están visibles
            if (showLeft && showRight) {
                grid.classList.add("ambas-columnas");
            } else {
                grid.classList.remove("ambas-columnas");
            }

            // FORZAR REPAINT del navegador de forma más suave
            void grid.offsetHeight;

            // Forzar recalcular estilos en todas las etiquetas
            const etiquetas = grid.querySelectorAll(".etiqueta-card");
            etiquetas.forEach((etiqueta) => {
                void etiqueta.offsetHeight;
            });
        };

        // Escuchar eventos personalizados
        window.addEventListener("toggleLeft", () => {
            const showLeft = JSON.parse(
                localStorage.getItem("showLeft") ?? "true"
            );
            const showRight = JSON.parse(
                localStorage.getItem("showRight") ?? "true"
            );
            window.updateGridClasses(showLeft, showRight);
        });

        window.addEventListener("solo", () => {
            window.updateGridClasses(false, false);
        });

        window.addEventListener("toggleRight", () => {
            const showLeft = JSON.parse(
                localStorage.getItem("showLeft") ?? "true"
            );
            const showRight = JSON.parse(
                localStorage.getItem("showRight") ?? "true"
            );
            window.updateGridClasses(showLeft, showRight);
        });

        function applyInitialClasses() {
            const grid = document.getElementById("grid-maquina");
            if (!grid) {
                const observer = new MutationObserver((mutations, obs) => {
                    const grid = document.getElementById("grid-maquina");
                    if (grid) {
                        obs.disconnect();
                        const showLeft = JSON.parse(
                            localStorage.getItem("showLeft") ?? "true"
                        );
                        const showRight = JSON.parse(
                            localStorage.getItem("showRight") ?? "true"
                        );
                        window.updateGridClasses(showLeft, showRight);
                    }
                });
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                });
                return;
            }

            const showLeft = JSON.parse(
                localStorage.getItem("showLeft") ?? "true"
            );
            const showRight = JSON.parse(
                localStorage.getItem("showRight") ?? "true"
            );
            window.updateGridClasses(showLeft, showRight);
        }

        applyInitialClasses();
    });
}

// Auto-inicializar cuando el DOM esté listo o tras navegación Livewire
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initGridClasses);
} else {
    initGridClasses();
}

document.addEventListener("livewire:navigated", initGridClasses);
