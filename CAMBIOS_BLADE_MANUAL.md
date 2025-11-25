# Instrucciones para Completar la Refactorización

## Paso 1: Agregar @vite y @push al inicio del archivo

**Ubicación**: Después de la línea 1 (`<x-slot name="title">`)

**Agregar**:
```blade
<x-app-layout>
    <x-slot name="title">Planificación por Máquina</x-slot>

    {{-- AGREGAR ESTAS LÍNEAS --}}
    @push('calendar')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    @endpush

    @vite(['resources/js/modules/produccion-maquinas/index.js', 'resources/css/produccion/maquinas.css'])

    <script data-navigate-once>
        // Inyectar datos para el módulo JS
        window.ProduccionMaquinas = {
            maquinas: @json($resources),
            planillas: @json($planillasEventos),
            turnosActivos: @json($turnosLista),
            cargaTurnoResumen: @json($cargaTurnoResumen ?? []),
            planDetallado: @json($planDetallado ?? []),
            realDetallado: @json($realDetallado ?? [])
        };
    </script>
    {{-- FIN AGREGAR --}}

    <div id="produccion-maquinas-container">
```

## Paso 2: Eliminar scripts CDN

**Ubicación**: Líneas 273-278

**Eliminar completamente**:
```blade
<!-- Scripts externos -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
<script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>
<script src="{{ asset('js/multiselect-elementos.js') }}"></script>
```

## Paso 3: Eliminar bloque <style>

**Ubicación**: Líneas 728-1142

**Eliminar desde**:
```blade
<style>
    /* Contenedor calendario */
    ...
</style>
```

Todo este bloque ya está en `resources/css/produccion/maquinas.css`

## Paso 4: Eliminar bloque <script>

**Ubicación**: Líneas 1143-4587 (hasta el final del archivo antes de `</x-app-layout>`)

**Eliminar desde**:
```blade
<script data-navigate-once>
    function inicializarCalendarioMaquinas() {
        ...
    }
    ...
</script>
```

Todo este JavaScript ya está modularizado en `resources/js/modules/produccion-maquinas/`

## Paso 5: Compilar assets

```bash
# Desarrollo
npm run dev

# Producción
npm run build
```

## Verificación Post-Refactorización

Comprobar que funcionan:
- [ ] El calendario se renderiza
- [ ] Los filtros funcionan
- [ ] Drag & drop de planillas
- [ ] Panel lateral de elementos
- [ ] Modales de cambio de estado
- [ ] Modal de redistribución
- [ ] Botones de optimizar y balancear
- [ ] Modo pantalla completa
- [ ] Toggle de turnos
- [ ] Tooltips en eventos

## Tamaño Final Esperado

- **Antes**: ~4587 líneas
- **Después**: ~600-800 líneas (solo HTML estructural y Blade directives)

## ⚠️ Nota Importante

Mantener intactos todos los modales HTML y elementos del DOM. Solo eliminar:
1. Scripts CDN (reemplazados por npm packages)
2. CSS inline (movido a archivo separado)
3. JavaScript inline (modularizado)

El HTML estructural debe permanecer exactamente igual.
