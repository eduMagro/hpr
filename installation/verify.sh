#!/bin/bash

# Script to verify the installation status

echo "=== Verificando Instalación HPR ==="

# 1. Check Docker Containers
echo -n "Check Docker containers... "
if docker ps | grep -q "hpr-laravel.test-1"; then
    echo "OK (Running)"
else
    echo "FAIL (Not running)"
    docker ps
fi

# 2. Check Database Connection
echo -n "Check Database... "
# Attempt to run a simple artisan command that uses DB
if ./vendor/bin/sail artisan model:show User &> /dev/null; then
     echo "OK (Connected)"
else
    # Maybe migrations haven't run, check connection
    if ./vendor/bin/sail artisan db:show &> /dev/null; then
         echo "OK (Connected)"
    else
         echo "FAIL (Cannot connect)"
    fi
fi

# 3. Check Hostname
echo -n "Check app.test resolution... "
if grep -q "app.test" /etc/hosts; then
    echo "OK (Found in /etc/hosts)"
else
    echo "FAIL (Missing in /etc/hosts)"
fi

# 4. Check Frontend Assets
echo -n "Check Frontend Build... "
if [ -d "public/build" ]; then
    echo "OK (Build folder exists)"
else
    echo "FAIL (Missing public/build)"
fi

echo "=== Verificación completada ==="
