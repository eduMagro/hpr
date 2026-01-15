import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Interceptor global de fetch para manejar sesiones expiradas (error 419)
 */
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const response = await originalFetch(...args);

    if (response.status === 419) {
        // Evitar mostrar múltiples alertas
        if (!window._sessionExpiredShown) {
            window._sessionExpiredShown = true;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesión expirada',
                    text: 'Tu sesión ha expirado. La página se recargará.',
                    confirmButtonText: 'Recargar',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => window.location.reload());
            } else {
                alert('Tu sesión ha expirado. La página se recargará.');
                window.location.reload();
            }
        }

        throw new Error('Sesión expirada');
    }

    return response;
};

/**
 * Interceptor de axios para manejar sesiones expiradas (error 419)
 */
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            if (!window._sessionExpiredShown) {
                window._sessionExpiredShown = true;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión expirada',
                        text: 'Tu sesión ha expirado. La página se recargará.',
                        confirmButtonText: 'Recargar',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => window.location.reload());
                } else {
                    alert('Tu sesión ha expirado. La página se recargará.');
                    window.location.reload();
                }
            }
        }
        return Promise.reject(error);
    }
);
