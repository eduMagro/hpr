const etiquetas = [];

document
    .getElementById("qrEtiqueta")
    .addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            // Detecta cuando se presiona "Enter"
            event.preventDefault(); // Evita que se recargue la página si el input está en un formulario
            agregarEtiqueta();
        }
    });

function agregarEtiqueta() {
    const qrEtiqueta = document.getElementById("qrEtiqueta");
    const etiqueta = qrEtiqueta.value.trim();

    console.log("Valor escaneado:", qrEtiqueta.value);
    console.log("Valor procesado:", etiqueta);

    if (!etiqueta) {
        Swal.fire({
            icon: "warning",
            title: "QR Inválido",
            text: "Por favor, escanee un QR válido.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }

    if (etiquetas.includes(etiqueta)) {
        Swal.fire({
            icon: "error",
            title: "Etiqueta Duplicada",
            text: "Esta etiqueta ya ha sido agregada.",
            confirmButtonColor: "#d33",
        });
        qrEtiqueta.value = "";
        return;
    }

    etiquetas.push(etiqueta);

    const etiquetasList = document.getElementById("etiquetasList");
    const listItem = document.createElement("li");
    listItem.textContent = etiqueta;

    const removeButton = document.createElement("button");
    removeButton.textContent = "❌";
    removeButton.className = "ml-2 text-red-600 hover:text-red-800";
    removeButton.onclick = () => {
        etiquetas.splice(etiquetas.indexOf(etiqueta), 1);
        etiquetasList.removeChild(listItem);
    };

    listItem.appendChild(removeButton);
    etiquetasList.appendChild(listItem);

    qrEtiqueta.value = "";
}

function crearPaquete() {
    if (etiquetas.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin Etiquetas",
            text: "No hay etiquetas para crear un paquete.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }

    const ubicacionId =
        document.getElementById("ubicacionInput")?.value || null;

    console.log("Enviando datos al servidor...");
    console.log("Etiquetas:", etiquetas);
    console.log("Ubicación ID:", ubicacionId);

    fetch("/paquetes", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            etiquetas,
            ubicacion_id: ubicacionId,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("Respuesta del servidor:", data);

            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    text: `Paquete creado con éxito. ID: ${data.paquete_id}`,
                    confirmButtonColor: "#28a745",
                });

                etiquetas.length = 0;
                document.getElementById("etiquetasList").innerHTML = "";
            } else {
                let errorMessage = data.message; // Definir la variable antes de modificarla
                // 🔹 Mostrar etiquetas con paquete_id asignado
                if (
                    data.etiquetas_ocupadas &&
                    data.etiquetas_ocupadas.length > 0
                ) {
                    errorMessage += `\nEtiquetas ocupadas: ${data.etiquetas_ocupadas.join(
                        ", "
                    )}`;
                }
                // 🔹 Mostrar elementos incompletos
                if (
                    data.elementos_incompletos &&
                    data.elementos_incompletos.length > 0
                ) {
                    errorMessage += `\n\nElementos incompletos:\n${data.elementos_incompletos
                        .map(
                            (el) =>
                                `ID: ${el.id} | Etiqueta: ${el.etiqueta_id} | Estado: ${el.estado}`
                        )
                        .join("\n")}`;
                }
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: errorMessage,
                    confirmButtonColor: "#d33",
                });
            }
        })
        .catch((error) => {
            console.error("Error en fetch:", error);
            Swal.fire({
                icon: "error",
                title: "Error en conexión",
                text: "Hubo un problema con el servidor. Inténtalo más tarde.",
                confirmButtonColor: "#d33",
            });
        });
}

document.addEventListener("DOMContentLoaded", function () {
    const crearPaqueteBtn = document.getElementById("crearPaqueteBtn");
    if (crearPaqueteBtn) {
        crearPaqueteBtn.addEventListener("click", crearPaquete);
    } else {
        console.error("El botón #crearPaqueteBtn no existe en el DOM.");
    }
});
