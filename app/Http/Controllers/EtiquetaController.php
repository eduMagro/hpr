<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Paquete;
use App\Models\OrdenPlanilla;
use App\Models\Etiqueta;
use App\Models\ProductoBase;
use App\Models\Ubicacion;
use App\Models\Movimiento;
use App\Models\AsignacionTurno;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Maquina;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

use Exception;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Services\CompletarLoteService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;


class EtiquetaController extends Controller
{
    private function aplicarFiltros($query, Request $request)
    {
        if ($request->filled('id') && is_numeric($request->id)) {
            $query->where('id', (int) $request->id);
        }

        if ($request->filled('codigo')) {
            $query->where('codigo', $request->codigo);
        }

        if ($request->has('etiqueta_sub_id') && $request->etiqueta_sub_id !== '') {
            $query->where('etiqueta_sub_id', 'like', '%' . $request->etiqueta_sub_id . '%');
        }

        if ($request->filled('paquete')) {
            // Buscar el paquete por su código
            $paquete = Paquete::where('codigo', $request->paquete)->first();

            if ($paquete) {
                $query->where('paquete_id', $paquete->id);
            } else {
                // Si no existe el paquete con ese código, que no devuelva resultados
                $query->whereRaw('1 = 0');
            }
        }


        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('codigo_planilla')) {
            $query->whereHas('planilla', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->codigo_planilla . '%');
            });
        }

        if ($request->filled('numero_etiqueta')) {
            $query->where('id', $request->numero_etiqueta);
        }

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        foreach (
            [
                'id' => 'ID',
                'codigo' => 'Código',
                'codigo_planilla' => 'Código Planilla',
                'paquete' => 'Paquete',
                'estado' => 'Estado',
                'numero_etiqueta' => 'Número de Etiqueta',
                'nombre' => 'Nombre',
                'etiqueta_sub_id' => 'Subetiqueta',
            ] as $campo => $etiqueta
        ) {
            if ($request->filled($campo)) {
                $filtros[] = $etiqueta . ': <strong>' . e($request->$campo) . '</strong>';
            }
        }

        if ($request->filled('sort')) {
            $direccion = $request->order === 'asc' ? 'ascendente' : 'descendente';
            $filtros[] = 'Ordenado por <strong>' . e($request->sort) . '</strong> en orden <strong>' . $direccion . '</strong>';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc'
                ? '▲' // flecha hacia arriba
                : '▼'; // flecha hacia abajo
        } else {
            $icon = '⇅'; // símbolo de orden genérico
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }
    private function aplicarOrdenamiento($query, Request $request)
    {
        $columnasPermitidas = [
            'id',
            'codigo',
            'codigo_planilla',
            'etiqueta',
            'etiqueta_sub_id',
            'paquete_id',
            'maquina',
            'maquina_2',
            'maquina3',
            'producto1',
            'producto2',
            'producto3',
            'figura',
            'peso',
            'diametro',
            'longitud',
            'estado',
            'created_at',
        ];

        $sort = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';
        }

        return $query->orderBy($sort, $order);
    }

    public function index(Request $request)
    {
        $query = Etiqueta::with([
            'planilla:id,codigo,obra_id,cliente_id,seccion',
            'paquete:id,codigo',
            'producto:id,codigo,nombre',
            'producto2:id,codigo,nombre',
            'soldador1:id,name,primer_apellido',
            'soldador2:id,name,primer_apellido',
            'ensamblador1:id,name,primer_apellido',
            'ensamblador2:id,name,primer_apellido',
        ])->whereNotNull('etiqueta_sub_id');

        // aplicar filtros y ordenamiento
        $query = $this->aplicarFiltros($query, $request);
        $query = $this->aplicarOrdenamiento($query, $request);

        // paginación
        $etiquetas = $query->paginate($request->input('per_page', 10))
            ->appends($request->except('page'));

        // 🔥 en lugar de otra query con get(), cargamos solo para la página actual
        $etiquetasJson = $etiquetas->load([
            'planilla.obra:id,obra',
            'planilla.cliente:id,empresa',
            'elementos:id,etiqueta_id,dimensiones,barras,diametro,peso',
        ])->keyBy('id');

        $filtrosActivos = $this->filtrosActivos($request);

        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'codigo' => $this->getOrdenamiento('codigo', 'Código'),
            'codigo_planilla' => $this->getOrdenamiento('codigo_planilla', 'Planilla'),
            'etiqueta' => $this->getOrdenamiento('etiqueta', 'Etiqueta'),
            'etiqueta_sub_id' => $this->getOrdenamiento('etiqueta_sub_id', 'Subetiqueta'),
            'paquete' => $this->getOrdenamiento('paquete_id', 'Paquete'),
            'maquina' => $this->getOrdenamiento('maquina', 'Máquina 1'),
            'maquina_2' => $this->getOrdenamiento('maquina_2', 'Máquina 2'),
            'maquina3' => $this->getOrdenamiento('maquina3', 'Máquina 3'),
            'producto1' => $this->getOrdenamiento('producto1', 'Materia Prima 1'),
            'producto2' => $this->getOrdenamiento('producto2', 'Materia Prima 2'),
            'producto3' => $this->getOrdenamiento('producto3', 'Materia Prima 3'),
            'figura' => $this->getOrdenamiento('figura', 'Figura'),
            'peso' => $this->getOrdenamiento('peso', 'Peso'),
            'diametro' => $this->getOrdenamiento('diametro', 'Diámetro'),
            'longitud' => $this->getOrdenamiento('longitud', 'Longitud'),
            'estado' => $this->getOrdenamiento('estado', 'Estado'),
        ];

        return view('etiquetas.index', compact('etiquetas', 'etiquetasJson', 'ordenables', 'filtrosActivos'));
    }

    public function calcularPatronCorte(Request $request, $etiqueta)
    {
        $etiqueta = Etiqueta::where('etiqueta_sub_id', $etiqueta)
            ->with('elementos')
            ->firstOrFail();

        $elemento = $etiqueta->elementos->first();
        if (!$elemento) {
            return response()->json([
                'message' => 'No hay elementos en la etiqueta.',
            ], 400);
        }

        $diametro = $request->input('diametro', $elemento->diametro);
        $longitudesDisponibles = ProductoBase::query()
            ->where('diametro', $diametro)
            ->where('tipo', 'barra')
            ->distinct()
            ->pluck('longitud') // ya viene en metros
            ->unique()
            ->sort()
            ->values();


        if (empty($longitudesDisponibles)) {
            return response()->json([
                'message' => "No hay longitudes disponibles para Ø{$diametro} mm",
            ], 400);
        }

        $longitudElementoM = $elemento->longitud / 100;
        if ($longitudElementoM <= 0) {
            return response()->json([
                'message' => 'Longitud del elemento no válida.',
            ], 400);
        }

        $patrones = [];

        foreach ($longitudesDisponibles as $longitudM) {
            $porBarra = floor($longitudM / $longitudElementoM);
            $sobraCm  = round(($longitudM - ($porBarra * $longitudElementoM)) * 100, 2);
            $aprovechamiento = $porBarra > 0
                ? round(100 * ($porBarra * $longitudElementoM) / $longitudM, 2)
                : 0;

            $patron = $porBarra > 0
                ? implode(' + ', array_fill(0, $porBarra, number_format($elemento->longitud, 2))) . " = {$porBarra} piezas"
                : "No caben piezas";

            $patrones[] = [
                'longitud_m'      => $longitudM,
                'longitud_cm'     => $longitudM * 100,
                'por_barra'       => $porBarra,
                'sobra_cm'        => $sobraCm,
                'aprovechamiento' => $aprovechamiento,
                'patron'          => $patron,
            ];
        }

        // Generar HTML si lo necesitas para un SweetAlert o modal
        $html = "<ul class='text-left space-y-4'>";
        foreach ($patrones as $p) {
            $color = $p['aprovechamiento'] >= 98 ? 'text-green-600'
                : ($p['aprovechamiento'] >= 90 ? 'text-yellow-500' : 'text-red-600');

            $html .= "<li class='leading-snug'>";
            $html .= "<div class='font-bold text-sm text-gray-800'>Elemento {$elemento->longitud} cm</div>";
            $html .= "<div>📏 <strong>{$p['longitud_m']} m</strong></div>";
            $html .= "<div>🧩 <span class='font-semibold text-gray-700'>Patrón:</span> {$p['patron']}</div>";
            $html .= "<div>🪵 <span class='font-semibold text-gray-700'>Sobra:</span> {$p['sobra_cm']} cm</div>";
            $html .= "<div>📈 <span class='font-semibold {$color}'>Aprovechamiento:</span> ";
            $html .= "<span class='{$color} font-bold'>{$p['aprovechamiento']}%</span></div>";
            $html .= "</li>";
        }
        $html .= "</ul>";

        return response()->json([
            'success'  => true,
            'patrones' => $patrones,
            'html'     => $html,
        ]);
    }
    /**
     * Devuelve un array de IDs de planilla empezando por la actual
     * y continuando con las siguientes en la cola de la MISMA máquina,
     * según la tabla orden_planillas (posicion ASC).
     */
    private function obtenerPlanillasMismaMaquinaEnOrden(Etiqueta $etiquetaA): array
    {
        $planillaActual = $etiquetaA->planilla;
        if (!$planillaActual) {
            return [$etiquetaA->planilla_id]; // fallback
        }

        // 1) Averiguar la máquina de esta planilla consultando orden_planillas
        $filaActual = OrdenPlanilla::where('planilla_id', $planillaActual->id)->first();
        if (!$filaActual) {
            // Si no hay fila en orden_planillas, devolvemos solo la actual
            return [$planillaActual->id];
        }

        $maquinaId = (int) $filaActual->maquina_id;
        $posActual = (int) $filaActual->posicion;

        // 2) Traer TODA la cola de esa máquina, ordenada
        $todaCola = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->orderBy('posicion', 'asc')
            ->pluck('planilla_id')
            ->toArray();

        // 3) Reordena la lista poniendo primero la actual y luego las siguientes
        // (sin repetir), manteniendo el orden de 'posicion'
        $lista = [];
        $ya = [];
        // primero la actual (si está)
        if (!in_array($planillaActual->id, $lista, true)) {
            $lista[] = $planillaActual->id;
            $ya[$planillaActual->id] = true;
        }
        // luego las que vengan después de la actual
        foreach ($todaCola as $pid) {
            if (isset($ya[$pid])) continue;
            $lista[] = $pid;
            $ya[$pid] = true;
        }
        // por si hubiera alguna anterior que no queremos perder (opcional)
        // foreach ($todaCola as $pid) { if (!isset($ya[$pid])) { $lista[] = $pid; $ya[$pid]=true; } }

        return $lista;
    }
    /**
     * Candidatos de UNA planilla dada: mismo Ø, estado pendiente y stock.
     * Devuelve array de ['id' => subid, 'L' => longitud_cm, 'disponibles' => barras]
     */
    private function construirCandidatosEnPlanilla(int $planillaId, int $diametroMm, string $excluirSubId): array
    {
        $planilla = Planilla::with('etiquetas.elementos')->find($planillaId);
        if (!$planilla) return [];

        $candidatos = [];
        foreach ($planilla->etiquetas as $otra) {
            $subId = $otra->etiqueta_sub_id;
            if ($subId === $excluirSubId) continue;

            $estado = strtolower(trim((string) ($otra->estado ?? '')));
            if ($estado !== 'pendiente') continue;

            $e = $otra->elementos->first();
            if (!$e) continue;
            if ((int) ($e->diametro ?? 0) !== $diametroMm) continue;

            $longitudCm  = (float) ($e->longitud ?? 0);       // 👈 recuerda: en tu BD longitud viene en cm para elementos
            $disponibles = (int)   max(0, (int) ($e->barras ?? 0));
            if ($longitudCm <= 0 || $disponibles <= 0) continue;

            $candidatos[] = [
                'id'          => $subId,
                'L'           => $longitudCm,
                'disponibles' => $disponibles,
            ];
        }

        return $candidatos;
    }
    public function optimizarCorte(Request $request, string $etiquetaSubId)
    {
        /* ─────────────────────────────
     |  0) Parámetros / constantes
     ───────────────────────────── */
        $Kmax                   = (int) ($request->input('kmax') ?? 5);   // cortes máx por barra (incluye A)
        $EPS                    = 0.01;                                   // tolerancia numérica
        $UMBRAL_OK              = 99.0;                                   // % objetivo
        $permitirRepeticiones   = true;                                   // permitir repetir (incluido A) para k>=3
        $kMinimo                = 2;                                      // empezamos por parejas (A+B)

        /* ─────────────────────────────
     |  1) Cargar contexto + validar
     ───────────────────────────── */
        $etiquetaA = Etiqueta::with(['elementos', 'planilla.etiquetas.elementos'])->where('etiqueta_sub_id', $etiquetaSubId)->firstOrFail();

        $elementoA = $etiquetaA->elementos->first();
        if (!$elementoA) {
            return response()->json(['success' => false, 'message' => 'La subetiqueta A no tiene elementos.'], 400);
        }

        $longitudAcm = (float) ($elementoA->longitud ?? 0);   // cm
        $diametro    = (int)   ($elementoA->diametro ?? 0);
        $barrasA     = (int)   max(1, ($elementoA->barras ?? 1)); // piezas pendientes (A consume 1 fijo)
        if ($longitudAcm <= 0 || $diametro <= 0) {
            return response()->json(['success' => false, 'message' => 'Datos de A inválidos (longitud/diámetro).'], 400);
        }

        /* 2) Construir lista de planillas a explorar: actual + siguientes de la MISMA máquina */
        $planillasEnOrden = $this->obtenerPlanillasMismaMaquinaEnOrden($etiquetaA);

        /* 3) Longitudes de producto base (orden asc, en cm; ProductoBase.longitud viene en METROS) */
        $longitudesBarraCm = $this->recogerLongitudesProductosBaseEnCm($diametro);
        if (empty($longitudesBarraCm)) {
            return response()->json(['success' => false, 'message' => 'No hay longitudes de barra disponibles en productos base para este diámetro.'], 400);
        }

        /* 4) Preparativos comunes */
        $repeticionesA = $permitirRepeticiones ? max(0, $barrasA - 1) : 0;

        $comparador = function (array $x, array $y) use ($EPS) {
            if ($x['aprovechamiento'] > $y['aprovechamiento'] + $EPS) return -1;
            if ($y['aprovechamiento'] > $x['aprovechamiento'] + $EPS) return 1;
            if ($x['sobra_cm'] + $EPS < $y['sobra_cm']) return -1;
            if ($y['sobra_cm'] + $EPS < $x['sobra_cm']) return 1;
            if (($x['max_long_cm'] ?? 0) > ($y['max_long_cm'] ?? 0) + $EPS) return -1;
            if (($y['max_long_cm'] ?? 0) > ($x['max_long_cm'] ?? 0) + $EPS) return 1;
            return strcmp($x['clave_estable'], $y['clave_estable']);
        };

        $topGlobal98           = [];
        $combinacionesYaVistas = [];
        $progresoPorLongitud   = []; // guardará bloques por planilla y por longitud (diagnóstico opcional)

        /* 5) Iterar planillas mientras falten patrones para el Top 3 */
        foreach ($planillasEnOrden as $planillaId) {
            if (count($topGlobal98) >= 3) break; // ya están los 3 mejores ≥98

            // 5.1) Candidatos SOLO de esta planilla
            $candidatos = $this->construirCandidatosEnPlanilla($planillaId, $diametro, $etiquetaSubId);

            // Si no hay candidatos en esta planilla, pasa a la siguiente
            if (empty($candidatos)) continue;

            // 5.2) Pre-carga de etiquetas para ESTA planilla (y A) → evita N+1
            $subIdsNecesarios = collect($candidatos)->pluck('id')->push($etiquetaSubId)->unique()->values();
            $mapaEtiquetas    = Etiqueta::with(['elementos', 'planilla'])
                ->whereIn('etiqueta_sub_id', $subIdsNecesarios)
                ->get()
                ->keyBy('etiqueta_sub_id');

            // 5.3) Ordenar candidatos por L desc (poda más efectiva)
            usort($candidatos, fn($a, $b) => $b['L'] <=> $a['L']);

            // 5.4) Explorar por longitudes (de menor a mayor)
            foreach ($longitudesBarraCm as $longitudBarraCmActual) {
                if (count($topGlobal98) >= 3) break;

                $topLocal98    = [];
                $mejorLocal    = null;
                $kMaxExplorado = $kMinimo;

                // k=2: SOLO A+B (B≠A)
                [$encontradosLocal98, $mejorLocal] = $this->explorarParejasAB(
                    $etiquetaSubId,
                    $longitudAcm,
                    $longitudBarraCmActual,
                    $candidatos,
                    $UMBRAL_OK,
                    $comparador
                );
                $this->acumularPatrones($encontradosLocal98, $topLocal98, $topGlobal98, $combinacionesYaVistas, $comparador);

                // k≥3 si aún falta para completar Top 3 global
                if (count($topGlobal98) < 3) {
                    for ($k = 3; $k <= $Kmax && count($topGlobal98) < 3; $k++) {
                        $encontrados = $this->explorarK(
                            $k,
                            $etiquetaSubId,
                            $longitudAcm,
                            $longitudBarraCmActual,
                            $candidatos,
                            $repeticionesA,
                            $UMBRAL_OK,
                            $comparador
                        );

                        $this->acumularPatrones($encontrados, $topLocal98, $topGlobal98, $combinacionesYaVistas, $comparador);

                        if (!$mejorLocal) {
                            $mejorLocal = $this->mejorPatron($encontrados, $comparador);
                        } else {
                            $mejorK = $this->mejorPatron($encontrados, $comparador);
                            if ($mejorK && $comparador($mejorK, $mejorLocal) < 0) {
                                $mejorLocal = $mejorK;
                            }
                        }

                        $kMaxExplorado = $k;
                    }
                }

                // (opcional) diagnóstico con etiqueta de planilla
                $progresoPorLongitud[] = [
                    'planilla_id'       => $planillaId,
                    'longitud_barra_cm' => (int) $longitudBarraCmActual,
                    'top_local_98'      => array_slice($topLocal98, 0, 3),
                    'mejor_local'       => $mejorLocal,
                    'k_max_explorado'   => $kMaxExplorado,
                ];
            }

            // 5.5) Si después de esta planilla ya tenemos Top 3, salimos del bucle
            if (count($topGlobal98) >= 3) break;
        }

        /* 6) Ordenar y completar grupos para canvas como antes */
        usort($topGlobal98, $comparador);
        $topGlobal98 = array_slice($topGlobal98, 0, 3);

        // Para cada patrón del top, construir grupos con el mapa de SU planilla cargado en su iteración.
        // Sencillo: si quieres tener los grupos siempre, puedes reconstruir usando un mapa general:
        if (!empty($topGlobal98)) {
            // Prepara un mapa global con todos los subids implicados en el top
            $todosSubIdsTop = collect($topGlobal98)->flatMap(fn($p) => array_keys($p['conteo_por_subid']))->unique()->values();
            $mapaGlobal = Etiqueta::with(['elementos', 'planilla'])
                ->whereIn('etiqueta_sub_id', $todosSubIdsTop)
                ->get()
                ->keyBy('etiqueta_sub_id');

            foreach ($topGlobal98 as &$pat) {
                $pat['grupos'] = $this->construirGruposParaCanvas($pat['conteo_por_subid'], $mapaGlobal);
            }
            unset($pat);
        }

        $htmlResumen = $this->construirHtmlResumenMultiLongitudes($longitudesBarraCm, $progresoPorLongitud);

        /* ─────────────────────────────
     |  7) Responder
     ───────────────────────────── */
        return response()->json([
            'success'                 => true,
            'longitudes_barra_cm'     => array_values($longitudesBarraCm),
            'top_global'              => $topGlobal98,
            'progreso_por_longitud'   => $progresoPorLongitud,
            'kmax'                    => $Kmax,
            'umbral_ok'               => $UMBRAL_OK,
            'permitio_repeticion'     => $permitirRepeticiones,
            'html_resumen'            => $htmlResumen,
            // por consistencia con tu front previo:
            'longitud_barra_m'        => null, // ahora probamos múltiples longitudes (si quieres, puedes repetir por cada top)
            'longitud_barra_cm'       => null,
        ]);
    }

    /* ============================================================
   =              HELPERS PRIVADOS / UTILIDADES               =
   ============================================================ */

    /**
     * Construye candidatos (subetiquetas) con mismo Ø, estado pendiente y stock.
     * Devuelve array de ['id' => subid, 'L' => longitud_cm, 'disponibles' => barras]
     */
    private function construirCandidatosMismoDiametro(Etiqueta $etiquetaA, int $diametroMm, string $etiquetaSubIdA): array
    {
        $candidatos = [];

        foreach ($etiquetaA->planilla->etiquetas as $otra) {
            $subId = $otra->etiqueta_sub_id;
            if ($subId === $etiquetaSubIdA) continue;

            $estado = strtolower(trim((string) ($otra->estado ?? '')));
            if ($estado !== 'pendiente') continue;

            $e = $otra->elementos->first();
            if (!$e) continue;
            if ((int) ($e->diametro ?? 0) !== $diametroMm) continue;

            $longitudCm  = (float) ($e->longitud ?? 0);
            $disponibles = (int)   max(0, (int) ($e->barras ?? 0));
            if ($longitudCm <= 0 || $disponibles <= 0) continue;

            $candidatos[] = [
                'id'          => $subId,
                'L'           => $longitudCm,
                'disponibles' => $disponibles,
            ];
        }

        return $candidatos;
    }

    /**
     * Recoge longitudes de productos base **en cm**, deduplicadas y orden asc.
     * Tu tabla tiene el campo `longitud` EN METROS (float/decimal).
     */
    private function recogerLongitudesProductosBaseEnCm(int $diametroMm): array
    {
        // Si NO tienes columna `activo`, elimina ese where.
        $productos = ProductoBase::query()
            ->where('diametro', $diametroMm)
            ->pluck('longitud')        // longitud en METROS
            ->filter(fn($m) => is_numeric($m) && (float)$m > 0)
            ->map(function ($m) {
                // Convertimos a cm y normalizamos a entero (evita flotantes raros)
                $cm = (float)$m * 100.0;
                return (int) round($cm);
            })
            ->unique()
            ->sort()                   // ascendente
            ->values()
            ->all();

        return $productos; // array<int cm>
    }


    /**
     * Explora parejas A+B (B≠A) para una longitud concreta.
     * Retorna [patrones_98, mejor_patron_encontrado (aunque <98 o null)]
     */
    private function explorarParejasAB(
        string $subIdA,
        float $longitudAcm,
        int $longitudBarraCm,
        array $candidatos,
        float $UMBRAL_OK,
        callable $comparador
    ): array {
        $patrones98   = [];
        $mejorLocal   = null;

        foreach ($candidatos as $cand) {
            $suma = $longitudAcm + $cand['L'];
            if ($suma > $longitudBarraCm) continue;

            $aprov = round(($suma / $longitudBarraCm) * 100, 2);
            $sobra = round($longitudBarraCm - $suma, 2);

            $patron = [
                'longitud_barra_cm'  => (int) $longitudBarraCm,
                'k'                  => 2,
                'etiquetas'          => [$subIdA, $cand['id']],
                'conteo_por_subid'   => $this->contarMultiset([$subIdA, $cand['id']]),
                'longitudes_cm'      => [$longitudAcm, $cand['L']],
                'total_cm'           => $suma,
                'sobra_cm'           => $sobra,
                'aprovechamiento'    => $aprov,
                'max_long_cm'        => max($longitudAcm, $cand['L']),
                'patron_humano'      => number_format($longitudAcm, 2, ',', '.') . ' + ' . number_format($cand['L'], 2, ',', '.') . ' = ' . number_format($suma, 2, ',', '.'),
                'tipo_schema'        => 'A+B',
                'clave_estable'      => $this->claveCombinacion([$subIdA, $cand['id']]),
            ];

            if ($aprov >= $UMBRAL_OK) {
                $patrones98[] = $patron;
            }

            if (!$mejorLocal || $comparador($patron, $mejorLocal) < 0) {
                $mejorLocal = $patron;
            }
        }

        // ordenar los >=98 y recortar a 3
        usort($patrones98, $comparador);
        $patrones98 = array_slice($patrones98, 0, 3);

        return [$patrones98, $mejorLocal];
    }

    /**
     * Explora combinaciones de tamaño k (k>=3) permitiendo repeticiones por stock.
     * Devuelve lista de patrones con % ≥ UMBRAL_OK.
     */
    private function explorarK(
        int $kObjetivo,
        string $subIdA,
        float $longitudAcm,
        int $longitudBarraCm,
        array $candidatos,
        int $repeticionesA,
        float $UMBRAL_OK,
        callable $comparador
    ): array {
        $resultados = [];

        // mapa stock por subid (para candidatos)
        $stock = [];
        foreach ($candidatos as $c) {
            $stock[$c['id']] = $c['disponibles'];
        }

        // vector longitudes por subid
        $LporSub = [];
        foreach ($candidatos as $c) {
            $LporSub[$c['id']] = $c['L'];
        }
        $LporSub[$subIdA] = $longitudAcm;

        $seleccion = [];               // lista de subids (sin A fijo)
        $usos      = [];               // subid => usados
        $sumaSel   = 0.0;

        // DFS: debemos elegir (kObjetivo-1) acompañantes, pudiendo usar A hasta repeticionesA
        $subidsOrdenados = array_keys($LporSub);
        // orden descendente por longitud para podar antes
        usort($subidsOrdenados, fn($x, $y) => ($LporSub[$y] <=> $LporSub[$x]));

        $dfs = function () use (
            &$dfs,
            $kObjetivo,
            $subIdA,
            $longitudAcm,
            $longitudBarraCm,
            &$seleccion,
            &$usos,
            &$sumaSel,
            $LporSub,
            $stock,
            $repeticionesA,
            $UMBRAL_OK,
            &$resultados,
            $subidsOrdenados
        ) {
            $kActual = 1 + count($seleccion); // +1 por A fijo
            $sumaActual = $longitudAcm + $sumaSel;
            if ($sumaActual > $longitudBarraCm) {
                return; // poda por suma
            }

            if ($kActual === $kObjetivo) {
                // construir patrón final: [A] + seleccion
                $ids = array_merge([$subIdA], $seleccion);
                $total = $sumaActual;
                $aprov = round(($total / $longitudBarraCm) * 100, 2);
                $sobra = round($longitudBarraCm - $total, 2);

                if ($aprov >= $UMBRAL_OK) {
                    $longitudes = array_map(fn($sid) => $LporSub[$sid], $ids);
                    $resultados[] = [
                        'longitud_barra_cm'  => (int) $longitudBarraCm,
                        'k'                  => $kObjetivo,
                        'etiquetas'          => $ids,
                        'conteo_por_subid'   => $this->contarMultiset($ids),
                        'longitudes_cm'      => $longitudes,
                        'total_cm'           => $total,
                        'sobra_cm'           => $sobra,
                        'aprovechamiento'    => $aprov,
                        'max_long_cm'        => max($longitudes),
                        'patron_humano'      => implode(' + ', array_map(fn($x) => number_format($x, 2, ',', '.'), $longitudes)) . ' = ' . number_format($total, 2, ',', '.'),
                        'tipo_schema'        => $this->schemaDesdeIds($ids, $subIdA),
                        'clave_estable'      => $this->claveCombinacion($ids),
                    ];
                }
                return;
            }

            foreach ($subidsOrdenados as $sid) {
                // reglas de stock:
                if ($sid === $subIdA) {
                    $usadosA = $usos[$sid] ?? 0;
                    if ($usadosA >= $repeticionesA) continue; // A solo extra hasta repeticionesA
                } else {
                    $usados = $usos[$sid] ?? 0;
                    $disp = $stock[$sid] ?? 0;
                    if ($usados >= $disp) continue;
                }

                // elegir
                $seleccion[]   = $sid;
                $usos[$sid]    = ($usos[$sid] ?? 0) + 1;
                $sumaSel      += $LporSub[$sid];

                // poda rápida por suma
                if ($longitudAcm + $sumaSel <= $longitudBarraCm) {
                    $dfs();
                }

                // deshacer
                array_pop($seleccion);
                $usos[$sid] -= 1;
                if ($usos[$sid] <= 0) unset($usos[$sid]);
                $sumaSel -= $LporSub[$sid];
            }
        };

        $dfs();

        // ordenar y devolver (sin recorte aquí; se recorta al acumular)
        usort($resultados, $comparador);

        // deduplicación aquí no es estrictamente necesaria; se hace al acumular en top global/local
        return $resultados;
    }

    /**
     * Inserta patrones en TopLocal y TopGlobal con deduplicación por combinación multiset.
     */
    private function acumularPatrones(array $encontrados, array &$topLocal98, array &$topGlobal98, array &$combinacionesYaVistas, callable $comparador): void
    {
        foreach ($encontrados as $p) {
            $clave = $p['clave_estable']; // ids ordenados, con repeticiones
            if (isset($combinacionesYaVistas[$clave])) continue;
            $combinacionesYaVistas[$clave] = true;

            $topLocal98[]  = $p;
            $topGlobal98[] = $p;
        }

        // ordenar y recortar a 3 en ambos
        usort($topLocal98, $comparador);
        $topLocal98  = array_slice($topLocal98, 0, 3);

        usort($topGlobal98, $comparador);
        $topGlobal98 = array_slice($topGlobal98, 0, 3);
    }

    /** Devuelve el mejor patrón de una lista según comparador (o null). */
    private function mejorPatron(array $lista, callable $comparador): ?array
    {
        if (empty($lista)) return null;
        $mejor = $lista[0];
        foreach ($lista as $p) {
            if ($comparador($p, $mejor) < 0) $mejor = $p;
        }
        return $mejor;
    }

    /** Cuenta multiplicidades de subids en una combinación (multiset). */
    private function contarMultiset(array $ids): array
    {
        $conteo = [];
        foreach ($ids as $id) {
            $conteo[$id] = ($conteo[$id] ?? 0) + 1;
        }
        ksort($conteo);
        return $conteo;
    }

    /** Clave estable de combinación: ids ordenados con repeticiones, unidos por '|'. */
    private function claveCombinacion(array $ids): string
    {
        sort($ids, SORT_NATURAL);
        return implode('|', $ids);
    }

    /** Deducción de schema tipo "A+B", "A+A+B", "A+B+B+B", etc. */
    private function schemaDesdeIds(array $ids, string $subIdA): string
    {
        $conteo = $this->contarMultiset($ids);
        $partes = [];
        foreach ($conteo as $sid => $n) {
            $letra = ($sid === $subIdA) ? 'A' : 'B';
            // si quieres distinguir C,D por subetiqueta diferente, puedes extenderlo
            if ($letra === 'B' && $n > 1) $partes[] = str_repeat('B', 1) . str_repeat('+B', $n - 1);
            else $partes[] = $letra . ($n > 1 ? str_repeat('+' . $letra, $n - 1) : '');
        }
        // Orden canónico: A primero, luego resto
        usort($partes, function ($x, $y) {
            if (str_starts_with($x, 'A') && !str_starts_with($y, 'A')) return -1;
            if (!str_starts_with($x, 'A') && str_starts_with($y, 'A')) return 1;
            return strcmp($x, $y);
        });
        return implode('+', $partes);
    }

    /** Mapea a la estructura esperada por el canvas, SIN N+1. */
    private function construirGruposParaCanvas(array $conteoPorSubid, Collection $mapaEtiquetas): array
    {
        $grupos = [];
        foreach ($conteoPorSubid as $subId => $veces) {
            $etq = $mapaEtiquetas->get($subId);
            if (!$etq) continue;

            $grupos[] = [
                'etiqueta' => [
                    'id'              => $etq->id,
                    'etiqueta_sub_id' => $etq->etiqueta_sub_id,
                ],
                'elementos' => $etq->elementos->map(function ($el) {
                    return [
                        'id'          => $el->id,
                        'codigo'      => $el->codigo,
                        'barras'      => (int) ($el->barras ?? 0),
                        'diametro'    => (int) ($el->diametro ?? 0),
                        'dimensiones' => (string) ($el->dimensiones ?? ''),
                        'peso'        => (float) ($el->peso ?? 0),
                        'longitud'    => (float) ($el->longitud ?? 0),
                    ];
                })->values(),
            ];
        }
        return $grupos;
    }

    /** HTML de resumen por longitud con top local (≥98) y mejor local si aplica. */
    private function construirHtmlResumenMultiLongitudes(array $longitudesBarraCm, array $progresoPorLongitud): string
    {
        $html = "<div class='space-y-4'>";
        foreach ($progresoPorLongitud as $bloque) {
            $Lcm = (int) $bloque['longitud_barra_cm'];
            $html .= "<div class='p-3 border rounded-md'>";
            $html .= "<div class='text-sm text-gray-700 mb-2'>Barra: <strong>" . number_format($Lcm, 0, ',', '.') . " cm</strong></div>";

            $topLocal = $bloque['top_local_98'] ?? [];
            if (empty($topLocal)) {
                $html .= "<div class='text-xs text-gray-500'>Sin patrones ≥ 98% para esta longitud.</div>";
            } else {
                $html .= "<div class='font-semibold text-sm mb-1'>Top (≥98%)</div><ul class='space-y-1'>";
                foreach ($topLocal as $p) {
                    $cls = $p['aprovechamiento'] >= 98 ? 'text-green-600' : ($p['aprovechamiento'] >= 90 ? 'text-yellow-500' : 'text-red-600');
                    $html .= "<li class='text-sm leading-snug'>
                    <div>🔹 Patrón: <strong>{$p['patron_humano']} cm</strong></div>
                    <div>🪵 Sobra: <strong>" . number_format($p['sobra_cm'], 2, ',', '.') . " cm</strong></div>
                    <div>📈 Aprovechamiento: <span class='font-bold {$cls}'>" . number_format($p['aprovechamiento'], 2, ',', '.') . "%</span></div>
                    <div class='text-[11px] text-gray-500'>k={$p['k']}, esquema: {$p['tipo_schema']}</div>
                </li>";
                }
                $html .= "</ul>";
            }

            if (!empty($bloque['mejor_local'])) {
                $p = $bloque['mejor_local'];
                $cls = $p['aprovechamiento'] >= 98 ? 'text-green-600' : ($p['aprovechamiento'] >= 90 ? 'text-yellow-500' : 'text-red-600');
                $html .= "<div class='mt-2 text-xs text-gray-600'>Mejor local (diagnóstico): ";
                $html .= "<span class='font-semibold'>{$p['patron_humano']} cm</span> — sobra <strong>" . number_format($p['sobra_cm'], 2, ',', '.') . " cm</strong>, ";
                $html .= "aprov <span class='font-bold {$cls}'>" . number_format($p['aprovechamiento'], 2, ',', '.') . "%</span>, k={$p['k']}</div>";
            }

            $html .= "</div>";
        }
        $html .= "</div>";
        return $html;
    }

    // public function render(Request $request)
    // {
    //     $etiqueta = \App\Models\Etiqueta::with(['planilla', 'elementos']) // 👈 añadimos relación elementos
    //         ->findOrFail($request->id);

    //     $maquinaTipo = $request->maquina_tipo ?? 'barra';

    //     // devolvemos el HTML del componente blade
    //     $html = view('components.etiqueta.etiqueta', [
    //         'etiqueta' => $etiqueta,
    //         'planilla' => $etiqueta->planilla,
    //         'maquinaTipo' => $maquinaTipo,
    //     ])->render();

    //     // 👇 devolvemos también los elementos (en array plano)
    //     return response()->json([
    //         'html' => $html,
    //         'elementos' => $etiqueta->elementos->toArray(), // todos los datos que necesitas en JS
    //     ]);
    // }

    public function fabricacionOptimizada(Request $request)
    {
        try {
            $data = $request->validate([
                'producto_base.longitud_barra_cm' => ['required', 'numeric', 'min:1'],
                'repeticiones' => ['required', 'integer', 'min:1'],
                'etiquetas' => ['required', 'array', 'min:1'],
                'etiquetas.*.etiqueta_sub_id' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        }

        $longitud = (int) $data['producto_base']['longitud_barra_cm'];
        $userId = Auth::id();
        $compaId = auth()->user()->compañeroDeTurno()?->id;
        $resultados = [];

        DB::beginTransaction();
        try {
            $fabrica = app(\App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio::class);

            foreach ($data['etiquetas'] as $item) {
                $subId = $item['etiqueta_sub_id'];

                $maquinaId = Elemento::where('etiqueta_sub_id', $subId)->value('maquina_id');
                if (!$maquinaId) throw new \RuntimeException("Sin máquina para {$subId}");

                $maquina = Maquina::findOrFail($maquinaId);

                $dto = new \App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos(
                    etiquetaSubId: $subId,
                    maquinaId: $maquinaId,
                    longitudSeleccionada: $longitud,
                    operario1Id: $userId,
                    operario2Id: $compaId,
                    opciones: ['origen' => 'optimizada']
                );

                $resultado = $fabrica->porMaquina($maquina)->actualizar($dto);

                $resultados[] = [
                    'etiqueta_sub_id' => $subId,
                    'estado' => $resultado->etiqueta->estado ?? null,
                    'warnings' => $resultado->warnings ?? [],
                ];
            }

            DB::commit();
            return response()->json(['success' => true, 'resultados' => $resultados]);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            DB::rollBack();
            return $e->getResponse(); // devolvemos la response real de Laravel
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en fabricacionOptimizada', [
                'error' => $e->getMessage(),
                'payload' => $data,
                'user' => $userId
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function actualizarEtiqueta(Request $request, $id, $maquina_id)
    {
        // Delegación a servicios (nuevo flujo)
        try {

            $maquina = Maquina::findOrFail($maquina_id);

            $rules = [];

            if ($maquina->tipo_material === 'barra') {
                $rules['longitudSeleccionada'] = ['required', 'integer', 'min:1'];
            }

            $request->validate($rules);

            $dto = new \App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos(
                etiquetaSubId: $id,
                maquinaId: (int) $maquina_id,
                longitudSeleccionada: (int) $request->input('longitudSeleccionada'),
                operario1Id: Auth::id(),
                operario2Id: auth()->user()->compañeroDeTurno()?->id,
                opciones: []
            );

            log::info("Delegando actualización de etiqueta {$dto->etiquetaSubId} a servicio para máquina {$maquina->id} ({$maquina->tipo}, operario1Id={$dto->operario1Id}, operario2Id={$dto->operario2Id})");
            /** @var \App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio $fabrica */
            $fabrica = app(\App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio::class);
            $servicio = $fabrica->porMaquina($maquina);

            $resultado = $servicio->actualizar($dto);
            $etiqueta = $resultado->etiqueta;

            return response()->json([
                'success' => true,
                'estado' => $etiqueta->estado,
                'productos_afectados' => $resultado->productosAfectados,
                'warnings' => $resultado->warnings,
                'fecha_inicio' => optional($etiqueta->fecha_inicio)->format('d-m-Y H:i:s'),
                'fecha_finalizacion' => optional($etiqueta->fecha_finalizacion)->format('d-m-Y H:i:s'),
            ], 200);
        } catch (HttpResponseException $e) {
            // ⚡️ devolvemos la response que ya trae el servicio
            return $e->getResponse();
        } catch (\Throwable $e) {
            // cualquier otra excepción sí la tratamos aquí
            try {
                $servicioClass = isset($servicio) ? get_class($servicio) : null;
                $maquinaLocal = isset($maquina) ? $maquina : Maquina::find($maquina_id);
                $etq = Etiqueta::where('etiqueta_sub_id', (int) $id)->first();

                Log::error('Error en actualizarEtiqueta (delegado a servicio)', [
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'etiqueta_sub_id' => (int) $id,
                    'etiqueta_id' => optional($etq)->id,
                    'etiqueta_estado_actual' => optional($etq)->estado,
                    'planilla_id' => optional($etq)->planilla_id,
                    'maquina_id' => (int) $maquina_id,
                    'maquina_tipo' => optional($maquinaLocal)->tipo,
                    'servicio' => $servicioClass,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'request_longitud' => $request->input('longitud'),
                ]);
            } catch (\Throwable $logEx) {
                Log::error('Fallo al registrar contexto de error en actualizarEtiqueta', [
                    'error_original' => $e->getMessage(),
                    'error_log' => $logEx->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error inesperado',
                'etiqueta_sub_id' => (int) $id,
            ], 400);
        }

        // Flujo legado (no alcanzado tras 'return'); se mantiene temporalmente por compatibilidad
        DB::beginTransaction();
        try {
            $warnings = []; // Array para acumular mensajes de alerta
            // Array para almacenar los productos consumidos y su stock actualizado
            $productosAfectados = [];
            $longitudSeleccionada = $request->input('longitud');

            // Obtener la etiqueta y su planilla asociada
            $etiqueta = Etiqueta::with('elementos.planilla')->where('etiqueta_sub_id', $id)->firstOrFail();
            $planilla_id = $etiqueta->planilla_id;
            $planilla = Planilla::find($planilla_id);

            $operario1 = Auth::id();
            $operario2 = auth()->user()->compañeroDeTurno()?->id;

            // Convertir el campo ensamblado a minúsculas para facilitar comparaciones
            $ensambladoText = strtolower($planilla->ensamblado);
            // Se obtiene la máquina actual (por ejemplo, de tipo ensambladora o soldadora según corresponda)
            $maquina = Maquina::findOrFail($maquina_id);
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($query) use ($maquina_id) {
                    $query->where('maquina_id', $maquina_id)
                        ->orWhere('maquina_id_2', $maquina_id);
                })
                ->get();
            // Suma total de los pesos de los elementos en la máquina
            $pesoTotalMaquina = $elementosEnMaquina->sum('peso');
            $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('estado', 'fabricado')->count();
            // Número total de elementos asociados a la etiqueta
            $numeroElementosTotalesEnEtiqueta = $etiqueta->elementos()->count();
            // Verificar si la etiqueta está repartida en diferentes máquinas
            $enOtrasMaquinas = $etiqueta->elementos()
                ->where('maquina_id', '!=', $maquina_id)
                ->exists();
            // Buscar la ubicación que contenga el código de la máquina en su descripción
            $ubicacion = Ubicacion::where('descripcion', 'like', "%{$maquina->codigo}%")->first();
            if (!$ubicacion) {
                // ID de una ubicación por defecto (ajústalo según tu base de datos)
                $ubicacion = Ubicacion::find(33); // Cambia '1' por el ID de la ubicación predeterminada
            }
            // 1. Agrupar los elementos por diámetro sumando sus pesos
            $diametrosConPesos = [];
            foreach ($elementosEnMaquina as $elemento) {
                $diametro = $elemento->diametro;
                $peso = $elemento->peso;
                if (!isset($diametrosConPesos[$diametro])) {
                    $diametrosConPesos[$diametro] = 0;
                }
                $diametrosConPesos[$diametro] += $peso;
            }
            // Convertir los diámetros requeridos a enteros
            // 2) Diámetros requeridos (normalizados)
            $diametrosRequeridos = array_map('intval', array_keys($diametrosConPesos));
            Log::info("🔍 Diametros requeridos", $diametrosRequeridos);

            // Si por alguna razón no hay diametros (p.ej. diametro null en elementos), intenta derivarlos
            if (empty($diametrosRequeridos)) {
                $derivados = $elementosEnMaquina->pluck('diametro')
                    ->filter(fn($d) => $d !== null && $d !== '')
                    ->map(fn($d) => (int) round((float) $d))
                    ->unique()
                    ->values()
                    ->all();
                $diametrosRequeridos = $derivados;
                Log::info('🔄 Diametros requeridos derivados de elementos', $diametrosRequeridos);
            }
            // -------------------------------------------- ESTADO PENDIENTE --------------------------------------------
            switch ($etiqueta->estado) {
                case 'pendiente':
                    log::info("Etiqueta {$id}: estado pendiente");
                    // Si la etiqueta está pendiente, verificar si ya están todos los elementos fabricados
                    if ($numeroElementosCompletadosEnMaquina >= $numeroElementosTotalesEnEtiqueta) {
                        // Actualizar estado de la etiqueta a "fabricado"
                        $etiqueta->update(['estado' => 'fabricado']);
                    }
                    // ─────────────────────────────────────────────────────────────────────
                    // 1) LOG AUXILIAR: contexto de lo que vamos a necesitar
                    // ─────────────────────────────────────────────────────────────────────
                    // Log::info("🔍 Diámetros requeridos", $diametrosRequeridos);
                    // Log::info(
                    //     "📦 Productos totales en máquina {$maquina->id}",
                    //     $maquina->productos()->with('productoBase')->get()->toArray()
                    // );

                    // ─────────────────────────────────────────────────────────────────────
                    // 2) BASE QUERY: traer productos de la máquina solo de los diámetros
                    //    que pide la etiqueta (diametrosRequeridos). Cargamos productoBase
                    //    para poder filtrar/leer diametro/longitud/tipo con comodidad.
                    // ─────────────────────────────────────────────────────────────────────
                    $productosQuery = $maquina->productos()
                        ->whereHas('productoBase', function ($query) use ($diametrosRequeridos) {
                            $query->whereIn('diametro', $diametrosRequeridos);
                        })
                        ->with('productoBase');

                    // ─────────────────────────────────────────────────────────────────────
                    // 3) VALIDACIÓN DE LONGITUD (solo si la materia prima es "barra")
                    //    - Si en la máquina hay barras de varias longitudes y el usuario
                    //      no ha elegido ninguna, paramos y pedimos que seleccione.
                    //    - Si eligió longitud, filtramos por esa longitud.
                    // ─────────────────────────────────────────────────────────────────────
                    if ($maquina->tipo_material === 'barra') {
                        // Cargamos una primera muestra para explorar longitudes existentes
                        $productosPrevios = $productosQuery->get();

                        // Obtenemos las longitudes disponibles en producto_base (únicas)
                        $longitudes = $productosPrevios->pluck('productoBase.longitud')->unique();

                        // Si hay varias longitudes y no nos han dicho cuál usar, paramos
                        if ($longitudes->count() > 1 && !$longitudSeleccionada) {
                            return response()->json([
                                'success' => false,
                                'error'   => "Hay varias longitudes disponibles para barras (" . $longitudes->implode(', ') . " m). Selecciona una longitud para continuar.",
                            ], 400);
                        }

                        // Si sí nos han indicado una longitud, la aplicamos al filtrado
                        if ($longitudSeleccionada) {
                            $productosQuery->whereHas('productoBase', function ($query) use ($longitudSeleccionada) {
                                $query->where('longitud', $longitudSeleccionada);
                            });
                        }

                        // Re-ejecutamos la query con los filtros definitivos
                        $productos = $productosQuery->orderBy('peso_stock')->get();
                    } else {
                        // Si no trabajamos con barras, ejecutamos tal cual
                        $productos = $productosQuery->orderBy('peso_stock')->get();
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 4) SI TRAS FILTRAR NO QUEDA NADA, NO PODEMOS FABRICAR
                    // ─────────────────────────────────────────────────────────────────────
                    if ($productos->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'error'   => 'No se encontraron productos en la máquina con los diámetros especificados y la longitud indicada.',
                        ], 400);
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 5) AGRUPAR POR DIÁMETRO para facilitar los chequeos posteriores.
                    //    Nota: casteamos a (int) por si vinieran strings desde BD.
                    // ─────────────────────────────────────────────────────────────────────
                    $productosAgrupados = $productos->groupBy(fn($p) => (int) $p->productoBase->diametro);

                    // ─────────────────────────────────────────────────────────────────────
                    // 6) CHEQUEO DE FALTANTES (diámetros sin NINGÚN producto en máquina)
                    //
                    //    Si un diámetro requerido no tiene ni un solo producto en la máquina,
                    //    no podemos empezar: generamos recarga por cada faltante y salimos.
                    //
                    //    Motivo de parar: no existe material del diámetro, no es solo que
                    //    haya poco; es que no hay NADA para empezar a cortar/fabricar.
                    // ─────────────────────────────────────────────────────────────────────
                    $faltantes = [];
                    foreach ($diametrosRequeridos as $diametroReq) {
                        if (!$productosAgrupados->has((int)$diametroReq) || $productosAgrupados[(int)$diametroReq]->isEmpty()) {
                            $faltantes[] = (int) $diametroReq;
                        }
                    }

                    if (!empty($faltantes)) {
                        // Cancelamos la transacción principal para no dejar estados a medias
                        DB::rollBack();

                        // Por cada diámetro faltante, solicitamos recarga (no hay material)
                        foreach ($faltantes as $diametroFaltante) {
                            $productoBaseFaltante = ProductoBase::where('diametro', $diametroFaltante)
                                ->where('tipo', $maquina->tipo_material) // usar SIEMPRE el campo real
                                ->first();

                            if ($productoBaseFaltante) {
                                // Transacción corta y autónoma: el movimiento se registra pase lo que pase
                                DB::transaction(function () use ($productoBaseFaltante, $maquina) {
                                    $this->generarMovimientoRecargaMateriaPrima($productoBaseFaltante, $maquina, null);
                                    Log::info('✅ Movimiento de recarga creado (faltante)', [
                                        'producto_base_id' => $productoBaseFaltante->id,
                                        'maquina_id'       => $maquina->id,
                                    ]);
                                });
                            } else {
                                Log::warning("No se encontró ProductoBase para Ø{$diametroFaltante} y tipo {$maquina->tipo_material}");
                            }
                        }

                        // En faltantes SÍ paramos: no podemos arrancar sin ningún material de ese diámetro
                        return response()->json([
                            'success' => false,
                            'error'   => 'No hay materias primas disponibles para los siguientes diámetros: '
                                . implode(', ', $faltantes)
                                . '. Se han generado automáticamente las solicitudes de recarga.',
                        ], 400);
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 7) SIMULACIÓN DE CONSUMO (sin tocar BD) PARA DETECTAR INSUFICIENCIAS
                    //    Objetivo: prever si, con el stock actual y la demanda por diámetro,
                    //    habrá déficit. La simulación reparte el peso necesario entre los
                    //    productos disponibles del mismo diámetro, agotando primero el que
                    //    menos peso tiene (minimiza restos).
                    //
                    //    Resultado: por cada diámetro, obtenemos:
                    //      - un "plan" de consumo por producto (SOLO informativo)
                    //      - un "pendiente" (déficit) si el stock total no alcanza
                    //    Con esto, avisamos al gruista/operario y opcionalmente creamos
                    //    movimiento de recarga. NO se descuenta stock real aquí.
                    // ─────────────────────────────────────────────────────────────────────

                    $warnings   = $warnings ?? [];
                    $simulacion = []; // [diametro => ['plan' => [[producto_id, consumo_previsto]], 'pendiente' => kg]]

                    foreach ($diametrosConPesos as $diametro => $pesoNecesario) {

                        // Productos de este diámetro (ya filtrados por longitud si es barra)
                        $productosPorDiametro = $productos
                            ->filter(fn($p) => (int)$p->productoBase->diametro === (int)$diametro)
                            // Estrategia: agotar primero el que menos stock tiene
                            ->sortBy('peso_stock'); // ascendente

                        $restante   = (float) $pesoNecesario;
                        $plan       = []; // [[producto_id, consumo_previsto_kg], ...]
                        $stockTotal = 0.0;

                        foreach ($productosPorDiametro as $prod) {
                            $disponible = (float) ($prod->peso_stock ?? 0);
                            if ($disponible <= 0) continue;

                            $stockTotal += $disponible;

                            if ($restante <= 0) break;

                            $consumoPrevisto = min($disponible, $restante);
                            if ($consumoPrevisto > 0) {
                                $plan[]    = ['producto_id' => $prod->id, 'consumo' => $consumoPrevisto];
                                $restante -= $consumoPrevisto;
                            }
                        }

                        $pendiente = max(0, $restante); // kg que faltarán si no llega recarga

                        $simulacion[(int)$diametro] = [
                            'plan'      => $plan,      // SOLO informativo para logs/UI
                            'pendiente' => $pendiente, // 0 si alcanza; >0 si faltará
                            'stock'     => $stockTotal // útil para logs
                        ];
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 8) ALERTAS Y (OPCIONAL) SOLICITUD DE RECARGA PARA LOS DIÁMETROS QUE
                    //    QUEDARÁN CORTOS. NO paramos el flujo: seguimos a "fabricando".
                    // ─────────────────────────────────────────────────────────────────────

                    $diamInsuf = collect($simulacion)
                        ->filter(fn($info) => ($info['pendiente'] ?? 0) > 0)
                        ->keys()
                        ->map(fn($d) => (int)$d)
                        ->values()
                        ->all();

                    if (!empty($diamInsuf)) {
                        foreach ($diamInsuf as $dInsuf) {
                            $deficitKg   = $simulacion[$dInsuf]['pendiente'] ?? null;
                            $stockActual = $simulacion[$dInsuf]['stock']     ?? null;

                            // Aviso claro para UI (toast/alerta)
                            $warnings[] = "Advertencia: Ø{$dInsuf} mm quedará corto. "
                                . "Faltarán ~" . number_format($deficitKg, 2) . " kg (stock actual: "
                                . number_format($stockActual, 2) . " kg). Se ha solicitado recarga.";

                            // Log detallado con el "plan" simulado (útil para trazabilidad)
                            Log::warning('⚠️ Simulación: déficit previsto en diámetro', [
                                'maquina_id' => $maquina->id,
                                'diametro'   => $dInsuf,
                                'pendiente'  => $deficitKg,
                                'plan'       => $simulacion[$dInsuf]['plan'],
                                'stock'      => $stockActual,
                                'necesario'  => (float)($diametrosConPesos[$dInsuf] ?? 0),
                            ]);

                            // (Opcional) solicitar recarga automática, sin parar el flujo
                            if ($solicitarRecargaAuto ?? true) { // flag por si quieres desactivarlo
                                $productoBase = ProductoBase::where('diametro', $dInsuf)
                                    ->where('tipo', $maquina->tipo_material)
                                    ->first();

                                if ($productoBase) {
                                    try {
                                        // Tu método existente. productoId = null → materia prima genérica
                                        $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina, null);

                                        Log::info('📣 Recarga solicitada (déficit previsto)', [
                                            'maquina_id'       => $maquina->id,
                                            'producto_base_id' => $productoBase->id,
                                            'diametro'         => $dInsuf,
                                            'deficit_kg'       => $deficitKg,
                                        ]);
                                    } catch (\Throwable $e) {
                                        Log::error('❌ Error al solicitar recarga (déficit previsto)', [
                                            'maquina_id'       => $maquina->id,
                                            'producto_base_id' => $productoBase->id ?? null,
                                            'diametro'         => $dInsuf,
                                            'deficit_kg'       => $deficitKg,
                                            'error'            => $e->getMessage(),
                                        ]);
                                    }
                                } else {
                                    Log::warning("No se encontró ProductoBase para Ø{$dInsuf} y tipo {$maquina->tipo_material} (recarga no creada).");
                                }
                            }
                        }
                    }

                    // ─────────────────────────────────────────────────────────────────────
                    // 9) ARRANQUE DE FABRICACIÓN: cambiamos estados de planilla/etiqueta/elementos
                    //    - Si la planilla no tenía fecha de inicio, la fijamos y pasamos a "fabricando".
                    //    - Marcamos elementos en máquina como "fabricando" y asignamos operarios.
                    //    - Ponemos la etiqueta en "fabricando".
                    // ─────────────────────────────────────────────────────────────────────
                    if ($etiqueta->planilla) {
                        if (is_null($etiqueta->planilla->fecha_inicio)) {
                            $etiqueta->planilla->fecha_inicio = now();
                            $etiqueta->planilla->estado       = "fabricando";
                            $etiqueta->planilla->save();
                        }
                    } else {
                        // Caso raro: etiqueta sin planilla asociada → no podemos continuar
                        return response()->json([
                            'success' => false,
                            'error'   => 'La etiqueta no tiene una planilla asociada.',
                        ], 400);
                    }

                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id   = $operario1;
                        $elemento->users_id_2 = $operario2;
                        $elemento->estado     = "fabricando";
                        $elemento->save();
                    }

                    $etiqueta->estado        = "fabricando";
                    $etiqueta->operario1_id  = $operario1;
                    $etiqueta->operario2_id  = $operario2;
                    $etiqueta->fecha_inicio  = now();
                    $etiqueta->save();

                    break;

                // -------------------------------------------- ESTADO FABRICANDO --------------------------------------------
                case 'fabricando':
                    // Verificamos si ya todos los elementos en la máquina han sido completados
                    if (
                        isset($elementosEnMaquina) &&
                        $elementosEnMaquina->count() > 0 &&
                        $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count() &&
                        in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                    ) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => "Todos los elementos en la máquina ya han sido completados.",
                        ], 400);
                    }

                    // ✅ Pasamos `$productosAfectados` y `$planilla` como referencia
                    $productosAfectados = [];
                    $resultado = $this->actualizarElementosYConsumos(
                        $elementosEnMaquina,
                        $maquina,
                        $etiqueta,
                        $warnings,
                        $numeroElementosCompletadosEnMaquina,
                        $enOtrasMaquinas,
                        $productosAfectados,
                        $planilla
                    );

                    if ($resultado instanceof \Illuminate\Http\JsonResponse) {
                        DB::rollBack();
                        return $resultado;
                    }
                    break;

                // -------------------------------------------- ESTADO FABRICADA --------------------------------------------
                case 'fabricada':
                    // La etiqueta está fabricada, lo que significa que ya se asignó una máquina secundaria (maquina_id_2)
                    // y el proceso de fabricación terminó, pero el proceso de elaboración (ensamblado o soldadura) aún no ha finalizado.
                    if ($maquina->tipo === 'ensambladora') {
                        // Si la máquina es de tipo ensambladora, se inicia la fase de ensamblado:
                        $etiqueta->fecha_inicio_ensamblado = now();
                        $etiqueta->estado = 'ensamblando';
                        $etiqueta->ensamblador1_id =  $operario1;
                        $etiqueta->ensamblador2_id =  $operario2;
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'soldadora') {
                        // Si la máquina es de tipo soldadora, se inicia la fase de soldadura:
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->soldador1_id =  $operario1;
                        $etiqueta->soldador2_id =  $operario2;
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'dobladora manual') {
                        // Si la máquina es de tipo soldadora, se inicia la fase de soldadura:
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'doblando';
                        $etiqueta->soldador1_id =  $operario1;
                        $etiqueta->soldador2_id =  $operario2;
                        $etiqueta->save();
                    } else {
                        // Verificamos si ya todos los elementos en la máquina han sido completados
                        if (
                            isset($elementosEnMaquina) &&
                            $elementosEnMaquina->count() > 0 &&
                            $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count() &&
                            in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                        ) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'error' => "Todos los elementos en la máquina ya han sido completados.",
                            ], 400);
                        }

                        // Opcional: Si la máquina no es de los tipos esperados, se puede registrar un warning o dejar el estado sin cambios.
                        Log::info("La máquina actual no es ensambladora ni soldadora en el estado 'fabricada'.");
                    }
                    break;
                // -------------------------------------------- ESTADO ENSAMBLADA --------------------------------------------
                case 'ensamblada':
                    // Verificamos si ya todos los elementos en la máquina han sido completados
                    if (
                        isset($elementosEnMaquina) &&
                        $elementosEnMaquina->count() > 0 &&
                        $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count() &&
                        in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                    ) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => "Todos los elementos en la máquina ya han sido completados.",
                        ], 400);
                    }

                    // ✅ Pasamos `$productosAfectados` y `$planilla` como referencia
                    $productosAfectados = [];
                    $resultado = $this->actualizarElementosYConsumos(
                        $elementosEnMaquina,
                        $maquina,
                        $etiqueta,
                        $warnings,
                        $numeroElementosCompletadosEnMaquina,
                        $enOtrasMaquinas,
                        $productosAfectados,
                        $planilla
                    );

                    if ($resultado instanceof \Illuminate\Http\JsonResponse) {
                        DB::rollBack();
                        return $resultado;
                    }

                    if ($maquina->tipo === 'soldadora') {
                        // Si la máquina es de tipo soldadora, se inicia la fase de soldadura:
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->soldador1 =  $operario1;
                        $etiqueta->soldador2 =  $operario2;
                        $etiqueta->save();
                    } else {
                        // Opcional: Si la máquina no es de los tipos esperados, se puede registrar un warning o dejar el estado sin cambios.
                        Log::info("La máquina actual no es ensambladora ni soldadora en el estado 'fabricada'.");
                    }
                    break;

                // -------------------------------------------- ESTADO ENSAMBLANDO --------------------------------------------
                case 'ensamblando':

                    foreach ($elementosEnMaquina as $elemento) {
                        Log::info("Entra en el condicional para completar elementos");
                        $elemento->estado = "completado";
                        $elemento->users_id =  $operario1;
                        $elemento->users_id_2 =  $operario2;
                        $elemento->save();
                    }
                    $elementosEtiquetaCompletos = $etiqueta->elementos()
                        ->where('estado', '!=', 'completado')
                        ->doesntExist();

                    if ($elementosEtiquetaCompletos) {
                        $etiqueta->estado = 'completada';
                        $etiqueta->fecha_finalizacion = now();
                        $etiqueta->save();
                    } else {
                        // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                        if ($enOtrasMaquinas) {
                            $etiqueta->estado = 'ensamblada';
                            $etiqueta->save();
                        }
                    }

                    // Finalizar la fase de ensamblado
                    $etiqueta->fecha_finalizacion_ensamblado = now();
                    $etiqueta->save();
                    // -------------- CONSUMOS
                    $consumos = [];

                    foreach ($diametrosConPesos as $diametro => $pesoNecesarioTotal) {
                        // Si la máquina es ID 7, solo permitir diámetro 5
                        if ($maquina->tipo == 'ensambladora' && $diametro != 5) {
                            continue; // Saltar cualquier otro diámetro
                        }

                        $productosPorDiametro = $maquina->productos()
                            ->whereHas('productoBase', fn($q) => $q->where('diametro', $diametro))
                            ->orderBy('peso_stock')
                            ->get();


                        if ($productosPorDiametro->isEmpty()) {
                            return response()->json([
                                'success' => false,
                                'error' => "No se encontraron materias primas para el diámetro {$diametro}.",
                            ], 400);
                        }

                        $consumos[$diametro] = [];

                        foreach ($productosPorDiametro as $producto) {
                            if ($pesoNecesarioTotal <= 0) {
                                break;
                            }
                            if ($producto->peso_stock > 0) {
                                $restar = min($producto->peso_stock, $pesoNecesarioTotal);
                                $producto->peso_stock -= $restar;
                                $pesoNecesarioTotal -= $restar;
                                if ($producto->peso_stock == 0) {
                                    $producto->estado = "consumido";
                                    $producto->ubicacion_id = NULL;
                                    $producto->maquina_id = NULL;
                                }
                                $producto->save();

                                // Registrar cuánto se consumió de este producto para este diámetro
                                $consumos[$diametro][] = [
                                    'producto_id' => $producto->id,
                                    'consumido' => $restar,
                                ];
                            }
                        }

                        // Si aún queda peso pendiente, no hay suficiente materia prima
                        if ($pesoNecesarioTotal > 0) {
                            // Buscamos el producto base que coincida con este diámetro y la máquina
                            $productoBase = ProductoBase::where('diametro', $diametro)
                                ->where('tipo', $maquina->tipo_material)
                                ->first();

                            if ($productoBase) {
                                $this->generarMovimientoRecargaMateriaPrima(
                                    $productoBase,
                                    $maquina,
                                    null // puedes pasar un producto específico si lo tienes
                                );
                            } else {
                                Log::warning("No se encontró ProductoBase para diámetro {$diametro} y tipo {$maquina->tipo_material}");
                            }
                            return response()->json([
                                'success' => false,
                                'error' => "No hay suficiente materia prima para el diámetro {$diametro} en la máquina {$maquina->nombre}.",
                            ], 400);
                        }
                    }
                    foreach ($elementosEnMaquina as $elemento) {
                        $pesoRestanteElemento = $elemento->peso;
                        // Obtener los registros de consumo para el diámetro del elemento
                        $consumosDisponibles = $consumos[$elemento->diametro] ?? [];
                        $productosAsignados = [];

                        // Mientras el elemento requiera peso y existan registros de consumo
                        while ($pesoRestanteElemento > 0 && count($consumosDisponibles) > 0) {
                            // Tomar el primer registro de consumo
                            $consumo = &$consumosDisponibles[0];

                            if ($consumo['consumido'] <= $pesoRestanteElemento) {
                                // Se usa totalmente este consumo para el elemento
                                $productosAsignados[] = $consumo['producto_id'];
                                $pesoRestanteElemento -= $consumo['consumido'];
                                array_shift($consumosDisponibles);
                            } else {
                                // Solo se consume parcialmente este registro
                                $productosAsignados[] = $consumo['producto_id'];
                                $consumo['consumido'] -= $pesoRestanteElemento;
                                $pesoRestanteElemento = 0;
                            }
                        }

                        $elemento->producto_id = $productosAsignados[0] ?? null;
                        $elemento->producto_id_2 = $productosAsignados[1] ?? null;
                        $elemento->producto_id_3 = $productosAsignados[2] ?? null;

                        $elemento->estado = "completado";

                        $elemento->save();

                        // Actualizar el registro global de consumos para este diámetro
                        $consumos[$elemento->diametro] = $consumosDisponibles;
                    }

                    break;
                // -------------------------------------------- ESTADO SOLDANDO --------------------------------------------
                case 'soldando':
                    // Finalizar la fase de soldadura
                    $etiqueta->fecha_finalizacion_soldadura = now();
                    $etiqueta->estado = 'completada';
                    $etiqueta->save();

                    break;
                // -------------------------------------------- ESTADO SOLDANDO --------------------------------------------
                case 'doblando':
                    // Finalizar la fase de soldadura
                    $etiqueta->fecha_finalizacion_soldadura = now();
                    $etiqueta->estado = 'completada';
                    $etiqueta->save();

                    break;
                // -------------------------------------------- ESTADO COMPLETADA --------------------------------------------
                case 'completada':
                    return response()->json([
                        'success' => false,
                        'error' => "Etiqueta ya completada.",
                    ], 400);
                    break;

                default:
                    throw new \Exception("Estado desconocido de la etiqueta.");
            }


            DB::commit();
            return response()->json([
                'success' => true,
                'estado' => $etiqueta->estado,
                'peso' => $pesoTotalMaquina,
                'productos_afectados' => $productosAfectados,
                'fecha_inicio' => $etiqueta->fecha_inicio ? Carbon::parse($etiqueta->fecha_inicio)->format('d/m/Y H:i:s') : 'No asignada',
                'fecha_finalizacion' => $etiqueta->fecha_finalizacion ? Carbon::parse($etiqueta->fecha_finalizacion)->format('d/m/Y H:i:s') : 'No asignada',
                'warnings' => $warnings // Incluir los warnings en la respuesta
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    private function actualizarElementosYConsumos($elementosEnMaquina, $maquina, &$etiqueta, &$warnings, &$numeroElementosCompletadosEnMaquina, $enOtrasMaquinas, &$productosAfectados, &$planilla)
    {

        foreach ($elementosEnMaquina as $elemento) {
            $elemento->estado = "fabricado";
            $elemento->save();
        }

        // ✅ ACTUALIZAR EL CONTADOR DE ELEMENTOS COMPLETADOS
        $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('estado', 'fabricado')->count();

        // -------------- CONSUMOS
        $consumos = [];
        foreach ($elementosEnMaquina->groupBy('diametro') as $diametro => $elementos) {
            // Si la máquina es ID 7, solo permitir diámetro 5
            if ($maquina->tipo == 'ensambladora' && $diametro != 5) {
                continue; // Saltar cualquier otro diámetro
            }
            $pesoNecesarioTotal = $elementos->sum('peso');

            $productosPorDiametro = $maquina->productos()
                ->whereHas('productoBase', function ($query) use ($diametro) {
                    $query->where('diametro', $diametro);
                })
                ->with('productoBase')
                ->orderBy('peso_stock')
                ->get();

            if ($productosPorDiametro->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => "No se encontraron materias primas para el diámetro {$diametro}.",
                ], 400);
            }

            $consumos[$diametro] = [];
            foreach ($productosPorDiametro as $producto) {
                if ($pesoNecesarioTotal <= 0) break;

                $pesoInicial = $producto->peso_inicial ?? $producto->peso_stock;

                $restar = min($producto->peso_stock, $pesoNecesarioTotal);
                $producto->peso_stock -= $restar;
                $pesoNecesarioTotal -= $restar;

                if ($producto->peso_stock == 0) {
                    $producto->estado = "consumido";
                    $producto->ubicacion_id = null;
                    $producto->maquina_id = null;
                }

                $producto->save();

                $productosAfectados[] = [
                    'id' => $producto->id,
                    'peso_stock' => $producto->peso_stock,
                    'peso_inicial' => $pesoInicial,
                ];

                $consumos[$diametro][] = [
                    'producto_id' => $producto->id,
                    'consumido' => $restar,
                ];
            }
            if ($pesoNecesarioTotal > 0) {

                // 1️⃣  Encontrar ProductoBase SÍ o SÍ
                $productoBase = ProductoBase::where('diametro', $diametro)
                    ->where('tipo', $maquina->tipo_material)          // usa SIEMPRE la columna real
                    ->first();

                if (!$productoBase) {
                    Log::warning("No se encontró ProductoBase Ø{$diametro} / tipo {$maquina->tipo_material}");
                    // De todos modos abortamos; mejor lanzar un error claro
                    DB::rollBack();
                    return new JsonResponse([
                        'success' => false,
                        'error'   => "No existe materia prima configurada para Ø{$diametro} mm (tipo {$maquina->tipo_material}).",
                    ], 400);
                }

                // 2️⃣  Deshacemos TODA la transacción principal
                DB::rollBack();

                // 3️⃣  Insertamos el movimiento en SU propia transacción
                DB::transaction(function () use ($productoBase, $maquina) {
                    $this->generarMovimientoRecargaMateriaPrima($productoBase, $maquina);
                    Log::info('✅ Movimiento de recarga creado', [
                        'producto_base_id' => $productoBase->id,
                        'maquina_id'       => $maquina->id,
                    ]);
                });

                // 4️⃣  Respondemos y detenemos la ejecución
                return new JsonResponse([
                    'success' => false,
                    'error'   => "No hay suficiente materia prima para Ø{$diametro} mm en la máquina {$maquina->nombre}. "
                        . "Se ha generado automáticamente la solicitud de recarga.",
                ], 400);
            }
        }

        // ✅ Asignar productos consumidos a los elementos
        foreach ($elementosEnMaquina as $elemento) {
            $pesoRestanteElemento = $elemento->peso;
            $consumosDisponibles = $consumos[$elemento->diametro] ?? [];
            $productosAsignados = [];

            while ($pesoRestanteElemento > 0 && count($consumosDisponibles) > 0) {
                $consumo = &$consumosDisponibles[0];

                if ($consumo['consumido'] <= $pesoRestanteElemento) {
                    $productosAsignados[] = $consumo['producto_id'];
                    $pesoRestanteElemento -= $consumo['consumido'];
                    array_shift($consumosDisponibles);
                } else {
                    $productosAsignados[] = $consumo['producto_id'];
                    $consumo['consumido'] -= $pesoRestanteElemento;
                    $pesoRestanteElemento = 0;
                }
            }

            $elemento->producto_id = $productosAsignados[0] ?? null;
            $elemento->producto_id_2 = $productosAsignados[1] ?? null;
            $elemento->producto_id_3 = $productosAsignados[2] ?? null;
            $elemento->save();
        }

        // ✅ Lógica de "TALLER" y "CARCASAS"
        $ensambladoText = strtolower($etiqueta->planilla->ensamblado ?? '');

        if (str_contains($ensambladoText, 'taller')) {
            // Verificar si todos los elementos de la etiqueta están en estado "completado"
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'fabricado')->doesntExist();
            if (str_contains($planilla->comentario, 'amarrado')) {
            } elseif (str_contains($planilla->comentario, 'ensamblado amarrado')) {
            } else {
                // Verificar si TODOS los elementos de la máquina actual están completados
                if ($elementosEnMaquina->count() > 0 && $numeroElementosCompletadosEnMaquina >= $elementosEnMaquina->count()) {
                    // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                    if ($enOtrasMaquinas) {
                        $etiqueta->estado = 'parcialmente completada';
                    } else {
                        // Si no hay elementos en otras máquinas, se marca como fabricada/completada
                        $etiqueta->estado = 'fabricada';
                        $etiqueta->fecha_finalizacion = now();
                    }

                    $etiqueta->save();
                }
                // Buscar una máquina de soldar disponible
                $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                    ->whereDoesntHave('elementos')
                    ->first();

                if (!$maquinaSoldarDisponible) {
                    $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                        ->whereHas('elementos', function ($query) {
                            $query->orderBy('created_at');
                        })
                        ->first();
                }

                if ($maquinaSoldarDisponible) {
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->maquina_id_3 = $maquinaSoldarDisponible->id;
                        $elemento->save();
                    }
                } else {
                    throw new \Exception("No se encontró una máquina de soldar disponible para taller.");
                }
            }
        } elseif (str_contains($ensambladoText, 'carcasas')) {
            $elementosEtiquetaCompletos = $etiqueta->elementos()
                ->where('diametro', '!=', 5.00)
                ->where('estado', '!=', 'fabricado')
                ->doesntExist();

            if ($elementosEtiquetaCompletos) {
                $etiqueta->estado = $maquina->tipo === 'estribadora' ? 'fabricada' : 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            }

            // 🔧 Solo si la máquina actual no es cortadora_dobladora
            if ($maquina->tipo !== 'cortadora_dobladora') {
                $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();

                if ($maquinaEnsambladora) {
                    foreach ($elementosEnMaquina as $elemento) {
                        if (is_null($elemento->maquina_id_2)) {
                            $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                            $elemento->save();
                        }
                    }
                }
            }
        } else {

            // 🧠 Regla especial: si el nombre de la etiqueta contiene "pates"
            if (Str::of($etiqueta->nombre ?? '')->lower()->contains('pates')) {

                $cid = (string) Str::uuid();

                Log::info("[pates][$cid] Disparada regla especial", [
                    'etiqueta_id'     => $etiqueta->id ?? null,
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id ?? null,
                    'etiqueta_nombre' => $etiqueta->nombre ?? null,
                    'maquina_id'      => $maquina->id ?? null,
                    'maquina_tipo'    => $maquina->tipo ?? null,
                    'maquina_obra_id' => $maquina->obra_id ?? null,
                ]);
                DB::transaction(function () use ($etiqueta, $maquina) {
                    // 1) Marcar etiqueta como "fabricada" y cerrar fecha
                    $etiqueta->estado = 'fabricada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();

                    // 2) Buscar una máquina tipo "dobladora_manual"
                    $dobladora = Maquina::where('tipo', 'dobladora manual')
                        // si quieres priorizar la misma obra:
                        ->when($maquina->obra_id, fn($q) => $q->where('obra_id', $maquina->obra_id))
                        ->orderBy('id')
                        ->first();

                    if ($dobladora) {
                        // 3) Asignar maquina_id_2 a TODOS los elementos de esa etiqueta en ESTA máquina
                        Elemento::where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id)
                            ->where('maquina_id', $maquina->id)
                            ->update(['maquina_id_2' => $dobladora->id]);
                        // 🔔 Generar movimiento para que el gruista lleve el paquete a la dobladora
                        // $this->generarMovimientoEtiqueta(
                        //     $maquina,
                        //     $dobladora,
                        //     (int) $etiqueta->etiqueta_sub_id,
                        //     $etiqueta->planilla_id ?? optional($etiqueta->planilla)->id
                        // );

                        // 3.b) Asegurar que la planilla aparece en la cola de la dobladora (orden_planillas)
                        $planillaId = $etiqueta->planilla_id ?? optional($etiqueta->planilla)->id;

                        if ($planillaId) {
                            // Evitamos duplicados de la misma planilla en esa máquina
                            $yaExiste = OrdenPlanilla::where('maquina_id', $dobladora->id)
                                ->where('planilla_id', $planillaId)
                                ->lockForUpdate()   // bloqueamos la cola mientras consultamos/insertamos
                                ->exists();

                            if (! $yaExiste) {
                                // Obtenemos la última posición de esa máquina de forma segura
                                $ultimaPos = OrdenPlanilla::where('maquina_id', $dobladora->id)
                                    ->select('posicion')
                                    ->orderByDesc('posicion')
                                    ->lockForUpdate()
                                    ->value('posicion');

                                OrdenPlanilla::create([
                                    'maquina_id'  => $dobladora->id,
                                    'planilla_id' => $planillaId,
                                    'posicion'    => is_null($ultimaPos) ? 0 : ($ultimaPos + 1),
                                ]);
                            }
                        } else {
                            Log::warning('No se pudo encolar planilla en dobladora: planilla_id nulo', [
                                'etiqueta_id' => $etiqueta->id ?? null,
                                'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id ?? null,
                                'dobladora_id' => $dobladora->id,
                            ]);
                        }
                    } else {
                        Log::warning('No hay dobladora_manual para asignar maquina_id_2', [
                            'maquina_origen_id' => $maquina->id,
                            'etiqueta_sub_id'   => $etiqueta->etiqueta_sub_id,
                        ]);
                    }
                });
            } else {
                // ✅ Lógica normal que ya tenías
                // Verificar si todos los elementos de la etiqueta están en estado "fabricado"
                $elementosEtiquetaCompletos = $etiqueta->elementos()
                    ->where('estado', '!=', 'fabricado')
                    ->doesntExist();

                if ($elementosEtiquetaCompletos) {
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                } else {
                    // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                    if ($enOtrasMaquinas) {
                        $etiqueta->estado = 'parcialmente completada';
                        $etiqueta->save();
                    }
                }
            }
        }
        // ✅ Si ya no quedan elementos de esta planilla en ESTA máquina, sacarla de la cola y compactar posiciones
        $quedanPendientesEnEstaMaquina = Elemento::where('planilla_id', $planilla->id)
            ->where('maquina_id', $maquina->id)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '!=', 'fabricado');
            })
            ->exists();

        if (! $quedanPendientesEnEstaMaquina) {

            // 🔍 Verificamos que todas las etiquetas de esa planilla tengan paquete asignado
            $todasEtiquetasEnPaquete = $planilla->etiquetas()
                ->whereDoesntHave('paquete') // etiquetas sin paquete
                ->doesntExist();

            if ($todasEtiquetasEnPaquete) {
                DB::transaction(function () use ($planilla, $maquina) {
                    // 1) Buscar registro en la cola
                    $registro = OrdenPlanilla::where('planilla_id', $planilla->id)
                        ->where('maquina_id', $maquina->id)
                        ->lockForUpdate()
                        ->first();

                    if ($registro) {
                        $posicionEliminada = $registro->posicion;

                        // 2) Eliminar de la cola
                        $registro->delete();

                        // 3) Reordenar posiciones posteriores
                        OrdenPlanilla::where('maquina_id', $maquina->id)
                            ->where('posicion', '>', $posicionEliminada)
                            ->decrement('posicion');
                    }
                });
            }
        }

        // ✅ Si todos los elementos de la planilla están completados, actualizar la planilla
        $todosElementosPlanillaCompletos = $planilla->elementos()
            ->where('estado', '!=', 'fabricado')
            ->doesntExist();

        if ($todosElementosPlanillaCompletos) {
            $planilla->fecha_finalizacion = now();
            $planilla->estado = 'completada';
            $planilla->save();

            DB::transaction(function () use ($planilla, $maquina) {
                // 1. Eliminar el registro de esa planilla en esta máquina
                OrdenPlanilla::where('planilla_id', $planilla->id)
                    ->where('maquina_id', $maquina->id)
                    ->delete();

                // 2. Reordenar las posiciones de las planillas restantes en esta máquina
                $ordenes = OrdenPlanilla::where('maquina_id', $maquina->id)
                    ->orderBy('posicion')
                    ->lockForUpdate()
                    ->get();

                foreach ($ordenes as $index => $orden) {
                    $orden->posicion = $index;
                    $orden->save();
                }
            });
        }

        return true;
    }
    /**
     * Genera un movimiento "Movimiento paquete" para trasladar una subetiqueta
     * (no requiere paquete_id aún). Deduplica por origen/destino + etiqueta_sub_id.
     */
    protected function generarMovimientoEtiqueta(
        Maquina $origen,
        Maquina $destino,
        int $etiquetaSubId,
        ?int $planillaId = null
    ): void {
        try {
            $referencia = "etiqueta_sub {$etiquetaSubId}";

            // 🛑 evitar duplicados
            $yaExiste = Movimiento::where('tipo', 'Movimiento paquete')
                ->where('estado', 'pendiente')
                ->where('maquina_origen',  $origen->id)
                ->where('maquina_destino', $destino->id)
                ->where('descripcion', 'like', "%{$referencia}%")
                ->lockForUpdate()
                ->exists();

            if ($yaExiste) {
                Log::info('Movimiento paquete ya existente; no se duplica', [
                    'origen'        => $origen->id,
                    'destino'       => $destino->id,
                    'etiqueta_sub'  => $etiquetaSubId,
                    'planilla_id'   => $planillaId,
                ]);
                return;
            }

            Movimiento::create([
                'tipo'             => 'Movimiento paquete',
                'maquina_origen'   => $origen->id,
                'maquina_destino'  => $destino->id,
                'producto_id'      => null,
                'producto_base_id' => null,
                'estado'           => 'pendiente',
                'descripcion'      => "Trasladar {$referencia}"
                    . ($planillaId ? " (planilla {$planillaId})" : '')
                    . " desde {$origen->nombre} hasta {$destino->nombre}.",
                'prioridad'        => 1,
                'fecha_solicitud'  => now(),
                'solicitado_por'   => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear Movimiento paquete (etiqueta)', [
                'maquina_origen'  => $origen->id,
                'maquina_destino' => $destino->id,
                'etiqueta_sub_id' => $etiquetaSubId,
                'planilla_id'     => $planillaId,
                'error'           => $e->getMessage(),
            ]);
            throw new \Exception('No se pudo registrar la solicitud de movimiento de paquete.');
        }
    }

    protected function generarMovimientoRecargaMateriaPrima(
        ProductoBase $productoBase,
        Maquina $maquina,
        ?int $productoId = null
    ): void {
        try {
            Movimiento::create([
                'tipo'              => 'Recarga materia prima',
                'maquina_origen'    => null,
                'maquina_destino'   => $maquina->id,
                'producto_id'       => $productoId,
                'producto_base_id'  => $productoBase->id,
                'estado'            => 'pendiente',
                'descripcion'       => "Se solicita materia prima del tipo "
                    . strtolower($productoBase->tipo)
                    . " (Ø{$productoBase->diametro}, {$productoBase->longitud} mm) "
                    . "en la máquina {$maquina->nombre}",
                'prioridad'         => 1,
                'fecha_solicitud'   => now(),
                'solicitado_por'    => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            // Lo registras y vuelves a lanzar una excepción más “amigable”
            Log::error('Error al crear movimiento de recarga', [
                'maquina_id' => $maquina->id,
                'producto_base_id' => $productoBase->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('No se pudo registrar la solicitud de recarga de materia prima.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Buscar la etiqueta o lanzar excepción si no se encuentra
            $etiqueta = Etiqueta::findOrFail($id);

            // Si los campos de fecha vienen vacíos, forzar null
            $request->merge([
                'fecha_inicio'                => $request->fecha_inicio ?: null,
                'fecha_finalizacion'          => $request->fecha_finalizacion ?: null,
                'fecha_inicio_ensamblado'     => $request->fecha_inicio_ensamblado ?: null,
                'fecha_finalizacion_ensamblado' => $request->fecha_finalizacion_ensamblado ?: null,
                'fecha_inicio_soldadura'      => $request->fecha_inicio_soldadura ?: null,
                'fecha_finalizacion_soldadura' => $request->fecha_finalizacion_soldadura ?: null,
            ]);

            // Validar los datos recibidos con mensajes personalizados
            $validatedData = $request->validate([
                'numero_etiqueta'          => 'required|string|max:50',
                'nombre'                   => 'required|string|max:255',
                'peso_kg'                  => 'nullable|numeric',
                'fecha_inicio'             => 'nullable|date_format:d/m/Y',
                'fecha_finalizacion'       => 'nullable|date_format:d/m/Y',
                'fecha_inicio_ensamblado'  => 'nullable|date_format:d/m/Y',
                'fecha_finalizacion_ensamblado' => 'nullable|date_format:d/m/Y',
                'fecha_inicio_soldadura'   => 'nullable|date_format:d/m/Y',
                'fecha_finalizacion_soldadura' => 'nullable|date_format:d/m/Y',
                'estado'                   => 'nullable|string|in:pendiente,fabricando,completada'
            ], [
                'numero_etiqueta.required' => 'El campo Número de Etiqueta es obligatorio.',
                'numero_etiqueta.string'   => 'El campo Número de Etiqueta debe ser una cadena de texto.',
                'numero_etiqueta.max'      => 'El campo Número de Etiqueta no debe exceder 50 caracteres.',

                'nombre.required'          => 'El campo Nombre es obligatorio.',
                'nombre.string'            => 'El campo Nombre debe ser una cadena de texto.',
                'nombre.max'               => 'El campo Nombre no debe exceder 255 caracteres.',

                'peso_kg.numeric'          => 'El campo Peso debe ser un número.',

                'fecha_inicio.date_format'             => 'El campo Fecha Inicio no corresponde al formato DD/MM/YYYY.',
                'fecha_finalizacion.date_format'       => 'El campo Fecha Finalización no corresponde al formato DD/MM/YYYY.',
                'fecha_inicio_ensamblado.date_format'    => 'El campo Fecha Inicio Ensamblado no corresponde al formato DD/MM/YYYY.',
                'fecha_finalizacion_ensamblado.date_format' => 'El campo Fecha Finalización Ensamblado no corresponde al formato DD/MM/YYYY.',
                'fecha_inicio_soldadura.date_format'     => 'El campo Fecha Inicio Soldadura no corresponde al formato DD/MM/YYYY.',
                'fecha_finalizacion_soldadura.date_format' => 'El campo Fecha Finalización Soldadura no corresponde al formato DD/MM/YYYY.',
                'estado.in'              => 'El campo Estado debe ser: pendiente, fabricando o completada.'
            ]);

            // Convertir las fechas al formato 'Y-m-d' si existen
            if (!empty($validatedData['fecha_inicio'])) {
                $validatedData['fecha_inicio'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_inicio'])
                    ->format('Y-m-d');
            }
            if (!empty($validatedData['fecha_finalizacion'])) {
                $validatedData['fecha_finalizacion'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_finalizacion'])
                    ->format('Y-m-d');
            }
            if (!empty($validatedData['fecha_inicio_ensamblado'])) {
                $validatedData['fecha_inicio_ensamblado'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_inicio_ensamblado'])
                    ->format('Y-m-d');
            }
            if (!empty($validatedData['fecha_finalizacion_ensamblado'])) {
                $validatedData['fecha_finalizacion_ensamblado'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_finalizacion_ensamblado'])
                    ->format('Y-m-d');
            }
            if (!empty($validatedData['fecha_inicio_soldadura'])) {
                $validatedData['fecha_inicio_soldadura'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_inicio_soldadura'])
                    ->format('Y-m-d');
            }
            if (!empty($validatedData['fecha_finalizacion_soldadura'])) {
                $validatedData['fecha_finalizacion_soldadura'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_finalizacion_soldadura'])
                    ->format('Y-m-d');
            }

            // Actualizar la etiqueta con los datos validados
            $etiqueta->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta actualizada correctamente',
                'data'    => $etiqueta->numero_etiqueta
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Etiqueta no encontrada'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la etiqueta. Intente nuevamente. ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            // Buscar la etiqueta o lanzar excepción si no se encuentra
            $etiqueta = Etiqueta::findOrFail($id);

            // Eliminar la etiqueta
            $etiqueta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta eliminada correctamente'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Etiqueta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la etiqueta. Intente nuevamente. ' . $e->getMessage()
            ], 500);
        }
    }

    //METODOS PROVISIONALES

    public function fabricarLote(Request $request)
    {
        try {
            $etiquetaSubIds = $request->input('etiquetas');
            $maquinaId = $request->input('maquina_id');

            if (!is_array($etiquetaSubIds) || empty($etiquetaSubIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionaron etiquetas válidas.',
                    'errors' => [
                        ['id' => null, 'error' => 'El parámetro "etiquetas" debe ser un array no vacío.']
                    ],
                ], 422);
            }

            if (!$maquinaId || !is_numeric($maquinaId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionó una máquina válida.',
                    'errors' => [
                        ['id' => null, 'error' => 'El parámetro "maquina_id" es obligatorio y debe ser numérico.']
                    ],
                ], 422);
            }

            $maquina = Maquina::findOrFail($maquinaId);
            $fabricadas = 0;
            $warnings = [];
            $errors = [];

            foreach ($etiquetaSubIds as $subId) {
                try {
                    $etiqueta = Etiqueta::where('etiqueta_sub_id', $subId)->firstOrFail();

                    if (in_array($etiqueta->estado, ['completada', 'fabricada'])) {
                        throw new \Exception("La etiqueta {$etiqueta->codigo} ya está completada.");
                    }

                    $resultado = $this->verificarYPrepararFabricacion($etiqueta, $maquina);

                    if ($resultado === true) {
                        $fabricadas++;
                    } else {
                        // No se detiene el flujo
                        $fabricadas++;
                        $warnings[] = [
                            'id'    => $subId,
                            'error' => $resultado['error'] ?? 'Error desconocido.',
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = [
                        'id' => $subId,
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                    ];
                }
            }

            $mensaje = $fabricadas > 0
                ? "Empezamos a fabricar {$fabricadas} etiqueta(s)."
                : "No se pudo preparar ninguna etiqueta.";

            return response()->json([
                'success' => $fabricadas > 0,
                'message' => $mensaje,
                'errors'  => $errors,
                'warnings' => $warnings,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al fabricar las etiquetas.',
                'errors' => [
                    ['id' => null, 'error' => $e->getMessage(), 'line' => $e->getLine()]
                ],
            ], 500);
        }
    }


    public function verificarYPrepararFabricacion(Etiqueta $etiqueta, Maquina $maquina): bool|array
    {
        DB::beginTransaction();

        try {
            $elementosEnMaquina = $etiqueta->elementos->where('maquina_id', $maquina->id);

            if ($elementosEnMaquina->isEmpty()) {
                throw new \Exception("La etiqueta no tiene elementos asignados a la máquina {$maquina->nombre}.");
            }

            $operario1 = Auth::id();
            $operario2 = auth()->user()->compañeroDeTurno()?->id;

            foreach ($elementosEnMaquina as $elemento) {
                $elemento->update([
                    'estado'       => 'fabricando',
                    'fecha_inicio' => $elemento->fecha_inicio ?? now(),
                    'users_id'     => $operario1,
                    'users_id_2'   => $operario2,
                ]);
            }

            $diametrosConPesos = $elementosEnMaquina
                ->groupBy(fn($e) => (float) $e->diametro)
                ->map(fn($grupo) => $grupo->sum('peso'));

            $faltantes = [];

            foreach ($diametrosConPesos as $diametro => $pesoNecesarioTotal) {
                $productos = $maquina->productos()
                    ->whereHas(
                        'productoBase',
                        fn($q) => $q
                            ->where('diametro', $diametro)
                            ->where('tipo', $maquina->tipo_material)
                    )
                    ->with('productoBase')
                    ->orderBy('peso_stock')
                    ->get();

                $stockDisponible = $productos->sum('peso_stock');

                if ($stockDisponible < $pesoNecesarioTotal) {
                    $faltantes[] = $diametro;
                    $this->avisarGruistaRecarga($diametro, $maquina, $etiqueta->codigo);
                }
            }

            // Estado planilla y etiqueta
            $planilla = $etiqueta->planilla;

            if (!$planilla) {
                throw new \Exception("La etiqueta no tiene una planilla asociada.");
            }

            if (is_null($planilla->fecha_inicio)) {
                $planilla->update([
                    'fecha_inicio' => now(),
                    'estado'       => 'fabricando',
                ]);
            }

            $etiqueta->update([
                'estado'        => 'fabricando',
                'operario1_id'  => $operario1,
                'operario2_id'  => $operario2,
                'fecha_inicio'  => $etiqueta->fecha_inicio ?? now(),
            ]);

            DB::commit();

            if (!empty($faltantes)) {
                return [
                    'success' => false,
                    'error'   => 'Falta stock para Ø' . implode(', Ø', $faltantes)
                        . ". Se han solicitado recargas automáticamente.",
                ];
            }

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function avisarGruistaRecarga(float $diametro, Maquina $maquina, string $codigoEtiqueta): void
    {
        $productoBase = ProductoBase::where('diametro', $diametro)
            ->where('tipo', $maquina->tipo_material)
            ->first();

        if (!$productoBase) {
            Log::warning("ProductoBase no encontrado para Ø{$diametro} y tipo {$maquina->tipo_material}");
            return;
        }

        $yaExiste = Movimiento::where('tipo', 'Recarga materia prima')
            ->where('producto_base_id', $productoBase->id)
            ->where('maquina_destino', $maquina->id)
            ->where('estado', 'pendiente')
            ->exists();

        if (!$yaExiste) {
            Movimiento::create([
                'tipo'               => 'Recarga materia prima',
                'producto_base_id'   => $productoBase->id,
                'maquina_destino'    => $maquina->id,
                'estado'             => 'pendiente',
                'prioridad'          => 1,
                'descripcion'        => "Recarga solicitada automática",
                'fecha_solicitud'    => now(),
                'solicitado_por'     => Auth::id(),
            ]);

            Log::info("✅ Movimiento de recarga creado para Ø{$diametro} en {$maquina->nombre}");
        } else {
            Log::info("⏭️ Movimiento ya existente para Ø{$diametro} en {$maquina->nombre}");
        }
    }


    public function completarLote(Request $request, CompletarLoteService $service)
    {
        $etiquetas = (array) $request->input('etiquetas', []);

        $maquinaId = (int) $request->input('maquina_id');

        $res = $service->completarLote($etiquetas, $maquinaId);

        return response()->json($res);
    }
}
