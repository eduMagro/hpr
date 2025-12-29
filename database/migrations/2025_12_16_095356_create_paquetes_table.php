<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `paquetes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `planilla_id` bigint(20) DEFAULT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','asignado_a_salida','en_reparto','enviado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `subido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `paquetes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `ubicacion_id` (`ubicacion_id`),
  ADD KEY `paquetes_planilla_id_foreign` (`planilla_id`),
  ADD KEY `fk_paquetes_nave` (`nave_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `paquetes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
