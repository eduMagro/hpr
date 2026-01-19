@echo off
REM =====================================================================
REM Script de Sincronización FerraWin → Producción
REM Ejecutar con Windows Task Scheduler a las 14:00
REM =====================================================================

setlocal

REM Configuración
set PHP_PATH=C:\xampp\php\php.exe
set PROJECT_PATH=C:\xampp\htdocs\manager
set LOG_PATH=%PROJECT_PATH%\storage\logs\ferrawin-sync-push.log

REM Fecha y hora para el log
echo. >> "%LOG_PATH%"
echo ========================================== >> "%LOG_PATH%"
echo [%date% %time%] Iniciando sincronización >> "%LOG_PATH%"
echo ========================================== >> "%LOG_PATH%"

REM Ejecutar comando de sincronización
cd /d "%PROJECT_PATH%"
"%PHP_PATH%" artisan sync:ferrawin-push --compress >> "%LOG_PATH%" 2>&1

REM Verificar resultado
if %ERRORLEVEL% EQU 0 (
    echo [%date% %time%] Sincronización completada exitosamente >> "%LOG_PATH%"
) else (
    echo [%date% %time%] ERROR: Sincronización falló con código %ERRORLEVEL% >> "%LOG_PATH%"
)

echo. >> "%LOG_PATH%"

endlocal
