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
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


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

        // Obtener el usuario autenticado
        $user = auth()->user();

        // **Definir colores para cada turno**
        $coloresTurnos = [
            'mañana' => ['bg' => '#FFD700', 'border' => '#d4af37'],  // Amarillo
            'tarde' => ['bg' => '#FF8C00', 'border' => '#cc7000'],   // Naranja
            'noche' => ['bg' => '#1E90FF', 'border' => '#0050b3'],   // Azul
            'flexible' => ['bg' => '#32CD32', 'border' => '#228b22'], // Verde
            'vacaciones' => ['bg' => '#f87171', 'border' => '#dc2626'], // Rojo
            'baja' => ['bg' => '#6366f1', 'border' => '#4338ca'], // Azul oscuro
        ];

        // **Eventos de fichajes**
        $eventosFichajes = $user->registrosFichajes->flatMap(function ($fichaje) {
            return [
                [
                    'title' => 'Entrada: ' . Carbon::parse($fichaje->entrada)->format('H:i'),
                    'start' => Carbon::parse($fichaje->entrada)->toIso8601String(),
                    'color' => '#28a745', // Verde para entradas
                    'allDay' => false
                ],
                $fichaje->salida ? [
                    'title' => 'Salida: ' . Carbon::parse($fichaje->salida)->format('H:i'),
                    'start' => Carbon::parse($fichaje->salida)->toIso8601String(),
                    'color' => '#dc3545', // Rojo para salidas
                    'allDay' => false
                ] : null
            ];
        })->filter();

        // **Eventos de turnos asignados**
        $eventosTurnos = $user->asignacionesTurnos->map(function ($asignacion) use ($coloresTurnos) {
            $color = $coloresTurnos[$asignacion->turno->nombre] ?? ['bg' => '#808080', 'border' => '#606060']; // Gris por defecto

            return [
                'title' => ucfirst($asignacion->turno->nombre),
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        // **Combinar eventos**
        $eventos = $eventosFichajes->merge($eventosTurnos);

        // Pasar datos a la vista
        return view('User.index', compact('registrosUsuarios', 'usuariosConectados', 'obras', 'user', 'eventos', 'coloresTurnos'));
    }

    public function show($id)
    {
        $user = User::with(['registrosFichajes', 'asignacionesTurnos.turno'])->findOrFail($id);

        // **Definir colores para cada turno**
        $coloresTurnos = [
            'mañana' => ['bg' => '#FFD700', 'border' => '#d4af37'],  // Amarillo
            'tarde' => ['bg' => '#FF8C00', 'border' => '#cc7000'],   // Naranja
            'noche' => ['bg' => '#1E90FF', 'border' => '#0050b3'],   // Azul
            'flexible' => ['bg' => '#32CD32', 'border' => '#228b22'], // Verde
            'vacaciones' => ['bg' => '#f87171', 'border' => '#dc2626'], // Rojo
            'baja' => ['bg' => '#6366f1', 'border' => '#4338ca'], // Azul oscuro
        ];

        // **Eventos de fichajes (entradas y salidas)**
        $eventosFichajes = $user->registrosFichajes->flatMap(function ($fichaje) {
            return [
                [
                    'title' => 'Entrada: ' . Carbon::parse($fichaje->entrada)->format('H:i'),
                    'start' => Carbon::parse($fichaje->entrada)->toIso8601String(),
                    'color' => '#28a745', // Verde
                    'allDay' => false
                ],
                $fichaje->salida ? [
                    'title' => 'Salida: ' . Carbon::parse($fichaje->salida)->format('H:i'),
                    'start' => Carbon::parse($fichaje->salida)->toIso8601String(),
                    'color' => '#dc3545', // Rojo
                    'allDay' => false
                ] : null
            ];
        })->filter();

        // **Eventos de turnos asignados**
        $eventosTurnos = $user->asignacionesTurnos->map(function ($asignacion) use ($coloresTurnos) {
            $color = $coloresTurnos[$asignacion->turno->nombre] ?? ['bg' => '#808080', 'border' => '#606060']; // Gris por defecto

            return [
                'title' => ucfirst($asignacion->turno->nombre),
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        // **Combinar eventos**
        $eventos = $eventosFichajes->merge($eventosTurnos);

        return view('User.show', compact('user', 'eventos', 'coloresTurnos'));
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
    public function actualizarUsuario(Request $request, $id)
    {

        try {
            // Validar los datos con mensajes personalizados
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $id,
                'rol' => 'required|string|max:255',
                'categoria' => 'required|string|max:255',
                'turno' => 'nullable|string|in:nocturno,diurno,flexible'
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser un texto válido.',
                'name.max' => 'El nombre no puede superar los 255 caracteres.',

                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ingresar un correo electrónico válido.',
                'email.max' => 'El correo no puede superar los 255 caracteres.',
                'email.unique' => 'Este correo ya está registrado en otro usuario.',

                'rol.required' => 'El rol es obligatorio.',
                'rol.string' => 'El rol debe ser un texto válido.',
                'rol.max' => 'El rol no puede superar los 255 caracteres.',

                'categoria.required' => 'La categoría es obligatoria.',
                'categoria.string' => 'La categoría debe ser un texto válido.',
                'categoria.max' => 'La categoría no puede superar los 255 caracteres.',

                'turno.string' => 'El turno debe ser un texto válido.',
                'turno.in' => 'El turno debe ser "mañana", "tarde", "noche" o "flexible".'
            ]);

            // Buscar el usuario
            $usuario = User::find($id);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }

            // Actualizar los datos
            $usuario->update([
                'name' => $request->name,
                'email' => $request->email,
                'rol' => $request->rol,
                'categoria' => $request->categoria,
                'turno' => $request->turno
            ]);

            return response()->json(['success' => 'Usuario actualizado correctamente.']);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
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
