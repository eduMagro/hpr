<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `grupos_resumen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `planilla_id` bigint(20) UNSIGNED NOT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `diametro` decimal(8,2) NOT NULL,
  `dimensiones` varchar(255) DEFAULT NULL,
  `total_elementos` int(11) NOT NULL DEFAULT 0,
  `peso_total` decimal(10,3) NOT NULL DEFAULT 0.000,
  `total_etiquetas` int(11) NOT NULL DEFAULT 0,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `grupos_resumen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grupos_resumen_codigo_unique` (`codigo`),
  ADD KEY `grupos_resumen_planilla_id_index` (`planilla_id`),
  ADD KEY `grupos_resumen_maquina_id_index` (`maquina_id`),
  ADD KEY `grupos_resumen_activo_index` (`activo`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `grupos_resumen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
