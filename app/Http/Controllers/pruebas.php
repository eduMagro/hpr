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

    public function actualizarEtiqueta(Request $request, $id, $maquina_id)
    {
        DB::beginTransaction();
        try {
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
            $elementosCompletados = $elementosEnMaquina->where('estado', 'completado')->count();
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
                    break;
                // -------------------------------------------- ESTADO FABRICANDO --------------------------------------------
                case 'fabricando':

                    // -------------- ACTUALIZAMOS ELEMENTOS
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id = Auth::id();
                        $elemento->users_id_2 = session()->get('compañero_id', null);
                        $elemento->save();
                    }

                    //Comprobamos si ya estan todos los elementos en la maquina completados.
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
                    // -------------- ASIGNAMOS LOS PRODUCTOS A SUS ELEMENTOS

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
                    }

                    // -------------- ¿TALLER O CARCASAS?
                    if (str_contains($ensambladoText, 'taller')) {
                        // Lógica para "taller"
                        // Por ejemplo, se puede asignar una máquina de soldar a cada elemento.
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
                        // Verificar si TODOS los elementos de la máquina actual están completados
                        if ($elementosEnMaquina->count() > 0 && $elementosCompletados >= $elementosEnMaquina->count()) {
                            // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                            if ($enOtrasMaquinas) {
                                $etiqueta->estado = 'parcialmente_completada';
                                $message = "Etiqueta parcialmente completada en esta máquina, pero existen elementos en otras máquinas.";
                            } else {
                                // Si no hay elementos en otras máquinas, se marca como fabricada/completada
                                $etiqueta->estado = 'fabricada';
                                $etiqueta->fecha_finalizacion = now();
                                $message = "Etiqueta fabricada completamente en esta máquina.";
                            }
                            $warnings[] = $message;
                            $etiqueta->save();
                        }
                    } elseif (str_contains($ensambladoText, 'carcasas')) {
                        // Lógica para "carcasas"
                        // Si la máquina actual es de tipo "estribadora", asignamos a cada elemento una máquina de tipo ensambladora en maquina_id_2
                        if ($maquina->tipo === 'estribadora') {
                            $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();
                            if (!$maquinaEnsambladora) {
                                throw new \Exception("No se encontró una máquina ensambladora disponible.");
                            }
                            foreach ($elementosEnMaquina as $elemento) {
                                $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                                $elemento->save();
                            }
                        } else {
                            // Si la máquina no es estribadora, puedes incluir otra lógica o dejarlo sin cambios.
                        }
                        // Verificar si TODOS los elementos de la máquina actual están completados
                        if ($elementosEnMaquina->count() > 0 && $elementosCompletados >= $elementosEnMaquina->count()) {
                            // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                            if ($enOtrasMaquinas) {
                                $etiqueta->estado = 'parcialmente_completada';
                                $message = "Etiqueta parcialmente completada en esta máquina, pero existen elementos en otras máquinas.";
                            } else {
                                // Si no hay elementos en otras máquinas, se marca como fabricada/completada
                                $etiqueta->estado = 'fabricada';
                                $etiqueta->fecha_finalizacion = now();
                                $message = "Etiqueta fabricada completamente en esta máquina.";
                            }
                            $warnings[] = $message;
                            $etiqueta->save();
                        }
                    } else {
                        // Verificar si TODOS los elementos de la etiqueta están en estado "completado"
                        $todosElementosCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();
                        if ($todosElementosCompletos) {
                            $etiqueta->estado = 'completada';
                            $etiqueta->fecha_finalizacion = now();
                            $etiqueta->save();
                        }
                    }

                    // Si todos los elementos de la planilla están completados, actualizar la planilla
                    $todosElementosPlanillaCompletos = $planilla->elementos()->where('estado', '!=', 'completado')->doesntExist();
                    if ($todosElementosPlanillaCompletos) {
                        $planilla->fecha_finalizacion = now();
                        $planilla->estado = 'completada';
                        $planilla->save();
                    }
                    break;
                // -------------------------------------------- ESTADO PARCIALMENTE COMPLETADA --------------------------------------------
                case 'parcialmente_completada':
                    // -------------- ACTUALIZAMOS ELEMENTOS
                    foreach ($elementosEnMaquina as $elemento) {
                        $elemento->users_id = Auth::id();
                        $elemento->users_id_2 = session()->get('compañero_id', null);
                        $elemento->save();
                    }

                    //Comprobamos si ya estan todos los elementos en la maquina completados.
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
                    // -------------- ASIGNAMOS LOS PRODUCTOS A SUS ELEMENTOS

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
                    }


                    // -------------- ¿TALLER O CARCASAS?
                    if (str_contains($ensambladoText, 'taller')) {
                        // Lógica para "taller"
                        // Por ejemplo, se puede asignar una máquina de soldar a cada elemento.
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
                        // Verificar si TODOS los elementos de la máquina actual están completados
                        if ($elementosEnMaquina->count() > 0 && $elementosCompletados >= $elementosEnMaquina->count()) {
                            // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                            if ($enOtrasMaquinas) {
                                $etiqueta->estado = 'parcialmente_completada';
                                $message = "Etiqueta parcialmente completada en esta máquina, pero existen elementos en otras máquinas.";
                            } else {
                                // Si no hay elementos en otras máquinas, se marca como fabricada/completada
                                $etiqueta->estado = 'fabricada';
                                $etiqueta->fecha_finalizacion = now();
                                $message = "Etiqueta fabricada completamente en esta máquina.";
                            }
                            $warnings[] = $message;
                            $etiqueta->save();
                        }
                    } elseif (str_contains($ensambladoText, 'carcasas')) {
                        // Lógica para "carcasas"
                        // Si la máquina actual es de tipo "estribadora", asignamos a cada elemento una máquina de tipo ensambladora en maquina_id_2
                        if ($maquina->tipo === 'estribadora') {
                            $maquinaEnsambladora = Maquina::where('tipo', 'ensambladora')->first();
                            if (!$maquinaEnsambladora) {
                                throw new \Exception("No se encontró una máquina ensambladora disponible.");
                            }
                            foreach ($elementosEnMaquina as $elemento) {
                                $elemento->maquina_id_2 = $maquinaEnsambladora->id;
                                $elemento->save();
                            }
                        } else {
                            // Si la máquina no es estribadora, puedes incluir otra lógica o dejarlo sin cambios.
                        }
                        // Verificar si TODOS los elementos de la máquina actual están completados
                        if ($elementosEnMaquina->count() > 0 && $elementosCompletados >= $elementosEnMaquina->count()) {
                            // Si la etiqueta tiene elementos en otras máquinas, marcamos como parcialmente completada
                            if ($enOtrasMaquinas) {
                                $etiqueta->estado = 'parcialmente_completada';
                                $message = "Etiqueta parcialmente completada en esta máquina, pero existen elementos en otras máquinas.";
                            } else {
                                // Si no hay elementos en otras máquinas, se marca como fabricada/completada
                                $etiqueta->estado = 'fabricada';
                                $etiqueta->fecha_finalizacion = now();
                                $message = "Etiqueta fabricada completamente en esta máquina.";
                            }
                            $warnings[] = $message;
                            $etiqueta->save();
                        }
                    } else {
                        // Verificar si TODOS los elementos de la etiqueta están en estado "completado"
                        $todosElementosCompletos = $etiqueta->elementos()->where('estado', '!=', 'completado')->doesntExist();
                        if ($todosElementosCompletos) {
                            $etiqueta->estado = 'completada';
                            $etiqueta->fecha_finalizacion = now();
                            $etiqueta->save();
                        }
                    }
                    // Si todos los elementos de la planilla están completados, actualizar la planilla
                    $todosElementosPlanillaCompletos = $planilla->elementos()->where('estado', '!=', 'completado')->doesntExist();
                    if ($todosElementosPlanillaCompletos) {
                        $planilla->fecha_finalizacion = now();
                        $planilla->estado = 'completada';
                        $planilla->save();
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

                // -------------------------------------------- ESTADO ENSAMBLANDO --------------------------------------------
                case 'ensamblando':
                    // Finalizar la fase de ensamblado
                    $etiqueta->fecha_finalizacion_ensamblado = now();
                    $etiqueta->estado = 'ensamblado';
                    $etiqueta->save();
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
                    // Diferenciar según el tipo de planilla:
                    // Si es "carcasas", se finaliza el proceso en ensamblado
                    if (str_contains($ensambladoText, 'carcasas')) {
                        $etiqueta->estado = 'completada';
                        $etiqueta->save();
                        DB::commit();
                        return response()->json([
                            'success' => true,
                            'message' => 'Proceso completado en la etapa de ensamblado para carcasas.',
                            'etiqueta' => $etiqueta,
                        ]);
                    }

                    // Si es "taller", se continúa con la soldadura
                    if (str_contains($ensambladoText, 'taller')) {
                        // Si la máquina es ensambladora, se busca una máquina de soldar disponible
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
                                // Se asume que, en este flujo, se quiere marcar el elemento como "completado"
                                $elemento->estado = 'completado';
                                $elemento->save();
                            }
                        } else {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'error'   => 'No se encontró ninguna máquina de soldar disponible',
                            ], 500);
                        }

                        $etiqueta->fecha_inicio_soldadura = now();
                        $etiqueta->estado = 'soldando';
                        $etiqueta->save();
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
                    // Si la etiqueta ya está completada, se retorna un error
                    DB::commit();
                    return response()->json([
                        'success' => false,
                        'message' => 'La etiqueta ya se encuentra completada.',
                        'etiqueta' => $etiqueta,
                    ], 400);
                    break;

                default:
                    throw new \Exception("Estado desconocido de la etiqueta.");
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Proceso completo de la etiqueta ejecutado correctamente.',
                'etiqueta' => $etiqueta,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
