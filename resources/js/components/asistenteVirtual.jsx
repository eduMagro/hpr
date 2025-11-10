import React, { useState, useRef, useEffect } from "react";
import axios from "axios";

/**
 * AsistenteVirtual - Componente FASE 0
 *
 * Interfaz básica pero funcional para probar el asistente
 * Sin estilos complejos, enfocado en funcionalidad
 */
const AsistenteVirtual = () => {
    const [mensajes, setMensajes] = useState([]);
    const [preguntaActual, setPreguntaActual] = useState("");
    const [cargando, setCargando] = useState(false);
    const [mostrarSugerencias, setMostrarSugerencias] = useState(true);
    const [sugerencias, setSugerencias] = useState([]);
    const messagesEndRef = useRef(null);

    // Cargar sugerencias al montar
    useEffect(() => {
        cargarSugerencias();
    }, []);

    // Auto-scroll a último mensaje
    useEffect(() => {
        scrollToBottom();
    }, [mensajes]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    const cargarSugerencias = async () => {
        try {
            const response = await axios.get("/api/asistente/sugerencias");
            if (response.data.success) {
                setSugerencias(response.data.data);
            }
        } catch (error) {
            console.error("Error cargando sugerencias:", error);
        }
    };

    const enviarPregunta = async (pregunta = null) => {
        const preguntaFinal = pregunta || preguntaActual.trim();

        if (!preguntaFinal) return;

        // Agregar pregunta del usuario
        const nuevaPregunta = {
            tipo: "usuario",
            texto: preguntaFinal,
            timestamp: new Date(),
        };

        setMensajes((prev) => [...prev, nuevaPregunta]);
        setPreguntaActual("");
        setCargando(true);
        setMostrarSugerencias(false);

        try {
            const response = await axios.post("/api/asistente/preguntar", {
                pregunta: preguntaFinal,
            });

            if (response.data.success) {
                const respuesta = {
                    tipo: "asistente",
                    texto: response.data.data.respuesta,
                    fuentes: response.data.data.fuentes,
                    coste: response.data.data.coste_estimado,
                    tiempo: response.data.data.tiempo,
                    timestamp: new Date(),
                };

                setMensajes((prev) => [...prev, respuesta]);
            } else {
                throw new Error(response.data.error || "Error desconocido");
            }
        } catch (error) {
            const errorMsg = {
                tipo: "error",
                texto:
                    "Lo siento, hubo un error al procesar tu pregunta: " +
                    (error.response?.data?.error || error.message),
                timestamp: new Date(),
            };
            setMensajes((prev) => [...prev, errorMsg]);
        } finally {
            setCargando(false);
        }
    };

    const handleKeyPress = (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            enviarPregunta();
        }
    };

    const usarSugerencia = (ejemplo) => {
        enviarPregunta(ejemplo);
    };

    return (
        <div style={styles.container}>
            <div style={styles.header}>
                <h2 style={styles.titulo}>🤖 Asistente Virtual</h2>
                <p style={styles.subtitulo}>
                    Pregúntame sobre pedidos, stock, planillas o movimientos
                </p>
            </div>

            <div style={styles.chatContainer}>
                {/* Mensajes */}
                <div style={styles.mensajesContainer}>
                    {mensajes.length === 0 && mostrarSugerencias && (
                        <div style={styles.bienvenida}>
                            <h3 style={styles.bienvenidaTitulo}>
                                👋 ¡Hola! ¿En qué puedo ayudarte?
                            </h3>
                            <p style={styles.bienvenidaTexto}>
                                Prueba preguntar algo como:
                            </p>
                            {sugerencias.map((categoria, idx) => (
                                <div key={idx} style={styles.categoriaBox}>
                                    <h4 style={styles.categoriaTitle}>
                                        {categoria.categoria}
                                    </h4>
                                    {categoria.ejemplos.map((ejemplo, eidx) => (
                                        <button
                                            key={eidx}
                                            style={styles.sugerenciaBtn}
                                            onClick={() =>
                                                usarSugerencia(ejemplo)
                                            }
                                        >
                                            "{ejemplo}"
                                        </button>
                                    ))}
                                </div>
                            ))}
                        </div>
                    )}

                    {mensajes.map((mensaje, index) => (
                        <div
                            key={index}
                            style={{
                                ...styles.mensaje,
                                ...(mensaje.tipo === "usuario"
                                    ? styles.mensajeUsuario
                                    : mensaje.tipo === "error"
                                    ? styles.mensajeError
                                    : styles.mensajeAsistente),
                            }}
                        >
                            <div style={styles.mensajeHeader}>
                                <strong>
                                    {mensaje.tipo === "usuario"
                                        ? "👤 Tú"
                                        : mensaje.tipo === "error"
                                        ? "⚠️ Error"
                                        : "🤖 Asistente"}
                                </strong>
                                <span style={styles.timestamp}>
                                    {mensaje.timestamp.toLocaleTimeString(
                                        "es-ES",
                                        {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        }
                                    )}
                                </span>
                            </div>

                            <div style={styles.mensajeTexto}>
                                {mensaje.texto}
                            </div>

                            {mensaje.fuentes && mensaje.fuentes.length > 0 && (
                                <div style={styles.metadatos}>
                                    📚 Fuentes: {mensaje.fuentes.join(", ")}
                                </div>
                            )}

                            {mensaje.coste && (
                                <div style={styles.metadatos}>
                                    💵 Coste: $
                                    {(mensaje.coste * 1000).toFixed(3)} / ⏱️{" "}
                                    {mensaje.tiempo}s
                                </div>
                            )}
                        </div>
                    ))}

                    {cargando && (
                        <div
                            style={{
                                ...styles.mensaje,
                                ...styles.mensajeAsistente,
                            }}
                        >
                            <div style={styles.cargando}>
                                <span>●</span>
                                <span>●</span>
                                <span>●</span>
                            </div>
                        </div>
                    )}

                    <div ref={messagesEndRef} />
                </div>

                {/* Input */}
                <div style={styles.inputContainer}>
                    <input
                        type="text"
                        style={styles.input}
                        value={preguntaActual}
                        onChange={(e) => setPreguntaActual(e.target.value)}
                        onKeyPress={handleKeyPress}
                        placeholder="Escribe tu pregunta aquí..."
                        disabled={cargando}
                    />
                    <button
                        style={{
                            ...styles.btnEnviar,
                            ...(cargando ? styles.btnEnviarDisabled : {}),
                        }}
                        onClick={() => enviarPregunta()}
                        disabled={cargando || !preguntaActual.trim()}
                    >
                        {cargando ? "⏳" : "📤"} Enviar
                    </button>
                </div>
            </div>

            {/* Footer con estadísticas */}
            <div style={styles.footer}>
                <small style={styles.footerText}>
                    💡 Versión FASE 0 - Prototipo básico | Total consultas hoy:{" "}
                    {mensajes.filter((m) => m.tipo === "usuario").length}
                </small>
            </div>
        </div>
    );
};

// Estilos básicos inline (en producción usar CSS/Tailwind)
const styles = {
    container: {
        maxWidth: "900px",
        margin: "20px auto",
        backgroundColor: "#f5f5f5",
        borderRadius: "12px",
        boxShadow: "0 4px 6px rgba(0,0,0,0.1)",
        overflow: "hidden",
        fontFamily: "Arial, sans-serif",
    },
    header: {
        backgroundColor: "#2563eb",
        color: "white",
        padding: "20px",
        textAlign: "center",
    },
    titulo: {
        margin: "0 0 8px 0",
        fontSize: "24px",
    },
    subtitulo: {
        margin: 0,
        fontSize: "14px",
        opacity: 0.9,
    },
    chatContainer: {
        height: "600px",
        display: "flex",
        flexDirection: "column",
    },
    mensajesContainer: {
        flex: 1,
        overflowY: "auto",
        padding: "20px",
        backgroundColor: "#ffffff",
    },
    bienvenida: {
        textAlign: "center",
        padding: "40px 20px",
    },
    bienvenidaTitulo: {
        fontSize: "22px",
        marginBottom: "10px",
        color: "#333",
    },
    bienvenidaTexto: {
        color: "#666",
        marginBottom: "30px",
    },
    categoriaBox: {
        marginBottom: "20px",
        textAlign: "left",
        backgroundColor: "#f9fafb",
        padding: "15px",
        borderRadius: "8px",
        border: "1px solid #e5e7eb",
    },
    categoriaTitle: {
        margin: "0 0 10px 0",
        fontSize: "16px",
        color: "#374151",
    },
    sugerenciaBtn: {
        display: "block",
        width: "100%",
        textAlign: "left",
        padding: "10px 15px",
        margin: "5px 0",
        backgroundColor: "#ffffff",
        border: "1px solid #d1d5db",
        borderRadius: "6px",
        cursor: "pointer",
        fontSize: "14px",
        color: "#4b5563",
        transition: "all 0.2s",
    },
    mensaje: {
        marginBottom: "15px",
        padding: "12px 16px",
        borderRadius: "8px",
        maxWidth: "80%",
    },
    mensajeUsuario: {
        backgroundColor: "#dbeafe",
        marginLeft: "auto",
        borderBottomRightRadius: "4px",
    },
    mensajeAsistente: {
        backgroundColor: "#f3f4f6",
        marginRight: "auto",
        borderBottomLeftRadius: "4px",
    },
    mensajeError: {
        backgroundColor: "#fee2e2",
        marginRight: "auto",
        borderLeft: "3px solid #ef4444",
    },
    mensajeHeader: {
        display: "flex",
        justifyContent: "space-between",
        marginBottom: "6px",
        fontSize: "12px",
        color: "#6b7280",
    },
    timestamp: {
        fontSize: "11px",
        opacity: 0.7,
    },
    mensajeTexto: {
        fontSize: "14px",
        lineHeight: "1.5",
        whiteSpace: "pre-wrap",
        color: "#1f2937",
    },
    metadatos: {
        marginTop: "8px",
        fontSize: "11px",
        color: "#9ca3af",
        fontStyle: "italic",
    },
    cargando: {
        display: "flex",
        gap: "4px",
    },
    inputContainer: {
        display: "flex",
        padding: "15px",
        backgroundColor: "#ffffff",
        borderTop: "1px solid #e5e7eb",
        gap: "10px",
    },
    input: {
        flex: 1,
        padding: "12px 16px",
        border: "1px solid #d1d5db",
        borderRadius: "8px",
        fontSize: "14px",
        outline: "none",
    },
    btnEnviar: {
        padding: "12px 24px",
        backgroundColor: "#2563eb",
        color: "white",
        border: "none",
        borderRadius: "8px",
        cursor: "pointer",
        fontSize: "14px",
        fontWeight: "bold",
        transition: "background-color 0.2s",
    },
    btnEnviarDisabled: {
        backgroundColor: "#9ca3af",
        cursor: "not-allowed",
    },
    footer: {
        padding: "12px 20px",
        backgroundColor: "#f9fafb",
        borderTop: "1px solid #e5e7eb",
        textAlign: "center",
    },
    footerText: {
        color: "#6b7280",
        fontSize: "12px",
    },
};

// Animación CSS para puntos de carga
const styleSheet = document.styleSheets[0];
styleSheet.insertRule(
    `
    @keyframes blink {
        0%, 20% { opacity: 0.2; }
        50% { opacity: 1; }
        100% { opacity: 0.2; }
    }
`,
    styleSheet.cssRules.length
);

// Aplicar animación a los puntos
document.querySelectorAll(".cargando span").forEach((span, i) => {
    span.style.animation = `blink 1.4s infinite ${i * 0.2}s`;
});

export default AsistenteVirtual;
