# Calendario de Producción

## Descripción General

El calendario de producción es una vista de planificación temporal que muestra los eventos de fabricación de planillas en las diferentes máquinas. Cada evento representa un conjunto de elementos que deben fabricarse en una máquina específica.

## Funcionamiento del Sistema

### 1. Agrupación de Elementos

Los elementos se agrupan de la siguiente manera:

1. **Por Planilla y Máquina**: Todos los elementos de una planilla se agrupan por la máquina donde se fabricarán
2. **Por Orden de Planilla**: Dentro de cada grupo planilla-máquina, los elementos se sub-agrupan por `orden_planilla_id`
3. **Cada sub-grupo genera un evento independiente** en el calendario

```
Planilla #2024-5205
├── Máquina 1
│   ├── Orden 1 → Evento A
│   ├── Orden 2 → Evento B
│   └── Orden 3 → Evento C
└── Máquina 2
    └── Orden 1 → Evento D
```

### 2. Cálculo de Duración de Eventos

La duración de cada evento se calcula sumando:

- **Tiempo de fabricación**: Se obtiene de la tabla `elementos` (campo `tiempo_fabricacion`)
- **Tiempo de amarrado**: 20 minutos (1200 segundos) por cada elemento

```php
Duración = Σ (tiempo_fabricacion_elemento + 1200) por cada elemento
Duración mínima = 1 hora (3600 segundos)
```

#### Ejemplo:
```
Evento con 3 elementos:
- Elemento 1: tiempo_fabricacion = 3600s (1h)
- Elemento 2: tiempo_fabricacion = 2400s (40min)
- Elemento 3: tiempo_fabricacion = 1800s (30min)

Duración total = (3600 + 1200) + (2400 + 1200) + (1800 + 1200)
               = 4800 + 3600 + 3000
               = 11400 segundos (3.17 horas)
```

### 3. Cálculo de Inicio de Eventos

El inicio de cada evento se determina según el estado de sus elementos:

#### A. Evento con elementos fabricando/completados

Si **al menos un elemento** tiene estado `fabricando` o `fabricado`:

1. Se busca la `fecha_inicio` más antigua de las **etiquetas** relacionadas con esos elementos
2. El evento inicia en esa fecha
3. La duración se calcula dinámicamente:
   ```
   Duración = Tiempo transcurrido desde inicio + Tiempo pendiente

   Donde:
   - Tiempo transcurrido = fecha_inicio_más_antigua hasta now()
   - Tiempo pendiente = Σ (tiempo_fabricacion + 1200) de elementos pendientes
   ```

**Ejemplo:**
```
Evento con 5 elementos:
- 2 elementos completados (iniciaron el 14/11/2025 a las 10:00)
- 1 elemento fabricando (inició el 14/11/2025 a las 11:30)
- 2 elementos pendientes (tiempo total: 7200s)

Inicio del evento: 14/11/2025 10:00 (fecha más antigua)
Ahora es: 15/11/2025 14:00
Tiempo transcurrido: 28 horas = 100800s
Duración total: 100800s + 7200s = 108000s (30 horas)
Fin del evento: 16/11/2025 16:00
```

#### B. Evento completamente pendiente

Si **todos los elementos** están en estado `pendiente`:

1. El evento inicia en `inicioCola` (el fin del evento anterior en esa máquina)
2. Si `inicioCola < now()`, entonces inicia en `now()`
3. La duración es la suma total de todos los elementos

**Ejemplo:**
```
Evento completamente pendiente:
- 4 elementos pendientes
- Duración calculada: 14400s (4 horas)
- Evento anterior termina: 15/11/2025 16:00
- Inicio: 15/11/2025 16:00
- Fin: 15/11/2025 20:00
```

### 4. Encadenamiento de Eventos (Cola)

Los eventos se encadenan secuencialmente por máquina:

1. El primer evento de la máquina inicia según su estado (ver punto 3)
2. Cada evento subsiguiente inicia cuando termina el anterior
3. La variable `inicioCola` se actualiza con el fin del evento actual
4. El siguiente evento usa ese `inicioCola` como punto de partida

```
Máquina 1:
┌─────────────┐
│  Evento 1   │ Inicio: 14/11 10:00, Fin: 14/11 14:00
└─────────────┘
                ┌─────────────┐
                │  Evento 2   │ Inicio: 14/11 14:00, Fin: 14/11 18:00
                └─────────────┘
                                ┌─────────────┐
                                │  Evento 3   │ Inicio: 14/11 18:00, Fin: 15/11 02:00
                                └─────────────┘
```

### 5. Relaciones de Datos

#### Estructura de tablas:

```
Planilla
  ├── id
  ├── codigo_limpio
  ├── estado (pendiente, fabricando, fabricado)
  └── fecha_estimada_entrega

Elemento
  ├── id
  ├── codigo
  ├── planilla_id
  ├── etiqueta_sub_id
  ├── orden_planilla_id  ← Determina a qué evento pertenece
  ├── maquina_id
  ├── tiempo_fabricacion  ← Tiempo en segundos de este elemento
  ├── estado (pendiente, fabricando, fabricado)
  └── Relación: subetiquetas → hasMany(Etiqueta, 'etiqueta_sub_id', 'etiqueta_sub_id')

Etiqueta
  ├── id
  ├── etiqueta_sub_id
  ├── fecha_inicio        ← Fecha de inicio de fabricación de la etiqueta
  └── estado
```

#### Flujo de datos:

```
1. Cargar elementos: Elemento::with(['planilla', 'maquina', 'subetiquetas'])
2. Agrupar por: planilla_id + maquina_id
3. Sub-agrupar por: orden_planilla_id
4. Para cada sub-grupo:
   a. Separar elementos por estado
   b. Obtener tiempo_fabricacion de cada elemento
   c. Obtener fecha_inicio de las etiquetas relacionadas
   d. Calcular duración total (tiempo_fabricacion + amarrado)
   e. Determinar fecha de inicio (fecha_inicio más antigua de etiquetas)
   f. Generar evento(s) en calendario
```

### 6. Sistema de Turnos Configurables

El calendario respeta los **turnos configurables** definidos en la base de datos. Los turnos activos determinan los horarios laborables.

#### Características de los Turnos:

- **Turnos activos**: Solo se trabaja durante los turnos marcados como `activo = 1`
- **Horarios configurables**: Cada turno tiene `hora_inicio` y `hora_fin`
- **Offsets de días**: Los turnos pueden cruzar medianoche usando `offset_dias_inicio` y `offset_dias_fin`
- **Control en tiempo real**: Se pueden activar/desactivar turnos desde el calendario sin refrescar la página

#### Ejemplo de Turnos:

```
Turno Mañana:   06:00 - 14:00  (offset_inicio: 0, offset_fin: 0)
Turno Tarde:    14:00 - 22:00  (offset_inicio: 0, offset_fin: 0)
Turno Noche:    22:00 - 06:00  (offset_inicio: -1, offset_fin: 0)
```

**El turno noche tiene `offset_inicio = -1` porque técnicamente pertenece al día siguiente**

#### Lógica de Fin de Semana:

El sistema implementa lógica especial para los fines de semana:

**Viernes:**
- ✅ Se trabaja en todos los turnos activos
- ✅ El último evento del viernes termina al fin del último turno (ej: 22:00)

**Sábado:**
- ❌ Completamente no laborable
- ❌ No se generan segmentos para ningún turno

**Domingo:**
- ✅ Solo es laborable desde las **22:00** (inicio del turno noche del lunes)
- ❌ Los turnos mañana y tarde NO se ejecutan el domingo
- ✅ El turno noche (22:00-06:00) SÍ se ejecuta porque tiene `offset_dias_inicio = -1`

**Ejemplo de flujo de fin de semana:**
```
Viernes 20:00 → Evento trabaja hasta 22:00 (fin turno tarde)
Sábado        → Completamente no laborable (se salta)
Domingo 00:00 → No laborable hasta las 22:00
Domingo 22:00 → ✅ Comienza turno noche del lunes
Lunes 06:00   → Continúa con turno mañana
```

### 7. Tramos Laborales

La función `generarTramosLaborales()` divide un evento en múltiples tramos respetando:

- ✅ **Horarios de turnos activos**: Solo consume tiempo durante turnos con `activo = 1`
- ✅ **Continuidad dentro del turno**: Si un evento termina a las 10:00 (dentro del turno mañana), el siguiente empieza a las 10:00
- ✅ **Saltos fuera de turno**: Si un evento termina fuera de horario de turnos, el siguiente espera al próximo turno
- ✅ **Fines de semana**: Respeta la lógica especial de fin de semana
- ✅ **Festivos**: Salta días festivos definidos en la base de datos

**Ejemplo con solo turno mañana activo (06:00-14:00):**
```
Evento de 10h iniciando lunes 08:00:
┌────────────┐              ┌────────────┐
│ Lunes      │              │ Martes     │
│ 08:00-14:00│              │ 06:00-10:00│
│ (6 horas)  │              │ (4 horas)  │
└────────────┘              └────────────┘
    Tramo 1                     Tramo 2

Lunes 14:00-Martes 06:00 → No laborable (fuera de turno)
```

**Ejemplo con 3 turnos activos (mañana, tarde, noche):**
```
Evento de 20h iniciando lunes 10:00:
┌────────────┬────────────┬────────────┐  ┌────────────┐
│ Lunes      │ Lunes      │ Lunes      │  │ Martes     │
│ 10:00-14:00│ 14:00-22:00│ 22:00-06:00│  │ 06:00-10:00│
│ (4h)       │ (8h)       │ (8h)       │  │ (4h)       │
└────────────┴────────────┴────────────┘  └────────────┘
   Tramo 1      Tramo 2      Tramo 3         Tramo 4

NO hay espacios sin trabajar - los eventos son continuos
```

**Ejemplo cruzando fin de semana:**
```
Evento iniciando viernes 18:00 con 12h de duración:
┌────────────┐              ┌────────────┐  ┌────────────┐
│ Viernes    │              │ Domingo    │  │ Lunes      │
│ 18:00-22:00│              │ 22:00-06:00│  │ 06:00-10:00│
│ (4 horas)  │              │ (8 horas)  │  │ (4 horas)  │
└────────────┘              └────────────┘  └────────────┘
   Tramo 1                     Tramo 2         Tramo 3

Viernes 22:00 → Fin del turno tarde
Sábado → Se salta completamente
Domingo 00:00-21:59 → No laborable
Domingo 22:00 → Comienza turno noche del lunes ✅
```

#### Control de Turnos en Tiempo Real

El calendario incluye un panel de control de turnos que permite:

- ✅ Ver todos los turnos configurados con sus horarios
- ✅ Activar/desactivar turnos con un click
- ✅ Ver feedback visual inmediato del estado
- ✅ Re-renderizar eventos automáticamente sin refrescar la página

**Acceso:** Panel de filtros → Sección "⏰ Turnos Activos"

#### Bug Corregido: Referencias Compartidas de Carbon

**Problema anterior:**
Cuando un evento terminaba, las fechas se modificaban incorrectamente debido a referencias compartidas de objetos Carbon en PHP.

**Síntoma:**
- Con solo turno mañana (06:00-14:00) activo
- Evento que debería terminar a las 14:00
- Aparecía terminando a medianoche (00:00)

**Causa:**
```php
// Incorrecto - comparte referencia
$cursor = $end;
```

**Solución:**
```php
// Correcto - crea nueva copia
$cursor = $end->copy();
```

**Ubicación del fix:** `ProduccionController.php` líneas 1129 y 1169

### 7. Visualización en Calendario

Cada evento muestra:

- **Título**: Código de planilla
- **Color de fondo**:
  - Gris (`#9e9e9e`): Planilla sin revisar
  - Verde (`#22c55e`): Dentro de plazo de entrega
  - Rojo (`#ef4444`): Fuera de plazo de entrega
- **Progreso**: % de elementos completados
- **Propiedades extendidas**:
  - Obra, cliente, código de planilla
  - Estado, duración en horas
  - Fecha de entrega, fin programado
  - Lista de códigos y IDs de elementos

### 8. Estados del Sistema

```
┌──────────────┐
│  PENDIENTE   │ → Todos los elementos en estado pendiente
└──────────────┘   Inicio: inicioCola o now()
       ↓           Duración: suma total de elementos

┌──────────────┐
│  FABRICANDO  │ → Al menos 1 elemento fabricando/completado
└──────────────┘   Inicio: fecha_inicio más antigua de etiquetas
       ↓           Duración: dinámica (transcurrido + pendiente)

┌──────────────┐
│  FABRICADO   │ → Todos los elementos fabricados
└──────────────┘   Inicio: fecha_inicio más antigua de etiquetas
                   Duración: tiempo real transcurrido
```

## Consideraciones Técnicas

### Performance

1. **Eager Loading**: Se cargan las relaciones `['planilla', 'maquina', 'subetiquetas']` para evitar N+1 queries
2. **Agrupación en memoria**: Los elementos se agrupan usando Collections de Laravel
3. **Cálculos optimizados**: Se usan funciones `sum()` y `map()` de Collections

### Manejo de Errores

1. Si un elemento no tiene etiqueta relacionada: tiempo_fabricacion = 1200s (20 min)
2. Si un evento no genera tramos: se registra warning y se omite
3. Duración mínima garantizada: 1 hora (3600s)

### Timezone

- Todas las fechas se convierten a la timezone configurada en `config('app.timezone')`
- Por defecto: `Europe/Madrid`

## Flujo Completo de Ejemplo

```
INPUT:
Planilla #2024-5205 en Máquina 1
- Elemento 1: orden_planilla_id=1, estado=fabricado, tiempo_fabricacion=3600s, etiqueta.fecha_inicio=14/11 10:00
- Elemento 2: orden_planilla_id=1, estado=fabricando, tiempo_fabricacion=2400s, etiqueta.fecha_inicio=14/11 11:00
- Elemento 3: orden_planilla_id=1, estado=pendiente, tiempo_fabricacion=1800s
- Elemento 4: orden_planilla_id=2, estado=pendiente, tiempo_fabricacion=4800s
- Elemento 5: orden_planilla_id=2, estado=pendiente, tiempo_fabricacion=3000s

PROCESO:
1. Sub-grupo orden_planilla_id=1 (Elementos 1,2,3):
   - Fecha inicio más antigua: 14/11 10:00
   - Ahora: 15/11 14:00
   - Tiempo transcurrido: 28h = 100800s
   - Elementos pendientes: 1 (1800s + 1200s = 3000s)
   - Duración total: 100800s + 3000s = 103800s (28.83h)
   - Evento A: 14/11 10:00 → 15/11 14:50

2. Sub-grupo orden_planilla_id=2 (Elementos 4,5):
   - Todos pendientes
   - Inicio: 15/11 14:50 (fin del Evento A)
   - Duración: (4800+1200) + (3000+1200) = 10200s (2.83h)
   - Evento B: 15/11 14:50 → 15/11 17:40

OUTPUT:
Calendario muestra 2 eventos para Planilla #2024-5205 en Máquina 1
```

## Notas Adicionales

- El sistema respeta horarios laborales y excluye festivos
- Los eventos se actualizan dinámicamente al recargar la página
- El estado de "fabricando" hace que el evento se alargue hasta now() + tiempo pendiente
- Cada sub-grupo (`orden_planilla_id`) es completamente independiente en cálculo de tiempos

---

## Corte Visual de Eventos por Turnos Desactivados

### Funcionalidad

Cuando se desactiva un turno en los filtros superiores del calendario, los eventos se "cortan" visualmente en la franja horaria de ese turno. Esto simula que durante ese tiempo no se trabaja y los tiempos de fabricación se extienden al siguiente turno activo.

### Implementación

**Archivo:** `app/Http/Controllers/ProduccionController.php`

**Método:** `generarEventosMaquinas()`

```php
// AGRUPAR TRAMOS CONSECUTIVOS (cortar en turnos desactivados, fines de semana y festivos)
// Si hay más de 2 horas entre el fin de un tramo y el inicio del siguiente,
// consideramos que hay un corte (turno desactivado, fin de semana, festivo)
$gruposTramos = [];
$grupoActual = [];
$maxGapHoras = 2; // Gap máximo: 2 horas para detectar turnos desactivados
```

### Lógica de Corte

1. El servicio `FinProgramadoService` genera tramos laborales basados en turnos activos (`activo = true`)
2. Si un turno está desactivado, no se genera tramo para esa franja horaria
3. Se crea un "gap" entre tramos que excede las 2 horas máximas permitidas
4. Cada grupo de tramos consecutivos se convierte en un evento separado en el calendario

### Ejemplo Visual

```
Con turno tarde (14:00-22:00) ACTIVO:
┌─────────────────────────────────────────────┐
│            Evento continuo                  │
│   06:00 ─────────────────────────── 22:00   │
└─────────────────────────────────────────────┘

Con turno tarde (14:00-22:00) DESACTIVADO:
┌───────────────────┐        ┌───────────────────┐
│    Evento 1       │        │    Evento 2       │
│  06:00 ── 14:00   │  GAP   │  06:00 ── 14:00   │
└───────────────────┘ (16h)  └───────────────────┘
      Día 1                        Día 2
```

---

## Fix: Posicionamiento Correcto de Eventos en Vista de Horas Extendidas

### Problema

La vista de "horas extendidas" (`slotMaxTime: 168:00:00`) tiene un bug intermitente donde los eventos no se posicionan correctamente en el eje Y (tiempo). Los eventos pueden aparecer desfasados ~70 minutos de su hora real.

**Síntoma:** Al abrir la consola del navegador (F12), el calendario se redimensiona y los eventos van automáticamente a su posición correcta.

**Causa:** FullCalendar no recalcula correctamente las posiciones de los eventos en el render inicial cuando se usa una vista hackeada de horas extendidas.

### Solución

Simular el comportamiento de resize que ocurre al abrir F12, forzando a FullCalendar a recalcular las posiciones de los eventos.

**Archivo:** `resources/views/produccion/maquinas.blade.php`

```javascript
// Fix para problema de posicionamiento: simular resize como al abrir F12
function forzarRecalculoPosiciones() {
    const calendario = document.getElementById('calendario');
    if (!calendario || !window.calendar) return;

    // Guardar ancho original
    const anchoOriginal = calendario.style.width;

    // Reducir ancho temporalmente (simula abrir F12)
    calendario.style.width = (calendario.offsetWidth - 1) + 'px';
    window.calendar.updateSize();

    // Restaurar ancho original
    requestAnimationFrame(() => {
        calendario.style.width = anchoOriginal || '';
        window.calendar.updateSize();
    });
}

// Ejecutar después de cargar eventos
calendar.on('eventsSet', function() {
    setTimeout(forzarRecalculoPosiciones, 100);
    setTimeout(forzarRecalculoPosiciones, 500);
});
```

### Por qué funciona

1. Al cambiar el ancho del contenedor, FullCalendar dispara un recálculo interno de posiciones
2. `updateSize()` fuerza la actualización de dimensiones del calendario
3. Al restaurar el ancho original inmediatamente después, se recalculan las posiciones correctamente
4. Se ejecuta múltiples veces (100ms y 500ms) para cubrir diferentes timings de carga de eventos

### Notas Importantes

- Este fix es necesario debido al uso no estándar de FullCalendar con `slotMaxTime` extendido
- El problema es intermitente y depende del timing de carga
- Sin este fix, los eventos pueden aparecer desfasados visualmente aunque los datos sean correctos

---

## Fix: Eventos Pendientes Iniciando en el Próximo Turno Activo

### Problema

Cuando un evento pendiente (primera en cola) se genera fuera del horario de turnos activos, el evento debería comenzar en el siguiente turno disponible, no en la hora actual (`now()`).

**Ejemplo del problema:**
- Son las 22:23 y el turno de noche está desactivado
- El próximo turno activo es mañana a las 06:00
- El evento debería empezar a las 06:00, no a las 22:23

### Solución

La función `generarTramosLaborales()` en `FinProgramadoService` detecta si la fecha de inicio está fuera de horario laborable y la ajusta automáticamente:

**Archivo:** `app/Services/FinProgramadoService.php`

```php
// Verificar si el inicio está dentro de horario laborable
$segmentosInicio = $this->obtenerSegmentosLaborablesDia($inicio);
$segmentosDiaAnterior = $this->obtenerSegmentosLaborablesDia($inicio->copy()->subDay());
$todosSegmentos = array_merge($segmentosDiaAnterior, $segmentosInicio);
$dentroDeSegmento = false;

foreach ($todosSegmentos as $seg) {
    if ($inicio->gte($seg['inicio']) && $inicio->lt($seg['fin'])) {
        $dentroDeSegmento = true;
        break;
    }
}

// Si el inicio NO está dentro de un segmento laborable, mover al siguiente
if (!$dentroDeSegmento) {
    $inicio = $this->siguienteLaborableInicio($inicio);
}
```

### Recarga de Turnos

Para garantizar que siempre se use el estado actual de los turnos (especialmente después de que el usuario active/desactive un turno), el servicio incluye:

```php
/**
 * Fuerza la recarga de turnos desde la base de datos
 */
public function recargarTurnos(): void
{
    $this->turnosActivos = Turno::where('activo', true)->get();
}
```

El método `init()` ahora llama a `recargarTurnos()` en lugar de `cargarTurnos()` para asegurar datos frescos:

```php
public function init(): self
{
    $this->cargarFestivos();
    // Forzar recarga de turnos para obtener estado actual
    $this->recargarTurnos();
    return $this;
}
```

### Flujo de Ajuste de Inicio

```
1. Evento pendiente detectado → $fechaInicio = Carbon::now()
2. Llamar generarTramosLaborales($fechaInicio, $duracion)
3. Verificar si $fechaInicio está dentro de un segmento laborable
4. Si NO está:
   a. Llamar siguienteLaborableInicio($fechaInicio)
   b. Buscar el próximo turno activo
   c. Retornar el inicio de ese turno
5. El primer tramo generado comienza en el turno ajustado
6. El evento usa el inicio del primer tramo
```

### Ejemplo

```
Situación: 22:23 Lunes, turno noche DESACTIVADO
Turnos activos: Mañana (06:00-14:00), Tarde (14:00-22:00)

Proceso:
1. $fechaInicio = 22:23 Lunes
2. Segmentos del día: [06:00-14:00, 14:00-22:00]
3. ¿22:23 está en algún segmento? NO
4. Llamar siguienteLaborableInicio(22:23 Lunes)
5. Buscar siguiente segmento...
   - 22:23 > 14:00 (fin mañana) → continuar
   - 22:23 > 22:00 (fin tarde) → continuar
   - Avanzar a Martes 00:00
   - 00:00 < 06:00 (inicio mañana) → ENCONTRADO
6. Retorna: Martes 06:00
7. Primer tramo: Martes 06:00 → ...
8. Evento: start = "Martes 06:00"
```
