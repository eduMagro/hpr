<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `subpaquetes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `elemento_id` bigint(20) NOT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `dimensiones` varchar(255) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `subpaquetes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elemento_id` (`elemento_id`),
  ADD KEY `planilla_id` (`planilla_id`),
  ADD KEY `subpaquetes_paquete_id_foreign` (`paquete_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `subpaquetes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
