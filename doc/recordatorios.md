# Recordatorios - Tareas Pendientes

## 2026-01-20 - Aplicar Subetiquetas

### Estado Actual
Se detectaron **~90,000 elementos** sin `etiqueta_sub_id` asignado. Esto afecta a planillas antiguas importadas antes de que existiera la funcionalidad de subetiquetas.

### Script Creado
```
/scripts/aplicar_subetiquetas_todas.php
```

### Parámetros
- `?dry_run=1` - Solo ver qué se haría
- `?limit=N` - Limitar a N planillas
- `?offset=N` - Saltar las primeras N planillas (para continuar)

### Progreso
- [ ] Ejecutar script completo
- Último offset procesado: **0** (no iniciado)

### Cómo Continuar
```
# Ver estado actual:
/scripts/aplicar_subetiquetas_todas.php?dry_run=1

# Continuar desde donde se quedó (cambiar offset según último procesado):
/scripts/aplicar_subetiquetas_todas.php?offset=0

# Si falla, anotar el número de planilla y continuar:
/scripts/aplicar_subetiquetas_todas.php?offset=XXX
```

### Comandos Relacionados
```bash
# Aplicar a planillas específicas:
php artisan planillas:aplicar-subetiquetas 2026-252 2026-253

# Script web para planillas específicas:
/scripts/aplicar_subetiquetas.php?codigos=2026-252,2026-253
```

---

## Otros Pendientes

### Filtrado en produccion/maquinas
- Se corrigió la función `limpiarResaltado()` para quitar clases directamente del DOM
- Verificar que funciona correctamente el reset de filtros

### Scripts Disponibles
| Script | Descripción |
|--------|-------------|
| `/scripts/limpiar_no_aprobadas.php` | Elimina planillas no aprobadas de orden_planillas |
| `/scripts/aplicar_subetiquetas.php?codigos=X` | Aplica subetiquetas a planillas específicas |
| `/scripts/aplicar_subetiquetas_todas.php` | Aplica subetiquetas a todas las planillas pendientes |

### Comandos Artisan Disponibles
| Comando | Descripción |
|---------|-------------|
| `php artisan orden-planillas:reindexar` | Reindexar posiciones de orden_planillas |
| `php artisan planillas:aplicar-subetiquetas` | Aplicar subetiquetas a planillas específicas |

---

## 2026-01-21 - Automatización de Salidas (COMPLETADO)

### Descripción del Sistema
Sistema automático para asociar paquetes a salidas basándose en `obra_id` + `fecha_salida`.

### Sistema Implementado

#### Concepto Simple
- Cuando se crea un paquete, el `PaqueteObserver` busca automáticamente una salida con:
  - `obra_id` = obra de la planilla del paquete
  - `fecha_salida` = fecha de entrega (de elementos o de planilla)
- Si existe una salida con espacio (< 28tn), asocia el paquete
- Si no existe o está llena, crea una nueva salida automáticamente
- Las salidas también se pueden crear manualmente desde gestionar-salidas

#### Flujo
```
Operario crea paquete
        ↓
PaqueteObserver se activa
        ↓
Determina fecha (elemento.fecha_entrega o planilla.fecha_estimada_entrega)
        ↓
Busca salida: WHERE obra_id=X AND fecha_salida=Y AND estado!='completada'
        ↓
Si existe con espacio → Asocia paquete
Si no existe/llena → Crea nueva salida con obra_id + fecha_salida
```

#### Archivos Clave

**Observer:**
- `app/Observers/PaqueteObserver.php` - Lógica de asociación automática

**Base de datos:**
- `salidas.obra_id` (nuevo campo) - Obra prioritaria de la salida

**Modelo:**
- `app/Models/Salida.php` - Nueva relación `obra()` y campo en fillable

#### Migraciones
- `2026_01_21_174726_add_obra_id_to_salidas_table` - Añade obra_id a salidas
- `2026_01_21_174803_remove_automatizacion_salidas_activa_from_planillas_table` - Elimina campo obsoleto

#### Código Eliminado
Se eliminó el sistema manual de selección/automatización:
- Ruta `POST /planificacion/automatizar-salidas`
- Métodos en `PlanificacionController`: automatizarSalidas, verificarConflictosPaquetes, etc.
- Funciones JS: toggleSeleccionEvento, automatizarSalidas, limpiarSeleccion
- eventClick handler en calendar.js

#### Notas Importantes
- La salida puede tener paquetes de otras obras (añadidos manualmente)
- El `obra_id` en salida indica la obra "prioritaria" para la automatización
- Si se mueve una agrupación de fecha, se debe actualizar la `fecha_salida` de la salida correspondiente

#### Sincronización de Salidas al Mover Agrupaciones
Cuando se mueve una agrupación (planillas o elementos) en el calendario:
1. Se actualiza la fecha de las planillas/elementos
2. Se actualizan automáticamente las salidas con `obra_id + fecha_salida` correspondiente

#### Fusión de Elementos con su Planilla
Caso especial cuando elementos con `fecha_entrega` propia se mueven al día de su planilla:
- Si la nueva fecha coincide con `fecha_estimada_entrega` de la planilla
- Los elementos pierden su `fecha_entrega` (se pone a `null`)
- Se fusionan en una sola agrupación con la planilla
- Las salidas asociadas también se mueven a la fecha de la planilla

Esto permite que elementos que tenían entregas separadas puedan consolidarse con su planilla original arrastrándolos al mismo día.
