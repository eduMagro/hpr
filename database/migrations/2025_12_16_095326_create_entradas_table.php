<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `entradas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `albaran` varchar(255) NOT NULL,
  `codigo_sage` varchar(50) DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) NOT NULL DEFAULT 'abierto',
  `pdf_albaran` varchar(255) DEFAULT NULL,
  `otros` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_entradas_usuario` (`usuario_id`),
  ADD KEY `entradas_pedido_id_foreign` (`pedido_id`),
  ADD KEY `fk_entrada_linea` (`pedido_producto_id`),
  ADD KEY `fk_entradas_nave` (`nave_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `entradas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
