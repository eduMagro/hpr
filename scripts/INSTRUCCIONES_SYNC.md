# Instrucciones para Sincronizar Planillas

## Proceso completo

### PASO 1: Exportar datos de PRODUCCIÓN
1. Sube el archivo `scripts/exportar_para_sync.php` a producción
2. Accede desde el navegador: `https://tudominio.com/scripts/exportar_para_sync.php`
3. Se descargará un archivo `backup_produccion_FECHA.sql`

### PASO 2: Importar en LOCAL
1. Abre phpMyAdmin local (http://localhost/phpmyadmin)
2. Selecciona tu base de datos
3. Ve a "Importar"
4. Selecciona el archivo `backup_produccion_FECHA.sql`
5. Click en "Continuar"

### PASO 3: Ejecutar el comando en LOCAL
```bash
php artisan planillas:sincronizar-excel
```

### PASO 4: Exportar datos actualizados de LOCAL
1. Accede desde el navegador: `http://localhost/manager/scripts/exportar_despues_sync.php`
2. Se descargará un archivo `datos_sync_FECHA.sql`

### PASO 5: Importar en PRODUCCIÓN
1. Accede a phpMyAdmin de producción
2. Selecciona la base de datos
3. Ve a "Importar"
4. Selecciona el archivo `datos_sync_FECHA.sql`
5. Click en "Continuar"

**IMPORTANTE:** El archivo `datos_sync_FECHA.sql` ya incluye los TRUNCATE necesarios.

---

## Notas de seguridad
- Elimina los archivos PHP de /scripts/ después de usarlos
- No dejes estos scripts accesibles en producción permanentemente
- Haz un backup completo antes de importar en producción
