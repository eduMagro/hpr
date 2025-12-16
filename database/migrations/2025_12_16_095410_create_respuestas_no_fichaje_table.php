<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `respuestas_no_fichaje` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asignacion_turno_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL COMMENT 'Si es entrada o salida',
  `motivo` enum('olvido','no_trabajo','problema_app','otro') NOT NULL,
  `fue_a_trabajar` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `notificado_at` timestamp NULL DEFAULT NULL COMMENT 'Cuando se envió la notificación',
  `respondido_at` timestamp NULL DEFAULT NULL COMMENT 'Cuando respondió el usuario',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `respuestas_no_fichaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_respuestas_asignacion` (`asignacion_turno_id`),
  ADD KEY `idx_respuestas_user` (`user_id`),
  ADD KEY `idx_respuestas_fecha` (`created_at`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `respuestas_no_fichaje`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
