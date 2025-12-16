<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `elementos` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED DEFAULT NULL,
  `users_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `elaborado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=sin elaboración (barra estándar), 1=requiere elaboración',
  `orden_planilla_id` bigint(20) UNSIGNED DEFAULT NULL,
  `etiqueta_id` bigint(20) DEFAULT NULL,
  `etiqueta_sub_id` varchar(20) DEFAULT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_id_3` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id_3` bigint(20) UNSIGNED DEFAULT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `figura` varchar(50) DEFAULT NULL,
  `fila` int(4) DEFAULT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `etiqueta` varchar(50) DEFAULT NULL,
  `diametro` decimal(10,2) NOT NULL,
  `longitud` decimal(10,2) NOT NULL,
  `barras` int(5) DEFAULT NULL,
  `dobles_barra` int(11) NOT NULL,
  `peso` decimal(10,2) NOT NULL,
  `dimensiones` text DEFAULT NULL,
  `tiempo_fabricacion` decimal(10,2) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `fecha_entrega` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `elementos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `maquina_id` (`maquina_id`),
  ADD KEY `fk_elementos_users_id` (`users_id`),
  ADD KEY `fk_elementos_planilla` (`planilla_id`),
  ADD KEY `fk_elementos_etiqueta` (`etiqueta_id`),
  ADD KEY `fk_producto` (`producto_id`),
  ADD KEY `fk_users_2` (`users_id_2`),
  ADD KEY `fk_elementos_producto2` (`producto_id_2`),
  ADD KEY `fk_elementos_paquete` (`paquete_id`),
  ADD KEY `fk_elementos_maquina2` (`maquina_id_2`),
  ADD KEY `fk_elementos_maquina3` (`maquina_id_3`),
  ADD KEY `fk_elementos_producto_3` (`producto_id_3`),
  ADD KEY `elementos_orden_planilla_id_index` (`orden_planilla_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `elementos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
