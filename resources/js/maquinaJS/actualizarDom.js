/**
 * ================================================================================
 * SISTEMA CENTRALIZADO DE ACTUALIZACIÓN DEL DOM - VERSIÓN FINAL CORREGIDA
 * ================================================================================
 * ✅ Compatible con estructura HTML real (.proceso .estado-{estado})
 * ✅ Usa CSS variables (--bg-estado)
 * ✅ Sincronización automática completa
 * ✅ Inyección de colores incluida
 * ================================================================================
 */

(function (global) {
    "use strict";

    // ========================================================================
    // INYECCIÓN DE COLORES DE ESTADO (antes en coloresEstado.js)
    // ========================================================================
    const COLORES_ESTADO = {
        pendiente: "#ffffff", // blanco
        fabricando: "#facc15", // amarillo
        ensamblando: "#facc15", // amarillo
        soldando: "#facc15", // amarillo
        fabricada: "#22c55e", // verde
        completada: "#22c55e", // verde
        ensamblada: "#22c55e", // verde
        soldada: "#22c55e", // verde
        "en-paquete": "#e3e4FA", // morado/lavanda
    };

    // Inyectar estilos de colores en el head
    function inyectarColoresEstado() {
        if (document.getElementById("colores-estado-css")) {
            console.log("⚠️ Los estilos de colores ya están inyectados");
            return;
        }

        let css =
            "\n/* === Colores de estado (inyectados dinámicamente) === */\n.proceso {\n    --bg-estado: #e5e7eb;\n}\n";

        for (const [estado, color] of Object.entries(COLORES_ESTADO)) {
            css += `.proceso.estado-${estado} {\n    --bg-estado: ${color};\n}\n`;
        }

        const style = document.createElement("style");
        style.id = "colores-estado-css";
        style.textContent = css;
        document.head.appendChild(style);

        console.log(
            "✅ Colores de estado inyectados:",
            Object.keys(COLORES_ESTADO).length,
            "estados"
        );
    }

    // ⚠️ DESHABILITADO: Los colores ahora están en app.css para evitar reflows
    // La inyección dinámica causaba problemas de desplazamiento en los selects
    // inyectarColoresEstado();
    console.log('ℹ️ Colores de estado cargados desde app.css (no inyectados dinámicamente)');

    // ========================================================================
    // CONFIGURACIÓN DE COLORES Y ESTADOS (según CSS del proyecto)
    // ========================================================================
    const ESTADOS_CONFIG = {
        pendiente: {
            cssClass: "estado-pendiente",
            bgColor: "#ffffff",
            texto: "Pendiente",
            icono: "⏳",
        },
        fabricando: {
            cssClass: "estado-fabricando",
            bgColor: "#facc15",
            texto: "Fabricando",
            icono: "⚙️",
        },
        fabricada: {
            cssClass: "estado-fabricada",
            bgColor: "#22c55e",
            texto: "Fabricada",
            icono: "✅",
        },
        ensamblando: {
            cssClass: "estado-ensamblando",
            bgColor: "#facc15",
            texto: "Ensamblando",
            icono: "🔧",
        },
        ensamblada: {
            cssClass: "estado-ensamblada",
            bgColor: "#22c55e",
            texto: "Ensamblada",
            icono: "✅",
        },
        soldando: {
            cssClass: "estado-soldando",
            bgColor: "#facc15",
            texto: "Soldando",
            icono: "🔥",
        },
        soldada: {
            cssClass: "estado-soldada",
            bgColor: "#22c55e",
            texto: "Soldada",
            icono: "✅",
        },
        completada: {
            cssClass: "estado-completada",
            bgColor: "#22c55e",
            texto: "Completada",
            icono: "✅",
        },
        empaquetada: {
            cssClass: "estado-en-paquete",
            bgColor: "#e3e4FA", // Morado/lavanda (mismo que el CSS)
            texto: "Empaquetada",
            icono: "📦",
        },
        "en-paquete": {
            cssClass: "estado-en-paquete",
            bgColor: "#e3e4FA", // Morado/lavanda (mismo que el CSS)
            texto: "En Paquete",
            icono: "📦",
        },
    };

    // ========================================================================
    // FUNCIÓN PRINCIPAL: ACTUALIZAR ESTADO DE ETIQUETA
    // ========================================================================
    function actualizarEstadoEtiqueta(
        etiquetaId,
        nuevoEstado,
        datosExtra = {}
    ) {
        console.log(
            `🔄 Actualizando etiqueta ${etiquetaId} a estado: ${nuevoEstado}`
        );

        const safeId = String(etiquetaId).replace(/\./g, "-");
        const elemento = document.querySelector(`#etiqueta-${safeId}`);

        if (!elemento) {
            console.warn(
                `❌ No se encontró elemento para etiqueta: ${etiquetaId}`
            );
            return false;
        }

        const estadoNormalizado = String(nuevoEstado).toLowerCase().trim();
        const config = ESTADOS_CONFIG[estadoNormalizado];

        if (!config) {
            console.warn(`⚠️ Estado no reconocido: ${nuevoEstado}`);
            return false;
        }

        // 1. Actualizar dataset
        elemento.dataset.estado = estadoNormalizado;

        // 2. Actualizar clases CSS (eliminar todas las clases estado-*)
        const clases = Array.from(elemento.classList);
        clases.forEach((clase) => {
            if (clase.startsWith("estado-")) {
                elemento.classList.remove(clase);
            }
        });

        // 3. Añadir nueva clase de estado
        elemento.classList.add(config.cssClass);

        // 4. Actualizar CSS variable --bg-estado
        elemento.style.setProperty("--bg-estado", config.bgColor);

        // 5. Actualizar background del SVG si existe
        const contenedorSvg = elemento.querySelector('[id^="contenedor-svg-"]');
        if (contenedorSvg) {
            const svg = contenedorSvg.querySelector("svg");
            if (svg) {
                svg.style.background = config.bgColor;
            }
        }

        // 6. Actualizar color de fondo de la card
        const card = elemento.querySelector(".etiqueta-card") || elemento;
        if (card) {
            card.style.background = config.bgColor;
        }

        // 7. Gestionar botones según el estado
        gestionarBotones(elemento, estadoNormalizado);

        // 9. Aplicar animación
        aplicarAnimacion(elemento);

        // 10. Disparar evento de actualización
        window.dispatchEvent(
            new CustomEvent("etiqueta:actualizada", {
                detail: {
                    etiquetaId,
                    estado: estadoNormalizado,
                    datosExtra,
                },
            })
        );

        console.log(`✅ Etiqueta ${etiquetaId} actualizada a ${nuevoEstado}`);
        return true;
    }

    // ========================================================================
    // GESTIONAR BOTONES SEGÚN ESTADO
    // ========================================================================
    function gestionarBotones(elemento, estado) {
        const botonesFabricar = elemento.querySelectorAll(".btn-fabricar");

        const estadosFinales = ["empaquetada", "en-paquete"];

        botonesFabricar.forEach((btn) => {
            if (estadosFinales.includes(estado)) {
                btn.disabled = true;
                btn.style.opacity = "0.5";
                btn.style.cursor = "not-allowed";
                btn.title = `Etiqueta ya ${estado}`;
            } else {
                btn.disabled = false;
                btn.style.opacity = "1";
                btn.style.cursor = "pointer";
                btn.title = "Fabricar esta etiqueta";
            }
        });
    }

    // ========================================================================
    // APLICAR ANIMACIÓN
    // ========================================================================
    function aplicarAnimacion(elemento) {
        // ✅ FIX: Solo transicionar transform y background, NO "all"
        // "all" causaba reflows globales afectando selectsde toda la aplicación
        elemento.style.transition = "transform 0.5s ease, background-color 0.5s ease";
        elemento.style.transform = "scale(1.03)";

        setTimeout(() => {
            elemento.style.transform = "scale(1)";
        }, 400);
    }

    // ========================================================================
    // ACTUALIZAR MÚLTIPLES ETIQUETAS (PARA PAQUETES)
    // ========================================================================
    function actualizarMultiplesEtiquetas(
        etiquetaIds,
        estado,
        datosExtra = {}
    ) {
        console.log(`🔄 Actualizando ${etiquetaIds.length} etiquetas...`);

        const resultados = etiquetaIds.map((id) =>
            actualizarEstadoEtiqueta(id, estado, datosExtra)
        );

        const exitosas = resultados.filter((r) => r).length;
        console.log(
            `✅ ${exitosas}/${etiquetaIds.length} etiquetas actualizadas`
        );

        return exitosas;
    }

    // ========================================================================
    // OBTENER ESTADO ACTUAL DE UNA ETIQUETA
    // ========================================================================
    function obtenerEstadoActual(etiquetaId) {
        const safeId = String(etiquetaId).replace(/\./g, "-");
        const elemento = document.querySelector(`#etiqueta-${safeId}`);

        if (!elemento) return null;

        return {
            id: etiquetaId,
            estado: elemento.dataset.estado || "desconocido",
            elemento: elemento,
        };
    }

    // ========================================================================
    // LISTENER: CREAR PAQUETE
    // ========================================================================
    window.addEventListener("paquete:creado", (event) => {
        const { codigoPaquete, etiquetaIds } = event.detail;

        console.log(
            `📦 Evento paquete:creado recibido - ${codigoPaquete} con ${etiquetaIds.length} etiquetas`
        );

        // Actualizar todas las etiquetas a estado empaquetada
        actualizarMultiplesEtiquetas(etiquetaIds, "empaquetada", {
            codigo_paquete: codigoPaquete,
        });
    });

    // ========================================================================
    // EXPONER API PÚBLICA
    // ========================================================================
    global.SistemaDOM = {
        actualizarEstadoEtiqueta,
        actualizarMultiplesEtiquetas,
        obtenerEstadoActual,
        ESTADOS_CONFIG,
    };

    // API de colores (antes ColoresEstado)
    global.ColoresEstado = {
        actualizar: (estado, nuevoColor) => {
            COLORES_ESTADO[estado] = nuevoColor;
            const styleElement = document.getElementById("colores-estado-css");
            if (styleElement) {
                let css =
                    "\n/* === Colores de estado (inyectados dinámicamente) === */\n.proceso {\n    --bg-estado: #e5e7eb;\n}\n";
                for (const [est, col] of Object.entries(COLORES_ESTADO)) {
                    css += `.proceso.estado-${est} {\n    --bg-estado: ${col};\n}\n`;
                }
                styleElement.textContent = css;
                console.log(
                    `✅ Color actualizado para estado "${estado}": ${nuevoColor}`
                );
            }
        },
        obtenerColor: (estado) => COLORES_ESTADO[estado],
        todos: () => ({ ...COLORES_ESTADO }),
    };

    console.log("✅ Sistema centralizado de DOM inicializado");
})(window);
