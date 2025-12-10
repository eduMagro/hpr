<?php

/**
 * Script para ejecutar el scheduler de Laravel desde Plesk
 * Configurar en Plesk: Tareas programadas → Ejecutar script PHP
 * Frecuencia: Estilo cron → * * * * *
 */

// Cambiar al directorio del proyecto
chdir(__DIR__);

// Ejecutar el schedule:run
exec('php artisan schedule:run 2>&1', $output, $returnCode);

// Mostrar resultado
echo implode("\n", $output);
exit($returnCode);
