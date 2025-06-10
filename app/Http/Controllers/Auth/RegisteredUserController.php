<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Turno;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('users.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $categorias = Categoria::orderBy('nombre')->get(); // Puedes añadir select('id', 'nombre') si quieres optimizar
        $empresas = Empresa::orderBy('nombre')->get(); // Puedes añadir select('id', 'nombre') si quieres optimizar

        return view('auth.register', compact('categorias', 'empresas'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'primer_apellido' => ['required', 'string', 'max:255'],
            'segundo_apellido' => ['nullable', 'string', 'max:255'],

            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'movil_personal' => ['nullable', 'string', 'lowercase', 'max:255', 'unique:users,movil_personal'],
            'movil_empresa' => ['nullable', 'string', 'lowercase', 'max:255', 'unique:users,movil_empresa'],
            'dni' => [
                'required',
                'string',
                'max:9',
                'regex:/^(?:\d{8}[A-Z]|[XYZ]\d{7}[A-Z])$/',
                'unique:' . User::class
            ],
            'empresa_id' => ['required', 'exists:empresas,id'],
            'rol' => ['required', 'string', 'max:255', 'in:operario,oficina,visitante'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'turno' => ['string', 'max:255', 'in:diurno,nocturno,mañana,flexible'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',

            'primer_apellido.required' => 'El primer apellido es obligatorio.',
            'primer_apellido.string' => 'El primer apellido debe ser un texto válido.',
            'primer_apellido.max' => 'El primer apellido no puede superar los 255 caracteres.',

            'segundo_apellido.string' => 'El segundo apellido debe ser un texto válido.',
            'segundo_apellido.max' => 'El segundo apellido no puede superar los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser un texto válido.',
            'email.lowercase' => 'El correo debe estar en minúsculas.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'movil_personal.string' => 'El móvil personal debe ser un texto válido.',
            'movil_personal.lowercase' => 'El móvil personal debe estar en minúsculas.',
            'movil_personal.max' => 'El móvil personal no puede superar los 255 caracteres.',
            'movil_personal.unique' => 'Este número de móvil personal ya está registrado.',

            'movil_empresa.string' => 'El móvil de empresa debe ser un texto válido.',
            'movil_empresa.lowercase' => 'El móvil de empresa debe estar en minúsculas.',
            'movil_empresa.max' => 'El móvil de empresa no puede superar los 255 caracteres.',
            'movil_empresa.unique' => 'Este número de móvil de empresa ya está registrado.',

            'dni.required' => 'El DNI es obligatorio.',
            'dni.string' => 'El DNI debe ser un texto válido.',
            'dni.max' => 'El DNI no puede superar los 9 caracteres.',
            'dni.regex' => 'El DNI debe tener el formato correcto (8 números seguidos de una letra).',
            'dni.unique' => 'Este DNI ya está registrado.',

            'empresa_id.required' => 'La empresa es obligatoria.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',

            'rol.required' => 'El rol es obligatorio.',
            'rol.string' => 'El rol debe ser un texto válido.',
            'rol.max' => 'El rol no puede superar los 255 caracteres.',
            'rol.in' => 'El rol debe ser uno de los siguientes: operario, oficina o visitante.',

            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no es válida.',

            'turno.string' => 'El turno debe ser un texto válido.',
            'turno.max' => 'El turno no puede superar los 255 caracteres.',
            'turno.in' => 'El turno debe ser uno de los siguientes: diurno, nocturno, mañana o flexible.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'primer_apellido' => $request->primer_apellido,
            'segundo_apellido' => $request->segundo_apellido,
            'email' => $request->email,
            'movil_personal' => $request->movil_personal,
            'movil_empresa' => $request->movil_empresa,
            'dni' => $request->dni,
            'empresa_id' => $request->empresa_id,
            'rol' => $request->rol,
            'categoria_id' => $request->categoria_id,
            'turno' => $request->turno,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return redirect(route('users.index', absolute: false));
    }

    /**
     * Calcula los días de vacaciones restantes en función de la fecha de registro.
     *
     * @return int
     */
}
