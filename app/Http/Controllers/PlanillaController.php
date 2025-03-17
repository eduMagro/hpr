<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;

use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\Obra;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use App\Imports\PlanillaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PlanillaController extends Controller
{
    public function asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planillaId)
    {

        $estribo = $doblesPorBarra >= 5 && $diametro < 20;

        $maquinas = collect(); // Inicializar con una colección vacía de Laravel

        $diametrosPlanilla = Elemento::where('planilla_id', $planillaId)->distinct()->pluck('diametro')->toArray();

        $maquinaForzada = null;
        if (count($diametrosPlanilla) > 1) {
            $maxDiametro = max($diametrosPlanilla);
            if ($maxDiametro <= 12) {
                $maquinaForzada = ['PS12', 'F12'];
            } elseif ($maxDiametro <= 16) {
                $maquinaForzada = ['MS16'];
            } elseif ($maxDiametro <= 20) {
                $maquinaForzada = ['MSR20'];
            } elseif ($maxDiametro <= 25) {
                $maquinaForzada = ['SL28'];
            } else {
                $maquinaForzada = ['MANUAL'];
            }
        }

        if ($diametro == 5) {
            $maquinas = Maquina::where('codigo', 'ID5')->get();
        } elseif ($estribo) {
            if ($diametro == 8) {
                $maquinas = Maquina::where('codigo', 'PS12')->get();
            } elseif (in_array($diametro, [10, 12])) {
                $maquinas = Maquina::where('codigo', 'F12')->get();
            } elseif (in_array($diametro, [10, 12])) {
                $maquinas = Maquina::whereIn('codigo', ['PS12', 'F12'])->get();
            } elseif (in_array($diametro, [10, 16])) {
                $maquinas = Maquina::whereIn('codigo', ['PS12', 'F12', 'MS16'])->get();
            }
        } elseif (!$estribo && $diametro >= 10 && $diametro <= 16) {
            $maquinas = Maquina::where('codigo', 'MS16')->get();
        } elseif (!$estribo && $diametro >= 8 && $diametro <= 20) {
            $maquinas = Maquina::where('codigo', 'MSR20')->get();
        } elseif (!$estribo && $diametro >= 12 && $diametro <= 25) {
            $maquinas = Maquina::where('codigo', 'SL28')->get();
        } elseif ($maquinaForzada) {
            $maquinas = Maquina::whereIn('codigo', $maquinaForzada)->get();
        } else {
            $maquinas = Maquina::where('codigo', 'MANUAL')->get();
        }

        if ($maquinas->isEmpty()) {
            return null;
        }

        // Selección de la máquina con menor carga
        $maquinaSeleccionada = null;
        $pesoMinimo = PHP_INT_MAX;

        foreach ($maquinas as $maquina) {
            $pesoActual = Elemento::where('maquina_id', $maquina->id)->sum('peso');

            if ($pesoActual < 5000) {
                // Prioriza la primera máquina con menos de 5,000 kg
                return $maquina->id;
            }

            // Si todas están por encima del umbral, selecciona la de menor peso acumulado
            if ($pesoActual < $pesoMinimo) {
                $pesoMinimo = $pesoActual;
                $maquinaSeleccionada = $maquina;
            }
        }

        return $maquinaSeleccionada?->id ?? null;
    }


    //------------------------------------------------------------------------------------ FILTROS
    private function aplicarFiltros($query, Request $request)
    {
        // 🔍 Búsqueda global en múltiples campos
        if ($request->has('buscar') && $request->buscar) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%$buscar%")
                    ->orWhere('cliente', 'like', "%$buscar%")
                    ->orWhere('nom_obra', 'like', "%$buscar%")
                    ->orWhere('cod_obra', 'like', "%$buscar%")
                    ->orWhereHas('user', function ($q) use ($buscar) {
                        $q->where('name', 'like', "%$buscar%");
                    });
            });
        }

        // 🔢 Filtros específicos
        $filters = [
            'codigo' => 'codigo',
            'cod_obra' => 'cod_obra',
            'nom_obra' => 'nom_obra',
            'cliente' => 'cliente',
            'ensamblado' => 'ensamblado',
        ];

        foreach ($filters as $requestKey => $column) {
            if ($request->has($requestKey) && $request->$requestKey) {
                $query->where($column, 'like', "%{$request->$requestKey}%");
            }
        }

        // 📅 Filtrado por rango de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_finalizacion') && $request->fecha_finalizacion) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        // 🏗️ Filtrar por usuario
        if ($request->has('name') && $request->name) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name}%");
            });
        }

        // 📌 Filtrar por planilla_id si está presente
        if ($request->has('planilla_id')) {
            $query->where('id', $request->planilla_id);
        }

        // 📌 Filtrar por codigo de planilla si está presente
        if ($request->has('codigo') && $request->codigo) {
            $query->where('codigo', 'like', "%{$request->codigo}%");
        }

        // 🛠️ Filtrar por estado de fabricación
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', 'like', "%{$request->estado}%");
        }
        // 🏷️ Ordenar los resultados
        $allowedSortColumns = ['created_at', 'codigo', 'cliente', 'nom_obra']; // Lista de columnas permitidas

        $sortBy = $request->filled('sort_by') && in_array($request->input('sort_by'), $allowedSortColumns)
            ? $request->input('sort_by')
            : 'created_at'; // Default seguro

        $order = $request->filled('order') && in_array($request->input('order'), ['asc', 'desc'])
            ? $request->input('order')
            : 'desc'; // Default seguro

        $query->orderBy($sortBy, $order);

        return $query;
    }



    //------------------------------------------------------------------------------------ INDEX()
    public function index(Request $request)
    {
        try {
            $query = Planilla::with(['user', 'elementos', 'cliente', 'obra']);

            // Aplicar filtros
            $query = $this->aplicarFiltros($query, $request);

            // 📌 Columnas permitidas para ordenar
            $allowedSortColumns = ['created_at', 'codigo', 'cliente', 'nom_obra'];

            // 📌 Si no se envía un `sort_by` válido, usar 'created_at' por defecto
            $sortBy = $request->filled('sort_by') && in_array($request->input('sort_by'), $allowedSortColumns)
                ? $request->input('sort_by')
                : 'created_at';

            // 📌 Si no se envía `order`, usar 'desc' por defecto
            $order = $request->filled('order') && in_array($request->input('order'), ['asc', 'desc'])
                ? $request->input('order')
                : 'desc';

            // 📌 Verifica que `sort_by` no esté vacío antes de ordenar
            if (!empty($sortBy)) {
                $query->orderBy($sortBy, $order);
            } else {
                $query->orderBy('created_at', 'desc'); // Default seguro
            }

            // 📌 Paginación
            $perPage = $request->input('per_page', 10);
            $planillas = $query->paginate($perPage)->appends($request->except('page'));

            // 📌 Cargar suma de peso completado
            $planillas->loadSum([
                'elementos as suma_peso_completados' => function ($query) {
                    $query->where('estado', 'completado');
                }
            ], 'peso');
            $clientes = Cliente::where('activo', 1)->get();
            $obras = Obra::where('completada', 0)->get();
            // Retornar vista con los datos
            return view('planillas.index', compact('planillas', 'clientes', 'obras'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $planilla = Planilla::with([
            'paquetes:id,planilla_id,peso,ubicacion_id',
            'paquetes.ubicacion:id,nombre',
            'paquetes.subpaquetes:id,paquete_id,elemento_id,peso',
            'paquetes.subpaquetes.elemento:id,planilla_id,estado,peso',
            'elementos:id,planilla_id,estado,peso,diametro,paquete_id,maquina_id',
            'elementos.ubicacion:id,nombre',
            'elementos.maquina:id,nombre',
            'etiquetas:id'
        ])->findOrFail($id);

        $getColor = fn($estado) => match (strtolower(trim($estado ?? 'desconocido'))) {
            'completado' => 'bg-green-200',
            'pendiente' => 'bg-red-200',
            'fabricando' => 'bg-blue-200',
            default => 'bg-gray-200'
        };

        $elementos = $planilla->elementos->map(fn($e) => tap($e, fn($e) => $e->color = $getColor($e->estado)));
        $subpaquetes = $planilla->paquetes->flatMap->subpaquetes->map(fn($s) => tap($s, fn($s) => $s->color = 'bg-green-200'));

        [$elementosConPaquete, $elementosSinPaquete] = $elementos->partition(fn($e) => !empty($e->paquete_id));

        $paquetes = $planilla->paquetes->map(fn($p) => tap($p, function ($p) use ($elementosConPaquete, $subpaquetes) {
            $p->color = 'bg-gray-300';
            $p->elementos = $elementosConPaquete->where('paquete_id', $p->id);
            $p->subpaquetes = $subpaquetes->where('paquete_id', $p->id);
        }));

        return view('planillas.show', [
            'planillaCalculada' => [
                'planilla' => $planilla,
                'progreso' => round(min(100, ($elementos->where('estado', 'completado')->sum('peso') / max(1, $planilla->peso_total)) * 100), 2),
                'paquetes' => $paquetes,
                'elementosSinPaquete' => $elementosSinPaquete,
                'subpaquetes' => $subpaquetes
            ]
        ]);
    }


    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        return view('planillas.create');
    }

    //------------------------------------------------------------------------------------ IMPORT()
    public function import(Request $request)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        // Validar el archivo
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.mimes' => 'El archivo debe tener uno de los siguientes formatos: xlsx o xls',
        ]);


        DB::beginTransaction(); // Iniciar la transacción

        try {
            $file = $request->file('file');

            // Convertir el archivo a un arreglo
            $importedData = Excel::toArray([], $file);

            // Procesar la primera hoja del archivo
            $firstSheet = $importedData[0] ?? [];

            if (empty($firstSheet)) {
                throw new Exception('El archivo está vacío o no contiene datos válidos.');
            }

            // Tomar los encabezados y las filas de datos
            $headers = $firstSheet[0] ?? [];
            $data = array_slice($firstSheet, 1);

            // Filtrar filas vacías
            $filteredData = array_filter($data, function ($row) {
                return array_filter($row); // Ignorar filas completamente vacías
            });

            if (empty($filteredData)) {
                throw new Exception('El archivo no contiene filas válidas después de las cabeceras.');
            }

            // Agrupar los datos por el código (columna K, índice 8)
            $groupedByCodigo = [];
            foreach ($filteredData as $row) {
                $codigo = $row[10] ?? 'Sin código';
                $groupedByCodigo[$codigo][] = $row;
            }

            foreach ($groupedByCodigo as $codigo => $rows) {
                // Sumar todos los pesos de las filas con este código
                $pesoTotal = array_reduce($rows, function ($carry, $row) {
                    return $carry + (float) ($row[34] ?? 0);
                }, 0);

                // Tomar el nom_obra de la primera fila de la planilla (todas son iguales)
                $nomObra = trim(Str::lower($rows[0][3] ?? ''));
                // Tomar el nomCliente de la primera fila de la planilla (todas son iguales)
                $nomCliente = trim(Str::lower($rows[0][1] ?? ''));

                // Buscar la obra ignorando mayúsculas/minúsculas y espacios extras
                $obra = Obra::whereRaw('LOWER(TRIM(obra)) = ?', [$nomObra])->first();
                // Buscar el cliente ignorando mayúsculas/minúsculas y espacios extras
                $cliente = Cliente::whereRaw('LOWER(TRIM(empresa)) = ?', [$nomCliente])->first();

                // Log para verificar la comparación
                Log::info("Comparando: '$nomObra' con '" . ($obra ? $obra->obra : 'Ninguna obra encontrada') . "'");
                Log::info("Comparando: '$nomCliente' con '" . ($cliente ? $cliente->empresa : 'Ningun cliente encontrado') . "'");

                // Si no encuentra la obra, cancelar la importación
                if (!$obra) {
                    DB::rollBack();
                    return redirect()->route('planillas.index')
                        ->with('error', "La obra '{$rows[0][3]}' no coincide con ninguna obra registrada. Verifica el nombre en el Excel.");
                }
                // Si no encuentra el cliente, cancelar la importación
                if (!$cliente) {
                    DB::rollBack();
                    return redirect()->route('planillas.index')
                        ->with('error', "El cliente '{$rows[0][1]}' no coincide con ningún cliente registrado. Verifica el nombre en el Excel.");
                }


                // Crear el registro de planilla
                $planilla = Planilla::create([
                    'users_id' => auth()->id(),
                    'cliente_id' => $cliente->id,
                    'obra_id' => $obra->id,
                    'seccion' => $rows[0][7] ?? null,
                    'descripcion' => $rows[0][12] ?? null,
                    'ensamblado' => $rows[0][4] ?? null,
                    'codigo' => $codigo,
                    'peso_total' => $pesoTotal,
                    'fecha_inicio' => null,
                    'fecha_finalizacion' => null, // Actualizaremos más adelante
                    'tiempo_fabricacion' => 0, // Inicialmente en 0, lo actualizamos después
                    'fecha_estimada_entrega' => now()->addDays(7), // Fecha actual + 7 días

                ]);

                // Array para almacenar etiquetas ya registradas en esta ejecución
                $etiquetasRegistradas = [];
                foreach ($rows as $row) {
                    $diametro = $row[25] ?? 0;
                    $longitud = $row[27] ?? 0;
                    $figura = $row[26] ?? null;
                    $doblesPorBarra = $row[33] ?? 0;
                    $barras = $row[32] ?? 0;
                    $ensamblado = $row[4] ?? null;
                    $planillaId = $planilla->id; // Asegúrate de definirlo antes de la llamada

                    // Llamar a asignarMaquina con los nuevos parámetros
                    $maquina_id = $this->asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planillaId);
                    $tiempos = $this->calcularTiemposElemento($row);

                    // Verificar si la etiqueta ya existe antes de crearla
                    $numeroEtiqueta = $row[30] ?? null;
                    $marca = $row[23] ?? 'Sin marca';
                    if (!isset($etiquetasRegistradas[$numeroEtiqueta])) {
                        $etiqueta = Etiqueta::where('numero_etiqueta', $numeroEtiqueta)
                            ->where('planilla_id', $planilla->id)
                            ->first();

                        if (!$etiqueta) {
                            $etiqueta = Etiqueta::create([
                                'numero_etiqueta' => $numeroEtiqueta,
                                'planilla_id' => $planilla->id,
                                'nombre' => $row[22] ?? 'Sin nombre',
                                'peso' => 0, // Inicialmente en 0, lo actualizamos después
                                'marca' => null, // Se actualizará después
                            ]);
                        }

                        $etiquetasRegistradas[$numeroEtiqueta] = $etiqueta;
                    }


                    // Crear el registro de elemento
                    $elemento = Elemento::create([
                        'planilla_id' => $planilla->id,
                        'etiqueta_id' => $etiqueta->id, // Relación con etiqueta
                        'maquina_id' => $maquina_id,
                        'figura' => $row[26],
                        'fila' => $row[21],
                        'marca' => $row[23],
                        'etiqueta' => $row[30],
                        'diametro' => $diametro,
                        'longitud' => $longitud,
                        'barras' => $row[32],
                        'dobles_barra' => $row[33] ?? 0,
                        'peso' => $row[34],
                        'dimensiones' => $row[47] ?? null,
                        'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                    ]);
                }
                foreach ($etiquetasRegistradas as $etiqueta) {
                    $etiqueta->peso = $etiqueta->elementos()->sum('peso');

                    $marcaMasComun = $etiqueta->elementos()
                        ->select('marca', DB::raw('COUNT(*) as total'))
                        ->groupBy('marca')
                        ->orderByDesc('total')
                        ->value('marca');

                    $etiqueta->marca = $marcaMasComun;
                    $etiqueta->save();
                }
            }
            $planilla->update([
                'tiempo_fabricacion' => $planilla->elementos->sum('tiempo_fabricacion'),
            ]);

            DB::commit(); // Confirmar la transacción
            return redirect()->route('planillas.index')->with('success', 'Planillas importadas con éxito.');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return redirect()->route('planillas.index')->with('error', 'Hubo un problema al importar las planillas: ' . $e->getMessage());
        }
    }
    //------------------------------------------------------------------------------------ CALCULARTIEMPOSELEMENTO()
    private function calcularTiemposElemento(array $row)
    {
        $barras = $row[32] ?? 0;
        $doblesBarra = $row[33] ?? 0;

        // Calcular el tiempo estimado para el elemento
        $tiempoFabricacion = ($doblesBarra > 0)
            ? ($barras * $doblesBarra * 1.5) // Cálculo para barras con dobles
            : ($barras * 2); // Cálculo para barras rectas



        return [
            'tiempo_fabricacion' => $tiempoFabricacion,
        ];
    }

    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'cod_obra' => 'required|string|max:255',
                'cod_cliente' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'nom_obra' => 'required|string|max:255',
                'seccion' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'ensamblado' => 'required|string|max:255',
                'codigo' => 'required|string|max:255',
                'peso_total' => 'required|numeric|min:0',
            ]);

            Planilla::create($validated);

            return redirect()->route('planillas.index')->with('success', 'Planilla creada exitosamente.');
        } catch (ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }
    //------------------------------------------------------------------------------------ EDIT()
    public function edit($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $planilla = Planilla::findOrFail($id);  // Encuentra la planilla por su ID

        return view('planillas.edit', compact('planilla'));
    }
    public function updateX(Request $request, $id)
    {

        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            $planilla = Planilla::findOrFail($id);

            // Actualizar la planilla con datos limpios
            $planilla->update([
                'cod_obra' => $request->cod_obra,
                'cod_cliente' => $request->cod_cliente,
                'cliente' => $request->cliente,
                'nom_obra' => $request->nom_obra,
                'seccion' => $request->seccion,
                'descripcion' => $request->descripcion,
                'ensamblado' => $request->ensamblado,
                'codigo' => $request->codigo,
                'peso_total' => $request->peso_total,
                'tiempo_fabricacion' => 0,
            ]);

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('planillas.index')->with('success', 'Planilla actualizada');
        } catch (ValidationException $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Buscar la planilla o lanzar excepción si no se encuentra
            $planilla = Planilla::findOrFail($id);

            // Si los campos de fecha vienen vacíos, forzar null
            $request->merge([
                'fecha_inicio'           => $request->fecha_inicio ?: null,
                'fecha_estimada_entrega' => $request->fecha_estimada_entrega ?: null,
                'fecha_finalizacion'     => $request->fecha_finalizacion ?: null,
                'fecha_importacion'      => $request->fecha_importacion ?: null,
            ]);

            // Validar los datos recibidos con mensajes personalizados
            $validatedData = $request->validate([
                'codigo'                 => 'required|string|max:50',
                'cliente_id'             => 'nullable|integer|exists:clientes,id',
                'obra_id'               => 'nullable|integer|exists:obras,id',
                'seccion'                => 'nullable|string|max:100',
                'descripcion'            => 'nullable|string',
                'ensamblado'             => 'nullable|string|max:100',
                'comentario'             => 'nullable|string|max:255',
                'peso_fabricado'         => 'nullable|numeric',
                'peso_total'             => 'nullable|numeric',
                'estado'                 => 'nullable|string|in:pendiente,fabricando,completada',
                'fecha_inicio'           => 'nullable|date_format:d/m/Y H:i',
                'fecha_finalizacion'     => 'nullable|date_format:d/m/Y H:i',
                'fecha_estimada_entrega' => 'nullable|date_format:d/m/Y',
                'fecha_importacion'      => 'nullable|date_format:d/m/Y',

                'usuario'                => 'nullable|string|max:100'
            ], [
                'codigo.required'     => 'El campo Código es obligatorio.',
                'codigo.string'       => 'El campo Código debe ser una cadena de texto.',
                'codigo.max'          => 'El campo Código no debe exceder 50 caracteres.',

                'cliente_id.integer'    => 'El campo cliente_id debe ser un número entero.',
                'cliente_id.exists'     => 'El cliente especificaoa en cliente_id no existe.',
                'obra_id.integer'  => 'El campo obra_id debe ser un número entero.',
                'obra_id.exists'     => 'La obra especificada en obra_id no existe.',

                'seccion.string'      => 'El campo Sección debe ser una cadena de texto.',
                'seccion.max'         => 'El campo Sección no debe exceder 100 caracteres.',

                'descripcion.string'  => 'El campo Descripción debe ser una cadena de texto.',

                'ensamblado.string'   => 'El campo Ensamblado debe ser una cadena de texto.',
                'ensamblado.max'      => 'El campo Ensamblado no debe exceder 100 caracteres.',

                'comentario.string'   => 'El campo Comentario debe ser una cadena de texto.',
                'comentario.max'      => 'El campo Comentario no debe exceder 255 caracteres.',

                'peso_fabricado.numeric' => 'El campo Peso Fabricado debe ser un número.',
                'peso_total.numeric'     => 'El campo Peso Total debe ser un número.',

                'estado.in'             => 'El campo Estado debe ser: pendiente, fabricando o completada.',

                // Se modifican los mensajes para referir a cada campo.
                'fecha_inicio.date_format'           => 'El campo Fecha Inicio no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_finalizacion.date_format'     => 'El campo Fecha Finalización no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_estimada_entrega.date_format' => 'El campo Fecha Estimada de Entrega no corresponde al formato DD/MM/YYYY.',
                'fecha_importacion.date_format'           => 'El campo Fecha Estimada de Entrega no corresponde al formato DD/MM/YYYY.',

                'usuario.string'        => 'El campo Usuario debe ser una cadena de texto.',
                'usuario.max'           => 'El campo Usuario no debe exceder 100 caracteres.'
            ]);

            // ✅ Validación personalizada: Comprobar que la obra seleccionada pertenece al cliente seleccionado
            if (!empty($validatedData['obra_id']) && !empty($validatedData['cliente_id'])) {
                $obra = Obra::find($validatedData['obra_id']);
                if ($obra && $obra->cliente_id != $validatedData['cliente_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La obra seleccionada no pertenece al cliente seleccionado.'
                    ], 422);
                }
            }
            // 1) Convertir fecha_inicio si existe
            if (!empty($validatedData['fecha_inicio'])) {
                $validatedData['fecha_inicio'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_inicio'])
                    ->format('Y-m-d H:i:s');
            }

            // 2) Convertir fecha_finalizacion si existe
            if (!empty($validatedData['fecha_finalizacion'])) {
                $validatedData['fecha_finalizacion'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_finalizacion'])
                    ->format('Y-m-d H:i:s');
            }

            // 3) Convertir fecha_estimada_entrega si existe (si la recibes con día/mes/año)
            if (!empty($validatedData['fecha_estimada_entrega'])) {
                $validatedData['fecha_estimada_entrega'] = Carbon::createFromFormat('d/m/Y', $validatedData['fecha_estimada_entrega'])
                    ->format('Y-m-d');
            }
            // Actualizar la planilla con los datos validados
            $planilla->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Planilla actualizada correctamente',
                'data'    => $planilla->codigo_limpio
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Planilla no encontrada'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la planilla. Intente nuevamente. ' . $e->getMessage()
            ], 500);
        }
    }



    //------------------------------------------------------------------------------------ DESTROY()

    // Eliminar una planilla y sus elementos asociados
    public function destroy($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        DB::beginTransaction();
        try {
            // Buscar la planilla a eliminar
            $planilla = Planilla::with('elementos')->findOrFail($id);

            // Eliminar los elementos asociados directamente
            foreach ($planilla->elementos as $elemento) {
                $elemento->delete();
            }

            // Eliminar la planilla
            $planilla->delete();

            DB::commit(); // Confirmamos la transacción
            return redirect()->route('planillas.index')->with('success', 'Planilla eliminada correctamente.');
        } catch (Exception $e) {
            DB::rollBack(); // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la planilla: ' . $e->getMessage());
        }
    }
}
