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
        $orientacion = request('orientacion', 'vertical'); // 'vertical' | 'horizontal'
        $estaGirado  = ($orientacion === 'vertical');      // true => vertical

        // 5) Tamaño de la vista (según orientación)
        if ($estaGirado) {
            // Vertical: W × H (sin transponer)
            $columnasVista = $columnasReales;
            $filasVista    = $filasReales;
        } else {
            // Horizontal: H × W (transpuesta)
            $columnasVista = $filasReales;
            $filasVista    = $columnasReales;
        }

        // 6) Cargar todas las localizaciones de la nave (incluido nombre)
        $localizacionesConMaquina = collect();
        $localizacionesZonas      = collect();
        $maquinasDisponibles      = collect();
        $ocupadas                 = [];

        if ($obraActiva) {
            $localizaciones = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $obraActiva->id)
                ->get();

            // 6.1) Máquinas colocadas
            $localizacionesConMaquina = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->filter(fn($l) => $l->maquina) // asegúrate de que existe relación
                ->values()
                ->map(function ($l) {
                    // tamaño en celdas por si lo usas en data-*
                    $wCeldas = max(1, (int) ($l->x2 - $l->x1 + 1));
                    $hCeldas = max(1, (int) ($l->y2 - $l->y1 + 1));

                    return [
                        'id'         => (int) $l->id,
                        'x1'         => (int) $l->x1,
                        'y1'         => (int) $l->y1,
                        'x2'         => (int) $l->x2,
                        'y2'         => (int) $l->y2,
                        'tipo'       => 'maquina',
                        'maquina_id' => (int) $l->maquina_id,
                        'nombre'     => (string) ($l->nombre ?: $l->maquina->nombre),
                        'nave_id'    => (int) $l->nave_id,
                        'wCeldas'    => $wCeldas,
                        'hCeldas'    => $hCeldas,
                    ];
                });

            // 6.2) Zonas no-maquina (transitable / almacenamiento / carga_descarga)
            $localizacionesZonas = $localizaciones
                ->filter(fn($l) => $l->tipo !== 'maquina')
                ->values()
                ->map(function ($l) {
                    $tipoNorm = str_replace('-', '_', (string) $l->tipo); // normaliza por si hubiera guiones
                    return [
                        'id'      => (int) $l->id,
                        'x1'      => (int) $l->x1,
                        'y1'      => (int) $l->y1,
                        'x2'      => (int) $l->x2,
                        'y2'      => (int) $l->y2,
                        'tipo'    => $tipoNorm, // 'transitable' | 'almacenamiento' | 'carga_descarga'
                        'nombre'  => (string) ($l->nombre ?: strtoupper(str_replace('_', '/', $tipoNorm))),
                        'nave_id' => (int) $l->nave_id,
                    ];
                });

            // 6.3) Coords para colisiones: incluye TODO salvo transitables (igual que el backend)
            $ocupadas = $localizaciones
                ->filter(fn($l) => str_replace('-', '_', $l->tipo) !== 'transitable')
                ->map(fn($l) => [
                    'x1' => (int) $l->x1,
                    'y1' => (int) $l->y1,
                    'x2' => (int) $l->x2,
                    'y2' => (int) $l->y2,
                ])->values()->all();

            // 6.4) Máquinas disponibles (de esta obra, no grúa, no colocadas)
            $maquinasColocadasIds = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->pluck('maquina_id')
                ->unique()
                ->values()
                ->all();

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

        // 7) (Opcional) payload ligero “machines” por si lo sigues usando en JS
        $machines = $localizacionesConMaquina->map(function ($loc) {
            return [
                'id'    => $loc['id'],
                'mx1'   => (float) $loc['x1'],
                'my1'   => (float) $loc['y1'],
                'mx2'   => (float) $loc['x2'],
                'my2'   => (float) $loc['y2'],
                'code'  => $loc['nombre'],
                'label' => $loc['nombre'],
            ];
        })->toArray();

        // 8) Sectores (cada 20 m)
        $sectorSize      = 20;
        $numeroSectores  = max(1, (int) ceil($largoM / $sectorSize));

        // 9) Contexto JS para la vista
        $ctx = [
            'naveId'            => $obraActiva?->id,
            'estaGirado'        => $estaGirado,               // true = vertical
            'orientacion'       => $orientacion,              // 'vertical' | 'horizontal'
            'columnasReales'    => $columnasReales,
            'filasReales'       => $filasReales,
            'columnasVista'     => $columnasVista,
            'filasVista'        => $filasVista,
            'ocupadas'          => $ocupadas,                 // ⬅️ ahora incluye zonas (salvo transitables)
            'storeUrl'          => route('localizaciones.store'),
            'deleteUrlTemplate' => url('/localizaciones/:id'), // se reemplaza :id en el JS
        ];

        // 10) Dimensiones para cabecera
        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obraActiva?->obra,
        ];

        // 11) LOG útil
        Log::debug('localizaciones.index payload', [
            'obra_id'         => $obraActiva?->id,
            'orientacion'     => $orientacion,
            'grid_real'       => "{$columnasReales}x{$filasReales}",
            'grid_vista'      => "{$columnasVista}x{$filasVista}",
            'loc_maquinas'    => $localizacionesConMaquina->count(),
            'loc_zonas'       => $localizacionesZonas->count(),
            'ocupadas_count'  => count($ocupadas),
            'maqs_disp'       => $maquinasDisponibles->count(),
        ]);

        return view('localizaciones.index', [
            'obras'                    => $obras,
            'obraActualId'             => $obraActualId,
            'cliente'                  => $cliente,
            'dimensiones'              => $dimensiones,
            'numeroSectores'           => $numeroSectores,
            'columnasVista'            => $columnasVista,
            'filasVista'               => $filasVista,

            // 👇 PARA PINTAR TODAS LAS LOCALIZACIONES EN LA CUADRÍCULA
            'localizacionesConMaquina' => $localizacionesConMaquina, // overlays de máquinas
            'localizacionesZonas'      => $localizacionesZonas,      // overlays de zonas

            // Bandeja de máquinas sin colocar
            'maquinasDisponibles'      => $maquinasDisponibles,

            // Contexto JS
            'machines'                 => $machines,
            'ctx'                      => $ctx,
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
        Log::info("✅ Entró al método update() con ID: {$id}");

        // Normalizaciones similares a store()
        if ($request->filled('tipo')) {
            $request->merge([
                'tipo' => str_replace('-', '_', $request->input('tipo'))
            ]);
        }
        if ($request->has('nombre')) {
            $request->merge([
                'nombre' => trim((string) $request->input('nombre'))
            ]);
        }

        $isJson = $request->expectsJson() || $request->ajax() || $request->isJson();

        try {
            /** @var \App\Models\Localizacion $localizacion */
            $localizacion = Localizacion::findOrFail($id);

            // --- Determinar nave y tipo finales (request o actuales) ---
            $naveIdFinal = $request->filled('nave_id') ? (int) $request->input('nave_id') : (int) $localizacion->nave_id;
            $tipoFinal   = $request->filled('tipo') ? (string) $request->input('tipo') : (string) $localizacion->tipo;

            // --- Reglas de validación (alineadas con store) ---
            $rules = [
                'x1'      => 'required|integer|min:1',
                'y1'      => 'required|integer|min:1',
                'x2'      => 'required|integer|min:1',
                'y2'      => 'required|integer|min:1',
                'nave_id' => 'nullable|integer|exists:obras,id',
                'tipo'    => 'nullable|in:maquina,transitable,almacenamiento,carga_descarga',
                'nombre'  => 'sometimes|string|max:100',
            ];

            // maquina_id obligatorio si (tipoFinal == 'maquina')
            if ($tipoFinal === 'maquina') {
                $rules['maquina_id'] = 'sometimes|nullable|integer|exists:maquinas,id';
            } else {
                $rules['maquina_id'] = 'nullable|integer|exists:maquinas,id';
            }


            $messages = [
                'x1.required'     => 'La coordenada x1 es obligatoria.',
                'y1.required'     => 'La coordenada y1 es obligatoria.',
                'x2.required'     => 'La coordenada x2 es obligatoria.',
                'y2.required'     => 'La coordenada y2 es obligatoria.',
                'tipo.in'         => 'Tipo inválido.',
                'nave_id.exists'  => 'La nave indicada no existe.',
                'maquina_id.required' => 'Debes seleccionar la máquina.',
                'maquina_id.exists'   => 'La máquina indicada no existe.',
                'nombre.max'      => 'El nombre no puede superar 100 caracteres.',
            ];

            $v = \Validator::make($request->all(), $rules, $messages);
            if ($v->fails()) {
                return $isJson
                    ? response()->json([
                        'success' => false,
                        'message' => 'Errores de validación.',
                        'errors'  => $v->errors()
                    ], 422)
                    : back()->withErrors($v)->withInput();
            }
            $data = $v->validated();

            // --- Normalizar coordenadas ---
            $x1 = min((int)$request->x1, (int)$request->x2);
            $x2 = max((int)$request->x1, (int)$request->x2);
            $y1 = min((int)$request->y1, (int)$request->y2);
            $y2 = max((int)$request->y1, (int)$request->y2);

            // --- Comprobación de solape ---
            // Regla: bloqueamos solape contra NO-transitables; ignoramos la propia localización.
            // Si la que movemos es transitable, puede solapar transitables, pero nunca no-transitables.
            $solapa = Localizacion::where('nave_id', $naveIdFinal)
                ->where('id', '!=', $localizacion->id)
                ->where('tipo', '!=', 'transitable') // solo colisiona con no-transitables
                ->where(function ($q) use ($x1, $y1, $x2, $y2) {
                    $q->where('x1', '<=', $x2)->where('x2', '>=', $x1)
                        ->where('y1', '<=', $y2)->where('y2', '>=', $y1);
                })
                ->exists();

            if ($solapa && $tipoFinal !== 'transitable') {
                // si YO no soy transitable, no puedo pisar un no-transitable
                return $isJson
                    ? response()->json([
                        'success' => false,
                        'message' => 'La nueva posición solapa con otra zona no transitable en esta nave.'
                    ], 409)
                    : back()->withErrors(['solape' => 'La nueva posición solapa con otra zona no transitable en esta nave.'])->withInput();
            }
            if ($solapa && $tipoFinal === 'transitable') {
                // por coherencia con store() no dejamos pisar no-transitables
                return $isJson
                    ? response()->json([
                        'success' => false,
                        'message' => 'No puedes solapar zonas no transitables.'
                    ], 409)
                    : back()->withErrors(['solape' => 'No puedes solapar zonas no transitables.'])->withInput();
            }

            // --- Preparar payload de actualización ---
            $payload = [
                'x1' => $x1,
                'y1' => $y1,
                'x2' => $x2,
                'y2' => $y2,
            ];

            // Si vienen cambios de nave/tipo/nombre, aplicar
            if ($request->filled('nave_id')) {
                $payload['nave_id'] = $naveIdFinal;
            }
            if ($request->filled('tipo')) {
                $payload['tipo'] = $tipoFinal;
            }
            if ($request->has('nombre')) {
                // permitir cadena vacía => normalízalo si prefieres
                $payload['nombre'] = (string) ($data['nombre'] ?? $request->input('nombre', ''));
            }

            // maquina_id: obligatorio si tipoFinal=maquina, sino nulificar
            if ($tipoFinal === 'maquina') {
                $payload['maquina_id'] = (int) ($data['maquina_id'] ?? $localizacion->maquina_id);
            } else {
                $payload['maquina_id'] = null;
            }

            // --- Guardar ---
            $localizacion->update($payload);

            return response()->json([
                'success'      => true,
                'message'      => 'Localización actualizada correctamente.',
                'id'           => $localizacion->id,
                'nombre'       => $localizacion->nombre,
                'localizacion' => $localizacion
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('❌ Localización no encontrada', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'La localización no existe.'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('💥 Error al actualizar la localización', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la localización.',
                // 'debug' => config('app.debug') ? $e->getMessage() : null, // opcional
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
            'ocupadas'       => $ocupadas,
            'storeUrl'       => route('localizaciones.store'),

            // 👇 Muy importante: usar url() con placeholder textual
            'updateUrlTemplate' => url('/localizaciones/:id'),
            'deleteUrlTemplate' => url('/localizaciones/:id'),
        ];

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
        // Normaliza tipo "carga-descarga" -> "carga_descarga"
        if ($request->filled('tipo')) {
            $request->merge([
                'tipo' => str_replace('-', '_', $request->input('tipo'))
            ]);
        }

        // Normaliza nombre (trim)
        if ($request->has('nombre')) {
            $request->merge([
                'nombre' => trim((string) $request->input('nombre'))
            ]);
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
            'nombre'  => 'required|string|max:100', // <-- ahora obligatorio y guardado
        ];

        // maquina_id obligatorio solo si tipo=maquina
        if ($request->input('tipo') === 'maquina') {
            $rules['maquina_id'] = 'required|integer|exists:maquinas,id';
        } else {
            $rules['maquina_id'] = 'nullable|integer|exists:maquinas,id';
        }

        $messages = [
            'tipo.required'        => 'Debes seleccionar el tipo de zona.',
            'tipo.in'              => 'Tipo inválido.',
            'nave_id.required'     => 'Debe indicar la nave.',
            'nave_id.exists'       => 'La nave indicada no existe.',
            'maquina_id.required'  => 'Debes seleccionar la máquina.',
            'maquina_id.exists'    => 'La máquina indicada no existe.',
            'nombre.required'      => 'Debes indicar un nombre.',
            'nombre.max'           => 'El nombre no puede superar 100 caracteres.',
        ];

        $v = Validator::make($request->all(), $rules, $messages);
        if ($v->fails()) {
            return $isJson
                ? response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors'  => $v->errors()
                ], 422)
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
            })
            ->exists();

        if ($solapa) {
            return $isJson
                ? response()->json([
                    'success' => false,
                    'message' => 'Ya existe una zona que solapa en esta nave.'
                ], 409)
                : back()->withErrors(['solape' => 'Ya existe una zona que solapa en esta nave.'])->withInput();
        }

        try {
            $localizacion = Localizacion::create([
                'x1'         => $x1,
                'y1'         => $y1,
                'x2'         => $x2,
                'y2'         => $y2,
                'tipo'       => $data['tipo'],
                'nombre'     => $data['nombre'], // <-- guardamos el nombre
                'maquina_id' => $data['tipo'] === 'maquina' ? ($data['maquina_id'] ?? null) : null,
                'nave_id'    => $data['nave_id'],
            ]);

            return $isJson
                ? response()->json([
                    'success'      => true,
                    'message'      => 'Zona guardada.',
                    'id'           => $localizacion->id,
                    'nombre'       => $localizacion->nombre, // <-- devolvemos nombre para pintarlo
                    'localizacion' => $localizacion
                ], 201)
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

    //------------------------------------------------------------------------------------ MAPA LOCALIZACIONES()
    /**
     * Vista del mapa de localizaciones mostrando los PAQUETES ubicados
     * Similar a index() pero enfocado en visualizar paquetes en lugar de gestionar localizaciones
     */
    public function mapaLocalizaciones()
    {
        // 1) Obtener todas las obras del cliente "Hierros Paco Reyes"
        $obras = Obra::with('cliente')
            ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
            ->orderBy('obra')
            ->get();

        // 2) Determinar la obra activa (desde parámetro ?obra=ID o la primera disponible)
        $obraActualId = request('obra');
        $obraActiva   = $obras->firstWhere('id', $obraActualId) ?? $obras->first();
        $cliente      = $obraActiva?->cliente;

        // 3) Dimensiones de la nave en metros (convertidas a celdas de 0.5m)
        $anchoM = max(1, (int) ($obraActiva->ancho_m ?? 22));
        $largoM = max(1, (int) ($obraActiva->largo_m ?? 115));
        $columnasReales = $anchoM * 2; // cada celda = 0.5m
        $filasReales    = $largoM * 2;

        // 4) Sin rotación: el mapa siempre crece de ABAJO hacia ARRIBA
        $estaGirado = false; // ⬅️ CRÍTICO: desactivamos rotación
        $columnasVista = $columnasReales; // ancho
        $filasVista    = $filasReales;    // largo (vertical)

        // 5) Inicializar colecciones
        $localizacionesMaquinas = collect();
        $localizacionesZonas    = collect();
        $paquetesConLocalizacion = collect();
        $ocupadas = [];

        if ($obraActiva) {
            // 5.1) Cargar TODAS las localizaciones de la nave (máquinas y zonas)
            $localizaciones = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $obraActiva->id)
                ->get();

            // 5.2) Separar localizaciones de MÁQUINAS
            $localizacionesMaquinas = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->filter(fn($l) => $l->maquina)
                ->values()
                ->map(function ($l) {
                    return [
                        'id'         => (int) $l->id,
                        'x1'         => (int) $l->x1,
                        'y1'         => (int) $l->y1,
                        'x2'         => (int) $l->x2,
                        'y2'         => (int) $l->y2,
                        'tipo'       => 'maquina',
                        'maquina_id' => (int) $l->maquina_id,
                        'nombre'     => (string) ($l->nombre ?: $l->maquina->nombre),
                        'nave_id'    => (int) $l->nave_id,
                    ];
                });

            // 5.3) Separar localizaciones de ZONAS (transitable, almacenamiento, carga_descarga)
            $localizacionesZonas = $localizaciones
                ->filter(fn($l) => $l->tipo !== 'maquina')
                ->values()
                ->map(function ($l) {
                    $tipoNorm = str_replace('-', '_', (string) $l->tipo);
                    return [
                        'id'      => (int) $l->id,
                        'x1'      => (int) $l->x1,
                        'y1'      => (int) $l->y1,
                        'x2'      => (int) $l->x2,
                        'y2'      => (int) $l->y2,
                        'tipo'    => $tipoNorm,
                        'nombre'  => (string) ($l->nombre ?: strtoupper(str_replace('_', ' ', $tipoNorm))),
                        'nave_id' => (int) $l->nave_id,
                    ];
                });

            // 5.4) Coordenadas ocupadas (para colisiones, excluye transitables)
            $ocupadas = $localizaciones
                ->filter(fn($l) => str_replace('-', '_', $l->tipo) !== 'transitable')
                ->map(fn($l) => [
                    'x1' => (int) $l->x1,
                    'y1' => (int) $l->y1,
                    'x2' => (int) $l->x2,
                    'y2' => (int) $l->y2,
                ])->values()->all();

            // 5.5) ⭐ OBTENER PAQUETES CON LOCALIZACIÓN EN ESTA NAVE
            $paquetesConLocalizacion = Paquete::with([
                'localizacionPaquete', // coordenadas x1,y1,x2,y2
                'etiquetas.elementos', // para calcular tipo de contenido
                'planilla.obra'        // info adicional: planilla y su obra
            ])
            ->where('nave_id', $obraActiva->id)
            ->whereHas('localizacionPaquete') // solo paquetes con localización
            ->get()
            ->map(function ($paquete) {
                $loc = $paquete->localizacionPaquete;

                return [
                    'id'                => (int) $paquete->id,
                    'codigo'            => (string) $paquete->codigo,
                    'peso'              => (float) $paquete->peso,
                    'x1'                => (int) $loc->x1,
                    'y1'                => (int) $loc->y1,
                    'x2'                => (int) $loc->x2,
                    'y2'                => (int) $loc->y2,
                    'tipo_contenido'    => $paquete->getTipoContenido(), // 'barras', 'estribos', 'mixto'
                    'cantidad_etiquetas' => $paquete->etiquetas->count(),
                    'cantidad_elementos' => $paquete->etiquetas->sum(fn($e) => $e->elementos->count()),
                    'planilla'          => $paquete->planilla?->codigo,
                    'obra'              => $paquete->planilla?->obra?->obra ?? '-',
                ];
            });
        }

        // 6) Información para la cabecera
        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obraActiva?->obra,
        ];

        // 7) Sectores verticales cada 20m
        $sectorSize = 20;
        $numeroSectores = max(1, (int) ceil($largoM / $sectorSize));

        // 8) Contexto para JavaScript
        $ctx = [
            'naveId'         => $obraActiva?->id,
            'estaGirado'     => false, // ⬅️ siempre false = crecimiento vertical
            'columnasReales' => $columnasReales,
            'filasReales'    => $filasReales,
            'columnasVista'  => $columnasVista,
            'filasVista'     => $filasVista,
            'ocupadas'       => $ocupadas,
        ];

        // 9) Log de debug
        Log::debug('mapaPaquetes payload', [
            'obra_id'        => $obraActiva?->id,
            'grid_real'      => "{$columnasReales}x{$filasReales}",
            'loc_maquinas'   => $localizacionesMaquinas->count(),
            'loc_zonas'      => $localizacionesZonas->count(),
            'paquetes_count' => $paquetesConLocalizacion->count(),
        ]);

        // 10) Retornar vista
        return view('mapa_paquetes.mapaLocalizaciones', [
            'obras'                     => $obras,
            'obraActualId'              => $obraActualId,
            'cliente'                   => $cliente,
            'dimensiones'               => $dimensiones,
            'numeroSectores'            => $numeroSectores,
            'columnasVista'             => $columnasVista,
            'filasVista'                => $filasVista,
            'localizacionesMaquinas'    => $localizacionesMaquinas,
            'localizacionesZonas'       => $localizacionesZonas,
            'paquetesConLocalizacion'   => $paquetesConLocalizacion,
            'ctx'                       => $ctx,
        ]);
    }

    //------------------------------------------------------------------------------------ UPDATE PAQUETE POSICION()
    /**
     * Actualiza la posición de un paquete en el mapa (arrastrar y soltar)
     */
    public function updatePaquetePosicion(Request $request, $paqueteId)
    {
        try {
            // Validar los datos recibidos
            $validated = $request->validate([
                'x1' => 'required|integer|min:1',
                'y1' => 'required|integer|min:1',
                'x2' => 'required|integer|min:1',
                'y2' => 'required|integer|min:1',
            ]);

            // Buscar la localización del paquete
            $localizacionPaquete = LocalizacionPaquete::where('paquete_id', $paqueteId)->firstOrFail();

            // Actualizar las coordenadas
            $localizacionPaquete->update([
                'x1' => $validated['x1'],
                'y1' => $validated['y1'],
                'x2' => $validated['x2'],
                'y2' => $validated['y2'],
            ]);

            Log::info("✅ Paquete {$paqueteId} movido a ({$validated['x1']},{$validated['y1']}) - ({$validated['x2']},{$validated['y2']})");

            return response()->json([
                'success' => true,
                'message' => 'Posición del paquete actualizada correctamente',
                'localizacion' => $localizacionPaquete
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error("❌ Error actualizando posición del paquete {$paqueteId}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la posición del paquete',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de naves (obras de HPR) para el selector
     */
    public function getNavesApi()
    {
        try {
            $obras = Obra::with('cliente')
                ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
                ->orderBy('obra')
                ->get()
                ->map(function ($obra) {
                    return [
                        'id' => $obra->id,
                        'obra' => $obra->obra,
                        'nombre' => $obra->obra,
                        'ancho_m' => $obra->ancho_m ?? 22,
                        'largo_m' => $obra->largo_m ?? 115,
                    ];
                });

            return response()->json($obras);
        } catch (\Exception $e) {
            Log::error("Error al obtener naves: " . $e->getMessage());
            return response()->json(['error' => 'Error al cargar naves'], 500);
        }
    }

    /**
     * Obtener datos del mapa de una nave específica para renderizar en el modal
     */
    public function getMapaDataApi($naveId)
    {
        try {
            $obra = Obra::findOrFail($naveId);

            // Dimensiones de la nave
            $anchoM = max(1, (int) ($obra->ancho_m ?? 22));
            $largoM = max(1, (int) ($obra->largo_m ?? 115));
            $anchoCeldas = $anchoM * 2; // 0.5m por celda
            $largoCeldas = $largoM * 2;

            // Localizaciones existentes (máquinas)
            $localizaciones = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $naveId)
                ->get();

            $maquinas = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->filter(fn($l) => $l->maquina)
                ->values()
                ->map(function ($l) {
                    return [
                        'id' => (int) $l->id,
                        'x1' => (int) $l->x1,
                        'y1' => (int) $l->y1,
                        'x2' => (int) $l->x2,
                        'y2' => (int) $l->y2,
                        'nombre' => (string) ($l->nombre ?: $l->maquina->nombre),
                    ];
                });

            // Paquetes existentes en la nave (a través del modelo Paquete que tiene nave_id)
            $paquetes = Paquete::with('localizacionPaquete')
                ->where('nave_id', $naveId)
                ->whereHas('localizacionPaquete')
                ->get()
                ->map(function ($paquete) {
                    $lp = $paquete->localizacionPaquete;
                    return [
                        'id' => (int) $lp->id,
                        'paquete_id' => (int) $paquete->id,
                        'codigo' => $paquete->codigo,
                        'x1' => (int) $lp->x1,
                        'y1' => (int) $lp->y1,
                        'x2' => (int) $lp->x2,
                        'y2' => (int) $lp->y2,
                    ];
                });

            return response()->json([
                'nave_id' => $naveId,
                'obra' => $obra->obra,
                'dimensiones' => [
                    'ancho_m' => $anchoM,
                    'largo_m' => $largoM,
                    'ancho_celdas' => $anchoCeldas,
                    'largo_celdas' => $largoCeldas,
                ],
                'maquinas' => $maquinas,
                'paquetes' => $paquetes,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener datos del mapa para nave {$naveId}: " . $e->getMessage());
            return response()->json(['error' => 'Error al cargar datos del mapa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Renderizar el componente de mapa para carga dinámica (AJAX)
     */
    public function renderMapaComponente($naveId)
    {
        try {
            $obra = Obra::findOrFail($naveId);

            // Dimensiones
            $anchoM = max(1, (int) ($obra->ancho_m ?? 22));
            $largoM = max(1, (int) ($obra->largo_m ?? 115));
            $columnasReales = $anchoM * 2;
            $filasReales = $largoM * 2;

            // Contexto para el componente
            $ctx = [
                'columnasReales' => $columnasReales,
                'filasReales' => $filasReales,
                'estaGirado' => true, // vertical por defecto
                'naveId' => $naveId,
            ];

            // Localizaciones de máquinas
            $localizaciones = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $naveId)
                ->get();

            $localizacionesMaquinas = $localizaciones
                ->where('tipo', 'maquina')
                ->whereNotNull('maquina_id')
                ->filter(fn($l) => $l->maquina)
                ->values()
                ->map(function ($l) {
                    return [
                        'id' => (int) $l->id,
                        'x1' => (int) $l->x1,
                        'y1' => (int) $l->y1,
                        'x2' => (int) $l->x2,
                        'y2' => (int) $l->y2,
                        'maquina_id' => (int) $l->maquina_id,
                        'nombre' => (string) ($l->nombre ?: $l->maquina->nombre),
                    ];
                })->toArray();

            // Zonas
            $localizacionesZonas = $localizaciones
                ->filter(fn($l) => $l->tipo !== 'maquina')
                ->values()
                ->map(function ($l) {
                    return [
                        'id' => (int) $l->id,
                        'x1' => (int) $l->x1,
                        'y1' => (int) $l->y1,
                        'x2' => (int) $l->x2,
                        'y2' => (int) $l->y2,
                        'tipo' => $l->tipo ?? 'transitable',
                        'nombre' => (string) $l->nombre,
                    ];
                })->toArray();

            // Paquetes existentes
            $paquetesConLocalizacion = Paquete::with(['localizacionPaquete', 'etiquetas.elementos'])
                ->where('nave_id', $naveId)
                ->whereHas('localizacionPaquete')
                ->get()
                ->map(function ($paquete) {
                    $loc = $paquete->localizacionPaquete;
                    return [
                        'id' => (int) $paquete->id,
                        'codigo' => (string) $paquete->codigo,
                        'x1' => (int) $loc->x1,
                        'y1' => (int) $loc->y1,
                        'x2' => (int) $loc->x2,
                        'y2' => (int) $loc->y2,
                        'tipo_contenido' => $paquete->getTipoContenido(),
                        'orientacion' => 'I',
                    ];
                })->toArray();

            $dimensiones = [
                'ancho' => $anchoM,
                'largo' => $largoM,
                'obra' => $obra->obra,
            ];

            // Renderizar el componente
            return view('partials.mapa-component-ajax', [
                'ctx' => $ctx,
                'localizacionesZonas' => $localizacionesZonas,
                'localizacionesMaquinas' => $localizacionesMaquinas,
                'paquetesConLocalizacion' => $paquetesConLocalizacion,
                'dimensiones' => $dimensiones,
                'obraActualId' => $naveId,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al renderizar componente de mapa: " . $e->getMessage());
            return response('<div class="text-red-500 p-4">Error al cargar el mapa: ' . $e->getMessage() . '</div>', 500);
        }
    }

    /**
     * Guardar nueva ubicación de un paquete (crear o actualizar)
     */
    public function guardarLocalizacionPaquete(Request $request)
    {
        try {
            $validated = $request->validate([
                'nave_id' => 'required|integer|exists:obras,id',
                'paquete_id' => 'required|integer|exists:paquetes,id',
                'x1' => 'required|integer|min:1',
                'y1' => 'required|integer|min:1',
                'x2' => 'required|integer|min:1',
                'y2' => 'required|integer|min:1',
            ]);

            // Actualizar el nave_id en el paquete
            $paquete = Paquete::findOrFail($validated['paquete_id']);
            $paquete->update(['nave_id' => $validated['nave_id']]);

            // Verificar si el paquete ya tiene una ubicación
            $localizacionExistente = LocalizacionPaquete::where('paquete_id', $validated['paquete_id'])->first();

            if ($localizacionExistente) {
                // Actualizar ubicación existente
                $localizacionExistente->update([
                    'x1' => $validated['x1'],
                    'y1' => $validated['y1'],
                    'x2' => $validated['x2'],
                    'y2' => $validated['y2'],
                ]);

                Log::info("✅ Ubicación del paquete {$validated['paquete_id']} actualizada en nave {$validated['nave_id']}");

                return response()->json([
                    'success' => true,
                    'message' => 'Ubicación del paquete actualizada correctamente',
                    'localizacion' => $localizacionExistente
                ]);
            } else {
                // Crear nueva ubicación
                $nuevaLocalizacion = LocalizacionPaquete::create([
                    'paquete_id' => $validated['paquete_id'],
                    'x1' => $validated['x1'],
                    'y1' => $validated['y1'],
                    'x2' => $validated['x2'],
                    'y2' => $validated['y2'],
                ]);

                Log::info("✅ Nueva ubicación creada para paquete {$validated['paquete_id']} en nave {$validated['nave_id']}");

                return response()->json([
                    'success' => true,
                    'message' => 'Paquete ubicado correctamente en el mapa',
                    'localizacion' => $nuevaLocalizacion
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("❌ Error guardando ubicación de paquete: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la ubicación del paquete',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}