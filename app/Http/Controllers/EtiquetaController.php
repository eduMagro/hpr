<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Paquete;
use App\Models\OrdenPlanilla;
use App\Models\Etiqueta;
use App\Models\Producto;
use App\Models\ProductoBase;
use App\Models\Ubicacion;
use App\Models\Movimiento;
use App\Models\AsignacionTurno;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Maquina;
use App\Models\EtiquetaHistorial;
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
        // Normaliza alias -> nombres usados en backend
        $aliasFechas = [
            'inicio_fabricacion'        => 'fecha_inicio',
            'final_fabricacion'         => 'fecha_finalizacion',
            'inicio_ensamblado'         => 'fecha_inicio_ensamblado',
            'final_ensamblado'          => 'fecha_finalizacion_ensamblado',
            'inicio_soldadura'          => 'fecha_inicio_soldadura',
            'final_soldadura'           => 'fecha_finalizacion_soldadura',
        ];

        $merge = [];
        foreach ($aliasFechas as $alias => $destino) {
            if ($request->filled($alias) && !$request->filled($destino)) {
                $merge[$destino] = $request->input($alias);
            }
        }
        if ($merge) {
            $request->merge($merge);
        }

        if ($request->filled('id')) {
            $input = trim($request->id);
            $query->where('id', 'like', '%' . $input . '%');
        }


        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
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

        /* ── Filtro por código de planilla (formato AAAA-nnnnnn) ──────── */
        if ($request->filled('codigo_planilla')) {
            $input = trim($request->codigo_planilla);

            $query->whereHas('planilla', function ($q) use ($input) {

                // Caso 1: formato AAAA-nnnnnn (con o sin ceros)
                if (preg_match('/^(\d{4})-(\d{1,6})$/', $input, $m)) {
                    $anio = $m[1];
                    $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                    $codigoFormateado = "{$anio}-{$num}";
                    $q->where('codigo', 'like', "%{$codigoFormateado}%");
                    return;
                }

                // Caso 2: solo números (por ejemplo, "4512")
                if (preg_match('/^\d{1,6}$/', $input)) {
                    $q->where('codigo', 'like', "%{$input}%");
                    return;
                }

                // Caso 3: texto o formato libre
                $q->where('codigo', 'like', "%{$input}%");
            });
        }

        /* ── Fechas: Fabricación ──────── */
        if ($request->filled('fecha_inicio')) {
            $query->where('fecha_inicio', '>=', Carbon::parse($request->fecha_inicio)->startOfDay());
        }
        if ($request->filled('fecha_finalizacion')) {
            $query->where('fecha_finalizacion', '<=', Carbon::parse($request->fecha_finalizacion)->endOfDay());
        }

        /* ── Fechas: Ensamblado ──────── */
        if ($request->filled('fecha_inicio_ensamblado')) {
            $query->where('fecha_inicio_ensamblado', '>=', Carbon::parse($request->fecha_inicio_ensamblado)->startOfDay());
        }
        if ($request->filled('fecha_finalizacion_ensamblado')) {
            $query->where('fecha_finalizacion_ensamblado', '<=', Carbon::parse($request->fecha_finalizacion_ensamblado)->endOfDay());
        }

        /* ── Fechas: Soldadura ──────── */
        if ($request->filled('fecha_inicio_soldadura')) {
            $query->where('fecha_inicio_soldadura', '>=', Carbon::parse($request->fecha_inicio_soldadura)->startOfDay());
        }
        if ($request->filled('fecha_finalizacion_soldadura')) {
            $query->where('fecha_finalizacion_soldadura', '<=', Carbon::parse($request->fecha_finalizacion_soldadura)->endOfDay());
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
                'fecha_inicio' => 'Inicio Fabricación desde',
                'fecha_finalizacion'  => 'Fin Fabricación hasta',
                'fecha_inicio_ensamblado'  => 'Inicio Ensamblado desde',
                'fecha_finalizacion_ensamblado'   => 'Fin Ensamblado hasta',
                'fecha_inicio_soldadura'   => 'Inicio Soldadura desde',
                'fecha_finalizacion_soldadura'    => 'Fin Soldadura hasta',
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
        $sort  = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $map = [
            'id'              => 'etiquetas.id',
            'codigo'          => 'etiquetas.codigo',
            'codigo_planilla' => 'planillas.codigo',    //es otra tabla
            'etiqueta'        => 'etiquetas.etiqueta',
            'etiqueta_sub_id' => 'etiquetas.etiqueta_sub_id',
            'paquete_id'      => 'etiquetas.paquete_id',
            'maquina'         => 'etiquetas.maquina',
            'maquina_2'       => 'etiquetas.maquina_2',
            'producto1'       => 'etiquetas.producto1',
            'producto2'       => 'etiquetas.producto2',
            'producto3'       => 'etiquetas.producto3',
            'figura'          => 'etiquetas.figura',
            'peso'            => 'etiquetas.peso',
            'diametro'        => 'etiquetas.diametro',
            'longitud'        => 'etiquetas.longitud',
            'estado'          => 'etiquetas.estado',
            'created_at'      => 'etiquetas.created_at',
        ];

        $column = $map[$sort] ?? 'etiquetas.created_at';

        // Si ordenamos por una columna de planillas, añadimos el JOIN
        if (str_starts_with($column, 'planillas.')) {
            $query->leftJoin('planillas', 'planillas.id', '=', 'etiquetas.planilla_id')
                ->select('etiquetas.*'); // evita colisiones de columnas
        }

        return $query->orderBy($column, $order);
    }

    public function index(Request $request)
    {
        return view('etiquetas.index-livewire');
    }

    public function indexTradicional(Request $request)
    {
        $query = Etiqueta::with([
            'planilla:id,codigo,obra_id,cliente_id,seccion',
            'paquete:id,codigo',
            'producto:id,codigo',
            'producto2:id,codigo',
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

    public function calcularPatronCorteSimple(Request $request, $etiqueta)
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

        // ⚙️ Convertimos el diámetro siempre a float
        $diametro = floatval($request->input('diametro', $elemento->diametro));
        $numPiezas = intval($request->input('barras', $elemento->barras));

        // 🔹 Longitudes disponibles de barras en el catálogo base
        $longitudesDisponibles = ProductoBase::query()
            ->where('tipo', 'barra')
            ->whereRaw('ABS(diametro - ?) < 0.01', [$diametro]) // tolerancia
            ->distinct()
            ->pluck('longitud')
            ->unique()
            ->sort()
            ->values();

        if ($longitudesDisponibles->isEmpty()) {
            return response()->json([
                'message' => "No hay longitudes disponibles para Ø{$diametro} mm",
            ], 400);
        }

        // 1️⃣ Obtener ID de la máquina SL28
        $maquinaSL28 = Maquina::where('codigo', 'SL28')->first();
        $maquinaId = optional($maquinaSL28)->id;

        // 2️⃣ Buscar productos cargados en esa máquina de ese diámetro y tipo
        $longitudesEnMaquina = collect();

        if ($maquinaId) {
            $longitudesEnMaquina = Producto::with('productoBase')
                ->where('maquina_id', $maquinaId)
                ->get()
                ->filter(
                    fn($p) =>
                    $p->productoBase?->tipo === 'barra' &&
                        abs(floatval($p->productoBase?->diametro) - $diametro) < 0.01
                )
                ->pluck('productoBase.longitud')
                ->unique()
                ->values();
        }

        // 🧮 Cálculo del patrón
        $longitudElementoM = $elemento->longitud / 100;
        if ($longitudElementoM <= 0) {
            return response()->json([
                'message' => 'Longitud del elemento no válida.',
            ], 400);
        }

        $patrones = [];

        foreach ($longitudesDisponibles as $longitudM) {
            $porBarra = floor($longitudM / $longitudElementoM);
            if ($numPiezas > 0) {
                $porBarra = min($porBarra, $numPiezas); // 👈 no más piezas de las necesarias
            }
            $sobraCm  = round(($longitudM - ($porBarra * $longitudElementoM)) * 100, 2);
            $aprovechamiento = $porBarra > 0
                ? round(100 * ($porBarra * $longitudElementoM) / $longitudM, 2)
                : 0;

            $patron = $porBarra > 0
                ? implode(' + ', array_fill(0, $porBarra, number_format($elemento->longitud, 2))) . " = {$porBarra} piezas"
                : "No caben piezas";

            // 3️⃣ ¿Está disponible en la máquina SL28?
            $disponible = $longitudesEnMaquina->contains(function ($l) use ($longitudM) {
                return abs(floatval($l) - floatval($longitudM)) < 0.01;
            });

            $patrones[] = [
                'longitud_m'      => $longitudM,
                'longitud_cm'     => $longitudM * 100,
                'por_barra'       => $porBarra,
                'sobra_cm'        => $sobraCm,
                'aprovechamiento' => $aprovechamiento,
                'patron'          => $patron,
                'disponible_en_maquina' => $disponible,
            ];
        }

        // 🔠 Esquema tipo A + A + A
        $letra = 'A';
        $etiquetasEsquema = implode(' + ', array_fill(0, min($porBarra, $numPiezas), $letra));


        return response()->json([
            'success'  => true,
            'patrones' => $patrones,
            'patron_letras' => $etiquetasEsquema,
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

        // 2) Traer la cola de ESA máquina, SOLO posiciones posteriores
        $posteriores = OrdenPlanilla::where('maquina_id', $maquinaId)
            ->where('posicion', '>', $posActual)
            ->orderBy('posicion', 'asc')
            ->pluck('planilla_id')
            ->toArray();

        // 3) Lista: primero la actual, luego posteriores (sin repetir)
        $lista = array_values(array_unique(array_merge([$planillaActual->id], $posteriores)));

        return $lista;
    }
    /**
     * Candidatos de UNA planilla dada: mismo Ø, estado pendiente y stock.
     * Devuelve array de ['id' => subid, 'L' => longitud_cm, 'disponibles' => barras]
     */
    /**
     * Candidatos de UNA planilla dada: mismo Ø, estado pendiente, stock
     * y (opcionalmente) MISMA máquina.
     * Devuelve array de ['id' => subid, 'L' => longitud_cm, 'disponibles' => barras]
     */
    private function construirCandidatosEnPlanilla(
        int $planillaId,
        int $diametroMm,
        string $excluirSubId,
        ?int $maquinaIdObjetivo = null,
        ?int $barrasRequeridas = null
    ): array {
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

            // ⬇️ NUEVO: filtrar por MISMA máquina si se indicó
            if ($maquinaIdObjetivo !== null) {
                // intentamos en este orden: etiqueta.maquina_id, luego elemento.maquina_id
                $maquinaEtiquetaId = isset($otra->maquina_id) ? (int) $otra->maquina_id : null;
                $maquinaElementoId = isset($e->maquina_id)    ? (int) $e->maquina_id    : null;

                // si hay dato de máquina, debe coincidir con la objetivo
                $maquinaDetectada = $maquinaEtiquetaId ?? $maquinaElementoId;

                // si no se pudo detectar o no coincide, descartamos
                if ($maquinaDetectada === null || $maquinaDetectada !== $maquinaIdObjetivo) {
                    continue;
                }
            }

            if ((int) ($e->diametro ?? 0) !== $diametroMm) continue;

            $longitudCm  = (float) ($e->longitud ?? 0);       // en BD: cm
            $disponibles = (int)   max(0, (int) ($e->barras ?? 0));
            if ($longitudCm <= 0 || $disponibles <= 0) continue;

            // ⬇️ NUEVO: filtrar por MISMO número de barras si se indicó
            if ($barrasRequeridas !== null && $disponibles !== $barrasRequeridas) {
                continue;
            }

            $candidatos[] = [
                'id'          => $subId,
                'L'           => $longitudCm,
                'disponibles' => $disponibles,
            ];
        }

        return $candidatos;
    }
    public function calcularPatronCorteOptimizado(Request $request, string $etiquetaSubId)
    {
        /* ─────────────────────────────
        |  0) Parámetros / constantes
        ───────────────────────────── */
        $Kmax                 = (int) ($request->input('kmax') ?? 5);
        $EPS                  = 0.01;
        $UMBRAL_OK            = 95.0;
        $permitirRepeticiones = true;
        $kMinimo              = 2;

        /* ─────────────────────────────
            |  1) Cargar contexto + validar
            ───────────────────────────── */
        // 1) Cargar contexto + validar
        $etiquetaA = Etiqueta::with(['elementos', 'planilla.etiquetas.elementos'])
            ->where('etiqueta_sub_id', $etiquetaSubId)
            ->firstOrFail();

        $elementoA = $etiquetaA->elementos->first();
        if (!$elementoA) {
            return response()->json(['success' => false, 'message' => 'La subetiqueta A no tiene elementos.'], 400);
        }

        // 👉 nueva: máquina objetivo (cola donde está esta planilla)
        $maquinaObjetivoId = optional(
            OrdenPlanilla::where('planilla_id', $etiquetaA->planilla_id)->first()
        )->maquina_id;
        $maquinaObjetivoId = $maquinaObjetivoId ? (int) $maquinaObjetivoId : null;

        $longitudAcm = (float) ($elementoA->longitud ?? 0);   // cm
        $diametro    = (int)   ($elementoA->diametro ?? 0);
        $barrasA     = (int)   max(1, ($elementoA->barras ?? 1));
        if ($longitudAcm <= 0 || $diametro <= 0) {
            return response()->json(['success' => false, 'message' => 'Datos de A inválidos (longitud/diámetro).'], 400);
        }

        /* 2) Construir lista de planillas a explorar: actual + siguientes de la MISMA máquina */
        $planillasEnOrden = $this->obtenerPlanillasMismaMaquinaEnOrden($etiquetaA);

        /* 3) Longitudes de producto base (en cm), filtradas por MÁSCARA oficial */
        $longitudesCatalogoCm = $this->recogerLongitudesProductosBaseEnCm($diametro); // p. ej. [1200,1400,1500,1600]
        $mascara              = $this->mascaraDisponibilidadBarraCm();                 // p. ej. Ø10 => [1200,1400]
        $permitidasCm         = $mascara[$diametro] ?? [];

        // Intersección: solo probamos lo que existe en catálogo Y está permitido por la máscara
        $longitudesBarraCm = array_values(array_intersect(
            array_map('intval', $longitudesCatalogoCm),
            array_map('intval', $permitidasCm)
        ));
        sort($longitudesBarraCm, SORT_NUMERIC);

        if (empty($longitudesBarraCm)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay longitudes de barra permitidas por la máscara para este diámetro.',
                'detalle' => [
                    'diametro'              => $diametro,
                    'catalogo_cm'           => $longitudesCatalogoCm,
                    'mascara_cm'            => $permitidasCm,
                    'interseccion_resultado' => $longitudesBarraCm,
                ],
            ], 400);
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
        $progresoPorLongitud   = [];

        /* 5) Iterar planillas mientras falten patrones para el Top 3 */
        foreach ($planillasEnOrden as $planillaId) {
            if (count($topGlobal98) >= 3) break;

            // ⬇️ ahora filtrará por máquina y por mismo número de barras
            $candidatos = $this->construirCandidatosEnPlanilla(
                $planillaId,
                $diametro,
                $etiquetaSubId,
                $maquinaObjetivoId,
                $barrasA
            );
            if (empty($candidatos)) continue;
            // 5.2) Pre-carga de etiquetas para ESTA planilla (y A) → evita N+1
            $subIdsNecesarios = collect($candidatos)->pluck('id')->push($etiquetaSubId)->unique()->values();
            $mapaEtiquetas    = Etiqueta::with(['elementos', 'planilla'])
                ->whereIn('etiqueta_sub_id', $subIdsNecesarios)
                ->get()
                ->keyBy('etiqueta_sub_id');

            // 5.3) Ordenar candidatos por L desc (poda más efectiva)
            usort($candidatos, fn($a, $b) => $b['L'] <=> $a['L']);

            // 5.4) Explorar por longitudes (de menor a mayor) – YA filtradas por máscara
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

            if (count($topGlobal98) >= 3) break;
        }

        /* 6) Ordenar y completar grupos para canvas */
        usort($topGlobal98, $comparador);
        $topGlobal98 = array_slice($topGlobal98, 0, 3);

        $etiquetasPlanillas = [];
        if (!empty($topGlobal98)) {
            $todosSubIdsTop = collect($topGlobal98)->flatMap(fn($p) => array_keys($p['conteo_por_subid']))->unique()->values();
            $mapaGlobal = Etiqueta::with(['elementos', 'planilla'])
                ->whereIn('etiqueta_sub_id', $todosSubIdsTop)
                ->get()
                ->keyBy('etiqueta_sub_id');

            foreach ($topGlobal98 as &$pat) {
                $pat['grupos'] = $this->construirGruposParaCanvas($pat['conteo_por_subid'], $mapaGlobal);
            }
            unset($pat);

            // Construir mapa etiqueta -> planilla para el frontend
            foreach ($mapaGlobal as $subId => $etiqueta) {
                $etiquetasPlanillas[$subId] = $etiqueta->planilla?->codigo_limpio ?? null;
            }
        }

        $htmlResumen = $this->construirHtmlResumenMultiLongitudes($longitudesBarraCm, $progresoPorLongitud);

        /* ─────────────────────────────
        |  7) Responder
        ───────────────────────────── */
        return response()->json([
            'success'               => true,
            'longitudes_barra_cm'   => array_values($longitudesBarraCm),
            'top_global'            => $topGlobal98,
            'etiquetas_planillas'   => $etiquetasPlanillas,
            'progreso_por_longitud' => $progresoPorLongitud,
            'kmax'                  => $Kmax,
            'umbral_ok'             => $UMBRAL_OK,
            'permitio_repeticion'   => $permitirRepeticiones,
            'html_resumen'          => $htmlResumen,
            'longitud_barra_m'      => null,
            'longitud_barra_cm'     => null,
        ]);
    }


    /* ============================================================
   =              HELPERS PRIVADOS / UTILIDADES               =
   ============================================================ */


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
     * Máscara oficial de disponibilidad para tipo "barra".
     * Devuelve longitudes PERMITIDAS en **centímetros** por diámetro.
     * Úsala para filtrar/validar antes de optimizar el corte.
     */
    private function mascaraDisponibilidadBarraCm(): array
    {
        return [
            8  => [],                          // Ø8 → no disponible en ninguna longitud
            10 => [1200, 1400],                // Ø10 → solo 12 m y 14 m
            12 => [1200, 1400, 1500, 1600],    // Ø12 → 12/14/15/16 m
            16 => [1200, 1400, 1500, 1600],    // Ø16 → 12/14/15/16 m
            20 => [1200, 1400, 1500, 1600],    // Ø20 → 12/14/15/16 m
            25 => [1200, 1400, 1500, 1600],    // Ø25 → 12/14/15/16 m
            32 => [1200, 1400, 1500, 1600],    // Ø32 → 12/14/15/16 m
        ];
    }
    /**
     * Construye letras por subid respetando el orden de aparición en la SECUENCIA.
     * - A está reservado para $subIdA
     * - El resto: B, C, D... según vayan apareciendo en $secuenciaSubIds.
     * Devuelve:
     *   - mapa_letras       [subid => 'A'|'B'|'C'...]
     *   - secuencia_letras  ['A','B','B','C']
     *   - esquema           "A+B+B+C"
     *   - resumen_letras    "A + B×2 + C"
     */
    private function esquemaDesdeSecuencia(array $secuenciaSubIds, string $subIdA): array
    {
        // 1) mapa de letras
        $mapa = [$subIdA => 'A'];
        $next = 'B';
        foreach ($secuenciaSubIds as $sid) {
            if (!isset($mapa[$sid])) {
                $mapa[$sid] = $next;
                $next = chr(ord($next) + 1); // B->C->D...
            }
        }

        // 2) secuencia de letras fiel al orden
        $seqLetras = array_map(fn($sid) => $mapa[$sid], $secuenciaSubIds);

        // 3) esquema "A+B+B+C"
        $esquema = implode('+', $seqLetras);

        // 4) resumen "A + B×2 + C"
        $conteo = [];
        foreach ($seqLetras as $L) $conteo[$L] = ($conteo[$L] ?? 0) + 1;
        ksort($conteo);
        $trozos = [];
        foreach ($conteo as $L => $n) {
            $trozos[] = $n > 1 ? "{$L}×{$n}" : $L;
        }
        $resumen = implode(' + ', $trozos);

        return [
            'mapa_letras'      => $mapa,
            'secuencia_letras' => $seqLetras,
            'esquema'          => $esquema,
            'resumen_letras'   => $resumen,
        ];
    }

    /**
     * Formatea la secuencia de subids para mostrar tanto secuencia como resumen.
     *  - "ETQ... + ETQ... + ETQ..."
     *  - "ETQ... × 2 + ETQ..."
     */
    private function formatearSecuenciaEtiquetas(array $secuenciaSubIds): array
    {
        $secuenciaHumana = implode(' + ', $secuenciaSubIds);

        $conteo = [];
        foreach ($secuenciaSubIds as $sid) {
            $conteo[$sid] = ($conteo[$sid] ?? 0) + 1;
        }
        $trozos = [];
        foreach ($conteo as $sid => $n) {
            $trozos[] = $n > 1 ? "{$sid} × {$n}" : $sid;
        }
        $resumenHumano = implode(' + ', $trozos);

        return [
            'secuencia_humana' => $secuenciaHumana,
            'resumen_humano'   => $resumenHumano,
        ];
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
        $patrones98 = [];
        $mejorLocal = null;

        foreach ($candidatos as $cand) {
            $suma = $longitudAcm + $cand['L'];
            if ($suma > $longitudBarraCm) continue;

            $aprov = round(($suma / $longitudBarraCm) * 100, 2);
            $sobra = round($longitudBarraCm - $suma, 2);

            // SECUENCIA real en orden (A, B)
            $secuenciaIds = [$subIdA, $cand['id']];
            $infoEsq      = $this->esquemaDesdeSecuencia($secuenciaIds, $subIdA);
            $infoEtiq     = $this->formatearSecuenciaEtiquetas($secuenciaIds);

            $patron = [
                'longitud_barra_cm'   => (int) $longitudBarraCm,
                'k'                   => 2,
                'etiquetas'           => $secuenciaIds,                // orden real
                'conteo_por_subid'    => $this->contarMultiset($secuenciaIds),
                'longitudes_cm'       => [$longitudAcm, $cand['L']],
                'total_cm'            => $suma,
                'sobra_cm'            => $sobra,
                'aprovechamiento'     => $aprov,
                'max_long_cm'         => max($longitudAcm, $cand['L']),
                'patron_humano'       => number_format($longitudAcm, 2, ',', '.') . ' + ' . number_format($cand['L'], 2, ',', '.') . ' = ' . number_format($suma, 2, ',', '.'),
                // NUEVO: campos de esquema/etiquetas
                'mapa_letras'         => $infoEsq['mapa_letras'],
                'secuencia_letras'    => $infoEsq['secuencia_letras'],
                'esquema'             => $infoEsq['esquema'],          // "A+B"
                'resumen_letras'      => $infoEsq['resumen_letras'],   // "A + B"
                'etiquetas_secuencia' => $infoEtiq['secuencia_humana'],
                'etiquetas_resumen'   => $infoEtiq['resumen_humano'],
                // clave (multiset) para deduplicar combinaciones, no el orden
                'clave_estable'       => $this->claveCombinacion($secuenciaIds),
            ];

            if ($aprov >= $UMBRAL_OK) {
                $patrones98[] = $patron;
            }
            if (!$mejorLocal || $comparador($patron, $mejorLocal) < 0) {
                $mejorLocal = $patron;
            }
        }

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

        // stock por subid (candidatos)
        $stock = [];
        foreach ($candidatos as $c) {
            $stock[$c['id']] = $c['disponibles'];
        }

        // longitudes por subid
        $LporSub = [];
        foreach ($candidatos as $c) {
            $LporSub[$c['id']] = $c['L'];
        }
        $LporSub[$subIdA] = $longitudAcm;

        $seleccion = []; // subids (sin A)
        $usos      = [];
        $sumaSel   = 0.0;

        // orden por L desc para podar
        $subidsOrdenados = array_keys($LporSub);
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
            $subidsOrdenados,
            $comparador
        ) {
            $kActual = 1 + count($seleccion); // +A
            $sumaActual = $longitudAcm + $sumaSel;
            if ($sumaActual > $longitudBarraCm) return;

            if ($kActual === $kObjetivo) {
                // secuencia real: A seguido de selección en ese orden
                $ids = array_merge([$subIdA], $seleccion);
                $total = $sumaActual;
                $aprov = round(($total / $longitudBarraCm) * 100, 2);
                $sobra = round($longitudBarraCm - $total, 2);

                if ($aprov >= $UMBRAL_OK) {
                    $longitudes = array_map(fn($sid) => $LporSub[$sid], $ids);

                    // NUEVO: esquema y textos fieles a la secuencia
                    $infoEsq  = $this->esquemaDesdeSecuencia($ids, $subIdA);
                    $infoEtiq = $this->formatearSecuenciaEtiquetas($ids);

                    $resultados[] = [
                        'longitud_barra_cm'   => (int) $longitudBarraCm,
                        'k'                   => $kObjetivo,
                        'etiquetas'           => $ids,                         // orden real
                        'conteo_por_subid'    => $this->contarMultiset($ids),  // para canvas
                        'longitudes_cm'       => $longitudes,
                        'total_cm'            => $total,
                        'sobra_cm'            => $sobra,
                        'aprovechamiento'     => $aprov,
                        'max_long_cm'         => max($longitudes),
                        'patron_humano'       => implode(' + ', array_map(fn($x) => number_format($x, 2, ',', '.'), $longitudes)) . ' = ' . number_format($total, 2, ',', '.'),
                        'mapa_letras'         => $infoEsq['mapa_letras'],
                        'secuencia_letras'    => $infoEsq['secuencia_letras'],
                        'esquema'             => $infoEsq['esquema'],          // "A+B+B+C"
                        'resumen_letras'      => $infoEsq['resumen_letras'],   // "A + B×2 + C"
                        'etiquetas_secuencia' => $infoEtiq['secuencia_humana'],
                        'etiquetas_resumen'   => $infoEtiq['resumen_humano'],
                        // dedupe por multiset (orden-insensible)
                        'clave_estable'       => $this->claveCombinacion($ids),
                    ];
                }
                return;
            }

            foreach ($subidsOrdenados as $sid) {
                // reglas de stock (A limitado por $repeticionesA)
                if ($sid === $subIdA) {
                    $usadosA = $usos[$sid] ?? 0;
                    if ($usadosA >= $repeticionesA) continue;
                } else {
                    $usados = $usos[$sid] ?? 0;
                    $disp   = $stock[$sid] ?? 0;
                    if ($usados >= $disp) continue;
                }

                // elegir
                $seleccion[] = $sid;
                $usos[$sid]  = ($usos[$sid] ?? 0) + 1;
                $sumaSel    += $LporSub[$sid];

                // poda por suma
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

        usort($resultados, $comparador);
        return $resultados;
    }


    /**
     * Inserta patrones en TopLocal y TopGlobal con deduplicación por combinación multiset.
     * Descarta patrones que solo contengan una etiqueta (ej: A+A+A) - deben combinar al menos 2 etiquetas diferentes.
     */
    private function acumularPatrones(array $encontrados, array &$topLocal98, array &$topGlobal98, array &$combinacionesYaVistas, callable $comparador): void
    {
        foreach ($encontrados as $p) {
            // Descartar patrones que solo tienen una etiqueta única (ej: A+A+A)
            // Los patrones optimizados deben combinar al menos 2 etiquetas diferentes
            $etiquetasUnicas = count(array_keys($p['conteo_por_subid'] ?? []));
            if ($etiquetasUnicas < 2) {
                continue;
            }

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
                  <div class='text-[11px] text-gray-500'>
                k={$p['k']}, esquema: {$p['esquema']}" . (!empty($p['resumen_letras']) ? " ({$p['resumen_letras']})" : "") . "
                </div>
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
    public function render(Request $r)
    {
        $etiqueta = Etiqueta::with('elementos')->findOrFail($r->input('id'));
        $html = view('components.etiqueta.etiqueta', [
            'etiqueta' => $etiqueta,
            'planilla' => $etiqueta->planilla,
            'maquina_tipo' => $r->input('maquina_tipo'),
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function fabricacionSyntaxLine28(Request $request)
    {
        try {
            $data = $request->validate([
                'producto_base.longitud_barra_cm' => ['required', 'numeric', 'min:1'],
                'repeticiones' => ['required', 'integer', 'min:1'],
                'desperdicio_manual_cm' => ['nullable', 'numeric', 'min:0'],
                'etiquetas' => ['required', 'array', 'min:1'],
                'etiquetas.*.etiqueta_sub_id' => ['required', 'string'],
                'etiquetas.*.patron_letras' => ['nullable', 'string', 'max:100'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        }

        $longitud = (int) $data['producto_base']['longitud_barra_cm'];
        $desperdicioManualCm = $data['desperdicio_manual_cm'] ?? null;
        $userId = Auth::id();
        $compaId = auth()->user()->compañeroDeTurno()?->id;
        $resultados = [];
        $maquina = null; // Para usar después en el logging

        DB::beginTransaction();
        try {
            $fabrica = app(\App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio::class);

            foreach ($data['etiquetas'] as $item) {
                $subId = $item['etiqueta_sub_id'];

                $maquinaId = Elemento::where('etiqueta_sub_id', $subId)->value('maquina_id');
                if (!$maquinaId) throw new \RuntimeException("Sin máquina para {$subId}");

                $maquina = Maquina::findOrFail($maquinaId);
                $patronLetras = $item['patron_letras'] ?? null;
                $dto = new \App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos(
                    etiquetaSubId: $subId,
                    maquinaId: $maquinaId,
                    longitudSeleccionada: $longitud,
                    operario1Id: $userId,
                    operario2Id: $compaId,
                    opciones: [
                        'origen' => 'optimizada',
                        'patron_letras' => $patronLetras,
                        'desperdicio_manual_cm' => $desperdicioManualCm,
                    ]
                );

                $resultado = $fabrica->porMaquina($maquina)->actualizar($dto);

                $resultados[] = [
                    'etiqueta_sub_id' => $subId,
                    'estado' => $resultado->etiqueta->estado ?? null,
                    'warnings' => $resultado->warnings ?? [],
                ];
            }

            DB::commit();

            // 🔧 REGISTRAR LOG DE PATRÓN DE CORTE - SYNTAX LINE
            // Se hace DESPUÉS del commit para que los elementos tengan productos asignados
            if ($maquina) {
                \App\Services\ProductionLogger::logCortePatron(
                    etiquetas: $data['etiquetas'],
                    longitudBarraCm: $longitud,
                    maquina: $maquina,
                    tipoPatron: 'optimizado',
                    patronInfo: []
                );
            }

            // ✅ Calcular el peso total de la etiqueta
            $pesoTotalEtiqueta = $resultado->etiqueta->peso
                ?? $resultado->etiqueta->elementos->sum('peso')
                ?? 0;

            // ✅ Obtener información de coladas para actualizar UI sin recargar
            $etiqueta = $resultado->etiqueta;
            $etiqueta->load('producto');
            $productoNColada = $etiqueta->producto?->n_colada ?? null;
            $producto2NColada = null;
            if ($etiqueta->producto_id_2) {
                $producto2 = Producto::find($etiqueta->producto_id_2);
                $producto2NColada = $producto2?->n_colada;
            }

            return response()->json([
                'success' => true,
                'estado' => $etiqueta->estado ?? null,
                'peso_etiqueta' => $pesoTotalEtiqueta,
                'fecha_inicio' => $etiqueta->fecha_inicio,
                'fecha_finalizacion' => $etiqueta->fecha_finalizacion,
                'productos_afectados' => $resultado->productosAfectados ?? [],
                'warnings' => $resultado->warnings ?? [],
                'producto_n_colada' => $productoNColada,
                'producto2_n_colada' => $producto2NColada,
            ]);
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

            // 🔍 Obtener estado anterior para logs
            $etiquetaAntes = Etiqueta::where('etiqueta_sub_id', $id)->first();
            $estadoAnterior = $etiquetaAntes ? $etiquetaAntes->estado : 'pendiente';
            $fechaInicio = $etiquetaAntes ? $etiquetaAntes->fecha_inicio : null;

            // Construir opciones (para grúa: producto_id y paquete_completo)
            $opciones = [];
            if ($request->has('producto_id')) {
                $opciones['producto_id'] = (int) $request->input('producto_id');
            }
            if ($request->has('paquete_completo')) {
                $opciones['paquete_completo'] = (bool) $request->input('paquete_completo');
            }

            $dto = new \App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos(
                etiquetaSubId: $id,
                maquinaId: (int) $maquina_id,
                longitudSeleccionada: (int) $request->input('longitudSeleccionada'),
                operario1Id: Auth::id(),
                operario2Id: auth()->user()->compañeroDeTurno()?->id,
                opciones: $opciones
            );

            /** @var \App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio $fabrica */
            $fabrica = app(\App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio::class);
            $servicio = $fabrica->porMaquina($maquina);

            $resultado = $servicio->actualizar($dto);
            $etiqueta = $resultado->etiqueta;

            // 🔄 Refrescar la etiqueta desde la base de datos para obtener el peso actualizado
            $etiqueta->refresh();
            $etiqueta->load(['producto', 'producto2']);

            // Extraer coladas únicas de los productos afectados
            $coladas = collect($resultado->productosAfectados)
                ->pluck('n_colada')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // Recargar elementos con productos para obtener las coladas por elemento
            $etiqueta->load(['elementos.producto', 'elementos.producto2', 'elementos.producto3']);

            // Construir mapeo de coladas por elemento
            $coladasPorElemento = [];
            foreach ($etiqueta->elementos as $elemento) {
                $coladasElemento = [];

                if ($elemento->producto && $elemento->producto->n_colada) {
                    $coladasElemento[] = $elemento->producto->n_colada;
                }
                if ($elemento->producto2 && $elemento->producto2->n_colada) {
                    $coladasElemento[] = $elemento->producto2->n_colada;
                }
                if ($elemento->producto3 && $elemento->producto3->n_colada) {
                    $coladasElemento[] = $elemento->producto3->n_colada;
                }

                if (!empty($coladasElemento)) {
                    $coladasPorElemento[$elemento->id] = array_values(array_unique($coladasElemento));
                }
            }

            // Log de actualización con coladas
            if (in_array($etiqueta->estado, ['fabricada', 'completada']) && !empty($coladas)) {
                $logger = app(\App\Services\ActionLoggerService::class);
                $logger->logMaquinas('etiqueta_fabricada', [
                    'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                    'estado' => $etiqueta->estado,
                    'coladas' => implode(', ', $coladas),
                    'peso' => $etiqueta->peso,
                    'maquina_id' => $maquina->id,
                    'maquina_nombre' => $maquina->nombre,
                ]);
            }

            // 📊 LOG DE PRODUCCIÓN EN CSV
            $operario1 = Auth::user();
            $operario2 = $operario1->compañeroDeTurno();

            // Log según tipo de cambio de estado
            if ($estadoAnterior === 'pendiente' && $etiqueta->estado === 'fabricando') {
                // Inicio de fabricación
                \App\Services\ProductionLogger::logInicioFabricacion(
                    $etiqueta,
                    $maquina,
                    $operario1,
                    $operario2
                );
            } elseif ($estadoAnterior !== $etiqueta->estado) {
                // Cambio de estado durante fabricación
                \App\Services\ProductionLogger::logCambioEstadoFabricacion(
                    $etiqueta,
                    $estadoAnterior,
                    $etiqueta->estado,
                    $maquina,
                    $resultado->productosAfectados ?? [],
                    $coladas,
                    $fechaInicio ? \Carbon\Carbon::parse($fechaInicio) : null,
                    $etiqueta->fecha_finalizacion ? \Carbon\Carbon::parse($etiqueta->fecha_finalizacion) : null
                );
            }

            // Determinar si esta máquina es secundaria para la etiqueta
            $esSecundaria = $etiqueta->elementos()
                ->where('maquina_id_2', $maquina->id)
                ->exists();

            // Para máquinas secundarias, devolver estado2 como el estado relevante
            $estadoRelevante = $esSecundaria ? ($etiqueta->estado2 ?? 'pendiente') : $etiqueta->estado;

            return response()->json([
                'success' => true,
                'estado' => $estadoRelevante,
                'estado_principal' => $etiqueta->estado,
                'estado2' => $etiqueta->estado2,
                'es_secundaria' => $esSecundaria,
                'peso_etiqueta' => $etiqueta->peso,
                'nombre' => $etiqueta->etiqueta_sub_id,
                'productos_afectados' => $resultado->productosAfectados,
                'coladas' => $coladas,
                'coladas_por_elemento' => $coladasPorElemento,
                // Coladas de la etiqueta (asignadas en primer y segundo clic)
                'producto_n_colada' => $etiqueta->producto?->n_colada,
                'producto2_n_colada' => $etiqueta->producto2?->n_colada,
                'warnings' => $resultado->warnings,
                'metricas' => $resultado->metricas,
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
            $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('elaborado', 1)->count();
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

            // Si por alguna razón no hay diametros (p.ej. diametro null en elementos), intenta derivarlos
            if (empty($diametrosRequeridos)) {
                $derivados = $elementosEnMaquina->pluck('diametro')
                    ->filter(fn($d) => $d !== null && $d !== '')
                    ->map(fn($d) => (int) round((float) $d))
                    ->unique()
                    ->values()
                    ->all();
                $diametrosRequeridos = $derivados;
            }
            // -------------------------------------------- ESTADO PENDIENTE --------------------------------------------
            switch ($etiqueta->estado) {
                case 'pendiente':
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
                                });
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

                                    } catch (\Throwable $e) {
                                        Log::error('❌ Error al solicitar recarga (déficit previsto)', [
                                            'maquina_id'       => $maquina->id,
                                            'producto_base_id' => $productoBase->id ?? null,
                                            'diametro'         => $dInsuf,
                                            'deficit_kg'       => $deficitKg,
                                            'error'            => $e->getMessage(),
                                        ]);
                                    }
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

                    // 🔄 GUARDAR HISTORIAL ANTES DE CAMBIOS (para sistema UNDO)
                    $historialResult = EtiquetaHistorial::registrarCambio(
                        $etiqueta,
                        'iniciar_fabricacion',
                        'fabricando',
                        $maquina->id,
                        Auth::id(),
                        [] // No hay consumo de productos en este paso
                    );

                    if ($etiqueta->planilla) {
                        $etiqueta->planilla->estado = "fabricando";
                        $etiqueta->planilla->save();
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
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'soldadora') {
                        // Si la máquina es de tipo soldadora, se inicia la fase de soldadura:
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'dobladora_manual') {
                        // Si la máquina es de tipo dobladora manual, se inicia la fase de doblado:
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'doblando';
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
                    }
                    break;

                // -------------------------------------------- ESTADO ENSAMBLANDO --------------------------------------------
                case 'ensamblando':

                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id =  $operario1;
                        $elemento->users_id_2 =  $operario2;
                        $elemento->elaborado = 1;
                        $elemento->save();
                    }
                    // Verificar si todos los elementos de la etiqueta están elaborados
                    $elementosEtiquetaCompletos = $etiqueta->elementos()
                        ->where('elaborado', '!=', 1)
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

                        $elemento->elaborado = 1;

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
            $elemento->elaborado = 1;
            $elemento->save();
        }

        // ✅ ACTUALIZAR EL CONTADOR DE ELEMENTOS COMPLETADOS
        $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('elaborado', 1)->count();

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
            // Verificar si todos los elementos de la etiqueta están elaborados
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('elaborado', '!=', 1)->doesntExist();
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
            }
        } elseif (str_contains($ensambladoText, 'carcasas')) {
            $elementosEtiquetaCompletos = $etiqueta->elementos()
                ->where('diametro', '!=', 5.00)
                ->where('elaborado', 0)
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
                    $algunoAsignado = false;
                    foreach ($elementosEnMaquina as $elemento) {
                        if (is_null($elemento->maquina_id_2)) {
                            $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                            $elemento->save();
                            $algunoAsignado = true;
                        }
                    }
                    // Actualizar estado2 de la etiqueta si se asignó algún elemento
                    if ($algunoAsignado && is_null($etiqueta->estado2)) {
                        $etiqueta->estado2 = 'pendiente';
                        $etiqueta->save();
                    }
                }
            }
        } else {

            // 🧠 Regla especial: si el nombre de la etiqueta contiene "pates"
            if (Str::of($etiqueta->nombre ?? '')->lower()->contains('pates')) {
                DB::transaction(function () use ($etiqueta, $maquina) {
                    // 1) Marcar etiqueta como "fabricada" y cerrar fecha
                    $etiqueta->estado = 'fabricada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();

                    // 2) Buscar una máquina tipo "dobladora_manual"
                    $dobladora = Maquina::where('tipo', 'dobladora_manual')
                        // si quieres priorizar la misma obra:
                        ->when($maquina->obra_id, fn($q) => $q->where('obra_id', $maquina->obra_id))
                        ->orderBy('id')
                        ->first();

                    if ($dobladora) {
                        // 3) Asignar maquina_id_2 a TODOS los elementos de esa etiqueta en ESTA máquina
                        Elemento::where('etiqueta_sub_id', $etiqueta->etiqueta_sub_id)
                            ->where('maquina_id', $maquina->id)
                            ->update(['maquina_id_2' => $dobladora->id]);

                        // Actualizar estado2 de la etiqueta
                        $etiqueta->estado2 = 'pendiente';
                        $etiqueta->save();
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
                            \App\Servicios\Etiquetas\Base\ServicioEtiquetaBase::encolarPlanillaEnMaquina($dobladora->id, $planillaId);
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
            ->where('elaborado', 0)
            ->exists();

        // ❌ DESHABILITADO: La verificación automática de paquetes y eliminación de planilla
        // ahora se hace manualmente desde la vista de máquina con el botón "Planilla Completada"
        /*
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
        */

        // ✅ Si todos los elementos de la planilla están completados, actualizar la planilla
        $todosElementosPlanillaCompletos = $planilla->elementos()
            ->where('elaborado', 0)
            ->doesntExist();

        if ($todosElementosPlanillaCompletos) {
            // Determinar si la planilla tiene ensamblaje en taller
            $tieneEnsamblajeTaller = $planilla->tieneEnsamblajeTaller();

            if ($tieneEnsamblajeTaller) {
                // Si tiene ensamblaje en taller → estado 'fabricada' (pendiente de ensamblar)
                $planilla->estado = 'fabricada';
                $planilla->save();
            } else {
                // Si NO tiene ensamblaje en taller → estado 'completada'
                $planilla->estado = 'completada';
                $planilla->save();

                // ❌ DESHABILITADO: La eliminación de la cola ahora es manual
                // con el botón "Planilla Completada" en la vista de máquina
                /*
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
                */
            }
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
                'nave_id'           => $maquina->obra_id, // Nave donde se ejecuta el movimiento
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

            // Normalizar campos: la tabla envía 'peso' pero el modelo usa 'peso'
            // Aceptamos tanto 'peso_kg' como 'peso'
            $pesoValue = $request->peso_kg ?? $request->peso ?? null;
            // Limpiar el valor de peso: si es vacío o no numérico, convertir a null
            if ($pesoValue === '' || $pesoValue === null || (!is_numeric($pesoValue) && !is_float($pesoValue))) {
                $pesoValue = null;
            }
            $request->merge(['peso_kg' => $pesoValue]);

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
            // Aceptamos fechas en formato Y-m-d (desde inputs date) o d/m/Y
            $validatedData = $request->validate([
                'numero_etiqueta'          => 'nullable|string|max:50',
                'nombre'                   => 'nullable|string|max:255',
                'marca'                    => 'nullable|string|max:100',
                'peso_kg'                  => 'nullable|numeric',
                'fecha_inicio'             => 'nullable|date',
                'fecha_finalizacion'       => 'nullable|date',
                'fecha_inicio_ensamblado'  => 'nullable|date',
                'fecha_finalizacion_ensamblado' => 'nullable|date',
                'fecha_inicio_soldadura'   => 'nullable|date',
                'fecha_finalizacion_soldadura' => 'nullable|date',
                'estado'                   => 'nullable|string|in:pendiente,fabricando,ensamblando,soldando,completada,empaquetada'
            ], [
                'numero_etiqueta.string'   => 'El campo Número de Etiqueta debe ser una cadena de texto.',
                'numero_etiqueta.max'      => 'El campo Número de Etiqueta no debe exceder 50 caracteres.',

                'nombre.string'            => 'El campo Nombre debe ser una cadena de texto.',
                'nombre.max'               => 'El campo Nombre no debe exceder 255 caracteres.',

                'peso_kg.numeric'          => 'El campo Peso debe ser un número.',

                'fecha_inicio.date'             => 'El campo Fecha Inicio no es una fecha válida.',
                'fecha_finalizacion.date'       => 'El campo Fecha Finalización no es una fecha válida.',
                'fecha_inicio_ensamblado.date'    => 'El campo Fecha Inicio Ensamblado no es una fecha válida.',
                'fecha_finalizacion_ensamblado.date' => 'El campo Fecha Finalización Ensamblado no es una fecha válida.',
                'fecha_inicio_soldadura.date'     => 'El campo Fecha Inicio Soldadura no es una fecha válida.',
                'fecha_finalizacion_soldadura.date' => 'El campo Fecha Finalización Soldadura no es una fecha válida.',
                'estado.in'              => 'El campo Estado no es válido.'
            ]);

            // Convertir peso_kg a peso para el modelo
            if (isset($validatedData['peso_kg'])) {
                $validatedData['peso'] = $validatedData['peso_kg'];
                unset($validatedData['peso_kg']);
            }

            // Convertir las fechas al formato 'Y-m-d' si vienen en otro formato
            $camposFecha = ['fecha_inicio', 'fecha_finalizacion', 'fecha_inicio_ensamblado',
                           'fecha_finalizacion_ensamblado', 'fecha_inicio_soldadura', 'fecha_finalizacion_soldadura'];

            foreach ($camposFecha as $campo) {
                if (!empty($validatedData[$campo])) {
                    try {
                        // Intentar parsear la fecha (acepta múltiples formatos)
                        $validatedData[$campo] = Carbon::parse($validatedData[$campo])->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Si falla, dejar null
                        $validatedData[$campo] = null;
                    }
                }
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

    // ============================================================================
    // SISTEMA DE DESHACER (UNDO)
    // ============================================================================

    /**
     * Deshace el último cambio de una etiqueta
     * Revierte estado, elementos, productos consumidos y planilla si aplica
     *
     * @param string $etiquetaSubId El etiqueta_sub_id de la etiqueta
     * @return \Illuminate\Http\JsonResponse
     */
    public function deshacerEtiqueta(string $etiquetaSubId)
    {
        try {
            // Obtener la etiqueta actual
            $etiqueta = Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();

            if (!$etiqueta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Etiqueta no encontrada.',
                    'puede_deshacer' => false,
                ], 404);
            }

            // Verificar si hay cambios que deshacer en el historial
            $ultimoCambio = EtiquetaHistorial::ultimoCambio($etiquetaSubId);

            // CASO ESPECIAL: No hay historial pero la etiqueta está en "fabricando"
            // Esto puede pasar con etiquetas que iniciaron fabricación antes de implementar el historial
            if (!$ultimoCambio && $etiqueta->estado === 'fabricando') {
                return $this->deshacerFabricandoSinHistorial($etiqueta);
            }

            if (!$ultimoCambio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay cambios que deshacer para esta etiqueta.',
                    'puede_deshacer' => false,
                ], 400);
            }

            // Ejecutar la reversión
            $resultado = $ultimoCambio->revertir(Auth::id());

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'],
                ], 400);
            }

            // Obtener la etiqueta actualizada
            $etiqueta = Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();

            // Verificar si hay más cambios que deshacer
            $puedeDeshacer = EtiquetaHistorial::puedeDeshacer($etiquetaSubId);
            $proximoCambio = $puedeDeshacer ? EtiquetaHistorial::ultimoCambio($etiquetaSubId) : null;

            return response()->json([
                'success' => true,
                'message' => $resultado['message'],
                'estado' => $etiqueta->estado ?? $ultimoCambio->snapshot_etiqueta['estado'],
                'estado_anterior' => $ultimoCambio->estado_nuevo,
                'cambios_realizados' => $resultado['cambios'],
                'puede_deshacer' => $puedeDeshacer,
                'proximo_estado' => $proximoCambio ? $proximoCambio->snapshot_etiqueta['estado'] : null,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al deshacer etiqueta', [
                'etiqueta_sub_id' => $etiquetaSubId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer el cambio: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deshace una etiqueta en estado "fabricando" cuando no hay historial
     * Esto maneja el caso de etiquetas que iniciaron fabricación antes de implementar el sistema de historial
     *
     * @param Etiqueta $etiqueta
     * @return \Illuminate\Http\JsonResponse
     */
    private function deshacerFabricandoSinHistorial(Etiqueta $etiqueta): \Illuminate\Http\JsonResponse
    {
        return DB::transaction(function () use ($etiqueta) {
            $cambios = [];

            // 1. Restaurar elementos a estado pendiente y limpiar operarios
            $elementosActualizados = $etiqueta->elementos()
                ->where('estado', 'fabricando')
                ->update([
                    'estado' => 'pendiente',
                    'users_id' => null,
                    'users_id_2' => null,
                ]);
            $cambios[] = "Elementos restaurados: {$elementosActualizados}";

            // 2. Restaurar etiqueta a pendiente
            $etiqueta->update([
                'estado' => 'pendiente',
                'fecha_inicio' => null,
                'operario1_id' => null,
                'operario2_id' => null,
            ]);
            $cambios[] = "Etiqueta restaurada a estado: pendiente";

            // 3. Verificar si la planilla debe volver a pendiente
            if ($etiqueta->planilla) {
                $planilla = $etiqueta->planilla->fresh();

                // Si ninguna etiqueta está en proceso, restaurar planilla a pendiente
                $hayEtiquetasEnProceso = $planilla->etiquetas()
                    ->whereIn('estado', ['fabricando', 'completada', 'fabricada', 'en-paquete'])
                    ->exists();

                if (!$hayEtiquetasEnProceso && $planilla->estado !== 'pendiente') {
                    $planilla->update([
                        'estado' => 'pendiente',
                        'fecha_inicio' => null,
                        'fecha_finalizacion' => null,
                    ]);
                    $cambios[] = "Planilla restaurada a estado: pendiente";
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cambio revertido exitosamente (sin historial previo). Estado restaurado a: pendiente',
                'estado' => 'pendiente',
                'estado_anterior' => 'fabricando',
                'cambios_realizados' => $cambios,
                'puede_deshacer' => false,
                'proximo_estado' => null,
            ], 200);
        });
    }

    /**
     * Obtiene el historial de cambios de una etiqueta
     *
     * @param string $etiquetaSubId
     * @return \Illuminate\Http\JsonResponse
     */
    public function historialEtiqueta(string $etiquetaSubId)
    {
        try {
            $historial = EtiquetaHistorial::where('etiqueta_sub_id', $etiquetaSubId)
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'accion' => $item->accion,
                        'estado_anterior' => $item->estado_anterior,
                        'estado_nuevo' => $item->estado_nuevo,
                        'descripcion' => $item->descripcion,
                        'fecha' => $item->created_at->format('d/m/Y H:i:s'),
                        'revertido' => $item->revertido,
                        'puede_revertir' => !$item->revertido,
                    ];
                });

            $puedeDeshacer = EtiquetaHistorial::puedeDeshacer($etiquetaSubId);
            $ultimoCambio = $puedeDeshacer ? EtiquetaHistorial::ultimoCambio($etiquetaSubId) : null;

            return response()->json([
                'success' => true,
                'historial' => $historial,
                'puede_deshacer' => $puedeDeshacer,
                'ultimo_estado_reversible' => $ultimoCambio ? $ultimoCambio->snapshot_etiqueta['estado'] : null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifica si una etiqueta puede deshacer cambios
     *
     * @param string $etiquetaSubId
     * @return \Illuminate\Http\JsonResponse
     */
    public function puedeDeshacer(string $etiquetaSubId)
    {
        $etiqueta = Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)->first();
        $estadoActual = $etiqueta?->estado;

        $puedeDeshacer = EtiquetaHistorial::puedeDeshacer($etiquetaSubId);
        $ultimoCambio = $puedeDeshacer ? EtiquetaHistorial::ultimoCambio($etiquetaSubId) : null;

        // Caso especial: etiqueta en fabricando sin historial (legacy)
        $puedeUndoLegacy = !$puedeDeshacer && $estadoActual === 'fabricando';

        return response()->json([
            'puede_deshacer' => $puedeDeshacer || $puedeUndoLegacy,
            'estado_actual' => $estadoActual,
            'estado_anterior' => $ultimoCambio
                ? $ultimoCambio->snapshot_etiqueta['estado']
                : ($puedeUndoLegacy ? 'pendiente' : null),
            'accion_a_deshacer' => $ultimoCambio
                ? $ultimoCambio->accion
                : ($puedeUndoLegacy ? 'iniciar_fabricacion (legacy)' : null),
        ]);
    }

    /**
     * Obtener la longitud de barra del producto asignado a una etiqueta.
     * Se usa en el segundo clic de fabricación cuando la decisión de patrón no está en memoria.
     */
    public function longitudAsignada(string $etiquetaSubId): JsonResponse
    {
        $etiqueta = Etiqueta::where('etiqueta_sub_id', $etiquetaSubId)
            ->with('producto.productoBase')
            ->first();

        if (!$etiqueta) {
            return response()->json(['error' => 'Etiqueta no encontrada'], 404);
        }

        if (!$etiqueta->producto_id) {
            return response()->json(['error' => 'No hay producto asignado'], 404);
        }

        $producto = $etiqueta->producto;
        if (!$producto || !$producto->productoBase) {
            return response()->json(['error' => 'Producto sin base'], 404);
        }

        $longitudM = $producto->productoBase->longitud; // en metros
        $longitudCm = $longitudM ? (int) ($longitudM * 100) : null;

        return response()->json([
            'longitud_barra_m' => $longitudM,
            'longitud_barra_cm' => $longitudCm,
            'producto_id' => $producto->id,
            'producto_codigo' => $producto->codigo,
            'diametro' => $producto->productoBase->diametro,
        ]);
    }

    /**
     * Renderiza múltiples etiquetas para impresión.
     * Útil cuando las etiquetas no están en el DOM (ej: grupos multiplanilla).
     * POST /etiquetas/render-multiple
     */
    public function renderMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'etiqueta_sub_ids' => 'required|array|min:1',
            'etiqueta_sub_ids.*' => 'required|string',
            'maquina_tipo' => 'nullable|string',
        ]);

        $etiquetaSubIds = $request->input('etiqueta_sub_ids');
        $maquinaTipo = $request->input('maquina_tipo', 'barra');

        $etiquetas = Etiqueta::with([
                'planilla.obra',
                'planilla.cliente',
                'elementos.producto',
                'elementos.producto2',
                'elementos.producto3',
                'paquete'
            ])
            ->whereIn('etiqueta_sub_id', $etiquetaSubIds)
            ->get()
            ->keyBy('etiqueta_sub_id');

        $resultado = [];

        foreach ($etiquetaSubIds as $subId) {
            $etiqueta = $etiquetas->get($subId);

            if (!$etiqueta) {
                $resultado[] = [
                    'etiqueta_sub_id' => $subId,
                    'found' => false,
                    'html' => null,
                    'elementos' => [],
                ];
                continue;
            }

            // Renderizar el HTML de la etiqueta
            $html = view('components.etiqueta.etiqueta', [
                'etiqueta' => $etiqueta,
                'planilla' => $etiqueta->planilla,
                'maquinaTipo' => $maquinaTipo,
            ])->render();

            // Preparar datos de elementos para el SVG
            $elementos = $etiqueta->elementos->map(function ($el) {
                return [
                    'id' => $el->id,
                    'diametro' => $el->diametro,
                    'dimensiones' => $el->dimensiones,
                    'barras' => $el->barras,
                    'peso' => $el->peso,
                    'tipo' => $el->tipo,
                    'marca' => $el->marca,
                    'longitud' => $el->longitud,
                    'coladas' => [
                        'colada1' => $el->producto?->n_colada,
                        'colada2' => $el->producto2?->n_colada ?? null,
                        'colada3' => $el->producto3?->n_colada ?? null,
                    ],
                ];
            })->toArray();

            $resultado[] = [
                'etiqueta_sub_id' => $subId,
                'found' => true,
                'html' => $html,
                'elementos' => $elementos,
                'data' => [
                    'id' => $etiqueta->id,
                    'nombre' => $etiqueta->nombre,
                    'peso_kg' => $etiqueta->peso_kg,
                    'estado' => $etiqueta->estado,
                    'planilla_codigo' => $etiqueta->planilla->codigo_limpio ?? $etiqueta->planilla->codigo,
                    'planilla_seccion' => $etiqueta->planilla->seccion,
                    'obra' => $etiqueta->planilla->obra->obra ?? 'N/A',
                    'cliente' => $etiqueta->planilla->cliente->empresa ?? 'N/A',
                    'paquete_codigo' => $etiqueta->paquete?->codigo,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'etiquetas' => $resultado,
            'total' => count($resultado),
            'found' => collect($resultado)->where('found', true)->count(),
        ]);
    }
}
