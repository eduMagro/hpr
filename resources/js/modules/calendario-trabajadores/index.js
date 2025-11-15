import { initCalendar } from "./calendar.js";

// Función para inicializar el calendario de trabajadores
function inicializarCalendarioTrabajadores() {
    const el = document.getElementById("calendario");
    if (!el) return;

    // Verificar que sea el calendario de trabajadores
    if (el.getAttribute('data-calendar-type') !== 'trabajadores') return;

    if (!window.AppPlanif) return;

    // Destruir calendario anterior si existe
    if (window.calendarTrabajadores) {
        try {
            window.calendarTrabajadores.destroy();
        } catch (e) {
            // Ignorar errores de destrucción
        }
        window.calendarTrabajadores = null;
    }

    const calendar = initCalendar(el, window.AppPlanif);
    window.calendarTrabajadores = calendar;
}

// Inicializar solo una vez
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCalendarioTrabajadores, { once: true });
} else {
    inicializarCalendarioTrabajadores();
}

// Limpiar al salir de la página
document.addEventListener('livewire:navigating', () => {
    if (window.calendarTrabajadores) {
        try {
            window.calendarTrabajadores.destroy();
        } catch (e) {
            // Ignorar errores
        }
        window.calendarTrabajadores = null;
    }
}, { once: true });
