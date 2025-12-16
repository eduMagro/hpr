<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `albaranes_venta_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `albaran_id` bigint(20) UNSIGNED NOT NULL,
  `pedido_linea_id` bigint(20) UNSIGNED NOT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_unitario` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `albaranes_venta_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albaranes_venta_lineas_albaran_id_foreign` (`albaran_id`),
  ADD KEY `albaranes_venta_lineas_pedido_linea_id_foreign` (`pedido_linea_id`),
  ADD KEY `albaranes_venta_lineas_producto_base_id_foreign` (`producto_base_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `albaranes_venta_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
