# Exportación de códigos BVBS en Producción

## Resumen

El sistema genera archivos BVBS (formato BF2D) para la máquina PROGRESS MSR20. En producción, los archivos se descargan al ordenador del usuario y un script local los mueve automáticamente a la carpeta compartida de la máquina.

## Arquitectura

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         FLUJO DE EXPORTACIÓN                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  1. Usuario en PC oficina                                               │
│     └── Abre app de PRODUCCIÓN (VPS)                                    │
│         └── Navega a Máquinas > MSR20                                   │
│             └── Selecciona posición                                      │
│                 └── Clic en "Exportar BVBS"                             │
│                                                                          │
│  2. Servidor Producción (VPS)                                           │
│     └── MaquinaController::exportarBVBS()                               │
│         └── Genera contenido BVBS con ProgressBVBSService               │
│             └── Intenta guardar en red (FALLA - VPS no tiene acceso)    │
│                 └── Fallback: descarga al navegador                     │
│                                                                          │
│  3. PC Oficina (Downloads)                                              │
│     └── Archivo BVBS_MSR20_PRJ1234_20260124_120000.bvbs                 │
│         └── BVBS_Monitor.ps1 (script local) detecta el archivo          │
│             └── Mueve a \\192.168.0.10\...\COMPARTIDO_MAQUINA_MSR\      │
│                                                                          │
│  4. Máquina MSR20                                                       │
│     └── Script existente copia archivos de carpeta compartida           │
│         └── Programa de la máquina procesa los códigos                  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

## Por qué no funciona directamente desde producción

El servidor de producción (VPS) está en Internet y no tiene acceso a la red local de la oficina (192.168.0.x). Las rutas UNC como `\\192.168.0.10\...` son IPs privadas, no accesibles desde fuera de la red.

**En LOCAL** (servidor XAMPP en la oficina):
- Tiene acceso directo a la red 192.168.0.x
- Puede escribir en la carpeta compartida
- Funciona sin necesidad del script

**En PRODUCCIÓN** (VPS externo):
- No tiene acceso a la red local
- El archivo se descarga al navegador del usuario
- El script local lo mueve a la carpeta compartida

## Instalación del Monitor BVBS

### Requisitos

- Windows 10/11
- PowerShell (incluido en Windows)
- Acceso a la carpeta compartida `\\192.168.0.10\Datos\Compartido\COMPARTIDO_MAQUINA_MSR\`

### Archivos necesarios

Los scripts están en `/scripts/`:

| Archivo | Descripción |
|---------|-------------|
| `BVBS_Monitor.ps1` | Script PowerShell que monitorea la carpeta de descargas |
| `Instalar_BVBS_Monitor.bat` | Instalador (ejecutar en el PC de la oficina) |
| `Desinstalar_BVBS_Monitor.bat` | Desinstalador |

### Pasos de instalación

1. Copiar la carpeta `scripts/` al PC que usa la app para exportar BVBS
2. Ejecutar `Instalar_BVBS_Monitor.bat` como administrador
3. Confirmar que quieres iniciarlo ahora
4. Listo - se iniciará automáticamente con Windows

### Ubicación tras instalación

```
C:\Users\{USUARIO}\BVBS_Monitor\
├── BVBS_Monitor.ps1           # Script principal
└── BVBS_Monitor_Silent.vbs    # Lanzador silencioso (sin ventana)
```

El acceso directo de inicio automático se crea en:
```
%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\BVBS_Monitor.lnk
```

## Funcionamiento del Monitor

### Configuración (en BVBS_Monitor.ps1)

```powershell
$carpetaDescargas = [Environment]::GetFolderPath('UserProfile') + '\Downloads'
$carpetaDestino = '\\192.168.0.10\Datos\Compartido\COMPARTIDO_MAQUINA_MSR'
$patron = 'BVBS_*.bvbs'
```

### Comportamiento

1. **Inicio**: Se ejecuta en segundo plano al iniciar Windows (sin ventana visible)
2. **Monitoreo**: Usa `FileSystemWatcher` de Windows (eventos, no polling)
3. **Detección**: Cuando aparece un archivo `BVBS_*.bvbs` en Downloads
4. **Acción**:
   - Copia el archivo a la carpeta compartida
   - Verifica que se copió correctamente
   - Elimina el original de Downloads
   - Muestra notificación de Windows
5. **Errores**: Si no puede acceder a la carpeta compartida, muestra alerta

### Consumo de recursos

- CPU: ~0% (usa eventos del sistema, no revisa constantemente)
- RAM: ~20-30 MB (proceso PowerShell en segundo plano)

## Código fuente relevante

### Controlador (MaquinaController.php)

**Archivo:** `app/Http/Controllers/MaquinaController.php`
**Método:** `exportarBVBS()` (línea 1828)

```php
// Rutas que intenta (solo funcionan en LOCAL)
$rutasAIntentar = [
    '\\\\192.168.0.10\\Datos\\Compartido\\COMPARTIDO_MAQUINA_MSR\\',
    'M:\\COMPARTIDO_MAQUINA_MSR\\',
];

// Si falla, hace fallback a descarga
return response()->download(
    Storage::disk('local')->path($path),
    $filename,
    ['Content-Type' => 'text/plain; charset=UTF-8']
);
```

### Servicio BVBS (ProgressBVBSService.php)

**Archivo:** `app/Services/ProgressBVBSService.php`

Genera el formato BF2D compatible con máquinas PROGRESS MSR20/Ferrawin:
- Convierte datos de elementos a líneas BVBS
- Calcula checksum tipo C (suma ASCII mod 100)
- Formato: `BF2D;H:p=marca;H:n=barras;H:d=diametro;H:l=longitud;...;C:checksum`

## Formato del archivo BVBS

```
BF2D;H:p=M01;H:n=4;H:d=12;H:l=2500;H:g=B500SD;H:j=PRJ1234;H:r=Plano01;C:45
BF2D;H:p=M02;H:n=8;H:d=16;H:l=3000;G:l=1500/w=90;G:l=1500;H:g=B500SD;C:72
```

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| `H:p` | Marca/etiqueta | M01 |
| `H:n` | Número de barras | 4 |
| `H:d` | Diámetro (mm) | 12 |
| `H:l` | Longitud total (mm) | 2500 |
| `G:l/w` | Dimensiones: longitud(mm)/ángulo(°) | 1500/w=90 |
| `H:g` | Calidad del acero | B500SD |
| `H:j` | Código de proyecto | PRJ1234 |
| `H:r` | Nombre del plano | Plano01 |
| `C:` | Checksum | 45 |

## Solución de problemas

### El archivo no se mueve automáticamente

1. Verificar que el script está corriendo:
   - Abrir Administrador de tareas
   - Buscar proceso `powershell.exe`

2. Verificar acceso a carpeta compartida:
   - Abrir Explorador de archivos
   - Navegar a `\\192.168.0.10\Datos\Compartido\COMPARTIDO_MAQUINA_MSR\`
   - Si no abre, hay problema de red/permisos

3. Reinstalar el monitor:
   - Ejecutar `Desinstalar_BVBS_Monitor.bat`
   - Ejecutar `Instalar_BVBS_Monitor.bat`

### El script no inicia con Windows

1. Verificar que existe el acceso directo:
   ```
   %APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\BVBS_Monitor.lnk
   ```

2. Verificar política de ejecución de PowerShell:
   ```powershell
   Get-ExecutionPolicy
   # Debe ser RemoteSigned o Bypass
   ```

### Ver logs del script

El script no genera logs por defecto. Para depurar, ejecutar manualmente:
```powershell
powershell -ExecutionPolicy Bypass -File "C:\Users\{USUARIO}\BVBS_Monitor\BVBS_Monitor.ps1"
```

Esto abrirá una ventana con los mensajes de estado.

## Alternativas consideradas

| Alternativa | Pros | Contras |
|-------------|------|---------|
| VPN en VPS | Acceso directo a red local | Complejidad, mantenimiento |
| Sincronizar BD producción→local | Usar app local | Duplicación de datos |
| Descarga manual | Sin instalación | Paso extra cada vez |
| **Script monitor (elegida)** | Un clic, automático | Requiere instalación una vez |

## Historial de cambios

- **2026-01-24**: Documentación inicial del sistema de exportación BVBS en producción
