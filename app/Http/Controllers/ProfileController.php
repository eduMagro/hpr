<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Vacaciones;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Obra;
class ProfileController extends Controller
{

    public function index(Request $request)
    {
        // Obtener la cantidad de usuarios conectados
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();
        $obras = Obra::where('completada', 0)->get();

        // Consulta de usuarios sin el LEFT JOIN innecesario
        $query = User::query()->select('users.*');

        // Filtrar por nombre si se pasa como parámetro
        if ($request->has('name')) {
            $name = $request->input('name');
            $query->where('users.name', 'like', '%' . $name . '%');
        }

        // Ordenar
        $sortBy = $request->input('sort_by', 'users.created_at');
        $order = $request->input('order', 'desc');
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosUsuarios = $query->paginate($perPage)->appends($request->except('page'));

        // Pasar datos a la vista
        return view('User.index', compact('registrosUsuarios', 'usuariosConectados', 'obras'));
    }

    public function show($id)
    {
        $user = User::with(['registrosFichajes', 'asignacionesTurnos.turno', 'vacaciones'])->findOrFail($id);

        // **Obtener fechas de vacaciones**
        $fechasVacaciones = $user->vacaciones->pluck('fecha')->toArray();

        // **Eventos de fichajes (entradas y salidas)**
        $eventosFichajes = $user->registrosFichajes->flatMap(function ($fichaje) {
            $events = [];

            // Entrada
            $events[] = [
                'title' => 'Entrada: ' . Carbon::parse($fichaje->entrada)->format('H:i'),
                'start' => Carbon::parse($fichaje->entrada)->toIso8601String(),
                'color' => '#28a745', // Verde para entradas
                'allDay' => false
            ];

            // Salida (si existe)
            if ($fichaje->salida) {
                $events[] = [
                    'title' => 'Salida: ' . Carbon::parse($fichaje->salida)->format('H:i'),
                    'start' => Carbon::parse($fichaje->salida)->toIso8601String(),
                    'color' => '#dc3545', // Rojo para salidas
                    'allDay' => false
                ];
            }

            return $events;
        });

        // **Eventos de turnos asignados (EXCLUYENDO los días que sean vacaciones)**
        $eventosTurnos = $user->asignacionesTurnos->filter(function ($asignacion) use ($fechasVacaciones) {
            return !in_array($asignacion->fecha, $fechasVacaciones); // Solo incluir si NO es un día de vacaciones
        })->map(function ($asignacion) {
            return [
                'title' => 'Turno: ' . ucfirst($asignacion->turno->nombre),
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'color' => match ($asignacion->turno->nombre) {
                    'mañana' => '#FFD700',  // Amarillo para turno de mañana
                    'tarde' => '#FF8C00',   // Naranja para turno de tarde
                    'noche' => '#1E90FF',   // Azul para turno de noche
                    'flexible' => '#32CD32', // Verde para flexible
                    default => '#808080',   // Gris si hay un error
                },
                'allDay' => true // Ocupa todo el día
            ];
        });

        // **Eventos de vacaciones**
        $eventosVacaciones = $user->vacaciones->map(function ($vacacion) {
            return [
                'title' => 'Vacaciones',
                'start' => Carbon::parse($vacacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171', // Rojo claro para vacaciones
                'borderColor' => '#dc2626', // Rojo oscuro para el borde
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        // **Combinar fichajes, turnos (sin vacaciones) y vacaciones en un solo array**
        $eventos = $eventosFichajes->merge($eventosTurnos)->merge($eventosVacaciones);

        return view('User.show', compact('user', 'eventos'));
    }

    /**
     * Display the user's profile form.
     */
    public function edit($id)
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Verificar si el usuario autenticado es administrador
        if ($authUser->categoria !== 'administrador') {
            return redirect()->route('dashboard')->with('abort', 'No tienes permiso para editar perfiles.');
        }

        // Buscar el usuario que se quiere editar
        $user = User::findOrFail($id);

        return view('profile.edit', compact('user'));
    }



    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, $id): RedirectResponse
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Verificar si el usuario autenticado es administrador
        if ($authUser->categoria !== 'administrador') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para actualizar perfiles.');
        }

        // Buscar el usuario que se quiere actualizar
        $user = User::findOrFail($id);
        $user->fill($request->validated());

        if ($request->filled('email') && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->filled('categoria')) {
            $user->categoria = $request->input('categoria');
        }

        $user->save();

        return Redirect::route('profile.edit', ['id' => $id])->with('status', 'profile-updated');
    }

    public function destroy(Request $request, $id)
    {
        $admin = auth()->user();

        // Verificar que el usuario autenticado es un administrador
        if ($admin->categoria !== 'administrador') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para eliminar usuarios.');
        }

        // Buscar el usuario a eliminar
        $user = User::findOrFail($id);

        // Evitar que un administrador se elimine a sí mismo
        if ($admin->id === $user->id) {
            return redirect()->route('dashboard')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        // Validar la contraseña del administrador
        if (!Hash::check($request->password, $admin->password)) {
            return back()->withErrors(['userDeletion.password' => 'La contraseña proporcionada es incorrecta.']);
        }

        // Eliminar usuario
        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuario eliminado correctamente.');
    }
}
