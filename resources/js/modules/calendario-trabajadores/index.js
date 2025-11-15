import { initCalendar } from "./calendar.js";
import { onPageReady, onPageLeave } from "../../utils/livewire-helper.js";

// Función para inicializar el calendario de trabajadores
function inicializarCalendarioTrabajadores() {
    const el = document.getElementById("calendario");
    if (!el) return console.warn("[cal] No hay #calendario");
    if (!window.AppPlanif)
        return console.error("[cal] window.AppPlanif no existe");

    // Destruir calendario anterior si existe
    if (window.calendarTrabajadores) {
        try {
            window.calendarTrabajadores.destroy();
        } catch (e) {
            console.warn('Error al destruir calendario de trabajadores:', e);
        }
    }

    const calendar = initCalendar(el, window.AppPlanif);
    window.calendarTrabajadores = calendar;
}

// Usar helper para inicializar en carga y navegación
onPageReady(inicializarCalendarioTrabajadores, {
    selector: '#calendario[data-calendar-type="trabajadores"]'
});

// Limpiar al salir de la página
onPageLeave(() => {
    if (window.calendarTrabajadores) {
        try {
            window.calendarTrabajadores.destroy();
            window.calendarTrabajadores = null;
        } catch (e) {
            console.warn('Error al limpiar calendario de trabajadores:', e);
        }
    }
});
