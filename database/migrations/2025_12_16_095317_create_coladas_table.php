<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `coladas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero_colada` varchar(100) NOT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `codigo_adherencia` varchar(255) DEFAULT NULL,
  `documento` varchar(255) DEFAULT NULL COMMENT 'Ruta al PDF con toda la documentación',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `coladas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coladas_numero_producto_unique` (`numero_colada`,`producto_base_id`),
  ADD KEY `coladas_producto_base_id_foreign` (`producto_base_id`),
  ADD KEY `coladas_fabricante_id_foreign` (`fabricante_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `coladas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
