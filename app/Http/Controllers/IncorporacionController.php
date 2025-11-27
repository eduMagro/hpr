<?php

namespace App\Http\Controllers;

use App\Models\Incorporacion;
use App\Models\IncorporacionDocumento;
use App\Models\IncorporacionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncorporacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Incorporacion::with(['creador', 'formaciones', 'documentos'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('empresa')) {
            $query->where('empresa_destino', $request->empresa);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_provisional', 'like', "%{$buscar}%")
                    ->orWhere('email_provisional', 'like', "%{$buscar}%")
                    ->orWhere('dni', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $incorporaciones = $query->paginate(15);

        // Estadísticas
        $stats = [
            'pendientes' => Incorporacion::where('estado', 'pendiente')->count(),
            'datos_recibidos' => Incorporacion::where('estado', 'datos_recibidos')->count(),
            'en_proceso' => Incorporacion::where('estado', 'en_proceso')->count(),
            'completadas' => Incorporacion::where('estado', 'completada')->count(),
        ];

        return view('incorporaciones.index', compact('incorporaciones', 'stats'));
    }

    public function create()
    {
        return view('incorporaciones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'empresa_destino' => 'required|in:hpr_servicios,hierros_paco_reyes',
            'name' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'telefono_provisional' => 'required|string|max:20',
        ]);

        // Generar nombre_provisional combinando los campos
        $validated['nombre_provisional'] = trim($validated['name'] . ' ' . $validated['primer_apellido'] . ' ' . ($validated['segundo_apellido'] ?? ''));
        $validated['created_by'] = auth()->id();
        $validated['token'] = Str::random(64);

        $incorporacion = Incorporacion::create($validated);

        // Registrar log
        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_CREADA,
            'Incorporación creada para ' . $validated['nombre_provisional']
        );

        return redirect()
            ->route('incorporaciones.show', $incorporacion)
            ->with('success', 'Incorporación creada correctamente. Puedes enviar el enlace al candidato.');
    }

    public function show(Incorporacion $incorporacion)
    {
        $incorporacion->load(['formaciones', 'documentos.subidoPor', 'logs.usuario', 'creador']);

        // Preparar checklist de documentos
        $documentosPost = [];
        foreach (Incorporacion::DOCUMENTOS_POST as $tipo => $nombre) {
            $doc = $incorporacion->documentos->where('tipo', $tipo)->first();
            $documentosPost[$tipo] = [
                'nombre' => $nombre,
                'documento' => $doc,
                'completado' => $doc ? $doc->completado : false,
            ];
        }

        return view('incorporaciones.show', compact('incorporacion', 'documentosPost'));
    }

    public function update(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'puesto' => 'nullable|string|max:255',
            'nombre_provisional' => 'required|string|max:255',
            'email_provisional' => 'nullable|email|max:255',
            'telefono_provisional' => 'nullable|string|max:20',
        ]);

        $validated['updated_by'] = auth()->id();

        $incorporacion->update($validated);

        return redirect()
            ->route('incorporaciones.show', $incorporacion)
            ->with('success', 'Incorporación actualizada correctamente.');
    }

    public function destroy(Incorporacion $incorporacion)
    {
        // Obtener carpeta del usuario
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaCarpeta = "private/documentos/{$carpetaUsuario}";

        // Eliminar toda la carpeta del usuario si existe
        if (Storage::exists($rutaCarpeta)) {
            Storage::deleteDirectory($rutaCarpeta);
        }

        $incorporacion->delete();

        return redirect()
            ->route('incorporaciones.index')
            ->with('success', 'Incorporación eliminada correctamente.');
    }

    public function subirDocumento(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(Incorporacion::DOCUMENTOS_POST)),
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notas' => 'nullable|string|max:500',
        ]);

        // Guardar archivo en carpeta privada del usuario
        $archivo = $request->file('archivo');
        $nombreArchivo = $validated['tipo'] . '_' . time() . '.' . $archivo->getClientOriginalExtension();

        // Obtener carpeta del usuario
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $archivo->storeAs("private/documentos/{$carpetaUsuario}", $nombreArchivo);

        // Crear o actualizar documento
        $documento = $incorporacion->documentos()->updateOrCreate(
            ['tipo' => $validated['tipo']],
            [
                'archivo' => $nombreArchivo,
                'notas' => $validated['notas'] ?? null,
                'completado' => true,
                'completado_at' => now(),
                'subido_por' => auth()->id(),
            ]
        );

        // Registrar log
        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_DOCUMENTO_SUBIDO,
            'Documento subido: ' . Incorporacion::DOCUMENTOS_POST[$validated['tipo']]
        );

        // Verificar si todos los documentos están completos
        if ($incorporacion->porcentajeDocumentosPost() === 100) {
            $incorporacion->update(['estado' => Incorporacion::ESTADO_COMPLETADA]);
            $incorporacion->registrarLog(
                IncorporacionLog::ACCION_ESTADO_CAMBIADO,
                'Estado cambiado a Completada (todos los documentos subidos)'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Documento subido correctamente',
            'documento' => $documento,
            'porcentaje' => $incorporacion->porcentajeDocumentosPost(),
        ]);
    }

    public function eliminarDocumento(Request $request, Incorporacion $incorporacion, $tipo)
    {
        $documento = $incorporacion->documentos()->where('tipo', $tipo)->first();

        if (!$documento) {
            return response()->json(['success' => false, 'message' => 'Documento no encontrado'], 404);
        }

        // Eliminar archivo de la carpeta privada
        if ($documento->archivo) {
            $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
            Storage::delete("private/documentos/{$carpetaUsuario}/" . $documento->archivo);
        }

        $documento->delete();

        // Si estaba completada, cambiar a en_proceso
        if ($incorporacion->estado === Incorporacion::ESTADO_COMPLETADA) {
            $incorporacion->update(['estado' => Incorporacion::ESTADO_EN_PROCESO]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documento eliminado correctamente',
            'porcentaje' => $incorporacion->fresh()->porcentajeDocumentosPost(),
        ]);
    }

    public function cambiarEstado(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente,datos_recibidos,en_proceso,completada,cancelada',
        ]);

        $estadoAnterior = $incorporacion->estado;
        $incorporacion->update([
            'estado' => $validated['estado'],
            'updated_by' => auth()->id(),
        ]);

        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_ESTADO_CAMBIADO,
            "Estado cambiado de {$estadoAnterior} a {$validated['estado']}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'estado' => $incorporacion->estado_badge,
        ]);
    }

    public function marcarEnlaceEnviado(Incorporacion $incorporacion)
    {
        $incorporacion->update([
            'enlace_enviado_at' => now(),
        ]);

        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_ENLACE_ENVIADO,
            'Enlace marcado como enviado'
        );

        return response()->json([
            'success' => true,
            'message' => 'Enlace marcado como enviado',
        ]);
    }

    public function copiarEnlace(Incorporacion $incorporacion)
    {
        return response()->json([
            'success' => true,
            'url' => $incorporacion->url_formulario,
        ]);
    }

    /**
     * Descargar archivo privado de incorporación
     */
    public function descargarArchivo(Incorporacion $incorporacion, string $archivo)
    {
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaArchivo = "private/documentos/{$carpetaUsuario}/{$archivo}";

        if (!Storage::exists($rutaArchivo)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::download($rutaArchivo);
    }

    /**
     * Ver archivo privado de incorporación (inline)
     */
    public function verArchivo(Incorporacion $incorporacion, string $archivo)
    {
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaArchivo = "private/documentos/{$carpetaUsuario}/{$archivo}";

        if (!Storage::exists($rutaArchivo)) {
            abort(404, 'Archivo no encontrado');
        }

        $mimeType = Storage::mimeType($rutaArchivo);

        return response(Storage::get($rutaArchivo))
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $archivo . '"');
    }

    /**
     * Obtener la carpeta del usuario para una incorporación
     */
    private function obtenerCarpetaUsuario(Incorporacion $incorporacion): string
    {
        // Si tiene usuario vinculado, usar su carpeta
        if ($incorporacion->user_id) {
            $usuario = User::withoutGlobalScopes()->find($incorporacion->user_id);
            if ($usuario) {
                $nombre = Str::slug($usuario->name, '_');
                $apellido = Str::slug($usuario->primer_apellido, '_');
                return "{$nombre}_{$apellido}_{$usuario->id}";
            }
        }

        // Fallback: usar el ID de la incorporación
        return "incorporacion_{$incorporacion->id}";
    }
}
