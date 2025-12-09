<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;

class DebugStockDiferencia extends Command
{
    protected $signature = 'debug:stock-diferencia';
    protected $description = 'Debug diferencia entre StockService y ProductosTable';

    public function handle()
    {
        // Todos los productos encarretado diámetro 12 almacenados
        $productos = Producto::with(['productoBase', 'obra'])
            ->where('estado', 'almacenado')
            ->whereHas('productoBase', function($q) {
                $q->where('tipo', 'encarretado')
                  ->where('diametro', 12);
            })
            ->get();

        $this->info("Total productos encarretado Ø12 almacenados: " . $productos->count());
        $this->info("Total peso: " . number_format($productos->sum('peso_inicial'), 2) . " kg");
        $this->newLine();

        // Agrupar por obra
        $porObra = $productos->groupBy('obra_id');

        $this->info("Desglose por obra:");
        foreach ($porObra as $obraId => $prods) {
            $obra = $prods->first()->obra;
            $total = $prods->sum('peso_inicial');
            $count = $prods->count();
            $this->line("  Obra ID: $obraId | Nombre: " . ($obra ? $obra->obra : 'NULL') . " | Productos: $count | Total: " . number_format($total, 2) . " kg");
        }

        $this->newLine();
        $this->info("Solo Nave A (obra_id=1):");
        $naveA = $productos->where('obra_id', 1);
        $this->line("  Productos: " . $naveA->count());
        $this->line("  Total: " . number_format($naveA->sum('peso_inicial'), 2) . " kg");
    }
}
