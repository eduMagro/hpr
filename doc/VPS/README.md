# Documentación de Deployment - Manager HPR

Documentación completa para el deployment del proyecto Manager HPR en un VPS dedicado.

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Estructura de la Documentación](#estructura-de-la-documentación)
3. [Inicio Rápido](#inicio-rápido)
4. [Documentos Principales](#documentos-principales)
5. [Archivos de Configuración](#archivos-de-configuración)
6. [Scripts de Automatización](#scripts-de-automatización)
7. [Soporte](#soporte)

---

## Introducción

Este directorio contiene toda la documentación necesaria para desplegar y mantener el proyecto **Manager HPR** (Hierros Paco Reyes) en un servidor VPS dedicado.

El proyecto es una aplicación Laravel 11 compleja con las siguientes características:
- Sistema de gestión empresarial (ERP)
- Procesamiento de archivos pesados (Excel, PDF)
- Generación de documentos y códigos QR
- Sistema de colas para procesamiento asíncrono
- Componentes reactivos con Livewire
- Frontend moderno con Vite, TailwindCSS y Alpine.js/Vue.js

---

## Estructura de la Documentación

```
docs/
├── README.md                      # Este archivo
├── VPS-REQUIREMENTS.md            # Lista completa de requisitos del VPS
├── VPS-INSTALLATION-GUIDE.md      # Guía paso a paso de instalación
├── DEPLOYMENT-CHECKLIST.md        # Checklist detallado de deployment
├── config/                        # Archivos de configuración
│   ├── nginx-config.conf          # Configuración de Nginx
│   ├── supervisor-config.conf     # Configuración de Supervisor
│   ├── php.ini                    # Configuración de PHP
│   └── .env.production            # Variables de entorno de producción
└── scripts/                       # Scripts de automatización
    ├── deploy.sh                  # Script de deployment
    ├── first-deploy.sh            # Script de primer deployment
    └── backup.sh                  # Script de backups
```

---

## Inicio Rápido

### Para el Primer Deployment

1. **Lee los requisitos:**
   - Abre [VPS-REQUIREMENTS.md](VPS-REQUIREMENTS.md)
   - Asegúrate de que tu VPS cumple con todos los requisitos

2. **Sigue la guía de instalación:**
   - Abre [VPS-INSTALLATION-GUIDE.md](VPS-INSTALLATION-GUIDE.md)
   - Sigue cada paso cuidadosamente

3. **Usa el checklist:**
   - Abre [DEPLOYMENT-CHECKLIST.md](DEPLOYMENT-CHECKLIST.md)
   - Marca cada ítem a medida que lo completas

4. **Automatiza con scripts:**
   - Usa `scripts/first-deploy.sh` para el primer deployment
   - Configura los servicios con los archivos de `config/`

### Para Deployments Posteriores

1. Usa el script `scripts/deploy.sh`
2. Sigue el checklist simplificado en [DEPLOYMENT-CHECKLIST.md](DEPLOYMENT-CHECKLIST.md)

---

## Documentos Principales

### 1. VPS-REQUIREMENTS.md

**Propósito:** Lista exhaustiva de todos los requisitos de software y hardware.

**Contenido:**
- Sistema operativo recomendado
- Software y servicios necesarios
- Extensiones PHP requeridas
- Requisitos de hardware
- Puertos y firewall
- Conectividad externa

**Cuándo usar:** Antes de contratar el VPS o al inicio del proyecto.

---

### 2. VPS-INSTALLATION-GUIDE.md

**Propósito:** Guía paso a paso para instalar todo el software necesario.

**Contenido:**
- Preparación inicial del servidor
- Instalación de software base
- Configuración de cada servicio
- Deployment de la aplicación
- Configuración SSL
- Troubleshooting

**Cuándo usar:** Durante la configuración inicial del VPS.

---

### 3. DEPLOYMENT-CHECKLIST.md

**Propósito:** Checklist completo para asegurar que no se olvida ningún paso.

**Contenido:**
- Checklist de pre-deployment
- Checklist de instalación
- Checklist de configuración
- Checklist de verificación
- Checklist de seguridad
- Checklist de backups
- Checklist de deployments futuros

**Cuándo usar:** Durante todo el proceso de deployment, marcando cada ítem.

---

## Archivos de Configuración

### config/nginx-config.conf

**Descripción:** Configuración completa de Nginx para Laravel.

**Características:**
- Redirección HTTP → HTTPS
- Headers de seguridad
- PHP-FPM configurado
- Cache de archivos estáticos
- Gzip compression
- Límites de upload (50MB)

**Ubicación en el servidor:** `/etc/nginx/sites-available/manager`

**Cómo usar:**
```bash
sudo cp config/nginx-config.conf /etc/nginx/sites-available/manager
sudo ln -s /etc/nginx/sites-available/manager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

### config/supervisor-config.conf

**Descripción:** Configuración de Supervisor para workers de colas.

**Características:**
- 2 workers por defecto
- Auto-restart en fallos
- Logs configurados
- Timeouts apropiados

**Ubicación en el servidor:** `/etc/supervisor/conf.d/manager-worker.conf`

**Cómo usar:**
```bash
sudo cp config/supervisor-config.conf /etc/supervisor/conf.d/manager-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

---

### config/php.ini

**Descripción:** Configuración de PHP optimizada para la aplicación.

**Características:**
- Memory limit: 512M
- Upload size: 50M
- OPcache habilitado
- Timeouts apropiados (300s)

**Ubicación en el servidor:**
- `/etc/php/8.2/fpm/php.ini`
- `/etc/php/8.2/cli/php.ini`

**Cómo usar:**
```bash
# Backup del original
sudo cp /etc/php/8.2/fpm/php.ini /etc/php/8.2/fpm/php.ini.backup

# Aplicar configuración (editar manualmente o copiar secciones)
sudo vim /etc/php/8.2/fpm/php.ini

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

### config/.env.production

**Descripción:** Template de variables de entorno para producción.

**Características:**
- Todas las variables necesarias
- Comentarios explicativos
- Opciones múltiples (SMTP, Redis, etc.)
- Valores seguros por defecto

**Ubicación en el servidor:** `/var/www/manager/.env`

**Cómo usar:**
```bash
cp config/.env.production /var/www/manager/.env
vim /var/www/manager/.env  # Editar con valores reales
chmod 600 /var/www/manager/.env
php artisan key:generate
```

---

## Scripts de Automatización

### scripts/first-deploy.sh

**Propósito:** Automatizar el primer deployment completo.

**Qué hace:**
1. Clona el repositorio
2. Instala dependencias PHP y JS
3. Compila assets
4. Configura .env
5. Ejecuta migraciones
6. Optimiza la aplicación

**Cómo usar:**
```bash
# Hacer ejecutable
chmod +x scripts/first-deploy.sh

# Editar variables de configuración
vim scripts/first-deploy.sh

# Ejecutar
sudo ./scripts/first-deploy.sh
```

**IMPORTANTE:** Edita las variables `REPO_URL`, `BRANCH`, `DB_NAME`, etc. antes de ejecutar.

---

### scripts/deploy.sh

**Propósito:** Automatizar deployments posteriores (actualizaciones).

**Qué hace:**
1. Activa modo mantenimiento
2. Pull de cambios de Git
3. Actualiza dependencias
4. Compila assets
5. Ejecuta migraciones
6. Limpia y cachea configuración
7. Reinicia workers
8. Desactiva modo mantenimiento

**Cómo usar:**
```bash
# Hacer ejecutable
chmod +x scripts/deploy.sh

# Ejecutar
sudo ./scripts/deploy.sh
```

**Cuándo usar:** Cada vez que necesites actualizar el código en producción.

---

### scripts/backup.sh

**Propósito:** Crear backups automáticos de base de datos y archivos.

**Qué hace:**
1. Backup de base de datos (comprimido)
2. Backup de directorio storage
3. Backup de archivo .env
4. Backups semanales (domingos)
5. Backups mensuales (día 1)
6. Limpieza de backups antiguos

**Cómo usar:**
```bash
# Hacer ejecutable
chmod +x scripts/backup.sh

# Editar credenciales de BD
vim scripts/backup.sh

# Ejecutar manualmente
sudo ./scripts/backup.sh

# O configurar en cron (diario a las 2 AM)
sudo crontab -e
# Agregar:
# 0 2 * * * /var/www/manager/docs/scripts/backup.sh
```

**Retención por defecto:**
- Backups diarios: 7 días
- Backups semanales: 30 días
- Backups mensuales: 90 días

---

## Workflow Recomendado

### Primer Deployment

1. **Preparación** (1-2 días)
   - Leer [VPS-REQUIREMENTS.md](VPS-REQUIREMENTS.md)
   - Contratar VPS con especificaciones mínimas
   - Configurar dominio

2. **Instalación** (2-4 horas)
   - Seguir [VPS-INSTALLATION-GUIDE.md](VPS-INSTALLATION-GUIDE.md)
   - Usar [DEPLOYMENT-CHECKLIST.md](DEPLOYMENT-CHECKLIST.md)
   - Ejecutar `scripts/first-deploy.sh`

3. **Configuración** (1-2 horas)
   - Aplicar configuraciones de `config/`
   - Configurar SSL
   - Configurar backups

4. **Verificación** (30 minutos)
   - Probar todas las funcionalidades
   - Revisar logs
   - Verificar servicios

### Deployments Posteriores

1. **Pre-deployment**
   - Hacer backup manual
   - Notificar a usuarios (si aplica)

2. **Deployment**
   - Ejecutar `scripts/deploy.sh`
   - O seguir proceso manual del checklist

3. **Post-deployment**
   - Verificar funcionalidad
   - Monitorear logs
   - Notificar completitud

---

## Especificaciones del Proyecto

### Stack Tecnológico

**Backend:**
- Laravel 11.45.1
- PHP 8.2
- MySQL 8.0 / MariaDB 10.6
- Redis (opcional)

**Frontend:**
- Vite 5.0
- TailwindCSS 3.4
- Alpine.js 3.14
- Vue.js 3.5 (componentes específicos)
- Livewire 3.6

**Servicios:**
- Nginx (webserver)
- Supervisor (queue workers)
- Certbot (SSL)
- Cron (scheduled tasks)

### Características Especiales

- **Colas activas:** Requiere Supervisor obligatoriamente
- **Importación Excel:** Archivos grandes, necesita memoria (512M)
- **Generación PDF:** DomPDF con librerías gráficas
- **Códigos QR:** SimpleSoftwareIO/SimpleQRCode
- **Procesamiento de imágenes:** Intervention/Image con GD
- **Tareas programadas:** Festivos, turnos, vacaciones

---

## Recursos Mínimos del VPS

```
CPU:    2-4 vCPUs
RAM:    4-8 GB (mínimo 4GB por importaciones Excel)
Disco:  40-80 GB SSD
Ancho:  2-5 TB/mes
```

---

## Puertos Necesarios

```
22    → SSH
80    → HTTP (redirect a HTTPS)
443   → HTTPS
3306  → MySQL (SOLO localhost)
6379  → Redis (SOLO localhost, si se usa)
```

---

## Seguridad

### Checklist Básico de Seguridad

- [ ] Firewall configurado (UFW)
- [ ] SSH con claves, no contraseñas
- [ ] Root login SSH deshabilitado
- [ ] Fail2ban instalado
- [ ] SSL/TLS configurado (Let's Encrypt)
- [ ] APP_DEBUG=false en producción
- [ ] Archivos sensibles no accesibles vía web
- [ ] MySQL no expuesto externamente
- [ ] Redis no expuesto externamente
- [ ] Backups automáticos configurados

---

## Mantenimiento Regular

### Diario
- Revisar logs de aplicación
- Verificar que workers estén corriendo
- Verificar espacio en disco

### Semanal
- Revisar backups
- Revisar logs de sistema
- Actualizar paquetes de seguridad

### Mensual
- Revisar rendimiento
- Limpiar logs antiguos
- Revisar alertas de monitoreo

---

## Troubleshooting Rápido

| Problema | Solución Rápida |
|----------|----------------|
| Error 500 | Ver `storage/logs/laravel.log` |
| Error 502 | Verificar `systemctl status php8.2-fpm` |
| Colas no procesan | Verificar `supervisorctl status` |
| No se suben archivos | Verificar permisos en `storage/` |
| CSS/JS no cargan | Ejecutar `npm run build` |
| BD no conecta | Verificar credenciales en `.env` |

Para troubleshooting detallado, consultar [VPS-INSTALLATION-GUIDE.md](VPS-INSTALLATION-GUIDE.md#troubleshooting).

---

## Soporte y Contacto

### Documentación Oficial
- [Laravel 11 Docs](https://laravel.com/docs/11.x)
- [Laravel Deployment](https://laravel.com/docs/11.x/deployment)
- [Nginx Docs](https://nginx.org/en/docs/)
- [Supervisor Docs](http://supervisord.org/)

### Repositorio
- GitHub: https://github.com/eduMagro/hpr
- Rama de producción: `59-edu` (ajustar según tu configuración)

### Notas Importantes

1. **NUNCA** expongas credenciales en el código
2. **SIEMPRE** haz backup antes de deployments
3. **VERIFICA** logs después de cada deployment
4. **MANTÉN** documentación actualizada
5. **PRUEBA** en staging antes de producción (si es posible)

---

## Historial de Cambios

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| 1.0.0   | 2025-11-12 | Documentación inicial completa |

---

## Licencia

Este proyecto es propiedad de Hierros Paco Reyes. Toda la documentación es confidencial.

---

**Última actualización:** 2025-11-12
**Autor:** Análisis exhaustivo del codebase Manager HPR
**Versión Laravel:** 11.45.1
**Versión PHP:** 8.2+
