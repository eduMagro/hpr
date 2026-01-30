# Guía de Instalación del Proyecto HPR

Esta carpeta contiene scripts y documentación para desplegar el proyecto desde cero en un entorno Ubuntu (como después de un formateo).

## Archivos
- `setup.sh`: Script principal de instalación automatizada.
- `docker/`: Configuración y notas sobre Docker.
- `php/`: Notas sobre la configuración de PHP.
- `env_backup`: Copia de seguridad del archivo `.env`.
- `dns/`: Configuración del dominio local `app.test`.

## Documentación Relacionada
- [Cron & Backups](../BACKUP_CRON_SETUP.md)
- [Print Service](../DOCS_PRINT_SERVICE.md)

## Uso Rápido
Para instalar todo el entorno (PHP, Docker, Node, Dependencias, Base de Datos):

1. Abrir terminal en la carpeta raíz del proyecto.
2. Ejecutar:
   ```bash
   sudo chmod +x installation/setup.sh
   sudo ./installation/setup.sh
   ```

## Qué hace el script
1. Actualiza los repositorios de Ubuntu.
2. Instala dependencias básicas (`curl`, `git`, `unzip`).
3. Instala **PHP 8.2** y las extensiones necesarias.
4. Instala **Composer** (Gestor de paquetes PHP).
5. Instala **Node.js 20** (Para el frontend/Vite).
6. Instala **Docker** y **Docker Compose**.
7. Configura el proyecto:
   - Copia `.env.example` a `.env` (si no existe).
   - Ejecuta `composer install`.
   - Ejecuta `npm install` y `npm run build`.
   - Levanta los contenedores con Laravel Sail.
   - Genera la key de la aplicación.
   - Ejecuta las migraciones de base de datos (Nota: Desactivado por defecto, ejecutar manualmente si es necesario).
   - Crea el enlace simbólico de storage.

## Notas Importantes
- **Base de Datos**: Las credenciales se encuentran en el archivo `.env`. El script asume la configuración por defecto de Laravel Sail (`mysql`).
- **Permisos**: El script se debe ejecutar con `sudo`, pero ejecuta los comandos del proyecto (composer, sail) como el usuario normal para evitar problemas de permisos.
