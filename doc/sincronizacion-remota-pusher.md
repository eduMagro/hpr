# Sistema de Sincronizacion Remota via Pusher

## Descripcion General

El sistema de sincronizacion remota permite iniciar y controlar la sincronizacion de datos FerraWin → Manager desde cualquier ubicacion (produccion, movil, casa) enviando comandos al servidor de la oficina via Pusher Channels.

Anteriormente, la sincronizacion solo podia ejecutarse desde un PC de la oficina con acceso directo a SQL Server. Con este sistema, un usuario en produccion puede pulsar "Sincronizar" y el comando se ejecuta automaticamente en la oficina.

## Arquitectura

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              OFICINA (LAN)                                  │
│                                                                             │
│  ┌─────────────────────┐         ┌─────────────────────────────────────┐   │
│  │   SQL SERVER        │         │   WINDOWS (listener)                │   │
│  │   192.168.0.7:1433  │◄───────►│                                     │   │
│  │                     │  ODBC   │   ferrawin-sync/                    │   │
│  │   - FERRAWIN DB     │         │   ├── sync-optimizado.php          │   │
│  │   - Planillas       │         │   ├── sync-listener.php (daemon)   │   │
│  │   - Elementos       │         │   └── .env (credenciales)          │   │
│  └─────────────────────┘         └──────────────┬──────────────────────┘   │
│                                                 │                           │
└─────────────────────────────────────────────────┼───────────────────────────┘
                                                  │
                                                  │ WSS + HTTPS
                                                  ▼
                                         ┌─────────────────┐
                                         │  PUSHER.COM     │
                                         │  (WebSocket)    │
                                         │  Cluster: eu    │
                                         └────────┬────────┘
                                                  │
                                                  ▼
                              ┌───────────────────────────────────┐
                              │  PRODUCCION (Linux)               │
                              │  hierrospacoreyes.es              │
                              │                                   │
                              │  Laravel + Livewire               │
                              │  └── SyncMonitor.php              │
                              └───────────────────────────────────┘
```

## Flujo de Sincronizacion

```
1. Usuario en produccion
   └── Pulsa "Sincronizar 2025" en SyncMonitor
              │
              ▼
2. Laravel (SyncMonitor.php)
   └── Detecta que NO es entorno local (Linux)
   └── Dispara SyncCommandEvent via Pusher
              │
              ▼
3. Pusher Channels
   └── Envia mensaje al canal private-sync-control
   └── WebSocket wss://ws-eu.pusher.com
              │
              ▼
4. sync-listener.php (oficina)
   └── Recibe evento 'sync.command'
   └── Ejecuta sync-optimizado.php en background
              │
              ▼
5. sync-optimizado.php
   └── Conecta a SQL Server (192.168.0.7)
   └── Lee planillas y elementos
   └── Envia a produccion via API REST
              │
              ▼
6. Produccion recibe datos
   └── Guarda en base de datos
   └── Planillas sincronizadas
```

## Componentes

### Produccion (Laravel)

| Archivo | Descripcion |
|---------|-------------|
| `config/broadcasting.php` | Configuracion de Pusher Channels |
| `app/Events/SyncCommandEvent.php` | Evento para enviar comandos (start, pause) |
| `app/Events/SyncStatusEvent.php` | Evento para recibir estado del listener |
| `routes/channels.php` | Autorizacion del canal privado |
| `app/Livewire/SyncMonitor.php` | UI de control (modificado para soporte remoto) |

### Windows (ferrawin-sync)

| Archivo | Descripcion |
|---------|-------------|
| `sync-listener.php` | Daemon WebSocket que escucha comandos |
| `sync-optimizado.php` | Script de sincronizacion |
| `start-listener.bat` | Inicia listener con ventana visible |
| `start-listener-background.vbs` | Inicia listener sin ventana (para inicio automatico) |
| `stop-listener.bat` | Detiene el listener |
| `.env` | Credenciales (SQL Server, Pusher, API) |

## Protocolo de Mensajes

### Canal
- **Nombre:** `private-sync-control`
- **Tipo:** Privado (requiere autenticacion)
- **Autenticacion:** HMAC-SHA256 con Pusher secret

### Eventos

#### sync.command (Produccion → Windows)

```json
{
  "command": "start",
  "params": {
    "año": "2025",
    "target": "production",
    "desde_codigo": null
  },
  "requestId": "uuid-v4",
  "timestamp": "2026-01-17T10:30:00Z"
}
```

| Campo | Valores | Descripcion |
|-------|---------|-------------|
| command | `start`, `pause`, `status` | Accion a ejecutar |
| params.año | `2024`, `2025`, `todos`, `nuevas` | Año a sincronizar |
| params.target | `local`, `production` | Destino de la sincronizacion |
| params.desde_codigo | `2025-007816` o `null` | Para continuar desde una planilla |

#### sync.status (Windows → Produccion)

```json
{
  "status": "running",
  "progress": "150/500",
  "year": "2025",
  "target": "production",
  "lastPlanilla": "2025-007850",
  "message": null,
  "requestId": "uuid-v4",
  "timestamp": "2026-01-17T10:31:00Z"
}
```

| Campo | Valores | Descripcion |
|-------|---------|-------------|
| status | `running`, `paused`, `completed`, `error`, `idle` | Estado actual |
| progress | `150/500` | Planillas procesadas / total |
| year | `2025` | Año en sincronizacion |
| lastPlanilla | `2025-007850` | Ultima planilla procesada |

## Archivos de Control

| Archivo | Ubicacion | Funcion |
|---------|-----------|---------|
| `sync.pid` | ferrawin-sync/ | PID del proceso de sincronizacion activo |
| `sync.pause` | ferrawin-sync/ | Señal para pausar (se crea al pausar, se detecta y para) |
| `listener.pid` | ferrawin-sync/ | PID del daemon listener |
| `listener.status` | ferrawin-sync/ | Estado JSON del listener con heartbeat |

## Configuracion

### Variables de entorno (.env)

#### Produccion (Laravel)
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=tu_app_id
PUSHER_APP_KEY=tu_app_key
PUSHER_APP_SECRET=tu_app_secret
PUSHER_APP_CLUSTER=eu
```

#### Windows (ferrawin-sync)
```env
# SQL Server FerraWin
FERRAWIN_HOST=192.168.0.7
FERRAWIN_PORT=1433
FERRAWIN_DATABASE=FERRAWIN
FERRAWIN_USERNAME=sa
FERRAWIN_PASSWORD=********

# API Produccion
PRODUCTION_URL=https://app.hierrospacoreyes.es/
PRODUCTION_TOKEN=********

# Pusher
PUSHER_APP_ID=tu_app_id
PUSHER_APP_KEY=tu_app_key
PUSHER_APP_SECRET=tu_app_secret
PUSHER_APP_CLUSTER=eu
```

## Requisitos de Red

| Origen | Destino | Puerto | Protocolo | Descripcion |
|--------|---------|--------|-----------|-------------|
| PC oficina | SQL Server | 1433 | TCP | Conexion ODBC a FerraWin |
| PC oficina | ws-eu.pusher.com | 443 | WSS | WebSocket para recibir comandos |
| PC oficina | app.hierrospacoreyes.es | 443 | HTTPS | API para enviar datos |

## Instalacion

### Produccion

1. Instalar dependencia:
```bash
composer require pusher/pusher-php-server
```

2. Configurar `.env` con credenciales Pusher

3. Subir archivos:
   - `config/broadcasting.php`
   - `app/Events/SyncCommandEvent.php`
   - `app/Events/SyncStatusEvent.php`
   - `routes/channels.php`

### Windows (Oficina)

1. Instalar XAMPP (PHP 8.1+)

2. Copiar carpeta `ferrawin-sync` a `C:\xampp\htdocs\`

3. Configurar `.env` con credenciales

4. Instalar dependencias:
```batch
cd C:\xampp\htdocs\ferrawin-sync
composer update
```

5. Iniciar listener:
```batch
start-listener.bat
```

6. (Opcional) Inicio automatico con Windows:
   - Copiar `start-listener-background.vbs` a `shell:startup`

## Dependencias

### Produccion (composer.json)
```json
{
  "pusher/pusher-php-server": "^7.2"
}
```

### Windows (ferrawin-sync/composer.json)
```json
{
  "pusher/pusher-php-server": "^7.2",
  "ratchet/pawl": "^0.4",
  "guzzlehttp/guzzle": "^7.0",
  "vlucas/phpdotenv": "^5.5"
}
```

## Seguridad

- **Canal privado:** Requiere autenticacion, solo usuarios autorizados pueden suscribirse
- **Firma HMAC-SHA256:** Valida que los mensajes vienen de Pusher
- **Tokens API:** Comunicacion sync → produccion autenticada
- **Credenciales en .env:** No expuestas en codigo fuente

## Solucion de Problemas

### El listener no conecta

1. Verificar credenciales Pusher en `.env`
2. Verificar acceso a Internet
3. Comprobar que no hay firewall bloqueando puerto 443

### La sincronizacion no se ejecuta

1. Verificar que el listener esta corriendo (`listener.pid` existe)
2. Revisar logs en `ferrawin-sync/logs/listener-*.log`
3. Verificar acceso a SQL Server

### El comando no llega

1. Verificar que produccion tiene `BROADCAST_CONNECTION=pusher`
2. Verificar credenciales Pusher en ambos lados
3. Comprobar en Pusher Dashboard si los mensajes se envian

## Comandos Utiles

```batch
# Iniciar listener (con ventana)
start-listener.bat

# Iniciar listener (sin ventana)
wscript start-listener-background.vbs

# Detener listener
stop-listener.bat

# Ver estado del listener
type listener.status

# Ver logs
type logs\listener-2026-01-17.log
```

## Cuenta Pusher

- **Servicio:** Pusher Channels
- **Plan:** Sandbox (gratuito)
- **Limites:** 200,000 mensajes/dia, 100 conexiones
- **URL:** https://dashboard.pusher.com
