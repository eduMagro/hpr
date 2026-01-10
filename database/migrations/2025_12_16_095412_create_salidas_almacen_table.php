<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `salidas_almacen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('pendiente','en_ruta','completada','activa','cancelada') DEFAULT 'pendiente',
  `camionero_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `salidas_almacen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `salidas_almacen_camionero_id_foreign` (`camionero_id`),
  ADD KEY `salidas_almacen_created_by_foreign` (`created_by`),
  ADD KEY `salidas_almacen_updated_by_foreign` (`updated_by`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `salidas_almacen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
