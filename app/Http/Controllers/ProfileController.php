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

class ProfileController extends Controller
{

    public function index(Request $request)
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('dashboard')->with('abort', 'No tienes los permisos necesarios.');
        }

        $usuariosConectados = null;

        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();

        // Obtener las ubicaciones con sus productos asociados
        $usuarios = User::all();

        $query = User::query();
        // $query = $this->aplicarFiltros($query, $request);

        // Aplicar filtro por código si se pasa como parámetro en la solicitud
        if ($request->has('name')) {
            $name = $request->input('name');
            $query->where('name', 'like', '%' . $name . '%');
        }
        // Ordenar
        $sortBy = $request->input('sort_by', 'created_at');  // Primer criterio de ordenación (nombre)
        $order = $request->input('order', 'desc');        // Orden del primer criterio (asc o desc)

        // Aplicar ordenamiento por múltiples columnas
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosUsuarios = $query->paginate($perPage)->appends($request->except('page'));

        // Pasar las ubicaciones y productos a la vista
        return view('User.index', compact('registrosUsuarios', 'usuariosConectados'));
    }

    // En tu UserController.php

    public function show($id)
    {
        // Obtén al usuario y sus relaciones
        $user = User::with(['entradas', 'movimientos'])->findOrFail($id);

        // Pasa la variable a la vista
        return view('User.show', compact('user'));
    }



    /**
     * Display the user's profile form.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id); // Busca el usuario por ID o devuelve error 404

        return view('profile.edit', compact('user')); // Pasa el usuario a la vista
    }


    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        if ($request->has('categoria')) {
            $request->user()->categoria = $request->input('categoria');
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
