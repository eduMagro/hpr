<?php

namespace App\Http\Controllers;

use App\Models\Incorporacion;
use App\Models\IncorporacionFormacion;
use App\Models\IncorporacionLog;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Categoria;
use App\Services\DniOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\IncorporacionCompletada;
use App\Models\Departamento;

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

        // Validación base - con imágenes o PDF del DNI
        $rules = [
            'dni_frontal' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'dni_trasero' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'dni' => 'nullable|string|size:9|regex:/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z])$/i', // Campo manual de respaldo
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
            $rules['formacion_otros_nombres.*'] = 'nullable|string|max:255';
        } else {
            $rules['formacion_generica'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_especifica'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $messages = [
            'dni_frontal.required' => 'El archivo del frente del DNI es obligatorio.',
            'dni_frontal.file' => 'El frente del DNI debe ser un archivo.',
            'dni_frontal.mimes' => 'El frente del DNI debe ser JPG, PNG o PDF.',
            'dni_frontal.max' => 'El archivo del frente del DNI no puede superar 5MB.',
            'dni_trasero.mimes' => 'El reverso del DNI debe ser JPG, PNG o PDF.',
            'dni_trasero.max' => 'El archivo del reverso del DNI no puede superar 5MB.',
            'dni.size' => 'El DNI debe tener exactamente 9 caracteres.',
            'dni.regex' => 'El formato del DNI no es válido (8 números + letra o NIE).',
            'numero_afiliacion_ss.required' => 'El número de afiliación es obligatorio.',
            'numero_afiliacion_ss.size' => 'El número de afiliación debe tener exactamente 12 dígitos.',
            'numero_afiliacion_ss.regex' => 'El número de afiliación debe contener solo números.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener 9 dígitos.',
            'certificado_bancario.required' => 'El certificado bancario es obligatorio.',
            'certificado_bancario.file' => 'El certificado bancario debe ser un archivo.',
            'certificado_bancario.mimes' => 'El certificado bancario debe ser PDF, JPG o PNG.',
            'certificado_bancario.max' => 'El certificado bancario no puede superar 5MB.',
            'formacion_curso_20h.mimes' => 'El curso de 20H debe ser PDF, JPG o PNG.',
            'formacion_curso_20h.max' => 'El curso de 20H no puede superar 5MB.',
            'formacion_curso_6h.mimes' => 'El curso de 6H Ferralla debe ser PDF, JPG o PNG.',
            'formacion_curso_6h.max' => 'El curso de 6H Ferralla no puede superar 5MB.',
            'formacion_otros.*.mimes' => 'Los archivos de otros cursos deben ser PDF, JPG o PNG.',
            'formacion_otros.*.max' => 'Los archivos de otros cursos no pueden superar 5MB.',
            'formacion_otros_nombres.*.string' => 'El nombre del curso debe ser texto.',
            'formacion_otros_nombres.*.max' => 'El nombre del curso no puede superar 255 caracteres.',
            'formacion_generica.mimes' => 'La formación genérica debe ser PDF, JPG o PNG.',
            'formacion_generica.max' => 'La formación genérica no puede superar 5MB.',
            'formacion_especifica.mimes' => 'La formación específica debe ser PDF, JPG o PNG.',
            'formacion_especifica.max' => 'La formación específica no puede superar 5MB.',
        ];

        $validated = $request->validate($rules, $messages);

        // Extraer DNI de las imágenes usando OCR
        $dniOcrService = new DniOcrService();
        $resultadoOcr = $dniOcrService->extraerDni(
            $request->file('dni_frontal'),
            $request->file('dni_trasero')
        );

        // Usar el DNI del OCR o el introducido manualmente
        $dniExtraido = $resultadoOcr['dni'];
        $dniManual = $request->input('dni');

        // Priorizar DNI manual si fue proporcionado, sino usar el del OCR
        $dniFinal = !empty($dniManual) ? strtoupper($dniManual) : $dniExtraido;

        // Validar que tenemos un DNI válido
        if (empty($dniFinal)) {
            return redirect()->route('incorporacion.publica', $token)
                ->with('error', 'No se pudo detectar el DNI de las fotos. Por favor, asegúrate de que las imágenes sean claras y el DNI sea legible, o introduce el DNI manualmente.')
                ->withInput();
        }

        // Validar formato del DNI extraído
        if (!$dniOcrService->validarFormatoDni($dniFinal)) {
            return redirect()->route('incorporacion.publica', $token)
                ->with('error', 'El DNI detectado (' . $dniFinal . ') no tiene un formato válido. Por favor, verifica las fotos o introduce el DNI manualmente.')
                ->withInput();
        }

        // Añadir el DNI validado a los datos
        $validated['dni'] = $dniFinal;

        Log::info('OCR DNI - DNI extraído: ' . ($dniExtraido ?? 'null') . ', DNI manual: ' . ($dniManual ?? 'null') . ', DNI final: ' . $dniFinal);

        DB::beginTransaction();

        try {
            // Crear el usuario automáticamente
            $usuario = $this->crearUsuario($incorporacion, $validated);

            // Carpeta del usuario: nombre_id (sin caracteres especiales)
            $carpetaUsuario = $this->generarNombreCarpeta($usuario);

            // Guardar imágenes del DNI
            $dniFrontal = $request->file('dni_frontal');
            $nombreDniFrontal = 'dni_frontal_' . time() . '.' . $dniFrontal->getClientOriginalExtension();
            $dniFrontal->storeAs("private/documentos/{$carpetaUsuario}", $nombreDniFrontal);

            $nombreDniTrasero = null;
            if ($request->hasFile('dni_trasero')) {
                $dniTrasero = $request->file('dni_trasero');
                $nombreDniTrasero = 'dni_trasero_' . time() . '.' . $dniTrasero->getClientOriginalExtension();
                $dniTrasero->storeAs("private/documentos/{$carpetaUsuario}", $nombreDniTrasero);
            }

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
                'dni_frontal' => $nombreDniFrontal,
                'dni_trasero' => $nombreDniTrasero,
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

            // Enviar correo a departamentos de programador y recursos humanos
            $this->notificarDepartamentos($incorporacion);

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

    /**
     * Notificar a los departamentos de programador y recursos humanos
     */
    private function notificarDepartamentos(Incorporacion $incorporacion): void
    {
        try {
            // Obtener emails de usuarios en departamentos de programador y recursos humanos
            $emails = User::whereHas('departamentos', function ($query) {
                $query->whereIn('nombre', ['programador', 'recursos humanos', 'Programador', 'Recursos Humanos', 'RRHH', 'rrhh']);
            })
            ->where('estado', 'activo')
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->toArray();

            if (empty($emails)) {
                Log::warning('No se encontraron destinatarios para notificar la incorporación completada.');
                return;
            }

            Mail::to($emails)->send(new IncorporacionCompletada($incorporacion));

            Log::info('Notificación de incorporación enviada a: ' . implode(', ', $emails));

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de incorporación: ' . $e->getMessage());
        }
    }
}
