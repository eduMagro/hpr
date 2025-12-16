<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `salidas` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `codigo_salida` varchar(10) DEFAULT NULL,
  `codigo_sage` varchar(50) DEFAULT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `camion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `importe` decimal(10,2) DEFAULT NULL,
  `importe_paralizacion` decimal(10,2) DEFAULT NULL,
  `horas_grua` decimal(10,2) DEFAULT NULL,
  `importe_grua` decimal(10,2) DEFAULT NULL,
  `horas_paralizacion` decimal(10,2) DEFAULT NULL,
  `horas_almacen` decimal(5,2) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_salida` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','en tránsito','completada') DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `camion_id` (`camion_id`),
  ADD KEY `fk_salidas_user` (`user_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `salidas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
