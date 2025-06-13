<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Alerta;
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

class EtiquetaController extends Controller
{
    private function aplicarFiltros($query, Request $request)
    {
        $query->when($request->filled('id'), fn($q) => $q->where('id', $request->id));

        $query->when($request->filled('estado'), fn($q) => $q->where('estado', $request->estado));

        if ($request->filled('codigo_planilla')) {
            $planillas = Planilla::where('codigo', 'LIKE', '%' . $request->codigo_planilla . '%')->pluck('id');

            if ($planillas->isNotEmpty()) {
                $query->whereIn('planilla_id', $planillas);
            } else {
                session()->flash('error', 'No se encontraron planillas con ese código.');
            }
        }

        // Añadir más filtros aquí si agregas nuevos campos en el encabezado
    }
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id')) {
            $filtros[] = 'ID: <strong>' . $request->id . '</strong>';
        }

        if ($request->filled('codigo_planilla')) {
            $filtros[] = 'Código Planilla: <strong>' . $request->codigo_planilla . '</strong>';
        }

        if ($request->filled('estado')) {
            $estado = ucfirst($request->estado);
            $filtros[] = 'Estado: <strong>' . $estado . '</strong>';
        }

        if ($request->filled('numero_etiqueta')) {
            $filtros[] = 'Número de Etiqueta: <strong>' . $request->numero_etiqueta . '</strong>';
        }

        if ($request->filled('nombre')) {
            $filtros[] = 'Nombre: <strong>' . $request->nombre . '</strong>';
        }

        // Agrega más condiciones según los campos filtrables

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
    public function index(Request $request)
    {
        // 1️⃣  Columnas que SÍ se pueden ordenar
        $sortable = [
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

        // 2️⃣  Obtener sort & order, con valores por defecto seguros
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        // Normalizar y validar
        if (! in_array($sort, $sortable)) {
            $sort = 'created_at';
        }
        $order = $order === 'asc' ? 'asc' : 'desc';

        // 3️⃣  Construir la consulta
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
        ])
            ->whereNotNull('etiqueta_sub_id')
            ->orderBy($sort, $order);          // ← aquí aplicamos lo elegido

        // 4️⃣  Resto de la lógica
        $this->aplicarFiltros($query, $request);

        $perPage   = $request->input('per_page', 10);
        $etiquetas = $query->paginate($perPage)->appends($request->except('page'));

        $etiquetasJson = Etiqueta::with('elementos')
            ->whereNotNull('etiqueta_sub_id')->get();
        $filtrosActivos = $this->filtrosActivos($request);

        // 5️⃣  Seguir usando tu helper para los encabezados
        $ordenables = [
            'id'            => $this->getOrdenamiento('id', 'ID'),
            'codigo'        => $this->getOrdenamiento('codigo', 'Código'),
            'codigo_planilla' => $this->getOrdenamiento('codigo_planilla', 'Planilla'),
            'etiqueta'      => $this->getOrdenamiento('etiqueta', 'Etiqueta'),
            'etiqueta_sub_id'   => $this->getOrdenamiento('etiqueta_sub_id', 'etiqueta_sub_id'),
            'paquete_id'    => $this->getOrdenamiento('paquete_id', 'Paquete'),
            'maquina'       => $this->getOrdenamiento('maquina', 'Maq. 1'),
            'maquina_2'     => $this->getOrdenamiento('maquina_2', 'Maq. 2'),
            'maquina3'      => $this->getOrdenamiento('maquina3', 'Maq. 3'),
            'producto1'     => $this->getOrdenamiento('producto1', 'M. Prima 1'),
            'producto2'     => $this->getOrdenamiento('producto2', 'M. Prima 2'),
            'producto3'     => $this->getOrdenamiento('producto3', 'M. Prima 3'),
            'figura'        => $this->getOrdenamiento('figura', 'Figura'),
            'peso'          => $this->getOrdenamiento('peso', 'Peso (kg)'),
            'diametro'      => $this->getOrdenamiento('diametro', 'Diámetro'),
            'longitud'      => $this->getOrdenamiento('longitud', 'Longitud'),
            'estado'        => $this->getOrdenamiento('estado', 'Estado'),
        ];

        return view('etiquetas.index', compact(
            'etiquetas',
            'etiquetasJson',
            'ordenables',
            'filtrosActivos'
        ));
    }

    public function actualizarEtiqueta(Request $request, $id, $maquina_id)
    {
        DB::beginTransaction();
        try {
            $warnings = []; // Array para acumular mensajes de alerta
            // Array para almacenar los productos consumidos y su stock actualizado
            $productosAfectados = [];
            // Obtener la etiqueta y su planilla asociada
            $etiqueta = Etiqueta::with('elementos.planilla')->where('etiqueta_sub_id', $id)->firstOrFail();
            $planilla_id = $etiqueta->planilla_id;
            $planilla = Planilla::find($planilla_id);
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
            $diametrosRequeridos = array_map('intval', array_keys($diametrosConPesos));
            // -------------------------------------------- ESTADO PENDIENTE --------------------------------------------
            switch ($etiqueta->estado) {
                case 'pendiente':

                    // Actualizar el estado de los elementos en la máquina a "fabricando" y asignamos usuarios
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id = Auth::id();
                        $elemento->users_id_2 = session()->get('compañero_id', null);
                        $elemento->estado = "fabricando";
                        $elemento->save();
                    }

                    // ------- CONSUMOS

                    // Obtener los productos disponibles en la máquina con los diámetros requeridos
                    $productos = $maquina->productos()
                        ->whereHas('productoBase', function ($query) use ($diametrosRequeridos) {
                            $query->whereIn('diametro', $diametrosRequeridos);
                        })
                        ->with('productoBase') // para evitar consultas adicionales al acceder a diametro
                        ->orderBy('peso_stock')
                        ->get();

                    if ($productos->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'No se encontraron productos en la máquina con los diámetros especificados.',
                        ], 400);
                    }
                    // Agrupar productos por diámetro (asegurando que sean enteros)
                    $productosAgrupados = $productos->groupBy(fn($producto) => (int) $producto->productoBase->diametro);


                    // Verificar si hay productos para cada diámetro requerido
                    $faltantes = [];
                    foreach ($diametrosRequeridos as $diametro) {
                        if (!$productosAgrupados->has($diametro) || $productosAgrupados[$diametro]->isEmpty()) {
                            $faltantes[] = $diametro;
                        }
                    }

                    // Si hay diámetros sin productos, devolver error
                    if (!empty($faltantes)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'No hay materias primas disponibles para los siguientes diámetros: ' . implode(', ', $faltantes),
                        ], 400);
                    }
                    // Arreglo donde se guardarán los detalles del consumo por diámetro
                    $consumos = []; // Estructura: [ <diametro> => [ ['producto_id' => X, 'consumido' => Y], ... ], ... ]

                    // Para cada diámetro, comprobar que el stock total es suficiente
                    foreach ($diametrosConPesos as $diametro => $pesoNecesario) {
                        $productosPorDiametro = $productos->filter(fn($producto) => $producto->productoBase->diametro == $diametro);

                        $stockTotal = $productosPorDiametro->sum('peso_stock');

                        if ($stockTotal < $pesoNecesario) {
                            if ($stockTotal < $pesoNecesario) {
                                $warnings[] = "El stock para el {$diametro} es insuficiente. Avisaremos a los gruistas en turno.";
                                $this->lanzarAlertaStockInsuficiente($diametro, $maquina->nombre);
                            }
                        }
                    }

                    if ($etiqueta->planilla) {
                        if (is_null($etiqueta->planilla->fecha_inicio)) {
                            $etiqueta->planilla->fecha_inicio = now();
                            $etiqueta->planilla->estado = "fabricando";
                            $etiqueta->planilla->save();
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => 'La etiqueta no tiene una planilla asociada.',
                        ], 400);
                    }

                    // Actualizar la etiqueta a "fabricando"


                    $etiqueta->estado = "fabricando";
                    $etiqueta->operario1_id = Auth::id();
                    $etiqueta->operario2_id = session()->get('compañero_id', null);
                    $etiqueta->fecha_inicio = now();
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
                        $etiqueta->ensamblador1_id = Auth::id();
                        $etiqueta->ensamblador2_id = session()->get('compañero_id', null);
                        $etiqueta->save();
                    } elseif ($maquina->tipo === 'soldadora') {
                        // Si la máquina es de tipo soldadora, se inicia la fase de soldadura:
                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->soldador1_id = Auth::id();
                        $etiqueta->soldador2_id = session()->get('compañero_id', null);
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
                        $etiqueta->soldador1 = Auth::id();
                        $etiqueta->soldador2 = session()->get('compañero_id', null);
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
                        $elemento->users_id = Auth::id();
                        $elemento->users_id_2 = session()->get('compañero_id', null);
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
                $warnings[] = "El stock para el {$diametro} es insuficiente. Avisaremos a los gruistas en turno.";
                Log::info("Entrando al condicional de stock insuficiente para el diámetro {$diametro} en la máquina {$maquina->nombre}.");
                $this->lanzarAlertaStockInsuficiente($diametro, $maquina->nombre);

                return response()->json([
                    'success' => false,
                    'error' => "No hay suficiente materia prima para el diámetro {$diametro} en la máquina {$maquina->nombre}.",
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
                        $etiqueta->estado = 'parcialmente_completada';
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

            // Verificar si todos los elementos de la etiqueta están en estado "completado"
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'fabricado')->doesntExist();

            if ($elementosEtiquetaCompletos) {
                $etiqueta->estado = 'completada';
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();
            } else {
                // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                if ($enOtrasMaquinas) {
                    $etiqueta->estado = 'parcialmente_completada';
                    $etiqueta->save();
                }
            }
        }

        // ✅ Si todos los elementos de la planilla están completados, actualizar la planilla
        $todosElementosPlanillaCompletos = $planilla->elementos()->where('estado', '!=', 'fabricado')->doesntExist();
        if ($todosElementosPlanillaCompletos) {
            $planilla->fecha_finalizacion = now();
            $planilla->estado = 'completada';
            $planilla->save();
        }

        return true;
    }
    private function lanzarAlertaStockInsuficiente($diametro, $maquinaNombre)
    {
        $mensaje = "Stock insuficiente para el diámetro {$diametro} en la máquina {$maquinaNombre}.";

        $gruistasEnTurno = User::whereHas('maquina', function ($q) {
            $q->where('nombre', 'like', '%grua%');
        })
            ->whereHas('asignacionesTurnos', function ($query) {
                $query->where('fecha', Carbon::now()->toDateString());
            })
            ->get();

        Log::info("{$gruistasEnTurno}");
        // if ($gruistasEnTurno->isNotEmpty()) {
        //     foreach ($gruistasEnTurno as $gruista) {
        //         Alerta::create([
        //             'mensaje'         => $mensaje,
        //             'destinatario_id' => $gruista->id,
        //             'user_id_1'       => Auth::id(),
        //             'user_id_2'       => session()->get('compañero_id', null),
        //             'leida'           => false,
        //         ]);
        //     }
        // }
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
}
