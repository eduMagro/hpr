# Guía de Instalación del VPS - Proyecto Manager HPR

Esta guía te llevará paso a paso desde un VPS limpio hasta tener la aplicación completamente funcional.

---

## Tabla de Contenidos

1. [Preparación Inicial](#preparación-inicial)
2. [Instalación de Software Base](#instalación-de-software-base)
3. [Configuración de MySQL](#configuración-de-mysql)
4. [Configuración de PHP](#configuración-de-php)
5. [Configuración de Nginx](#configuración-de-nginx)
6. [Configuración de Redis](#configuración-de-redis)
7. [Configuración de Supervisor](#configuración-de-supervisor)
8. [Configuración de SSL con Certbot](#configuración-de-ssl-con-certbot)
9. [Deployment de la Aplicación](#deployment-de-la-aplicación)
10. [Configuración de Cron](#configuración-de-cron)
11. [Verificación Final](#verificación-final)
12. [Troubleshooting](#troubleshooting)

---

## Preparación Inicial

### 1. Conectar al VPS vía SSH

```bash
ssh root@TU_IP_VPS
```

### 2. Actualizar el Sistema

```bash
apt update && apt upgrade -y
```

### 3. Configurar Zona Horaria

```bash
timedatectl set-timezone Europe/Madrid
```

### 4. Crear Usuario para la Aplicación (opcional pero recomendado)

```bash
# Crear usuario
adduser deployer

# Agregar a sudo
usermod -aG sudo deployer

# Cambiar a ese usuario
su - deployer
```

### 5. Configurar Firewall Básico

```bash
# Instalar UFW si no está
sudo apt install -y ufw

# Permitir SSH
sudo ufw allow 22/tcp

# Permitir HTTP y HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Habilitar firewall
sudo ufw enable

# Verificar estado
sudo ufw status
```

---

## Instalación de Software Base

### 1. Agregar Repositorio de PHP 8.2

```bash
# Agregar repositorio ondrej/php
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### 2. Instalar PHP 8.2 y Todas las Extensiones

```bash
sudo apt install -y \
  php8.2-fpm \
  php8.2-cli \
  php8.2-common \
  php8.2-mysql \
  php8.2-mbstring \
  php8.2-xml \
  php8.2-curl \
  php8.2-gd \
  php8.2-zip \
  php8.2-bcmath \
  php8.2-intl \
  php8.2-sqlite3 \
  php8.2-opcache \
  php8.2-readline
```

### 3. Verificar Instalación de PHP

```bash
php -v
# Debe mostrar: PHP 8.2.x

php -m | grep -E 'gd|zip|mysql|mbstring|xml|curl'
# Debe mostrar todas las extensiones
```

### 4. Instalar Nginx

```bash
sudo apt install -y nginx
```

### 5. Instalar MySQL

```bash
sudo apt install -y mysql-server
```

### 6. Instalar Redis (Opcional pero Recomendado)

```bash
sudo apt install -y redis-server
```

### 7. Instalar Supervisor

```bash
sudo apt install -y supervisor
```

### 8. Instalar Composer

```bash
# Descargar instalador
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php

# Instalar globalmente
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Verificar
composer --version

# Limpiar
rm composer-setup.php
```

### 9. Instalar Node.js 18 LTS

```bash
# Agregar repositorio NodeSource
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -

# Instalar Node.js y npm
sudo apt install -y nodejs

# Verificar
node -v
npm -v
```

### 10. Instalar Certbot para SSL

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 11. Instalar Herramientas Adicionales

```bash
sudo apt install -y git curl wget unzip vim
```

### 12. Instalar Librerías Gráficas

```bash
sudo apt install -y \
  libgd-dev \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  libfontconfig1 \
  zlib1g-dev
```

---

## Configuración de MySQL

### 1. Ejecutar Script de Seguridad

```bash
sudo mysql_secure_installation
```

Responder:
- **Validate password component?** Y (si quieres contraseñas fuertes)
- **Remove anonymous users?** Y
- **Disallow root login remotely?** Y
- **Remove test database?** Y
- **Reload privilege tables now?** Y

### 2. Crear Base de Datos y Usuario

```bash
sudo mysql
```

Dentro de MySQL:

```sql
-- Crear base de datos
CREATE DATABASE manager_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario
CREATE USER 'manager_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SUPER_SEGURA';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON manager_production.* TO 'manager_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Salir
EXIT;
```

### 3. Verificar Conexión

```bash
mysql -u manager_user -p manager_production
# Ingresar password y verificar que conecta
```

### 4. Configurar MySQL para Rendimiento (Opcional)

Editar `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```bash
sudo vim /etc/mysql/mysql.conf.d/mysqld.cnf
```

Agregar/modificar:

```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
```

Reiniciar MySQL:

```bash
sudo systemctl restart mysql
```

---

## Configuración de PHP

### 1. Editar php.ini para FPM

```bash
sudo vim /etc/php/8.2/fpm/php.ini
```

Buscar y modificar estas líneas:

```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
file_uploads = On
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; OPcache configuration
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.save_comments = 1
```

### 2. Editar php.ini para CLI (mismo archivo)

```bash
sudo vim /etc/php/8.2/cli/php.ini
```

Aplicar los mismos cambios (excepto opcache puede quedarse con validate_timestamps = 1).

### 3. Crear Directorio de Logs de PHP

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

### 4. Configurar PHP-FPM Pool

```bash
sudo vim /etc/php/8.2/fpm/pool.d/www.conf
```

Modificar estas líneas:

```ini
user = www-data
group = www-data

listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500
```

### 5. Reiniciar PHP-FPM

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

### 6. Verificar Estado

```bash
sudo systemctl status php8.2-fpm
```

---

## Configuración de Nginx

### 1. Eliminar Configuración por Defecto

```bash
sudo rm /etc/nginx/sites-enabled/default
```

### 2. Crear Configuración del Sitio

Ver archivo `nginx-config.conf` en la carpeta `docs/config/`.

```bash
sudo vim /etc/nginx/sites-available/manager
```

Pegar la configuración del archivo `nginx-config.conf` (ajustar dominio y rutas).

### 3. Habilitar Sitio

```bash
sudo ln -s /etc/nginx/sites-available/manager /etc/nginx/sites-enabled/
```

### 4. Verificar Configuración

```bash
sudo nginx -t
```

Debe mostrar:
```
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 5. Reiniciar Nginx

```bash
sudo systemctl restart nginx
sudo systemctl enable nginx
```

---

## Configuración de Redis

### 1. Editar Configuración de Redis

```bash
sudo vim /etc/redis/redis.conf
```

Modificar:

```conf
# Bind solo a localhost (seguridad)
bind 127.0.0.1 ::1

# Requerir password (opcional pero recomendado)
requirepass TU_PASSWORD_REDIS_SEGURA

# Persistencia
save 900 1
save 300 10
save 60 10000

# Memoria máxima (ajustar según tu RAM)
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 2. Reiniciar Redis

```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 3. Verificar Estado

```bash
sudo systemctl status redis-server

# Probar conexión
redis-cli ping
# Debe responder: PONG
```

---

## Configuración de Supervisor

Ver archivo `supervisor-config.conf` en la carpeta `docs/config/`.

### 1. Crear Configuración para Workers

```bash
sudo vim /etc/supervisor/conf.d/manager-worker.conf
```

Pegar contenido del archivo `supervisor-config.conf` (ajustar rutas).

### 2. Recargar Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### 3. Verificar Estado de Workers

```bash
sudo supervisorctl status
```

Debe mostrar:
```
manager-worker:manager-worker_00   RUNNING   pid 12345, uptime 0:00:05
manager-worker:manager-worker_01   RUNNING   pid 12346, uptime 0:00:05
```

---

## Configuración de SSL con Certbot

### 1. Obtener Certificado SSL

```bash
sudo certbot --nginx -d app.hierrospacoreyes.es
```

Seguir las instrucciones:
- Ingresar email
- Aceptar términos
- Elegir si compartir email con EFF
- Certbot modificará automáticamente la configuración de Nginx

### 2. Verificar Renovación Automática

```bash
sudo certbot renew --dry-run
```

### 3. Configurar Auto-renovación (ya viene configurado)

```bash
sudo systemctl status certbot.timer
```

---

## Deployment de la Aplicación

### 1. Crear Directorio del Proyecto

```bash
sudo mkdir -p /var/www
cd /var/www
```

### 2. Clonar Repositorio

```bash
# Si es repositorio privado, configurar SSH key primero
sudo git clone https://github.com/eduMagro/hpr.git manager

# O si es otro repositorio
# sudo git clone TU_REPO_URL manager
```

### 3. Cambiar a la Rama Correcta

```bash
cd /var/www/manager
sudo git checkout 59-edu  # O la rama que uses en producción
```

### 4. Instalar Dependencias PHP

```bash
sudo composer install --optimize-autoloader --no-dev
```

### 5. Configurar Permisos

```bash
sudo chown -R www-data:www-data /var/www/manager
sudo chmod -R 755 /var/www/manager
sudo chmod -R 775 /var/www/manager/storage
sudo chmod -R 775 /var/www/manager/bootstrap/cache
```

### 6. Configurar Variables de Entorno

Ver archivo `.env.production` en la carpeta `docs/config/`.

```bash
sudo cp /var/www/manager/.env.example /var/www/manager/.env
sudo vim /var/www/manager/.env
```

Editar con tus valores de producción (ver `.env.production`).

### 7. Generar Application Key

```bash
cd /var/www/manager
sudo php artisan key:generate
```

### 8. Crear Symlink de Storage

```bash
sudo php artisan storage:link
```

### 9. Ejecutar Migraciones

```bash
# CUIDADO: Esto creará las tablas en la base de datos
sudo php artisan migrate --force
```

Si tienes seeders iniciales:

```bash
sudo php artisan db:seed --force
```

### 10. Compilar Assets Frontend

```bash
# Instalar dependencias
cd /var/www/manager
sudo npm install

# Compilar para producción
sudo npm run build
```

### 11. Optimizar Laravel

```bash
cd /var/www/manager

# Cachear configuración
sudo php artisan config:cache

# Cachear rutas
sudo php artisan route:cache

# Cachear vistas
sudo php artisan view:cache

# Optimizar autoloader
sudo composer dump-autoload --optimize
```

### 12. Ajustar Permisos Finales

```bash
sudo chown -R www-data:www-data /var/www/manager
```

---

## Configuración de Cron

### 1. Editar Crontab del Usuario www-data

```bash
sudo crontab -e -u www-data
```

### 2. Agregar Laravel Scheduler

```cron
* * * * * cd /var/www/manager && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Verificar que Cron Está Activo

```bash
sudo systemctl status cron
```

---

## Verificación Final

### 1. Verificar Servicios Activos

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo systemctl status supervisor
sudo systemctl status cron
```

Todos deben mostrar `active (running)`.

### 2. Verificar Workers de Supervisor

```bash
sudo supervisorctl status
```

Deben estar en estado `RUNNING`.

### 3. Verificar Logs

```bash
# Logs de Laravel
sudo tail -f /var/www/manager/storage/logs/laravel.log

# Logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Logs de PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Logs de Workers
sudo tail -f /var/www/manager/storage/logs/worker.log
```

### 4. Probar la Aplicación

Abrir navegador y visitar:
```
https://app.hierrospacoreyes.es
```

### 5. Verificar Funcionalidades Críticas

- [ ] Login funciona
- [ ] Subir archivos funciona
- [ ] Exportar Excel funciona
- [ ] Generar PDFs funciona
- [ ] Envío de emails funciona (revisar logs)
- [ ] Importar planillas funciona

---

## Troubleshooting

### Problema: Error 500 Internal Server Error

**Solución:**

```bash
# Ver logs de Laravel
sudo tail -100 /var/www/manager/storage/logs/laravel.log

# Ver logs de Nginx
sudo tail -100 /var/log/nginx/error.log

# Verificar permisos
sudo chown -R www-data:www-data /var/www/manager/storage
sudo chmod -R 775 /var/www/manager/storage
```

### Problema: Error 502 Bad Gateway

**Solución:**

```bash
# Verificar que PHP-FPM esté ejecutándose
sudo systemctl status php8.2-fpm

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm

# Verificar socket de PHP-FPM
ls -la /run/php/php8.2-fpm.sock
```

### Problema: Las colas no procesan trabajos

**Solución:**

```bash
# Verificar estado de workers
sudo supervisorctl status

# Reiniciar workers
sudo supervisorctl restart manager-worker:*

# Ver logs de workers
sudo tail -100 /var/www/manager/storage/logs/worker.log

# Ver tabla de jobs
mysql -u manager_user -p manager_production -e "SELECT * FROM jobs;"
```

### Problema: No se pueden subir archivos

**Solución:**

```bash
# Verificar permisos de storage
sudo chmod -R 775 /var/www/manager/storage

# Verificar configuración PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Verificar configuración Nginx
sudo grep client_max_body_size /etc/nginx/nginx.conf
```

### Problema: CSS/JS no cargan

**Solución:**

```bash
# Limpiar cachés
cd /var/www/manager
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan view:clear

# Recompilar assets
sudo npm run build

# Verificar que public/build existe
ls -la /var/www/manager/public/build
```

### Problema: Base de datos no conecta

**Solución:**

```bash
# Verificar MySQL está corriendo
sudo systemctl status mysql

# Probar conexión manualmente
mysql -u manager_user -p manager_production

# Verificar variables de .env
grep DB_ /var/www/manager/.env

# Limpiar config cache
sudo php artisan config:clear
```

### Problema: Emails no se envían

**Solución:**

```bash
# Verificar configuración de mail en .env
grep MAIL_ /var/www/manager/.env

# Verificar queue workers están corriendo
sudo supervisorctl status

# Ver trabajos fallidos
cd /var/www/manager
sudo php artisan queue:failed

# Probar envío de email de prueba
sudo php artisan tinker
# Dentro de tinker:
# Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

---

## Mantenimiento Regular

### Actualizar la Aplicación

```bash
cd /var/www/manager

# Pull cambios
sudo git pull origin 59-edu

# Actualizar dependencias
sudo composer install --optimize-autoloader --no-dev
sudo npm install
sudo npm run build

# Ejecutar migraciones
sudo php artisan migrate --force

# Limpiar cachés
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear

# Cachear nuevamente
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache

# Reiniciar workers
sudo supervisorctl restart manager-worker:*

# Reiniciar servicios
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

### Backups Recomendados

**Base de Datos (diario):**

```bash
# Crear script de backup
sudo vim /usr/local/bin/backup-db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mysql"
mkdir -p $BACKUP_DIR
mysqldump -u manager_user -pTU_PASSWORD manager_production | gzip > $BACKUP_DIR/manager_$DATE.sql.gz
# Eliminar backups más antiguos de 7 días
find $BACKUP_DIR -name "manager_*.sql.gz" -mtime +7 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-db.sh

# Agregar a cron (diario a las 2 AM)
sudo crontab -e
# Agregar:
# 0 2 * * * /usr/local/bin/backup-db.sh
```

**Archivos (semanal):**

```bash
# Backup de storage
sudo tar -czf /var/backups/storage_$(date +%Y%m%d).tar.gz /var/www/manager/storage
```

---

## Seguridad Adicional

### 1. Configurar Fail2Ban (SSH)

```bash
sudo apt install -y fail2ban

sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo vim /etc/fail2ban/jail.local
```

Buscar `[sshd]` y asegurar:

```ini
[sshd]
enabled = true
port = 22
maxretry = 3
bantime = 3600
```

Reiniciar:

```bash
sudo systemctl restart fail2ban
sudo systemctl enable fail2ban
```

### 2. Deshabilitar Login Root SSH

```bash
sudo vim /etc/ssh/sshd_config
```

Cambiar:

```
PermitRootLogin no
PasswordAuthentication no  # Solo si usas SSH keys
```

Reiniciar SSH:

```bash
sudo systemctl restart sshd
```

---

**¡Instalación Completa!**

Tu aplicación Manager HPR ahora está desplegada y funcionando en el VPS.
