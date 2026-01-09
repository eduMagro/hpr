# Sistema de Aprobacion de Planillas

## Descripcion General

El sistema de aprobacion de planillas permite gestionar el flujo de trabajo desde la creacion de una planilla en Ferrawin hasta su entrada en produccion. Introduce un paso intermedio de "aprobacion" por parte del tecnico de despiece antes de que la planilla pueda ser revisada y entrar en produccion.

## Flujo de Trabajo

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  FERRAWIN   │───>│  IMPORTADA  │───>│  APROBADA   │───>│  REVISADA   │───>│ PRODUCCION  │
│  (ZFECHA)   │    │ (created_at)│    │(aprobada_at)│    │(revisada_at)│    │             │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
                                             │
                                             ▼
                                   fecha_estimada_entrega
                                    = aprobada_at + 7 dias
```

### Estados de una Planilla

| Estado | Descripcion |
|--------|-------------|
| **Sin aprobar** | Planilla importada pero pendiente de confirmacion del tecnico |
| **Aprobada** | Tecnico confirmo la planilla, fecha de entrega establecida |
| **Revisada** | Planilla lista para entrar en produccion |
| **En produccion** | Planilla siendo fabricada |

## Campos de Base de Datos

### Tabla `planillas`

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `fecha_creacion_ferrawin` | datetime | Fecha de creacion en Ferrawin (ZFECHA de ORD_HEAD) |
| `aprobada` | boolean | Indica si la planilla fue aprobada |
| `aprobada_por_id` | foreignId | ID del usuario que aprobo la planilla |
| `aprobada_at` | datetime | Fecha y hora de la aprobacion |

### Migracion

```php
// database/migrations/2026_01_09_174107_add_aprobacion_fields_to_planillas_table.php

Schema::table('planillas', function (Blueprint $table) {
    $table->datetime('fecha_creacion_ferrawin')->nullable()->after('codigo');
    $table->boolean('aprobada')->default(false)->after('revisada_at');
    $table->foreignId('aprobada_por_id')->nullable()->after('aprobada')
          ->constrained('users')->nullOnDelete();
    $table->datetime('aprobada_at')->nullable()->after('aprobada_por_id');
});
```

## Modelo Planilla

### Nuevos Campos Fillable

```php
protected $fillable = [
    // ... campos existentes ...
    'fecha_creacion_ferrawin',
    'aprobada',
    'aprobada_por_id',
    'aprobada_at',
];
```

### Nuevos Casts

```php
protected $casts = [
    // ... casts existentes ...
    'fecha_creacion_ferrawin' => 'datetime',
    'aprobada' => 'boolean',
    'aprobada_at' => 'datetime',
];
```

### Relaciones

```php
// Relacion con el usuario que aprobo
public function aprobador()
{
    return $this->belongsTo(User::class, 'aprobada_por_id');
}
```

### Atributos Calculados

```php
// Fecha de entrega calculada (aprobada_at + 7 dias)
public function getFechaEstimadaEntregaCalculadaAttribute()
{
    if ($this->aprobada && $this->aprobada_at) {
        return Carbon::parse($this->aprobada_at)->addDays(7);
    }
    return null;
}

// Fecha de Ferrawin formateada
public function getFechaCreacionFerrawinFormateadaAttribute()
{
    if (!$this->fecha_creacion_ferrawin) {
        return null;
    }
    return Carbon::parse($this->fecha_creacion_ferrawin)->format('d/m/Y');
}
```

## Importacion desde Ferrawin

### FerrawinQuery (ferrawin-sync)

El servicio de consulta ahora incluye `fecha_creacion_ferrawin` en los datos formateados:

```php
// src/FerrawinQuery.php - formatearParaApi()

return [
    'codigo' => $codigo,
    'descripcion' => $primerElemento->descripcion_planilla ?? null,
    'seccion' => $primerElemento->seccion ?? null,
    'ensamblado' => $primerElemento->ensamblado ?? null,
    'fecha_creacion_ferrawin' => $primerElemento->fecha ?? null,  // NUEVO
    'elementos' => $elementos,
];
```

### FerrawinBulkImportService (Manager)

Al crear una planilla, se guarda la fecha de Ferrawin y se deja sin aprobar:

```php
$planilla = Planilla::create([
    // ... otros campos ...
    'fecha_creacion_ferrawin' => $data['fecha_creacion_ferrawin'] ?? null,
    'fecha_estimada_entrega' => null,  // Se establece al aprobar
    'revisada' => false,
    'aprobada' => false,
]);
```

## Componente Livewire: PlanillasTable

### Nuevas Propiedades

```php
#[Url(keep: true)]
public $aprobada = '';  // Filtro por estado de aprobacion
```

### Metodo de Aprobacion

```php
public function aprobarPlanilla($planillaId)
{
    $planilla = Planilla::findOrFail($planillaId);

    if ($planilla->aprobada) {
        $this->dispatch('planilla-actualizada', [
            'message' => 'Esta planilla ya esta aprobada',
            'type' => 'warning'
        ]);
        return;
    }

    $planilla->aprobada = true;
    $planilla->aprobada_por_id = auth()->id();
    $planilla->aprobada_at = now();
    $planilla->fecha_estimada_entrega = now()->addDays(7)->setTime(10, 0, 0);
    $planilla->save();

    $this->dispatch('planilla-actualizada', [
        'message' => 'Planilla aprobada. Fecha de entrega: ' . $planilla->fecha_estimada_entrega,
        'type' => 'success'
    ]);
}
```

### Filtro de Aprobacion

```php
public function aplicarFiltros($query)
{
    // ... otros filtros ...

    if ($this->aprobada !== '') {
        $query->where('aprobada', (bool) $this->aprobada);
    }

    return $query;
}
```

### Contador de Planillas Sin Aprobar

```php
$planillasSinAprobar = Planilla::where('aprobada', false)
    ->whereIn('estado', ['pendiente', 'fabricando'])
    ->count();
```

## Interfaz de Usuario

### Badge de Alerta

Muestra un aviso cuando hay planillas pendientes de aprobacion:

```blade
@if ($planillasSinAprobar > 0)
    <div class="my-4 bg-orange-100 border-l-4 border-orange-500 p-4 rounded-r-lg shadow">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-3xl">📋</span>
                <div>
                    <h3 class="text-lg font-bold text-orange-800">
                        {{ $planillasSinAprobar }} planillas pendientes de aprobacion
                    </h3>
                    <p class="text-sm text-orange-700">
                        Las planillas deben ser aprobadas para establecer la fecha de entrega
                    </p>
                </div>
            </div>
            <button wire:click="verSinAprobar" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                Ver planillas sin aprobar
            </button>
        </div>
    </div>
@endif
```

### Columnas en la Tabla

| Columna | Descripcion |
|---------|-------------|
| **Aprobada** | Badge verde (Si) o naranja (No) |
| **Fecha aprobacion** | Fecha/hora + nombre del aprobador |

### Boton de Aprobar

- **Icono**: Circulo con check (naranja)
- **Ubicacion**: Columna de acciones
- **Comportamiento**:
  - Muestra confirmacion con la fecha de entrega calculada
  - Al confirmar, aprueba la planilla
  - Cambia a icono verde cuando ya esta aprobada

```blade
@if(!$planilla->aprobada)
    <button wire:click="aprobarPlanilla({{ $planilla->id }})"
        wire:confirm="Aprobar esta planilla? La fecha de entrega sera {{ now()->addDays(7)->format('d/m/Y') }}"
        class="w-6 h-6 bg-orange-100 text-orange-600 rounded hover:bg-orange-200"
        title="Aprobar planilla">
        <!-- SVG icon -->
    </button>
@else
    <span class="w-6 h-6 bg-green-100 text-green-600 rounded" title="Ya aprobada">
        <!-- SVG check icon -->
    </span>
@endif
```

### Filtro de Aprobacion

```blade
<select wire:model.live="aprobada">
    <option value="">Todas</option>
    <option value="1">Si</option>
    <option value="0">No</option>
</select>
```

## Reglas de Negocio

1. **Fecha de entrega**: Se calcula automaticamente como `aprobada_at + 7 dias` al momento de aprobar.

2. **Una sola aprobacion**: Una planilla solo puede ser aprobada una vez. No se puede desaprobar.

3. **Orden del flujo**:
   - Primero se aprueba (tecnico confirma que puede hacerla)
   - Luego se revisa (entra en produccion)

4. **Planillas importadas**: Las planillas nuevas importadas desde Ferrawin llegan con `aprobada = false` y `fecha_estimada_entrega = null`.

5. **Fecha de Ferrawin**: Se guarda como referencia para trazabilidad pero no afecta al calculo de entrega.

## Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `database/migrations/2026_01_09_*` | Nueva migracion con campos de aprobacion |
| `app/Models/Planilla.php` | Nuevos campos, casts, relaciones y atributos |
| `app/Livewire/PlanillasTable.php` | Filtro, metodo aprobar, contadores |
| `resources/views/livewire/planillas-table.blade.php` | UI de aprobacion |
| `ferrawin-sync/src/FerrawinQuery.php` | Incluir fecha_creacion_ferrawin |
| `app/Services/FerrawinSync/FerrawinBulkImportService.php` | Guardar fecha Ferrawin |

## Proximas Mejoras (Pendientes)

- [ ] Asignar planillas a tecnicos especificos
- [ ] Notificaciones cuando se asignan planillas
- [ ] Historial de aprobaciones
- [ ] Posibilidad de rechazar planillas con comentario
- [ ] Dashboard de planillas por tecnico
