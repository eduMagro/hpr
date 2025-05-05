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

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('users_id')) {
            $nombreUsuario = User::find($request->users_id)?->name ?? 'Desconocido';
            $filtros[] = 'Responsable: <strong>' . $nombreUsuario . '</strong>';
        }

        if ($request->filled('codigo')) {
            $filtros[] = 'Código Planilla: <strong>' . $request->codigo . '</strong>';
        }

        if ($request->filled('codigo_cliente')) {
            $filtros[] = 'Código cliente: <strong>' . $request->codigo_cliente . '</strong>';
        }

        if ($request->filled('cliente')) {
            $filtros[] = 'Cliente: <strong>' . $request->cliente . '</strong>';
        }

        if ($request->filled('cod_obra')) {
            $filtros[] = 'Código obra: <strong>' . $request->cod_obra . '</strong>';
        }

        if ($request->filled('nom_obra')) {
            $filtros[] = 'Obra: <strong>' . $request->nom_obra . '</strong>';
        }


        if ($request->filled('seccion')) {
            $filtros[] = 'Sección: <strong>' . $request->seccion . '</strong>';
        }

        if ($request->filled('descripcion')) {
            $filtros[] = 'Descripción: <strong>' . $request->descripcion . '</strong>';
        }

        if ($request->filled('ensamblado')) {
            $filtros[] = 'Ensamblado: <strong>' . $request->ensamblado . '</strong>';
        }

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst($request->estado) . '</strong>';
        }

        if ($request->filled('fecha_finalizacion')) {
            $filtros[] = 'Fecha finalización: <strong>' . $request->fecha_finalizacion . '</strong>';
        }
        if ($request->filled('fecha_importacion')) {
            $filtros[] = 'Fecha importación: <strong>' . $request->fecha_importacion . '</strong>';
        }
        if ($request->filled('fecha_estimada_entrega')) {
            $filtros[] = 'Fecha estimada entrega: <strong>' . $request->fecha_estimada_entrega . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'fecha_estimada_entrega' => 'Entrega estimada',
                'estado' => 'Estado',
                'seccion' => 'Sección',
                'peso_total' => 'Peso total',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }

        return $filtros;
    }
    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="text-white text-decoration-none">' .
            $titulo . ' <i class="' . $icon . '"></i></a>';
    }

    //------------------------------------------------------------------------------------ FILTROS
    public function aplicarFiltros($query, Request $request)
    {

        if ($request->filled('users_id')) {
            $query->where('users_id', $request->users_id);
        }

        if ($request->filled('codigo_cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->codigo_cliente . '%');
            });
        }

        if ($request->filled('cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('empresa', 'like', '%' . $request->cliente . '%');
            });
        }

        if ($request->filled('cod_obra')) {
            $query->whereHas('obra', function ($q) use ($request) {
                $q->where('cod_obra', 'like', '%' . $request->cod_obra . '%');
            });
        }

        if ($request->filled('nom_obra')) {
            $query->whereHas('obra', function ($q) use ($request) {
                $q->where('obra', 'like', '%' . $request->nom_obra . '%');
            });
        }


        if ($request->filled('seccion')) {
            $query->where('seccion', 'like', '%' . $request->seccion . '%');
        }

        if ($request->filled('descripcion')) {
            $query->where('descripcion', 'like', '%' . $request->descripcion . '%');
        }

        if ($request->filled('ensamblado')) {
            $query->where('ensamblado', 'like', '%' . $request->ensamblado . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_finalizacion')) {
            $query->whereDate('fecha_finalizacion', $request->fecha_finalizacion);
        }
        if ($request->filled('fecha_importacion')) {
            $query->whereDate('created_at', $request->fecha_importacion);
        }
        if ($request->filled('fecha_estimada_entrega')) {
            $query->whereDate('fecha_estimada_entrega', $request->fecha_estimada_entrega);
        }

        // Ordenación
        $sortBy = $request->input('sort', 'fecha_estimada_entrega');
        $order = $request->input('order', 'desc');
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        return $query;
    }



    //------------------------------------------------------------------------------------ INDEX()
    public function index(Request $request)
    {
        try {
            // 1️⃣ Iniciar la consulta base con relaciones
            $query = Planilla::with(['user', 'elementos', 'cliente', 'obra']);

            // 2️⃣ Aplicar filtros desde el formulario (usando método personalizado)
            $query = $this->aplicarFiltros($query, $request);

            // 3️⃣ Definir columnas ordenables para la vista (cabecera de la tabla)
            $ordenables = [
                'codigo' => $this->getOrdenamiento('codigo', 'Código'),
                'cliente' => $this->getOrdenamiento('cliente', 'Cliente'),
                'cod_obra' => $this->getOrdenamiento('cod_obra', 'Código Obra'),
                'nom_obra' => $this->getOrdenamiento('nom_obra', 'Obra'),
                'seccion' => $this->getOrdenamiento('seccion', 'Sección'),
                'descripcion' => $this->getOrdenamiento('descripcion', 'Descripción'),
                'ensamblado' => $this->getOrdenamiento('ensamblado', 'Ensamblado'),
                'comentario' => $this->getOrdenamiento('comentario', 'Comentario'),
                'peso_fabricado' => $this->getOrdenamiento('peso_fabricado', 'Peso Fabricado'),
                'peso_total' => $this->getOrdenamiento('peso_total', 'Peso Total'),
                'estado' => $this->getOrdenamiento('estado', 'Estado'),
                'fecha_inicio' => $this->getOrdenamiento('fecha_inicio', 'Fecha Inicio'),
                'fecha_finalizacion' => $this->getOrdenamiento('fecha_finalizacion', 'Fecha Finalización'),
                'fecha_importacion' => $this->getOrdenamiento('fecha_importacion', 'Fecha Importación'),
                'fecha_entrega' => $this->getOrdenamiento('fecha_entrega', 'Fecha Entrega'),
                'name' => $this->getOrdenamiento('name', 'Usuario'),
            ];

            // 6️⃣ Aplicar paginación y mantener filtros al cambiar de página
            $perPage = $request->input('per_page', 10);
            $planillas = $query->paginate($perPage)->appends($request->except('page'));

            // 7️⃣ Cargar suma de pesos fabricados por planilla
            $planillas->loadSum([
                'elementos as suma_peso_completados' => function ($query) {
                    $query->where('estado', 'fabricado');
                }
            ], 'peso');

            // 🔟 Obtener texto de filtros aplicados para mostrar en la vista
            $filtrosActivos = $this->filtrosActivos($request);
            // En tu controlador
            $clientes = Cliente::select('id', 'codigo', 'empresa')->get();
            $obras = Obra::select('id', 'cod_obra', 'obra')->get();
            // ✅ Retornar la vista con todos los datos necesarios
            return view('planillas.index', compact(
                'planillas',
                'clientes',
                'obras',
                'ordenables',
                'filtrosActivos'
            ));
        } catch (Exception $e) {
            // ⚠️ Si algo falla, redirigir con mensaje de error
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $planilla = Planilla::with([
            'paquetes:id,planilla_id,peso,ubicacion_id',
            'paquetes.ubicacion:id,nombre',
            'paquetes:id,paquete_id,elemento_id,peso',
            'paquetes.elementos:id,planilla_id,estado,peso',
            'elementos:id,planilla_id,estado,peso,diametro,paquete_id,maquina_id',
            'elementos.ubicacion:id,nombre',
            'elementos.maquina:id,nombre',
            'etiquetas:id'
        ])->findOrFail($id);

        $getColor = fn($estado) => match (strtolower(trim($estado ?? 'desconocido'))) {
            'fabricado' => 'bg-green-200',
            'pendiente' => 'bg-red-200',
            'fabricando' => 'bg-blue-200',
            default => 'bg-gray-200'
        };

        $elementos = $planilla->elementos->map(fn($e) => tap($e, fn($e) => $e->color = $getColor($e->estado)));


        [$elementosConPaquete, $elementosSinPaquete] = $elementos->partition(fn($e) => !empty($e->paquete_id));

        $paquetes = $planilla->paquetes->map(fn($p) => tap($p, function ($p) use ($elementosConPaquete) {
            $p->color = 'bg-gray-300';
            $p->elementos = $elementosConPaquete->where('paquete_id', $p->id);
        }));

        return view('planillas.show', [
            'planillaCalculada' => [
                'planilla' => $planilla,
                'progreso' => round(min(100, ($elementos->where('estado', 'fabricado')->sum('peso') / max(1, $planilla->peso_total)) * 100), 2),
                'paquetes' => $paquetes,
                'elementosSinPaquete' => $elementosSinPaquete
            ]
        ]);
    }


    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        return view('planillas.create');
    }

    //------------------------------------------------------------------------------------ IMPORT()
    //------------------------------------------------------------------------------------ IMPORT()
    public function import(Request $request)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.mimes' => 'El archivo debe tener uno de los siguientes formatos: xlsx o xls',
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $importedData = Excel::toArray([], $file);
            $firstSheet = $importedData[0] ?? [];

            if (empty($firstSheet)) {
                throw new Exception('El archivo está vacío o no contiene datos válidos.');
            }

            $headers = $firstSheet[0] ?? [];
            $data = array_slice($firstSheet, 1);
            $filteredData = array_filter($data, fn($row) => array_filter($row));

            if (empty($filteredData)) {
                throw new Exception('El archivo no contiene filas válidas después de las cabeceras.');
            }

            $groupedByCodigo = [];
            foreach ($filteredData as $row) {
                $codigo = $row[10] ?? 'Sin código';
                $groupedByCodigo[$codigo][] = $row;
            }

            foreach ($groupedByCodigo as $codigo => $rows) {
                $pesoTotal = array_reduce($rows, fn($carry, $row) => $carry + (float) ($row[34] ?? 0), 0);

                $nomObra = trim(Str::lower($rows[0][3] ?? ''));
                $nomCliente = trim(Str::lower($rows[0][1] ?? ''));

                $obra = Obra::whereRaw('LOWER(TRIM(obra)) = ?', [$nomObra])->first();
                $cliente = Cliente::whereRaw('LOWER(TRIM(empresa)) = ?', [$nomCliente])->first();

                if (!$obra || !$cliente) {
                    DB::rollBack();
                    return redirect()->route('planillas.index')
                        ->with('error', 'La obra o el cliente del archivo no coinciden con los registros.');
                }

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
                    'fecha_finalizacion' => null,
                    'tiempo_fabricacion' => 0,
                    'fecha_estimada_entrega' => now()->addDays(7),
                ]);

                // Agrupar filas por número de etiqueta (columna 30)
                $filasAgrupadasPorEtiqueta = [];
                foreach ($rows as $row) {
                    $numeroEtiqueta = $row[21] ?? null;
                    if ($numeroEtiqueta) {
                        $filasAgrupadasPorEtiqueta[$numeroEtiqueta][] = $row;
                    }
                }

                foreach ($filasAgrupadasPorEtiqueta as $numeroEtiqueta => $filas) {
                    // Crear etiqueta padre
                    $etiquetaPadre = Etiqueta::create([
                        'numero_etiqueta' => $numeroEtiqueta,
                        'planilla_id' => $planilla->id,
                        'nombre' => $filas[0][22] ?? 'Sin nombre',
                        'peso' => 0,
                        'marca' => null,
                        'etiqueta_sub_id' => null,
                    ]);

                    // Agrupar por máquina antes de crear subetiquetas
                    $gruposPorMaquina = [];
                    foreach ($filas as $row) {
                        $diametro = $row[25] ?? 0;
                        $longitud = $row[27] ?? 0;
                        $figura = $row[26] ?? null;
                        $doblesPorBarra = $row[33] ?? 0;
                        $barras = $row[32] ?? 0;
                        $ensamblado = $row[4] ?? null;

                        $maquina_id = $this->asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planilla->id);
                        $gruposPorMaquina[$maquina_id][] = ['row' => $row, 'maquina_id' => $maquina_id];
                    }

                    $contadorSubetiquetas = 1;
                    foreach ($gruposPorMaquina as $maquina_id => $grupo) {
                        $idHijo = "{$etiquetaPadre->id}.{$contadorSubetiquetas}";

                        // Crear subetiqueta relacionada a la etiqueta padre
                        $subEtiqueta = Etiqueta::create([
                            'numero_etiqueta' => $numeroEtiqueta,
                            'planilla_id' => $planilla->id,
                            'nombre' => $grupo[0]['row'][22] ?? 'Sin nombre',
                            'peso' => 0,
                            'marca' => null,
                            'etiqueta_sub_id' => $idHijo,
                        ]);

                        foreach ($grupo as $item) {
                            $row = $item['row'];
                            $tiempos = $this->calcularTiemposElemento($row);

                            Elemento::create([
                                'planilla_id' => $planilla->id,
                                'etiqueta_id' => $subEtiqueta->id,
                                'etiqueta_sub_id' => $idHijo,
                                'maquina_id' => $maquina_id,
                                'figura' => $row[26],
                                'fila' => $row[21],
                                'marca' => $row[23],
                                'etiqueta' => $row[30],
                                'diametro' => $row[25],
                                'longitud' => $row[27],
                                'barras' => $row[32],
                                'dobles_barra' => $row[33] ?? 0,
                                'peso' => $row[34],
                                'dimensiones' => $row[47] ?? null,
                                'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                            ]);
                        }

                        $subEtiqueta->peso = $subEtiqueta->elementos()->sum('peso');
                        $subEtiqueta->marca = $subEtiqueta->elementos()
                            ->select('marca', DB::raw('COUNT(*) as total'))
                            ->groupBy('marca')
                            ->orderByDesc('total')
                            ->value('marca');
                        $subEtiqueta->save();

                        $contadorSubetiquetas++;
                    }
                }

                $planilla->update([
                    'tiempo_fabricacion' => $planilla->elementos->sum('tiempo_fabricacion'),
                ]);
            }

            DB::commit();
            return redirect()->route('planillas.index')->with('success', 'Planillas importadas con éxito.');
        } catch (Exception $e) {
            DB::rollBack();
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
                'fecha_estimada_entrega' => 'nullable|date_format:d/m/Y H:i',
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
                'fecha_estimada_entrega.date_format' => 'El campo Fecha Estimada de Entrega no corresponde al formato DD/MM/YYYY HH:mm.',
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
                $validatedData['fecha_estimada_entrega'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_estimada_entrega'])
                    ->format('Y-m-d H:i:s');
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
