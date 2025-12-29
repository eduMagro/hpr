-- ============================================================================
-- SCRIPT SIMPLE PARA CORREGIR SUBETIQUETAS CON ELEMENTOS EN DIFERENTES MAQUINAS
-- ============================================================================
-- Ejecutar paso a paso en phpMyAdmin o cliente MySQL
-- ============================================================================

-- ============================================================================
-- PASO 1: DIAGNÓSTICO - Ver el problema
-- ============================================================================

-- 1.1 Subetiquetas con elementos en múltiples máquinas
SELECT
    e.etiqueta_sub_id,
    et.codigo AS codigo_padre,
    COUNT(DISTINCT e.maquina_id) AS num_maquinas,
    GROUP_CONCAT(DISTINCT CONCAT(m.codigo, '(', m.id, ')') ORDER BY e.maquina_id SEPARATOR ', ') AS maquinas,
    COUNT(e.id) AS total_elementos
FROM elementos e
INNER JOIN etiquetas et ON e.etiqueta_id = et.id
LEFT JOIN maquinas m ON e.maquina_id = m.id
WHERE e.etiqueta_sub_id IS NOT NULL
    AND e.maquina_id IS NOT NULL
    AND e.deleted_at IS NULL
GROUP BY e.etiqueta_sub_id, et.codigo
HAVING COUNT(DISTINCT e.maquina_id) > 1
ORDER BY e.etiqueta_sub_id;

-- 1.2 Detalle de elementos afectados
SELECT
    e.id,
    e.etiqueta_sub_id,
    e.maquina_id,
    m.codigo AS maquina,
    e.peso,
    e.barras,
    e.estado
FROM elementos e
LEFT JOIN maquinas m ON e.maquina_id = m.id
WHERE e.etiqueta_sub_id IN (
    SELECT etiqueta_sub_id
    FROM elementos
    WHERE etiqueta_sub_id IS NOT NULL
        AND maquina_id IS NOT NULL
        AND deleted_at IS NULL
    GROUP BY etiqueta_sub_id
    HAVING COUNT(DISTINCT maquina_id) > 1
)
AND e.deleted_at IS NULL
ORDER BY e.etiqueta_sub_id, e.maquina_id;

-- ============================================================================
-- PASO 2: CREAR BACKUP (MUY IMPORTANTE - EJECUTAR ANTES DE CORREGIR)
-- ============================================================================

CREATE TABLE IF NOT EXISTS elementos_backup_20241209 AS
SELECT * FROM elementos WHERE deleted_at IS NULL;

CREATE TABLE IF NOT EXISTS etiquetas_backup_20241209 AS
SELECT * FROM etiquetas WHERE deleted_at IS NULL;

-- ============================================================================
-- PASO 3: CORRECCIÓN AUTOMÁTICA
-- ============================================================================

-- Este procedimiento crea nuevas subetiquetas para separar elementos por máquina
-- La primera máquina encontrada mantiene la subetiqueta original
-- Las demás máquinas reciben nuevas subetiquetas

DROP PROCEDURE IF EXISTS fix_subetiquetas;

DELIMITER //

CREATE PROCEDURE fix_subetiquetas()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_sub_id VARCHAR(255);
    DECLARE v_codigo_padre VARCHAR(255);
    DECLARE v_etiqueta_id INT;
    DECLARE v_primera_maquina INT;
    DECLARE v_maquina_id INT;
    DECLARE v_nuevo_sufijo INT;
    DECLARE v_nueva_sub VARCHAR(255);
    DECLARE v_peso DECIMAL(10,3);
    DECLARE v_count INT DEFAULT 0;

    -- Cursor para subetiquetas problemáticas
    DECLARE cur CURSOR FOR
        SELECT etiqueta_sub_id
        FROM elementos
        WHERE etiqueta_sub_id IS NOT NULL
            AND maquina_id IS NOT NULL
            AND deleted_at IS NULL
        GROUP BY etiqueta_sub_id
        HAVING COUNT(DISTINCT maquina_id) > 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SELECT 'Iniciando corrección de subetiquetas...' AS mensaje;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_sub_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET v_count = v_count + 1;

        -- Obtener código padre
        SET v_codigo_padre = SUBSTRING_INDEX(v_sub_id, '.', 1);

        -- Obtener etiqueta_id
        SELECT etiqueta_id INTO v_etiqueta_id
        FROM elementos
        WHERE etiqueta_sub_id = v_sub_id AND deleted_at IS NULL
        LIMIT 1;

        -- Primera máquina (mantiene la sub original)
        SELECT maquina_id INTO v_primera_maquina
        FROM elementos
        WHERE etiqueta_sub_id = v_sub_id
            AND maquina_id IS NOT NULL
            AND deleted_at IS NULL
        ORDER BY maquina_id
        LIMIT 1;

        -- Procesar otras máquinas
        BEGIN
            DECLARE done2 INT DEFAULT FALSE;
            DECLARE cur2 CURSOR FOR
                SELECT DISTINCT maquina_id
                FROM elementos
                WHERE etiqueta_sub_id = v_sub_id
                    AND maquina_id IS NOT NULL
                    AND maquina_id != v_primera_maquina
                    AND deleted_at IS NULL;
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done2 = TRUE;

            OPEN cur2;

            maq_loop: LOOP
                FETCH cur2 INTO v_maquina_id;
                IF done2 THEN
                    LEAVE maq_loop;
                END IF;

                -- Siguiente sufijo libre
                SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)), 0) + 1
                INTO v_nuevo_sufijo
                FROM elementos
                WHERE etiqueta_sub_id LIKE CONCAT(v_codigo_padre, '.%')
                    AND deleted_at IS NULL;

                SET v_nueva_sub = CONCAT(v_codigo_padre, '.', LPAD(v_nuevo_sufijo, 2, '0'));

                -- Asegurar unicidad
                WHILE EXISTS (SELECT 1 FROM elementos WHERE etiqueta_sub_id = v_nueva_sub AND deleted_at IS NULL) DO
                    SET v_nuevo_sufijo = v_nuevo_sufijo + 1;
                    SET v_nueva_sub = CONCAT(v_codigo_padre, '.', LPAD(v_nuevo_sufijo, 2, '0'));
                END WHILE;

                -- Peso de elementos a mover
                SELECT COALESCE(SUM(peso), 0) INTO v_peso
                FROM elementos
                WHERE etiqueta_sub_id = v_sub_id
                    AND maquina_id = v_maquina_id
                    AND deleted_at IS NULL;

                -- Crear nueva fila en etiquetas
                INSERT INTO etiquetas (
                    codigo, etiqueta_sub_id, planilla_id, nombre, estado, peso,
                    producto_id, producto_id_2, ubicacion_id,
                    operario1_id, operario2_id, soldador1_id, soldador2_id,
                    ensamblador1_id, ensamblador2_id, marca, paquete_id,
                    numero_etiqueta, created_at, updated_at
                )
                SELECT
                    codigo, v_nueva_sub, planilla_id, nombre, COALESCE(estado, 'pendiente'), v_peso,
                    producto_id, producto_id_2, ubicacion_id,
                    operario1_id, operario2_id, soldador1_id, soldador2_id,
                    ensamblador1_id, ensamblador2_id, marca, paquete_id,
                    numero_etiqueta, NOW(), NOW()
                FROM etiquetas
                WHERE id = v_etiqueta_id AND deleted_at IS NULL
                LIMIT 1;

                -- Mover elementos
                UPDATE elementos
                SET etiqueta_sub_id = v_nueva_sub, updated_at = NOW()
                WHERE etiqueta_sub_id = v_sub_id
                    AND maquina_id = v_maquina_id
                    AND deleted_at IS NULL;

                SELECT CONCAT('  -> Máquina ', v_maquina_id, ': elementos movidos a ', v_nueva_sub) AS detalle;

            END LOOP maq_loop;

            CLOSE cur2;
        END;

        -- Recalcular peso de la sub original
        UPDATE etiquetas
        SET peso = (
            SELECT COALESCE(SUM(peso), 0)
            FROM elementos
            WHERE etiqueta_sub_id = v_sub_id AND deleted_at IS NULL
        ), updated_at = NOW()
        WHERE etiqueta_sub_id = v_sub_id AND deleted_at IS NULL;

        SELECT CONCAT('Procesada: ', v_sub_id, ' (primera máquina: ', v_primera_maquina, ')') AS progreso;

    END LOOP read_loop;

    CLOSE cur;

    SELECT CONCAT('Corrección completada. Subetiquetas procesadas: ', v_count) AS resultado;

END //

DELIMITER ;

-- ============================================================================
-- PASO 4: EJECUTAR LA CORRECCIÓN
-- ============================================================================

-- Descomenta la siguiente línea cuando estés listo:
-- CALL fix_subetiquetas();

-- ============================================================================
-- PASO 5: VERIFICAR RESULTADOS
-- ============================================================================

-- Ejecutar después de la corrección para verificar que todo está bien:

-- 5.1 No debería devolver ninguna fila (problema resuelto)
/*
SELECT etiqueta_sub_id, COUNT(DISTINCT maquina_id) AS num_maquinas
FROM elementos
WHERE etiqueta_sub_id IS NOT NULL
    AND maquina_id IS NOT NULL
    AND deleted_at IS NULL
GROUP BY etiqueta_sub_id
HAVING COUNT(DISTINCT maquina_id) > 1;
*/

-- 5.2 Ver nuevas etiquetas creadas hoy
/*
SELECT codigo, etiqueta_sub_id, peso, created_at
FROM etiquetas
WHERE DATE(created_at) = CURDATE()
ORDER BY created_at DESC;
*/

-- ============================================================================
-- PASO 6: ROLLBACK (si algo sale mal)
-- ============================================================================

-- Restaurar elementos desde backup:
/*
UPDATE elementos e
INNER JOIN elementos_backup_20241209 b ON e.id = b.id
SET e.etiqueta_sub_id = b.etiqueta_sub_id;
*/

-- Eliminar etiquetas nuevas creadas hoy:
/*
DELETE FROM etiquetas WHERE DATE(created_at) = CURDATE();
*/

-- ============================================================================
-- LIMPIAR DESPUÉS DE VERIFICAR QUE TODO ESTÁ BIEN (OPCIONAL)
-- ============================================================================

-- DROP TABLE IF EXISTS elementos_backup_20241209;
-- DROP TABLE IF EXISTS etiquetas_backup_20241209;
-- DROP PROCEDURE IF EXISTS fix_subetiquetas;
