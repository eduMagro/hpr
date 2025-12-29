<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `albaranes_venta_productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salida_almacen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `albaran_linea_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `peso_kg` decimal(10,2) DEFAULT NULL,
  `cantidad` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `albaranes_venta_productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_salida_producto` (`salida_almacen_id`,`producto_id`),
  ADD KEY `fk_avp_linea` (`albaran_linea_id`),
  ADD KEY `fk_avp_producto` (`producto_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `albaranes_venta_productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
