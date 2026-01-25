@echo off
chcp 65001 >nul
title Instalador BVBS Monitor

echo ============================================================
echo           INSTALADOR - BVBS Monitor
echo ============================================================
echo.

:: Crear carpeta de destino para el script
set "DESTINO=%USERPROFILE%\BVBS_Monitor"
if not exist "%DESTINO%" mkdir "%DESTINO%"

:: Copiar el script de PowerShell
echo Copiando script...
copy /Y "%~dp0BVBS_Monitor.ps1" "%DESTINO%\BVBS_Monitor.ps1" >nul

:: Crear el script VBS que ejecuta PowerShell oculto (sin ventana)
echo Creando lanzador silencioso...
(
echo Set objShell = CreateObject^("WScript.Shell"^)
echo objShell.Run "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File ""%DESTINO%\BVBS_Monitor.ps1""", 0, False
) > "%DESTINO%\BVBS_Monitor_Silent.vbs"

:: Crear acceso directo en la carpeta de Inicio de Windows
echo Configurando inicio automatico...
set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"

:: Usar PowerShell para crear el acceso directo
powershell -Command "$ws = New-Object -ComObject WScript.Shell; $s = $ws.CreateShortcut('%STARTUP%\BVBS_Monitor.lnk'); $s.TargetPath = '%DESTINO%\BVBS_Monitor_Silent.vbs'; $s.WorkingDirectory = '%DESTINO%'; $s.Description = 'Monitor de archivos BVBS para MSR20'; $s.Save()"

echo.
echo ============================================================
echo           INSTALACION COMPLETADA
echo ============================================================
echo.
echo El monitor se iniciara automaticamente con Windows.
echo.
echo Carpeta de instalacion: %DESTINO%
echo.
echo Quieres iniciar el monitor ahora? (S/N)
set /p INICIAR="> "

if /i "%INICIAR%"=="S" (
    echo.
    echo Iniciando BVBS Monitor...
    start "" "%DESTINO%\BVBS_Monitor_Silent.vbs"
    echo Monitor iniciado en segundo plano.
)

echo.
echo Presiona cualquier tecla para cerrar...
pause >nul
