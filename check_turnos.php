<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;
use App\Models\User;
use App\Models\Maquina;
use App\Models\Festivo;
use App\Models\AsignacionTurno;

echo "=== SIMULANDO EXACTAMENTE EL CONTROLADOR trabajadores() ===\n\n";

// Código EXACTO del controlador (copiado de ProduccionController.php)
$coloresEventos = [
    1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'],
    2 => ['bg' => '#6EE7B7', 'border' => '#34D399'],
    3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'],
];

$maquinas = Maquina::orderByRaw("FIELD(id, 1, 2, 3, 4, 5, 18, 19, 17, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 20, 21, 22, 23)")
    ->orderBy('id')
    ->get()
    ->map(fn($m) => ['id' => $m->id, 'title' => $m->nombre, 'obra_id' => $m->obra_id]);

$trabajadores = User::with([
    'asignacionesTurnos.turno:id,hora_inicio,hora_fin',
    'asignacionesTurnos.obra.cliente',
    'categoria',
    'maquina'
])
    ->where('rol', 'operario')
    ->whereHas('asignacionesTurnos', function ($q) {
        $q->whereHas('obra.cliente', function ($q) {
            $q->whereRaw('LOWER(empresa) LIKE ?', ['%hierros paco reyes%']);
        });
    })
    ->get();

$fechaHoy = Carbon::today()->subWeek();
$fechaLimite = $fechaHoy->copy()->addDays(40);

$eventos = [];

foreach ($trabajadores as $trabajador) {
    foreach ($trabajador->asignacionesTurnos as $asignacionTurno) {
        if ($asignacionTurno->turno_id == 10) {
            continue;
        }
        $fechaTurno = Carbon::parse($asignacionTurno->fecha);

        if ($fechaTurno->between($fechaHoy, $fechaLimite)) {
            $turno = $asignacionTurno->turno;

            $horaEntrada = $turno?->hora_inicio ?? '08:00:00';
            $horaSalida = $turno?->hora_fin ?? '16:00:00';

            if ($horaEntrada === '22:00:00' && $horaSalida === '06:00:00') {
                $start = $asignacionTurno->fecha . 'T00:00:00';
                $end   = $asignacionTurno->fecha . 'T06:00:00';
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
            $resourceId = $maquinaId ?: 'SIN';

            $estado = $asignacionTurno->estado ?? 'activo';
            $mostrarEstado = $estado !== 'activo';

            if (!$mostrarEstado || in_array($estado, ['vacaciones', 'baja', 'justificada', 'injustificada'])) {
                if (in_array($estado, ['vacaciones', 'baja', 'justificada', 'injustificada'])) {
                    $color = match ($estado) {
                        'vacaciones'      => ['bg' => '#f87171', 'border' => '#dc2626'],
                        'baja'            => ['bg' => '#FF8C00', 'border' => '#FF6600'],
                        'justificada'     => ['bg' => '#32CD32', 'border' => '#228B22'],
                        'injustificada'   => ['bg' => '#DC143C', 'border' => '#B22222'],
                    };
                } else {
                    $obraId = $asignacionTurno->obra_id;
                    $color = $coloresEventos[$obraId] ?? ['bg' => '#d1d5db', 'border' => '#9ca3af'];
                }

                $eventos[] = [
                    'id' => 'turno-' . $asignacionTurno->id,
                    'title' => $trabajador->nombre_completo ?? $trabajador->name,
                    'start' => $start,
                    'end' => $end,
                    'resourceId' => $resourceId,
                    'user_id' => $trabajador->id,
                    'backgroundColor' => $color['bg'],
                ];
            }
        }
    }
}

echo "Total eventos generados: " . count($eventos) . "\n\n";

// Eventos de Enrique
$eventosEnrique = array_filter($eventos, fn($e) => $e['user_id'] == 51);
echo "Eventos de Enrique: " . count($eventosEnrique) . "\n\n";

foreach ($eventosEnrique as $e) {
    echo "ID: {$e['id']} | Start: {$e['start']} | End: {$e['end']} | ResourceId: {$e['resourceId']} (tipo: " . gettype($e['resourceId']) . ")\n";
}

echo "\n=== PRIMERAS 3 MÁQUINAS ===\n";
foreach ($maquinas->take(3) as $m) {
    echo "ID: {$m['id']} (tipo: " . gettype($m['id']) . ") | Title: {$m['title']}\n";
}
