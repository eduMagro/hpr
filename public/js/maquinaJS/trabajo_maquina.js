document.addEventListener("DOMContentLoaded", function () {
    const qrInput = document.getElementById("qrInput");

    if (!qrInput) {
        console.error("Error: No se encontró el input #qrInput en el DOM.");
        return;
    }

    qrInput.addEventListener("keypress", function (e) {
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

            actualizarElemento(etiquetaId);
            this.value = ""; // Limpiar input tras lectura
        }
    });
});

async function actualizarElemento(id) {
    let url = `/actualizar-etiqueta/${id}`;

    try {
        let response = await fetch(url, {
            method: "POST",
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

    if (estadoEtiqueta) estadoEtiqueta.textContent = data.estado;
    if (inicioEtiqueta) inicioEtiqueta.textContent = data.fecha_inicio || "No asignada";
    if (finalEtiqueta) finalEtiqueta.textContent = data.fecha_finalizacion || "No asignada";

    // ✅ Asegurar que hay productos afectados antes de actualizar el DOM
    if (!data.productos_afectados || data.productos_afectados.length === 0) {
        Swal.fire({
            icon: "info",
            title: "Estado actualizado",
            text: "No hubo cambios en los productos. Recargando página...",
            timer: 2000,
            showConfirmButton: false,
            didClose: () => location.reload() // 🔴 Recargar tras la alerta
        });
        return;
    }

    // ✅ Actualizar todos los productos afectados en el DOM
    data.productos_afectados.forEach((producto) => {
        let progresoTexto = document.getElementById(`progreso-texto-${producto.id}`);
        let progresoBarra = document.getElementById(`progreso-barra-${producto.id}`);
        let pesoStockElemento = document.getElementById(`peso-stock-${producto.id}`);

        if (pesoStockElemento) {
            pesoStockElemento.textContent = `${producto.peso_stock} kg`; // ✅ Actualiza visualmente el peso
        }

        if (progresoTexto) {
            progresoTexto.textContent = `${producto.peso_stock} / ${producto.peso_inicial} kg`;
        }

        if (progresoBarra) {
            let progresoPorcentaje = (producto.peso_stock / producto.peso_inicial) * 100;
            progresoBarra.style.height = `${progresoPorcentaje}%`;
        }
    });

    // ✅ Confirmación visual de que se ha actualizado el estado
    Swal.fire({
        icon: "success",
        title: "Actualización exitosa",
        text: "Los productos han sido actualizados.",
        timer: 1500,
        showConfirmButton: false,
    });
}

