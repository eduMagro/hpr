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

        ];

        foreach ($filters as $requestKey => $column) {
            if ($request->has($requestKey) && $request->$requestKey !== null && $request->$requestKey !== '') {
                $query->where($column, 'like', "%{$request->$requestKey}%");
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
            $input = $request->codigo_planilla;

            $query->whereHas('planilla', function ($q) use ($input) {
                $q->where('codigo', 'like', "%{$input}%");
            });
        }


        // Etiqueta
        if ($request->has('etiqueta') && $request->etiqueta) {
            $query->whereHas('etiquetaRelacion', function ($q) use ($request) {
                $q->where('id', $request->etiqueta);
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
        $query = Elemento::with([
            'planilla',
            'etiquetaRelacion',
            'maquina',
            'maquina_2',
            'maquina_3',
            'producto',
            'producto2',
            'producto3',
        ])->orderBy('created_at', 'desc');

        $query = $this->aplicarFiltros($query, $request);
        $query = $this->aplicarOrdenamientoElementos($query, $request);
        $totalPesoFiltrado = (clone $query)->sum('peso');
        // Paginación
        $perPage = $request->input('per_page', 10);
        $elementos = $query->paginate($perPage)->appends($request->except('page'));

        // Asegurar relación etiqueta
        $elementos->getCollection()->transform(function ($elemento) {
            $elemento->etiquetaRelacion = $elemento->etiquetaRelacion ?? (object) ['id' => '', 'nombre' => ''];
            return $elemento;
        });

        // Todas las máquinas
        $maquinas = Maquina::all();

        // Definir columnas ordenables para la vista
        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'codigo' => $this->getOrdenamiento('codigo', 'Código Elemento'),
            'codigo_planilla' => $this->getOrdenamiento('codigo_planilla', 'Planilla'),
            'etiqueta' => $this->getOrdenamiento('etiqueta', 'Etiqueta'),
            'subetiqueta' => $this->getOrdenamiento('subetiqueta', 'SubEtiqueta'),
            'maquina' => $this->getOrdenamiento('maquina', 'Maq. 1'),
            'maquina_2' => $this->getOrdenamiento('maquina_2', 'Maq. 2'),
            'maquina3' => $this->getOrdenamiento('maquina3', 'Maq. 3'),
            'producto1' => $this->getOrdenamiento('producto1', 'M. Prima 1'),
            'producto2' => $this->getOrdenamiento('producto2', 'M. Prima 2'),
            'producto3' => $this->getOrdenamiento('producto3', 'M. Prima 3'),
            'figura' => $this->getOrdenamiento('figura', 'Figura'),
            'peso' => $this->getOrdenamiento('peso', 'Peso (kg)'),
            'diametro' => $this->getOrdenamiento('diametro', 'Diámetro (mm)'),
            'longitud' => $this->getOrdenamiento('longitud', 'Longitud (m)'),
            'estado' => $this->getOrdenamiento('estado', 'Estado'),
        ];

        return view('elementos.index', compact('elementos', 'maquinas', 'ordenables', 'totalPesoFiltrado'));
    }
    public function actualizarCampo(Request $request, Elemento $elemento)
    {
        $campo = $request->campo;
        $valor = $request->valor;

        \Log::info("Actualizando elemento {$elemento->id}, campo: {$campo}, valor: '{$valor}'");

        $camposPermitidos = ['maquina_id', 'maquina_id_2', 'maquina_id_3'];
        if (!in_array($campo, $camposPermitidos)) {
            \Log::warning("Campo no permitido: {$campo}");
            return response()->json(['error' => 'Campo no permitido'], 403);
        }

        $planillaId = $elemento->planilla_id;

        \DB::beginTransaction();
        try {
            // 🧠 Máquina real original (antes del cambio)
            $maquinaOriginal = $this->obtenerMaquinaReal($elemento);

            // 👉 Quitar asignación
            if (empty($valor)) {
                $elemento->$campo = null;
                $elemento->save();

                // 🧹 OrdenPlanilla si se queda vacía la máquina original
                $quedanElementos = \App\Models\Elemento::where('planilla_id', $planillaId)
                    ->get()
                    ->filter(fn($e) => $this->obtenerMaquinaReal($e) === $maquinaOriginal)
                    ->isNotEmpty();

                if (!$quedanElementos) {
                    $ordenOriginal = \App\Models\OrdenPlanilla::where('planilla_id', $planillaId)
                        ->where('maquina_id', $maquinaOriginal)
                        ->first();

                    if ($ordenOriginal) {
                        $pos = $ordenOriginal->posicion;
                        $ordenOriginal->delete();

                        \App\Models\OrdenPlanilla::where('maquina_id', $maquinaOriginal)
                            ->where('posicion', '>', $pos)
                            ->decrement('posicion');
                    }
                }

                \DB::commit();
                return response()->json([
                    'ok' => true,
                    'campo' => $campo,
                    'maquina_id' => null
                ]);
            }

            // ✅ Nueva máquina
            $nuevaMaquina = \App\Models\Maquina::find($valor);
            if (!$nuevaMaquina) {
                \DB::rollBack();
                return response()->json(['error' => 'Máquina no encontrada'], 404);
            }

            // 1️⃣ Cambiar el campo de máquina solicitado y guardar
            $elemento->$campo = $nuevaMaquina->id;
            $elemento->save();

            // 2️⃣ Recalcular máquina real tras el cambio
            $elemento->refresh();
            $nuevaMaquinaReal = (int) $this->obtenerMaquinaReal($elemento);

            // ================== SUBETIQUETA: unir por PREFIJO + NOMBRE, o crear ==================
            $subIdOriginal = $elemento->getOriginal('etiqueta_sub_id'); // antes del cambio de sub
            $etiquetaPadre = \App\Models\Etiqueta::lockForUpdate()->findOrFail($elemento->etiqueta_id);

            $codigoPadre = (string) $etiquetaPadre->codigo;      // p.ej. ETQ2509010
            $prefijoSub  = $codigoPadre . '.';                   // p.ej. ETQ2509010.
            $nombreObj   = \Illuminate\Support\Str::of((string)$etiquetaPadre->nombre)
                ->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim()->__toString();

            // Candidatos: misma etiqueta_id, sub-id con el prefijo
            $candidatos = \App\Models\Elemento::where('etiqueta_id', $elemento->etiqueta_id)
                ->whereNotNull('etiqueta_sub_id')
                ->where('etiqueta_sub_id', 'like', $prefijoSub . '%')
                ->lockForUpdate()
                ->get();

            // Grupos por sub-id cuyos elementos están TODOS en la máquina destino
            $gruposValidos = $candidatos->groupBy('etiqueta_sub_id')->filter(
                fn($grupo) => $grupo->every(fn($e) => (int) $this->obtenerMaquinaReal($e) === $nuevaMaquinaReal)
            );

            // Filtro por NOMBRE (de la fila etiquetas del sub-id)
            $subIds = $gruposValidos->keys()->values()->all();
            $nombresPorSub = collect();
            if (!empty($subIds)) {
                $nombresPorSub = \App\Models\Etiqueta::whereIn('etiqueta_sub_id', $subIds)
                    ->pluck('nombre', 'etiqueta_sub_id')
                    ->map(fn($n) => \Illuminate\Support\Str::of((string)$n)->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim()->__toString());
            }

            $gruposCompatibles = $gruposValidos->filter(function ($grupo, $subId) use ($nombresPorSub, $nombreObj) {
                // solo une si existe fila de etiquetas para ese subId y coincide el nombre normalizado
                if (!$nombresPorSub->has($subId)) return false;
                return $nombresPorSub[$subId] === $nombreObj;
            });

            // Elegir sub-id destino: más poblado; empate → sufijo más bajo
            $subDestinoHermano = null;
            if ($gruposCompatibles->isNotEmpty()) {
                $subDestinoHermano = $gruposCompatibles->sort(function ($a, $b) {
                    $cntA = $a->count();
                    $cntB = $b->count();
                    if ($cntA !== $cntB) return $cntB <=> $cntA;
                    $subA = $a->first()->etiqueta_sub_id;
                    $subB = $b->first()->etiqueta_sub_id;
                    $numA = (int) (preg_match('/\.(\d+)$/', $subA, $mA) ? $mA[1] : 9999);
                    $numB = (int) (preg_match('/\.(\d+)$/', $subB, $mB) ? $mB[1] : 9999);
                    return $numA <=> $numB;
                })->keys()->first();
            }

            // ¿El sub-id original está compartido por otros?
            $estaCompartido = $subIdOriginal
                ? \App\Models\Elemento::where('etiqueta_sub_id', $subIdOriginal)
                ->where('id', '!=', $elemento->id)
                ->lockForUpdate()
                ->exists()
                : false;

            // Decidir sub-id destino
            $subIdDestino = $subIdOriginal; // por defecto conserva si iba solo
            if ($subDestinoHermano) {
                // Reunirse SOLO si nombre y prefijo coinciden
                $subIdDestino = $subDestinoHermano;
            } else {
                // No hay hermano compatible: si no tenía sub o estaba compartido → crear nuevo
                if (!$subIdOriginal || $estaCompartido) {
                    $subIdDestino = \App\Models\Etiqueta::generarCodigoSubEtiqueta($codigoPadre);
                    // Crear fila etiquetas de la sub con el nombre del padre (si no existe)
                    $existeSub = \App\Models\Etiqueta::where('etiqueta_sub_id', $subIdDestino)->exists();
                    if (!$existeSub) {
                        $dataNueva = [
                            'codigo'          => $codigoPadre,
                            'etiqueta_sub_id' => $subIdDestino,
                            'planilla_id'     => $etiquetaPadre->planilla_id,
                            'nombre'          => $etiquetaPadre->nombre,
                            'estado'          => $etiquetaPadre->estado ?? 'pendiente',
                            'peso'            => 0.0,
                        ];
                        foreach (
                            [
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
                                'fecha_finalizacion_soldadura',
                            ] as $col
                        ) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('etiquetas', $col)) {
                                $dataNueva[$col] = $etiquetaPadre->$col;
                            }
                        }
                        \App\Models\Etiqueta::create($dataNueva);
                    }
                }
            }

            // Aplicar sub-id si cambió
            if ($subIdDestino !== $subIdOriginal) {
                $elemento->etiqueta_sub_id = $subIdDestino;
                $elemento->save();
            }

            // ================== PESOS: DELTA (resta en origen / suma en destino) ==================
            $pesoDelta = (float) ($elemento->peso ?? 0);

            // 1) Origen −delta (si cambió)
            if ($subIdOriginal && $subIdOriginal !== $subIdDestino && \Illuminate\Support\Facades\Schema::hasColumn('etiquetas', 'peso')) {
                \App\Models\Etiqueta::where('etiqueta_sub_id', $subIdOriginal)
                    ->update(['peso' => \DB::raw('GREATEST(peso - ' . $pesoDelta . ', 0)')]);

                // Si se queda sin elementos, borrar filas de ese subId
                $quedan = \App\Models\Elemento::where('etiqueta_sub_id', $subIdOriginal)->exists();
                if (!$quedan) {
                    \App\Models\Etiqueta::where('etiqueta_sub_id', $subIdOriginal)->delete();
                }
            }

            // 2) Destino +delta
            if ($subIdDestino && \Illuminate\Support\Facades\Schema::hasColumn('etiquetas', 'peso')) {
                $tocadas = \App\Models\Etiqueta::where('etiqueta_sub_id', $subIdDestino)
                    ->update(['peso' => \DB::raw('peso + ' . $pesoDelta)]);

                // Si no había fila aún (caso creación), ajusta al peso real actual
                if ($tocadas === 0) {
                    $pesoActual = (float) \App\Models\Elemento::where('etiqueta_sub_id', $subIdDestino)->sum('peso');
                    \App\Models\Etiqueta::where('etiqueta_sub_id', $subIdDestino)
                        ->update(['peso' => $pesoActual]);
                }
            }

            // 3) Actualizar PADRE (fila con etiqueta_sub_id NULL) agregando todas sus sub-filas
            if (\Illuminate\Support\Facades\Schema::hasColumn('etiquetas', 'peso')) {
                $filaPadre = \App\Models\Etiqueta::lockForUpdate()
                    ->where('codigo', $codigoPadre)
                    ->whereNull('etiqueta_sub_id')
                    ->first();

                if ($filaPadre) {
                    $pesoPadre = (float) \App\Models\Elemento::where('etiqueta_sub_id', 'like', $codigoPadre . '.%')->sum('peso');
                    $filaPadre->peso = $pesoPadre;
                    $filaPadre->save();
                }
            }
            // ================== FIN sub-etiquetas y pesos ==================

            // 4️⃣ Asegurar entrada en orden_planillas para la nueva máquina
            $yaExiste = \App\Models\OrdenPlanilla::where('planilla_id', $planillaId)
                ->where('maquina_id', $nuevaMaquinaReal)
                ->exists();

            if (!$yaExiste) {
                $posiciones = \App\Models\OrdenPlanilla::where('planilla_id', $planillaId)->pluck('posicion');
                $nuevaPos = $posiciones->isNotEmpty() ? intval(round($posiciones->avg())) : 1;

                // Saltar posiciones "fabricando"
                $posFinal = $nuevaPos;
                $ocupada = \App\Models\OrdenPlanilla::with('planilla')
                    ->where('maquina_id', $nuevaMaquinaReal)
                    ->where('posicion', $posFinal)
                    ->first();

                while ($ocupada && $ocupada->planilla && $ocupada->planilla->estado === 'fabricando') {
                    $posFinal++;
                    $ocupada = \App\Models\OrdenPlanilla::with('planilla')
                        ->where('maquina_id', $nuevaMaquinaReal)
                        ->where('posicion', $posFinal)
                        ->first();
                }

                \App\Models\OrdenPlanilla::where('maquina_id', $nuevaMaquinaReal)
                    ->where('posicion', '>=', $posFinal)
                    ->increment('posicion');

                \App\Models\OrdenPlanilla::create([
                    'planilla_id' => $planillaId,
                    'maquina_id'  => $nuevaMaquinaReal,
                    'posicion'    => $posFinal,
                ]);
            }

            // 5️⃣ Limpiar orden_planillas si la máquina original quedó vacía
            $quedanElementos = \App\Models\Elemento::where('planilla_id', $planillaId)
                ->get()
                ->filter(fn($e) => $this->obtenerMaquinaReal($e) === $maquinaOriginal)
                ->isNotEmpty();

            if (!$quedanElementos) {
                $ordenOriginal = \App\Models\OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $maquinaOriginal)
                    ->first();

                if ($ordenOriginal) {
                    $posElim = $ordenOriginal->posicion;
                    $ordenOriginal->delete();

                    \App\Models\OrdenPlanilla::where('maquina_id', $maquinaOriginal)
                        ->where('posicion', '>', $posElim)
                        ->decrement('posicion');
                }
            }

            \DB::commit();

            return response()->json([
                'ok' => true,
                'campo' => $campo,
                'maquina_id' => $nuevaMaquina->id
            ]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error("Error al actualizar elemento {$elemento->id}: " . $e->getMessage(), [
                'campo' => $campo,
                'valor' => $valor,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
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
        if ($tipo1 === 'dobladora manual') return $e->maquina_id;
        if ($tipo2 === 'dobladora manual') return $e->maquina_id_2;

        return $e->maquina_id;
    }
    /**
     * Divide un elemento en N partes, repartiendo peso, barras y tiempo de fabricación.
     * Crea nuevas etiquetas para cada parte.
     */

    public function dividirElemento(Request $request)
    {
        $request->validate([
            'elemento_id' => 'required|exists:elementos,id',
            'num_nuevos'  => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                /** @var \App\Models\Elemento $elemento */
                $elemento = Elemento::lockForUpdate()
                    ->with('etiquetaRelacion') // relación a Etiqueta (ajústala si el nombre difiere)
                    ->findOrFail($request->elemento_id);

                // Partes = original + N nuevos
                $nuevos      = (int) $request->num_nuevos;
                $totalPartes = $nuevos + 1;

                // === Reparto de PESO ===
                $pesoTotal = (float) ($elemento->peso ?? 0);
                $pesoBase  = $pesoTotal / $totalPartes;
                // redondeo: ajusta la precisión a tu necesidad (3 decimales típico en kg)
                $prec      = 3;
                $pesos     = array_fill(0, $totalPartes, round($pesoBase, $prec));
                // corrige para que la suma cuadre exactamente con el total
                $diff = round($pesoTotal - array_sum($pesos), $prec);
                $pesos[$totalPartes - 1] = round($pesos[$totalPartes - 1] + $diff, $prec);

                // === Reparto de BARRAS (enteros) ===
                $barrasTotal = (int) ($elemento->barras ?? 0);
                $barrasBase  = intdiv($barrasTotal, $totalPartes);
                $resto       = $barrasTotal % $totalPartes;
                $barrasParts = array_fill(0, $totalPartes, $barrasBase);
                for ($i = 0; $i < $resto; $i++) {
                    $barrasParts[$i] += 1; // reparte +1 a las primeras $resto partes
                }

                // === Etiqueta base y sufijos por CODIGO (no por etiqueta_sub_id) ===
                $etqOriginal = $elemento->etiquetaRelacion
                    ?: Etiqueta::lockForUpdate()->findOrFail($elemento->etiqueta_id);

                // Tomamos el CODIGO de la etiqueta original como raíz
                $baseCodigo = $etqOriginal->codigo ?: preg_replace('/[.\-]\d+$/', '', (string) $etqOriginal->etiqueta_sub_id);

                // Bloquea la serie de ese codigo y obtiene el sufijo máximo ya usado para ese código
                $maxSufijo = (int) DB::table('etiquetas')
                    ->where('codigo', $baseCodigo)
                    ->lockForUpdate()
                    ->selectRaw("COALESCE(MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)), 0) AS max_suf")
                    ->value('max_suf');

                // === Reparto de TIEMPO DE FABRICACIÓN (numérico, entero en minutos o segundos) ===
                $tiempoTotal = (int) ($elemento->tiempo_fabricacion ?? 0);
                $tiempoBase  = intdiv($tiempoTotal, $totalPartes);
                $restoTiempo = $tiempoTotal % $totalPartes;
                $tiempos     = array_fill(0, $totalPartes, $tiempoBase);
                for ($i = 0; $i < $restoTiempo; $i++) {
                    $tiempos[$i] += 1;
                }

                // === 1) Actualiza ORIGINAL
                $elemento->peso               = $pesos[0];
                $elemento->barras             = $barrasParts[0];
                $elemento->tiempo_fabricacion = $tiempos[0];
                $elemento->save();

                // Si tu etiqueta representa solo ese elemento, actualiza su peso:
                $etqOriginal->peso = $pesos[0];
                $etqOriginal->save();

                // === 2) Crea N CLONES: cada uno con etiqueta nueva y sus pesos/barras ===
                for ($i = 1; $i < $totalPartes; $i++) {

                    // 2.1 Generar etiqueta_sub_id libre para ESTE codigo
                    $maxSufijo++;
                    $nuevoSubId = sprintf('%s.%02d', $baseCodigo, $maxSufijo);
                    // seguridad extra ante huecos ocupados (raro con lockForUpdate, pero por si acaso):
                    while (DB::table('etiquetas')->where('etiqueta_sub_id', $nuevoSubId)->exists()) {
                        $maxSufijo++;
                        $nuevoSubId = sprintf('%s.%02d', $baseCodigo, $maxSufijo);
                    }

                    // 2.2 Clonar Etiqueta (replica campos, asigna codigo y sub_id, y PESO de la parte)
                    $nuevaEtiqueta = $etqOriginal->replicate();
                    $nuevaEtiqueta->codigo          = $baseCodigo;
                    $nuevaEtiqueta->etiqueta_sub_id = $nuevoSubId;
                    $nuevaEtiqueta->peso            = $pesos[$i];
                    // (opcional) reset de tiempos/estados si procede:
                    // $nuevaEtiqueta->fecha_inicio = null;
                    // $nuevaEtiqueta->fecha_finalizacion = null;
                    // $nuevaEtiqueta->estado = 'pendiente';
                    $nuevaEtiqueta->save();

                    // 2.3 Clonar Elemento con CODIGO nuevo y reparto de peso/barras
                    $clon = $elemento->replicate(); // replica del ORIGINAL ya actualizado
                    $clon->codigo         = Elemento::generarCodigo(); // tu generador ELyymmXXXX
                    $clon->peso           = $pesos[$i];
                    $clon->barras         = $barrasParts[$i];
                    $clon->etiqueta_id    = $nuevaEtiqueta->id;
                    $clon->etiqueta_sub_id = $nuevaEtiqueta->etiqueta_sub_id;
                    $clon->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'El elemento se dividió correctamente en ' . $totalPartes . ' partes',
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
        $turno = Turno::where('hora_entrada', '<=', $horaActual)
            ->where('hora_salida', '>=', $horaActual)
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
            Log::info("Entrando al metodo...");
            $elemento = Elemento::findOrFail($id);
            $nuevaMaquinaId = $request->maquina_id;

            if ($elemento->maquina_id == $nuevaMaquinaId) {
                Log::info("El elemento ya pertenece a esa maquina");
            }

            $prefijo = (int) $elemento->etiqueta_sub_id;

            // Buscar hermanos en la nueva máquina con mismo prefijo
            $hermano = Elemento::where('maquina_id', $nuevaMaquinaId)
                ->where('etiqueta_sub_id', 'like', "$prefijo.%")
                ->first();
            Log::info("Buscando a mirmano");

            if ($hermano) {
                $elemento->etiqueta_sub_id = $hermano->etiqueta_sub_id;
            } else {
                $sufijos = Elemento::where('etiqueta_sub_id', 'like', "$prefijo.%")
                    ->pluck('etiqueta_sub_id')
                    ->map(fn($e) => (int) explode('.', $e)[1])
                    ->toArray();
                $next = empty($sufijos) ? 1 : (max($sufijos) + 1);
                $elemento->etiqueta_sub_id = "$prefijo.$next";
            }

            $elemento->maquina_id = $nuevaMaquinaId;
            $elemento->save();
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

            // 🚚 Si cambió la máquina, recalcular etiqueta_sub_id
            if (
                array_key_exists('maquina_id', $validated)
                && $validated['maquina_id'] != $elemento->maquina_id
            ) {
                $nuevoMaquinaId = $validated['maquina_id'];
                $prefijo = (int) $elemento->etiqueta_sub_id; // parte antes del punto

                // 1) Buscar hermanos en la máquina destino con ese mismo prefijo
                $hermano = Elemento::where('maquina_id', $nuevoMaquinaId)
                    ->where('etiqueta_sub_id', 'like', "$prefijo.%")
                    ->first();

                if ($hermano) {
                    // Si existe, reutilizar la misma etiqueta_sub_id
                    $validated['etiqueta_sub_id'] = $hermano->etiqueta_sub_id;
                } else {
                    // 2) No hay hermanos; generar siguiente sufijo libre
                    $sufijos = Elemento::where('etiqueta_sub_id', 'like', "$prefijo.%")
                        ->pluck('etiqueta_sub_id')
                        ->map(function ($full) use ($prefijo) {
                            return (int) explode('.', $full)[1];
                        })
                        ->toArray();

                    $next = empty($sufijos) ? 1 : (max($sufijos) + 1);
                    $validated['etiqueta_sub_id'] = "$prefijo.$next";
                }
            }

            // Actualizar resto de campos
            $elemento->fill($validated);

            if ($elemento->isDirty()) {
                if ($elemento->isDirty('estado')) {
                    Log::debug("⚠️ Estado sí cambió: {$elemento->getOriginal('estado')} → {$elemento->estado}");
                }
                $elemento->save();
            }


            // Si cambió de máquina, actualizar orden_planillas
            if (array_key_exists('maquina_id', $validated) && $validated['maquina_id'] != $elemento->getOriginal('maquina_id')) {
                $planillaId = $elemento->planilla_id;
                $nuevaMaquinaId = $validated['maquina_id'];
                $maquinaAnteriorId = $elemento->getOriginal('maquina_id');

                // 1. Insertar en nueva máquina si no existe
                $existe = OrdenPlanilla::where('planilla_id', $planillaId)
                    ->where('maquina_id', $nuevaMaquinaId)
                    ->exists();

                if (!$existe) {
                    $ultimaPosicion = OrdenPlanilla::where('maquina_id', $nuevaMaquinaId)->max('posicion') ?? 0;

                    OrdenPlanilla::create([
                        'planilla_id' => $planillaId,
                        'maquina_id' => $nuevaMaquinaId,
                        'posicion' => $ultimaPosicion + 1,
                    ]);
                }

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
}
