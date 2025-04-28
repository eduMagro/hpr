// Declarar la variable globalmente para que esté disponible en todo el script
let etiquetaTimers = {};

document.addEventListener("DOMContentLoaded", () => {
    /*** PROCESO ETIQUETA ***/
    const procesoEtiqueta = document.getElementById("procesoEtiqueta");
    const maquinaInfo = document.getElementById("maquina-info");

    if (!procesoEtiqueta) {
        console.error("Error: No se encontró el input de etiqueta en el DOM.");
        return;
    }

    const maquinaId = maquinaInfo?.dataset?.maquinaId;
    if (!maquinaId) {
        console.error("Error: No se encontró la información de la máquina.");
        return;
    }

    procesoEtiqueta.addEventListener("keypress", (e) => {
        if (e.key !== "Enter") return;
        e.preventDefault();

        const etiquetaId = e.target.value.trim();

        // Validar que el ID sea un número válido mayor que cero
        if (!etiquetaId || isNaN(etiquetaId) || Number(etiquetaId) <= 0) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "❌ ID inválido. Intenta de nuevo.",
            });
            return;
        }

        actualizarEtiqueta(etiquetaId, maquinaId);

        e.target.value = ""; // Limpiar input tras procesar
    });

    /**
     * Envía la solicitud PUT para actualizar la etiqueta en el servidor.
     */
    async function actualizarEtiqueta(id, maquinaId) {
        const url = `/actualizar-etiqueta/${id}/maquina/${maquinaId}`;
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        try {
            const response = await fetch(url, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ id }),
            });
            let data;

            try {
                data = await response.json();
            } catch (jsonError) {
                throw new Error(
                    "Respuesta del servidor no es JSON válido. Posible error HTML: sesión caducada o ruta incorrecta."
                );
            }

            if (!response.ok) {
                showErrorAlert(data.error || "Error desconocido.");
                return;
            }

            if (data.success) {
                if (data.warnings && data.warnings.length > 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Atención",
                        html: data.warnings.join("<br>"),
                        confirmButtonText: "OK",
                    }).then(() => {
                        // Actualiza el DOM y, si corresponde, agrega la etiqueta a la lista.
                        actualizarDOMEtiqueta(id, data);
                    });
                } else {
                    actualizarDOMEtiqueta(id, data);
                }
            } else {
                showErrorAlert(error);
            }
        } catch (error) {
            showErrorAlert(error);
        }
    }

    /**
     * Actualiza el DOM según los datos devueltos por el servidor.
     * Si el estado indica que la etiqueta está completada, se agrega automáticamente a la lista.
     */
    function actualizarDOMEtiqueta(id, data) {
        const safeId = id.replace(/\./g, "-"); // Reemplaza "." por "-" para usarlo en IDs HTML
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

        function showAlert(icon, title, text, timer = 2000) {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                timer: timer,
                showConfirmButton: false,
            });
        }

        switch (data.estado.toLowerCase()) {
            case "completada":
                // Si hay un temporizador iniciado, calcular el tiempo transcurrido
                if (etiquetaTimers[id]) {
                    const elapsedTime = Date.now() - etiquetaTimers[id];
                    // Se puede asignar el valor al objeto data para utilizarlo en el backend o mostrarlo
                    data.tiempo_fabricacion = elapsedTime;
                    showAlert(
                        "success",
                        "Etiqueta completada",
                        `Tiempo de fabricación: ${elapsedTime} ms`
                    );
                    // Aquí podrías hacer otra petición para guardar el tiempo en la base de datos si lo requieres
                    // await actualizarTiempoFabricacion(id, elapsedTime);55555555555555555555555555555555555555555555555555
                    // Una vez calculado, se elimina el temporizador de la etiqueta
                    delete etiquetaTimers[id];
                } else {
                    showAlert(
                        "success",
                        "Etiqueta completada",
                        "Hemos completado la etiqueta."
                    );
                }
                // 💥 Aquí la clave: safeId
                const safeId = id.replace(/\./g, "-");
                scrollToNextDiv(safeId);
                break;
            case "fabricando":
                // Si no se ha iniciado el temporizador para esta etiqueta, se guarda el instante actual
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
                scrollToNextDiv(id);
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
                scrollToNextDiv(id);
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
                scrollToNextDiv(id);
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

        // Si la etiqueta se encuentra en estado completada o fabricada, agregarla automáticamente a la lista.
        if (
            data.estado.toLowerCase() === "completada" ||
            data.estado.toLowerCase() === "fabricada"
        ) {
            agregarItemEtiqueta(id, data);
        }

        // Si hay información de productos afectados, actualiza sus datos en el DOM.
        if (data.productos_afectados && data.productos_afectados.length > 0) {
            data.productos_afectados.forEach((producto) => {
                let pesoStockElemento = document.getElementById(
                    `peso-stock-${producto.id}`
                );
                let progresoTexto = document.getElementById(
                    `progreso-texto-${producto.id}`
                );
                let progresoBarra = document.getElementById(
                    `progreso-barra-${producto.id}`
                );

                if (pesoStockElemento) {
                    pesoStockElemento.textContent = `${producto.peso_stock} kg`;
                }
                if (progresoTexto) {
                    progresoTexto.textContent = `${producto.peso_stock} / ${producto.peso_inicial} kg`;
                }
                if (progresoBarra) {
                    let progresoPorcentaje =
                        (producto.peso_stock / producto.peso_inicial) * 100;
                    progresoBarra.style.height = `${progresoPorcentaje}%`;
                }
            });
        } else {
            console.warn(
                "No se encontraron productos afectados en la respuesta."
            );
        }
    }
    function scrollToNextDiv(currentEtiquetaId) {
        const safeId = currentEtiquetaId.replace(/\./g, "-"); // 🔹 Corriges los puntos (bien)
        const currentDiv = document.getElementById(`etiqueta-${safeId}`); // 🔹 Buscas el div

        if (!currentDiv) {
            console.warn(
                `No se encontró el div actual para el ID: etiqueta-${safeId}`
            );
            return;
        }

        const allDivs = Array.from(document.querySelectorAll(".proceso")); // 🔹 Agarras todos los divs `.proceso`
        const indexActual = allDivs.indexOf(currentDiv); // 🔹 Buscas la posición

        if (indexActual === -1) {
            console.warn(
                "No se encontró el índice del div actual en la lista de procesos."
            );
            return;
        }

        const siguienteDiv = allDivs[indexActual + 1];
        if (siguienteDiv) {
            siguienteDiv.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
        } else {
            console.info(
                "Última etiqueta alcanzada, no hay más para hacer scroll."
            );
            Swal.fire({
                icon: "success",
                title: "¡Perfecto!",
                text: "Has terminado todas las etiquetas de esta máquina.",
                timer: 2500,
                showConfirmButton: false,
            });
        }
    }

    function showErrorAlert(error) {
        // Se puede extraer el mensaje del error, ya sea error.message o el propio error
        const mensaje =
            error.message || error || "Ocurrió un error inesperado.";
        Swal.fire({
            icon: "error",
            title: "Ha ocurrido un error",
            text: `Ha pasado: ${mensaje}`,
            footer: "Por favor, inténtalo de nuevo o contacta al soporte si el problema persiste.",
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
