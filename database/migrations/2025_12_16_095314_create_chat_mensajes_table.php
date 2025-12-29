<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `chat_mensajes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversacion_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('user','assistant','system') NOT NULL DEFAULT 'user',
  `contenido` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `chat_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_mensajes_conversacion_id_created_at_index` (`conversacion_id`,`created_at`),
  ADD KEY `idx_conversacion` (`conversacion_id`),
  ADD KEY `idx_conversacion_role` (`conversacion_id`,`role`),
  ADD KEY `idx_created_at` (`created_at`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `chat_mensajes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
