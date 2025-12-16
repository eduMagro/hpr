<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `planillas` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seccion` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `ensamblado` varchar(255) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `revisada` tinyint(1) NOT NULL DEFAULT 0,
  `revisada_por_id` bigint(20) UNSIGNED DEFAULT NULL,
  `revisada_at` datetime DEFAULT NULL,
  `peso_total` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT 'pendiente',
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `tiempo_fabricacion` decimal(10,2) DEFAULT NULL,
  `fecha_estimada_entrega` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `planillas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_planillas_users_id` (`users_id`),
  ADD KEY `fk_planillas_obra_id` (`obra_id`),
  ADD KEY `fk_cliente_id` (`cliente_id`),
  ADD KEY `planillas_revisada_por_id_fk` (`revisada_por_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `planillas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
