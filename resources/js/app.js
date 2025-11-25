import "./bootstrap";
// import "./calendario-trabajadores/index.js";

// Asegurar Alpine disponible incluso si Livewire no lo inyecta
import Alpine from "alpinejs";
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}
