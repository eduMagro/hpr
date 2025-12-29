/**
 * Sistema de selección múltiple para elementos arrastrables
 * Permite seleccionar múltiples elementos y arrastrarlos juntos a las máquinas
 */

(function() {
    'use strict';

    // Array para almacenar los IDs de elementos seleccionados
    let elementosSeleccionados = [];

    /**
     * Alterna la selección de un elemento
     */
    function toggleSeleccion(elementoDiv) {
        const elementoId = elementoDiv.dataset.elementoId;

        if (elementoDiv.classList.contains('seleccionado')) {
            // Deseleccionar
            elementoDiv.classList.remove('seleccionado');
            const index = elementosSeleccionados.indexOf(elementoId);
            if (index > -1) {
                elementosSeleccionados.splice(index, 1);
            }
        } else {
            // Seleccionar
            elementoDiv.classList.add('seleccionado');
            if (!elementosSeleccionados.includes(elementoId)) {
                elementosSeleccionados.push(elementoId);
            }
        }

        actualizarBadgeSeleccion();
    }

    /**
     * Actualiza el badge que muestra la cantidad de elementos seleccionados
     */
    function actualizarBadgeSeleccion() {
        let badge = document.getElementById('selection-badge');

        if (elementosSeleccionados.length > 0) {
            if (!badge) {
                badge = document.createElement('div');
                badge.id = 'selection-badge';
                badge.className = 'selection-badge show';
                document.body.appendChild(badge);
            }
            badge.textContent = `${elementosSeleccionados.length} elemento${elementosSeleccionados.length > 1 ? 's' : ''} seleccionado${elementosSeleccionados.length > 1 ? 's' : ''}`;
            badge.classList.add('show');
        } else if (badge) {
            badge.classList.remove('show');
        }
    }

    /**
     * Limpia todas las selecciones
     */
    function limpiarSelecciones() {
        document.querySelectorAll('.elemento-drag.seleccionado').forEach(el => {
            el.classList.remove('seleccionado');
        });
        elementosSeleccionados = [];
        actualizarBadgeSeleccion();
    }

    /**
     * Obtiene todos los IDs de elementos seleccionados, o el elemento siendo arrastrado
     */
    function getElementosParaMover(elementoArrastrado) {
        const elementoId = elementoArrastrado.dataset.elementoId;

        // Si el elemento arrastrado está seleccionado, mover todos los seleccionados
        if (elementosSeleccionados.includes(elementoId)) {
            return elementosSeleccionados.map(id => parseInt(id));
        }

        // Si no, solo mover el elemento arrastrado
        return [parseInt(elementoId)];
    }

    /**
     * Obtiene información completa de todos los elementos a mover
     * 🆕 Soporta elementos agrupados (múltiples elementos con mismas dimensiones)
     */
    function getDataElementosParaMover(elementoArrastrado) {
        const elementoId = elementoArrastrado.dataset.elementoId;
        const planillaId = elementoArrastrado.dataset.planillaId;
        const maquinaOriginal = elementoArrastrado.dataset.maquinaOriginal;

        let elementosIds = [parseInt(elementoId)];
        let maquinaOrigenId = parseInt(maquinaOriginal);

        // 🆕 Verificar si es un elemento agrupado
        const esGrupo = elementoArrastrado.dataset.esGrupo === 'true';
        if (esGrupo && elementoArrastrado.dataset.elementosGrupo) {
            try {
                const idsGrupo = JSON.parse(elementoArrastrado.dataset.elementosGrupo);
                elementosIds = idsGrupo.map(id => parseInt(id));
                console.log('📦 Elemento agrupado detectado, IDs:', elementosIds);
            } catch (e) {
                console.error('Error parseando elementosGrupo:', e);
            }
        }

        // Si el elemento arrastrado está seleccionado, incluir todos los seleccionados
        if (elementosSeleccionados.includes(elementoId) && elementosSeleccionados.length > 1) {
            // Combinar IDs del grupo con los seleccionados manualmente
            const idsSeleccionados = elementosSeleccionados.map(id => parseInt(id));

            // Para cada elemento seleccionado, verificar si es un grupo y añadir sus IDs
            elementosSeleccionados.forEach(selId => {
                const selDiv = document.querySelector(`.elemento-drag[data-elemento-id="${selId}"]`);
                if (selDiv && selDiv.dataset.esGrupo === 'true' && selDiv.dataset.elementosGrupo) {
                    try {
                        const idsGrupoSel = JSON.parse(selDiv.dataset.elementosGrupo);
                        idsGrupoSel.forEach(gId => {
                            const gIdInt = parseInt(gId);
                            if (!elementosIds.includes(gIdInt)) {
                                elementosIds.push(gIdInt);
                            }
                        });
                    } catch (e) {
                        console.error('Error parseando elementosGrupo de seleccionado:', e);
                    }
                } else {
                    // Elemento individual seleccionado
                    const selIdInt = parseInt(selId);
                    if (!elementosIds.includes(selIdInt)) {
                        elementosIds.push(selIdInt);
                    }
                }
            });

            // Obtener la máquina origen del primer elemento seleccionado
            const primerElementoDiv = document.querySelector(`.elemento-drag[data-elemento-id="${elementosSeleccionados[0]}"]`);
            if (primerElementoDiv && primerElementoDiv.dataset.maquinaOriginal) {
                maquinaOrigenId = parseInt(primerElementoDiv.dataset.maquinaOriginal);
            }
        }

        return {
            elementosIds: elementosIds,
            planillaId: parseInt(planillaId),
            maquinaOriginal: maquinaOrigenId,
            cantidad: elementosIds.length
        };
    }

    /**
     * Remueve elementos del panel después de moverlos
     */
    function removerElementosDelPanel(elementosIds) {
        elementosIds.forEach(id => {
            const elementoDiv = document.querySelector(`.elemento-drag[data-elemento-id="${id}"]`);
            if (elementoDiv) {
                elementoDiv.remove();
            }

            // Remover de la lista de seleccionados
            const index = elementosSeleccionados.indexOf(id.toString());
            if (index > -1) {
                elementosSeleccionados.splice(index, 1);
            }
        });

        actualizarBadgeSeleccion();
    }

    // Exportar funciones al objeto global para que puedan ser usadas desde el HTML
    window.MultiSelectElementos = {
        toggleSeleccion,
        limpiarSelecciones,
        getElementosParaMover,
        getDataElementosParaMover,
        removerElementosDelPanel,
        getSeleccionados: () => elementosSeleccionados
    };
})();
