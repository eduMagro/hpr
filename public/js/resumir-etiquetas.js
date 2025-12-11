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

        // 2. Construir HTML del preview
        const gruposHtml = preview.grupos.map(g => `
            <div class="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
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
                </div>
            </div>
        `).join('');

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
            width: '550px',
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
 * Imprime todas las etiquetas de un grupo
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

            // Usar el sistema de impresión QR existente si está disponible
            if (typeof window.imprimirQRsEnCadena === 'function') {
                const etiquetasParaImprimir = data.etiquetas.map(e => e.etiqueta_sub_id);
                window.imprimirQRsEnCadena(etiquetasParaImprimir);
            } else if (typeof window.imprimirQR === 'function') {
                // Imprimir una por una
                for (const etiqueta of data.etiquetas) {
                    await window.imprimirQR(etiqueta.etiqueta_sub_id);
                }
            } else {
                // Fallback: mostrar lista de etiquetas para impresión manual
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
