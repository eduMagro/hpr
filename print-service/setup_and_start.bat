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

REM Intentar encontrar Python de varias formas
set "PYTHON_EXE="

REM Opcion 1: python en PATH
where python >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=*" %%i in ('where python') do (
        if not defined PYTHON_EXE set "PYTHON_EXE=%%i"
    )
)

REM Opcion 2: py launcher (viene con instalador oficial de Python)
if not defined PYTHON_EXE (
    where py >nul 2>&1
    if %errorlevel% equ 0 (
        set "PYTHON_EXE=py"
    )
)

REM Opcion 3: Rutas comunes de instalacion
if not defined PYTHON_EXE (
    if exist "C:\Python312\python.exe" set "PYTHON_EXE=C:\Python312\python.exe"
    if exist "C:\Python311\python.exe" set "PYTHON_EXE=C:\Python311\python.exe"
    if exist "C:\Python310\python.exe" set "PYTHON_EXE=C:\Python310\python.exe"
    if exist "%LOCALAPPDATA%\Programs\Python\Python312\python.exe" set "PYTHON_EXE=%LOCALAPPDATA%\Programs\Python\Python312\python.exe"
    if exist "%LOCALAPPDATA%\Programs\Python\Python311\python.exe" set "PYTHON_EXE=%LOCALAPPDATA%\Programs\Python\Python311\python.exe"
    if exist "%LOCALAPPDATA%\Programs\Python\Python310\python.exe" set "PYTHON_EXE=%LOCALAPPDATA%\Programs\Python\Python310\python.exe"
)

if not defined PYTHON_EXE (
    echo.
    echo ============================================================
    echo   [ERROR] Python no esta instalado o no se encuentra
    echo ============================================================
    echo.
    echo   Por favor instala Python 3.10 o superior:
    echo.
    echo   1. Ve a: https://www.python.org/downloads/
    echo   2. Descarga Python 3.11 o superior
    echo   3. IMPORTANTE: Marca "Add Python to PATH" en el instalador
    echo   4. Reinicia este script despues de instalar
    echo.
    echo   NOTA: NO instales Python desde Microsoft Store
    echo.
    echo ============================================================
    echo.
    echo Abriendo pagina de descarga de Python...
    start https://www.python.org/downloads/
    pause
    exit /b 1
)

REM Mostrar version de Python
for /f "tokens=*" %%i in ('"%PYTHON_EXE%" --version 2^>^&1') do set PYTHON_VERSION=%%i
echo [OK] %PYTHON_VERSION% detectado
echo [INFO] Ejecutable: %PYTHON_EXE%
echo.

REM ============================================================
REM PASO 2: Crear/Verificar entorno virtual
REM ============================================================
echo [PASO 2/4] Verificando entorno virtual...

set "VENV_PYTHON=%SCRIPT_DIR%venv\Scripts\python.exe"
set "VENV_PIP=%SCRIPT_DIR%venv\Scripts\pip.exe"
set "VENV_ACTIVATE=%SCRIPT_DIR%venv\Scripts\activate.bat"

if not exist "%VENV_PYTHON%" (
    echo [INFO] Creando entorno virtual...
    "%PYTHON_EXE%" -m venv "%SCRIPT_DIR%venv"
    if errorlevel 1 (
        echo [ERROR] No se pudo crear el entorno virtual
        echo.
        echo Posibles soluciones:
        echo   1. Asegurate de NO usar Python de Microsoft Store
        echo   2. Instala Python desde python.org
        echo   3. Ejecuta como administrador
        pause
        exit /b 1
    )

    REM Verificar que se creo correctamente
    if not exist "%VENV_PYTHON%" (
        echo [ERROR] El entorno virtual no se creo correctamente
        echo         No se encuentra: %VENV_PYTHON%
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

REM Verificar si flask esta instalado
"%VENV_PYTHON%" -c "import flask" >nul 2>&1
if errorlevel 1 (
    echo [INFO] Instalando dependencias...
    echo.
    echo --- Actualizando pip ---
    "%VENV_PYTHON%" -m pip install --upgrade pip
    if errorlevel 1 (
        echo [AVISO] No se pudo actualizar pip, continuando...
    )
    echo.
    echo --- Instalando Flask y Flask-CORS ---
    "%VENV_PYTHON%" -m pip install flask flask-cors
    if errorlevel 1 (
        echo [ERROR] No se pudo instalar Flask
        pause
        exit /b 1
    )
    echo.
    echo --- Instalando pywin32 ---
    "%VENV_PYTHON%" -m pip install pywin32
    if errorlevel 1 (
        echo [ERROR] No se pudo instalar pywin32
        pause
        exit /b 1
    )
    echo.
)

REM Verificar que flask se instalo correctamente
"%VENV_PYTHON%" -c "import flask" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Flask no se pudo instalar correctamente
    pause
    exit /b 1
)
echo [OK] Dependencias instaladas
echo.

REM ============================================================
REM PASO 4: Verificar e instalar b-PAC SDK
REM ============================================================
echo [PASO 4/4] Verificando Brother b-PAC SDK...

"%VENV_PYTHON%" -c "import win32com.client; win32com.client.Dispatch('bpac.Document')" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Brother b-PAC SDK detectado
    echo.
    goto :START_SERVICE
)

echo [AVISO] Brother b-PAC SDK no detectado
echo.

REM Buscar si hay un .msi en la carpeta actual
set "MSI_FILE="
for %%f in ("*.msi") do set "MSI_FILE=%%f"

if not defined MSI_FILE goto :NO_MSI_FOUND

echo [INFO] Encontrado archivo: %MSI_FILE%
echo [INFO] Instalando b-PAC SDK silenciosamente...
msiexec /i "%MSI_FILE%" /qb
echo [OK] Instalacion de b-PAC completada
echo.
goto :START_SERVICE

:NO_MSI_FOUND
echo.
echo ============================================================
echo   INSTALACION DE BROTHER b-PAC SDK
echo ============================================================
echo.
echo   El SDK de Brother es necesario para imprimir etiquetas.
echo.
echo   Opciones:
echo   1. Descarga el "Client Component (64-bit)" desde:
echo      https://support.brother.com/g/s/es/dev/en/bpac/download/index.html
echo.
echo   2. Coloca el archivo .msi en esta carpeta:
echo      %SCRIPT_DIR%
echo.
echo   3. Vuelve a ejecutar este script
echo.
echo ============================================================
echo.

set /p "OPEN_BROWSER=Quieres abrir la pagina de descarga ahora? (S/N): "
if /i not "%OPEN_BROWSER%"=="S" goto :SKIP_BPAC

start https://support.brother.com/g/s/es/dev/en/bpac/download/index.html
echo.
echo [INFO] Pagina abierta. Descarga "Client Component 64-bit" (.msi)
echo        Guarda el archivo en: %SCRIPT_DIR%
echo.
set /p "WAIT_DOWNLOAD=Presiona ENTER cuando hayas descargado el archivo..."

REM Buscar de nuevo el .msi
set "MSI_FILE="
for %%f in ("*.msi") do set "MSI_FILE=%%f"

if not defined MSI_FILE (
    echo [AVISO] No se encontro archivo .msi
    echo         Puedes continuar sin b-PAC pero la impresion fallara.
    goto :SKIP_BPAC
)

echo [INFO] Encontrado: %MSI_FILE%
echo [INFO] Instalando...
msiexec /i "%MSI_FILE%" /qb
echo [OK] b-PAC SDK instalado correctamente
goto :START_SERVICE

:SKIP_BPAC
echo [INFO] Puedes instalar b-PAC manualmente mas tarde.
echo.

:START_SERVICE

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

"%VENV_PYTHON%" "%SCRIPT_DIR%print_service.py"

pause
