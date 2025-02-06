// Espera a que el DOM se cargue completamente
document.addEventListener("DOMContentLoaded", () => {
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

        const etiquetaIdNum = parseInt(etiquetaId, 10);

        // Verificar si `etiquetasEnUnaSolaMaquina` está definido antes de usarlo
        if (
            typeof etiquetasEnUnaSolaMaquina !== "undefined" &&
            !etiquetasEnUnaSolaMaquina.includes(etiquetaIdNum)
        ) {
            Swal.fire({
                icon: "warning",
                title: "Acción no permitida",
                text: "Esta etiqueta tiene elementos en otras máquinas. No puedes procesarla.",
            });
            e.target.value = ""; // Limpiar el input tras el error
            return;
        }

        // Verificar el estado actual de la etiqueta en el DOM
        const estadoElemento = document.getElementById(`estado-${etiquetaId}`);
        if (
            estadoElemento &&
            estadoElemento.textContent.trim().toLowerCase() === "completado"
        ) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: "La etiqueta se reiniciará a estado pendiente y se revertirá el consumo. ¿Desea continuar?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, reiníciala",
                cancelButtonText: "Cancelar",
            }).then((result) => {
                if (result.isConfirmed) {
                    actualizarEtiqueta(etiquetaId, maquinaId);
                }
            });
        } else {
            actualizarEtiqueta(etiquetaId, maquinaId);
        }

        e.target.value = ""; // Limpiar input tras procesar
    });
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

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(
                errorData.error ||
                    `Error HTTP ${response.status}: ${response.statusText}`
            );
        }

        const data = await response.json();
        if (data.success) {
            actualizarDOMEtiqueta(id, data);
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

/**
 * Actualiza el DOM según los datos devueltos por el servidor.
 */
function actualizarDOMEtiqueta(id, data) {
    const estadoEtiqueta = document.getElementById(`estado-${id}`);
    const inicioEtiqueta = document.getElementById(`inicio-${id}`);
    const finalEtiqueta = document.getElementById(`final-${id}`);
    const contenedorEstadoEt = document.getElementById(
        `contenedor-estado-et-${id}`
    );

    if (estadoEtiqueta) estadoEtiqueta.textContent = data.estado;
    if (inicioEtiqueta)
        inicioEtiqueta.textContent = data.fecha_inicio || "No asignada";
    if (finalEtiqueta)
        finalEtiqueta.textContent = data.fecha_finalizacion || "No asignada";

    // Verificar que el estado no sea `undefined` antes de procesarlo
    if (!data.estado) {
        console.warn(`Estado de etiqueta no válido para ID ${id}:`, data);
        return;
    }

    // Actualizar el DOM según el estado devuelto
    switch (data.estado.toLowerCase()) {
        case "completada":
            Swal.fire({
                icon: "success",
                title: "Etiqueta completada",
                text: "Hemos terminado de fabricar la etiqueta.",
                timer: 2000,
                showConfirmButton: false,
            });
            break;
        case "fabricando":
            Swal.fire({
                icon: "info",
                title: "Etiqueta comenzada",
                text: "Empezamos a fabricar la etiqueta.",
                timer: 2000,
                showConfirmButton: false,
            });
            break;
        case "pendiente":
            Swal.fire({
                icon: "info",
                title: "Etiqueta reiniciada",
                text: "Hemos reiniciado la etiqueta.",
                timer: 2000,
                showConfirmButton: false,
            });
            break;
        default:
            console.warn(
                `Estado no manejado para etiqueta ${id}:`,
                data.estado
            );
            Swal.fire({
                icon: "warning",
                title: "Estado desconocido",
                text: `El estado recibido (${data.estado}) no está reconocido.`,
                timer: 3000,
                showConfirmButton: false,
            });
    }
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
        console.warn("No se encontraron productos afectados en la respuesta.");
    }
}
