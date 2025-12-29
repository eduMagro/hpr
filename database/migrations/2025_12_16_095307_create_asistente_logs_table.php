<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `asistente_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `pregunta` text NOT NULL,
  `respuesta` text NOT NULL,
  `tipo_consulta` varchar(50) DEFAULT NULL,
  `coste` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `tokens_input` int(11) NOT NULL DEFAULT 0,
  `tokens_output` int(11) NOT NULL DEFAULT 0,
  `duracion_segundos` decimal(8,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `asistente_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_tipo_consulta` (`tipo_consulta`),
  ADD KEY `idx_created_at` (`created_at`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `asistente_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
