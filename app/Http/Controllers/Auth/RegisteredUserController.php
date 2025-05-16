<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'movil_personal' => ['nullable', 'string', 'lowercase', 'max:255', 'unique:users,movil_personal'],
            'movil_empresa' => ['nullable', 'string', 'lowercase', 'max:255', 'unique:users,movil_empresa'],
            'dni' => [
                'required',
                'string',
                'max:9',
                'regex:/^(?:\d{8}[A-Z]|[XYZ]\d{7}[A-Z])$/', // Soporta DNI y NIE
                'unique:' . User::class
            ],
            'rol' => ['required', 'string', 'max:255', 'in:operario,oficina,visitante'], // Campo rol

            'categoria' => ['string', 'max:255'],

            'turno' => ['string', 'max:255', 'in:diurno,nocturno,mañana,flexible'], // Campo turno añadido

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

            'rol.required' => 'El rol es obligatorio.',
            'rol.string' => 'El rol debe ser un texto válido.',
            'rol.max' => 'El rol no puede superar los 255 caracteres.',
            'rol.in' => 'El rol debe ser uno de los siguientes: operario, oficina o visitante.',

            'categoria.string' => 'La categoría debe ser un texto válido.',
            'categoria.max' => 'La categoría no puede superar los 255 caracteres.',

            'turno.string' => 'El turno debe ser un texto válido.',
            'turno.max' => 'El turno no puede superar los 255 caracteres.',
            'turno.in' => 'El turno debe ser uno de los siguientes: diurno, nocturno, mañana o flexible.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Calcular los días de vacaciones disponibles
        $diasVacaciones = $this->calcularVacaciones();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'movil_personal' => $request->movil_personal,
            'movil_empresa' => $request->movil_empresa,
            'dni' => $request->dni,
            'rol' => $request->rol,
            'categoria' => $request->categoria,
            'turno' => $request->turno,
            'dias_vacaciones' => $diasVacaciones,
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
    private function calcularVacaciones(): int
    {
        $totalDiasVacaciones = 22; // Total de días de vacaciones por año
        $fechaActual = Carbon::now();
        $diasDelAño = $fechaActual->isLeapYear() ? 366 : 365; // Considera años bisiestos
        $diasTranscurridos = $fechaActual->dayOfYear; // Días desde el 1 de enero hasta hoy

        // Cálculo proporcional de los días de vacaciones restantes
        $diasVacacionesRestantes = floor(($totalDiasVacaciones / $diasDelAño) * ($diasDelAño - $diasTranscurridos));

        return max($diasVacacionesRestantes, 0); // Asegura que nunca sea negativo
    }
}
