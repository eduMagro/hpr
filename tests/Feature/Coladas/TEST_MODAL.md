# 🧪 Cómo Probar el Modal de Trazabilidad

## Paso 1: Ir al Panel de Control

```
URL: http://localhost/production-logs
```

## Paso 2: Abrir Consola del Navegador

Presiona **F12** y ve a la pestaña **Console**

## Paso 3: Verificar que el Modal Existe

Ejecuta en la consola:

```javascript
document.getElementById('modalDetallesFabricacion')
```

**Resultado esperado:** Debe devolver un elemento HTML, NO `null`

Si devuelve `null`, el modal no se está renderizando. Verifica que el archivo Blade se cargó correctamente.

## Paso 4: Verificar que Bootstrap Está Disponible

```javascript
typeof bootstrap
```

**Resultado esperado:** `"object"`

Si devuelve `"undefined"`, Bootstrap no está cargado. Necesitas incluir Bootstrap en el proyecto.

## Paso 5: Verificar que la Función Existe

```javascript
typeof window.mostrarDetallesFabricacion
```

**Resultado esperado:** `"function"`

Si devuelve `"undefined"`, el script no se cargó. Verifica que el `@push('scripts')` funciona.

## Paso 6: Probar Abrir el Modal Manualmente

```javascript
window.mostrarDetallesFabricacion(12345)
```

**Resultado esperado:** El modal debe abrirse (aunque mostrará error de datos si la etiqueta no existe)

## Paso 7: Verificar Rutas API

```javascript
fetch('/api/fabricacion/detalles-etiqueta?etiqueta_id=12345')
    .then(r => r.json())
    .then(d => console.log(d))
```

**Resultado esperado:** Debe devolver JSON con `success: false` o `success: true` (dependiendo si existe la etiqueta)

---

## 🔧 Soluciones a Problemas Comunes

### Problema 1: Modal no se Renderiza

**Síntoma:**
```javascript
document.getElementById('modalDetallesFabricacion')
// → null
```

**Solución:**
1. Verificar que el archivo existe:
   ```bash
   ls resources/views/components/fabricacion/modal-detalles.blade.php
   ```

2. Limpiar caché de vistas:
   ```bash
   php artisan view:clear
   php artisan cache:clear
   ```

3. Verificar que el `@include` está en el archivo:
   ```bash
   grep -n "modal-detalles" resources/views/livewire/production-logs-table.blade.php
   ```

---

### Problema 2: Bootstrap No Disponible

**Síntoma:**
```javascript
typeof bootstrap
// → "undefined"
```

**Solución:**

Verificar en el layout principal (`resources/views/layouts/app.blade.php` o similar) que Bootstrap esté incluido:

```html
<!-- Debe existir algo como: -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

Si no existe, añade Bootstrap al layout.

**Alternativa:** Usar jQuery modal si Bootstrap no está disponible:

```javascript
// En lugar de bootstrap.Modal, usar jQuery:
$('#modalDetallesFabricacion').modal('show');
```

---

### Problema 3: Función No Disponible

**Síntoma:**
```javascript
typeof window.mostrarDetallesFabricacion
// → "undefined"
```

**Solución:**

1. Verificar que el script se cargó:
   ```javascript
   // Ver todos los scripts de la página
   Array.from(document.scripts).map(s => s.src || 'inline')
   ```

2. Definir la función manualmente en consola para probar:
   ```javascript
   window.mostrarDetallesFabricacion = function(etiquetaId) {
       alert('Probando modal para etiqueta: ' + etiquetaId);
       $('#modalDetallesFabricacion').modal('show');
   }
   ```

3. Luego hacer click en el botón "Coladas" y ver si funciona.

---

### Problema 4: Click No Hace Nada

**Síntoma:** Haces click en "Coladas" y no pasa nada

**Solución:**

1. Verificar errores en consola (F12 → Console)

2. Ver el HTML del botón:
   ```javascript
   document.querySelector('button[onclick*="mostrarDetallesFabricacion"]')
   ```

3. Probar click manualmente:
   ```javascript
   // Obtener el botón
   const btn = document.querySelector('button[onclick*="mostrarDetallesFabricacion"]');
   console.log('Botón:', btn);
   console.log('onclick:', btn.getAttribute('onclick'));

   // Ejecutar manualmente
   eval(btn.getAttribute('onclick'));
   ```

---

### Problema 5: Modal Se Abre Pero No Carga Datos

**Síntoma:** Modal se abre pero muestra "Error al cargar detalles"

**Solución:**

1. Verificar que las rutas existen:
   ```bash
   php artisan route:list | grep fabricacion
   ```

   **Debe mostrar:**
   ```
   GET api/fabricacion/detalles-etiqueta
   GET api/fabricacion/buscar-colada
   GET api/fabricacion/estadisticas
   GET api/fabricacion/meses-disponibles
   ```

2. Probar la ruta manualmente:
   ```
   http://localhost/api/fabricacion/detalles-etiqueta?etiqueta_id=12345
   ```

3. Verificar que existen archivos CSV:
   ```bash
   ls storage/app/produccion_piezas/
   ```

4. Verificar contenido del CSV:
   ```bash
   cat storage/app/produccion_piezas/fabricacion_2025_11.csv | grep ASIGNACION_COLADAS
   ```

---

## ✅ Test Completo de Integración

Ejecuta este script completo en la consola:

```javascript
(async function testModal() {
    console.log('=== TEST MODAL DE TRAZABILIDAD ===\n');

    // 1. Verificar modal existe
    const modal = document.getElementById('modalDetallesFabricacion');
    console.log('1. Modal existe:', modal !== null);

    // 2. Verificar Bootstrap
    console.log('2. Bootstrap disponible:', typeof bootstrap !== 'undefined');

    // 3. Verificar función
    console.log('3. Función disponible:', typeof window.mostrarDetallesFabricacion === 'function');

    // 4. Verificar botón existe
    const btn = document.querySelector('button[onclick*="mostrarDetallesFabricacion"]');
    console.log('4. Botón "Coladas" existe:', btn !== null);

    // 5. Probar API
    try {
        const response = await fetch('/api/fabricacion/meses-disponibles');
        const data = await response.json();
        console.log('5. API responde:', data.success === true);
        console.log('   Meses disponibles:', data.data?.meses);
    } catch (error) {
        console.log('5. API ERROR:', error.message);
    }

    console.log('\n=== FIN TEST ===');
})();
```

**Resultado esperado:**
```
=== TEST MODAL DE TRAZABILIDAD ===

1. Modal existe: true
2. Bootstrap disponible: true
3. Función disponible: true
4. Botón "Coladas" existe: true
5. API responde: true
   Meses disponibles: ["2025_11"]

=== FIN TEST ===
```

---

## 🚀 Test Rápido (Sin Datos Reales)

Si no tienes datos todavía, puedes probar que el modal funciona visualmente:

```javascript
// Abrir modal vacío
const modalEl = document.getElementById('modalDetallesFabricacion');
const modal = new bootstrap.Modal(modalEl);
modal.show();
```

El modal debe abrirse mostrando "Cargando..." o la estructura vacía.

---

## 📞 Reportar Problema

Si ninguno de los tests funciona, reporta:

1. **Navegador y versión:** (Chrome 120, Firefox 115, etc.)
2. **Resultado del test completo:** (copiar consola)
3. **Errores en consola:** (screenshot de errores en rojo)
4. **Resultado de:**
   ```bash
   php artisan route:list | grep fabricacion
   php artisan view:clear
   ```

---

**Powered by FERRALLIN 🤖**
