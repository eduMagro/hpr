<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
    public function index(Request $request)
    {
        $filterResult = $this->getFilteredGroups($request);
        $grupos = $filterResult['grupos'];

        // Count active incidents for the header
        $activasCount = \App\Models\Incidencia::where('estado', '!=', 'resuelta')->count();

        // Get machines for the create modal
        $maquinas = \App\Models\Maquina::with('obra:id,obra')->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'imagen', 'obra_id']);

        return view('incidencias.index', compact('grupos', 'activasCount', 'maquinas'));
    }

    public function listAjax(Request $request)
    {
        $filterResult = $this->getFilteredGroups($request);
        $grupos = $filterResult['grupos'];

        return view('incidencias.partials.lista', compact('grupos'))->render();
    }

    private function getFilteredGroups(Request $request)
    {
        // Define the filter based on request
        $incidentState = $request->has('ver_inactivas') ? 'resuelta' : '!=,resuelta';
        $operator = $request->has('ver_inactivas') ? '=' : '!=';

        // Use a subquery to order machines by their latest relevant incident
        $latestIncidentSubquery = \App\Models\Incidencia::select('fecha_reporte')
            ->whereColumn('maquina_id', 'maquinas.id')
            ->where('estado', $operator, 'resuelta')
            ->latest('fecha_reporte')
            ->take(1);

        // Query machines that have relevant incidents
        $grupos = \App\Models\Maquina::whereHas('incidencias', function ($query) use ($operator) {
            $query->where('estado', $operator, 'resuelta');
        })
            ->with([
                'incidencias' => function ($query) use ($operator) {
                    $query->where('estado', $operator, 'resuelta')
                        ->with('user')
                        ->latest('fecha_reporte');
                },
                'obra:id,obra'
            ])
            ->addSelect(['latest_incident_date' => $latestIncidentSubquery])
            ->orderByDesc('latest_incident_date')
            ->paginate(10);

        return ['grupos' => $grupos];
    }

    public function show($id)
    {
        $incidencia = \App\Models\Incidencia::with(['maquina', 'user', 'resolver'])->findOrFail($id);

        // Load history for the same machine
        $historial = \App\Models\Incidencia::where('maquina_id', $incidencia->maquina_id)
            ->where('id', '!=', $id)
            ->latest('fecha_reporte')
            ->limit(5)
            ->get();

        return view('incidencias.show', compact('incidencia', 'historial'));
    }

    public function create(Request $request)
    {
        $maquina_id = $request->get('maquina_id');
        $maquinas = \App\Models\Maquina::select('id', 'codigo', 'nombre')->get();
        return view('incidencias.create', compact('maquinas', 'maquina_id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'descripcion' => 'nullable|string', // Changed to nullable as per modal optional hint
            'titulo' => 'required',
            'fotos.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240', // Increased max size
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Added validation for single file
            'estado_maquina' => 'nullable|in:activa,averiada,pausa,mantenimiento'
        ]);

        $paths = [];

        // Helper to save file to public directory
        $saveFile = function ($file) use (&$paths) {
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('incidencias-archivos'), $filename);
            $paths[] = 'incidencias-archivos/' . $filename;
        };

        // Handle multiple files
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $file) {
                $saveFile($file);
            }
        }

        // Handle single file (from mobile modal)
        if ($request->hasFile('imagen')) {
            $saveFile($request->file('imagen'));
        }

        $incidencia = \App\Models\Incidencia::create([
            'maquina_id' => $request->maquina_id,
            'user_id' => auth()->id(),
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion ?? '',
            'fotos' => $paths,
            'prioridad' => $request->prioridad ?? 'media',
            'estado' => 'abierta',
            'fecha_reporte' => now(),
        ]);

        // Update machine status if provided

        $maquina = \App\Models\Maquina::find($request->maquina_id);
        if ($maquina) {
            // Default to 'averiada' only if not provided, for backward compatibility or safety
            $nuevoEstado = $request->input('estado_maquina', 'averiada');
            $maquina->estado = $nuevoEstado;
            $maquina->save();

            // Send Alert
            try {
                $mensaje = "Incidencia en máquina: {$maquina->nombre} | {$request->titulo} | Solicitado por " . auth()->user()->name . " | <a class='text-blue-500 hover:underline hover:text-blue-600' href='" . route('incidencias.show', $incidencia->id) . "'>[Incidencia]</a>";

                // Get configuration or default to 'oficina' role
                $config = \App\Models\Configuracion::get('alertas_averias_destinatarios', [
                    'roles' => ['oficina'],
                    'departamentos' => [],
                    'usuarios' => []
                ]);

                $recipientIds = collect();

                // By Role
                if (!empty($config['roles'])) {
                    $ids = \App\Models\User::whereIn('rol', $config['roles'])->pluck('id');
                    $recipientIds = $recipientIds->merge($ids);
                }

                // By Department
                if (!empty($config['departamentos'])) {
                    $ids = \App\Models\User::whereHas('departamentos', function ($q) use ($config) {
                        $q->whereIn('nombre', $config['departamentos']);
                    })->pluck('id');
                    $recipientIds = $recipientIds->merge($ids);
                }

                // By specific Users
                if (!empty($config['usuarios'])) {
                    $recipientIds = $recipientIds->merge($config['usuarios']);
                }

                $recipientIds = $recipientIds->unique();

                if ($recipientIds->isNotEmpty()) {
                    $alerta = \App\Models\Alerta::create([
                        'mensaje' => $mensaje,
                        'user_id_1' => auth()->id(),
                        'destino' => 'Averías',
                        'leida' => false,
                    ]);

                    foreach ($recipientIds as $userId) {
                        \App\Models\AlertaLeida::create([
                            'alerta_id' => $alerta->id,
                            'user_id' => $userId,
                            'leida_en' => null,
                        ]);
                    }
                }

            } catch (\Throwable $e) {
                // Log error but allow flow to continue
                \Illuminate\Support\Facades\Log::error('Error enviando alerta de incidencia: ' . $e->getMessage());
            }
        }

        return redirect()->route('incidencias.show', $incidencia->id)->with('success', 'Incidencia reportada correctamente');
    }

    public function resolve(Request $request, $id)
    {
        $incidencia = \App\Models\Incidencia::with('maquina')->findOrFail($id);

        $request->validate([
            'resolucion' => 'required',
            'coste' => 'nullable|numeric|min:0'
        ]);

        $incidencia->update([
            'estado' => 'resuelta',
            'resolucion' => $request->resolucion,
            'fecha_resolucion' => now(),
            'resuelto_por' => auth()->id(),
            'coste' => $request->coste,
        ]);

        $this->syncGasto($incidencia);

        // Check if machine has other active incidents, if not, set to 'activa'
        $activeIncidents = \App\Models\Incidencia::where('maquina_id', $incidencia->maquina_id)
            ->where('estado', '!=', 'resuelta')
            ->exists();

        if (!$activeIncidents && $incidencia->maquina) {
            $maquina = $incidencia->maquina;
            $maquina->estado = 'activa';
            $maquina->save();
        }

        return redirect()->back()->with('success', 'Incidencia marcada como resuelta');
    }

    private function syncGasto(\App\Models\Incidencia $incidencia)
    {
        if ($incidencia->coste && $incidencia->coste > 0) {
            // Find or create motive based on description or title
            $descripcion = $incidencia->descripcion ?: $incidencia->titulo;
            $motivoNombre = \Illuminate\Support\Str::limit($descripcion, 190);

            // Try to find existing motive by name or create
            $motivo = \App\Models\GastoMotivo::firstOrCreate(
                ['nombre' => $motivoNombre]
            );

            // Determine Nave/Obra. 
            // If maquina->obra is "Nave A" or "Nave B" (or ID 1, 2 typically), set nave_id.
            // Logic: If maquina has obra_id, check if that obra is a Nave.
            $naveId = null;
            $obraId = null;

            if ($incidencia->maquina && $incidencia->maquina->obra_id) {
                // Check if this obra is actually a specific "Nave" (often implicit in project logic)
                // For now, assign to nave_id as requested ("nave_id -> maquina_id -> obra_id")
                // Assuming maquina.obra_id points to the Nave.
                $naveId = $incidencia->maquina->obra_id;
            }

            $gastoData = [
                'fecha_pedido' => $incidencia->fecha_reporte,
                'fecha_llegada' => $incidencia->fecha_resolucion,
                'nave_id' => $naveId,
                'obra_id' => null, // Enforced null if using nave_id, or logically distinct
                'proveedor_id' => null,
                'maquina_id' => $incidencia->maquina_id,
                'motivo_id' => $motivo->id,
                'coste' => $incidencia->coste,
                'codigo_factura' => null,
                'observaciones' => $incidencia->resolucion,
                'incidencia_id' => $incidencia->id,
            ];

            \App\Models\Gasto::updateOrCreate(
                ['incidencia_id' => $incidencia->id],
                $gastoData
            );
        } else {
            // If cost removed/zero, remove linked Gasto if exists
            if ($incidencia->gasto) {
                $incidencia->gasto->delete();
            }
        }
    }

    public function updateResolution(Request $request, $id)
    {
        $incidencia = \App\Models\Incidencia::with(['maquina', 'gasto'])->findOrFail($id);

        $request->validate([
            'resolucion' => 'required',
            'coste' => 'nullable|numeric|min:0'
        ]);

        $incidencia->update([
            'resolucion' => $request->resolucion,
            'coste' => $request->coste,
        ]);

        $this->syncGasto($incidencia);

        return redirect()->back()->with('success', 'Resolución actualizada correctamente');
    }
}
