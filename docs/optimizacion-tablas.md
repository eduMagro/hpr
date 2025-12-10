# Optimización de Tablas - Recomendaciones

## ElementosTable - Análisis de Rendimiento

### Problemas Detectados

1. **Carga de máquinas en cada render** - `Maquina::all()` se ejecuta en cada render
2. **Consulta adicional para suma de peso** - `(clone $query)->sum('peso')`
3. **`whereHas` múltiples** - Cada filtro de relación genera subconsultas
4. **Búsquedas con `LIKE '%valor%'`** - No usan índices
5. **Serialización completa del objeto** - `@js($elemento)` en cada fila
6. **Filtro de máquinas repetido por fila** - `whereIn` ejecutado 3 veces por fila
7. **Transform post-paginación**

---

## Soluciones a Implementar

### 1. Cachear máquinas (Prioridad Alta)
```php
public function render()
{
    // Cachear máquinas por 1 hora
    $maquinas = cache()->remember('maquinas_lista', 3600, function () {
        return Maquina::all();
    });

    // Pre-filtrar por tipo para evitar hacerlo en la vista
    $maquinasPorTipo = [
        'maq1' => $maquinas->whereIn('tipo', ['cortadora_dobladora', 'estribadora', 'cortadora manual', 'grua']),
        'maq2' => $maquinas->whereIn('tipo', ['cortadora_dobladora', 'estribadora', 'cortadora_manual', 'dobladora_manual', 'soldadora']),
        'maq3' => $maquinas->whereIn('tipo', ['soldadora', 'ensambladora']),
    ];

    return view('livewire.elementos-table', [
        'maquinasPorTipo' => $maquinasPorTipo,
    ]);
}
```

### 2. Seleccionar solo columnas necesarias (Prioridad Alta)
```php
$query = Elemento::select([
    'id', 'codigo', 'planilla_id', 'etiqueta_id', 'etiqueta_sub_id',
    'dimensiones', 'diametro', 'barras', 'maquina_id', 'maquina_id_2',
    'maquina_id_3', 'producto_id', 'producto_id_2', 'producto_id_3',
    'figura', 'peso', 'longitud', 'estado', 'created_at'
])->with([...]);
```

### 3. Optimizar serialización en la vista (Prioridad Alta)
```php
// En lugar de @js($elemento) completo, solo los campos editables:
x-data="{
    editando: false,
    elemento: {
        id: {{ $elemento->id }},
        codigo: '{{ $elemento->codigo }}',
        dimensiones: '{{ $elemento->dimensiones }}',
        diametro: {{ $elemento->diametro ?? 'null' }},
        barras: {{ $elemento->barras ?? 'null' }},
        figura: '{{ $elemento->figura }}',
        peso: {{ $elemento->peso ?? 'null' }},
        longitud: {{ $elemento->longitud ?? 'null' }},
        estado: '{{ $elemento->estado }}'
    }
}"
```

### 4. JOINs en lugar de whereHas (Prioridad Alta)
```php
// En lugar de:
$query->whereHas('planilla', fn($q) => $q->where('codigo', 'like', "%{$input}%"));

// Usar:
$query->join('planillas', 'planillas.id', '=', 'elementos.planilla_id')
      ->where('planillas.codigo', 'like', "%{$input}%")
      ->select('elementos.*');
```

### 5. Índices de base de datos (Prioridad Alta)
```sql
-- Ejecutar en MySQL/MariaDB
ALTER TABLE elementos ADD INDEX idx_planilla_estado (planilla_id, estado);
ALTER TABLE elementos ADD INDEX idx_maquina (maquina_id);
ALTER TABLE elementos ADD INDEX idx_maquina_2 (maquina_id_2);
ALTER TABLE elementos ADD INDEX idx_maquina_3 (maquina_id_3);
ALTER TABLE elementos ADD INDEX idx_codigo (codigo);
ALTER TABLE elementos ADD INDEX idx_estado (estado);
ALTER TABLE elementos ADD INDEX idx_etiqueta (etiqueta_id);
ALTER TABLE elementos ADD INDEX idx_producto (producto_id);
```

### 6. Optimizar cálculo de peso total (Prioridad Media)
```php
// Opción A: Usar subquery
$baseQuery = Elemento::query();
$this->aplicarFiltros($baseQuery);

$totalPeso = DB::table(DB::raw("({$baseQuery->toSql()}) as sub"))
    ->mergeBindings($baseQuery->getQuery())
    ->sum('peso');

// Opción B: Calcular en el frontend con los datos paginados (aproximado)
```

### 7. Aumentar debounce (Prioridad Baja)
```html
<!-- De 300ms a 500ms para reducir peticiones -->
wire:model.live.debounce.500ms="codigo"
```

### 8. Eager loading selectivo de relaciones (Prioridad Media)
```php
$query = Elemento::with([
    'planilla:id,codigo',
    'etiquetaRelacion:id',
    'maquina:id,nombre',
    'maquina_2:id,nombre',
    'maquina_3:id,nombre',
    'producto:id,codigo',
    'producto2:id,codigo',
    'producto3:id,codigo',
]);
```

---

## Métricas de Referencia

| Métrica | Antes | Después |
|---------|-------|---------|
| Tiempo render() | ? ms | ? ms |
| Consultas SQL | ? | ? |
| Memoria | ? MB | ? MB |

---

## Notas
- Medir antes y después de cada cambio
- Probar con diferentes volúmenes de datos
- Verificar que los filtros sigan funcionando correctamente
