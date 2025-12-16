/**
 * ================================================================================
 * EXTENSIÓN: actualizarDom.js - Integración con Gestión de Paquetes
 * ================================================================================
 * Este archivo extiende las funcionalidades de actualizarDom.js para
 * sincronizar cambios entre el sistema de creación de paquetes y la gestión.
 * ================================================================================
 */

(function (global) {
    "use strict";

    /**
     * Actualizar el estado visual de una etiqueta cuando se añade/elimina de un paquete
     */
    function actualizarEstadoEtiquetaPaquete(
        etiquetaId,
        estado,
        codigoPaquete = null
    ) {
        const safeId = String(etiquetaId).replace(/\./g, "-");

        // 1. Buscar etiqueta individual por ID
        let elemento = document.querySelector(`#etiqueta-${safeId}`);

        // 2. Si no se encuentra, buscar por data-etiqueta-sub-id en el wrapper
        if (!elemento) {
            const wrapper = document.querySelector(`[data-etiqueta-sub-id="${etiquetaId}"]`);
            if (wrapper) {
                elemento = wrapper.querySelector('.etiqueta-card');
            }
        }

        // 3. Si tampoco, buscar en grupos que contengan esta etiqueta
        if (!elemento) {
            const grupos = document.querySelectorAll('[data-etiquetas-sub-ids]');
            for (const grupo of grupos) {
                try {
                    const etiquetasEnGrupo = JSON.parse(grupo.dataset.etiquetasSubIds || '[]');
                    if (etiquetasEnGrupo.includes(etiquetaId)) {
                        elemento = grupo;
                        console.log(`📦 Etiqueta ${etiquetaId} encontrada en grupo`);
                        break;
                    }
                } catch (e) {
                    // Ignorar errores de parseo
                }
            }
        }

        if (!elemento) {
            console.warn(`⚠️ No se encontró elemento: ${etiquetaId}`);
            return;
        }

        console.log(
            `🔄 Actualizando estado de etiqueta ${etiquetaId} a: ${estado}`
        );

        // 1. Limpiar clases de estado anteriores
        const clases = Array.from(elemento.classList);
        clases.forEach((clase) => {
            if (clase.startsWith("estado-")) {
                elemento.classList.remove(clase);
            }
        });

        // 2. Añadir clase según estado
        if (estado === "en-paquete" && codigoPaquete) {
            elemento.classList.add("estado-en-paquete");
            elemento.dataset.estado = "en-paquete";
            elemento.dataset.paquete = codigoPaquete;
            elemento.style.setProperty("--bg-estado", "#e3e4FA");

            // Actualizar info del paquete
            actualizarInfoPaqueteEnEtiqueta(elemento, codigoPaquete);
        } else if (estado === "pendiente") {
            elemento.classList.add("estado-pendiente");
            elemento.dataset.estado = "pendiente";
            delete elemento.dataset.paquete;
            elemento.style.setProperty("--bg-estado", "#ffffff");

            // Ocultar código de paquete inline
            const paqueteCodigoSpan = elemento.querySelector(".paquete-codigo");
            if (paqueteCodigoSpan) {
                paqueteCodigoSpan.textContent = "";
                paqueteCodigoSpan.style.display = "none";
            }
        }

        // 3. Actualizar SVG y card
        const contenedorSvg = elemento.querySelector('[id^="contenedor-svg-"]');
        if (contenedorSvg) {
            const svg = contenedorSvg.querySelector("svg");
            if (svg) {
                svg.style.background =
                    estado === "en-paquete" ? "#e3e4FA" : "#ffffff";
            }
        }

        const card = elemento.querySelector(".etiqueta-card") || elemento;
        if (card) {
            card.style.background =
                estado === "en-paquete" ? "#e3e4FA" : "#ffffff";
        }

        // 4. Animación de cambio
        // ✅ FIX: Solo transicionar transform y background, NO "all"
        elemento.style.transition = "transform 0.5s ease, background-color 0.5s ease";
        elemento.style.transform = "scale(1.03)";
        setTimeout(() => {
            elemento.style.transform = "scale(1)";
        }, 400);

        console.log(
            `✅ Etiqueta ${etiquetaId} actualizada a estado: ${estado}`
        );
    }

    /**
     * Actualizar o añadir información del paquete en la etiqueta (inline al lado del código)
     */
    function actualizarInfoPaqueteEnEtiqueta(elemento, codigoPaquete) {
        // Buscar el span del código de paquete
        const paqueteCodigoSpan = elemento.querySelector(".paquete-codigo");

        if (paqueteCodigoSpan) {
            paqueteCodigoSpan.textContent = `(${codigoPaquete})`;
            paqueteCodigoSpan.style.display = "inline";
        }
    }

    /**
     * Listener para eventos de actualización de paquetes
     */
    function inicializarListenersPaquetes() {
        console.log("🎧 Inicializando listeners de paquetes...");

        // Evento: Paquete creado
        window.addEventListener("paquete:creado", (e) => {
            console.log("🎉 Paquete creado:", e.detail);
            const { codigoPaquete, etiquetaIds } = e.detail;

            if (Array.isArray(etiquetaIds)) {
                etiquetaIds.forEach((etiquetaId) => {
                    actualizarEstadoEtiquetaPaquete(
                        etiquetaId,
                        "en-paquete",
                        codigoPaquete
                    );
                });
            }
        });

        // Evento: Paquete actualizado (etiqueta añadida o eliminada)
        window.addEventListener("paquete:actualizado", (e) => {
            console.log("🔄 Paquete actualizado:", e.detail);
            const { paqueteId, etiquetaCodigo, eliminada } = e.detail;

            if (eliminada) {
                // Etiqueta eliminada del paquete - quitar código de paquete inline
                actualizarEstadoEtiquetaPaquete(etiquetaCodigo, "pendiente");
            } else {
                // Etiqueta añadida al paquete - necesitamos el código del paquete
                // Podrías hacer un fetch aquí o pasar el código en el evento
                fetch(`/api/paquetes/${paqueteId}`)
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success && data.paquete) {
                            actualizarEstadoEtiquetaPaquete(
                                etiquetaCodigo,
                                "en-paquete",
                                data.paquete.codigo
                            );
                        }
                    })
                    .catch((err) =>
                        console.error("Error al obtener info del paquete:", err)
                    );
            }
        });

        // Evento: Paquete eliminado completamente
        // NOTA: La actualización del DOM se hace directamente en eliminarPaquete.js
        // Este listener es solo para logging y posibles extensiones futuras
        window.addEventListener("paquete:eliminado", (e) => {
            console.log("🗑️ Evento paquete:eliminado recibido:", e.detail);
            // La actualización del DOM ya se hace en eliminarPaquete.js con limpiarCodigoPaqueteDeEtiqueta()
            // que restaura el color correcto según el estado real de cada etiqueta
        });

        console.log("✅ Listeners de paquetes inicializados");
    }

    // ============================================================================
    // API PÚBLICA
    // ============================================================================

    // Extender el objeto global o crear uno nuevo
    global.GestionPaquetesDOM = {
        actualizarEstadoEtiquetaPaquete,
        actualizarInfoPaqueteEnEtiqueta,
        inicializarListenersPaquetes,
    };

    // Inicializar automáticamente
    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            inicializarListenersPaquetes
        );
    } else {
        inicializarListenersPaquetes();
    }
})(window);
