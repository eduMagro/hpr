<?php

return [

    // 📌 Prefijos que los operarios pueden usar en rutas (middleware)
    'prefijos_operario' => [
        'produccion.trabajadores',
        'users.',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'entradas.',   // ✅ acceso permitido
        'movimientos.', // ✅ acceso permitido
        'ubicaciones.',
    ],

    // 📌 Prefijos que deben salir en el dashboard para operarios
    'prefijos_operario_dashboard' => [
        'produccion.trabajadores',
        'users.',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'ubicaciones.',
        // 👀 aquí NO ponemos movimientos ni entradas
    ],


    // 📌 Prefijos permitidos para TRANSPORTISTAS
    'prefijos_transportista' => [
        'users.',
        'alertas.',
        'vacaciones.solicitar',
        'planificacion.index',
        'usuarios.editarSubirImagen',
        'usuarios.imagen',
        'nominas.crearDescargarMes',
    ],

    // 📌 Rutas libres (si las necesitas en helpers también)
    'rutas_libres' => [
        'politica.privacidad',
        'politica.cookies',
        'politicas.aceptar',
        'ayuda.index',
        'usuarios.show',
        'usuarios.index',
        'usuarios.imagen',
        'usuarios.editarSubirImagen',
        'nominas.crearDescargarMes',
        'turno.cambiarMaquina',
        'salida.completarDesdeMovimiento',
        'alertas.index',
        'alertas.store',
        'alertas.update',
        'alertas.destroy',
        'alertas.verMarcarLeidas',
        'alertas.verSinLeer',
        'vacaciones.solicitar',
    ],

    // 📌 Correos con acceso total
    'correos_acceso_total' => [
        'eduardo.magro@pacoreyes.com',
        'sebastian.duran@pacoreyes.com',
        'juanjose.dorado@pacoreyes.com',
        'josemanuel.amuedo@pacoreyes.com',
        'jose.amuedo@pacoreyes.com',
        'manuel.reyes@pacoreyes.com',
        'alvarofaces@gruporeyestejero.com',
        'pabloperez@gruporeyestejero.com',
        'marcosloldorado@gmail.com',
    ],

];
