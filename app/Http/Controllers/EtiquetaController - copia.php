<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Alerta;
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
            $etiqueta = Etiqueta::with('elementos.planilla')->findOrFail($id);

            $planilla_id = $etiqueta->planilla_id;
            $planilla = Planilla::find($planilla_id);

            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($query) use ($maquina_id) {
                    $query->where('maquina_id', $maquina_id)
                        ->orWhere('maquina_id_2', $maquina_id);
                })
                ->get();
            // ELEMENTOS COMPLETADOS?? SALIMOS DEL CONDICIONAL
            $elementosCompletados = $elementosEnMaquina->where('estado', 'completado')->count();
            // Para la máquina con id 7, incluir los elementos extra (aquellos con maquina_id_2 = 7)

            // Verificar si la etiqueta está repartida en diferentes máquinas
            $enOtrasMaquinas = $etiqueta->elementos()
                ->where('maquina_id', '!=', $maquina_id)
                ->exists();

            $maquina = Maquina::findOrFail($maquina_id);
            if (!$maquina) {
                return response()->json([
                    'success' => false,
                    'error' => 'La máquina asociada al elemento no existe.',
                ], 404);
            }
            // if ($maquina->tipo === 'ensambladora') {
            //     $elementosExtra = Elemento::with('etiquetaRelacion', 'planilla')
            //         ->where('maquina_id_2', $maquina->id)
            //         ->where('maquina_id', '!=', $maquina->id)
            //         ->get();
            //     $elementosEnMaquina = $elementosEnMaquina->merge($elementosExtra);
            // }
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

            $producto1 = null;
            $producto2 = null;

            if ($etiqueta->estado == "pendiente") { // ---------------------------------- P E N D I E N T E

                // Actualizar el estado de los elementos en la máquina a "fabricando"
                foreach ($elementosEnMaquina as $elemento) {
                    $elemento->users_id = Auth::id();
                    $elemento->users_id_2 = session()->get('compañero_id', null);
                    $elemento->estado = "fabricando";
                    $elemento->save();
                }

                // Convertir los diámetros requeridos a enteros
                $diametrosRequeridos = array_map('intval', array_keys($diametrosConPesos));

                // Obtener los productos disponibles en la máquina con los diámetros requeridos
                $productos = $maquina->productos()
                    ->whereIn('diametro', $diametrosRequeridos)
                    ->orderBy('peso_stock')
                    ->get();

                // Depuración: Ver qué diámetros se están obteniendo
                // dd($productos->pluck('diametro')->toArray()); 

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

                // Continúa con la lógica si hay productos para todos los diámetros...


                // Arreglo donde se guardarán los detalles del consumo por diámetro
                $consumos = []; // Estructura: [ <diametro> => [ ['producto_id' => X, 'consumido' => Y], ... ], ... ]

                // Para cada diámetro, comprobar que el stock total es suficiente
                foreach ($diametrosConPesos as $diametro => $pesoNecesario) {
                    $productosPorDiametro = $productos->where('diametro', $diametro);
                    $stockTotal = $productosPorDiametro->sum('peso_stock');

                    if ($stockTotal < $pesoNecesario) {
                        // Acumular el mensaje de alerta
                        $warnings[] = "El stock para el  {$diametro} es insuficiente. Avisaremos a los gruistas.";

                        // Lógica para obtener TODOS los usuarios gruistas y generar una alerta para cada uno
                        $gruistas = User::where('categoria', 'gruista')->get();
                        if ($gruistas->isNotEmpty()) {
                            foreach ($gruistas as $gruista) {
                                Alerta::create([
                                    'mensaje' => "Stock insuficiente para el diámetro {$diametro} en la máquina {$maquina->nombre}.",
                                    'destinatario' => 'gruista',   // Puedes ajustar este campo según tu lógica de negocio
                                    'user_id_1' => Auth::id(),  // Usuario que genera la alerta
                                    'user_id_2' => $gruista->id, // Usuario destinatario (gruista)
                                    'leida' => false,       // Se marca como no leída por defecto
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
            } elseif ($etiqueta->estado == "fabricando") {  // ---------------------------------- F A B R I C A N D O
                // Actualizar el estado de los elementos en la máquina a "fabricando"
                foreach ($elementosEnMaquina as $elemento) {
                    $elemento->users_id = Auth::id();
                    $elemento->users_id_2 = session()->get('compañero_id', null);
                    $elemento->save();
                }

                if (
                    isset($elementosEnMaquina) &&
                    $elementosEnMaquina->count() > 0 &&
                    $elementosCompletados >= $elementosEnMaquina->count() &&
                    in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                ) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => "Todos los elementos en la máquina ya han sido completados.",
                    ], 400);
                }

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

                // **Filtramos los elementos en la máquina** para procesar solo los de diámetro 5 cuando la máquina es 7
                // if ($maquina_id == 7) {
                //     $elementosEnMaquina = $elementosEnMaquina->where('diametro', 5);
                // }

                // Asignar a cada elemento los productos de los cuales se consumió material,
                // de acuerdo al peso que requiere cada uno
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

                    // Asignar hasta dos productos al elemento según lo requerido
                    $elemento->producto_id = $productosAsignados[0] ?? null;
                    $elemento->producto_id_2 = $productosAsignados[1] ?? null;
                    $elemento->producto_id_3 = $productosAsignados[2] ?? null;

                    $elemento->estado = "completado";
                    $ensamblado = strtoupper($etiqueta->planilla->ensamblado);

                    if (
                        (strpos($ensamblado, 'TALLER') !== false || strpos($ensamblado, 'CARCASAS') !== false)
                    ) {

                        if ($maquina->tipo === 'estribadora') {

                            $elemento->maquina_id_2 = 7;
                        } elseif ($maquina->tipo === 'ensambladora') {

                            // Buscar una máquina de soldar disponible
                            $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                                ->whereDoesntHave('elementos') // Verifica si tiene elementos en lugar de etiquetas
                                ->first();

                            // Si no hay máquinas de soldar libres, seleccionar la que recibió un elemento primero
                            if (!$maquinaSoldarDisponible) {
                                $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                                    ->whereHas('elementos', function ($query) {
                                        $query->orderBy('created_at'); // Seleccionar la que lleva más tiempo trabajando
                                    })
                                    ->first();
                            }

                            if ($maquinaSoldarDisponible) {
                                $elemento->maquina_id_3 = $maquinaSoldarDisponible->id;
                            } else {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'No se encontró ninguna máquina de soldar disponible',
                                ], 500);
                            }
                        }
                    }
                    $elemento->save();
                    $etiqueta->save();
                    // Actualizar el registro global de consumos para este diámetro
                    $consumos[$elemento->diametro] = $consumosDisponibles;
                }
                // Verificar si hay al menos un elemento con diámetro 5.00 en el campo "diametro"
                $tiene_dm5 = $etiqueta->elementos->contains(function ($elemento) {
                    return (float) $elemento->diametro === 5.00;
                });

                if (
                    strpos(strtolower($etiqueta->planilla->ensamblado), 'taller') !== false ||
                    strpos(strtolower($etiqueta->planilla->ensamblado), 'carcasas') !== false ||
                    !empty($enOtrasMaquinas)
                ) {
                    Log::info('Vamos a ver que tiene $maquina' . $enOtrasMaquinas);

                    if ($maquina->tipo === 'cortadora_dobladora' && $tiene_dm5) {
                        // Actualizar el estado de los elementos en la máquina a "fabricando"
                        foreach ($elementosEnMaquina as $elemento) {

                            $elemento->estado = "completado";
                            $elemento->save();
                        }
                        $etiqueta->estado = 'ensamblando';
                        $etiqueta->fecha_finalizacion = null;
                    }
                } else {

                    Log::info('Vamos a ver que tiene en otras maquina' . $enOtrasMaquinas);
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                }

                $etiqueta->save();

                // Verificar si todos los elementos de la etiqueta están en estado "completado"
                $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();

                if ($elementosEtiquetaCompletos) {
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                }

                // Si todos los elementos de la planilla están en estado "completado", actualizar la planilla
                $elementosPlanillaCompletos = $etiqueta->planilla->elementos()
                    ->where('estado', '!=', 'completado') // Buscar elementos que NO estén completados
                    ->doesntExist(); // Si no hay ninguno, significa que todos están completados

                if ($elementosPlanillaCompletos) {
                    $planilla->fecha_finalizacion = now(); // Si deseas registrar la fecha de finalización
                    $planilla->estado = "completada"; // Actualizar estado de la planilla
                    $planilla->save();
                }
            } elseif ($etiqueta->estado == "ensamblando") {    // ------------------------------------------------------------ E N S A M B L A N D O

                if (
                    isset($elementosEnMaquina) &&
                    $elementosEnMaquina->count() > 0 &&
                    $elementosCompletados >= $elementosEnMaquina->count() &&
                    in_array($maquina->tipo, ['cortadora_dobladora', 'estribadora'])
                ) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => "Todos los elementos en la máquina ya han sido completados.",
                    ], 400);
                }

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
                    $ensamblado = strtoupper($etiqueta->planilla->ensamblado);

                    if (
                        (strpos($ensamblado, 'TALLER') !== false || strpos($ensamblado, 'CARCASAS') !== false)
                        && $elementosEnMaquina->count() == $etiqueta->elementos->count()
                    ) {

                        // Buscar una máquina de soldar disponible
                        $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                            ->whereDoesntHave('elementos') // Verifica si tiene elementos en lugar de etiquetas
                            ->first();

                        // Si no hay máquinas de soldar libres, seleccionar la que recibió un elemento primero
                        if (!$maquinaSoldarDisponible) {
                            $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                                ->whereHas('elementos', function ($query) {
                                    $query->orderBy('created_at'); // Seleccionar la que lleva más tiempo trabajando
                                })
                                ->first();
                        }

                        if ($maquinaSoldarDisponible) {
                            $elemento->maquina_id_3 = $maquinaSoldarDisponible->id;
                        } else {
                            return response()->json([
                                'success' => false,
                                'error' => 'No se encontró ninguna máquina de soldar disponible',
                            ], 500);
                        }
                    }
                    $elemento->save();
                    $etiqueta->save();
                    // Actualizar el registro global de consumos para este diámetro
                    $consumos[$elemento->diametro] = $consumosDisponibles;
                }
                // Verificar si hay al menos un elemento con diámetro 5.00 
                $tiene_dm5 = $etiqueta->elementos->contains(function ($elemento) {
                    return (float) $elemento->diametro === 5.00;
                });

                if (
                    strpos(strtolower($etiqueta->planilla->ensamblado), 'taller') !== false
                ) {

                    if ($maquina->tipo === 'cortadora_dobladora' && $tiene_dm5) {

                        $etiqueta->estado = 'ensamblando';
                        $etiqueta->fecha_finalizacion = null;
                    } elseif ($maquina->tipo === 'ensambladora') {
                        $etiqueta->estado = 'soldando';
                        $etiqueta->fecha_finalizacion = null;
                    } else {
                        $etiqueta->estado = 'completada';
                        $etiqueta->fecha_finalizacion = now();
                    }
                }

                $etiqueta->ensamblador1 = Auth::id();
                $etiqueta->ensamblador2 = session()->get('compañero_id', null);
                // Guardar los cambios en la etiqueta
                $etiqueta->save();

                // Verificar si todos los elementos de la etiqueta están en estado "completado"
                $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();

                if ($elementosEtiquetaCompletos) {
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                }

                // // Si todos los elementos de la planilla están completados, actualizar la planilla
                // $elementosPlanillaCompletos = $etiqueta->planilla->elementos()
                //     ->whereNull('fecha_finalizacion')
                //     ->doesntExist();
                // if ($elementosPlanillaCompletos) {
                //     $planilla->fecha_finalizacion = now();
                //     $planilla->estado = "completada";
                //     $planilla->save();
                // }
            } elseif ($etiqueta->estado == "soldando") {     // ---------------------------------- S O L D A N D O
                if ($maquina->tipo !== 'soldadora') {
                    return response()->json([
                        'success' => false,
                        'error' => "La etiqueta esta en otra máquina",
                    ], 400);
                }

                $ensamblado = strtoupper($etiqueta->planilla->ensamblado);

                if (
                    (strpos($ensamblado, 'TALLER') !== false || strpos($ensamblado, 'CARCASAS') !== false)
                    && $elementosEnMaquina->count() == $etiqueta->elementos->count()
                ) {

                    // Código a ejecutar si "taller" o "carcasas" están en la variable ensamblado
                    if ($maquina->tipo === 'cortadora_dobladora') {
                        // Asignar "IDEA 5"
                        $elemento->maquina_id_2 = 7;
                    } elseif ($maquina->tipo === 'ensambladora') {

                        // Buscar una máquina de soldar disponible
                        $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                            ->whereDoesntHave('elementos') // Verifica si tiene elementos en lugar de etiquetas
                            ->first();

                        // Si no hay máquinas de soldar libres, seleccionar la que recibió un elemento primero
                        if (!$maquinaSoldarDisponible) {
                            $maquinaSoldarDisponible = Maquina::whereRaw('LOWER(nombre) LIKE LOWER(?)', ['%soldadora%'])
                                ->whereHas('elementos', function ($query) {
                                    $query->orderBy('created_at'); // Seleccionar la que lleva más tiempo trabajando
                                })
                                ->first();
                        }

                        if ($maquinaSoldarDisponible) {
                            $elemento->maquina_id_3 = $maquinaSoldarDisponible->id;
                        } else {
                            return response()->json([
                                'success' => false,
                                'error' => 'No se encontró ninguna máquina de soldar disponible',
                            ], 500);
                        }
                    }

                    $elemento->save();
                    $etiqueta->save();
                }

                $etiqueta->estado = 'completada';
                $etiqueta->soldador1 = Auth::id();
                $etiqueta->soldador2 = session()->get('compañero_id', null);
                $etiqueta->fecha_finalizacion = now();
                $etiqueta->save();

                // Verificar si todos los elementos de la etiqueta están en estado "completado"
                $elementosEtiquetaCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();

                if ($elementosEtiquetaCompletos) {
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                    $etiqueta->save();
                }

                // Si todos los elementos de la planilla están completados, actualizar la planilla
                $elementosPlanillaCompletos = $etiqueta->planilla->elementos()
                    ->whereNull('fecha_finalizacion')
                    ->doesntExist();
                if ($elementosPlanillaCompletos) {
                    $planilla->fecha_finalizacion = now();
                    $planilla->estado = "completada";
                    $planilla->save();
                }
            } elseif ($etiqueta->estado == "completada") { // ---------------------------------- C O M P L E T A D O
                return response()->json([
                    'success' => false,
                    'error' => "Ya la has completado.",
                ], 400);
            }


            DB::commit();

            return response()->json([
                'success' => true,
                'estado' => $etiqueta->estado,
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
