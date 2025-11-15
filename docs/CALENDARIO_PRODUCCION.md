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

### 6. Tramos Laborales

La función `generarTramosLaborales()` divide un evento en múltiples tramos si cruza:

- Horarios no laborales
- Fines de semana
- Festivos

**Ejemplo:**
```
Evento de 10 horas iniciando viernes 17:00:
┌────────────┐              ┌────────────┐
│ Viernes    │              │ Lunes      │
│ 17:00-19:00│              │ 08:00-16:00│
│ (2 horas)  │              │ (8 horas)  │
└────────────┘              └────────────┘
    Tramo 1                     Tramo 2
```

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
