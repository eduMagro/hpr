/**
 * ================================================================================
 * MÓDULO: trabajoPaquete.js
 * ================================================================================
 * Gestión completa del sistema de paquetes:
 * - Validación de etiquetas via QR
 * - Gestión del carro de etiquetas
 * - Creación de paquetes
 * - Eliminación de paquetes
 * ================================================================================
 */

(function (global) {
    "use strict";

    // ============================================================================
    // VARIABLES Y ESTADO
    // ============================================================================

    let items = []; // Array de etiquetas en el carro
    let isInitialized = false;

    // ============================================================================
    // VALIDACIÓN DE ETIQUETAS VIA QR
    // ============================================================================

    /**
     * Valida una etiqueta en el backend
     * @param {string} codigo - Código de la etiqueta
     * @returns {Promise<Object>} - Datos de la etiqueta validada
     */
    async function validarEtiqueta(codigo) {
        const url = `/etiquetas/${encodeURIComponent(
            codigo
        )}/validar-para-paquete`;

        console.log("🔍 Validando etiqueta:", codigo);
        console.log("📡 URL:", url);

        const response = await fetch(url, {
            method: "GET",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content,
            },
        });

        console.log("📊 Status HTTP:", response.status);
        console.log("📊 Status Text:", response.statusText);

        const contentType = response.headers.get("content-type");
        console.log("📊 Content-Type:", contentType);

        // Verificar que la respuesta es JSON
        if (!contentType || !contentType.includes("application/json")) {
            const textResponse = await response.text();
            console.error("❌ Respuesta NO es JSON:", textResponse);

            throw {
                type: "InvalidResponse",
                message: "El servidor no devolvió JSON",
                status: response.status,
                statusText: response.statusText,
                contentType: contentType,
                body: textResponse,
            };
        }

        const data = await response.json();
        console.log("📊 Respuesta JSON:", data);

        // Verificar status HTTP
        if (!response.ok) {
            throw {
                type: "HttpError",
                message: data?.message || data?.motivo || "Error al validar",
                status: response.status,
                statusText: response.statusText,
                data: data,
            };
        }

        return data;
    }

    /**
     * Muestra alert de error con detalles
     * @param {Object} error - Objeto de error
     * @param {string} codigo - Código de la etiqueta
     */
    async function mostrarErrorValidacion(error, codigo) {
        let errorTitle = "Error inesperado";
        let errorMessage = error.message || "Error desconocido";
        let errorDetails = "";

        if (error.type === "InvalidResponse") {
            errorTitle = "Error del servidor";
            errorDetails = `
                <p><strong>Status:</strong> ${error.status} ${
                error.statusText
            }</p>
                <p><strong>Content-Type:</strong> ${
                    error.contentType || "No definido"
                }</p>
                <details>
                    <summary>Ver respuesta completa</summary>
                    <pre style="max-height: 300px; overflow: auto; font-size: 11px;">${error.body.substring(
                        0,
                        1000
                    )}</pre>
                </details>
            `;
        } else if (error.type === "HttpError") {
            errorTitle = `Error ${error.status}`;
            errorMessage = error.message;
            if (error.data?.error) {
                errorDetails = `<p><strong>Error técnico:</strong> ${error.data.error}</p>`;
            }
        } else if (error instanceof TypeError) {
            if (
                error.message.includes("Failed to fetch") ||
                error.message.includes("NetworkError")
            ) {
                errorTitle = "Error de conexión";
                errorMessage = "No se pudo conectar con el servidor";
                errorDetails = `
                    <ul style="text-align: left; font-size: 13px;">
                        <li>Verifica tu conexión a internet</li>
                        <li>El servidor podría estar caído</li>
                        <li>Podría haber un problema con el firewall</li>
                    </ul>
                `;
            }
        }

        await Swal.fire({
            icon: "error",
            title: errorTitle,
            html: `
                <div style="text-align: left;">
                    <p><strong>Mensaje:</strong> ${errorMessage}</p>
                    <p><strong>Código de etiqueta:</strong> ${codigo}</p>
                    ${errorDetails}
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #666;">Ver información técnica</summary>
                        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-size: 11px; overflow: auto; max-height: 200px;">${
                            error.stack || JSON.stringify(error, null, 2)
                        }</pre>
                    </details>
                </div>
            `,
            confirmButtonText: "Cerrar",
            width: "600px",
            showCancelButton: true,
            cancelButtonText: "Copiar error",
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                copiarErrorAlPortapapeles(error, codigo);
            }
        });
    }

    /**
     * Copia información del error al portapapeles
     */
    function copiarErrorAlPortapapeles(error, codigo) {
        const errorInfo = `
Código: ${codigo}
Error: ${error.message}
Tipo: ${error.type || error.constructor.name}
Detalle: ${JSON.stringify(error, null, 2)}
URL: /etiquetas/${codigo}/validar-para-paquete
Timestamp: ${new Date().toISOString()}
        `.trim();

        navigator.clipboard
            .writeText(errorInfo)
            .then(() => {
                if (typeof showAlert === "function") {
                    showAlert(
                        "info",
                        "Copiado",
                        "Error copiado al portapapeles",
                        1500
                    );
                } else {
                    alert("Error copiado al portapapeles");
                }
            })
            .catch(() => {
                alert("No se pudo copiar al portapapeles");
            });
    }

    // ============================================================================
    // GESTIÓN DEL CARRO
    // ============================================================================

    /**
     * Agrega una etiqueta al carro
     * @param {string} codigo - Código de la etiqueta
     * @param {Object} data - Datos de la etiqueta
     */
    function agregarItemEtiqueta(codigo, data) {
        const id = data.id || codigo;
        const safeId = id.replace(/\./g, "-");

        // Verificar si ya está en el carro
        if (items.some((item) => item.id === id || item.id === safeId)) {
            console.warn("⚠️ Etiqueta ya está en el carro:", id);
            return false;
        }

        const newItem = {
            id: id,
            type: "etiqueta",
            peso: parseFloat(data.peso) || 0,
            estado: data.estado || "desconocido",
            nombre: data.nombre || "Sin nombre",
        };

        items.push(newItem);
        console.log("✅ Etiqueta agregada al carro:", newItem);

        actualizarListaVisual();
        return true;
    }

    /**
     * Elimina una etiqueta del carro
     * @param {string} id - ID de la etiqueta
     */
    function eliminarItem(id) {
        const index = items.findIndex((item) => item.id === id);
        if (index !== -1) {
            items.splice(index, 1);
            console.log("🗑️ Etiqueta eliminada del carro:", id);
            actualizarListaVisual();
        }
    }

    /**
     * Limpia todo el carro
     */
    function limpiarCarro() {
        items = [];
        console.log("🧹 Carro limpiado");
        actualizarListaVisual();
    }

    /**
     * Obtiene todas las etiquetas del carro
     * @returns {Array} - Array de etiquetas
     */
    function obtenerItems() {
        return [...items]; // Retorna copia para evitar mutaciones
    }

    /**
     * Calcula el peso total del carro
     * @returns {number} - Peso total en kg
     */
    function calcularPesoTotal() {
        return items.reduce(
            (acc, item) => acc + (parseFloat(item.peso) || 0),
            0
        );
    }

    // ============================================================================
    // ACTUALIZACIÓN DE LA INTERFAZ
    // ============================================================================

    /**
     * Actualiza la lista visual de etiquetas en el DOM
     */
    function actualizarListaVisual() {
        console.log("🔄 Actualizando lista visual del carro");

        const itemsList = document.getElementById("itemsList");
        if (!itemsList) {
            console.error("❌ No se encontró el elemento #itemsList");
            return;
        }

        itemsList.innerHTML = "";

        // Renderizar cada etiqueta
        items.forEach((item) => {
            const listItem = document.createElement("li");
            listItem.className =
                "flex items-center justify-between py-2 px-3 bg-white rounded border mb-2";
            listItem.dataset.code = item.id;

            // Contenido de la etiqueta
            const contentDiv = document.createElement("div");
            contentDiv.className = "flex-1";
            contentDiv.innerHTML = `
                <div class="font-semibold text-gray-800">${item.id}</div>
                <div class="text-sm text-gray-600">
                    <span class="inline-block mr-3">📦 ${item.type}</span>
                    <span class="inline-block mr-3">⚖️ ${item.peso} kg</span>
                    <span class="inline-block">✅ ${item.estado}</span>
                </div>
            `;

            // Botón eliminar
            const removeButton = document.createElement("button");
            removeButton.textContent = "❌";
            removeButton.className =
                "ml-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded px-2 py-1 transition";
            removeButton.title = "Eliminar del carro";
            removeButton.onclick = () => {
                eliminarItem(item.id);
            };

            listItem.appendChild(contentDiv);
            listItem.appendChild(removeButton);
            itemsList.appendChild(listItem);
        });

        // Sumatorio total
        const pesoTotal = calcularPesoTotal();
        const sumatorioItem = document.createElement("li");
        sumatorioItem.className =
            "py-3 px-3 bg-blue-50 rounded border-2 border-blue-200 mt-3";
        sumatorioItem.innerHTML = `
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-blue-900">Total de peso:</span>
                <span class="text-xl font-bold text-blue-600">${pesoTotal.toFixed(
                    2
                )} kg</span>
            </div>
            <div class="text-sm text-blue-700 mt-1">
                ${items.length} etiqueta${
            items.length !== 1 ? "s" : ""
        } en el carro
            </div>
        `;
        itemsList.appendChild(sumatorioItem);
    }

    // ============================================================================
    // CREACIÓN DE PAQUETES
    // ============================================================================
    function obtenerMaquinaId() {
        // Prioriza data-maquina-id en #maquina-info; si no, usa window.maquinaId
        const desdeDom = Number(
            document.getElementById("maquina-info")?.dataset?.maquinaId
        );
        const desdeGlobal = Number(window.maquinaId);
        return Number.isFinite(desdeDom) && desdeDom > 0
            ? desdeDom
            : Number.isFinite(desdeGlobal) && desdeGlobal > 0
            ? desdeGlobal
            : null;
    }

    function obtenerUbicacionId() {
        // Intenta por input/hidden #ubicacion-id; si no, usa window.ubicacionId
        const desdeDom = Number(document.getElementById("ubicacion-id")?.value);
        const desdeGlobal = Number(window.ubicacionId);
        return Number.isFinite(desdeDom) && desdeDom > 0
            ? desdeDom
            : Number.isFinite(desdeGlobal) && desdeGlobal > 0
            ? desdeGlobal
            : null;
    }

    /**
     * Crea un paquete con las etiquetas del carro contra POST /paquetes
     * Respeta el precheck de warnings del backend y reintenta con confirmar=true
     */
    async function crearPaquete() {
        if (items.length === 0) {
            await Swal.fire({
                icon: "warning",
                title: "Carro vacío",
                text: "No hay etiquetas en el carro para crear un paquete",
                confirmButtonText: "OK",
            });
            return;
        }

        const maquinaId = obtenerMaquinaId();
        const ubicacionId = obtenerUbicacionId();

        if (!maquinaId || !ubicacionId) {
            await Swal.fire({
                icon: "error",
                title: "Faltan datos",
                html: `
                <div style="text-align:left">
                    <p>No se pudo determinar <b>máquina</b> o <b>ubicación</b>.</p>
                    <ul style="margin-top:8px;font-size:13px">
                        <li>Debe existir <code>#maquina-info[data-maquina-id]</code> o <code>window.maquinaId</code>.</li>
                        <li>Debe existir <code>#ubicacion-id</code> o <code>window.ubicacionId</code>.</li>
                    </ul>
                </div>
            `,
            });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: "question",
            title: "Crear paquete",
            html: `
            <p>¿Deseas crear un paquete con <strong>${
                items.length
            }</strong> etiqueta${items.length !== 1 ? "s" : ""}?</p>
            <p class="text-gray-600 mt-2">Peso total: <strong>${calcularPesoTotal().toFixed(
                2
            )} kg</strong></p>
        `,
            showCancelButton: true,
            confirmButtonText: "Sí, crear paquete",
            cancelButtonText: "Cancelar",
        });
        if (!confirmacion.isConfirmed) return;

        if (global.customLoader) {
            global.customLoader.show({
                text: "Creando paquete",
                subtext: "Procesando etiquetas...",
                type: "bars",
            });
        }

        // Payload que espera tu store(): items[{id,type}], maquina_id, ubicacion_id
        const payload = {
            items: items.map((it) => ({ id: it.id, type: "etiqueta" })),
            maquina_id: Number(maquinaId),
            ubicacion_id: Number(ubicacionId),
        };

        try {
            // 1º intento: precheck que puede devolver warnings en 200
            const resp = await fetch("/paquetes", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content,
                },
                body: JSON.stringify(payload),
            });

            let data;
            try {
                data = await resp.clone().json();
            } catch {
                throw new Error(
                    `Error inesperado del servidor (Código ${resp.status})`
                );
            }

            if (!resp.ok) {
                throw new Error(
                    data?.message ||
                        `Error ${resp.status}: no se pudo crear el paquete`
                );
            }

            // Si viene bloque de warnings y success=false, pide confirmación y reintenta con confirmar=true
            if (data.warning && !data.success) {
                const w = data.warning;
                let html =
                    "<div style='text-align:left'>Se detectaron advertencias:";
                if (
                    Array.isArray(w.etiquetas_no_encontradas) &&
                    w.etiquetas_no_encontradas.length
                ) {
                    html += `<br><b>No encontradas:</b> ${w.etiquetas_no_encontradas.join(
                        ", "
                    )}`;
                }
                if (
                    Array.isArray(w.etiquetas_ocupadas) &&
                    w.etiquetas_ocupadas.length
                ) {
                    html += `<br><b>Ya empaquetadas:</b> ${w.etiquetas_ocupadas.join(
                        ", "
                    )}`;
                }
                if (
                    Array.isArray(w.etiquetas_incompletas) &&
                    w.etiquetas_incompletas.length
                ) {
                    html += `<br><b>Incompletas:</b> ${w.etiquetas_incompletas.join(
                        ", "
                    )}`;
                }
                if (
                    Array.isArray(w.elementos_no_encontrados) &&
                    w.elementos_no_encontrados.length
                ) {
                    html += `<br><b>Elementos no encontrados:</b> ${w.elementos_no_encontrados.join(
                        ", "
                    )}`;
                }
                if (
                    Array.isArray(w.elementos_incompletos) &&
                    w.elementos_incompletos.length
                ) {
                    html += `<br><b>Elementos incompletos:</b> ${w.elementos_incompletos.join(
                        ", "
                    )}`;
                }
                html += "</div>";

                const res = await Swal.fire({
                    icon: "warning",
                    title: "Advertencias",
                    html,
                    showCancelButton: true,
                    confirmButtonText: "Continuar",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                });

                if (!res.isConfirmed) {
                    throw new Error("Operación cancelada por el usuario.");
                }

                if (global.customLoader) {
                    global.customLoader.show({
                        text: "Confirmando",
                        subtext: "Aplicando cambios...",
                        type: "bars",
                    });
                }

                const resp2 = await fetch("/paquetes", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content,
                    },
                    body: JSON.stringify({ ...payload, confirmar: true }),
                });

                let data2;
                try {
                    data2 = await resp2.clone().json();
                } catch {
                    throw new Error(
                        `Error inesperado del servidor (Código ${resp2.status})`
                    );
                }

                if (!resp2.ok || !data2.success) {
                    throw new Error(
                        data2?.message ||
                            `Error ${resp2.status}: no se pudo confirmar el paquete`
                    );
                }
                data = data2;
            }

            if (global.customLoader) global.customLoader.hide();

            // Éxito
            // Éxito
            const pesoTotalAntesDeLimpiar = calcularPesoTotal();
            const idsAntesDeLimpiar = items.map((i) => i.id);

            await Swal.fire({
                icon: "success",
                title: "Paquete creado",
                html: `
        <p>El paquete <strong>${
            data.codigo_paquete || data.paquete?.codigo || "N/D"
        }</strong> se ha creado correctamente</p>
        <p class="text-gray-600 mt-2"><strong>${
            idsAntesDeLimpiar.length
        }</strong> etiqueta${
                    idsAntesDeLimpiar.length !== 1 ? "s" : ""
                } empaquetada${idsAntesDeLimpiar.length !== 1 ? "s" : ""}</p>
        <p class="text-gray-600">Peso total: <strong>${pesoTotalAntesDeLimpiar.toFixed(
            2
        )} kg</strong></p>
    `,
                confirmButtonText: "Continuar",
            });

            // Actualiza el DOM sin recargar
            actualizarUITrasPaquete({
                codigoPaquete:
                    data.codigo_paquete || data.paquete?.codigo || null,
                etiquetaIds: idsAntesDeLimpiar,
                pesoTotal: pesoTotalAntesDeLimpiar,
                codigoPlanilla: data.codigo_planilla || null,
            });
        } catch (error) {
            if (global.customLoader) global.customLoader.hide();
            console.error("❌ Error al crear paquete:", error);
            await Swal.fire({
                icon: "error",
                title: "Error al crear paquete",
                text: error.message || "Ha ocurrido un error inesperado",
                confirmButtonText: "Cerrar",
            });
        }
    }

    // ============================================================================
    // INICIALIZACIÓN DEL MÓDULO
    // ============================================================================

    /**
     * Inicializa el módulo de paquetes
     */
    function inicializar() {
        if (isInitialized) {
            console.warn("⚠️ Módulo trabajoPaquete ya fue inicializado");
            return;
        }

        console.log("🚀 Inicializando módulo trabajoPaquete.js");

        // Inicializar input de QR
        const qrInput = document.getElementById("qrItem");
        if (qrInput) {
            inicializarInputQR(qrInput);
        } else {
            console.warn("⚠️ No se encontró el input #qrItem");
        }

        // Inicializar botón de crear paquete
        const crearPaqueteBtn = document.getElementById("crearPaqueteBtn");
        if (crearPaqueteBtn) {
            crearPaqueteBtn.addEventListener("click", crearPaquete);
            console.log("✅ Botón crear paquete inicializado");
        } else {
            console.warn("⚠️ No se encontró el botón #crearPaqueteBtn");
        }

        // Inicializar formulario de eliminar paquete
        inicializarFormularioEliminar();

        isInitialized = true;
        console.log("✅ Módulo trabajoPaquete.js inicializado correctamente");
    }

    /**
     * Inicializa el input de QR para validación
     */
    function inicializarInputQR(qrInput) {
        qrInput.addEventListener("input", async function (e) {
            const codigo = e.target.value.trim();

            if (!codigo || codigo.length < 3) return;

            // Delay para asegurar que el scanner terminó
            await new Promise((resolve) => setTimeout(resolve, 100));

            try {
                // Validar etiqueta
                const data = await validarEtiqueta(codigo);

                // Verificar si es válida
                if (!data.valida) {
                    await Swal.fire({
                        icon: "warning",
                        title: "Etiqueta no válida",
                        html: `
                            <div style="text-align: left;">
                                <p><strong>Código:</strong> ${codigo}</p>
                                <p><strong>Motivo:</strong> ${
                                    data.motivo || "No especificado"
                                }</p>
                                ${
                                    data.estado_actual
                                        ? `<p><strong>Estado actual:</strong> ${data.estado_actual}</p>`
                                        : ""
                                }
                                ${
                                    data.paquete_actual
                                        ? `<p><strong>Paquete actual:</strong> ${data.paquete_actual}</p>`
                                        : ""
                                }
                            </div>
                        `,
                        confirmButtonText: "OK",
                    });
                    e.target.value = "";
                    e.target.focus();
                    return;
                }

                // Agregar al carro
                const agregado = agregarItemEtiqueta(codigo, data);

                // Limpiar y enfocar para siguiente escaneo
                e.target.value = "";
                e.target.focus();
            } catch (error) {
                console.error("❌ Error al validar etiqueta:", error);
                await mostrarErrorValidacion(error, codigo);
                e.target.value = "";
                e.target.focus();
            }
        });

        // Respaldo con evento 'change'
        qrInput.addEventListener("change", function (e) {
            const event = new Event("input", { bubbles: true });
            e.target.dispatchEvent(event);
        });

        console.log("✅ Input de QR inicializado");
    }

    /**
     * Inicializa el formulario de eliminar paquete
     */
    function inicializarFormularioEliminar() {
        const deleteForm = document.getElementById("deleteForm");
        if (!deleteForm) {
            console.warn("⚠️ No se encontró el formulario #deleteForm");
            return;
        }

        deleteForm.addEventListener("submit", async function (event) {
            event.preventDefault();

            const paqueteId = document.getElementById("paquete_id")?.value;

            if (!paqueteId) {
                await Swal.fire({
                    icon: "warning",
                    title: "Campo vacío",
                    text: "Por favor, ingrese un ID válido.",
                    confirmButtonColor: "#3085d6",
                });
                return;
            }

            const confirmacion = await Swal.fire({
                title: "¿Estás seguro?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar",
            });

            if (confirmacion.isConfirmed) {
                deleteForm.action = `/paquetes/${paqueteId}`;
                deleteForm.submit();
            }
        });

        console.log("✅ Formulario de eliminar paquete inicializado");
    }
    function setEstadoEnPaquete(node, codigoPaquete) {
        try {
            node.classList.remove(
                "estado-pendiente",
                "estado-fabricando",
                "estado-ensamblando",
                "estado-soldando",
                "estado-fabricada",
                "estado-completada",
                "estado-ensamblada",
                "estado-soldada"
            );
            node.classList.add("estado-en-paquete");
            const badge = node.querySelector(
                ".badge-estado, .estado-texto, [data-role='estado-badge']"
            );
            if (badge) badge.textContent = "En paquete";

            const pkgSpan = node.querySelector(
                ".badge-paquete, [data-role='paquete-badge']"
            );
            if (pkgSpan) {
                pkgSpan.textContent = codigoPaquete || "";
                pkgSpan.title = "Código de paquete";
            }

            node.dataset.paquete = codigoPaquete || "";
            node.setAttribute("data-paquete", codigoPaquete || "");

            // Si esa tarjeta debe desaparecer del centro al empaquetar:
            if (node.matches("[data-ocultar-al-empaquetar='1']")) {
                node.remove(); // o node.classList.add("hidden")
            }
        } catch (_) {}
    }

    /**
     * Actualiza el DOM tras crear paquete sin recargar.
     * @param {{ codigoPaquete:string|null, etiquetaIds:string[], pesoTotal:number, codigoPlanilla?:string|null }} info
     */
    function actualizarUITrasPaquete(info) {
        const { codigoPaquete, etiquetaIds, pesoTotal, codigoPlanilla } =
            info || {};

        // 1) Limpia el carro (estado + lista)
        try {
            limpiarCarro();
        } catch (_) {}

        // 2) Marca cada tarjeta/fila de etiqueta
        (etiquetaIds || []).forEach((id) => {
            const selector = `[data-etiqueta-sub-id="${CSS.escape(
                id
            )}"], [data-etiqueta="${CSS.escape(id)}"]`;
            document
                .querySelectorAll(selector)
                .forEach((n) => setEstadoEnPaquete(n, codigoPaquete));
        });

        // 3) Si tienes un contador agregado opcional
        const totalPesoNode = document.querySelector(
            "[data-role='total-peso-paquetes']"
        );
        if (totalPesoNode && typeof pesoTotal === "number") {
            const actual = parseFloat(totalPesoNode.dataset.valor || "0") || 0;
            const nuevo = actual + Number(pesoTotal || 0);
            totalPesoNode.dataset.valor = String(nuevo);
            totalPesoNode.textContent = `${nuevo.toFixed(2)} kg`;
        }

        // 4) Si tienes una lista visual de paquetes
        const lista = document.getElementById("paquetesList");
        if (lista && codigoPaquete) {
            const li = document.createElement("li");
            li.className =
                "px-3 py-2 bg-white border rounded mb-2 flex items-center justify-between";
            li.innerHTML = `
            <div class="flex-1">
                <div class="font-semibold text-gray-800">Paquete ${codigoPaquete}</div>
                <div class="text-xs text-gray-600">
                    ${etiquetaIds.length} etiqueta${
                etiquetaIds.length !== 1 ? "s" : ""
            } ·
                    ${
                        typeof pesoTotal === "number"
                            ? `${pesoTotal.toFixed(2)} kg`
                            : ""
                    }
                    ${codigoPlanilla ? `· Planilla ${codigoPlanilla}` : ""}
                </div>
            </div>
            <div class="ml-3">
                <button class="text-sm px-2 py-1 border rounded hover:bg-gray-50" data-role="ver-paquete" data-codigo="${codigoPaquete}">
                    Ver
                </button>
            </div>
        `;
            lista.prepend(li);
        }

        // 5) Evento global por si otros módulos quieren reaccionar
        try {
            window.dispatchEvent(
                new CustomEvent("paquete:creado", {
                    detail: {
                        codigoPaquete,
                        etiquetaIds,
                        pesoTotal,
                        codigoPlanilla,
                    },
                })
            );
        } catch (_) {}
    }

    // ============================================================================
    // API PÚBLICA DEL MÓDULO
    // ============================================================================

    global.TrabajoPaquete = {
        inicializar,
        agregarItemEtiqueta,
        eliminarItem,
        limpiarCarro,
        obtenerItems,
        calcularPesoTotal,
        crearPaquete,
        validarEtiqueta,
        actualizarListaVisual,
    };

    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", inicializar);
    } else {
        inicializar();
    }
})(window);
