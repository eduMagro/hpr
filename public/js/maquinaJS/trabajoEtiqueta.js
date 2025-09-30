// Declarar la variable globalmente para que esté disponible en todo el script
let etiquetaTimers = {};

document.addEventListener("DOMContentLoaded", () => {
    const maquinaInfo = document.getElementById("maquina-info");
    const maquinaId = maquinaInfo?.dataset?.maquinaId;

    if (!maquinaId) {
        console.error("Error: No se encontró la información de la máquina.");
        return;
    }

    // --- CLICK EN BOTÓN FABRICAR: hace lo mismo que escanear + Enter ---
    document.addEventListener("click", async (ev) => {
        const btn = ev.target.closest(".btn-fabricar");
        if (!btn) return;
        ev.preventDefault();

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

        let longitudSeleccionada = null;

        // Solo para máquinas tipo barra: loop controlado hasta que el usuario fabrique o cancele
        if ((window.MAQUINA_TIPO || "").toLowerCase() === "barra") {
            while (true) {
                let decision;
                try {
                    decision = await mejorCorte(id, diametro, csrfToken);
                } catch (err) {
                    showErrorAlert(err);
                    return; // error irrecuperable
                }

                // Canceló el selector
                if (!decision) return;

                if (decision.accion === "optimizar") {
                    // Mostrar sugerencia y actuar según botón
                    let outcome;
                    try {
                        outcome = await mejorCorteHermano(
                            id,
                            diametro,
                            decision.patrones,
                            csrfToken
                        );
                    } catch (err) {
                        showErrorAlert(err);
                        return;
                    }

                    if (outcome === "fabricado") {
                        // el flujo terminó fabricando vía optimización
                        return;
                    } else if (outcome === "volver") {
                        // volver al selector -> iteración del while
                        continue;
                    } else {
                        // outcome === null => cancelar
                        return;
                    }
                } else if (decision.accion === "fabricar") {
                    longitudSeleccionada = decision.longitud_m;
                    break; // salir del bucle para hacer el PUT de fabricación
                }
            }
        }

        // PUT: fabricar (para barra con longitudSeleccionada o para otras máquinas sin longitud)
        try {
            const response = await fetch(url, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ longitudSeleccionada }),
            });

            const data = await response.json();

            if (!response.ok || data.success === false) {
                await Swal.fire({
                    icon: "error",
                    title: "Error al fabricar",
                    text: data?.message || "Ha ocurrido un error.",
                });
                return; // 👈 salimos aquí, no seguimos con actualizarDOMEtiqueta
            }

            // ✅ flujo normal
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
    async function mejorCorte(id, diametro, csrfToken) {
        const res = await fetch(`/etiquetas/${id}/patron-corte`, {
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
                return `
      <button type="button"
        class="opcion-longitud w-full text-left border rounded p-4 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 transition"
        data-longitud="${p.longitud_m}">
        <div class="flex items-center gap-2">
          <span class="font-semibold">${p.longitud_m} m</span>
        </div>
        <div class="mt-1">🧩 <em>${p.patron}</em></div>
        <div>🪵 Sobra: <span class="font-medium">${p.sobra_cm} cm</span></div>
        <div>📈 <span class="font-bold ${color}">${p.aprovechamiento}%</span></div>
      </button>`;
            })
            .join("");

        const html = `
    <div class="space-y-3">
      ${cards}
      <div class="pt-2">
        <button id="btn-optimizar-corte" type="button"
          class="w-full md:w-auto inline-flex items-center gap-2 rounded px-4 py-2 border text-sm font-medium hover:bg-gray-50">
          🤝 Buscar compañero de corte
        </button>
      </div>
    </div>`;

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

                    // ✅ tu lógica existente: click en una longitud -> fabricar
                    document
                        .querySelectorAll(".opcion-longitud")
                        .forEach((btn) => {
                            btn.addEventListener("click", () => {
                                resolve({
                                    accion: "fabricar",
                                    longitud_m: parseFloat(
                                        btn.dataset.longitud
                                    ),
                                });
                                Swal.close();
                            });
                        });

                    // ✅ tu lógica existente: click en optimizar -> buscar compañero
                    document
                        .getElementById("btn-optimizar-corte")
                        ?.addEventListener("click", () => {
                            resolve({
                                accion: "optimizar",
                                patrones: data.patrones,
                            });
                            Swal.close();
                        });

                    // ✅ NUEVO: hacer el modal arrastrable por el título
                    makeSwalDraggable(".swal2-title");
                },
                didClose: () => {
                    // restaurar por si el drag deshabilitó selección del texto
                    document.body.style.userSelect = "";
                },
            });

            // si llega aquí es que pulsó Cancelar o cerró con ESC (pero outsideClick está bloqueado)
            if (dlg.isDismissed) resolve(null);
        });
    }

    /** Corte optimizado: soporta patrones de 2..K cortes y resalta en la vista */
    /** Corte optimizado: soporta patrones de 2..K cortes y resalta en la vista */
    async function mejorCorteHermano(id, diametro, patrones, csrfToken) {
        const resp = await fetch(`/etiquetas/${id}/optimizar-corte`, {
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
            html += `<div class="mb-2 text-sm text-gray-600">Se muestran los mejores patrones (≥98%) entre todas las longitudes disponibles.</div>`;
            html += `<div class="space-y-2">`;

            top.forEach((p, idx) => {
                const cls =
                    p.aprovechamiento >= 98
                        ? "text-green-600"
                        : p.aprovechamiento >= 90
                        ? "text-yellow-500"
                        : "text-red-600";

                // 🔹 Etiquetas (secuencia completa)
                const etiquetasSecuencia = Array.isArray(p.etiquetas)
                    ? p.etiquetas.join(" + ")
                    : "";

                // 🔹 Etiquetas (resumen por conteo: subid × n)
                const conteo = p.conteo_por_subid || {};
                const etiquetasResumen = Object.keys(conteo)
                    .sort() // orden alfabético estable
                    .map((sid) => {
                        const n = parseInt(conteo[sid] || 0, 10);
                        return n > 1 ? `${sid} × ${n}` : sid;
                    })
                    .join(" + ");

                html += `
<label class="flex items-start gap-2 p-2 border rounded-md cursor-pointer hover:bg-gray-50">
  <input type="radio" name="patronElegido" value="${idx}" ${
                    idx === 0 ? "checked" : ""
                } />
  <div class="text-sm leading-snug">
      <div class="font-semibold">Barra: ${p.longitud_barra_cm} cm · k=${
                    p.k
                } · esquema: ${p.tipo_schema}</div>
      <div>🔹 <strong>${p.patron_humano} cm</strong></div>
      <div>🪵 Sobra: <strong>${Number(p.sobra_cm).toFixed(2)} cm</strong></div>
      <div>📈 Aprovechamiento: <span class="font-bold ${cls}">${Number(
                    p.aprovechamiento
                ).toFixed(2)}%</span></div>
      <div class="mt-1 text-xs text-gray-700">
          <div><span class="font-semibold">Etiquetas (secuencia):</span> ${etiquetasSecuencia}</div>
          <div><span class="font-semibold">Etiquetas (resumen):</span> ${etiquetasResumen}</div>
      </div>
      <button type="button"
          class="btn-ver-elementos px-2 py-1 mt-2 rounded bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs"
          data-idx="${idx}">
          👀 Ver elementos
      </button>
  </div>
</label>`;
            });

            html += `</div>`;
        } else {
            html +=
                data?.html_resumen ||
                "<em>No hay patrones ≥98%. Revisa el resumen por longitudes.</em>";
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
                if (typeof makeSwalDraggable === "function") {
                    makeSwalDraggable(".swal2-title");
                }

                document
                    .querySelectorAll(".btn-ver-elementos")
                    .forEach((btn) => {
                        btn.addEventListener("click", () => {
                            const idx = parseInt(btn.dataset.idx, 10);
                            const patron = top[idx];
                            if (patron && Array.isArray(patron.grupos)) {
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

            const etiquetas = [];
            const conteo = patron.conteo_por_subid || {};
            Object.keys(conteo).forEach((subid) => {
                const n = parseInt(conteo[subid] || 0, 10);
                for (let i = 0; i < n; i++) {
                    etiquetas.push({ etiqueta_sub_id: subid, elementos: [] });
                }
            });
            if (!etiquetas.length)
                etiquetas.push({ etiqueta_sub_id: id, elementos: [] });

            const body = {
                producto_base: { longitud_barra_cm: longitudBarraCm },
                repeticiones: 1,
                etiquetas,
            };

            const fabricarResp = await fetch(
                `/etiquetas/fabricacion-optimizada`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify(body),
                }
            );

            const res = await fabricarResp.json();
            if (!fabricarResp.ok)
                throw new Error(res?.message || "Error al fabricar.");

            await Swal.fire({
                icon: "success",
                title: "Fabricación iniciada",
                text: res?.message || "Todo en marcha.",
                allowOutsideClick: false,
            });

            window.location.reload();
            return "fabricado";
        }

        if (dlg.isDenied) return "volver";
        return null;
    }

    // ---- Drag para SweetAlert2 ----
    function makeSwalDraggable(handleSelector = ".swal2-title") {
        const popup = Swal.getPopup?.();
        const container = Swal.getContainer?.();
        if (!popup || !container) return;

        const handle = popup.querySelector(handleSelector) || popup;
        popup.style.position = "fixed"; // importante para moverlo
        popup.style.margin = 0; // evita recentrados
        popup.style.transform = "none"; // quita translate(-50%, -50%)
        popup.style.left = "50%"; // posición inicial centrada
        popup.style.top = "25%"; // un poco arriba
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

            // desactivar selección mientras arrastras
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

            // límites dentro del viewport
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
        const safeId = id.replace(/\./g, "-"); // ✅ declarar una sola vez
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
                scrollToNextDiv(safeId); // ✅ usa safeId ya declarado
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

        // actualiza data-estado para lectores lógicos
        proceso.dataset.estado = String(estado || "").toLowerCase();

        // quita clases estado-*
        proceso.className = proceso.className
            .split(" ")
            .filter((c) => !c.startsWith("estado-"))
            .join(" ")
            .trim();

        // añade clase estado-<estado>
        proceso.classList.add("estado-" + proceso.dataset.estado);

        // refresca el fondo del svg con la CSS var centralizada
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
        // Si data.id no está definido, usamos el id pasado como argumento (etiquetaId)
        const id = data.id || etiquetaId;
        const safeId = id.replace(/\./g, "-");
        if (items.some((item) => item.id === safeId)) {
            console.warn("Etiqueta ya agregada:", safeId);
            return;
        }
        // Se agrega también el peso, que se extrae de data (o se asigna 0 si no existe)
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
        itemsList.innerHTML = ""; // Limpiar la lista

        // Mostrar cada item en la lista
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

        // Calcular sumatorio de pesos de todas las etiquetas en la lista
        const sumatorio = items.reduce(
            (acc, item) => acc + (parseFloat(item.peso) || 0),
            0
        );
        const sumatorioFormateado = sumatorio.toFixed(2); // Ajusta a dos decimales

        // Mostrar el peso total de los elementos en la lista
        const sumatorioItem = document.createElement("li");
        sumatorioItem.textContent = `Total de peso: ${sumatorioFormateado} kg`;
        sumatorioItem.style.fontWeight = "bold";
        itemsList.appendChild(sumatorioItem);
    }
});
