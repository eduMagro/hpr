# Reset de Planillas

## Descripcion General

El reset de planillas permite devolver una planilla a su estado inicial, eliminando todo el progreso de fabricacion y reasignando maquinas a los elementos.

## Flujo del Reset

### 1. Modal de Configuracion (Previo al Reset)

Antes de ejecutar el reset, se abre un modal que permite:

- **Editar fecha estimada de entrega**: Modificar `fecha_estimada_entrega` de la planilla
- **Ver elementos**: Lista de todos los elementos con su diametro, forma, unidades, etc.
- **Asignar maquinas**: Cambiar la maquina asignada (`maquina_id`) a cada elemento
- **Filtrar por diametro**: Filtrar la tabla de elementos por diametro
- **Asignacion masiva**: Asignar la misma maquina a todos los elementos filtrados

### 2. Opciones de Guardado

- **Guardar cambios**: Solo guarda la fecha y asignaciones sin resetear
- **Guardar y Resetear**: Guarda los cambios y ejecuta el reset completo

### 3. Proceso de Reset (`ejecutarResetPlanilla`)

El reset se ejecuta en dos fases con transacciones separadas:

#### FASE 1: Limpieza (transaccion con reintentos)

1. **Eliminar paquetes**: Se eliminan todos los paquetes asociados a la planilla
2. **Resetear etiquetas**: Estado = `pendiente`, fechas = null, operarios = null
3. **Eliminar ordenes**: Se eliminan registros de `orden_planillas`
4. **Resetear elementos**: Estado = `pendiente`, fechas = null, operarios = null, `maquina_id` = null
5. **Resetear planilla**: Estado = `pendiente`, fechas = null, revisada = false

#### FASE 2: Reasignacion (transaccion separada)

1. **Reasignar maquinas**: `AsignarMaquinaService::repartirPlanilla()` asigna maquinas a elementos segun reglas
2. **Crear ordenes**: `OrdenPlanillaService::crearOrdenParaPlanilla()` crea registros en `orden_planillas`

## Calculo de Posicion en Cola

Al crear ordenes en `orden_planillas`, la posicion se calcula segun `fecha_estimada_entrega`:

- **Con fecha**: Se inserta en la posicion correcta (antes de planillas con fecha posterior)
- **Sin fecha**: Va al final de la cola (`max(posicion) + 1`)

Las posiciones de otras planillas se desplazan automaticamente para hacer hueco.

## Endpoints

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | `/planillas/{id}/config-reset` | Obtiene datos para el modal (planilla, elementos, maquinas) |
| POST | `/planillas/{id}/config-reset` | Guarda fecha_estimada_entrega y asignaciones de maquinas |
| POST | `/planillas/{id}/resetear` | Ejecuta el reset completo |

## Archivos Relacionados

- **Controller**: `app/Http/Controllers/PlanillaController.php`
  - `getConfigReset()`: Obtiene datos para modal
  - `saveConfigReset()`: Guarda configuracion
  - `resetearPlanilla()`: Ejecuta reset
  - `ejecutarResetPlanilla()`: Logica interna del reset

- **Servicios**:
  - `app/Services/AsignarMaquinaService.php`: Asignacion de maquinas a elementos
  - `app/Services/OrdenPlanillaService.php`: Gestion de ordenes y posiciones

- **Vista**: `resources/views/livewire/planillas-table.blade.php`
  - Modal `#modal-config-planilla`
  - Funciones JS: `abrirModalConfigPlanilla()`, `guardarConfigPlanilla()`, etc.

## Validaciones

- No se puede resetear si hay etiquetas en estado `cortando` o `procesando`
- El sistema reintenta hasta 3 veces en caso de deadlock de base de datos
- Timeout de sesion configurado a 60 segundos para operaciones largas

## Modelo de Datos Afectados

```
planillas
  - estado -> 'pendiente'
  - fecha_inicio -> null
  - fecha_finalizacion -> null
  - revisada -> false

etiquetas
  - estado -> 'pendiente'
  - fecha_inicio -> null
  - fecha_finalizacion -> null
  - operario_id -> null

elementos
  - estado -> 'pendiente'
  - fecha_inicio -> null
  - fecha_finalizacion -> null
  - operario_id -> null
  - maquina_id -> null (se reasigna despues)

paquetes
  - Se eliminan completamente

orden_planillas
  - Se eliminan y recrean con nuevas posiciones
```
