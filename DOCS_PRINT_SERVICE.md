# Documentacion: Sistema de Impresion Automatica de QR (P-Touch)

## Resumen

Sistema para imprimir etiquetas QR automaticamente usando impresoras Brother P-Touch. El sistema consta de:

1. **Servicio local Python** (Flask) que corre en `localhost:8765`
2. **Frontend JavaScript** que detecta si el servicio esta corriendo
3. **Instalador automatico** descargable desde la aplicacion web

---

## Arquitectura

```
Usuario hace clic "Imprimir QR" en productos/index
         |
         v
[JavaScript verifica servicio localhost:8765]
         |
    +----+----+
    |         |
    v         v
[Servicio    [Servicio NO disponible]
disponible]       |
    |             v
    v        [Modal de instalacion]
[Modal de         |
impresion]        v
    |        [Descarga ZIP instalador]
    v             |
[POST a Laravel   v
genera codigos]  [Usuario ejecuta setup_and_start.bat]
    |
    v
[POST a localhost:8765/print]
    |
    v
[Servicio Python usa b-PAC SDK]
    |
    v
[Impresora Brother P-Touch imprime]
```

---

## Archivos del Sistema

### Backend Laravel

| Archivo | Descripcion |
|---------|-------------|
| `routes/web.php` | Ruta `print-service/download` para descargar el instalador ZIP |
| `resources/views/productos/index.blade.php` | Modal de impresion + Modal de instalacion + JavaScript |

### Servicio Python (carpeta `print-service/`)

| Archivo | Descripcion |
|---------|-------------|
| `setup_and_start.bat` | Script principal que instala dependencias y arranca el servicio |
| `print_service.py` | Servidor Flask que escucha en puerto 8765 |
| `b_pac_printer.py` | Integracion con Brother b-PAC SDK via COM |
| `requirements.txt` | Dependencias Python (flask, flask-cors, pywin32) |
| `etiqueta_qr.lbx` | Plantilla de etiqueta para P-Touch Editor |
| `README.md` | Documentacion del servicio |

---

## Endpoints del Servicio Python

### GET `/`
Health check. Retorna:
```json
{
  "status": "running",
  "service": "Brother P-Touch Print Service",
  "version": "1.0.0"
}
```

### POST `/print`
Imprime etiquetas QR. Espera:
```json
{
  "codigos": [
    {"codigo": "MP250101"},
    {"codigo": "MP250102"}
  ]
}
```

Retorna:
```json
{
  "success": true,
  "cantidad": 2,
  "total_solicitado": 2,
  "message": "Se imprimieron 2 de 2 etiquetas"
}
```

### GET `/test`
Prueba conexion con la impresora.

---

## Flujo de Instalacion

1. Usuario hace clic en "Imprimir QR"
2. JavaScript hace GET a `localhost:8765` (timeout 2 segundos)
3. Si falla, muestra modal con:
   - Instrucciones paso a paso
   - Boton "Descargar Instalador"
   - Boton "Ya lo tengo corriendo"
4. Usuario descarga ZIP desde `/print-service/download`
5. Usuario extrae y ejecuta `setup_and_start.bat`
6. Script automaticamente:
   - Verifica Python (si no existe, abre pagina de descarga)
   - Crea entorno virtual
   - Instala flask, flask-cors, pywin32
   - Verifica b-PAC SDK (solo warning si no existe)
   - Inicia servidor en puerto 8765
7. Usuario hace clic en "Ya lo tengo corriendo"
8. Sistema verifica y abre modal de impresion

---

## Requisitos del Sistema

### En el servidor web
- Laravel con ZipArchive habilitado

### En el equipo cliente (donde se imprime)
- **Python 3.8+** (con "Add to PATH" marcado)
- **P-Touch Editor 6** de Brother
- **Brother b-PAC SDK** - [Descargar](https://support.brother.com/g/s/es/dev/en/bpac/download/index.html)
- **Impresora Brother P-Touch** conectada (USB o Bluetooth)

---

## Problemas Conocidos y Soluciones

### Error: "Failed to fetch" / "ERR_CONNECTION_REFUSED"
**Causa:** El servicio Python no esta corriendo.
**Solucion:** Ejecutar `setup_and_start.bat`

### Error: Caracteres Unicode rotos en CMD
**Causa:** CMD de Windows no soporta bien UTF-8.
**Solucion:** Se usan solo caracteres ASCII en el script .bat

### Error: "python: can't open file 'print_service.py'"
**Causa:** El script se ejecuta desde otro directorio.
**Solucion:** El script usa `cd /d "%~dp0"` para cambiar al directorio correcto.

### Error: pip no funciona dentro del venv
**Causa:** Windows requiere path completo.
**Solucion:** Se usa `venv\Scripts\python.exe -m pip` en lugar de solo `pip`.

### Error: errorlevel incorrecto en batch
**Causa:** `%errorlevel%` no se actualiza correctamente dentro de bloques `if`.
**Solucion:** Se usa `if errorlevel 1` y verificacion post-instalacion.

---

## Estado Actual (Diciembre 2024)

### Completado
- [x] Script `setup_and_start.bat` que instala y arranca automaticamente
- [x] Modal de instalacion en el frontend
- [x] Verificacion automatica del servicio antes de imprimir
- [x] Ruta de descarga del instalador ZIP
- [x] Correccion de caracteres Unicode en .bat
- [x] Correccion de paths en .bat
- [x] Correccion de errorlevel en .bat

### Pendiente de probar
- [ ] Instalacion completa en equipo limpio
- [ ] Impresion real con impresora P-Touch conectada
- [ ] Verificar que b-PAC SDK funciona correctamente

---

## Comandos Utiles

### Probar servicio manualmente
```cmd
cd C:\xampp\htdocs\manager\print-service
setup_and_start.bat
```

### Verificar que el servicio responde
```
curl http://localhost:8765
```

### Ver logs del servicio
```cmd
type print_service.log
```

---

## Proximos Pasos

1. Probar que el instalador funciona completamente
2. Probar impresion real con impresora Brother
3. Considerar hacer el servicio un Windows Service para que inicie automaticamente
4. Posible: crear instalador .exe con PyInstaller para no depender de Python instalado
