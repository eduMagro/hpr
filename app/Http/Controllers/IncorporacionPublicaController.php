<?php

namespace App\Http\Controllers;

use App\Models\Incorporacion;
use App\Models\IncorporacionFormacion;
use App\Models\IncorporacionLog;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncorporacionPublicaController extends Controller
{
    public function show($token)
    {
        $incorporacion = Incorporacion::where('token', $token)->firstOrFail();

        // Si ya completó los datos, mostrar página de confirmación
        if ($incorporacion->datos_completados_at) {
            return view('incorporaciones.publica-completada', compact('incorporacion'));
        }

        // Si está cancelada, mostrar mensaje
        if ($incorporacion->estado === Incorporacion::ESTADO_CANCELADA) {
            return view('incorporaciones.publica-cancelada', compact('incorporacion'));
        }

        // Determinar tipos de formación según empresa
        $tiposFormacion = $incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR
            ? IncorporacionFormacion::TIPOS_HPR
            : IncorporacionFormacion::TIPOS_HIERROS;

        return view('incorporaciones.publica', compact('incorporacion', 'tiposFormacion'));
    }

    public function store(Request $request, $token)
    {
        $incorporacion = Incorporacion::where('token', $token)->firstOrFail();

        // Verificar que no esté ya completada
        if ($incorporacion->datos_completados_at) {
            return redirect()->route('incorporacion.publica', $token)
                ->with('error', 'Este formulario ya fue completado anteriormente.');
        }

        // Validación base
        $rules = [
            'dni' => ['required', 'string', 'size:9', 'regex:/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z])$/'],
            'numero_afiliacion_ss' => ['required', 'string', 'size:12', 'regex:/^[0-9]{12}$/'],
            'email' => 'required|email|max:255',
            'telefono' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'certificado_bancario' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        // Validación de formación según empresa (todos opcionales)
        if ($incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR) {
            $rules['formacion_curso_20h'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_curso_6h'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_otros'] = 'nullable|array';
            $rules['formacion_otros.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_otros_nombres'] = 'nullable|array';
            $rules['formacion_otros_nombres.*'] = 'string|max:255';
        } else {
            $rules['formacion_generica'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_especifica'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $messages = [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.regex' => 'El formato del DNI no es válido (8 números + letra o NIE).',
            'numero_afiliacion_ss.required' => 'El número de afiliación es obligatorio.',
            'numero_afiliacion_ss.regex' => 'El número de afiliación debe tener 12 dígitos.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener 9 dígitos.',
            'certificado_bancario.required' => 'El certificado bancario es obligatorio.',
            'certificado_bancario.mimes' => 'El certificado bancario debe ser PDF, JPG o PNG.',
            'certificado_bancario.max' => 'El certificado bancario no puede superar 5MB.',
            'formacion_curso_20h.required' => 'El curso de 20H es obligatorio.',
            'formacion_curso_6h.required' => 'El curso de 6H Ferralla es obligatorio.',
            'formacion_generica.required' => 'La formación genérica del puesto es obligatoria.',
            'formacion_especifica.required' => 'La formación específica del puesto es obligatoria.',
        ];

        $validated = $request->validate($rules, $messages);

        DB::beginTransaction();

        try {
            // Crear el usuario automáticamente
            $usuario = $this->crearUsuario($incorporacion, $validated);

            // Carpeta del usuario: nombre_id (sin caracteres especiales)
            $carpetaUsuario = $this->generarNombreCarpeta($usuario);

            // Guardar certificado bancario
            $certBancario = $request->file('certificado_bancario');
            $nombreCert = 'certificado_bancario_' . time() . '.' . $certBancario->getClientOriginalExtension();
            $certBancario->storeAs("private/documentos/{$carpetaUsuario}", $nombreCert);

            // Actualizar datos personales y vincular usuario
            $incorporacion->update([
                'dni' => strtoupper($validated['dni']),
                'numero_afiliacion_ss' => $validated['numero_afiliacion_ss'],
                'email' => strtolower($validated['email']),
                'telefono' => $validated['telefono'],
                'certificado_bancario' => $nombreCert,
                'datos_completados_at' => now(),
                'estado' => Incorporacion::ESTADO_DATOS_RECIBIDOS,
                'user_id' => $usuario->id,
            ]);

            // Guardar documentos de formación
            if ($incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR) {
                $this->guardarFormacion($incorporacion, $request->file('formacion_curso_20h'), 'curso_20h_generico', null, $carpetaUsuario);
                $this->guardarFormacion($incorporacion, $request->file('formacion_curso_6h'), 'curso_6h_ferralla', null, $carpetaUsuario);

                // Otros cursos (múltiples)
                if ($request->hasFile('formacion_otros')) {
                    $nombres = $request->input('formacion_otros_nombres', []);
                    foreach ($request->file('formacion_otros') as $index => $archivo) {
                        $nombre = $nombres[$index] ?? 'Otro curso ' . ($index + 1);
                        $this->guardarFormacion($incorporacion, $archivo, 'otros_cursos', $nombre, $carpetaUsuario);
                    }
                }
            } else {
                $this->guardarFormacion($incorporacion, $request->file('formacion_generica'), 'formacion_generica_puesto', null, $carpetaUsuario);
                $this->guardarFormacion($incorporacion, $request->file('formacion_especifica'), 'formacion_especifica_puesto', null, $carpetaUsuario);
            }

            // Registrar log
            $incorporacion->registrarLog(
                IncorporacionLog::ACCION_DATOS_COMPLETADOS,
                'El candidato ha completado el formulario de incorporación. Usuario creado automáticamente.',
                null,
                [
                    'dni' => $incorporacion->dni,
                    'email' => $incorporacion->email,
                    'telefono' => $incorporacion->telefono,
                    'user_id' => $usuario->id,
                ]
            );

            DB::commit();

            return redirect()->route('incorporacion.publica', $token)
                ->with('success', 'Datos enviados correctamente. Gracias por completar el formulario.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar incorporación: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());

            $mensajeError = 'Error: ' . $e->getMessage();

            // En desarrollo mostrar más detalles
            if (config('app.debug')) {
                $mensajeError .= ' (Línea: ' . $e->getLine() . ' en ' . basename($e->getFile()) . ')';
            }

            return redirect()->route('incorporacion.publica', $token)
                ->with('error', $mensajeError)
                ->withInput();
        }
    }

    /**
     * Crear usuario automáticamente a partir de los datos de incorporación
     */
    private function crearUsuario(Incorporacion $incorporacion, array $datos): User
    {
        $dni = strtoupper($datos['dni']);

        // Verificar si ya existe un usuario con este DNI (sin el scope de activos)
        $usuarioExistente = User::withoutGlobalScopes()->where('dni', $dni)->first();
        if ($usuarioExistente) {
            // Si existe pero está inactivo, reactivarlo
            if ($usuarioExistente->estado !== 'activo') {
                $usuarioExistente->update(['estado' => 'activo']);
            }
            return $usuarioExistente;
        }

        // Obtener empresa_id basado en empresa_destino
        $empresaId = $this->obtenerEmpresaId($incorporacion->empresa_destino);

        // Obtener categoría por defecto (primera disponible)
        $categoriaId = Categoria::first()?->id ?? 1;

        // Contraseña: DNI sin la letra (los 8 primeros caracteres numéricos)
        $password = preg_replace('/[^0-9]/', '', $dni);

        return User::create([
            'name' => ucwords(strtolower($incorporacion->name)),
            'primer_apellido' => ucwords(strtolower($incorporacion->primer_apellido)),
            'segundo_apellido' => $incorporacion->segundo_apellido ? ucwords(strtolower($incorporacion->segundo_apellido)) : null,
            'dni' => $dni,
            'email' => strtolower($datos['email']),
            'movil_personal' => $datos['telefono'],
            'password' => Hash::make($password),
            'empresa_id' => $empresaId,
            'categoria_id' => $categoriaId,
            'estado' => 'activo',
            'dias_vacaciones' => 0,
            // rol y turno se dejan null para que se asignen manualmente desde users
        ]);
    }

    /**
     * Obtener el ID de empresa basado en el valor de empresa_destino
     */
    private function obtenerEmpresaId(string $empresaDestino): int
    {
        if ($empresaDestino === Incorporacion::EMPRESA_HPR) {
            return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])->value('id') ?? 1;
        }

        return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])->value('id') ?? 1;
    }

    /**
     * Generar nombre de carpeta para el usuario: nombre_apellido_id
     */
    private function generarNombreCarpeta(User $usuario): string
    {
        $nombre = Str::slug($usuario->name, '_');
        $apellido = Str::slug($usuario->primer_apellido, '_');

        return "{$nombre}_{$apellido}_{$usuario->id}";
    }

    private function guardarFormacion(Incorporacion $incorporacion, $archivo, $tipo, $nombre = null, $carpetaUsuario = null)
    {
        if (!$archivo) return;

        $nombreArchivo = $tipo . '_' . time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();

        // Guardar en carpeta privada del usuario
        if ($carpetaUsuario) {
            $archivo->storeAs("private/documentos/{$carpetaUsuario}", $nombreArchivo);
        } else {
            // Fallback al método anterior (no debería usarse)
            $archivo->storeAs('incorporaciones', $nombreArchivo, 'public');
        }

        $incorporacion->formaciones()->create([
            'tipo' => $tipo,
            'nombre' => $nombre,
            'archivo' => $nombreArchivo,
        ]);
    }
}
