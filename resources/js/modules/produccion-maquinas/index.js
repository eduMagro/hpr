import { crearCalendario } from './calendar.js';
import { initIndicadorPosicion } from './event-handlers.js';
import { initFiltros, toggleFiltros } from './filtros.js';
import { aplicarLineasTurnos, toggleTurno } from './turnos.js';
import { toggleFullScreen } from './fullscreen.js';
import { onPageReady, onPageLeave } from '../../utils/livewire-helper.js';
import { Draggable } from '@fullcalendar/interaction';

// FullCalendar base styles (previously loaded via CDN in Blade)
import '@fullcalendar/core/index.css';
import '@fullcalendar/resource-timegrid/index.css';

// Importar modulos adicionales
import './panel-elementos.js';
import './modales.js';

let calendar = null;

// Marker to detect that the Vite module is active (and avoid legacy Blade/CDN bootstraps).
window.__hprProduccionMaquinasVite = true;

// Legacy Blade scripts in this view still reference FullCalendar.Draggable.
window.FullCalendar = window.FullCalendar || {};
window.FullCalendar.Draggable = Draggable;

function inicializarCalendario() {
    // Destruir calendario anterior si existe
    if (window.calendar) {
        try {
            window.calendar.destroy();
        } catch (e) {
            console.warn('Error al destruir calendario anterior:', e);
        }
    }

    // Obtener datos del DOM inyectados desde Blade
    const maquinas = window.ProduccionMaquinas?.maquinas || [];
    const planillas = window.ProduccionMaquinas?.planillas || [];
    const turnosActivos = window.ProduccionMaquinas?.turnosActivos || [];

    if (!maquinas.length) {
        console.warn('No hay datos de máquinas disponibles');
        return;
    }

    // Inicializar indicador de posición
    initIndicadorPosicion();

    // Crear calendario
    calendar = crearCalendario(maquinas, planillas, turnosActivos);
    window.calendar = calendar;

    // Renderizar calendario
    calendar.render();

    // Inicializar filtros
    initFiltros(calendar);

    // Aplicar líneas de turnos
    window.aplicarLineasTurnos = function() {
        aplicarLineasTurnos(turnosActivos);
    };

    setTimeout(() => {
        aplicarLineasTurnos(turnosActivos);
    }, 100);

    // Re-aplicar cuando cambie la vista
    calendar.on('datesSet', function() {
        setTimeout(() => {
            aplicarLineasTurnos(turnosActivos);
        }, 100);
    });

    // Sticky header al hacer scroll
    initStickyHeader();
}

function initStickyHeader() {
    setTimeout(() => {
        const headerResources = document.querySelector('.fc-datagrid-header');
        const headerTime = document.querySelector('.fc-col-header');
        const headerSection = document.querySelector('.fc-scrollgrid-section-header');

        if (!headerSection) return;

        const headerInitialTop = headerSection.getBoundingClientRect().top + window.pageYOffset;

        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > headerInitialTop - 10) {
                if (headerResources) {
                    headerResources.style.position = 'fixed';
                    headerResources.style.top = '0px';
                    headerResources.style.zIndex = '1000';
                    headerResources.style.backgroundColor = 'white';
                    headerResources.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
                if (headerTime) {
                    headerTime.style.position = 'fixed';
                    headerTime.style.top = '0px';
                    headerTime.style.zIndex = '1000';
                    headerTime.style.backgroundColor = 'white';
                    headerTime.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
                if (headerSection) {
                    headerSection.style.position = 'fixed';
                    headerSection.style.top = '0px';
                    headerSection.style.zIndex = '999';
                    headerSection.style.backgroundColor = 'white';
                    headerSection.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                    headerSection.style.width = '100%';
                }
            } else {
                if (headerResources) {
                    headerResources.style.position = '';
                    headerResources.style.top = '';
                    headerResources.style.zIndex = '';
                    headerResources.style.boxShadow = '';
                }
                if (headerTime) {
                    headerTime.style.position = '';
                    headerTime.style.top = '';
                    headerTime.style.zIndex = '';
                    headerTime.style.boxShadow = '';
                }
                if (headerSection) {
                    headerSection.style.position = '';
                    headerSection.style.top = '';
                    headerSection.style.zIndex = '';
                    headerSection.style.boxShadow = '';
                    headerSection.style.width = '';
                }
            }
        }, { passive: true });
    }, 500);
}

// Exponer funciones globales necesarias
window.toggleFiltros = toggleFiltros;
window.toggleTurno = toggleTurno;
window.toggleFullScreen = toggleFullScreen;

// Usar helper para inicializar en carga y navegación
onPageReady(inicializarCalendario, {
    selector: '#calendario[data-calendar-type="maquinas"]'
});

// Limpiar al salir de la página
onPageLeave(() => {
    if (window.calendar) {
        try {
            window.calendar.destroy();
            window.calendar = null;
        } catch (e) {
            console.warn('Error al limpiar calendario de máquinas:', e);
        }
    }
});
