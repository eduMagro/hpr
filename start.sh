#!/bin/bash

# Script de Inicio Rápido (Daily Start)
# Úsalo cuando enciendas el PC para levantar el proyecto.

echo "==================================="
echo "   Iniciando Entorno HPR Project   "
echo "==================================="

# 1. Levantar Docker (Sail)
echo "🚀 Levantando contenedores Docker..."

# Check docker permissions
if ! docker info > /dev/null 2>&1; then
    echo "⚠️  Permisos de Docker no activos en esta sesión (Reinicia el PC para arreglarlo permanentemente)."
    echo "🔄 Intentando iniciar con sudo..."
    sudo ./vendor/bin/sail up -d
else
    ./vendor/bin/sail up -d
fi

# 2. Mensaje de estado
if [ $? -eq 0 ]; then
    echo "✅ Contenedores OK."
    
    # 3. Compilar Assets (Build para producción local)
    # Si vas a desarrollar, cancela esto y usa 'npm run dev' manualmente.
    echo "🎨 Actualizando vista (Frontend Build)..."
    npm run build
    
    echo "==================================="
    echo "🎉 PROYECTO INICIADO CORRECTAMENTE"
    echo "==================================="
    echo "🌍 App Web:      http://app.test"
    echo "🗄️  phpMyAdmin:   http://localhost:8080"
    echo "==================================="
else
    echo "❌ Error al levantar Docker. Asegúrate de que Docker Desktop/Service está corriendo."
fi
