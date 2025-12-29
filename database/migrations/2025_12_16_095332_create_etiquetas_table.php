<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `etiquetas` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `etiqueta_sub_id` varchar(64) DEFAULT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `operario1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `operario2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soldador1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soldador2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ensamblador1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ensamblador2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `numero_etiqueta` int(11) DEFAULT NULL,
  `peso` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `fecha_inicio_ensamblado` datetime DEFAULT NULL,
  `fecha_finalizacion_ensamblado` datetime DEFAULT NULL,
  `fecha_inicio_soldadura` datetime DEFAULT NULL,
  `fecha_finalizacion_soldadura` datetime DEFAULT NULL,
  `estado` varchar(255) DEFAULT 'pendiente',
  `grupo_resumen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `subido` tinyint(1) DEFAULT 0,
  `impresa` tinyint(1) DEFAULT 0 COMMENT 'Indica si la etiqueta ya fue impresa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `etiquetas_etiqueta_sub_id_unique` (`etiqueta_sub_id`),
  ADD KEY `planilla_id` (`planilla_id`),
  ADD KEY `fk_etiquetas_producto_id` (`producto_id`),
  ADD KEY `fk_etiquetas_producto_id_2` (`producto_id_2`),
  ADD KEY `etiquetas_ubicacion_id_foreign` (`ubicacion_id`),
  ADD KEY `etiquetas_soldador1_foreign` (`soldador1_id`),
  ADD KEY `etiquetas_soldador2_foreign` (`soldador2_id`),
  ADD KEY `etiquetas_ensamblador1_foreign` (`ensamblador1_id`),
  ADD KEY `etiquetas_ensamblador2_foreign` (`ensamblador2_id`),
  ADD KEY `fk_etiquetas_operario1` (`operario1_id`),
  ADD KEY `fk_etiquetas_operario2` (`operario2_id`),
  ADD KEY `etiquetas_paquete_id_foreign` (`paquete_id`),
  ADD KEY `etiquetas_grupo_resumen_id_index` (`grupo_resumen_id`),
  ADD KEY `idx_etiquetas_impresa` (`impresa`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `etiquetas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
