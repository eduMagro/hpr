<?php

use App\Database\IdempotentSqlMigration;
use Illuminate\Support\Facades\DB;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `snapshots_produccion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo_operacion` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `orden_planillas_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`orden_planillas_data`)),
  `elementos_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`elementos_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        if ($this->tableExists('snapshots_produccion') && !$this->columnExists('snapshots_produccion', 'user_id')) {
            $this->runSql(<<<'SQL'
ALTER TABLE `snapshots_produccion`
  ADD COLUMN `user_id` bigint(20) UNSIGNED DEFAULT NULL;
SQL
            );
        }

        $this->runSql(<<<'SQL'
ALTER TABLE `snapshots_produccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `snapshots_produccion_user_id_foreign` (`user_id`);
SQL
        );

        $this->runSql(<<<'SQL'
ALTER TABLE `snapshots_produccion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
SQL
        );

    }

    public function down(): void
    {
    }

    private function tableExists(string $table): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};
