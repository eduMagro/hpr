-- =====================================================================
-- QUERIES ÚTILES - Sistema de Asignación de Coladas
-- =====================================================================
-- Fecha: 17 de Noviembre de 2025
-- Propósito: Debugging y análisis del sistema de asignación de coladas
-- =====================================================================

-- =====================================================================
-- 1. ANÁLISIS DE STOCK POR DIÁMETRO
-- =====================================================================

-- Stock disponible por diámetro (todas las máquinas)
SELECT
    pb.diametro,
    pb.tipo,
    COUNT(DISTINCT p.id) as total_productos,
    SUM(p.peso_stock) as stock_total_kg,
    AVG(p.peso_stock) as stock_promedio_kg,
    MIN(p.peso_stock) as stock_minimo_kg,
    MAX(p.peso_stock) as stock_maximo_kg,
    COUNT(DISTINCT p.n_colada) as coladas_diferentes,
    CASE
        WHEN COUNT(DISTINCT p.id) > 10 THEN '⚠️ ALTA'
        WHEN COUNT(DISTINCT p.id) > 5 THEN '⚠️ MEDIA'
        ELSE '✅ BAJA'
    END as fragmentacion
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE p.peso_stock > 0
GROUP BY pb.diametro, pb.tipo
ORDER BY stock_total_kg DESC;

-- Stock por diámetro en una máquina específica
SELECT
    pb.diametro,
    COUNT(*) as productos,
    SUM(p.peso_stock) as stock_total,
    AVG(p.peso_stock) as stock_promedio,
    MIN(p.peso_stock) as stock_min,
    MAX(p.peso_stock) as stock_max,
    COUNT(DISTINCT p.n_colada) as coladas_diferentes
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE p.maquina_id = 1  -- Cambiar ID de máquina
  AND p.peso_stock > 0
GROUP BY pb.diametro
ORDER BY stock_total DESC;

-- =====================================================================
-- 2. ELEMENTOS Y SUS ASIGNACIONES DE PRODUCTOS
-- =====================================================================

-- Elementos con 1 producto asignado
SELECT
    e.id,
    e.codigo,
    e.diametro,
    e.peso,
    e.estado,
    p1.id as producto_id,
    p1.n_colada as colada_1,
    p1.peso_stock as stock_restante_producto
FROM elementos e
JOIN productos p1 ON e.producto_id = p1.id
WHERE e.producto_id IS NOT NULL
  AND e.producto_id_2 IS NULL
  AND e.estado = 'fabricado'
ORDER BY e.id DESC
LIMIT 50;

-- Elementos con 2 productos asignados (stock fragmentado)
SELECT
    e.id,
    e.codigo,
    e.diametro,
    e.peso as peso_elemento,
    p1.id as producto_1_id,
    p1.n_colada as colada_1,
    p1.peso_stock as stock_p1,
    p2.id as producto_2_id,
    p2.n_colada as colada_2,
    p2.peso_stock as stock_p2,
    CASE
        WHEN p1.n_colada = p2.n_colada THEN 'Misma colada'
        ELSE '⚠️ Coladas diferentes'
    END as mezcla_coladas
FROM elementos e
JOIN productos p1 ON e.producto_id = p1.id
JOIN productos p2 ON e.producto_id_2 = p2.id
WHERE e.producto_id_2 IS NOT NULL
  AND e.producto_id_3 IS NULL
  AND e.estado = 'fabricado'
ORDER BY e.id DESC
LIMIT 50;

-- Elementos con 3 productos (fragmentación extrema)
SELECT
    e.id,
    e.codigo,
    e.diametro,
    e.peso as peso_elemento,
    p1.id as prod_1,
    p1.n_colada as colada_1,
    p2.id as prod_2,
    p2.n_colada as colada_2,
    p3.id as prod_3,
    p3.n_colada as colada_3,
    CONCAT(
        COALESCE(p1.n_colada, 'N/A'), ', ',
        COALESCE(p2.n_colada, 'N/A'), ', ',
        COALESCE(p3.n_colada, 'N/A')
    ) as todas_las_coladas
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE e.producto_id_3 IS NOT NULL
  AND e.estado = 'fabricado'
ORDER BY e.id DESC;

-- =====================================================================
-- 3. DISTRIBUCIÓN DE ASIGNACIONES
-- =====================================================================

-- Estadísticas: Cuántos elementos con 1, 2 o 3 productos
SELECT
    CASE
        WHEN producto_id IS NOT NULL AND producto_id_2 IS NULL THEN '1 producto (simple)'
        WHEN producto_id_2 IS NOT NULL AND producto_id_3 IS NULL THEN '2 productos (doble)'
        WHEN producto_id_3 IS NOT NULL THEN '3 productos (triple - máximo)'
        ELSE 'Sin productos asignados'
    END as tipo_asignacion,
    COUNT(*) as total_elementos,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM elementos WHERE estado = 'fabricado'), 2) as porcentaje
FROM elementos
WHERE estado = 'fabricado'
GROUP BY tipo_asignacion
ORDER BY total_elementos DESC;

-- =====================================================================
-- 4. TRAZABILIDAD DE COLADAS
-- =====================================================================

-- Todas las coladas usadas en elementos fabricados
SELECT
    COALESCE(p.n_colada, 'Sin colada') as colada,
    pb.diametro,
    pb.tipo,
    COUNT(DISTINCT e.id) as elementos_que_la_usan,
    SUM(e.peso) as peso_total_elementos_kg
FROM elementos e
LEFT JOIN productos p ON e.producto_id = p.id
    OR e.producto_id_2 = p.id
    OR e.producto_id_3 = p.id
LEFT JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE e.estado = 'fabricado'
GROUP BY p.n_colada, pb.diametro, pb.tipo
ORDER BY elementos_que_la_usan DESC;

-- Elementos fabricados con una colada específica
SELECT
    e.id,
    e.codigo,
    e.diametro,
    e.peso,
    CASE
        WHEN p1.n_colada = 'ABC123' THEN 'Producto 1'
        WHEN p2.n_colada = 'ABC123' THEN 'Producto 2'
        WHEN p3.n_colada = 'ABC123' THEN 'Producto 3'
    END as posicion_colada
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE p1.n_colada = 'ABC123'  -- Cambiar por la colada a buscar
   OR p2.n_colada = 'ABC123'
   OR p3.n_colada = 'ABC123'
ORDER BY e.id;

-- Elementos con mezcla de coladas (calidad/auditoría)
SELECT
    e.id,
    e.codigo,
    e.diametro,
    e.peso,
    p1.n_colada as colada_1,
    p2.n_colada as colada_2,
    p3.n_colada as colada_3,
    COUNT(DISTINCT COALESCE(p1.n_colada, p2.n_colada, p3.n_colada)) as total_coladas_diferentes
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE e.estado = 'fabricado'
  AND (
      (p1.n_colada IS NOT NULL AND p2.n_colada IS NOT NULL AND p1.n_colada != p2.n_colada)
   OR (p2.n_colada IS NOT NULL AND p3.n_colada IS NOT NULL AND p2.n_colada != p3.n_colada)
   OR (p1.n_colada IS NOT NULL AND p3.n_colada IS NOT NULL AND p1.n_colada != p3.n_colada)
  )
ORDER BY e.id DESC;

-- =====================================================================
-- 5. PRODUCTOS CONSUMIDOS Y DISPONIBLES
-- =====================================================================

-- Productos completamente consumidos (estado 'consumido')
SELECT
    p.id,
    pb.diametro,
    pb.tipo,
    p.n_colada,
    p.peso_inicial as peso_original_kg,
    p.peso_stock as stock_actual_kg,
    p.estado,
    p.updated_at as fecha_consumo
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE p.estado = 'consumido'
ORDER BY p.updated_at DESC
LIMIT 100;

-- Productos parcialmente consumidos
SELECT
    p.id,
    pb.diametro,
    pb.tipo,
    p.n_colada,
    p.peso_inicial as peso_original,
    p.peso_stock as stock_actual,
    (p.peso_inicial - p.peso_stock) as peso_consumido,
    ROUND(((p.peso_inicial - p.peso_stock) / p.peso_inicial) * 100, 2) as porcentaje_consumido
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE p.peso_stock > 0
  AND p.peso_stock < p.peso_inicial
ORDER BY porcentaje_consumido DESC;

-- Productos sin consumir (peso_stock = peso_inicial)
SELECT
    p.id,
    pb.diametro,
    pb.tipo,
    p.n_colada,
    p.peso_stock as stock_disponible,
    m.nombre as maquina,
    u.descripcion as ubicacion
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
LEFT JOIN maquinas m ON p.maquina_id = m.id
LEFT JOIN ubicaciones u ON p.ubicacion_id = u.id
WHERE p.peso_stock = p.peso_inicial
  AND p.peso_stock > 0
ORDER BY pb.diametro, p.peso_stock DESC;

-- =====================================================================
-- 6. MOVIMIENTOS DE RECARGA
-- =====================================================================

-- Recargas pendientes
SELECT
    m.id,
    m.fecha_solicitud,
    pb.diametro,
    pb.tipo,
    pb.descripcion,
    maq.nombre as maquina_destino,
    m.prioridad,
    m.descripcion as detalle,
    DATEDIFF(NOW(), m.fecha_solicitud) as dias_pendiente
FROM movimientos m
JOIN productos_base pb ON m.producto_base_id = pb.id
JOIN maquinas maq ON m.maquina_destino = maq.id
WHERE m.tipo = 'Recarga materia prima'
  AND m.estado = 'pendiente'
ORDER BY m.prioridad DESC, m.fecha_solicitud ASC;

-- Historial de recargas completadas
SELECT
    m.id,
    m.fecha_solicitud,
    m.fecha_completado,
    DATEDIFF(m.fecha_completado, m.fecha_solicitud) as dias_tardados,
    pb.diametro,
    pb.tipo,
    maq.nombre as maquina,
    m.estado
FROM movimientos m
JOIN productos_base pb ON m.producto_base_id = pb.id
JOIN maquinas maq ON m.maquina_destino = maq.id
WHERE m.tipo = 'Recarga materia prima'
  AND m.estado IN ('completado', 'finalizado')
ORDER BY m.fecha_completado DESC
LIMIT 50;

-- =====================================================================
-- 7. ANÁLISIS DE FRAGMENTACIÓN
-- =====================================================================

-- Diámetros con fragmentación alta (muchos productos pequeños)
SELECT
    pb.diametro,
    COUNT(*) as total_productos,
    SUM(p.peso_stock) as stock_total,
    AVG(p.peso_stock) as promedio_por_producto,
    MIN(p.peso_stock) as producto_mas_pequeno,
    MAX(p.peso_stock) as producto_mas_grande,
    CASE
        WHEN COUNT(*) > 10 THEN '⚠️ FRAGMENTACIÓN ALTA'
        WHEN COUNT(*) > 5 THEN '⚠️ FRAGMENTACIÓN MEDIA'
        ELSE '✅ FRAGMENTACIÓN BAJA'
    END as estado_fragmentacion,
    CASE
        WHEN COUNT(*) > 10 THEN 'Consolidar productos pequeños'
        WHEN COUNT(*) > 5 THEN 'Vigilar fragmentación'
        ELSE 'Estado óptimo'
    END as recomendacion
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE p.peso_stock > 0
GROUP BY pb.diametro
ORDER BY total_productos DESC;

-- Productos candidatos para consolidación (pequeños del mismo diámetro)
SELECT
    pb.diametro,
    p.id,
    p.n_colada,
    p.peso_stock,
    m.nombre as maquina,
    u.descripcion as ubicacion
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
LEFT JOIN maquinas m ON p.maquina_id = m.id
LEFT JOIN ubicaciones u ON p.ubicacion_id = u.id
WHERE p.peso_stock > 0
  AND p.peso_stock < 500  -- Productos menores a 500 kg
  AND pb.diametro IN (
      SELECT diametro
      FROM productos p2
      JOIN productos_base pb2 ON p2.producto_base_id = pb2.id
      WHERE p2.peso_stock > 0
        AND p2.peso_stock < 500
      GROUP BY pb2.diametro
      HAVING COUNT(*) > 3  -- Diámetros con más de 3 productos pequeños
  )
ORDER BY pb.diametro, p.peso_stock ASC;

-- =====================================================================
-- 8. ELEMENTOS PENDIENTES DE FABRICAR
-- =====================================================================

-- Elementos pendientes agrupados por diámetro
SELECT
    diametro,
    COUNT(*) as total_elementos,
    SUM(peso) as peso_total_necesario_kg,
    AVG(peso) as peso_promedio_elemento
FROM elementos
WHERE estado = 'pendiente'
  AND diametro IS NOT NULL
GROUP BY diametro
ORDER BY peso_total_necesario_kg DESC;

-- Comparar necesidad vs stock disponible por diámetro
SELECT
    COALESCE(e.diametro, pb.diametro) as diametro,
    COALESCE(SUM(e.peso), 0) as peso_necesario_kg,
    COALESCE(SUM(p.peso_stock), 0) as stock_disponible_kg,
    COALESCE(SUM(p.peso_stock), 0) - COALESCE(SUM(e.peso), 0) as diferencia_kg,
    CASE
        WHEN COALESCE(SUM(p.peso_stock), 0) >= COALESCE(SUM(e.peso), 0) THEN '✅ Stock suficiente'
        WHEN COALESCE(SUM(p.peso_stock), 0) > 0 THEN '⚠️ Stock insuficiente'
        ELSE '❌ Sin stock'
    END as estado
FROM elementos e
FULL OUTER JOIN productos p ON CAST(e.diametro AS INT) = (
    SELECT pb2.diametro
    FROM productos_base pb2
    WHERE pb2.id = p.producto_base_id
)
LEFT JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE (e.estado = 'pendiente' OR e.estado IS NULL)
  AND (p.peso_stock > 0 OR p.peso_stock IS NULL)
GROUP BY COALESCE(e.diametro, pb.diametro)
ORDER BY peso_necesario_kg DESC;

-- =====================================================================
-- 9. AUDITORÍA Y VERIFICACIÓN
-- =====================================================================

-- Elementos fabricados sin productos asignados (ERROR)
SELECT
    e.id,
    e.codigo,
    e.diametro,
    e.peso,
    e.estado,
    e.updated_at
FROM elementos e
WHERE e.estado = 'fabricado'
  AND e.producto_id IS NULL
ORDER BY e.updated_at DESC;

-- Productos con peso_stock negativo (ERROR)
SELECT
    p.id,
    pb.diametro,
    p.n_colada,
    p.peso_stock,
    p.peso_inicial,
    p.updated_at
FROM productos p
JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE p.peso_stock < 0;

-- Verificar integridad: elementos.peso vs suma de productos consumidos
SELECT
    e.id,
    e.codigo,
    e.peso as peso_elemento,
    (COALESCE(p1.peso_inicial - p1.peso_stock, 0) +
     COALESCE(p2.peso_inicial - p2.peso_stock, 0) +
     COALESCE(p3.peso_inicial - p3.peso_stock, 0)) as peso_consumido_productos,
    ABS(e.peso - (
        COALESCE(p1.peso_inicial - p1.peso_stock, 0) +
        COALESCE(p2.peso_inicial - p2.peso_stock, 0) +
        COALESCE(p3.peso_inicial - p3.peso_stock, 0)
    )) as diferencia
FROM elementos e
LEFT JOIN productos p1 ON e.producto_id = p1.id
LEFT JOIN productos p2 ON e.producto_id_2 = p2.id
LEFT JOIN productos p3 ON e.producto_id_3 = p3.id
WHERE e.estado = 'fabricado'
  AND ABS(e.peso - (
        COALESCE(p1.peso_inicial - p1.peso_stock, 0) +
        COALESCE(p2.peso_inicial - p2.peso_stock, 0) +
        COALESCE(p3.peso_inicial - p3.peso_stock, 0)
    )) > 0.01  -- Diferencia mayor a 10 gramos
ORDER BY diferencia DESC;

-- =====================================================================
-- 10. REPORTING Y DASHBOARDS
-- =====================================================================

-- Resumen general del sistema
SELECT
    'Elementos' as categoria,
    'Total' as subcategoria,
    COUNT(*) as valor
FROM elementos
UNION ALL
SELECT 'Elementos', 'Pendientes', COUNT(*) FROM elementos WHERE estado = 'pendiente'
UNION ALL
SELECT 'Elementos', 'Fabricados', COUNT(*) FROM elementos WHERE estado = 'fabricado'
UNION ALL
SELECT 'Stock', 'Total disponible (kg)', SUM(peso_stock) FROM productos WHERE peso_stock > 0
UNION ALL
SELECT 'Stock', 'Productos con stock', COUNT(*) FROM productos WHERE peso_stock > 0
UNION ALL
SELECT 'Stock', 'Productos consumidos', COUNT(*) FROM productos WHERE estado = 'consumido'
UNION ALL
SELECT 'Recargas', 'Pendientes', COUNT(*) FROM movimientos WHERE tipo = 'Recarga materia prima' AND estado = 'pendiente'
UNION ALL
SELECT 'Asignaciones', '1 producto', COUNT(*) FROM elementos WHERE estado = 'fabricado' AND producto_id IS NOT NULL AND producto_id_2 IS NULL
UNION ALL
SELECT 'Asignaciones', '2 productos', COUNT(*) FROM elementos WHERE estado = 'fabricado' AND producto_id_2 IS NOT NULL AND producto_id_3 IS NULL
UNION ALL
SELECT 'Asignaciones', '3 productos', COUNT(*) FROM elementos WHERE estado = 'fabricado' AND producto_id_3 IS NOT NULL;

-- Top 10 coladas más utilizadas
SELECT
    COALESCE(p.n_colada, 'Sin colada') as colada,
    COUNT(DISTINCT e.id) as elementos,
    SUM(e.peso) as peso_total_kg,
    COUNT(DISTINCT pb.diametro) as diametros_diferentes
FROM elementos e
LEFT JOIN productos p ON e.producto_id = p.id
    OR e.producto_id_2 = p.id
    OR e.producto_id_3 = p.id
LEFT JOIN productos_base pb ON p.producto_base_id = pb.id
WHERE e.estado = 'fabricado'
GROUP BY p.n_colada
ORDER BY elementos DESC
LIMIT 10;

-- =====================================================================
-- FIN DE QUERIES ÚTILES
-- =====================================================================
