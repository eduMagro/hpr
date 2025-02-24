<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistroFichaje;
use App\Models\User;
use App\Models\Obra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RegistroFichajeController extends Controller
{
    /**
     * Mostrar lista de fichajes.
     */
    public function index()
    {
        $fichajes = RegistroFichaje::with('user')->latest()->paginate(10);
        return view('users.show', compact('fichajes'));
    }

    /**
     * Mostrar formulario para crear un nuevo fichaje.
     */
    public function create()
    {
        $usuarios = User::all(); // Obtener todos los usuarios para el formulario
        return view('registros_fichajes.create', compact('usuarios'));
    }

    /**
     * Guardar un nuevo fichaje en la base de datos.
     */
    public function store(Request $request)
    {
        // 🔍 REGISTRAR LOG DE LOS DATOS RECIBIDOS
        Log::info('📩 Datos recibidos en store()', $request->all());
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo' => 'required|in:entrada,salida',
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
            ], [
                'latitud.required' => 'La latitud es requerida.',
                'latitud.numeric' => 'La latitud debe ser un número',
                'longitud.required' => 'La longitud es requerida.',
                'longitud.numeric' => 'La longitud debe ser un número.',
            ]);

            $user = User::findOrFail($request->user_id);

            // Verificar si el usuario tiene rol de operario
            if ($user->rol !== 'operario') {
                return response()->json(['error' => 'No tienes permisos para fichar.'], 403);
            }

            $fechaHoy = now()->toDateString();

            // Obtener coordenadas de la obra seleccionada
        $obra = Obra::findOrFail($request->obra_id);
        $latitud = $obra->latitud;
        $longitud = $obra->longitud;
        $radio = $obra->distancia;

            // Calcular la distancia entre la ubicación del usuario y la nave
            $distancia = $this->calcularDistancia(
                $request->latitud,
                $request->longitud,
                $latitud,
                $longitud
            );
            if ($distancia > $radio) {
                return response()->json(['error' => 'No puedes fichar fuera de la nave de trabajo.'], 403);
            }
            // Obtener el turno del trabajador para el día actual
            $asignacionTurno = $user->asignacionesTurnos()->where('fecha', $fechaHoy)->first();

            if (!$asignacionTurno) {
                return response()->json(['error' => 'No tienes un turno asignado para hoy.'], 403);
            }

            $turnoNombre = strtolower($asignacionTurno->turno->nombre); // Convertir a minúsculas

            // Verificar si el usuario ya tiene un registro de fichaje hoy
            $fichaje = RegistroFichaje::where('user_id', $request->user_id)
                ->whereDate('entrada', $fechaHoy)
                ->first();

            // Validar fichaje según el turno asignado
            if ($request->tipo === 'entrada') {
                if ($fichaje) {
                    return response()->json(['error' => 'Ya has registrado una entrada hoy.'], 403);
                }

                if (!$this->validarHoraEntrada($turnoNombre, now())) {
                    return response()->json(['error' => 'No puedes fichar fuera de tu horario de turno.'], 403);
                }

                RegistroFichaje::create([
                    'user_id' => $request->user_id,
                    'entrada' => now(),
                    'salida' => null
                ]);
            } elseif ($request->tipo === 'salida') {
                if (!$fichaje) {
                    return response()->json(['error' => 'No puedes registrar una salida sin haber registrado entrada.'], 403);
                }

                if (!$this->validarHoraSalida($turnoNombre, now())) {
                    return response()->json(['error' => 'No puedes fichar salida fuera de tu horario de turno.'], 403);
                }

                $fichaje->update(['salida' => now()]);
            }

            return response()->json(['success' => 'Fichaje registrado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el fichaje: ' . $e->getMessage()], 500);
        }
    }

    private function validarHoraEntrada($turno, $horaActual)
    {
        $hora = $horaActual->format('H:i');

        return match ($turno) {
            'mañana' => $hora >= '05:45' && $hora <= '08:30',
            'tarde' => $hora >= '13:45' && $hora <= '16:30',
            'noche' => $hora >= '11:45' && $hora <= '23:59' || $hora >= '00:00' && $hora <= '02:30',
            default => false,
        };
    }

    private function validarHoraSalida($turno, $horaActual)
    {
        $hora = $horaActual->format('H:i');

        return match ($turno) {
            'mañana' => $hora >= '14:00' && $hora <= '15:00',
            'tarde' => $hora >= '22:00' && $hora <= '23:00',
            'noche' => $hora >= '06:00' && $hora <= '07:30',
            default => false,
        };
    }

    /**
     * Calcula la distancia en metros entre dos puntos geográficos usando la fórmula de Haversine.
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000; // Radio de la Tierra en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $radioTierra * $c; // Distancia en metros
    }
    /**
     * Mostrar un registro de fichaje específico.
     */
    public function show($id)
    {
        $fichaje = RegistroFichaje::with('user')->findOrFail($id);
        return view('registros_fichaje.show', compact('fichaje'));
    }

    /**
     * Eliminar un fichaje.
     */
    public function destroy($id)
    {
        $fichaje = RegistroFichaje::findOrFail($id);
        $fichaje->delete();

        return redirect()->route('registros-fichaje.index')
            ->with('success', 'Fichaje eliminado correctamente.');
    }
}
