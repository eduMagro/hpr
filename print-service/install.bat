@echo off
echo ===============================================
echo   Instalador del Servicio de Impresion P-Touch
echo ===============================================
echo.

REM Verificar si Python esta instalado
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python no esta instalado.
    echo Por favor instala Python 3.8 o superior desde https://www.python.org/downloads/
    echo Asegurate de marcar "Add Python to PATH" durante la instalacion.
    pause
    exit /b 1
)

echo [OK] Python detectado
python --version
echo.

REM Crear entorno virtual
echo Creando entorno virtual...
python -m venv venv
if %errorlevel% neq 0 (
    echo [ERROR] No se pudo crear el entorno virtual
    pause
    exit /b 1
)
echo [OK] Entorno virtual creado
echo.

REM Activar entorno virtual e instalar dependencias
echo Instalando dependencias...
call venv\Scripts\activate.bat
pip install --upgrade pip
pip install -r requirements.txt
if %errorlevel% neq 0 (
    echo [ERROR] Error instalando dependencias
    pause
    exit /b 1
)
echo [OK] Dependencias instaladas
echo.

echo ===============================================
echo   Instalacion completada exitosamente!
echo ===============================================
echo.
echo Para iniciar el servicio, ejecuta: start_service.bat
echo.
pause
