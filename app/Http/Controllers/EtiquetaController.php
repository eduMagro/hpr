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
use Exception;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Services\CompletarLoteService;


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
            'planilla',
            'elementos',
            'paquete',
            'producto',
            'producto2',
            'soldador1',
            'soldador2',
            'ensamblador1',
            'ensamblador2',
        ])->whereNotNull('etiqueta_sub_id');

        // Aplicar filtros y ordenamiento seguros
        $query = $this->aplicarFiltros($query, $request);
        $query = $this->aplicarOrdenamiento($query, $request);

        // Paginación
        $etiquetas = $query->paginate($request->input('per_page', 10))->appends($request->except('page'));

        // JSON para scripts
        $etiquetasJson = Etiqueta::select('id', 'etiqueta_sub_id', 'nombre', 'peso', 'estado', 'fecha_inicio', 'fecha_finalizacion', 'planilla_id')
            ->whereNotNull('etiqueta_sub_id')
            ->with([
                'planilla' => function ($q) {
                    $q->select('id', 'obra_id', 'cliente_id', 'codigo', 'seccion')
                        ->with(['obra:id,obra', 'cliente:id,empresa']);
                },
                'elementos' => function ($q) {
                    $q->select('id', 'etiqueta_id', 'dimensiones', 'barras', 'diametro', 'peso');
                }
            ])->get()->keyBy('id');

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
    public function fabricacionOptimizada(Request $request)
    {
        $data = $request->validate([
            'producto_base' => ['required', 'array'],
            'producto_base.longitud_barra_cm' => ['required', 'numeric', 'min:1'],
            'repeticiones' => ['required', 'integer', 'min:1'],
            'etiquetas' => ['required', 'array', 'min:1'],

            'etiquetas.*.etiqueta_sub_id' => ['required', 'string'], // ¡no int!
            'etiquetas.*.elementos' => ['required', 'array', 'min:1'],
            'etiquetas.*.elementos.*' => ['integer'],

            // opcional: si en el diálogo eliges máquina por subetiqueta
            'etiquetas.*.maquina_id' => ['nullable', 'integer', Rule::exists('maquinas', 'id')],
        ]);

        $longitudSeleccionada = (int) ($data['producto_base']['longitud_barra_cm'] ?? 0);
        $userId        = Auth::id();
        $companeroId   = auth()->user()->compañeroDeTurno()?->id;

        $resultados = [];

        DB::beginTransaction();
        try {
            /** @var \App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio $fabrica */
            $fabrica = app(\App\Servicios\Etiquetas\Fabrica\FabricaEtiquetaServicio::class);

            foreach ($data['etiquetas'] as $item) {
                $subId = (string) $item['etiqueta_sub_id'];

                // 1) localizar etiqueta
                $etiqueta = Etiqueta::where('etiqueta_sub_id', $subId)->firstOrFail();

                // 2) resolver máquina: payload -> etiqueta -> (si quieres, tu heurística)
                $maquinaId = $item['maquina_id'] ?? $etiqueta->maquina_id;
                if (!$maquinaId) {
                    throw new \RuntimeException("No se pudo determinar la máquina para {$subId}");
                }
                $maquina = Maquina::findOrFail($maquinaId);

                // 3) construir DTO igual que en actualizarEtiqueta()
                $dto = new \App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos(
                    etiquetaSubId: $subId,                      // string, respeta ".01"
                    maquinaId: (int) $maquina->id,
                    longitudSeleccionada: $longitudSeleccionada,
                    operario1Id: $userId,
                    operario2Id: $companeroId,
                    opciones: ['origen' => 'optimizada']        // flag opcional
                );

                // 4) llamar al mismo servicio/factoría
                $servicio  = $fabrica->porMaquina($maquina);
                $resultado = $servicio->actualizar($dto);

                $resultados[] = [
                    'etiqueta_sub_id' => $subId,
                    'estado'          => $resultado->etiqueta->estado ?? null,
                    'warnings'        => $resultado->warnings ?? [],
                ];
            }

            DB::commit();

            return response()->json([
                'success'   => true,
                'message'   => 'Se han puesto en fabricación las subetiquetas implicadas.',
                'resultados' => $resultados,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Error en EtiquetaController@fabricacionOptimizada', [
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
                'payload'   => $data,
                'user_id'   => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo lanzar la fabricación optimizada.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function actualizarEtiqueta(Request $request, $id, $maquina_id)
    {
        // Delegación a servicios (nuevo flujo)
        try {
            $maquina = Maquina::findOrFail($maquina_id);

            $dto = new \App\Servicios\Etiquetas\DTOs\ActualizarEtiquetaDatos(
                etiquetaSubId: $id,
                maquinaId: (int) $maquina_id,
                longitudSeleccionada: $request->input('longitud'),
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
        } catch (\Throwable $e) {
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
                'error' => $e->getMessage(),
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
