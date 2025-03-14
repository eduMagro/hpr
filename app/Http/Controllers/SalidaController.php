<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Salida;
use App\Models\Paquete;
use App\Models\Etiqueta;
use App\Models\Elemento;
use App\Models\EmpresaTransporte;
use App\Models\Camion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class SalidaController extends Controller
{
    public function index(Request $request)
    {
        // Verificar si el usuario es administrador
        if (auth()->user()->rol == 'oficina') {
            // Administrador: Mostrar todas las salidas con todas sus relaciones
            $query = Salida::with(['paquetes.subpaquetes', 'paquetes.elementos', 'paquetes.planilla']);
        } else {
            // Usuario normal: Mostrar solo salidas con estado "pendiente"
            $query = Salida::where('estado', 'pendiente')
                ->with(['paquetes.subpaquetes', 'paquetes.elementos']);
        }

        // Obtener los datos paginados
        $salidas = $query->get(); // No usamos paginate para agrupar correctamente
        // Procesar cada salida para extraer planillas únicas, clientes y obras sin duplicados
        foreach ($salidas as $salida) {
            // Obtener planillas únicas asociadas a la salida
            $salida->planillasUnicas = $salida->paquetes
                ->pluck('planilla')
                ->unique()
                ->filter()
                ->values();

            // Extraer clientes únicos a nivel de salida
            $salida->clientesUnicos = $salida->planillasUnicas
                ->map(fn($planilla) => $planilla->cliente)
                ->unique()
                ->filter()
                ->values();

            // Extraer obras únicas a nivel de salida
            $salida->obrasUnicas = $salida->planillasUnicas
                ->map(fn($planilla) => $planilla->nom_obra)
                ->unique()
                ->filter()
                ->values();
        }

        // Agrupar por mes
        $salidasPorMes = $salidas->groupBy(function ($salida) {
            return \Carbon\Carbon::parse($salida->fecha_salida)->translatedFormat('F Y'); // Ejemplo: "Marzo 2025"
        });

        // Pasar los datos a la vista
        return view('salidas.index', compact('salidasPorMes', 'salidas'));
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
            // Comprobar si los paquetes seleccionados ya están asociados a alguna salida
            $paquetesRepetidos = DB::table('salidas_paquetes')
                ->whereIn('paquete_id', $request->paquete_ids)  // Paquetes seleccionados
                ->whereNotNull('salida_id')  // Asegurarse de que estén asociados a alguna salida
                ->pluck('paquete_id')  // Extraer solo los IDs
                ->toArray();  // Convertir el resultado a un array

            // Encontrar los paquetes repetidos
            $repetidos = array_intersect($request->paquete_ids, $paquetesRepetidos);

            // Si hay paquetes repetidos, devolver el error
            if ($repetidos) {
                return back()->withErrors(['paquete_ids' => 'Los siguientes paquetes ya están asociados a una salida: ' . implode(', ', $repetidos)]);
            }
            $camion = Camion::find($request->camion_id);
            $empresa = $camion->empresaTransporte;  // Accede a la empresa del camión

            // Crear la salida
            $salida = Salida::create([
                'empresa_id' => $empresa->id,
                'camion_id' => $request->camion_id,
                'fecha_salida' => null,
                'estado' => 'pendiente', // Estado por defecto, puedes cambiarlo si es necesario
            ]);
            // Generar el código de salida
            $codigo_salida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);

            // Asignar el código de salida
            $salida->codigo_salida = $codigo_salida;
            $salida->save();

            // Asociar los paquetes a la salida
            foreach ($request->paquete_ids as $paquete_id) {
                // Asociar el paquete existente a la salida
                $salida->paquetes()->attach($paquete_id);
            }

            // Retornar una respuesta de éxito
            return redirect()->route('salidas.create')->with('success', 'Salida creada con éxito');
        } catch (\Exception $e) {
            // Capturar cualquier excepción y retornar un error general
            return back()->withErrors(['error' => 'Hubo un problema al crear la salida: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $salida = Salida::findOrFail($id);


            $field = $request->input('field');
            $value = $request->input('value');

            // Solo se permiten actualizar estos campos en línea
            $allowedFields = ['importe', 'paralizacion', 'horas', 'horas_almacen', 'fecha_salida', 'estado'];

            if (!in_array($field, $allowedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El campo especificado no es editable en línea.'
                ], 422);
            }

            // Reglas y mensajes de validación para el inline update
            $rules = [
                'camion_id'     => 'required|integer|exists:camiones,id',
                'empresa_id'    => 'required|integer|exists:empresas,id',
                'importe'        => 'nullable|numeric',
                'paralizacion'        => 'nullable|numeric',
                'horas'          => 'nullable|numeric',
                'horas_almacen'  => 'nullable|numeric',
                'fecha_salida'   => 'nullable|date',
                'estado'         => 'nullable|string|max:50',
            ];
            $messages = [
                'camion_id.required'   => 'El campo camion_id es obligatorio.',
                'camion_id.integer'    => 'El campo camion_id debe ser un número entero.',
                'camion_id.exists'     => 'El camión especificado no existe.',
                'empresa_id.required'  => 'El campo empresa_id es obligatorio.',
                'empresa_id.integer'   => 'El campo empresa_id debe ser un número entero.',
                'empresa_id.exists'    => 'La empresa especificada no existe.',
                'importe.numeric'       => 'El campo importe debe ser un número.',
                'paralizacion.numeric'       => 'El campo paralización debe ser un número.',
                'horas.numeric'         => 'El campo horas debe ser un número.',
                'horas_almacen.numeric' => 'El campo horas/almacén debe ser un número.',
                'fecha_salida.date'     => 'El campo fecha debe ser una fecha válida.',
                'estado.string'         => 'El campo estado debe ser una cadena de texto.',
                'estado.max'            => 'El campo estado no debe tener más de 50 caracteres.',
            ];

            // Validar el valor para el campo específico
            $request->validate([
                $field => $rules[$field]
            ], [
                $field . '.numeric' => $messages[$field . '.numeric'] ?? '',
                $field . '.date'    => $messages[$field . '.date'] ?? '',
                $field . '.string'  => $messages[$field . '.string'] ?? '',
                $field . '.max'     => $messages[$field . '.max'] ?? '',
            ]);

            // Si es fecha, convertir el formato
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

            $salida->$field = $value;
            $salida->save();

            return response()->json([
                'success' => true,
                'message' => 'Salida actualizada correctamente.',
                'data'    => $salida
            ]);


            // Si se envía fecha_salida, convertir el formato
            if (!empty($validatedData['fecha_salida'])) {
                try {
                    $validatedData['fecha_salida'] = Carbon::parse($validatedData['fecha_salida'])->format('Y-m-d');
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de fecha no válido.'
                    ], 422);
                }
            }

            $salida->update($validatedData);

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
        // Filtra las salidas del mes correspondiente
        $salidas = Salida::whereMonth('fecha_salida', Carbon::parse($mes)->month)
            ->whereYear('fecha_salida', Carbon::parse($mes)->year)
            ->get();

        // Procesa el resumen por cliente, similar a la vista.
        $clientSummary = [];
        foreach ($salidas as $salida) {
            $importe = $salida->importe ?? 0;
            foreach ($salida->clientesUnicos as $cliente) {
                if ($cliente) {
                    if (!isset($clientSummary[$cliente])) {
                        $clientSummary[$cliente] = 0;
                    }
                    $clientSummary[$cliente] += $importe;
                }
            }
        }

        // Usa Excel::download() para generar y retornar el archivo
        return Excel::download(new SalidasExport($salidas, $clientSummary), 'salidas_' . $mes . '.xlsx');
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
