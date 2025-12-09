<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <meta name="theme-color" content="#ffffff">

    <!-- ✅ Vite Assets - Cache busting automático -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/styles.css', 'resources/css/etiquetas-responsive.css'])

    <!-- Alpine.js ya está incluido en Livewire 3, NO cargar desde CDN -->

    <!-- ✅ Librerías que no bloquean renderizado - Versionadas para evitar problemas de caché -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
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
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }
    </style>
</head>

<body class="font-sans antialiased transition-colors duration-200">
    <div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden">
        <!-- Sidebar Menu Enhanced -->
        <x-sidebar-menu-enhanced />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header Enhanced -->
            <x-top-header-enhanced />

            <!-- Alerts -->
            @include('layouts.alerts')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-neutral-100 dark:bg-gray-900 transition-colors">
                <div class="py-2 px-2 sm:px-6 lg:px-8 h-full">
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
                    <div class="transition-colors h-auto">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modales globales (fuera del contenedor con overflow-hidden) -->
    @stack('modals')

    <!-- Livewire Scripts -->
    @livewireScripts

    @stack('scripts')

    <script data-navigate-once>
        // Desactiva wire:navigate en enlaces para evitar navegaciones SPA con errores de fetch
        function disableWireNavigate() {
            document.querySelectorAll('a[wire\\:navigate]').forEach((link) => {
                link.removeAttribute('wire:navigate');
            });
        }

        document.addEventListener('DOMContentLoaded', disableWireNavigate);
        document.addEventListener('livewire:navigated', disableWireNavigate);
    </script>

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

        // Sistema SPA personalizado compatible con Vite
        class CustomSPA {
            constructor() {
                this.progressBar = this.createProgressBar();
                this.isNavigating = false;
                this.executedScripts = new Set();
                this.collectExistingScripts();
                this.init();
            }

            createProgressBar() {
                const bar = document.createElement('div');
                bar.className = 'livewire-progress-bar';
                bar.style.width = '0%';
                bar.style.display = 'none';
                document.body.appendChild(bar);
                return bar;
            }

            showProgress() {
                this.progressBar.style.display = 'block';
                this.progressBar.style.width = '0%';
                setTimeout(() => this.progressBar.style.width = '70%', 50);
            }

            hideProgress() {
                this.progressBar.style.width = '100%';
                setTimeout(() => {
                    this.progressBar.style.display = 'none';
                    this.progressBar.style.width = '0%';
                }, 300);
            }

            init() {
                // Interceptar clicks en links con data-spa-link
                document.addEventListener('click', (e) => {
                    const link = e.target.closest('a[data-spa-link]');
                    if (link && !this.isNavigating) {
                        e.preventDefault();
                        this.navigate(link.href);
                    }
                });

                // Manejar botones back/forward del navegador
                window.addEventListener('popstate', (e) => {
                    // Solo navegar si es nuestra navegación SPA
                    if (e.state && e.state.spa && !this.isNavigating) {
                        this.navigate(window.location.href, false);
                    }
                });

                // Prevenir que Livewire intente trackear URLs demasiado
                document.addEventListener('livewire:init', () => {
                    if (typeof Livewire !== 'undefined' && Livewire.hook) {
                        // Deshabilitar el tracking de URL de Livewire para evitar conflictos
                        Livewire.hook('url.changed', () => {
                            // No hacer nada - dejamos que nuestro SPA maneje la URL
                            return false;
                        });
                    }
                });
            }

            async navigate(url, pushState = true) {
                if (this.isNavigating) {
                    console.log('⏸️ Navegación ya en curso, ignorando...');
                    return;
                }

                console.log('🚀 Iniciando SPA navigation a:', url);
                this.isNavigating = true;
                this.showProgress();

                try {
                    console.log('📡 Haciendo fetch...');
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (!response.ok) {
                        console.error('❌ Fetch failed:', response.status);
                        throw new Error('Navigation failed');
                    }

                    console.log('✅ Fetch exitoso, parseando HTML...');
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Reemplazar solo el contenido principal
                    const newMain = doc.querySelector('main');
                    const currentMain = document.querySelector('main');
                    // Capturar scripts de la nueva vista antes de hacer morph
                    const newScripts = newMain ? Array.from(newMain.querySelectorAll('script')) : [];

                    if (!newMain) {
                        console.error('❌ No se encontró <main> en la nueva página');
                        window.location.href = url;
                        return;
                    }

                    if (!currentMain) {
                        console.error('❌ No se encontró <main> en la página actual');
                        window.location.href = url;
                        return;
                    }

                    console.log('🔄 Reemplazando contenido...');

                    // Reemplazar contenido usando morphing de Alpine
                    if (typeof Alpine !== 'undefined' && Alpine.morph) {
                        console.log('✨ Usando Alpine.morph');
                        Alpine.morph(currentMain, newMain);
                    } else {
                        console.log('⚠️ Alpine.morph no disponible, usando innerHTML');
                        currentMain.innerHTML = newMain.innerHTML;
                    }

                    // Ejecutar scripts de la vista para que Alpine tenga sus factories disponibles
                    this.executeScripts(newScripts);

                    // Actualizar título
                    document.title = doc.title;

                    // Scroll to top
                    window.scrollTo({
                        top: 0,
                        behavior: 'instant'
                    });

                    // Actualizar URL después de un delay para evitar conflictos con Livewire
                    if (pushState) {
                        setTimeout(() => {
                            console.log('🔗 Actualizando URL a:', url);
                            window.history.pushState({
                                spa: true
                            }, '', url);
                        }, 100);
                    }

                    console.log('✅ Navegación SPA completada exitosamente');
                } catch (error) {
                    console.error('❌ SPA navigation error:', error);
                    console.log('🔄 Fallback a navegación normal');
                    // Fallback a navegación normal
                    window.location.href = url;
                } finally {
                    this.hideProgress();
                    this.isNavigating = false;
                }
            }

            executeScripts(scripts) {
                scripts.forEach((oldScript) => {
                    if (oldScript.hasAttribute('data-navigate-once')) return;

                    const forceReload = oldScript.hasAttribute('data-navigate-reload');
                    const signature = this.getScriptSignature(oldScript);
                    if (!forceReload && this.executedScripts.has(signature)) return;

                    const script = document.createElement('script');
                    // Copiar atributos (src, type, etc)
                    for (const {
                            name,
                            value
                        }
                        of Array.from(oldScript.attributes)) {
                        script.setAttribute(name, value);
                    }
                    // Copiar contenido inline
                    if (oldScript.textContent) {
                        script.textContent = oldScript.textContent;
                    }
                    document.body.appendChild(script);
                    // Remover para evitar duplicados en el DOM
                    script.remove();
                    if (!forceReload) {
                        this.executedScripts.add(signature);
                    }
                });
            }

            collectExistingScripts() {
                document.querySelectorAll('script').forEach((script) => {
                    const signature = this.getScriptSignature(script);
                    this.executedScripts.add(signature);
                });
            }

            getScriptSignature(script) {
                if (script.src) return `src:${script.src}`;
                return `inline:${(script.textContent || '').trim()}`;
            }
        }

        // Inicializar SPA solo después de que todo esté cargado
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => new CustomSPA());
        } else {
            new CustomSPA();
        }
    </script>
</body>

</html>
