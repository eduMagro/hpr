<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Localizacion;
use App\Models\LocalizacionPaquete;
use App\Models\Producto;
use App\Models\Paquete;
use App\Models\Maquina;
use App\Models\Obra;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class LocalizacionController extends Controller
{
    //------------------------------------------------------------------------------------ INDEX()

    public function index()
    {
        // Obras del cliente "Hierros Paco Reyes"
        $obras = Obra::with('cliente')
            ->whereHas('cliente', function ($query) {
                $query->where('empresa', 'LIKE', '%hierros paco reyes%');
            })
            ->orderBy('obra')
            ->get();

        // Obra activa pasada por parámetro ?obra=ID
        $obraActualId = request('obra');
        $obraActiva = $obras->firstWhere('id', $obraActualId) ?? $obras->first();

        // Cliente (relación desde obra activa)
        $cliente = $obraActiva?->cliente;

        // Localizaciones de la obra activa, con su máquina
        $localizaciones = $obraActiva
            ? \App\Models\Localizacion::with('maquina')->where('nave_id', $obraActiva->id)->get()
            : collect();

        // Solo las que tienen máquina
        $localizacionesMaquinas = $localizaciones->whereNotNull('maquina_id');

        // 🔽 Serializa aquí, fuera de Blade
        $machines = $localizacionesMaquinas->map(function ($loc) {
            return [
                'id'    => $loc->id,
                'mx1'   => (float) $loc->x1,  // METROS
                'my1'   => (float) $loc->y1,
                'mx2'   => (float) $loc->x2,
                'my2'   => (float) $loc->y2,
                'code'  => optional($loc->maquina)->codigo ?? (optional($loc->maquina)->nombre ?? 'Máquina'),
                'label' => optional($loc->maquina)->nombre ?? 'Máquina',
            ];
        })->values()->toArray();

        // LOG
        Log::debug('MACHINES payload', [
            'obra_id'   => $obraActiva?->id,
            'count'     => count($machines),
            'sample'    => array_slice($machines, 0, 3),
        ]);

        // Maquinas de esa obra (AGREGADO: igual que en create)
        $maquinas = $obraActiva
            ? \App\Models\Maquina::where('obra_id', $obraActiva->id)->get()
            : collect();

        // Dimensiones de la nave
        $ancho = $obraActiva?->ancho_m ?? 10;
        $largo = $obraActiva?->largo_m ?? 10;

        $dimensiones = [
            'ancho' => $ancho,
            'alto'  => $largo,
            'obra'  => $obraActiva?->obra,
        ];

        // Sectores (cada 20 metros)
        $sectorSize = 20;
        $numeroSectores = max(1, ceil($largo / $sectorSize));

        // AGREGADO: Localizaciones con relación de máquina (igual que en create)
        $localizacionesMaquinas = Localizacion::with('maquina:id,nombre')
            ->whereNotNull('maquina_id')
            ->where('nave_id', $obraActiva->id)
            ->get()
            ->filter(fn($loc) => $loc->maquina)
            ->map(function ($loc) {
                return [
                    'id'         => $loc->id,
                    'x1'         => $loc->x1,
                    'y1'         => $loc->y1,
                    'x2'         => $loc->x2,
                    'y2'         => $loc->y2,
                    'tipo'       => $loc->tipo,
                    'maquina_id' => $loc->maquina_id,
                    'nombre'     => $loc->maquina->nombre,
                    'nave_id'    => $loc->nave_id,
                ];
            });

        return view('localizaciones.index', [
            'localizacionesMaquinas' => $localizacionesMaquinas,
            'machines'               => $machines,
            'maquinas'               => $maquinas,
            'obras'                  => $obras,
            'obraActualId'           => $obraActualId,
            'cliente'                => $cliente,
            'dimensiones'            => $dimensiones,
            'numeroSectores'         => $numeroSectores,
            // AGREGADO: igual que en create
        ]);
    }

    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $localizacion = Localizacion::findOrFail($id);
        return response()->json($localizacion);
    }

    //------------------------------------------------------------------------------------ EDITARMAPA()
    public function editarMapa()
    {
        // Obras del cliente "Hierros Paco Reyes"
        $obras = Obra::with('cliente')
            ->whereHas('cliente', function ($query) {
                $query->where('empresa', 'LIKE', '%hierros paco reyes%');
            })
            ->orderBy('obra')
            ->get();

        // Obra activa pasada por parámetro ?obra=ID
        $obraActualId = request('obra');
        $obraActiva = $obras->firstWhere('id', $obraActualId) ?? $obras->first();

        // Cliente (relación desde obra activa)
        $cliente = $obraActiva?->cliente;

        // Localizaciones de la obra activa
        $localizaciones = $obraActiva
            ? Localizacion::where('nave_id', $obraActiva->id)->get()
            : collect();

        return view('localizaciones.editarMapa', compact('localizaciones', 'obras', 'obraActualId', 'cliente', 'obraActiva'));
    }
    //------------------------------------------------------------------------------------ UPDATE LOCALIZACION()
    public function update(Request $request, $id)
    {
        Log::info("✅ Entró al método update() con ID: $id");
        try {
            $request->validate([
                'x1' => 'required|integer|min:1',
                'y1' => 'required|integer|min:1',
                'x2' => 'required|integer|min:1',
                'y2' => 'required|integer|min:1',
            ], [
                'x1.required' => 'La coordenada x1 es obligatoria.',
                'y1.required' => 'La coordenada y1 es obligatoria.',
                'x2.required' => 'La coordenada x2 es obligatoria.',
                'y2.required' => 'La coordenada y2 es obligatoria.',
            ]);

            $localizacion = Localizacion::findOrFail($id);

            // Reordenar coordenadas por si acaso
            $x1 = min($request->x1, $request->x2);
            $x2 = max($request->x1, $request->x2);
            $y1 = min($request->y1, $request->y2);
            $y2 = max($request->y1, $request->y2);

            $localizacion->update([
                'x1' => $x1,
                'y1' => $y1,
                'x2' => $x2,
                'y2' => $y2,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Localización actualizada correctamente.',
                'localizacion' => $localizacion
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La localización no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la localización: ' . $e->getMessage()
            ], 500);
        }
    }


    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {

        // Obras del cliente "Hierros Paco Reyes"
        $obras = Obra::with('cliente')
            ->whereHas('cliente', function ($query) {
                $query->where('empresa', 'LIKE', '%hierros paco reyes%');
            })
            ->orderBy('obra')
            ->get();

        // Obra activa pasada por parámetro ?obra=ID
        $obraActualId = request('obra');
        $obraActiva = $obras->firstWhere('id', $obraActualId) ?? $obras->first();
        // Dimensiones de la nave
        $ancho = $obraActiva?->ancho_m ?? 10;
        $largo = $obraActiva?->largo_m ?? 10;
        $dimensiones = [
            'ancho' => $ancho,
            'alto'  => $largo,
            'obra'  => $obraActiva?->obra,
        ];
        // Cliente (relación desde obra activa)
        $cliente = $obraActiva?->cliente;

        // Primera variable: Localizaciones CON máquina asignada
        $localizacionesConMaquina = collect([]);
        // Segunda variable: (por definir después)
        $localizacionesTodas = collect([]);
        $maquinas = collect([]);

        if ($obraActiva) {
            // 1️⃣ Localizaciones donde maquina_id NO es null, filtradas por nave_id
            $localizacionesConMaquina = Localizacion::with('maquina:id,nombre')
                ->whereNotNull('maquina_id')
                ->where('nave_id', $obraActiva->id) // 👈 filtro por nave_id (clave foránea de obras)
                ->get()
                ->filter(fn($loc) => $loc->maquina) // 👈 evita relaciones rotas
                ->map(function ($loc) {
                    return [
                        'id'         => $loc->id,
                        'x1'         => $loc->x1,
                        'y1'         => $loc->y1,
                        'x2'         => $loc->x2,
                        'y2'         => $loc->y2,
                        'tipo'       => $loc->tipo,
                        'maquina_id' => $loc->maquina_id,
                        'nombre'     => $loc->maquina->nombre,
                        'nave_id'    => $loc->nave_id,
                    ];
                });

            // 2️⃣ Segunda variable (por definir después)
            // $localizacionesTodas = ...

            // Bandeja de máquinas disponibles
            $maquinas = Maquina::where('obra_id', $obraActiva->id)->select('id', 'nombre', 'ancho_m', 'largo_m')->get();
        }

        return view('localizaciones.create', compact('localizacionesConMaquina', 'localizacionesTodas', 'maquinas', 'obras', 'obraActualId', 'cliente', 'dimensiones'));
    }
    //------------------------------------------------------------------------------------ VERIFICAR()

    public function verificar(Request $request)
    {
        // Forzamos JSON si viene sin Accept correcto
        if (!$request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        // ✅ Validación incluyendo nave_id
        $validator = Validator::make($request->all(), [
            'x1'         => 'required|integer|min:1',
            'y1'         => 'required|integer|min:1',
            'x2'         => 'required|integer|min:1',
            'y2'         => 'required|integer|min:1',
            'maquina_id' => 'nullable|integer|exists:maquinas,id',
            'excluir_id' => 'nullable|integer',
            'nave_id'    => 'required|integer|exists:obras,id', // <-- AJUSTA la tabla si es otra
        ]);

        if ($validator->fails()) {
            return response()->json([
                'existe' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Normalizar coordenadas
            $x1 = min((int)$request->x1, (int)$request->x2);
            $x2 = max((int)$request->x1, (int)$request->x2);
            $y1 = min((int)$request->y1, (int)$request->y2);
            $y2 = max((int)$request->y1, (int)$request->y2);
            $excluirId = $request->excluir_id;
            $naveId    = (int) $request->nave_id;

            Log::info('🔎 Verificando nave', compact('naveId', 'x1', 'y1', 'x2', 'y2', 'excluirId'));

            // 🔒 Base limitada a la misma nave y excluyendo transitables
            $base = Localizacion::query()
                ->select('id', 'tipo', 'x1', 'y1', 'x2', 'y2', 'maquina_id')
                ->where('nave_id', $naveId)
                ->where('tipo', '!=', 'transitable')
                ->when($excluirId, fn($q) => $q->where('id', '!=', $excluirId));

            // Coincidencia exacta
            if ($exacta = (clone $base)->where(compact('x1', 'y1', 'x2', 'y2'))->first()) {
                return response()->json([
                    'existe' => true,
                    'tipo'   => 'exacta',
                    'localizacion' => $exacta,
                ]);
            }

            // Solape (inclusivo por celdas)
            $superpuesta = (clone $base)->where(function ($q) use ($x1, $y1, $x2, $y2) {
                $q->where('x1', '<=', $x2)->where('x2', '>=', $x1)
                    ->where('y1', '<=', $y2)->where('y2', '>=', $y1);
            })->first();

            if ($superpuesta) {
                return response()->json([
                    'existe' => true,
                    'tipo'   => 'parcial',
                    'localizacion' => $superpuesta,
                ]);
            }
            // 🛑 Evitar duplicado de maquina_id
            if ($request->filled('maquina_id')) {
                $yaExiste = Localizacion::where('nave_id', $naveId)
                    ->where('maquina_id', $request->maquina_id)
                    ->when($excluirId, fn($q) => $q->where('id', '!=', $excluirId))
                    ->first();

                if ($yaExiste) {
                    return response()->json([
                        'existe' => true,
                        'tipo'   => 'duplicado_maquina',
                        'localizacion' => $yaExiste,
                        'message' => 'Esta máquina ya tiene una ubicación asignada en esta nave.'
                    ]);
                }
            }

            return response()->json(['existe' => false]);
        } catch (\Exception $e) {
            Log::error('❌ Error al guardar localización', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la localización.',
                'error'   => $e->getMessage(), // añade esto si no lo tienes
            ], 500);
        }
    }

    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        try {
            // ✅ Validación con nave_id obligatorio
            $validated = $request->validate([
                'x1'         => 'required|integer|min:1',
                'y1'         => 'required|integer|min:1',
                'x2'         => 'required|integer|min:1',
                'y2'         => 'required|integer|min:1',
                'tipo'       => 'required|in:material,maquina,transitable',
                'maquina_id' => 'nullable|integer|exists:maquinas,id',
                'nave_id'    => 'required|integer|exists:obras,id', // <-- AJUSTA la tabla si es otra
            ], [
                'nave_id.required' => 'Debe indicar la nave.',
                'nave_id.exists'   => 'La nave indicada no existe.',
            ]);

            // Normalizar coordenadas
            $x1 = min($validated['x1'], $validated['x2']);
            $x2 = max($validated['x1'], $validated['x2']);
            $y1 = min($validated['y1'], $validated['y2']);
            $y2 = max($validated['y1'], $validated['y2']);

            // (Opcional) Doble-check: que no exista solape en esta nave
            $solape = Localizacion::where('nave_id', $validated['nave_id'])
                ->where('tipo', '!=', 'transitable')
                ->where(function ($q) use ($x1, $y1, $x2, $y2) {
                    $q->where('x1', '<=', $x2)->where('x2', '>=', $x1)
                        ->where('y1', '<=', $y2)->where('y2', '>=', $y1);
                })->exists();

            if ($solape) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una localización que solapa en esta nave.',
                ], 409);
            }

            // Crear localización vinculada a la nave
            $localizacion = Localizacion::create([
                'x1'         => $x1,
                'y1'         => $y1,
                'x2'         => $x2,
                'y2'         => $y2,
                'tipo'       => $validated['tipo'],
                'maquina_id' => $validated['tipo'] === 'maquina' ? ($validated['maquina_id'] ?? null) : null,
                'nave_id'    => $validated['nave_id'], // 👈 vinculación clave
            ]);

            return response()->json([
                'success'      => true,
                'message'      => 'Localización guardada correctamente.',
                'localizacion' => $localizacion
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al guardar localización', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la localización.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    //------------------------------------------------------------------------------------ DESTROY()
    public function destroy($id)
    {
        try {
            $localizacion = \App\Models\Localizacion::findOrFail($id);

            $localizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Localización eliminada correctamente.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La localización no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la localización: ' . $e->getMessage()
            ], 500);
        }
    }
}
