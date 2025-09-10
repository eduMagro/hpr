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
        console.log("Botón fabricar clicado para etiqueta ID:", etiquetaId);
        if (!etiquetaId) {
            Swal.fire({
                icon: "error",
                title: "Etiqueta no válida",
                text: "Falta el ID de etiqueta.",
            });
            return;
        }

        // (Opcional) anti doble clic
        const prevDisabled = btn.disabled;
        btn.disabled = true;
        try {
            await actualizarEtiqueta(etiquetaId, maquinaId);
        } finally {
            btn.disabled = prevDisabled;
        }
    });

    function validarYObtenerLongitudSeleccionada() {
        const checkboxesBarra = document.querySelectorAll(
            "input.checkbox-longitud"
        );
        const longitudesMarcadas = new Set();

        checkboxesBarra.forEach((checkbox) => {
            if (checkbox.dataset.diametro && checkbox.checked) {
                longitudesMarcadas.add(checkbox.value);
            }
        });

        if (longitudesMarcadas.size === 0) {
            Swal.fire({
                icon: "warning",
                title: "Longitud no seleccionada",
                text: "Debes seleccionar una longitud de barra antes de continuar.",
            });
            return null;
        }

        if (longitudesMarcadas.size > 1) {
            Swal.fire({
                icon: "error",
                title: "Demasiadas longitudes seleccionadas",
                text: "Solo puedes seleccionar una única longitud de barra. Revisa tu selección.",
            });
            return null;
        }

        return parseFloat([...longitudesMarcadas][0]);
    }

    async function actualizarEtiqueta(id, maquinaId) {
        const url = `/actualizar-etiqueta/${id}/maquina/${maquinaId}`;
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        let longitudSeleccionada = null;
        if (window.tipoMaquina === "barra") {
            longitudSeleccionada = validarYObtenerLongitudSeleccionada();
            if (longitudSeleccionada === null) return; // ⛔ DETIENE el flujo
        }

        try {
            const response = await fetch(url, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ id, longitud: longitudSeleccionada }),
            });

            let data;
            try {
                data = await response.json();
            } catch {
                throw new Error(
                    "Respuesta del servidor no es JSON válido. Posible error HTML: sesión caducada o ruta incorrecta."
                );
            }

            if (!response.ok) {
                console.error("Respuesta no OK:", response.status, data);
                showErrorAlert(
                    data?.error ||
                        `Error ${response.status}: ${
                            data?.message || "respuesta no válida"
                        }`
                );
                return;
            }

            if (data.success) {
                if (data.warnings?.length) {
                    await Swal.fire({
                        icon: "warning",
                        title: "Atención",
                        html: data.warnings.join("<br>"),
                        confirmButtonText: "OK",
                    });
                }
                actualizarDOMEtiqueta(id, data);
            } else {
                showErrorAlert(data?.message || "Error desconocido");
            }
        } catch (error) {
            showErrorAlert(error);
        }
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
        })
            .then((result) => {
                if (result.isDenied) notificarProgramador(mensaje);
            })
            .then(() => {
                window.location.reload();
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
