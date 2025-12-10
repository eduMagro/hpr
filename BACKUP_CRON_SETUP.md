# Configuración de Backup Automático - Plesk

## Problema Original

Se necesita configurar backups automáticos de la base de datos `app_hpr` cada 30 minutos en hosting compartido Plesk.

### Comando original que fallaba:
```bash
mysqldump -u eduMagro -pg1f*llkPPgDu8mr70 app_hpr > /tmp/app_hpr_$(/bin/date +\%Y-\%m-\%d_\%H).sql
```

### Errores encontrados:
- `/bin/date: No such file or directory`
- `mysqldump: command not found`
- `/usr/bin/date: No such file or directory`
- `/usr/bin/mysqldump: No such file or directory`

## Diagnóstico

El hosting tiene un entorno muy restringido. Los comandos disponibles en `/bin/` y `/usr/bin/` son solo:
- bash, cat, chmod, cp, curl, du, grep, groups, gunzip, gzip, head, id, less, ln, ls, mkdir, more, mv, pwd, rm, rmdir, scp, sh, tail, tar, touch, unzip, vi, wget, zip

**NO están disponibles:** `date`, `mysqldump`

## Solución Propuesta

Crear un comando Artisan de Laravel para hacer los backups y programarlo en Plesk.

## Pasos Pendientes

### 1. Ejecutar en tarea programada de Plesk:
```bash
ls /opt/plesk/php/
```
Esto mostrará las versiones de PHP disponibles (ej: 7.4, 8.0, 8.1, 8.2)

### 2. Información necesaria:
- [ ] Ruta completa del proyecto en el servidor (donde está el archivo `artisan`)
- [ ] Versión de PHP disponible en Plesk
- [ ] Dónde guardar los backups (ej: `/var/www/vhosts/dominio/backups/`)

### 3. Crear comando Artisan
Una vez tengamos la info anterior, crear:
- Archivo: `app/Console/Commands/DatabaseBackup.php`
- Registro en: `app/Console/Kernel.php` (si es necesario)

### 4. Comando final para Plesk
Será algo como:
```bash
/opt/plesk/php/8.X/bin/php /ruta/al/proyecto/artisan db:backup
```
Configurar para ejecutar cada 30 minutos.

## Credenciales de BD (para el comando)
- Usuario: `eduMagro`
- Password: `g1f*llkPPgDu8mr70`
- Base de datos: `app_hpr`

## Notas adicionales
- El proveedor ofrece backups cada 8 horas, pero se necesitan cada 30 minutos
- Considerar limpieza automática de backups antiguos para no llenar el disco
