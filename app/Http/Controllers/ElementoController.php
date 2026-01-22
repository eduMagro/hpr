<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Models\Alerta;
use App\Models\AsignacionTurno;
use App\Models\turno;
use App\Models\User;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\SubEtiquetaService;

class ElementoController extends Controller
{
    /**
     * Aplica los filtros a la consulta de elementos
     */
    private function aplicarFiltros($query, Request $request)
    {

        // 🔢 Filtros específicos
        $filters = [
            'id' => 'id',
            'figura' => 'figura',
            'etiqueta_sub_id' => 'etiqueta_sub_id',
            'dimensiones' => 'dimensiones',
            'planilla_id' => 'planilla_id',
            'barras' => 'barras'

        ];

        foreach ($filters as $requestKey => $column) {
            if ($request->has($requestKey) && $request->$requestKey !== null && $request->$requestKey !== '') {
                $query->where($column, 'like', '%' . trim($request->$requestKey) . '%');
            }
        }

        if ($request->filled('codigo')) {
            $codigos = explode(',', $request->codigo);
            if (count($codigos) > 1) {
                $query->whereIn('codigo', $codigos);
            } else {
                $query->where('codigo', 'like', '%' . $codigos[0] . '%');
            }
        }



        // 📅 Filtrado por rango de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_finalizacion') && $request->fecha_finalizacion) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        if ($request->filled('codigo_planilla')) {
            $input = trim($request->codigo_planilla);

            $query->whereHas('planilla', function ($q) use ($input) {

                if (preg_match('/^(\d{4})-(\d{1,6})$/', $input, $m)) {
                    $anio = $m[1];
                    $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                    $codigoFormateado = "{$anio}-{$num}";
                    $q->where('planillas.codigo', 'like', "%{$codigoFormateado}%");
                    return;
                }

                if (preg_match('/^\d{1,6}$/', $input)) {
                    $q->where('planillas.codigo', 'like', "%{$input}%");
                    return;
                }

                // 📝 Caso 3: texto o formato libre
                $q->where('planillas.codigo', 'like', "%{$input}%");
            });
        }



        // Etiqueta
        if ($request->has('etiqueta') && $request->etiqueta) {
            $query->whereHas('etiquetaRelacion', function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->etiqueta . '%');
            });
        }
        if ($request->filled('subetiqueta')) {
            $query->where('etiqueta_sub_id', 'like', '%' . $request->subetiqueta . '%');
        }

        // Máquinas
        if ($request->has('maquina') && $request->maquina) {
            $query->whereHas('maquina', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->maquina}%");
            });
        }

        if ($request->has('maquina_2') && $request->maquina_2) {
            $query->whereHas('maquina_2', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->maquina_2}%");
            });
        }

        if ($request->has('maquina3') && $request->maquina3) {
            $query->whereHas('maquina_3', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->maquina3}%");
            });
        }

        // Productos
        if ($request->has('producto1') && $request->producto1) {
            $query->whereHas('producto', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->producto1}%");
            });
        }

        if ($request->has('producto2') && $request->producto2) {
            $query->whereHas('producto2', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->producto2}%");
            });
        }

        if ($request->has('producto3') && $request->producto3) {
            $query->whereHas('producto3', function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->producto3}%");
            });
        }

        // Estado
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', 'like', "%{$request->estado}%");
        }
        if ($request->filled('peso')) {
            $query->where('peso', 'like', "%{$request->peso}%");
        }

        if ($request->filled('diametro')) {
            $query->where('diametro', 'like', "%{$request->diametro}%");
        }

        if ($request->filled('longitud')) {
            $query->where('longitud', 'like', "%{$request->longitud}%");
        }

        return $query;
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
    /**
     * Ordenamiento seguro para la tabla elementos.
     */
    private function aplicarOrdenamientoElementos($query, Request $request)
    {
        // Todas las columnas que SÍ se pueden ordenar (coinciden con tu array $ordenables)
        $columnasPermitidas = [
            'id',
            'codigo',
            'codigo_planilla',
            'etiqueta',
            'subetiqueta',
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
            'created_at',    // para el orden inicial por fecha
        ];

        // Lee los parámetros y sanea
        $sort  = $request->input('sort', 'created_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (!in_array($sort, $columnasPermitidas, true)) {
            $sort = 'created_at';              // fallback seguro
        }

        return $query->orderBy($sort, $order);
    }

    public function index(Request $request)
    {
        // Retornar vista Livewire
        return view('elementos.index-livewire');
    }
    public function actualizarMaquina(Request $request, Elemento $elemento)
    {
        $campo = $request->campo;
        $valor = $request->valor;

        Log::info("Actualizando elemento {$elemento->id}, campo: {$campo}, valor: '{$valor}'");

        // 1. Bloquear si el elemento ya está fabricado
        if ($elemento->estado === 'fabricado') {
            return response()->json([
                'error' => 'El elemento ya está fabricado'
            ], 403);
        }


        $camposPermitidos = ['maquina_id', 'maquina_id_2', 'maquina_id_3'];
        if (!in_array($campo, $camposPermitidos)) {
            Log::warning("Campo no permitido: {$campo}");
            return response()->json(['error' => 'Campo no permitido'], 403);
        }

        $planillaId = $elemento->planilla_id;

        DB::beginTransaction();
        try {
            // 🧠 Máquina real original (antes del cambio)
            $maquinaOriginal = $this->obtenerMaquinaReal($elemento);

            // 👉 Quitar asignación (vaciar campo)
            if (empty($valor)) {
                $elemento->$campo = null;
                $elemento->save();

                // 🧹 Si la máquina original se queda sin elementos de esta planilla, limpiar orden_planillas
                $quedanElementos = Elemento::where('planilla_id', $planillaId)
                    ->get()
                    ->filter(fn($e) => $this->obtenerMaquinaReal($e) === $maquinaOriginal)
                    ->isNotEmpty();

                if (!$quedanElementos && $maquinaOriginal) {
                    $ordenOriginal = OrdenPlanilla::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaOriginal)
                        ->first();

                    if ($ordenOriginal) {
                        $pos = $ordenOriginal->posicion;
                        $ordenOriginal->delete();

                        OrdenPlanilla::where('maquina_id', $maquinaOriginal)
                            ->where('posicion', '>', $pos)
                            ->decrement('posicion');
                    }
                }

                DB::commit();
                return response()->json([
                    'ok'        => true,
                    'campo'     => $campo,
                    'maquina_id' => null
                ]);
            }

            // ✅ Nueva máquina
            $nuevaMaquina = Maquina::find($valor);
            if (!$nuevaMaquina) {
                DB::rollBack();
                return response()->json(['error' => 'Máquina no encontrada'], 404);
            }

            // ⛔ Validación de diámetro y compatibilidad
            [$ok, $msg] = $this->validarDiametroMaquina($nuevaMaquina, $elemento);
            if (!$ok) {
                DB::rollBack();
                return response()->json([
                    'swal' => [
                        'icon'  => 'error',
                        'title' => 'Diámetro no válido',
                        'text'  => $msg,
                    ]
                ], 422);
            }

            // 1️⃣ Guardar el nuevo id de máquina en el campo solicitado
            $elemento->$campo = $nuevaMaquina->id;
            $elemento->save();

            // 2️⃣ Recalcular máquina real tras el cambio
            $elemento->refresh();
            $nuevaMaquinaReal = (int) $this->obtenerMaquinaReal($elemento);

            // ================== SUBETIQUETAS + PESOS (modularizado por tipo_material) ==================
            /** @var SubEtiquetaService $svc */
            $svc = app(SubEtiquetaService::class);
            [$subDestino, $subOriginal] = $svc->reubicarSegunTipoMaterial($elemento, $nuevaMaquinaReal);

            // ================== FIN sub-etiquetas y pesos ==================

            // 3️⃣ Asegurar entrada en orden_planillas para la NUEVA máquina real
            $ordenPlanilla = OrdenPlanilla::where('planilla_id', $planillaId)
                ->where('maquina_id', $nuevaMaquinaReal)
                ->lockForUpdate()
                ->first();

            if (!$ordenPlanilla) {
                // Obtener la máxima posición actual en la máquina destino con bloqueo
                // para prevenir race conditions en operaciones concurrentes
                $maxPosicion = OrdenPlanilla::where('maquina_id', $nuevaMaquinaReal)
                    ->lockForUpdate()
                    ->max('posicion');

                // La nueva posición será al final de la cola
                $nuevaPos = ($maxPosicion !== null) ? intval($maxPosicion) + 1 : 1;

                // Crear entrada directamente en la última posición
                $ordenPlanilla = OrdenPlanilla::create([
                    'planilla_id' => $planillaId,
                    'maquina_id'  => $nuevaMaquinaReal,
                    'posicion'    => $nuevaPos,
                ]);
            }

            // 🔗 Actualizar orden_planilla_id del elemento
            $elemento->orden_planilla_id = $ordenPlanilla->id;
            $elemento->save();

            // 4️⃣ Limpiar orden_planillas si la máquina ORIGINAL quedó vacía
            $quedanElementos = Elemento::where('planilla_id', $planillaId)
                ->get()
                ->filter(fn($e) => $this->obtenerMaquinaReal($e) === $maquinaOriginal)
                ->isNotEmpty();

            if (!$quedanElementos && $maquinaOriginal) {
                $ordenOriginal = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaOriginal)
                    ->first();

                if ($ordenOriginal) {
                    $posElim = $ordenOriginal->posicion;
                    $ordenOriginal->delete();

                    OrdenPlanilla::where('maquina_id', $maquinaOriginal)
                        ->where('posicion', '>', $posElim)
                        ->decrement('posicion');
                }
            }

            DB::commit();

            return response()->json([
                'ok'         => true,
                'campo'      => $campo,
                'maquina_id' => $nuevaMaquina->id,
                // 'etiqueta_sub_id' => $subDestino, // <- opcional para depurar en el frontend
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al actualizar elemento {$elemento->id}: " . $e->getMessage(), [
                'campo' => $campo,
                'valor' => $valor,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Valida que el diámetro del elemento esté dentro de los permitidos por la máquina.
     * - Si el elemento no tiene diámetro (null), se permite.
     * - Si la máquina no tiene min o max definidos (null), ese lado no limita.
     *
     * @return array{0: bool, 1: string|null} [ok, mensajeError]
     */
    private function validarDiametroMaquina(Maquina $maquina, Elemento $elemento): array
    {
        // Ajusta el nombre del campo si en tu modelo es otro (p.ej. diametro_mm)
        $diametro = $elemento->diametro;

        if ($diametro === null) {
            return [true, null];
        }

        $min = $maquina->diametro_min; // pueden ser null
        $max = $maquina->diametro_max;

        if ($min !== null && $diametro < (int) $min) {
            return [false, "El diámetro {$diametro} está por debajo del mínimo ({$min}) permitido por la máquina {$maquina->codigo}."];
        }

        if ($max !== null && $diametro > (int) $max) {
            return [false, "El diámetro {$diametro} supera el máximo ({$max}) permitido por la máquina {$maquina->codigo}."];
        }

        return [true, null];
    }
    private function obtenerMaquinaReal($e)
    {
        // Asegurar que las relaciones estén cargadas
        if (!$e->relationLoaded('maquina')) {
            $e->load(['maquina', 'maquina_2', 'maquina_3']);
        }

        $tipo1 = optional($e->maquina)->tipo;
        $tipo2 = optional($e->maquina_2)->tipo;
        $tipo3 = optional($e->maquina_3)->tipo;

        if ($tipo1 === 'ensambladora') return $e->maquina_id_2;
        if ($tipo1 === 'soldadora')    return $e->maquina_id_3 ?? $e->maquina_id;
        if ($tipo1 === 'dobladora_manual') return $e->maquina_id;
        if ($tipo2 === 'dobladora_manual') return $e->maquina_id_2;

        return $e->maquina_id;
    }
    /**
     * Divide un elemento en N partes, repartiendo peso, barras y tiempo de fabricación.
     * Crea nuevas etiquetas para cada parte.
     */

    public function dividirElemento(Request $request)
    {
        $request->validate([
            'elemento_id'    => 'required|exists:elementos,id',
            'barras_a_mover' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                /** @var \App\Models\Elemento $elemento */
                $elemento = Elemento::lockForUpdate()
                    ->with('etiquetaRelacion')
                    ->findOrFail($request->elemento_id);

                $barrasAMover = (int) $request->barras_a_mover;
                $barrasTotal  = (int) ($elemento->barras ?? 0);

                // Validar que no se muevan todas o más barras
                if ($barrasAMover >= $barrasTotal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes mover todas o más barras de las que tiene el elemento.',
                    ], 422);
                }

                // Calcular barras que quedan en el original
                $barrasOriginal = $barrasTotal - $barrasAMover;

                // === Reparto proporcional de PESO ===
                $pesoTotal    = (float) ($elemento->peso ?? 0);
                $pesoPorBarra = $barrasTotal > 0 ? $pesoTotal / $barrasTotal : 0;
                $prec         = 3;

                $pesoOriginal = round($pesoPorBarra * $barrasOriginal, $prec);
                $pesoNuevo    = round($pesoTotal - $pesoOriginal, $prec);

                // === Reparto proporcional de TIEMPO DE FABRICACIÓN ===
                $tiempoTotal     = (int) ($elemento->tiempo_fabricacion ?? 0);
                $tiempoPorBarra  = $barrasTotal > 0 ? $tiempoTotal / $barrasTotal : 0;

                $tiempoOriginal = (int) round($tiempoPorBarra * $barrasOriginal);
                $tiempoNuevo    = $tiempoTotal - $tiempoOriginal;

                // === Etiqueta base y sufijos por CODIGO ===
                $etqOriginal = $elemento->etiquetaRelacion
                    ?: Etiqueta::lockForUpdate()->findOrFail($elemento->etiqueta_id);

                $baseCodigo = $etqOriginal->codigo ?: preg_replace('/[.\-]\d+$/', '', (string) $etqOriginal->etiqueta_sub_id);

                // Obtener el sufijo máximo ya usado para ese código
                $maxSufijo = (int) DB::table('etiquetas')
                    ->where('codigo', $baseCodigo)
                    ->lockForUpdate()
                    ->selectRaw("COALESCE(MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)), 0) AS max_suf")
                    ->value('max_suf');

                // === 1) Actualiza ORIGINAL con las barras restantes ===
                $elemento->peso               = $pesoOriginal;
                $elemento->barras             = $barrasOriginal;
                $elemento->tiempo_fabricacion = $tiempoOriginal;
                $elemento->save();

                $etqOriginal->peso = $pesoOriginal;
                $etqOriginal->save();

                // === 2) Crear nueva etiqueta y elemento con las barras movidas ===
                $maxSufijo++;
                $nuevoSubId = sprintf('%s.%02d', $baseCodigo, $maxSufijo);

                while (DB::table('etiquetas')->where('etiqueta_sub_id', $nuevoSubId)->exists()) {
                    $maxSufijo++;
                    $nuevoSubId = sprintf('%s.%02d', $baseCodigo, $maxSufijo);
                }

                // Clonar Etiqueta
                $nuevaEtiqueta = $etqOriginal->replicate();
                $nuevaEtiqueta->codigo          = $baseCodigo;
                $nuevaEtiqueta->etiqueta_sub_id = $nuevoSubId;
                $nuevaEtiqueta->peso            = $pesoNuevo;
                $nuevaEtiqueta->save();

                // Clonar Elemento
                $clon = $elemento->replicate();
                $clon->codigo              = Elemento::generarCodigo();
                $clon->peso                = $pesoNuevo;
                $clon->barras              = $barrasAMover;
                $clon->tiempo_fabricacion  = $tiempoNuevo;
                $clon->etiqueta_id         = $nuevaEtiqueta->id;
                $clon->etiqueta_sub_id     = $nuevaEtiqueta->etiqueta_sub_id;
                $clon->save();

                return response()->json([
                    'success' => true,
                    'message' => "Division completada:<br><strong>Etiqueta original:</strong> {$barrasOriginal} barras ({$pesoOriginal} kg)<br><strong>Nueva etiqueta ({$nuevoSubId}):</strong> {$barrasAMover} barras ({$pesoNuevo} kg)",
                ], 200);
            });
        } catch (\Throwable $e) {
            Log::error('Hubo un error al dividir el elemento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el elemento. Intente nuevamente.',
            ], 500);
        }
    }

    /**
     * Divide un elemento automáticamente en múltiples etiquetas para mantener el peso bajo 1200 kg.
     * Recibe: elemento_id, num_etiquetas, barras_por_etiqueta, etiquetas_con_barra_extra
     */
    public function dividirAuto(Request $request)
    {
        $request->validate([
            'elemento_id'              => 'required|exists:elementos,id',
            'num_etiquetas'            => 'required|integer|min:2',
            'barras_por_etiqueta'      => 'required|integer|min:1',
            'etiquetas_con_barra_extra' => 'required|integer|min:0',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                /** @var \App\Models\Elemento $elemento */
                $elemento = Elemento::lockForUpdate()
                    ->with('etiquetaRelacion')
                    ->findOrFail($request->elemento_id);

                $numEtiquetas           = (int) $request->num_etiquetas;
                $barrasPorEtiqueta      = (int) $request->barras_por_etiqueta;
                $etiquetasConBarraExtra = (int) $request->etiquetas_con_barra_extra;

                $barrasTotales = (int) ($elemento->barras ?? 0);
                $pesoTotal     = (float) ($elemento->peso ?? 0);
                $tiempoTotal   = (int) ($elemento->tiempo_fabricacion ?? 0);

                // Calcular totales esperados para validación
                $barrasCalculadas = ($barrasPorEtiqueta * $numEtiquetas) + $etiquetasConBarraExtra;
                if ($barrasCalculadas !== $barrasTotales) {
                    return response()->json([
                        'success' => false,
                        'message' => "El cálculo de barras no coincide: esperadas {$barrasTotales}, calculadas {$barrasCalculadas}.",
                    ], 422);
                }

                // Calcular proporciones
                $pesoPorBarra   = $barrasTotales > 0 ? $pesoTotal / $barrasTotales : 0;
                $tiempoPorBarra = $barrasTotales > 0 ? $tiempoTotal / $barrasTotales : 0;
                $prec = 3;

                // === Etiqueta base y sufijos por CODIGO ===
                $etqOriginal = $elemento->etiquetaRelacion
                    ?: Etiqueta::lockForUpdate()->findOrFail($elemento->etiqueta_id);

                $baseCodigo = $etqOriginal->codigo ?: preg_replace('/[.\-]\d+$/', '', (string) $etqOriginal->etiqueta_sub_id);

                // Obtener el sufijo máximo ya usado para ese código
                $maxSufijo = (int) DB::table('etiquetas')
                    ->where('codigo', $baseCodigo)
                    ->lockForUpdate()
                    ->selectRaw("COALESCE(MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)), 0) AS max_suf")
                    ->value('max_suf');

                // Distribuir: primeras 'etiquetasConBarraExtra' etiquetas tienen barrasPorEtiqueta + 1 barras
                // El resto tienen barrasPorEtiqueta barras
                $etiquetasCreadas = [];
                $barrasRestantes  = $barrasTotales;
                $pesoRestante     = $pesoTotal;
                $tiempoRestante   = $tiempoTotal;

                // La primera etiqueta es la original
                $barrasOriginal = $etiquetasConBarraExtra > 0 ? $barrasPorEtiqueta + 1 : $barrasPorEtiqueta;
                $etiquetasConBarraExtraRestantes = $etiquetasConBarraExtra > 0 ? $etiquetasConBarraExtra - 1 : 0;

                $pesoOriginal   = round($pesoPorBarra * $barrasOriginal, $prec);
                $tiempoOriginal = (int) round($tiempoPorBarra * $barrasOriginal);

                // Actualizar el elemento original
                $elemento->peso               = $pesoOriginal;
                $elemento->barras             = $barrasOriginal;
                $elemento->tiempo_fabricacion = $tiempoOriginal;
                $elemento->save();

                $etqOriginal->peso = $pesoOriginal;
                $etqOriginal->save();

                $barrasRestantes -= $barrasOriginal;
                $pesoRestante    -= $pesoOriginal;
                $tiempoRestante  -= $tiempoOriginal;

                // Crear las nuevas etiquetas (num_etiquetas - 1)
                for ($i = 1; $i < $numEtiquetas; $i++) {
                    $maxSufijo++;
                    $nuevoSubId = sprintf('%s.%02d', $baseCodigo, $maxSufijo);

                    // Asegurar que no exista
                    while (DB::table('etiquetas')->where('etiqueta_sub_id', $nuevoSubId)->exists()) {
                        $maxSufijo++;
                        $nuevoSubId = sprintf('%s.%02d', $baseCodigo, $maxSufijo);
                    }

                    // Calcular barras para esta etiqueta
                    $barrasNueva = $etiquetasConBarraExtraRestantes > 0 ? $barrasPorEtiqueta + 1 : $barrasPorEtiqueta;
                    if ($etiquetasConBarraExtraRestantes > 0) {
                        $etiquetasConBarraExtraRestantes--;
                    }

                    // Si es la última, asignar las barras restantes para evitar errores de redondeo
                    if ($i === $numEtiquetas - 1) {
                        $barrasNueva = $barrasRestantes;
                    }

                    $pesoNuevo   = round($pesoPorBarra * $barrasNueva, $prec);
                    $tiempoNuevo = (int) round($tiempoPorBarra * $barrasNueva);

                    // Si es la última, asignar peso y tiempo restantes para evitar errores de redondeo
                    if ($i === $numEtiquetas - 1) {
                        $pesoNuevo   = round($pesoRestante, $prec);
                        $tiempoNuevo = $tiempoRestante;
                    }

                    // Clonar Etiqueta
                    $nuevaEtiqueta = $etqOriginal->replicate();
                    $nuevaEtiqueta->codigo          = $baseCodigo;
                    $nuevaEtiqueta->etiqueta_sub_id = $nuevoSubId;
                    $nuevaEtiqueta->peso            = $pesoNuevo;
                    $nuevaEtiqueta->save();

                    // Clonar Elemento
                    $clon = $elemento->replicate();
                    $clon->codigo              = Elemento::generarCodigo();
                    $clon->peso                = $pesoNuevo;
                    $clon->barras              = $barrasNueva;
                    $clon->tiempo_fabricacion  = $tiempoNuevo;
                    $clon->etiqueta_id         = $nuevaEtiqueta->id;
                    $clon->etiqueta_sub_id     = $nuevaEtiqueta->etiqueta_sub_id;
                    $clon->save();

                    $etiquetasCreadas[] = $nuevoSubId;

                    $barrasRestantes -= $barrasNueva;
                    $pesoRestante    -= $pesoNuevo;
                    $tiempoRestante  -= $tiempoNuevo;
                }

                $countCreadas = count($etiquetasCreadas);
                $etiquetaOriginalSubId = $etqOriginal->etiqueta_sub_id;

                // Array con todas las etiquetas (original + nuevas) para imprimir
                $todasLasEtiquetas = array_merge([$etiquetaOriginalSubId], $etiquetasCreadas);

                return response()->json([
                    'success' => true,
                    'message' => "Se crearon <strong>{$countCreadas} nuevas etiquetas</strong>:<br><small>" .
                                 implode(', ', $etiquetasCreadas) . "</small><br><br>" .
                                 "Etiqueta original: {$barrasOriginal} barras ({$pesoOriginal} kg)",
                    'etiquetas_creadas' => $etiquetasCreadas,
                    'etiqueta_original' => $etiquetaOriginalSubId,
                    'todas_las_etiquetas' => $todasLasEtiquetas,
                ], 200);
            });
        } catch (\Throwable $e) {
            Log::error('Error en dividirAuto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al dividir automáticamente: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Almacena un nuevo elemento en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function solicitarCambioMaquina(Request $request, $elementoId)
    {
        $motivo     = $request->motivo;
        $maquinaId  = $request->maquina_id;
        $horaActual = Carbon::now()->format('H:i:s');

        // Obtener el elemento que solicita el cambio
        $elemento = Elemento::find($elementoId);
        if (!$elemento) {
            return response()->json(['message' => 'Elemento no encontrado.'], 404);
        }

        $etiquetaSubId = $elemento->etiqueta_sub_id;
        $maquinaOrigen = Maquina::find($elemento->maquina_id);
        $maquinaDestino = Maquina::find($maquinaId);

        // Buscar turno actual
        $turno = Turno::where('hora_inicio', '<=', $horaActual)
            ->where('hora_fin', '>=', $horaActual)
            ->first();

        if (!$turno) {
            return response()->json(['message' => 'No se encontró turno activo.'], 404);
        }

        // Buscar asignaciones activas para hoy en la máquina destino
        $asignaciones = AsignacionTurno::where('fecha', Carbon::today())
            ->where('maquina_id', $maquinaId)
            ->where('turno_id', $turno->id)
            ->get();

        if ($asignaciones->isEmpty()) {
            return response()->json(['message' => 'No hay usuarios asignados a esa máquina.'], 404);
        }

        foreach ($asignaciones as $asignacion) {
            $usuarioDestino = User::find($asignacion->user_id);

            $mensaje = "Solicitud de cambio de máquina para elemento #{$elemento->id} (etiqueta {$etiquetaSubId}): {$motivo}. "
                . "Origen: " . ($maquinaOrigen?->nombre ?? 'N/A') . ", Destino: " . ($maquinaDestino?->nombre ?? 'N/A');

            Alerta::create([
                'user_id_1'       => auth()->id(),
                'user_id_2'       => $usuarioDestino->id,
                'destino'         => 'produccion',
                'destinatario'    => $usuarioDestino->name,
                'destinatario_id' => $usuarioDestino->id,
                'mensaje'         => $mensaje,
                'leida'           => false,
                'completada'      => false,
            ]);

            Log::info("Alerta enviada", [
                'elemento_id' => $elemento->id,
                'etiqueta_sub_id' => $etiquetaSubId,
                'usuario_id'  => $usuarioDestino->id,
                'mensaje'     => $mensaje,
            ]);
        }

        return response()->json(['message' => 'Solicitud enviada correctamente al operario asignado.']);
    }

    public function cambioMaquina(Request $request, $id)
    {
        try {
            $request->validate([
                'maquina_id' => 'required|exists:maquinas,id',
            ]);
            Log::info("Entrando al metodo cambioMaquina...");

            return DB::transaction(function () use ($request, $id) {
                $elemento = Elemento::lockForUpdate()->findOrFail($id);
                $nuevaMaquinaId = (int) $request->maquina_id;

                if ($elemento->maquina_id == $nuevaMaquinaId) {
                    Log::info("El elemento ya pertenece a esa maquina");
                }

                // Actualizar máquina del elemento
                $elemento->maquina_id = $nuevaMaquinaId;
                $elemento->save();

                // Usar SubEtiquetaService para reubicar subetiquetas correctamente
                /** @var SubEtiquetaService $svc */
                $svc = app(SubEtiquetaService::class);
                $svc->reubicarParaProduccion($elemento, $nuevaMaquinaId);

                // Marcar la alerta como completada
                $alertaId = $request->query('alerta_id');

                if ($alertaId) {
                    $alerta = Alerta::find($alertaId);
                    if ($alerta) {
                        $alerta->completada = true;
                        $alerta->save();
                        Log::info("Alerta {$alertaId} completada");
                    }
                }

                return redirect()->route('dashboard')->with('success', 'Cambio de máquina aplicado correctamente.');
            });
        } catch (\Exception $e) {
            Log::error("Error al cambiar máquina de elemento {$id}: {$e->getMessage()}");
            return back()->with('error', 'No se pudo cambiar la máquina del elemento.');
        }
    }

    public function crearSubEtiqueta(Request $request)
    {
        $request->validate([
            'elemento_id'     => 'required|exists:elementos,id',
            'etiqueta_sub_id' => 'required|string', // base: ETQ-25-0001.02 -> ETQ-25-0001
            'partes'          => 'nullable|integer|min:2' // por defecto 2
        ]);

        // columnas REALES
        $colPesoElem = 'peso'; // peso en elementos
        $colPesoEtiq = 'peso'; // peso total almacenado en etiquetas

        return DB::transaction(function () use ($request, $colPesoElem, $colPesoEtiq) {
            $partes = (int) ($request->partes ?? 2);
            if ($partes !== 2) {
                return response()->json(['success' => false, 'message' => 'Esta versión divide en 2.'], 422);
            }

            /** @var \App\Models\Elemento $elemento */
            $elemento = \App\Models\Elemento::with('etiqueta')
                ->lockForUpdate()
                ->findOrFail($request->elemento_id);

            $etiquetaOriginal = $elemento->etiqueta;

            $pesoTotal   = (float) ($elemento->{$colPesoElem} ?? 0);
            $barrasTotal = (int)   ($elemento->barras ?? 0);

            if ($pesoTotal <= 0)  return response()->json(['success' => false, 'message' => 'El elemento no tiene peso positivo.'], 422);
            if ($barrasTotal < 1) return response()->json(['success' => false, 'message' => 'El elemento no tiene barras.'], 422);

            // 1) dividir peso (cuadrando en la segunda parte)
            $pesoA = round($pesoTotal / 2, 3);
            $pesoB = round($pesoTotal - $pesoA, 3);

            // 2) dividir barras
            $barrasA = intdiv($barrasTotal, 2) + ($barrasTotal % 2);
            $barrasB = $barrasTotal - $barrasA;

            // 3) actualizar elemento original (parte A)
            $elemento->{$colPesoElem} = $pesoA;
            $elemento->barras         = $barrasA;
            $elemento->save();

            // 4) generar sub_id para nueva subetiqueta
            $baseCodigo = explode('.', $request->etiqueta_sub_id)[0];
            $existentes = \App\Models\Etiqueta::where('etiqueta_sub_id', 'like', "$baseCodigo.%")
                ->lockForUpdate()
                ->pluck('etiqueta_sub_id')
                ->toArray();

            $nuevoSubId = null;
            for ($j = 1; $j <= 500; $j++) {
                $cand = $baseCodigo . '.' . str_pad($j, 2, '0', STR_PAD_LEFT);
                if (!in_array($cand, $existentes)) {
                    $nuevoSubId = $cand;
                    break;
                }
            }
            if (!$nuevoSubId) throw new \RuntimeException('No hay subetiqueta disponible.');

            // 5) crear nueva subetiqueta SIN copiar peso
            $nuevaEtiqueta = $etiquetaOriginal->replicate();
            $nuevaEtiqueta->etiqueta_sub_id = $nuevoSubId;

            // ⚠️ Reset explícito del peso para no arrastrar el de la original
            $nuevaEtiqueta->{$colPesoEtiq} = 0;
            // Guardamos sin disparar lógicas raras de peso (si las hubiera)
            $nuevaEtiqueta->save();

            // 6) crear nuevo elemento (parte B) en la subetiqueta
            $nuevoElemento = $elemento->replicate();
            $nuevoElemento->etiqueta_id     = $nuevaEtiqueta->id;
            $nuevoElemento->etiqueta_sub_id = $nuevoSubId;
            $nuevoElemento->{$colPesoElem}  = $pesoB;
            $nuevoElemento->barras          = $barrasB;
            $nuevoElemento->codigo          = \App\Models\Elemento::generarCodigo(); // si aplica
            $nuevoElemento->save();

            // 7) ajustar pesos de etiquetas **solo con increment/decrement (sin setear)**
            DB::table('etiquetas')
                ->where('id', $etiquetaOriginal->id)
                ->decrement($colPesoEtiq, $pesoB);   // ✅ RESTA en original

            DB::table('etiquetas')
                ->where('id', $nuevaEtiqueta->id)
                ->increment($colPesoEtiq, $pesoB);   // ✅ SUMA en la nueva

            // 8) devolver estado final
            $etiquetaOriginalRefrescada = \App\Models\Etiqueta::find($etiquetaOriginal->id);
            $nuevaEtiquetaRefrescada    = \App\Models\Etiqueta::find($nuevaEtiqueta->id);

            return response()->json([
                'success' => true,
                'message' => 'Elemento partido en 2. Solo se resta en la etiqueta original y se suma en la nueva.',
                'data' => [
                    'elemento_original' => ['barras' => $elemento->barras, 'peso' => $elemento->{$colPesoElem}],
                    'nuevo_elemento'    => ['barras' => $nuevoElemento->barras, 'peso' => $nuevoElemento->{$colPesoElem}],
                    'etiqueta_original' => ['id' => $etiquetaOriginal->id, 'peso' => $etiquetaOriginalRefrescada->{$colPesoEtiq}],
                    'subetiqueta_nueva' => ['id' => $nuevaEtiqueta->id, 'sub_id' => $nuevoSubId, 'peso' => $nuevaEtiquetaRefrescada->{$colPesoEtiq}],
                ]
            ]);
        });
    }


    public function moverTodoANuevaSubEtiqueta(Request $request)
    {
        $request->validate([
            'elemento_id' => 'required|exists:elementos,id',
        ]);

        $colPesoElem = 'peso';
        $colPesoEtiq = 'peso';

        return DB::transaction(function () use ($request, $colPesoElem, $colPesoEtiq) {
            /** @var \App\Models\Elemento $elemento */
            $elemento = Elemento::with('etiquetaRelacion')
                ->lockForUpdate()
                ->findOrFail($request->elemento_id);

            // Usar la relación correcta del modelo (no el campo string 'etiqueta')
            $etiquetaOriginal = $elemento->etiquetaRelacion;
            if (!$etiquetaOriginal) {
                return response()->json(['success' => false, 'message' => 'Etiqueta del elemento no encontrada.'], 404);
            }

            $pesoElemento = (float) ($elemento->{$colPesoElem} ?? 0);

            // Base del sub_id (antes del punto)
            $baseCodigo = explode('.', (string) $etiquetaOriginal->etiqueta_sub_id)[0];

            // Buscar el siguiente sub_id libre
            $existentes = Etiqueta::where('etiqueta_sub_id', 'like', "$baseCodigo.%")
                ->lockForUpdate()
                ->pluck('etiqueta_sub_id')
                ->toArray();

            $nuevoSubId = null;
            for ($j = 1; $j <= 500; $j++) {
                $cand = $baseCodigo . '.' . str_pad($j, 2, '0', STR_PAD_LEFT);
                if (!in_array($cand, $existentes)) {
                    $nuevoSubId = $cand;
                    break;
                }
            }
            if (!$nuevoSubId) {
                throw new \RuntimeException('No hay subetiqueta disponible.');
            }

            // Crear nueva subetiqueta (replica) con peso reseteado
            $nuevaEtiqueta = $etiquetaOriginal->replicate();
            $nuevaEtiqueta->etiqueta_sub_id = $nuevoSubId;
            $nuevaEtiqueta->{$colPesoEtiq} = 0; // no arrastrar peso de la original
            $nuevaEtiqueta->save();

            // Mover el elemento a la nueva subetiqueta
            $elemento->etiqueta_id = $nuevaEtiqueta->id;
            $elemento->etiqueta_sub_id = $nuevoSubId;
            $elemento->save();

            // Ajustar pesos en etiquetas (si corresponde)
            if ($pesoElemento > 0) {
                DB::table('etiquetas')
                    ->where('id', $etiquetaOriginal->id)
                    ->decrement($colPesoEtiq, $pesoElemento);

                DB::table('etiquetas')
                    ->where('id', $nuevaEtiqueta->id)
                    ->increment($colPesoEtiq, $pesoElemento);
            }

            return response()->json([
                'success' => true,
                'message' => 'Elemento movido a nueva subetiqueta correctamente.',
                'data' => [
                    'elemento_id' => $elemento->id,
                    'subetiqueta_nueva' => $nuevoSubId,
                ]
            ], 200);
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'conjunto_id' => 'required|exists:conjuntos,id',
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'diametro' => 'required|numeric|min:0',
            'longitud' => 'required|numeric|min:0',
            'peso' => 'required|numeric|min:0',
        ]);

        Elemento::create($validated);

        return redirect()->route('elementos.index')->with('success', 'Elemento creado exitosamente.');
    }

    public function showByEtiquetas($planillaId)
    {

        $planilla = Planilla::with(['elementos'])->findOrFail($planillaId);

        // Obtener elementos clasificados por etiquetas
        $etiquetasConElementos = Etiqueta::with('elementos')
            ->whereHas('elementos', function ($query) use ($planillaId) {
                $query->where('planilla_id', $planillaId);
            })
            ->get();

        return view('elementos.show', compact('planilla', 'etiquetasConElementos'));
    }

    public function update(Request $request, $id)
    {
        try {

            // Validar los datos recibidos con mensajes personalizados
            $validated = $request->validate([
                'planilla_id'   => 'nullable|integer|exists:planillas,id',
                'etiqueta_id'   => 'nullable|integer|exists:etiquetas,id',
                'maquina_id'    => 'nullable|integer|exists:maquinas,id',
                'maquina_id_2'  => 'nullable|integer|exists:maquinas,id',
                'maquina_id_3'  => 'nullable|integer|exists:maquinas,id',
                'producto_id'   => 'nullable|integer|exists:productos,id',
                'producto_id_2' => 'nullable|integer|exists:productos,id',
                'producto_id_3' => 'nullable|integer|exists:productos,id',
                'figura'        => 'nullable|string|max:255',
                'dimensiones'   => 'nullable|string|max:255',
                'fila'          => 'nullable|string|max:255',
                'marca'         => 'nullable|string|max:255',
                'etiqueta'      => 'nullable|string|max:255',
                'diametro'      => 'nullable|numeric',
                'peso'      => 'nullable|numeric',
                'longitud'      => 'nullable|numeric',
                'estado'        => 'nullable|string|max:50'
            ], [
                'planilla_id.integer'   => 'El campo planilla_id debe ser un número entero.',
                'planilla_id.exists'    => 'La planilla especificada en planilla_id no existe.',
                'etiqueta_id.integer'   => 'El campo etiqueta_id debe ser un número entero.',
                'etiqueta_id.exists'    => 'La etiqueta especificada en etiqueta_id no existe.',
                'maquina_id.integer'    => 'El campo maquina_id debe ser un número entero.',
                'maquina_id.exists'     => 'La máquina especificada en maquina_id no existe.',
                'maquina_id_2.integer'  => 'El campo maquina_id_2 debe ser un número entero.',
                'maquina_id_2.exists'   => 'La máquina especificada en maquina_id_2 no existe.',
                'maquina_id_3.integer'  => 'El campo maquina_id_3 debe ser un número entero.',
                'maquina_id_3.exists'   => 'La máquina especificada en maquina_id_3 no existe.',
                'producto_id.integer'   => 'El campo producto_id debe ser un número entero.',
                'producto_id.exists'    => 'El producto especificado en producto_id no existe.',
                'producto_id_2.integer' => 'El campo producto_id_2 debe ser un número entero.',
                'producto_id_2.exists'  => 'El producto especificado en producto_id_2 no existe.',
                'producto_id_3.integer' => 'El campo producto_id_3 debe ser un número entero.',
                'producto_id_3.exists'  => 'El producto especificado en producto_id_3 no existe.',
                'figura.string'         => 'El campo figura debe ser una cadena de texto.',
                'figura.max'            => 'El campo figura no debe tener más de 255 caracteres.',
                'dimensiones.string'    => 'El campo dimensiones debe ser una cadena de texto.',
                'dimensiones.max'       => 'El campo dimensiones no debe tener más de 255 caracteres.',
                'fila.string'           => 'El campo fila debe ser una cadena de texto.',
                'fila.max'              => 'El campo fila no debe tener más de 255 caracteres.',
                'marca.string'          => 'El campo marca debe ser una cadena de texto.',
                'marca.max'             => 'El campo marca no debe tener más de 255 caracteres.',
                'etiqueta.string'       => 'El campo etiqueta debe ser una cadena de texto.',
                'etiqueta.max'          => 'El campo etiqueta no debe tener más de 255 caracteres.',
                'diametro.numeric'      => 'El campo diametro debe ser un número.',
                'peso.numeric'          => 'El campo peso debe ser un número.',
                'longitud.numeric'      => 'El campo longitud debe ser un número.',
                'estado.string'         => 'El campo estado debe ser una cadena de texto.',
                'estado.max'            => 'El campo estado no debe tener más de 50 caracteres.',
            ]);

            $elemento = Elemento::findOrFail($id);

            // ⚠️ VALIDACIÓN: Solo permitir fabricar si la planilla está revisada
            if (array_key_exists('estado', $validated)) {
                $nuevoEstado = $validated['estado'];
                $estadosProduccion = ['fabricando', 'fabricado'];

                if (in_array($nuevoEstado, $estadosProduccion)) {
                    $planilla = $elemento->planilla;

                    if (!$planilla || !$planilla->revisada) {
                        return response()->json([
                            'error' => '⚠️ No se puede fabricar esta planilla porque aún no ha sido revisada',
                            'planilla_codigo' => $planilla ? $planilla->codigo : 'N/A',
                            'revisada' => false
                        ], 403);
                    }
                }
            }

            // 🚚 Si cambió la máquina, usar SubEtiquetaService para reubicar subetiquetas
            $maquinaCambio = array_key_exists('maquina_id', $validated)
                && $validated['maquina_id'] != $elemento->maquina_id;

            // Actualizar resto de campos
            $elemento->fill($validated);

            if ($elemento->isDirty()) {
                if ($elemento->isDirty('estado')) {
                    Log::debug("⚠️ Estado sí cambió: {$elemento->getOriginal('estado')} → {$elemento->estado}");

                    // 🔧 Actualizar fechas en etiqueta cuando cambia el estado
                    $this->actualizarFechasEtiqueta($elemento);
                }
                $elemento->save();
            }


            // Si cambió de máquina, actualizar orden_planillas y reubicar subetiquetas
            if ($maquinaCambio) {
                $planillaId = $elemento->planilla_id;
                $nuevaMaquinaId = (int) $validated['maquina_id'];
                $maquinaAnteriorId = $elemento->getOriginal('maquina_id');

                // 1. Obtener o crear OrdenPlanilla en nueva máquina
                $ordenPlanilla = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $nuevaMaquinaId)
                    ->first();

                if (!$ordenPlanilla) {
                    $ultimaPosicion = OrdenPlanilla::where('maquina_id', $nuevaMaquinaId)->max('posicion') ?? 0;

                    $ordenPlanilla = OrdenPlanilla::create([
                        'planilla_id' => $planillaId,
                        'maquina_id' => $nuevaMaquinaId,
                        'posicion' => $ultimaPosicion + 1,
                    ]);
                }

                // 🔗 Actualizar orden_planilla_id del elemento
                $elemento->orden_planilla_id = $ordenPlanilla->id;
                $elemento->save();

                // 🏷️ Usar SubEtiquetaService para reubicar subetiquetas correctamente
                /** @var SubEtiquetaService $svc */
                $svc = app(SubEtiquetaService::class);
                $svc->reubicarParaProduccion($elemento, $nuevaMaquinaId);

                // 2. Eliminar de la máquina anterior si ya no hay elementos
                $quedan = \App\Models\Elemento::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaAnteriorId)
                    ->exists();

                if (!$quedan) {
                    OrdenPlanilla::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaAnteriorId)
                        ->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Elemento actualizado correctamente',
                'data'    => $elemento
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Elemento con ID {$id} no encontrado", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Elemento no encontrado'
            ], 404);
        } catch (ValidationException $e) {
            Log::error('Error de validación', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error("Error al actualizar el elemento con ID {$id}", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el elemento. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * Elimina un elemento existente de la base de datos.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Elemento $elemento)
    {
        $elemento->delete();
        return redirect()->route('elementos.index')->with('success', 'Elemento eliminado exitosamente.');
    }

    public function filtrar(Request $request)
    {
        $planilla_id = $request->input('planilla_id');
        $maquina_id = $request->input('maquina_id');

        $resultado = Elemento::query()
            ->where('planilla_id', $planilla_id)
            ->where('maquina_id', $maquina_id)
            ->get();

        return response()->json($resultado);
    }

    /**
     * 🔧 Actualiza las fechas en la etiqueta cuando cambia el estado del elemento
     */
    private function actualizarFechasEtiqueta(Elemento $elemento)
    {
        $etiqueta = $elemento->etiquetaRelacion;
        if (!$etiqueta) {
            return; // No hay etiqueta asociada
        }

        $tipoMaquina = optional($elemento->maquina)->tipo;
        $ahora = now();

        // Según el estado y tipo de máquina, actualizar los campos correspondientes
        switch ($elemento->estado) {
            case 'fabricando':
                // Registrar inicio de fabricación
                if ($tipoMaquina === 'ensambladora') {
                    if (!$etiqueta->fecha_inicio_ensamblado) {
                        $etiqueta->fecha_inicio_ensamblado = $ahora;
                    }
                } elseif ($tipoMaquina === 'soldadora') {
                    if (!$etiqueta->fecha_inicio_soldadura) {
                        $etiqueta->fecha_inicio_soldadura = $ahora;
                    }
                } else {
                    // dobladora/cortadora
                    if (!$etiqueta->fecha_inicio) {
                        $etiqueta->fecha_inicio = $ahora;
                    }
                }
                break;

            case 'fabricado':
                // Registrar fin de fabricación
                if ($tipoMaquina === 'ensambladora') {
                    $etiqueta->fecha_finalizacion_ensamblado = $ahora;
                } elseif ($tipoMaquina === 'soldadora') {
                    $etiqueta->fecha_finalizacion_soldadura = $ahora;
                } else {
                    // dobladora/cortadora
                    $etiqueta->fecha_finalizacion = $ahora;
                }
                break;
        }

        $etiqueta->save();
        Log::info("🔧 Fechas de etiqueta actualizadas", [
            'etiqueta_id' => $etiqueta->id,
            'elemento_id' => $elemento->id,
            'tipo_maquina' => $tipoMaquina,
            'estado' => $elemento->estado
        ]);
    }

    public function show(Elemento $elemento)
    {
        //
    }

    /**
     * Obtiene las máquinas disponibles para un elemento según su diámetro.
     * GET /api/elementos/{id}/maquinas-disponibles
     */
    public function maquinasDisponibles($elementoId)
    {
        try {
            $elemento = Elemento::findOrFail($elementoId);
            $diametro = (int) $elemento->diametro;
            $maquinaActualId = $elemento->maquina_id;

            // Obtener todas las máquinas y filtrar por diámetro
            // Una máquina soporta el diámetro si:
            // - diametro_min es null O diametro >= diametro_min
            // - diametro_max es null O diametro <= diametro_max
            $maquinas = Maquina::orderBy('codigo')
                ->get()
                ->filter(function ($m) use ($diametro) {
                    $minOk = is_null($m->diametro_min) || $diametro >= (int) $m->diametro_min;
                    $maxOk = is_null($m->diametro_max) || $diametro <= (int) $m->diametro_max;
                    return $minOk && $maxOk;
                })
                ->map(function ($m) use ($maquinaActualId) {
                    return [
                        'id' => $m->id,
                        'codigo' => $m->codigo,
                        'nombre' => $m->nombre ?? $m->codigo,
                        'tipo' => $m->tipo,
                        'diametro_min' => $m->diametro_min,
                        'diametro_max' => $m->diametro_max,
                        'es_actual' => $m->id === $maquinaActualId,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'elemento' => [
                    'id' => $elemento->id,
                    'diametro' => $diametro,
                    'maquina_actual_id' => $maquinaActualId,
                ],
                'maquinas' => $maquinas,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener máquinas disponibles: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener máquinas disponibles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambia directamente la máquina de un elemento.
     * Si el elemento pertenece a un grupo resumido, cambia todos los elementos similares del grupo.
     * Valida diámetros y usa SubEtiquetaService para hermanos MSR20.
     * POST /elementos/{id}/cambiar-maquina
     */
    public function cambiarMaquinaDirecto(Request $request, $elementoId)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
        ]);

        try {
            return DB::transaction(function () use ($request, $elementoId) {
                $elementoOriginal = Elemento::with('etiquetaRelacion')->lockForUpdate()->findOrFail($elementoId);
                $nuevaMaquina = Maquina::findOrFail($request->maquina_id);
                $diametro = (int) $elementoOriginal->diametro;
                $dimensiones = $elementoOriginal->dimensiones;

                // Validar que la máquina soporte el diámetro
                $minOk = is_null($nuevaMaquina->diametro_min) || $diametro >= (int) $nuevaMaquina->diametro_min;
                $maxOk = is_null($nuevaMaquina->diametro_max) || $diametro <= (int) $nuevaMaquina->diametro_max;

                if (!$minOk || !$maxOk) {
                    return response()->json([
                        'success' => false,
                        'message' => "La máquina {$nuevaMaquina->codigo} no soporta el diámetro Ø{$diametro} (rango permitido: {$nuevaMaquina->diametro_min}-{$nuevaMaquina->diametro_max})",
                    ], 422);
                }

                $maquinaAnterior = $elementoOriginal->maquina ? $elementoOriginal->maquina->codigo : 'Sin asignar';

                // Verificar si el elemento pertenece a un grupo resumido
                $etiqueta = $elementoOriginal->etiquetaRelacion;
                $grupoResumenId = $etiqueta ? $etiqueta->grupo_resumen_id : null;

                // Colección de elementos a cambiar
                $elementosACambiar = collect([$elementoOriginal]);

                // Si está en un grupo resumido, buscar elementos similares (mismo diámetro y dimensiones)
                if ($grupoResumenId) {
                    // Obtener todas las etiquetas del grupo
                    $etiquetasDelGrupo = Etiqueta::where('grupo_resumen_id', $grupoResumenId)
                        ->pluck('etiqueta_sub_id')
                        ->toArray();

                    // Buscar elementos similares en el grupo (mismo diámetro y dimensiones)
                    $elementosSimilares = Elemento::whereIn('etiqueta_sub_id', $etiquetasDelGrupo)
                        ->where('diametro', $diametro)
                        ->where('dimensiones', $dimensiones)
                        ->where('id', '!=', $elementoOriginal->id)
                        ->lockForUpdate()
                        ->get();

                    $elementosACambiar = $elementosACambiar->merge($elementosSimilares);

                    Log::info("Cambio de máquina en grupo resumido", [
                        'grupo_resumen_id' => $grupoResumenId,
                        'elemento_original_id' => $elementoId,
                        'elementos_similares' => $elementosSimilares->count(),
                        'total_elementos' => $elementosACambiar->count(),
                    ]);
                }

                // Verificar que al menos un elemento no esté ya en la máquina destino
                $elementosYaEnDestino = $elementosACambiar->where('maquina_id', $nuevaMaquina->id)->count();
                if ($elementosYaEnDestino === $elementosACambiar->count()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Todos los elementos ya están asignados a esa máquina',
                    ], 422);
                }

                /** @var SubEtiquetaService $svc */
                $svc = app(SubEtiquetaService::class);
                $elementosMovidos = 0;

                // Cambiar máquina de cada elemento
                foreach ($elementosACambiar as $elemento) {
                    // Saltar si ya está en la máquina destino
                    if ($elemento->maquina_id == $nuevaMaquina->id) {
                        continue;
                    }

                    // Actualizar máquina del elemento
                    $elemento->maquina_id = $nuevaMaquina->id;
                    $elemento->save();

                    // Usar SubEtiquetaService para reubicar subetiquetas correctamente
                    $svc->reubicarParaProduccion($elemento, $nuevaMaquina->id);

                    $elementosMovidos++;
                }

                $mensaje = $elementosMovidos === 1
                    ? "Elemento movido a {$nuevaMaquina->codigo}"
                    : "{$elementosMovidos} elementos movidos a {$nuevaMaquina->codigo}";

                Log::info("Elementos movidos de {$maquinaAnterior} a {$nuevaMaquina->codigo}", [
                    'elemento_original_id' => $elementoId,
                    'elementos_movidos' => $elementosMovidos,
                    'maquina_anterior' => $maquinaAnterior,
                    'maquina_nueva' => $nuevaMaquina->codigo,
                    'grupo_resumen_id' => $grupoResumenId,
                    'usuario_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'elementos_movidos' => $elementosMovidos,
                    'maquina_anterior' => $maquinaAnterior,
                    'maquina_nueva' => $nuevaMaquina->codigo,
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Error al cambiar máquina del elemento {$elementoId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la máquina: ' . $e->getMessage(),
            ], 500);
        }
    }
}
