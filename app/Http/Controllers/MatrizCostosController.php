<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Services\CostCalculationService;
use Illuminate\Http\Request;

class MatrizCostosController extends Controller
{
    protected $costService;

    public function __construct(CostCalculationService $costService)
    {
        $this->costService = $costService;
    }

    public function index()
    {
        $obras = Obra::where('estado', '!=', 'cancelada')
            ->orderBy('created_at', 'desc')
            ->get();

        $summaries = $obras->map(function ($obra) {
            return $this->costService->getObraFinancialSummary($obra->id);
        });

        $totals = [
            'budget' => $summaries->sum('budget'),
            'real_cost' => $summaries->sum('real_cost'),
            'revenue' => $summaries->sum('revenue'),
            'deviation' => $summaries->sum('deviation'),
            'count' => $obras->count()
        ];

        return view('matriz_costos.index', compact('summaries', 'totals'));
    }
}
