<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pedidos_almacen_venta_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pedido_almacen_venta_id` bigint(20) UNSIGNED NOT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `unidad_medida` varchar(10) DEFAULT 'kg',
  `cantidad_solicitada` decimal(10,2) DEFAULT 0.00,
  `cantidad_servida` decimal(10,2) DEFAULT 0.00,
  `cantidad_pendiente` decimal(10,2) DEFAULT 0.00,
  `kg_por_bulto_override` decimal(8,2) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedidos_almacen_venta_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_almacen_venta_id` (`pedido_almacen_venta_id`),
  ADD KEY `producto_base_id` (`producto_base_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedidos_almacen_venta_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
