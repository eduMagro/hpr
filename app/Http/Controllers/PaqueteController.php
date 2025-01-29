<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Ubicacion;
use App\Models\Elemento;
use Illuminate\Support\Facades\DB;

class PaqueteController extends Controller
{
    public function index(Request $request)
    {
        $query = Paquete::with([
            'planilla',
            'etiquetas.elementos',
            'ubicacion'
        ])
            ->orderBy('created_at', 'desc'); // Ordenar por fecha de creación descendente

        // Aplicar filtro por ID si se proporciona
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }

        $paquetes = $query->paginate(10);

        return view('paquetes.index', compact('paquetes'));
    }
    /**
     * Crear un nuevo paquete y asociar etiquetas existentes.
     */
    public function store(Request $request)
    {
        $request->validate([
            'etiquetas' => 'required|array|min:1', // Debe recibir un array con al menos una etiqueta
            'etiquetas.*' => 'integer|exists:etiquetas,id|distinct' // Asegurar que los IDs existen y son únicos
        ]);

        try {
            DB::beginTransaction();

            // Verificar si las etiquetas están disponibles
            if ($mensajeError = $this->verificarEtiquetasDisponibles($request->etiquetas)) {
                DB::rollBack();
                return response()->json(['success' => false] + $mensajeError, 400);
            }

            // Obtener etiquetas completadas
            $etiquetas = Etiqueta::whereIn('id', $request->etiquetas)
                ->where('estado', 'completado')
                ->with('elementos')
                ->get();

            if ($etiquetas->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron elementos completados para estas etiquetas.'
                ], 400);
            }

            // Obtener elementos y validar código de máquina
            $elementos = $etiquetas->flatMap->elementos;
            if ($mensajeError = $this->verificarElementos($elementos)) {
                DB::rollBack();
                return response()->json(['success' => false] + $mensajeError, 400);
            }

            // Obtener ubicación basada en el código de máquina
            $codigoMaquina = $elementos->pluck('codigo_maquina')->unique()->first();
            $ubicacion = $this->obtenerUbicacionPorCodigoMaquina($codigoMaquina);

            if (!$ubicacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró una ubicación para la máquina con código $codigoMaquina"
                ], 400);
            }

            // Crear el paquete y asignar etiquetas
            $paquete = $this->crearPaquete($etiquetas->first()->planilla_id, $ubicacion->id);
            $this->asignarEtiquetasAPaquete($request->etiquetas, $paquete->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete creado y etiquetas asociadas correctamente.',
                'paquete_id' => $paquete->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica si alguna etiqueta ya tiene un paquete asignado.
     */
    private function verificarEtiquetasDisponibles(array $etiquetas)
    {
        $etiquetasOcupadas = Etiqueta::whereIn('id', $etiquetas)
            ->whereNotNull('paquete_id')
            ->pluck('id');

        if ($etiquetasOcupadas->isNotEmpty()) {
            return [
                'message' => 'Al menos una de las etiquetas ya está asignada a un paquete.',
                'etiquetas_ocupadas' => $etiquetasOcupadas->toArray()
            ];
        }

        return null;
    }

    /**
     * Verifica si los elementos de las etiquetas son válidos y pertenecen a la misma máquina.
     */
    private function verificarElementos($elementos)
    {
        if ($elementos->isEmpty()) {
            return ['message' => 'No se encontraron elementos asociados con código de máquina.'];
        }

        $codigosMaquina = $elementos->pluck('codigo_maquina')->unique();
        if ($codigosMaquina->count() > 1) {
            return [
                'message' => 'Los elementos pertenecen a diferentes máquinas. No pueden asignarse al mismo paquete.',
                'codigos_maquina_detectados' => $codigosMaquina
            ];
        }

        return null;
    }

    /**
     * Obtiene la ubicación a partir del código de máquina.
     */
    private function obtenerUbicacionPorCodigoMaquina($codigoMaquina)
    {
        return Ubicacion::where('descripcion', 'LIKE', "%$codigoMaquina%")->first();
    }

    /**
     * Crea un nuevo paquete con la planilla y la ubicación.
     */
    private function crearPaquete($planillaId, $ubicacionId)
    {
        return Paquete::create([
            'planilla_id' => $planillaId,
            'ubicacion_id' => $ubicacionId,
        ]);
    }

    /**
     * Asigna las etiquetas al paquete creado.
     */
    private function asignarEtiquetasAPaquete(array $etiquetas, $paqueteId)
    {
        Etiqueta::whereIn('id', $etiquetas)->update(['paquete_id' => $paqueteId]);
    }


    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Buscar el paquete
            $paquete = Paquete::findOrFail($id);

            // Desasociar las etiquetas (poner `paquete_id` en NULL en lugar de eliminarlas)
            Etiqueta::where('paquete_id', $paquete->id)->update(['paquete_id' => null]);

            // Eliminar el paquete
            $paquete->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paquete eliminado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el paquete: ' . $e->getMessage()
            ], 500);
        }
    }
}
