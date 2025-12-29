<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `orden_planillas` (
  `id` int(10) UNSIGNED NOT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `maquina_id` bigint(20) UNSIGNED NOT NULL,
  `posicion` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `orden_planillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orden_planilla_maquina` (`maquina_id`),
  ADD KEY `idx_orden_planillas_planilla` (`planilla_id`),
  ADD KEY `idx_orden_planillas_planilla_maquina` (`planilla_id`,`maquina_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `orden_planillas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
