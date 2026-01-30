<?php

/**
 * Configuración de accesos del sistema.
 *
 * La lógica principal de permisos está centralizada en:
 * App\Services\PermissionService
 *
 * Este archivo contiene configuraciones que no cambian frecuentemente.
 * Para permisos dinámicos, usar las tablas:
 * - departamento_seccion: secciones accesibles por departamento
 * - departamento_ruta: rutas específicas por departamento
 * - permisos_acceso: permisos directos de usuario
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Correos con Acceso Total
    |--------------------------------------------------------------------------
    |
    | Usuarios con acceso completo al sistema sin restricciones.
    | Estos usuarios pueden ver y acceder a todas las secciones y rutas.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Rutas Universales
    |--------------------------------------------------------------------------
    |
    | Rutas accesibles para CUALQUIER usuario autenticado, sin importar empresa.
    | Usadas para funciones básicas como ver el propio perfil.
    |
    */
    'rutas_universales' => [
        'usuarios.show',           // Mi perfil
        'usuarios.imagen',         // Imagen de perfil
        'politica.privacidad',
        'politica.cookies',
        'politicas.aceptar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas Libres
    |--------------------------------------------------------------------------
    |
    | Rutas accesibles para cualquier usuario autenticado de empresas permitidas.
    | No requieren permisos específicos.
    |
    */
    'rutas_libres' => [
        // Políticas y ayuda
        'ayuda.index',

        // Perfil y usuario
        'usuarios.index',
        'usuarios.editarSubirImagen',
        'nominas.crearDescargarMes',
        'incorporaciones.descargarMiContrato',
        'usuarios.getVacationData',

        // Alertas
        'alertas.index',
        'alertas.store',
        'alertas.update',
        'alertas.destroy',
        'alertas.verMarcarLeidas',
        'alertas.verSinLeer',

        // Vacaciones y turnos
        'vacaciones.solicitar',
        'users.verEventos-turnos',
        'users.verResumen-asistencia',
        'turno.cambiarMaquina',
        'salida.completarDesdeMovimiento',

        // API públicas
        'api.mapaNave',
        'api.produccion.eventos',
        'api.produccion.recursos',
        'api.produccion.resumen',
        'api.produccion.ultimo-snapshot',
        'api.produccion.obras-activas',
        'produccion.actualizaciones',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefijos para Transportistas
    |--------------------------------------------------------------------------
    |
    | Rutas accesibles para usuarios con rol 'transportista'.
    | TODO: Migrar a departamento_ruta para mayor flexibilidad.
    |
    */
    'prefijos_transportista' => [
        'users.',
        'alertas.',
        'vacaciones.solicitar',
        'planificacion.index',
        'usuarios.editarSubirImagen',
        'usuarios.imagen',
        'nominas.crearDescargarMes',
    ],

];
