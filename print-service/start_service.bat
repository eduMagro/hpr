@echo off
title Servicio de Impresion P-Touch
echo ===============================================
echo   Servicio de Impresion P-Touch
echo ===============================================
echo.
echo Iniciando servicio en http://localhost:8765
echo.
echo Presiona Ctrl+C para detener el servicio
echo.

REM Activar entorno virtual
call venv\Scripts\activate.bat

REM Iniciar servicio
python print_service.py

pause
