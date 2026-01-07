<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use App\Models\PlanillaEntidad;
use App\Models\OrdenPlanillaEnsamblaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanificacionEnsamblajeController extends Controller
{
    /**
     * Vista principal de planificación de ensamblaje.
     * Muestra:
     * - Entidades listas para ensamblar (todos elementos fabricados)
     * - Cola de trabajo actual de la máquina
     */
    public function index(Request $request)
    {
        // Obtener máquinas ensambladoras
        $maquinasEnsambladoras = Maquina::where('tipo', 'ensambladora')
            ->orderBy('nombre')
            ->get();

        if ($maquinasEnsambladoras->isEmpty()) {
            return view('ensamblaje.planificacion', [
                'maquinas' => collect(),
                'maquinaSeleccionada' => null,
                'entidadesListas' => collect(),
                'colaEnsamblaje' => collect(),
                'entidadesEnProgreso' => collect(),
            ]);
        }

        // Máquina seleccionada (por defecto la primera)
        $maquinaId = $request->get('maquina_id', $maquinasEnsambladoras->first()->id);
        $maquina = Maquina::find($maquinaId) ?? $maquinasEnsambladoras->first();

        // Entidades ya en la cola de esta máquina
        $idsEnCola = OrdenPlanillaEnsamblaje::where('maquina_id', $maquina->id)
            ->pluck('planilla_entidad_id')
            ->toArray();

        // Entidades listas para ensamblar (todos elementos fabricados) y NO en cola
        $entidadesListas = PlanillaEntidad::with(['planilla.obra', 'planilla.cliente', 'elementos'])
            ->listasParaEnsamblaje()
            ->whereNotIn('id', $idsEnCola)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($entidad) {
                $entidad->estado_elementos = $entidad->elementos_fabricados;
                return $entidad;
            });

        // Cola de ensamblaje actual
        $colaEnsamblaje = OrdenPlanillaEnsamblaje::with([
                'entidad.planilla.obra',
                'entidad.planilla.cliente',
                'entidad.elementos',
                'asignadoPor'
            ])
            ->where('maquina_id', $maquina->id)
            ->whereIn('estado', ['pendiente', 'pausada'])
            ->orderBy('posicion')
            ->get()
            ->map(function ($orden) {
                $orden->estado_elementos = $orden->entidad->elementos_fabricados ?? [];
                return $orden;
            });

        // Entidades en proceso
        $entidadesEnProgreso = OrdenPlanillaEnsamblaje::with([
                'entidad.planilla.obra',
                'entidad.elementos',
            ])
            ->where('maquina_id', $maquina->id)
            ->where('estado', 'en_proceso')
            ->get();

        return view('ensamblaje.planificacion', [
            'maquinas' => $maquinasEnsambladoras,
            'maquinaSeleccionada' => $maquina,
            'entidadesListas' => $entidadesListas,
            'colaEnsamblaje' => $colaEnsamblaje,
            'entidadesEnProgreso' => $entidadesEnProgreso,
        ]);
    }

    /**
     * Añadir entidad a la cola de ensamblaje.
     */
    public function asignar(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'planilla_entidad_id' => 'required|exists:planilla_entidades,id',
            'prioridad' => 'nullable|integer|min:1|max:5',
        ]);

        $maquinaId = $request->maquina_id;
        $entidadId = $request->planilla_entidad_id;

        // Verificar que no esté ya en la cola
        $existe = OrdenPlanillaEnsamblaje::where('maquina_id', $maquinaId)
            ->where('planilla_entidad_id', $entidadId)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Esta entidad ya está en la cola de ensamblaje'
            ], 400);
        }

        // Verificar que la entidad esté lista
        $entidad = PlanillaEntidad::find($entidadId);
        if (!$entidad->listaParaEnsamblaje()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta entidad aún no tiene todos sus elementos fabricados'
            ], 400);
        }

        // Crear la orden
        $orden = OrdenPlanillaEnsamblaje::create([
            'maquina_id' => $maquinaId,
            'planilla_entidad_id' => $entidadId,
            'posicion' => OrdenPlanillaEnsamblaje::siguientePosicion($maquinaId),
            'prioridad' => $request->prioridad ?? 3,
            'asignado_por' => auth()->id(),
            'fecha_asignacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Entidad añadida a la cola de ensamblaje',
            'orden' => $orden->load('entidad')
        ]);
    }

    /**
     * Quitar entidad de la cola.
     */
    public function quitar(Request $request)
    {
        $request->validate([
            'orden_id' => 'required|exists:orden_planillas_ensamblaje,id',
        ]);

        $orden = OrdenPlanillaEnsamblaje::find($request->orden_id);

        if ($orden->estado === 'en_proceso') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede quitar una entidad que está en proceso'
            ], 400);
        }

        $maquinaId = $orden->maquina_id;
        $posicionEliminada = $orden->posicion;

        $orden->delete();

        // Reordenar posiciones posteriores
        OrdenPlanillaEnsamblaje::where('maquina_id', $maquinaId)
            ->where('posicion', '>', $posicionEliminada)
            ->decrement('posicion');

        return response()->json([
            'success' => true,
            'message' => 'Entidad eliminada de la cola'
        ]);
    }

    /**
     * Reordenar la cola (drag & drop).
     */
    public function reordenar(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'orden' => 'required|array',
            'orden.*' => 'exists:orden_planillas_ensamblaje,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->orden as $posicion => $ordenId) {
                OrdenPlanillaEnsamblaje::where('id', $ordenId)
                    ->where('maquina_id', $request->maquina_id)
                    ->update(['posicion' => $posicion + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Cola reordenada correctamente'
        ]);
    }

    /**
     * Asignar múltiples entidades a la cola.
     */
    public function asignarMultiple(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'entidades' => 'required|array',
            'entidades.*' => 'exists:planilla_entidades,id',
        ]);

        $maquinaId = $request->maquina_id;
        $añadidas = 0;
        $errores = [];

        foreach ($request->entidades as $entidadId) {
            // Verificar que no esté ya en la cola
            $existe = OrdenPlanillaEnsamblaje::where('maquina_id', $maquinaId)
                ->where('planilla_entidad_id', $entidadId)
                ->exists();

            if ($existe) {
                continue;
            }

            // Verificar que esté lista
            $entidad = PlanillaEntidad::find($entidadId);
            if (!$entidad->listaParaEnsamblaje()) {
                $errores[] = $entidad->marca;
                continue;
            }

            OrdenPlanillaEnsamblaje::create([
                'maquina_id' => $maquinaId,
                'planilla_entidad_id' => $entidadId,
                'posicion' => OrdenPlanillaEnsamblaje::siguientePosicion($maquinaId),
                'asignado_por' => auth()->id(),
                'fecha_asignacion' => now(),
            ]);

            $añadidas++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se añadieron {$añadidas} entidades a la cola",
            'errores' => $errores,
        ]);
    }
}
