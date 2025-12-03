/**
 * Firebase Cloud Messaging - Cliente
 * Inicializa FCM y gestiona el token del dispositivo
 */

// Evitar redeclaración si el script se carga múltiples veces
if (typeof window.FirebasePush !== 'undefined') {
    // Ya está definido, no hacer nada
} else {

class FirebasePush {
    constructor() {
        this.messaging = null;
        this.currentToken = null;
        this.config = null;
        this.isInitialized = false;
    }

    /**
     * Inicializa Firebase con la configuración del servidor
     */
    async init() {
        if (this.isInitialized) {
            return this.currentToken;
        }

        try {
            // Obtener configuración del servidor
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
            const response = await fetch(baseUrl + '/api/fcm/config', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('No se pudo obtener la configuración de Firebase');
            }

            this.config = await response.json();

            // Verificar que la configuración esté completa
            if (!this.config.apiKey || !this.config.projectId) {
                console.warn('Firebase: Configuración incompleta');
                return null;
            }

            // Cargar Firebase dinámicamente
            await this.loadFirebaseScripts();

            // Inicializar Firebase
            if (!firebase.apps.length) {
                firebase.initializeApp(this.config);
            }

            this.messaging = firebase.messaging();
            this.isInitialized = true;

            // Registrar service worker y enviar configuración
            await this.registerServiceWorker();

            // Configurar handler para mensajes en foreground
            this.setupForegroundHandler();

            return this.currentToken;

        } catch (error) {
            console.error('Firebase: Error de inicialización', error);
            return null;
        }
    }

    /**
     * Carga los scripts de Firebase con timeout extendido
     */
    async loadFirebaseScripts() {
        if (typeof firebase !== 'undefined') {
            return;
        }

        const scripts = [
            'https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js',
            'https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js'
        ];

        for (const src of scripts) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;

                // Timeout de 30 segundos
                const timeout = setTimeout(() => {
                    reject(new Error(`Timeout cargando ${src}`));
                }, 30000);

                script.onload = () => {
                    clearTimeout(timeout);
                    resolve();
                };
                script.onerror = (e) => {
                    clearTimeout(timeout);
                    reject(e);
                };
                document.head.appendChild(script);
            });
        }
    }

    /**
     * Registra el service worker y espera a que esté activo
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.warn('Service Workers no soportados');
            return null;
        }

        try {
            const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            console.log('Firebase: Service Worker registrado');

            // Esperar a que el SW esté activo
            await this.waitForServiceWorkerActive(registration);

            // Enviar configuración al SW
            this.sendConfigToSW(registration);

            return registration;

        } catch (error) {
            console.error('Firebase: Error registrando Service Worker', error);
            return null;
        }
    }

    /**
     * Espera a que el Service Worker esté activo
     */
    async waitForServiceWorkerActive(registration) {
        return new Promise((resolve) => {
            if (registration.active) {
                resolve();
                return;
            }

            const sw = registration.installing || registration.waiting;
            if (sw) {
                sw.addEventListener('statechange', () => {
                    if (sw.state === 'activated') {
                        resolve();
                    }
                });
            } else {
                resolve();
            }
        });
    }

    /**
     * Envía la configuración al Service Worker
     */
    sendConfigToSW(registration) {
        if (registration.active) {
            registration.active.postMessage({
                type: 'FIREBASE_CONFIG',
                config: this.config
            });
        }
    }

    /**
     * Solicita permiso para notificaciones y obtiene el token
     */
    async requestPermission() {
        try {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                console.log('Firebase: Permiso de notificaciones denegado');
                return null;
            }

            return await this.getToken();

        } catch (error) {
            console.error('Firebase: Error solicitando permiso', error);
            return null;
        }
    }

    /**
     * Obtiene el token FCM y lo registra en el servidor
     */
    async getToken() {
        if (!this.isInitialized) {
            await this.init();
        }

        if (!this.messaging) {
            return null;
        }

        try {
            // Obtener el Service Worker registration
            const swRegistration = await navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js');

            if (!swRegistration) {
                console.error('Firebase: Service Worker no registrado');
                // Intentar registrar de nuevo
                await this.registerServiceWorker();
            }

            // Esperar a que haya un SW activo
            await navigator.serviceWorker.ready;

            const token = await this.messaging.getToken({
                vapidKey: this.config.vapidKey,
                serviceWorkerRegistration: swRegistration || await navigator.serviceWorker.ready
            });

            if (token) {
                this.currentToken = token;
                await this.registerTokenOnServer(token);
                console.log('Firebase: Token obtenido');
            }

            return token;

        } catch (error) {
            console.error('Firebase: Error obteniendo token', error);
            return null;
        }
    }

    /**
     * Registra el token en el servidor
     */
    async registerTokenOnServer(token) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';

            const response = await fetch(baseUrl + '/api/fcm/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    token: token,
                    device_type: 'web',
                    device_name: this.getDeviceName()
                })
            });

            if (!response.ok) {
                throw new Error('Error registrando token');
            }

            const data = await response.json();
            console.log('Firebase: Token registrado en servidor', data);

        } catch (error) {
            console.error('Firebase: Error registrando token en servidor', error);
        }
    }

    /**
     * Elimina el token del servidor
     */
    async removeToken() {
        if (!this.currentToken) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';

            await fetch(baseUrl + '/api/fcm/token', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    token: this.currentToken
                })
            });

            this.currentToken = null;
            console.log('Firebase: Token eliminado');

        } catch (error) {
            console.error('Firebase: Error eliminando token', error);
        }
    }

    /**
     * Configura el handler para mensajes en foreground
     */
    setupForegroundHandler() {
        if (!this.messaging) {
            return;
        }

        this.messaging.onMessage((payload) => {
            console.log('Firebase: Mensaje recibido en foreground', payload);

            // Mostrar notificación usando la API de notificaciones o SweetAlert
            this.showForegroundNotification(payload);
        });
    }

    /**
     * Muestra notificación cuando la app está en primer plano
     */
    showForegroundNotification(payload) {
        const title = payload.notification?.title || 'Nueva notificación';
        const body = payload.notification?.body || '';
        const url = payload.data?.url || '/';

        // Si SweetAlert2 está disponible, usarlo
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: body,
                icon: 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('click', () => {
                        if (url !== '/') {
                            window.location.href = url;
                        }
                    });
                }
            });
        } else {
            // Fallback a notificación nativa
            if (Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: body,
                    icon: '/img/logo.png'
                });

                notification.onclick = () => {
                    window.focus();
                    if (url !== '/') {
                        window.location.href = url;
                    }
                };
            }
        }
    }

    /**
     * Obtiene el nombre del dispositivo
     */
    getDeviceName() {
        const ua = navigator.userAgent;
        if (ua.includes('Chrome')) return 'Chrome';
        if (ua.includes('Firefox')) return 'Firefox';
        if (ua.includes('Safari')) return 'Safari';
        if (ua.includes('Edge')) return 'Edge';
        return 'Navegador Web';
    }

    /**
     * Verifica si las notificaciones están soportadas
     */
    isSupported() {
        return 'Notification' in window &&
               'serviceWorker' in navigator &&
               'PushManager' in window;
    }

    /**
     * Verifica el estado del permiso de notificaciones
     */
    getPermissionState() {
        if (!('Notification' in window)) {
            return 'unsupported';
        }
        return Notification.permission;
    }
}

// Instancia global
window.FirebasePush = new FirebasePush();

// ============================================
// PWA Install Prompt
// ============================================

let deferredPrompt = null;

// Capturar el evento de instalación antes de que el navegador lo muestre
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('PWA: beforeinstallprompt capturado');
    e.preventDefault();
    deferredPrompt = e;

    // Disparar evento personalizado para que Alpine.js pueda reaccionar
    window.dispatchEvent(new CustomEvent('pwa-install-available'));
});

// Detectar si la app ya está instalada
window.addEventListener('appinstalled', () => {
    console.log('PWA: Aplicación instalada');
    deferredPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});

/**
 * Mostrar el prompt de instalación de PWA
 */
window.showPWAInstallPrompt = async function() {
    if (!deferredPrompt) {
        console.log('PWA: No hay prompt disponible');
        return false;
    }

    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('PWA: Usuario eligió:', outcome);

    deferredPrompt = null;
    return outcome === 'accepted';
};

/**
 * Verificar si la PWA puede ser instalada
 */
window.canInstallPWA = function() {
    return deferredPrompt !== null;
};

/**
 * Verificar si la app está en modo standalone (instalada)
 */
window.isPWAInstalled = function() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true;
};

// Auto-inicializar si hay un usuario autenticado
document.addEventListener('DOMContentLoaded', async () => {
    // Verificar si el usuario está autenticado (presencia de csrf-token y body con data-user)
    const isAuthenticated = document.querySelector('meta[name="csrf-token"]') &&
                           document.body.dataset.userId;

    if (isAuthenticated && window.FirebasePush.isSupported()) {
        // Inicializar Firebase Push
        await window.FirebasePush.init();

        // Si ya tiene permiso, obtener token automáticamente
        if (window.FirebasePush.getPermissionState() === 'granted') {
            await window.FirebasePush.getToken();
        }
    }
});

} // Fin del bloque if para evitar redeclaración
