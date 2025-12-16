<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `pedido_global_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distribuidor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso_total` decimal(10,2) DEFAULT NULL,
  `fecha_pedido` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `estado` varchar(50) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_unico` (`codigo`),
  ADD KEY `fk_pedidos_compras_pedido_global` (`pedido_global_id`),
  ADD KEY `pedidos_fabricante_id_foreign` (`fabricante_id`),
  ADD KEY `pedidos_distribuidor_id_foreign` (`distribuidor_id`),
  ADD KEY `fk_pedidos_created_by` (`created_by`),
  ADD KEY `fk_pedidos_updated_by` (`updated_by`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedidos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
