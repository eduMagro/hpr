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

    <style>
        /* Oculta cualquier elemento marcado con x-cloak hasta que Alpine quite el atributo */
        [x-cloak] {
            display: none !important;
        }
    </style>
    <style>
        /* 📏 Ajuste global del tamaño base */
        html {
            font-size: 70%;
            /* Reduce todo proporcionalmente, sin romper layout */
        }

        /* 🔧 Asegura que no haya márgenes ni overflow */
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            background-color: #f9fafb;
            /* opcional: color de fondo base */
        }

        /* 🪶 Opcional: suaviza textos y mantiene proporciones */
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
    </style>

</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')
        @include('layouts.alerts')
        <x-notificacion-alerta />
        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}

                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')

</body>

</html>
