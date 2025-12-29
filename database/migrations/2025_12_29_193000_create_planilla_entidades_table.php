<?php

use App\Database\IdempotentSqlMigration;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $this->runSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `planilla_entidades` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `planilla_id` bigint(20) NOT NULL,
  `linea` varchar(20) DEFAULT NULL COMMENT 'ZCODLIN de FerraWin',
  `marca` varchar(100) NOT NULL COMMENT 'Identificador de la entidad (P1, V1, etc)',
  `situacion` varchar(255) NOT NULL COMMENT 'Tipo/ubicación (PUNZ, VIGA, PILAR, etc)',
  `cantidad` int(11) NOT NULL DEFAULT 1 COMMENT 'Cantidad de unidades a fabricar',
  `miembros` int(11) NOT NULL DEFAULT 1,
  `modelo` varchar(50) DEFAULT NULL COMMENT 'Código de modelo FerraWin',
  `longitud_ensamblaje` decimal(10,2) DEFAULT NULL COMMENT 'Longitud total del ensamblaje en cm',
  `peso_total` decimal(10,2) DEFAULT NULL COMMENT 'Peso total de la entidad en kg',
  `total_barras` int(11) DEFAULT 0,
  `total_estribos` int(11) DEFAULT 0,
  `composicion` JSON DEFAULT NULL COMMENT 'Detalle de barras y estribos',
  `distribucion` JSON DEFAULT NULL COMMENT 'Distribución de armadura longitudinal y transversal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_planilla_entidades_planilla` (`planilla_id`),
  KEY `idx_planilla_entidades_marca` (`marca`),
  CONSTRAINT `fk_planilla_entidades_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Entidades/ensamblajes de cada planilla (pilares, vigas, etc)';
SQL
        );
    }

    public function down(): void
    {
        $this->runSql('DROP TABLE IF EXISTS `planilla_entidades`');
    }
};
