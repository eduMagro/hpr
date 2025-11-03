<?php

namespace App\Services\PlanillaImport;

use App\Models\Planilla;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Services\PlanillaImport\DTOs\ProcesamientoResult;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Procesa los datos de una planilla individual.
 * 
 * Responsabilidades:
 * - Crear/obtener cliente y obra
 * - Crear planilla
 * - Crear etiquetas padre
 * - Crear elementos agregados
 * - Aplicar política de subetiquetas (configurable)
 * - Calcular totales
 * 
 * NO incluye:
 * - Asignación de máquinas (AsignarMaquinaService)
 * - Creación de orden_planillas (OrdenPlanillaService)
 */
class PlanillaProcessor
{
    protected array $diametrosPermitidos;
    protected int $tiempoSetupElemento;
    protected array $estrategiasSubetiquetas;

    public function __construct()
    {
        $this->diametrosPermitidos = config('planillas.importacion.diametros_permitidos', [5, 8, 10, 12, 16, 20, 25, 32]);
        $this->tiempoSetupElemento = config('planillas.importacion.tiempo_setup_elemento', 1200);

        // ✅ Configuración de estrategias por máquina
        $this->estrategiasSubetiquetas = config('planillas.importacion.estrategias_subetiquetas', []);

        // ✅ Log de configuración para debugging
        Log::channel('planilla_import')->debug("🔧 [PlanillaProcessor] Configuración cargada", [
            'estrategias_configuradas' => array_keys($this->estrategiasSubetiquetas),
            'default_estrategia' => config('planillas.importacion.estrategia_subetiquetas_default', 'legacy'),
            'limite_elementos' => config('planillas.importacion.limite_elementos_por_subetiqueta', 5),
        ]);
    }

    /**
     * Procesa una planilla completa.
     *
     * @param string $codigoPlanilla
     * @param array $filas
     * @param array &$advertencias
     * @param Planilla|null $planillaExistente
     * @param bool $aplicarPoliticaSubetiquetas Si es false, se debe llamar manualmente a aplicarPoliticaSubetiquetasPostAsignacion()
     * @return ProcesamientoResult
     * @throws \Exception
     */
    public function procesar(
        string $codigoPlanilla,
        array $filas,
        array &$advertencias,
        ?Planilla $planillaExistente = null,
        bool $aplicarPoliticaSubetiquetas = false  // ✅ Por defecto FALSE para nueva lógica
    ): ProcesamientoResult {
        // 1. Si hay planilla existente (reimportación), usarla
        if ($planillaExistente) {
            $planilla = $planillaExistente;

            // Calcular nuevo peso total (se actualizará al final)
            $pesoTotal = $this->calcularPesoTotal($filas, $codigoPlanilla, $advertencias);
        } else {
            // 2. Resolver cliente y obra (solo para nueva planilla)
            [$cliente, $obra] = $this->resolverClienteYObra($filas[0], $codigoPlanilla, $advertencias);

            if (!$cliente || !$obra) {
                throw new \Exception("No se pudo resolver cliente u obra para planilla {$codigoPlanilla}");
            }

            // 3. Calcular peso total
            $pesoTotal = $this->calcularPesoTotal($filas, $codigoPlanilla, $advertencias);

            // 4. Crear planilla base
            $planilla = $this->crearPlanilla($cliente, $obra, $filas[0], $codigoPlanilla, $pesoTotal);
        }

        // 5. Crear etiquetas padre y elementos
        $etiquetasPadre = $this->crearEtiquetasYElementos($planilla, $codigoPlanilla, $filas, $advertencias);

        // 6. ⚠️ POLÍTICA DE SUBETIQUETAS (OPCIONAL - solo si se especifica)
        // Por defecto NO se aplica aquí para permitir que se haga DESPUÉS de asignar máquinas
        if ($aplicarPoliticaSubetiquetas) {
            Log::channel('planilla_import')->warning("⚠️ [PlanillaProcessor] Aplicando política ANTES de asignar máquinas (legacy mode)");
            $this->aplicarPoliticaSubetiquetas($planilla, $etiquetasPadre);
            $this->limpiarEtiquetasPadreHuerfanas($planilla);
        } else {
            Log::channel('planilla_import')->info("⏳ [PlanillaProcessor] Política de subetiquetas diferida (se aplicará después de asignar máquinas)");
        }

        // 7. Guardar tiempo total
        $this->guardarTiempoTotal($planilla);

        // ℹ️ NOTA: La asignación de máquinas y creación de orden_planillas
        // se hace DESPUÉS en PlanillaImportService

        return new ProcesamientoResult(
            planilla: $planilla,
            elementosCreados: $planilla->elementos()->count(),
            etiquetasCreadas: count($etiquetasPadre)
        );
    }

    /**
     * ✅ NUEVO: Aplica la política de subetiquetas DESPUÉS de asignar máquinas.
     * 
     * Este método debe llamarse después de AsignarMaquinaService::repartirPlanilla()
     * para que las estrategias por máquina funcionen correctamente.
     *
     * @param Planilla $planilla
     * @return void
     */
    public function aplicarPoliticaSubetiquetasPostAsignacion(Planilla $planilla): void
    {
        Log::channel('planilla_import')->info("🎯 [PlanillaProcessor] Aplicando política de subetiquetas POST-asignación para planilla {$planilla->id}");

        // Obtener todas las etiquetas padre de esta planilla
        $etiquetasPadre = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNull('etiqueta_sub_id')
            ->get()
            ->all();  // ✅ Convertir a array para compatibilidad

        Log::channel('planilla_import')->debug("   📋 Total etiquetas padre: " . count($etiquetasPadre));

        if (empty($etiquetasPadre)) {
            Log::channel('planilla_import')->warning("   ⚠️ No se encontraron etiquetas padre");
            return;
        }

        // Aplicar política
        $this->aplicarPoliticaSubetiquetas($planilla, $etiquetasPadre);

        // Limpiar etiquetas huérfanas
        $eliminadas = $this->limpiarEtiquetasPadreHuerfanas($planilla);

        Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Política de subetiquetas completada", [
            'etiquetas_procesadas' => count($etiquetasPadre),
            'etiquetas_padre_eliminadas' => $eliminadas,
        ]);
    }

    // ========== MÉTODOS PRIVADOS ==========

    protected function resolverClienteYObra(array $fila, string $codigoPlanilla, array &$advertencias): array
    {
        $codCliente = trim($fila[0] ?? '');
        $nomCliente = trim($fila[1] ?? 'Cliente sin nombre');
        $codObra = trim($fila[2] ?? '');
        $nomObra = trim($fila[3] ?? 'Obra sin nombre');

        if (!$codCliente || !$codObra) {
            $advertencias[] = "Planilla {$codigoPlanilla}: falta código de cliente u obra.";
            return [null, null];
        }

        $cliente = Cliente::firstOrCreate(
            ['codigo' => $codCliente],
            ['empresa' => $nomCliente]
        );

        $obra = Obra::firstOrCreate(
            ['cod_obra' => $codObra],
            [
                'cliente_id' => $cliente->id,
                'obra' => $nomObra
            ]
        );

        return [$cliente, $obra];
    }

    protected function calcularPesoTotal(array $filas, string $codigoPlanilla, array &$advertencias): float
    {
        $pesoTotal = 0.0;

        foreach ($filas as $fila) {
            $peso = $this->normalizarNumerico(
                $fila[34] ?? null,
                'peso',
                $fila['_xl_row'] ?? 0,
                $codigoPlanilla,
                $advertencias
            );

            if ($peso !== false) {
                $pesoTotal += $peso;
            }
        }

        return $pesoTotal;
    }

    protected function crearPlanilla(
        Cliente $cliente,
        Obra $obra,
        array $primeraFila,
        string $codigoPlanilla,
        float $pesoTotal
    ): Planilla {
        return Planilla::create([
            'users_id' => auth()->id(),
            'cliente_id' => $cliente->id,
            'obra_id' => $obra->id,
            'seccion' => $primeraFila[7] ?? null,
            'descripcion' => $primeraFila[12] ?? null,
            'ensamblado' => $primeraFila[4] ?? null,
            'codigo' => $codigoPlanilla,
            'peso_total' => $pesoTotal,
            'fecha_estimada_entrega' => now()
                ->addDays(config('planillas.importacion.dias_entrega_default', 7))
                ->setTime(10, 0, 0),
        ]);
    }

    protected function crearEtiquetasYElementos(
        Planilla $planilla,
        string $codigoPlanilla,
        array $filas,
        array &$advertencias
    ): array {
        // Agrupar por número de etiqueta (columna 30)
        $porEtiqueta = [];

        foreach ($filas as $fila) {
            $numEtiqueta = $fila[30] ?? null;
            if ($numEtiqueta) {
                $porEtiqueta[$numEtiqueta][] = $fila;
            }
        }

        $etiquetasPadre = [];

        foreach ($porEtiqueta as $numEtiqueta => $filasEtiqueta) {
            // Crear etiqueta padre (contenedor)
            $codigoPadre = Etiqueta::generarCodigoEtiqueta();

            $etiquetaPadre = Etiqueta::create([
                'codigo' => $codigoPadre,
                'planilla_id' => $planilla->id,
                'nombre' => $filasEtiqueta[0][22] ?? 'Sin nombre',
            ]);

            $etiquetasPadre[] = $etiquetaPadre;

            // Agregar elementos por clave compuesta
            $elementosAgregados = $this->agregarElementos($filasEtiqueta, $codigoPlanilla, $advertencias);

            // Crear elementos
            $this->crearElementos(
                $planilla,
                $etiquetaPadre,
                $elementosAgregados,
                $codigoPlanilla,
                $advertencias
            );
        }

        return $etiquetasPadre;
    }

    protected function agregarElementos(array $filas, string $codigoPlanilla, array &$advertencias): array
    {
        $agregados = [];

        foreach ($filas as $fila) {
            if (!array_filter($fila)) {
                continue;
            }

            // Clave de agrupación: figura|fila|marca|diametro|longitud|dobles|dimensiones
            $clave = implode('|', [
                $fila[26], // figura
                $fila[21], // fila
                $fila[23], // marca
                $fila[25], // diametro
                $fila[27], // longitud
                $fila[33] ?? 0, // dobles_barra
                $fila[47] ?? '', // dimensiones
            ]);

            $excelRow = $fila['_xl_row'] ?? 0;

            // Normalizar peso y barras
            $peso = $this->normalizarNumerico($fila[34] ?? null, 'peso', $excelRow, $codigoPlanilla, $advertencias);
            $barras = $this->normalizarNumerico($fila[32] ?? null, 'barras', $excelRow, $codigoPlanilla, $advertencias);

            if ($peso === false || $barras === false) {
                continue;
            }

            // Agregar al grupo
            if (!isset($agregados[$clave])) {
                $agregados[$clave] = [
                    'fila' => $fila,
                    'peso' => 0.0,
                    'barras' => 0,
                ];
            }

            $agregados[$clave]['peso'] += $peso;
            $agregados[$clave]['barras'] += (int)$barras;
        }

        return $agregados;
    }

    protected function crearElementos(
        Planilla $planilla,
        Etiqueta $etiquetaPadre,
        array $elementosAgregados,
        string $codigoPlanilla,
        array &$advertencias
    ): void {
        foreach ($elementosAgregados as $item) {
            $fila = $item['fila'];
            $excelRow = $fila['_xl_row'] ?? 0;

            // Validar diámetro
            $diametro = $this->normalizarNumerico($fila[25] ?? null, 'diametro', $excelRow, $codigoPlanilla, $advertencias);

            if ($diametro === false) {
                continue;
            }

            if (!in_array((int)$diametro, $this->diametrosPermitidos, true)) {
                $advertencias[] = "Planilla {$codigoPlanilla}: diámetro no admitido '{$fila[25]}' (fila {$excelRow}).";
                continue;
            }

            // Validar longitud
            $longitud = $this->normalizarNumerico($fila[27] ?? null, 'longitud', $excelRow, $codigoPlanilla, $advertencias);

            if ($longitud === false) {
                continue;
            }

            // Dobles por barra
            $doblesBarra = (int)($this->normalizarNumerico($fila[33] ?? 0, 'dobles_barra', $excelRow, $codigoPlanilla, $advertencias) ?: 0);

            // Calcular tiempo de fabricación
            $tiempoFabricacion = $this->calcularTiempoFabricacion($item['barras'], $doblesBarra);

            // Crear elemento
            Elemento::create([
                'codigo' => Elemento::generarCodigo(),
                'planilla_id' => $planilla->id,
                'etiqueta_id' => $etiquetaPadre->id,
                'etiqueta_sub_id' => null, // Se asignará en política de subetiquetas
                'maquina_id' => null, // Se asignará por el servicio de máquinas
                'figura' => $fila[26] ?: null,
                'fila' => $fila[21] ?: null,
                'marca' => $fila[23] ?: null,
                'etiqueta' => $fila[30] ?: null,
                'diametro' => (int)$diametro,
                'longitud' => (float)$longitud,
                'barras' => (int)$item['barras'],
                'dobles_barra' => $doblesBarra,
                'peso' => (float)$item['peso'],
                'dimensiones' => $fila[47] ?? null,
                'tiempo_fabricacion' => $tiempoFabricacion,
                'estado' => 'pendiente',
            ]);
        }
    }

    /**
     * Aplica la política de subetiquetas según configuración por máquina.
     *
     * @param Planilla $planilla
     * @param array $etiquetasPadre
     * @return void
     */
    protected function aplicarPoliticaSubetiquetas(Planilla $planilla, array $etiquetasPadre): void
    {
        Log::channel('planilla_import')->info("🏷️ [PlanillaProcessor] Iniciando aplicación de política de subetiquetas", [
            'planilla_id' => $planilla->id,
            'total_etiquetas_padre' => count($etiquetasPadre),
        ]);

        foreach ($etiquetasPadre as $padre) {
            $elementos = Elemento::where('planilla_id', $planilla->id)
                ->where('etiqueta_id', $padre->id)
                ->get();

            Log::channel('planilla_import')->debug("   📦 Etiqueta padre {$padre->codigo}: {$elementos->count()} elementos");

            if ($elementos->isEmpty()) {
                Log::channel('planilla_import')->debug("      ⭕ Sin elementos, saltando");
                continue;
            }

            // Agrupar por máquina
            $gruposPorMaquina = $elementos->groupBy(
                fn($e) => $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? 0
            );

            Log::channel('planilla_import')->debug("      🔧 Agrupados en " . $gruposPorMaquina->count() . " máquinas: " . json_encode($gruposPorMaquina->keys()->toArray()));

            foreach ($gruposPorMaquina as $maquinaId => $lote) {
                $maquinaId = (int)$maquinaId;

                Log::channel('planilla_import')->debug("         ⚙️ Procesando máquina {$maquinaId} con {$lote->count()} elementos");

                if ($maquinaId === 0) {
                    Log::channel('planilla_import')->warning("         ⚠️ Elementos sin máquina asignada → estrategia INDIVIDUAL forzada");
                    // Sin máquina → sub nueva por elemento
                    foreach ($lote as $elemento) {
                        [$subId, $subRowId] = $this->crearSubetiquetaSiguiente($padre);
                        $elemento->update([
                            'etiqueta_id' => $subRowId,
                            'etiqueta_sub_id' => $subId
                        ]);
                    }
                    continue;
                }

                // ✅ Obtener estrategia configurada para esta máquina
                $maquina = \App\Models\Maquina::find($maquinaId);
                $estrategia = $this->obtenerEstrategiaParaMaquina($maquina);

                Log::channel('planilla_import')->info("         🎯 Máquina {$maquina->codigo} (ID {$maquinaId}) → estrategia: {$estrategia}");

                // Aplicar estrategia
                if ($estrategia === 'individual') {
                    $this->aplicarEstrategiaIndividual($lote, $padre);
                } elseif ($estrategia === 'agrupada') {
                    $this->aplicarEstrategiaAgrupada($lote, $padre);
                } else {
                    // Fallback a estrategia por defecto (tipo_material)
                    $this->aplicarEstrategiaLegacy($lote, $padre, $maquina);
                }
            }

            // Recalcular pesos
            $this->recalcularPesosEtiquetas($padre);
        }

        Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Política de subetiquetas completada para planilla {$planilla->id}");
    }

    protected function obtenerEstrategiaParaMaquina($maquina): string
    {
        if (!$maquina) {
            return 'individual'; // Sin máquina → individual por defecto
        }

        // Buscar por código de máquina
        if (isset($this->estrategiasSubetiquetas[$maquina->codigo])) {
            return $this->estrategiasSubetiquetas[$maquina->codigo];
        }

        // Buscar por tipo de máquina
        if (isset($this->estrategiasSubetiquetas[$maquina->tipo])) {
            return $this->estrategiasSubetiquetas[$maquina->tipo];
        }

        // Fallback a estrategia por defecto
        return config('planillas.importacion.estrategia_subetiquetas_default', 'legacy');
    }

    protected function aplicarEstrategiaIndividual($elementos, Etiqueta $padre): void
    {
        foreach ($elementos as $elemento) {
            [$subId, $subRowId] = $this->crearSubetiquetaSiguiente($padre);
            $elemento->update([
                'etiqueta_id' => $subRowId,
                'etiqueta_sub_id' => $subId
            ]);
        }
    }

    protected function aplicarEstrategiaAgrupada($elementos, Etiqueta $padre): void
    {
        $limitePorSubetiqueta = config('planillas.importacion.limite_elementos_por_subetiqueta', 5);

        Log::channel('planilla_import')->debug("📦 [PlanillaProcessor] Estrategia AGRUPADA para etiqueta {$padre->codigo}: {$elementos->count()} elementos → máx. {$limitePorSubetiqueta} por subetiqueta");

        // Dividir en lotes de 5 elementos
        $lotes = $elementos->chunk($limitePorSubetiqueta);

        Log::channel('planilla_import')->debug("   📊 Total subetiquetas necesarias: {$lotes->count()}");

        foreach ($lotes as $indexLote => $lote) {
            // Verificar si ya existe una subetiqueta para algún elemento del lote
            $subsExistentes = $lote->pluck('etiqueta_sub_id')->filter()->unique();

            if ($subsExistentes->isEmpty()) {
                // Crear nueva subetiqueta para este lote
                [$subCanonica, $subCanId] = $this->crearSubetiquetaSiguiente($padre);

                Log::channel('planilla_import')->debug("   ➕ Lote " . ($indexLote + 1) . ": creada subetiqueta {$subCanonica} para {$lote->count()} elementos");
            } else {
                // Usar la primera subetiqueta existente
                $subCanonica = (string)$subsExistentes->sortBy(
                    fn($sid) => (int)(preg_match('/\.(\d+)$/', (string)$sid, $m) ? $m[1] : 9999)
                )->first();

                $subCanId = $this->asegurarSubetiquetaExiste($subCanonica, $padre);

                Log::channel('planilla_import')->debug("   ♻️ Lote " . ($indexLote + 1) . ": reutilizando subetiqueta {$subCanonica} para {$lote->count()} elementos");
            }

            // Asignar todos los elementos del lote a esta subetiqueta
            foreach ($lote as $elemento) {
                if ($elemento->etiqueta_sub_id !== $subCanonica || $elemento->etiqueta_id !== $subCanId) {
                    $elemento->update([
                        'etiqueta_id' => $subCanId,
                        'etiqueta_sub_id' => $subCanonica
                    ]);
                }
            }
        }

        Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Etiqueta {$padre->codigo}: {$elementos->count()} elementos distribuidos en {$lotes->count()} subetiquetas");
    }

    protected function aplicarEstrategiaLegacy($elementos, Etiqueta $padre, $maquina): void
    {
        $tipoMaterial = strtolower((string)optional($maquina)->tipo_material);

        if ($tipoMaterial === 'barra') {
            // Barra → sub nueva por elemento
            $this->aplicarEstrategiaIndividual($elementos, $padre);
        } else {
            // Encarretado u otro → sub canónica por máquina
            $subsExistentes = collect($elementos)
                ->pluck('etiqueta_sub_id')
                ->filter()
                ->unique()
                ->values();

            if ($subsExistentes->isEmpty()) {
                [$subCanonica, $subCanId] = $this->crearSubetiquetaSiguiente($padre);
            } else {
                $subCanonica = (string)$subsExistentes->sortBy(
                    fn($sid) => (int)(preg_match('/\.(\d+)$/', (string)$sid, $m) ? $m[1] : 9999)
                )->first();

                $subCanId = $this->asegurarSubetiquetaExiste($subCanonica, $padre);
            }

            foreach ($elementos as $elemento) {
                if ($elemento->etiqueta_sub_id !== $subCanonica || $elemento->etiqueta_id !== $subCanId) {
                    $elemento->update([
                        'etiqueta_id' => $subCanId,
                        'etiqueta_sub_id' => $subCanonica
                    ]);
                }
            }
        }
    }

    protected function crearSubetiquetaSiguiente(Etiqueta $padre): array
    {
        $subId = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);

        $subRow = Etiqueta::firstWhere('etiqueta_sub_id', $subId);

        if (!$subRow) {
            $data = [
                'codigo' => $padre->codigo,
                'etiqueta_sub_id' => $subId,
                'planilla_id' => $padre->planilla_id,
                'nombre' => $padre->nombre,
                'estado' => $padre->estado ?? 'pendiente',
                'peso' => 0.0,
            ];

            // Copiar campos adicionales si existen
            $camposOpcionales = [
                'producto_id',
                'producto_id_2',
                'ubicacion_id',
                'operario1_id',
                'operario2_id',
                'soldador1_id',
                'soldador2_id',
                'ensamblador1_id',
                'ensamblador2_id',
                'marca',
                'paquete_id',
                'numero_etiqueta',
                'fecha_inicio',
                'fecha_finalizacion',
                'fecha_inicio_ensamblado',
                'fecha_finalizacion_ensamblado',
                'fecha_inicio_soldadura',
                'fecha_finalizacion_soldadura'
            ];

            foreach ($camposOpcionales as $campo) {
                if (Schema::hasColumn('etiquetas', $campo)) {
                    $data[$campo] = $padre->$campo;
                }
            }

            $subRow = Etiqueta::create($data);
        }

        return [$subId, (int)$subRow->id];
    }

    protected function asegurarSubetiquetaExiste(string $subId, Etiqueta $padre): int
    {
        $row = Etiqueta::firstWhere('etiqueta_sub_id', $subId);

        if ($row) {
            return (int)$row->id;
        }

        $data = [
            'codigo' => $padre->codigo,
            'etiqueta_sub_id' => $subId,
            'planilla_id' => $padre->planilla_id,
            'nombre' => $padre->nombre,
            'estado' => $padre->estado ?? 'pendiente',
            'peso' => 0.0,
        ];

        return (int)Etiqueta::create($data)->id;
    }

    protected function recalcularPesosEtiquetas(Etiqueta $padre): void
    {
        if (!Schema::hasColumn('etiquetas', 'peso')) {
            return;
        }

        $codigo = (string)$padre->codigo;

        // Actualizar peso de cada subetiqueta
        $subs = Etiqueta::where('codigo', $codigo)
            ->whereNotNull('etiqueta_sub_id')
            ->pluck('etiqueta_sub_id');

        foreach ($subs as $subId) {
            $peso = (float)Elemento::where('etiqueta_sub_id', $subId)->sum('peso');
            Etiqueta::where('etiqueta_sub_id', $subId)->update(['peso' => $peso]);
        }

        // Actualizar peso del padre
        $pesoPadre = (float)Elemento::where('etiqueta_sub_id', 'like', $codigo . '.%')->sum('peso');
        Etiqueta::where('codigo', $codigo)->whereNull('etiqueta_sub_id')->update(['peso' => $pesoPadre]);
    }

    protected function guardarTiempoTotal(Planilla $planilla): void
    {
        $elementos = $planilla->elementos()->get();
        $tiempoTotal = (float)$elementos->sum('tiempo_fabricacion') +
            ($elementos->count() * $this->tiempoSetupElemento);

        $planilla->update(['tiempo_fabricacion' => $tiempoTotal]);
    }

    protected function calcularTiempoFabricacion(int $barras, int $doblesBarra): float
    {
        if ($doblesBarra > 0) {
            // Elementos doblados (estribos)
            return $barras * $doblesBarra * 1.5;
        }

        // Barras rectas
        return $barras * 2;
    }

    protected function normalizarNumerico(
        $valor,
        string $campo,
        int $excelRow,
        string $codigoPlanilla,
        array &$advertencias
    ) {
        if ($valor === null || $valor === '') {
            return 0;
        }

        $raw = trim((string)$valor);

        // Normalizar: "1.234,56" → "1234.56", "1,23" → "1.23"
        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $norm = str_replace('.', '', $raw);
            $norm = str_replace(',', '.', $norm);
        } elseif (strpos($raw, ',') !== false) {
            $norm = str_replace(',', '.', $raw);
        } else {
            $norm = $raw;
        }

        if (!preg_match('/^-?\d+(\.\d+)?$/', $norm)) {
            $advertencias[] = "Fila omitida (planilla {$codigoPlanilla}, Excel {$excelRow}): {$campo}='{$valor}' no es numérico.";
            return false;
        }

        $num = (float)$norm;

        // Regla: barras no puede ser negativo
        if ($campo === 'barras' && $num < 0) {
            $advertencias[] = "Fila omitida (planilla {$codigoPlanilla}, Excel {$excelRow}): {$campo} negativo ('{$valor}').";
            return false;
        }

        return $num;
    }

    protected function limpiarEtiquetasPadreHuerfanas(Planilla $planilla): int
    {
        // Obtener etiquetas padre (sin etiqueta_sub_id)
        $etiquetasPadre = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNull('etiqueta_sub_id')
            ->get();

        if ($etiquetasPadre->isEmpty()) {
            return 0;
        }

        $eliminadas = 0;

        foreach ($etiquetasPadre as $padre) {
            // Verificar si tiene elementos asignados directamente
            $tieneElementos = Elemento::where('planilla_id', $planilla->id)
                ->where('etiqueta_id', $padre->id)
                ->exists();

            // Si no tiene elementos directos, eliminarla
            if (!$tieneElementos) {
                $padre->delete();
                $eliminadas++;
            }
        }

        if ($eliminadas > 0) {
            Log::channel('planilla_import')->info(
                "🗑️ [PlanillaProcessor] Planilla {$planilla->codigo}: eliminadas {$eliminadas} etiquetas padre sin elementos"
            );
        }

        return $eliminadas;
    }
}
