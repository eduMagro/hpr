<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Salida;
use App\Models\SalidaCliente;
use App\Exports\SalidasExport;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\EmpresaTransporte;
use App\Models\Camion;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Mail\SalidaCompletadaTrazabilidadEnviadaMailable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


use Illuminate\Support\Facades\Mail;

class SalidaFerrallaController extends Controller
{
    public function index(Request $request)
    {
        // Cargar relaciones según el rol del usuario
        if (auth()->user()->rol == 'oficina') {
            $salidas = Salida::with([
                'salidaClientes.cliente',
                'salidaClientes.obra',
                'paquetes.planilla.obra',
                'empresaTransporte',
                'camion'
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            $empresasTransporte = EmpresaTransporte::orderBy('nombre')->get();
            $camiones = Camion::orderBy('modelo')->get(); // Con empresa_transporte_id
            // Prepara el array plano con solo los datos necesarios
            $camionesJson = $camiones->map(function ($camion) {
                return [
                    'id'         => $camion->id,
                    'modelo'     => $camion->modelo,
                    'empresa_id' => $camion->empresaTransporte->id ?? null,
                ];
            });
        } else {
            $salidas = Salida::where('estado', 'pendiente')
                ->with([
                    'paquetes' => function ($query) {
                        $query->distinct();
                    },
                    'paquetes.etiquetas',
                    'empresaTransporte',
                    'camion',
                    // No cargamos 'clientes', sino la relación de salidaClientes
                    'salidaClientes.cliente',
                    'salidaClientes.obra',
                ])
                ->get();
        }

        // Extraer todos los paquetes de las salidas
        $paquetes = $salidas->pluck('paquetes')->flatten();

        // Agrupar las salidas por mes
        $salidasPorMes = $salidas->groupBy(function ($salida) {
            return \Carbon\Carbon::parse($salida->fecha_salida)->translatedFormat('F Y');
        });

        // Crear un resumen mensual (si sigue siendo necesario, puedes ajustarlo para que agrupe por empresa y obra si lo deseas)
        $resumenMensual = [];
        foreach ($salidasPorMes as $mes => $salidasGrupo) {
            $empresaSummary = [];
            foreach ($salidasGrupo as $salida) {
                $nombreEmpresa = trim($salida->empresaTransporte->nombre ?? "N/A") ?: "Empresa desconocida";
                $empresaId = $salida->empresaTransporte->id ?? null;
                if (!isset($empresaSummary[$nombreEmpresa])) {
                    $empresaSummary[$nombreEmpresa] = [
                        'empresa_id' => $empresaId,
                        'horas_paralizacion' => 0,
                        'importe_paralizacion' => 0,
                        'horas_grua' => 0,
                        'importe_grua' => 0,
                        'horas_almacen' => 0,
                        'importe' => 0,
                        'total' => 0,
                    ];
                }
                // Ahora se suma desde cada registro en salidaClientes
                foreach ($salida->salidaClientes as $registro) {
                    $empresaSummary[$nombreEmpresa]['horas_paralizacion'] += $registro->horas_paralizacion ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe_paralizacion'] += $registro->importe_paralizacion ?? 0;
                    $empresaSummary[$nombreEmpresa]['horas_grua'] += $registro->horas_grua ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe_grua'] += $registro->importe_grua ?? 0;
                    $empresaSummary[$nombreEmpresa]['horas_almacen'] += $registro->horas_almacen ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe'] += $registro->importe ?? 0;
                }
            }
            foreach ($empresaSummary as $nombreEmpresa => &$data) {
                $data['total'] = $data['importe_paralizacion'] + $data['importe_grua'] + $data['importe'];
            }
            $resumenMensual[$mes] = $empresaSummary;
        }

        return view('salidas.index', compact(
            'salidasPorMes',
            'salidas',
            'resumenMensual',
            'paquetes',
            'empresasTransporte',
            'camiones',
            'camionesJson'
        ));
    }

    public function show($id)
    {
        // Obtener la salida con su ID
        $salida = Salida::findOrFail($id);

        // Obtener los paquetes asociados con los elementos y la planilla (incluyendo cliente y obra)
        $paquetes = $salida->paquetes()->with([
            'etiquetas',
            'planilla.cliente',
            'planilla.obra'
        ])->get();

        // Agrupar los paquetes por combinación de cliente y obra
        $groupedPackages = $paquetes->groupBy(function ($paquete) {
            // Obtenemos el nombre del cliente y la obra, o asignamos un valor por defecto
            $clienteName = $paquete->planilla->cliente->empresa ?? 'Sin Cliente';
            $obraName = $paquete->planilla->obra->obra ?? 'Sin Obra';
            return $clienteName . '|' . $obraName;
        });

        // Opcional: formatear el agrupamiento para facilitar el uso en la vista
        $groupedPackagesFormatted = [];
        foreach ($groupedPackages as $key => $group) {
            list($clienteName, $obraName) = explode('|', $key);
            $groupedPackagesFormatted[] = [
                'cliente' => $clienteName,
                'obra' => $obraName,
                'paquetes' => $group,
            ];
        }

        // Pasar la salida, los paquetes y el agrupamiento formateado a la vista
        return view('salidas.show', compact('salida', 'paquetes', 'groupedPackagesFormatted'));
    }

    public function actualizarEstado(Request $request, $salidaId)
    {
        try {
            $salida = Salida::findOrFail($salidaId);

            // Verificamos que el estado actual sea pendiente antes de cambiarlo a completado
            if ($salida->estado != 'pendiente') {
                return response()->json(['message' => 'La salida ya estaba completada.'], 400);
            }
            // Asignamos el usuario autenticado (gruista)
            $salida->user_id = auth()->id();

            // Actualizamos el estado
            $salida->estado = 'completada';
            $salida->fecha_salida = now();
            $salida->save();


            return response()->json([
                'message' => 'Salida completada con éxito.'
            ]);
        } catch (\Exception $e) {
            // Capturamos cualquier error y retornamos un mensaje
            return response()->json(['message' => 'Hubo un error al completar la salida. ' . $e->getMessage()], 500);
        }
    }

    public function completarDesdeMovimiento($movimientoId)
    {
        try {
            $movimiento = Movimiento::findOrFail($movimientoId);

            if ($movimiento->tipo !== 'salida') {
                return response()->json([
                    'success' => false,
                    'message' => 'El movimiento no es de tipo salida.'
                ], 422);
            }



            $salida = Salida::with([
                'clientes',
                'paquetes.etiquetas.planilla.obra',
                'paquetes.etiquetas.planilla.elementos', // <-- esta es la clave
                'paquetes.etiquetas.planilla.elementos.producto.productoBase',
                'paquetes.etiquetas.planilla.elementos.producto'
            ])->find($movimiento->salida_id);


            if (!$salida) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la salida asociada.'
                ], 404);
            }


            $this->generarYEnviarTrazabilidad($salida);
            $salida->estado = 'completada';
            $salida->save();
            $movimiento->update([
                'estado' => 'completado',
                'fecha_ejecucion' => now(),
                'ejecutado_por' => auth()->id()
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Movimiento y salida completados. Email enviado.'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error en completarDesdeMovimiento(): ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al completar la salida: ' . $e->getMessage()
            ], 500);
        }
    }
    private function generarYEnviarTrazabilidad(Salida $salida)
    {
        try {
            $etiquetas = $salida->paquetes->flatMap->etiquetas;
            $etiquetasPorObra = $etiquetas->groupBy(fn($etiqueta) => optional($etiqueta->planilla?->obra)->id);

            foreach ($salida->clientes as $cliente) {
                $clienteNombre = Str::slug($cliente->empresa ?? 'sin_cliente');

                foreach ($etiquetasPorObra as $obraId => $grupoEtiquetas) {
                    $obra = optional($grupoEtiquetas->first()?->planilla?->obra);
                    if (!$obra) continue;

                    $obraNombre = Str::slug($obra->obra ?? 'obra_desconocida');
                    $obraCodigo = $obra->cod_obra ?? 'sin_codigo';

                    $planillaIds = $grupoEtiquetas->pluck('planilla.id')->filter()->unique();

                    $planillas = Planilla::with('elementos.producto.productoBase', 'elementos.producto')
                        ->whereIn('id', $planillaIds)
                        ->get();


                    // 🔁 Ahora sí puedes recorrer sus elementos
                    $elementos = $planillas
                        ->flatMap(fn($planilla) => $planilla->elementos)
                        ->filter(fn($e) => $e->producto && $e->producto->productoBase)
                        ->values();

                    Log::debug('📦 Planillas cargadas', [
                        'ids' => $planillas->pluck('id'),
                        'elementos_totales' => $planillas->flatMap->elementos->count(),
                        'productos_null' => $planillas->flatMap->elementos->filter(fn($e) => is_null($e->producto))->count(),
                        'producto_base_null' => $planillas->flatMap->elementos->filter(fn($e) => optional($e->producto)->productoBase === null)->count(),
                    ]);

                    $datosPorDiametro = $elementos
                        ->groupBy(fn($e) => $e->producto->productoBase->diametro ?? 'N/A')
                        ->map(fn($grupo) => $grupo->groupBy(fn($e) => $e->producto->n_colada ?? 'Desconocida'));

                    Log::debug('🎯 DEBUG TRAZABILIDAD', [
                        'cliente_id' => $cliente->id,
                        'obra_id' => $obra->id ?? null,
                        'planillas' => $planillas->pluck('id'),
                        'elementos_count' => $elementos->count(),
                        'datosPorDiametro' => $datosPorDiametro->toArray(),
                    ]);

                    $año = now()->year;
                    $rutaRelativa = "private/trazabilidad_{$año}/{$clienteNombre}/{$obraNombre}";
                    $rutaCompleta = storage_path("app/{$rutaRelativa}");

                    // Crear carpeta si no existe
                    if (!File::exists($rutaCompleta)) {
                        File::makeDirectory($rutaCompleta, 0755, true, true);
                    }

                    $codigoLimpio = str_replace('/', '-', $salida->codigo_salida);
                    $nombreArchivo = "trazabilidad_salida_{$codigoLimpio}_obra_{$obraCodigo}.pdf";


                    // Generar y guardar PDF
                    $pdf = Pdf::loadView('pdfs.trazabilidad-pdf', [
                        'salida' => $salida,
                        'obra' => $obra,
                        'cliente' => $cliente,
                        'datosPorDiametro' => $datosPorDiametro,
                    ]);
                    $pdf->save("{$rutaCompleta}/{$nombreArchivo}");

                    // Enviar email con adjunto
                    Mail::send('emails.salidas.salida-completada-trazabilidad-enviada', [
                        'salida' => $salida,
                        'obra' => $obra,
                        'cliente' => $cliente,
                    ], function ($message) use ($salida, $rutaCompleta, $nombreArchivo) {
                        $message->to(['eduardo.magro@pacoreyes.com'])
                            ->subject('Salida completada')
                            ->attach("{$rutaCompleta}/{$nombreArchivo}");
                    });
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en generarYEnviarTrazabilidad(): ' . $e->getMessage(), [
                'salida_id' => $salida->id,
                'codigo_salida' => $salida->codigo_salida,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException("No se pudo generar o enviar la trazabilidad para la salida {$salida->codigo_salida}.");
        }
    }


    public function create(Request $request)
    {
        // Redirigir a la nueva vista de gestión de salidas
        $planillas = $request->get('planillas', '');

        if (empty($planillas)) {
            return redirect()->route('planificacion.index')
                ->with('info', 'Selecciona planillas desde el calendario para crear salidas');
        }

        return redirect()->route('salidas-ferralla.gestionar-salidas', ['planillas' => $planillas]);
    }

    public function store(Request $request)
    {
        // Log inicial para confirmar que el método se está ejecutando
        Log::info('🚀 Iniciando store de salida', [
            'request_all' => $request->all(),
            'user_id' => auth()->id() ?? 'guest',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        try {
            $request->validate([
                'camion_id' => 'required|exists:camiones,id',
                'paquete_ids' => 'required|array',
                'paquete_ids.*' => 'exists:paquetes,id',
            ], [
                'camion_id.required' => 'Por favor, seleccione un camión.',
                'camion_id.exists' => 'El camión seleccionado no existe en el sistema.',
                'paquete_ids.required' => 'Debe seleccionar al menos un paquete.',
                'paquete_ids.array' => 'Los paquetes seleccionados no son válidos.',
                'paquete_ids.*.exists' => 'Uno o más paquetes seleccionados no existen en el sistema.',
            ]);

            // Paquetes repetidos por ID
            $paquetesRepetidos = DB::table('salidas_paquetes')
                ->whereIn('paquete_id', $request->paquete_ids)
                ->whereNotNull('salida_id')
                ->pluck('paquete_id')
                ->toArray();

            $repetidos = array_intersect($request->paquete_ids, $paquetesRepetidos);

            if ($repetidos) {
                // 🔎 Buscar los paquetes para obtener código y planilla
                $paquetesInfo = Paquete::with('planilla')
                    ->whereIn('id', $repetidos)
                    ->get()
                    ->map(function ($paquete) {
                        $codigoPaquete = $paquete->codigo ?? 'Sin código';
                        $codigoPlanilla = $paquete->planilla ? ($paquete->planilla->codigo ?? $paquete->planilla->id) : 'Sin planilla';
                        return "{$codigoPaquete} (Planilla {$codigoPlanilla})";
                    })
                    ->toArray();

                $mensaje = 'Los siguientes paquetes ya están asociados a una salida: ' . implode(', ', $paquetesInfo);

                return back()->withErrors(['paquete_ids' => $mensaje]);
            }


            // Obtener el camión y la empresa de transporte asociada
            // $camion = Camion::find($request->camion_id);
            // $empresa = $camion->empresaTransporte;

            // Obtener la primera planilla de los paquetes seleccionados
            $primeraPlanilla = Paquete::with('planilla')
                ->whereIn('id', $request->paquete_ids)
                ->get()
                ->pluck('planilla')    // colección de planillas
                ->filter()             // quitamos nulos
                ->first();             // primera planilla válida

            // Si hay planilla válida, usamos su fecha_estimada_entrega
            $fechaSalida = $primeraPlanilla
                ? $primeraPlanilla->getRawOriginal('fecha_estimada_entrega')
                : now();

            // Crear la salida
            $salida = Salida::create([
                // 'empresa_id' => $empresa->id,
                // 'camion_id' => $request->camion_id,
                'fecha_salida' => $fechaSalida,
                'estado' => 'pendiente', // Estado por defecto
            ]);

            // Generar el código de salida y asignarlo
            $codigo_salida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);
            $salida->codigo_salida = $codigo_salida;
            $salida->save();

            // Asociar los paquetes a la salida (tabla salidas_paquetes)
            foreach ($request->paquete_ids as $paquete_id) {
                $salida->paquetes()->attach($paquete_id);
            }

            /*
             * Para la asociación en salida_cliente (ahora con obra_id):
             * Se recorre cada paquete seleccionado y se carga la relación anidada:
             * planilla.obra, para obtener tanto el cliente como la obra.
             * Se arma un array de combinaciones únicas [cliente_id, obra_id] para insertar
             * un registro por cada combinación en la tabla pivote.
             */
            $pivotData = [];
            foreach ($request->paquete_ids as $paquete_id) {
                // Cargar la relación planilla y dentro de ella la obra
                $paquete = Paquete::with('planilla.obra')->find($paquete_id);
                if (!$paquete) {
                    Log::info("Paquete {$paquete_id} no encontrado");
                    continue;
                }
                if (!$paquete->planilla) {
                    Log::info("Paquete {$paquete_id} no tiene planilla");
                    continue;
                }
                if (!$paquete->planilla->cliente_id) {
                    Log::info("Paquete {$paquete_id} tiene planilla, pero no tiene cliente asociado");
                    continue;
                }
                if (!$paquete->planilla->obra) {
                    Log::info("Paquete {$paquete_id} tiene planilla, pero no tiene obra asociada");
                    continue;
                }

                $cliente_id = $paquete->planilla->cliente_id;
                $obra_id = $paquete->planilla->obra->id;
                // Usamos una clave compuesta para evitar duplicados
                $clave = $cliente_id . '_' . $obra_id;
                // Solo se añade si no existe ya
                if (!isset($pivotData[$clave])) {
                    $pivotData[$clave] = [
                        'salida_id' => $salida->id,
                        'cliente_id' => $cliente_id,
                        'obra_id' => $obra_id,
                        'horas_paralizacion' => 0,
                        'importe_paralizacion' => 0,
                        'horas_grua' => 0,
                        'importe_grua' => 0,
                        'horas_almacen' => 0,
                        'importe' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Insertar en la tabla pivote salida_cliente
            if (!empty($pivotData)) {
                DB::table('salida_cliente')->insert(array_values($pivotData));
            } else {
                Log::warning('No se encontraron combinaciones de cliente y obra para asociar a la salida.');
            }

            Log::info('✅ Salida creada con éxito', [
                'salida_id' => $salida->id,
                'codigo_salida' => $codigo_salida,
                'fecha_salida' => $fechaSalida,
                'num_paquetes' => count($request->paquete_ids),
                'paquetes_ids' => $request->paquete_ids,
                'combinaciones_cliente_obra' => count($pivotData),
            ]);

            return redirect()->route('planificacion.index')->with('success', 'Salida creada con éxito');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación - no logueamos porque ya se maneja automáticamente
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Error al crear salida en método store', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => [
                    'camion_id' => $request->camion_id ?? null,
                    'paquete_ids' => $request->paquete_ids ?? [],
                    'num_paquetes' => is_array($request->paquete_ids) ? count($request->paquete_ids) : 0,
                ],
                'user_id' => auth()->id() ?? 'guest',
                'timestamp' => now()->toDateTimeString(),
            ]);

            return back()->withErrors(['error' => 'Hubo un problema al crear la salida: ' . $e->getMessage()]);
        }
    }

    public function crearSalidaDesdeCalendario(Request $request)
    {
        // Log inicial para confirmar que el método se está ejecutando
        Log::info('🚀 Iniciando crearSalidaDesdeCalendario', [
            'request_all' => $request->all(),
            'user_id' => auth()->id() ?? 'guest',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        try {
            $request->validate([
                'planillas_ids' => 'required|array|min:1',
                'planillas_ids.*' => 'exists:planillas,id',
                'camion_id' => 'nullable|exists:camiones,id',
            ]);

            // Buscar todos los paquetes asociados a las planillas dadas
            $paqueteIds = Paquete::whereIn('planilla_id', $request->planillas_ids)
                ->pluck('id')
                ->toArray();

            if (empty($paqueteIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron paquetes asociados a las planillas seleccionadas.'
                ], 422);
            }

            // Paquetes repetidos por ID
            $paquetesRepetidos = DB::table('salidas_paquetes')
                ->whereIn('paquete_id', $paqueteIds)
                ->whereNotNull('salida_id')
                ->pluck('paquete_id')
                ->toArray();

            $repetidos = array_intersect($paqueteIds, $paquetesRepetidos);

            if ($repetidos) {
                // 🔎 Buscar los paquetes para obtener código y planilla
                $paquetesInfo = Paquete::with('planilla')
                    ->whereIn('id', $repetidos)
                    ->get()
                    ->map(function ($paquete) {
                        $codigoPaquete = $paquete->codigo ?? 'Sin código';
                        $codigoPlanilla = $paquete->planilla ? ($paquete->planilla->codigo ?? $paquete->planilla->id) : 'Sin planilla';
                        return "{$codigoPaquete} (Planilla {$codigoPlanilla})";
                    })
                    ->toArray();

                $mensaje = 'Los siguientes paquetes ya están asociados a una salida: ' . implode(', ', $paquetesInfo);

                return response()->json([
                    'success' => false,
                    'message' => $mensaje
                ], 422);
            }

            // Obtener el camión y la empresa de transporte
            // $camion = Camion::findOrFail($request->camion_id);
            // $empresa = $camion->empresaTransporte;

            // Obtener la primera planilla para la fecha
            $primeraPlanilla = Planilla::whereIn('id', $request->planillas_ids)->first();
            $fechaSalida = $primeraPlanilla
                ? $primeraPlanilla->getRawOriginal('fecha_estimada_entrega')
                : now();

            // Crear la salida
            $salida = Salida::create([
                // 'empresa_id' => $empresa?->id,
                // 'camion_id' => $camion->id,
                'fecha_salida' => $fechaSalida,
                'estado' => 'pendiente',
            ]);

            // Generar código salida
            $codigo_salida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);
            $salida->codigo_salida = $codigo_salida;
            $salida->save();

            // Asociar paquetes a la salida
            $salida->paquetes()->attach($paqueteIds);

            // Asociar cliente y obra en salida_cliente
            $pivotData = [];
            foreach ($paqueteIds as $paquete_id) {
                $paquete = Paquete::with('planilla.obra')->find($paquete_id);
                if ($paquete?->planilla && $paquete->planilla->cliente_id && $paquete->planilla->obra) {
                    $clave = $paquete->planilla->cliente_id . '_' . $paquete->planilla->obra->id;
                    if (!isset($pivotData[$clave])) {
                        $pivotData[$clave] = [
                            'salida_id' => $salida->id,
                            'cliente_id' => $paquete->planilla->cliente_id,
                            'obra_id' => $paquete->planilla->obra->id,
                            'horas_paralizacion' => 0,
                            'importe_paralizacion' => 0,
                            'horas_grua' => 0,
                            'importe_grua' => 0,
                            'horas_almacen' => 0,
                            'importe' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
            if (!empty($pivotData)) {
                DB::table('salida_cliente')->insert(array_values($pivotData));
            }

            Log::info('✅ Salida creada desde calendario con éxito', [
                'salida_id' => $salida->id,
                'codigo_salida' => $codigo_salida,
                'fecha_salida' => $fechaSalida,
                'planillas_ids' => $request->planillas_ids,
                'num_planillas' => count($request->planillas_ids),
                'num_paquetes' => count($paqueteIds),
                'paquetes_ids' => $paqueteIds,
                'combinaciones_cliente_obra' => count($pivotData),
                'camion_id' => $request->camion_id ?? 'sin camión',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Salida creada con éxito',
                'salida_id' => $salida->id,
                'codigo_salida' => $codigo_salida
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación
            Log::warning('⚠️ Validación fallida al crear salida desde calendario', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'user_id' => auth()->id() ?? 'guest',
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Error al crear salida desde calendario', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => [
                    'planillas_ids' => $request->planillas_ids ?? [],
                    'num_planillas' => is_array($request->planillas_ids) ? count($request->planillas_ids) : 0,
                    'camion_id' => $request->camion_id ?? null,
                ],
                'user_id' => auth()->id() ?? 'guest',
                'timestamp' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la salida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function crearSalidasVaciasDesdeCalendario(Request $request)
    {
        // Log inicial para confirmar que el método se está ejecutando
        Log::info('🚀 Iniciando crearSalidasVaciasDesdeCalendario', [
            'request_all' => $request->all(),
            'user_id' => auth()->id() ?? 'guest',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        try {
            $request->validate([
                'planillas_ids' => 'required|array|min:1',
                'planillas_ids.*' => 'exists:planillas,id',
                'cantidad' => 'required|integer|min:1|max:10',
            ]);

            Log::info('✅ Validación pasada', [
                'planillas_ids' => $request->planillas_ids,
                'cantidad' => $request->cantidad,
            ]);

            // Obtener la primera planilla para la fecha
            $primeraPlanilla = Planilla::whereIn('id', $request->planillas_ids)->first();
            $fechaSalida = $primeraPlanilla
                ? $primeraPlanilla->getRawOriginal('fecha_estimada_entrega')
                : now();

            Log::info('📅 Fecha salida determinada', [
                'fecha_salida' => $fechaSalida,
                'planilla_id' => $primeraPlanilla?->id,
            ]);

            $salidasCreadas = [];

            // Crear N salidas vacías
            for ($i = 0; $i < $request->cantidad; $i++) {
                $numero = $i + 1;
                Log::info("🔄 Creando salida {$numero} de {$request->cantidad}");

                // Crear la salida vacía
                $salida = Salida::create([
                    'fecha_salida' => $fechaSalida,
                    'estado' => 'pendiente',
                ]);

                // Generar código salida
                $codigo_salida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);
                $salida->codigo_salida = $codigo_salida;
                $salida->save();

                Log::info("✅ Salida creada", [
                    'salida_id' => $salida->id,
                    'codigo_salida' => $codigo_salida,
                ]);

                // Asociar cliente y obra basado en las planillas (sin paquetes aún)
                $pivotData = [];
                foreach ($request->planillas_ids as $planilla_id) {
                    $planilla = Planilla::with('obra')->find($planilla_id);
                    if ($planilla && $planilla->cliente_id && $planilla->obra) {
                        $clave = $planilla->cliente_id . '_' . $planilla->obra->id;
                        if (!isset($pivotData[$clave])) {
                            $pivotData[$clave] = [
                                'salida_id' => $salida->id,
                                'cliente_id' => $planilla->cliente_id,
                                'obra_id' => $planilla->obra->id,
                                'horas_paralizacion' => 0,
                                'importe_paralizacion' => 0,
                                'horas_grua' => 0,
                                'importe_grua' => 0,
                                'horas_almacen' => 0,
                                'importe' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                if (!empty($pivotData)) {
                    DB::table('salida_cliente')->insert(array_values($pivotData));
                    Log::info("📦 Relaciones salida_cliente creadas", [
                        'salida_id' => $salida->id,
                        'num_relaciones' => count($pivotData),
                    ]);
                }

                $salidasCreadas[] = [
                    'id' => $salida->id,
                    'codigo_salida' => $codigo_salida,
                ];
            }

            Log::info('✅ Todas las salidas vacías creadas con éxito', [
                'cantidad' => $request->cantidad,
                'salidas_ids' => array_column($salidasCreadas, 'id'),
                'codigos' => array_column($salidasCreadas, 'codigo_salida'),
                'planillas_ids' => $request->planillas_ids,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se crearon {$request->cantidad} salida(s) vacía(s) con éxito",
                'salidas_ids' => array_column($salidasCreadas, 'id'),
                'salidas' => $salidasCreadas,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación
            Log::warning('⚠️ Validación fallida al crear salidas vacías', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'user_id' => auth()->id() ?? 'guest',
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Error al crear salidas vacías desde calendario', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => [
                    'planillas_ids' => $request->planillas_ids ?? [],
                    'cantidad' => $request->cantidad ?? null,
                ],
                'user_id' => auth()->id() ?? 'guest',
                'timestamp' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear las salidas vacías: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerSalidasPorPlanillas(Request $request)
    {
        try {
            Log::info('🔍 Obteniendo salidas por planillas', [
                'planillas_ids' => $request->input('planillas_ids'),
            ]);

            $planillasIds = explode(',', $request->input('planillas_ids', ''));

            // Obtener todos los paquetes de estas planillas
            $paquetesIds = Paquete::whereIn('planilla_id', $planillasIds)
                ->pluck('id')
                ->toArray();

            // Obtener salidas que contengan alguno de estos paquetes
            $salidas = Salida::with(['salidaClientes.obra:id,obra,cod_obra', 'paquetes'])
                ->whereHas('paquetes', function ($query) use ($paquetesIds) {
                    $query->whereIn('paquetes.id', $paquetesIds);
                })
                ->get()
                ->map(function ($salida) {
                    return [
                        'id' => $salida->id,
                        'codigo_salida' => $salida->codigo_salida,
                        'fecha_salida' => $salida->fecha_salida,
                        'estado' => $salida->estado,
                        'num_paquetes' => $salida->paquetes->count(),
                    ];
                });

            Log::info('✅ Salidas obtenidas', [
                'num_salidas' => $salidas->count(),
            ]);

            return response()->json([
                'salidas' => $salidas,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener salidas por planillas', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al cargar las salidas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarAsignacionesPaquetes(Request $request)
    {
        try {
            Log::info('💾 Guardando asignaciones de paquetes', [
                'num_asignaciones' => count($request->input('asignaciones', [])),
            ]);

            $request->validate([
                'asignaciones' => 'required|array',
                'asignaciones.*.paquete_id' => 'required|exists:paquetes,id',
                'asignaciones.*.salida_id' => 'nullable|exists:salidas,id',
            ]);

            $asignaciones = $request->input('asignaciones');
            $actualizados = 0;

            foreach ($asignaciones as $asignacion) {
                $paquete = Paquete::find($asignacion['paquete_id']);

                if (!$paquete) {
                    Log::warning("⚠️ Paquete no encontrado", ['paquete_id' => $asignacion['paquete_id']]);
                    continue;
                }

                // Si hay salida_id, asociar el paquete a la salida
                if ($asignacion['salida_id']) {
                    $salida = Salida::find($asignacion['salida_id']);

                    if ($salida) {
                        // Verificar si ya está asociado
                        $yaAsociado = DB::table('salidas_paquetes')
                            ->where('paquete_id', $paquete->id)
                            ->where('salida_id', $salida->id)
                            ->exists();

                        if (!$yaAsociado) {
                            // Remover asociación anterior si existe
                            DB::table('salidas_paquetes')
                                ->where('paquete_id', $paquete->id)
                                ->delete();

                            // Crear nueva asociación
                            DB::table('salidas_paquetes')->insert([
                                'paquete_id' => $paquete->id,
                                'salida_id' => $salida->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Actualizar estado del paquete a 'asignado_a_salida'
                            $paquete->update(['estado' => 'asignado_a_salida']);

                            $actualizados++;
                            Log::info("📦 Paquete asociado a salida", [
                                'paquete_id' => $paquete->id,
                                'salida_id' => $salida->id,
                                'estado' => 'asignado_a_salida',
                            ]);
                        }
                    }
                } else {
                    // Si salida_id es null, desasociar el paquete
                    $deleted = DB::table('salidas_paquetes')
                        ->where('paquete_id', $paquete->id)
                        ->delete();

                    if ($deleted > 0) {
                        // Volver el estado a 'pendiente'
                        $paquete->update(['estado' => 'pendiente']);

                        $actualizados++;
                        Log::info("📦 Paquete desasociado de salida", [
                            'paquete_id' => $paquete->id,
                            'estado' => 'pendiente',
                        ]);
                    }
                }
            }

            Log::info('✅ Asignaciones guardadas', [
                'total_procesados' => count($asignaciones),
                'actualizados' => $actualizados,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se actualizaron {$actualizados} asignaciones correctamente",
                'actualizados' => $actualizados,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Validación fallida al guardar asignaciones', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Error al guardar asignaciones de paquetes', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las asignaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function informacionGestionPaquetes(Request $request)
    {
        try {
            Log::info('🔍 Obteniendo información para gestión de paquetes', [
                'planillas_ids' => $request->input('planillas_ids'),
                'salidas_ids' => $request->input('salidas_ids'),
            ]);

            $planillasIds = explode(',', $request->input('planillas_ids', ''));
            $salidasIds = explode(',', $request->input('salidas_ids', ''));

            // Obtener planillas con información relevante
            $planillas = Planilla::with(['obra:id,obra,cod_obra', 'user:id,name'])
                ->whereIn('id', $planillasIds)
                ->get()
                ->map(function ($planilla) {
                    return [
                        'id' => $planilla->id,
                        'codigo' => $planilla->codigo,
                        'obra' => $planilla->obra?->obra,
                        'cod_obra' => $planilla->obra?->cod_obra,
                        'operario' => $planilla->user?->name,
                        'peso_total' => $planilla->peso_total,
                        'fecha_estimada_entrega' => $planilla->fecha_estimada_entrega,
                    ];
                });

            // Obtener salidas
            $salidas = Salida::with(['salidaClientes.obra:id,obra,cod_obra'])
                ->whereIn('id', $salidasIds)
                ->get()
                ->map(function ($salida) {
                    return [
                        'id' => $salida->id,
                        'codigo_salida' => $salida->codigo_salida,
                        'fecha_salida' => $salida->fecha_salida,
                        'estado' => $salida->estado,
                        'obras' => $salida->salidaClientes->map(fn($sc) => [
                            'id' => $sc->obra?->id,
                            'nombre' => $sc->obra?->obra,
                            'cod_obra' => $sc->obra?->cod_obra,
                        ])->unique('id')->values(),
                    ];
                });

            // Obtener todos los paquetes de las planillas con su salida actual
            $paquetes = Paquete::with(['planilla:id,codigo', 'salida:id,codigo_salida'])
                ->whereIn('planilla_id', $planillasIds)
                ->get()
                ->map(function ($paquete) {
                    return [
                        'id' => $paquete->id,
                        'codigo' => $paquete->codigo,
                        'planilla_id' => $paquete->planilla_id,
                        'planilla_codigo' => $paquete->planilla?->codigo,
                        'salida_id' => $paquete->salida_id,
                        'salida_codigo' => $paquete->salida?->codigo_salida,
                        'peso' => $paquete->peso,
                        'longitud' => $paquete->longitud,
                        'diametro' => $paquete->diametro,
                    ];
                });

            Log::info('✅ Información de gestión obtenida', [
                'num_planillas' => $planillas->count(),
                'num_salidas' => $salidas->count(),
                'num_paquetes' => $paquetes->count(),
            ]);

            return response()->json([
                'planillas' => $planillas,
                'salidas' => $salidas,
                'paquetes' => $paquetes,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener información de gestión de paquetes', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al cargar la información: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene información de una salida específica y sus paquetes para la gestión individual
     */
    public function informacionPaquetesSalida(Request $request)
    {
        try {
            $salidaId = $request->input('salida_id');

            Log::info('🔍 Obteniendo información de paquetes para salida', [
                'salida_id' => $salidaId,
            ]);

            // Obtener la salida con sus relaciones
            $salida = Salida::with([
                'salidaClientes.obra:id,obra,cod_obra',
                'empresaTransporte:id,nombre',
                'camion:id,modelo',
            ])->findOrFail($salidaId);

            // Obtener paquetes asignados a esta salida
            $paquetesAsignados = Paquete::with(['planilla.obra:id,obra,cod_obra'])
                ->whereHas('salidas', function ($q) use ($salidaId) {
                    $q->where('salidas.id', $salidaId);
                })
                ->get()
                ->map(function ($paquete) {
                    return [
                        'id' => $paquete->id,
                        'codigo' => $paquete->codigo,
                        'planilla_id' => $paquete->planilla_id,
                        'peso' => $paquete->peso,
                        'planilla' => [
                            'id' => $paquete->planilla->id ?? null,
                            'codigo' => $paquete->planilla->codigo ?? null,
                            'obra' => [
                                'id' => $paquete->planilla->obra->id ?? null,
                                'obra' => $paquete->planilla->obra->obra ?? null,
                                'cod_obra' => $paquete->planilla->obra->cod_obra ?? null,
                            ],
                        ],
                    ];
                });

            // Obtener las planillas relacionadas con los paquetes de esta salida
            $planillasIds = $paquetesAsignados->pluck('planilla_id')->unique()->filter();

            // Obtener paquetes disponibles: de las mismas planillas pero sin salida asignada
            $paquetesDisponibles = Paquete::with(['planilla.obra:id,obra,cod_obra'])
                ->whereIn('planilla_id', $planillasIds)
                ->whereDoesntHave('salidas')
                ->get()
                ->map(function ($paquete) {
                    return [
                        'id' => $paquete->id,
                        'codigo' => $paquete->codigo,
                        'planilla_id' => $paquete->planilla_id,
                        'peso' => $paquete->peso,
                        'planilla' => [
                            'id' => $paquete->planilla->id ?? null,
                            'codigo' => $paquete->planilla->codigo ?? null,
                            'obra' => [
                                'id' => $paquete->planilla->obra->id ?? null,
                                'obra' => $paquete->planilla->obra->obra ?? null,
                                'cod_obra' => $paquete->planilla->obra->cod_obra ?? null,
                            ],
                        ],
                    ];
                });

            Log::info('✅ Información de paquetes de salida obtenida', [
                'num_paquetes_asignados' => $paquetesAsignados->count(),
                'num_paquetes_disponibles' => $paquetesDisponibles->count(),
            ]);

            return response()->json([
                'salida' => [
                    'id' => $salida->id,
                    'codigo_salida' => $salida->codigo_salida,
                    'codigo_sage' => $salida->codigo_sage,
                    'fecha_salida' => $salida->fecha_salida,
                    'estado' => $salida->estado,
                    'empresa_transporte' => [
                        'nombre' => $salida->empresaTransporte->nombre ?? null,
                    ],
                    'camion' => [
                        'modelo' => $salida->camion->modelo ?? null,
                    ],
                ],
                'paquetesAsignados' => $paquetesAsignados,
                'paquetesDisponibles' => $paquetesDisponibles,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener información de paquetes de salida', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al cargar la información de la salida: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda los paquetes asignados a una salida específica
     */
    public function guardarPaquetesSalida(Request $request)
    {
        try {
            Log::info('💾 Guardando paquetes de salida', [
                'salida_id' => $request->input('salida_id'),
                'num_paquetes' => count($request->input('paquetes_ids', [])),
            ]);

            $request->validate([
                'salida_id' => 'required|exists:salidas,id',
                'paquetes_ids' => 'required|array',
                'paquetes_ids.*' => 'exists:paquetes,id',
            ]);

            $salidaId = $request->input('salida_id');
            $paquetesIds = $request->input('paquetes_ids', []);

            $salida = Salida::findOrFail($salidaId);

            // Primero, eliminar todos los paquetes actuales de esta salida
            DB::table('salidas_paquetes')
                ->where('salida_id', $salidaId)
                ->delete();

            // Luego, agregar los nuevos paquetes
            $insertData = [];
            foreach ($paquetesIds as $paqueteId) {
                // Verificar que el paquete no esté ya en otra salida
                $existeEnOtraSalida = DB::table('salidas_paquetes')
                    ->where('paquete_id', $paqueteId)
                    ->where('salida_id', '!=', $salidaId)
                    ->exists();

                if ($existeEnOtraSalida) {
                    // Eliminar de la otra salida primero
                    DB::table('salidas_paquetes')
                        ->where('paquete_id', $paqueteId)
                        ->delete();
                }

                $insertData[] = [
                    'salida_id' => $salidaId,
                    'paquete_id' => $paqueteId,
                ];
            }

            if (!empty($insertData)) {
                DB::table('salidas_paquetes')->insert($insertData);
            }

            Log::info('✅ Paquetes de salida guardados', [
                'salida_id' => $salidaId,
                'num_paquetes_asignados' => count($insertData),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Los paquetes de la salida se han actualizado correctamente',
                'num_paquetes' => count($insertData),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Validación fallida al guardar paquetes de salida', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Error al guardar paquetes de salida', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los paquetes de la salida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            /** @var \App\Models\Salida $salida */
            $salida = Salida::findOrFail($id);

            $field     = $request->input('field');
            $value     = $request->input('value');
            $clienteId = $request->input('cliente_id');
            $obraId    = $request->input('obra_id');

            // Campos actualizables en 'salidas'
            $salidaFields = [
                'fecha_salida',
                'estado',
                'codigo_sage',
                'empresa_id',
                'camion_id',
            ];

            // Campos actualizables en 'salida_cliente' (pivot)
            $salidaClienteFields = [
                'importe',
                'horas_paralizacion',
                'importe_paralizacion',
                'horas_grua',
                'importe_grua',
                'horas_almacen',
            ];

            $allFields = array_merge($salidaFields, $salidaClienteFields);

            if (!in_array($field, $allFields, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo especificado no es editable en línea.'
                ], 422);
            }

            // Reglas de validación campo a campo
            $rules = [
                'fecha_salida'         => ['nullable', 'date'],
                'estado'               => ['nullable', 'string', 'max:50'],
                'codigo_sage'          => ['nullable', 'string', 'max:100'],
                'empresa_id' => ['nullable', 'integer', 'exists:empresa_transportes,id'],
                'camion_id'            => ['nullable', 'integer', 'exists:camiones,id'],

                'importe'              => ['nullable', 'numeric'],
                'horas_paralizacion'   => ['nullable', 'numeric'],
                'importe_paralizacion' => ['nullable', 'numeric'],
                'horas_grua'           => ['nullable', 'numeric'],
                'importe_grua'         => ['nullable', 'numeric'],
                'horas_almacen'        => ['nullable', 'numeric'],
            ];

            // Valida sólo el campo que viene
            if (array_key_exists($field, $rules)) {
                $request->validate([$field => $rules[$field]]);
            }

            // Normalizaciones previas
            // 1) Fecha con hora aceptando varios formatos
            if ($field === 'fecha_salida' && filled($value)) {
                try {
                    // Acepta 'd/m/Y H:i' o cualquier parseable por Carbon
                    $value = self::parseFechaHora($value)?->format('Y-m-d H:i:s');
                    if (!$value) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Formato de fecha no válido. Usa DD/MM/YYYY HH:MM o YYYY-MM-DD HH:MM:SS.'
                        ], 422);
                    }
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de fecha no válido.'
                    ], 422);
                }
            }

            // 2) Numéricos: null -> null, cadena vacía -> null
            if (in_array($field, $salidaClienteFields, true)) {
                if ($value === '' || $value === null) {
                    $value = null;
                } else {
                    $value = (float) $value;
                }
            }

            // ------- Persistencia -------
            if (in_array($field, $salidaFields, true)) {

                if ($field === 'empresa_id') {
                    // Cambiar empresa => limpiar camión si no pertenece
                    $nuevaEmpresaId = $value ?: null;

                    $salida->empresa_id = $nuevaEmpresaId;

                    // Si hay un camión asignado y no coincide con la nueva empresa, nuléalo
                    if ($salida->camion_id) {
                        $camionPertenece = Camion::where('id', $salida->camion_id)
                            ->where('empresa_id', $nuevaEmpresaId)
                            ->exists();

                        if (!$camionPertenece) {
                            $salida->camion_id = null;
                        }
                    }

                    $salida->save();
                } elseif ($field === 'camion_id') {
                    // Validar pertenencia del camión a la empresa actual (si hay)
                    if ($value) {
                        $empresaId = $salida->empresa_id;
                        if ($empresaId) {
                            $camionOk = Camion::where('id', $value)
                                ->where('empresa_id', $empresaId)
                                ->exists();

                            if (!$camionOk) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'El camión no pertenece a la empresa de transporte seleccionada.'
                                ], 422);
                            }
                        }
                    }

                    $salida->camion_id = $value ?: null;
                    $salida->save();
                } else {
                    // fecha_salida, estado, codigo_sage
                    $salida->$field = $value;
                    $salida->save();
                }
            } else {
                // Pivot: salida_cliente — requiere cliente y obra
                if (!$clienteId || !$obraId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falta el ID del cliente u obra para actualizar el campo.'
                    ], 422);
                }

                DB::table('salida_cliente')
                    ->where('salida_id', $salida->id)
                    ->where('cliente_id', $clienteId)
                    ->where('obra_id', $obraId)
                    ->update([$field => $value]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Salida actualizada correctamente.',
                'data'    => [
                    'id'                     => $salida->id,
                    'fecha_salida'           => $salida->fecha_salida,
                    'estado'                 => $salida->estado,
                    'codigo_sage'            => $salida->codigo_sage,
                    'empresa_id'             => $salida->empresa_id,
                    'camion_id'              => $salida->camion_id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la salida.' . $e->getMessage(),
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function actualizarCodigoSage(Request $request, Salida $salida)
    {
        $request->validate([
            'codigo' => 'required|string|max:255',
        ], [
            'codigo.required' => 'El código es obligatorio.',
        ]);

        try {
            $salida->codigo_sage = $request->codigo;
            $salida->save();

            return response()->json([
                'success' => true,
                'message' => 'Código SAGE actualizado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el código SAGE.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function export($mes)
    {
        $meses = [
            'enero' => 'January',
            'febrero' => 'February',
            'marzo' => 'March',
            'abril' => 'April',
            'mayo' => 'May',
            'junio' => 'June',
            'julio' => 'July',
            'agosto' => 'August',
            'septiembre' => 'September',
            'octubre' => 'October',
            'noviembre' => 'November',
            'diciembre' => 'December',
        ];

        try {
            // 🔹 Extraer el nombre del mes (sin el año)
            preg_match('/([a-zA-Záéíóú]+)/', $mes, $matches);
            $mesSolo = strtolower($matches[1] ?? '');

            // 🔹 Validar si el mes es válido
            if (!isset($meses[$mesSolo])) {
                return redirect()->route('salidas.ferralla.index')->with('error', "Mes no válido: $mes");
            }

            $mesIngles = $meses[$mesSolo];

            // 🔹 Extraer el año de la variable `$mes`
            preg_match('/(\d{4})/', $mes, $yearMatch);
            $anio = $yearMatch[1] ?? \Carbon\Carbon::now()->year;

            // 🔹 Obtener el número del mes con Carbon
            $numeroMes = \Carbon\Carbon::parse("1 $mesIngles")->month;

            // 🔹 Obtener salidas con sus relaciones, usando la nueva relación salidaClientes
            $salidas = \App\Models\Salida::whereMonth('fecha_salida', $numeroMes)
                ->whereYear('fecha_salida', $anio)
                ->with([
                    'salidaClientes.cliente',
                    'salidaClientes.obra',
                    'empresaTransporte',
                    'camion',
                    // Si necesitas datos de paquetes también
                    'paquetes.planilla.obra'
                ])
                ->get();

            if ($salidas->isEmpty()) {
                return redirect()->route('salidas.ferralla.index')->with('error', "No hay salidas registradas en $mesSolo $anio.");
            }

            // 🔹 Generar resumen por empresa de transporte
            $empresaSummary = [];

            foreach ($salidas as $salida) {
                $empresa = $salida->empresaTransporte; // Relación belongsTo (único objeto)
                if (!$empresa) {
                    continue;
                }
                $nombreEmpresa = trim($empresa->nombre) ?: "Empresa desconocida";

                if (!isset($empresaSummary[$nombreEmpresa])) {
                    $empresaSummary[$nombreEmpresa] = [
                        'obras' => collect(),
                        'horas_paralizacion' => 0,
                        'importe_paralizacion' => 0,
                        'horas_grua' => 0,
                        'importe_grua' => 0,
                        'horas_almacen' => 0,
                        'importe' => 0,
                        'total' => 0,
                    ];
                }

                // 🔹 Obtener las obras de la salida a través de la relación salidaClientes
                $obrasEmpresa = $salida->salidaClientes
                    ->pluck('obra.obra')
                    ->unique()
                    ->filter()
                    ->values();

                $empresaSummary[$nombreEmpresa]['obras'] = $empresaSummary[$nombreEmpresa]['obras']
                    ->merge($obrasEmpresa)
                    ->unique();

                // 🔹 Acumular valores desde la relación salidaClientes
                foreach ($salida->salidaClientes as $registro) {
                    // Se asume que cada registro tiene los campos de horas e importes
                    $empresaSummary[$nombreEmpresa]['horas_paralizacion'] += $registro->horas_paralizacion ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe_paralizacion'] += $registro->importe_paralizacion ?? 0;
                    $empresaSummary[$nombreEmpresa]['horas_grua'] += $registro->horas_grua ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe_grua'] += $registro->importe_grua ?? 0;
                    $empresaSummary[$nombreEmpresa]['horas_almacen'] += $registro->horas_almacen ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe'] += $registro->importe ?? 0;
                }

                // 🔹 Calcular el total de la empresa
                $empresaSummary[$nombreEmpresa]['total'] =
                    $empresaSummary[$nombreEmpresa]['importe_paralizacion'] +
                    $empresaSummary[$nombreEmpresa]['importe_grua'] +
                    $empresaSummary[$nombreEmpresa]['importe'];
            }

            // 🔹 Convertir las obras en cadenas de texto para exportar correctamente
            foreach ($empresaSummary as $empresa => &$data) {
                $data['obras'] = $data['obras']->implode(', ');
            }

            return \Excel::download(new \App\Exports\SalidasExport($salidas, $empresaSummary), "salidas_{$mesSolo}_{$anio}.xlsx");
        } catch (\Exception $e) {
            return redirect()->route('salidas.ferralla.index')->with('error', 'Hubo un problema al exportar las salidas: ' . $e->getMessage());
        }
    }

    public function actualizarFechaSalida(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:salidas,id',
            'fecha_salida' => 'required|date'
        ]);

        $salida = Salida::findOrFail($request->id);
        $salida->fecha_salida = Carbon::parse($request->fecha_salida);
        $salida->save();

        return response()->json(['success' => true]);
    }

    public function quitarPaquete(Salida $salida, Paquete $paquete)
    {
        // 1. Quitar el paquete de la salida
        $salida->paquetes()->detach($paquete->id);

        // 2. Obtener datos relacionados
        $planilla = $paquete->planilla;
        $obra = $planilla?->obra;
        $clienteId = $planilla?->cliente_id;

        if (!$obra || !$clienteId) {
            return back()->with('error', 'No se pudo determinar la obra o el cliente del paquete.');
        }

        // 3. ¿Quedan más paquetes en esa salida para esa obra?
        $quedanPaquetesMismaObra = $salida->paquetes()
            ->whereHas('planilla', fn($q) => $q->where('obra_id', $obra->id))
            ->exists();

        // 4. Si no quedan, borramos la relación en salida_cliente
        if (!$quedanPaquetesMismaObra) {
            $salida->salidaClientes()
                ->where('cliente_id', $clienteId)
                ->where('obra_id', $obra->id)
                ->delete();
        }

        // 5. ¿Quedan paquetes en la salida?
        $quedanPaquetes = $salida->paquetes()->exists();

        if (!$quedanPaquetes) {
            $salida->delete();
            return redirect()->route('planificacion.index')
                ->with('success', 'Paquete quitado y salida eliminada porque no quedaban más paquetes.');
        }

        return back()->with('success', 'Paquete quitado correctamente.');
    }

    public function destroy($id)
    {
        try {
            // Buscar la salida o lanzar excepción si no existe
            $salida = Salida::findOrFail($id);

            // Si existen relaciones (por ejemplo, registros en salidas_paquetes o salida_cliente),
            // puedes eliminarlas de forma automática si definiste ON DELETE CASCADE en las claves foráneas.
            // En caso contrario, deberías eliminarlas manualmente antes de eliminar la salida.

            $salida->delete();

            return redirect()->route('salidas.ferralla.index')
                ->with('success', 'Salida eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('salidas.ferralla.index')
                ->with('error', 'Hubo un problema al eliminar la salida: ' . $e->getMessage());
        }
    }

    /**
     * Muestra la vista para gestionar salidas y paquetes de planillas agrupadas
     */
    public function gestionarSalidas(Request $request)
    {
        $planillasIds = explode(',', $request->get('planillas', ''));

        if (empty($planillasIds[0])) {
            return redirect()->route('planificacion.index')
                ->with('error', 'No se especificaron planillas para gestionar');
        }

        // Obtener planillas con sus relaciones
        $planillas = Planilla::with(['obra', 'cliente', 'paquetes'])
            ->whereIn('id', $planillasIds)
            ->get()
            ->map(function ($planilla) {
                $estado = $planilla->estado;
                $colorClass = match ($estado) {
                    'completada' => 'bg-green-500 text-white',
                    'pendiente' => 'bg-yellow-500 text-black',
                    'fabricando' => 'bg-blue-500 text-white',
                    default => 'bg-gray-400 text-white',
                };

                $planilla->estado_class = $colorClass;
                return $planilla;
            });

        // Obtener las obras de las planillas (asumimos que comparten obra)
        $obrasIds = $planillas->pluck('obra_id')->unique()->filter();

        Log::info('🔍 Buscando salidas', [
            'obras_ids' => $obrasIds->toArray(),
        ]);

        // Buscar salidas de estas obras con estado pendiente
        // O salidas recientes sin obra asignada (recién creadas)
        $salidasExistentes = Salida::with(['paquetes.planilla', 'paquetes.etiquetas.elementos', 'empresaTransporte', 'camion', 'obras'])
            ->where(function($query) use ($obrasIds) {
                // Salidas con obras específicas y estado pendiente
                $query->where('estado', 'pendiente')
                      ->whereHas('obras', function ($q) use ($obrasIds) {
                          $q->whereIn('obras.id', $obrasIds);
                      });
            })
            ->orWhere(function($query) {
                // O salidas pendientes recientes sin obra (vacías recién creadas)
                $query->where('estado', 'pendiente')
                      ->whereDoesntHave('obras')
                      ->where('created_at', '>=', now()->subDays(7));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('✅ Salidas encontradas', [
            'cantidad' => $salidasExistentes->count(),
            'salidas' => $salidasExistentes->pluck('codigo_salida')->toArray(),
        ]);

        // Obtener paquetes disponibles: solo con estado 'pendiente' de estas planillas
        $paquetesDisponibles = Paquete::with(['planilla', 'etiquetas.elementos'])
            ->whereIn('planilla_id', $planillasIds)
            ->where('estado', 'pendiente')
            ->get();

        // Obtener empresas y camiones para los formularios
        $empresas = EmpresaTransporte::all();
        $camiones = Camion::with('empresaTransporte')->get();

        return view('salidas.gestionar-salidas', [
            'planillas' => $planillas,
            'salidasExistentes' => $salidasExistentes,
            'paquetesDisponibles' => $paquetesDisponibles,
            'empresas' => $empresas,
            'camiones' => $camiones,
        ]);
    }

    /**
     * Crea múltiples salidas vacías de forma masiva
     */
    public function crearSalidasVaciasMasivo(Request $request)
    {
        try {
            Log::info('📦 Creando salidas vacías masivamente', [
                'num_salidas' => count($request->input('salidas', [])),
                'planillas_ids' => $request->input('planillas_ids'),
            ]);

            $request->validate([
                'salidas' => 'required|array|min:1',
                'salidas.*.fecha_salida' => 'required|date',
                'salidas.*.camion_id' => 'nullable|exists:camiones,id',
                'salidas.*.empresa_transporte_id' => 'nullable|exists:empresas_transporte,id',
                'planillas_ids' => 'required|array',
            ]);

            $salidasData = $request->input('salidas');
            $planillasIds = $request->input('planillas_ids');

            // Obtener información de las planillas para construir el código de salida
            $planillas = Planilla::with('obra')->whereIn('id', $planillasIds)->get();
            $obra = $planillas->first()->obra ?? null;
            $codigoObra = $obra ? $obra->cod_obra : 'OBRA';

            $salidasCreadas = [];

            foreach ($salidasData as $index => $salidaData) {
                // Generar código de salida único
                $fechaSalida = Carbon::parse($salidaData['fecha_salida']);
                $año = $fechaSalida->format('y');
                $mes = $fechaSalida->format('m');

                // Buscar el último número de salida para este año y mes
                $ultimoNumero = Salida::where('codigo_salida', 'LIKE', "S{$año}{$mes}%")
                    ->orderBy('codigo_salida', 'desc')
                    ->value('codigo_salida');

                if ($ultimoNumero) {
                    $numero = intval(substr($ultimoNumero, 5)) + 1;
                } else {
                    $numero = 1;
                }

                $codigoSalida = sprintf('S%s%s%04d', $año, $mes, $numero + $index);

                // Crear la salida
                $salida = Salida::create([
                    'codigo_salida' => $codigoSalida,
                    'fecha_salida' => $fechaSalida,
                    'empresa_id' => $salidaData['empresa_transporte_id'] ?? null,
                    'camion_id' => $salidaData['camion_id'] ?? null,
                    'codigo_sage' => $salidaData['codigo_sage'] ?? null,
                    'estado' => 'pendiente',
                ]);

                // Asociar la salida con las obras de las planillas
                // Esto permite filtrar salidas por obra después
                if ($obra && $obra->id) {
                    SalidaCliente::create([
                        'salida_id' => $salida->id,
                        'cliente_id' => $obra->cliente_id ?? null,
                        'obra_id' => $obra->id,
                    ]);
                }

                $salidasCreadas[] = $salida;

                Log::info('✅ Salida vacía creada', [
                    'codigo_salida' => $codigoSalida,
                    'salida_id' => $salida->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Salidas creadas correctamente',
                'salidas_creadas' => count($salidasCreadas),
                'salidas' => collect($salidasCreadas)->map(fn($s) => [
                    'id' => $s->id,
                    'codigo_salida' => $s->codigo_salida,
                ]),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('⚠️ Validación fallida al crear salidas vacías masivo', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Error al crear salidas vacías masivo', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear las salidas: ' . $e->getMessage()
            ], 500);
        }
    }
}
