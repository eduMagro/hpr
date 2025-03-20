<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Alerta;
use App\Models\AsignacionTurno;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Maquina;
use Illuminate\Support\Facades\Log;


class etiquetaController extends Controller
{
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
            'ensambladorRelacion',
            'ensamblador2Relacion'
        ])->orderBy('created_at', 'desc'); // Ordenar por fecha de creación descendente

        // Filtrar por ID si está presente
        $query->when($request->filled('id'), function ($q) use ($request) {
            $q->where('id', $request->id);
        });

        // Filtrar por Estado si está presente
        $query->when($request->filled('estado'), function ($q) use ($request) {
            $q->where('estado', $request->estado);
        });

        // Filtrar por Código de Planilla con búsqueda parcial (LIKE)
        if ($request->filled('codigo_planilla')) {
            $planillas = Planilla::where('codigo', 'LIKE', '%' . $request->codigo_planilla . '%')->pluck('id');

            if ($planillas->isNotEmpty()) {
                $query->whereIn('planilla_id', $planillas);
            } else {
                return redirect()->back()->with('error', 'No se encontraron planillas con ese código.');
            }
        }

        // Paginación de la tabla
        $etiquetas = $query->paginate(10)->appends($request->query());

        // Obtener todas las etiquetas con elementos (sin paginar) para JavaScript
        $etiquetasJson = Etiqueta::with('elementos')->get();

        return view('etiquetas.index', compact('etiquetas', 'etiquetasJson'));
    }

    public function actualizarEtiqueta(Request $request, $id, $maquina_id)
    {
        DB::beginTransaction();
        try {
            $warnings = []; // Array para acumular mensajes de alerta
            // Array para almacenar los productos consumidos y su stock actualizado
            $productosAfectados = [];
            // Obtener la etiqueta y su planilla asociada
            $etiqueta = Etiqueta::with('elementos.planilla')->findOrFail($id);
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
            $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('estado', 'completado')->count();
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
                        ->whereIn('diametro', $diametrosRequeridos)
                        ->orderBy('peso_stock')
                        ->get();
                    if ($productos->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'No se encontraron productos en la máquina con los diámetros especificados.',
                        ], 400);
                    }
                    // Agrupar productos por diámetro (asegurando que sean enteros)
                    $productosAgrupados = $productos->groupBy(fn($producto) => (int) $producto->diametro);

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
                        $productosPorDiametro = $productos->where('diametro', $diametro);
                        $stockTotal = $productosPorDiametro->sum('peso_stock');

                        if ($stockTotal < $pesoNecesario) {
                            // Acumular el mensaje de alerta para el log
                            $warnings[] = "El stock para el {$diametro} es insuficiente. Avisaremos a los gruistas en turno.";

                            // Obtener solo los gruistas que tienen asignado turno en la fecha actual
                            $gruistasEnTurno = User::where('categoria', 'gruista')
                                ->whereHas('asignacionesTurnos', function ($query) {
                                    $query->where('fecha', Carbon::now()->toDateString());
                                })
                                ->get();

                            // Si existen gruistas en turno, se crea una alerta por cada uno
                            if ($gruistasEnTurno->isNotEmpty()) {
                                foreach ($gruistasEnTurno as $gruista) {
                                    Alerta::create([
                                        'mensaje'      => "Stock insuficiente para el diámetro {$diametro} en la máquina {$maquina->nombre}.",
                                        'destinatario_id' => $gruista->id, // El destinatario es el gruista en turno
                                        'user_id_1'    => Auth::id(),   // Emisor de la alerta
                                        'user_id_2'    => session()->get('compañero_id', null),
                                        'leida'        => false,
                                    ]);
                                }
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
                // -------------------------------------------- ESTADO PARCIALMENTE COMPLETADA --------------------------------------------
                case 'parcialmente_completada':

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
                    } else {
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

                    // -------------- CONSUMOS
                    $consumos = [];

                    foreach ($diametrosConPesos as $diametro => $pesoNecesarioTotal) {
                        // Si la máquina es ID 7, solo permitir diámetro 5
                        if ($maquina->tipo == 'ensambladora' && $diametro != 5) {
                            continue; // Saltar cualquier otro diámetro
                        }

                        $productosPorDiametro = $maquina->productos()
                            ->where('diametro', $diametro)
                            ->orderBy('peso_stock')
                            ->get();

                        if ($productosPorDiametro->isEmpty()) {
                            return response()->json([
                                'success' => false,
                                'error' => "No se encontraron materias primas para el diámetro {$diametro} en la máquina.",
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
                                'error' => "No hay suficiente materia prima para el diámetro {$diametro}.",
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
            Log::info("Entra en el condicional para completar elementos");
            $elemento->estado = "completado";
            $elemento->users_id = Auth::id();
            $elemento->users_id_2 = session()->get('compañero_id', null);
            $elemento->save();
        }

        // ✅ ACTUALIZAR EL CONTADOR DE ELEMENTOS COMPLETADOS
        $numeroElementosCompletadosEnMaquina = $elementosEnMaquina->where('estado', 'completado')->count();

        // -------------- CONSUMOS
        $consumos = [];
        foreach ($elementosEnMaquina->groupBy('diametro') as $diametro => $elementos) {
            // Si la máquina es ID 7, solo permitir diámetro 5
            if ($maquina->tipo == 'ensambladora' && $diametro != 5) {
                continue; // Saltar cualquier otro diámetro
            }
            $pesoNecesarioTotal = $elementos->sum('peso');

            $productosPorDiametro = $maquina->productos()
                ->where('diametro', $diametro)
                ->orderBy('peso_stock')
                ->get();

            if ($productosPorDiametro->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => "No se encontraron materias primas para el diámetro {$diametro} en la máquina.",
                ], 400);
            }

            $consumos[$diametro] = [];
            foreach ($productosPorDiametro as $producto) {
                if ($pesoNecesarioTotal <= 0) {
                    break;
                }

                $pesoInicial = $producto->peso_inicial ?? $producto->peso_stock;

                $restar = min($producto->peso_stock, $pesoNecesarioTotal);
                $producto->peso_stock -= $restar;
                $pesoNecesarioTotal -= $restar;

                if ($producto->peso_stock == 0) {
                    $producto->estado = "consumido";
                    $producto->ubicacion_id = NULL;
                    $producto->maquina_id = NULL;
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
                return response()->json([
                    'success' => false,
                    'error' => "No hay suficiente materia prima para el diámetro {$diametro}.",
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
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();
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
                ->where('estado', '!=', 'completado')
                ->doesntExist();

            if ($elementosEtiquetaCompletos) {
                // Si la máquina actual es de tipo "estribadora", asignamos una ensambladora
                if ($maquina->tipo === 'estribadora') {
                    $etiqueta->estado = 'fabricada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                } else {
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                }
            } else {
                // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                if ($enOtrasMaquinas) {
                    $etiqueta->estado = 'parcialmente_completada';
                    $etiqueta->save();
                }
            }
            // Si la máquina actual es de tipo "estribadora", asignamos una ensambladora
            if ($maquina->tipo === 'estribadora') {
                $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();
                if (!$maquinaEnsambladora) {
                    throw new \Exception("No se encontró una máquina ensambladora disponible.");
                }
                foreach ($elementosEnMaquina as $elemento) {
                    $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                    $elemento->save();
                }
            }
        } else {

            // Verificar si todos los elementos de la etiqueta están en estado "completado"
            $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();

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
        $todosElementosPlanillaCompletos = $planilla->elementos()->where('estado', '!=', 'completado')->doesntExist();
        if ($todosElementosPlanillaCompletos) {
            $planilla->fecha_finalizacion = now();
            $planilla->estado = 'completada';
            $planilla->save();
        }

        return true;
    }

    public function verificarEtiquetas(Request $request)
    {
        $etiquetas = $request->input('etiquetas', []);

        // Buscar etiquetas que no estén en estado "completado"
        $etiquetasIncompletas = Etiqueta::whereIn('id', $etiquetas)
            ->where('estado', '!=', 'completado')
            ->pluck('id') // Solo obtiene los IDs de las etiquetas incompletas
            ->toArray();

        if (!empty($etiquetasIncompletas)) {
            return response()->json([
                'success' => false,
                'message' => 'Algunas etiquetas no están completas.',
                'etiquetas_incompletas' => $etiquetasIncompletas,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Todas las etiquetas están completas.'
        ]);
    }
}
