<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pedidos_globales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad_total` decimal(12,2) NOT NULL,
  `precio_referencia` decimal(12,4) DEFAULT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distribuidor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedidos_globales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_pedidos_globales_distribuidor` (`distribuidor_id`),
  ADD KEY `pedidos_globales_fabricante_id_foreign` (`fabricante_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedidos_globales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
