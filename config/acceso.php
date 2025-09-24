<?php

return [

    // 📌 Prefijos permitidos para OPERARIOS
    'prefijos_operario' => [
        'produccion.trabajadores',
        'users.',
        'alertas.',
        'productos.',
        'pedidos.',
        'ayuda.',
        'maquinas.',
        'entradas.',
        'movimientos.',
        'ubicaciones.',
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
    ],

];
