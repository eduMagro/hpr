<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `pedido_producto_coladas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `colada_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_producto_id` bigint(20) UNSIGNED NOT NULL,
  `colada` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bulto` decimal(15,3) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedido_producto_coladas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_producto_coladas_pedido_producto_id_foreign` (`pedido_producto_id`),
  ADD KEY `pedido_producto_coladas_user_id_foreign` (`user_id`),
  ADD KEY `pedido_producto_coladas_colada_id_foreign` (`colada_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `pedido_producto_coladas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
