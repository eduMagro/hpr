<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\Etiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Maquina;


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
    ]) ->orderBy('created_at', 'desc'); // Ordenar por fecha de creación descendente

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
        $primerElemento = $etiqueta->elementos()
            ->where('maquina_id', $maquina_id)
            ->first();

        if (!$primerElemento) {
            return response()->json([
                'success' => false,
                'error' => 'No se encontraron elementos asociados a esta etiqueta.',
            ], 400);
        }

        $maquina = Maquina::findOrFail($maquina_id);
        if (!$maquina) {
            return response()->json([
                'success' => false,
                'error' => 'La máquina asociada al elemento no existe.',
            ], 404);
        }

        // ✅ Verificar si la planilla tiene fecha_inicio NULL y actualizarla
        if ($primerElemento->planilla && is_null($primerElemento->planilla->fecha_inicio)) {
            $primerElemento->planilla->fecha_inicio = now();
            $primerElemento->planilla->save();
        }

        $productosConsumidos = [];
        $producto1 = null;
        $producto2 = null;

        if ($etiqueta->estado == "pendiente") {
            $otrasMaquinas = $etiqueta->elementos()
                ->where('maquina_id', '!=', $maquina->id)
                ->exists();

            if ($otrasMaquinas) {
                return response()->json([
                    'success' => false,
                    'error' => 'La etiqueta está repartida en diferentes máquinas. Trabaja con el elemento que sí está en la máquina.',
                ], 400);
            }

            $productos = $maquina->productos()
                ->where('diametro', $primerElemento->diametro)
                ->orderBy('peso_stock')
                ->get();

            if ($productos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'En esta máquina no hay materia prima con ese diámetro.',
                ], 400);
            }
			//Verificar si hay suficiente peso, para ir avisando al gruista
			 $productos = $maquina->productos()
                ->where('diametro', $primerElemento->diametro)
                ->orderBy('peso_stock')
                ->get();

            $pesoRequerido = $etiqueta->peso;
            if ($pesoRequerido <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'El peso de la etiqueta es 0, no es necesario consumir materia prima.',
                ], 400);
            }

            foreach ($productos as $prod) {
                if ($pesoRequerido <= 0) {
                    break;
                }

                $pesoDisponible = $prod->peso_stock;
                if ($pesoDisponible > 0) {
                    $restar = min($pesoDisponible, $pesoRequerido);
                    $prod->peso_stock -= $restar;

                    if ($prod->peso_stock == 0) {
                        $prod->estado = "consumido";
                    }

                    $prod->save();
                    $productosConsumidos[] = $prod;
                    $pesoRequerido -= $restar;
                }
            }

            if ($pesoRequerido > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay suficiente materia prima. Avisa al gruista.',
                ], 400);
            }

 // ✅ Verificar si esta es la primera etiqueta completada de la planilla
            $otrasEtiquetasFinalizadas = $primerElemento->planilla->elementos()
                ->whereNotNull('fecha_finalizacion')
                ->exists();

            if (!$otrasEtiquetasFinalizadas) {
                $primerElemento->planilla->fecha_inicio = now();
                $primerElemento->planilla->save();
            }

            $etiqueta->estado = "fabricando";
            $etiqueta->fecha_inicio = now();
            $etiqueta->users_id_1 = Auth::id();
            $etiqueta->users_id_2 = session()->get('compañero_id', null);
            $etiqueta->save();
        } elseif ($etiqueta->estado == "fabricando") {
           
            $etiqueta->fecha_finalizacion = now();
            $etiqueta->estado = 'completado';
            $etiqueta->save();

           
            // ✅ Verificar si todas las etiquetas están finalizadas para asignar fecha_finalizacion a la planilla
            $todasFinalizadas = $primerElemento->planilla->elementos()->whereNull('fecha_finalizacion')->doesntExist();
            if ($todasFinalizadas) {
                $primerElemento->planilla->fecha_finalizacion = now();
                $primerElemento->planilla->save();
            }

            // ✅ Asignar productos consumidos a la etiqueta
            $producto1 = $productosConsumidos[0] ?? null;
            $producto2 = $productosConsumidos[1] ?? null;

            $etiqueta->producto_id = $producto1?->id;
            $etiqueta->producto_id_2 = $producto2?->id;
            $etiqueta->save();
        } elseif ($etiqueta->estado == "completado") {
            // Código de reversión de consumo
        }

        DB::commit();

        $productosAfectados = [];
        if ($producto1) {
            $productosAfectados[] = [
                'id' => $producto1->id,
                'peso_stock' => $producto1->peso_stock,
                'peso_inicial' => $producto1->peso_inicial
            ];
        }
        if ($producto2) {
            $productosAfectados[] = [
                'id' => $producto2->id,
                'peso_stock' => $producto2->peso_stock,
                'peso_inicial' => $producto2->peso_inicial
            ];
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
}
