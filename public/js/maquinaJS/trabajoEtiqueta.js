/**
 * ================================================================================
 * MÓDULO: trabajoEtiqueta.js
 * ================================================================================
 * Gestión del proceso de fabricación de etiquetas:
 * - Inicio y fin de fabricación
 * - Estados de etiquetas
 * - Actualización de stock
 * - Integración con sistema de cortes/patrones
 * ================================================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    if (window.__trabajoEtiquetaInit) return; // 👈 guard
    window.__trabajoEtiquetaInit = true;

    console.log("🚀 Inicializando módulo trabajoEtiqueta.js");

    // ============================================================================
    // CLICK EN BOTÓN FABRICAR
    // ============================================================================

    document.addEventListener("click", async (ev) => {
        const btn = ev.target.closest(".btn-fabricar");
        if (!btn) return;
        ev.preventDefault();

        // ✅ leer maquinaId en el momento del click
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

            if (["completada", "en-paquete"].includes(estadoActual)) {
                await Swal.fire({
                    icon: "info",
                    title: "Etiqueta ya completada",
                    text: `La etiqueta ${etiquetaId} ya está en estado ${estadoActual}.`,
                    timer: 2500,
                    showConfirmButton: false,
                });
                return; // 🔥 Detenemos aquí para no lanzar flujos de corte ni fabricación
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
            // 🧠 Si ya está fabricando y hay una decisión previa, la usamos directamente
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
                        // 💾 Guardamos la decisión del primer clic
                        window._decisionCortePorEtiqueta =
                            window._decisionCortePorEtiqueta || {};

                        window._decisionCortePorEtiqueta[id] = {
                            longitudBarraCm: outcome.longitudBarraCm,
                            etiquetas: outcome.etiquetas,
                        };

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

                    // 💾 Guardamos la decisión del primer clic
                    window._decisionCortePorEtiqueta =
                        window._decisionCortePorEtiqueta || {};
                    window._decisionCortePorEtiqueta[id] = {
                        longitudBarraCm,
                        etiquetas: [{ etiqueta_sub_id: id, elementos: [] }],
                    };

                    await Cortes.enviarAFabricacionOptimizada({
                        ...window._decisionCortePorEtiqueta[id],
                        csrfToken,
                        etiquetaId: id,
                        onUpdate: actualizarDOMEtiqueta,
                    });
                    return;
                }
            }
        }

        // ────────────────────────────────────────────────
        //  B) MÁQUINAS NO BARRA → flujo clásico (PUT)
        // ────────────────────────────────────────────────
        try {
            const response = await fetch(url, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({}),
            });

            const data = await response.json();

            if (!response.ok || data.success === false) {
                await Swal.fire({
                    icon: "error",
                    title: "Error al fabricar",
                    text: data?.message || "Ha ocurrido un error.",
                });
                return;
            }

            if (data.success) {
                if (Array.isArray(data.warnings) && data.warnings.length) {
                    await Swal.fire({
                        icon: "warning",
                        title: "Atención",
                        html: data.warnings.join("<br>"),
                        confirmButtonText: "OK",
                        allowOutsideClick: false,
                    });
                }
                actualizarDOMEtiqueta(id, data);
            }
        } catch (error) {
            await Swal.fire({
                icon: "error",
                title: "Error inesperado",
                text: error.message || String(error),
            });
        }
    }

    // ============================================================================
    // ALERTAS Y FEEDBACK
    // ============================================================================

    function showAlert(icon, title, text, timer = 2000) {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            },
        });

        Toast.fire({ icon, title, text });
    }

    function showErrorAlert(error) {
        const mensaje =
            error?.message || error || "Ocurrió un error inesperado.";
        Swal.fire({
            icon: "error",
            title: "Ha ocurrido un error",
            text: `Problemas: ${mensaje}`,
            footer: "Por favor, inténtalo de nuevo o contacta al soporte si el problema continua.",
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: "Cerrar",
            showDenyButton: true,
            denyButtonText: "Reportar Error",
        }).then((result) => {
            if (result.isDenied) notificarProgramador(mensaje);
        });
    }

    // ============================================================================
    // ACTUALIZACIÓN DEL DOM
    // ============================================================================

    function actualizarDOMEtiqueta(id, data) {
        const safeId = id.replace(/\./g, "-");
        const estado = (data.estado || "").toLowerCase();

        aplicarEstadoAProceso(id, estado);

        switch (estado) {
            case "pendiente":
                showAlert(
                    "info",
                    "Pendiente",
                    "La etiqueta está esperando a ser procesada."
                );
                break;

            case "fabricando":
                showAlert(
                    "info",
                    "Fabricando",
                    "La etiqueta está en proceso de fabricación.",
                    5000
                );
                break;

            case "fabricada":
                showAlert(
                    "success",
                    "Etiqueta fabricada",
                    "El proceso de fabricación ha finalizado correctamente."
                );
                scrollToNextDiv(safeId);

                // ✅ Agregar automáticamente al carro de paquetes
                if (typeof window.TrabajoPaquete !== "undefined") {
                    window.TrabajoPaquete.agregarItemEtiqueta(id, {
                        id: id,
                        peso: data.peso || data.peso_kg || 0,
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
                scrollToNextDiv(safeId);

                // ✅ Agregar automáticamente al carro de paquetes
                if (typeof window.TrabajoPaquete !== "undefined") {
                    window.TrabajoPaquete.agregarItemEtiqueta(id, {
                        id: id,
                        peso: data.peso || data.peso_kg || 0,
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
        } else {
            console.warn(
                "No se encontraron productos afectados en la respuesta."
            );
        }
    }

    // ============================================================================
    // ESTADOS VISUALES
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
            console.info(
                "Estados vistos:",
                allDivs.map((d, i) => ({ i, id: d.id, estado: leerEstado(d) }))
            );
            Swal.fire({
                icon: "success",
                title: "¡Perfecto!",
                text: "Has terminado todas las etiquetas de esta planilla.",
                showConfirmButton: true,
            });
        }
    }

    console.log("✅ Módulo trabajoEtiqueta.js inicializado correctamente");
});
