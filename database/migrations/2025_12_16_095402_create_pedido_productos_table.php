<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pedido_productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pedido_id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `pedido_global_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_manual` varchar(255) DEFAULT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad_recepcionada` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha_estimada_entrega` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedido_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_base_id` (`producto_base_id`),
  ADD KEY `fk_pedido_productos_pedido_global` (`pedido_global_id`),
  ADD KEY `idx_pedido_producto_obra_id` (`obra_id`),
  ADD KEY `fk_pedido_productos_pedido_id` (`pedido_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedido_productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
