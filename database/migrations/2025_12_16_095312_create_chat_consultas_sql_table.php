<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `chat_consultas_sql` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mensaje_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `consulta_sql` text NOT NULL,
  `consulta_natural` text NOT NULL,
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resultados`)),
  `filas_afectadas` int(11) NOT NULL DEFAULT 0,
  `exitosa` tinyint(1) NOT NULL DEFAULT 1,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `chat_consultas_sql`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_consultas_sql_mensaje_id_foreign` (`mensaje_id`),
  ADD KEY `chat_consultas_sql_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_auditoria` (`user_id`,`exitosa`,`created_at`),
  ADD KEY `idx_consulta` (`consulta_sql`(768));
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `chat_consultas_sql`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
