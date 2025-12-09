-- ============================================================================
-- SCRIPT PARA DETECTAR Y CORREGIR ETIQUETAS CON ELEMENTOS EN DIFERENTES MAQUINAS
-- ============================================================================
-- Este script implementa la misma lógica que SubEtiquetaService.php
-- Regla: No puede haber una etiqueta_sub_id con elementos en máquinas diferentes
-- Solución: Crear nuevas subetiquetas para separar los elementos por máquina
-- ============================================================================

-- ============================================================================
-- PARTE 1: CONSULTAS DE DIAGNÓSTICO (SOLO LECTURA)
-- ============================================================================

-- 1.1 Ver todas las subetiquetas que tienen elementos en más de una máquina
SELECT
    e.etiqueta_sub_id,
    et.codigo AS codigo_etiqueta,
    COUNT(DISTINCT e.maquina_id) AS num_maquinas,
    GROUP_CONCAT(DISTINCT e.maquina_id ORDER BY e.maquina_id) AS maquinas,
    GROUP_CONCAT(DISTINCT m.codigo ORDER BY e.maquina_id) AS codigos_maquinas,
    COUNT(e.id) AS total_elementos,
    SUM(e.peso) AS peso_total
FROM elementos e
INNER JOIN etiquetas et ON e.etiqueta_id = et.id
LEFT JOIN maquinas m ON e.maquina_id = m.id
WHERE e.etiqueta_sub_id IS NOT NULL
    AND e.maquina_id IS NOT NULL
    AND e.deleted_at IS NULL
GROUP BY e.etiqueta_sub_id, et.codigo
HAVING COUNT(DISTINCT e.maquina_id) > 1
ORDER BY num_maquinas DESC, e.etiqueta_sub_id;

-- 1.2 Ver el detalle de los elementos afectados
SELECT
    e.id AS elemento_id,
    e.codigo AS elemento_codigo,
    e.etiqueta_sub_id,
    et.codigo AS codigo_etiqueta_padre,
    e.maquina_id,
    m.codigo AS maquina_codigo,
    m.nombre AS maquina_nombre,
    e.peso,
    e.barras,
    e.estado
FROM elementos e
INNER JOIN etiquetas et ON e.etiqueta_id = et.id
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
ORDER BY e.etiqueta_sub_id, e.maquina_id, e.id;

-- 1.3 Resumen de cuántas subetiquetas necesitan corrección
SELECT
    COUNT(*) AS subetiquetas_con_problema,
    SUM(total_elementos) AS elementos_afectados
FROM (
    SELECT
        etiqueta_sub_id,
        COUNT(*) AS total_elementos
    FROM elementos
    WHERE etiqueta_sub_id IS NOT NULL
        AND maquina_id IS NOT NULL
        AND deleted_at IS NULL
    GROUP BY etiqueta_sub_id
    HAVING COUNT(DISTINCT maquina_id) > 1
) sub;


-- ============================================================================
-- PARTE 2: PROCEDIMIENTO PARA CORREGIR SUBETIQUETAS
-- ============================================================================

DROP PROCEDURE IF EXISTS corregir_subetiquetas_maquinas;

DELIMITER //

CREATE PROCEDURE corregir_subetiquetas_maquinas()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_etiqueta_sub_id VARCHAR(255);
    DECLARE v_codigo_padre VARCHAR(255);
    DECLARE v_etiqueta_id INT;
    DECLARE v_maquina_id INT;
    DECLARE v_elemento_id INT;
    DECLARE v_nuevo_sufijo INT;
    DECLARE v_nueva_sub_id VARCHAR(255);
    DECLARE v_primera_maquina INT DEFAULT NULL;
    DECLARE v_planilla_id INT;
    DECLARE v_peso_elemento DECIMAL(10,3);

    -- Cursor para obtener subetiquetas con elementos en múltiples máquinas
    DECLARE cur_subetiquetas CURSOR FOR
        SELECT DISTINCT e.etiqueta_sub_id
        FROM elementos e
        WHERE e.etiqueta_sub_id IS NOT NULL
            AND e.maquina_id IS NOT NULL
            AND e.deleted_at IS NULL
        GROUP BY e.etiqueta_sub_id
        HAVING COUNT(DISTINCT e.maquina_id) > 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Crear tabla temporal para log
    DROP TEMPORARY TABLE IF EXISTS log_correcciones;
    CREATE TEMPORARY TABLE log_correcciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        etiqueta_sub_id_original VARCHAR(255),
        etiqueta_sub_id_nueva VARCHAR(255),
        elemento_id INT,
        maquina_id INT,
        accion VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    OPEN cur_subetiquetas;

    read_loop: LOOP
        FETCH cur_subetiquetas INTO v_etiqueta_sub_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Obtener el código padre (parte antes del punto)
        SET v_codigo_padre = SUBSTRING_INDEX(v_etiqueta_sub_id, '.', 1);

        -- Obtener etiqueta_id y planilla_id de la primera fila
        SELECT etiqueta_id, planilla_id INTO v_etiqueta_id, v_planilla_id
        FROM elementos
        WHERE etiqueta_sub_id = v_etiqueta_sub_id
            AND deleted_at IS NULL
        LIMIT 1;

        -- Obtener la primera máquina (los elementos de esta máquina mantienen la subetiqueta original)
        SELECT maquina_id INTO v_primera_maquina
        FROM elementos
        WHERE etiqueta_sub_id = v_etiqueta_sub_id
            AND maquina_id IS NOT NULL
            AND deleted_at IS NULL
        ORDER BY maquina_id
        LIMIT 1;

        -- Log: elementos que mantienen la subetiqueta original
        INSERT INTO log_correcciones (etiqueta_sub_id_original, etiqueta_sub_id_nueva, elemento_id, maquina_id, accion)
        SELECT v_etiqueta_sub_id, v_etiqueta_sub_id, id, maquina_id, 'MANTIENE_ORIGINAL'
        FROM elementos
        WHERE etiqueta_sub_id = v_etiqueta_sub_id
            AND maquina_id = v_primera_maquina
            AND deleted_at IS NULL;

        -- Procesar cada máquina diferente a la primera
        BEGIN
            DECLARE done_maq INT DEFAULT FALSE;
            DECLARE cur_maquinas CURSOR FOR
                SELECT DISTINCT maquina_id
                FROM elementos
                WHERE etiqueta_sub_id = v_etiqueta_sub_id
                    AND maquina_id IS NOT NULL
                    AND maquina_id != v_primera_maquina
                    AND deleted_at IS NULL;
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done_maq = TRUE;

            OPEN cur_maquinas;

            maq_loop: LOOP
                FETCH cur_maquinas INTO v_maquina_id;
                IF done_maq THEN
                    LEAVE maq_loop;
                END IF;

                -- Obtener el siguiente sufijo disponible para este código padre
                SELECT COALESCE(MAX(
                    CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)
                ), 0) + 1
                INTO v_nuevo_sufijo
                FROM etiquetas
                WHERE codigo = v_codigo_padre
                    AND etiqueta_sub_id LIKE CONCAT(v_codigo_padre, '.%')
                    AND deleted_at IS NULL;

                -- También verificar en elementos por si hay subetiquetas sin registro en etiquetas
                SELECT GREATEST(v_nuevo_sufijo, COALESCE(MAX(
                    CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)
                ), 0) + 1)
                INTO v_nuevo_sufijo
                FROM elementos
                WHERE etiqueta_sub_id LIKE CONCAT(v_codigo_padre, '.%')
                    AND deleted_at IS NULL;

                -- Generar nueva subetiqueta
                SET v_nueva_sub_id = CONCAT(v_codigo_padre, '.', LPAD(v_nuevo_sufijo, 2, '0'));

                -- Asegurar que no existe
                WHILE EXISTS (SELECT 1 FROM etiquetas WHERE etiqueta_sub_id = v_nueva_sub_id AND deleted_at IS NULL)
                   OR EXISTS (SELECT 1 FROM elementos WHERE etiqueta_sub_id = v_nueva_sub_id AND deleted_at IS NULL) DO
                    SET v_nuevo_sufijo = v_nuevo_sufijo + 1;
                    SET v_nueva_sub_id = CONCAT(v_codigo_padre, '.', LPAD(v_nuevo_sufijo, 2, '0'));
                END WHILE;

                -- Calcular el peso total de los elementos que se van a mover
                SELECT COALESCE(SUM(peso), 0) INTO v_peso_elemento
                FROM elementos
                WHERE etiqueta_sub_id = v_etiqueta_sub_id
                    AND maquina_id = v_maquina_id
                    AND deleted_at IS NULL;

                -- Crear la nueva fila en etiquetas (copiando datos de la etiqueta padre)
                INSERT INTO etiquetas (
                    codigo, etiqueta_sub_id, planilla_id, nombre, estado, peso,
                    producto_id, producto_id_2, ubicacion_id,
                    operario1_id, operario2_id, soldador1_id, soldador2_id,
                    ensamblador1_id, ensamblador2_id, marca, paquete_id,
                    numero_etiqueta, fecha_inicio, fecha_finalizacion,
                    fecha_inicio_ensamblado, fecha_finalizacion_ensamblado,
                    fecha_inicio_soldadura, fecha_finalizacion_soldadura,
                    created_at, updated_at
                )
                SELECT
                    codigo, v_nueva_sub_id, planilla_id, nombre, estado, v_peso_elemento,
                    producto_id, producto_id_2, ubicacion_id,
                    operario1_id, operario2_id, soldador1_id, soldador2_id,
                    ensamblador1_id, ensamblador2_id, marca, paquete_id,
                    numero_etiqueta, NULL, NULL,
                    NULL, NULL,
                    NULL, NULL,
                    NOW(), NOW()
                FROM etiquetas
                WHERE id = v_etiqueta_id
                    AND deleted_at IS NULL
                LIMIT 1;

                -- Log: elementos que se van a mover
                INSERT INTO log_correcciones (etiqueta_sub_id_original, etiqueta_sub_id_nueva, elemento_id, maquina_id, accion)
                SELECT v_etiqueta_sub_id, v_nueva_sub_id, id, maquina_id, 'MOVIDO_A_NUEVA_SUB'
                FROM elementos
                WHERE etiqueta_sub_id = v_etiqueta_sub_id
                    AND maquina_id = v_maquina_id
                    AND deleted_at IS NULL;

                -- Actualizar los elementos de esta máquina con la nueva subetiqueta
                UPDATE elementos
                SET etiqueta_sub_id = v_nueva_sub_id,
                    updated_at = NOW()
                WHERE etiqueta_sub_id = v_etiqueta_sub_id
                    AND maquina_id = v_maquina_id
                    AND deleted_at IS NULL;

            END LOOP maq_loop;

            CLOSE cur_maquinas;
        END;

        -- Recalcular peso de la subetiqueta original
        UPDATE etiquetas et
        SET peso = (
            SELECT COALESCE(SUM(e.peso), 0)
            FROM elementos e
            WHERE e.etiqueta_sub_id = v_etiqueta_sub_id
                AND e.deleted_at IS NULL
        ),
        updated_at = NOW()
        WHERE et.etiqueta_sub_id = v_etiqueta_sub_id
            AND et.deleted_at IS NULL;

    END LOOP read_loop;

    CLOSE cur_subetiquetas;

    -- Mostrar resultados
    SELECT * FROM log_correcciones ORDER BY etiqueta_sub_id_original, maquina_id;

    -- Resumen
    SELECT
        accion,
        COUNT(*) AS total_elementos
    FROM log_correcciones
    GROUP BY accion;

END //

DELIMITER ;


-- ============================================================================
-- PARTE 3: EJECUCIÓN (DESCOMENTAR PARA EJECUTAR)
-- ============================================================================

-- IMPORTANTE: Primero ejecuta las consultas de diagnóstico (PARTE 1)
-- para verificar qué datos se van a modificar.

-- Cuando estés listo para corregir, descomenta la siguiente línea:
-- CALL corregir_subetiquetas_maquinas();


-- ============================================================================
-- PARTE 4: VERIFICACIÓN POST-CORRECCIÓN
-- ============================================================================

-- Después de ejecutar la corrección, ejecuta estas consultas para verificar:

-- 4.1 Verificar que ya no hay subetiquetas con elementos en múltiples máquinas
/*
SELECT
    e.etiqueta_sub_id,
    COUNT(DISTINCT e.maquina_id) AS num_maquinas
FROM elementos e
WHERE e.etiqueta_sub_id IS NOT NULL
    AND e.maquina_id IS NOT NULL
    AND e.deleted_at IS NULL
GROUP BY e.etiqueta_sub_id
HAVING COUNT(DISTINCT e.maquina_id) > 1;
*/
-- Si esta consulta devuelve 0 filas, la corrección fue exitosa.

-- 4.2 Ver las nuevas subetiquetas creadas (por fecha de hoy)
/*
SELECT
    et.codigo,
    et.etiqueta_sub_id,
    et.peso,
    COUNT(e.id) AS num_elementos,
    et.created_at
FROM etiquetas et
LEFT JOIN elementos e ON e.etiqueta_sub_id = et.etiqueta_sub_id AND e.deleted_at IS NULL
WHERE DATE(et.created_at) = CURDATE()
    AND et.deleted_at IS NULL
GROUP BY et.id
ORDER BY et.created_at DESC;
*/


-- ============================================================================
-- PARTE 5: ROLLBACK (EN CASO DE PROBLEMAS)
-- ============================================================================

-- Si necesitas revertir los cambios, puedes usar el log_correcciones
-- para identificar qué elementos fueron modificados.
-- NOTA: El procedimiento crea una tabla temporal que se pierde al cerrar la sesión.
-- Para un rollback real, necesitarías hacer un backup antes de ejecutar.

-- Crear backup antes de ejecutar:
/*
CREATE TABLE elementos_backup_subetiquetas AS
SELECT * FROM elementos WHERE deleted_at IS NULL;

CREATE TABLE etiquetas_backup_subetiquetas AS
SELECT * FROM etiquetas WHERE deleted_at IS NULL;
*/

-- Para restaurar (si algo sale mal):
/*
-- Restaurar elementos
UPDATE elementos e
INNER JOIN elementos_backup_subetiquetas b ON e.id = b.id
SET e.etiqueta_sub_id = b.etiqueta_sub_id,
    e.updated_at = NOW();

-- Eliminar etiquetas creadas hoy
DELETE FROM etiquetas
WHERE DATE(created_at) = CURDATE()
    AND etiqueta_sub_id LIKE '%.%';
*/
