<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `movimientos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(70) DEFAULT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_base_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_origen` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_destino` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_origen` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_destino` bigint(20) UNSIGNED DEFAULT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'pendiente',
  `prioridad` tinyint(4) DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT NULL,
  `fecha_ejecucion` datetime DEFAULT NULL,
  `solicitado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `ejecutado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `pedido_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salida_id` bigint(20) DEFAULT NULL,
  `salida_almacen_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `ubicacion_origen` (`ubicacion_origen`),
  ADD KEY `ubicacion_destino` (`ubicacion_destino`),
  ADD KEY `maquina_origen` (`maquina_origen`),
  ADD KEY `maquina_id` (`maquina_destino`),
  ADD KEY `fk_paquete_id` (`paquete_id`),
  ADD KEY `solicitado_por` (`solicitado_por`),
  ADD KEY `ejecutado_por` (`ejecutado_por`),
  ADD KEY `movimientos_producto_base_id_foreign` (`producto_base_id`),
  ADD KEY `movimientos_pedido_id_foreign` (`pedido_id`),
  ADD KEY `movimientos_salida_id_foreign` (`salida_id`),
  ADD KEY `fk_movimientos_pedido_producto` (`pedido_producto_id`),
  ADD KEY `movimientos_nave_id_foreign` (`nave_id`),
  ADD KEY `movimientos_salida_almacen_id_foreign` (`salida_almacen_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `movimientos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
