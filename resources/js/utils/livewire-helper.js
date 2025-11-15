/**
 * Helper para ejecutar código tanto en DOMContentLoaded como en livewire:navigated
 * Esto asegura que los scripts se ejecuten correctamente en navegación SPA
 *
 * @param {Function} callback - Función a ejecutar
 * @param {Object} options - Opciones de configuración
 * @param {string} options.selector - Selector CSS para verificar si ejecutar (opcional)
 * @param {boolean} options.once - Si ejecutar solo una vez (default: false)
 */
export function onPageReady(callback, options = {}) {
    const { selector = null, once = false } = options;
    let hasRun = false;

    const execute = () => {
        // Si se especificó un selector, verificar que exista
        if (selector && !document.querySelector(selector)) {
            return;
        }

        // Si once es true y ya se ejecutó, no ejecutar de nuevo
        if (once && hasRun) {
            return;
        }

        hasRun = true;
        callback();
    };

    // Ejecutar en carga inicial
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', execute);
    } else {
        execute();
    }

    // Ejecutar en navegaciones de Livewire
    document.addEventListener('livewire:navigated', execute);
}

/**
 * Ejecuta una función de limpieza cuando se sale de la página
 * Útil para limpiar event listeners, calendarios, etc.
 *
 * @param {Function} callback - Función de limpieza
 */
export function onPageLeave(callback) {
    document.addEventListener('livewire:navigating', callback);
}

export default { onPageReady, onPageLeave };
