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
        // 1) Obras (cliente HPR)
        $obras = Obra::with('cliente')
            ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
            ->orderBy('obra')
            ->get();

        // 2) Obra activa (?obra=ID)
        $obraActualId = request('obra');
        $obraActiva   = $obras->firstWhere('id', $obraActualId) ?? $obras->first();
        $cliente      = $obraActiva?->cliente;

        // 3) Dimensiones nave (m) -> grid real a 0,5 m/celda
        $anchoM = max(1, (int) ($obraActiva->ancho_m ?? 22));
        $largoM = max(1, (int) ($obraActiva->largo_m ?? 115));
        $columnasReales = $anchoM * 2; // celdas
        $filasReales    = $largoM * 2; // celdas

        // 4) Orientación (vertical por defecto)
        //    vertical => la vista intercambia ejes (lado largo queda en vertical)
        $orientacion = request('orientacion', 'vertical'); // 'vertical' | 'horizontal'
        $estaGirado  = ($orientacion === 'vertical');

        // 5) Tamaño de la vista (según orientación)
        // 4) Orientación
        $orientacion = request('orientacion', 'vertical'); // 'vertical' | 'horizontal'
        $estaGirado  = ($orientacion === 'vertical');      // true => vertical

        // 5) Tamaño de la vista (según orientación)  ✅ CORREGIDO
        if ($estaGirado) {
            // Vertical: W × H (sin transponer)
            $columnasVista = $columnasReales;
            $filasVista    = $filasReales;
        } else {
            // Horizontal: H × W (transpuesta)
            $columnasVista = $filasReales;
            $filasVista    = $columnasReales;
        }


        // 6) Localizaciones con máquina (solo las que tienen máquina, y con su nombre)
        $localizacionesConMaquina = collect();
        if ($obraActiva) {
            $localizacionesConMaquina = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $obraActiva->id)
                ->whereNotNull('maquina_id')
                ->get()
                ->filter(fn($loc) => $loc->maquina)
                ->map(function ($loc) {
                    return [
                        'id'           => $loc->id,
                        'x1'           => (int) $loc->x1,
                        'y1'           => (int) $loc->y1,
                        'x2'           => (int) $loc->x2,
                        'y2'           => (int) $loc->y2,
                        'maquina_id'   => (int) $loc->maquina_id,
                        'maquina_nombre' => $loc->maquina->nombre ?? 'Máquina',
                        // NOTA: no guardamos nombre de localización; usamos el de la máquina
                    ];
                })
                ->values();
        }

        // 7) Payload “ligero” para dibujar (si lo necesitas en JS)
        $machines = $localizacionesConMaquina->map(function ($loc) {
            return [
                'id'    => $loc['id'],
                'mx1'   => (float) $loc['x1'], // en “celdas reales” (equivale a metros*2)
                'my1'   => (float) $loc['y1'],
                'mx2'   => (float) $loc['x2'],
                'my2'   => (float) $loc['y2'],
                'code'  => $loc['maquina_nombre'],
                'label' => $loc['maquina_nombre'],
            ];
        })->toArray();

        // 8) Sectores (cada 20 m)
        $sectorSize      = 20;
        $numeroSectores  = max(1, (int) ceil($largoM / $sectorSize));

        // 9) Contexto JS para la vista
        $ctx = [
            'naveId'         => $obraActiva?->id,
            'estaGirado'     => $estaGirado,          // ⬅️ clave: true = vertical
            'orientacion'    => $orientacion,         // 'vertical' | 'horizontal'
            'columnasReales' => $columnasReales,
            'filasReales'    => $filasReales,
            'columnasVista'  => $columnasVista,
            'filasVista'     => $filasVista,
            'ocupadas'       => $localizacionesConMaquina->map(fn($l) => [
                'x1' => $l['x1'],
                'y1' => $l['y1'],
                'x2' => $l['x2'],
                'y2' => $l['y2'],
            ])->toArray(),
        ];

        // 10) Dimensiones para cabecera
        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obraActiva?->obra,
        ];

        // 11) (Opcional) Máquinas de esa obra, por si las listaras
        $maquinas = $obraActiva
            ? \App\Models\Maquina::where('obra_id', $obraActiva->id)->get()
            : collect();

        // 12) LOG útil
        Log::debug('localizaciones.index payload', [
            'obra_id'        => $obraActiva?->id,
            'orientacion'    => $orientacion,
            'grid_real'      => "{$columnasReales}x{$filasReales}",
            'grid_vista'     => "{$columnasVista}x{$filasVista}",
            'loc_count'      => $localizacionesConMaquina->count(),
            'sample'         => $localizacionesConMaquina->take(3),
        ]);

        return view('localizaciones.index', [
            'obras'                    => $obras,
            'obraActualId'             => $obraActualId,
            'cliente'                  => $cliente,
            'dimensiones'              => $dimensiones,
            'numeroSectores'           => $numeroSectores,
            'localizacionesConMaquina' => $localizacionesConMaquina, // para pintar overlays
            'machines'                 => $machines,                  // por si lo usas en JS
            'ctx'                      => $ctx,                       // ⬅️ trae estaGirado + tamaños
            'columnasVista'            => $columnasVista,
            'filasVista'               => $filasVista,
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
        // 📌 Obras del cliente "Hierros Paco Reyes"
        $obras = Obra::with('cliente')
            ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
            ->orderBy('obra')
            ->get();

        // 📌 Obra activa
        $obraActualId = request('obra');
        $obraActiva   = $obras->firstWhere('id', $obraActualId) ?? $obras->first();
        $cliente      = $obraActiva?->cliente;

        // 📌 Dimensiones nave (m)
        $anchoM = max(1, (int) ($obraActiva->ancho_m ?? 50));
        $largoM = max(1, (int) ($obraActiva->largo_m ?? 50));

        // 📌 Grid real (celdas de 0,5 m)
        $columnasReales = $anchoM * 2;
        $filasReales    = $largoM * 2;

        // 📌 Vista: lado más largo en horizontal
        $estaGirado     = $filasReales > $columnasReales;
        $columnasVista  = $estaGirado ? $filasReales : $columnasReales;
        $filasVista     = $estaGirado ? $columnasReales : $filasReales;

        // 📌 Colecciones para la vista
        $localizacionesConMaquina = collect();
        $localizacionesZonas      = collect(); // 👈 NUEVO: no-maquina
        $ocupadas                 = [];
        $localizacionesTodas      = collect();
        $maquinasDisponibles      = collect();

        if ($obraActiva) {
            // Todas las localizaciones de la nave (incluye nombre)
            $localizaciones = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $obraActiva->id)
                ->get();

            // 👇 Maquinas colocadas (overlays)
            $localizacionesConMaquina = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->filter(fn($l) => $l->maquina) // solo si existe la relación
                ->values()
                ->map(function ($l) {
                    return [
                        'id'         => (int) $l->id,
                        'x1'         => (int) $l->x1,
                        'y1'         => (int) $l->y1,
                        'x2'         => (int) $l->x2,
                        'y2'         => (int) $l->y2,
                        'tipo'       => (string) $l->tipo,            // 'maquina'
                        'maquina_id' => (int) $l->maquina_id,
                        'nombre'     => (string) ($l->nombre ?: $l->maquina->nombre), // respeta tu nueva columna
                        'nave_id'    => (int) $l->nave_id,
                    ];
                });

            // 👇 Zonas no-maquina (transitable / almacenamiento / carga_descarga)
            $localizacionesZonas = $localizaciones
                ->filter(fn($l) => $l->tipo !== 'maquina')
                ->values()
                ->map(function ($l) {
                    return [
                        'id'         => (int) $l->id,
                        'x1'         => (int) $l->x1,
                        'y1'         => (int) $l->y1,
                        'x2'         => (int) $l->x2,
                        'y2'         => (int) $l->y2,
                        'tipo'       => (string) $l->tipo,            // 'transitable' | 'almacenamiento' | 'carga_descarga'
                        'nombre'     => (string) ($l->nombre ?: strtoupper(str_replace('_', '/', $l->tipo))),
                        'nave_id'    => (int) $l->nave_id,
                    ];
                });

            // 👇 Coords para colisiones: incluye todo salvo transitables (coincide con tu backend)
            $ocupadas = $localizaciones
                ->filter(fn($l) => $l->tipo !== 'transitable')
                ->map(fn($l) => [
                    'x1' => (int) $l->x1,
                    'y1' => (int) $l->y1,
                    'x2' => (int) $l->x2,
                    'y2' => (int) $l->y2,
                ])->values()->all();

            $localizacionesTodas = $localizaciones;

            // IDs de máquinas ya colocadas en ESTA nave
            $maquinasColocadasIds = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->pluck('maquina_id')
                ->unique()
                ->values()
                ->all();

            // Máquinas disponibles: de la obra, no grúa, no colocadas
            $maquinasDisponibles = Maquina::where('obra_id', $obraActiva->id)
                ->when(!empty($maquinasColocadasIds), fn($q) => $q->whereNotIn('id', $maquinasColocadasIds))
                ->where(function ($q) {
                    $q->whereNull('tipo')->orWhereRaw('LOWER(tipo) <> ?', ['grua']);
                })
                ->select('id', 'nombre', 'ancho_m', 'largo_m')
                ->get()
                ->map(function ($m) {
                    $wCeldas = max(1, (int) round(($m->ancho_m ?? 1) * 2));
                    $hCeldas = max(1, (int) round(($m->largo_m ?? 1) * 2));
                    return [
                        'id'      => (int) $m->id,
                        'nombre'  => (string) $m->nombre,
                        'wCeldas' => $wCeldas,
                        'hCeldas' => $hCeldas,
                    ];
                })->values();
        }

        // Cabecera
        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obraActiva?->obra,
        ];

        // Contexto para JS
        $ctx = [
            'naveId'         => (int) $obraActiva->id,
            'columnasVista'  => (int) $columnasVista,
            'filasVista'     => (int) $filasVista,
            'columnasReales' => (int) $columnasReales,
            'filasReales'    => (int) $filasReales,
            'estaGirado'     => (bool) $estaGirado,
            'ocupadas'       => $ocupadas, // 👈 ahora incluye no-transitables
            'storeUrl'       => route('localizaciones.store'),
        ];
        $ctx['deleteUrlTemplate'] = route('localizaciones.destroy', ['localizacione' => ':id']);

        return view('localizaciones.create', compact(
            'obras',
            'obraActualId',
            'obraActiva',
            'cliente',
            'dimensiones',
            'columnasReales',
            'filasReales',
            'columnasVista',
            'filasVista',
            'estaGirado',
            'localizacionesConMaquina',
            'localizacionesZonas',   // 👈 pásalo a la vista
            'localizacionesTodas',
            'maquinasDisponibles',
            'ctx'
        ));
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
        // Normaliza "carga-descarga" -> "carga_descarga"
        if ($request->filled('tipo')) {
            $request->merge(['tipo' => str_replace('-', '_', $request->input('tipo'))]);
        }

        $isJson = $request->expectsJson() || $request->ajax() || $request->isJson();

        // Reglas base
        $rules = [
            'x1'      => 'required|integer|min:1',
            'y1'      => 'required|integer|min:1',
            'x2'      => 'required|integer|min:1',
            'y2'      => 'required|integer|min:1',
            'tipo'    => 'required|in:maquina,transitable,almacenamiento,carga_descarga',
            'nave_id' => 'required|integer|exists:obras,id',
        ];

        // maquina_id obligatorio solo si tipo=maquina
        if ($request->input('tipo') === 'maquina' || str_replace('-', '_', $request->input('tipo')) === 'maquina') {
            $rules['maquina_id'] = 'required|integer|exists:maquinas,id';
        } else {
            $rules['maquina_id'] = 'nullable|integer|exists:maquinas,id';
        }

        $messages = [
            'tipo.required'      => 'Debes seleccionar el tipo de zona.',
            'tipo.in'            => 'Tipo inválido.',
            'nave_id.required'   => 'Debe indicar la nave.',
            'nave_id.exists'     => 'La nave indicada no existe.',
            'maquina_id.required' => 'Debes seleccionar la máquina.',
            'maquina_id.exists'  => 'La máquina indicada no existe.',
        ];

        $v = Validator::make($request->all(), $rules, $messages);
        if ($v->fails()) {
            return $isJson
                ? response()->json(['success' => false, 'message' => 'Errores de validación.', 'errors' => $v->errors()], 422)
                : back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        // Normalizar coordenadas
        $x1 = min($data['x1'], $data['x2']);
        $x2 = max($data['x1'], $data['x2']);
        $y1 = min($data['y1'], $data['y2']);
        $y2 = max($data['y1'], $data['y2']);

        // Solape (permitimos transitables, bloqueamos contra no-transitables)
        $solapa = Localizacion::where('nave_id', $data['nave_id'])
            ->where('tipo', '!=', 'transitable')
            ->where(function ($q) use ($x1, $y1, $x2, $y2) {
                $q->where('x1', '<=', $x2)->where('x2', '>=', $x1)
                    ->where('y1', '<=', $y2)->where('y2', '>=', $y1);
            })->exists();

        if ($solapa) {
            return $isJson
                ? response()->json(['success' => false, 'message' => 'Ya existe una zona que solapa en esta nave.'], 409)
                : back()->withErrors(['solape' => 'Ya existe una zona que solapa en esta nave.'])->withInput();
        }

        try {
            $localizacion = Localizacion::create([
                'x1'         => $x1,
                'y1'         => $y1,
                'x2'         => $x2,
                'y2'         => $y2,
                'tipo'       => $data['tipo'],
                'maquina_id' => $data['tipo'] === 'maquina' ? ($data['maquina_id'] ?? null) : null,
                'nave_id'    => $data['nave_id'],
            ]);

            return $isJson
                ? response()->json(['success' => true, 'message' => 'Zona guardada.', 'id' => $localizacion->id, 'localizacion' => $localizacion], 201)
                : back()->with('success', 'Zona guardada.');
        } catch (\Throwable $e) {
            \Log::error('Error al guardar zona', ['e' => $e->getMessage()]);
            return $isJson
                ? response()->json(['success' => false, 'message' => 'Error al guardar.'], 500)
                : back()->with('error', 'Error al guardar.')->withInput();
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
