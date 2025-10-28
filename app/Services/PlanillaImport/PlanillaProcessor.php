<?php

namespace App\Services\PlanillaImport;

use App\Models\Planilla;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Services\PlanillaImport\DTOs\ProcesamientoResult;
use Illuminate\Support\Facades\Schema;

/**
 * Procesa los datos de una planilla individual.
 * 
 * Responsabilidades:
 * - Crear/obtener cliente y obra
 * - Crear planilla
 * - Crear etiquetas padre
 * - Crear elementos agregados
 * - Aplicar polÃ­tica de subetiquetas (configurable)
 * - Calcular totales
 * 
 * NO incluye:
 * - AsignaciÃ³n de mÃ¡quinas (AsignarMaquinaService)
 * - CreaciÃ³n de orden_planillas (OrdenPlanillaService)
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

        // âœ… ConfiguraciÃ³n de estrategias por mÃ¡quina
        $this->estrategiasSubetiquetas = config('planillas.importacion.estrategias_subetiquetas', []);
    }

    /**
     * Procesa una planilla completa.
     *
     * @param string $codigoPlanilla
     * @param array $filas
     * @param array &$advertencias
     * @return ProcesamientoResult
     * @throws \Exception
     */
    public function procesar(string $codigoPlanilla, array $filas, array &$advertencias, ?Planilla $planillaExistente = null): ProcesamientoResult
    {
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

        // 5. Aplicar polÃ­tica de subetiquetas
        $this->aplicarPoliticaSubetiquetas($planilla, $etiquetasPadre);

        // 7. Guardar tiempo total
        $this->guardarTiempoTotal($planilla);

        // âš ï¸ NOTA: La asignaciÃ³n de mÃ¡quinas y creaciÃ³n de orden_planillas
        // se hace DESPUÃ‰S en PlanillaImportService

        return new ProcesamientoResult(
            planilla: $planilla,
            elementosCreados: $planilla->elementos()->count(),
            etiquetasCreadas: count($etiquetasPadre)
        );
    }

    /**
     * Resuelve (busca o crea) cliente y obra desde los datos de una fila.
     *
     * @param array $fila
     * @param string $codigoPlanilla
     * @param array &$advertencias
     * @return array [Cliente|null, Obra|null]
     */
    protected function resolverClienteYObra(array $fila, string $codigoPlanilla, array &$advertencias): array
    {
        $codCliente = trim($fila[0] ?? '');
        $nomCliente = trim($fila[1] ?? 'Cliente sin nombre');
        $codObra = trim($fila[2] ?? '');
        $nomObra = trim($fila[3] ?? 'Obra sin nombre');

        if (!$codCliente || !$codObra) {
            $advertencias[] = "Planilla {$codigoPlanilla}: falta cÃ³digo de cliente u obra.";
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

    /**
     * Calcula el peso total sumando todos los elementos.
     *
     * @param array $filas
     * @param string $codigoPlanilla
     * @param array &$advertencias
     * @return float
     */
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

    /**
     * Crea la planilla base.
     *
     * @param Cliente $cliente
     * @param Obra $obra
     * @param array $primeraFila
     * @param string $codigoPlanilla
     * @param float $pesoTotal
     * @return Planilla
     */
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

    /**
     * Crea etiquetas padre y elementos agregados.
     *
     * @param Planilla $planilla
     * @param string $codigoPlanilla
     * @param array $filas
     * @param array &$advertencias
     * @return array Array de etiquetas padre creadas
     */
    protected function crearEtiquetasYElementos(
        Planilla $planilla,
        string $codigoPlanilla,
        array $filas,
        array &$advertencias
    ): array {
        // Agrupar por nÃºmero de etiqueta (columna 30)
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

    /**
     * Agrupa elementos por clave compuesta para consolidarlos.
     *
     * @param array $filas
     * @param string $codigoPlanilla
     * @param array &$advertencias
     * @return array
     */
    protected function agregarElementos(array $filas, string $codigoPlanilla, array &$advertencias): array
    {
        $agregados = [];

        foreach ($filas as $fila) {
            if (!array_filter($fila)) {
                continue;
            }

            // Clave de agrupaciÃ³n: figura|fila|marca|diametro|longitud|dobles|dimensiones
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

    /**
     * Crea los elementos en la base de datos.
     *
     * @param Planilla $planilla
     * @param Etiqueta $etiquetaPadre
     * @param array $elementosAgregados
     * @param string $codigoPlanilla
     * @param array &$advertencias
     * @return void
     */
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

            // Validar diÃ¡metro
            $diametro = $this->normalizarNumerico($fila[25] ?? null, 'diametro', $excelRow, $codigoPlanilla, $advertencias);

            if ($diametro === false) {
                continue;
            }

            if (!in_array((int)$diametro, $this->diametrosPermitidos, true)) {
                $advertencias[] = "Planilla {$codigoPlanilla}: diÃ¡metro no admitido '{$fila[25]}' (fila {$excelRow}).";
                continue;
            }

            // Validar longitud
            $longitud = $this->normalizarNumerico($fila[27] ?? null, 'longitud', $excelRow, $codigoPlanilla, $advertencias);

            if ($longitud === false) {
                continue;
            }

            // Dobles por barra
            $doblesBarra = (int)($this->normalizarNumerico($fila[33] ?? 0, 'dobles_barra', $excelRow, $codigoPlanilla, $advertencias) ?: 0);

            // Calcular tiempo de fabricaciÃ³n
            $tiempoFabricacion = $this->calcularTiempoFabricacion($item['barras'], $doblesBarra);

            // Crear elemento
            Elemento::create([
                'codigo' => Elemento::generarCodigo(),
                'planilla_id' => $planilla->id,
                'etiqueta_id' => $etiquetaPadre->id,
                'etiqueta_sub_id' => null, // Se asignarÃ¡ en polÃ­tica de subetiquetas
                'maquina_id' => null, // Se asignarÃ¡ por el servicio de mÃ¡quinas
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
     * Aplica la polÃ­tica de subetiquetas segÃºn configuraciÃ³n por mÃ¡quina.
     *
     * @param Planilla $planilla
     * @param array $etiquetasPadre
     * @return void
     */
    protected function aplicarPoliticaSubetiquetas(Planilla $planilla, array $etiquetasPadre): void
    {
        foreach ($etiquetasPadre as $padre) {
            $elementos = Elemento::where('planilla_id', $planilla->id)
                ->where('etiqueta_id', $padre->id)
                ->get();

            if ($elementos->isEmpty()) {
                continue;
            }

            // Agrupar por mÃ¡quina
            $gruposPorMaquina = $elementos->groupBy(
                fn($e) => $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? 0
            );

            foreach ($gruposPorMaquina as $maquinaId => $lote) {
                $maquinaId = (int)$maquinaId;

                if ($maquinaId === 0) {
                    // Sin mÃ¡quina â†’ sub nueva por elemento
                    foreach ($lote as $elemento) {
                        [$subId, $subRowId] = $this->crearSubetiquetaSiguiente($padre);
                        $elemento->update([
                            'etiqueta_id' => $subRowId,
                            'etiqueta_sub_id' => $subId
                        ]);
                    }
                    continue;
                }

                // âœ… Obtener estrategia configurada para esta mÃ¡quina
                $maquina = \App\Models\Maquina::find($maquinaId);
                $estrategia = $this->obtenerEstrategiaParaMaquina($maquina);

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
    }

    /**
     * Obtiene la estrategia de subetiquetas configurada para una mÃ¡quina.
     *
     * @param \App\Models\Maquina|null $maquina
     * @return string 'individual', 'agrupada', o 'legacy'
     */
    protected function obtenerEstrategiaParaMaquina($maquina): string
    {
        if (!$maquina) {
            return 'individual'; // Sin mÃ¡quina â†’ individual por defecto
        }

        // Buscar por cÃ³digo de mÃ¡quina
        if (isset($this->estrategiasSubetiquetas[$maquina->codigo])) {
            return $this->estrategiasSubetiquetas[$maquina->codigo];
        }

        // Buscar por tipo de mÃ¡quina
        if (isset($this->estrategiasSubetiquetas[$maquina->tipo])) {
            return $this->estrategiasSubetiquetas[$maquina->tipo];
        }

        // Fallback a estrategia por defecto
        return config('planillas.importacion.estrategia_subetiquetas_default', 'legacy');
    }

    /**
     * Estrategia INDIVIDUAL: Una subetiqueta por cada elemento.
     * 
     * Ãštil para mÃ¡quinas que procesan barras individuales.
     *
     * @param \Illuminate\Support\Collection $elementos
     * @param Etiqueta $padre
     * @return void
     */
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

    /**
     * Estrategia AGRUPADA: Una subetiqueta compartida por elementos similares.
     * 
     * Agrupa elementos que comparten caracterÃ­sticas clave:
     * - Mismo diÃ¡metro
     * - Misma longitud
     * - Mismo nÃºmero de dobles
     * - Mismas dimensiones
     *
     * @param \Illuminate\Support\Collection $elementos
     * @param Etiqueta $padre
     * @return void
     */
    protected function aplicarEstrategiaAgrupada($elementos, Etiqueta $padre): void
    {
        // Agrupar elementos por caracterÃ­sticas similares
        $grupos = $elementos->groupBy(function ($elemento) {
            return implode('|', [
                $elemento->diametro ?? '',
                $elemento->longitud ?? '',
                $elemento->dobles_barra ?? 0,
                $elemento->dimensiones ?? '',
            ]);
        });

        // Crear una subetiqueta por grupo
        foreach ($grupos as $grupo) {
            // Verificar si ya existe una subetiqueta para algÃºn elemento del grupo
            $subsExistentes = $grupo->pluck('etiqueta_sub_id')->filter()->unique();

            if ($subsExistentes->isEmpty()) {
                // Crear nueva subetiqueta para todo el grupo
                [$subCanonica, $subCanId] = $this->crearSubetiquetaSiguiente($padre);
            } else {
                // Usar la primera subetiqueta existente
                $subCanonica = (string)$subsExistentes->sortBy(
                    fn($sid) => (int)(preg_match('/\.(\d+)$/', (string)$sid, $m) ? $m[1] : 9999)
                )->first();

                $subCanId = $this->asegurarSubetiquetaExiste($subCanonica, $padre);
            }

            // Asignar todos los elementos del grupo a esta subetiqueta
            foreach ($grupo as $elemento) {
                if ($elemento->etiqueta_sub_id !== $subCanonica || $elemento->etiqueta_id !== $subCanId) {
                    $elemento->update([
                        'etiqueta_id' => $subCanId,
                        'etiqueta_sub_id' => $subCanonica
                    ]);
                }
            }
        }
    }

    /**
     * Estrategia LEGACY: Basada en tipo_material de la mÃ¡quina.
     * 
     * Mantiene compatibilidad con el sistema anterior:
     * - tipo_material = 'barra' â†’ individual
     * - tipo_material = 'encarretado' u otro â†’ agrupada
     *
     * @param \Illuminate\Support\Collection $elementos
     * @param Etiqueta $padre
     * @param \App\Models\Maquina|null $maquina
     * @return void
     */
    protected function aplicarEstrategiaLegacy($elementos, Etiqueta $padre, $maquina): void
    {
        $tipoMaterial = strtolower((string)optional($maquina)->tipo_material);

        if ($tipoMaterial === 'barra') {
            // Barra â†’ sub nueva por elemento
            $this->aplicarEstrategiaIndividual($elementos, $padre);
        } else {
            // Encarretado u otro â†’ sub canÃ³nica por mÃ¡quina
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

    /**
     * Crea la siguiente subetiqueta para un padre.
     *
     * @param Etiqueta $padre
     * @return array [subId string, subRowId int]
     */
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

    /**
     * Asegura que existe una fila de subetiqueta y retorna su ID.
     *
     * @param string $subId
     * @param Etiqueta $padre
     * @return int
     */
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

    /**
     * Recalcula los pesos de las etiquetas (padre y subs).
     *
     * @param Etiqueta $padre
     * @return void
     */
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

    /**
     * Guarda el tiempo total de fabricaciÃ³n de la planilla.
     *
     * @param Planilla $planilla
     * @return void
     */
    protected function guardarTiempoTotal(Planilla $planilla): void
    {
        $elementos = $planilla->elementos()->get();
        $tiempoTotal = (float)$elementos->sum('tiempo_fabricacion') +
            ($elementos->count() * $this->tiempoSetupElemento);

        $planilla->update(['tiempo_fabricacion' => $tiempoTotal]);
    }

    /**
     * Calcula el tiempo de fabricaciÃ³n de un elemento.
     *
     * @param int $barras
     * @param int $doblesBarra
     * @return float
     */
    protected function calcularTiempoFabricacion(int $barras, int $doblesBarra): float
    {
        if ($doblesBarra > 0) {
            // Elementos doblados (estribos)
            return $barras * $doblesBarra * 1.5;
        }

        // Barras rectas
        return $barras * 2;
    }

    /**
     * Normaliza y valida un valor numÃ©rico.
     *
     * @param mixed $valor
     * @param string $campo
     * @param int $excelRow
     * @param string $codigoPlanilla
     * @param array &$advertencias
     * @return float|false False si el valor es invÃ¡lido
     */
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

        // Normalizar: "1.234,56" â†’ "1234.56", "1,23" â†’ "1.23"
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
}
