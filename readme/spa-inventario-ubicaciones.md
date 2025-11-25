# SPA e Inventario en Ubicaciones

## Contexto
- App usa Livewire Navigate (SPA) y un SPA custom con Alpine/Fetch. Esto causaba parpadeos, doble ejecución de scripts y errores de rehidratación (`_x_refs undefined`, redeclaraciones, NetworkError).
- Se integró el modo Inventario dentro de `resources/views/ubicaciones/index.blade.php` para evitar saltos a otra vista y mantener una experiencia única.

## Estrategias aplicadas
- `data-navigate-once` para componentes persistentes (header/sidebar) y `data-navigate-reload` para scripts que deben correr en cada navegación.
- Ejecución controlada de scripts tras el morph del SPA (en `layouts/app.blade.php`) evitando duplicados; reejecuta los marcados como reload.
- Aislar piezas sensibles con `wire:ignore` cuando Livewire podría rehidratar refs de Alpine (modal de inventario).
- Estado global en `Alpine.store('inv')` para modo inventario y acordeones; no depende del root de render de Livewire.
- LocalStorage por ubicación (`inv-<id>`) para persistir escaneos; SweetAlert para errores/feedback.

## Uso actual
- Botón “Inventario” en `/ubicaciones` activa/desactiva el modo en el store Alpine.
- Click en una ubicación (con modo activo) abre modal de inventario con materiales, input enfocado, contador de escaneos y limpieza rápida.
- Sectores como acordeón (cerrados por defecto) con botón global “Abrir/Cerrar todo”.
- Paquetes no se renderizan en esta vista (pendiente de nueva implementación).

## Rutas y archivos clave
- `resources/views/ubicaciones/index.blade.php`: vista principal con modo inventario integrado, acordeones y modal.
- `resources/views/layouts/app.blade.php`: control de ejecución de scripts SPA (morph + firmas).
- `public/js/inventario/inventario.js`: UI de acordeones y animaciones de inventario heredadas (para otras vistas).
- `resources/views/components/top-header-enhanced.blade.php` y `.../sidebar-menu-enhanced.blade.php`: marcados con `data-navigate-once`.

## Pendientes
- Consolidar navegación (decidir SPA Livewire vs SPA custom) para reducir complejidad.
- Si se aprueba, eliminar la vista antigua de inventario tras verificar funcionalidad integrada.
- Revisar vistas con scripts inline para marcarlas correctamente (`data-navigate-reload` o stores Alpine) y evitar nuevos `_x_refs`.
