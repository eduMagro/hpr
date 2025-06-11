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

    // Validación: campo vacío
    if (!itemCode) {
        Swal.fire({
            icon: "warning",
            title: "Código vacío",
            text: "Por favor, escanea o introduce un código válido de subetiqueta.",
        });
        return;
    }

    // Validación: evitar duplicados
    if (items.some((i) => i.id === itemCode)) {
        Swal.fire({
            icon: "error",
            title: "Etiqueta duplicada",
            text: "Esta etiqueta ya está en la lista.",
        });
        qrItem.value = "";
        return;
    }

    // Buscar la etiqueta en los datos precargados
    const etiqueta = etiquetasData.find(
        (e) => String(e.codigo).trim() === itemCode
    );

    if (!etiqueta) {
        Swal.fire({
            icon: "error",
            title: "Etiqueta no encontrada",
            text: "No se encontró la etiqueta en los datos disponibles.",
        });
        return;
    }

    // Calcular el peso de la etiqueta
    let peso = 0;
    if (Array.isArray(etiqueta.elementos) && etiqueta.elementos.length > 0) {
        peso = etiqueta.elementos.reduce((total, id) => {
            const el = pesosElementos.find((e) => e.id == id);
            return total + (el ? parseFloat(el.peso) : 0);
        }, 0);
    } else if (etiqueta.pesoTotal) {
        peso = parseFloat(etiqueta.pesoTotal) || 0;
    } else {
        Swal.fire({
            icon: "error",
            title: "Etiqueta incompleta",
            text: "La etiqueta no tiene elementos ni peso definido.",
        });
        return;
    }

    // Agregar a la lista
    const newItem = { id: itemCode, type: "etiqueta", peso };
    items.push(newItem);

    // Limpiar input y actualizar la lista visual
    qrItem.value = "";
    actualizarLista();
}

/***************************************
 * Función: eliminarItem
 * - Elimina un item de la lista por su código.
 ***************************************/
function eliminarItem(itemCode) {
    const index = items.findIndex((i) => i.id === itemCode);
    if (index > -1) {
        items.splice(index, 1);

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
        listItem.textContent = `${item.id} – ${(
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
    console.log("Iniciando la creación del paquete con etiquetas:", items);

    if (items.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin etiquetas",
            text: "No has agregado ninguna etiqueta a la lista.",
        });
        return;
    }

    if (
        typeof maquinaId === "undefined" ||
        typeof ubicacionId === "undefined"
    ) {
        Swal.fire({
            icon: "error",
            title: "Error de configuración",
            text: "No se ha definido correctamente la máquina o la ubicación.",
        });
        return;
    }

    const enviarSolicitudPaquete = (confirmar = false) => {
        const bodyData = {
            items: items.map((item) => ({
                id: item.id, // es el código de subetiqueta
                type: "etiqueta",
            })),
            maquina_id: parseInt(maquinaId),
            ubicacion_id: parseInt(ubicacionId),
            ...(confirmar && { confirmar: true }),
        };

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

    enviarSolicitudPaquete(false)
        .then(async (response) => {
            let data;
            try {
                data = await response.clone().json();
            } catch {
                throw new Error(
                    `Error inesperado del servidor (Código ${response.status})`
                );
            }

            if (!response.ok) {
                throw new Error(
                    data.message || `Error del servidor (${response.status})`
                );
            }

            return data;
        })
        .then((data) => {
            if (data.success) {
                return data;
            }

            if (data.warning) {
                let mensajeAdicional =
                    "Algunos elementos presentan advertencias:";

                if (data.warning.etiquetas_ocupadas?.length) {
                    mensajeAdicional += `<br><br><strong>Etiquetas ya empaquetadas:</strong> ${data.warning.etiquetas_ocupadas.join(
                        ", "
                    )}`;
                }

                if (data.warning.etiquetas_incompletas?.length) {
                    mensajeAdicional += `<br><strong>Etiquetas incompletas:</strong> ${data.warning.etiquetas_incompletas.join(
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
                                    throw new Error(
                                        errData.message || "Error al confirmar."
                                    );
                                });
                            }
                            return response.json();
                        });
                    } else {
                        throw new Error("Operación cancelada por el usuario.");
                    }
                });
            }

            throw new Error(
                data.message || "Error desconocido al crear el paquete."
            );
        })
        .then((data) => {
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    html: `Paquete creado correctamente. <br>Código: <strong>${data.codigo_paquete}</strong><br>
                           <button onclick="generateAndPrintQR('${data.codigo_paquete}', '${data.codigo_planilla}', 'PAQUETE')"
                                   class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">📄 Imprimir QR</button>`,
                }).then(() => {
                    items.length = 0;
                    document.getElementById("itemsList").innerHTML = "";
                    window.location.reload();
                });
            }
        })
        .catch((error) => {
            Swal.fire({
                icon: "error",
                title: "Error en Controlador",
                text: `No se pudo crear el paquete. Detalles: ${error.message}`,
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
