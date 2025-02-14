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
            'user',
            'user2'
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

        // Obtener los resultados con paginación
        $etiquetas = $query->paginate(10);

        return view('etiquetas.index', compact('etiquetas'));
    }

    public function actualizarEtiqueta(Request $request, $id, $maquina_id)
    {
        DB::beginTransaction();

        try {
            $etiqueta = Etiqueta::with('elementos.planilla')->findOrFail($id);

            $planilla_id = $etiqueta->planilla_id;
            $planilla = Planilla::find($planilla_id);

            // Capturamos el primero objeto en la maquina de la misma etiqueta
            $primerElemento = $etiqueta->elementos()
                ->where('maquina_id', $maquina_id)
                ->first();

            if (!$primerElemento) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontraron elementos asociados a esta etiqueta en esta máquina.',
                ], 400);
            }
            $elementosEnMaquina = $etiqueta->elementos()
                ->where(function ($query) use ($maquina_id) {
                    $query->where('maquina_id', $maquina_id)
                        ->orWhere('maquina_id_2', $maquina_id);
                })
                ->get();

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
            if ($maquina->id == 7) {
                $elementosExtra = Elemento::with('etiquetaRelacion', 'planilla')
                    ->where('maquina_id_2', $maquina->id)
                    ->where('maquina_id', '!=', $maquina->id)
                    ->get();
                $elementosEnMaquina = $elementosEnMaquina->merge($elementosExtra);
            }
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
                    $elemento->fecha_inicio = now();
                    $elemento->save();
                }

                // 2. Obtener los productos disponibles en la máquina que tengan los diámetros requeridos
                $productos = $maquina->productos()
                    ->whereIn('diametro', array_keys($diametrosConPesos))
                    ->orderBy('peso_stock')
                    ->get();

                if ($productos->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'En esta máquina no hay materias primas con los diámetros requeridos.',
                    ], 400);
                }

                // Arreglo donde se guardarán los detalles del consumo por diámetro
                $consumos = []; // Estructura: [ <diametro> => [ ['producto_id' => X, 'consumido' => Y], ... ], ... ]

                // Para cada diámetro, comprobar que el stock total es suficiente
                foreach ($diametrosConPesos as $diametro => $pesoNecesario) {
                    $productosPorDiametro = $productos->where('diametro', $diametro);
                    $stockTotal = $productosPorDiametro->sum('peso_stock');

                    if ($stockTotal < $pesoNecesario) {
                        // Obtener el usuario gruista (ajusta según tu lógica de negocio)

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
                if (!$enOtrasMaquinas) {
                    $etiqueta->users_id_1 = Auth::id();
                    $etiqueta->users_id_2 = session()->get('compañero_id', null);
                }
                $etiqueta->save();
            } elseif ($etiqueta->estado == "fabricando" || $etiqueta->estado == "parcial completada") {  // ---------------------------------- F A B R I C A N D O

                // ELEMENTOS COMPLETADOS?? SALIMOS DEL CONDICIONAL
                $elementosCompletados = $elementosEnMaquina->where('estado', 'completado')->count();

                if ($elementosCompletados == $elementosEnMaquina->count()) {
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
                    if ($maquina_id == 7 && $diametro != 5) {
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
                                'consumido'   => $restar,
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
                if ($maquina_id == 7) {
                    $elementosEnMaquina = $elementosEnMaquina->where('diametro', 5);
                }

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
                    $elemento->estado = "completado";
                    $elemento->fecha_finalizacion = now();
                    $elemento->ubicacion_id = $ubicacion->id;
                    $ensamblado = strtoupper($etiqueta->planilla->ensamblado);

                    if (strpos($ensamblado, 'CARCASAS') !== false) {
                        $this->asignarMaquinas($elemento, $etiqueta, 'CARCASAS');
                    } elseif (strpos($ensamblado, 'TALLER') !== false) {
                        $this->asignarMaquinas($elemento, $etiqueta, 'TALLER');
                    }

                    $elemento->save();
                    $etiqueta->save();
                    // Actualizar el registro global de consumos para este diámetro
                    $consumos[$elemento->diametro] = $consumosDisponibles;
                }

                // Verificar cuántos elementos de la etiqueta están completados
                $totalElementos = $etiqueta->elementos()->count();
                $elementosCompletados = $etiqueta->elementos()->where('estado', 'completado')->count();

                if ($elementosCompletados == $totalElementos) {
                    // Si todos los elementos están completados, la etiqueta pasa a "completada"
                    $etiqueta->estado = 'completada';
                    $etiqueta->fecha_finalizacion = now();
                } else {
                    // Si alguno no está completado, mantener el estado actual
                    $etiqueta->estado = 'parcial completada';
                    $etiqueta->fecha_finalizacion = null;
                }

                // Guardar los cambios en la etiqueta
                $etiqueta->save();

                // Si todos los elementos de la planilla están completados, actualizar la planilla
                $todasFinalizadas = $etiqueta->planilla->elementos()
                    ->whereNull('fecha_finalizacion')
                    ->doesntExist();
                if ($todasFinalizadas) {
                    $planilla->fecha_finalizacion = now();
                    $planilla->estado = "completada";
                    $planilla->save();
                }
            } elseif ($etiqueta->estado == "completada") { // ---------------------------------- C O M P L E T A D O
                return response()->json([
                    'success' => false,
                    'error' => "Ya la has completado.",
                ], 400);
                // ---------------------------------- REVERTIR PESOS --  C O M P L E T A D O
                // $producto1 = $etiqueta->producto_id ? $maquina->productos()->find($etiqueta->producto_id) : null;
                // $producto2 = $etiqueta->producto_id_2 ? $maquina->productos()->find($etiqueta->producto_id_2) : null;


                // Se toma como referencia el peso total original de la etiqueta
                // $pesoRestaurar = $etiqueta->peso;

                // Restaurar en producto1 (sin sobrepasar el peso_inicial)
                // if ($producto1) {
                //     $pesoIncremento = min($pesoRestaurar, $producto1->peso_inicial - $producto1->peso_stock);
                //     $producto1->peso_stock += $pesoIncremento;
                //     $pesoRestaurar -= $pesoIncremento;
                // Se restaura el estado al original (o se deja "fabricando" según la lógica de negocio)
                //     $producto1->estado = "fabricando";
                //     $producto1->save();
                // }

                // Restaurar en producto2, si aún queda peso pendiente por restaurar
                // if ($pesoRestaurar > 0 && $producto2) {
                //     $pesoIncremento = min($pesoRestaurar, $producto2->peso_inicial - $producto2->peso_stock);
                //     $producto2->peso_stock += $pesoIncremento;
                //     $pesoRestaurar -= $pesoIncremento;
                //     $producto2->estado = "fabricando";
                //     $producto2->save();
                // }

                // ---------------------------------- REVERTIR ELEMENTOS -- C O M P L E T A D O

                // 2. (Opcional) Revertir el estado de los elementos asociados a la etiqueta,
                // en caso de que hayan sido modificados (por ejemplo, pasar de "fabricando" a "pendiente")
                // foreach ($elementosEnMaquina as $elemento) {
                //     $elemento->estado = "pendiente";
                //     $elemento->fecha_inicio = null;
                //     $elemento->fecha_finalizacion = null;
                //     $elemento->users_id = null;
                //     $elemento->users_id_2 = null;
                //     $elemento->save();
                // }

                // // ---------------------------------- REVERTIR ETIQUETAS -- C O M P L E T A D O
                // $etiqueta->fecha_inicio = null;
                // $etiqueta->fecha_finalizacion = null;
                // $etiqueta->estado = "pendiente";
                // $etiqueta->users_id_1 = null;
                // $etiqueta->users_id_2 = null;
                // $etiqueta->producto_id = null;
                // $etiqueta->producto_id_2 = null;
                // $etiqueta->save();
                // // ---------------------------------- REVERTIR ETIQUETAS -- C O M P L E T A D O
                // $planilla->fecha_inicio = null;
                // $planilla->fecha_finalizacion = null;
                // $planilla->estado = 'pendiente';
                // $planilla->save();
            }


            DB::commit();

            // Preparar una lista de productos afectados (únicos) para incluir en la respuesta
            $productosAfectados = [];
            foreach ($elementosEnMaquina as $elemento) {
                if ($elemento->producto_id) {
                    $producto = $maquina->productos()->find($elemento->producto_id);
                    if ($producto && !collect($productosAfectados)->pluck('id')->contains($producto->id)) {
                        $productosAfectados[] = [
                            'id' => $producto->id,
                            'peso_stock' => $producto->peso_stock,
                            'peso_inicial' => $producto->peso_inicial,
                        ];
                    }
                }
                if ($elemento->producto_id_2) {
                    $producto = $maquina->productos()->find($elemento->producto_id_2);
                    if ($producto && !collect($productosAfectados)->pluck('id')->contains($producto->id)) {
                        $productosAfectados[] = [
                            'id' => $producto->id,
                            'peso_stock' => $producto->peso_stock,
                            'peso_inicial' => $producto->peso_inicial,
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'estado' => $etiqueta->estado,
                'fecha_inicio' => $etiqueta->fecha_inicio ? Carbon::parse($etiqueta->fecha_inicio)->format('d/m/Y H:i:s') : 'No asignada',
                'fecha_finalizacion' => $etiqueta->fecha_finalizacion ? Carbon::parse($etiqueta->fecha_finalizacion)->format('d/m/Y H:i:s') : 'No asignada',
                'productos_afectados' => $productosAfectados
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

    /**
     * Asigna las máquinas a un elemento según el tipo de ensamblado.
     *
     * @param  \App\Models\Elemento $elemento
     * @param  \App\Models\Etiqueta $etiqueta
     * @param  string $tipoEnsamblado ("CARCASAS" o "TALLER")
     * @return bool|string Devuelve true si se asignó correctamente, o un mensaje de error en caso contrario.
     */

    public function asignarMaquinas($elemento, $etiqueta, $tipoEnsamblado)
    {
        // Obtener la máquina "IDEA 5"
        $maquinaIdea5 = Maquina::whereRaw('LOWER(nombre) = LOWER(?)', ['IDEA 5'])->first();

        // Validar que exista la máquina "IDEA 5"
        if (!$maquinaIdea5) {
            return response()->json([
                'success' => false,
                'error' => 'No encontramos la maquina Idea 5',
            ], 500);
        }

        // Asignar "IDEA 5"
        $elemento->maquina_id_2 = $maquinaIdea5->id;
        $elemento->ubicacion_id = null;
        $etiqueta->ubicacion_id = 33;

        // Si el ensamblado es "TALLER", también se asigna una máquina de soldar
        if ($tipoEnsamblado === 'TALLER') {
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

        // Guardar cambios
        $elemento->save();
        $etiqueta->save();
    }
}
