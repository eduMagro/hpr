<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `eventos_ficticios_obra` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trabajador_ficticio_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `eventos_ficticios_obra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eventos_ficticios_obra_fecha_obra_id_index` (`fecha`,`obra_id`),
  ADD KEY `eventos_ficticios_obra_trabajador_ficticio_id_foreign` (`trabajador_ficticio_id`),
  ADD KEY `eventos_ficticios_obra_obra_id_foreign` (`obra_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `eventos_ficticios_obra`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
