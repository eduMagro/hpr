<?php

namespace App\Http\Controllers;

use App\Models\EtiquetaEnsamblaje;
use App\Models\OrdenPlanillaEnsamblaje;
use App\Models\OrdenPlanilla;
use App\Models\Planilla;
use App\Services\EtiquetaEnsamblajeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EtiquetaEnsamblajeController extends Controller
{
    protected EtiquetaEnsamblajeService $service;

    public function __construct(EtiquetaEnsamblajeService $service)
    {
        $this->service = $service;
    }

    /**
     * Actualiza el estado de una etiqueta de ensamblaje (endpoint principal para frontend).
     * Flujo: pendiente → en_proceso → completada
     */
    public function actualizarEstado(Request $request, $id)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
        ]);

        $etiqueta = EtiquetaEnsamblaje::with(['entidad.planilla', 'planilla'])->findOrFail($id);
        $maquinaId = $request->maquina_id;
        $estadoAnterior = $etiqueta->estado;

        // Verificar que la entidad está asignada a esta máquina
        $ordenExiste = OrdenPlanillaEnsamblaje::where('maquina_id', $maquinaId)
            ->where('planilla_entidad_id', $etiqueta->planilla_entidad_id)
            ->exists();

        if (!$ordenExiste) {
            return response()->json([
                'success' => false,
                'message' => 'Esta etiqueta no está asignada a esta máquina'
            ], 400);
        }

        // Transición de estados usando los métodos del modelo
        $resultado = false;

        if ($estadoAnterior === 'pendiente') {
            $resultado = $etiqueta->iniciar(auth()->id());
        } elseif ($estadoAnterior === 'en_proceso') {
            $resultado = $etiqueta->completar();
        } elseif ($estadoAnterior === 'completada') {
            return response()->json([
                'success' => true,
                'message' => 'La etiqueta ya está completada',
                'etiqueta' => $etiqueta,
                'estado' => $etiqueta->estado,
            ]);
        }

        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el estado de la etiqueta',
            ], 400);
        }

        $etiqueta->refresh();

        // Si se completó la etiqueta, verificar si hay que actualizar la orden y la planilla
        if ($etiqueta->estado === 'completada') {
            $this->verificarCompletadoEntidad($etiqueta, $maquinaId);
        }

        return response()->json([
            'success' => true,
            'message' => $this->getMensajeEstado($etiqueta->estado),
            'etiqueta' => $etiqueta->fresh(['operario']),
            'estado' => $etiqueta->estado,
            'estado_anterior' => $estadoAnterior,
        ]);
    }

    /**
     * Verifica si todas las etiquetas de una entidad están completadas.
     */
    protected function verificarCompletadoEntidad(EtiquetaEnsamblaje $etiqueta, int $maquinaId): void
    {
        $entidadId = $etiqueta->planilla_entidad_id;

        $totalEtiquetas = EtiquetaEnsamblaje::where('planilla_entidad_id', $entidadId)->count();
        $completadas = EtiquetaEnsamblaje::where('planilla_entidad_id', $entidadId)
            ->where('estado', 'completada')
            ->count();

        if ($completadas >= $totalEtiquetas) {
            // Marcar la orden de ensamblaje como completada
            OrdenPlanillaEnsamblaje::where('planilla_entidad_id', $entidadId)
                ->where('maquina_id', $maquinaId)
                ->update([
                    'estado' => 'completada',
                    'fecha_fin' => now(),
                ]);

            $this->verificarCompletadoPlanilla($etiqueta->planilla_id);
        }
    }

    /**
     * Verifica si todas las etiquetas de una planilla están completadas.
     */
    protected function verificarCompletadoPlanilla(int $planillaId): void
    {
        $planilla = Planilla::find($planillaId);

        if (!$planilla || !$planilla->ensamblajeCompletado()) {
            return;
        }

        DB::transaction(function () use ($planilla) {
            $planilla->estado = 'completada';
            $planilla->save();

            OrdenPlanilla::where('planilla_id', $planilla->id)->delete();
        });
    }

    /**
     * Obtiene el mensaje según el estado.
     */
    protected function getMensajeEstado(string $estado): string
    {
        return match ($estado) {
            'en_proceso' => 'Ensamblaje iniciado',
            'completada' => '¡Ensamblaje completado!',
            default => 'Estado actualizado',
        };
    }

    /**
     * Genera etiquetas de ensamblaje para todas las entidades de una planilla.
     */
    public function generar(Planilla $planilla)
    {
        $etiquetas = $this->service->generarParaPlanilla($planilla);

        return redirect()
            ->route('planillas.show', $planilla)
            ->with('success', "Se generaron {$etiquetas->count()} etiquetas de ensamblaje.");
    }

    /**
     * Inicia el ensamblaje de una etiqueta.
     */
    public function iniciar(Request $request, EtiquetaEnsamblaje $etiqueta)
    {
        $operarioId = $request->user()?->id;

        if ($this->service->iniciarEnsamblaje($etiqueta, $operarioId)) {
            return response()->json([
                'success' => true,
                'message' => 'Ensamblaje iniciado',
                'estado' => $etiqueta->fresh()->estado,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se puede iniciar el ensamblaje',
        ], 400);
    }

    /**
     * Completa el ensamblaje de una etiqueta.
     */
    public function completar(EtiquetaEnsamblaje $etiqueta)
    {
        if ($this->service->completarEnsamblaje($etiqueta)) {
            return response()->json([
                'success' => true,
                'message' => 'Ensamblaje completado',
                'estado' => $etiqueta->fresh()->estado,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se puede completar el ensamblaje',
        ], 400);
    }

    /**
     * Marca una etiqueta como impresa.
     */
    public function marcarImpresa(EtiquetaEnsamblaje $etiqueta)
    {
        $etiqueta->marcarImpresa();

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta marcada como impresa',
        ]);
    }
}
