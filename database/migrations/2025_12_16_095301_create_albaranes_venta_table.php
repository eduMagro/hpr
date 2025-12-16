<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `albaranes_venta` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salida_id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `fecha` date NOT NULL DEFAULT curdate(),
  `estado` enum('pendiente','servido','cancelado') DEFAULT 'pendiente',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `albaranes_venta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `albaranes_venta_salida_id_foreign` (`salida_id`),
  ADD KEY `albaranes_venta_cliente_id_foreign` (`cliente_id`),
  ADD KEY `albaranes_venta_created_by_foreign` (`created_by`),
  ADD KEY `albaranes_venta_updated_by_foreign` (`updated_by`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `albaranes_venta`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
