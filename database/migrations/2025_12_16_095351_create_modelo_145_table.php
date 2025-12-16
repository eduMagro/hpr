<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `modelo_145` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `estado_civil` varchar(20) DEFAULT NULL,
  `hijos_a_cargo` int(11) DEFAULT 0,
  `hijos_menores_3` int(11) DEFAULT 0,
  `ascendientes_mayores_65` tinyint(1) DEFAULT 0,
  `ascendientes_mayores_75` tinyint(1) DEFAULT 0,
  `discapacidad_porcentaje` int(11) DEFAULT 0,
  `discapacidad_familiares` tinyint(1) DEFAULT 0,
  `contrato_indefinido` tinyint(1) DEFAULT 1,
  `fecha_declaracion` date DEFAULT NULL,
  `es_simulacion` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `modelo_145`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `modelo_145`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
