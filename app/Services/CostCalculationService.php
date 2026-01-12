<?php

namespace App\Services;

use App\Models\Obra;
use App\Models\Nomina;
use App\Models\AsignacionTurno;
use App\Models\Salida;
use App\Models\AlbaranVentaLinea;
use App\Models\Configuracion;

class CostCalculationService
{
    /**
     * Calcula el coste de mano de obra para una obra específica.
     * Basado en las nóminas de los trabajadores y sus horas asignadas a la obra.
     */
    public function getLaborCost($obraId)
    {
        // Obtener turnos asignados a esta obra
        $turnos = AsignacionTurno::where('obra_id', $obraId)->get();
        $totalCost = 0;

        foreach ($turnos as $turno) {
            // Obtener la nómina del trabajador para el mes del turno
            $fechaTurno = $turno->fecha;
            $mes = date('m', strtotime($fechaTurno));
            $anio = date('Y', strtotime($fechaTurno));

            $nomina = Nomina::where('empleado_id', $turno->user_id)
                ->whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->first();

            if ($nomina && $nomina->coste_empresa > 0) {
                // Cálculo simplificado: Prorrateo básico
                // En un sistema real, dividiríamos el coste mensual por horas totales 
                // y multiplicaríamos por las horas del turno.
                // Aquí asumiremos un valor representativo por turno si no tenemos horas exactas.
                $costePorTurno = $nomina->coste_empresa / 22; // Estimación simple (22 días laborables)
                $totalCost += $costePorTurno;
            }
        }

        return $totalCost;
    }

    /**
     * Calcula el coste de material consumido (Hierro).
     * Peso consumido * Precio de compra base.
     */
    public function getMaterialCost($obraId)
    {
        $obra = Obra::find($obraId);
        if (!$obra) return 0;

        // Precio base de compra (puedes ajustarlo o traerlo de configuración)
        $precioCompraKg = Configuracion::where('clave', 'coste_hierro_kg')->value('valor') ?? 0.85;

        // Sumar peso total de productos asignados a la obra que han sido consumidos
        $pesoTotal = $obra->productos()->where('estado', 'consumido')->sum('peso_inicial');

        return $pesoTotal * $precioCompraKg;
    }

    /**
     * Calcula los gastos de logística asociados a las salidas de la obra.
     */
    public function getLogisticsCost($obraId)
    {
        $totalLogistics = Salida::whereHas('obras', function ($q) use ($obraId) {
            $q->where('obra_id', $obraId);
        })->sum('importe');

        $totalGrua = Salida::whereHas('obras', function ($q) use ($obraId) {
            $q->where('obra_id', $obraId);
        })->sum('importe_grua');

        $totalParalizacion = Salida::whereHas('obras', function ($q) use ($obraId) {
            $q->where('obra_id', $obraId);
        })->sum('importe_paralizacion');

        return $totalLogistics + $totalGrua + $totalParalizacion;
    }

    /**
     * Calcula los ingresos generados por la obra (Venta de material).
     */
    public function getRevenue($obraId)
    {
        // Ingresos desde albaranes de venta vinculados a la obra
        // Se vinculan a través de los productos físicos asignados a la línea de albarán
        return AlbaranVentaLinea::whereHas('albaranesVentaProductos.producto', function ($q) use ($obraId) {
            $q->where('obra_id', $obraId);
        })->selectRaw('SUM(precio_unitario * cantidad_kg) as total')->value('total') ?? 0;
    }

    /**
     * Obtiene el resumen consolidado de una obra.
     */
    public function getObraFinancialSummary($obraId)
    {
        $obra = Obra::find($obraId);
        if (!$obra) return null;

        $labor = $this->getLaborCost($obraId);
        $material = $this->getMaterialCost($obraId);
        $logistics = $this->getLogisticsCost($obraId);
        $revenue = $this->getRevenue($obraId);

        $realCost = $labor + $material + $logistics;
        $budget = $obra->presupuesto_estimado ?? 0;
        $deviation = $budget - $realCost;
        $margin = $revenue - $realCost;

        return [
            'obra' => $obra,
            'budget' => $budget,
            'labor_cost' => $labor,
            'material_cost' => $material,
            'logistics_cost' => $logistics,
            'real_cost' => $realCost,
            'revenue' => $revenue,
            'deviation' => $deviation,
            'margin' => $margin,
            'margin_percentage' => $revenue > 0 ? ($margin / $revenue) * 100 : 0
        ];
    }
}
