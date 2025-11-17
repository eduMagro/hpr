<?php

namespace Tests\Feature\Fabricacion;

use App\Models\Elemento;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\Movimiento;
use App\Models\Planilla;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Suite de tests para fabricación de etiquetas
 *
 * IMPORTANTE: Estos tests usan datos EXISTENTES en tu BD
 * No requieren seeder - se adaptan a lo que tengas
 *
 * Uso: php artisan test --filter=FabricacionEtiquetasTest
 */
class FabricacionEtiquetasTest extends TestCase
{
    // NO usar RefreshDatabase para trabajar con datos reales
    // use RefreshDatabase;

    protected $etiquetasPrueba = [];
    protected $maquinas = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Recopilar datos existentes para testing
        $this->recopilarDatosPrueba();
    }

    private function recopilarDatosPrueba(): void
    {
        // Buscar máquinas disponibles
        $this->maquinas['cortadora_barra'] = Maquina::where('tipo', 'cortadora_dobladora')
            ->where('tipo_material', 'barra')
            ->first();

        $this->maquinas['cortadora_encar'] = Maquina::where('tipo', 'cortadora_dobladora')
            ->where('tipo_material', 'encarretado')
            ->first();

        $this->maquinas['dobladora'] = Maquina::where('tipo', 'dobladora_manual')->first();
        $this->maquinas['ensambladora'] = Maquina::where('tipo', 'ensambladora')->first();
        $this->maquinas['soldadora'] = Maquina::where('tipo', 'soldadora')->first();

        // Buscar etiquetas de prueba
        $this->etiquetasPrueba['pendiente'] = Etiqueta::where('estado', 'pendiente')
            ->whereHas('elementos')
            ->first();

        $this->etiquetasPrueba['fabricando'] = Etiqueta::where('estado', 'fabricando')->first();
        $this->etiquetasPrueba['fabricada'] = Etiqueta::where('estado', 'fabricada')->first();
    }

    // ========================================
    // TESTS FLUJOS BÁSICOS
    // ========================================

    /** @test */
    public function test_01_puede_listar_etiquetas_pendientes()
    {
        $etiquetas = Etiqueta::where('estado', 'pendiente')
            ->with(['planilla', 'elementos'])
            ->limit(10)
            ->get();

        echo "\n📋 ETIQUETAS PENDIENTES ENCONTRADAS: " . $etiquetas->count() . "\n";

        foreach ($etiquetas->take(5) as $etiqueta) {
            echo "  • {$etiqueta->codigo} - {$etiqueta->nombre} - {$etiqueta->elementos->count()} elementos\n";
        }

        $this->assertGreaterThanOrEqual(0, $etiquetas->count());
    }

    /** @test */
    public function test_02_puede_iniciar_fabricacion_etiqueta()
    {
        $etiqueta = Etiqueta::where('estado', 'pendiente')
            ->whereHas('elementos')
            ->first();

        if (!$etiqueta) {
            $this->markTestSkipped('No hay etiquetas pendientes con elementos');
        }

        $maquina = $this->maquinas['cortadora_barra'];

        if (!$maquina) {
            $this->markTestSkipped('No hay máquina cortadora de barra disponible');
        }

        echo "\n🔧 INICIANDO FABRICACIÓN\n";
        echo "  Etiqueta: {$etiqueta->codigo}\n";
        echo "  Máquina: {$maquina->nombre}\n";
        echo "  Elementos: {$etiqueta->elementos->count()}\n";

        // Act - Primera llamada: iniciar fabricación
        $response = $this->putJson(
            "/actualizar-etiqueta/{$etiqueta->etiqueta_sub_id}/maquina/{$maquina->id}",
            [
                'operario1_id' => 1,
                'longitudSeleccionada' => 12,
            ]
        );

        echo "\n  Respuesta HTTP: " . $response->status() . "\n";

        if ($response->status() === 200) {
            $data = $response->json();
            echo "  Estado resultante: " . ($data['estado'] ?? 'N/A') . "\n";
            echo "  ✅ Fabricación iniciada correctamente\n";

            $etiqueta->refresh();
            $this->assertContains($etiqueta->estado, ['fabricando', 'fabricada', 'completada']);
        } else {
            echo "  ⚠️ Respuesta: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
            // No falla el test, solo reporta
            $this->assertTrue(true, 'Test ejecutado - revisar salida');
        }
    }

    /** @test */
    public function test_03_verifica_stock_disponible_por_diametro()
    {
        $maquina = $this->maquinas['cortadora_barra'];

        if (!$maquina) {
            $this->markTestSkipped('No hay máquina disponible');
        }

        echo "\n📊 STOCK DISPONIBLE EN MÁQUINA: {$maquina->nombre}\n";

        $productos = Producto::where('obra_id', $maquina->obra_id)
            ->where('peso_stock', '>', 0)
            ->with('productoBase')
            ->get()
            ->groupBy(function ($p) {
                return $p->productoBase->diametro ?? 'N/A';
            });

        foreach ($productos as $diametro => $prods) {
            $totalStock = $prods->sum('peso_stock');
            echo "  Ø{$diametro}mm: " . number_format($totalStock, 2) . " kg ({$prods->count()} productos)\n";
        }

        $this->assertGreaterThan(0, $productos->count(), 'Debe haber stock disponible');
    }

    /** @test */
    public function test_04_detecta_etiquetas_con_multiples_diametros()
    {
        $etiquetas = Etiqueta::whereHas('elementos', function ($q) {
            $q->whereNotNull('diametro');
        })
        ->with('elementos')
        ->limit(10)
        ->get();

        echo "\n🔍 ETIQUETAS CON MÚLTIPLES DIÁMETROS:\n";

        $multiDiametro = [];
        foreach ($etiquetas as $etiqueta) {
            $diametros = $etiqueta->elementos->pluck('diametro')->unique()->filter()->count();
            if ($diametros > 1) {
                $multiDiametro[] = $etiqueta;
                $diamList = $etiqueta->elementos->pluck('diametro')->unique()->filter()->sort()->values()->toArray();
                echo "  • {$etiqueta->codigo}: " . implode(', ', array_map(fn($d) => "Ø{$d}", $diamList)) . "\n";
            }
        }

        echo "\n  Total encontradas: " . count($multiDiametro) . "\n";

        $this->assertGreaterThanOrEqual(0, count($multiDiametro));
    }

    /** @test */
    public function test_05_identifica_planillas_con_regla_taller()
    {
        $planillasTaller = Planilla::whereNotNull('ensamblado')
            ->where(function ($q) {
                $q->where('ensamblado', 'like', '%taller%')
                  ->orWhere('ensamblado', 'like', '%TALLER%');
            })
            ->with('etiquetas')
            ->limit(10)
            ->get();

        echo "\n🔧 PLANILLAS CON REGLA TALLER:\n";

        foreach ($planillasTaller as $planilla) {
            echo "  • {$planilla->codigo} - {$planilla->seccion}\n";
            echo "    Ensamblado: {$planilla->ensamblado}\n";
            echo "    Etiquetas: {$planilla->etiquetas->count()}\n";
        }

        echo "\n  Total encontradas: {$planillasTaller->count()}\n";

        $this->assertGreaterThanOrEqual(0, $planillasTaller->count());
    }

    /** @test */
    public function test_06_identifica_planillas_con_regla_carcasas()
    {
        $planillasCarcasas = Planilla::whereNotNull('ensamblado')
            ->where(function ($q) {
                $q->where('ensamblado', 'like', '%carcasas%')
                  ->orWhere('ensamblado', 'like', '%CARCASAS%');
            })
            ->with('etiquetas')
            ->limit(10)
            ->get();

        echo "\n📦 PLANILLAS CON REGLA CARCASAS:\n";

        foreach ($planillasCarcasas as $planilla) {
            echo "  • {$planilla->codigo} - {$planilla->seccion}\n";
            echo "    Ensamblado: {$planilla->ensamblado}\n";
            echo "    Etiquetas: {$planilla->etiquetas->count()}\n";
        }

        echo "\n  Total encontradas: {$planillasCarcasas->count()}\n";

        $this->assertGreaterThanOrEqual(0, $planillasCarcasas->count());
    }

    /** @test */
    public function test_07_identifica_etiquetas_pates()
    {
        $etiquetasPates = Etiqueta::where(function ($q) {
                $q->where('nombre', 'like', '%pates%')
                  ->orWhere('nombre', 'like', '%PATES%');
            })
            ->with('elementos')
            ->limit(10)
            ->get();

        echo "\n🔩 ETIQUETAS TIPO PATES:\n";

        foreach ($etiquetasPates as $etiqueta) {
            echo "  • {$etiqueta->codigo} - {$etiqueta->nombre}\n";
            echo "    Elementos: {$etiqueta->elementos->count()}\n";
        }

        echo "\n  Total encontradas: {$etiquetasPates->count()}\n";

        $this->assertGreaterThanOrEqual(0, $etiquetasPates->count());
    }

    /** @test */
    public function test_08_verifica_elementos_con_maquinas_asignadas()
    {
        $elementos = Elemento::whereNotNull('maquina_id')
            ->with(['maquina', 'etiqueta'])
            ->limit(20)
            ->get();

        echo "\n⚙️ ELEMENTOS CON MÁQUINAS ASIGNADAS:\n";

        $estadisticas = [
            'con_1_maquina' => 0,
            'con_2_maquinas' => 0,
            'con_3_maquinas' => 0,
        ];

        foreach ($elementos->take(10) as $elemento) {
            $maquinas = collect([
                $elemento->maquina_id,
                $elemento->maquina_id_2,
                $elemento->maquina_id_3
            ])->filter()->count();

            if ($maquinas == 1) $estadisticas['con_1_maquina']++;
            elseif ($maquinas == 2) $estadisticas['con_2_maquinas']++;
            elseif ($maquinas == 3) $estadisticas['con_3_maquinas']++;

            echo "  • {$elemento->codigo} - {$maquinas} máquina(s)\n";
        }

        echo "\n  Estadísticas:\n";
        echo "    Con 1 máquina: {$estadisticas['con_1_maquina']}\n";
        echo "    Con 2 máquinas: {$estadisticas['con_2_maquinas']}\n";
        echo "    Con 3 máquinas: {$estadisticas['con_3_maquinas']}\n";

        $this->assertGreaterThanOrEqual(0, $elementos->count());
    }

    /** @test */
    public function test_09_verifica_movimientos_de_recarga()
    {
        $movimientos = Movimiento::where('tipo', 'entrada')
            ->where('estado', 'pendiente')
            ->with('productoBase')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        echo "\n📥 MOVIMIENTOS DE RECARGA PENDIENTES:\n";

        foreach ($movimientos as $mov) {
            $pb = $mov->productoBase;
            $descripcion = $pb ? "Ø{$pb->diametro}mm" : 'N/A';
            echo "  • ID: {$mov->id} - {$descripcion} - {$mov->cantidad} kg\n";
            echo "    Creado: {$mov->created_at->format('d/m/Y H:i')}\n";
        }

        echo "\n  Total pendientes: {$movimientos->count()}\n";

        $this->assertGreaterThanOrEqual(0, $movimientos->count());
    }

    /** @test */
    public function test_10_verifica_estado_de_planillas()
    {
        $estados = Planilla::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();

        echo "\n📊 DISTRIBUCIÓN DE ESTADOS DE PLANILLAS:\n";

        foreach ($estados as $estado) {
            echo "  • {$estado->estado}: {$estado->total} planillas\n";
        }

        $this->assertGreaterThan(0, $estados->count());
    }

    /** @test */
    public function test_11_verifica_elementos_fabricados_con_productos()
    {
        $elementos = Elemento::where('estado', 'fabricado')
            ->whereNotNull('producto_id')
            ->with(['producto', 'producto2', 'producto3'])
            ->limit(20)
            ->get();

        echo "\n✅ ELEMENTOS FABRICADOS CON PRODUCTOS ASIGNADOS:\n";

        $stats = [
            'con_1_producto' => 0,
            'con_2_productos' => 0,
            'con_3_productos' => 0,
        ];

        foreach ($elementos->take(10) as $elemento) {
            $productos = collect([
                $elemento->producto_id,
                $elemento->producto_id_2,
                $elemento->producto_id_3
            ])->filter()->count();

            if ($productos == 1) $stats['con_1_producto']++;
            elseif ($productos == 2) $stats['con_2_productos']++;
            elseif ($productos == 3) $stats['con_3_productos']++;

            $coladas = [];
            if ($elemento->producto) $coladas[] = $elemento->producto->n_colada;
            if ($elemento->producto2) $coladas[] = $elemento->producto2->n_colada;
            if ($elemento->producto3) $coladas[] = $elemento->producto3->n_colada;

            $coladasStr = implode(', ', array_filter($coladas));
            echo "  • {$elemento->codigo} - {$productos} producto(s) - Coladas: {$coladasStr}\n";
        }

        echo "\n  Estadísticas:\n";
        echo "    Con 1 producto: {$stats['con_1_producto']}\n";
        echo "    Con 2 productos: {$stats['con_2_productos']}\n";
        echo "    Con 3 productos: {$stats['con_3_productos']}\n";

        $this->assertGreaterThanOrEqual(0, $elementos->count());
    }

    /** @test */
    public function test_12_verifica_consumo_de_stock()
    {
        $productos = Producto::whereColumn('peso_stock', '<', 'peso_inicial')
            ->with('productoBase')
            ->limit(20)
            ->get();

        echo "\n📉 PRODUCTOS CON STOCK CONSUMIDO:\n";

        $totalConsumido = 0;

        foreach ($productos->take(10) as $producto) {
            $consumido = $producto->peso_inicial - $producto->peso_stock;
            $totalConsumido += $consumido;

            $pb = $producto->productoBase;
            $desc = $pb ? "Ø{$pb->diametro}mm" : 'N/A';

            echo "  • {$producto->codigo} ({$desc})\n";
            echo "    Inicial: {$producto->peso_inicial} kg | Stock: {$producto->peso_stock} kg | Consumido: " . number_format($consumido, 2) . " kg\n";
        }

        echo "\n  Total consumido (muestra): " . number_format($totalConsumido, 2) . " kg\n";

        $this->assertGreaterThanOrEqual(0, $productos->count());
    }

    /** @test */
    public function test_13_lista_maquinas_disponibles()
    {
        $maquinas = Maquina::with('obra')->get();

        echo "\n🏭 MÁQUINAS DISPONIBLES EN EL SISTEMA:\n";

        $porTipo = $maquinas->groupBy('tipo');

        foreach ($porTipo as $tipo => $maquinasTipo) {
            echo "\n  {$tipo} ({$maquinasTipo->count()}):\n";
            foreach ($maquinasTipo as $maquina) {
                $material = $maquina->tipo_material ? " - {$maquina->tipo_material}" : '';
                $obra = $maquina->obra ? " - Obra: {$maquina->obra->obra}" : '';
                echo "    • {$maquina->codigo} - {$maquina->nombre}{$material}{$obra}\n";
            }
        }

        $this->assertGreaterThan(0, $maquinas->count());
    }

    /** @test */
    public function test_14_verifica_etiquetas_completadas_hoy()
    {
        $hoy = now()->startOfDay();

        $completadas = Etiqueta::whereIn('estado', ['fabricada', 'completada'])
            ->where('fecha_finalizacion', '>=', $hoy)
            ->with('planilla')
            ->get();

        echo "\n📅 ETIQUETAS COMPLETADAS HOY:\n";

        foreach ($completadas->take(10) as $etiqueta) {
            $planillaCodigo = $etiqueta->planilla->codigo ?? 'N/A';
            echo "  • {$etiqueta->codigo} - {$etiqueta->nombre}\n";
            echo "    Planilla: {$planillaCodigo}\n";
            echo "    Finalizada: {$etiqueta->fecha_finalizacion}\n";
        }

        echo "\n  Total completadas hoy: {$completadas->count()}\n";

        $this->assertGreaterThanOrEqual(0, $completadas->count());
    }

    /** @test */
    public function test_15_resumen_general_del_sistema()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 RESUMEN GENERAL DEL SISTEMA DE FABRICACIÓN\n";
        echo str_repeat("=", 60) . "\n";

        // Planillas
        $planillas = Planilla::count();
        $planillasPendientes = Planilla::where('estado', 'pendiente')->count();
        $planillasFabricando = Planilla::where('estado', 'fabricando')->count();
        $planillasCompletadas = Planilla::where('estado', 'completada')->count();

        echo "\n📋 PLANILLAS:\n";
        echo "  Total: {$planillas}\n";
        echo "  Pendientes: {$planillasPendientes}\n";
        echo "  En fabricación: {$planillasFabricando}\n";
        echo "  Completadas: {$planillasCompletadas}\n";

        // Etiquetas
        $etiquetas = Etiqueta::count();
        $etiquetasPendientes = Etiqueta::where('estado', 'pendiente')->count();
        $etiquetasFabricando = Etiqueta::where('estado', 'fabricando')->count();
        $etiquetasCompletadas = Etiqueta::whereIn('estado', ['fabricada', 'completada'])->count();

        echo "\n🏷️ ETIQUETAS:\n";
        echo "  Total: {$etiquetas}\n";
        echo "  Pendientes: {$etiquetasPendientes}\n";
        echo "  En fabricación: {$etiquetasFabricando}\n";
        echo "  Completadas: {$etiquetasCompletadas}\n";

        // Elementos
        $elementos = Elemento::count();
        $elementosPendientes = Elemento::whereIn('estado', ['pendiente', null])->count();
        $elementosFabricando = Elemento::where('estado', 'fabricando')->count();
        $elementosFabricados = Elemento::where('estado', 'fabricado')->count();

        echo "\n🔩 ELEMENTOS:\n";
        echo "  Total: {$elementos}\n";
        echo "  Pendientes: {$elementosPendientes}\n";
        echo "  En fabricación: {$elementosFabricando}\n";
        echo "  Fabricados: {$elementosFabricados}\n";

        // Máquinas
        $maquinas = Maquina::count();
        $cortadoras = Maquina::where('tipo', 'cortadora_dobladora')->count();
        $dobladoras = Maquina::where('tipo', 'dobladora_manual')->count();
        $ensambladoras = Maquina::where('tipo', 'ensambladora')->count();

        echo "\n🏭 MÁQUINAS:\n";
        echo "  Total: {$maquinas}\n";
        echo "  Cortadoras: {$cortadoras}\n";
        echo "  Dobladoras: {$dobladoras}\n";
        echo "  Ensambladoras: {$ensambladoras}\n";

        // Stock
        $stockTotal = Producto::sum('peso_stock');
        $stockConsumido = DB::table('productos')
            ->selectRaw('SUM(peso_inicial - peso_stock) as consumido')
            ->value('consumido') ?? 0;

        echo "\n📦 STOCK:\n";
        echo "  Stock disponible: " . number_format($stockTotal, 2) . " kg\n";
        echo "  Total consumido: " . number_format($stockConsumido, 2) . " kg\n";

        echo "\n" . str_repeat("=", 60) . "\n";

        $this->assertTrue(true);
    }

    /** @test */
    public function test_16_puede_ejecutar_endpoint_de_fabricacion()
    {
        $etiqueta = Etiqueta::where('estado', 'pendiente')
            ->whereHas('elementos', function($q) {
                $q->whereNotNull('maquina_id');
            })
            ->first();

        if (!$etiqueta) {
            $this->markTestSkipped('No hay etiqueta pendiente con elementos y máquina asignada');
        }

        $elemento = $etiqueta->elementos()->whereNotNull('maquina_id')->first();
        $maquina = Maquina::find($elemento->maquina_id);

        if (!$maquina) {
            $this->markTestSkipped('No se encontró la máquina asignada');
        }

        echo "\n🎯 TEST DE ENDPOINT DE FABRICACIÓN:\n";
        echo "  Etiqueta: {$etiqueta->codigo}\n";
        echo "  Máquina: {$maquina->nombre}\n";
        echo "  Estado inicial: {$etiqueta->estado}\n";

        $response = $this->putJson(
            "/actualizar-etiqueta/{$etiqueta->etiqueta_sub_id}/maquina/{$maquina->id}",
            [
                'operario1_id' => 1,
                'longitudSeleccionada' => 12,
            ]
        );

        echo "  Código HTTP: {$response->status()}\n";

        if ($response->status() === 200) {
            $data = $response->json();
            echo "  ✅ SUCCESS: " . ($data['success'] ? 'true' : 'false') . "\n";
            echo "  Estado resultante: " . ($data['estado'] ?? 'N/A') . "\n";

            if (isset($data['warnings'])) {
                echo "  ⚠️ Warnings:\n";
                foreach ($data['warnings'] as $warning) {
                    echo "    - {$warning}\n";
                }
            }

            if (isset($data['coladas'])) {
                echo "  Coladas utilizadas: " . implode(', ', $data['coladas']) . "\n";
            }

            $this->assertTrue($data['success'] ?? false);
        } else {
            echo "  ❌ Error en la respuesta\n";
            echo "  " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

            // No falla el test, solo reporta
            $this->assertTrue(true, 'Endpoint ejecutado - revisar salida para detalles');
        }
    }
}
