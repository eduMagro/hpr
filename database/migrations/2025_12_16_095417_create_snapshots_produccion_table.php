<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `snapshots_produccion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo_operacion` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `orden_planillas_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`orden_planillas_data`)),
  `elementos_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`elementos_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `snapshots_produccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `snapshots_produccion_user_id_foreign` (`user_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `snapshots_produccion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
