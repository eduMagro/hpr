<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `localizaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `x1` int(11) NOT NULL,
  `y1` int(11) NOT NULL,
  `x2` int(11) NOT NULL,
  `y2` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `localizaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unico_maquina_por_nave` (`nave_id`,`maquina_id`),
  ADD KEY `fk_localizaciones_maquina` (`maquina_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `localizaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
