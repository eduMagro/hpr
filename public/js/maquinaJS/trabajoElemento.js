document.addEventListener("DOMContentLoaded", function () {
    const procesoElemento = document.getElementById("procesoElemento");
    let maquina_id = document.getElementById("maquina-info").dataset.maquinaId;

    if (!procesoElemento) {
        console.error("Error: No se encontró el input de elemento en el DOM.");
        return;
    }
    procesoElemento.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            let elementoId = this.value.trim();

            if (!elementoId || isNaN(elementoId) || elementoId <= 0) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "❌ ID inválido. Intenta de nuevo.",
                });
                return;
            }

            actualizarElemento(elementoId, maquina_id);
            this.value = ""; // Limpiar input tras lectura
        }
    });
});
async function actualizarElemento(id, maquina_id) {
    let url = `/actualizar-elemento/${id}/maquina/${maquina_id}`;
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
            body: JSON.stringify({ id }),
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
    let estadoElemento = document.getElementById(`estado-${id}`);
    let inicioElemento = document.getElementById(`inicio-${id}`);
    let finalElemento = document.getElementById(`final-${id}`);
    let contenedorEstado = document.getElementById(`contenedor-estado-${id}`);

    if (estadoElemento) estadoElemento.textContent = data.estado;
    if (inicioElemento)
        inicioElemento.textContent = data.fecha_inicio || "No asignada";
    if (finalElemento)
        finalElemento.textContent = data.fecha_finalizacion || "No asignada";

    let stickerExistente = document.querySelector(`#sticker-${id}`);
    if (stickerExistente) {
        stickerExistente.remove();
    }

    if (data.estado === "pendiente") {
        Swal.fire({
            icon: "info",
            title: "Elemento reiniciado",
            text: "Se ha restaurado el elemento a estado pendiente.",
            timer: 2000,
            showConfirmButton: false,
        });
    } else if (data.estado === "fabricando") {
        Swal.fire({
            icon: "info",
            title: "Elemento comenzado",
            text: "Empezamos a fabricar el elemento.",
            timer: 2000,
            showConfirmButton: false,
        });
    } else if (data.estado === "completado") {
        let sticker = document.createElement("span");
        sticker.id = `sticker-${id}`;
        sticker.innerHTML = "✅";
        sticker.style.marginLeft = "10px";
        sticker.style.fontSize = "20px";

        if (contenedorEstado) {
            contenedorEstado.appendChild(sticker);
        } else {
            estadoElemento?.parentNode?.appendChild(sticker);
        }
        Swal.fire({
            icon: "success",
            title: "Elemento completado",
            text: "Hemos terminado de fabricar el elemento.",
            timer: 2000,
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
