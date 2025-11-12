@echo off
echo Limpiando configuracion de Laravel...
cd /d C:\xampp\htdocs\manager
php artisan config:clear
php artisan cache:clear
echo.
echo ¡Listo! La nueva API key de Anthropic esta configurada.
echo Ahora recarga el navegador y prueba el asistente.
pause
