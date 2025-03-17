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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class SalidaController extends Controller
{
    public function index(Request $request)
    {
        // Cargar relaciones según el rol del usuario, incluyendo la relación 'clientes'
        if (auth()->user()->rol == 'oficina') {
            // Obtener todas las salidas con sus clientes asociados
            $salidas = Salida::with(['clientes', 'paquetes.planilla.obra', 'empresaTransporte', 'camion']) // Cargar relaciones necesarias
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $salidas = Salida::where('estado', 'pendiente')
                ->with([
                    'paquetes' => function ($query) {
                        $query->distinct();
                    },
                    'paquetes.subpaquetes',
                    'paquetes.elementos',
                    'empresaTransporte',
                    'camion',
                    'clientes'
                ])
                ->get();
        }
        // 🔹 Asignar obras específicas para cada cliente en cada salida
        $salidas->each(function ($salida) {
            // 🔹 Para cada cliente en la salida, filtrar solo las obras de sus paquetes
            $salida->clientes->each(function ($cliente) use ($salida) {
                $cliente->obrasUnicas = $salida->paquetes
                    ->where('planilla.cliente_id', $cliente->id) // 🔹 Filtrar solo las obras del cliente
                    ->pluck('planilla.obra.obra')
                    ->unique()
                    ->filter()
                    ->values();
            });
        });
        // Agrupar las salidas por mes para la tabla principal
        $salidasPorMes = $salidas->groupBy(function ($salida) {
            return \Carbon\Carbon::parse($salida->fecha_salida)->translatedFormat('F Y');
        });
        // Crear un array para almacenar los resúmenes de cada mes
        $resumenMensual = [];

        foreach ($salidasPorMes as $mes => $salidasGrupo) {
            $clientSummary = [];

            foreach ($salidasGrupo as $salida) {
                foreach ($salida->clientes as $cliente) {
                    // Asegurar que el nombre del cliente no esté vacío
                    $nombreCliente = trim($cliente->empresa) ?: "Cliente desconocido";

                    if (!isset($clientSummary[$nombreCliente])) {
                        $clientSummary[$nombreCliente] = [
                            'horas_paralizacion'   => 0,
                            'importe_paralizacion' => 0,
                            'horas_grua'           => 0,
                            'importe_grua'         => 0,
                            'horas_almacen'        => 0,
                            'importe'              => 0,
                            'total'                => 0,
                        ];
                    }

                    // Acumular valores por cliente
                    $clientSummary[$nombreCliente]['horas_paralizacion']   += $cliente->pivot->horas_paralizacion ?? 0;
                    $clientSummary[$nombreCliente]['importe_paralizacion'] += $cliente->pivot->importe_paralizacion ?? 0;
                    $clientSummary[$nombreCliente]['horas_grua']           += $cliente->pivot->horas_grua ?? 0;
                    $clientSummary[$nombreCliente]['importe_grua']         += $cliente->pivot->importe_grua ?? 0;
                    $clientSummary[$nombreCliente]['horas_almacen']        += $cliente->pivot->horas_almacen ?? 0;
                    $clientSummary[$nombreCliente]['importe']              += $cliente->pivot->importe ?? 0;

                    // Calcular el total sumando todos los importes
                    $clientSummary[$nombreCliente]['total'] =
                        $clientSummary[$nombreCliente]['importe_paralizacion'] +
                        $clientSummary[$nombreCliente]['importe_grua'] +
                        $clientSummary[$nombreCliente]['importe'];
                }
            }

            // Guardamos el resumen en un array asociado a cada mes
            $resumenMensual[$mes] = $clientSummary;
        }

        return view('salidas.index', compact('salidasPorMes', 'salidas', 'resumenMensual'));
    }

    public function show($id)
    {
        // Obtener la salida con su ID
        $salida = Salida::findOrFail($id);

        // Obtener los paquetes asociados con los elementos y subpaquetes
        $paquetes = $salida->paquetes()->with(['elementos', 'subpaquetes'])->get();

        // Pasar la salida y los paquetes a la vista
        return view('salidas.show', compact('salida', 'paquetes'));
    }

    public function actualizarEstado(Request $request, $salidaId)
    {
        try {
            $salida = Salida::findOrFail($salidaId);

            // Verificamos que el estado actual sea pendiente antes de cambiarlo a completado
            if ($salida->estado != 'pendiente') {
                return response()->json(['message' => 'El estado de la salida ya ha sido actualizado.'], 400);
            }

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


    public function create()
    {
        // Obtener planillas COMPLETADAS con los paquetes, sus elementos y subpaquetes
        $planillasCompletadas = Planilla::where('estado', 'completada')
            ->with(['paquetes' => function ($query) {
                // Filtrar solo los paquetes que NO tienen salida asociada
                $query->whereDoesntHave('salidas');
            }, 'paquetes.elementos', 'paquetes.subpaquetes'])  // Incluir subpaquetes y elementos
            ->whereHas('paquetes', function ($query) {
                // Asegurarnos de que la planilla tenga al menos un paquete sin salida asociada
                $query->whereDoesntHave('salidas');
            })
            ->orderBy('fecha_estimada_entrega', 'asc') // Ordenar por fecha estimada de entrega
            ->get();

        // Obtener los paquetes de las planillas (filtrados previamente)
        $paquetes = $planillasCompletadas->pluck('paquetes')->flatten();

        // Obtener las empresas con sus camiones
        $empresas = EmpresaTransporte::with('camiones')->get();

        // Pasar planillas, paquetes disponibles, elementos y subpaquetes a la vista
        return view('salidas.create', [
            'planillasCompletadas' => $planillasCompletadas,
            'paquetes' => $paquetes,
            'empresas' => $empresas,
        ]);
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'camion_id'   => 'required|exists:camiones,id',
                'paquete_ids' => 'required|array',
                'paquete_ids.*' => 'exists:paquetes,id',
            ], [
                'camion_id.required'   => 'Por favor, seleccione un camión.',
                'camion_id.exists'     => 'El camión seleccionado no existe en el sistema.',
                'paquete_ids.required' => 'Debe seleccionar al menos un paquete.',
                'paquete_ids.array'    => 'Los paquetes seleccionados no son válidos.',
                'paquete_ids.*.exists' => 'Uno o más paquetes seleccionados no existen en el sistema.',
            ]);

            // Comprobar si alguno de los paquetes seleccionados ya está asociado a una salida
            $paquetesRepetidos = DB::table('salidas_paquetes')
                ->whereIn('paquete_id', $request->paquete_ids)
                ->whereNotNull('salida_id')
                ->pluck('paquete_id')
                ->toArray();

            $repetidos = array_intersect($request->paquete_ids, $paquetesRepetidos);
            if ($repetidos) {
                return back()->withErrors(['paquete_ids' => 'Los siguientes paquetes ya están asociados a una salida: ' . implode(', ', $repetidos)]);
            }

            // Obtener el camión y la empresa de transporte asociada
            $camion = Camion::find($request->camion_id);
            $empresa = $camion->empresaTransporte;

            // Crear la salida
            $salida = Salida::create([
                'empresa_id'   => $empresa->id,
                'camion_id'    => $request->camion_id,
                'fecha_salida' => null,
                'estado'       => 'pendiente', // Estado por defecto
            ]);

            // Generar el código de salida y asignarlo
            $codigo_salida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);
            $salida->codigo_salida = $codigo_salida;
            $salida->save();

            // Asociar los paquetes a la salida (se llenará la tabla salidas_paquetes)
            foreach ($request->paquete_ids as $paquete_id) {
                $salida->paquetes()->attach($paquete_id);
            }

            /*
             * Para la asociación salida_cliente:
             * Se recorre cada paquete seleccionado y se carga la relación anidada:
             * planilla.obra.cliente.
             * Si se encuentra el cliente, se añade a un arreglo de IDs únicos.
             */
            // Asociar los paquetes a la salida (se llenará la tabla salidas_paquetes)
            foreach ($request->paquete_ids as $paquete_id) {
                $salida->paquetes()->attach($paquete_id);
            }

            // Obtener los clientes de la planilla (ya que planilla tiene cliente_id)
            $uniqueClientIds = [];
            foreach ($request->paquete_ids as $paquete_id) {
                // Cargamos la relación anidada para tener acceso a la planilla
                $paquete = \App\Models\Paquete::with('planilla')->find($paquete_id);
                if (!$paquete) {
                    Log::info("Paquete {$paquete_id} no encontrado");
                } elseif (!$paquete->planilla) {
                    Log::info("Paquete {$paquete_id} no tiene planilla");
                } elseif (!$paquete->planilla->cliente_id) {
                    Log::info("Paquete {$paquete_id} tiene planilla, pero no tiene cliente asociado");
                } else {
                    Log::info("Paquete {$paquete_id} OK: Cliente ID " . $paquete->planilla->cliente_id);
                    $uniqueClientIds[$paquete->planilla->cliente_id] = true;
                }
            }

            $clientIds = array_keys($uniqueClientIds);
            Log::info('Unique client IDs: ' . implode(', ', $clientIds));

            // Si se han obtenido clientes, asociarlos en la tabla pivote salida_cliente
            if (!empty($clientIds)) {
                $salida->clientes()->attach($clientIds, [
                    'horas_paralizacion'   => 0,
                    'importe_paralizacion' => 0,
                    'horas_grua'           => 0,
                    'importe_grua'         => 0,
                    'horas_almacen'        => 0,
                    'importe'              => 0,
                ]);
            } else {
                Log::warning('No se encontraron clientes para asociar a la salida.');
            }


            return redirect()->route('salidas.index')->with('success', 'Salida creada con éxito');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Hubo un problema al crear la salida: ' . $e->getMessage()]);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            // Buscar la salida por ID
            $salida = Salida::findOrFail($id);

            // Obtener campo y valor desde la petición
            $field = $request->input('field');
            $value = $request->input('value');
            $clienteId = $request->input('cliente_id'); // Para actualización en salida_cliente

            // Definir campos para cada tabla
            $salidaFields = ['fecha_salida', 'estado'];
            $salidaClienteFields = [
                'importe',
                'horas_paralizacion',
                'importe_paralizacion',
                'horas_grua',
                'importe_grua',
                'horas_almacen'
            ];

            if (!in_array($field, array_merge($salidaFields, $salidaClienteFields))) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo especificado no es editable en línea.'
                ], 422);
            }

            // Validaciones
            $rules = [
                'importe'              => 'nullable|numeric',
                'horas_paralizacion'   => 'nullable|numeric',
                'importe_paralizacion' => 'nullable|numeric',
                'horas_grua'           => 'nullable|numeric',
                'importe_grua'         => 'nullable|numeric',
                'horas_almacen'        => 'nullable|numeric',
                'fecha_salida'         => 'nullable|date',
                'estado'               => 'nullable|string|max:50',
            ];

            $request->validate([$field => $rules[$field]]);

            // Si el campo es 'fecha_salida', formatear la fecha correctamente
            if ($field === 'fecha_salida' && !empty($value)) {
                try {
                    $value = Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de fecha no válido.'
                    ], 422);
                }
            }

            // **Actualizar en la tabla 'salidas'**
            if (in_array($field, $salidaFields)) {
                $salida->$field = $value;
                $salida->save();
                Log::info("Salida {$salida->id} actualizada: {$field} -> {$value}");
            }
            // **Actualizar en la tabla 'salida_cliente'**
            elseif (in_array($field, $salidaClienteFields)) {
                if (!$clienteId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falta el ID del cliente para actualizar el campo.'
                    ], 422);
                }

                // Verificar si la relación existe antes de actualizar
                $updated = DB::table('salida_cliente')
                    ->where('salida_id', $salida->id)
                    ->where('cliente_id', $clienteId)
                    ->update([$field => $value]);

                if ($updated) {
                    Log::info("Salida_cliente actualizada: salida_id={$salida->id}, cliente_id={$clienteId}, {$field} -> {$value}");
                } else {
                    Log::warning("Intento fallido de actualización en salida_cliente: salida_id={$salida->id}, cliente_id={$clienteId}, campo={$field}");
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo actualizar el campo en salida_cliente.'
                    ], 422);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Salida actualizada correctamente.',
                'data'    => $salida
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la salida.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function export($mes)
    {
        $meses = [
            'enero'      => 'January',
            'febrero'    => 'February',
            'marzo'      => 'March',
            'abril'      => 'April',
            'mayo'       => 'May',
            'junio'      => 'June',
            'julio'      => 'July',
            'agosto'     => 'August',
            'septiembre' => 'September',
            'octubre'    => 'October',
            'noviembre'  => 'November',
            'diciembre'  => 'December',
        ];

        try {
            // 🔹 Extraer solo el nombre del mes (eliminar el año si está presente)
            preg_match('/([a-zA-Záéíóú]+)/', $mes, $matches);
            $mesSolo = strtolower($matches[1] ?? '');

            // 🔹 Validar si el mes está en el array
            if (!isset($meses[$mesSolo])) {
                return redirect()->route('salidas.index')->with('error', "Mes no válido: $mes");
            }

            $mesIngles = $meses[$mesSolo];

            // 🔹 Extraer el año de la variable `$mes`
            preg_match('/(\d{4})/', $mes, $yearMatch);
            $anio = $yearMatch[1] ?? Carbon::now()->year;

            // 🔹 Obtener el número del mes con Carbon
            $numeroMes = Carbon::parse("1 $mesIngles")->month;

            // 🔹 Obtener salidas con sus relaciones
            $salidas = Salida::whereMonth('fecha_salida', $numeroMes)
                ->whereYear('fecha_salida', $anio)
                ->with([
                    'clientes',
                    'empresaTransporte',
                    'camion',
                    'paquetes.planilla.obra' // 🔹 Cargar la relación de obras
                ])
                ->get();

            if ($salidas->isEmpty()) {
                return redirect()->route('salidas.index')->with('error', "No hay salidas registradas en $mesSolo $anio.");
            }

            // 🔹 Generar resumen por cliente y obras
            $clientSummary = [];

            foreach ($salidas as $salida) {
                foreach ($salida->clientes as $cliente) {
                    $nombreCliente = trim($cliente->empresa) ?: "Cliente desconocido";

                    if (!isset($clientSummary[$nombreCliente])) {
                        $clientSummary[$nombreCliente] = [
                            'obras'                => collect(),
                            'horas_paralizacion'   => 0,
                            'importe_paralizacion' => 0,
                            'horas_grua'           => 0,
                            'importe_grua'         => 0,
                            'horas_almacen'        => 0,
                            'importe'              => 0,
                            'total'                => 0,
                        ];
                    }

                    // 🔹 Filtrar solo las obras del cliente
                    $obrasCliente = $salida->paquetes
                        ->where('planilla.cliente_id', $cliente->id)
                        ->pluck('planilla.obra.nombre')
                        ->unique()
                        ->filter()
                        ->values();

                    // 🔹 Acumular las obras del cliente
                    $clientSummary[$nombreCliente]['obras'] = $clientSummary[$nombreCliente]['obras']->merge($obrasCliente)->unique();

                    // 🔹 Acumular valores del cliente
                    $clientSummary[$nombreCliente]['horas_paralizacion']   += $cliente->pivot->horas_paralizacion ?? 0;
                    $clientSummary[$nombreCliente]['importe_paralizacion'] += $cliente->pivot->importe_paralizacion ?? 0;
                    $clientSummary[$nombreCliente]['horas_grua']           += $cliente->pivot->horas_grua ?? 0;
                    $clientSummary[$nombreCliente]['importe_grua']         += $cliente->pivot->importe_grua ?? 0;
                    $clientSummary[$nombreCliente]['horas_almacen']        += $cliente->pivot->horas_almacen ?? 0;
                    $clientSummary[$nombreCliente]['importe']              += $cliente->pivot->importe ?? 0;

                    // 🔹 Calcular el total sumando todos los importes
                    $clientSummary[$nombreCliente]['total'] =
                        $clientSummary[$nombreCliente]['importe_paralizacion'] +
                        $clientSummary[$nombreCliente]['importe_grua'] +
                        $clientSummary[$nombreCliente]['importe'];
                }
            }

            // 🔹 Convertir las obras en cadenas de texto para exportar correctamente
            foreach ($clientSummary as $cliente => &$data) {
                $data['obras'] = $data['obras']->implode(', ');
            }

            return Excel::download(new SalidasExport($salidas, $clientSummary), "salidas_{$mesSolo}_{$anio}.xlsx");
        } catch (\Exception $e) {
            return redirect()->route('salidas.index')->with('error', 'Hubo un problema al exportar las salidas: ' . $e->getMessage());
        }
    }


    public function marcarSubido(Request $request)
    {
        $codigo = $request->codigo;

        // Buscar en paquetes, etiquetas o elementos
        $paquete = Paquete::where('id', $codigo)->first();
        $etiqueta = Etiqueta::where('id', $codigo)->first();
        $elemento = Elemento::where('id', $codigo)->first();

        if ($paquete) {
            $paquete->subido = true;
            $paquete->save();
            return response()->json(['success' => true, 'mensaje' => 'Paquete marcado como subido.']);
        }

        if ($etiqueta) {
            $etiqueta->subido = true;
            $etiqueta->save();
            return response()->json(['success' => true, 'mensaje' => 'Etiqueta marcada como subida.']);
        }

        if ($elemento) {
            $elemento->subido = true;
            $elemento->save();
            return response()->json(['success' => true, 'mensaje' => 'Elemento marcado como subido.']);
        }

        return response()->json(['success' => false, 'mensaje' => 'Código no encontrado.'], 404);
    }
}
