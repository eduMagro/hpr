# Creación de Paquetes y Asociación Automática a Salidas

## Índice

1. [Resumen General](#resumen-general)
2. [Archivos Involucrados](#archivos-involucrados)
3. [Flujo Completo Paso a Paso](#flujo-completo-paso-a-paso)
4. [Validaciones en la Creación del Paquete](#validaciones-en-la-creación-del-paquete)
5. [Lógica de Fechas de Entrega](#lógica-de-fechas-de-entrega)
6. [Asociación Automática a Salidas (Observer)](#asociación-automática-a-salidas-observer)
7. [Modelos y Relaciones](#modelos-y-relaciones)
8. [Diagramas de Flujo](#diagramas-de-flujo)
9. [Casos de Uso y Ejemplos](#casos-de-uso-y-ejemplos)

---

## Resumen General

El sistema permite crear paquetes desde el componente **tipo-normal** (máquinas de fabricación). Cuando se crea un paquete, este se asocia automáticamente a una salida mediante un Observer que:

1. Determina la fecha de entrega efectiva del paquete
2. Busca una salida existente con la misma `obra_id` + `fecha_salida`
3. Si no existe o está llena, crea una nueva salida automáticamente

### Concepto Clave: Partición de Planillas

En **Planificación** se pueden crear dos eventos independientes para la misma planilla asignando diferentes `fecha_entrega` a los elementos. Esto permite "partir" una planilla en dos entregas distintas que irán a salidas diferentes.

---

## Archivos Involucrados

| Archivo | Ubicación | Función |
|---------|-----------|---------|
| **tipo-normal.blade.php** | `resources/views/components/maquinas/tipo/` | UI del tab "Crear Paquete" (líneas 343-389) |
| **PaqueteController.php** | `app/Http/Controllers/` | Método `store()` - validación y creación (líneas 269-537) |
| **PaqueteObserver.php** | `app/Observers/` | Asociación automática a salida (líneas 24-95) |
| **Paquete.php** | `app/Models/` | Modelo con generación de código único (líneas 44-122) |
| **Salida.php** | `app/Models/` | Modelo de salidas con relación a paquetes |
| **SalidaPaquete.php** | `app/Models/` | Tabla pivote de la relación N:M |
| **Elemento.php** | `app/Models/` | Modelo con campo `fecha_entrega` |
| **gestionPaquetes.js** | `resources/js/paquetesJs/` | Actualización del DOM post-creación |

---

## Flujo Completo Paso a Paso

### Paso 1: Interfaz de Usuario (tipo-normal)

**Archivo:** `tipo-normal.blade.php` (líneas 343-389)

El operario:
1. Escanea etiquetas con el lector QR
2. Las etiquetas se agregan a la lista `itemsList`
3. Hace clic en "Crear Paquete"

```html
<!-- Tab: Crear Paquete -->
<input type="text" id="qrItem" placeholder="Escanear etiqueta">
<ul id="itemsList"> <!-- Lista de etiquetas en el carro --> </ul>
<button id="crearPaqueteBtn">Crear Paquete</button>
```

### Paso 2: Validación y Creación (PaqueteController::store)

**Archivo:** `PaqueteController.php` (líneas 269-537)

#### 2.1 Validación de Request
```php
$request->validate([
    'items'             => 'required|array|min:1',
    'items.*.id'        => 'required|string',
    'items.*.type'      => 'required|in:etiqueta,elemento',
    'maquina_id'        => 'required|integer|exists:maquinas,id',
]);
```

#### 2.2 Separación de Items
```php
$etiquetasSubIds = collect($request->items)
    ->where('type', 'etiqueta')
    ->pluck('id')->toArray();

$elementosIds = collect($request->items)
    ->where('type', 'elemento')
    ->pluck('id')->toArray();

$todosElementos = $elementosDesdeEtiquetas->merge($elementosDirectos);
```

#### 2.3 Validaciones de Negocio

Ver sección [Validaciones en la Creación del Paquete](#validaciones-en-la-creación-del-paquete).

#### 2.4 Creación del Paquete
```php
$paquete = $this->crearPaquete(
    $planilla->id,           // planilla_id
    $ubicacion?->id,         // ubicacion_id (null para grúa)
    $pesoTotal,              // peso total
    $maquina->obra_id,       // nave_id
    $maquina->id             // maquina_id
);
```

#### 2.5 Generación de Código Único
**Formato:** `P{YY}{MM}nnnn`
**Ejemplo:** `P2501/0001` (Enero 2025, paquete 1)

### Paso 3: Asociación Automática (PaqueteObserver)

Ver sección [Asociación Automática a Salidas](#asociación-automática-a-salidas-observer).

### Paso 4: Actualización del DOM

**Archivo:** `gestionPaquetes.js`

```javascript
window.addEventListener("paquete:creado", (e) => {
    const { codigoPaquete, etiquetaIds } = e.detail;

    etiquetaIds.forEach((etiquetaId) => {
        actualizarEstadoEtiquetaPaquete(etiquetaId, "en-paquete", codigoPaquete);
    });
});
```

- Cambia color de fondo de etiqueta a morado (#e3e4FA)
- Muestra código del paquete junto a la etiqueta

---

## Validaciones en la Creación del Paquete

### 3.1 Validación de Obra Única

**Líneas:** 313-336

Todas las etiquetas deben pertenecer a la **misma obra**.

```php
$obrasUnicas = $etiquetasParaValidar
    ->pluck('planilla.obra_id')
    ->filter()
    ->unique();

if ($obrasUnicas->count() > 1) {
    // ERROR: "las etiquetas pertenecen a obras diferentes"
}
```

### 3.2 Validación de Fecha de Entrega

**Líneas:** 338-400

Esta es la validación más compleja. Considera tanto la `fecha_entrega` de los elementos como la `fecha_estimada_entrega` de las planillas.

Ver sección [Lógica de Fechas de Entrega](#lógica-de-fechas-de-entrega).

### 3.3 Validación de Peso Máximo

**Líneas:** 361-371

El peso total no puede exceder **1350 kg**.

```php
$pesoMaximo = 1350;
$pesoTotalCalculado = $todosElementos->sum(fn($e) => $e->peso ?? 0);

if ($pesoTotalCalculado > $pesoMaximo) {
    // ERROR: "el peso total excede el límite máximo"
}
```

---

## Lógica de Fechas de Entrega

### Concepto

Los elementos pueden tener su propia `fecha_entrega` asignada en Planificación, independiente de la `fecha_estimada_entrega` de la planilla. Esto permite partir una planilla en múltiples entregas.

### Jerarquía de Fechas

1. **Fecha del elemento** (`elemento.fecha_entrega`) - Prioridad alta
2. **Fecha de la planilla** (`planilla.fecha_estimada_entrega`) - Fallback

### Reglas de Validación

| Caso | Condición | Resultado |
|------|-----------|-----------|
| **1** | Mezcla: algunos elementos con fecha, otros sin | **ERROR** |
| **2** | Todos con fecha pero diferentes | **ERROR** |
| **3** | Todos sin fecha (`null`) | Valida `fecha_estimada_entrega` de planillas |
| **4** | Todos con la misma fecha | **OK** |

### Implementación

```php
// Obtener fechas de todos los elementos
$fechasElementos = $todosElementos->map(function ($elemento) {
    if ($elemento->fecha_entrega) {
        return $elemento->fecha_entrega instanceof Carbon
            ? $elemento->fecha_entrega->format('Y-m-d')
            : $elemento->fecha_entrega;
    }
    return null;
});

$elementosConFecha = $fechasElementos->filter()->unique()->values();
$elementosSinFecha = $fechasElementos->filter(fn($f) => $f === null)->count();
$totalElementos = $todosElementos->count();
```

### Caso 1: Mezcla de elementos con y sin fecha

```php
if ($elementosSinFecha > 0 && $elementosConFecha->count() > 0) {
    // ERROR: "algunos elementos tienen fecha de entrega asignada y otros no"
}
```

**Mensaje de error:**
> "No se puede crear el paquete: algunos elementos tienen fecha de entrega asignada y otros no. Los elementos sin fecha se rigen por la fecha de la planilla, lo que resultaría en salidas diferentes."

### Caso 2: Todos con fecha pero diferentes

```php
if ($elementosConFecha->count() > 1) {
    // ERROR: "los elementos tienen diferentes fechas de entrega"
}
```

**Mensaje de error:**
> "No se puede crear el paquete: los elementos tienen diferentes fechas de entrega (20/01/2025, 25/01/2025). Un paquete solo puede contener elementos con la misma fecha de entrega."

### Caso 3: Todos sin fecha

```php
if ($elementosSinFecha === $totalElementos) {
    // Validar que las planillas tengan la misma fecha_estimada_entrega
    $fechasPlanillas = $etiquetasParaValidar
        ->pluck('planilla.fecha_estimada_entrega')
        ->filter()
        ->unique();

    if ($fechasPlanillas->count() > 1) {
        // ERROR: "las planillas tienen diferentes fechas de entrega"
    }
}
```

### Caso 4: Todos con la misma fecha

Si `$elementosConFecha->count() === 1`, todos los elementos tienen la misma fecha y la validación pasa.

---

## Asociación Automática a Salidas (Observer)

### Disparo del Observer

**Archivo:** `PaqueteObserver.php`

```php
public function created(Paquete $paquete): void
{
    $this->asociarASalidaAutomatica($paquete);
}
```

El Observer se dispara automáticamente cuando se crea un paquete.

### Flujo de Asociación

#### 1. Obtener obra_id

```php
$planilla = $paquete->planilla;
$obraId = $planilla->obra_id;

// Verificar que la obra existe
$obraExiste = Obra::where('id', $obraId)->exists();
```

#### 2. Determinar fecha de entrega

```php
private function determinarFechaEntrega(Paquete $paquete, $planilla): ?string
{
    $paquete->load('etiquetas.elementos');

    // Prioridad: fecha_entrega de elementos
    foreach ($paquete->etiquetas as $etiqueta) {
        foreach ($etiqueta->elementos as $elemento) {
            if ($elemento->fecha_entrega) {
                return $elemento->fecha_entrega->toDateString();
            }
        }
    }

    // Fallback: fecha_estimada_entrega de planilla
    return $planilla->getRawOriginal('fecha_estimada_entrega');
}
```

#### 3. Buscar salida disponible

```php
private function buscarSalidaDisponible(int $obraId, string $fechaEntrega, float $pesoPaquete): ?Salida
{
    $salidas = Salida::where('obra_id', $obraId)
        ->whereDate('fecha_salida', $fechaEntrega)
        ->where('estado', '!=', 'completada')
        ->orderBy('created_at', 'asc')
        ->get();

    foreach ($salidas as $salida) {
        $pesoActual = $this->calcularPesoSalida($salida);
        $limitePeso = $this->obtenerLimitePeso($salida);

        if (($pesoActual + $pesoPaquete) <= $limitePeso) {
            return $salida;
        }
    }

    return null;
}
```

**Criterios de búsqueda:**
- Misma `obra_id`
- Misma `fecha_salida`
- Estado diferente de 'completada'
- Peso disponible suficiente

**Límite de peso:**
- Si tiene camión: capacidad del camión
- Sin camión: 28 toneladas por defecto

#### 4. Crear nueva salida si no existe

```php
private function crearNuevaSalida(int $obraId, string $fechaSalida): Salida
{
    $salida = Salida::create([
        'obra_id' => $obraId,
        'fecha_salida' => $fechaSalida,
        'estado' => 'pendiente',
        'user_id' => auth()->id(),
    ]);

    // Código: AS{YY}/{id_formateado}
    // Ejemplo: AS25/0001
    $codigoSalida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);
    $salida->codigo_salida = $codigoSalida;
    $salida->save();

    return $salida;
}
```

#### 5. Asociar paquete a salida

```php
// Insertar en tabla pivote
$paquete->salidas()->syncWithoutDetaching([$salidaAsignada->id]);

// Actualizar estado del paquete
$paquete->estado = 'asignado_a_salida';
$paquete->saveQuietly();

// Crear registro en salida_cliente
$this->asegurarSalidaCliente($salidaAsignada, $planilla);
```

---

## Modelos y Relaciones

### Paquete → Salidas (N:M)

**Archivo:** `Paquete.php`

```php
public function salidas()
{
    return $this->belongsToMany(
        Salida::class,
        'salidas_paquetes',
        'paquete_id',
        'salida_id'
    );
}

// Accessor para obtener salida principal
public function getSalidaIdAttribute()
{
    return DB::table('salidas_paquetes')
        ->where('paquete_id', $this->id)
        ->value('salida_id');
}
```

### Salida → Paquetes (N:M)

**Archivo:** `Salida.php`

```php
public function paquetes()
{
    return $this->belongsToMany(
        Paquete::class,
        'salidas_paquetes',
        'salida_id',
        'paquete_id'
    );
}
```

### Tabla Pivote: salidas_paquetes

**Migración:** `2025_12_16_095413_create_salidas_paquetes_table.php`

```php
Schema::create('salidas_paquetes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('salida_id')->constrained('salidas');
    $table->foreignId('paquete_id')->constrained('paquetes');
    $table->timestamps();
});
```

### Elemento

**Archivo:** `Elemento.php`

```php
protected $fillable = [
    // ...
    'fecha_entrega'
];

protected $casts = [
    'fecha_entrega' => 'date',
];
```

---

## Diagramas de Flujo

### Flujo General de Creación

```
┌─────────────────────────────────────┐
│  Usuario en tipo-normal             │
│  Escanea etiquetas con QR           │
│  Hace clic: "Crear Paquete"         │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ PaqueteController::store()              │
│ • Validar obra única                    │
│ • Validar fecha de entrega              │
│ • Validar peso <= 1350 kg               │
│ • Generar código: P{YY}{MM}nnnn         │
│ • Crear registro Paquete                │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ OBSERVER: PaqueteObserver::created()    │
│ Dispara automáticamente                 │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Determinar fecha de entrega:            │
│ • Prioridad: elemento.fecha_entrega     │
│ • Fallback: planilla.fecha_estimada     │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ Buscar SALIDA existente:                │
│ • obra_id = planilla.obra_id            │
│ • fecha_salida = fecha_entrega          │
│ • estado != 'completada'                │
│ • peso disponible suficiente            │
└──────────────┬──────────────────────────┘
               │
      ┌────────┴────────┐
      ▼                 ▼
  ¿EXISTE?         NO EXISTE
      │                 │
      ▼                 ▼
   USA LA         CREA NUEVA
   SALIDA         SALIDA
   EXISTENTE      AS{YY}/nnnn
      │                 │
      └────────┬────────┘
               ▼
┌─────────────────────────────────────────┐
│ Asociar paquete a salida:               │
│ • INSERT salidas_paquetes               │
│ • UPDATE paquete.estado = 'asignado'    │
│ • INSERT/UPDATE salida_cliente          │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ JavaScript: actualizar DOM              │
│ • Evento "paquete:creado"               │
│ • Actualizar color etiqueta             │
│ • Mostrar código paquete                │
└─────────────────────────────────────────┘
```

### Flujo de Validación de Fechas

```
          Elementos a paquetizar
                    │
                    ▼
    ┌───────────────────────────────┐
    │ Obtener fecha_entrega de      │
    │ cada elemento                 │
    └───────────────┬───────────────┘
                    │
                    ▼
    ┌───────────────────────────────┐
    │ ¿Todos fecha_entrega = null?  │
    └───────────────┬───────────────┘
                    │
         ┌──────────┴──────────┐
         ▼                     ▼
        SÍ                    NO
         │                     │
         ▼                     ▼
┌─────────────────┐   ┌─────────────────────┐
│ Validar que     │   │ ¿Hay mezcla de      │
│ todas las       │   │ null y fechas?      │
│ planillas       │   └──────────┬──────────┘
│ tengan la misma │              │
│ fecha_estimada  │     ┌────────┴────────┐
└────────┬────────┘     ▼                 ▼
         │             SÍ                NO
         │              │                 │
         │              ▼                 ▼
         │         ┌────────┐   ┌─────────────────┐
         │         │ ERROR  │   │ ¿Todas las      │
         │         │ Caso 1 │   │ fechas iguales? │
         │         └────────┘   └────────┬────────┘
         │                               │
         │                      ┌────────┴────────┐
         │                      ▼                 ▼
         │                     SÍ                NO
         │                      │                 │
         │                      ▼                 ▼
         │                 ┌────────┐       ┌────────┐
         │                 │   OK   │       │ ERROR  │
         │                 │ Caso 4 │       │ Caso 2 │
         │                 └────────┘       └────────┘
         │
         ▼
┌─────────────────┐
│ ¿Fechas de      │
│ planillas       │
│ iguales?        │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
   SÍ        NO
    │         │
    ▼         ▼
┌──────┐  ┌────────┐
│  OK  │  │ ERROR  │
│Caso 3│  │ Caso 3 │
└──────┘  └────────┘
```

---

## Casos de Uso y Ejemplos

### Ejemplo 1: Planilla sin partición (caso simple)

```
Planilla P001
├── fecha_estimada_entrega: 22/01/2025
├── Elemento 1 → fecha_entrega: null
├── Elemento 2 → fecha_entrega: null
└── Elemento 3 → fecha_entrega: null

Operario escanea: Elemento 1, 2, 3
Resultado: Paquete creado, asociado a salida del 22/01/2025
```

### Ejemplo 2: Planilla partida en Planificación

```
Planilla P001
├── fecha_estimada_entrega: 22/01/2025
├── Elemento 1 → fecha_entrega: 20/01/2025  ┐
├── Elemento 2 → fecha_entrega: 20/01/2025  ├─ Evento 1
├── Elemento 3 → fecha_entrega: 20/01/2025  ┘
├── Elemento 4 → fecha_entrega: 25/01/2025  ┐
├── Elemento 5 → fecha_entrega: 25/01/2025  ├─ Evento 2
└── Elemento 6 → fecha_entrega: 25/01/2025  ┘

Operario escanea: Elemento 1, 2, 3
Resultado: Paquete creado, asociado a salida del 20/01/2025

Operario escanea: Elemento 4, 5, 6
Resultado: Paquete creado, asociado a salida del 25/01/2025
```

### Ejemplo 3: Error por mezcla de fechas

```
Planilla P001
├── fecha_estimada_entrega: 22/01/2025
├── Elemento 1 → fecha_entrega: 20/01/2025
├── Elemento 2 → fecha_entrega: null
└── Elemento 3 → fecha_entrega: 20/01/2025

Operario escanea: Elemento 1, 2, 3
Resultado: ERROR
Mensaje: "algunos elementos tienen fecha de entrega asignada y otros no"
```

### Ejemplo 4: Error por fechas diferentes

```
Planilla P001
├── fecha_estimada_entrega: 22/01/2025
├── Elemento 1 → fecha_entrega: 20/01/2025
├── Elemento 2 → fecha_entrega: 25/01/2025
└── Elemento 3 → fecha_entrega: 20/01/2025

Operario escanea: Elemento 1, 2, 3
Resultado: ERROR
Mensaje: "los elementos tienen diferentes fechas de entrega (20/01/2025, 25/01/2025)"
```

### Ejemplo 5: Múltiples planillas, todas sin fecha en elementos

```
Planilla P001 (fecha_estimada_entrega: 22/01/2025)
├── Elemento 1 → fecha_entrega: null

Planilla P002 (fecha_estimada_entrega: 22/01/2025)
├── Elemento 2 → fecha_entrega: null

Operario escanea: Elemento 1, 2
Resultado: Paquete creado (misma obra validada), asociado a salida del 22/01/2025
```

### Ejemplo 6: Múltiples planillas con fechas diferentes

```
Planilla P001 (fecha_estimada_entrega: 22/01/2025)
├── Elemento 1 → fecha_entrega: null

Planilla P002 (fecha_estimada_entrega: 25/01/2025)
├── Elemento 2 → fecha_entrega: null

Operario escanea: Elemento 1, 2
Resultado: ERROR
Mensaje: "las planillas tienen diferentes fechas de entrega"
```

---

## Notas Técnicas

### Códigos Generados

| Entidad | Formato | Ejemplo |
|---------|---------|---------|
| Paquete | `P{YY}{MM}nnnn` | P2501/0001 |
| Salida Automática | `AS{YY}/nnnn` | AS25/0001 |

### Límites

| Parámetro | Valor |
|-----------|-------|
| Peso máximo por paquete | 1,350 kg |
| Peso máximo por salida (sin camión) | 28,000 kg |
| Peso máximo por salida (con camión) | Capacidad del camión |

### Estados del Paquete

- `pendiente` - Recién creado (antes del Observer)
- `asignado_a_salida` - Después de asociarse a una salida
- Otros estados según el flujo de negocio

### Estados de la Salida

- `pendiente` - Creada automáticamente, esperando más paquetes
- `completada` - Ya fue despachada (no acepta más paquetes)

---

*Documento generado: Enero 2025*
