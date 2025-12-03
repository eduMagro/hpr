<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('') }}">
    <meta name="google" content="notranslate">



    <title>{{ $title ?? config('app.name') }}</title>

    <!-- ✅ Preconexión a fuentes (mejora FCP) -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- ✅ Iconos para dispositivos -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imagenes/ico/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('imagenes/ico/favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('imagenes/ico/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('imagenes/ico/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('imagenes/ico/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('imagenes/ico/android-chrome-512x512.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#1e40af">

    <!-- ✅ Vite Assets - Cache busting automático -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/styles.css', 'resources/css/etiquetas-responsive.css'])

    <!-- Alpine.js ya está incluido en Livewire 3, NO cargar desde CDN -->

    <!-- ✅ Librerías que no bloquean renderizado - Versionadas para evitar problemas de caché -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" defer></script>

    <!-- ✅ FullCalendar (solo si es necesario en esta vista) -->
    @stack('calendar') {{-- así solo lo cargas si lo necesitas --}}

    <!-- Livewire Styles -->
    @livewireStyles

    <script>
        // Prevenir que errores de scripts externos rompan Livewire
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('browser is not defined')) {
                console.warn('Error de browser API ignorado:', e.message);
                e.preventDefault();
                return true;
            }
        });
    </script>

    <style>
        /* Oculta cualquier elemento marcado con x-cloak hasta que Alpine quite el atributo */
        [x-cloak] {
            display: none !important;
        }

        /* Escala global solo en pantallas grandes */
        @media (min-width: 768px) {
            html {
                font-size: 70%;
            }
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            background-color: #f9fafb;
        }

        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* Personalizar barra de progreso de Livewire Navigate */
        .livewire-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            z-index: 99999;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.5);
            transition: opacity 0.3s ease;
        }

        .livewire-progress-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 1s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Overlay de transición para navegación */
        .navigation-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(2px);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .navigation-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        /* Spinner de carga */
        .navigation-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-200" @auth data-user-id="{{ auth()->id() }}" @endauth>
    <!-- Overlay de navegación -->
    <div id="navigation-overlay" class="navigation-overlay">
        <div class="navigation-spinner"></div>
    </div>

    <div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden">
        <!-- Sidebar Menu Enhanced -->
            <x-sidebar-menu-enhanced />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header Enhanced -->
                <x-top-header-enhanced />

            <!-- Alerts -->
            @include('layouts.alerts')

            <!-- Prompt para notificaciones push -->
            @auth
                <x-notification-prompt />
            @endauth

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-neutral-100 dark:bg-gray-900 transition-colors">
                <div class="py-6 px-0 sm:px-6 lg:px-8">
                    <!-- Breadcrumbs -->
                    <x-breadcrumbs />

                    <!-- Page Heading -->
                    @isset($header)
                        <header class="mb-6">
                            <div
                                class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-6 py-4 border border-gray-200 dark:border-gray-700 transition-colors">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <!-- Main Content -->
                    <div class="transition-colors">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Livewire Scripts -->
    @livewireScripts(['navigate' => true])

    @stack('scripts')

    <!-- Firebase Cloud Messaging -->
    <script src="{{ asset('js/firebase-push.js') }}" defer></script>

    <!-- Dark Mode Support Script -->
    <script data-navigate-once>
        // Aplicar dark mode desde localStorage al cargar
        if (localStorage.getItem('dark_mode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Re-aplicar en cada navegación de Livewire
        document.addEventListener('livewire:navigated', () => {
            if (localStorage.getItem('dark_mode') === 'true') {
                document.documentElement.classList.add('dark');
            }
        });

        // Configurar la barra de progreso de Livewire Navigate
        document.addEventListener('livewire:init', () => {
            // Verificar que Livewire y navigate existan
            if (typeof Livewire !== 'undefined' && Livewire.navigate && typeof Livewire.navigate.config === 'function') {
                // Configurar Navigate para esperar a que el DOM esté completamente cargado
                Livewire.navigate.config({
                    showProgressBar: true,
                    progressBarDuration: 1000, // Duración mínima de la barra en ms
                    progressBarColor: '#3b82f6',
                });
            }

            if (typeof Livewire !== 'undefined' && typeof Livewire.hook === 'function') {
                Livewire.hook('navigate', ({url, history}) => {
                    // Scroll to top on navigation
                    setTimeout(() => {
                        window.scrollTo({ top: 0, behavior: 'instant' });
                    }, 0);
                });
            }
        });

        // Control del overlay de navegación
        const overlay = document.getElementById('navigation-overlay');
        let isNavigating = false;
        let navigationTimeout = null;

        document.addEventListener('livewire:navigating', () => {
            console.log('Navegando...');
            isNavigating = true;

            // Mostrar overlay después de un pequeño delay para navegaciones rápidas
            navigationTimeout = setTimeout(() => {
                if (isNavigating) {
                    overlay.classList.add('active');
                }
            }, 100);
        });

        document.addEventListener('livewire:navigated', () => {
            // Cancelar el timeout si la navegación fue muy rápida
            if (navigationTimeout) {
                clearTimeout(navigationTimeout);
                navigationTimeout = null;
            }

            // Remover overlay inmediatamente - Livewire ya se encarga de la sincronización
            isNavigating = false;
            overlay.classList.remove('active');

            // Cargar FullCalendar dinámicamente si estamos en la página de máquinas
            const calendarioEl = document.getElementById('calendario');
            if (calendarioEl && calendarioEl.dataset.calendarType === 'maquinas') {
                // Pequeño delay para que el script de la página se ejecute primero
                setTimeout(() => {
                    // Destruir calendario anterior si existe
                    if (window.calendar) {
                        try {
                            window.calendar.destroy();
                            window.calendar = null;
                        } catch (e) {
                            console.warn('Error al destruir calendario anterior:', e);
                        }
                    }

                    // Función para esperar a que la función de inicialización esté disponible
                    function esperarYCargar(intentos = 0) {
                        if (typeof window.inicializarCalendarioMaquinas === 'function') {
                            if (typeof FullCalendar === 'undefined') {
                                console.log('📅 Cargando FullCalendar dinámicamente...');
                                const scripts = [
                                    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js',
                                    'https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js',
                                    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js'
                                ];

                                function cargarScriptSecuencial(index, callback) {
                                    if (index >= scripts.length) {
                                        if (callback) callback();
                                        return;
                                    }
                                    const script = document.createElement('script');
                                    script.src = scripts[index];
                                    script.onload = () => cargarScriptSecuencial(index + 1, callback);
                                    document.head.appendChild(script);
                                }

                                cargarScriptSecuencial(0, () => {
                                    console.log('✅ FullCalendar cargado, inicializando calendario...');
                                    window.inicializarCalendarioMaquinas();
                                });
                            } else {
                                // FullCalendar ya está cargado, solo inicializar
                                console.log('📅 FullCalendar ya disponible, reinicializando calendario...');
                                window.inicializarCalendarioMaquinas();
                            }
                        } else if (intentos < 20) {
                            // Esperar un poco más
                            setTimeout(() => esperarYCargar(intentos + 1), 50);
                        } else {
                            console.error('❌ No se encontró la función inicializarCalendarioMaquinas');
                        }
                    }

                    esperarYCargar();
                }, 100);
            }
        });

        // Manejar errores de navegación
        document.addEventListener('livewire:navigate-failed', () => {
            console.error('Navegación fallida');
            isNavigating = false;
            overlay.classList.remove('active');
            if (navigationTimeout) {
                clearTimeout(navigationTimeout);
                navigationTimeout = null;
            }
        });
    </script>
</body>

</html>
