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
