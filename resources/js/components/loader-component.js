/**
 * Sistema de Loader Personalizado
 * Alternativa a SweetAlert para mostrar indicadores de carga
 */

class CustomLoader {
    constructor() {
        this.loaderElement = null;
        this.isShowing = false;
        this.initStyles();
    }

    /**
     * Inyecta los estilos CSS del loader en el documento
     */
    initStyles() {
        if (document.getElementById("custom-loader-styles")) return;

        const style = document.createElement("style");
        style.id = "custom-loader-styles";
        style.textContent = `
            /* Overlay de fondo */
            .custom-loader-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(3px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .custom-loader-overlay.show {
                opacity: 1;
            }

            /* Contenedor del loader */
            .custom-loader-container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
                min-width: 280px;
                transform: scale(0.9);
                transition: transform 0.2s ease;
            }

            .custom-loader-overlay.show .custom-loader-container {
                transform: scale(1);
            }

            /* Spinner circular */
            .custom-loader-spinner {
                width: 60px;
                height: 60px;
                border: 4px solid #e5e7eb;
                border-top-color: #3b82f6;
                border-radius: 50%;
                animation: custom-loader-spin 0.8s linear infinite;
            }

            @keyframes custom-loader-spin {
                to { transform: rotate(360deg); }
            }

            /* Spinner de puntos (alternativa) */
            .custom-loader-dots {
                display: flex;
                gap: 8px;
            }

            .custom-loader-dot {
                width: 12px;
                height: 12px;
                background: #3b82f6;
                border-radius: 50%;
                animation: custom-loader-bounce 1.4s infinite ease-in-out both;
            }

            .custom-loader-dot:nth-child(1) {
                animation-delay: -0.32s;
            }

            .custom-loader-dot:nth-child(2) {
                animation-delay: -0.16s;
            }

            @keyframes custom-loader-bounce {
                0%, 80%, 100% { 
                    transform: scale(0.6);
                    opacity: 0.5;
                }
                40% { 
                    transform: scale(1);
                    opacity: 1;
                }
            }

            /* Spinner de barras (alternativa) */
            .custom-loader-bars {
                display: flex;
                gap: 4px;
                height: 40px;
                align-items: center;
            }

            .custom-loader-bar {
                width: 6px;
                background: #3b82f6;
                border-radius: 3px;
                animation: custom-loader-bar-grow 1.2s infinite ease-in-out;
            }

            .custom-loader-bar:nth-child(1) { animation-delay: -0.24s; }
            .custom-loader-bar:nth-child(2) { animation-delay: -0.12s; }
            .custom-loader-bar:nth-child(3) { animation-delay: 0s; }

            @keyframes custom-loader-bar-grow {
                0%, 40%, 100% { height: 20%; }
                20% { height: 100%; }
            }

            /* Texto del loader */
            .custom-loader-text {
                color: #374151;
                font-size: 16px;
                font-weight: 500;
                text-align: center;
                line-height: 1.5;
                max-width: 300px;
            }

            .custom-loader-subtext {
                color: #6b7280;
                font-size: 14px;
                text-align: center;
                margin-top: -10px;
            }

            /* Responsive */
            @media (max-width: 640px) {
                .custom-loader-container {
                    padding: 30px 20px;
                    min-width: 240px;
                }

                .custom-loader-spinner {
                    width: 50px;
                    height: 50px;
                }

                .custom-loader-text {
                    font-size: 15px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Crea el elemento HTML del loader
     * @param {Object} options - Opciones de configuración
     * @returns {HTMLElement}
     */
    createLoader(options = {}) {
        const {
            text = "Cargando...",
            subtext = null,
            type = "spinner", // 'spinner', 'dots', 'bars'
        } = options;

        const overlay = document.createElement("div");
        overlay.className = "custom-loader-overlay";
        overlay.id = "custom-loader-overlay";

        const container = document.createElement("div");
        container.className = "custom-loader-container";

        // Crear el indicador de carga según el tipo
        let loaderElement;
        switch (type) {
            case "dots":
                loaderElement = document.createElement("div");
                loaderElement.className = "custom-loader-dots";
                loaderElement.innerHTML = `
                    <div class="custom-loader-dot"></div>
                    <div class="custom-loader-dot"></div>
                    <div class="custom-loader-dot"></div>
                `;
                break;

            case "bars":
                loaderElement = document.createElement("div");
                loaderElement.className = "custom-loader-bars";
                loaderElement.innerHTML = `
                    <div class="custom-loader-bar"></div>
                    <div class="custom-loader-bar"></div>
                    <div class="custom-loader-bar"></div>
                `;
                break;

            case "spinner":
            default:
                loaderElement = document.createElement("div");
                loaderElement.className = "custom-loader-spinner";
                break;
        }

        container.appendChild(loaderElement);

        // Agregar texto principal
        if (text) {
            const textElement = document.createElement("div");
            textElement.className = "custom-loader-text";
            textElement.textContent = text;
            container.appendChild(textElement);
        }

        // Agregar subtexto
        if (subtext) {
            const subtextElement = document.createElement("div");
            subtextElement.className = "custom-loader-subtext";
            subtextElement.textContent = subtext;
            container.appendChild(subtextElement);
        }

        overlay.appendChild(container);
        return overlay;
    }

    /**
     * Muestra el loader
     * @param {Object} options - Opciones de configuración
     */
    show(options = {}) {
        // Si ya está mostrando, no hacer nada
        if (this.isShowing) return;

        // Remover loader anterior si existe
        this.hide();

        // Crear y agregar nuevo loader
        this.loaderElement = this.createLoader(options);
        document.body.appendChild(this.loaderElement);

        // Forzar reflow para que la transición funcione
        this.loaderElement.offsetHeight;

        // Mostrar con animación
        requestAnimationFrame(() => {
            this.loaderElement.classList.add("show");
        });

        this.isShowing = true;

        // Prevenir scroll del body
        document.body.style.overflow = "hidden";
    }

    /**
     * Oculta el loader
     */
    hide() {
        if (!this.loaderElement) return;

        // Ocultar con animación
        this.loaderElement.classList.remove("show");

        // Remover del DOM después de la animación
        setTimeout(() => {
            if (this.loaderElement && this.loaderElement.parentNode) {
                this.loaderElement.parentNode.removeChild(this.loaderElement);
            }
            this.loaderElement = null;
            this.isShowing = false;

            // Restaurar scroll del body
            document.body.style.overflow = "";
        }, 200);
    }

    /**
     * Actualiza el texto del loader actual
     * @param {string} text - Nuevo texto principal
     * @param {string} subtext - Nuevo subtexto (opcional)
     */
    updateText(text, subtext = null) {
        if (!this.loaderElement) return;

        const textElement = this.loaderElement.querySelector(
            ".custom-loader-text"
        );
        if (textElement) {
            textElement.textContent = text;
        }

        const subtextElement = this.loaderElement.querySelector(
            ".custom-loader-subtext"
        );
        if (subtext) {
            if (subtextElement) {
                subtextElement.textContent = subtext;
            } else {
                const newSubtext = document.createElement("div");
                newSubtext.className = "custom-loader-subtext";
                newSubtext.textContent = subtext;
                this.loaderElement
                    .querySelector(".custom-loader-container")
                    .appendChild(newSubtext);
            }
        } else if (subtextElement) {
            subtextElement.remove();
        }
    }

    /**
     * Verifica si el loader está visible
     * @returns {boolean}
     */
    isVisible() {
        return this.isShowing;
    }
}

// Crear instancia global
window.customLoader = new CustomLoader();

// Exportar para uso en módulos
if (typeof module !== "undefined" && module.exports) {
    module.exports = CustomLoader;
}
