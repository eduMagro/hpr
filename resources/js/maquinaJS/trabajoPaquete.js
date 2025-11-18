/**
 * ================================================================================
 * MÓDULO: trabajoPaquete.js - VERSIÓN FINAL DEFINITIVA
 * ================================================================================
 * ✅ Peso usando data.peso_etiqueta (correcto)
 * ✅ Sistema DOM centralizado que funciona
 * ✅ Actualización automática de colores
 * ================================================================================
 */

(function (global) {
    "use strict";

    let items = [];
    let isInitialized = false;

    // ============================================================================
    // VALIDACIÓN DE ETIQUETAS VIA QR
    // ============================================================================

    async function validarEtiqueta(codigo) {
        const url = `/etiquetas/${encodeURIComponent(
            codigo
        )}/validar-para-paquete`;

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content,
                },
            });

            const contentType = response.headers.get("content-type") || "";
            if (!contentType.includes("application/json")) {
                throw new Error("Respuesta del servidor no es JSON");
            }

            const data = await response.json();

            // Log para debug - respuesta del endpoint
            console.log("🔍 validarEtiqueta response:", {
                status: response.status,
                data: data,
                peso_etiqueta: data.peso_etiqueta,
            });

            if (!response.ok) {
                throw new Error(
                    data?.message || data?.motivo || "Error al validar"
                );
            }

            return data;
        } catch (error) {
            throw error;
        }
    }

    // ============================================================================
    // GESTIÓN DEL CARRO
    // ============================================================================

    function agregarItemEtiqueta(codigo, data) {
        const id = data.id || codigo;
        if (items.some((i) => i.id === id)) return false;

        const peso = parseFloat(data.peso_etiqueta) || 0;

        // Log para debug
        console.log("🔍 agregarItemEtiqueta:", {
            codigo,
            id,
            peso_etiqueta_recibido: data.peso_etiqueta,
            peso_parseado: peso,
            data_completa: data,
        });

        const newItem = {
            id,
            type: "etiqueta",
            peso: peso, // ✅ USA data.peso_etiqueta
            estado: data.estado || "desconocido",
            nombre: data.nombre || "Sin nombre",
        };

        items.push(newItem);
        actualizarListaVisual();
        return true;
    }

    function eliminarItem(id) {
        const i = items.findIndex((x) => x.id === id);
        if (i >= 0) items.splice(i, 1);
        actualizarListaVisual();
    }

    function limpiarCarro() {
        items = [];
        actualizarListaVisual();
    }

    function calcularPesoTotal() {
        return items.reduce((acc, i) => acc + (parseFloat(i.peso) || 0), 0);
    }

    function obtenerItems() {
        return items;
    }

    function actualizarListaVisual() {
        const itemsList = document.getElementById("itemsList");
        if (!itemsList) return;
        itemsList.innerHTML = "";

        for (const item of items) {
            const li = document.createElement("li");
            li.className =
                "flex justify-between items-center px-3 py-2 bg-white border rounded mb-2";
            li.dataset.code = item.id;

            li.innerHTML = `
                <div>
                    <div class="font-semibold">${item.id
                } === ${item.peso.toFixed(2)} kg</div>
                </div>
                <button class="text-red-600 hover:text-red-800" title="Eliminar">❌</button>
            `;

            li.querySelector("button").onclick = () => eliminarItem(item.id);
            itemsList.appendChild(li);
        }

        const total = calcularPesoTotal();
        const resumen = document.createElement("li");
        resumen.className = "py-3 px-3 bg-blue-50 border-t-2 mt-3 font-bold";
        resumen.textContent = `Total: ${total.toFixed(2)} kg (${items.length
            } etiquetas)`;
        itemsList.appendChild(resumen);
    }

    // ============================================================================
    // CREACIÓN DE PAQUETES
    // ============================================================================

    async function crearPaquete() {
        if (!items.length) {
            await Swal.fire(
                "Carro vacío",
                "No hay etiquetas para empaquetar",
                "warning"
            );
            return;
        }

        const maquinaId = Number(
            document.getElementById("maquina-info")?.dataset?.maquinaId ||
            window.maquinaId
        );
        const ubicacionId = Number(
            document.getElementById("ubicacion-id")?.value || window.ubicacionId
        );

        if (!maquinaId || !ubicacionId) {
            await Swal.fire(
                "Faltan datos",
                "Debe especificarse la máquina y la ubicación.",
                "error"
            );
            return;
        }

        const payload = {
            items: items.map((i) => ({ id: i.id, type: i.type })),
            maquina_id: maquinaId,
            ubicacion_id: ubicacionId,
        };

        const confirmarCreacion = async (extra = {}) => {
            const resp = await fetch("/paquetes", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content,
                },
                body: JSON.stringify({ ...payload, ...extra }),
            });

            const data = await resp.json();
            if (!resp.ok)
                throw new Error(data.message || "Error al crear el paquete");
            return data;
        };

        try {
            const data = await confirmarCreacion();

            if (data.success) return postCreacion(data);

            if (data.warning) {
                let html = `<div style='text-align:left;'>Se detectaron advertencias:`;
                for (const [clave, lista] of Object.entries(data.warning)) {
                    if (Array.isArray(lista) && lista.length) {
                        html += `<br><strong>${clave.replaceAll(
                            "_",
                            " "
                        )}:</strong> ${lista.join(", ")}`;
                    }
                }
                html += `</div>`;

                const confirm = await Swal.fire({
                    icon: "warning",
                    title: "Advertencias",
                    html,
                    showCancelButton: true,
                    confirmButtonText: "Continuar",
                    cancelButtonText: "Cancelar",
                });

                if (confirm.isConfirmed) {
                    const confirmData = await confirmarCreacion({
                        confirmar: true,
                    });
                    if (confirmData.success) return postCreacion(confirmData);
                    throw new Error(confirmData.message);
                }

                throw new Error("Operación cancelada por el usuario.");
            }

            throw new Error("No se pudo crear el paquete");
        } catch (e) {
            await Swal.fire("Error", e.message || "Error inesperado", "error");
        }
    }

    async function postCreacion(data) {
        const codigo = data.codigo_paquete || data.paquete?.codigo || "N/D";
        const peso = calcularPesoTotal();
        const etiquetas = [...items.map((i) => i.id)];

        await Swal.fire({
            icon: "success",
            title: "Paquete creado",
            html: `<p><strong>${codigo}</strong> creado correctamente</p><p>${etiquetas.length
                } etiquetas · ${peso.toFixed(2)} kg</p>`,
        });

        limpiarCarro();

        // ⭐ DISPARAR EVENTO
        console.log(`📦 Disparando evento paquete:creado para ${codigo}`);
        window.dispatchEvent(
            new CustomEvent("paquete:creado", {
                detail: {
                    codigoPaquete: codigo,
                    etiquetaIds: etiquetas,
                    pesoTotal: peso,
                },
            })
        );

        // ⭐ ACTUALIZAR DOM DIRECTAMENTE SI EL SISTEMA NO ESTÁ DISPONIBLE
        if (typeof window.SistemaDOM === "undefined") {
            console.log(
                "⚠️ SistemaDOM no disponible, actualizando manualmente"
            );
            etiquetas.forEach((etiquetaId) => {
                actualizarEtiquetaManual(etiquetaId, codigo);
            });
        } else {
            console.log(
                "✅ SistemaDOM detectado, se actualizará automáticamente"
            );
        }
    }

    // ============================================================================
    // ACTUALIZACIÓN MANUAL DEL DOM (FALLBACK)
    // ============================================================================

    function actualizarEtiquetaManual(etiquetaId, codigoPaquete) {
        const safeId = String(etiquetaId).replace(/\./g, "-");
        const elemento = document.querySelector(`#etiqueta-${safeId}`);

        if (!elemento) {
            console.warn(`❌ No se encontró elemento: ${etiquetaId}`);
            return;
        }

        console.log(`🔄 Actualizando manualmente: ${etiquetaId}`);

        // 1. Eliminar todas las clases estado-*
        const clases = Array.from(elemento.classList);
        clases.forEach((clase) => {
            if (clase.startsWith("estado-")) {
                elemento.classList.remove(clase);
            }
        });

        // 2. Añadir clase estado-en-paquete
        elemento.classList.add("estado-en-paquete");

        // 3. Actualizar dataset
        elemento.dataset.estado = "en-paquete";

        // 4. Actualizar CSS variable
        elemento.style.setProperty("--bg-estado", "#e3e4FA");

        // 5. Actualizar SVG si existe
        const contenedorSvg = elemento.querySelector('[id^="contenedor-svg-"]');
        if (contenedorSvg) {
            const svg = contenedorSvg.querySelector("svg");
            if (svg) {
                svg.style.background = "#e3e4FA";
            }
        }

        // 6. Actualizar fondo de la card
        const card = elemento.querySelector(".etiqueta-card") || elemento;
        if (card) {
            card.style.background = "#e3e4FA";
        }

        // 7. Añadir info del paquete
        const h3 = elemento.querySelector("h3");
        if (h3 && !elemento.querySelector(".paquete-info")) {
            const paqueteInfo = document.createElement("div");
            paqueteInfo.className =
                "paquete-info text-sm font-semibold mt-2 no-print";
            paqueteInfo.style.cssText =
                "display: flex; align-items: center; gap: 0.25rem; color: #7c3aed; font-size: 0.875rem;";
            paqueteInfo.innerHTML = `
                <svg style="width: 1rem; height: 1rem;" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                </svg>
                <span>Paquete: ${codigoPaquete}</span>
            `;
            h3.parentNode.insertBefore(paqueteInfo, h3.nextSibling);
        }

        // 8. Deshabilitar botones
        const botones = elemento.querySelectorAll(".btn-fabricar");
        botones.forEach((btn) => {
            btn.disabled = true;
            btn.style.opacity = "0.5";
            btn.style.cursor = "not-allowed";
        });

        // 9. Animación
        // ✅ FIX: Solo transicionar transform y background, NO "all"
        elemento.style.transition = "transform 0.5s ease, background-color 0.5s ease";
        elemento.style.transform = "scale(1.03)";
        setTimeout(() => {
            elemento.style.transform = "scale(1)";
        }, 400);

        console.log(`✅ Etiqueta ${etiquetaId} actualizada manualmente`);
    }

    // ============================================================================
    // INICIALIZACIÓN
    // ============================================================================

    function inicializar() {
        if (isInitialized) return;
        isInitialized = true;

        // console.log("🚀 Inicializando TrabajoPaquete...");

        // Inicializar input QR
        const inputQR = document.getElementById("qrItem");
        if (inputQR) {
            inputQR.addEventListener("change", () =>
                inputQR.dispatchEvent(new Event("input"))
            );
            inputQR.addEventListener("input", async function () {
                const codigo = this.value.trim();
                if (!codigo) return;

                try {
                    const data = await validarEtiqueta(codigo);
                    if (!data.valida) {
                        await Swal.fire(
                            "Etiqueta no válida",
                            data.motivo || "Motivo no especificado",
                            "warning"
                        );
                        this.value = "";
                        this.focus();
                        return;
                    }

                    const ok = agregarItemEtiqueta(codigo, data);
                    if (!ok) {
                        await Swal.fire(
                            "Etiqueta duplicada",
                            "Ya está en el carro",
                            "info"
                        );
                    }

                    this.value = "";
                    this.focus();
                } catch (err) {
                    console.error("Error de validación:", err);
                    await Swal.fire(
                        "Error",
                        err.message || "Fallo en validación",
                        "error"
                    );
                    this.value = "";
                    this.focus();
                }
            });
        }

        // Inicializar botón crear paquete
        const btnCrear = document.getElementById("crearPaqueteBtn");
        if (btnCrear) {
            btnCrear.addEventListener("click", crearPaquete);
        }

        // Event listener para botones de agregar al carro
        document.addEventListener("click", async function (e) {
            if (
                e.target.classList.contains("btn-agregar-carro") ||
                e.target.closest(".btn-agregar-carro")
            ) {
                const btn = e.target.classList.contains("btn-agregar-carro")
                    ? e.target
                    : e.target.closest(".btn-agregar-carro");

                const etiquetaId = btn.dataset.etiquetaId;

                if (!etiquetaId) {
                    console.error("No se encontró etiqueta_id en el botón");
                    return;
                }

                // ✅ DETECTAR PESTAÑA ACTIVA (crear vs gestión)
                const tabCrearActivo = document.querySelector('[x-show="tabActivo === \'crear\'"]');
                const tabGestionActivo = document.querySelector('[x-show="tabActivo === \'gestion\'"]');

                const estaEnCrear = tabCrearActivo && window.getComputedStyle(tabCrearActivo).display !== 'none';
                const estaEnGestion = tabGestionActivo && window.getComputedStyle(tabGestionActivo).display !== 'none';

                // ✅ MODO GESTIÓN: Añadir al input de escanear etiqueta del primer paquete visible
                if (estaEnGestion) {
                    console.log("📦 Modo Gestión: Añadiendo al input de añadir etiqueta");

                    // Buscar el primer input visible de añadir etiqueta en paquetes expandidos
                    const inputEtiqueta = document.querySelector('input[id^="input-etiqueta-"]');

                    if (inputEtiqueta) {
                        inputEtiqueta.value = etiquetaId;
                        inputEtiqueta.focus();

                        // Resaltar el input brevemente
                        inputEtiqueta.classList.add('ring-4', 'ring-green-400');
                        setTimeout(() => {
                            inputEtiqueta.classList.remove('ring-4', 'ring-green-400');
                        }, 1000);

                        console.log(`✅ Etiqueta ${etiquetaId} añadida al input de gestión`);
                    } else {
                        await Swal.fire({
                            icon: "info",
                            title: "Expande un paquete",
                            text: "Para añadir una etiqueta, primero expande el paquete donde deseas añadirla",
                        });
                    }
                    return;
                }

                // ✅ MODO CREAR: Añadir al carro (comportamiento original)
                console.log("🛒 Modo Crear: Añadiendo etiqueta al carro:", etiquetaId);

                try {
                    // Validar etiqueta
                    const data = await validarEtiqueta(etiquetaId);

                    if (!data.valida) {
                        await Swal.fire({
                            icon: "warning",
                            title: "Etiqueta no válida",
                            text: data.motivo || "Motivo no especificado",
                        });
                        return;
                    }

                    // Agregar al carro
                    const ok = agregarItemEtiqueta(etiquetaId, data);

                    if (ok) {
                        // Éxito
                    } else {
                        await Swal.fire({
                            icon: "info",
                            title: "Etiqueta duplicada",
                            text: "Ya está en el carro",
                        });
                    }
                } catch (error) {
                    console.error("Error al añadir al carro:", error);
                    await Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.message || "No se pudo añadir al carro",
                    });
                }
            }
        });

        // console.log("✅ TrabajoPaquete inicializado");
    }

    // ============================================================================
    // API PÚBLICA
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

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", inicializar);
    } else {
        inicializar();
    }
    document.addEventListener("livewire:navigated", inicializar);
})(window);
