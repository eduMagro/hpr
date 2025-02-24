<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        if (auth()->user()->categoria !== 'administrador') {
            return redirect()->route('users.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        return view('auth.register');
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],

            'rol' => ['required', 'string', 'max:255', 'in:operario,oficina,visitante'], // Campo rol

            'categoria' => ['required', 'string', 'max:255', 'in:administracion,gruista,operario,mecanico,visitante'],

            'turno' => ['required', 'string', 'max:255', 'in:diurno,nocturno,flexible'], // Campo turno añadido

            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser un texto válido.',
            'email.lowercase' => 'El correo debe estar en minúsculas.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'rol.required' => 'El rol es obligatorio.',
            'rol.string' => 'El rol debe ser un texto válido.',
            'rol.max' => 'El rol no puede superar los 255 caracteres.',
            'rol.in' => 'El rol debe ser uno de los siguientes: operario, oficina o visitante.',

            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.string' => 'La categoría debe ser un texto válido.',
            'categoria.max' => 'La categoría no puede superar los 255 caracteres.',
            'categoria.in' => 'La categoría debe ser una de las siguientes: administración, gruista, operario, mecánico o visitante.',

            'turno.required' => 'El turno es obligatorio.',
            'turno.string' => 'El turno debe ser un texto válido.',
            'turno.max' => 'El turno no puede superar los 255 caracteres.',
            'turno.in' => 'El turno debe ser uno de los siguientes: diurno, nocturno o flexible.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);



        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol,
            'categoria' => $request->categoria,
            'turno' => $request->turno,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return redirect(route('users.index', absolute: false));
    }
}
