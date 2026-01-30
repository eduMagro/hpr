#!/bin/bash

# HPR Project Installation Script
# Usage: sudo ./setup.sh

set -e
export DEBIAN_FRONTEND=noninteractive

# Validate sudo
if [ "$EUID" -ne 0 ]; then
  echo "Por favor ejecuta este script como root (sudo ./setup.sh)"
  exit
fi

echo "=== Inicio de la instalación de HPR ==="

# 1. Update System
echo "[1/7] Actualizando sistema..."
apt-get update -qq

# 2. Install Dependencies
echo "[2/7] Instalando dependencias del sistema..."
apt-get install -y curl git unzip zip software-properties-common ca-certificates gnupg

# 3. Install PHP 8.2 (Required for Composer/Laravel)
echo "[3/7] Instalando PHP 8.2 y extensiones..."
add-apt-repository ppa:ondrej/php -y
apt-get update -qq
apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-curl php8.2-mbstring php8.2-xml \
    php8.2-zip php8.2-mysql php8.2-bcmath php8.2-intl php8.2-gd php8.2-sqlite3

# 4. Install Composer
echo "[4/7] Instalando Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    echo "Composer instalado."
else
    echo "Composer ya existe."
fi

# 5. Install Node.js (LTS)
echo "[5/7] Instalando Node.js..."
if ! command -v node &> /dev/null; then
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list
    apt-get update -qq
    apt-get install -y nodejs
    echo "Node.js instalado."
else
    echo "Node.js ya existe."
fi

# 6. Install Docker
echo "[6/7] Instalando Docker..."
if ! command -v docker &> /dev/null; then
    install -m 0755 -d /etc/apt/keyrings
    # Remove old versions if any
    for pkg in docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc; do apt-get remove -y $pkg || true; done
    
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc

    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      tee /etc/apt/sources.list.d/docker.list > /dev/null
    apt-get update -qq
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    
    echo "Docker instalado."
else
    echo "Docker ya existe."
fi

# Add user to docker group
if [ -n "$SUDO_USER" ]; then
    usermod -aG docker $SUDO_USER
fi

# 8. Configure /etc/hosts for app.test
echo "[Configuration] Configurando dominio local app.test..."
if grep -q "app.test" /etc/hosts; then
    echo "app.test ya existe en /etc/hosts."
else
    echo "127.0.0.1 app.test" >> /etc/hosts
    echo "Añadido app.test a /etc/hosts."
fi

# 9. Setup Project
echo "[Finalizando] Configurando proyecto..."

# Fix permissions
chown -R $SUDO_USER:$SUDO_USER .

# Run commands as user
sudo -u $SUDO_USER bash << 'EOF'
    # Copy Env if needed
    if [ ! -f .env ]; then
        if [ -f installation/env_backup ]; then
            echo "Restaurando .env desde backup..."
            cp installation/env_backup .env
        else
            echo "Creando .env desde ejemplo..."
            cp .env.example .env
        fi
    fi

    # Install PHP Deps
    echo "Ejecutando composer install..."
    composer install

    # Install Node Deps
    echo "Ejecutando npm install..."
    npm install
    npm run build

    # Start Sail
    echo "Construyendo contenedores Docker..."
    ./vendor/bin/sail build

    echo "Iniciando Docker Sail..."
    ./vendor/bin/sail up -d
    
    # Wait a bit for DB
    echo "Esperando a la base de datos..."
    sleep 10

    # Key & Migrate
    echo "Configurando Laravel..."
    ./vendor/bin/sail artisan key:generate
    # ./vendor/bin/sail artisan migrate
    ./vendor/bin/sail artisan storage:link

    # Fix permissions for storage and logs
    echo "Ajustando permisos de storage..."
    sudo chmod -R 777 storage bootstrap/cache

    # Ensure SESSION_DRIVER is file if migrations are not run
    if grep -q "SESSION_DRIVER=database" .env; then
        echo "Ajustando SESSION_DRIVER a file (migraciones pendientes)..."
        sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/' .env
    fi
EOF

echo "=== Instalación completada exitosamente ==="
echo ""
echo "Aplicación disponible en: http://localhost"
echo "Base de datos (phpMyAdmin): http://localhost:8080"
