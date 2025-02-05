document.addEventListener("DOMContentLoaded", function () {
    const procesoEtiqueta = document.getElementById("procesoEtiqueta");
    let maquina_id = document.getElementById("maquina-info").dataset.maquinaId;

    if (!procesoEtiqueta) {
        console.error("Error: No se encontró el input de etiqueta en el DOM.");
        return;
    }

    procesoEtiqueta.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            let etiquetaId = this.value.trim();

            if (!etiquetaId || isNaN(etiquetaId) || etiquetaId <= 0) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "❌ ID inválido. Intenta de nuevo.",
                });
                return;
            }

            // Si la etiqueta tiene elementos en otras máquinas, no permitir su actualización
            if (!etiquetasEnUnaSolaMaquina.includes(parseInt(etiquetaId))) {
                Swal.fire({
                    icon: "warning",
                    title: "Acción no permitida",
                    text: "Esta etiqueta tiene elementos en otras máquinas. No puedes procesarla.",
                });
                this.value = ""; // Limpiar input tras intento fallido
                return;
            }

            // Verificar el estado actual de la etiqueta (asumiendo que existe un elemento con id "estado-<etiquetaId>")
            let estadoElemento = document.getElementById(
                `estado-${etiquetaId}`
            );
            if (
                estadoElemento &&
                estadoElemento.textContent.trim().toLowerCase() === "completado"
            ) {
                // Mostrar SweetAlert de confirmación antes de reiniciar la etiqueta
                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "La etiqueta se reiniciará a estado pendiente y se revertirá el consumo. ¿Desea continuar?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, reiníciala",
                    cancelButtonText: "Cancelar",
                }).then((result) => {
                    if (result.isConfirmed) {
                        actualizarEtiqueta(etiquetaId, maquina_id);
                    }
                });
            } else {
                // Si no está en estado "completado", se actualiza normalmente
                actualizarEtiqueta(etiquetaId, maquina_id);
            }
            this.value = ""; // Limpiar input tras lectura
        }
    });
});

async function actualizarEtiqueta(id, maquina_id) {
    let url = `/actualizar-etiqueta/${id}/maquina/${maquina_id}`;

    try {
        let response = await fetch(url, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json", // Forzar JSON en la respuesta
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                id,
            }),
        });

        if (!response.ok) {
            let errorData = await response.json();
            throw new Error(
                errorData.error ||
                    `Error HTTP ${response.status}: ${response.statusText}`
            );
        }

        let data = await response.json();
        if (data.success) {
            actualizarDOM(id, data);
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.error || "Ocurrió un error inesperado.",
            });
        }
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "Ocurrió un error en la actualización.",
        });
    }
}

function actualizarDOM(id, data) {
    let estadoEtiqueta = document.getElementById(`estado-${id}`);
    let inicioEtiqueta = document.getElementById(`inicio-${id}`);
    let finalEtiqueta = document.getElementById(`final-${id}`);
    let contenedorEstadoEt = document.getElementById(
        `contenedor-estado-et-${id}`
    ); // Contenedor donde agregar el sticker

    if (estadoEtiqueta) estadoEtiqueta.textContent = data.estado;
    if (inicioEtiqueta)
        inicioEtiqueta.textContent = data.fecha_inicio || "No asignada";
    if (finalEtiqueta)
        finalEtiqueta.textContent = data.fecha_finalizacion || "No asignada";

    // ✅ Quitar stickers previos antes de actualizar el estado
    let stickerExistente = document.querySelector(`#sticker-${id}`);
    if (stickerExistente) {
        stickerExistente.remove();
    }

    // ✅ Si el estado es "completado", agregar un sticker verde
    if (data.estado === "completado") {
        let sticker = document.createElement("span");
        sticker.id = `sticker-${id}`;
        sticker.innerHTML = "✅"; // Puedes cambiarlo por un icono SVG
        sticker.style.marginLeft = "10px"; // Espaciado del sticker
        sticker.style.fontSize = "20px";

        if (contenedorEstado) {
            contenedorEstado.appendChild(sticker);
        } else {
            estadoElemento?.parentNode?.appendChild(sticker);
        }

        Swal.fire({
            icon: "info",
            title: "Etiqueta reiniciada",
            text: "Se ha restaurado la etiqueta a estado pendiente.",
            timer: 2000,
            showConfirmButton: false,
        });
    } else if (data.estado === "fabricando") {
        Swal.fire({
            icon: "info",
            title: "Etiqueta comenzada",
            text: "Empezamos a fabricar la etiqueta.",
            timer: 2000,
            showConfirmButton: false,
        });
    }
    if (data.estado === "completado") {
        Swal.fire({
            icon: "success",
            title: "Etiqueta completada",
            text: "Hemos terminado de fabricar la etiqueta.",
            timer: 2000,
            showConfirmButton: false,
        });
    }

    // ✅ Verificar si hay productos afectados antes de intentar actualizarlos
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

            // ✅ Actualiza visualmente el peso en el DOM
            if (pesoStockElemento) {
                pesoStockElemento.textContent = `${producto.peso_stock} kg`;
            }

            // ✅ Actualiza el texto de progreso
            if (progresoTexto) {
                progresoTexto.textContent = `${producto.peso_stock} / ${producto.peso_inicial} kg`;
            }

            // ✅ Actualiza la barra de progreso
            if (progresoBarra) {
                let progresoPorcentaje =
                    (producto.peso_stock / producto.peso_inicial) * 100;
                progresoBarra.style.height = `${progresoPorcentaje}%`;
            }
        });
    } else {
        console.warn("No se encontraron productos afectados en la respuesta.");
    }
}
