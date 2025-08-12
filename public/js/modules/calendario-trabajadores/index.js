import { initCalendar } from "./calendar.js";

document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("calendario");
    if (!el) return console.warn("[cal] No hay #calendario");
    if (!window.AppPlanif)
        return console.error("[cal] window.AppPlanif no existe");

    initCalendar(el, window.AppPlanif); // ← le pasamos cfg explícitamente
});
