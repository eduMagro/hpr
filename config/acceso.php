<?php

return [

    // ══════════════════════════════════════════════════════════════════════════
    // ⚠️  DEPRECADO: Los permisos de operarios ahora se gestionan desde
    //     /departamentos asignando secciones al departamento "Operario"
    //
    // Para migrar: php artisan permisos:migrar-operarios
    // ══════════════════════════════════════════════════════════════════════════
    // 📌 Referencia de prefijos que los operarios necesitan (para migración):
    // - albaranes, produccion.trabajadores, users, alertas, productos, pedidos
    // - ayuda, maquinas, etiquetas, elementos, subetiquetas, paquetes
    // - localizaciones, api, entradas, movimientos, ubicaciones
    // - inventario-backups, incorporaciones, usuarios, vacaciones
    // ══════════════════════════════════════════════════════════════════════════


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
        'incorporaciones.descargarMiContrato', // ✅ descargar mi contrato
        'turno.cambiarMaquina',
        'salida.completarDesdeMovimiento',
        'alertas.index',
        'alertas.store',
        'alertas.update',
        'alertas.destroy',
        'alertas.verMarcarLeidas',
        'alertas.verSinLeer',
        'vacaciones.solicitar',
        'users.verEventos-turnos',
        'users.verResumen-asistencia',
        'usuarios.getVacationData', // ✅ datos de vacaciones propios
        'api.mapaNave', // ✅ mapa de nave (usado en grúa y otras vistas)
        'api.produccion.eventos', // ✅ eventos del calendario de máquinas
        'api.produccion.recursos', // ✅ recursos del calendario de máquinas
        'api.produccion.resumen', // ✅ resumen del calendario de máquinas
        'api.produccion.ultimo-snapshot', // ✅ snapshot para deshacer
        'api.produccion.obras-activas', // ✅ obras activas para priorización
        'produccion.actualizaciones', // ✅ actualizaciones en tiempo real
    ],

    // 📌 Correos con acceso total
    'correos_acceso_total' => [
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
