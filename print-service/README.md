# Servicio de Impresión Automática P-Touch

Sistema de impresión automática de etiquetas QR para códigos de productos usando Brother P-Touch Editor 6 y b-PAC SDK.

## Características

- **Automatización completa**: 1 clic desde la aplicación web para generar códigos e imprimir
- **Generación de QR**: Códigos QR con el identificador del producto
- **Texto legible**: Código del producto impreso debajo del QR
- **Impresión en cadena**: Imprime automáticamente todas las etiquetas generadas
- **Sin intervención manual**: No requiere abrir P-Touch Editor ni importar archivos Excel

## Requisitos Previos

### 1. Software necesario

- **Python 3.8 o superior** - [Descargar aquí](https://www.python.org/downloads/)
  - Durante la instalación, marca la opción "Add Python to PATH"

- **P-Touch Editor 6** - Debe estar instalado en el PC
  - Descargable desde el sitio web de Brother

- **Brother b-PAC SDK** - [Descargar aquí](https://support.brother.com/g/s/es/dev/en/bpac/download/index.html)
  - Es un SDK gratuito de Brother
  - Instalar la versión para Windows
  - Compatible con modelos P-Touch: PT-P700, PT-P750W, PT-E550W, PT-D600, y otros modelos profesionales

### 2. Hardware necesario

- Impresora Brother P-Touch conectada al PC (USB o Bluetooth)
- Cintas de etiquetas Brother compatibles (se recomienda 29mm x 90mm)

## Instalación

### Paso 1: Instalar Brother b-PAC SDK

1. Descarga el instalador de b-PAC SDK desde el sitio de Brother
2. Ejecuta el instalador y sigue las instrucciones
3. Reinicia el PC después de la instalación

### Paso 2: Instalar el Servicio Local

1. Abre una terminal de comandos (cmd) como **Administrador**

2. Navega a la carpeta del servicio:
   ```cmd
   cd C:\xampp\htdocs\manager\print-service
   ```

3. Ejecuta el instalador:
   ```cmd
   install.bat
   ```

4. Espera a que se complete la instalación. Verás mensajes como:
   ```
   [OK] Python detectado
   [OK] Entorno virtual creado
   [OK] Dependencias instaladas
   ```

### Paso 3: Iniciar el Servicio

1. Ejecuta el archivo `start_service.bat` (doble clic)

2. Verás una ventana con el mensaje:
   ```
   Servicio de Impresión P-Touch
   ===============================================
   Iniciando servicio en http://localhost:8765
   Presiona Ctrl+C para detener el servicio
   ```

3. **IMPORTANTE**: Mantén esta ventana abierta mientras uses la aplicación web

## Uso

### Desde la Aplicación Web

1. Asegúrate de que el servicio esté ejecutándose (`start_service.bat`)

2. Asegúrate de que la impresora Brother P-Touch esté encendida y conectada

3. En la aplicación web, ve a la sección **Productos**

4. Haz clic en el botón verde **"Generar e Imprimir QR"**

5. Ingresa la cantidad de etiquetas a generar

6. Haz clic en **"Generar e Imprimir"**

7. El sistema automáticamente:
   - Genera los códigos secuenciales
   - Crea las etiquetas con QR + código de texto
   - Las envía a la impresora P-Touch
   - Muestra confirmación de éxito

### Estados del Proceso

- **"Generando códigos..."** - El servidor está generando los códigos
- **"✓ X códigos generados. Enviando a impresora..."** - Códigos listos, enviando a impresión
- **"✓ ¡Completado! X etiquetas enviadas a imprimir."** - Proceso finalizado con éxito
- **"Error..."** - Ocurrió un problema (ver sección de solución de problemas)

## Solución de Problemas

### Error: "No se pudo conectar con el servicio de impresión"

**Causa**: El servicio local no está ejecutándose

**Solución**:
- Ejecuta `start_service.bat`
- Verifica que aparezca el mensaje "Escuchando en http://localhost:8765"

### Error: "No se pudo inicializar Brother b-PAC SDK"

**Causa**: b-PAC SDK no está instalado correctamente

**Solución**:
1. Reinstala Brother b-PAC SDK
2. Reinicia el PC
3. Verifica que P-Touch Editor 6 esté instalado

### Error: "Error imprimiendo etiqueta"

**Causa**: Impresora no está conectada o no tiene cinta

**Solución**:
- Verifica que la impresora esté encendida
- Verifica la conexión USB/Bluetooth
- Verifica que haya cinta instalada
- Prueba imprimir una etiqueta de prueba desde P-Touch Editor manualmente

## Arquitectura del Sistema

```
Usuario hace clic "Generar e Imprimir" en la web
         ↓
Servidor genera códigos + datos en JSON
         ↓
Web envía petición a http://localhost:8765/print
         ↓
Servicio local recibe datos
         ↓
Usa Brother b-PAC SDK para crear etiquetas QR + código
         ↓
Imprime automáticamente todas las etiquetas
         ↓
Web muestra confirmación
```

## Logs y Diagnóstico

El servicio genera un archivo de log: `print_service.log`

Para ver los logs:
```cmd
cd C:\xampp\htdocs\manager\print-service
type print_service.log
```

---

**Versión**: 1.0.0
