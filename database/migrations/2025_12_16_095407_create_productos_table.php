<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `producto_base_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distribuidor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `n_colada` varchar(255) DEFAULT NULL,
  `colada_id` bigint(20) UNSIGNED DEFAULT NULL,
  `n_paquete` varchar(255) DEFAULT NULL,
  `peso_inicial` decimal(10,2) NOT NULL,
  `peso_stock` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `fecha_consumido` datetime DEFAULT NULL,
  `consumido_by` bigint(20) UNSIGNED DEFAULT NULL,
  `otros` text DEFAULT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `entrada_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `ubicacion_id` (`ubicacion_id`),
  ADD KEY `maquina_id` (`maquina_id`),
  ADD KEY `fk_productos_productos_base` (`producto_base_id`),
  ADD KEY `productos_distribuidor_id_foreign` (`distribuidor_id`),
  ADD KEY `fk_productos_entrada_id` (`entrada_id`),
  ADD KEY `fk_updated_by_productos` (`updated_by`),
  ADD KEY `consumido_by` (`consumido_by`),
  ADD KEY `fk_productos_obra` (`obra_id`),
  ADD KEY `productos_fabricante_id_foreign` (`fabricante_id`),
  ADD KEY `productos_colada_id_foreign` (`colada_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
