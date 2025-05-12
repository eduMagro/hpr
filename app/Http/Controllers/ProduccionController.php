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
        $estadoProduccionMaquinas = Maquina::selectRaw('maquinas.*, (
            SELECT COUNT(*) FROM elementos
            WHERE elementos.maquina_id_2 = maquinas.id
        ) as elementos_ensambladora')
            ->withCount(['elementos as elementos_count' => function ($query) {
                $query->where('estado', '!=', 'fabricado');
            }])
            ->get()
            ->mapWithKeys(function ($maquina) {
                $inProduction = $maquina->tipo === 'ensambladora'
                    ? $maquina->elementos_ensambladora > 0
                    : $maquina->elementos_count > 0;

                return [
                    $maquina->id => [
                        'nombre' => $maquina->nombre,
                        'codigo' => $maquina->codigo,
                        'en_produccion' => $inProduction,
                    ]
                ];
            });
        $maquinas = Maquina::orderBy('id')->get(['id', 'nombre', 'codigo'])->map(function ($maquina) {
            return [
                'id' => str_pad($maquina->id, 3, '0', STR_PAD_LEFT),
                'title' => $maquina->nombre,
                'codigo' => strtolower($maquina->codigo)
            ];
        });

        $trabajadores = User::with(['asignacionesTurnos.turno:id,hora_entrada,hora_salida'])
            ->where('rol', 'operario')
            ->whereNotNull('maquina_id') // aquí antes usabas `especialidad`
            ->get();

        $fechaHoy = Carbon::today();
        $fechaLimite = $fechaHoy->copy()->addDays(14);

        Log::info('Generando eventos para trabajadores');
        $eventos = [];

        foreach ($trabajadores as $trabajador) {
            foreach ($trabajador->asignacionesTurnos as $asignacionTurno) {
                // Ignorar turnos de vacaciones
                if ($asignacionTurno->turno_id == 10) {
                    continue;
                }
                $fechaTurno = Carbon::parse($asignacionTurno->fecha);

                if ($fechaTurno->between($fechaHoy, $fechaLimite)) {
                    $turno = $asignacionTurno->turno;

                    $horaEntrada = $turno?->hora_entrada ?? '08:00:00';
                    $horaSalida = $turno?->hora_salida ?? '16:00:00';

                    if ($horaEntrada === '22:00:00' && $horaSalida === '06:00:00') {
                        $start = Carbon::parse($asignacionTurno->fecha)->subDay()->format('Y-m-d') . 'T22:00:00';
                        $end = $asignacionTurno->fecha . 'T06:00:00';
                    } elseif ($horaEntrada === '06:00:00') {
                        $start = $asignacionTurno->fecha . 'T06:00:00';
                        $end = $asignacionTurno->fecha . 'T14:00:00';
                    } elseif ($horaEntrada === '14:00:00') {
                        $start = $asignacionTurno->fecha . 'T14:00:00';
                        $end = $asignacionTurno->fecha . 'T22:00:00';
                    } else {
                        $start = $asignacionTurno->fecha . 'T' . $horaEntrada;
                        $end = $asignacionTurno->fecha . 'T' . $horaSalida;
                    }

                    $maquinaId = $asignacionTurno->maquina_id ?? $trabajador->maquina_id;
                    $resourceId = $maquinaId ? str_pad($maquinaId, 3, '0', STR_PAD_LEFT) : null;

                    // Evento del turno
                    $eventos[] = [
                        'id' => 'turno-' . $asignacionTurno->id,
                        'title' => $trabajador->name,
                        'start' => $start,
                        'end' => $end,
                        'resourceId' => $resourceId,
                        'extendedProps' => [
                            'categoria_id' => $trabajador->categoria_id,
                            'categoria_nombre' => $trabajador->categoria?->nombre,
                        ],
                        'maquina_id' => $trabajador->maquina_id
                    ];
                    // Evento de entrada
                    if ($asignacionTurno->entrada) {
                        $entradaHora = Carbon::parse($asignacionTurno->entrada)->format('H:i');
                        $eventos[] = [
                            'id' => 'entrada-' . $asignacionTurno->id,
                            'title' => '🟢 ' . $trabajador->name,
                            'start' => Carbon::parse($asignacionTurno->entrada)->toIso8601String(),
                            'resourceId' => $resourceId,
                            'color' => '#28a745',
                            'textColor' => '#ffffff',
                            'allDay' => false,
                        ];

                        $registroFichajes[$trabajador->id]['entrada'] = $entradaHora;
                    }

                    // Evento de salida
                    if ($asignacionTurno->salida) {
                        $salidaHora = Carbon::parse($asignacionTurno->salida)->format('H:i');
                        $eventos[] = [
                            'id' => 'salida-' . $asignacionTurno->id,
                            'title' => '🔴 ' . $trabajador->name,
                            'start' => Carbon::parse($asignacionTurno->salida)->toIso8601String(),
                            'resourceId' => $resourceId,
                            'color' => '#dc3545',
                            'textColor' => '#ffffff',
                            'allDay' => false,
                        ];

                        $registroFichajes[$trabajador->id]['salida'] = $salidaHora;
                    }
                }
            }
        }



        Log::info('Eventos generados: ', ['count' => count($eventos)]);
        $trabajadoresEventos = $eventos;

        $fechaActual = Carbon::today();
        $operariosTrabajando = User::where('rol', 'operario')
            ->whereHas('asignacionesTurnos', function ($query) use ($fechaActual) {
                $query->whereDate('fecha', $fechaActual)
                    ->where('turno_id', '<>', 10); // Excluir vacaciones
            })
            ->get();

        $idsConEventos = collect($eventos)->pluck('trabajador.id')->unique();
        $trabajadoresSinEvento = $trabajadores->filter(fn($t) => !$idsConEventos->contains($t->id));

        Log::info('Trabajadores sin eventos:', $trabajadoresSinEvento->pluck('name')->toArray());

        return view('produccion.index', compact('maquinas', 'trabajadoresEventos', 'operariosTrabajando', 'estadoProduccionMaquinas', 'registroFichajes'));
    }

    public function actualizarPuesto(Request $request, $id)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
        ]);

        $asignacion = AsignacionTurno::findOrFail($id);
        $asignacion->maquina_id = $request->input('maquina_id');
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
