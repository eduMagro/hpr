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
use App\Models\Obra;
use App\Models\Localizacion;
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
use App\Services\ActionLoggerService;

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
            $camiones = Camion::with('empresaTransporte')->orderBy('modelo')->get();
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

            // Inicializar variables para usuarios no oficina
            $empresasTransporte = collect();
            $camiones = collect();
            $camionesJson = collect();
        }

        // Extraer todos los paquetes de las salidas
        $paquetes = $salidas->pluck('paquetes')->flatten();

        // Agrupar las salidas por mes
        $salidasPorMes = $salidas->groupBy(function ($salida) {
            return \Carbon\Carbon::parse($salida->fecha_salida)->translatedFormat('F Y');
        });

        // Crear un resumen mensual por Empresa de Transporte
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

        // Crear un resumen mensual por Cliente y Obra
        $resumenClienteObra = [];
        foreach ($salidasPorMes as $mes => $salidasGrupo) {
            $clienteObraSummary = [];
            foreach ($salidasGrupo as $salida) {
                foreach ($salida->salidaClientes as $registro) {
                    $nombreCliente = trim($registro->cliente->empresa ?? "N/A") ?: "Cliente desconocido";
                    $nombreObra = trim($registro->obra->obra ?? "N/A") ?: "Obra desconocida";
                    $clave = "{$nombreCliente} - {$nombreObra}";

                    if (!isset($clienteObraSummary[$clave])) {
                        $clienteObraSummary[$clave] = [
                            'cliente_id' => $registro->cliente->id ?? null,
                            'obra_id' => $registro->obra->id ?? null,
                            'horas_paralizacion' => 0,
                            'importe_paralizacion' => 0,
                            'horas_grua' => 0,
                            'importe_grua' => 0,
                            'horas_almacen' => 0,
                            'importe' => 0,
                            'total' => 0,
                        ];
                    }

                    $clienteObraSummary[$clave]['horas_paralizacion'] += $registro->horas_paralizacion ?? 0;
                    $clienteObraSummary[$clave]['importe_paralizacion'] += $registro->importe_paralizacion ?? 0;
                    $clienteObraSummary[$clave]['horas_grua'] += $registro->horas_grua ?? 0;
                    $clienteObraSummary[$clave]['importe_grua'] += $registro->importe_grua ?? 0;
                    $clienteObraSummary[$clave]['horas_almacen'] += $registro->horas_almacen ?? 0;
                    $clienteObraSummary[$clave]['importe'] += $registro->importe ?? 0;
                }
            }
            foreach ($clienteObraSummary as $clave => &$data) {
                $data['total'] = $data['importe_paralizacion'] + $data['importe_grua'] + $data['importe'];
            }
            $resumenClienteObra[$mes] = $clienteObraSummary;
        }

        return view('salidas.index', compact(
            'salidasPorMes',
            'salidas',
            'resumenMensual',
            'resumenClienteObra',
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

    /**
     * Obtiene los paquetes de una salida con sus localizaciones y etiquetas
     * para mostrar en el mapa durante la ejecución de salida
     */
    public function paquetesPorSalida($salidaId)
    {
        try {
            $salida = Salida::with([
                'paquetes.localizacionPaquete',
                'paquetes.etiquetas',
                'paquetes.planilla.obra',
                'paquetes.planilla.cliente',
                'paquetes.nave'
            ])->findOrFail($salidaId);

            // Agrupar paquetes por nave y obtener información de cada nave
            $paquetesPorNave = [];

            foreach ($salida->paquetes as $paquete) {
                $naveId = $paquete->nave_id;
                if ($naveId) {
                    if (!isset($paquetesPorNave[$naveId])) {
                        $nave = $paquete->nave;
                        $paquetesPorNave[$naveId] = [
                            'nave_id' => $naveId,
                            'nave_nombre' => $nave ? ($nave->obra ?? "Nave {$nave->id}") : "Nave {$naveId}",
                            'paquetes' => []
                        ];
                    }
                }
            }

            // Preparar paquetes con sus datos
            $paquetes = $salida->paquetes->map(function ($paquete) {
                $loc = $paquete->localizacionPaquete;

                return [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'peso' => $paquete->peso,
                    'estado' => $paquete->estado,
                    'nave_id' => $paquete->nave_id,
                    'obra' => $paquete->planilla?->obra?->obra ?? 'N/A',
                    'cliente' => $paquete->planilla?->cliente?->empresa ?? 'N/A',
                    'tipo' => $paquete->getTipoContenido(),
                    'num_etiquetas' => $paquete->etiquetas->count(),
                    'etiquetas' => $paquete->etiquetas->map(function ($etiqueta) {
                        return [
                            'id' => $etiqueta->id,
                            'codigo' => $etiqueta->codigo,
                            'numero_etiqueta' => $etiqueta->numero_etiqueta,
                            'etiqueta_sub_id' => $etiqueta->etiqueta_sub_id,
                        ];
                    }),
                    'localizacion' => $loc ? [
                        'x1' => $loc->x1,
                        'y1' => $loc->y1,
                        'x2' => $loc->x2,
                        'y2' => $loc->y2,
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'salida' => [
                    'id' => $salida->id,
                    'salidaId' => $salida->id,
                    'codigo_salida' => $salida->codigo_salida,
                    'estado' => $salida->estado,
                ],
                'paquetesPorNave' => array_values($paquetesPorNave),
                'paquetes' => $paquetes
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener paquetes de salida', [
                'salida_id' => $salidaId,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los paquetes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el HTML del mapa renderizado para una nave específica en el contexto de una salida
     */
    public function obtenerMapaNave($salidaId, $naveId)
    {
        try {
            $salida = Salida::with([
                'paquetes.localizacionPaquete',
                'paquetes.nave'
            ])->findOrFail($salidaId);

            $nave = Obra::find($naveId);
            if (!$nave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nave no encontrada'
                ], 404);
            }

            // Obtener datos del mapa similar a MaquinaController::obtenerDatosMapaParaNave
            $mapaData = $this->obtenerDatosMapaParaNave($naveId, $salidaId);

            // Renderizar el componente de mapa
            $html = view('components.mapa-salida-renderizado', [
                'mapaData' => $mapaData,
                'naveId' => $naveId
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'nave' => [
                    'id' => $nave->id,
                    'nombre' => $nave->obra,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener mapa de nave', [
                'salida_id' => $salidaId,
                'nave_id' => $naveId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el mapa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los datos necesarios para renderizar el mapa de una nave
     */
    private function obtenerDatosMapaParaNave(?int $naveId, ?int $salidaId = null): array
    {
        if (!$naveId) {
            return [];
        }

        $obra = Obra::find($naveId);
        if (!$obra) {
            return [];
        }

        $anchoM = max(1, (int) ($obra->ancho_m ?? 22));
        $largoM = max(1, (int) ($obra->largo_m ?? 115));
        $columnasReales = $anchoM * 2;
        $filasReales = $largoM * 2;

        $ctx = [
            'naveId'         => $naveId,
            'columnasReales' => $columnasReales,
            'filasReales'    => $filasReales,
            'estaGirado'     => false,
            'columnasVista'  => $columnasReales,
            'filasVista'     => $filasReales,
        ];

        $localizaciones = Localizacion::with('maquina:id,nombre')
            ->where('nave_id', $naveId)
            ->get();

        $localizacionesMaquinas = $localizaciones
            ->where('tipo', 'maquina')
            ->whereNotNull('maquina_id')
            ->filter(fn($loc) => $loc->maquina)
            ->map(function ($loc) {
                return [
                    'id'         => (int) $loc->id,
                    'x1'         => (int) $loc->x1,
                    'y1'         => (int) $loc->y1,
                    'x2'         => (int) $loc->x2,
                    'y2'         => (int) $loc->y2,
                    'maquina_id' => (int) $loc->maquina_id,
                    'nombre'     => (string) ($loc->nombre ?: $loc->maquina->nombre),
                ];
            })->values()->toArray();

        $localizacionesZonas = $localizaciones
            ->where('tipo', '!=', 'maquina')
            ->map(function ($loc) {
                return [
                    'id'    => (int) $loc->id,
                    'x1'    => (int) $loc->x1,
                    'y1'    => (int) $loc->y1,
                    'x2'    => (int) $loc->x2,
                    'y2'    => (int) $loc->y2,
                    'tipo'  => $loc->tipo ?? 'transitable',
                    'nombre'=> (string) $loc->nombre,
                ];
            })->values()->toArray();

        // Si hay salidaId, filtrar solo paquetes de esa salida
        $paquetesQuery = Paquete::with('localizacionPaquete')
            ->where('nave_id', $naveId)
            ->whereHas('localizacionPaquete');

        if ($salidaId) {
            $paquetesQuery->whereHas('salidas', function ($q) use ($salidaId) {
                $q->where('salidas.id', $salidaId);
            });
        }

        $paquetesConLocalizacion = $paquetesQuery->get()
            ->map(function ($paquete) {
                $loc = $paquete->localizacionPaquete;
                return [
                    'id'             => (int) $paquete->id,
                    'codigo'         => (string) $paquete->codigo,
                    'x1'             => (int) $loc->x1,
                    'y1'             => (int) $loc->y1,
                    'x2'             => (int) $loc->x2,
                    'y2'             => (int) $loc->y2,
                    'tipo_contenido' => $paquete->getTipoContenido(),
                    'orientacion'    => $paquete->orientacion ?? 'I',
                ];
            })->values()->toArray();

        $dimensiones = [
            'ancho' => $anchoM,
            'largo' => $largoM,
            'obra'  => $obra->obra,
        ];

        return [
            'ctx'                      => $ctx,
            'localizacionesZonas'     => $localizacionesZonas,
            'localizacionesMaquinas'   => $localizacionesMaquinas,
            'paquetesConLocalizacion'  => $paquetesConLocalizacion,
            'dimensiones'              => $dimensiones,
            'obraActualId'             => $naveId,
            'mapaId'                   => 'mapa-salida-nave-' . $naveId,
        ];
    }

    /**
     * Valida una subetiqueta escaneada para una salida
     */
    public function validarSubetiquetaParaSalida(Request $request)
    {
        try {
            $request->validate([
                'codigo' => 'required|string',
                'salida_id' => 'required|exists:salidas,id'
            ]);

            $codigo = trim($request->codigo);
            $salidaId = $request->salida_id;

            // Buscar la etiqueta por código
            $etiqueta = Etiqueta::where('codigo', $codigo)->first();

            if (!$etiqueta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código no encontrado',
                    'error' => true
                ]);
            }

            // Verificar que la etiqueta pertenece a un paquete de esta salida
            $paquete = Paquete::whereHas('salidas', function ($q) use ($salidaId) {
                $q->where('salidas.id', $salidaId);
            })
            ->where('id', $etiqueta->paquete_id)
            ->with(['planilla.obra', 'etiquetas'])
            ->first();

            if (!$paquete) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta etiqueta no pertenece a ningún paquete de la salida',
                    'error' => true
                ]);
            }

            return response()->json([
                'success' => true,
                'etiqueta' => [
                    'id' => $etiqueta->id,
                    'codigo' => $etiqueta->codigo,
                    'numero_etiqueta' => $etiqueta->numero_etiqueta,
                ],
                'paquete' => [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'peso' => $paquete->peso,
                    'obra' => $paquete->planilla?->obra?->obra ?? 'N/A',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al validar subetiqueta', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al validar la etiqueta',
                'error' => true
            ], 500);
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


            try {
                $this->generarYEnviarTrazabilidad($salida);
            } catch (\Throwable $e) {
                Log::warning('⚠️ Trazabilidad no enviada, se continúa con la salida', [
                    'salida_id' => $salida->id,
                    'codigo_salida' => $salida->codigo_salida,
                    'error' => $e->getMessage(),
                ]);
            }
            $salida->estado = 'completada';
            $salida->save();
            $movimiento->update([
                'estado' => 'completado',
                'fecha_ejecucion' => now(),
                'ejecutado_por' => auth()->id()
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Movimiento y salida completados.'
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

    public function store(Request $request, ActionLoggerService $logger)
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

            // Obtener información de los paquetes para el log
            $paquetes = Paquete::with('planilla.obra', 'planilla.cliente')->whereIn('id', $request->paquete_ids)->get();
            $codigosPaquetes = $paquetes->pluck('codigo')->implode(', ');
            $obras = $paquetes->pluck('planilla.obra.obra')->unique()->filter()->implode(', ');
            $clientes = $paquetes->pluck('planilla.cliente.empresa')->unique()->filter()->implode(', ');

            $logger->logGestionarSalidas('salida_creada', [
                'codigo_salida' => $codigo_salida,
                'fecha_salida' => Carbon::parse($fechaSalida)->format('Y-m-d H:i'),
                'cantidad_paquetes' => count($request->paquete_ids),
                'paquetes' => $codigosPaquetes ?: 'N/A',
                'obras' => $obras ?: 'N/A',
                'clientes' => $clientes ?: 'N/A',
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

    public function guardarAsignacionesPaquetes(Request $request, ActionLoggerService $logger)
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

                            // Log to CSV
                            $logger->logGestionarSalidas('paquete_asignado_a_salida', [
                                'paquete_codigo' => $paquete->codigo ?? 'N/A',
                                'salida_codigo' => $salida->codigo ?? 'N/A',
                                'planilla_codigo' => $paquete->planilla->codigo ?? 'N/A',
                                'obra' => $paquete->planilla->obra->obra ?? 'N/A',
                                'cliente' => $paquete->planilla->cliente->empresa ?? 'N/A',
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

                        // Log to CSV
                        $logger->logGestionarSalidas('paquete_removido_de_salida', [
                            'paquete_codigo' => $paquete->codigo ?? 'N/A',
                            'planilla_codigo' => $paquete->planilla->codigo ?? 'N/A',
                            'obra' => $paquete->planilla->obra->obra ?? 'N/A',
                            'cliente' => $paquete->planilla->cliente->empresa ?? 'N/A',
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

    /**
     * Obtiene información de una salida específica y sus paquetes para la gestión individual
     */
    public function informacionPaquetesSalida(Request $request)
    {
        try {
            $salidaId = $request->input('salida_id');
            $mostrarTodos = $request->boolean('mostrar_todos', false);

            Log::info('🔍 Obteniendo información de paquetes para salida', [
                'salida_id' => $salidaId,
                'mostrar_todos' => $mostrarTodos,
            ]);

            // Obtener la salida con sus relaciones
            $salida = Salida::with([
                'salidaClientes.obra:id,obra,cod_obra',
                'salidaClientes.cliente:id,empresa',
                'empresaTransporte:id,nombre',
                'camion:id,modelo',
            ])->findOrFail($salidaId);

            // Función helper para mapear paquetes
            $mapPaquete = function ($paquete) {
                // Mapear etiquetas con sus elementos
                $etiquetas = [];
                if ($paquete->etiquetas) {
                    foreach ($paquete->etiquetas as $etiqueta) {
                        $elementos = [];
                        if ($etiqueta->elementos) {
                            foreach ($etiqueta->elementos as $elemento) {
                                $elementos[] = [
                                    'id' => $elemento->id,
                                    'dimensiones' => $elemento->dimensiones,
                                    'peso' => $elemento->peso,
                                    'longitud' => $elemento->longitud,
                                    'diametro' => $elemento->diametro,
                                ];
                            }
                        }
                        $etiquetas[] = [
                            'id' => $etiqueta->id,
                            'elementos' => $elementos,
                        ];
                    }
                }

                return [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'planilla_id' => $paquete->planilla_id,
                    'peso' => $paquete->peso,
                    'nave' => [
                        'id' => $paquete->nave->id ?? null,
                        'obra' => $paquete->nave->obra ?? null,
                    ],
                    'planilla' => [
                        'id' => $paquete->planilla->id ?? null,
                        'codigo' => $paquete->planilla->codigo ?? null,
                        'obra' => [
                            'id' => $paquete->planilla->obra->id ?? null,
                            'obra' => $paquete->planilla->obra->obra ?? null,
                            'cod_obra' => $paquete->planilla->obra->cod_obra ?? null,
                        ],
                        'cliente' => [
                            'id' => $paquete->planilla->cliente->id ?? null,
                            'empresa' => $paquete->planilla->cliente->empresa ?? null,
                        ],
                    ],
                    'etiquetas' => $etiquetas,
                ];
            };

            // Obtener paquetes asignados a esta salida
            $paquetesAsignados = Paquete::with(['planilla.obra:id,obra,cod_obra', 'planilla.cliente:id,empresa', 'nave:id,obra', 'etiquetas.elementos'])
                ->whereHas('salidas', function ($q) use ($salidaId) {
                    $q->where('salidas.id', $salidaId);
                })
                ->get()
                ->map($mapPaquete);

            // Obtener obras relacionadas con esta salida (desde salidaClientes y paquetes asignados)
            $obrasIds = collect();

            // Obras desde salidaClientes
            $obrasIds = $obrasIds->merge($salida->salidaClientes->pluck('obra_id'));

            // Obras desde paquetes asignados
            $obrasIds = $obrasIds->merge($paquetesAsignados->pluck('planilla.obra.id'));

            $obrasIds = $obrasIds->filter()->unique()->values();

            // Obtener paquetes disponibles según el filtro (solo estado pendiente)
            $queryDisponibles = Paquete::with(['planilla.obra:id,obra,cod_obra', 'planilla.cliente:id,empresa', 'nave:id,obra', 'etiquetas.elementos'])
                ->whereDoesntHave('salidas')
                ->where('estado', 'pendiente');

            if (!$mostrarTodos && $obrasIds->isNotEmpty()) {
                // Solo paquetes de las obras de esta salida
                $queryDisponibles->whereHas('planilla', function ($q) use ($obrasIds) {
                    $q->whereIn('obra_id', $obrasIds);
                });
            }

            $paquetesDisponibles = $queryDisponibles->get()->map($mapPaquete);

            // Obtener todos los paquetes disponibles (para el toggle) - solo estado pendiente
            $paquetesTodos = Paquete::with(['planilla.obra:id,obra,cod_obra', 'planilla.cliente:id,empresa', 'nave:id,obra', 'etiquetas.elementos'])
                ->whereDoesntHave('salidas')
                ->where('estado', 'pendiente')
                ->get()
                ->map($mapPaquete);

            // Extraer listas únicas de obras y planillas para los filtros (de paquetes con estado pendiente)
            $todasObras = $paquetesTodos
                ->pluck('planilla.obra')
                ->filter(fn($o) => !empty($o['id']))
                ->unique(fn($o) => $o['id'])
                ->values();

            $todasPlanillas = $paquetesTodos
                ->pluck('planilla')
                ->filter(fn($p) => !empty($p['id']))
                ->unique(fn($p) => $p['id'])
                ->map(function ($p) {
                    return [
                        'id' => $p['id'],
                        'codigo' => $p['codigo'],
                        'obra_id' => $p['obra']['id'] ?? null,
                    ];
                })->values();

            Log::info('✅ Información de paquetes de salida obtenida', [
                'num_paquetes_asignados' => $paquetesAsignados->count(),
                'num_paquetes_disponibles' => $paquetesDisponibles->count(),
                'num_paquetes_todos' => $paquetesTodos->count(),
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
                    'salida_clientes' => $salida->salidaClientes->map(function ($sc) {
                        return [
                            'obra' => [
                                'id' => $sc->obra->id ?? null,
                                'obra' => $sc->obra->obra ?? null,
                                'cod_obra' => $sc->obra->cod_obra ?? null,
                            ],
                            'cliente' => [
                                'id' => $sc->cliente->id ?? null,
                                'empresa' => $sc->cliente->empresa ?? null,
                            ],
                        ];
                    }),
                ],
                'paquetesAsignados' => $paquetesAsignados,
                'paquetesDisponibles' => $paquetesDisponibles,
                'paquetesTodos' => $paquetesTodos,
                'filtros' => [
                    'obras' => $todasObras,
                    'planillas' => $todasPlanillas,
                    'obrasRelacionadas' => $obrasIds,
                ],
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
    public function guardarPaquetesSalida(Request $request, ActionLoggerService $logger)
    {
        try {
            Log::info('💾 Guardando paquetes de salida', [
                'salida_id' => $request->input('salida_id'),
                'num_paquetes' => count($request->input('paquetes_ids', [])),
            ]);

            $request->validate([
                'salida_id' => 'required|exists:salidas,id',
                'paquetes_ids' => 'present|array',
                'paquetes_ids.*' => 'exists:paquetes,id',
            ]);

            $salidaId = $request->input('salida_id');
            $paquetesIds = $request->input('paquetes_ids', []);

            $salida = Salida::findOrFail($salidaId);

            // Obtener los paquetes actualmente asignados a esta salida
            $paquetesAnteriores = DB::table('salidas_paquetes')
                ->where('salida_id', $salidaId)
                ->pluck('paquete_id')
                ->toArray();

            // Identificar paquetes que se ELIMINAN de la salida (estaban antes, ya no están ahora)
            $paquetesEliminados = array_diff($paquetesAnteriores, $paquetesIds);

            // Cambiar estado a 'pendiente' para paquetes eliminados
            if (!empty($paquetesEliminados)) {
                Paquete::whereIn('id', $paquetesEliminados)
                    ->update(['estado' => 'pendiente']);

                Log::info('📦 Paquetes cambiados a pendiente', [
                    'paquetes_ids' => $paquetesEliminados,
                    'estado' => 'pendiente',
                ]);
            }

            // Eliminar todos los paquetes actuales de esta salida
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

                // Cambiar estado a 'asignado_a_salida' para paquetes asignados
                Paquete::whereIn('id', $paquetesIds)
                    ->update(['estado' => 'asignado_a_salida']);

                Log::info('📦 Paquetes cambiados a asignado_a_salida', [
                    'paquetes_ids' => $paquetesIds,
                    'estado' => 'asignado_a_salida',
                ]);
            }

            // Sincronizar salida_clientes con las obras/clientes de los paquetes asignados
            // (también elimina registros de obras que ya no tienen paquetes)
            $this->sincronizarSalidaClientes($salidaId, $paquetesIds);

            Log::info('✅ Paquetes de salida guardados', [
                'salida_id' => $salidaId,
                'num_paquetes_asignados' => count($insertData),
                'num_paquetes_eliminados' => count($paquetesEliminados),
            ]);

            // Log changes to CSV
            if (!empty($paquetesIds)) {
                $paquetesAsignados = Paquete::with('planilla.obra', 'planilla.cliente')->whereIn('id', $paquetesIds)->get();
                $codigosPaquetes = $paquetesAsignados->pluck('codigo')->implode(', ');

                $logger->logGestionarSalidas('paquetes_salida_actualizados', [
                    'salida_codigo' => $salida->codigo ?? 'N/A',
                    'paquetes_asignados' => count($insertData),
                    'paquetes_removidos' => count($paquetesEliminados),
                    'codigos_paquetes_asignados' => $codigosPaquetes ?: 'N/A',
                ]);
            }

            if (!empty($paquetesEliminados)) {
                $paquetesRemovidosInfo = Paquete::with('planilla.obra', 'planilla.cliente')->whereIn('id', $paquetesEliminados)->get();
                $codigosPaquetesRemovidos = $paquetesRemovidosInfo->pluck('codigo')->implode(', ');

                $logger->logGestionarSalidas('paquetes_devueltos_a_disponibles', [
                    'salida_codigo' => $salida->codigo ?? 'N/A',
                    'cantidad_paquetes' => count($paquetesEliminados),
                    'codigos_paquetes' => $codigosPaquetesRemovidos ?: 'N/A',
                ]);
            }

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

    public function update(Request $request, $id, ActionLoggerService $logger)
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

            // Log the update action
            $logger->logGestionarSalidas('salida_actualizada', [
                'codigo_salida' => $salida->codigo ?? 'N/A',
                'codigo_sage' => $salida->codigo_sage ?? 'N/A',
                'campo' => $field,
                'valor_nuevo' => is_array($value) ? json_encode($value) : $value,
                'cliente_id' => $clienteId ?? 'N/A',
                'obra_id' => $obraId ?? 'N/A',
            ]);

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
            // Español
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
            // Inglés (para compatibilidad)
            'january' => 'January',
            'february' => 'February',
            'march' => 'March',
            'april' => 'April',
            'may' => 'May',
            'june' => 'June',
            'july' => 'July',
            'august' => 'August',
            'september' => 'September',
            'october' => 'October',
            'november' => 'November',
            'december' => 'December',
        ];

        try {
            // 🔹 Extraer el nombre del mes (sin el año)
            preg_match('/([a-zA-Záéíóú]+)/', $mes, $matches);
            $mesSolo = strtolower($matches[1] ?? '');

            // 🔹 Validar si el mes es válido
            if (!isset($meses[$mesSolo])) {
                return redirect()->route('salidas-ferralla.index')->with('error', "Mes no válido: $mes");
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
                return redirect()->route('salidas-ferralla.index')->with('error', "No hay salidas registradas en $mesSolo $anio.");
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
            return redirect()->route('salidas-ferralla.index')->with('error', 'Hubo un problema al exportar las salidas: ' . $e->getMessage());
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

    public function destroy($id, ActionLoggerService $logger)
    {
        try {
            // Buscar la salida o lanzar excepción si no existe
            $salida = Salida::findOrFail($id);

            // Store data for logging before deletion
            $codigoSalida = $salida->codigo ?? 'N/A';
            $codigoSage = $salida->codigo_sage ?? 'N/A';
            $numPaquetes = $salida->paquetes->count();

            // Liberar los paquetes asignados (eliminar relaciones de la tabla pivot)
            $salida->paquetes()->detach();

            // Eliminar la salida
            $salida->delete();

            // Log the deletion
            $logger->logGestionarSalidas('salida_eliminada', [
                'codigo_salida' => $codigoSalida,
                'codigo_sage' => $codigoSage,
                'paquetes_liberados' => $numPaquetes,
            ]);

            // Si es petición AJAX, devolver JSON
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Salida eliminada correctamente.'
                ]);
            }

            return redirect()->route('salidas-ferralla.index')
                ->with('success', 'Salida eliminada correctamente.');
        } catch (\Exception $e) {
            // Si es petición AJAX, devolver JSON con error
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hubo un problema al eliminar la salida: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('salidas-ferralla.index')
                ->with('error', 'Hubo un problema al eliminar la salida: ' . $e->getMessage());
        }
    }

    /**
     * Muestra la vista para gestionar salidas y paquetes de planillas agrupadas
     */
    public function gestionarSalidas(Request $request)
    {
        $planillasIds = explode(',', $request->get('planillas', ''));
        $mostrarTodosPaquetes = $request->get('todos_paquetes', '0') === '1'; // Toggle para mostrar todos los paquetes

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

        // Obtener las obras y clientes de las planillas
        $obrasIds = $planillas->pluck('obra_id')->unique()->filter();
        $clientesIds = $planillas->pluck('cliente_id')->unique()->filter();

        Log::info('🔍 Buscando salidas', [
            'obras_ids' => $obrasIds->toArray(),
            'clientes_ids' => $clientesIds->toArray(),
        ]);

        // Buscar SOLO salidas con estado pendiente para estas obras/clientes
        $salidasExistentes = Salida::with([
                'paquetes.planilla.obra',
                'paquetes.planilla.cliente',
                'paquetes.nave',
                'paquetes.etiquetas.elementos',
                'empresaTransporte',
                'camion',
                'obras',
                'clientes',
                'salidaClientes.obra',
                'salidaClientes.cliente'
            ])
            ->where('estado', 'pendiente')
            ->where(function($query) use ($obrasIds, $clientesIds) {
                // Salidas con obras específicas
                $query->whereHas('obras', function ($q) use ($obrasIds) {
                    $q->whereIn('obras.id', $obrasIds);
                })
                // O salidas con clientes específicos
                ->orWhereHas('clientes', function ($q) use ($clientesIds) {
                    $q->whereIn('clientes.id', $clientesIds);
                });
            })
            ->orderBy('fecha_salida', 'asc')
            ->get();

        Log::info('✅ Salidas encontradas (solo pendientes)', [
            'cantidad' => $salidasExistentes->count(),
            'salidas' => $salidasExistentes->pluck('codigo_salida')->toArray(),
        ]);

        // Obtener AMBOS conjuntos de paquetes para filtrado dinámico sin recarga
        // 1. Paquetes de las planillas seleccionadas (obra/cliente específico)
        $paquetesFiltrados = Paquete::with(['planilla.obra', 'planilla.cliente', 'nave', 'etiquetas.elementos'])
            ->whereIn('planilla_id', $planillasIds)
            ->where('estado', 'pendiente')
            ->get();

        // 2. TODOS los paquetes pendientes disponibles
        $paquetesTodos = Paquete::with(['planilla.obra', 'planilla.cliente', 'nave', 'etiquetas.elementos'])
            ->where('estado', 'pendiente')
            ->whereDoesntHave('salidas') // No asignados a ninguna salida
            ->get();

        Log::info('📦 Cargando paquetes para filtrado dinámico', [
            'paquetes_filtrados' => $paquetesFiltrados->count(),
            'paquetes_todos' => $paquetesTodos->count(),
        ]);

        // El conjunto inicial depende del toggle
        $paquetesDisponibles = $mostrarTodosPaquetes ? $paquetesTodos : $paquetesFiltrados;

        // Obtener empresas y camiones para los formularios
        $empresas = EmpresaTransporte::all();
        $camiones = Camion::with('empresaTransporte')->get();

        return view('salidas.gestionar-salidas', [
            'planillas' => $planillas,
            'salidasExistentes' => $salidasExistentes,
            'paquetesDisponibles' => $paquetesDisponibles, // Los que se muestran inicialmente
            'paquetesFiltrados' => $paquetesFiltrados, // Para JavaScript - solo de obra/cliente
            'paquetesTodos' => $paquetesTodos, // Para JavaScript - todos pendientes
            'empresas' => $empresas,
            'camiones' => $camiones,
            'mostrarTodosPaquetes' => $mostrarTodosPaquetes, // Pasar el estado del toggle
            'obrasIds' => $obrasIds,
            'clientesIds' => $clientesIds,
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
            $planillas = Planilla::with('obra.cliente')->whereIn('id', $planillasIds)->get();

            // Recopilar todas las obras y clientes únicos de las planillas
            $obrasClientesUnicos = collect();
            foreach ($planillas as $planilla) {
                if ($planilla->obra_id) {
                    $obrasClientesUnicos->push([
                        'obra_id' => $planilla->obra_id,
                        'cliente_id' => $planilla->cliente_id ?? $planilla->obra->cliente_id ?? null,
                    ]);
                }
            }
            // Eliminar duplicados por obra_id
            $obrasClientesUnicos = $obrasClientesUnicos->unique('obra_id');

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

                // Asociar la salida con TODAS las obras y clientes de las planillas
                foreach ($obrasClientesUnicos as $oc) {
                    SalidaCliente::create([
                        'salida_id' => $salida->id,
                        'cliente_id' => $oc['cliente_id'],
                        'obra_id' => $oc['obra_id'],
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

    /**
     * Sincroniza la tabla salida_clientes con las obras/clientes de los paquetes asignados.
     * Crea registros nuevos si no existen para las combinaciones cliente/obra de los paquetes.
     *
     * @param int $salidaId
     * @param array $paquetesIds
     * @return void
     */
    private function sincronizarSalidaClientes(int $salidaId, array $paquetesIds): void
    {
        if (empty($paquetesIds)) {
            return;
        }

        // Obtener las combinaciones únicas de cliente/obra de los paquetes asignados
        $paquetes = Paquete::with('planilla.obra', 'planilla.cliente')
            ->whereIn('id', $paquetesIds)
            ->get();

        $obrasClientesUnicos = [];
        foreach ($paquetes as $paquete) {
            if ($paquete->planilla && $paquete->planilla->obra_id && $paquete->planilla->cliente_id) {
                $clave = $paquete->planilla->cliente_id . '-' . $paquete->planilla->obra_id;
                if (!isset($obrasClientesUnicos[$clave])) {
                    $obrasClientesUnicos[$clave] = [
                        'cliente_id' => $paquete->planilla->cliente_id,
                        'obra_id' => $paquete->planilla->obra_id,
                    ];
                }
            }
        }

        // Obtener los registros existentes en salida_clientes para esta salida
        $existentes = SalidaCliente::where('salida_id', $salidaId)
            ->get()
            ->keyBy(function ($item) {
                return $item->cliente_id . '-' . $item->obra_id;
            });

        // Crear registros nuevos para las combinaciones que no existen
        $nuevosRegistros = 0;
        foreach ($obrasClientesUnicos as $clave => $datos) {
            if (!$existentes->has($clave)) {
                SalidaCliente::create([
                    'salida_id' => $salidaId,
                    'cliente_id' => $datos['cliente_id'],
                    'obra_id' => $datos['obra_id'],
                ]);
                $nuevosRegistros++;
            }
        }

        // Eliminar registros que ya no tienen paquetes de esa obra/cliente
        $registrosEliminados = 0;
        foreach ($existentes as $clave => $registro) {
            if (!isset($obrasClientesUnicos[$clave])) {
                $registro->delete();
                $registrosEliminados++;
            }
        }

        if ($nuevosRegistros > 0 || $registrosEliminados > 0) {
            Log::info('🔄 Sincronizados salida_clientes', [
                'salida_id' => $salidaId,
                'nuevos_registros' => $nuevosRegistros,
                'registros_eliminados' => $registrosEliminados,
                'obras_clientes' => array_values($obrasClientesUnicos),
            ]);
        }
    }
}
