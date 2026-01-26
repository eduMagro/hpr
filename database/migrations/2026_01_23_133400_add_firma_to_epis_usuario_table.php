<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration {
    public function up(): void
    {
        $this->runSql(<<<'SQL'
            ALTER TABLE `epis_usuario`
            ADD COLUMN `firmado` BOOLEAN NOT NULL DEFAULT 0,
            ADD COLUMN `firmado_dia` DATETIME NULL DEFAULT NULL,
            ADD COLUMN `firma_ruta` VARCHAR(255) NULL DEFAULT NULL;
SQL
        );
    }

    public function down(): void
    {
    }
};
