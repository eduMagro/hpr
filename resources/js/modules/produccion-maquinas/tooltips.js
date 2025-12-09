import { tooltipsDeshabilitados } from './event-handlers.js';

export function createTooltip(info) {
    const props = info.event.extendedProps;
    const tooltip = document.createElement('div');
    tooltip.className = 'fc-tooltip';

    let estadoRevision = '';
    if (props.revisada === false || props.revisada === 0) {
        estadoRevision = '<br><span class="text-red-400 font-bold">⚠️ SIN REVISAR - No iniciar producción</span>';
    } else if (props.revisada === true || props.revisada === 1) {
        estadoRevision = `<br><span class="text-green-400">✅ Revisada por ${props.revisada_por || 'N/A'}</span>`;
    }

    const elementosDebug = props.codigos_elementos ? props.codigos_elementos.join(', ') : 'N/A';
    const maquinaId = info.event.getResources()[0]?.id || 'N/A';

    tooltip.innerHTML = `
    <div class="bg-gray-900 text-white text-xs rounded px-2 py-1 shadow-md max-w-xs">
        <strong>${info.event.title}</strong><br>
        Obra: ${props.obra}<br>
        Estado producción: ${props.estado}<br>
        Máquina: <span class="text-blue-300">${maquinaId}</span><br>
        Elementos: <span class="text-purple-300">${elementosDebug}</span><br>
        Duración: <span class="text-cyan-300">${props.duracion_horas || 0} hrs</span><br>
        Fin programado: <span class="text-yellow-300">${props.fin_programado}</span><br>
        Fecha estimada entrega: <span class="text-green-300">${props.fecha_entrega}</span>${estadoRevision}
    </div>`;

    tooltip.style.display = 'none';
    document.body.appendChild(tooltip);

    info.el.addEventListener('mouseenter', function(e) {
        if (!tooltipsDeshabilitados) {
            tooltip.style.left = e.pageX + 10 + 'px';
            tooltip.style.top = e.pageY + 10 + 'px';
            tooltip.style.display = 'block';
        }
    });

    info.el.addEventListener('mousemove', function(e) {
        if (!tooltipsDeshabilitados) {
            tooltip.style.left = e.pageX + 10 + 'px';
            tooltip.style.top = e.pageY + 10 + 'px';
        }
    });

    info.el.addEventListener('mouseleave', function() {
        tooltip.style.display = 'none';
    });
}
