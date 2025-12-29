<?php

namespace App\Http\Controllers;

use App\Models\EtiquetaEnsamblaje;
use App\Models\Planilla;
use App\Services\EtiquetaEnsamblajeService;
use Illuminate\Http\Request;

class EtiquetaEnsamblajeController extends Controller
{
    protected EtiquetaEnsamblajeService $service;

    public function __construct(EtiquetaEnsamblajeService $service)
    {
        $this->service = $service;
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
