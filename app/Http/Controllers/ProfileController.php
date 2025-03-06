<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Maquina;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Obra;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class ProfileController extends Controller
{

    public function aplicarFiltros(Request $request)
    {
        // Iniciar la consulta de usuarios
        $query = User::query()->select('users.*');

        // Aplicar filtros si están presentes en la solicitud
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        // Filtrar por nombre
        if ($request->filled('name')) {
            $query->where('users.name', 'like', '%' . $request->input('name') . '%');
        }

        // Filtrar por email
        if ($request->filled('email')) {
            $query->where('users.email', 'like', '%' . $request->input('email') . '%');
        }

        // Filtrar por rol
        if ($request->filled('rol')) {
            $query->where('users.rol', $request->input('rol'));
        }

        // Filtrar por categoría
        if ($request->filled('categoria')) {
            $query->where('users.categoria', 'like', '%' . $request->input('categoria') . '%');
        }

        // Filtrar por especialidad
        if ($request->filled('especialidad')) {
            $query->where('users.especialidad', 'like', '%' . $request->input('especialidad') . '%');
        }
        // Obtener la fecha de hoy
        $hoy = Carbon::today()->toDateString();
        // Filtrar por turno de hoy si se selecciona un turno
        if ($request->filled('turno')) {
            $query->whereHas('asignacionesTurnos', function ($q) use ($request, $hoy) {
                $q->where('fecha', $hoy)->whereHas('turno', function ($t) use ($request) {
                    $t->where('nombre', $request->input('turno'));
                });
            });
        }
        // Filtrar por estado
        if ($request->filled('estado')) {
            $query->where('users.estado', $request->input('estado'));
        }

        // Ordenar resultados
        $sortBy = $request->input('sort_by', 'users.created_at');
        $order = $request->input('order', 'desc');
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        return $query;
    }

    public function index(Request $request)
    {
        // Obtener la cantidad de usuarios conectados
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();
        $obras = Obra::where('completada', 0)->get();
        // Obtener valores únicos desde la tabla users
        $categorias = User::distinct()->pluck('categoria')->filter()->sort();
        $especialidades = Maquina::distinct()->pluck('codigo')->filter()->sort();
        $roles = User::distinct()->pluck('rol')->filter()->sort();
        // Obtener la fecha de hoy
        $hoy = Carbon::today()->toDateString();

        // Obtener los turnos asignados para hoy desde la tabla asignaciones_turnos
        $turnosHoy = AsignacionTurno::where('fecha', $hoy)
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->pluck('turnos.nombre')
            ->unique()
            ->sort();
        // Aplicar filtros
        $query = $this->aplicarFiltros($request);

        $registrosUsuarios = $query->paginate(10)->appends($request->except('page'));
        // Obtener el usuario autenticado
        $user = auth()->user();

        // **Asignación fija de colores a los turnos**
        $coloresTurnos = [
            'mañana' => ['bg' => '#FFD700', 'border' => $this->darkenColor('#FFD700')],  // Dorado
            'tarde' => ['bg' => '#f87171', 'border' => $this->darkenColor('#f87171')],   // Rojo claro
            'noche' => ['bg' => '#1E90FF', 'border' => $this->darkenColor('#1E90FF')],   // Azul
            'baja' => ['bg' => '#32CD32', 'border' => $this->darkenColor('#32CD32')],    // Verde lima
            'vacaciones' => ['bg' => '#FF8C00', 'border' => $this->darkenColor('#FF8C00')], // Naranja
            'falta_justificada' => ['bg' => '#6366f1', 'border' => $this->darkenColor('#6366f1')], // Azul violáceo
            'falta_injustificada' => ['bg' => '#FF69B4', 'border' => $this->darkenColor('#FF69B4')], // Rosa
            'media_falta_justificada' => ['bg' => '#A52A2A', 'border' => $this->darkenColor('#A52A2A')], // Marrón
            'media_falta_injustificada' => ['bg' => '#8A2BE2', 'border' => $this->darkenColor('#8A2BE2')], // Violeta
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
        return view('User.index', compact('registrosUsuarios', 'usuariosConectados', 'obras', 'user', 'eventos', 'coloresTurnos', 'categorias', 'especialidades', 'roles', 'turnosHoy'));
    }

    public function show($id)
    {
        $user = User::with(['registrosFichajes', 'asignacionesTurnos.turno'])->findOrFail($id);

        // Fecha de inicio (1 de enero del año actual)
        $inicioAño = Carbon::now()->startOfYear();

        // Contar asignaciones de turnos específicos desde el 1 de enero
        $faltasInjustificadas = $user->asignacionesTurnos->where('turno.nombre', 'falta_injustificada')
            ->where('fecha', '>=', $inicioAño)->count();

        $faltasJustificadas = $user->asignacionesTurnos->where('turno.nombre', 'falta_justificada')
            ->where('fecha', '>=', $inicioAño)->count();

        $diasBaja = $user->asignacionesTurnos->where('turno.nombre', 'baja')
            ->where('fecha', '>=', $inicioAño)->count();

        // **Obtener todos los turnos de la base de datos**
        $turnos = Turno::all();

        // **Asignación fija de colores a los turnos**
        $coloresTurnos = [
            'mañana'              => ['bg' => '#008000', 'border' => $this->darkenColor('#008000')],  // Verde
            'tarde'               => ['bg' => '#0000FF', 'border' => $this->darkenColor('#0000FF')],  // Azul
            'noche'               => ['bg' => '#FFFF00', 'border' => $this->darkenColor('#FFFF00')],  // Amarillo
            'baja'                => ['bg' => '#D3D3D3', 'border' => $this->darkenColor('#D3D3D3')],  // Gris claro
            'vacaciones'          => ['bg' => '#FFD700', 'border' => $this->darkenColor('#FFD700')],  // Dorado
            'falta_justificada'   => ['bg' => '#808080', 'border' => $this->darkenColor('#808080')],  // Gris 
            'falta_injustificada' => ['bg' => '#000000', 'border' => $this->darkenColor('#000000')],  // Negro
        ];

        // Uso en el código
        $coloresAsignados = $turnos->mapWithKeys(function ($turno) use ($coloresTurnos) {
            return [
                $turno->nombre => $coloresTurnos[$turno->nombre] ?? ['bg' => '#708090', 'border' => $this->darkenColor('#708090')] // Gris oscuro si no está definido
            ];
        });



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

        return view('User.show', compact(
            'user',
            'eventos',
            'coloresTurnos',
            'turnos',
            'faltasInjustificadas',
            'faltasJustificadas',
            'mediaFaltasJustificadas',
            'mediaFaltasInjustificadas',
            'diasBaja'
        ));
    }

    /**
     * Función para oscurecer un color en hexadecimal.
     */
    private function darkenColor($hex, $percent = 20)
    {
        $hex = str_replace("#", "", $hex);
        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];

        foreach ($rgb as &$value) {
            $value = max(0, min(255, $value - ($value * ($percent / 100))));
        }

        return sprintf("#%02X%02X%02X", $rgb[0], $rgb[1], $rgb[2]);
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
                'name' => 'required|string|max:50',
                'email' => 'required|email|max:255|unique:users,email,' . $id,
                'rol' => 'required|string|max:50',
                'categoria' => 'required|string|max:50',
                'especialidad' => 'nullable|string|max:15',
                'turno' => 'nullable|string|in:nocturno,diurno,flexible'
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.string' => 'El nombre debe ser un texto válido.',
                'name.max' => 'El nombre no puede superar los 50 caracteres.',

                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ingresar un correo electrónico válido.',
                'email.max' => 'El correo no puede superar los 50 caracteres.',
                'email.unique' => 'Este correo ya está registrado en otro usuario.',

                'rol.required' => 'El rol es obligatorio.',
                'rol.string' => 'El rol debe ser un texto válido.',
                'rol.max' => 'El rol no puede superar los 50 caracteres.',

                'categoria.required' => 'La categoría es obligatoria.',
                'categoria.string' => 'La categoría debe ser un texto válido.',
                'categoria.max' => 'La categoría no puede superar los 255 caracteres.',

                'especialidad.string' => 'La especialidad debe ser un texto válido.',

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
                'especialidad' => $request->especialidad,
                'turno' => $request->turno
            ]);

            return response()->json(['success' => 'Usuario actualizado correctamente.']);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function generarTurnos(User $user)
    {
        // Obtener los IDs de los turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        // Definir el inicio y fin del año actual
        $inicio = Carbon::now()->startOfYear();
        $fin = Carbon::now()->endOfYear();

        // Determinar el turno asignado según el tipo de turno del usuario
        if ($user->turno == 'diurno') {
            $turnoAsignado = ($user->turno_actual == 1) ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } else {
            return redirect()->back()->with('error', 'El usuario no tiene un turno asignado.');
        }

        // Recorrer las fechas del año y asignar turnos (excluyendo sábados y domingos)
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            AsignacionTurno::updateOrCreate(
                ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                ['turno_id' => $turnoAsignado, 'asignacion_manual' => false, 'modificado' => false]
            );

            // Si es diurno, cambiar el turno el viernes para mantener la rotación
            if ($user->turno == 'diurno' && $fecha->dayOfWeek == Carbon::FRIDAY) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }

        return redirect()->back()->with('success', "Turnos generados correctamente para {$user->name}.");
    }

    public function destroy(Request $request, $id)
    {
        try {

            // Buscar el usuario a eliminar
            $user = User::findOrFail($id);

            // Validar la contraseña del administrador
            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors(['userDeletion.password' => 'La contraseña proporcionada es incorrecta.']);
            }

            // Eliminar usuario
            $user->delete();

            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }
}
