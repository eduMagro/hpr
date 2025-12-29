function eliminarPaquete(paqueteId) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/paquetes/${paqueteId}`, {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Actualizar DOM: quitar código de paquete de las etiquetas liberadas
                        // Usar etiquetas_data si está disponible (contiene id y estado)
                        const etiquetasData = data.etiquetas_data || (data.etiquetas_ids || []).map(id => ({ id, estado: 'fabricada' }));

                        if (etiquetasData.length > 0) {
                            console.log(`🗑️ Paquete ${data.codigo_paquete} eliminado, liberando ${etiquetasData.length} etiquetas`);

                            etiquetasData.forEach((etiqueta) => {
                                const etiquetaId = etiqueta.id;
                                const estadoReal = etiqueta.estado || 'fabricada';

                                // Usar la función de gestionPaquetes.js si está disponible
                                if (window.GestionPaquetesDOM && window.GestionPaquetesDOM.actualizarEstadoEtiquetaPaquete) {
                                    // Primero quitar estado en-paquete, luego restaurar el color según estado real
                                    limpiarCodigoPaqueteDeEtiqueta(etiquetaId, estadoReal);
                                } else {
                                    // Fallback: actualizar manualmente
                                    limpiarCodigoPaqueteDeEtiqueta(etiquetaId, estadoReal);
                                }
                            });

                            // Emitir evento para que otros componentes se actualicen
                            window.dispatchEvent(new CustomEvent('paquete:eliminado', {
                                detail: {
                                    paqueteId: paqueteId,
                                    codigoPaquete: data.codigo_paquete,
                                    etiquetasIds: data.etiquetas_ids,
                                    etiquetasData: etiquetasData
                                }
                            }));
                        }

                        Swal.fire({
                            icon: "success",
                            title: "Eliminado",
                            text: data.message,
                            confirmButtonColor: "#28a745",
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Refrescar el componente de gestión de paquetes si existe
                        if (window.refrescarGestionPaquetes) {
                            window.refrescarGestionPaquetes();
                        }
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: data.message,
                            confirmButtonColor: "#d33",
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error en fetch:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error en conexión",
                        text: "No se pudo conectar con el servidor.",
                        confirmButtonColor: "#d33",
                    });
                });
        }
    });
}

/**
 * Limpia el código de paquete de una etiqueta y restaura su color según el estado real
 * @param {string} etiquetaId - ID de la etiqueta
 * @param {string} estado - Estado real de la etiqueta (fabricada, completada, ensamblada, soldada, pendiente)
 */
function limpiarCodigoPaqueteDeEtiqueta(etiquetaId, estado = 'fabricada') {
    const safeId = String(etiquetaId).replace(/\./g, "-");

    // Buscar por diferentes métodos
    let elemento = document.querySelector(`#etiqueta-${safeId}`);

    if (!elemento) {
        const wrapper = document.querySelector(`[data-etiqueta-sub-id="${etiquetaId}"]`);
        if (wrapper) {
            elemento = wrapper;
        }
    }

    if (!elemento) {
        // Buscar en grupos
        const grupos = document.querySelectorAll('[data-etiquetas-sub-ids]');
        for (const grupo of grupos) {
            try {
                const etiquetasEnGrupo = JSON.parse(grupo.dataset.etiquetasSubIds || '[]');
                if (etiquetasEnGrupo.includes(etiquetaId)) {
                    elemento = grupo;
                    break;
                }
            } catch (e) {}
        }
    }

    if (elemento) {
        // Quitar código de paquete inline
        const paqueteCodigoSpan = elemento.querySelector('.paquete-codigo');
        if (paqueteCodigoSpan) {
            paqueteCodigoSpan.textContent = '';
            paqueteCodigoSpan.style.display = 'none';
        }

        // Quitar clase de en-paquete
        elemento.classList.remove('estado-en-paquete');
        delete elemento.dataset.paquete;

        // Determinar color de fondo según el estado real
        const coloresPorEstado = {
            'fabricada': '#d4edda',      // Verde claro
            'completada': '#d4edda',     // Verde claro
            'ensamblada': '#d4edda',     // Verde claro
            'soldada': '#d4edda',        // Verde claro
            'fabricando': '#fff3cd',     // Amarillo claro
            'pendiente': '#ffffff',      // Blanco
            'en-paquete': '#e3e4FA'      // Lavanda
        };

        const colorFondo = coloresPorEstado[estado] || '#d4edda';

        // Actualizar SVG y card
        const contenedorSvg = elemento.querySelector('[id^="contenedor-svg-"]');
        if (contenedorSvg) {
            const svg = contenedorSvg.querySelector('svg');
            if (svg) {
                svg.style.background = colorFondo;
            }
        }

        const card = elemento.querySelector('.etiqueta-card') || elemento;
        if (card) {
            card.style.background = colorFondo;
        }

        console.log(`✅ Código de paquete eliminado de etiqueta ${etiquetaId}, estado restaurado: ${estado}`);
    }
}
