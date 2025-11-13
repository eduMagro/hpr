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

    <!-- ✅ Tu CSS (precarga y minificación) -->
    <link rel="preload" href="{{ asset('css/styles.css') }}" as="style" onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    </noscript>

    <!-- ✅ Tailwind (si lo usas como principal) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- ✅ Solo una versión de Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- ✅ Librerías que no bloquean renderizado -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js" defer></script>

    <!-- ✅ FullCalendar (solo si es necesario en esta vista) -->
    @stack('calendar') {{-- así solo lo cargas si lo necesitas --}}

    <!-- Livewire Styles -->
    @livewireStyles

    <style>
        /* Oculta cualquier elemento marcado con x-cloak hasta que Alpine quite el atributo */
        [x-cloak] {
            display: none !important;
        }
    </style>
    <style>
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
    </style>

    @livewireStyles
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
            <x-notificacion-alerta />

            <!-- Page Content with Scroll -->
            <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 transition-colors">
                <div class="py-6 px-4 sm:px-6 lg:px-8">
                    <!-- Breadcrumbs -->
                    <x-breadcrumbs />

                    <!-- Page Heading -->
                    @isset($header)
                        <header class="mb-6">
                            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-6 py-4 border border-gray-200 dark:border-gray-700 transition-colors">
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
    @stack('scripts')

    <!-- Livewire Scripts -->
    @livewireScripts
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Dark Mode Support Script -->
    <script>
        // Aplicar dark mode desde localStorage al cargar
        if (localStorage.getItem('dark_mode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>

</html>
