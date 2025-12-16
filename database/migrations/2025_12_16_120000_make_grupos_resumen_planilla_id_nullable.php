<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
ALTER TABLE `grupos_resumen`
  MODIFY COLUMN `planilla_id` BIGINT UNSIGNED NULL;
SQL
        );
    }

    public function down(): void
    {
        $this->runSql(<<<'SQL'
ALTER TABLE `grupos_resumen`
  MODIFY COLUMN `planilla_id` BIGINT UNSIGNED NOT NULL;
SQL
        );
    }
};

