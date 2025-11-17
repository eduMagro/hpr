# 🔬 EJEMPLO REAL - Cómo se Aplican las Coladas

**Generado por FERRALLIN** - 17 de Noviembre de 2025

---

## 🎯 RESULTADO DEL TEST

```
✅ Test ejecutado exitosamente
⏱️ Tiempo: 0.18 segundos
📊 Elemento analizado: EL25111 (ID: 160132)
```

---

## 📋 EJEMPLO REAL DE ASIGNACIÓN SIMPLE

### Elemento a Fabricar

```
ID del elemento:     160132
Código:              EL25111
Diámetro necesario:  Ø16mm
Peso necesario:      1,126.69 kg
Estado inicial:      pendiente
```

---

## 📦 PRODUCTOS DISPONIBLES EN MÁQUINA (Syntax Line 28)

### Stock de Ø16mm Disponible:

| ID Producto | Colada | Stock Inicial | Stock Actual | Consumido |
|-------------|--------|---------------|--------------|-----------|
| **594** | **ASDF** | 25,000.00 kg | **23,508.46 kg** | 1,491.54 kg |
| 592 | 165 | 25,000.00 kg | 20,530.16 kg | 4,469.84 kg |

**Total disponible Ø16mm:** 44,038.62 kg

---

## ⚙️ PROCESO DE ASIGNACIÓN

### Paso 1: Sistema Busca Productos del Diámetro

```sql
SELECT * FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE pb.diametro = 16
  AND p.maquina_id = 1
  AND p.peso_stock > 0
ORDER BY p.peso_stock ASC;  -- ¡Primero los más pequeños!
```

**Resultado:**
- Producto 592: 20,530.16 kg (Colada: 165)
- Producto 594: 23,508.46 kg (Colada: ASDF) ← **Más grande, pero se elegirá primero el pequeño**

**Nota:** El sistema ordena por `peso_stock ASC` para consumir primero los productos con menos stock.

---

### Paso 2: Sistema Calcula Peso Necesario

```
Elemento EL25111 necesita: 1,126.69 kg de Ø16mm
```

---

### Paso 3: Sistema Consume del Primer Producto

```
Producto 592 (Colada: 165):
  Stock actual:     20,530.16 kg
  Peso a consumir:  1,126.69 kg
  Stock restante:   19,403.47 kg ← Suficiente, no se agota
```

**Decisión del Sistema:**
✅ Un solo producto cubre todo el peso necesario
✅ Se asigna como `producto_id`
✅ NO se necesita `producto_id_2` ni `producto_id_3`

---

### Paso 4: Sistema Asigna al Elemento

```php
// En la base de datos:
elemento.producto_id   = 592;        // Producto con Colada: 165
elemento.producto_id_2 = NULL;       // No necesario
elemento.producto_id_3 = NULL;       // No necesario
elemento.estado        = 'fabricado';
```

---

### Paso 5: Sistema Actualiza el Producto

```php
// Producto 592 actualizado:
producto.peso_stock = 20530.16 - 1126.69 = 19403.47 kg;
producto.estado     = 'disponible';  // Aún tiene stock
```

---

## 📊 RESULTADO FINAL

### Elemento Fabricado

```
✅ Elemento EL25111 (ID: 160132)

Productos asignados:
  • producto_id:   592 (Colada: 165)
  • producto_id_2: NULL
  • producto_id_3: NULL

Trazabilidad:
  • Colada utilizada: 165
  • Peso consumido: 1,126.69 kg
  • Proveedor: Identificable por colada
```

### Producto Consumido

```
📦 Producto 592 (Colada: 165)

Antes de fabricar:
  Stock: 20,530.16 kg

Después de fabricar:
  Stock: 19,403.47 kg
  Consumido en esta fabricación: 1,126.69 kg
  Estado: disponible (aún tiene stock)
```

---

## 🔍 ANÁLISIS DE TODOS LOS PRODUCTOS CON COLADAS

### Productos en Máquina Syntax Line 28:

```
📦 PRODUCTO 189 (Ø12mm)
  Colada: 90217
  Stock actual: 1,317.81 kg
  Stock inicial: 2,543.00 kg
  Ya consumido: 1,225.19 kg ⚠️ (48% consumido)

📦 PRODUCTO 475 (Ø25mm)
  Colada: 90891
  Stock actual: 2,600.00 kg
  Stock inicial: 2,600.00 kg
  Ya consumido: 0.00 kg ✅ (sin usar aún)

📦 PRODUCTO 592 (Ø16mm)
  Colada: 165
  Stock actual: 20,530.16 kg
  Stock inicial: 25,000.00 kg
  Ya consumido: 4,469.84 kg ⚠️ (18% consumido)

📦 PRODUCTO 594 (Ø16mm)
  Colada: ASDF
  Stock actual: 23,508.46 kg
  Stock inicial: 25,000.00 kg
  Ya consumido: 1,491.54 kg ✅ (6% consumido)

📦 PRODUCTO 595 (Ø20mm)
  Colada: ASDF
  Stock actual: 1,556.78 kg
  Stock inicial: 25,000.00 kg
  Ya consumido: 23,443.22 kg ⚠️⚠️ (94% consumido!)
```

---

## 🎯 CASOS DE USO DE COLADAS

### Caso 1: Elemento con 1 Producto (Simple)

```
Elemento necesita: 1,000 kg de Ø12mm
Producto A (Colada: 90217): 5,000 kg disponible

Resultado:
  elemento.producto_id = Producto A
  Coladas usadas: 90217 (1 colada)
```

---

### Caso 2: Elemento con 2 Productos (Doble)

```
Elemento necesita: 800 kg de Ø12mm
Producto A (Colada: 90217): 500 kg disponible
Producto B (Colada: 90218): 600 kg disponible

Resultado:
  elemento.producto_id   = Producto A (500 kg consumidos)
  elemento.producto_id_2 = Producto B (300 kg consumidos)
  Coladas usadas: 90217 + 90218 (2 coladas DIFERENTES)
```

**Importante:** El elemento se fabricó con **MEZCLA DE 2 COLADAS**.

---

### Caso 3: Elemento con 3 Productos (Triple - Máximo)

```
Elemento necesita: 1,000 kg de Ø12mm
Producto A (Colada: 90217): 300 kg disponible
Producto B (Colada: 90218): 400 kg disponible
Producto C (Colada: 90219): 500 kg disponible

Resultado:
  elemento.producto_id   = Producto A (300 kg consumidos)
  elemento.producto_id_2 = Producto B (400 kg consumidos)
  elemento.producto_id_3 = Producto C (300 kg consumidos)
  Coladas usadas: 90217 + 90218 + 90219 (3 coladas DIFERENTES)
```

**Importante:** El elemento se fabricó con **MEZCLA DE 3 COLADAS**.

**Limitación:** El sistema **NO puede asignar más de 3 productos** por elemento.

---

## 🔬 TRAZABILIDAD DE COLADAS

### ¿Por qué es Importante?

```
✅ CALIDAD: Rastrear origen del material
✅ AUDITORÍA: Cumplimiento de normativas
✅ PROBLEMAS: Identificar elementos afectados por colada defectuosa
✅ GARANTÍA: Documentar materiales utilizados
```

### Ejemplo de Rastreo

**Pregunta:** "¿Qué elementos se fabricaron con la colada 165?"

```sql
SELECT
    e.codigo as elemento,
    e.peso,
    CASE
        WHEN p1.n_colada = '165' THEN 'Producto 1'
        WHEN p2.n_colada = '165' THEN 'Producto 2'
        WHEN p3.n_colada = '165' THEN 'Producto 3'
    END as posicion
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE p1.n_colada = '165'
   OR p2.n_colada = '165'
   OR p3.n_colada = '165';
```

**Resultado Esperado:**
```
Elemento EL25111 (1,126.69 kg) - Producto 1
Elemento EL25112 (850.00 kg) - Producto 1
Elemento EL25113 (1,200.00 kg) - Producto 2 (mezcla)
...
```

---

## 📈 ESTADÍSTICAS DE COLADAS

### En esta Máquina (Syntax Line 28):

```
Total productos: 7
Coladas diferentes: 7

Coladas encontradas:
  - 90217 (Ø12mm)
  - 90891 (Ø25mm)
  - 165   (Ø16mm)
  - ASDF  (Ø16mm y Ø20mm) ← Misma colada en 2 diámetros
  - ... y más
```

---

## 💡 REGLAS DEL SISTEMA

### Orden de Consumo

```php
ORDER BY peso_stock ASC  // Consume primero los productos más pequeños
```

**Ventaja:** Evita que productos pequeños queden "olvidados" en stock.

### Límite de Productos

```
Máximo por elemento: 3 productos
  - producto_id
  - producto_id_2
  - producto_id_3
```

**Si se necesitaran más:** Consolidar productos pequeños antes de fabricar.

### Trazabilidad Completa

```
Cada producto tiene:
  - n_colada (número de colada)
  - peso_inicial (peso original)
  - peso_stock (peso actual)

Cada elemento guarda:
  - producto_id (hasta 3)
  - Referencia a las coladas usadas
```

---

## 🎯 CONCLUSIÓN DEL EJEMPLO

### Lo que Aprendimos:

1. **Elemento EL25111** necesitaba **1,126.69 kg** de Ø16mm
2. Sistema encontró **2 productos** disponibles de Ø16mm
3. Sistema eligió **Producto 592** (Colada: 165) por tener menos stock
4. **1 solo producto** fue suficiente (asignación simple)
5. **Trazabilidad completa:** Sabemos que se usó la Colada 165
6. **Stock actualizado:** Producto 592 ahora tiene 19,403.47 kg

### Próxima Fabricación:

Si otro elemento necesita Ø16mm, el sistema:
1. Verá que Producto 592 tiene 19,403.47 kg
2. Verá que Producto 594 tiene 23,508.46 kg
3. Elegirá Producto 592 (más pequeño)
4. Y así sucesivamente...

---

## 🔍 QUERIES ÚTILES PARA COLADAS

### Ver todas las coladas en sistema:

```sql
SELECT DISTINCT n_colada, COUNT(*) as productos
FROM productos
WHERE n_colada IS NOT NULL
GROUP BY n_colada
ORDER BY productos DESC;
```

### Ver productos de una colada específica:

```sql
SELECT p.id, pb.diametro, p.peso_stock, m.nombre as maquina
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
LEFT JOIN maquinas m ON p.maquina_id = m.id
WHERE p.n_colada = 'ASDF'
ORDER BY pb.diametro;
```

### Ver elementos fabricados con una colada:

```sql
SELECT e.codigo, e.peso, e.diametro
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE '165' IN (p1.n_colada, p2.n_colada, p3.n_colada)
  AND e.estado = 'fabricado';
```

---

**Powered by FERRALLIN 🤖**
**"Testing detallado, resultados confiables"** ✨
