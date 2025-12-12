/**
 * Sistema de Resumen de Etiquetas
 * Agrupa visualmente etiquetas con mismo diámetro y dimensiones
 */

/**
 * Ejecuta el proceso de resumen de etiquetas
 * @param {number|null} planillaId - ID de la planilla
 * @param {number|null} maquinaId - ID de la máquina (opcional)
 */
window.resumirEtiquetas = async function(planillaId, maquinaId) {
    if (!planillaId) {
        Swal.fire({
            icon: 'warning',
            title: 'Planilla requerida',
            text: 'Debes seleccionar una planilla para resumir etiquetas',
        });
        return;
    }

    // 1. Obtener preview
    Swal.fire({
        title: 'Analizando etiquetas...',
        text: 'Buscando etiquetas con mismo diámetro y dimensiones',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const params = new URLSearchParams();
        params.set('planilla_id', planillaId);
        if (maquinaId) params.set('maquina_id', maquinaId);

        const response = await fetch(`/api/etiquetas/resumir/preview?${params}`);
        const preview = await response.json();

        if (!preview.grupos || preview.grupos.length === 0) {
            return Swal.fire({
                icon: 'info',
                title: 'Sin grupos para resumir',
                text: 'No hay etiquetas con mismo diámetro y dimensiones que agrupar',
            });
        }

        // 2. Construir HTML del preview con detalles de etiquetas y elementos
        // Guardamos los datos de elementos para dibujar después
        window._elementosParaDibujar = [];

        const gruposHtml = preview.grupos.map((g, idx) => {
            // Detalle de cada etiqueta del grupo
            const etiquetasHtml = g.etiquetas.map((et, etIdx) => {
                const elementosHtml = et.elementos_detalle.map((el, elIdx) => {
                    const canvasId = `mini-fig-${idx}-${etIdx}-${elIdx}`;
                    // Guardar datos para dibujar después
                    window._elementosParaDibujar.push({
                        canvasId,
                        dimensiones: el.dimensiones || '',
                        peso: el.peso,
                        diametro: el.diametro
                    });

                    return `
                        <div class="inline-block bg-white border border-gray-200 rounded p-1 mr-1 mb-1 text-center" style="min-width: 80px;">
                            <div class="text-xs text-gray-500 font-mono">${el.codigo || el.marca}</div>
                            <canvas id="${canvasId}" class="w-full" style="height: 50px; display: block;"></canvas>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="pl-4 py-2 text-xs border-l-2 border-teal-200 ml-2 mb-1 bg-gray-50 rounded-r">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-gray-700">${et.etiqueta_sub_id}</span>
                            <span class="text-gray-400 bg-white px-2 py-0.5 rounded">${et.planilla_codigo}</span>
                        </div>
                        <div class="flex flex-wrap">${elementosHtml}</div>
                    </div>
                `;
            }).join('');

            return `
                <div class="py-2 border-b border-gray-200 last:border-0">
                    <div class="flex justify-between items-center cursor-pointer" onclick="document.getElementById('grupo-detalle-${idx}').classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180');">
                        <div class="flex-1">
                            <span class="font-bold text-teal-700">Ø${g.diametro}</span>
                            <span class="text-gray-500 text-sm ml-2">${g.dimensiones || 'barra'}</span>
                        </div>
                        <div class="text-right flex items-center gap-3">
                            <span class="bg-teal-100 text-teal-800 px-2 py-1 rounded text-sm font-medium">
                                ${g.total_etiquetas} etiquetas
                            </span>
                            <span class="text-gray-500 text-sm">${g.total_elementos} elem</span>
                            <span class="text-gray-400 text-sm">${g.peso_total} kg</span>
                            <svg class="w-4 h-4 text-gray-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                    <div id="grupo-detalle-${idx}" class="hidden mt-2">
                        ${etiquetasHtml}
                    </div>
                </div>
            `;
        }).join('');

        // 3. Mostrar preview y confirmar
        const result = await Swal.fire({
            icon: 'question',
            title: 'Resumir Etiquetas',
            html: `
                <div class="text-left">
                    <p class="mb-4 text-gray-600">
                        Se crearán <strong class="text-teal-600">${preview.total_grupos} grupos</strong>
                        agrupando <strong class="text-teal-600">${preview.total_etiquetas} etiquetas</strong>
                        (${preview.total_elementos} elementos, ${preview.peso_total} kg):
                    </p>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                        ${gruposHtml}
                    </div>
                    <p class="mt-4 text-sm text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Las etiquetas originales se mantienen para imprimir
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#14b8a6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Resumir',
            cancelButtonText: 'Cancelar',
            width: '700px',
            didOpen: () => {
                // Dibujar las mini figuras después de que el modal esté abierto
                if (typeof window.dibujarFiguraElemento === 'function' && window._elementosParaDibujar) {
                    setTimeout(() => {
                        window._elementosParaDibujar.forEach(el => {
                            const canvas = document.getElementById(el.canvasId);
                            if (canvas) {
                                canvas.width = canvas.clientWidth || 80;
                                canvas.height = canvas.clientHeight || 50;
                                window.dibujarFiguraElemento(el.canvasId, el.dimensiones, null, el.diametro);
                            }
                        });
                    }, 100);
                }
            }
        });

        if (!result.isConfirmed) return;

        // 4. Ejecutar resumen
        Swal.fire({
            title: 'Resumiendo etiquetas...',
            text: 'Creando grupos de resumen',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const execResponse = await fetch('/api/etiquetas/resumir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                planilla_id: planillaId,
                maquina_id: maquinaId
            })
        });

        const resultado = await execResponse.json();

        if (resultado.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Resumen completado',
                html: `
                    <p class="text-gray-600">${resultado.message}</p>
                    <p class="mt-3 text-sm text-gray-500">
                        Puedes desagrupar en cualquier momento haciendo clic en la X del grupo.
                    </p>
                `,
                confirmButtonColor: '#14b8a6',
            });

            // Refrescar vista
            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                window.refrescarEtiquetasMaquina();
            } else {
                location.reload();
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: resultado.message || 'No se pudo completar el resumen',
            });
        }

    } catch (error) {
        console.error('Error al resumir etiquetas:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
        });
    }
};

/**
 * Expande/colapsa la lista de etiquetas de un grupo
 * @param {number} grupoId - ID del grupo
 */
window.toggleExpandirGrupo = function(grupoId) {
    const lista = document.getElementById(`grupo-lista-${grupoId}`);
    const chevron = document.querySelector(`.grupo-chevron-${grupoId}`);

    if (lista) {
        lista.classList.toggle('hidden');
    }
    if (chevron) {
        chevron.classList.toggle('rotate-180');
    }
};

/**
 * Desagrupa un grupo de resumen
 * @param {number} grupoId - ID del grupo a desagrupar
 */
window.desagruparGrupo = async function(grupoId) {
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Desagrupar?',
        text: 'Las etiquetas volverán a mostrarse individualmente',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, desagrupar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Desagrupando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(`/api/etiquetas/resumir/${grupoId}/desagrupar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const resultado = await response.json();

        if (resultado.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Grupo desagrupado',
                text: resultado.message,
                timer: 2000,
                showConfirmButton: false,
            });

            // Refrescar vista
            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                window.refrescarEtiquetasMaquina();
            } else {
                location.reload();
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: resultado.message,
            });
        }
    } catch (error) {
        console.error('Error al desagrupar:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
        });
    }
};

/**
 * Imprime todas las etiquetas originales de un grupo (una detrás de otra)
 * @param {number} grupoId - ID del grupo
 */
window.imprimirTodasEtiquetasGrupo = async function(grupoId) {
    Swal.fire({
        title: 'Preparando impresión...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(`/api/etiquetas/resumir/${grupoId}/imprimir`);
        const data = await response.json();

        if (data.success && data.etiquetas) {
            Swal.close();

            // Obtener los etiqueta_sub_id para imprimir
            const etiquetasSubIds = data.etiquetas.map(e => e.etiqueta_sub_id);

            // Usar la función imprimirEtiquetas que imprime la etiqueta completa con SVG
            if (typeof window.imprimirEtiquetas === 'function') {
                // Obtener el modo de impresión seleccionado
                const selectModo = document.getElementById(`modo-impresion-grupo-${grupoId}`);
                const modo = selectModo ? selectModo.value : 'a6';

                window.imprimirEtiquetas(etiquetasSubIds, modo);
            } else if (typeof window.imprimirQRsEnCadena === 'function') {
                // Fallback a impresión de QRs
                window.imprimirQRsEnCadena(etiquetasSubIds);
            } else {
                // Fallback: mostrar lista de etiquetas
                const listaHtml = data.etiquetas.map(e =>
                    `<li class="py-1">${e.etiqueta_sub_id} - ${e.nombre || ''}</li>`
                ).join('');

                Swal.fire({
                    icon: 'info',
                    title: 'Etiquetas para imprimir',
                    html: `
                        <p class="mb-3">Imprime las siguientes etiquetas:</p>
                        <ul class="text-left text-sm max-h-60 overflow-y-auto border rounded p-3">
                            ${listaHtml}
                        </ul>
                    `,
                    confirmButtonColor: '#14b8a6',
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudieron obtener las etiquetas',
            });
        }
    } catch (error) {
        console.error('Error al obtener etiquetas para imprimir:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
        });
    }
};

/**
 * Imprime una etiqueta individual
 * @param {string} etiquetaSubId - ID de la subetiqueta
 */
window.imprimirEtiquetaIndividual = function(etiquetaSubId) {
    if (typeof window.imprimirQR === 'function') {
        window.imprimirQR(etiquetaSubId);
    } else {
        console.warn('Función imprimirQR no disponible');
        Swal.fire({
            icon: 'info',
            title: 'Imprimir etiqueta',
            text: `Etiqueta: ${etiquetaSubId}`,
        });
    }
};

/**
 * Desagrupa todos los grupos de una planilla
 * @param {number} planillaId - ID de la planilla
 * @param {number|null} maquinaId - ID de la máquina (opcional)
 */
window.desagruparTodos = async function(planillaId, maquinaId = null) {
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Desagrupar todos?',
        text: 'Todas las etiquetas volverán a mostrarse individualmente',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, desagrupar todos',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Desagrupando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('/api/etiquetas/resumir/desagrupar-todos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                planilla_id: planillaId,
                maquina_id: maquinaId
            })
        });

        const resultado = await response.json();

        if (resultado.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Grupos desagrupados',
                text: resultado.message,
                timer: 2000,
                showConfirmButton: false,
            });

            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                window.refrescarEtiquetasMaquina();
            } else {
                location.reload();
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: resultado.message,
            });
        }
    } catch (error) {
        console.error('Error al desagrupar todos:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
        });
    }
};

console.log('Sistema de resumen de etiquetas cargado');
