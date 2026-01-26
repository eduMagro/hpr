// Service Worker para PWA + Firebase Messaging
// Este archivo DEBE estar en la raíz del dominio (public/)

const CACHE_NAME = 'hpr-manager-v2';
const STATIC_ASSETS = [
    // NO cachear rutas que requieren autenticación (como '/')
    '/imagenes/ico/android-chrome-192x192.png',
    '/imagenes/ico/android-chrome-512x512.png',
    '/imagenes/ico/favicon-32x32.png',
    '/imagenes/ico/apple-touch-icon.png'
];

// Evento de instalación - cachear recursos estáticos
self.addEventListener('install', (event) => {
    console.log('Service Worker: Instalando...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Cacheando archivos');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Evento de activación - limpiar caches antiguas
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activado');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Eliminando cache antigua', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Evento fetch - estrategia network-first para contenido dinámico
self.addEventListener('fetch', (event) => {
    // Solo manejar requests GET
    if (event.request.method !== 'GET') return;

    // Ignorar requests a APIs externas
    if (!event.request.url.startsWith(self.location.origin)) return;

    // NO interceptar navegación HTML - dejar que el servidor maneje la autenticación
    if (event.request.mode === 'navigate') return;

    // NO interceptar peticiones a rutas de la app (solo cachear assets estáticos)
    const url = new URL(event.request.url);
    const isStaticAsset = url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/);
    if (!isStaticAsset) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Clonar la respuesta para guardarla en cache
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    // Solo cachear respuestas válidas (no redirecciones)
                    if (response.status === 200 && response.type === 'basic') {
                        cache.put(event.request, responseClone);
                    }
                });
                return response;
            })
            .catch(() => {
                // Si falla la red, intentar servir desde cache
                return caches.match(event.request);
            })
    );
});

// ============================================
// Firebase Cloud Messaging
// ============================================

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
