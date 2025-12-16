<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `etiqueta_historial` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `etiqueta_id` bigint(20) UNSIGNED NOT NULL,
  `etiqueta_sub_id` varchar(50) NOT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `snapshot_etiqueta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_etiqueta`)),
  `snapshot_elementos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot_elementos`)),
  `snapshot_productos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot_productos`)),
  `snapshot_planilla` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot_planilla`)),
  `paquete_id_anterior` bigint(20) UNSIGNED DEFAULT NULL,
  `revertido` tinyint(1) NOT NULL DEFAULT 0,
  `revertido_at` timestamp NULL DEFAULT NULL,
  `revertido_por` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `etiqueta_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_etiqueta_historial_etiqueta` (`etiqueta_id`),
  ADD KEY `idx_etiqueta_historial_sub_id` (`etiqueta_sub_id`),
  ADD KEY `idx_etiqueta_historial_maquina` (`maquina_id`),
  ADD KEY `idx_etiqueta_historial_created` (`created_at`),
  ADD KEY `idx_etiqueta_historial_revertido` (`revertido`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `etiqueta_historial`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
