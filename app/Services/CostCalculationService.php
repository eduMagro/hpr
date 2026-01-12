<?php

namespace App\Services;

use App\Models\Obra;
use App\Models\Nomina;
use App\Models\AsignacionTurno;
use App\Models\User;
use App\Models\Salida;
use App\Models\AlbaranVentaLinea;
use App\Models\Configuracion;
use Carbon\Carbon;

class CostCalculationService
{
    /**
     * Calcula el coste de mano de obra para una obra específica.
     * Basado en las nóminas de los trabajadores y sus horas asignadas a la obra.
     */
    public function getLaborCost($obraId)
    {
        // Obtener turnos asignados a esta obra
        $turnos = AsignacionTurno::where('obra_id', $obraId)->with('user')->get();
        $totalCost = 0;
        $missingUsers = [];

        foreach ($turnos as $turno) {
            $user = $turno->user;
            if (!$user) continue;

            $fechaTurno = Carbon::parse($turno->fecha);
            $mes = $fechaTurno->month;
            $anio = $fechaTurno->year;

            // Strategy 1: Specific User, Current Month
            $nomina = Nomina::where('empleado_id', $user->id)
                ->whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->first();

            // Strategy 2: Category Proxy, Current Month
            if (!$nomina && $user->categoria_id) {
                $nomina = Nomina::where('categoria_id', $user->categoria_id)
                    ->whereMonth('fecha', $mes)
                    ->whereYear('fecha', $anio)
                    ->first();
            }

            // Strategy 3: Historical Search (up to 12 months back)
            if (!$nomina) {
                for ($i = 1; $i <= 12; $i++) {
                    $pastDate = $fechaTurno->copy()->subMonths($i);

                    // 3a. Specific User, Past Month
                    $nomina = Nomina::where('empleado_id', $user->id)
                        ->whereMonth('fecha', $pastDate->month)
                        ->whereYear('fecha', $pastDate->year)
                        ->first();

                    if ($nomina) break;

                    // 3b. Category Proxy, Past Month
                    if ($user->categoria_id) {
                        $nomina = Nomina::where('categoria_id', $user->categoria_id)
                            ->whereMonth('fecha', $pastDate->month)
                            ->whereYear('fecha', $pastDate->year)
                            ->first();
                    }

                    if ($nomina) break;
                }
            }

            if ($nomina && $nomina->coste_empresa > 0) {
                // Cálculo simplificado: Prorrateo básico
                // Estimación simple (22 días laborables)
                $costePorTurno = $nomina->coste_empresa / 22;
                $totalCost += $costePorTurno;
            } else {
                // Mark user as missing payroll info
                $missingUsers[$user->id] = $user;
            }
        }

        return ['cost' => $totalCost, 'missing_users' => collect($missingUsers)->values()];
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

        // Sumar coste real de los productos ("consumido" o servido en "salidas")
        $productosConsumidos = $obra->productos()
            ->with(['entrada.pedidoProducto']) // Cargar relación para precio compra
            ->where('estado', 'consumido')
            ->get();

        $costeTotalMaterial = 0;
        $precioBaseConfig = Configuracion::where('clave', 'coste_hierro_kg')->value('valor') ?? 0.85;

        foreach ($productosConsumidos as $prod) {
            // Intentar obtener precio real de compra desde la línea de pedido
            $precioCompra = $prod->entrada->pedidoProducto->precio_unitario ?? $precioBaseConfig;

            $costeSemielaborado = 0;
            // TODO: Si se quiere sumar mano de obra específica al producto aquí, 
            // habría que calcular cuántas horas se invirtieron en ESTE producto.
            // Actualmente la mano de obra va a la columna "M. Obra".

            $costeproducto = ($prod->peso_inicial * $precioCompra) + $costeSemielaborado;
            $costeTotalMaterial += $costeproducto;
        }

        return $costeTotalMaterial;
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

        $laborData = $this->getLaborCost($obraId);
        $labor = $laborData['cost'];
        $missingUsers = $laborData['missing_users'];

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
            'margin_percentage' => $revenue > 0 ? ($margin / $revenue) * 100 : 0,
            'missing_nominas_users' => $missingUsers // Add this for the UI
        ];
    }
}
