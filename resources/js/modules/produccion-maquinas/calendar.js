import { Calendar } from '@fullcalendar/core';
import resourceTimeGridPlugin from '@fullcalendar/resource-timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';
import { eventHandlers } from './event-handlers.js';
import { createResourceLabel } from './resource-label.js';
import { createTooltip } from './tooltips.js';

export function crearCalendario(maquinas, planillas, turnosActivos) {
    const calendarEl = document.getElementById('calendario');

    const calendar = new Calendar(calendarEl, {
        plugins: [resourceTimeGridPlugin, interactionPlugin],
        schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',

        initialView: 'resourceTimeGrid7Days',
        nextDayThreshold: '00:00:00',
        allDaySlot: false,

        locale: esLocale,
        timeZone: 'Europe/Madrid',

        height: 'auto',
        contentHeight: 'auto',

        slotMinTime: '05:00:00',
        slotMaxTime: '24:00:00',
        slotDuration: '01:00:00',
        slotLabelInterval: '01:00:00',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'resourceTimeGrid3Days,resourceTimeGrid7Days,resourceTimeGrid14Days'
        },

        views: {
            resourceTimeGrid3Days: {
                type: 'resourceTimeGrid',
                duration: { days: 3 },
                buttonText: '3 días'
            },
            resourceTimeGrid7Days: {
                type: 'resourceTimeGrid',
                duration: { days: 7 },
                buttonText: '7 días'
            },
            resourceTimeGrid14Days: {
                type: 'resourceTimeGrid',
                duration: { days: 14 },
                buttonText: '14 días'
            }
        },

        resources: maquinas,
        events: planillas,

        editable: true,
        droppable: true,
        eventDurationEditable: false,
        eventResourceEditable: true,

        resourceLabelContent: (arg) => createResourceLabel(arg),

        eventContent: function(arg) {
            const progreso = arg.event.extendedProps.progreso;
            const eventId = arg.event.id || arg.event._def.publicId;

            if (typeof progreso === 'number') {
                return {
                    html: `
                    <div class="w-full px-1 py-0.5 text-xs font-semibold text-white" data-event-id="${eventId}">
                        <div class="mb-0.5 truncate" title="${arg.event.title}">${arg.event.title}</div>
                        <div class="w-full h-2 bg-gray-300 rounded overflow-hidden">
                            <div class="h-2 bg-blue-500 rounded transition-all duration-500" style="width: ${progreso}%; min-width: 1px;"></div>
                        </div>
                    </div>`
                };
            }

            return {
                html: `
                <div class="truncate w-full text-xs font-semibold text-white px-2 py-1 rounded"
                     style="background-color: ${arg.event.backgroundColor};"
                     title="${arg.event.title}">
                    ${arg.event.title}
                </div>`
            };
        },

        eventDidMount: function(info) {
            createTooltip(info);
        }
    });

    // Agregar event handlers después de crear el calendario
    const handlers = eventHandlers(calendar);
    Object.keys(handlers).forEach(key => {
        calendar.setOption(key, handlers[key]);
    });

    return calendar;
}
