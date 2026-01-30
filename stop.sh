#!/bin/bash

# Script de Parada (Stop)
# Úsalo cuando termines de trabajar para detener los procesos sin borrarlos.

echo "==================================="
echo "   Deteniendo Entorno HPR Project  "
echo "==================================="

# Detener contenedores Docker (Sail)
echo "🛑 Deteniendo contenedores..."

# Check docker permissions
if ! docker info > /dev/null 2>&1; then
    echo "⚠️  Permisos de Docker no activos en esta sesión."
    echo "🔄 Intentando parar con sudo..."
    sudo ./vendor/bin/sail stop
else
    ./vendor/bin/sail stop
fi

if [ $? -eq 0 ]; then
    echo "==================================="
    echo "👋 Procesos detenidos correctamente"
    echo "==================================="
else
    echo "❌ Hubo un error al detener los procesos."
fi
