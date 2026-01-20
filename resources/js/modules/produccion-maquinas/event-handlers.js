import Swal from 'sweetalert2';

let indicadorPosicion = null;
let numeroPosicion = null;
let eventoArrastrandose = null;
let mostrarIndicador = false;
let tooltipsDeshabilitados = false;

export function initIndicadorPosicion() {
    indicadorPosicion = document.getElementById('indicador_posicion');
    numeroPosicion = document.getElementById('numero_posicion');

    // Listener GLOBAL de mousemove para el indicador
    document.addEventListener('mousemove', function(e) {
        if (mostrarIndicador && indicadorPosicion) {
            indicadorPosicion.style.left = (e.clientX + 20) + 'px';
            indicadorPosicion.style.top = (e.clientY - 20) + 'px';
            indicadorPosicion.style.display = 'block';
            indicadorPosicion.classList.remove('hidden');
        }
    });
}

function calcularPosicion(calendar, recursoId, eventoId, tiempoReferencia) {
    const eventosOrdenados = calendar.getEvents()
        .filter(ev => ev.getResources().some(r => r.id == recursoId) && ev.id !== eventoId)
        .sort((a, b) => a.start - b.start);

    let posicion = 1;
    for (let i = 0; i < eventosOrdenados.length; i++) {
        if (tiempoReferencia < eventosOrdenados[i].start) {
            posicion = i + 1;
            break;
        }
        posicion = i + 2;
    }
    return posicion;
}

async function reordenarPlanilla(planillaId, maquinaDestinoId, maquinaOrigenId, nuevaPosicion, elementosId, ordenPlanillaId = null, opcionesExtras = {}) {
    const res = await fetch('/planillas/reordenar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            id: planillaId,
            maquina_id: maquinaDestinoId,
            maquina_origen_id: maquinaOrigenId,
            nueva_posicion: nuevaPosicion,
            elementos_id: elementosId,
            orden_planilla_id: ordenPlanillaId, // ID específico de la posición en cola
            ...opcionesExtras
        })
    });

    return await res.json();
}

export function eventHandlers(calendar) {
    return {
        eventDragStart: function(info) {
            eventoArrastrandose = info.event;
            mostrarIndicador = true;
            tooltipsDeshabilitados = true;

            // Ocultar todos los tooltips existentes
            document.querySelectorAll('.fc-tooltip').forEach(t => t.style.display = 'none');

            // Calcular posición inicial
            const recursoId = info.event.getResources()[0]?.id;
            if (recursoId && numeroPosicion) {
                const posicion = calcularPosicion(calendar, recursoId, info.event.id, info.event.start);
                numeroPosicion.textContent = posicion;
            }
        },

        eventAllow: function(dropInfo, draggedEvent) {
            if (mostrarIndicador && draggedEvent) {
                const recursoId = dropInfo.resource?.id;
                if (recursoId && numeroPosicion) {
                    const posicion = calcularPosicion(calendar, recursoId, draggedEvent.id, dropInfo.start);
                    numeroPosicion.textContent = posicion;
                }
            }
            return true;
        },

        eventDragStop: function(info) {
            eventoArrastrandose = null;
            mostrarIndicador = false;
            tooltipsDeshabilitados = false;
            if (indicadorPosicion) {
                indicadorPosicion.classList.add('hidden');
                indicadorPosicion.style.display = 'none';
            }
            document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());
        },

        eventDrop: async function(info) {
            document.querySelectorAll('.fc-tooltip').forEach(t => t.remove());

            const planillaId = info.event.id.split('-')[1];
            const codigoPlanilla = info.event.extendedProps.codigo ?? info.event.title;
            const maquinaOrigenId = info.oldResource?.id ?? info.event.getResources()[0]?.id;
            const maquinaDestinoId = info.newResource?.id ?? info.event.getResources()[0]?.id;
            const elementosId = info.event.extendedProps.elementos_id || [];
            const ordenPlanillaId = info.event.extendedProps.orden_planilla_id || null;

            const resultado = await Swal.fire({
                title: '¿Reordenar planilla?',
                html: `¿Quieres mover la planilla <strong>${codigoPlanilla}</strong> ${maquinaOrigenId !== maquinaDestinoId ? 'a otra máquina' : 'en la misma máquina'}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, reordenar',
                cancelButtonText: 'Cancelar'
            });

            if (!resultado.isConfirmed) {
                info.revert();
                return;
            }

            const eventosOrdenados = calendar.getEvents()
                .filter(ev => ev.getResources().some(r => r.id == maquinaDestinoId))
                .sort((a, b) => a.start - b.start);
            const nuevaPosicion = eventosOrdenados.findIndex(ev => ev.id === info.event.id) + 1;

            try {
                const data = await reordenarPlanilla(planillaId, maquinaDestinoId, maquinaOrigenId, nuevaPosicion, elementosId, ordenPlanillaId);

                if (data.requiresNuevaPosicionConfirmation) {
                    const confirmacion = await Swal.fire({
                        title: 'Posición ya existe',
                        html: data.message + '<br><br><strong>¿Qué deseas hacer?</strong>',
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Crear nueva posición',
                        denyButtonText: 'Usar posición existente',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#10b981',
                        denyButtonColor: '#3b82f6',
                        cancelButtonColor: '#6b7280',
                        reverseButtons: false,
                        allowOutsideClick: false
                    });

                    if (confirmacion.isConfirmed) {
                        const data2 = await reordenarPlanilla(planillaId, maquinaDestinoId, maquinaOrigenId, nuevaPosicion, elementosId, ordenPlanillaId, { crear_nueva_posicion: true });
                        if (!data2.success) throw new Error(data2.message || 'Error al crear nueva posición');

                        calendar.refetchEvents();
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        }).fire({ icon: 'success', title: 'Nueva posición creada' });

                    } else if (confirmacion.isDenied) {
                        const data2 = await reordenarPlanilla(planillaId, maquinaDestinoId, maquinaOrigenId, nuevaPosicion, elementosId, ordenPlanillaId, { usar_posicion_existente: true });
                        if (!data2.success) throw new Error(data2.message || 'Error al mover a posición existente');

                        calendar.refetchEvents();
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        }).fire({ icon: 'success', title: 'Planilla movida a posición existente' });

                    } else {
                        info.revert();
                    }
                    return;
                }

                if (!data.success) throw new Error(data.message || 'Error al reordenar');

                calendar.refetchEvents();
                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                }).fire({ icon: 'success', title: 'Planilla reordenada' });

            } catch (error) {
                info.revert();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo reordenar'
                });
            }
        },

        eventClick: function(info) {
            const planillaId = info.event.id.split('-')[1];
            const codigo = info.event.extendedProps.codigo || 'N/A';
            if (window.cargarElementosPlanilla) {
                window.cargarElementosPlanilla(planillaId, codigo);
            }
        }
    };
}

export { tooltipsDeshabilitados };
