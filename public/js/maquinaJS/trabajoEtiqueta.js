/**
 * ================================================================================
 * MÓDULO: trabajoEtiqueta.js - VERSIÓN CON SISTEMA CENTRALIZADO
 * ================================================================================
 * ✅ Usa SistemaDOM para actualizar estados
 * ✅ Dispara eventos para sincronización
 * ✅ Control completo de estados de etiquetas
 * ================================================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    if (window.__trabajoEtiquetaInit) return;
    window.__trabajoEtiquetaInit = true;

    console.log("🚀 Inicializando módulo trabajoEtiqueta.js");

    // ============================================================================
    // CLICK EN BOTÓN FABRICAR
    // ============================================================================

    document.addEventListener("click", async (ev) => {
        const btn = ev.target.closest(".btn-fabricar");
        if (!btn) return;
        ev.preventDefault();

        const maquinaId =
            document.getElementById("maquina-info")?.dataset?.maquinaId;

        if (!maquinaId) {
            console.error("No se encontró #maquina-info o data-maquina-id.");
            await Swal.fire({
                icon: "error",
                title: "Falta la máquina",
                text: "No se pudo determinar la máquina de trabajo.",
            });
            return;
        }

        const etiquetaId = String(btn.dataset.etiquetaId || "").trim();
        const safeId = etiquetaId.replace(/\./g, "-");
        const elementoEtiqueta = document.querySelector(`#etiqueta-${safeId}`);

        if (elementoEtiqueta) {
            const estadoActual = (
                elementoEtiqueta.dataset.estado || ""
            ).toLowerCase();

            if (
                ["completada", "en-paquete", "empaquetada"].includes(
                    estadoActual
                )
            ) {
                await Swal.fire({
                    icon: "info",
                    title: "Etiqueta ya completada",
                    text: `La etiqueta ${etiquetaId} ya está en estado ${estadoActual}.`,
                    timer: 2500,
                    showConfirmButton: false,
                });
                return;
            }
        }

        const diametro = Number(
            window.DIAMETRO_POR_ETIQUETA?.[etiquetaId] ?? 0
        );

        if (!etiquetaId) {
            Swal.fire({
                icon: "error",
                title: "Etiqueta no válida",
                text: "Falta el ID de etiqueta.",
            });
            return;
        }
        if (
            !diametro &&
            (window.MAQUINA_TIPO || "").toLowerCase() === "barra"
        ) {
            Swal.fire({
                icon: "error",
                title: "Diámetro desconocido",
                text: "No se pudo determinar el diámetro de la subetiqueta.",
            });
            return;
        }

        const prevDisabled = btn.disabled;
        btn.disabled = true;
        try {
            await actualizarEtiqueta(etiquetaId, maquinaId, diametro);
        } finally {
            btn.disabled = prevDisabled;
        }
    });

    // ============================================================================
    // FUNCIÓN PRINCIPAL: ACTUALIZAR ETIQUETA
    // ============================================================================

    async function actualizarEtiqueta(id, maquinaId, diametro = null) {
        const url = `/actualizar-etiqueta/${id}/maquina/${maquinaId}`;
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        const safeId = id.replace(/\./g, "-");
        const estadoActual = document.querySelector(`#etiqueta-${safeId}`)
            ?.dataset?.estado;
        const esFabricando =
            (estadoActual || "").toLowerCase() === "fabricando";
        const esMaquinaBarra =
            (window.MAQUINA_TIPO || "").toLowerCase() === "barra";

        // ────────────────────────────────────────────────
        //  A) MÁQUINAS DE BARRA → SIEMPRE VÍA PATRONES
        // ────────────────────────────────────────────────
        if (esMaquinaBarra) {
            if (esFabricando && window._decisionCortePorEtiqueta?.[id]) {
                await Cortes.enviarAFabricacionOptimizada({
                    ...window._decisionCortePorEtiqueta[id],
                    csrfToken,
                    etiquetaId: id,
                    onUpdate: actualizarDOMEtiqueta,
                });
                return;
            }

            while (true) {
                let decision;
                try {
                    decision = await Cortes.mejorCorteSimple(
                        id,
                        diametro,
                        csrfToken
                    );
                } catch (err) {
                    showErrorAlert(err);
                    return;
                }

                if (!decision) return;

                if (decision.accion === "optimizar") {
                    let outcome;
                    try {
                        outcome = await Cortes.mejorCorteOptimizado(
                            id,
                            diametro,
                            decision.patrones,
                            csrfToken
                        );
                    } catch (err) {
                        showErrorAlert(err);
                        return;
                    }

                    if (outcome?.accion === "fabricar") {
                        window._decisionCortePorEtiqueta =
                            window._decisionCortePorEtiqueta || {};

                        window._decisionCortePorEtiqueta[id] = {
                            longitudBarraCm: outcome.longitudBarraCm,
                            etiquetas: outcome.etiquetas,
                        };
                        localStorage.setItem(
                            "decisionCortePorEtiqueta",
                            JSON.stringify(window._decisionCortePorEtiqueta)
                        );

                        await Cortes.enviarAFabricacionOptimizada({
                            ...window._decisionCortePorEtiqueta[id],
                            csrfToken,
                            etiquetaId: id,
                            onUpdate: actualizarDOMEtiqueta,
                        });
                        return;
                    } else if (outcome === "volver") {
                        continue;
                    } else {
                        return;
                    }
                }

                if (decision.accion === "fabricar_patron_simple") {
                    const longitudBarraCm = Math.round(
                        Number(decision.longitud_m || 0) * 100
                    );
                    if (!longitudBarraCm) {
                        showErrorAlert("Longitud de barra no válida.");
                        return;
                    }

                    window._decisionCortePorEtiqueta =
                        window._decisionCortePorEtiqueta || {};
                    window._decisionCortePorEtiqueta[id] = {
                        longitudBarraCm,
                        etiquetas: [{ etiqueta_sub_id: id, elementos: [] }],
                    };
                    localStorage.setItem(
                        "decisionCortePorEtiqueta",
                        JSON.stringify(window._decisionCortePorEtiqueta)
                    );

                    await Cortes.enviarAFabricacionOptimizada({
                        ...window._decisionCortePorEtiqueta[id],
                        csrfToken,
                        etiquetaId: id,
                        onUpdate: actualizarDOMEtiqueta,
                    });
                    return;
                }

                return;
            }
        }

        // ────────────────────────────────────────────────
        //  B) MÁQUINAS NORMALES → LLAMADA DIRECTA
        // ────────────────────────────────────────────────
        try {
            const res = await fetch(url, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({}),
            });

            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || "Error al actualizar");
            }

            actualizarDOMEtiqueta(id, data);
        } catch (err) {
            showErrorAlert(err);
        }
    }

    // ============================================================================
    // ACTUALIZAR DOM DE ETIQUETA (USANDO SISTEMA CENTRALIZADO)
    // ============================================================================

    function actualizarDOMEtiqueta(id, data) {
        const safeId = id.replace(/\./g, "-");

        // ✅ USAR SISTEMA CENTRALIZADO
        if (typeof window.SistemaDOM !== "undefined") {
            window.SistemaDOM.actualizarEstadoEtiqueta(id, data.estado, {
                peso: data.peso_etiqueta || data.peso_etiqueta_kg,
                nombre: data.nombre,
            });
        } else {
            // Fallback: actualización legacy
            aplicarEstadoAProceso(id, data.estado);
        }

        // Procesar según el estado
        switch ((data.estado || "").toLowerCase()) {
            case "cortando":
                showAlert(
                    "info",
                    "Cortando",
                    "El proceso de corte ha iniciado."
                );
                break;

            case "cortada":
                showAlert(
                    "success",
                    "Etiqueta cortada",
                    "El proceso de corte ha finalizado correctamente."
                );
                scrollToNextDiv(safeId);
                break;

            case "fabricando":
                showAlert(
                    "info",
                    "Fabricando",
                    "El proceso de fabricación ha iniciado."
                );
                break;

            case "fabricada":
                showAlert(
                    "success",
                    "Etiqueta fabricada",
                    "La etiqueta ha sido fabricada exitosamente."
                );
                scrollToNextDiv(safeId);

                // ✅ Agregar automáticamente al carro de paquetes
                if (typeof window.TrabajoPaquete !== "undefined") {
                    window.TrabajoPaquete.agregarItemEtiqueta(id, {
                        id: id,
                        peso: data.peso_etiqueta || data.peso_etiqueta_kg || 0,
                        estado: "fabricada",
                        nombre: data.nombre || "Sin nombre",
                    });
                }
                break;

            case "completada":
                showAlert(
                    "success",
                    "Etiqueta completada",
                    "La etiqueta ha sido procesada exitosamente."
                );

                // 🧹 Limpiar decisión de corte
                if (window._decisionCortePorEtiqueta?.[id]) {
                    delete window._decisionCortePorEtiqueta[id];
                    localStorage.setItem(
                        "decisionCortePorEtiqueta",
                        JSON.stringify(window._decisionCortePorEtiqueta)
                    );
                }

                scrollToNextDiv(safeId);

                // ✅ Agregar automáticamente al carro de paquetes
                if (typeof window.TrabajoPaquete !== "undefined") {
                    window.TrabajoPaquete.agregarItemEtiqueta(id, {
                        id: id,
                        peso: data.peso_etiqueta || data.peso_etiqueta_kg || 0,
                        estado: "completada",
                        nombre: data.nombre || "Sin nombre",
                    });
                }
                break;

            case "ensamblando":
                showAlert(
                    "info",
                    "Ensamblando",
                    "La etiqueta está en proceso de ensamblado."
                );
                break;

            case "ensamblada":
                showAlert(
                    "success",
                    "Etiqueta ensamblada",
                    "El proceso de ensamblado ha finalizado correctamente."
                );
                scrollToNextDiv(safeId);
                break;

            case "soldando":
                showAlert(
                    "info",
                    "Soldando",
                    "La etiqueta está en proceso de soldadura."
                );
                break;

            case "soldada":
                showAlert(
                    "success",
                    "Etiqueta soldada",
                    "El proceso de soldadura ha finalizado correctamente."
                );
                scrollToNextDiv(safeId);
                break;

            default:
                console.warn(
                    `Estado no manejado para etiqueta ${id}: ${data.estado}`
                );
                showAlert(
                    "warning",
                    "Estado desconocido",
                    `El estado recibido (${data.estado}) no está reconocido.`,
                    3000
                );
        }

        // Actualizar stock de productos
        if (data.productos_afectados?.length) {
            data.productos_afectados.forEach((producto) => {
                const pesoStockElemento = document.getElementById(
                    `peso-stock-${producto.id}`
                );
                const progresoTexto = document.getElementById(
                    `progreso-texto-${producto.id}`
                );
                const progresoBarra = document.getElementById(
                    `progreso-barra-${producto.id}`
                );

                if (pesoStockElemento)
                    pesoStockElemento.textContent = `${producto.peso_stock} kg`;
                if (progresoTexto)
                    progresoTexto.textContent = `${producto.peso_stock} / ${producto.peso_inicial} kg`;
                if (progresoBarra)
                    progresoBarra.style.height = `${
                        (producto.peso_stock / producto.peso_inicial) * 100
                    }%`;
            });
        }
    }

    // ============================================================================
    // ESTADOS VISUALES (FALLBACK LEGACY)
    // ============================================================================

    function aplicarEstadoAProceso(etiquetaSubId, estado) {
        const safe = etiquetaSubId.replace(/\./g, "-");
        const proceso = document.getElementById("etiqueta-" + safe);
        if (!proceso) return;

        proceso.dataset.estado = String(estado || "").toLowerCase();

        proceso.className = proceso.className
            .split(" ")
            .filter((c) => !c.startsWith("estado-"))
            .join(" ")
            .trim();

        proceso.classList.add("estado-" + proceso.dataset.estado);

        const contenedor = proceso.querySelector('[id^="contenedor-svg-"]');
        const svg = contenedor?.querySelector("svg");
        if (svg) {
            svg.style.background = getComputedStyle(proceso)
                .getPropertyValue("--bg-estado")
                .trim();
        }
    }

    // ============================================================================
    // NAVEGACIÓN ENTRE ETIQUETAS
    // ============================================================================

    function scrollToNextDiv(currentEtiquetaId) {
        const currentSelector = `#etiqueta-${CSS.escape(currentEtiquetaId)}`;
        const currentDiv = document.querySelector(currentSelector);
        const allDivs = Array.from(document.querySelectorAll(".proceso"));

        const limpiar = (txt) =>
            (txt || "")
                .normalize("NFD")
                .replace(/\p{Diacritic}/gu, "")
                .trim()
                .toLowerCase();

        const leerEstado = (div) => {
            const deData = div.dataset?.estado;
            if (deData) return limpiar(deData);
            const estadoSpan = div.querySelector('[id^="estado-"]');
            return limpiar(
                estadoSpan?.textContent || estadoSpan?.innerText || ""
            );
        };

        const ES_COMPLETADA = new Set([
            "completada",
            "completado",
            "finalizada",
            "finalizado",
            "terminada",
            "terminado",
            "hecha",
            "hecho",
            "empaquetada",
            "en-paquete",
        ]);

        const estaCompletada = (div) => ES_COMPLETADA.has(leerEstado(div));
        const idx = currentDiv ? allDivs.indexOf(currentDiv) : -1;
        const colaBusqueda = idx >= 0 ? allDivs.slice(idx + 1) : allDivs;
        const siguienteDiv = colaBusqueda.find((div) => !estaCompletada(div));

        if (siguienteDiv) {
            siguienteDiv.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
        } else {
            Swal.fire({
                icon: "success",
                title: "¡Perfecto!",
                text: "Has terminado todas las etiquetas de esta planilla.",
                showConfirmButton: true,
            });
        }
    }

    // ============================================================================
    // UTILIDADES
    // ============================================================================

    function showAlert(icon, title, text, timer = 2000) {
        Swal.fire({
            icon,
            title,
            text,
            timer,
            showConfirmButton: false,
        });
    }

    function showErrorAlert(error) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "Ha ocurrido un error inesperado",
        });
    }

    // ✅ EXPONER FUNCIONES PÚBLICAS
    window.actualizarDOMEtiqueta = actualizarDOMEtiqueta;

    console.log("✅ Módulo trabajoEtiqueta.js inicializado correctamente");
});
