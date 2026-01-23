/**
 * ================================================================================
 * MÓDULO: trabajoEtiqueta.js - VERSIÓN CON SISTEMA CENTRALIZADO
 * ================================================================================
 * ✅ Usa SistemaDOM para actualizar estados
 * ✅ Dispara eventos para sincronización
 * ✅ Control completo de estados de etiquetas
 * ================================================================================
 */

function initTrabajoEtiqueta() {
    if (window.__trabajoEtiquetaInit) return;
    window.__trabajoEtiquetaInit = true;

    // Restaurar decisiones de corte desde localStorage (por si hubo reload)
    try {
        const saved = localStorage.getItem("decisionCortePorEtiqueta");
        if (saved) {
            window._decisionCortePorEtiqueta = JSON.parse(saved);
            console.log("📦 Decisiones de corte restauradas desde localStorage");
        }
    } catch (e) {
        console.warn("No se pudieron restaurar decisiones de corte:", e);
    }

    // console.log("🚀 Inicializando módulo trabajoEtiqueta.js");

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

        // ═══════════════════════════════════════════════════════════════════
        // DETECTAR SI ES UN GRUPO DE ETIQUETAS RESUMIDAS
        // ═══════════════════════════════════════════════════════════════════
        const grupoId = btn.dataset.grupoId;
        if (grupoId) {
            const prevDisabled = btn.disabled;
            btn.disabled = true;
            try {
                await actualizarGrupo(grupoId, maquinaId);
            } finally {
                btn.disabled = prevDisabled;
            }
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
        const esSL28 =
            (window.MAQUINA_CODIGO || "").toUpperCase() === "SL28";
        const esCortadoraManual =
            (window.MAQUINA_TIPO_NOMBRE || "").toLowerCase() === "cortadora_manual";
        const esCortadoraDobladoraBarra =
            (window.MAQUINA_TIPO_NOMBRE || "").toLowerCase() === "cortadora_dobladora_barra";

        console.log("📋 [TrabajoEtiqueta] Tipo máquina:", {
            MAQUINA_TIPO: window.MAQUINA_TIPO,
            MAQUINA_CODIGO: window.MAQUINA_CODIGO,
            MAQUINA_TIPO_NOMBRE: window.MAQUINA_TIPO_NOMBRE,
            esMaquinaBarra,
            esSL28,
            esCortadoraManual,
            esCortadoraDobladoraBarra
        });

        // ────────────────────────────────────────────────
        //  A) MÁQUINAS DE BARRA (SL28, CORTADORA MANUAL O CORTADORA DOBLADORA BARRA) → VÍA PATRONES (SYNTAX LINE)
        // ────────────────────────────────────────────────
        if ((esMaquinaBarra && esSL28) || esCortadoraManual || esCortadoraDobladoraBarra) {
            // Si ya está fabricando (segundo clic), NO pedir desperdicio
            if (esFabricando) {
                // Intentar usar decisión guardada
                if (window._decisionCortePorEtiqueta?.[id]) {
                    await Cortes.enviarAFabricacionOptimizada({
                        ...window._decisionCortePorEtiqueta[id],
                        csrfToken,
                        etiquetaId: id,
                        onUpdate: actualizarDOMEtiqueta,
                        pedirDesperdicio: false,  // Segundo clic: no pedir desperdicio
                    });
                    // Limpiar la decisión después de usar
                    delete window._decisionCortePorEtiqueta[id];
                    localStorage.setItem("decisionCortePorEtiqueta", JSON.stringify(window._decisionCortePorEtiqueta));
                    return;
                } else {
                    // No hay decisión guardada, obtener longitud del producto asignado
                    try {
                        const res = await fetch(`/api/etiquetas/${id}/longitud-asignada`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.longitud_barra_cm) {
                                // Tenemos la longitud, ir directamente a fabricar sin pedir desperdicio
                                await Cortes.enviarAFabricacionOptimizada({
                                    longitudBarraCm: data.longitud_barra_cm,
                                    etiquetas: [{ etiqueta_sub_id: id, elementos: [] }],
                                    csrfToken,
                                    etiquetaId: id,
                                    onUpdate: actualizarDOMEtiqueta,
                                    pedirDesperdicio: false,  // Segundo clic: no pedir desperdicio
                                });
                                return;
                            }
                        }
                    } catch (err) {
                        console.warn("No se pudo obtener longitud asignada:", err);
                    }
                    // Si falló, continúa para mostrar patrones
                }
            }

            while (true) {
                let decision;
                try {
                    console.log("📋 [TrabajoEtiqueta] Llamando a mejorCorteSimple...");
                    decision = await Cortes.mejorCorteSimple(
                        id,
                        diametro,
                        csrfToken
                    );
                    console.log("📋 [TrabajoEtiqueta] Decisión recibida:", decision);
                } catch (err) {
                    console.error("📋 [TrabajoEtiqueta] Error en mejorCorteSimple:", err);
                    showErrorAlert(err);
                    return;
                }

                if (!decision) {
                    console.log("📋 [TrabajoEtiqueta] Sin decisión, saliendo...");
                    return;
                }

                console.log("📋 [TrabajoEtiqueta] Acción:", decision.accion);

                if (decision.accion === "optimizar") {
                    console.log("📋 [TrabajoEtiqueta] Entrando en flujo de optimización...");
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
                        // Obtener desperdicio estimado del patrón optimizado
                        const desperdicioEstimadoCm = outcome.patronInfo?.desperdicio_cm || outcome.patron?.desperdicio_cm || 0;

                        window._decisionCortePorEtiqueta =
                            window._decisionCortePorEtiqueta || {};

                        window._decisionCortePorEtiqueta[id] = {
                            longitudBarraCm: outcome.longitudBarraCm,
                            etiquetas: outcome.etiquetas,
                            desperdicioEstimadoCm,
                        };
                        localStorage.setItem(
                            "decisionCortePorEtiqueta",
                            JSON.stringify(window._decisionCortePorEtiqueta)
                        );

                        const resultado = await Cortes.enviarAFabricacionOptimizada({
                            ...window._decisionCortePorEtiqueta[id],
                            csrfToken,
                            etiquetaId: id,
                            onUpdate: actualizarDOMEtiqueta,
                        });

                        // Si el usuario canceló el modal de desperdicio, volver al inicio
                        if (resultado?.cancelled) {
                            continue;
                        }
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

                    // Obtener desperdicio estimado del patrón seleccionado
                    const desperdicioEstimadoCm = decision.patronInfo?.desperdicio_cm || decision.patron?.sobra_cm || 0;

                    window._decisionCortePorEtiqueta =
                        window._decisionCortePorEtiqueta || {};
                    window._decisionCortePorEtiqueta[id] = {
                        longitudBarraCm,
                        etiquetas: [{ etiqueta_sub_id: id, elementos: [] }],
                        desperdicioEstimadoCm,
                    };
                    localStorage.setItem(
                        "decisionCortePorEtiqueta",
                        JSON.stringify(window._decisionCortePorEtiqueta)
                    );

                    const resultado = await Cortes.enviarAFabricacionOptimizada({
                        ...window._decisionCortePorEtiqueta[id],
                        csrfToken,
                        etiquetaId: id,
                        onUpdate: actualizarDOMEtiqueta,
                    });

                    // Si el usuario canceló el modal de desperdicio, volver al inicio
                    if (resultado?.cancelled) {
                        continue;
                    }
                    return;
                }

                return;
            }
        }

        // ────────────────────────────────────────────────
        //  B) GRÚA → PEDIR ESCANEO DE QR DEL PRODUCTO
        // ────────────────────────────────────────────────
        const esGrua = (window.MAQUINA_TIPO_NOMBRE || "").toLowerCase() === "grua";

        if (esGrua) {
            try {
                await fabricarConGrua(id, maquinaId, csrfToken);
            } catch (err) {
                showErrorAlert(err);
            }
            return;
        }

        // ────────────────────────────────────────────────
        //  C) MÁQUINAS NORMALES → LLAMADA DIRECTA
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
    // ACTUALIZAR GRUPO DE ETIQUETAS RESUMIDAS
    // ============================================================================

    async function actualizarGrupo(grupoId, maquinaId) {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        // Obtener estado actual del grupo
        const grupoCard = document.querySelector(`[data-grupo-id="${grupoId}"] .etiqueta-card`);
        const estadoActual = (grupoCard?.dataset?.estado || "pendiente").toLowerCase();
        const diametro = parseInt(grupoCard?.dataset?.diametro) || 0;

        // Si ya está completada, no hacer nada
        if (["completada", "en-paquete", "empaquetada"].includes(estadoActual)) {
            await Swal.fire({
                icon: "info",
                title: "Grupo ya completado",
                text: `El grupo ya está en estado ${estadoActual}.`,
                timer: 2500,
                showConfirmButton: false,
            });
            return;
        }

        // Detectar tipo de máquina
        const esMaquinaBarra = (window.MAQUINA_TIPO || "").toLowerCase() === "barra";
        const esSL28 = (window.MAQUINA_CODIGO || "").toUpperCase() === "SL28";
        const esCortadoraManual = (window.MAQUINA_TIPO_NOMBRE || "").toLowerCase() === "cortadora_manual";
        const necesitaAsignarProducto = (esMaquinaBarra && esSL28) || esCortadoraManual;

        // Obtener IDs de etiquetas del grupo
        let etiquetasSubIds = [];
        try {
            etiquetasSubIds = JSON.parse(grupoCard?.dataset?.etiquetasSubIds || "[]");
        } catch (e) {
            console.warn("Error parseando etiquetas del grupo:", e);
        }

        const primeraEtiquetaId = grupoCard?.dataset?.primeraEtiquetaId;
        const planillaId = grupoCard?.dataset?.planillaId || window.PLANILLA_ID;

        // ────────────────────────────────────────────────
        // PRIMER CLIC (pendiente -> fabricando): Asignar producto si es SL28/cortadora
        // ────────────────────────────────────────────────
        if (estadoActual === "pendiente" && necesitaAsignarProducto && primeraEtiquetaId) {
            try {
                // Bucle para manejar "volver" desde patrones optimizados
                while (true) {
                    // Mostrar modal de selección de producto usando la primera etiqueta
                    const decision = await Cortes.mejorCorteSimple(primeraEtiquetaId, diametro, csrfToken);

                    if (!decision) {
                        return; // Usuario canceló
                    }

                    // ═══════════════════════════════════════════════════════════════════
                    // CASO: Usuario eligió OPTIMIZAR → Mostrar patrones optimizados
                    // ═══════════════════════════════════════════════════════════════════
                    if (decision.accion === "optimizar") {
                        const outcome = await Cortes.mejorCorteOptimizado(
                            primeraEtiquetaId,
                            diametro,
                            null, // Sin patrones previos, que los calcule
                            csrfToken
                        );

                        if (outcome === "volver") {
                            continue; // Volver al modal simple
                        }

                        if (!outcome || outcome.accion !== "fabricar") {
                            return; // Usuario canceló o cerró
                        }

                        // Fabricar con patrón optimizado
                        const desperdicioEstimadoCm = outcome.patronInfo?.desperdicio_cm || 0;
                        let coladasRecibidas = null;

                        const resultado = await Cortes.enviarAFabricacionOptimizada({
                            longitudBarraCm: outcome.longitudBarraCm,
                            etiquetas: outcome.etiquetas,
                            csrfToken,
                            etiquetaId: primeraEtiquetaId,
                            desperdicioEstimadoCm,
                            onUpdate: (id, data) => {
                                if (data.producto_n_colada) {
                                    coladasRecibidas = {
                                        colada1: data.producto_n_colada,
                                        colada2: data.producto2_n_colada || null
                                    };
                                }
                                actualizarEstadoVisualGrupo(grupoCard, "fabricando", grupoId, coladasRecibidas);
                            },
                            pedirDesperdicio: true,
                        });

                        if (resultado?.cancelled) {
                            continue; // Volver al inicio si canceló desperdicio
                        }

                        actualizarEstadoVisualGrupo(grupoCard, "fabricando", grupoId, coladasRecibidas);
                        showAlert("info", "Fabricando", `Grupo iniciado con patrón optimizado`);
                        return;
                    }

                    // ═══════════════════════════════════════════════════════════════════
                    // CASO: Usuario eligió FABRICAR PATRÓN SIMPLE
                    // ═══════════════════════════════════════════════════════════════════
                    if (decision.accion === "fabricar_patron_simple") {
                        const longitudBarraCm = Math.round(Number(decision.longitud_m || 0) * 100);
                        if (!longitudBarraCm) {
                            showErrorAlert("Longitud de barra no válida.");
                            return;
                        }

                        const desperdicioEstimadoCm = decision.patronInfo?.desperdicio_cm || decision.patron?.sobra_cm || 0;

                        // Enviar fabricación para TODAS las etiquetas del grupo
                        const etiquetasParaFabricar = etiquetasSubIds.map(id => ({
                            etiqueta_sub_id: id,
                            patron_letras: decision.patron_letras || ""
                        }));

                        let coladasRecibidas = null;

                        const resultado = await Cortes.enviarAFabricacionOptimizada({
                            longitudBarraCm,
                            etiquetas: etiquetasParaFabricar,
                            csrfToken,
                            etiquetaId: primeraEtiquetaId,
                            desperdicioEstimadoCm,
                            onUpdate: (id, data) => {
                                if (data.producto_n_colada) {
                                    coladasRecibidas = {
                                        colada1: data.producto_n_colada,
                                        colada2: data.producto2_n_colada || null
                                    };
                                }
                                actualizarEstadoVisualGrupo(grupoCard, "fabricando", grupoId, coladasRecibidas);
                            },
                            pedirDesperdicio: true,
                        });

                        if (resultado?.cancelled) {
                            continue; // Volver al inicio si canceló desperdicio
                        }

                        actualizarEstadoVisualGrupo(grupoCard, "fabricando", grupoId, coladasRecibidas);
                        showAlert("info", "Fabricando", `Grupo iniciado (${etiquetasSubIds.length} etiquetas)`);
                        return;
                    }

                    // Si llegamos aquí, algo salió mal
                    return;
                }

            } catch (err) {
                showErrorAlert(err);
            }
            return;
        }

        // ────────────────────────────────────────────────
        // SEGUNDO CLIC (fabricando -> completada) o máquinas normales
        // ────────────────────────────────────────────────
        try {
            const res = await fetch(`/api/etiquetas/resumir/${grupoId}/estado`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ maquina_id: maquinaId }),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || "Error al actualizar grupo");
            }

            const nuevoEstado = data.nuevo_estado || "actualizado";
            const etiquetasParaImprimir = data.imprimir_etiquetas || [];

            // Obtener coladas de la respuesta
            const coladasRecibidas = {
                colada1: data.producto_n_colada || null,
                colada2: data.producto2_n_colada || null
            };

            // Actualizar estado visual sin reload (pasar coladas)
            actualizarEstadoVisualGrupo(grupoCard, nuevoEstado, grupoId, coladasRecibidas);

            if (nuevoEstado === "fabricando") {
                const coladaInfo = coladasRecibidas.colada1 ? ` - Colada: ${coladasRecibidas.colada1}` : '';
                showAlert("info", "Fabricando", `Grupo iniciado (${data.etiquetas_actualizadas || 0} etiquetas)${coladaInfo}`);
            } else if (nuevoEstado === "completada") {
                showAlert("success", "¡Completado!", `Grupo completado (${data.etiquetas_actualizadas || 0} etiquetas)`);
            }

            // Si se completó, preguntar si quiere imprimir
            if (nuevoEstado === "completada" && etiquetasParaImprimir.length > 0) {
                const etSubIds = etiquetasParaImprimir.map(e => e.etiqueta_sub_id);

                const resultado = await Swal.fire({
                    icon: "question",
                    title: "Imprimir etiquetas",
                    html: `Se han completado <strong>${etSubIds.length}</strong> etiquetas.<br>¿Deseas imprimirlas ahora?`,
                    showCancelButton: true,
                    confirmButtonText: "Imprimir A6",
                    cancelButtonText: "No imprimir",
                    showDenyButton: true,
                    denyButtonText: "Imprimir A4",
                    confirmButtonColor: "#3085d6",
                    denyButtonColor: "#6c757d",
                });

                if (resultado.isConfirmed || resultado.isDenied) {
                    const modo = resultado.isConfirmed ? "a6" : "a4";

                    // Refrescar y esperar renderizado
                    if (typeof window.refrescarEtiquetasMaquina === "function") {
                        Swal.fire({
                            title: 'Preparando impresión...',
                            html: 'Renderizando etiquetas...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        await window.refrescarEtiquetasMaquina();

                        // Esperar mínimo para renderizado
                        await new Promise(resolve => setTimeout(resolve, 200));

                        Swal.close();
                    }

                    // Imprimir
                    if (typeof window.imprimirEtiquetas === "function") {
                        await window.imprimirEtiquetas(etSubIds, modo);
                    }
                }

                // NO reagrupar después de completar - las etiquetas completadas
                // deben permanecer separadas para poder crear paquetes individuales

                // Añadir etiquetas al carro
                if (window.TrabajoPaquete && typeof window.TrabajoPaquete.validarEtiqueta === 'function') {
                    let agregadas = 0;

                    for (const etSubId of etSubIds) {
                        try {
                            const dataEt = await window.TrabajoPaquete.validarEtiqueta(etSubId);
                            if (dataEt.valida) {
                                const ok = window.TrabajoPaquete.agregarItemEtiqueta(etSubId, dataEt);
                                if (ok) agregadas++;
                            }
                        } catch (e) {
                            console.warn(`No se pudo añadir ${etSubId} al carro:`, e);
                        }
                    }

                    if (agregadas > 0) {
                        showAlert("success", "Añadidas al carro", `${agregadas} etiquetas añadidas al carro`);
                    }
                }

                // Actualizar estado visual del grupo a completada (cambiar color de fondo)
                actualizarEstadoVisualGrupo(grupoCard, "completada", grupoId);
            }

        } catch (err) {
            showErrorAlert(err);
        }
    }

    // Función auxiliar para actualizar el estado visual del grupo sin reload
    function actualizarEstadoVisualGrupo(grupoCard, nuevoEstado, grupoId, coladas = null) {
        if (!grupoCard) return;

        // Actualizar clases CSS
        ["pendiente", "fabricando", "fabricada", "completada", "en-paquete"].forEach((est) => {
            grupoCard.classList.remove(`estado-${est}`);
        });
        grupoCard.classList.add(`estado-${nuevoEstado}`);
        grupoCard.dataset.estado = nuevoEstado;

        // Re-renderizar SVG para actualizar el color de fondo
        const contenedorSvgId = grupoCard.dataset.contenedorSvgId;
        const elementosJson = grupoCard.dataset.elementos;

        if (contenedorSvgId && elementosJson && typeof window.renderizarGrupoSVG === 'function') {
            try {
                const elementos = JSON.parse(elementosJson);

                // Si se recibieron coladas, actualizar los elementos con ellas
                if (coladas) {
                    elementos.forEach(elem => {
                        elem.coladas = {
                            colada1: coladas.colada1 || null,
                            colada2: coladas.colada2 || null,
                            colada3: null
                        };
                    });

                    // Actualizar también el data-elementos para que persista
                    grupoCard.dataset.elementos = JSON.stringify(elementos);
                }

                const grupoData = {
                    id: parseInt(contenedorSvgId),
                    etiqueta: { id: parseInt(contenedorSvgId) },
                    elementos: elementos
                };
                window.renderizarGrupoSVG(grupoData, grupoId);
            } catch (e) {
                console.warn('No se pudo re-renderizar SVG del grupo:', e);
            }
        }
    }

    // ============================================================================
    // FABRICAR CON GRÚA - FLUJO ESPECIAL
    // ============================================================================

    async function fabricarConGrua(etiquetaId, maquinaId, csrfToken) {
        // Obtener diámetro y longitud de la etiqueta para buscar sugerencias
        const diametroEtiqueta = Number(window.DIAMETRO_POR_ETIQUETA?.[etiquetaId] ?? 0);
        const longitudEtiquetaCm = Number(window.LONGITUD_POR_ETIQUETA?.[etiquetaId] ?? 0);
        const longitudEtiquetaM = longitudEtiquetaCm / 100;

        // Cargar sugerencias de productos
        let htmlSugerencias = '';
        try {
            const resSug = await fetch(`/api/productos/sugerencias?diametro=${diametroEtiqueta}&longitud=${longitudEtiquetaM}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (resSug.ok) {
                const dataSug = await resSug.json();
                if (dataSug.productos && dataSug.productos.length > 0) {
                    htmlSugerencias = `
                        <div class="mt-3 mb-2 text-left">
                            <p class="font-semibold text-gray-700 mb-2">📋 Productos disponibles (Ø${diametroEtiqueta}mm x ${longitudEtiquetaM.toFixed(1)}m):</p>
                            <div class="max-h-40 overflow-y-auto border rounded">
                                <table class="w-full text-xs">
                                    <thead class="bg-gray-100 sticky top-0">
                                        <tr>
                                            <th class="px-2 py-1 text-left">Código</th>
                                            <th class="px-2 py-1 text-left">Stock</th>
                                            <th class="px-2 py-1 text-left">Ubicación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${dataSug.productos.map(p => `
                                            <tr class="border-t hover:bg-blue-50 cursor-pointer" onclick="document.getElementById('swal-input-producto').value='${p.codigo}'">
                                                <td class="px-2 py-1 font-mono text-blue-600">${p.codigo}</td>
                                                <td class="px-2 py-1">${p.peso_stock} kg</td>
                                                <td class="px-2 py-1 ${p.ubicacion === 'Sin ubicación' ? 'text-gray-400 italic' : 'text-green-700 font-medium'}">${p.ubicacion}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Haz clic en una fila para seleccionar el producto</p>
                        </div>
                    `;
                } else {
                    htmlSugerencias = `<p class="text-orange-600 text-sm mt-2">⚠️ No hay productos disponibles con Ø${diametroEtiqueta}mm x ${longitudEtiquetaM.toFixed(1)}m</p>`;
                }
            }
        } catch (e) {
            console.warn('No se pudieron cargar sugerencias:', e);
        }

        // Paso 1: Pedir escaneo del QR del producto
        const { value: codigoProducto, isConfirmed } = await Swal.fire({
            title: '📦 Escanear producto',
            html: `
                <p class="mb-3">Escanea el QR del paquete de material a usar</p>
                ${htmlSugerencias}
                <input type="text" id="swal-input-producto" class="swal2-input" placeholder="Código del producto..." autofocus>
            `,
            showCancelButton: true,
            confirmButtonText: 'Siguiente',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#F97316',
            focusConfirm: false,
            preConfirm: () => {
                const val = document.getElementById('swal-input-producto').value;
                if (!val || !val.trim()) {
                    Swal.showValidationMessage('Debes escanear o introducir el código del producto');
                    return false;
                }
                return val.trim();
            }
        });

        if (!isConfirmed || !codigoProducto) return;

        // Paso 2: Buscar el producto por código
        let producto;
        try {
            const res = await fetch(`/api/productos/buscar-por-codigo?codigo=${encodeURIComponent(codigoProducto.trim())}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.message || 'Producto no encontrado');
            }

            producto = await res.json();
        } catch (err) {
            await Swal.fire({
                icon: 'error',
                title: 'Producto no encontrado',
                text: err.message || `No se encontró ningún producto con código: ${codigoProducto}`,
            });
            return;
        }

        // Validar tipo, diámetro y longitud ANTES de mostrar el modal de selección
        const tipoProducto = (producto.tipo || '').toLowerCase();
        const diametroProducto = Number(producto.diametro ?? 0);
        const longitudProducto = Number(producto.longitud ?? 0);

        // Validar que sea tipo barra
        if (tipoProducto && tipoProducto !== 'barra') {
            await Swal.fire({
                icon: 'error',
                title: 'Tipo de producto incorrecto',
                html: `
                    <p>El producto debe ser de tipo <strong>barra</strong>.</p>
                    <div class="mt-3 p-3 bg-red-50 rounded text-left">
                        <p><strong>Producto:</strong> ${producto.codigo}</p>
                        <p><strong>Tipo:</strong> ${producto.tipo || 'N/A'}</p>
                    </div>
                `,
            });
            return;
        }

        // Validar diámetro (debe ser igual)
        if (diametroProducto > 0 && diametroEtiqueta > 0 && Math.abs(diametroProducto - diametroEtiqueta) > 0.1) {
            await Swal.fire({
                icon: 'error',
                title: 'Diámetro incorrecto',
                html: `
                    <p>El diámetro del producto no coincide con el de la etiqueta.</p>
                    <div class="mt-3 p-3 bg-red-50 rounded text-left">
                        <p><strong>Producto:</strong> Ø${diametroProducto} mm</p>
                        <p><strong>Etiqueta requiere:</strong> Ø${diametroEtiqueta} mm</p>
                    </div>
                `,
            });
            return;
        }

        // Validar longitud (debe ser igual, tolerancia de 0.1m)
        if (longitudProducto > 0 && longitudEtiquetaM > 0 && Math.abs(longitudProducto - longitudEtiquetaM) > 0.1) {
            await Swal.fire({
                icon: 'error',
                title: 'Longitud incorrecta',
                html: `
                    <p>La longitud del producto no coincide con la de la etiqueta.</p>
                    <div class="mt-3 p-3 bg-red-50 rounded text-left">
                        <p><strong>Producto:</strong> ${longitudProducto.toFixed(2)} m</p>
                        <p><strong>Etiqueta requiere:</strong> ${longitudEtiquetaM.toFixed(2)} m</p>
                    </div>
                `,
            });
            return;
        }

        // Mostrar info del producto y preguntar uso
        const longitudTexto = producto.longitud ? `${producto.longitud} m` : 'N/A';
        const { value: paqueteCompleto, isConfirmed: confirmado } = await Swal.fire({
            title: '¿Cómo usar el paquete?',
            html: `
                <div class="text-left p-4 bg-gray-100 rounded mb-4">
                    <p><strong>Producto:</strong> ${producto.codigo}</p>
                    <p><strong>Diámetro:</strong> Ø${producto.diametro} mm</p>
                    <p><strong>Longitud:</strong> ${longitudTexto}</p>
                    <p><strong>Stock actual:</strong> ${producto.peso_stock?.toFixed(2) || 0} kg</p>
                    <p><strong>Colada:</strong> ${producto.n_colada || 'N/A'}</p>
                </div>
            `,
            input: 'radio',
            inputOptions: {
                'completo': '📦 Usar paquete completo (consumir todo)',
                'parcial': '✂️ Quitar barras (restar peso de la etiqueta)'
            },
            inputValue: 'completo',
            showCancelButton: true,
            confirmButtonText: 'Fabricar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10B981',
        });

        if (!confirmado) return;

        const usarPaqueteCompleto = paqueteCompleto === 'completo';

        // Paso 3: Enviar a fabricación
        const url = `/actualizar-etiqueta/${etiquetaId}/maquina/${maquinaId}`;

        const res = await fetch(url, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                producto_id: producto.id,
                paquete_completo: usarPaqueteCompleto,
            }),
        });

        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.message || "Error al fabricar");
        }

        // Mostrar resultado brevemente
        const pesoConsumido = data.metricas?.peso_consumido || 0;
        const paqueteCodigo = data.metricas?.paquete_codigo || null;

        await Swal.fire({
            icon: 'success',
            title: '¡Fabricación completada!',
            html: `
                <p>Etiqueta fabricada correctamente.</p>
                <p><strong>Producto usado:</strong> ${producto.codigo}</p>
                <p><strong>Peso consumido:</strong> ${pesoConsumido.toFixed(2)} kg</p>
                ${paqueteCodigo ? `<p><strong>Paquete:</strong> ${paqueteCodigo}</p>` : ''}
                ${usarPaqueteCompleto ? '<p class="text-orange-600">El producto ha sido marcado como consumido.</p>' : ''}
                <p class="mt-2 text-blue-600 font-semibold">Ahora selecciona dónde ubicar el paquete...</p>
            `,
            timer: 2000,
            showConfirmButton: false,
        });

        actualizarDOMEtiqueta(etiquetaId, data);

        // Abrir modal para ubicar el paquete fabricado directamente en el mapa
        if (paqueteCodigo && typeof abrirModalMoverPaquete === 'function') {
            abrirModalMoverPaquete();

            // Pre-rellenar el código del paquete, buscarlo y saltar directamente al mapa
            setTimeout(async () => {
                const inputCodigo = document.getElementById('codigo_paquete_mover');
                if (inputCodigo) {
                    inputCodigo.value = paqueteCodigo;

                    // Buscar el paquete y mostrar directamente el mapa
                    if (typeof buscarPaqueteParaMover === 'function') {
                        await buscarPaqueteParaMover();

                        // Saltar directamente al paso del mapa
                        setTimeout(() => {
                            if (typeof mostrarPasoMapa === 'function') {
                                mostrarPasoMapa();
                            }
                        }, 300);
                    }
                }
            }, 100);
        }
    }

    // ============================================================================
    // ACTUALIZAR DOM DE ETIQUETA (USANDO SISTEMA CENTRALIZADO)
    // ============================================================================

    function actualizarDOMEtiqueta(id, data) {
        const safeId = id.replace(/\./g, "-");

        // 🔍 DEBUG: Ver qué campos vienen en data
        console.log('🔍 DEBUG actualizarDOMEtiqueta - Datos recibidos:', {
            id,
            estado: data.estado,
            peso_etiqueta: data.peso_etiqueta,
            nombre: data.nombre,
            producto_n_colada: data.producto_n_colada,
            producto2_n_colada: data.producto2_n_colada,
            data_completa: data
        });

        // ✅ USAR SISTEMA CENTRALIZADO
        if (typeof window.SistemaDOM !== "undefined") {
            window.SistemaDOM.actualizarEstadoEtiqueta(id, data.estado, {
                peso: data.peso_etiqueta,
                nombre: data.nombre || id,
            });
        } else {
            // Fallback: actualización legacy
            aplicarEstadoAProceso(id, data.estado);
        }

        // ✅ Actualizar colada de la etiqueta en la leyenda del SVG
        if (data.producto_n_colada && window.elementosAgrupadosScript) {
            const grupoIndex = window.elementosAgrupadosScript.findIndex(g =>
                g.etiqueta && String(g.etiqueta.etiqueta_sub_id) === String(id)
            );

            if (grupoIndex !== -1) {
                const grupo = window.elementosAgrupadosScript[grupoIndex];
                grupo.colada_etiqueta = data.producto_n_colada;
                grupo.colada_etiqueta_2 = data.producto2_n_colada || null;

                // Regenerar el SVG para mostrar la colada en la leyenda
                if (typeof window.renderizarGrupoSVG === "function") {
                    window.renderizarGrupoSVG(grupo, grupoIndex);
                    console.log(`✅ SVG regenerado con colada de etiqueta: ${data.producto_n_colada}`);
                }
            }
        }

        // ✅ Actualizar SVG con coladas de elementos si están disponibles
        if (data.coladas_por_elemento && typeof window.actualizarSVGConColadas === "function") {
            window.actualizarSVGConColadas(id, data.coladas_por_elemento);
        }

        // ✅ Habilitar botón deshacer (se creó un nuevo registro en el historial)
        if (typeof window.HistorialEtiquetas !== "undefined") {
            window.HistorialEtiquetas.actualizarBoton(id, true);
        }

        // Procesar según el estado
        switch ((data.estado || "").toLowerCase()) {
            case "pendiente":
                showAlert(
                    "info",
                    "Pendiente",
                    "La etiqueta está pendiente de fabricación."
                );
                break;

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
                        peso_etiqueta: data.peso_etiqueta || 0,
                        estado: "fabricada",
                        nombre: data.nombre || id,
                    });
                }
                // 🔥 VALIDACIÓN MEJORADA: Actualizar coladas en el SVG
                if (
                    data.elementos &&
                    Array.isArray(data.elementos) &&
                    data.elementos.length > 0
                ) {
                    // Verificar que al menos un elemento tenga coladas
                    const tieneColadas = data.elementos.some(
                        (el) =>
                            el.coladas &&
                            (el.coladas.colada1 ||
                                el.coladas.colada2 ||
                                el.coladas.colada3)
                    );

                    if (tieneColadas) {
                        console.log(
                            `🔄 Actualizando coladas para etiqueta ${id}`,
                            data.elementos
                        );
                        actualizarColadasEnSVG(id, data.elementos);
                    } else {
                        console.warn(
                            `⚠️ No se encontraron coladas en elementos para etiqueta ${id}`
                        );
                    }
                } else {
                    console.warn(
                        `⚠️ No se recibieron elementos válidos para actualizar coladas en etiqueta ${id}`
                    );
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
                        peso_etiqueta: data.peso_etiqueta || 0,
                        estado: "completada",
                        nombre: data.nombre || id,
                    });
                }
                // 🔥 VALIDACIÓN MEJORADA: Actualizar coladas en el SVG
                if (
                    data.elementos &&
                    Array.isArray(data.elementos) &&
                    data.elementos.length > 0
                ) {
                    const tieneColadas = data.elementos.some(
                        (el) =>
                            el.coladas &&
                            (el.coladas.colada1 ||
                                el.coladas.colada2 ||
                                el.coladas.colada3)
                    );

                    if (tieneColadas) {
                        console.log(
                            `🔄 Actualizando coladas para etiqueta ${id}`,
                            data.elementos
                        );
                        actualizarColadasEnSVG(id, data.elementos);
                    } else {
                        console.warn(
                            `⚠️ No se encontraron coladas en elementos para etiqueta ${id}`
                        );
                    }
                } else {
                    console.warn(
                        `⚠️ No se recibieron elementos válidos para actualizar coladas en etiqueta ${id}`
                    );
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
                    progresoBarra.style.height = `${(producto.peso_stock / producto.peso_inicial) * 100
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
    // ACTUALIZA las coladas en el SVG de la etiqueta - VERSIÓN MEJORADA
    // ============================================================================
    function actualizarColadasEnSVG(etiquetaId, elementosActualizados) {
        console.log(
            `🔄 Actualizando coladas en SVG para etiqueta ${etiquetaId}`
        );

        // 🔥 VALIDACIÓN 1: Verificar que elementosActualizados sea válido
        if (!elementosActualizados || !Array.isArray(elementosActualizados)) {
            console.error(
                `❌ elementosActualizados no es un array válido para etiqueta ${etiquetaId}`
            );
            return;
        }

        if (elementosActualizados.length === 0) {
            console.warn(
                `⚠️ elementosActualizados está vacío para etiqueta ${etiquetaId}`
            );
            return;
        }

        // 🔥 VALIDACIÓN 2: Verificar que existe window.elementosAgrupadosScript
        if (
            !window.elementosAgrupadosScript ||
            !Array.isArray(window.elementosAgrupadosScript)
        ) {
            console.error(
                `❌ window.elementosAgrupadosScript no está disponible`
            );
            return;
        }

        // Buscar el grupo en window.elementosAgrupadosScript
        const index = window.elementosAgrupadosScript.findIndex(
            (grupo) => grupo.etiqueta?.etiqueta_sub_id === etiquetaId
        );

        if (index === -1) {
            console.warn(`⚠️ No se encontró grupo para etiqueta ${etiquetaId}`);
            return;
        }

        // Actualizar los datos con las nuevas coladas
        window.elementosAgrupadosScript[index].elementos =
            elementosActualizados;

        const grupo = window.elementosAgrupadosScript[index];
        const groupId = grupo.etiqueta?.id;

        // 🔥 VALIDACIÓN 3: Verificar que existe el contenedor SVG
        if (!groupId) {
            console.error(
                `❌ No se pudo obtener groupId para etiqueta ${etiquetaId}`
            );
            return;
        }

        const contenedor = document.getElementById("contenedor-svg-" + groupId);

        if (!contenedor) {
            console.warn(
                `⚠️ No se encontró contenedor SVG para etiqueta ${etiquetaId} (id: ${groupId})`
            );
            return;
        }

        try {
            // Limpiar y regenerar SVG
            const svgExistente = contenedor.querySelector("svg");
            if (svgExistente) {
                svgExistente.remove();
            }

            // Obtener configuración
            const ancho = 600,
                alto = 150;
            const proceso = contenedor.closest(".proceso");
            const svgBg = proceso
                ? getComputedStyle(proceso)
                    .getPropertyValue("--bg-estado")
                    .trim() || "#e5e7eb"
                : "#e5e7eb";

            // Crear nuevo SVG
            const svg = document.createElementNS(
                "http://www.w3.org/2000/svg",
                "svg"
            );
            svg.setAttribute("viewBox", `0 0 ${ancho} ${alto}`);
            svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
            svg.style.width = "100%";
            svg.style.height = "100%";
            svg.style.display = "block";
            svg.style.background = svgBg;

            // Construir leyenda CON coladas
            const legendEntries = (grupo.elementos || []).map(
                (elemento, idx) => {
                    const barras =
                        elemento.barras != null ? elemento.barras : 0;
                    let diametro = "N/A";
                    if (elemento.diametro != null && elemento.diametro !== "") {
                        const dstr = String(elemento.diametro).replace(
                            ",",
                            "."
                        );
                        const mtch = dstr.match(/-?\d+(?:\.\d+)?/);
                        if (mtch) {
                            const dn = parseFloat(mtch[0]);
                            if (isFinite(dn)) diametro = String(Math.round(dn));
                        }
                    }

                    // ✅ Construir texto de coladas
                    const coladas = [];
                    if (elemento.coladas?.colada1)
                        coladas.push(elemento.coladas.colada1);
                    if (elemento.coladas?.colada2)
                        coladas.push(elemento.coladas.colada2);
                    if (elemento.coladas?.colada3)
                        coladas.push(elemento.coladas.colada3);

                    const textColadas =
                        coladas.length > 0 ? ` (${coladas.join(", ")})` : "";

                    return {
                        letter: indexToLetters(idx),
                        text: `Ø${diametro} x${barras}${textColadas}`,
                    };
                }
            );

            // 🔥 VALIDACIÓN 4: Verificar que drawLegendBottomLeft existe
            if (typeof drawLegendBottomLeft === "function") {
                drawLegendBottomLeft(svg, legendEntries, ancho, alto);
            } else {
                console.error(`❌ drawLegendBottomLeft no está definida`);
            }

            // Nota temporal de éxito
            const nota = document.createElementNS(
                "http://www.w3.org/2000/svg",
                "text"
            );
            nota.setAttribute("x", ancho / 2);
            nota.setAttribute("y", alto / 2);
            nota.setAttribute("text-anchor", "middle");
            nota.setAttribute("fill", "#059669");
            nota.setAttribute("font-size", "14");
            nota.setAttribute("font-weight", "600");
            nota.textContent = "✓ Coladas actualizadas";
            svg.appendChild(nota);

            contenedor.appendChild(svg);

            console.log(
                `✅ SVG actualizado con coladas para etiqueta ${etiquetaId}`
            );

            // Eliminar nota después de 2 segundos
            setTimeout(() => {
                if (nota && nota.parentNode) {
                    nota.remove();
                }
            }, 2000);
        } catch (error) {
            console.error(
                `❌ Error al actualizar SVG para etiqueta ${etiquetaId}:`,
                error
            );

            // 🔥 OPCIONAL: Mostrar alerta al usuario
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "warning",
                    title: "Advertencia",
                    text: "Las coladas se guardaron pero hubo un problema al actualizar la visualización.",
                    timer: 3000,
                    showConfirmButton: false,
                });
            }
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

    // console.log("✅ Módulo trabajoEtiqueta.js inicializado correctamente");
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTrabajoEtiqueta);
} else {
    initTrabajoEtiqueta();
}
document.addEventListener("livewire:navigated", initTrabajoEtiqueta);
