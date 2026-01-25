@echo off
chcp 65001 >nul
title Desinstalador BVBS Monitor

echo ============================================================
echo         DESINSTALADOR - BVBS Monitor
echo ============================================================
echo.
echo Esto eliminara el monitor BVBS del sistema.
echo.
echo Continuar? (S/N)
set /p CONFIRMAR="> "

if /i not "%CONFIRMAR%"=="S" (
    echo Cancelado.
    pause
    exit
)

echo.
echo Deteniendo procesos...
taskkill /f /im powershell.exe /fi "WINDOWTITLE eq BVBS*" >nul 2>&1

echo Eliminando acceso directo de inicio...
del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\BVBS_Monitor.lnk" >nul 2>&1

echo Eliminando archivos...
rmdir /s /q "%USERPROFILE%\BVBS_Monitor" >nul 2>&1

echo.
echo ============================================================
echo         DESINSTALACION COMPLETADA
echo ============================================================
echo.
echo El monitor BVBS ha sido eliminado del sistema.
echo.
pause
