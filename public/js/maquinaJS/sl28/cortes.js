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
            console.log("🎯 [Cortes] Calculando patrones optimizados");

            let patrones = patronesPrevios;

            if (!patrones || patrones.length === 0) {
                const url = CONFIG.endpoints.calcularPatronOptimizado.replace(
                    "{id}",
                    etiquetaId
                );
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({ diametro, kmax: 5 }),
                });

                if (!response.ok) {
                    throw new Error("Error al calcular patrones optimizados");
                }

                const data = await response.json();
                patrones = data.top_global || [];
            }

            if (patrones.length === 0) {
                await Swal.fire({
                    icon: "warning",
                    title: "Sin patrones",
                    timer: 2000,
                });
                return null;
            }

            const resultado = await mostrarPopupOptimizado({
                etiquetaId,
                diametro,
                patrones,
            });

            return resultado;
        } catch (error) {
            console.error("❌ Error:", error);
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

    async function enviarAFabricacionOptimizada(params) {
        const { longitudBarraCm, etiquetas, csrfToken, onUpdate } = params;

        try {
            const payload = {
                producto_base: { longitud_barra_cm: longitudBarraCm },
                repeticiones: 1,
                etiquetas: etiquetas.map((e) => ({
                    etiqueta_sub_id: e.etiqueta_sub_id || e,
                    patron_letras: e.patron?.patron_letras || "",
                })),
            };

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
                return `
            <div class="patron-simple" data-index="${i}" style="
                border: 2px solid ${c.borde};
                background: ${c.fondo};
                padding: 12px 16px;
                border-radius: 8px;
                margin: 8px 0;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: all 0.2s;
            ">
                <div>
                    <span style="font-size: 20px; font-weight: bold;">${
                        p.longitud_m
                    }m</span>
        
                    <span style="font-size: 20px; font-weight: bold;">${esquemaLetras}</span>
        
                    <span style="margin-left: 12px; color: #6b7280;">→ ${
                        p.por_barra
                    } piezas</span>
                    ${
                        !p.disponible_en_maquina
                            ? '<span style="margin-left: 8px; color: #ef4444; font-size: 12px;">(no disponible)</span>'
                            : ""
                    }
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 24px; font-weight: bold; color: ${
                        c.texto
                    };">
                        ${p.aprovechamiento.toFixed(1)}%
                    </div>
                    <div style="font-size: 12px; color: #6b7280;">
                        Desperdicio: ${p.sobra_cm} cm
                    </div>
                </div>
            </div>
        `;
            })
            .join("");

        let seleccionado = null;

        const resultado = await Swal.fire({
            title: `📏 Corte Simple - ${etiquetaId}`,
            html: `
            <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                ${htmlOpciones}
            </div>
            <p style="margin-top: 16px; font-size: 13px; color: #6b7280;">
                💡 Click para seleccionar una opción
            </p>
        `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: "✓ Fabricar",
            denyButtonText: "🔍 Optimizar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#10b981",
            denyButtonColor: "#3b82f6",
            width: "600px",
            didOpen: () => {
                document.querySelectorAll(".patron-simple").forEach((el) => {
                    el.addEventListener("click", function () {
                        document
                            .querySelectorAll(".patron-simple")
                            .forEach((e) => {
                                e.style.boxShadow = "none";
                            });
                        this.style.boxShadow = "0 0 0 3px #3b82f6";
                        seleccionado =
                            disponibles[parseInt(this.dataset.index)];
                    });
                });

                const primero = document.querySelector(".patron-simple");
                if (primero) {
                    primero.click();
                }
            },
        });

        if (resultado.isConfirmed && seleccionado) {
            return {
                accion: "fabricar_patron_simple",
                longitud_m: seleccionado.longitud_m,
                patron: seleccionado,
            };
        } else if (resultado.isDenied) {
            return { accion: "optimizar" };
        }

        return null;
    }
    // ============================================================================
    // POPUP OPTIMIZADO (Derecha, draggeable, filtrado dinámico)
    // ============================================================================

    async function mostrarPopupOptimizado(datos) {
        const { patrones } = datos;

        const top3 = [...patrones]
            .sort((a, b) => b.aprovechamiento - a.aprovechamiento)
            .slice(0, 3);

        let indiceActual = 0;
        let patronActual = top3[0];

        // Generar esquema simple (A + B + A + C)
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

            const leyenda = Object.entries(letras)
                .map(([id, letra]) => `${letra} = ${id}`)
                .join(" • ");

            return { esquema, leyenda };
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
            document.getElementById("popup-patron-leyenda").textContent =
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
            document.getElementById("popup-patron-posicion").textContent = `${
                indiceActual + 1
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
                    font-size: 12px;
                    color: #6b7280;
                    margin: 8px 0 16px 0;
                    text-align: center;
                    line-height: 1.5;
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
                    resolve({
                        accion: "fabricar",
                        longitudBarraCm: patronActual.longitud_barra_cm,
                        etiquetas: patronActual.etiquetas.map((id) => ({
                            etiqueta_sub_id: id,
                        })),
                        patron: patronActual,
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

    console.log("✅ Módulo Cortes v3.0 Final cargado");

    return API;
})();
