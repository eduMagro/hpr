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
            $salidas = Salida::with([
                'paquetes.subpaquetes',
                'paquetes.elementos',
                'paquetes.planilla',
                'empresaTransporte',
                'camion',
                'clientes'  // Relación many-to-many a través de la tabla pivote
            ])->get();
        } else {
            $salidas = Salida::where('estado', 'pendiente')
                ->with([
                    'paquetes.subpaquetes',
                    'paquetes.elementos',
                    'empresaTransporte',
                    'camion',
                    'clientes'
                ])->get();
        }

        // Procesar cada salida para extraer planillas, clientes y obras (como hacías originalmente)
        foreach ($salidas as $salida) {
            // Obtener planillas únicas asociadas a la salida
            $salida->planillasUnicas = $salida->paquetes
                ->pluck('planilla')
                ->unique()
                ->filter()
                ->values();

            // Extraer clientes únicos a nivel de salida (por ejemplo, a partir de planillas)
            $salida->clientesUnicos = collect($salida->planillasUnicas ?? [])
                ->map(fn($planilla) => $planilla->cliente)
                ->unique()
                ->filter()
                ->values();

            // Extraer obras únicas a nivel de salida
            $salida->obrasUnicas = collect($salida->planillasUnicas ?? [])
                ->map(fn($planilla) => $planilla->nom_obra)
                ->unique()
                ->filter()
                ->values();
        }

        // Agrupar las salidas por mes para la tabla principal
        $salidasPorMes = $salidas->groupBy(function ($salida) {
            return \Carbon\Carbon::parse($salida->fecha_salida)->translatedFormat('F Y');
        });

        // Obtener los registros de la tabla pivote usando Eloquent
        // Con el modelo SalidaCliente ya podemos traer la relación con salida y cliente.
        $salidaClientes = SalidaCliente::with(['salida', 'cliente'])->get();

        return view('salidas.index', compact('salidasPorMes', 'salidas', 'salidaClientes'));
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
            // Se busca la salida por ID
            $salida = Salida::findOrFail($id);

            // Se obtiene el campo y el valor enviado desde la petición (inline edit)
            $field = $request->input('field');
            $value = $request->input('value');

            // Solo se permiten actualizar estos campos en línea
            $allowedFields = [
                'importe',
                'horas_paralizacion',
                'importe_paralizacion',
                'horas_grua',
                'importe_grua',
                'horas_almacen',
                'fecha_salida',
                'estado'
            ];

            if (!in_array($field, $allowedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo especificado no es editable en línea.'
                ], 422);
            }

            // Reglas de validación para cada campo editable
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

            // Mensajes de error personalizados para cada regla
            $messages = [
                'importe.numeric'              => 'El campo importe debe ser un número.',
                'horas_paralizacion.numeric'   => 'El campo horas de paralización debe ser un número.',
                'importe_paralizacion.numeric' => 'El campo importe de paralización debe ser un número.',
                'horas_grua.numeric'           => 'El campo horas de grua debe ser un número.',
                'importe_grua.numeric'         => 'El campo importe de grua debe ser un número.',
                'horas_almacen.numeric'        => 'El campo horas/almacén debe ser un número.',
                'fecha_salida.date'            => 'El campo fecha debe ser una fecha válida.',
                'estado.string'                => 'El campo estado debe ser una cadena de texto.',
                'estado.max'                   => 'El campo estado no debe tener más de 50 caracteres.',
            ];

            // Se valida el valor enviado para el campo específico
            $request->validate([
                $field => $rules[$field]
            ], [
                $field . '.numeric' => $messages[$field . '.numeric'] ?? '',
                $field . '.date'    => $messages[$field . '.date'] ?? '',
                $field . '.string'  => $messages[$field . '.string'] ?? '',
                $field . '.max'     => $messages[$field . '.max'] ?? '',
            ]);

            // Si el campo es 'fecha_salida' y se envía un valor, se convierte el formato a 'Y-m-d'
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

            // Se asigna el nuevo valor al campo y se guarda la salida
            $salida->$field = $value;
            $salida->save();

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
            // Convertir el mes en español a inglés (convertir a minúsculas para evitar problemas de mayúsculas/minúsculas)
            foreach ($meses as $esp => $eng) {
                $mes = str_replace($esp, $eng, strtolower($mes));
            }
            // Filtra las salidas del mes correspondiente
            $salidas = Salida::whereMonth('fecha_salida', Carbon::parse($mes)->month)
                ->whereYear('fecha_salida', Carbon::parse($mes)->year)
                ->get();

            // Procesa el resumen por cliente, similar a la vista.
            $clientSummary = [];
            foreach ($salidas as $salida) {
                $importe = $salida->importe ?? 0;
                if (is_null($salida->clientesUnicos)) {
                    $salida->clientesUnicos = collect();
                    foreach ($salida->clientesUnicos as $cliente) {
                        if ($cliente) {
                            if (!isset($clientSummary[$cliente])) {
                                $clientSummary[$cliente] = 0;
                            }
                            $clientSummary[$cliente] += $importe;
                        }
                    }
                }
            }

            DB::commit(); // Confirmar la transacción
            // Usa Excel::download() para generar y retornar el archivo
            return Excel::download(new SalidasExport($salidas, $clientSummary), 'salidas_' . $mes . '.xlsx');
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
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
