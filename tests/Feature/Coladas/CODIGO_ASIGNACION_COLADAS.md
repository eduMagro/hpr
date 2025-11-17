# 🔬 CÓDIGO DE ASIGNACIÓN DE COLADAS - Ubicación y Explicación

**Generado por FERRALLIN** - 17 de Noviembre de 2025

---

## 📍 UBICACIÓN DEL CÓDIGO

### Archivo Principal

```
📁 app/Servicios/Etiquetas/Base/ServicioEtiquetaBase.php
```

**Líneas:** 201-346
**Método:** `actualizarElementosYConsumosCompleto()`

---

## 🎯 SECCIÓN 1: CREACIÓN DEL POOL DE CONSUMOS

### Ubicación: Líneas 217-312

```php
// 1) CONSUMOS (con pool por diámetro y locks)
$consumos = [];
foreach ($elementosEnMaquina->groupBy(fn($e) => (int)$e->diametro) as $diametro => $elementos) {

    $pesoNecesarioTotal = (float) $elementos->sum('peso');

    // 👇 AQUÍ SE BUSCAN LOS PRODUCTOS (CON COLADAS)
    $productosPorDiametro = $maquina->productos()
        ->whereHas('productoBase', fn($q) => $q->where('diametro', (int)$diametro))
        ->with('productoBase')
        ->orderBy('peso_stock')  // ⭐ ORDENA POR MENOR STOCK PRIMERO
        ->lockForUpdate()
        ->get();

    $consumos[$diametro] = [];

    // 👇 AQUÍ SE CONSUMEN LOS PRODUCTOS Y SE GUARDAN LAS COLADAS
    foreach ($productosPorDiametro as $producto) {
        if ($pesoNecesarioTotal <= 0) break;

        $disponible = (float) $producto->peso_stock;
        $restar = min($disponible, $pesoNecesarioTotal);

        // Actualizar stock del producto
        $producto->peso_stock = $disponible - $restar;
        $pesoNecesarioTotal -= $restar;

        if ($producto->peso_stock <= 0) {
            $producto->peso_stock = 0;
            $producto->estado = 'consumido';
        }
        $producto->save();

        // 👇 AQUÍ SE REGISTRA LA COLADA EN EL ARRAY
        $productosAfectados[] = [
            'id'           => $producto->id,
            'peso_stock'   => $producto->peso_stock,
            'peso_inicial' => $pesoInicial,
            'n_colada'     => $producto->n_colada,  // ⭐ COLADA GUARDADA
        ];

        // Pool de consumos por diámetro
        $consumos[$diametro][] = [
            'producto_id' => $producto->id,  // ⭐ ID DEL PRODUCTO (CON COLADA)
            'consumido'   => $restar,
        ];
    }
}
```

---

## 🎯 SECCIÓN 2: ASIGNACIÓN DE PRODUCTOS A ELEMENTOS

### Ubicación: Líneas 314-346

```php
// 2) Asignar productos a elementos (pool compartido por diámetro)
foreach ($elementosEnMaquina as $elemento) {
    $d = (int) $elemento->diametro;

    if (!isset($consumos[$d])) {
        $consumos[$d] = [];
    }
    $disponibles = &$consumos[$d];

    $pesoRestante = (float) $elemento->peso;
    $asignados = [];

    // 👇 AQUÍ SE ASIGNAN LOS PRODUCTOS AL ELEMENTO
    while ($pesoRestante > 0 && count($disponibles) > 0) {
        $cons = &$disponibles[0];

        if ($cons['consumido'] <= $pesoRestante) {
            // Producto completo consumido
            $asignados[] = $cons['producto_id'];  // ⭐ GUARDA EL PRODUCTO_ID
            $pesoRestante -= $cons['consumido'];
            array_shift($disponibles);
        } else {
            // Producto parcialmente consumido
            $asignados[] = $cons['producto_id'];  // ⭐ GUARDA EL PRODUCTO_ID
            $cons['consumido'] -= $pesoRestante;
            $pesoRestante = 0;
        }
    }

    // 👇 AQUÍ SE ASIGNAN AL ELEMENTO (HASTA 3 PRODUCTOS)
    $elemento->producto_id   = $asignados[0] ?? null;  // ⭐ PRIMER PRODUCTO
    $elemento->producto_id_2 = $asignados[1] ?? null;  // ⭐ SEGUNDO PRODUCTO
    $elemento->producto_id_3 = $asignados[2] ?? null;  // ⭐ TERCER PRODUCTO

    if ($pesoRestante <= 0) {
        $elemento->estado = 'fabricado';
    }
    $elemento->save();  // ⭐ GUARDA EN LA BD
}
```

---

## 🔍 DESGLOSE LÍNEA POR LÍNEA

### Línea 228-233: Buscar Productos con Coladas

```php
$productosPorDiametro = $maquina->productos()
    ->whereHas('productoBase', fn($q) => $q->where('diametro', (int)$diametro))
    ->with('productoBase')
    ->orderBy('peso_stock')  // ← CLAVE: Ordena de menor a mayor
    ->lockForUpdate()        // ← CLAVE: Bloquea para evitar race conditions
    ->get();
```

**¿Qué hace?**
1. Busca productos de la máquina
2. Filtra por diámetro específico
3. **ORDENA por peso_stock (menor primero)**
4. Bloquea los registros (lockForUpdate)
5. Trae los productos con su ProductoBase

**Resultado:**
```php
Collection [
    Producto { id: 592, n_colada: '165', peso_stock: 20530.16 },    // Menos stock
    Producto { id: 594, n_colada: 'ASDF', peso_stock: 23508.46 },  // Más stock
]
```

---

### Línea 268-298: Consumir Productos y Crear Pool

```php
foreach ($productosPorDiametro as $producto) {
    if ($pesoNecesarioTotal <= 0) break;

    $disponible = (float) $producto->peso_stock;
    $restar = min($disponible, $pesoNecesarioTotal);

    // Actualizar el producto
    $producto->peso_stock = $disponible - $restar;
    $pesoNecesarioTotal -= $restar;

    if ($producto->peso_stock <= 0) {
        $producto->peso_stock = 0;
        $producto->estado = 'consumido';
        $producto->ubicacion_id = null;
        $producto->maquina_id = null;
    }
    $producto->save();

    // ⭐ REGISTRAR COLADA EN ARRAY DE PRODUCTOS AFECTADOS
    $productosAfectados[] = [
        'id'           => $producto->id,
        'peso_stock'   => $producto->peso_stock,
        'peso_inicial' => $pesoInicial,
        'n_colada'     => $producto->n_colada,  // ← LA COLADA SE GUARDA AQUÍ
    ];

    // ⭐ AGREGAR AL POOL DE CONSUMOS
    $consumos[$diametro][] = [
        'producto_id' => $producto->id,  // ← ID del producto (contiene la colada)
        'consumido'   => $restar,
    ];
}
```

**¿Qué hace?**
1. Recorre cada producto del diámetro
2. Calcula cuánto consumir de cada uno
3. Actualiza `peso_stock` del producto
4. Si se agota completamente, marca como 'consumido'
5. **Guarda la colada en `$productosAfectados`**
6. **Agrega el producto_id al pool de consumos**

**Ejemplo:**
```php
// Elemento necesita 1,126.69 kg de Ø16

// Iteración 1: Producto 592 (Colada: 165)
$disponible = 20530.16;
$restar = min(20530.16, 1126.69) = 1126.69;
$producto->peso_stock = 20530.16 - 1126.69 = 19403.47;

$consumos[16][] = [
    'producto_id' => 592,  // ← Este producto tiene n_colada = '165'
    'consumido'   => 1126.69,
];

// No se necesita iterar más porque pesoNecesarioTotal = 0
```

---

### Línea 315-346: Asignar Productos a Elementos

```php
foreach ($elementosEnMaquina as $elemento) {
    $d = (int) $elemento->diametro;
    $disponibles = &$consumos[$d];  // ← Toma el pool del diámetro

    $pesoRestante = (float) $elemento->peso;
    $asignados = [];

    // ⭐ ASIGNAR PRODUCTOS DEL POOL
    while ($pesoRestante > 0 && count($disponibles) > 0) {
        $cons = &$disponibles[0];

        if ($cons['consumido'] <= $pesoRestante) {
            $asignados[] = $cons['producto_id'];  // ← Guarda el ID
            $pesoRestante -= $cons['consumido'];
            array_shift($disponibles);
        } else {
            $asignados[] = $cons['producto_id'];  // ← Guarda el ID
            $cons['consumido'] -= $pesoRestante;
            $pesoRestante = 0;
        }
    }

    // ⭐ ASIGNAR HASTA 3 PRODUCTOS
    $elemento->producto_id   = $asignados[0] ?? null;
    $elemento->producto_id_2 = $asignados[1] ?? null;
    $elemento->producto_id_3 = $asignados[2] ?? null;

    $elemento->estado = 'fabricado';
    $elemento->save();
}
```

**¿Qué hace?**
1. Recorre cada elemento
2. Toma el pool de consumos de su diámetro
3. Va tomando productos del pool hasta cubrir el peso
4. **Asigna hasta 3 producto_id al elemento**
5. Guarda el elemento en la BD

**Ejemplo:**
```php
// Elemento EL25111 (Ø16, 1126.69 kg)

$disponibles = $consumos[16];  // [['producto_id' => 592, 'consumido' => 1126.69]]
$pesoRestante = 1126.69;
$asignados = [];

// Iteración 1
$cons = ['producto_id' => 592, 'consumido' => 1126.69];
$asignados[] = 592;  // ← Agrega producto 592
$pesoRestante = 0;

// Asignación final
$elemento->producto_id   = 592;   // ← Producto con Colada '165'
$elemento->producto_id_2 = null;
$elemento->producto_id_3 = null;
$elemento->save();
```

---

## 🎯 PUNTOS CLAVE DEL CÓDIGO

### 1. Orden de Consumo (Línea 231)

```php
->orderBy('peso_stock')  // ASC implícito (menor primero)
```

**¿Por qué?**
- Consume primero los productos con **MENOS stock**
- Evita que productos pequeños queden olvidados
- Optimiza la rotación de inventario

---

### 2. Pool Compartido por Diámetro (Línea 266)

```php
$consumos[$diametro] = [];
```

**¿Por qué?**
- Elementos del mismo diámetro comparten productos
- Optimización automática del consumo
- Un producto puede abastecer varios elementos

---

### 3. Hasta 3 Productos por Elemento (Líneas 338-340)

```php
$elemento->producto_id   = $asignados[0] ?? null;
$elemento->producto_id_2 = $asignados[1] ?? null;
$elemento->producto_id_3 = $asignados[2] ?? null;
```

**Limitación:**
- Máximo 3 productos por elemento
- Estructura fija de BD (3 columnas)
- Si se necesitaran más, hay que consolidar stock

---

### 4. Trazabilidad de Coladas (Línea 292)

```php
'n_colada' => $producto->n_colada,
```

**¿Dónde se guarda?**
- En el array `$productosAfectados[]` (para logs/respuesta)
- En la tabla `productos` (campo `n_colada`)
- Se puede rastrear desde `elementos` → `productos` → `n_colada`

---

### 5. Bloqueo de Registros (Línea 232)

```php
->lockForUpdate()
```

**¿Por qué?**
- Previene condiciones de carrera
- Múltiples operarios no pueden consumir el mismo stock
- Garantiza integridad de datos

---

## 📊 FLUJO COMPLETO VISUAL

```
┌─────────────────────────────────────────────────────────────┐
│ 1. BUSCAR PRODUCTOS DEL DIÁMETRO (Línea 228-233)           │
│    SELECT * FROM productos                                  │
│    WHERE maquina_id = X AND diametro = Y                   │
│    ORDER BY peso_stock ASC                                 │
│    FOR UPDATE                                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. CONSUMIR PRODUCTOS (Línea 268-298)                      │
│    - Restar peso_stock                                      │
│    - Marcar como 'consumido' si se agota                   │
│    - Guardar n_colada en $productosAfectados               │
│    - Agregar producto_id al pool $consumos[diametro]       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. ASIGNAR A ELEMENTOS (Línea 315-346)                     │
│    - Tomar del pool $consumos[diametro]                    │
│    - Asignar hasta 3 productos                             │
│    - elemento.producto_id   = ID 1 (con colada)            │
│    - elemento.producto_id_2 = ID 2 (con colada)            │
│    - elemento.producto_id_3 = ID 3 (con colada)            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. GUARDAR EN BD                                            │
│    - $elemento->save()                                      │
│    - $producto->save()                                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔍 TRAZABILIDAD: DE ELEMENTO A COLADA

### Cómo Rastrear la Colada de un Elemento

```sql
-- Opción 1: JOIN directo
SELECT
    e.codigo as elemento,
    e.peso,
    p1.id as producto_1_id,
    p1.n_colada as colada_1,
    p2.id as producto_2_id,
    p2.n_colada as colada_2,
    p3.id as producto_3_id,
    p3.n_colada as colada_3
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE e.id = 160132;
```

**Resultado:**
```
elemento: EL25111
peso: 1126.69
producto_1_id: 592
colada_1: 165
producto_2_id: NULL
colada_2: NULL
producto_3_id: NULL
colada_3: NULL
```

---

### Opción 2: Usando Eloquent

```php
$elemento = Elemento::with(['producto', 'producto2', 'producto3'])->find(160132);

$coladas = [
    $elemento->producto?->n_colada,
    $elemento->producto2?->n_colada,
    $elemento->producto3?->n_colada,
];

$coladas = array_filter($coladas); // Quitar nulls

echo "Coladas usadas: " . implode(', ', $coladas);
// Resultado: "Coladas usadas: 165"
```

---

## 📁 ARCHIVOS RELACIONADOS

### Archivo Principal:
```
app/Servicios/Etiquetas/Base/ServicioEtiquetaBase.php
```
- Método: `actualizarElementosYConsumosCompleto()`
- Líneas: 201-346

### Servicios Que Lo Usan:
```
app/Servicios/Etiquetas/Servicios/
├── CortadoraDobladoraBarraEtiquetaServicio.php
├── CortadoraDobladoraEncarretadoEtiquetaServicio.php
├── DobladoraEtiquetaServicio.php
├── EnsambladoraEtiquetaServicio.php
└── SoldadoraEtiquetaServicio.php
```

Todos estos servicios heredan de `ServicioEtiquetaBase` y usan el método `actualizarElementosYConsumosCompleto()`.

### Controlador:
```
app/Http/Controllers/EtiquetaController.php
```
- Método: `actualizarEtiqueta()` (línea ~1268)
- Llama al servicio correspondiente

### Modelos:
```
app/Models/Elemento.php      (producto_id, producto_id_2, producto_id_3)
app/Models/Producto.php      (n_colada, peso_stock)
app/Models/ProductoBase.php  (diametro, tipo)
```

---

## 🎯 RESUMEN

### Código Clave en 3 Líneas:

```php
// 1. Buscar productos con coladas (Línea 228)
$productosPorDiametro = $maquina->productos()->orderBy('peso_stock')->get();

// 2. Crear pool de consumos con IDs de productos (Línea 294-296)
$consumos[$diametro][] = ['producto_id' => $producto->id, 'consumido' => $restar];

// 3. Asignar hasta 3 productos al elemento (Línea 338-340)
$elemento->producto_id = $asignados[0] ?? null;
$elemento->producto_id_2 = $asignados[1] ?? null;
$elemento->producto_id_3 = $asignados[2] ?? null;
```

### Flujo Resumido:

1. **Línea 228:** Busca productos ordenados por peso (menor primero)
2. **Línea 268:** Consume productos y guarda coladas en array
3. **Línea 294:** Crea pool con producto_id (que contiene la colada)
4. **Línea 325:** Toma productos del pool
5. **Línea 338:** Asigna hasta 3 productos al elemento
6. **Línea 345:** Guarda elemento en BD

### Para Rastrear Colada:

```
elementos.producto_id → productos.id → productos.n_colada
```

---

**Powered by FERRALLIN 🤖**
**"Código claro, trazabilidad perfecta"** ✨
