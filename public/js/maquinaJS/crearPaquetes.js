// Aseguramos que las variables precargadas están disponibles
const etiquetasData = window.etiquetasData || []; // Ej.: [{ codigo: "7318", elementos: [27906,27907], pesoTotal: 81.68 }, ...]
const pesosElementos = window.pesosElementos || []; // Ej.: [{ id: 27906, peso: '77.81' }, { id: 27907, peso: '3.87' }, ...]
const subpaquetesData = window.subpaquetesData || []; // Ej.: [{ id: 'sub001', peso: '77.81' }, { id: 'sub002', peso: '3.87' }, ...]

const items = [];

document
    .getElementById("qrItem")
    .addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            console.log("Se presionó Enter en el input QR");
            agregarItem();
        }
    });

function agregarItem() {
    const qrItem = document.getElementById("qrItem");
    const itemCode = qrItem.value.trim();
    const itemType = document
        .getElementById("itemType")
        .value.trim()
        .toLowerCase();
    let peso = 0;

    console.log("Intentando agregar item:", { itemCode, itemType });

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

    if (itemType === "etiqueta") {
        // Buscar la etiqueta en etiquetasData
        const etiqueta = etiquetasData.find(
            (e) => String(e.codigo) === String(itemCode)
        );
        if (etiqueta) {
            // Si la etiqueta tiene un array de elementos, se suman los pesos de cada uno
            if (
                Array.isArray(etiqueta.elementos) &&
                etiqueta.elementos.length > 0
            ) {
                peso = etiqueta.elementos.reduce((total, elementoId) => {
                    // Convertir ambos a string para comparar de forma consistente
                    const elementoObj = pesosElementos.find(
                        (item) => String(item.id) === String(elementoId)
                    );
                    return (
                        total + (elementoObj ? parseFloat(elementoObj.peso) : 0)
                    );
                }, 0);
            } else if (etiqueta.pesoTotal) {
                peso = parseFloat(etiqueta.pesoTotal) || 0;
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Etiqueta sin datos",
                    text: "La etiqueta no tiene elementos asociados ni un peso total definido.",
                    confirmButtonColor: "#d33",
                });
                return;
            }
        } else {
            Swal.fire({
                icon: "error",
                title: "Etiqueta no encontrada",
                text: "No se encontró la etiqueta en los datos precargados.",
                confirmButtonColor: "#d33",
            });
            return;
        }
    } else if (itemType === "elemento") {
        const elementoObj = pesosElementos.find(
            (item) => String(item.id).trim() === String(itemCode).trim()
        );
        if (elementoObj) {
            peso = parseFloat(elementoObj.peso) || 0;
        } else {
            Swal.fire({
                icon: "error",
                title: "Elemento no encontrado",
                text: "No se encontró el elemento en los datos precargados.",
                confirmButtonColor: "#d33",
            });
            return;
        }
    } else if (itemType === "subpaquete") {
        // Para subpaquetes, buscamos el objeto en subpaquetesData por su id (código)
        const subpaqueteObj = subpaquetesData.find(
            (item) => item.id == itemCode
        );
        if (subpaqueteObj) {
            peso = parseFloat(subpaqueteObj.peso) || 0;
        } else {
            Swal.fire({
                icon: "error",
                title: "Subpaquete no encontrado",
                text: "No se encontró el subpaquete o no tiene un peso asociado.",
                confirmButtonColor: "#d33",
            });
            return;
        }
    } else {
        // Si se requiere otro comportamiento para otros tipos, se podría leer el valor del input
        const itemPesoInput = document.getElementById("itemPeso");
        peso = itemPesoInput ? parseFloat(itemPesoInput.value.trim()) : 0;
    }

    const newItem = { id: itemCode, type: itemType, peso: peso };
    items.push(newItem);
    console.log("Item agregado. Lista actualizada:", newItem);

    qrItem.value = "";
    // Limpiamos el input de peso solo si es un elemento (no para etiquetas o subpaquetes, cuyo peso se determina automáticamente)
    if (itemType !== "etiqueta" && itemType !== "subpaquete") {
        const itemPesoInput = document.getElementById("itemPeso");
        if (itemPesoInput) itemPesoInput.value = "";
    }
    actualizarLista();
}

function eliminarItem(itemCode) {
    console.log("Eliminando item:", itemCode);
    const index = items.findIndex((i) => i.id === itemCode);
    if (index > -1) {
        items.splice(index, 1);
        console.log("Item eliminado. Lista actualizada:", items);
        actualizarLista();
    }
}

function actualizarLista() {
    console.log("Actualizando la lista visual de items");
    const itemsList = document.getElementById("itemsList");
    itemsList.innerHTML = "";

    items.forEach((item) => {
        const listItem = document.createElement("li");
        listItem.textContent = `${item.type}: ${item.id} - Peso: ${(
            parseFloat(item.peso) || 0
        ).toFixed(2)} kg`;
        listItem.dataset.code = item.id;

        const removeButton = document.createElement("button");
        removeButton.textContent = "❌";
        removeButton.className = "ml-2 text-red-600 hover:text-red-800";
        removeButton.onclick = () => eliminarItem(item.id);

        listItem.appendChild(removeButton);
        itemsList.appendChild(listItem);
    });

    // Calcular y mostrar el peso total
    const totalPeso = items.reduce(
        (acc, item) => acc + (parseFloat(item.peso) || 0),
        0
    );
    const totalItem = document.createElement("li");
    totalItem.textContent = `Total de peso: ${totalPeso.toFixed(2)} kg`;
    totalItem.style.fontWeight = "bold";
    itemsList.appendChild(totalItem);
}

function crearPaquete() {
    console.log("Iniciando la creación del paquete con items:", items);
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
        .then((response) => {
            console.log("Respuesta de /verificar-items:", response);
            return response.json();
        })
        .then((data) => {
            console.log("Datos recibidos de /verificar-items:", data);
            if (!data.success) {
                let mensajeError = "Los siguientes ítems no están completos:\n";
                if (data.etiquetas_incompletas?.length) {
                    mensajeError += `- Etiquetas: ${data.etiquetas_incompletas.join(
                        ", "
                    )}\n`;
                }
                if (data.elementos_incompletos?.length) {
                    mensajeError += `- Elementos: ${data.elementos_incompletos.join(
                        ", "
                    )}\n`;
                }
                if (data.subpaquetes_incompletos?.length) {
                    mensajeError += `- Subpaquetes: ${data.subpaquetes_incompletos.join(
                        ", "
                    )}\n`;
                }
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: mensajeError.trim() || "Error desconocido.",
                    confirmButtonColor: "#d33",
                });
                return;
            }

            // Suponiendo que maquinaId y ubicacionId están definidos en otro lugar
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
        .then((response) => {
            console.log("Respuesta de /paquetes:", response);
            return response.json();
        })
        .then((data) => {
            console.log("Datos recibidos de /paquetes:", data);
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
                    console.log(
                        "Lista de items reiniciada después de crear el paquete."
                    );
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
        console.log(
            "Botón crearPaqueteBtn encontrado, asignando evento click."
        );
        crearPaqueteBtn.addEventListener("click", crearPaquete);
    } else {
        console.error("El botón #crearPaqueteBtn no existe en el DOM.");
    }
});
