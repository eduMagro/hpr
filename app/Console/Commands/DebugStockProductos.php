<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Services\StockService;

class DebugStockProductos extends Command
{
    protected $signature = 'debug:stock-productos {obraId?}';
    protected $description = 'Ver qué productos cuenta StockService';

    public function handle()
    {
        $obraId = $this->argument('obraId');
        $obraIds = $obraId ? [(int)$obraId] : null;

        $this->info("=== QUERY DIRECTA ===");
        $query = Producto::with('productoBase')
            ->where('estado', 'almacenado');

        if ($obraIds) {
            $query->whereIn('obra_id', $obraIds);
        }

        $productos = $query->get()
            ->filter(fn($producto) => $producto->productoBase);

        $encarretado12 = $productos->filter(function($p) {
            return $p->productoBase->tipo === 'encarretado'
                && (int)$p->productoBase->diametro === 12;
        });

        $this->info("Total productos almacenados: " . $productos->count());
        $this->info("Productos encarretado Ø12: " . $encarretado12->count());
        $this->info("Peso total encarretado Ø12: " . number_format($encarretado12->sum('peso_inicial'), 2) . " kg");

        $this->newLine();
        $this->info("=== StockService ===");
        $service = new StockService();
        $datos = $service->obtenerDatosStock($obraIds);
        $this->info("Stock encarretado Ø12 según StockService: " . ($datos['stockData'][12]['encarretado'] ?? 0) . " kg");

        $this->newLine();
        $this->info("=== Productos encarretado Ø12 ===");
        $this->table(
            ['ID', 'Código', 'Obra ID', 'Obra', 'Peso', 'Estado'],
            $encarretado12->map(fn($p) => [
                $p->id,
                $p->codigo,
                $p->obra_id,
                $p->obra->obra ?? 'NULL',
                number_format($p->peso_inicial, 2),
                $p->estado
            ])
        );
    }
}
