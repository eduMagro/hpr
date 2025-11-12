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
     * Vista principal del mapa de localizaciones de paquetes
     * Muestra un mapa interactivo con todos los paquetes y máquinas ubicados en la nave
     * 
     * @return \Illuminate\View\View
     */
    public function mapaLocalizaciones()
    {
        // 1) Obtener todas las obras del cliente "Hierros Paco Reyes"
        $obras = Obra::with('cliente')
            ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
            ->orderBy('obra')
            ->get();

        // 2) Determinar la obra activa (pasada por parámetro ?obra=ID o la primera disponible)
        $obraActualId = request('obra');
        $obraActiva   = $obras->firstWhere('id', $obraActualId) ?? $obras->first();
        $cliente      = $obraActiva?->cliente;

        // 3) Obtener dimensiones de la nave en metros
        // Estas dimensiones definen el tamaño real del espacio físico
        $anchoM = max(1, (int) ($obraActiva->ancho_m ?? 22));
        $largoM = max(1, (int) ($obraActiva->largo_m ?? 115));

        // 4) Calcular el grid en celdas (cada celda = 0.5m)
        // Esto permite una precisión de medio metro para colocar elementos
        $columnasReales = $anchoM * 2; // Multiplicamos por 2 porque cada metro = 2 celdas
        $filasReales    = $largoM * 2;

        // 5) Determinar orientación del mapa (vertical u horizontal)
        // El mapa se mostrará siempre con el lado más largo en horizontal para mejor visualización
        $estaGirado     = $filasReales > $columnasReales; // true si el largo > ancho
        $columnasVista  = $estaGirado ? $filasReales : $columnasReales;
        $filasVista     = $estaGirado ? $columnasReales : $filasReales;

        // 6) Cargar todas las localizaciones de máquinas en la nave
        $localizacionesMaquinas = collect();
        $localizacionesZonas = collect();
        
        if ($obraActiva) {
            // Obtener todas las localizaciones de la nave (máquinas y zonas)
            $localizaciones = Localizacion::with('maquina:id,nombre')
                ->where('nave_id', $obraActiva->id)
                ->get();

            // Separar máquinas de zonas
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

            // Zonas (transitable, almacenamiento, carga_descarga)
            $localizacionesZonas = $localizaciones
                ->filter(fn($l) => $l->tipo !== 'maquina')
                ->values()
                ->map(function ($l) {
                    return [
                        'id'      => (int) $l->id,
                        'x1'      => (int) $l->x1,
                        'y1'      => (int) $l->y1,
                        'x2'      => (int) $l->x2,
                        'y2'      => (int) $l->y2,
                        'tipo'    => (string) str_replace('-', '_', $l->tipo),
                        'nombre'  => (string) ($l->nombre ?: strtoupper(str_replace('_', '/', $l->tipo))),
                        'nave_id' => (int) $l->nave_id,
                    ];
                });
        }

        // 7) Cargar todos los paquetes con sus localizaciones
        $paquetesConLocalizacion = $this->obtenerPaquetesConLocalizacion($obraActiva);

        // 8) Preparar información de dimensiones para la cabecera
        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obraActiva?->obra,
        ];

        // 9) Contexto JavaScript para la vista
        // Este objeto se pasará al frontend para manejar la interactividad del mapa
        $ctx = [
            'naveId'         => $obraActiva?->id,
            'estaGirado'     => $estaGirado,
            'columnasReales' => $columnasReales,
            'filasReales'    => $filasReales,
            'columnasVista'  => $columnasVista,
            'filasVista'     => $filasVista,
            'anchoM'         => $anchoM,
            'largoM'         => $largoM,
        ];

        // 10) Log para debugging
        Log::debug('Mapa Localizaciones - Datos cargados', [
            'obra_id'        => $obraActiva?->id,
            'grid_real'      => "{$columnasReales}x{$filasReales}",
            'grid_vista'     => "{$columnasVista}x{$filasVista}",
            'paquetes_count' => $paquetesConLocalizacion->count(),
            'maquinas_count' => $localizacionesMaquinas->count(),
        ]);

        // Retornar la vista con todos los datos necesarios
        return view('localizaciones.mapaLocalizaciones', [
            'obras'                  => $obras,
            'obraActualId'           => $obraActualId,
            'obraActiva'             => $obraActiva,
            'cliente'                => $cliente,
            'dimensiones'            => $dimensiones,
            'columnasVista'          => $columnasVista,
            'filasVista'             => $filasVista,
            'localizacionesMaquinas' => $localizacionesMaquinas,
            'localizacionesZonas'    => $localizacionesZonas,
            'paquetesConLocalizacion'=> $paquetesConLocalizacion,
            'ctx'                    => $ctx,
        ]);
    }

    //------------------------------------------------------------------------------------ OBTENER PAQUETES CON LOCALIZACION()
    /**
     * Obtiene todos los paquetes que tienen localización asignada
     * y calcula sus dimensiones basándose en los elementos que contienen
     * 
     * @param Obra|null $obra La obra activa
     * @return \Illuminate\Support\Collection
     */
    private function obtenerPaquetesConLocalizacion($obra)
    {
        if (!$obra) {
            return collect();
        }

        // Obtener todos los paquetes de la nave que tienen localización
        $paquetes = Paquete::with([
            'localizacionPaquete', // Relación con la ubicación en el mapa
            'etiquetas.elementos'  // Etiquetas y sus elementos para calcular dimensiones
        ])
        ->where('nave_id', $obra->id)
        ->whereHas('localizacionPaquete') // Solo paquetes con localización asignada
        ->get();

        return $paquetes->map(function ($paquete) {
            $loc = $paquete->localizacionPaquete;
            
            // Calcular dimensiones del paquete basándose en sus elementos
            $dimensiones = $this->calcularDimensionesPaquete($paquete);

            return [
                'id'              => (int) $paquete->id,
                'codigo'          => (string) $paquete->codigo,
                'peso'            => (float) $paquete->peso,
                'x1'              => (int) $loc->x1,
                'y1'              => (int) $loc->y1,
                'x2'              => (int) $loc->x2,
                'y2'              => (int) $loc->y2,
                'ancho_celdas'    => (int) ($loc->x2 - $loc->x1 + 1),
                'alto_celdas'     => (int) ($loc->y2 - $loc->y1 + 1),
                'tipo_contenido'  => $dimensiones['tipo'], // 'barras', 'estribos' o 'mixto'
                'longitud_maxima' => $dimensiones['longitud_maxima'], // Para orientación
                'cantidad_elementos' => $dimensiones['cantidad'],
            ];
        })->values();
    }

    //------------------------------------------------------------------------------------ CALCULAR DIMENSIONES PAQUETE()
    /**
     * Calcula las dimensiones que debe tener un paquete en el mapa
     * basándose en los elementos (barras/estribos) que contiene
     * 
     * @param Paquete $paquete
     * @return array ['tipo' => string, 'longitud_maxima' => float, 'cantidad' => int]
     */
    private function calcularDimensionesPaquete($paquete)
    {
        $tieneBarras = false;
        $tieneEstribos = false;
        $longitudMaxima = 0;
        $cantidadTotal = 0;

        // Recorrer todas las etiquetas del paquete
        foreach ($paquete->etiquetas as $etiqueta) {
            foreach ($etiqueta->elementos as $elemento) {
                $cantidadTotal++;

                // Detectar tipo de elemento por su figura o dimensiones
                // Los estribos suelen ser elementos más pequeños y con forma específica
                if ($this->esEstribo($elemento)) {
                    $tieneEstribos = true;
                } else {
                    $tieneBarras = true;
                    // Para barras, guardamos la longitud máxima para orientar el rectángulo
                    $longitudMaxima = max($longitudMaxima, (float) $elemento->longitud);
                }
            }
        }

        // Determinar tipo de contenido
        $tipo = 'mixto';
        if ($tieneBarras && !$tieneEstribos) {
            $tipo = 'barras';
        } elseif ($tieneEstribos && !$tieneBarras) {
            $tipo = 'estribos';
        }

        return [
            'tipo'             => $tipo,
            'longitud_maxima'  => $longitudMaxima,
            'cantidad'         => $cantidadTotal,
        ];
    }

    //------------------------------------------------------------------------------------ ES ESTRIBO()
    /**
     * Determina si un elemento es un estribo basándose en sus características
     * 
     * @param mixed $elemento
     * @return bool
     */
    private function esEstribo($elemento)
    {
        // Los estribos normalmente tienen una figura específica
        // o longitudes/diámetros menores que las barras
        if (isset($elemento->figura)) {
            $figura = strtolower($elemento->figura);
            // Añade aquí las figuras que identifiquen estribos en tu sistema
            return in_array($figura, ['estribo', 'cerco', 'u', 'l']);
        }

        // Alternativa: detectar por dimensiones (estribos suelen ser < 3m)
        if (isset($elemento->longitud)) {
            return (float) $elemento->longitud < 3.0;
        }

        return false;
    }

    //------------------------------------------------------------------------------------ GUARDAR LOCALIZACION PAQUETE()
    /**
     * Guarda o actualiza la localización de un paquete en el mapa
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardarLocalizacionPaquete(Request $request)
    {
        // Validar datos recibidos
        $validator = Validator::make($request->all(), [
            'paquete_id' => 'required|integer|exists:paquetes,id',
            'x1'         => 'required|integer|min:1',
            'y1'         => 'required|integer|min:1',
            'x2'         => 'required|integer|min:1',
            'y2'         => 'required|integer|min:1',
            'nave_id'    => 'required|integer|exists:obras,id',
        ], [
            'paquete_id.required' => 'Debe indicar el paquete.',
            'paquete_id.exists'   => 'El paquete indicado no existe.',
            'nave_id.required'    => 'Debe indicar la nave.',
            'nave_id.exists'      => 'La nave indicada no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Normalizar coordenadas (asegurar que x1 < x2 y y1 < y2)
            $x1 = min($request->x1, $request->x2);
            $x2 = max($request->x1, $request->x2);
            $y1 = min($request->y1, $request->y2);
            $y2 = max($request->y1, $request->y2);

            // Verificar si ya existe una localización para este paquete
            $localizacion = LocalizacionPaquete::where('paquete_id', $request->paquete_id)->first();

            if ($localizacion) {
                // Actualizar localización existente
                $localizacion->update([
                    'x1' => $x1,
                    'y1' => $y1,
                    'x2' => $x2,
                    'y2' => $y2,
                ]);
                $mensaje = 'Localización del paquete actualizada correctamente.';
            } else {
                // Crear nueva localización
                $localizacion = LocalizacionPaquete::create([
                    'paquete_id' => $request->paquete_id,
                    'x1'         => $x1,
                    'y1'         => $y1,
                    'x2'         => $x2,
                    'y2'         => $y2,
                ]);
                $mensaje = 'Localización del paquete guardada correctamente.';
            }

            return response()->json([
                'success'      => true,
                'message'      => $mensaje,
                'localizacion' => $localizacion,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error al guardar localización de paquete', [
                'error' => $e->getMessage(),
                'paquete_id' => $request->paquete_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la localización del paquete.',
            ], 500);
        }
    }
}