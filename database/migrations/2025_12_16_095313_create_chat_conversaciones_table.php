<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `chat_conversaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `chat_conversaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_conversaciones_user_id_ultima_actividad_index` (`user_id`,`ultima_actividad`),
  ADD KEY `idx_user_actividad` (`user_id`,`ultima_actividad`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `chat_conversaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
