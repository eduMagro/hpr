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

        // ────────────────────────────────────────────────
        //  A) MÁQUINAS DE BARRA (SL28 O CORTADORA MANUAL) → VÍA PATRONES (SYNTAX LINE)
        // ────────────────────────────────────────────────
        if ((esMaquinaBarra && esSL28) || esCortadoraManual) {
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
    // FABRICAR CON GRÚA - FLUJO ESPECIAL
    // ============================================================================

    async function fabricarConGrua(etiquetaId, maquinaId, csrfToken) {
        // Paso 1: Pedir escaneo del QR del producto
        const { value: codigoProducto, isConfirmed } = await Swal.fire({
            title: '📦 Escanear producto',
            text: 'Escanea el QR del paquete de material a usar',
            input: 'text',
            inputPlaceholder: 'Código del producto...',
            showCancelButton: true,
            confirmButtonText: 'Siguiente',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#F97316',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes escanear o introducir el código del producto';
                }
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

        // Mostrar info del producto y preguntar uso
        const { value: paqueteCompleto, isConfirmed: confirmado } = await Swal.fire({
            title: '¿Cómo usar el paquete?',
            html: `
                <div class="text-left p-4 bg-gray-100 rounded mb-4">
                    <p><strong>Producto:</strong> ${producto.codigo}</p>
                    <p><strong>Diámetro:</strong> Ø${producto.diametro} mm</p>
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

        // Mostrar resultado
        const pesoConsumido = data.metricas?.peso_consumido || 0;
        await Swal.fire({
            icon: 'success',
            title: '¡Fabricación completada!',
            html: `
                <p>Etiqueta fabricada correctamente.</p>
                <p><strong>Producto usado:</strong> ${producto.codigo}</p>
                <p><strong>Peso consumido:</strong> ${pesoConsumido.toFixed(2)} kg</p>
                ${usarPaqueteCompleto ? '<p class="text-orange-600">El paquete ha sido marcado como consumido.</p>' : ''}
            `,
            timer: 3000,
            showConfirmButton: false,
        });

        actualizarDOMEtiqueta(etiquetaId, data);
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

        // ✅ Actualizar SVG con coladas si están disponibles
        if (data.coladas_por_elemento && typeof window.actualizarSVGConColadas === "function") {
            window.actualizarSVGConColadas(id, data.coladas_por_elemento);
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
