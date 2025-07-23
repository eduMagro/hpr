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
                $nombreEmpresa = trim($salida->empresaTransporte->nombre) ?: "Empresa desconocida";
                $empresaId = $salida->empresaTransporte->id;
                if (!isset($empresaSummary[$nombreEmpresa])) {
                    $empresaSummary[$nombreEmpresa] = [
                        'empresa_id'          => $empresaId,
                        'horas_paralizacion'  => 0,
                        'importe_paralizacion' => 0,
                        'horas_grua'          => 0,
                        'importe_grua'        => 0,
                        'horas_almacen'       => 0,
                        'importe'             => 0,
                        'total'               => 0,
                    ];
                }
                // Ahora se suma desde cada registro en salidaClientes
                foreach ($salida->salidaClientes as $registro) {
                    $empresaSummary[$nombreEmpresa]['horas_paralizacion']   += $registro->horas_paralizacion ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe_paralizacion'] += $registro->importe_paralizacion ?? 0;
                    $empresaSummary[$nombreEmpresa]['horas_grua']           += $registro->horas_grua ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe_grua']         += $registro->importe_grua ?? 0;
                    $empresaSummary[$nombreEmpresa]['horas_almacen']        += $registro->horas_almacen ?? 0;
                    $empresaSummary[$nombreEmpresa]['importe']              += $registro->importe ?? 0;
                }
            }
            foreach ($empresaSummary as $nombreEmpresa => &$data) {
                $data['total'] = $data['importe_paralizacion'] + $data['importe_grua'] + $data['importe'];
            }
            $resumenMensual[$mes] = $empresaSummary;
        }

        return view('salidas.index', compact('salidasPorMes', 'salidas', 'resumenMensual', 'paquetes'));
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
                'cliente'  => $clienteName,
                'obra'     => $obraName,
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
                return response()->json(['message' => 'La salida ya estaba completada de antes.'], 400);
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


  public function create(Request $request)
{
    // Si se han pasado planillas desde el calendario, usamos solo esas
    $planillasIds = explode(',', $request->get('planillas', ''));

    // Base del query
    $planillasQuery = Planilla::with([
        'paquetes' => function ($query) {
            // Filtramos paquetes sin salida
            $query->whereDoesntHave('salidas');
        },
        'paquetes.etiquetas',
        'cliente',
        'obra'
    ]);

    // Si se recibieron planillas por parámetro, filtramos
    if (!empty($planillasIds[0])) {
        $planillasQuery->whereIn('id', $planillasIds);
    }

    // Obtener las planillas
    $planillasCompletadas = $planillasQuery
        ->orderBy('fecha_estimada_entrega', 'asc')
        ->get()
        ->map(function ($planilla) {
            // Aquí definimos colores y etiquetas según estado
            $estado = $planilla->estado;
            $colorClass = match ($estado) {
                'completada' => 'bg-green-500 text-white',
                'pendiente'  => 'bg-yellow-500 text-black',
                'en_proceso' => 'bg-blue-500 text-white',
                default      => 'bg-gray-400 text-white',
            };

            // Le añadimos atributos dinámicos que luego usarás en Blade
            $planilla->estado_label = ucfirst($estado);
            $planilla->estado_class = $colorClass;

            return $planilla;
        });

    // Obtener paquetes
    $paquetes = $planillasCompletadas->pluck('paquetes')->flatten();

    // Empresas con camiones
    $empresas = EmpresaTransporte::with('camiones')->get();

    return view('salidas.create', [
        'planillasCompletadas' => $planillasCompletadas,
        'paquetes'             => $paquetes,
        'empresas'             => $empresas,
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
                'fecha_salida' => now(),
                'estado'       => 'pendiente', // Estado por defecto
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
                        'salida_id'            => $salida->id,
                        'cliente_id'           => $cliente_id,
                        'obra_id'              => $obra_id,
                        'horas_paralizacion'   => 0,
                        'importe_paralizacion' => 0,
                        'horas_grua'           => 0,
                        'importe_grua'         => 0,
                        'horas_almacen'        => 0,
                        'importe'              => 0,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }
            }

            // Insertar en la tabla pivote salida_cliente
            if (!empty($pivotData)) {
                DB::table('salida_cliente')->insert(array_values($pivotData));
            } else {
                Log::warning('No se encontraron combinaciones de cliente y obra para asociar a la salida.');
            }

            return redirect()->route('planificacion.index')->with('success', 'Salida creada con éxito');
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
            $obraId    = $request->input('obra_id');    // Nuevo: para identificar la obra

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
            }
            // **Actualizar en la tabla 'salida_cliente'**
            elseif (in_array($field, $salidaClienteFields)) {
                // Validamos que se hayan enviado cliente_id y obra_id
                if (!$clienteId || !$obraId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falta el ID del cliente u obra para actualizar el campo.'
                    ], 422);
                }

                // Actualizar usando los tres identificadores: salida_id, cliente_id y obra_id
                $updated = DB::table('salida_cliente')
                    ->where('salida_id', $salida->id)
                    ->where('cliente_id', $clienteId)
                    ->where('obra_id', $obraId)
                    ->update([$field => $value]);
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
            // 🔹 Extraer el nombre del mes (sin el año)
            preg_match('/([a-zA-Záéíóú]+)/', $mes, $matches);
            $mesSolo = strtolower($matches[1] ?? '');

            // 🔹 Validar si el mes es válido
            if (!isset($meses[$mesSolo])) {
                return redirect()->route('salidas.index')->with('error', "Mes no válido: $mes");
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
                return redirect()->route('salidas.index')->with('error', "No hay salidas registradas en $mesSolo $anio.");
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
                        'obras'              => collect(),
                        'horas_paralizacion' => 0,
                        'importe_paralizacion' => 0,
                        'horas_grua'         => 0,
                        'importe_grua'       => 0,
                        'horas_almacen'      => 0,
                        'importe'            => 0,
                        'total'              => 0,
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
            return redirect()->route('salidas.index')->with('error', 'Hubo un problema al exportar las salidas: ' . $e->getMessage());
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
                ->with('success', '✅ Paquete quitado y salida eliminada porque no quedaban más paquetes.');
        }

        return back()->with('success', '✅ Paquete quitado correctamente.');
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

            return redirect()->route('salidas.index')
                ->with('success', 'Salida eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('salidas.index')
                ->with('error', 'Hubo un problema al eliminar la salida: ' . $e->getMessage());
        }
    }
}
