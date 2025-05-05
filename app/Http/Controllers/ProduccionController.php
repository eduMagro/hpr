<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use App\Models\User;
use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProduccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Cargar máquinas ordenadas y darles formato
        $maquinas = Maquina::orderBy('id')->get(['id', 'nombre', 'codigo'])->map(function ($maquina) {
            return [
                'id' => str_pad($maquina->id, 3, '0', STR_PAD_LEFT),
                'title' => $maquina->nombre,
                'codigo' => strtolower($maquina->codigo) // ya en minúscula
            ];
        });

        // Agregar grúas manualmente
        $gruas = collect([
            ['id' => '1000', 'title' => 'grua 1', 'codigo' => 'grua 1'],
            ['id' => '1001', 'title' => 'grua 2', 'codigo' => 'grua 2'],
            ['id' => '1002', 'title' => 'grua 3', 'codigo' => 'grua 3'],
        ])->map(function ($grua) {
            $grua['codigo'] = strtolower($grua['codigo']); // asegurar minúsculas
            return $grua;
        });

        // Unir máquinas con grúas
        $maquinas = $maquinas->merge($gruas);

        // Indexar por código en minúsculas
        $maquinasIndexadas = $maquinas->keyBy('codigo');

        // Cargar trabajadores y sus asignaciones
        $trabajadores = User::with(['asignacionesTurnos.turno:id,hora_entrada,hora_salida'])
            ->where('rol', 'operario')
            ->whereNotNull('especialidad')
            ->get();

        $fechaHoy = Carbon::today();
        $fechaLimite = $fechaHoy->copy()->addDays(14);

        // Crear eventos sin usar cache
        Log::info('Generando eventos para trabajadores');
        $eventos = [];

        foreach ($trabajadores as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacionTurno) {
                $fechaTurno = Carbon::parse($asignacionTurno->fecha);

                if ($fechaTurno->between($fechaHoy, $fechaLimite)) {
                    $turno = $asignacionTurno->turno;

                    $start = $asignacionTurno->fecha . 'T' . ($turno ? $turno->hora_entrada : '08:00:00');
                    $end = $asignacionTurno->fecha . 'T' . ($turno ? $turno->hora_salida : '16:00:00');

                    // Comparar puesto vs código en minúsculas
                    $puesto = strtolower($asignacionTurno->puesto ?: $trabajador->especialidad);
                    $maquina = $maquinasIndexadas->get($puesto);
                    $resourceId = $maquina ? $maquina['id'] : null;

                    Log::info("Puesto: $puesto | Resource ID: $resourceId");

                    $eventos[] = [
                        'id' => $asignacionTurno->id,
                        'title' => $trabajador->name,
                        'start' => $start,
                        'end' => $end,
                        'resourceId' => $resourceId,
                        'trabajador' => $trabajador
                    ];
                }
            }
        }

        Log::info('Eventos generados: ', ['count' => count($eventos)]);
        $trabajadoresEventos = $eventos;

        // Mostrar operarios que trabajan mañana (excepto vacaciones)
        $fechaActual = Carbon::today()->addDays(1);
        $operariosTrabajando = User::where('rol', 'operario')
            ->whereHas('asignacionesTurnos', function ($query) use ($fechaActual) {
                $query->whereDate('fecha', $fechaActual)
                    ->where('turno_id', '<>', 10); // Excluir vacaciones
            })
            ->get();

        return view('produccion.index', compact('maquinas', 'trabajadoresEventos', 'operariosTrabajando'));
    }


    public function actualizarPuesto(Request $request, $id)
    {
        $asignacion = AsignacionTurno::findOrFail($id);
        $asignacion->puesto = $request->input('puesto');
        $asignacion->save();

        return response()->json(['success' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
