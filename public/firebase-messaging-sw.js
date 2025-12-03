// Firebase Messaging Service Worker
// Este archivo DEBE estar en la raíz del dominio (public/)

importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// La configuración se inyectará desde el frontend
let firebaseConfig = null;

// Escuchar mensajes del cliente para recibir la configuración
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'FIREBASE_CONFIG') {
        firebaseConfig = event.data.config;
        initializeFirebase();
    }
});

function initializeFirebase() {
    if (!firebaseConfig) {
        console.warn('Firebase: Configuración no disponible');
        return;
    }

    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    const messaging = firebase.messaging();

    // Manejar mensajes en background
    messaging.onBackgroundMessage((payload) => {
        console.log('Firebase: Mensaje recibido en background', payload);

        const notificationTitle = payload.notification?.title || 'Nueva notificación';
        const notificationOptions = {
            body: payload.notification?.body || '',
            icon: payload.notification?.icon || '/imagenes/ico/android-chrome-192x192.png',
            badge: '/imagenes/ico/favicon-32x32.png',
            tag: payload.data?.tag || 'default',
            data: payload.data || {},
            requireInteraction: true,
            actions: [
                {
                    action: 'open',
                    title: 'Abrir'
                },
                {
                    action: 'close',
                    title: 'Cerrar'
                }
            ]
        };

        return self.registration.showNotification(notificationTitle, notificationOptions);
    });
}

// Manejar clics en notificaciones
self.addEventListener('notificationclick', (event) => {
    console.log('Firebase: Click en notificación', event);

    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    // URL a abrir (de los datos de la notificación o la URL base)
    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Si ya hay una ventana abierta, enfocarla
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.focus();
                        if (urlToOpen !== '/') {
                            client.navigate(urlToOpen);
                        }
                        return;
                    }
                }
                // Si no hay ventana abierta, abrir una nueva
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Manejar cierre de notificaciones
self.addEventListener('notificationclose', (event) => {
    console.log('Firebase: Notificación cerrada', event);
});
