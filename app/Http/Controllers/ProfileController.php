<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{

    public function index(Request $request)
    {
        if (auth()->user()->categoria !== 'administrador') {
            return redirect()->route('dashboard')->with('abort', 'No tienes los permisos necesarios.');
        }
    
        // Obtener la cantidad de usuarios conectados
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();
    
        // Consulta de usuarios con su turno más reciente
        $query = User::query()
            ->leftJoin('asignaciones_turnos', function ($join) {
                $join->on('users.id', '=', 'asignaciones_turnos.user_id')
                     ->where('asignaciones_turnos.fecha', '=', DB::raw('(SELECT MAX(fecha) FROM asignaciones_turnos WHERE user_id = users.id)'));
            })
            ->leftJoin('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->select('users.*', 'turnos.nombre as turno');
    
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
        return view('User.index', compact('registrosUsuarios', 'usuariosConectados'));
    }
    

    public function show($id)
    {
        $user = User::with('registrosFichajes')->findOrFail($id);
        return view('user.show', compact('user'));
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
