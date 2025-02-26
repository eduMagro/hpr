const items = [];

document.getElementById("qrItem").addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        agregarItem();
    }
});


function agregarItem() {
    const qrItem = document.getElementById("qrItem");
    const itemCode = qrItem.value.trim();
    const itemType = document.getElementById("itemType").value.trim().toLowerCase();

    if (!itemCode) {
        Swal.fire({
            icon: "warning",
            title: "QR Inválido",
            text: "Por favor, escanee un QR válido.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }

    
    if (items.some((i) => i.id === itemCode)) {
        Swal.fire({
            icon: "error",
            title: "Item Duplicado",
            text: "Este item ya ha sido agregado.",
            confirmButtonColor: "#d33",
        });
        qrItem.value = "";
        return;
    }

    const newItem = { id: itemCode, type: itemType};
    items.push(newItem);
   

    actualizarLista();
}

function eliminarItem(itemCode) {
    const index = items.findIndex((i) => i.id === itemCode);
    if (index > -1) {
        items.splice(index, 1);
        actualizarLista();
    }
}

function actualizarLista() {
    const itemsList = document.getElementById("itemsList");

    // Limpiar la lista
    itemsList.innerHTML = "";

    // Volver a renderizar los elementos
    items.forEach((item) => {
        const listItem = document.createElement("li");
        listItem.textContent = `${item.type}: ${item.id}`;
        listItem.dataset.code = item.id;

        const removeButton = document.createElement("button");
        removeButton.textContent = "❌";
        removeButton.className = "ml-2 text-red-600 hover:text-red-800";
        removeButton.onclick = () => eliminarItem(item.id);

        listItem.appendChild(removeButton);
        itemsList.appendChild(listItem);
    });
}

function crearPaquete() {
    if (items.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin Items",
            text: "No has agregado ningún item a la lista.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }

    fetch("/verificar-items", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ items }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (!data.success) {
                let mensajeError = "Los siguientes ítems no están completos:\n";

                if (data.etiquetas_incompletas?.length) {
                    mensajeError += `- Etiquetas: ${data.etiquetas_incompletas.join(", ")}\n`;
                }
                if (data.elementos_incompletos?.length) {
                    mensajeError += `- Elementos: ${data.elementos_incompletos.join(", ")}\n`;
                }
                if (data.subpaquetes_incompletos?.length) {
                    mensajeError += `- Subpaquetes: ${data.subpaquetes_incompletos.join(", ")}\n`;
                }

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: mensajeError.trim() || "Error desconocido.",
                    confirmButtonColor: "#d33",
                });

                throw new Error("Error en verificación de ítems.");
            }

            const ubicacionId =
                document.getElementById("ubicacionInput")?.value || null;

            return fetch("/paquetes", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    items,
                    maquina_id: maquinaId,
                    ubicacion_id: ubicacionId,
                }),
            });
        })
        .then((response) => response.json())
        .then((data) => {
            if (data?.success) {
                Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    html: `Paquete creado con éxito. ID: <strong>${data.paquete_id}</strong> <br>
                        <button onclick="generateAndPrintQR('${data.paquete_id}', '${data.codigo_planilla}', 'PAQUETE')"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">QR</button>`,
                }).then(() => {
                    items.length = 0;
                    document.getElementById("itemsList").innerHTML = "";
                });
            } else {
                throw new Error(
                    data.message || "Error desconocido al crear el paquete."
                );
            }
        })
        .catch((error) => {
            console.error("Error en fetch:", error);
            Swal.fire({
                icon: "error",
                title: "Error en Controlador",
                text: `No se pudo conectar con el controlador. Detalles: ${error.message}`,
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
