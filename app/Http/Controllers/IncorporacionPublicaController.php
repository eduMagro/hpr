<?php

namespace App\Http\Controllers;

use App\Models\Incorporacion;
use App\Models\IncorporacionFormacion;
use App\Models\IncorporacionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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

        // Validación de formación según empresa
        if ($incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR) {
            $rules['formacion_curso_20h'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_curso_6h'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_otros'] = 'nullable|array';
            $rules['formacion_otros.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_otros_nombres'] = 'nullable|array';
            $rules['formacion_otros_nombres.*'] = 'string|max:255';
        } else {
            $rules['formacion_generica'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules['formacion_especifica'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
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

        try {
            // Guardar certificado bancario
            $certBancario = $request->file('certificado_bancario');
            $nombreCert = 'cert_' . $incorporacion->id . '_' . time() . '.' . $certBancario->getClientOriginalExtension();
            $certBancario->storeAs('incorporaciones', $nombreCert, 'public');

            // Actualizar datos personales
            $incorporacion->update([
                'dni' => strtoupper($validated['dni']),
                'numero_afiliacion_ss' => $validated['numero_afiliacion_ss'],
                'email' => strtolower($validated['email']),
                'telefono' => $validated['telefono'],
                'certificado_bancario' => $nombreCert,
                'datos_completados_at' => now(),
                'estado' => Incorporacion::ESTADO_DATOS_RECIBIDOS,
            ]);

            // Guardar documentos de formación
            if ($incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR) {
                $this->guardarFormacion($incorporacion, $request->file('formacion_curso_20h'), 'curso_20h_generico');
                $this->guardarFormacion($incorporacion, $request->file('formacion_curso_6h'), 'curso_6h_ferralla');

                // Otros cursos (múltiples)
                if ($request->hasFile('formacion_otros')) {
                    $nombres = $request->input('formacion_otros_nombres', []);
                    foreach ($request->file('formacion_otros') as $index => $archivo) {
                        $nombre = $nombres[$index] ?? 'Otro curso ' . ($index + 1);
                        $this->guardarFormacion($incorporacion, $archivo, 'otros_cursos', $nombre);
                    }
                }
            } else {
                $this->guardarFormacion($incorporacion, $request->file('formacion_generica'), 'formacion_generica_puesto');
                $this->guardarFormacion($incorporacion, $request->file('formacion_especifica'), 'formacion_especifica_puesto');
            }

            // Registrar log
            $incorporacion->registrarLog(
                IncorporacionLog::ACCION_DATOS_COMPLETADOS,
                'El candidato ha completado el formulario de incorporación',
                null,
                [
                    'dni' => $incorporacion->dni,
                    'email' => $incorporacion->email,
                    'telefono' => $incorporacion->telefono,
                ]
            );

            return redirect()->route('incorporacion.publica', $token)
                ->with('success', 'Datos enviados correctamente. Gracias por completar el formulario.');

        } catch (\Exception $e) {
            Log::error('Error al procesar incorporación: ' . $e->getMessage());

            return redirect()->route('incorporacion.publica', $token)
                ->with('error', 'Ha ocurrido un error al procesar los datos. Por favor, inténtelo de nuevo.')
                ->withInput();
        }
    }

    private function guardarFormacion(Incorporacion $incorporacion, $archivo, $tipo, $nombre = null)
    {
        if (!$archivo) return;

        $nombreArchivo = 'form_' . $incorporacion->id . '_' . $tipo . '_' . time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $archivo->storeAs('incorporaciones', $nombreArchivo, 'public');

        $incorporacion->formaciones()->create([
            'tipo' => $tipo,
            'nombre' => $nombre,
            'archivo' => $nombreArchivo,
        ]);
    }
}
