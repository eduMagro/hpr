# Checklist de Deployment - Manager HPR

Este checklist te guiará paso a paso en el proceso de deployment del proyecto Manager HPR en un VPS.

---

## Pre-Deployment

### Preparación del Servidor

- [ ] VPS contratado con especificaciones mínimas (2 vCPU, 4GB RAM, 40GB SSD)
- [ ] Sistema operativo instalado (Ubuntu 22.04 LTS o 24.04 LTS)
- [ ] Acceso SSH configurado
- [ ] Zona horaria configurada: `Europe/Madrid`
- [ ] Usuario deployer creado (opcional pero recomendado)
- [ ] Firewall básico configurado (UFW)

### Preparación Local

- [ ] Código subido a repositorio Git
- [ ] Rama de producción creada y testeada
- [ ] Assets compilados localmente (verificar que `npm run build` funciona)
- [ ] Migraciones revisadas
- [ ] Credenciales de base de datos anotadas
- [ ] Dominio configurado apuntando a IP del VPS

---

## Instalación de Software

### Sistema Base

- [ ] Sistema actualizado: `apt update && apt upgrade -y`
- [ ] Repositorio PHP 8.2 agregado (ondrej/php)
- [ ] Git instalado
- [ ] Curl, wget, unzip instalados
- [ ] Vim o nano instalado

### PHP 8.2

- [ ] PHP 8.2 FPM instalado
- [ ] PHP 8.2 CLI instalado
- [ ] Todas las extensiones instaladas:
  - [ ] php8.2-mysql
  - [ ] php8.2-mbstring
  - [ ] php8.2-xml
  - [ ] php8.2-curl
  - [ ] php8.2-gd
  - [ ] php8.2-zip
  - [ ] php8.2-bcmath
  - [ ] php8.2-intl
  - [ ] php8.2-sqlite3
  - [ ] php8.2-opcache
  - [ ] php8.2-readline
  - [ ] php8.2-exif

### Composer

- [ ] Composer instalado globalmente
- [ ] Versión verificada: `composer --version`
- [ ] Funciona correctamente: `composer diagnose`

### Nginx

- [ ] Nginx instalado
- [ ] Servicio habilitado: `systemctl enable nginx`
- [ ] Configuración por defecto eliminada

### MySQL/MariaDB

- [ ] MySQL Server instalado
- [ ] mysql_secure_installation ejecutado
- [ ] Base de datos creada: `manager_production`
- [ ] Usuario creado: `manager_user`
- [ ] Permisos otorgados correctamente
- [ ] Conexión verificada

### Redis (Opcional pero Recomendado)

- [ ] Redis server instalado
- [ ] Configurado para solo localhost
- [ ] Password configurado (opcional)
- [ ] Servicio habilitado: `systemctl enable redis-server`
- [ ] Conexión verificada: `redis-cli ping`

### Node.js y NPM

- [ ] Node.js 18 LTS instalado
- [ ] NPM instalado
- [ ] Versiones verificadas: `node -v && npm -v`

### Supervisor

- [ ] Supervisor instalado
- [ ] Servicio habilitado: `systemctl enable supervisor`

### Certbot

- [ ] Certbot instalado
- [ ] Plugin de Nginx instalado

### Librerías Gráficas

- [ ] libgd instalado
- [ ] libpng-dev instalado
- [ ] libjpeg-dev instalado
- [ ] libfreetype6-dev instalado
- [ ] libfontconfig1 instalado
- [ ] zlib1g-dev instalado

---

## Configuración de Servicios

### PHP Configuration

- [ ] `/etc/php/8.2/fpm/php.ini` editado con configuración de producción
- [ ] `/etc/php/8.2/cli/php.ini` editado
- [ ] Valores clave configurados:
  - [ ] memory_limit = 512M
  - [ ] upload_max_filesize = 50M
  - [ ] post_max_size = 50M
  - [ ] max_execution_time = 300
  - [ ] opcache habilitado
  - [ ] display_errors = Off
  - [ ] log_errors = On
- [ ] Directorio de logs creado: `/var/log/php/`
- [ ] PHP-FPM pool configurado
- [ ] PHP-FPM reiniciado: `systemctl restart php8.2-fpm`

### Nginx Configuration

- [ ] Archivo de configuración creado: `/etc/nginx/sites-available/manager`
- [ ] Dominio configurado correctamente
- [ ] Rutas ajustadas: `/var/www/manager/public`
- [ ] client_max_body_size configurado (50M)
- [ ] Configuración enlazada: `sites-enabled/manager`
- [ ] Configuración verificada: `nginx -t`
- [ ] Nginx reiniciado: `systemctl restart nginx`

### MySQL Configuration

- [ ] Parámetros de rendimiento configurados (opcional)
- [ ] MySQL reiniciado si se cambió configuración

### Redis Configuration (si se usa)

- [ ] Bind configurado a localhost
- [ ] Password configurado
- [ ] Persistencia configurada
- [ ] maxmemory configurado
- [ ] Redis reiniciado

### Supervisor Configuration

- [ ] Archivo creado: `/etc/supervisor/conf.d/manager-worker.conf`
- [ ] Rutas ajustadas
- [ ] Usuario configurado: www-data
- [ ] Número de workers configurado (2 recomendado)
- [ ] Supervisor recargado: `supervisorctl reread && supervisorctl update`
- [ ] Workers iniciados: `supervisorctl status`

---

## Deployment de la Aplicación

### Clonar Repositorio

- [ ] Directorio `/var/www` creado
- [ ] Repositorio clonado: `git clone REPO_URL manager`
- [ ] Rama correcta checkout: `git checkout RAMA_PRODUCCION`
- [ ] Permisos de propietario: `chown -R www-data:www-data /var/www/manager`

### Dependencias PHP

- [ ] Composer install ejecutado: `composer install --optimize-autoloader --no-dev`
- [ ] Sin errores en instalación

### Dependencias Frontend

- [ ] NPM install ejecutado: `npm install`
- [ ] Assets compilados: `npm run build`
- [ ] Directorio `public/build` creado y poblado

### Configuración de la Aplicación

- [ ] Archivo `.env` creado (copiado de `.env.production`)
- [ ] Variables de entorno configuradas:
  - [ ] APP_NAME
  - [ ] APP_ENV=production
  - [ ] APP_DEBUG=false
  - [ ] APP_URL (con https)
  - [ ] DB_DATABASE
  - [ ] DB_USERNAME
  - [ ] DB_PASSWORD
  - [ ] MAIL_* configurado
  - [ ] CACHE_STORE configurado
  - [ ] QUEUE_CONNECTION configurado
- [ ] APP_KEY generado: `php artisan key:generate`
- [ ] Permisos de .env: `chmod 600 .env`

### Permisos

- [ ] Storage: `chmod -R 775 storage`
- [ ] Bootstrap/cache: `chmod -R 775 bootstrap/cache`
- [ ] Propietario verificado: `chown -R www-data:www-data`

### Storage Symlink

- [ ] Symlink creado: `php artisan storage:link`
- [ ] Verificado: `ls -la public/storage`

### Base de Datos

- [ ] Migraciones ejecutadas: `php artisan migrate --force`
- [ ] Seeders ejecutados si aplica: `php artisan db:seed --force`
- [ ] Tablas verificadas en base de datos

### Optimización

- [ ] Config cacheado: `php artisan config:cache`
- [ ] Routes cacheados: `php artisan route:cache`
- [ ] Views cacheadas: `php artisan view:cache`
- [ ] Autoloader optimizado: `composer dump-autoload --optimize`

---

## SSL/TLS Configuration

### Certbot

- [ ] Certificado SSL obtenido: `certbot --nginx -d DOMINIO`
- [ ] Email configurado
- [ ] Términos aceptados
- [ ] Nginx configurado automáticamente por Certbot
- [ ] HTTPS verificado en navegador
- [ ] Auto-renovación verificada: `certbot renew --dry-run`

---

## Tareas Programadas

### Cron

- [ ] Crontab de www-data editado: `crontab -e -u www-data`
- [ ] Laravel scheduler agregado:
  ```
  * * * * * cd /var/www/manager && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Cron service verificado: `systemctl status cron`

---

## Verificación y Testing

### Servicios

- [ ] Nginx running: `systemctl status nginx`
- [ ] PHP-FPM running: `systemctl status php8.2-fpm`
- [ ] MySQL running: `systemctl status mysql`
- [ ] Redis running (si se usa): `systemctl status redis-server`
- [ ] Supervisor running: `systemctl status supervisor`
- [ ] Cron running: `systemctl status cron`

### Workers

- [ ] Workers de colas running: `supervisorctl status`
- [ ] Todos en estado RUNNING
- [ ] Logs sin errores: `tail -f storage/logs/worker.log`

### Aplicación

- [ ] Sitio web accesible vía HTTPS
- [ ] Redireccionamiento HTTP → HTTPS funciona
- [ ] Login funciona
- [ ] Subir archivos funciona
- [ ] Exportar Excel funciona
- [ ] Generar PDF funciona
- [ ] Importar planillas funciona
- [ ] Envío de emails funciona (revisar logs o tabla jobs)

### Logs

- [ ] Laravel logs revisados: `tail -100 storage/logs/laravel.log`
- [ ] Nginx error log revisado: `tail -100 /var/log/nginx/manager-error.log`
- [ ] PHP-FPM log revisado: `tail -100 /var/log/php8.2-fpm.log`
- [ ] Sin errores críticos

### Performance

- [ ] Página carga en tiempo razonable (< 2 segundos)
- [ ] Assets (CSS/JS) se cargan correctamente
- [ ] Imágenes se muestran correctamente
- [ ] QR codes se generan correctamente

---

## Seguridad Post-Deployment

### Firewall

- [ ] UFW habilitado
- [ ] Puerto 22 (SSH) abierto
- [ ] Puerto 80 (HTTP) abierto
- [ ] Puerto 443 (HTTPS) abierto
- [ ] MySQL (3306) NO expuesto externamente
- [ ] Redis (6379) NO expuesto externamente (si se usa)

### SSH

- [ ] Fail2ban instalado y configurado
- [ ] SSH con claves en lugar de contraseñas (recomendado)
- [ ] Root login deshabilitado: `PermitRootLogin no`
- [ ] Password authentication deshabilitado (si usas keys): `PasswordAuthentication no`

### Aplicación

- [ ] APP_DEBUG=false verificado
- [ ] APP_ENV=production verificado
- [ ] .env no accesible vía web
- [ ] Directorio .git no accesible vía web
- [ ] vendor/ no accesible vía web
- [ ] storage/ no accesible vía web

---

## Backups

### Configuración de Backups

- [ ] Script de backup creado: `/usr/local/bin/backup.sh`
- [ ] Script ejecutable: `chmod +x /usr/local/bin/backup.sh`
- [ ] Directorio de backups creado: `/var/backups/manager`
- [ ] Backup manual ejecutado para verificar
- [ ] Cron job de backup configurado (diario 2 AM recomendado)
- [ ] Backup externo configurado (opcional pero recomendado)

### Verificar Backups

- [ ] Backup de base de datos funciona
- [ ] Backup de storage funciona
- [ ] Backup de .env funciona
- [ ] Rotación de backups configurada
- [ ] Espacio en disco suficiente para backups

---

## Monitoreo (Opcional pero Recomendado)

- [ ] Monitoreo de uptime configurado
- [ ] Alertas de disco lleno configuradas
- [ ] Alertas de memoria configuradas
- [ ] Logs centralizados (opcional)
- [ ] Error tracking (Sentry, Bugsnag, etc.) configurado

---

## Documentación

- [ ] Credenciales guardadas en gestor de contraseñas seguro
- [ ] IP del servidor documentada
- [ ] Accesos SSH documentados
- [ ] Credenciales de base de datos documentadas
- [ ] Contactos de soporte documentados
- [ ] Procedimiento de deployment documentado
- [ ] Procedimiento de rollback documentado

---

## Post-Deployment

### Comunicación

- [ ] Cliente notificado del deployment
- [ ] Equipo notificado del deployment
- [ ] Usuarios de prueba creados si aplica

### Monitoreo Inicial

- [ ] Monitorear logs durante las primeras 24 horas
- [ ] Verificar que crons se ejecutan correctamente
- [ ] Verificar que emails se envían correctamente
- [ ] Verificar que workers procesan trabajos

### Contingencia

- [ ] Plan de rollback documentado
- [ ] Backup pre-deployment guardado
- [ ] Contacto de emergencia disponible

---

## Checklist de Actualización (Deployments Futuros)

Para deployments posteriores, usa este checklist simplificado:

- [ ] Habilitar modo mantenimiento: `php artisan down`
- [ ] Pull cambios: `git pull origin RAMA`
- [ ] Actualizar dependencias: `composer install --no-dev`
- [ ] Compilar assets: `npm run build`
- [ ] Ejecutar migraciones: `php artisan migrate --force`
- [ ] Limpiar cachés: `php artisan cache:clear && php artisan config:clear`
- [ ] Cachear config: `php artisan config:cache`
- [ ] Reiniciar workers: `supervisorctl restart manager-worker:*`
- [ ] Recargar PHP-FPM: `systemctl reload php8.2-fpm`
- [ ] Deshabilitar modo mantenimiento: `php artisan up`
- [ ] Verificar funcionamiento
- [ ] Revisar logs

---

## Troubleshooting Rápido

Si algo falla, verifica en este orden:

1. **Error 500**: Revisar `storage/logs/laravel.log`
2. **Error 502**: Verificar que PHP-FPM esté corriendo
3. **Colas no procesan**: Verificar supervisorctl status
4. **No se pueden subir archivos**: Verificar permisos de storage
5. **CSS/JS no cargan**: Verificar que public/build existe
6. **Base de datos no conecta**: Verificar credenciales en .env

---

**Estado del Deployment:**

- Iniciado: ___/___/_____ ___:___
- Completado: ___/___/_____ ___:___
- Desplegado por: _________________
- Versión/Commit: _________________

---

**Firma de Aprobación:**

Cliente: _________________ Fecha: ___/___/_____

Desarrollador: _________________ Fecha: ___/___/_____
