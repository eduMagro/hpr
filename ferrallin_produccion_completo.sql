-- ============================================================================
-- SCRIPT COMPLETO PARA DESPLEGAR FERRALLIN EN PRODUCCIÓN
-- ============================================================================
-- Este script incluye:
-- 1. Migraciones de base de datos (tablas de chat)
-- 2. Permisos en la tabla users
-- 3. Índices para optimización
-- 4. Inserción en tabla secciones para que aparezca en el dashboard
-- ============================================================================
-- IMPORTANTE: Ejecutar en phpMyAdmin en la base de datos de producción
-- ============================================================================

-- ============================================================================
-- PARTE 1: MIGRACIONES - TABLAS DE CHAT
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
-- PARTE 2: PERMISOS EN TABLA USERS
-- ============================================================================

-- Verificar y añadir columna puede_usar_asistente
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

-- Verificar y añadir columna puede_modificar_bd
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
-- PARTE 3: ÍNDICES PARA OPTIMIZACIÓN
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
ALTER TABLE `chat_consultas_sql`
  ADD FULLTEXT INDEX IF NOT EXISTS `idx_consulta` (`consulta_sql`);


-- ============================================================================
-- PARTE 4: INSERTAR SECCIÓN EN EL DASHBOARD
-- ============================================================================
-- ⚠️ ESTE ES EL PASO CRÍTICO QUE FALTA EN PRODUCCIÓN ⚠️
-- Esta inserción hace que aparezca el icono de FERRALLIN en el dashboard
-- ============================================================================

-- Insertar sección del Asistente Virtual (si no existe)
INSERT INTO `secciones` (`nombre`, `ruta`, `icono`, `mostrar_en_dashboard`, `created_at`, `updated_at`)
SELECT 'Asistente Virtual', 'asistente.index', 'imagenes/iconos/asistente.png', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `secciones` WHERE `nombre` = 'Asistente Virtual'
);


-- ============================================================================
-- PARTE 5: REGISTRAR MIGRACIONES EN LARAVEL
-- ============================================================================

-- Registrar las migraciones en la tabla de Laravel (si existe)
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2025_11_12_155044_create_chat_tables', 1),
('2025_11_12_182539_add_asistente_permissions_to_users_table', 1),
('2025_11_12_195006_add_indexes_to_chat_tables', 1);


-- ============================================================================
-- PARTE 6: CONFIGURAR PERMISOS INICIALES (OPCIONAL)
-- ============================================================================
-- Estos comandos son opcionales según la configuración de tu empresa
-- ============================================================================

-- Dar permisos a todos los usuarios de oficina para usar el asistente
-- UPDATE `users`
-- SET `puede_usar_asistente` = 1
-- WHERE `rol` = 'oficina';

-- Dar permisos para modificar BD solo a administradores específicos
-- UPDATE `users`
-- SET `puede_modificar_bd` = 1
-- WHERE `email` IN ('admin@ejemplo.com', 'superadmin@ejemplo.com');


-- ============================================================================
-- VERIFICACIÓN (OPCIONAL - DESCOMENTAR PARA EJECUTAR)
-- ============================================================================

-- Verificar que las tablas se crearon
-- SHOW TABLES LIKE 'chat_%';

-- Verificar que la sección se insertó en el dashboard
-- SELECT * FROM `secciones` WHERE `nombre` = 'Asistente Virtual';

-- Ver estructura de las tablas de chat
-- DESCRIBE chat_conversaciones;
-- DESCRIBE chat_mensajes;
-- DESCRIBE chat_consultas_sql;

-- Verificar columnas añadidas a users
-- DESCRIBE users;

-- Ver los índices creados
-- SHOW INDEX FROM chat_conversaciones;
-- SHOW INDEX FROM chat_mensajes;
-- SHOW INDEX FROM chat_consultas_sql;

-- Verificar permisos de usuarios
-- SELECT id, name, email, puede_usar_asistente, puede_modificar_bd FROM users LIMIT 10;


-- ============================================================================
-- IMPORTANTE: DESPUÉS DE EJECUTAR ESTE SCRIPT
-- ============================================================================
-- 1. Asegúrate de que los archivos de código están actualizados en producción:
--    - git pull origin [rama]
--
-- 2. Si usas caché de configuración en producción, límpiala:
--    - php artisan config:clear
--    - php artisan cache:clear
--    - php artisan route:clear
--    - php artisan view:clear
--
-- 3. Si usas assets compilados (Vite/Mix), reconstruye:
--    - npm run build (en producción)
--
-- 4. Verifica que el icono existe en: public/imagenes/iconos/asistente.png
--
-- 5. Asegúrate de que la variable OPENAI_API_KEY está configurada en el .env
--
-- 6. Si usas permisos por departamento, vincula el departamento a la sección:
--    INSERT INTO departamento_seccion (departamento_id, seccion_id)
--    SELECT [ID_DEPARTAMENTO], id FROM secciones WHERE nombre = 'Asistente Virtual';
-- ============================================================================

-- ============================================================================
-- FIN DEL SCRIPT DE DESPLIEGUE DE FERRALLIN
-- ============================================================================
