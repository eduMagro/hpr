# ============================================================
# BVBS Monitor - Monitorea descargas y copia a carpeta compartida
# Se ejecuta en segundo plano usando eventos de Windows (bajo consumo)
# ============================================================

# Configuracion
$carpetaDescargas = [Environment]::GetFolderPath('UserProfile') + '\Downloads'
$carpetaDestino = '\\192.168.0.10\Datos\Compartido\COMPARTIDO_MAQUINA_MSR'
$patron = 'BVBS_*.bvbs'

# Verificar que la carpeta de destino es accesible
function Test-DestinoAccesible {
    try {
        return Test-Path $carpetaDestino
    } catch {
        return $false
    }
}

# Funcion que se ejecuta cuando se detecta un archivo nuevo
function Mover-ArchivoBVBS {
    param([string]$rutaArchivo)

    $nombreArchivo = Split-Path $rutaArchivo -Leaf

    # Verificar que es un archivo BVBS
    if ($nombreArchivo -notlike $patron) {
        return
    }

    # Esperar un momento para asegurar que el archivo termino de descargarse
    Start-Sleep -Milliseconds 500

    # Verificar que el archivo existe y no esta vacio
    if (-not (Test-Path $rutaArchivo)) {
        return
    }

    $archivo = Get-Item $rutaArchivo
    if ($archivo.Length -eq 0) {
        Start-Sleep -Seconds 1
        $archivo = Get-Item $rutaArchivo
    }

    # Verificar acceso a carpeta destino
    if (-not (Test-DestinoAccesible)) {
        # Mostrar notificacion de error
        [System.Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null
        [System.Windows.Forms.MessageBox]::Show(
            "No se puede acceder a la carpeta compartida:`n$carpetaDestino`n`nEl archivo BVBS se queda en Descargas.",
            "BVBS Monitor - Error",
            [System.Windows.Forms.MessageBoxButtons]::OK,
            [System.Windows.Forms.MessageBoxIcon]::Warning
        )
        return
    }

    # Mover archivo
    $rutaDestino = Join-Path $carpetaDestino $nombreArchivo

    try {
        Copy-Item -Path $rutaArchivo -Destination $rutaDestino -Force

        # Verificar que se copio correctamente
        if ((Test-Path $rutaDestino) -and (Get-Item $rutaDestino).Length -gt 0) {
            # Eliminar el original de descargas
            Remove-Item -Path $rutaArchivo -Force

            # Notificacion de exito (toast notification)
            [System.Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null
            $notifyIcon = New-Object System.Windows.Forms.NotifyIcon
            $notifyIcon.Icon = [System.Drawing.SystemIcons]::Information
            $notifyIcon.BalloonTipIcon = 'Info'
            $notifyIcon.BalloonTipTitle = 'BVBS Exportado'
            $notifyIcon.BalloonTipText = "Archivo copiado a MSR:`n$nombreArchivo"
            $notifyIcon.Visible = $true
            $notifyIcon.ShowBalloonTip(3000)

            # Limpiar despues de mostrar
            Start-Sleep -Seconds 4
            $notifyIcon.Dispose()
        }
    } catch {
        [System.Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null
        [System.Windows.Forms.MessageBox]::Show(
            "Error al copiar archivo BVBS:`n$($_.Exception.Message)",
            "BVBS Monitor - Error",
            [System.Windows.Forms.MessageBoxButtons]::OK,
            [System.Windows.Forms.MessageBoxIcon]::Error
        )
    }
}

# Procesar archivos BVBS que ya existan en la carpeta de descargas
function Procesar-ArchivosExistentes {
    $archivosExistentes = Get-ChildItem -Path $carpetaDescargas -Filter $patron -ErrorAction SilentlyContinue
    foreach ($archivo in $archivosExistentes) {
        Mover-ArchivoBVBS -rutaArchivo $archivo.FullName
    }
}

# Crear el monitor de eventos del sistema de archivos
$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $carpetaDescargas
$watcher.Filter = $patron
$watcher.IncludeSubdirectories = $false
$watcher.EnableRaisingEvents = $true

# Evento cuando se crea un archivo nuevo
$onCreated = Register-ObjectEvent $watcher 'Created' -Action {
    $rutaArchivo = $Event.SourceEventArgs.FullPath
    Mover-ArchivoBVBS -rutaArchivo $rutaArchivo
}

# Evento cuando se renombra un archivo (por si el navegador descarga con nombre temporal primero)
$onRenamed = Register-ObjectEvent $watcher 'Renamed' -Action {
    $rutaArchivo = $Event.SourceEventArgs.FullPath
    Mover-ArchivoBVBS -rutaArchivo $rutaArchivo
}

# Procesar archivos existentes al iniciar
Procesar-ArchivosExistentes

# Mantener el script corriendo
Write-Host "BVBS Monitor iniciado correctamente" -ForegroundColor Green
Write-Host "Monitoreando: $carpetaDescargas" -ForegroundColor Cyan
Write-Host "Destino: $carpetaDestino" -ForegroundColor Cyan
Write-Host ""
Write-Host "El script se ejecuta en segundo plano. Puedes cerrar esta ventana." -ForegroundColor Yellow

# Bucle infinito para mantener el script vivo (bajo consumo)
while ($true) {
    Wait-Event -Timeout 3600
}
