<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `productos_codigos` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(10) NOT NULL,
  `anio` char(2) NOT NULL,
  `mes` varchar(2) DEFAULT NULL,
  `ultimo_numero` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `productos_codigos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigos_unicos` (`tipo`,`anio`,`mes`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `productos_codigos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }
};
