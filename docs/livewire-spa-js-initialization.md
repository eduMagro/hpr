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
-   [x] `resources/views/ubicaciones/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:17)
    -   **Ruta:** `/ubicaciones` → `ubicaciones.index`
-   [x] `resources/views/planillas/index.blade.php` ✅ **MIGRADO** (2025-12-17 09:19)
    -   **Ruta:** `/planillas` → `planillas.index`
    -   3 listeners consolidados (DOMContentLoaded, livewire:navigated, livewire:load)
-   [ ] `resources/views/epis/index.blade.php` - **NO REQUIERE** (usa Alpine.js, se reinicializa automáticamente)
    -   **Ruta:** `/epis` → `epis.index`
-   [ ] `resources/views/albaranes/index.blade.php`
-   [ ] `resources/views/openai/index.blade.php`
-   [ ] `resources/views/proveedores/index.blade.php`
-   [ ] `resources/views/productos/index.blade.php`
    -   **Ruta:** `/productos` → `productos.index`
-   [ ] `resources/views/vacaciones/index.blade.php`
-   [ ] `resources/views/produccion/trabajadoresObra.blade.php`
    -   **Ruta:** `/produccion/trabajadores-obra` → `produccion.verTrabajadoresObra`
-   [ ] `resources/views/produccion/maquinas.blade.php`
    -   **Ruta:** `/produccion/maquinas` → `produccion.verMaquinas`
-   [ ] (Añadir más según se vayan migrando)

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
✅ **Rendimiento óptimo**: No hay degradación con el uso

## 📚 Notas adicionales

-   **No usar funciones anónimas**: Siempre usar funciones nombradas para poder referenciarlas
-   **No usar nombres genéricos**: Evitar `js()`, `init()`, usar nombres descriptivos
-   **Limpiar recursos**: Si usas timers, intervals o listeners adicionales, limpiarlos en `livewire:navigating`
-   **Testear ambos escenarios**: Siempre probar navegación SPA y recarga completa

---

**Fecha de creación**: 2025-12-17
**Última actualización**: 2025-12-17
**Estado**: En implementación
