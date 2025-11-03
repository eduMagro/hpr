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
use Illuminate\Support\Facades\DB;

/**
 * Procesa los datos de una planilla individual - VERSIÓN OPTIMIZADA
 * 
 * Mejoras:
 * - Bulk inserts para elementos y etiquetas
 * - Aplicación correcta de subetiquetas por marca
 * - Respeta orden original de elementos
 * - Mejor rendimiento en importaciones masivas
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

        // ✅ Cargar estrategias con debug
        $this->estrategiasSubetiquetas = config('planillas.importacion.estrategias_subetiquetas', []);

        Log::channel('planilla_import')->debug("🔧 [PlanillaProcessor] Configuración cargada", [
            'estrategias_configuradas' => array_keys($this->estrategiasSubetiquetas),
            'default_estrategia' => config('planillas.importacion.estrategia_subetiquetas_default', 'legacy'),
            'limite_elementos' => config('planillas.importacion.limite_elementos_por_subetiqueta', 5),
        ]);
    }

    /**
     * Procesa una planilla completa.
     */
    public function procesar(
        string $codigoPlanilla,
        array $filas,
        array &$advertencias,
        ?Planilla $planillaExistente = null,
        bool $aplicarPoliticaSubetiquetas = false
    ): ProcesamientoResult {
        // 1. Si hay planilla existente (reimportación), usarla
        if ($planillaExistente) {
            $planilla = $planillaExistente;
            $pesoTotal = $this->calcularPesoTotal($filas, $codigoPlanilla, $advertencias);
        } else {
            // 2. Resolver cliente y obra
            [$cliente, $obra] = $this->resolverClienteYObra($filas[0], $codigoPlanilla, $advertencias);

            if (!$cliente || !$obra) {
                throw new \Exception("No se pudo resolver cliente u obra para planilla {$codigoPlanilla}");
            }

            $pesoTotal = $this->calcularPesoTotal($filas, $codigoPlanilla, $advertencias);
            $planilla = $this->crearPlanilla($cliente, $obra, $filas[0], $codigoPlanilla, $pesoTotal);
        }

        // 3. ✅ OPTIMIZADO: Crear etiquetas y elementos con bulk insert
        $resultado = $this->crearEtiquetasYElementosOptimizado($planilla, $codigoPlanilla, $filas, $advertencias);

        // 4. Política de subetiquetas (opcional)
        if ($aplicarPoliticaSubetiquetas) {
            Log::channel('planilla_import')->warning("⚠️ [PlanillaProcessor] Aplicando política ANTES de asignar máquinas (legacy mode)");
            $this->aplicarPoliticaSubetiquetasOptimizada($planilla, $resultado['etiquetas_padre']);
            $this->limpiarEtiquetasPadreHuerfanas($planilla);
        } else {
            Log::channel('planilla_import')->info("⏳ [PlanillaProcessor] Política de subetiquetas diferida");
        }

        // 5. Guardar tiempo total
        $this->guardarTiempoTotal($planilla);

        return new ProcesamientoResult(
            planilla: $planilla,
            elementosCreados: $resultado['elementos_creados'],
            etiquetasCreadas: $resultado['etiquetas_creadas']
        );
    }

    /**
     * ✅ OPTIMIZADO: Crea etiquetas y elementos con bulk inserts
     */
    protected function crearEtiquetasYElementosOptimizado(
        Planilla $planilla,
        string $codigoPlanilla,
        array $filas,
        array &$advertencias
    ): array {
        Log::channel('planilla_import')->info("📦 [PlanillaProcessor] Creando etiquetas y elementos (optimizado)");

        // Agrupar por número de etiqueta (columna 30) que ya viene por MARCA gracias al autocompletado
        $porEtiqueta = [];
        foreach ($filas as $fila) {
            $numEtiqueta = $fila[30] ?? null;
            if ($numEtiqueta) {
                $porEtiqueta[$numEtiqueta][] = $fila;
            }
        }

        Log::channel('planilla_import')->debug("   📋 Total grupos de marca: " . count($porEtiqueta));

        $etiquetasPadre = [];
        $elementosParaInsertar = [];
        $elementosCreados = 0;

        // ✅ Pre-generar códigos únicos para todos los elementos
        $codigosGenerados = $this->generarCodigosUnicos($porEtiqueta);
        $indiceCodigo = 0;

        foreach ($porEtiqueta as $numEtiqueta => $filasEtiqueta) {
            // Crear etiqueta padre
            $codigoPadre = Etiqueta::generarCodigoEtiqueta();

            $etiquetaPadre = Etiqueta::create([
                'codigo' => $codigoPadre,
                'planilla_id' => $planilla->id,
                'nombre' => $filasEtiqueta[0][22] ?? 'Sin nombre',
            ]);

            $etiquetasPadre[] = $etiquetaPadre;

            // Agregar elementos por clave compuesta
            $elementosAgregados = $this->agregarElementos($filasEtiqueta, $codigoPlanilla, $advertencias);

            // Preparar elementos para bulk insert
            foreach ($elementosAgregados as $item) {
                $fila = $item['fila'];
                $excelRow = $fila['_xl_row'] ?? 0;

                // Validar diámetro
                $diametro = $this->normalizarNumerico($fila[25] ?? null, 'diametro', $excelRow, $codigoPlanilla, $advertencias);
                if ($diametro === false) continue;

                if (!in_array((int)$diametro, $this->diametrosPermitidos, true)) {
                    $advertencias[] = "Planilla {$codigoPlanilla}: diámetro no admitido '{$fila[25]}' (fila {$excelRow}).";
                    continue;
                }

                // Validar longitud
                $longitud = $this->normalizarNumerico($fila[27] ?? null, 'longitud', $excelRow, $codigoPlanilla, $advertencias);
                if ($longitud === false) continue;

                $doblesBarra = (int)($this->normalizarNumerico($fila[33] ?? 0, 'dobles_barra', $excelRow, $codigoPlanilla, $advertencias) ?: 0);
                $tiempoFabricacion = $this->calcularTiempoFabricacion($item['barras'], $doblesBarra);

                // ✅ Usar código pre-generado único
                $codigoUnico = $codigosGenerados[$indiceCodigo] ?? Elemento::generarCodigo();
                $indiceCodigo++;

                $elementosParaInsertar[] = [
                    'codigo' => $codigoUnico,
                    'planilla_id' => $planilla->id,
                    'etiqueta_id' => $etiquetaPadre->id,
                    'etiqueta_sub_id' => null,
                    'maquina_id' => null,
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $elementosCreados++;
            }
        }

        // ✅ BULK INSERT de todos los elementos
        if (!empty($elementosParaInsertar)) {
            // Insertar en chunks para evitar límites de SQL
            $chunks = array_chunk($elementosParaInsertar, 100);
            foreach ($chunks as $chunk) {
                DB::table('elementos')->insert($chunk);
            }
            Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Bulk insert: {$elementosCreados} elementos");
        }

        return [
            'etiquetas_padre' => $etiquetasPadre,
            'elementos_creados' => $elementosCreados,
            'etiquetas_creadas' => count($etiquetasPadre),
        ];
    }

    /**
     * ✅ Pre-genera códigos únicos para todos los elementos de forma eficiente
     * 
     * Genera códigos usando timestamp + contador secuencial para garantizar unicidad
     * sin necesidad de consultar la base de datos repetidamente.
     */
    protected function generarCodigosUnicos(array $porEtiqueta): array
    {
        // Calcular estimación de códigos necesarios (con buffer del 150%)
        $totalFilas = 0;
        foreach ($porEtiqueta as $filasEtiqueta) {
            $totalFilas += count($filasEtiqueta);
        }

        // Agregar buffer porque algunas filas se agregan
        $codigosAGenerar = (int)ceil($totalFilas * 1.5);

        Log::channel('planilla_import')->debug("🔢 [PlanillaProcessor] Pre-generando hasta {$codigosAGenerar} códigos únicos");

        $codigos = [];

        // Generar base de código con timestamp + microtime para mayor unicidad
        $timestamp = now()->format('ymdHis'); // Ej: 20251103193220
        $microtime = substr((string)microtime(true), -4); // últimos 4 dígitos

        // Obtener el último código existente para continuar la secuencia
        $prefijo = 'EL' . now()->format('ymd'); // EL251103
        $ultimoCodigo = DB::table('elementos')
            ->where('codigo', 'like', "{$prefijo}%")
            ->orderByDesc('id')
            ->value('codigo');

        $contador = 1;
        if ($ultimoCodigo && preg_match('/EL\d{6}(\d+)$/', $ultimoCodigo, $matches)) {
            $contador = (int)$matches[1] + 1;
        }

        // Generar todos los códigos necesarios
        for ($i = 0; $i < $codigosAGenerar; $i++) {
            $codigos[] = sprintf('%s%04d', $prefijo, $contador);
            $contador++;
        }

        Log::channel('planilla_import')->debug("✅ [PlanillaProcessor] Códigos generados: {$codigos[0]} a " . end($codigos));

        return $codigos;
    }

    /**
     * ✅ OPTIMIZADO: Aplica política de subetiquetas respetando orden y marca
     */
    public function aplicarPoliticaSubetiquetasPostAsignacion(Planilla $planilla): void
    {
        Log::channel('planilla_import')->info("🎯 [PlanillaProcessor] Aplicando política POST-asignación (optimizada)");

        $etiquetasPadre = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNull('etiqueta_sub_id')
            ->get();

        if ($etiquetasPadre->isEmpty()) {
            Log::channel('planilla_import')->warning("   ⚠️ No hay etiquetas padre");
            return;
        }

        $this->aplicarPoliticaSubetiquetasOptimizada($planilla, $etiquetasPadre->all());
        $eliminadas = $this->limpiarEtiquetasPadreHuerfanas($planilla);

        Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Política completada", [
            'etiquetas_procesadas' => $etiquetasPadre->count(),
            'etiquetas_padre_eliminadas' => $eliminadas,
        ]);
    }

    /**
     * ✅ NUEVA LÓGICA: Aplica subetiquetas por marca respetando orden original
     * 
     * Flujo correcto:
     * 1. Para cada etiqueta padre (que representa una MARCA):
     * 2. Obtener elementos ordenados por ID (orden de creación)
     * 3. Agrupar por máquina manteniendo el orden
     * 4. Aplicar estrategia según la máquina
     */
    protected function aplicarPoliticaSubetiquetasOptimizada(Planilla $planilla, array $etiquetasPadre): void
    {
        Log::channel('planilla_import')->info("🏷️ [PlanillaProcessor] Aplicando política de subetiquetas optimizada");

        // Preparar datos para bulk updates
        $actualizacionesPorLote = [];

        foreach ($etiquetasPadre as $padre) {
            // ✅ Obtener elementos ORDENADOS por ID (orden de creación/importación)
            $elementos = Elemento::where('planilla_id', $planilla->id)
                ->where('etiqueta_id', $padre->id)
                ->orderBy('id', 'asc')  // ✅ CRÍTICO: Mantener orden original
                ->get();

            if ($elementos->isEmpty()) {
                continue;
            }

            Log::channel('planilla_import')->debug("   📦 Etiqueta padre {$padre->codigo}: {$elementos->count()} elementos");

            // ✅ Agrupar por máquina MANTENIENDO el orden
            $gruposPorMaquina = $this->agruparPorMaquinaManteniendoOrden($elementos);

            foreach ($gruposPorMaquina as $maquinaId => $lote) {
                $maquinaId = (int)$maquinaId;

                if ($maquinaId === 0) {
                    Log::channel('planilla_import')->warning("         ⚠️ Elementos sin máquina → estrategia INDIVIDUAL forzada");
                    $actualizacionesPorLote = array_merge(
                        $actualizacionesPorLote,
                        $this->aplicarEstrategiaIndividualOptimizada($lote, $padre)
                    );
                    continue;
                }

                $maquina = \App\Models\Maquina::find($maquinaId);
                $estrategia = $this->obtenerEstrategiaParaMaquina($maquina);

                Log::channel('planilla_import')->info("         🎯 Máquina {$maquina->codigo} (ID {$maquinaId}) → estrategia: {$estrategia}");

                if ($estrategia === 'individual') {
                    $actualizacionesPorLote = array_merge(
                        $actualizacionesPorLote,
                        $this->aplicarEstrategiaIndividualOptimizada($lote, $padre)
                    );
                } elseif ($estrategia === 'agrupada') {
                    $actualizacionesPorLote = array_merge(
                        $actualizacionesPorLote,
                        $this->aplicarEstrategiaAgrupadaOptimizada($lote, $padre)
                    );
                } else {
                    $actualizacionesPorLote = array_merge(
                        $actualizacionesPorLote,
                        $this->aplicarEstrategiaLegacyOptimizada($lote, $padre, $maquina)
                    );
                }
            }

            // Recalcular pesos después de todas las asignaciones
            $this->recalcularPesosEtiquetas($padre);
        }

        // ✅ BULK UPDATE de todas las asignaciones de subetiquetas
        $this->ejecutarBulkUpdates($actualizacionesPorLote);

        Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Política optimizada completada");
    }

    /**
     * ✅ Agrupa elementos por máquina MANTENIENDO el orden original
     */
    protected function agruparPorMaquinaManteniendoOrden($elementos): array
    {
        $grupos = [];

        foreach ($elementos as $elemento) {
            $maquinaId = $elemento->maquina_id ?? $elemento->maquina_id_2 ?? $elemento->maquina_id_3 ?? 0;

            if (!isset($grupos[$maquinaId])) {
                $grupos[$maquinaId] = collect();
            }

            $grupos[$maquinaId]->push($elemento);
        }

        return $grupos;
    }

    /**
     * ✅ OPTIMIZADO: Estrategia individual sin crear registros, solo preparar updates
     */
    protected function aplicarEstrategiaIndividualOptimizada($elementos, Etiqueta $padre): array
    {
        $updates = [];
        $contadorSubetiqueta = 1;

        // Verificar si ya hay subetiquetas creadas para este padre
        $ultimaSubetiqueta = Etiqueta::where('codigo', $padre->codigo)
            ->whereNotNull('etiqueta_sub_id')
            ->orderByRaw('CAST(SUBSTRING_INDEX(etiqueta_sub_id, ".", -1) AS UNSIGNED) DESC')
            ->first();

        if ($ultimaSubetiqueta && preg_match('/\.(\d+)$/', $ultimaSubetiqueta->etiqueta_sub_id, $m)) {
            $contadorSubetiqueta = (int)$m[1] + 1;
        }

        foreach ($elementos as $elemento) {
            $subId = sprintf('%s.%02d', $padre->codigo, $contadorSubetiqueta);
            $contadorSubetiqueta++;

            $updates[] = [
                'elemento_id' => $elemento->id,
                'sub_id' => $subId,
                'padre_id' => $padre->id,
            ];
        }

        return $updates;
    }

    /**
     * ✅ OPTIMIZADO: Estrategia agrupada de 5 en 5 (configurable)
     */
    protected function aplicarEstrategiaAgrupadaOptimizada($elementos, Etiqueta $padre): array
    {
        // ✅ Leer límite desde configuración
        $limitePorSubetiqueta = config('planillas.importacion.limite_elementos_por_subetiqueta', 5);
        $updates = [];

        Log::channel('planilla_import')->info("📦 [PlanillaProcessor] Estrategia AGRUPADA para etiqueta {$padre->codigo}", [
            'elementos_total' => $elementos->count(),
            'limite_por_subetiqueta' => $limitePorSubetiqueta,
            'subetiquetas_estimadas' => ceil($elementos->count() / $limitePorSubetiqueta),
        ]);

        // Verificar última subetiqueta
        $ultimaSubetiqueta = Etiqueta::where('codigo', $padre->codigo)
            ->whereNotNull('etiqueta_sub_id')
            ->orderByRaw('CAST(SUBSTRING_INDEX(etiqueta_sub_id, ".", -1) AS UNSIGNED) DESC')
            ->first();

        $contadorSubetiqueta = 1;
        if ($ultimaSubetiqueta && preg_match('/\.(\d+)$/', $ultimaSubetiqueta->etiqueta_sub_id, $m)) {
            $contadorSubetiqueta = (int)$m[1] + 1;
        }

        // Dividir en lotes según configuración
        $lotes = $elementos->chunk($limitePorSubetiqueta);

        Log::channel('planilla_import')->debug("   📊 Dividiendo en {$lotes->count()} lotes de hasta {$limitePorSubetiqueta} elementos");

        foreach ($lotes as $indiceLote => $lote) {
            $subId = sprintf('%s.%02d', $padre->codigo, $contadorSubetiqueta);

            Log::channel('planilla_import')->debug("      ✓ Lote " . ($indiceLote + 1) . ": {$lote->count()} elementos → {$subId}");

            $contadorSubetiqueta++;

            foreach ($lote as $elemento) {
                $updates[] = [
                    'elemento_id' => $elemento->id,
                    'sub_id' => $subId,
                    'padre_id' => $padre->id,
                ];
            }
        }

        Log::channel('planilla_import')->info("✅ [PlanillaProcessor] Etiqueta {$padre->codigo}: {$elementos->count()} elementos distribuidos en {$lotes->count()} subetiquetas");

        return $updates;
    }

    /**
     * ✅ Ejecuta bulk updates para asignar subetiquetas
     */
    protected function ejecutarBulkUpdates(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        Log::channel('planilla_import')->info("💾 [PlanillaProcessor] Ejecutando bulk update de {" . count($updates) . "} subetiquetas");

        // Agrupar por sub_id para crear etiquetas necesarias
        $subsUnicas = array_unique(array_column($updates, 'sub_id'));
        $subetiquetasACrear = [];

        foreach ($subsUnicas as $subId) {
            // Buscar el padre_id correspondiente
            $padreId = null;
            foreach ($updates as $update) {
                if ($update['sub_id'] === $subId) {
                    $padreId = $update['padre_id'];
                    break;
                }
            }

            if (!$padreId) continue;

            $padre = Etiqueta::find($padreId);
            if (!$padre) continue;

            // Verificar si la subetiqueta ya existe
            $existe = Etiqueta::where('etiqueta_sub_id', $subId)->exists();

            if (!$existe) {
                $subetiquetasACrear[] = [
                    'codigo' => $padre->codigo,
                    'etiqueta_sub_id' => $subId,
                    'planilla_id' => $padre->planilla_id,
                    'nombre' => $padre->nombre,
                    'estado' => 'pendiente',
                    'peso' => 0.0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Bulk insert de subetiquetas nuevas
        if (!empty($subetiquetasACrear)) {
            DB::table('etiquetas')->insert($subetiquetasACrear);
            Log::channel('planilla_import')->info("   ✅ Creadas " . count($subetiquetasACrear) . " subetiquetas nuevas");
        }

        // Obtener IDs de las subetiquetas
        $subIdToRowId = Etiqueta::whereIn('etiqueta_sub_id', $subsUnicas)
            ->pluck('id', 'etiqueta_sub_id')
            ->toArray();

        // Preparar y ejecutar updates por chunks
        $elementosParaActualizar = [];
        foreach ($updates as $update) {
            $subRowId = $subIdToRowId[$update['sub_id']] ?? null;
            if ($subRowId) {
                $elementosParaActualizar[] = [
                    'id' => $update['elemento_id'],
                    'etiqueta_id' => $subRowId,
                    'etiqueta_sub_id' => $update['sub_id'],
                ];
            }
        }

        // Bulk update usando CASE
        if (!empty($elementosParaActualizar)) {
            $chunks = array_chunk($elementosParaActualizar, 500);
            foreach ($chunks as $chunk) {
                $this->bulkUpdateElementos($chunk);
            }
            Log::channel('planilla_import')->info("   ✅ Actualizados " . count($elementosParaActualizar) . " elementos");
        }
    }

    /**
     * ✅ Bulk update eficiente usando CASE
     */
    protected function bulkUpdateElementos(array $elementos): void
    {
        $ids = array_column($elementos, 'id');

        $casesEtiquetaId = '';
        $casesSubId = '';

        foreach ($elementos as $elem) {
            $casesEtiquetaId .= sprintf("WHEN %d THEN %d ", $elem['id'], $elem['etiqueta_id']);
            // ✅ FIX: No usar quote() dentro de comillas, o el valor ya viene escapado
            $etiquetaSubIdEscapado = str_replace("'", "''", $elem['etiqueta_sub_id']); // Escapar comillas simples
            $casesSubId .= sprintf("WHEN %d THEN '%s' ", $elem['id'], $etiquetaSubIdEscapado);
        }

        $idsString = implode(',', $ids);

        DB::statement("
            UPDATE elementos
            SET 
                etiqueta_id = CASE id {$casesEtiquetaId} END,
                etiqueta_sub_id = CASE id {$casesSubId} END,
                updated_at = NOW()
            WHERE id IN ({$idsString})
        ");
    }

    // ========== MÉTODOS AUXILIARES (sin cambios funcionales) ==========

    protected function aplicarEstrategiaLegacyOptimizada($elementos, Etiqueta $padre, $maquina): array
    {
        $tipoMaterial = strtolower((string)optional($maquina)->tipo_material);

        if ($tipoMaterial === 'barra') {
            return $this->aplicarEstrategiaIndividualOptimizada($elementos, $padre);
        } else {
            return $this->aplicarEstrategiaAgrupadaOptimizada($elementos, $padre);
        }
    }

    protected function obtenerEstrategiaParaMaquina($maquina): string
    {
        if (!$maquina) {
            Log::channel('planilla_import')->debug("      ℹ️ Sin máquina → estrategia: individual");
            return 'individual';
        }

        // ✅ Prioridad 1: Buscar por código de máquina
        if (isset($this->estrategiasSubetiquetas[$maquina->codigo])) {
            $estrategia = $this->estrategiasSubetiquetas[$maquina->codigo];
            Log::channel('planilla_import')->debug("      🎯 Máquina {$maquina->codigo} → estrategia por CÓDIGO: {$estrategia}");
            return $estrategia;
        }

        // ✅ Prioridad 2: Buscar por tipo de máquina
        if (isset($this->estrategiasSubetiquetas[$maquina->tipo])) {
            $estrategia = $this->estrategiasSubetiquetas[$maquina->tipo];
            Log::channel('planilla_import')->debug("      🎯 Máquina {$maquina->codigo} (tipo: {$maquina->tipo}) → estrategia por TIPO: {$estrategia}");
            return $estrategia;
        }

        // ✅ Prioridad 3: Usar estrategia por defecto
        $estrategiaDefault = config('planillas.importacion.estrategia_subetiquetas_default', 'legacy');
        Log::channel('planilla_import')->debug("      ⚙️ Máquina {$maquina->codigo} (código: {$maquina->codigo}, tipo: {$maquina->tipo}) → estrategia DEFAULT: {$estrategiaDefault}");

        return $estrategiaDefault;
    }

    // Los demás métodos permanecen igual (resolverClienteYObra, calcularPesoTotal, crearPlanilla, etc.)
    // Por brevedad, no los repito aquí ya que no cambian

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

    protected function agregarElementos(array $filas, string $codigoPlanilla, array &$advertencias): array
    {
        $agregados = [];

        foreach ($filas as $fila) {
            if (!array_filter($fila)) {
                continue;
            }

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

            $peso = $this->normalizarNumerico($fila[34] ?? null, 'peso', $excelRow, $codigoPlanilla, $advertencias);
            $barras = $this->normalizarNumerico($fila[32] ?? null, 'barras', $excelRow, $codigoPlanilla, $advertencias);

            if ($peso === false || $barras === false) {
                continue;
            }

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

    protected function calcularTiempoFabricacion(int $barras, int $doblesBarra): float
    {
        if ($doblesBarra > 0) {
            return $barras * $doblesBarra * 1.5;
        }
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

        if ($campo === 'barras' && $num < 0) {
            $advertencias[] = "Fila omitida (planilla {$codigoPlanilla}, Excel {$excelRow}): {$campo} negativo ('{$valor}').";
            return false;
        }

        return $num;
    }

    protected function recalcularPesosEtiquetas(Etiqueta $padre): void
    {
        if (!Schema::hasColumn('etiquetas', 'peso')) {
            return;
        }

        $codigo = (string)$padre->codigo;

        $subs = Etiqueta::where('codigo', $codigo)
            ->whereNotNull('etiqueta_sub_id')
            ->pluck('etiqueta_sub_id');

        foreach ($subs as $subId) {
            $peso = (float)Elemento::where('etiqueta_sub_id', $subId)->sum('peso');
            Etiqueta::where('etiqueta_sub_id', $subId)->update(['peso' => $peso]);
        }

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

    protected function limpiarEtiquetasPadreHuerfanas(Planilla $planilla): int
    {
        $etiquetasPadre = Etiqueta::where('planilla_id', $planilla->id)
            ->whereNull('etiqueta_sub_id')
            ->get();

        if ($etiquetasPadre->isEmpty()) {
            return 0;
        }

        $eliminadas = 0;

        foreach ($etiquetasPadre as $padre) {
            $tieneElementos = Elemento::where('planilla_id', $planilla->id)
                ->where('etiqueta_id', $padre->id)
                ->exists();

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
