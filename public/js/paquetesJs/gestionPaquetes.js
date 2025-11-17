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
        const elemento = document.querySelector(`#etiqueta-${safeId}`);

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

            // Eliminar info del paquete
            const paqueteInfo = elemento.querySelector(".paquete-info");
            if (paqueteInfo) {
                paqueteInfo.remove();
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
     * Actualizar o añadir información del paquete en la etiqueta
     */
    function actualizarInfoPaqueteEnEtiqueta(elemento, codigoPaquete) {
        const h3 = elemento.querySelector("h3");
        if (!h3) return;

        // Buscar si ya existe info del paquete
        let paqueteInfo = elemento.querySelector(".paquete-info");

        if (!paqueteInfo) {
            // Crear nuevo elemento de info
            paqueteInfo = document.createElement("div");
            paqueteInfo.className =
                "paquete-info text-sm font-semibold mt-2 no-print";
            paqueteInfo.style.cssText =
                "display: flex; align-items: center; gap: 0.25rem; color: #7c3aed; font-size: 0.875rem;";
            h3.parentNode.insertBefore(paqueteInfo, h3.nextSibling);
        }

        // Actualizar contenido
        paqueteInfo.innerHTML = `
            <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
            </svg>
            <span>Paquete: ${codigoPaquete}</span>
        `;
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
                // Etiqueta eliminada del paquete
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
