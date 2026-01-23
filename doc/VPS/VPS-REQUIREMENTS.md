# Requisitos del VPS - Proyecto Manager HPR

Este documento detalla todos los requisitos de software, hardware y configuración necesarios para desplegar el proyecto Manager HPR en un VPS dedicado.

---

## Tabla de Contenidos

1. [Sistema Operativo](#sistema-operativo)
2. [Software y Servicios Requeridos](#software-y-servicios-requeridos)
3. [Requisitos de Hardware](#requisitos-de-hardware)
4. [Puertos y Firewall](#puertos-y-firewall)
5. [Permisos y Estructura](#permisos-y-estructura)
6. [Conectividad Externa](#conectividad-externa)
7. [Características del Proyecto](#características-del-proyecto)

---

## Sistema Operativo

### Recomendado
- **Ubuntu 22.04 LTS** (Jammy Jellyfish)
- **Ubuntu 24.04 LTS** (Noble Numbat)

### Alternativa
- **Debian 12** (Bookworm)

---

## Software y Servicios Requeridos

### 1. PHP 8.2+

**Versión mínima:** PHP 8.2 (requerido por Laravel 11)

**Extensiones obligatorias:**
```bash
php8.2-fpm          # FastCGI Process Manager
php8.2-cli          # Command Line Interface
php8.2-common       # Common files
php8.2-mysql        # MySQL/MariaDB driver
php8.2-mbstring     # Multibyte string support
php8.2-xml          # XML support
php8.2-curl         # cURL support
php8.2-gd           # GD library (manipulación de imágenes)
php8.2-zip          # ZIP archive support (Excel, QR)
php8.2-bcmath       # BC Math (cálculos de precisión)
php8.2-intl         # Internacionalización
php8.2-sqlite3      # SQLite support
php8.2-opcache      # Opcode cache (optimización)
php8.2-readline     # Readline support
php8.2-exif         # EXIF support (metadatos de imágenes)
```

**Configuración php.ini recomendada:**
```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
file_uploads = On
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
```

---

### 2. Servidor Web

**Recomendado:** Nginx 1.18+

**Alternativa:** Apache 2.4+

---

### 3. Base de Datos

**Opciones:**
- **MySQL 8.0+** (recomendado)
- **MariaDB 10.6+**

**Configuración:**
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Usuario dedicado con permisos completos sobre BD de producción

---

### 4. Cache y Sesiones

**Recomendado (pero opcional):**
- **Redis 6.0+**

**Beneficios:**
- Cache significativamente más rápido que database
- Sesiones más eficientes
- Colas más performantes

**Nota:** Actualmente el proyecto usa `database` para cache, sesiones y colas. Redis mejorará el rendimiento.

---

### 5. Gestor de Procesos

**OBLIGATORIO:**
- **Supervisor**

**Propósito:**
- Mantener workers de colas ejecutándose permanentemente
- Auto-restart en caso de fallo
- Gestión de logs

---

### 6. Node.js y npm

**Versión:** Node.js 18+ LTS

**Propósito:**
- Compilar assets frontend (Vite, TailwindCSS)
- Gestión de dependencias JavaScript

**Dependencias del proyecto:**
- Vite 5.0
- TailwindCSS 3.4.13
- Alpine.js 3.14.9
- Vue.js 3.5.13
- Autoprefixer, PostCSS

---

### 7. Composer

**Versión:** Última versión estable (2.7+)

**Propósito:**
- Gestión de dependencias PHP
- Autoloading optimizado

---

### 8. Tareas Programadas

**OBLIGATORIO:**
- **Cron** (incluido en sistemas Linux)

**Tareas programadas del proyecto:**
- Sincronización de festivos (anual, 1 enero 01:10)
- Generación de turnos (anual, 1 enero 00:00)
- Reset de vacaciones (anual, 1 enero 00:00)

---

### 9. SSL/TLS

**Recomendado:**
- **Certbot** con Let's Encrypt

**Propósito:**
- Certificados SSL gratuitos y automáticos
- Renovación automática

---

### 10. Herramientas del Sistema

```bash
git           # Control de versiones
curl          # Transferencia de datos
wget          # Descarga de archivos
unzip         # Descompresión
vim / nano    # Editores de texto
```

---

### 11. Librerías Gráficas

**Para generación de PDFs y manipulación de imágenes:**

```bash
libgd               # GD library
libpng-dev          # PNG support
libjpeg-dev         # JPEG support
libfreetype6-dev    # Font rendering
libfontconfig1      # Font configuration (DomPDF)
zlib1g-dev          # Compression
```

---

### 12. Correo Electrónico

**Opciones:**

**A) Servidor SMTP propio:**
- Postfix o Exim configurado
- Puerto 587 (TLS) o 465 (SSL)

**B) Relay SMTP externo (recomendado):**
- SendGrid
- Mailgun
- AWS SES
- Postmark
- Resend

**Configuración necesaria:**
- Host SMTP
- Puerto (587 con TLS recomendado)
- Usuario y contraseña
- Email FROM configurado

---

## Requisitos de Hardware

### Especificaciones Mínimas

```
CPU:         2 vCPUs
RAM:         4 GB
Disco:       40 GB SSD
Ancho Banda: 2 TB/mes
```

### Especificaciones Recomendadas

```
CPU:         4 vCPUs
RAM:         8 GB
Disco:       80 GB SSD
Ancho Banda: 5 TB/mes
```

### Justificación

**RAM (4-8 GB):**
- Importaciones de planillas Excel pueden consumir mucha memoria
- Procesamiento simultáneo de PDFs
- Cache de aplicación y base de datos
- Workers de colas

**CPU (2-4 vCPUs):**
- Workers de colas procesando emails
- Generación de PDFs
- Operaciones concurrentes
- Compilación de assets

**Disco (40-80 GB SSD):**
- Base de datos
- Logs de aplicación y sistema
- Archivos temporales (Excel, PDFs)
- PDFs de trazabilidad
- Archivos de cortes
- Exportaciones
- Backups locales

---

## Puertos y Firewall

### Puertos a Abrir

```
22    → SSH (administración remota)
80    → HTTP (redirigir a HTTPS)
443   → HTTPS (aplicación web)
```

### Puertos SOLO Localhost (NO exponer)

```
3306  → MySQL/MariaDB
6379  → Redis (si se usa)
```

### Puertos de Salida Necesarios

```
80/443  → HTTP/HTTPS (API externa de festivos, Composer)
587     → SMTP TLS (envío de emails)
465     → SMTP SSL (alternativa)
```

---

## Permisos y Estructura

### Propietario de Archivos

```bash
Usuario:  www-data (o usuario del webserver)
Grupo:    www-data
```

### Permisos de Directorios

```bash
# Directorio principal
/var/www/manager                → 755

# Directorios de escritura
/var/www/manager/storage        → 775 (recursivo)
/var/www/manager/bootstrap/cache → 775

# Symlink obligatorio
/var/www/manager/public/storage → symlink a /var/www/manager/storage/app/public
```

### Comando para Configurar Permisos

```bash
sudo chown -R www-data:www-data /var/www/manager
sudo chmod -R 755 /var/www/manager
sudo chmod -R 775 /var/www/manager/storage
sudo chmod -R 775 /var/www/manager/bootstrap/cache
```

---

## Conectividad Externa

### APIs Externas Utilizadas

**1. API de Festivos Españoles**
- URL: `https://date.nager.at/api/v3/PublicHolidays/{año}/ES`
- Propósito: Sincronización automática de festivos nacionales y autonómicos
- Frecuencia: Anual (1 de enero)
- Requiere: Conectividad saliente HTTPS

### Servicios Externos Configurables (opcionales)

- AWS SES (email)
- Postmark (email)
- Slack (notificaciones)
- Resend (email)

---

## Características del Proyecto

### Funcionalidades Principales

1. **Sistema de Colas Activo**
   - Envío asíncrono de emails
   - Procesamiento en background
   - Requiere workers con Supervisor

2. **Importación de Archivos Pesados**
   - Excel (planillas, inventario)
   - PDF (albaranes, trazabilidad)
   - Procesamiento con memoria alta

3. **Generación de Documentos**
   - PDFs con DomPDF
   - Códigos QR
   - Exportaciones Excel

4. **Manipulación de Imágenes**
   - Redimensionado de avatares
   - Optimización automática
   - Intervention/Image con GD

5. **Sistema de Autenticación y Permisos**
   - Laravel Breeze
   - Control de acceso granular
   - Roles: oficina, operario, transportista
   - Sesiones en base de datos

6. **Componentes Reactivos**
   - Livewire 3.6
   - Alpine.js
   - Vue.js (componentes específicos)

7. **Tareas Programadas**
   - Sincronización de festivos
   - Generación automática de turnos
   - Reset anual de vacaciones

8. **Módulos del Sistema**
   - Gestión de empresas
   - Inventario
   - Producción y planificación
   - Pedidos y albaranes
   - Control de nóminas
   - Transporte
   - Usuarios y permisos
   - Almacén con QR

---

## Dependencias PHP Principales

Según `composer.json`:

```json
{
  "php": "^8.2",
  "laravel/framework": "^11.31",
  "livewire/livewire": "^3.6",
  "maatwebsite/excel": "^3.1",
  "barryvdh/laravel-dompdf": "^3.1",
  "intervention/image": "^3.11",
  "simplesoftwareio/simple-qrcode": "^4.2",
  "spatie/laravel-csp": "^2.10",
  "phpmailer/phpmailer": "^6.10",
  "smalot/pdfparser": "^2.12"
}
```

---

## Servicios que Deben Estar Activos

```bash
# Verificar servicios activos
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server  # Si se usa
sudo systemctl status supervisor
sudo systemctl status cron
```

---

## Notas Adicionales

### Seguridad

- Nunca exponer MySQL/Redis a internet
- Usar firewall (ufw o iptables)
- Certificado SSL obligatorio para producción
- Configurar fail2ban para SSH
- Deshabilitar autenticación root SSH
- Usar claves SSH en lugar de contraseñas

### Backups

Configurar backups automáticos de:
- Base de datos (mysqldump diario)
- Directorio storage/ (archivos)
- Archivo .env (configuración)

### Monitoreo

Considerar:
- Logs centralizados
- Monitoreo de uptime
- Alertas de disco lleno
- Monitoreo de workers

---

## Referencias

- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Laravel Deployment](https://laravel.com/docs/11.x/deployment)
- [Nginx Laravel Configuration](https://laravel.com/docs/11.x/deployment#nginx)
- [Supervisor Configuration](https://laravel.com/docs/11.x/queues#supervisor-configuration)

---

**Última actualización:** 2025-11-12
**Versión del proyecto:** Laravel 11.45.1
**Autor:** Análisis exhaustivo del codebase
