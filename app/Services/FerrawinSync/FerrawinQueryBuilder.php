<?php

namespace App\Services\FerrawinSync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Constructor de consultas para FerraWin SQL Server.
 *
 * Encapsula todas las consultas SQL necesarias para obtener datos de FerraWin.
 */
class FerrawinQueryBuilder
{
    protected string $connection = 'ferrawin';

    /**
     * Obtiene los códigos de planillas de FerraWin en un rango de fechas.
     *
     * @param int $diasAtras
     * @return array
     */
    public function obtenerCodigosPlanillas(int $diasAtras = 7): array
    {
        $fechaDesde = Carbon::now()->subDays($diasAtras)->format('Y-m-d');

        $resultados = DB::connection($this->connection)
            ->table('ORD_HEAD')
            ->select(DB::raw("ZCONTA + '-' + ZCODIGO as codigo"))
            ->where('ZFECHA', '>=', $fechaDesde)
            ->distinct()
            ->get();

        return $resultados->pluck('codigo')->toArray();
    }

    /**
     * Obtiene todos los datos de una planilla específica.
     *
     * Esta consulta replica la lógica del script export_barras.ps1
     *
     * @param string $codigo Formato: "ZCONTA-ZCODIGO" (ej: "2025-001574")
     * @return array
     */
    public function obtenerDatosPlanilla(string $codigo): array
    {
        // Parsear el código
        $partes = explode('-', $codigo, 2);

        if (count($partes) !== 2) {
            return [];
        }

        [$zconta, $zcodigo] = $partes;

        $query = "
            SELECT
                p.ZCODCLI,
                p.ZCLIENTE,
                p.ZCODIGO as ZCODIGO_OBRA,
                p.ZNOMBRE as ZNOMBRE_OBRA,
                oh.ZMODULO,
                oh.ZMODULO as ZSECCION,
                oh.ZFECHA,
                oh.ZFECHAENT,
                oh.ZCONTA + '-' + oh.ZCODIGO as CODIGO_PLANILLA,
                oh.ZNUMERO,
                oh.ZNOMBRE as ZNOMBRE_PLANILLA,
                oh.ZCAMPO1,
                p.ZACTIVA,
                oh.ZTIPO,
                ob.ZCODLIN,
                od.ZSITUACION as ZDESCRIPCION_FILA,
                ob.ZMARCA,
                ob.ZMAQUINA,
                ob.ZDIAMETRO,
                ob.ZCODMODELO,
                ob.ZLONGTESTD,
                ob.ZNUMBEND,
                ob.ZSITUACION,
                COALESCE(pd.ZETIQUETA, '') as ZETIQUETA,
                ob.ZCANTIDAD,
                ob.ZPESOTESTD,
                ob.ZFIGURA,
                CAST(ob.ZOBJETO AS VARCHAR(MAX)) as ZOBJETO,
                oh.ZNOTAS
            FROM ORD_BAR ob
            LEFT JOIN ORD_HEAD oh ON ob.ZCONTA = oh.ZCONTA AND ob.ZCODIGO = oh.ZCODIGO
            LEFT JOIN ORD_DET od ON ob.ZCONTA = od.ZCONTA AND ob.ZCODIGO = od.ZCODIGO
                AND ob.ZORDEN = od.ZORDEN AND ob.ZCODLIN = od.ZCODLIN
            LEFT JOIN PROJECT p ON oh.ZCODOBRA = p.ZCODIGO
            LEFT JOIN PROD_DETO pd ON ob.ZCONTA = pd.ZCONTA AND ob.ZCODIGO = pd.ZCODPLA
                AND ob.ZCODLIN = pd.ZCODLIN AND ob.ZELEMENTO = pd.ZELEMENTO
            WHERE ob.ZCONTA = ? AND ob.ZCODIGO = ?
            ORDER BY ob.ZCODLIN, ob.ZELEMENTO
        ";

        return DB::connection($this->connection)
            ->select($query, [$zconta, $zcodigo]);
    }

    /**
     * Obtiene la fecha de última modificación de una planilla en FerraWin.
     *
     * @param string $codigo
     * @return Carbon|null
     */
    public function obtenerFechaModificacion(string $codigo): ?Carbon
    {
        $partes = explode('-', $codigo, 2);

        if (count($partes) !== 2) {
            return null;
        }

        [$zconta, $zcodigo] = $partes;

        // Buscar la fecha más reciente entre cabecera y barras
        $resultado = DB::connection($this->connection)
            ->table('ORD_HEAD')
            ->where('ZCONTA', $zconta)
            ->where('ZCODIGO', $zcodigo)
            ->value('ZFECHA');

        if ($resultado) {
            return Carbon::parse($resultado);
        }

        return null;
    }

    /**
     * Verifica si existe una planilla en FerraWin.
     *
     * @param string $codigo
     * @return bool
     */
    public function existePlanilla(string $codigo): bool
    {
        $partes = explode('-', $codigo, 2);

        if (count($partes) !== 2) {
            return false;
        }

        [$zconta, $zcodigo] = $partes;

        return DB::connection($this->connection)
            ->table('ORD_HEAD')
            ->where('ZCONTA', $zconta)
            ->where('ZCODIGO', $zcodigo)
            ->exists();
    }

    /**
     * Obtiene estadísticas de planillas en FerraWin.
     *
     * @param int $diasAtras
     * @return array
     */
    public function obtenerEstadisticas(int $diasAtras = 30): array
    {
        $fechaDesde = Carbon::now()->subDays($diasAtras)->format('Y-m-d');

        $total = DB::connection($this->connection)
            ->table('ORD_HEAD')
            ->where('ZFECHA', '>=', $fechaDesde)
            ->count();

        $porDia = DB::connection($this->connection)
            ->table('ORD_HEAD')
            ->select(DB::raw('CAST(ZFECHA AS DATE) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('ZFECHA', '>=', $fechaDesde)
            ->groupBy(DB::raw('CAST(ZFECHA AS DATE)'))
            ->orderBy('fecha', 'desc')
            ->limit(7)
            ->get();

        return [
            'total_ultimos_dias' => $total,
            'por_dia' => $porDia->toArray(),
        ];
    }
}
