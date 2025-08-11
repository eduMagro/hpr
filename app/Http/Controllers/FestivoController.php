<?php

namespace App\Http\Controllers;

use App\Models\Festivo;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FestivoController extends Controller
{

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'  => 'required|date',           // 'Y-m-d'
            'titulo' => 'nullable|string|max:120',
        ]);

        $fecha = Carbon::parse($data['fecha'])->startOfDay();
        $festivo = Festivo::updateOrCreate(
            ['anio' => (int)$fecha->year, 'fecha' => $fecha->toDateString()],
            ['titulo' => $data['titulo'] ?: 'Festivo']
        );

        return response()->json([
            'ok' => true,
            'festivo' => [
                'id'     => $festivo->id,
                'titulo' => $festivo->titulo,
                'fecha'  => $festivo->fecha->toDateString(),
                'anio'   => $festivo->anio,
            ],
        ]);
    }

    public function actualizarFecha(Request $request, Festivo $festivo)
    {
        $data = $request->validate([
            'fecha' => 'required|date', // formato 'Y-m-d'
        ]);

        $nueva = Carbon::parse($data['fecha'])->startOfDay();
        $festivo->fecha = $nueva->toDateString();
        $festivo->anio  = (int) $nueva->year;
        $festivo->save();

        return response()->json([
            'ok' => true,
            'festivo' => [
                'id'    => $festivo->id,
                'fecha' => $festivo->fecha,
                'anio'  => $festivo->anio,
            ],
        ]);
    }

    public function destroy(Festivo $festivo)
    {
        $festivo->delete();

        return response()->json(['ok' => true]);
    }
}
