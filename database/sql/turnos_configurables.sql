-- =====================================================
-- Sistema de Turnos Configurables
-- =====================================================

-- Tabla de configuración de turnos
CREATE TABLE IF NOT EXISTS `turnos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL COMMENT 'Nombre del turno (ej: Mañana, Tarde, Noche)',
  `hora_inicio` TIME NOT NULL COMMENT 'Hora de inicio del turno (ej: 06:00:00)',
  `hora_fin` TIME NOT NULL COMMENT 'Hora de fin del turno (ej: 14:00:00)',
  `offset_dias_inicio` TINYINT NOT NULL DEFAULT 0 COMMENT 'Offset en días para el inicio (-1 = día anterior, 0 = mismo día, 1 = día siguiente)',
  `offset_dias_fin` TINYINT NOT NULL DEFAULT 0 COMMENT 'Offset en días para el fin (-1 = día anterior, 0 = mismo día, 1 = día siguiente)',
  `activo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Si el turno está habilitado',
  `orden` INT NOT NULL DEFAULT 0 COMMENT 'Orden de visualización',
  `color` VARCHAR(7) NULL COMMENT 'Color hex para visualización (ej: #3b82f6)',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_turnos_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar turnos por defecto
INSERT INTO `turnos` (`nombre`, `hora_inicio`, `hora_fin`, `offset_dias_inicio`, `offset_dias_fin`, `activo`, `orden`, `color`) VALUES
('Mañana', '06:00:00', '14:00:00', 0, 0, 1, 1, '#3b82f6'),
('Tarde', '14:00:00', '22:00:00', 0, 0, 1, 2, '#f59e0b'),
('Noche', '22:00:00', '06:00:00', 0, 1, 1, 3, '#8b5cf6')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Comentarios sobre el uso:
--
-- TURNO DE MAÑANA (06:00 - 14:00):
--   offset_dias_inicio = 0 (comienza a las 06:00 del mismo día)
--   offset_dias_fin = 0 (termina a las 14:00 del mismo día)
--   Ejemplo: Lunes 06:00 → Lunes 14:00
--
-- TURNO DE TARDE (14:00 - 22:00):
--   offset_dias_inicio = 0 (comienza a las 14:00 del mismo día)
--   offset_dias_fin = 0 (termina a las 22:00 del mismo día)
--   Ejemplo: Lunes 14:00 → Lunes 22:00
--
-- TURNO DE NOCHE (22:00 - 06:00):
--   offset_dias_inicio = 0 (comienza a las 22:00 del mismo día)
--   offset_dias_fin = 1 (termina a las 06:00 del DÍA SIGUIENTE)
--   Ejemplo: Lunes 22:00 → Martes 06:00
--   IMPORTANTE: El turno de noche del LUNES comienza el DOMINGO a las 22:00
--               y termina el LUNES a las 06:00
