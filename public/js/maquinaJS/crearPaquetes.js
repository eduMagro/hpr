/***************************************
 * Variables Globales Precargadas
 ***************************************/
const etiquetasData = window.etiquetasData || []; // Ej.: [{ codigo: "7318", elementos: [27906,27907], pesoTotal: 81.68 }, ...]
const pesosElementos = window.pesosElementos || []; // Ej.: [{ id: 27906, peso: '77.81' }, { id: 27907, peso: '3.87' }, ...]
const items = [];

/***************************************
 * Asignación de Eventos
 ***************************************/
// Escucha el evento de tecla "Enter" en el input QR
document
    .getElementById("qrItem")
    .addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            console.log("Se presionó Enter en el input QR");
            agregarItem();
        }
    });

/***************************************
 * Función: agregarItem
 * - Valida el QR y el tipo de item.
 * - Calcula el peso según sea una etiqueta o un elemento.
 * - Muestra mensajes de error completos en cada validación.
 ***************************************/
function agregarItem() {
    const qrItem = document.getElementById("qrItem");
    const itemCode = qrItem.value.trim();
    const itemType = document
        .getElementById("itemType")
        .value.trim()
        .toLowerCase();
    let peso = 0;

    console.log("Intentando agregar item:", { itemCode, itemType });

    // Validación: QR vacío
    if (!itemCode) {
        Swal.fire({
            icon: "warning",
            title: "QR Inválido",
            text: "Por favor, escanee un QR válido.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }

    // Validación: Evitar duplicados
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

    // Procesamiento según tipo de item
    if (itemType === "etiqueta") {
        const etiqueta = etiquetasData.find((e) => {
            console.log(
                "Comparando",
                String(e.codigo),
                "con",
                String(itemCode)
            );
            return String(e.codigo).trim() === String(itemCode).trim();
        });

        if (etiqueta) {
            // Si la etiqueta tiene un array de elementos, se calcula el peso sumando cada uno
            if (
                Array.isArray(etiqueta.elementos) &&
                etiqueta.elementos.length > 0
            ) {
                peso = etiqueta.elementos.reduce((total, elementoId) => {
                    const elementoObj = pesosElementos.find(
                        (item) => String(item.id) === String(elementoId)
                    );
                    return (
                        total + (elementoObj ? parseFloat(elementoObj.peso) : 0)
                    );
                }, 0);
            } else if (etiqueta.pesoTotal) {
                // Si no hay elementos, se usa el peso total definido en la etiqueta
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
        // Para tipo 'elemento', se busca el objeto en los datos precargados
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
    } else {
        // Otros tipos: se utiliza el input de peso manual
        const itemPesoInput = document.getElementById("itemPeso");
        peso = itemPesoInput ? parseFloat(itemPesoInput.value.trim()) : 0;
    }

    // Se crea el nuevo item y se agrega al arreglo
    const newItem = { id: itemCode, type: itemType, peso: peso };
    items.push(newItem);
    console.log("Item agregado. Lista actualizada:", newItem);

    // Reiniciar el valor del input QR y, si corresponde, del input de peso
    qrItem.value = "";
    if (itemType !== "etiqueta") {
        const itemPesoInput = document.getElementById("itemPeso");
        if (itemPesoInput) itemPesoInput.value = "";
    }
    actualizarLista();
}

/***************************************
 * Función: eliminarItem
 * - Elimina un item de la lista por su código.
 ***************************************/
function eliminarItem(itemCode) {
    console.log("Eliminando item:", itemCode);
    const index = items.findIndex((i) => i.id === itemCode);
    if (index > -1) {
        items.splice(index, 1);
        console.log("Item eliminado. Lista actualizada:", items);
        actualizarLista();
    }
}

/***************************************
 * Función: actualizarLista
 * - Actualiza el DOM para reflejar la lista actual de items.
 * - Calcula y muestra el peso total.
 ***************************************/
function actualizarLista() {
    console.log("Actualizando la lista visual de items");
    const itemsList = document.getElementById("itemsList");
    itemsList.innerHTML = "";

    items.forEach((item) => {
        const listItem = document.createElement("li");
        listItem.textContent = `${item.type.toUpperCase()}: ${
            item.id
        } - Peso: ${(parseFloat(item.peso) || 0).toFixed(2)} kg`;
        listItem.dataset.code = item.id;

        const removeButton = document.createElement("button");
        removeButton.textContent = "❌";
        removeButton.className = "ml-2 text-red-600 hover:text-red-800";
        removeButton.onclick = () => eliminarItem(item.id);

        listItem.appendChild(removeButton);
        itemsList.appendChild(listItem);
    });

    const totalPeso = items.reduce(
        (acc, item) => acc + (parseFloat(item.peso) || 0),
        0
    );
    const totalItem = document.createElement("li");
    totalItem.textContent = `Total: ${totalPeso.toFixed(2)} kg`;
    totalItem.style.fontWeight = "bold";
    itemsList.appendChild(totalItem);
}

/***************************************
 * Función: crearPaquete
 * - Envía la lista de items al servidor para crear el paquete.
 * - Realiza la verificación previa de ítems y maneja cada respuesta del servidor.
 * - Muestra mensajes de error o éxito completos.
 ***************************************/
function crearPaquete() {
    console.log(
        "Iniciando la creación del paquete con items:",
        JSON.stringify(items, null, 2)
    );

    if (items.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin Items",
            text: "No has agregado ningún item a la lista.",
            confirmButtonColor: "#3085d6",
        });
        return;
    }

    // Función para enviar la solicitud a /paquetes
    const enviarSolicitudPaquete = (confirmar = false) => {
        const bodyData = {
            items: items.map((item) => ({
                ...item,
                id: item.id,
            })),
            maquina_id: parseInt(maquinaId),
            ubicacion_id: parseInt(ubicacionId),
        };
        if (confirmar) {
            bodyData.confirmar = true;
        }
        return fetch("/paquetes", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(bodyData),
        });
    };

    // Se realiza la llamada directamente a /paquetes
    enviarSolicitudPaquete(false)
        .then(async (response) => {
            let data;
            try {
                data = await response.clone().json(); // Intentamos parsear JSON
            } catch (jsonError) {
                // Si no es JSON, interpretamos como error grave
                throw new Error(
                    `Error inesperado del servidor (Código ${response.status})`
                );
            }

            if (!response.ok) {
                throw new Error(
                    data.message ||
                        `Error del servidor (Código ${response.status})`
                );
            }
            return data;
        })
        .then((data) => {
            if (data.success) {
                return data;
            } else if (data.warning) {
                // Si se recibe warning, se muestra una confirmación al usuario
                let mensajeAdicional =
                    data.warning.message ||
                    "Algunos elementos ya están asignados a otro paquete.";
                if (
                    data.warning.elementos_ocupados &&
                    data.warning.elementos_ocupados.length > 0
                ) {
                    mensajeAdicional += `<br><br><strong>Elementos en un paquete:</strong> ${data.warning.elementos_ocupados.join(
                        ", "
                    )}`;
                }
                return Swal.fire({
                    icon: "warning",
                    title: "Advertencia",
                    html: mensajeAdicional,
                    showCancelButton: true,
                    confirmButtonText: "Sí, continuar",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                }).then((result) => {
                    if (result.isConfirmed) {
                        return enviarSolicitudPaquete(true).then((response) => {
                            if (!response.ok) {
                                return response.json().then((errData) => {
                                    throw errData;
                                });
                            }
                            return response.json();
                        });
                    } else {
                        throw new Error("Operación cancelada por el usuario.");
                    }
                });
            } else {
                throw new Error(
                    data.message || "Error desconocido al crear el paquete."
                );
            }
        })
        .then((data) => {
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    html: `Paquete creado con éxito. ID: <strong>${data.paquete_id}</strong> <br>
                           <button onclick="generateAndPrintQR('${data.paquete_id}', '${data.codigo_planilla}', 'PAQUETE')"
                                   class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">QR</button>`,
                })
                    .then(() => {
                        // Reinicia la lista de items
                        items.length = 0;
                        document.getElementById("itemsList").innerHTML = "";
                    })
                    .then(() => {
                        window.location.reload();
                    });
            } else {
                throw new Error(
                    data.message || "Error desconocido al crear el paquete."
                );
            }
        })
        .catch((error) => {
            Swal.fire({
                icon: "error",
                title: "Error en Controlador",
                text: `No se pudo crear el paquete. Detalles: ${
                    error.message || JSON.stringify(error)
                }`,
                confirmButtonColor: "#d33",
            });
        });
}

/***************************************
 * Asignación del Evento al Botón
 * - Se asigna el evento click al botón de "Crear Paquete"
 *   cuando el DOM esté completamente cargado.
 ***************************************/
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
