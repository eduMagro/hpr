<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Página principal - HPR') }}</title>

    {{-- <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap (solo CSS si es necesario) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}"> --}}
      
      <!-- Agregar Bootstrap desde CDN -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
      {{-- para graficos --}}
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
      <!-- ESTILOS -->
      <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
          integrity="sha384-oBqDVmMz4fnFO9gybK3zs7hcm9Kmdt6Q5Ppp3HLzE5ptlTmz3Ovv9r3e2b9Kr5Tk" crossorigin="anonymous">
      </script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"
          integrity="sha384-pzjw8f+ua7Kw1TIq0lLvYdpP1fo/+/fo+2Vxwz3bce4C/qvkeBxjtvxDsw7fG5p5" crossorigin="anonymous">
      </script>
  <script src="https://cdn.tailwindcss.com"></script>
  
      <!-- Scripts -->
      <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/library.js"></script>
       <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
	
	<!-- JAVASCRIPT -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Iconos y Configuración PWA -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imagenes/ico/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('imagenes/ico/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('imagenes/ico/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('imagenes/ico/android-chrome-192x192.png') }}">
    <link rel="manifest" href="{{ asset('imagenes/ico/site.webmanifest') }}">
    <meta name="theme-color" content="#ffffff">

</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation', ['useTailwind' => true])

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
