// Declarar la variable globalmente para que esté disponible en todo el script
let etiquetaTimers = {};

document.addEventListener("DOMContentLoaded", () => {
    if (window.__trabajoEtiquetaInit) return; // 👈 guard
    window.__trabajoEtiquetaInit = true;

    // --- CLICK EN BOTÓN FABRICAR: hace lo mismo que escanear + Enter ---
    document.addEventListener("click", async (ev) => {
        const btn = ev.target.closest(".btn-fabricar");
        if (!btn) return;
        ev.preventDefault();

        // ✅ leer maquinaId en el momento del click (evita cortar el bootstrap)
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

        // ─────────────────────────────────────────────
        //  A) MÁQUINAS DE BARRA → SIEMPRE VÍA PATRONES
        // ─────────────────────────────────────────────
        if (esMaquinaBarra) {
            // 🧠 Si ya está fabricando y hay una decisión previa, la usamos directamente
            if (esFabricando && window._decisionCortePorEtiqueta?.[id]) {
                await enviarAFabricacionOptimizada({
                    ...window._decisionCortePorEtiqueta[id],
                    csrfToken,
                    etiquetaId: id,
                });
                return;
            }

            while (true) {
                let decision;
                try {
                    decision = await mejorCorteSimple(id, diametro, csrfToken);
                } catch (err) {
                    showErrorAlert(err);
                    return;
                }

                if (!decision) return;

                if (decision.accion === "optimizar") {
                    let outcome;
                    try {
                        outcome = await mejorCorteOptimizado(
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

                        await enviarAFabricacionOptimizada({
                            ...window._decisionCortePorEtiqueta[id],
                            csrfToken,
                            etiquetaId: id,
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

                    await enviarAFabricacionOptimizada({
                        ...window._decisionCortePorEtiqueta[id],
                        csrfToken,
                        etiquetaId: id,
                    });
                    return;
                }
            }
        }

        // ─────────────────────────────────────────────
        //  B) MÁQUINAS NO BARRA → flujo clásico (PUT)
        // ─────────────────────────────────────────────
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

    /** Selector de longitudes con botones + “Buscar compañero de corte” */
    async function mejorCorteSimple(id, diametro, csrfToken) {
        const res = await fetch(`/etiquetas/${id}/patron-corte-simple`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ diametro }),
        });

        const data = await res.json();
        if (!res.ok)
            throw new Error(
                data?.message || "No se pudieron calcular los patrones."
            );
        if (!data?.patrones?.length)
            throw new Error("No hay longitudes válidas para este diámetro.");

        const cards = data.patrones
            .map((p) => {
                const color =
                    p.aprovechamiento >= 98
                        ? "text-green-600"
                        : p.aprovechamiento >= 90
                        ? "text-yellow-500"
                        : "text-red-600";

                const icono = p.disponible_en_maquina ? "✅" : "❌";

                return `
  <button type="button"
    class="opcion-longitud w-full text-left border rounded p-4 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 transition"
    data-longitud="${p.longitud_m}">
    <div class="flex items-center gap-2">
      <span class="font-semibold">${p.longitud_m} m</span>
      <span>${icono}</span>
    </div>
    <div class="mt-1">🧩 <em>${p.patron}</em></div>
    <div>🪵 Sobra: <span class="font-medium">${p.sobra_cm} cm</span></div>
    <div>📈 <span class="font-bold ${color}">${p.aprovechamiento}%</span></div>
  </button>`;
            })
            .join("");

        // ⬇️ contenedor con dos columnas en pantallas medianas
        const html = `
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    ${cards}
  </div>
  <div class="pt-4 md:col-span-2">
    <button id="btn-patron-corte-optimizado" type="button"
      class="w-full md:w-auto inline-flex items-center gap-2 rounded px-4 py-2 border text-sm font-medium hover:bg-gray-50">
      🤝 Buscar compañero de corte
    </button>
  </div>
`;

        return new Promise(async (resolve) => {
            const dlg = await Swal.fire({
                title: "Elige longitud de barra",
                html,
                width: "48rem",
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: "Cancelar",
                allowOutsideClick: false,
                didOpen: () => {
                    // ✅ seguir pudiendo scrollear el fondo
                    document.documentElement.style.overflowY = "auto";
                    document.body.style.overflowY = "auto";

                    // ✅ click en una longitud -> fabricar
                    document
                        .querySelectorAll(".opcion-longitud")
                        .forEach((btn) => {
                            btn.addEventListener("click", () => {
                                resolve({
                                    accion: "fabricar_patron_simple",
                                    longitud_m: parseFloat(
                                        btn.dataset.longitud
                                    ),
                                });
                                Swal.close();
                            });
                        });

                    // ✅ click en optimizar -> buscar compañero
                    document
                        .getElementById("btn-patron-corte-optimizado")
                        ?.addEventListener("click", () => {
                            resolve({
                                accion: "optimizar",
                                patrones: data.patrones,
                            });
                            Swal.close();
                        });

                    // ✅ modal arrastrable
                    makeSwalDraggable(".swal2-title");
                },
                didClose: () => {
                    document.body.style.userSelect = "";
                },
            });

            // si llega aquí es que pulsó Cancelar o cerró con ESC
            if (dlg.isDismissed) resolve(null);
        });
    }

    // ✅ ÚNICO: unifica el envío al backend para cualquier caso (k=1 o k>1)
    async function enviarAFabricacionOptimizada2({
        longitudBarraCm,
        etiquetas,
        csrfToken,
    }) {
        const cuerpoPeticion = {
            producto_base: { longitud_barra_cm: Number(longitudBarraCm) },
            repeticiones: 1,
            etiquetas: etiquetas, // [{ etiqueta_sub_id, elementos: [] }, ...]
        };

        const resp = await fetch(`/etiquetas/fabricacion-optimizada`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(cuerpoPeticion),
        });

        const data = await resp.json();

        if (!resp.ok) {
            Swal.fire({
                icon: "error",
                title: "Error al fabricar",
                text: data?.message ?? "Error inesperado.",
            });
            return;
        }

        await Swal.fire({
            icon: "success",
            title: "Fabricación iniciada",
            text: data?.message || "Todo en marcha.",
            allowOutsideClick: false,
        });

        window.location.reload();
    }
    async function enviarAFabricacionOptimizada({
        longitudBarraCm,
        etiquetas,
        csrfToken,
        etiquetaId = null, // opcional, si es k=1 actualizamos DOM sin recargar
    }) {
        const cuerpoPeticion = {
            producto_base: { longitud_barra_cm: Number(longitudBarraCm) },
            repeticiones: 1,
            etiquetas, // [{ etiqueta_sub_id, elementos: [] }, ...]
        };

        const resp = await fetch(`/etiquetas/fabricacion-optimizada`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(cuerpoPeticion),
        });

        const data = await resp.json();

        if (!resp.ok || data.success === false) {
            Swal.fire({
                icon: "error",
                title: "Error al fabricar",
                text: data?.message ?? data?.error ?? "Error inesperado.",
            });
            return;
        }

        // Mensaje contextual según estado devuelto
        const estado = (data.estado || "").toLowerCase();
        const esFabricando = estado === "fabricando";
        const esCierre = estado === "fabricada" || estado === "completada";

        // Precheck (primer clic): avisa al operario si faltan kg y ya se solicitó recarga
        const faltanKg = data?.metricas?.precheck?.kg_faltantes ?? 0;
        const recargaId = data?.metricas?.precheck?.recarga_id ?? null;

        if (esFabricando) {
            // k=1 → actualizamos DOM en sitio sin recargar
            if (etiquetaId) {
                actualizarDOMEtiqueta(etiquetaId, data);
                return;
            }

            // k>1 → recargamos (más fácil para pintar varias etiquetas)
            window.location.reload();
            return;
        }

        if (esCierre) {
            if (etiquetaId) {
                actualizarDOMEtiqueta(etiquetaId, data);
                return;
            }

            window.location.reload();
            return;
        }

        // Fallback
        await Swal.fire({
            icon: "info",
            title: "Respuesta recibida",
            text: `Estado: ${data.estado ?? "N/D"}`,
        });
        if (etiquetaId) actualizarDOMEtiqueta(etiquetaId, data);
    }

    /** Corte optimizado: soporta patrones de 2..K cortes.
     *  Devuelve:
     *   - { accion: "fabricar", longitudBarraCm, etiquetas }  -> payload para enviarAFabricacionOptimizada
     *   - "volver" | null                                      -> control de flujo
     */
    async function mejorCorteOptimizado(id, diametro, patrones, csrfToken) {
        const resp = await fetch(`/etiquetas/${id}/patron-corte-optimizado`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ kmax: 5 }),
        });

        const data = await resp.json();
        if (!resp.ok)
            throw new Error(data?.message || "No se pudo optimizar el corte.");

        const top = Array.isArray(data.top_global) ? data.top_global : [];
        const tieneTop = top.length > 0;

        let html = "";
        if (tieneTop) {
            html += `<div class="space-y-2">`;

            top.forEach((p, idx) => {
                const cls =
                    p.aprovechamiento >= 98
                        ? "text-green-600"
                        : p.aprovechamiento >= 90
                        ? "text-yellow-500"
                        : "text-red-600";

                const secuencia = Array.isArray(p.etiquetas)
                    ? p.etiquetas.join(" + ")
                    : "";

                const esquema =
                    p.esquema ||
                    (() => {
                        const map = { [id]: "A" };
                        let code = "B".charCodeAt(0);
                        return (Array.isArray(p.etiquetas) ? p.etiquetas : [])
                            .map((sid) => {
                                if (!map[sid])
                                    map[sid] = String.fromCharCode(code++);
                                return map[sid];
                            })
                            .join(" + ");
                    })();

                html += `
<label class="opcion-patron flex items-start gap-2 p-2 border rounded-md cursor-pointer hover:bg-gray-50"
       data-idx="${idx}">
  <input type="radio" name="patronElegido" value="${idx}" ${
                    idx === 0 ? "checked" : ""
                }/>
  <div class="text-sm leading-snug w-full">
    <div class="font-semibold">Barra: ${p.longitud_barra_cm} cm</div>
    <div class="mt-1">🧩 Esquema: <strong>${esquema}</strong></div>
    <div>🔗 Secuencia: ${secuencia}</div>
    <div>📈 Aprovechamiento: <span class="font-bold ${cls}">${Number(
                    p.aprovechamiento
                ).toFixed(2)}%</span></div>
  </div>
</label>`;
            });

            html += `</div>`;
        } else {
            html += data?.html_resumen || "<em>No hay patrones ≥98%.</em>";
        }

        const dlg = await Swal.fire({
            icon: tieneTop ? "question" : "info",
            title: tieneTop ? "Corte Optimizado" : "Sin Top ≥98%",
            html,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: tieneTop ? "Fabricar patrón" : "Cerrar",
            denyButtonText: "Volver",
            cancelButtonText: "Cancelar",
            allowOutsideClick: false,
            backdrop: false,
            scrollbarPadding: false,
            heightAuto: false,
            didOpen: () => {
                document.documentElement.style.overflowY = "auto";
                document.body.style.overflowY = "auto";
                if (typeof makeSwalDraggable === "function")
                    makeSwalDraggable(".swal2-title");

                // Cada card es clicable: abre elementos (sustituyendo al antiguo botón)
                document.querySelectorAll(".opcion-patron").forEach((card) => {
                    card.addEventListener("click", () => {
                        const idx = parseInt(card.dataset.idx, 10);
                        const patron = top[idx];
                        if (!patron) return;

                        if (
                            Array.isArray(patron.grupos) &&
                            patron.grupos.length
                        ) {
                            mostrarModalPatron(patron.grupos);
                        }
                    });
                });
            },
        });

        if (dlg.isConfirmed && tieneTop) {
            const seleccionado =
                document.querySelector('input[name="patronElegido"]:checked')
                    ?.value ?? "0";
            const patron = top[parseInt(seleccionado, 10)] ?? top[0];

            const longitudBarraCm = Number(patron.longitud_barra_cm || 0);
            if (!longitudBarraCm)
                throw new Error(
                    "Longitud de barra no válida en el patrón seleccionado."
                );

            // ✅ Usa la secuencia real
            const etiquetas =
                Array.isArray(patron.etiquetas) && patron.etiquetas.length
                    ? patron.etiquetas.map((subid) => ({
                          etiqueta_sub_id: subid,
                          elementos: [],
                      }))
                    : [{ etiqueta_sub_id: id, elementos: [] }];

            // 👉 devolvemos el payload; la petición se hace en actualizarEtiqueta()
            return { accion: "fabricar", longitudBarraCm, etiquetas };
        }

        if (dlg.isDenied) return "volver";
        return null;
    }

    function mostrarModalPatron(grupos) {
        const contenedor = document.getElementById("contenedorPatron");
        contenedor.innerHTML = "";

        const cola = [];
        let finalizado = false; // 👈 guard para evitar doble “finalización”

        (grupos || []).forEach((grupo) => {
            const wrap = document.createElement("div");
            wrap.className = "border rounded-md p-2 bg-gray-50";
            wrap.innerHTML =
                "<p class='text-sm text-gray-400'>Cargando etiqueta…</p>";
            contenedor.appendChild(wrap);

            fetch("/etiquetas/render", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({
                    id: grupo.etiqueta.id,
                    maquina_tipo: window.MAQUINA_TIPO,
                }),
            })
                .then((resp) => resp.json())
                .then((data) => {
                    wrap.innerHTML = data.html;

                    // Normaliza por si falta `dimensiones` (opcional)
                    const elementosNormalizados = (grupo.elementos || []).map(
                        (el) => {
                            const e = { ...el };
                            let dims = (e.dimensiones ?? "").trim();
                            if (!dims) {
                                const L = Number(
                                    e.longitud_cm ?? e.longitud ?? e.cm ?? 0
                                );
                                if (L > 0) dims = String(L);
                            }
                            e.dimensiones = dims;
                            return e;
                        }
                    );

                    cola.push({
                        etiqueta: { id: grupo.etiqueta.id },
                        elementos: elementosNormalizados,
                    });

                    // ⚠️ Ejecuta la “finalización” SOLO UNA VEZ
                    if (!finalizado && cola.length === (grupos?.length || 0)) {
                        finalizado = true; // 👈 marca
                        window.elementosAgrupadosScript = cola;

                        // Repaint del canvas (siempre una sola vez)
                        if (window.__repaintTimer)
                            clearTimeout(window.__repaintTimer);
                        window.__repaintTimer = setTimeout(() => {
                            document.dispatchEvent(
                                new Event("DOMContentLoaded")
                            );
                        }, 0);
                    }
                })
                .catch((err) => {
                    wrap.innerHTML = `<p class="text-red-500">Error cargando etiqueta: ${err}</p>`;
                });
        });

        document.getElementById("modalPatron").classList.remove("hidden");
    }

    // ---- Drag para SweetAlert2 ----
    function makeSwalDraggable(handleSelector = ".swal2-title") {
        const popup = Swal.getPopup?.();
        const container = Swal.getContainer?.();
        if (!popup || !container) return;

        const handle = popup.querySelector(handleSelector) || popup;
        popup.style.position = "fixed";
        popup.style.margin = 0;
        popup.style.transform = "none";
        popup.style.left = "50%";
        popup.style.top = "25%";
        handle.style.cursor = "move";

        let startX,
            startY,
            startLeft,
            startTop,
            dragging = false;

        const onPointerDown = (e) => {
            const evt = e.touches?.[0] || e;
            dragging = true;
            const rect = popup.getBoundingClientRect();
            startX = evt.clientX;
            startY = evt.clientY;
            startLeft = rect.left;
            startTop = rect.top;

            document.body.style.userSelect = "none";
            window.addEventListener("mousemove", onPointerMove);
            window.addEventListener("mouseup", onPointerUp);
            window.addEventListener("touchmove", onPointerMove, {
                passive: false,
            });
            window.addEventListener("touchend", onPointerUp);
        };

        const onPointerMove = (e) => {
            if (!dragging) return;
            const evt = e.touches?.[0] || e;
            if (e.cancelable) e.preventDefault();

            const dx = evt.clientX - startX;
            const dy = evt.clientY - startY;

            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const rect = popup.getBoundingClientRect();
            let left = startLeft + dx;
            let top = startTop + dy;

            left = Math.max(8, Math.min(left, vw - rect.width - 8));
            top = Math.max(8, Math.min(top, vh - rect.height - 8));

            popup.style.left = left + "px";
            popup.style.top = top + "px";
        };

        const onPointerUp = () => {
            dragging = false;
            document.body.style.userSelect = "";
            window.removeEventListener("mousemove", onPointerMove);
            window.removeEventListener("mouseup", onPointerUp);
            window.removeEventListener("touchmove", onPointerMove);
            window.removeEventListener("touchend", onPointerUp);
        };

        handle.addEventListener("mousedown", onPointerDown);
        handle.addEventListener("touchstart", onPointerDown, { passive: true });
    }

    function actualizarDOMEtiqueta(id, data) {
        const safeId = id.replace(/\./g, "-");
        const estadoEtiqueta = document.getElementById(`estado-${safeId}`);
        const inicioEtiqueta = document.getElementById(`inicio-${safeId}`);
        const finalEtiqueta = document.getElementById(`final-${safeId}`);

        if (estadoEtiqueta) estadoEtiqueta.textContent = data.estado;
        if (inicioEtiqueta)
            inicioEtiqueta.textContent = data.fecha_inicio || "N/A";
        if (finalEtiqueta)
            finalEtiqueta.textContent = data.fecha_finalizacion || "N/A";

        if (!data.estado) {
            console.warn(`Estado de etiqueta no válido para ID ${id}:`, data);
            return;
        }

        // ✅ Sincroniza clase + CSS var + dataset (una sola verdad: CSS)
        aplicarEstadoAProceso(id, data.estado);

        function showAlert(icon, title, text, timer = 2000) {
            Swal.fire({ icon, title, text, timer, showConfirmButton: false });
        }

        switch (data.estado.toLowerCase()) {
            case "completada":
                if (etiquetaTimers[id]) {
                    const elapsedTime = Date.now() - etiquetaTimers[id];
                    data.tiempo_fabricacion = elapsedTime;
                    showAlert(
                        "success",
                        "Etiqueta completada",
                        `Tiempo de fabricación: ${elapsedTime} ms`
                    );
                    delete etiquetaTimers[id];
                } else {
                    showAlert(
                        "success",
                        "Etiqueta completada",
                        "Hemos completado la etiqueta."
                    );
                }
                scrollToNextDiv(safeId);
                break;

            case "fabricando":
                if (!etiquetaTimers[id]) {
                    etiquetaTimers[id] = Date.now();
                    showAlert(
                        "info",
                        "Fabricando",
                        "Estamos fabricando los elementos."
                    );
                }
                break;

            case "fabricada":
                showAlert(
                    "info",
                    "Fabricada",
                    "Los Elementos han sido fabricados y los pasamos a otra máquina."
                );
                scrollToNextDiv(safeId);
                break;

            case "ensamblando":
                showAlert(
                    "info",
                    "Ensamblando",
                    "El paquete ha sido enviado a la ensambladora."
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

        if (["completada", "fabricada"].includes(data.estado.toLowerCase())) {
            agregarItemEtiqueta(id, data);
        }

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

    // ✅ Unifica estado visual y CSS variable (lee --bg-estado para el SVG)
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

    // Actualiza la función para recibir el id conocido de la etiqueta
    function agregarItemEtiqueta(etiquetaId, data) {
        const id = data.id || etiquetaId;
        const safeId = id.replace(/\./g, "-");
        if (items.some((item) => item.id === safeId)) {
            console.warn("Etiqueta ya agregada:", safeId);
            return;
        }
        const newItem = { id: id, type: "etiqueta", peso: data.peso || 0 };
        items.push(newItem);
        console.log("Etiqueta agregada automáticamente a la lista:", newItem);
        actualizarLista();
    }

    function actualizarLista() {
        console.log("Actualizando la lista visual de items");
        const itemsList = document.getElementById("itemsList");
        if (!itemsList) {
            console.error("No se encontró el elemento con id 'itemsList'");
            return;
        }
        itemsList.innerHTML = "";

        items.forEach((item) => {
            const listItem = document.createElement("li");
            listItem.textContent = `${item.type}: ${item.id} - Peso: ${item.peso} kg`;
            listItem.dataset.code = item.id;

            const removeButton = document.createElement("button");
            removeButton.textContent = "❌";
            removeButton.className = "ml-2 text-red-600 hover:text-red-800";
            removeButton.onclick = () => eliminarItem(item.id);

            listItem.appendChild(removeButton);
            itemsList.appendChild(listItem);
        });

        const sumatorio = items.reduce(
            (acc, item) => acc + (parseFloat(item.peso) || 0),
            0
        );
        const sumatorioFormateado = sumatorio.toFixed(2);

        const sumatorioItem = document.createElement("li");
        sumatorioItem.textContent = `Total de peso: ${sumatorioFormateado} kg`;
        sumatorioItem.style.fontWeight = "bold";
        itemsList.appendChild(sumatorioItem);
    }
});
