/**
 * ================================================================================
 * MÓDULO: cortes.js v3.0 FINAL (SyntaxLine28) - SIMPLIFICADO
 * ================================================================================
 * Sistema de optimización de cortes MINIMALISTA
 * - Modal simple para patrones simples
 * - Popup draggeable a la derecha para patrones optimizados
 * - Foco en visualización de etiquetas en columna central
 * - Filtrado dinámico por posición del top
 * ================================================================================
 */

window.Cortes = (function () {
    "use strict";

    console.log("🔧 Inicializando módulo Cortes v3.0 Final");

    // ============================================================================
    // CONFIGURACIÓN
    // ============================================================================

    const CONFIG = {
        longitudesPorDiametro: window.LONGITUDES_POR_DIAMETRO || {},

        endpoints: {
            calcularPatronSimple: "/etiquetas/{id}/patron-corte-simple",
            calcularPatronOptimizado: "/etiquetas/{id}/patron-corte-optimizado",
            fabricacionOptimizada: "/etiquetas/fabricacion-optimizada",
        },

        // ESTILOS Y UMBRALES
        corteSimple: {
            umbralOptimo: 95, // verde
            umbralAceptable: 90, // amarillo
            colores: {
                verde: { borde: "#10b981", fondo: "#f0fdf4", texto: "#10b981" },
                amarillo: {
                    borde: "#f59e0b",
                    fondo: "#fffbeb",
                    texto: "#f59e0b",
                },
                rojo: { borde: "#ef4444", fondo: "#fef2f2", texto: "#ef4444" },
                gris: { borde: "#d1d5db", fondo: "#f9fafb", texto: "#9ca3af" }, // no disponible
            },
        },
    };

    // ============================================================================
    // FUNCIÓN 1: MEJOR CORTE SIMPLE
    // ============================================================================

    async function mejorCorteSimple(etiquetaId, diametro, csrfToken) {
        try {
            console.log(
                "🔍 [Cortes] Analizando corte simple para:",
                etiquetaId
            );

            const url = CONFIG.endpoints.calcularPatronSimple.replace(
                "{id}",
                etiquetaId
            );
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ diametro }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(
                    errorData.message || "Error al calcular patrón simple"
                );
            }

            const data = await response.json();
            const patrones = data.patrones || [];

            if (patrones.length === 0) {
                throw new Error("No hay barras disponibles");
            }

            const decision = await mostrarModalSimple({
                etiquetaId,
                diametro,
                patrones,
            });

            return decision;
        } catch (error) {
            console.error("❌ Error:", error);
            await Swal.fire({
                icon: "error",
                title: "Error",
                text: error.message,
                timer: 2000,
            });
            throw error;
        }
    }

    // ============================================================================
    // FUNCIÓN 2: MEJOR CORTE OPTIMIZADO
    // ============================================================================

    async function mejorCorteOptimizado(
        etiquetaId,
        diametro,
        patronesPrevios,
        csrfToken
    ) {
        try {
            console.log("🎯 [Cortes] Calculando patrones optimizados para:", etiquetaId, "diametro:", diametro);
            console.log("🎯 [Cortes] Patrones previos recibidos:", patronesPrevios);

            let patrones = patronesPrevios;
            let etiquetasPlanillas = {};

            if (!patrones || patrones.length === 0) {
                const url = CONFIG.endpoints.calcularPatronOptimizado.replace(
                    "{id}",
                    etiquetaId
                );
                console.log("🎯 [Cortes] Llamando al endpoint:", url);

                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({ diametro, kmax: 5 }),
                });

                console.log("🎯 [Cortes] Response status:", response.status);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error("🎯 [Cortes] Error response:", errorText);
                    throw new Error("Error al calcular patrones optimizados");
                }

                const data = await response.json();
                console.log("🎯 [Cortes] Data recibida del backend:", data);
                patrones = data.top_global || [];
                // Guardar el mapa de etiquetas a planillas
                etiquetasPlanillas = data.etiquetas_planillas || {};
            }

            console.log("🎯 [Cortes] Patrones a mostrar:", patrones.length, patrones);

            if (patrones.length === 0) {
                await Swal.fire({
                    icon: "warning",
                    title: "Sin patrones optimizados",
                    text: "No se encontraron combinaciones de etiquetas para optimizar",
                    timer: 3000,
                });
                return null;
            }

            console.log("🎯 [Cortes] Mostrando popup optimizado...");
            const resultado = await mostrarPopupOptimizado({
                etiquetaId,
                diametro,
                patrones,
                etiquetasPlanillas,
            });

            console.log("🎯 [Cortes] Resultado del popup:", resultado);
            return resultado;
        } catch (error) {
            console.error("❌ [Cortes] Error en mejorCorteOptimizado:", error);
            await Swal.fire({
                icon: "error",
                title: "Error al optimizar",
                text: error.message,
                timer: 2000,
            });
            throw error;
        }
    }

    // ============================================================================
    // FUNCIÓN 3: ENVIAR A FABRICACIÓN
    // ============================================================================

    // ============================================================================
    // FUNCIÓN AUXILIAR: PEDIR DESPERDICIO MANUAL
    // ============================================================================

    async function pedirDesperdicioManual(longitudBarraM, desperdicioEstimadoCm = 0) {
        const resultado = await Swal.fire({
            title: 'Desperdicio Real',
            width: 340,
            html: `
                <div style="text-align: center; padding: 0 8px;">
                    <p style="color: #6b7280; font-size: 14px; margin-bottom: 12px;">
                        Introduce el desperdicio real (cm)
                    </p>
                    <div style="background: #f3f4f6; padding: 10px; border-radius: 8px; margin-bottom: 16px;">
                        <p style="margin: 0; font-size: 13px;">
                            <strong>Barra:</strong> ${longitudBarraM}m
                            ${desperdicioEstimadoCm > 0 ? ` · <strong>Est:</strong> ${desperdicioEstimadoCm} cm` : ''}
                        </p>
                    </div>
                    <input type="number"
                           id="swal-desperdicio-input"
                           placeholder="cm"
                           min="0"
                           step="0.1"
                           value="${desperdicioEstimadoCm}"
                           style="width: 120px; font-size: 24px; text-align: center; padding: 12px; border: 2px solid #d1d5db; border-radius: 8px; outline: none;">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            focusConfirm: false,
            didOpen: () => {
                const input = document.getElementById('swal-desperdicio-input');
                if (input) {
                    input.focus();
                    input.select();
                }
            },
            preConfirm: () => {
                const valor = document.getElementById('swal-desperdicio-input').value;
                if (valor === '' || isNaN(parseFloat(valor))) {
                    Swal.showValidationMessage('Introduce un valor válido');
                    return false;
                }
                if (parseFloat(valor) < 0) {
                    Swal.showValidationMessage('No puede ser negativo');
                    return false;
                }
                return parseFloat(valor);
            }
        });

        if (resultado.isConfirmed) {
            return resultado.value;
        }
        return null;
    }

    // ============================================================================
    // FUNCIÓN 3: ENVIAR A FABRICACIÓN
    // ============================================================================

    async function enviarAFabricacionOptimizada(params) {
        const {
            longitudBarraCm,
            etiquetas,
            csrfToken,
            onUpdate,
            desperdicioEstimadoCm = 0,
            pedirDesperdicio = true  // Solo pedir en primer clic
        } = params;

        let desperdicioManualCm = null;

        // Solo pedir desperdicio si se indica (primer clic)
        if (pedirDesperdicio) {
            desperdicioManualCm = await pedirDesperdicioManual(
                (longitudBarraCm / 100).toFixed(2),
                desperdicioEstimadoCm
            );

            // Si el usuario cancela, no continuar
            if (desperdicioManualCm === null) {
                return { success: false, cancelled: true };
            }
        }

        try {
            const payload = {
                producto_base: { longitud_barra_cm: longitudBarraCm },
                repeticiones: 1,
                etiquetas: etiquetas.map((e) => ({
                    etiqueta_sub_id: e.etiqueta_sub_id || e,
                    patron_letras: e.patron?.patron_letras || "",
                })),
            };

            // Solo incluir desperdicio si se pidió
            if (desperdicioManualCm !== null) {
                payload.desperdicio_manual_cm = desperdicioManualCm;
            }

            const response = await fetch(
                CONFIG.endpoints.fabricacionOptimizada,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify(payload),
                }
            );

            const data = await response.json().catch(() => ({}));

            // Si el backend responde con error (4xx o 5xx), lanzamos ese detalle
            if (!response.ok) {
                const message =
                    data?.message ||
                    data?.error ||
                    "Error desconocido en la fabricación optimizada";
                throw { status: response.status, data, message };
            }

            if (typeof onUpdate === "function") {
                etiquetas.forEach((e) => {
                    const subId = e.etiqueta_sub_id || e;
                    onUpdate(subId, data);
                });
            }

            await Swal.fire({
                icon: "success",
                title: "¡Fabricación iniciada!",
                html: `<p class="text-lg">Barra: <strong>${(
                    longitudBarraCm / 100
                ).toFixed(2)}m</strong></p>
                   <p>Etiquetas: <strong>${etiquetas.length}</strong></p>`,
                timer: 2000,
                showConfirmButton: false,
            });

            return data;
        } catch (error) {
            console.error("❌ Error en fabricación:", error);

            // Si el backend devolvió un JSON con mensaje, lo mostramos
            const mensajeError =
                error?.data?.message ||
                error?.message ||
                "No se pudo completar la fabricación";

            await Swal.fire({
                icon: "error",
                title: "Error",
                text: mensajeError,
                showConfirmButton: true,
            });

            // devolvemos el error completo para manejarlo aguas arriba
            return {
                success: false,
                error: true,
                status: error?.status || 500,
                data: error?.data || null,
                message: mensajeError,
            };
        }
    }

    // ============================================================================
    // MODAL SIMPLE (Patrones simples)
    // ============================================================================

    async function mostrarModalSimple(datos) {
        const { etiquetaId, diametro, patrones } = datos;
        const cfg = CONFIG.corteSimple;

        //Este nos serviria para filtrar patrones simples por los productos que hay disponibles en la SL28
        //Por el momento comentamos esta parte para mostrar todos los patrones simples posibles
        // const disponibles = patrones
        //     .filter((p) => p.disponible_en_maquina && p.por_barra > 0)
        //     .sort((a, b) => b.aprovechamiento - a.aprovechamiento);

        const disponibles = patrones
            .filter((p) => p.por_barra > 0) // aún tiene sentido ocultar los que no caben
            .sort((a, b) => b.aprovechamiento - a.aprovechamiento);

        const htmlOpciones = disponibles
            .map((p, i) => {
                let nivel = "gris";
                if (p.disponible_en_maquina) {
                    if (p.aprovechamiento >= cfg.umbralOptimo) {
                        nivel = "verde";
                    } else if (p.aprovechamiento >= cfg.umbralAceptable) {
                        nivel = "amarillo";
                    } else {
                        nivel = "rojo";
                    }
                }
                const c = cfg.colores[nivel];
                const esquemaLetras = Array(p.por_barra).fill("A").join(" + ");

                // Iconos según nivel de aprovechamiento
                const iconoNivel = nivel === "verde" ? "✓" : nivel === "amarillo" ? "⚠" : nivel === "rojo" ? "✕" : "○";

                return `
                <div class="patron-simple-item" data-index="${i}" data-nivel="${nivel}">
                    <div class="patron-simple-left">
                        <div class="patron-simple-barra">
                            <span class="patron-barra-diametro">Ø${diametro}</span>
                            <span class="patron-barra-longitud">${p.longitud_m}m</span>
                            <span class="patron-barra-esquema">${esquemaLetras}</span>
                        </div>
                        <div class="patron-simple-info">
                            <span class="patron-piezas">${p.por_barra} piezas</span>
                            ${!p.disponible_en_maquina ? '<span class="patron-no-disponible">No disponible en máquina</span>' : ''}
                        </div>
                    </div>
                    <div class="patron-simple-right">
                        <div class="patron-aprovechamiento" data-nivel="${nivel}">
                            <span class="patron-icono">${iconoNivel}</span>
                            <span class="patron-porcentaje">${p.aprovechamiento.toFixed(1)}%</span>
                        </div>
                        <div class="patron-desperdicio">
                            Desperdicio: ${p.sobra_cm} cm
                        </div>
                    </div>
                </div>
            `;
            })
            .join("");

        let seleccionado = null;

        const resultado = await Swal.fire({
            title: `Patrón de Corte Simple`,
            html: `
                <style>
                    .modal-corte-simple-header {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 12px;
                        padding: 12px 16px;
                        background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
                        border-radius: 12px;
                        margin-bottom: 20px;
                    }
                    .modal-corte-simple-header .header-icon {
                        width: 40px;
                        height: 40px;
                        background: rgba(255,255,255,0.15);
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                    }
                    .modal-corte-simple-header .header-info {
                        text-align: left;
                    }
                    .modal-corte-simple-header .header-title {
                        color: white;
                        font-weight: 700;
                        font-size: 16px;
                        margin: 0;
                    }
                    .modal-corte-simple-header .header-subtitle {
                        color: rgba(255,255,255,0.8);
                        font-size: 13px;
                        margin: 2px 0 0 0;
                    }

                    .patron-simple-container {
                        max-height: 380px;
                        overflow-y: auto;
                        padding: 4px;
                        scrollbar-width: thin;
                        scrollbar-color: #cbd5e1 #f1f5f9;
                    }
                    .patron-simple-container::-webkit-scrollbar {
                        width: 6px;
                    }
                    .patron-simple-container::-webkit-scrollbar-track {
                        background: #f1f5f9;
                        border-radius: 3px;
                    }
                    .patron-simple-container::-webkit-scrollbar-thumb {
                        background: #cbd5e1;
                        border-radius: 3px;
                    }
                    .patron-simple-container::-webkit-scrollbar-thumb:hover {
                        background: #94a3b8;
                    }

                    .patron-simple-item {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 14px 18px;
                        margin: 8px 0;
                        border-radius: 12px;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        border: 2px solid #e2e8f0;
                        background: #ffffff;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                    }
                    .patron-simple-item:hover {
                        border-color: #1e3a5f;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(30,58,95,0.15);
                    }
                    .patron-simple-item.selected {
                        border-color: #1e3a5f;
                        background: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%);
                        box-shadow: 0 0 0 3px rgba(30,58,95,0.2), 0 4px 12px rgba(30,58,95,0.15);
                    }

                    .patron-simple-item[data-nivel="gris"] {
                        opacity: 0.7;
                    }

                    .patron-simple-left {
                        display: flex;
                        flex-direction: column;
                        gap: 6px;
                    }
                    .patron-simple-barra {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }
                    .patron-barra-diametro {
                        font-size: 18px;
                        font-weight: 600;
                        color: #64748b;
                        background: #f1f5f9;
                        padding: 4px 10px;
                        border-radius: 6px;
                    }
                    .patron-barra-longitud {
                        font-size: 22px;
                        font-weight: 700;
                        color: #1e3a5f;
                    }
                    .patron-barra-esquema {
                        font-size: 16px;
                        font-weight: 600;
                        color: #475569;
                        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                        background: #f1f5f9;
                        padding: 4px 10px;
                        border-radius: 6px;
                    }
                    .patron-simple-info {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }
                    .patron-piezas {
                        font-size: 13px;
                        color: #64748b;
                        font-weight: 500;
                    }
                    .patron-no-disponible {
                        font-size: 11px;
                        color: #ef4444;
                        background: #fef2f2;
                        padding: 2px 8px;
                        border-radius: 4px;
                        font-weight: 500;
                    }

                    .patron-simple-right {
                        text-align: right;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        gap: 4px;
                    }
                    .patron-aprovechamiento {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        padding: 6px 12px;
                        border-radius: 8px;
                        font-weight: 700;
                    }
                    .patron-aprovechamiento[data-nivel="verde"] {
                        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                        color: #047857;
                    }
                    .patron-aprovechamiento[data-nivel="amarillo"] {
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        color: #b45309;
                    }
                    .patron-aprovechamiento[data-nivel="rojo"] {
                        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                        color: #b91c1c;
                    }
                    .patron-aprovechamiento[data-nivel="gris"] {
                        background: #f3f4f6;
                        color: #6b7280;
                    }
                    .patron-icono {
                        font-size: 14px;
                    }
                    .patron-porcentaje {
                        font-size: 20px;
                    }
                    .patron-desperdicio {
                        font-size: 12px;
                        color: #94a3b8;
                    }

                    .modal-corte-simple-footer {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        margin-top: 16px;
                        padding: 12px;
                        background: #f8fafc;
                        border-radius: 10px;
                        color: #64748b;
                        font-size: 13px;
                    }
                    .modal-corte-simple-footer svg {
                        width: 18px;
                        height: 18px;
                        color: #1e3a5f;
                    }
                </style>

                <div class="modal-corte-simple-header">
                    <div class="header-icon">📏</div>
                    <div class="header-info">
                        <p class="header-title">Etiqueta: ${etiquetaId}</p>
                        <p class="header-subtitle">Selecciona el patrón de corte óptimo</p>
                    </div>
                </div>

                <div class="patron-simple-container">
                    ${htmlOpciones}
                </div>

                <div class="modal-corte-simple-footer">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Haz clic en una opción para seleccionarla</span>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: "Fabricar",
            denyButtonText: "Optimizar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#10b981",
            denyButtonColor: "#1e3a5f",
            width: "580px",
            customClass: {
                popup: 'modal-corte-simple-popup',
                title: 'modal-corte-simple-title',
                confirmButton: 'modal-corte-btn-fabricar',
                denyButton: 'modal-corte-btn-optimizar',
                cancelButton: 'modal-corte-btn-cancelar'
            },
            didOpen: () => {
                // Añadir estilos personalizados para los botones del modal
                const style = document.createElement('style');
                style.textContent = `
                    .modal-corte-simple-popup {
                        border-radius: 20px !important;
                        padding: 24px !important;
                    }
                    .modal-corte-simple-title {
                        display: none !important;
                    }
                    .modal-corte-btn-fabricar {
                        padding: 12px 28px !important;
                        font-weight: 600 !important;
                        border-radius: 10px !important;
                        font-size: 15px !important;
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
                        transition: all 0.2s ease !important;
                    }
                    .modal-corte-btn-fabricar:hover {
                        transform: translateY(-2px) !important;
                        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4) !important;
                    }
                    .modal-corte-btn-optimizar {
                        padding: 12px 28px !important;
                        font-weight: 600 !important;
                        border-radius: 10px !important;
                        font-size: 15px !important;
                        box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3) !important;
                        transition: all 0.2s ease !important;
                    }
                    .modal-corte-btn-optimizar:hover {
                        transform: translateY(-2px) !important;
                        box-shadow: 0 6px 16px rgba(30, 58, 95, 0.4) !important;
                    }
                    .modal-corte-btn-cancelar {
                        padding: 12px 28px !important;
                        font-weight: 500 !important;
                        border-radius: 10px !important;
                        font-size: 15px !important;
                        color: #64748b !important;
                        background: #f1f5f9 !important;
                        transition: all 0.2s ease !important;
                    }
                    .modal-corte-btn-cancelar:hover {
                        background: #e2e8f0 !important;
                    }
                `;
                document.head.appendChild(style);

                document.querySelectorAll(".patron-simple-item").forEach((el) => {
                    el.addEventListener("click", function () {
                        document
                            .querySelectorAll(".patron-simple-item")
                            .forEach((e) => {
                                e.classList.remove("selected");
                            });
                        this.classList.add("selected");
                        seleccionado =
                            disponibles[parseInt(this.dataset.index)];
                    });
                });

                const primero = document.querySelector(".patron-simple-item");
                if (primero) {
                    primero.click();
                }
            },
        });

        console.log("🔍 [Cortes] Resultado del modal simple:", resultado);
        console.log("🔍 [Cortes] isConfirmed:", resultado.isConfirmed, "isDenied:", resultado.isDenied, "isDismissed:", resultado.isDismissed);

        if (resultado.isConfirmed && seleccionado) {
            console.log("🔍 [Cortes] Usuario eligió FABRICAR patrón simple");
            // Generar el patrón de letras para el corte simple (todas las piezas son iguales: A + A + A...)
            const esquemaSimple = Array(seleccionado.por_barra).fill("A").join(" + ");

            return {
                accion: "fabricar_patron_simple",
                longitud_m: seleccionado.longitud_m,
                patron: seleccionado,
                patron_letras: esquemaSimple, // 🔧 Incluir el patrón de letras
                patronInfo: {
                    aprovechamiento: seleccionado.aprovechamiento,
                    desperdicio_cm: seleccionado.sobra_cm || 0,
                    esquema: esquemaSimple
                }
            };
        } else if (resultado.isDenied) {
            console.log("🔍 [Cortes] Usuario eligió OPTIMIZAR - llamando a mejorCorteOptimizado...");
            return { accion: "optimizar" };
        }

        console.log("🔍 [Cortes] Usuario CANCELÓ el modal");
        return null;
    }
    // ============================================================================
    // POPUP OPTIMIZADO (Derecha, draggeable, filtrado dinámico)
    // ============================================================================

    async function mostrarPopupOptimizado(datos) {
        const { patrones, etiquetasPlanillas } = datos;

        // Mapa de etiquetas a planillas (desde el backend)
        const mapaPlanillas = etiquetasPlanillas || {};

        const top3 = [...patrones]
            .sort((a, b) => b.aprovechamiento - a.aprovechamiento)
            .slice(0, 3);

        let indiceActual = 0;
        let patronActual = top3[0];

        // Generar esquema simple (A + B + A + C) con info de planilla
        const generarEsquema = (patron) => {
            const letras = {};
            let siguienteLetra = 65; // A

            const esquema = patron.etiquetas
                .map((id) => {
                    if (!letras[id]) {
                        letras[id] = String.fromCharCode(siguienteLetra++);
                    }
                    return letras[id];
                })
                .join(" + ");

            // Generar leyenda con planilla (usando mapa del backend)
            const leyendaItems = Object.entries(letras).map(([id, letra]) => {
                const planillaCodigo = mapaPlanillas[id] || null;

                // Formato vertical: planilla arriba, etiqueta abajo
                return `
                    <div class="leyenda-item">
                        ${planillaCodigo ? `<span class="leyenda-planilla">${planillaCodigo}</span>` : ''}
                        <span class="leyenda-etiqueta">${letra} = ${id}</span>
                    </div>
                `;
            });

            const leyendaHtml = leyendaItems.join("");

            return { esquema, leyenda: leyendaHtml };
        };

        // 🆕 Actualizar popup Y filtrar etiquetas por posición
        const actualizarPopup = () => {
            patronActual = top3[indiceActual];
            const { esquema, leyenda } = generarEsquema(patronActual);

            // 🔥 FILTRADO DINÁMICO: Ocultar TODAS las etiquetas primero
            document.querySelectorAll('[id^="etiqueta-"]').forEach((el) => {
                el.style.display = "none";
                el.style.outline = "";
                el.style.background = "";
                el.style.transform = "";
            });

            // 🔥 Mostrar y resaltar SOLO las del patrón actual (posición específica)
            patronActual.etiquetas.forEach((subId) => {
                const safeId = String(subId).replace(/\./g, "-");
                const elemento = document.querySelector(`#etiqueta-${safeId}`);
                if (elemento) {
                    elemento.style.display = "";
                    elemento.style.outline = "4px solid #3b82f6";
                    elemento.style.outlineOffset = "4px";
                    elemento.style.background = "#eff6ff";
                    // ✅ FIX: Solo transicionar propiedades específicas, NO "all"
                    elemento.style.transition = "transform 0.3s ease, background 0.3s ease, outline 0.3s ease";
                    elemento.style.transform = "scale(1.02)";
                }
            });

            const color =
                patronActual.aprovechamiento >= 90
                    ? "#10b981"
                    : patronActual.aprovechamiento >= 80
                        ? "#f59e0b"
                        : "#ef4444";

            document.getElementById("popup-patron-esquema").textContent =
                esquema;
            document.getElementById("popup-patron-leyenda").innerHTML =
                leyenda;
            document.getElementById("popup-patron-barra").textContent = `${(
                patronActual.longitud_barra_cm / 100
            ).toFixed(2)}m`;
            document.getElementById(
                "popup-patron-aprovechamiento"
            ).textContent = `${patronActual.aprovechamiento.toFixed(1)}%`;
            document.getElementById(
                "popup-patron-aprovechamiento"
            ).style.color = color;
            document.getElementById("popup-patron-posicion").textContent = `${indiceActual + 1
                }/${top3.length}`;

            document.getElementById("btn-prev").disabled = indiceActual === 0;
            document.getElementById("btn-next").disabled =
                indiceActual === top3.length - 1;
        };

        // Crear popup HTML a la derecha
        const popup = document.createElement("div");
        popup.id = "popup-patron-optimizado";
        popup.innerHTML = `
            <style>
                #popup-patron-optimizado {
                    position: fixed;
                    top: 80px;
                    right: 20px;
                    background: white;
                    border: 3px solid #3b82f6;
                    border-radius: 16px;
                    padding: 20px 24px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    z-index: 10000;
                    width: 380px;
                    animation: slideInFromRight 0.3s ease-out;
                    cursor: move;
                    user-select: none;
                }

                #popup-patron-optimizado.dragging {
                    opacity: 0.8;
                    cursor: grabbing;
                }

                @keyframes slideInFromRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }

                #popup-patron-optimizado h3 {
                    margin: 0 0 16px 0;
                    font-size: 16px;
                    font-weight: 700;
                    color: #1f2937;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    cursor: grab;
                    padding-bottom: 12px;
                    border-bottom: 2px solid #e5e7eb;
                }

                #popup-patron-optimizado h3:active {
                    cursor: grabbing;
                }

                .drag-handle {
                    color: #9ca3af;
                    font-size: 18px;
                    cursor: grab;
                }

                .drag-handle:active {
                    cursor: grabbing;
                }

                #popup-patron-esquema {
                    font-family: monospace;
                    font-size: 24px;
                    font-weight: bold;
                    color: #1f2937;
                    margin: 12px 0;
                    letter-spacing: 1px;
                    text-align: center;
                }

                #popup-patron-leyenda {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    justify-content: center;
                    margin: 8px 0 16px 0;
                }

                .leyenda-item {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 2px;
                    padding: 6px 10px;
                    background: #f8fafc;
                    border-radius: 8px;
                    border: 1px solid #e2e8f0;
                }

                .leyenda-planilla {
                    display: inline-block;
                    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
                    color: white;
                    font-size: 10px;
                    font-weight: 600;
                    padding: 2px 8px;
                    border-radius: 4px;
                }

                .leyenda-etiqueta {
                    font-size: 11px;
                    color: #475569;
                    font-weight: 500;
                }

                .popup-info-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 12px;
                    margin: 16px 0;
                    padding: 16px 0;
                    border-top: 2px solid #e5e7eb;
                    border-bottom: 2px solid #e5e7eb;
                }

                .popup-info-item {
                    text-align: center;
                }

                .popup-info-label {
                    font-size: 10px;
                    text-transform: uppercase;
                    color: #6b7280;
                    font-weight: 600;
                    margin-bottom: 4px;
                }

                .popup-info-value {
                    font-size: 18px;
                    font-weight: bold;
                    color: #1f2937;
                }

                .popup-botones {
                    display: flex;
                    gap: 8px;
                    margin-top: 12px;
                }

                .popup-botones button {
                    flex: 1;
                    padding: 10px 12px;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 13px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .popup-botones button:disabled {
                    opacity: 0.3;
                    cursor: not-allowed;
                }

                .popup-botones button:not(:disabled):hover {
                    transform: translateY(-1px);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                }

                #btn-fabricar {
                    background: #10b981;
                    color: white;
                }

                #btn-fabricar:hover:not(:disabled) {
                    background: #059669;
                }

                #btn-prev, #btn-next {
                    background: #3b82f6;
                    color: white;
                }

                #btn-prev:hover:not(:disabled), #btn-next:hover:not(:disabled) {
                    background: #2563eb;
                }

                #btn-volver {
                    background: #6b7280;
                    color: white;
                }

                #btn-volver:hover {
                    background: #4b5563;
                }

                #btn-cerrar {
                    background: #ef4444;
                    color: white;
                }

                #btn-cerrar:hover {
                    background: #dc2626;
                }

                .popup-hint {
                    text-align: center;
                    font-size: 11px;
                    color: #9ca3af;
                    margin-top: 12px;
                    font-style: italic;
                }
            </style>

            <h3>
                <span>🎯 Patrón Optimizado</span>
                <span class="drag-handle" title="Arrastra para mover">⋮⋮</span>
            </h3>

            <div id="popup-patron-esquema">A + B + C</div>
            <div id="popup-patron-leyenda">A = ETQ-001 • B = ETQ-002 • C = ETQ-003</div>

            <div class="popup-info-grid">
                <div class="popup-info-item">
                    <div class="popup-info-label">Barra</div>
                    <div id="popup-patron-barra" class="popup-info-value">12m</div>
                </div>
                <div class="popup-info-item">
                    <div class="popup-info-label">Aprovech.</div>
                    <div id="popup-patron-aprovechamiento" class="popup-info-value">95.5%</div>
                </div>
                <div class="popup-info-item">
                    <div class="popup-info-label">Posición</div>
                    <div id="popup-patron-posicion" class="popup-info-value">1/3</div>
                </div>
            </div>

            <div class="popup-botones">
                <button id="btn-prev">← Ant</button>
                <button id="btn-fabricar">✓ Fabricar</button>
                <button id="btn-next">Sig →</button>
            </div>

            <div class="popup-botones" style="margin-top: 8px;">
                <button id="btn-volver">← Volver</button>
                <button id="btn-cerrar">✕ Cerrar</button>
            </div>

            <div class="popup-hint">💡 Arrastra el popup para moverlo</div>
        `;

        document.body.appendChild(popup);

        // 🔥 DRAG & DROP functionality
        let isDragging = false;
        let currentX;
        let currentY;
        let initialX;
        let initialY;
        let xOffset = 0;
        let yOffset = 0;

        const dragStart = (e) => {
            if (e.type === "touchstart") {
                initialX = e.touches[0].clientX - xOffset;
                initialY = e.touches[0].clientY - yOffset;
            } else {
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;
            }

            if (
                e.target.closest("h3") ||
                e.target.classList.contains("drag-handle")
            ) {
                isDragging = true;
                popup.classList.add("dragging");
            }
        };

        const dragEnd = (e) => {
            initialX = currentX;
            initialY = currentY;
            isDragging = false;
            popup.classList.remove("dragging");
        };

        const drag = (e) => {
            if (isDragging) {
                e.preventDefault();

                if (e.type === "touchmove") {
                    currentX = e.touches[0].clientX - initialX;
                    currentY = e.touches[0].clientY - initialY;
                } else {
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                }

                xOffset = currentX;
                yOffset = currentY;

                setTranslate(currentX, currentY, popup);
            }
        };

        const setTranslate = (xPos, yPos, el) => {
            el.style.transform = `translate(${xPos}px, ${yPos}px)`;
        };

        popup.addEventListener("mousedown", dragStart);
        document.addEventListener("mouseup", dragEnd);
        document.addEventListener("mousemove", drag);
        popup.addEventListener("touchstart", dragStart);
        document.addEventListener("touchend", dragEnd);
        document.addEventListener("touchmove", drag);

        // Actualizar con primer patrón
        actualizarPopup();

        // Event listeners para navegación
        document.getElementById("btn-prev").addEventListener("click", () => {
            if (indiceActual > 0) {
                indiceActual--;
                actualizarPopup();
            }
        });

        document.getElementById("btn-next").addEventListener("click", () => {
            if (indiceActual < top3.length - 1) {
                indiceActual++;
                actualizarPopup();
            }
        });

        // Promesa para manejar la decisión del usuario
        return new Promise((resolve) => {
            const limpiarYRestaurar = () => {
                popup.remove();
                // Restaurar TODAS las etiquetas
                document.querySelectorAll('[id^="etiqueta-"]').forEach((el) => {
                    el.style.display = "";
                    el.style.outline = "";
                    el.style.background = "";
                    el.style.transform = "";
                });
            };

            document
                .getElementById("btn-fabricar")
                .addEventListener("click", () => {
                    limpiarYRestaurar();

                    // Generar el esquema de letras para incluirlo en cada etiqueta
                    const { esquema } = generarEsquema(patronActual);

                    console.log('🔧 Patrón generado:', esquema);
                    console.log('📦 Etiquetas con patrón:', patronActual.etiquetas.map((id) => ({
                        etiqueta_sub_id: id,
                        patron_letras: esquema
                    })));

                    resolve({
                        accion: "fabricar",
                        longitudBarraCm: patronActual.longitud_barra_cm,
                        etiquetas: patronActual.etiquetas.map((id) => ({
                            etiqueta_sub_id: id,
                            patron_letras: esquema, // 🔧 Incluir el patrón de letras
                        })),
                        patron: patronActual,
                        patronInfo: {
                            aprovechamiento: patronActual.aprovechamiento,
                            desperdicio_cm: patronActual.desperdicio_cm || 0,
                            esquema: esquema
                        }
                    });
                });

            document
                .getElementById("btn-volver")
                .addEventListener("click", () => {
                    limpiarYRestaurar();
                    resolve("volver");
                });

            document
                .getElementById("btn-cerrar")
                .addEventListener("click", () => {
                    limpiarYRestaurar();
                    resolve(null);
                });
        });
    }

    // ============================================================================
    // API PÚBLICA
    // ============================================================================

    const API = {
        mejorCorteSimple,
        mejorCorteOptimizado,
        enviarAFabricacionOptimizada,
        config: CONFIG,
    };

    // console.log("✅ Módulo Cortes v3.0 Final cargado");

    return API;
})();
