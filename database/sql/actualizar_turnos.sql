-- =====================================================
-- Actualizar tabla turnos existente
-- Ejecutar solo si la tabla ya existe con estructura antigua
-- =====================================================

-- Verificar si las columnas nuevas no existen y agregarlas
ALTER TABLE `turnos`
ADD COLUMN IF NOT EXISTS `hora_inicio` TIME NULL AFTER `nombre`,
ADD COLUMN IF NOT EXISTS `hora_fin` TIME NULL AFTER `hora_inicio`,
ADD COLUMN IF NOT EXISTS `offset_dias_inicio` TINYINT NOT NULL DEFAULT 0 AFTER `hora_fin`,
ADD COLUMN IF NOT EXISTS `offset_dias_fin` TINYINT NOT NULL DEFAULT 0 AFTER `offset_dias_inicio`,
ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `offset_dias_fin`,
ADD COLUMN IF NOT EXISTS `orden` INT NOT NULL DEFAULT 0 AFTER `activo`,
ADD COLUMN IF NOT EXISTS `color` VARCHAR(7) NULL AFTER `orden`,
ADD INDEX IF NOT EXISTS `idx_turnos_activo` (`activo`);

-- Migrar datos de columnas antiguas a nuevas (si existen)
UPDATE `turnos` SET
    `hora_inicio` = COALESCE(`hora_inicio`, `hora_entrada`),
    `hora_fin` = COALESCE(`hora_fin`, `hora_salida`),
    `offset_dias_inicio` = COALESCE(`offset_dias_inicio`, `entrada_offset`, 0),
    `offset_dias_fin` = COALESCE(`offset_dias_fin`, `salida_offset`, 0)
WHERE `hora_inicio` IS NULL OR `hora_fin` IS NULL;

-- Opcional: eliminar columnas antiguas (comentado por seguridad)
-- ALTER TABLE `turnos` DROP COLUMN IF EXISTS `hora_entrada`;
-- ALTER TABLE `turnos` DROP COLUMN IF EXISTS `entrada_offset`;
-- ALTER TABLE `turnos` DROP COLUMN IF EXISTS `hora_salida`;
-- ALTER TABLE `turnos` DROP COLUMN IF EXISTS `salida_offset`;
