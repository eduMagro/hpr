@echo off
setlocal enabledelayedexpansion
title Servicio de Impresion P-Touch

echo.
echo ============================================================
echo    SERVICIO DE IMPRESION P-TOUCH - INSTALACION AUTOMATICA
echo ============================================================
echo.

REM Obtener directorio del script y cambiar a el
set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%"

echo [INFO] Directorio: %SCRIPT_DIR%
echo.

REM ============================================================
REM PASO 1: Verificar Python
REM ============================================================
echo [PASO 1/4] Verificando Python...

where python >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ============================================================
    echo   [ERROR] Python no esta instalado
    echo ============================================================
    echo.
    echo   Por favor instala Python 3.8 o superior:
    echo.
    echo   1. Ve a: https://www.python.org/downloads/
    echo   2. Descarga Python 3.11 o superior
    echo   3. IMPORTANTE: Marca "Add Python to PATH" en el instalador
    echo   4. Reinicia este script despues de instalar
    echo.
    echo ============================================================
    echo.
    echo Abriendo pagina de descarga de Python...
    start https://www.python.org/downloads/
    pause
    exit /b 1
)

for /f "tokens=*" %%i in ('python --version 2^>^&1') do set PYTHON_VERSION=%%i
echo [OK] %PYTHON_VERSION% detectado
echo.

REM ============================================================
REM PASO 2: Crear/Verificar entorno virtual
REM ============================================================
echo [PASO 2/4] Verificando entorno virtual...

if not exist "venv\Scripts\activate.bat" (
    echo [INFO] Creando entorno virtual...
    python -m venv venv
    if %errorlevel% neq 0 (
        echo [ERROR] No se pudo crear el entorno virtual
        pause
        exit /b 1
    )
    echo [OK] Entorno virtual creado
) else (
    echo [OK] Entorno virtual ya existe
)
echo.

REM ============================================================
REM PASO 3: Instalar dependencias
REM ============================================================
echo [PASO 3/4] Verificando dependencias...

call venv\Scripts\activate.bat

REM Verificar si flask esta instalado
venv\Scripts\python.exe -c "import flask" >nul 2>&1
if errorlevel 1 (
    echo [INFO] Instalando dependencias...
    echo.
    echo --- Actualizando pip ---
    venv\Scripts\python.exe -m pip install --upgrade pip
    echo.
    echo --- Instalando Flask y Flask-CORS ---
    venv\Scripts\python.exe -m pip install flask flask-cors
    echo.
    echo --- Instalando pywin32 ---
    venv\Scripts\python.exe -m pip install pywin32
    echo.
)

REM Verificar que flask se instalo correctamente
venv\Scripts\python.exe -c "import flask" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Flask no se pudo instalar correctamente
    pause
    exit /b 1
)
echo [OK] Dependencias instaladas
echo.

REM ============================================================
REM PASO 4: Verificar b-PAC SDK
REM ============================================================
echo [PASO 4/4] Verificando Brother b-PAC SDK...

venv\Scripts\python.exe -c "import win32com.client; win32com.client.Dispatch('bpac.Document')" >nul 2>&1
if %errorlevel% neq 0 (
    echo [AVISO] Brother b-PAC SDK no detectado o no configurado
    echo         La impresion puede fallar si no esta instalado.
    echo         Descarga: https://support.brother.com/g/s/es/dev/en/bpac/download/index.html
    echo.
) else (
    echo [OK] Brother b-PAC SDK detectado
    echo.
)

REM ============================================================
REM INICIAR SERVICIO
REM ============================================================
echo.
echo ============================================================
echo   [OK] INSTALACION COMPLETADA - INICIANDO SERVICIO
echo ============================================================
echo.
echo   El servicio estara disponible en: http://localhost:8765
echo.
echo   Manten esta ventana abierta mientras uses la aplicacion
echo   Presiona Ctrl+C para detener el servicio
echo.
echo ============================================================
echo.

venv\Scripts\python.exe print_service.py

pause
