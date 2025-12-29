# Sistema de Sincronizacion FerraWin - Manager

## Indice

1. [Descripcion General](#descripcion-general)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Componentes](#componentes)
4. [Requisitos](#requisitos)
5. [Instalacion](#instalacion)
6. [Configuracion](#configuracion)
7. [Uso](#uso)
8. [Estructura de Datos](#estructura-de-datos)
9. [Logs y Monitoreo](#logs-y-monitoreo)
10. [Solucion de Problemas](#solucion-de-problemas)

---

## Descripcion General

El sistema de sincronizacion FerraWin-Manager permite transferir automaticamente los datos de planillas desde la base de datos FerraWin (SQL Server local) hacia el servidor de produccion de Manager (Laravel).

### Problema que resuelve

- FerraWin es un software de corte de barras que corre en Windows con SQL Server local
- Manager es una aplicacion web Laravel en un servidor externo (VPS)
- El servidor de produccion NO tiene acceso directo a la red local donde esta FerraWin
- Se necesita sincronizar planillas diariamente sin intervencion manual

### Solucion implementada

Un proyecto "tunel" (`ferrawin-sync`) que:
1. Se ejecuta en un PC de la red local con acceso a FerraWin
2. Consulta las planillas de los ultimos N dias
3. Envia los datos via HTTPS al servidor de produccion
4. Se programa para ejecutarse automaticamente cada dia

---

## Arquitectura del Sistema

```
+------------------+          +-------------------+          +------------------+
|                  |          |                   |          |                  |
|    FerraWin      |  SQL     |   ferrawin-sync   |  HTTPS   |     Manager      |
|   (SQL Server)   | -------> |     (Tunel)       | -------> |   (Produccion)   |
|   192.168.0.7    |          |   PC Local        |          |      VPS         |
|                  |          |                   |          |                  |
+------------------+          +-------------------+          +------------------+
                                      |
                                      | Task Scheduler
                                      | (14:00 diario)
                                      v
                              +-------------------+
                              |   sync.php        |
                              |   (Script PHP)    |
                              +-------------------+
```

### Flujo de datos

1. **Task Scheduler** ejecuta `sync-ferrawin.bat` a las 14:00
2. **sync.php** conecta a FerraWin y obtiene planillas de los ultimos 7 dias
3. Los datos se formatean y comprimen (gzip)
4. Se envian via POST a `/api/ferrawin/sync` en produccion
5. **Manager** recibe, valida e importa los datos
6. Se registra el resultado en logs y base de datos

---

## Componentes

### 1. Proyecto Tunel (ferrawin-sync)

Ubicacion: `C:\xampp\htdocs\ferrawin-sync\`

```
ferrawin-sync/
├── .env                    # Configuracion (credenciales)
├── .env.example            # Plantilla de configuracion
├── composer.json           # Dependencias PHP
├── composer.lock           # Versiones bloqueadas
├── sync.php                # Script principal de sincronizacion
├── sync-ferrawin.bat       # Script para Task Scheduler
├── test-connection.php     # Prueba de conexiones
├── logs/                   # Logs de sincronizacion
│   └── sync-YYYY-MM-DD.log
├── vendor/                 # Dependencias (Guzzle, Monolog, etc.)
└── src/
    ├── ApiClient.php       # Cliente HTTP para produccion
    ├── Config.php          # Cargador de configuracion
    ├── Database.php        # Conexion a SQL Server
    ├── FerrawinQuery.php   # Consultas SQL a FerraWin
    └── Logger.php          # Sistema de logging
```

#### Archivos clave

**src/FerrawinQuery.php** - Consultas a la base de datos FerraWin:
- `getCodigosPlanillas($dias)`: Obtiene codigos de planillas recientes
- `getDatosPlanilla($codigo)`: Obtiene todos los elementos de una planilla
- `formatearParaApi($datos)`: Transforma los datos al formato de la API

**src/ApiClient.php** - Cliente HTTP:
- `enviarPlanillas($planillas, $metadata)`: Envia lote de planillas
- `testConnection()`: Verifica conectividad con produccion

**sync.php** - Orquestador principal:
1. Verifica conexion a FerraWin
2. Verifica conexion a produccion
3. Obtiene planillas
4. Formatea y envia
5. Reporta resultados

### 2. API en Manager (Produccion)

Ubicacion: Proyecto Laravel Manager

#### Rutas

```php
// routes/api.php
Route::prefix('ferrawin')->group(function () {
    Route::get('/status', [FerrawinSyncController::class, 'status']);
    Route::post('/sync', [FerrawinSyncController::class, 'sync'])
        ->middleware('ferrawin.api');
});
```

#### Controlador

`app/Http/Controllers/Api/FerrawinSyncController.php`
- `status()`: Endpoint de health check (publico)
- `sync()`: Recibe y procesa sincronizacion (protegido por token)

#### Servicio de importacion

`app/Services/FerrawinSync/FerrawinBulkImportService.php`
- Importacion masiva optimizada
- Procesa en chunks de 100 elementos
- Maneja creacion/actualizacion de planillas

#### Middleware de autenticacion

`app/Http/Middleware/FerrawinApiAuth.php`
- Valida el token en header `X-Ferrawin-Token`
- Rechaza requests sin token valido

#### Modelo de logs

`app/Models/FerrawinSyncLog.php`
- Registra cada sincronizacion
- Campos: planillas, elementos, duracion, errores

---

## Requisitos

### PC Local (donde corre ferrawin-sync)

- **Sistema Operativo**: Windows 10/11
- **PHP**: 8.1 o superior
- **Extensiones PHP**:
  - `pdo_sqlsrv` (conexion a SQL Server)
  - `curl` (para Guzzle)
  - `mbstring`
  - `json`
- **Acceso de red**:
  - A FerraWin (192.168.0.7:1433)
  - A Internet (HTTPS al servidor de produccion)

### Servidor de Produccion

- **Laravel**: 10.x o superior
- **PHP**: 8.1 o superior
- **Base de datos**: MySQL/MariaDB
- **HTTPS**: Certificado SSL valido

---

## Instalacion

### Paso 1: Instalar driver SQL Server en PHP (Windows)

1. Descargar los drivers desde:
   https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server

2. Extraer y copiar los archivos DLL a `C:\xampp\php\ext\`:
   - `php_sqlsrv_82_nts_x64.dll`
   - `php_pdo_sqlsrv_82_nts_x64.dll`

3. Editar `C:\xampp\php\php.ini` y agregar al final:
   ```ini
   extension=php_sqlsrv_82_nts_x64.dll
   extension=php_pdo_sqlsrv_82_nts_x64.dll
   ```

4. Reiniciar Apache/XAMPP

5. Verificar instalacion:
   ```bash
   php -m | grep sqlsrv
   ```

### Paso 2: Configurar proyecto tunel

1. El proyecto ya esta en `C:\xampp\htdocs\ferrawin-sync\`

2. Las dependencias ya estan instaladas (`vendor/`)

3. Editar `.env` con los valores correctos:
   ```env
   # Base de datos FerraWin
   FERRAWIN_HOST=192.168.0.7
   FERRAWIN_PORT=1433
   FERRAWIN_DATABASE=FERRAWIN
   FERRAWIN_USERNAME=sa
   FERRAWIN_PASSWORD=tu_password

   # Servidor de produccion
   PRODUCTION_URL=https://tu-dominio.com
   API_TOKEN=token_seguro_generado

   # Sincronizacion
   SYNC_DIAS_ATRAS=7
   SYNC_COMPRESS=true

   # Logging
   LOG_LEVEL=info
   ```

### Paso 3: Configurar Manager en produccion

1. Subir el codigo actualizado al servidor

2. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```

3. Configurar variables de entorno en `.env`:
   ```env
   FERRAWIN_API_TOKEN=mismo_token_que_en_tunel
   ```

4. Verificar que la ruta funciona:
   ```bash
   curl https://tu-dominio.com/api/ferrawin/status
   ```
   Debe responder: `{"status":"ok",...}`

### Paso 4: Probar conexiones

Desde el PC local:
```bash
cd C:\xampp\htdocs\ferrawin-sync
php test-connection.php
```

Salida esperada:
```
=== Test de Conexiones FerraWin Sync ===

1. Probando conexion a FerraWin (SQL Server)...
   Host: 192.168.0.7
   Puerto: 1433
   Base de datos: FERRAWIN
   OK Conexion exitosa

2. Probando conexion a produccion...
   URL: https://tu-dominio.com
   OK Conexion exitosa

3. Verificando extension PHP sqlsrv...
   OK Extension cargada

=== Resumen ===
OK Todas las conexiones funcionan correctamente.
```

### Paso 5: Ejecutar sincronizacion manual

```bash
php C:\xampp\htdocs\ferrawin-sync\sync.php
```

O con dias personalizados:
```bash
php C:\xampp\htdocs\ferrawin-sync\sync.php 14
```

### Paso 6: Configurar Task Scheduler

1. Abrir **Programador de tareas** de Windows (taskschd.msc)

2. Click en **Crear tarea basica**

3. Configurar:
   - **Nombre**: FerraWin Sync
   - **Descripcion**: Sincronizacion diaria de planillas
   - **Desencadenador**: Diariamente a las 14:00
   - **Accion**: Iniciar un programa
   - **Programa**: `C:\xampp\htdocs\ferrawin-sync\sync-ferrawin.bat`
   - **Iniciar en**: `C:\xampp\htdocs\ferrawin-sync`

4. En propiedades avanzadas:
   - Marcar "Ejecutar tanto si el usuario inicio sesion como si no"
   - Marcar "Ejecutar con los privilegios mas altos"

---

## Configuracion

### Variables de entorno del tunel (.env)

| Variable | Descripcion | Valor por defecto |
|----------|-------------|-------------------|
| `FERRAWIN_HOST` | IP del servidor SQL Server | 192.168.0.7 |
| `FERRAWIN_PORT` | Puerto SQL Server | 1433 |
| `FERRAWIN_DATABASE` | Nombre de la base de datos | FERRAWIN |
| `FERRAWIN_USERNAME` | Usuario SQL Server | sa |
| `FERRAWIN_PASSWORD` | Password SQL Server | - |
| `PRODUCTION_URL` | URL del servidor Manager | - |
| `API_TOKEN` | Token de autenticacion | - |
| `SYNC_DIAS_ATRAS` | Dias hacia atras a consultar | 7 |
| `SYNC_COMPRESS` | Comprimir datos con gzip | true |
| `LOG_LEVEL` | Nivel de log (debug/info/warning/error) | info |

### Variables de entorno en Manager (.env)

| Variable | Descripcion |
|----------|-------------|
| `FERRAWIN_API_TOKEN` | Token para autenticar requests del tunel |

### Generar token seguro

```bash
# En Linux/Mac
openssl rand -hex 32

# En PHP
php -r "echo bin2hex(random_bytes(32));"
```

---

## Uso

### Sincronizacion manual

```bash
# Sincronizar ultimos 7 dias (por defecto)
php sync.php

# Sincronizar ultimos 14 dias
php sync.php 14

# Sincronizar ultimo dia
php sync.php 1
```

### Ver logs

Los logs se guardan en `ferrawin-sync/logs/sync-YYYY-MM-DD.log`

```bash
# Ver log de hoy
type logs\sync-2024-12-16.log

# Ver ultimas lineas
tail -f logs\sync-2024-12-16.log
```

### Verificar estado en Manager

En la base de datos de Manager, tabla `ferrawin_sync_logs`:

```sql
SELECT * FROM ferrawin_sync_logs ORDER BY created_at DESC LIMIT 10;
```

---

## Estructura de Datos

### Formato de envio (JSON)

```json
{
  "planillas": [
    {
      "codigo": "2024-001234",
      "descripcion": "Planilla edificio Norte",
      "seccion": "A",
      "ensamblado": "E1",
      "elementos": [
        {
          "codigo_cliente": "CLI001",
          "nombre_cliente": "Constructora ABC",
          "codigo_obra": "OBR001",
          "nombre_obra": "Edificio Norte",
          "ensamblado": "E1",
          "seccion": "A",
          "descripcion_planilla": "Vigas nivel 1",
          "fila": "1",
          "descripcion_fila": "Viga V-101",
          "marca": "M1",
          "diametro": 16,
          "figura": "F01",
          "longitud": 5.25,
          "dobles_barra": 2,
          "barras": 10,
          "peso": 82.5,
          "dimensiones": "250+100+250",
          "etiqueta": "ET001"
        }
      ]
    }
  ],
  "metadata": {
    "origen": "PC-OFICINA",
    "fecha_sync": "2024-12-16 14:00:00",
    "dias_consultados": 7
  }
}
```

### Tablas FerraWin consultadas

| Tabla | Descripcion |
|-------|-------------|
| `ORD_HEAD` | Cabecera de ordenes/planillas |
| `ORD_BAR` | Barras/elementos de cada planilla |
| `ORD_DET` | Detalles adicionales |
| `PROJECT` | Proyectos/obras |
| `PROD_DETO` | Etiquetas de produccion |

### Mapeo de campos

| Campo FerraWin | Campo Manager |
|----------------|---------------|
| ZCONTA + ZCODIGO | codigo (planilla) |
| ZCODCLI | codigo_cliente |
| ZCLIENTE | nombre_cliente |
| ZCODOBRA | codigo_obra |
| ZNOMBRE (PROJECT) | nombre_obra |
| ZMODULO | ensamblado, seccion |
| ZFECHA | fecha |
| ZNOMBRE (ORD_HEAD) | descripcion_planilla |
| ZCODLIN | fila |
| ZSITUACION | descripcion_fila |
| ZMARCA | marca |
| ZDIAMETRO | diametro |
| ZCODMODELO | figura |
| ZLONGTESTD | longitud |
| ZNUMBEND | dobles_barra |
| ZCANTIDAD | barras |
| ZPESOTESTD | peso |
| ZFIGURA | dimensiones |
| ZETIQUETA | etiqueta |

---

## Logs y Monitoreo

### Niveles de log

- **debug**: Informacion detallada para desarrollo
- **info**: Operaciones normales (recomendado para produccion)
- **warning**: Situaciones anomalas no criticas
- **error**: Errores que impiden completar la operacion

### Formato de log

```
[2024-12-16 14:00:01] INFO: === Iniciando sincronizacion FerraWin ===
[2024-12-16 14:00:01] INFO: Verificando conexion a FerraWin...
[2024-12-16 14:00:02] INFO: Conexion a FerraWin OK
[2024-12-16 14:00:02] INFO: Verificando conexion a produccion...
[2024-12-16 14:00:03] INFO: Conexion a produccion OK
[2024-12-16 14:00:03] INFO: Obteniendo planillas de los ultimos 7 dias...
[2024-12-16 14:00:04] INFO: Encontradas 15 planillas
[2024-12-16 14:00:10] INFO: Preparadas 15 planillas con 847 elementos
[2024-12-16 14:00:10] INFO: Enviando planillas a produccion...
[2024-12-16 14:00:15] INFO: Planillas enviadas {"cantidad":15,"elementos_total":847,"comprimido":true}
[2024-12-16 14:00:15] INFO: === Sincronizacion completada === {"duracion_segundos":14.23,"planillas_enviadas":15,"planillas_creadas":3,"planillas_actualizadas":12,"elementos_creados":156}
```

### Logs en Manager

Canal dedicado en `storage/logs/ferrawin_sync.log`:

```
[2024-12-16 14:00:12] ferrawin_sync.INFO: [API] Recibiendo sincronizacion {"ip":"xxx.xxx.xxx.xxx","content_length":"45678","compressed":true}
[2024-12-16 14:00:12] ferrawin_sync.INFO: [API] Datos recibidos {"planillas":15,"elementos_total":847}
[2024-12-16 14:00:15] ferrawin_sync.INFO: [API] Sincronizacion completada {"duracion":2.85,"planillas_creadas":3,"elementos_creados":156}
```

---

## Solucion de Problemas

### Error: "No se pudo conectar a la base de datos FerraWin"

**Causas posibles**:
1. SQL Server no esta corriendo
2. IP o puerto incorrectos
3. Credenciales invalidas
4. Firewall bloqueando conexion
5. Extension sqlsrv no instalada

**Soluciones**:
```bash
# Verificar extension
php -m | grep sqlsrv

# Probar conexion manual
php -r "new PDO('sqlsrv:Server=192.168.0.7,1433;Database=FERRAWIN', 'sa', 'password');"

# Verificar puerto abierto
telnet 192.168.0.7 1433
```

### Error: "No se pudo conectar al servidor de produccion"

**Causas posibles**:
1. URL incorrecta
2. Servidor no accesible
3. Certificado SSL invalido
4. Token incorrecto

**Soluciones**:
```bash
# Probar endpoint
curl -v https://tu-dominio.com/api/ferrawin/status

# Verificar DNS
nslookup tu-dominio.com

# Probar con token
curl -H "X-Ferrawin-Token: tu_token" https://tu-dominio.com/api/ferrawin/sync
```

### Error: "Extension sqlsrv no encontrada"

1. Descargar drivers correctos para tu version de PHP
2. Verificar arquitectura (x64 vs x86)
3. Verificar thread-safe (ts) vs non-thread-safe (nts)
4. Para XAMPP generalmente es: `php_pdo_sqlsrv_82_nts_x64.dll`

### Error: "Token invalido" (401 Unauthorized)

1. Verificar que el token en `.env` del tunel coincide con `FERRAWIN_API_TOKEN` en Manager
2. El token debe ser exactamente igual (sin espacios extra)
3. Regenerar token si es necesario

### La sincronizacion no se ejecuta automaticamente

1. Verificar Task Scheduler:
   - La tarea esta habilitada
   - El trigger esta configurado correctamente
   - La accion apunta al .bat correcto

2. Ver historial de la tarea en Task Scheduler

3. Ejecutar manualmente el .bat para verificar errores

### Planillas duplicadas

El sistema usa el codigo de planilla como identificador unico. Si una planilla ya existe:
- Se actualizan sus datos
- No se crean duplicados

---

## Seguridad

### Recomendaciones

1. **Token API**: Usar token largo y aleatorio (minimo 32 caracteres hex)
2. **HTTPS**: Siempre usar HTTPS en produccion
3. **Firewall**: Limitar IPs que pueden acceder al endpoint `/api/ferrawin/sync`
4. **Credenciales**: No commitear `.env` a repositorios
5. **Logs**: No loguear datos sensibles (passwords, tokens completos)

### Rotacion de token

Para cambiar el token:
1. Generar nuevo token
2. Actualizar en Manager `.env` -> `FERRAWIN_API_TOKEN`
3. Actualizar en tunel `.env` -> `API_TOKEN`
4. Reiniciar servicios si es necesario

---

## Mantenimiento

### Limpieza de logs

Los logs del tunel rotan automaticamente (30 dias).

Para limpiar logs antiguos manualmente:
```bash
# Windows
forfiles /p "C:\xampp\htdocs\ferrawin-sync\logs" /s /m *.log /d -30 /c "cmd /c del @path"
```

### Actualizaciones

Para actualizar dependencias del tunel:
```bash
cd C:\xampp\htdocs\ferrawin-sync
composer update
```

### Backup

Archivos importantes a respaldar:
- `ferrawin-sync/.env` (configuracion)
- `ferrawin-sync/logs/` (historico de sincronizaciones)
