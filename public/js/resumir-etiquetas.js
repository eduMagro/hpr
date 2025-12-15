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
 * Imprime todas las etiquetas originales de un grupo
 * Flujo: Desagrupar → Refrescar → Imprimir → Volver a agrupar
 * @param {number} grupoId - ID del grupo
 */
window.imprimirTodasEtiquetasGrupo = async function(grupoId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    console.log('🖨️ Iniciando impresión de grupo:', grupoId);

    Swal.fire({
        title: 'Preparando impresión...',
        html: 'Obteniendo datos del grupo...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        // 1. Obtener datos del grupo antes de desagrupar (para poder reagrupar después)
        console.log('📋 Paso 1: Obteniendo datos del grupo...');
        const dataResponse = await fetch(`/api/etiquetas/resumir/${grupoId}/imprimir`);
        const dataInfo = await dataResponse.json();
        console.log('📋 Datos obtenidos:', dataInfo);

        if (!dataInfo.success || !dataInfo.etiquetas || dataInfo.etiquetas.length === 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron obtener las etiquetas' });
            return;
        }

        const etiquetasSubIds = dataInfo.etiquetas.map(e => e.etiqueta_sub_id);
        // Obtener planilla_id y maquina_id del grupo (devueltos por la API)
        const planillaId = dataInfo.grupo?.planilla_id || null;
        const maquinaId = dataInfo.grupo?.maquina_id || window.maquinaId || null;

        console.log('📋 Etiquetas a imprimir:', etiquetasSubIds);
        console.log('📋 planillaId:', planillaId, 'maquinaId:', maquinaId);

        // Obtener el modo de impresión seleccionado
        const selectModo = document.getElementById(`modo-impresion-grupo-${grupoId}`);
        const modo = selectModo ? selectModo.value : 'a6';
        console.log('📋 Modo de impresión:', modo);

        // 2. Desagrupar
        console.log('🔓 Paso 2: Desagrupando...');
        Swal.update({ html: 'Desagrupando para renderizar figuras...' });
        const desagruparResponse = await fetch(`/api/etiquetas/resumir/${grupoId}/desagrupar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });

        const desagruparResult = await desagruparResponse.json();
        console.log('🔓 Resultado desagrupar:', desagruparResult);
        if (!desagruparResult.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo desagrupar: ' + (desagruparResult.message || '') });
            return;
        }

        // 3. Refrescar la vista para que las etiquetas individuales se rendericen
        console.log('🔄 Paso 3: Refrescando vista...');
        Swal.update({ html: 'Renderizando etiquetas...' });
        if (typeof window.refrescarEtiquetasMaquina === 'function') {
            await window.refrescarEtiquetasMaquina();
        }

        // 4. Esperar a que los SVGs se rendericen completamente
        console.log('⏳ Paso 4: Esperando renderizado de SVGs...');
        await new Promise(resolve => setTimeout(resolve, 800));

        // Verificar que los contenedores de etiquetas existen
        const domSafe = window.domSafe || (v => String(v).replace(/[^A-Za-z0-9_-]/g, '-'));
        let etiquetasEncontradas = 0;
        for (const id of etiquetasSubIds) {
            const safeId = domSafe(id);
            const contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                               document.getElementById(`etiqueta-${id}`);
            if (contenedor) {
                etiquetasEncontradas++;
                const svg = contenedor.querySelector('svg');
                console.log(`✅ Etiqueta ${id}: encontrada, SVG: ${svg ? 'sí' : 'no'}`);
            } else {
                console.warn(`❌ Etiqueta ${id}: NO encontrada en el DOM`);
            }
        }
        console.log(`📊 Etiquetas encontradas: ${etiquetasEncontradas}/${etiquetasSubIds.length}`);

        // 5. Imprimir las etiquetas (ahora están desagrupadas y renderizadas)
        console.log('🖨️ Paso 5: Imprimiendo...');
        Swal.close();

        if (typeof window.imprimirEtiquetas === 'function') {
            await window.imprimirEtiquetas(etiquetasSubIds, modo);
        } else {
            console.error('❌ La función imprimirEtiquetas no está disponible');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Función de impresión no disponible' });
            return;
        }

        // 6. Esperar un momento y volver a agrupar
        console.log('⏳ Paso 6: Esperando antes de reagrupar...');
        await new Promise(resolve => setTimeout(resolve, 1500));

        // 7. Volver a resumir/agrupar las etiquetas
        if (planillaId && maquinaId) {
            console.log('🔒 Paso 7: Reagrupando...');
            Swal.fire({
                title: 'Reagrupando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const resumirResponse = await fetch('/api/etiquetas/resumir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    planilla_id: planillaId,
                    maquina_id: maquinaId,
                }),
            });

            const resumirResult = await resumirResponse.json();
            console.log('🔒 Resultado reagrupar:', resumirResult);

            // 8. Refrescar la vista final
            console.log('🔄 Paso 8: Refrescando vista final...');
            if (typeof window.refrescarEtiquetasMaquina === 'function') {
                await window.refrescarEtiquetasMaquina();
            }

            Swal.close();
        } else {
            console.warn('⚠️ No se puede reagrupar: falta planillaId o maquinaId');
        }

        console.log('✅ Proceso de impresión completado');

    } catch (error) {
        console.error('❌ Error al imprimir etiquetas del grupo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error: ' + error.message,
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
