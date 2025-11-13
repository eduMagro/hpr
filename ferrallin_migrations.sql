-- ============================================================================
-- MIGRACIONES DE FERRALLIN - ASISTENTE VIRTUAL CON IA
-- ============================================================================
-- Ejecutar en phpMyAdmin en el orden que aparecen
-- Basado en las migraciones de Laravel
-- ============================================================================

-- ============================================================================
-- 1. MIGRACIÓN: 2025_11_12_155044_create_chat_tables
-- Crea las tablas principales para el sistema de chat
-- ============================================================================

-- Tabla: chat_conversaciones
CREATE TABLE IF NOT EXISTS `chat_conversaciones` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `ultima_actividad` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_conversaciones_user_id_foreign` (`user_id`),
  KEY `chat_conversaciones_user_id_ultima_actividad_index` (`user_id`, `ultima_actividad`),
  CONSTRAINT `chat_conversaciones_user_id_foreign`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: chat_mensajes
CREATE TABLE IF NOT EXISTS `chat_mensajes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversacion_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('user','assistant','system') NOT NULL DEFAULT 'user',
  `contenido` text NOT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Para guardar consultas SQL, resultados, etc.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_mensajes_conversacion_id_foreign` (`conversacion_id`),
  KEY `chat_mensajes_conversacion_id_created_at_index` (`conversacion_id`, `created_at`),
  CONSTRAINT `chat_mensajes_conversacion_id_foreign`
    FOREIGN KEY (`conversacion_id`)
    REFERENCES `chat_conversaciones` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: chat_consultas_sql (para auditoría)
CREATE TABLE IF NOT EXISTS `chat_consultas_sql` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mensaje_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `consulta_sql` text NOT NULL,
  `consulta_natural` text NOT NULL COMMENT 'La pregunta del usuario',
  `resultados` json DEFAULT NULL,
  `filas_afectadas` int(11) NOT NULL DEFAULT 0,
  `exitosa` tinyint(1) NOT NULL DEFAULT 1,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_consultas_sql_mensaje_id_foreign` (`mensaje_id`),
  KEY `chat_consultas_sql_user_id_foreign` (`user_id`),
  KEY `chat_consultas_sql_user_id_created_at_index` (`user_id`, `created_at`),
  CONSTRAINT `chat_consultas_sql_mensaje_id_foreign`
    FOREIGN KEY (`mensaje_id`)
    REFERENCES `chat_mensajes` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `chat_consultas_sql_user_id_foreign`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. MIGRACIÓN: 2025_11_12_182539_add_asistente_permissions_to_users_table
-- Añade permisos de asistente a la tabla users
-- ============================================================================

-- Verificar si las columnas ya existen antes de añadirlas
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'puede_usar_asistente';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' tinyint(1) NOT NULL DEFAULT 1 AFTER email;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Añadir columna puede_modificar_bd
SET @columnname = 'puede_modificar_bd';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' tinyint(1) NOT NULL DEFAULT 0 AFTER puede_usar_asistente;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;


-- ============================================================================
-- 3. MIGRACIÓN: 2025_11_12_195006_add_indexes_to_chat_tables
-- Añade índices para optimizar el rendimiento
-- ============================================================================

-- Índices para chat_conversaciones
ALTER TABLE `chat_conversaciones`
  ADD INDEX IF NOT EXISTS `idx_user_actividad` (`user_id`, `ultima_actividad`);

-- Índices para chat_mensajes
ALTER TABLE `chat_mensajes`
  ADD INDEX IF NOT EXISTS `idx_conversacion` (`conversacion_id`),
  ADD INDEX IF NOT EXISTS `idx_conversacion_role` (`conversacion_id`, `role`),
  ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- Índices para chat_consultas_sql
ALTER TABLE `chat_consultas_sql`
  ADD INDEX IF NOT EXISTS `idx_user` (`user_id`),
  ADD INDEX IF NOT EXISTS `idx_auditoria` (`user_id`, `exitosa`, `created_at`);

-- Índice FULLTEXT para búsqueda en consultas SQL
-- Nota: Si la tabla tiene registros, esto puede tardar
ALTER TABLE `chat_consultas_sql`
  ADD FULLTEXT INDEX IF NOT EXISTS `idx_consulta` (`consulta_sql`);


-- ============================================================================
-- VERIFICACIÓN Y REGISTRO EN TABLA DE MIGRACIONES
-- ============================================================================

-- Registrar las migraciones en la tabla de Laravel (si existe)
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2025_11_12_155044_create_chat_tables', 1),
('2025_11_12_182539_add_asistente_permissions_to_users_table', 1),
('2025_11_12_195006_add_indexes_to_chat_tables', 1);


-- ============================================================================
-- CONSULTAS DE VERIFICACIÓN (OPCIONAL)
-- ============================================================================

-- Verificar que las tablas se crearon correctamente
-- SHOW TABLES LIKE 'chat_%';

-- Ver estructura de las tablas
-- DESCRIBE chat_conversaciones;
-- DESCRIBE chat_mensajes;
-- DESCRIBE chat_consultas_sql;

-- Ver los índices creados
-- SHOW INDEX FROM chat_conversaciones;
-- SHOW INDEX FROM chat_mensajes;
-- SHOW INDEX FROM chat_consultas_sql;

-- Verificar columnas añadidas a users
-- DESCRIBE users;

-- ============================================================================
-- FIN DE LAS MIGRACIONES DE FERRALLIN
-- ============================================================================
