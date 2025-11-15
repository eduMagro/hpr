# Sistema de Turnos Configurables

## Archivos Creados

### 1. SQL
- `database/sql/turnos_configurables.sql` - Crear tabla desde cero
- `database/sql/actualizar_turnos.sql` - Actualizar tabla existente

### 2. Modelo
- `app/Models/Turno.php` - Actualizado con nuevos campos

### 3. Controlador
- `app/Http/Controllers/TurnoController.php` - CRUD completo

### 4. Vistas
- `resources/views/configuracion/turnos/index.blade.php` - Listado
- `resources/views/configuracion/turnos/create.blade.php` - Crear
- `resources/views/configuracion/turnos/edit.blade.php` - Editar
- `resources/views/configuracion/turnos/form.blade.php` - Formulario compartido

### 5. Rutas
- Ya existía `Route::resource('turnos', TurnoController::class);`
- Agregada `Route::patch('turnos/{turno}/toggle', ...)`

## Pasos para Implementar

### Paso 1: Ejecutar SQL

Ejecuta uno de estos scripts SQL según tu caso:

**Si la tabla NO existe:**
```sql
-- Ejecutar database/sql/turnos_configurables.sql
```

**Si la tabla YA existe:**
```sql
-- Ejecutar database/sql/actualizar_turnos.sql
```

### Paso 2: Verificar Datos

Verifica que los turnos por defecto se insertaron:
```sql
SELECT * FROM turnos;
```

Deberías ver:
- **Mañana**: 06:00-14:00, offset inicio=0, offset fin=0
- **Tarde**: 14:00-22:00, offset inicio=0, offset fin=0
- **Noche**: 22:00-06:00, offset inicio=0, offset fin=1

### Paso 3: Acceder a la Interfaz

Visita: `http://tu-dominio.com/turnos`

Desde ahí podrás:
- ✅ Ver listado de turnos
- ✅ Crear nuevos turnos
- ✅ Editar turnos existentes
- ✅ Activar/desactivar turnos
- ✅ Eliminar turnos
- ✅ Configurar horarios y offsets

### Paso 4: Actualizar Lógica de Calendario ✅ COMPLETADO

La lógica de calendario ya ha sido actualizada en `app/Http/Controllers/ProduccionController.php` para usar los turnos activos de la BD:

#### Cambios implementados:

1. **Método `obtenerSegmentosLaborablesDia()`**: Obtiene los segmentos de turnos activos para un día dado, aplicando los offsets para turnos que cruzan medianoche.

2. **Método `generarTramosLaborales()` actualizado**: Ahora consume tiempo solo durante las horas de los turnos activos:
   - Si hay turnos activos, solo consume tiempo durante sus horarios
   - Si no hay turnos activos, usa 24h completas como fallback
   - Respeta los offsets de los turnos para turnos nocturnos

#### Funcionamiento:

El método `generarTramosLaborales()` ahora:
1. Obtiene los segmentos de turnos activos para cada día usando `obtenerSegmentosLaborablesDia()`
2. Solo consume tiempo durante los horarios de los turnos activos
3. Si no hay turnos activos, usa 24h completas como fallback
4. Salta días festivos y fines de semana
5. Respeta los offsets de turnos que cruzan medianoche

**Ver implementación completa en**: `app/Http/Controllers/ProduccionController.php` (líneas 1005-1142)

## Ejemplo de Configuración

### Turno de Noche del Lunes

Para que el turno de noche del lunes comience el domingo a las 22:00:

1. **Nombre**: Noche
2. **Hora inicio**: 22:00
3. **Hora fin**: 06:00
4. **Offset días inicio**: 0 (mismo día)
5. **Offset días fin**: 1 (día siguiente)

**Resultado**:
- El turno de noche del **LUNES** se calcula como:
  - Inicio: Lunes 00:00 + offset 0 = Lunes 00:00, setear hora 22:00 = **Lunes 22:00**
  - Fin: Lunes 22:00 + offset 1 = **Martes 22:00**, setear hora 06:00 = **Martes 06:00**

**PERO** cuando la lógica pregunta "¿hay trabajo el domingo?", verá que hay un turno que empieza a las 22:00, entonces el domingo desde las 22:00 ES laborable.

## Notas Importantes

1. Solo los turnos con `activo = 1` se usan para calcular el calendario
2. Los turnos se procesan en orden (`orden` ASC, `hora_inicio` ASC)
3. Los offsets permiten manejar turnos que cruzan medianoche
4. El sistema es compatible con 1, 2 o 3 turnos diarios
5. Puedes eliminar turnos para dejar solo 2 (ej: mañana y tarde)

## Verificación

Después de implementar, verifica que:

```
Viernes 18:00 → Evento termina a las 23:00 (fin de turno tarde)
Sábado → No laborable (se salta)
Domingo 00:00-21:59 → No laborable (se salta)
Domingo 22:00 → Comienza turno de noche del lunes ✓
Lunes 06:00 → Termina turno de noche, comienza turno de mañana ✓
```
