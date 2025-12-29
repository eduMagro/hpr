/**
 * ================================================================================
 * MÓDULO: historialEtiquetas.js - SISTEMA DE DESHACER (UNDO)
 * ================================================================================
 * Gestiona el historial de cambios de etiquetas y permite revertir acciones.
 * Funciona como un Ctrl+Z para las operaciones de fabricación.
 * ================================================================================
 */

(function () {
    // Evitar múltiples inicializaciones
    if (window.__historialEtiquetasInit) return;
    window.__historialEtiquetasInit = true;

    // ============================================================================
    // CONFIGURACIÓN
    // ============================================================================

    const CONFIG = {
        // Número máximo de undos a mostrar en el historial
        maxHistorial: 20,
        // Tiempo de debounce para evitar múltiples clics (ms)
        debounceTime: 500,
    };

    // Estado interno
    let _ultimaAccion = 0; // Timestamp de última acción (para debounce)

    // ============================================================================
    // FUNCIONES PRINCIPALES
    // ============================================================================

    /**
     * Deshace el último cambio de una etiqueta
     * @param {string} etiquetaSubId - El ID de la etiqueta (ej: "A-001.1")
     * @returns {Promise<object>} Resultado de la operación
     */
    async function deshacerEtiqueta(etiquetaSubId) {
        // Debounce para evitar doble clic
        const ahora = Date.now();
        if (ahora - _ultimaAccion < CONFIG.debounceTime) {
            console.warn("Acción demasiado rápida, ignorando...");
            return { success: false, message: "Espera un momento antes de intentar de nuevo." };
        }
        _ultimaAccion = ahora;

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!csrfToken) {
            console.error("CSRF token no encontrado");
            return { success: false, message: "Error de seguridad: token no encontrado." };
        }

        try {
            // Confirmar antes de deshacer
            const confirmacion = await Swal.fire({
                icon: "question",
                title: "¿Deshacer último cambio?",
                text: `Se revertirá el último cambio de la etiqueta ${etiquetaSubId}`,
                showCancelButton: true,
                confirmButtonText: "Sí, deshacer",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#f59e0b",
                cancelButtonColor: "#6b7280",
            });

            if (!confirmacion.isConfirmed) {
                return { success: false, message: "Operación cancelada." };
            }

            // Mostrar loading
            Swal.fire({
                title: "Deshaciendo...",
                text: "Revirtiendo cambios...",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });

            // Llamar al endpoint
            const response = await fetch(`/etiquetas/${encodeURIComponent(etiquetaSubId)}/deshacer`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                Swal.fire({
                    icon: "error",
                    title: "Error al deshacer",
                    text: data.message || "No se pudo revertir el cambio.",
                });
                return { success: false, message: data.message };
            }

            // Éxito: actualizar la UI
            Swal.fire({
                icon: "success",
                title: "Cambio revertido",
                html: `
                    <p>${data.message}</p>
                    <p class="text-sm text-gray-600 mt-2">
                        Estado actual: <strong>${data.estado}</strong>
                    </p>
                    ${data.puede_deshacer ? '<p class="text-xs text-blue-600 mt-1">Puedes deshacer más cambios.</p>' : ''}
                `,
                timer: 3000,
                showConfirmButton: false,
            });

            // Actualizar DOM de la etiqueta
            actualizarDOMDespuesDeDeshacer(etiquetaSubId, data);

            return data;

        } catch (error) {
            console.error("Error al deshacer etiqueta:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error de conexión al intentar deshacer el cambio.",
            });
            return { success: false, message: error.message };
        }
    }

    /**
     * Verifica si una etiqueta puede deshacer cambios
     * @param {string} etiquetaSubId
     * @returns {Promise<object>}
     */
    async function verificarPuedeDeshacer(etiquetaSubId) {
        try {
            const response = await fetch(`/etiquetas/${encodeURIComponent(etiquetaSubId)}/puede-deshacer`, {
                headers: { Accept: "application/json" },
            });

            if (!response.ok) {
                return { puede_deshacer: false };
            }

            return await response.json();
        } catch (error) {
            console.error("Error al verificar undo:", error);
            return { puede_deshacer: false };
        }
    }

    /**
     * Obtiene el historial de cambios de una etiqueta
     * @param {string} etiquetaSubId
     * @returns {Promise<object>}
     */
    async function obtenerHistorial(etiquetaSubId) {
        try {
            const response = await fetch(`/etiquetas/${encodeURIComponent(etiquetaSubId)}/historial`, {
                headers: { Accept: "application/json" },
            });

            if (!response.ok) {
                return { success: false, historial: [] };
            }

            return await response.json();
        } catch (error) {
            console.error("Error al obtener historial:", error);
            return { success: false, historial: [] };
        }
    }

    /**
     * Muestra el historial de cambios en un modal
     * @param {string} etiquetaSubId
     */
    async function mostrarHistorial(etiquetaSubId) {
        Swal.fire({
            title: "Cargando historial...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const data = await obtenerHistorial(etiquetaSubId);

        if (!data.success || !data.historial?.length) {
            Swal.fire({
                icon: "info",
                title: "Sin historial",
                text: "No hay cambios registrados para esta etiqueta.",
            });
            return;
        }

        const historialHtml = data.historial.map((item, idx) => `
            <tr class="${item.revertido ? 'bg-gray-100 text-gray-400' : (idx === 0 ? 'bg-yellow-50' : '')}">
                <td class="px-2 py-1 text-xs">${item.fecha}</td>
                <td class="px-2 py-1 text-xs font-medium">${item.accion}</td>
                <td class="px-2 py-1 text-xs">${item.estado_anterior || '-'} → ${item.estado_nuevo}</td>
                <td class="px-2 py-1 text-xs">
                    ${item.revertido
                        ? '<span class="text-gray-400">Revertido</span>'
                        : (idx === 0 ? '<span class="text-green-600 font-bold">Actual</span>' : '')}
                </td>
            </tr>
        `).join('');

        Swal.fire({
            title: `Historial de ${etiquetaSubId}`,
            html: `
                <div class="max-h-80 overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-200 sticky top-0">
                            <tr>
                                <th class="px-2 py-1 text-xs">Fecha</th>
                                <th class="px-2 py-1 text-xs">Acción</th>
                                <th class="px-2 py-1 text-xs">Estado</th>
                                <th class="px-2 py-1 text-xs"></th>
                            </tr>
                        </thead>
                        <tbody>${historialHtml}</tbody>
                    </table>
                </div>
                ${data.puede_deshacer ? `
                    <p class="mt-3 text-sm text-blue-600">
                        Puedes deshacer el último cambio (estado anterior: <strong>${data.ultimo_estado_reversible}</strong>)
                    </p>
                ` : ''}
            `,
            width: "600px",
            showConfirmButton: true,
            confirmButtonText: data.puede_deshacer ? "Deshacer último cambio" : "Cerrar",
            showCancelButton: data.puede_deshacer,
            cancelButtonText: "Cerrar",
            confirmButtonColor: data.puede_deshacer ? "#f59e0b" : "#6b7280",
        }).then((result) => {
            if (result.isConfirmed && data.puede_deshacer) {
                deshacerEtiqueta(etiquetaSubId);
            }
        });
    }

    // ============================================================================
    // ACTUALIZACIÓN DEL DOM
    // ============================================================================

    /**
     * Actualiza el DOM de la etiqueta después de deshacer
     * @param {string} etiquetaSubId
     * @param {object} data - Datos devueltos por el endpoint
     */
    function actualizarDOMDespuesDeDeshacer(etiquetaSubId, data) {
        const safeId = etiquetaSubId.replace(/\./g, "-");
        const elemento = document.getElementById(`etiqueta-${safeId}`);

        if (elemento) {
            // Actualizar estado en dataset
            elemento.dataset.estado = data.estado;

            // Actualizar clases CSS
            elemento.className = elemento.className
                .split(" ")
                .filter((c) => !c.startsWith("estado-"))
                .join(" ")
                .trim();
            elemento.classList.add(`estado-${data.estado}`);

            // Actualizar color de fondo del SVG si existe
            const contenedor = elemento.querySelector('[id^="contenedor-svg-"]');
            const svg = contenedor?.querySelector("svg");
            if (svg) {
                svg.style.background = getComputedStyle(elemento)
                    .getPropertyValue("--bg-estado")
                    .trim();
            }

            // Si existe SistemaDOM, usarlo también
            if (typeof window.SistemaDOM !== "undefined") {
                window.SistemaDOM.actualizarEstadoEtiqueta(etiquetaSubId, data.estado);
            }
        }

        // Si volvemos a estado pendiente, limpiar coladas del SVG y datos
        if (data.estado === "pendiente") {
            // Limpiar coladas en elementosAgrupadosScript (datos en memoria)
            if (window.elementosAgrupadosScript) {
                const grupos = window.elementosAgrupadosScript;
                const grupo = grupos.find(g =>
                    g.etiqueta && String(g.etiqueta.etiqueta_sub_id) === String(etiquetaSubId)
                );
                if (grupo) {
                    // Limpiar coladas del grupo (nivel etiqueta)
                    grupo.colada_etiqueta = null;
                    grupo.colada_etiqueta_2 = null;

                    // Limpiar coladas de cada elemento
                    if (grupo.elementos) {
                        grupo.elementos.forEach(el => {
                            el.coladas = { colada1: null, colada2: null, colada3: null };
                        });
                    }

                    // Disparar evento para que canvasMaquina regenere el SVG
                    window.dispatchEvent(new CustomEvent('regenerar-svg-etiqueta', {
                        detail: { etiquetaSubId: etiquetaSubId }
                    }));
                }
            }

            // Limpiar el texto de colada en el encabezado de la etiqueta
            const spanColada = document.getElementById(`colada-${safeId}`);
            if (spanColada) {
                spanColada.textContent = "";
            }
        }

        // NO refrescamos la página para mantener la posición del scroll
        console.log(`✅ DOM actualizado para ${etiquetaSubId}: estado = ${data.estado}`);
    }

    /**
     * Actualiza el estado visual del botón de deshacer
     * @param {string} etiquetaSubId
     * @param {boolean} puedeDeshacer
     */
    function actualizarBotonDeshacer(etiquetaSubId, puedeDeshacer) {
        const safeId = etiquetaSubId.replace(/\./g, "-");
        const btn = document.querySelector(`#etiqueta-${safeId} .btn-deshacer`);

        console.log(`🔄 actualizarBotonDeshacer: ${etiquetaSubId}, puede=${puedeDeshacer}, btn encontrado=${!!btn}`);

        if (btn) {
            // Ya NO deshabilitamos el botón - siempre habilitado
            // Si no hay cambios, se muestra mensaje al hacer clic
            btn.disabled = false;
            btn.classList.remove("opacity-50", "cursor-not-allowed");
            btn.title = "Deshacer último cambio (Ctrl+Z)";
        }
    }

    // ============================================================================
    // EVENT LISTENERS
    // ============================================================================

    /**
     * Inicializa los event listeners para botones de deshacer
     */
    function initEventListeners() {
        // Delegación de eventos para botones de deshacer
        document.addEventListener("click", async (ev) => {
            const btn = ev.target.closest(".btn-deshacer");
            if (!btn) return;

            ev.preventDefault();
            ev.stopPropagation();

            const etiquetaId = btn.dataset.etiquetaId;
            if (!etiquetaId) {
                console.error("Botón deshacer sin data-etiqueta-id");
                return;
            }

            await deshacerEtiqueta(etiquetaId);
        });

        // Delegación para botón de historial
        document.addEventListener("click", async (ev) => {
            const btn = ev.target.closest(".btn-historial");
            if (!btn) return;

            ev.preventDefault();
            ev.stopPropagation();

            const etiquetaId = btn.dataset.etiquetaId;
            if (!etiquetaId) return;

            await mostrarHistorial(etiquetaId);
        });

        console.log("✅ Event listeners de historial inicializados");
    }

    // ============================================================================
    // ATAJO DE TECLADO (CTRL+Z)
    // ============================================================================

    /**
     * Inicializa el atajo de teclado Ctrl+Z para deshacer
     * Solo funciona si hay una etiqueta "enfocada" o la última fabricada
     */
    function initAtajoTeclado() {
        let ultimaEtiquetaFabricada = null;

        // Escuchar evento de fabricación para recordar la última etiqueta
        window.addEventListener("etiqueta-fabricada", (e) => {
            if (e.detail?.etiquetaSubId) {
                ultimaEtiquetaFabricada = e.detail.etiquetaSubId;
            }
        });

        // Atajo Ctrl+Z
        document.addEventListener("keydown", async (e) => {
            // Solo Ctrl+Z (sin Shift para no interferir con Ctrl+Shift+Z)
            if (e.ctrlKey && e.key === "z" && !e.shiftKey) {
                // No interferir si está en un input/textarea
                if (["INPUT", "TEXTAREA"].includes(document.activeElement?.tagName)) {
                    return;
                }

                // Buscar etiqueta enfocada o usar la última fabricada
                const etiquetaEnfocada = document.querySelector(".etiqueta-card:focus, .etiqueta-card:hover");
                const etiquetaId = etiquetaEnfocada?.dataset?.etiquetaId || ultimaEtiquetaFabricada;

                if (etiquetaId) {
                    e.preventDefault();
                    await deshacerEtiqueta(etiquetaId);
                }
            }
        });

        console.log("✅ Atajo Ctrl+Z habilitado para deshacer etiquetas");
    }

    // ============================================================================
    // INICIALIZACIÓN
    // ============================================================================

    function init() {
        initEventListeners();
        initAtajoTeclado();
        console.log("✅ Módulo historialEtiquetas.js inicializado");
    }

    // Exportar funciones públicas
    window.HistorialEtiquetas = {
        deshacer: deshacerEtiqueta,
        puedeDeshacer: verificarPuedeDeshacer,
        obtenerHistorial: obtenerHistorial,
        mostrarHistorial: mostrarHistorial,
        actualizarBoton: actualizarBotonDeshacer,
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    // Reinicializar en navegación Livewire
    document.addEventListener("livewire:navigated", init);
})();
