const etiquetas = [];

document
    .getElementById("qrEtiqueta")
    .addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            agregarEtiqueta();
        }
    });

function agregarEtiqueta() {
    const qrEtiqueta = document.getElementById("qrEtiqueta");
    const etiqueta = qrEtiqueta.value.trim();

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
            text: "No has metido etiquetas en la lista.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }
    // ------------------------------------ VERIFICAR ETIQUETAS
    fetch("/verificar-etiquetas", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({ etiquetas }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (!data.success) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: `Las siguientes etiquetas no están completas: ${data.etiquetas_incompletas.join(
                        ", "
                    )}`,
                    confirmButtonColor: "#d33",
                });
                return;
            }

            const ubicacionId =
                document.getElementById("ubicacionInput")?.value || null;
            // ------------------------------------ PAQUETES
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
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Éxito",
                            html: `Paquete creado con éxito. ID: <strong>${data.paquete_id}</strong> <br>
                                
                       <button
    onclick="generateAndPrintQR('${data.paquete_id}', '${data.codigo_planilla}', 'PAQUETE')"
    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
    QR
</button>
`,
                            confirmButtonColor: "#28a745",
                        }).then(() => {
                            window.location.reload(); // Recargar la página
                        });
                        etiquetas.length = 0;
                        document.getElementById("etiquetasList").innerHTML = "";
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: data.message,
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
        })
        .catch((error) => {
            console.error("Error en fetch:", error);
            Swal.fire({
                icon: "error",
                title: "Error en conexión",
                text: "No se pudo verificar el estado de las etiquetas.",
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
