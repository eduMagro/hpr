# Livewire SPA - Sistema de Inicialización de JavaScript

## 📋 Objetivo

Implementar un sistema robusto de inicialización de JavaScript que funcione correctamente tanto en:

-   **Navegación SPA** (Livewire sin recarga de página)
-   **Recarga completa** (F5, primera visita, navegación directa)

## 🔧 Problema a resolver

En una aplicación Livewire SPA, el JavaScript debe inicializarse en dos escenarios:

1. `DOMContentLoaded`: Cuando el usuario recarga la página (F5) o entra directamente
2. `livewire:navigated`: Cuando navega entre páginas sin recarga (SPA)

**Problema crítico**: Si no se gestionan correctamente los event listeners, se acumulan en cada navegación, causando:

-   Ejecución múltiple del mismo código
-   Consumo creciente de memoria
-   Comportamientos impredecibles
-   Degradación del rendimiento

## ✅ Solución implementada: Patrón Híbrido

### Características:

1. **Nombres únicos por página**: Cada página tiene su función inicializadora con nombre descriptivo
2. **Sistema de limpieza global**: Limpia automáticamente todos los listeners antes de cada navegación
3. **Protección contra doble inicialización**: Usa flags para evitar ejecutar el código múltiples veces
4. **Limpieza de recursos**: Resetea flags y limpia listeners antes de navegar

## 🏗️ Estructura de implementación

### 1. Sistema Global (app.blade.php)

Se implementa una vez en el layout principal:

-   Array global `window.pageInitializers` para rastrear inicializadores
-   Listener en `livewire:navigating` que limpia todos los inicializadores registrados

### 2. Patrón por página (cada blade.php)

Cada página implementa:

-   Función inicializadora con nombre único (ej: `initEpisPage`, `initUbicacionesPage`)
-   Registro en el array global
-   Listeners para `livewire:navigated` y `DOMContentLoaded`
-   Flag de protección contra doble inicialización
-   Limpieza de flag en `livewire:navigating`

## 📝 Plantilla de código

### En `resources/views/layouts/app.blade.php` (una sola vez):

```javascript
@push('scripts')
<script>
    // Sistema de limpieza global para Livewire SPA
    window.pageInitializers = window.pageInitializers || [];

    document.addEventListener('livewire:navigating', () => {
        // Limpiar todos los inicializadores registrados
        window.pageInitializers.forEach(init => {
            document.removeEventListener('livewire:navigated', init);
        });
        window.pageInitializers = [];
    });
</script>
@endpush
```

### En cada página (ejemplo: `epis/index.blade.php`):

```javascript
@push('scripts')
<script>
    function initNombrePaginaPage() {
        // Prevenir doble inicialización
        if (document.body.dataset.nombrePaginaPageInit === 'true') return;

        console.log('Inicializando página NombrePagina');

        // ========================================
        // TU CÓDIGO DE INICIALIZACIÓN AQUÍ
        // ========================================

        // Marcar como inicializado
        document.body.dataset.nombrePaginaPageInit = 'true';
    }

    // Registrar en el sistema global
    window.pageInitializers.push(initNombrePaginaPage);

    // Configurar listeners
    document.addEventListener('livewire:navigated', initNombrePaginaPage);
    document.addEventListener('DOMContentLoaded', initNombrePaginaPage);

    // Limpiar flag antes de navegar
    document.addEventListener('livewire:navigating', () => {
        document.body.dataset.nombrePaginaPageInit = 'false';
    });
</script>
@endpush
```

## 📊 Convenciones de nombres

Para mantener consistencia, usar el siguiente patrón:

| Archivo                       | Nombre de función       | Flag dataset          |
| ----------------------------- | ----------------------- | --------------------- |
| `epis/index.blade.php`        | `initEpisPage()`        | `episPageInit`        |
| `ubicaciones/index.blade.php` | `initUbicacionesPage()` | `ubicacionesPageInit` |
| `albaranes/index.blade.php`   | `initAlbaranesPage()`   | `albaranesPageInit`   |
| `openai/index.blade.php`      | `initOpenaiPage()`      | `openaiPageInit`      |
| `proveedores/index.blade.php` | `initProveedoresPage()` | `proveedoresPageInit` |

**Regla**: `init + NombreDescriptivo + Page()`

## 📦 Archivos modificados

### ✅ Sistema base implementado:

-   [x] `resources/views/layouts/app.blade.php` - Sistema global de limpieza ✅ **IMPLEMENTADO** (2025-12-17 08:48)

### 🔄 Páginas migradas al nuevo sistema:

-   [x] `resources/views/salidas/gestionar-salidas.blade.php` ✅ **MIGRADO** (2025-12-17 08:50)
    -   **Ruta:** `/salidas-ferralla/gestionar-salidas` → `salidas-ferralla.gestionar-salidas`
-   [x] `resources/views/pedidos/index.blade.php` ✅ **MIGRADO** (2025-12-17 08:52) - 4 inicializadores consolidados
    -   **Ruta:** `/pedidos` → `pedidos.index`
-   [x] `resources/views/livewire/paquetes-table.blade.php` ✅ **MIGRADO** (2025-12-17 08:54)
    -   **Componente Livewire** (usado en múltiples vistas)
-   [x] `resources/views/dashboard.blade.php` ✅ **MIGRADO** (2025-12-17 08:58) - Página principal
    -   **Ruta:** `/` → `dashboard`
-   [x] `resources/views/salidas/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:01) - 2 inicializadores consolidados
    -   **Ruta:** `/salidas-ferralla` → `salidas-ferralla.index`
-   [x] `resources/views/livewire/productos-table.blade.php` ✅ **MIGRADO** (2025-12-17 09:05)
    -   **Componente Livewire** (usado en `/productos`)
-   [x] `resources/views/livewire/production-logs-table.blade.php` ✅ **MIGRADO** (2025-12-17 09:06)
    -   **Componente Livewire** (usado en `/production-logs`)
-   [x] `resources/views/vacaciones/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:15)
    -   **Ruta:** `/vacaciones` → `vacaciones.index`
-   [x] `resources/views/ubicaciones/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:26)
    -   **Ruta:** `/ubicaciones` → `ubicaciones.index`
-   [x] `resources/views/planillas/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:19)
    -   **Ruta:** `/planillas` → `planillas.index`
    -   3 listeners consolidados (DOMContentLoaded, livewire:navigated, livewire:load)
-   [x] `resources/views/incorporaciones/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:32)
    -   **Ruta:** `/incorporaciones` → `incorporaciones.index`
-   [x] `resources/views/layouts/alerts.blade.php` ✅ **MIGRADO** (2025-12-17 09:38)
    -   **Sistema global de alertas** - 6 listeners consolidados
-   [x] `resources/views/departamentos/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:44)
    -   **Ruta:** `/departamentos` → `departamentos.index`
-   [x] `resources/views/entradas/create.blade.php` ✅ **MIGRADO** (2025-12-18 10:15)
    -   **Ruta:** `/entradas/create` → `entradas.create`
-   [x] `resources/views/elementos/index.blade.php` ✅ **MIGRADO** (2025-12-18 10:25)
    -   **Ruta:** `/elementos` → `elementos.index`
-   [x] `resources/views/empresas-transporte/index.blade.php` ✅ **MIGRADO** (2025-12-18 10:30)
    -   **Ruta:** `/empresas-transporte` → `empresas-transporte.index`
-   [x] `resources/views/maquinas/show.blade.php` ✅ **MIGRADO** (2025-12-18 10:45)
    -   **Ruta:** `/maquinas/{maquina}` → `maquinas.show`
-   [x] `resources/views/maquinas/seleccionar-maquina.blade.php` ✅ **MIGRADO** (2025-12-18 10:50)
    -   **Ruta:** `/maquinas/seleccionar` → `maquinas.seleccionar`
-   [x] `resources/views/movimientos/create.blade.php` ✅ **MIGRADO** (2025-12-18 10:55)
    -   **Ruta:** `/movimientos/create` → `movimientos.create`
-   [ ] `resources/views/epis/index.blade.php` - **NO REQUIERE** (usa Alpine.js, se reinicializa automáticamente)
    -   **Ruta:** `/epis` → `epis.index`
-   [x] `resources/views/entradas/index.blade.php` (Albaranes) ✅ **MIGRADO** (2025-12-18 11:30) (Refactored `entradas-table.blade.php` to use AlpineJS)
-   [ ] `resources/views/openai/index.blade.php` - **OMITIDO** (Pertenece a otra rama)
-   [ ] `resources/views/proveedores/index.blade.php` - **OMITIDO** (No se encuentra / Desconocido)
-   [x] `resources/views/productos/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:30)
    -   **Ruta:** `/productos` → `productos.index`
-   [x] `resources/views/vacaciones/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:15)
-   [x] `resources/views/produccion/trabajadoresObra.blade.php` ✅ **MIGRADO** (2025-12-18 11:15)
    -   **Ruta:** `/produccion/trabajadores-obra` → `produccion.verTrabajadoresObra`
-   [x] `resources/views/produccion/maquinas.blade.php` ✅ **MIGRADO** (2025-12-17 12:35)
    -   **Ruta:** `/produccion/maquinas` → `produccion.verMaquinas`
    -   Implements global singleton pattern for polling (`window._maquinasPollingInterval`) and event listeners (`window._maquinasListenerAdded`).
-   [x] `resources/views/movimientos/index.blade.php` ✅ **MIGRADO** (2025-12-17 12:45)
-   [x] `resources/views/livewire/etiquetas-table.blade.php` ✅ **MIGRADO** (Initialized listeners properly)
-   [x] `resources/views/livewire/elementos-table.blade.php` ✅ **MIGRADO** (Refactored to global functions)

## 🧠 Notas Técnicas y Lecciones Aprendidas (Latest Session)

### Patrón Singleton para Polling SPA

Para páginas con `setInterval` (polling):

1.  Asignar el intervalo a una propiedad de ventana global: `window._myPollingInterval`.
2.  Antes de iniciar uno nuevo, comprobar si existe y detenerlo.
3.  Dentro del bucle de polling, añadir un chequeo "suicida" (`!document.getElementById(...)`). Si el usuario navega fuera, el bucle debe detectarlo y detenerse a sí mismo limpiamente (`clearInterval(window._myPollingInterval)`).
4.  Exponer función global `stopPolling()` para limpiezas manuales.

### Patrón Singleton para Event Listeners

Para prevenir la acumulación exponencial de listeners `livewire:navigated` si el script se vuelve a ejecutar:

1.  Usar bandera global: `if (!window._listenerPageAdded) { addEventListener(...); window._listenerPageAdded = true; }`.

### Nullsafe Operator en PHP 8 (`?->`)

Fundamental para prevenir errores 500 en controladores cuando se accede a relaciones anidadas que podrían ser nulas (ej: `$obra?->cliente?->empresa` en lugar de `$obra->cliente->empresa`).

## 🧪 Cómo verificar que funciona

1. **Abrir consola del navegador**
2. **Navegar entre páginas** (sin recargar)
3. **Verificar que solo aparece un mensaje** de inicialización por navegación
4. **Recargar la página** (F5)
5. **Verificar que el código se ejecuta correctamente**
6. **Navegar 10-20 veces** entre páginas
7. **Verificar que no hay degradación** de rendimiento

## 🎯 Beneficios

✅ **Sin acumulación de listeners**: Se limpian automáticamente
✅ **Funciona en ambos modos**: SPA y recarga completa
✅ **Protección contra duplicados**: Flags previenen doble ejecución
✅ **Escalable**: Fácil de aplicar a nuevas páginas
✅ **Mantenible**: Patrón consistente en todo el proyecto
