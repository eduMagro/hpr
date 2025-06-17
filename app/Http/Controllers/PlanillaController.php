<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Etiqueta;
use App\Models\Obra;
use App\Models\User;
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
use App\Services\AsignacionMaquinaIAService;
use App\Models\OrdenPlanilla;

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
        if (! $maquinaSeleccionada) {
            Log::warning("⚠️ No se encontró máquina para el diámetro $diametro y longitud $longitud.");
            return null; // o throw new \Exception("Sin máquina compatible");
        }
        return $maquinaSeleccionada?->id ?? null;
    }
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('nombre_completo')) {
            $usuario = User::where(DB::raw("CONCAT(name, ' ', primer_apellido, ' ', segundo_apellido)"), 'like', '%' . $request->nombre_completo . '%')->first();

            $filtros[] = 'Responsable: <strong>' . ($usuario?->nombre_completo ?? $request->nombre_completo) . '</strong>';
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

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc'
                ? '▲' // flecha hacia arriba
                : '▼'; // flecha hacia abajo
        } else {
            $icon = '⇅'; // símbolo de orden genérico
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }
    //------------------------------------------------------------------------------------ FILTROS
    public function aplicarFiltros($query, Request $request)
    {
        // Filtro por usuario
        if ($request->filled('nombre_completo')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(users.name, ' ', users.primer_apellido, ' ', users.segundo_apellido)"), 'like', '%' . $request->nombre_completo . '%');
            });
        }


        // Filtro por código (columna directa)
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        // Filtro por código del cliente
        if ($request->filled('codigo_cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('codigo', 'like', '%' . $request->codigo_cliente . '%');
            });
        }

        // Filtro por nombre de cliente (empresa)
        if ($request->filled('cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('empresa', 'like', '%' . $request->cliente . '%');
            });
        }

        // Filtro por código de obra
        if ($request->filled('cod_obra')) {
            $query->whereHas('obra', function ($q) use ($request) {
                $q->where('cod_obra', 'like', '%' . $request->cod_obra . '%');
            });
        }

        // Filtro por nombre de obra
        if ($request->filled('nom_obra')) {
            $query->whereHas('obra', function ($q) use ($request) {
                $q->where('obra', 'like', '%' . $request->nom_obra . '%');
            });
        }

        // Filtros directos sobre columnas de la tabla planillas
        $query->when(
            $request->filled('seccion'),
            fn($q) =>
            $q->where('seccion', 'like', '%' . $request->seccion . '%')
        );

        $query->when(
            $request->filled('descripcion'),
            fn($q) =>
            $q->where('descripcion', 'like', '%' . $request->descripcion . '%')
        );

        $query->when(
            $request->filled('ensamblado'),
            fn($q) =>
            $q->where('ensamblado', 'like', '%' . $request->ensamblado . '%')
        );

        $query->when(
            $request->filled('estado'),
            fn($q) =>
            $q->where('estado', $request->estado)
        );

        // Fechas
        if ($request->filled('fecha_finalizacion')) {
            $query->whereDate('fecha_finalizacion', Carbon::parse($request->fecha_finalizacion)->format('Y-m-d'));
        }

        if ($request->filled('fecha_importacion')) {
            $query->whereDate('created_at', Carbon::parse($request->fecha_importacion)->format('Y-m-d'));
        }

        if ($request->filled('fecha_estimada_entrega')) {
            $query->whereDate('fecha_estimada_entrega', Carbon::parse($request->fecha_estimada_entrega)->format('Y-m-d'));
        }

        // Ordenación segura
        $sortBy = $request->input('sort', 'fecha_estimada_entrega');
        $order = $request->input('order', 'desc');

        // Validar sortBy para evitar SQL injection
        $allowedSorts = ['fecha_estimada_entrega', 'created_at', 'fecha_finalizacion', 'codigo', 'codigo_cliente', 'peso_total']; // Incluido 'peso_total' en las columnas permitidas para ordenamiento
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'fecha_estimada_entrega'; // Valor por defecto si no está en la lista
        }

        $query->orderBy($sortBy, $order);

        return $query;
    }
    //------------------------------------------------------------------------------------ INDEX()
    public function index(Request $request)
    {

        $user = auth()->user();
        $esAdmin = $user->esAdminDepartamento()
            || $user->esProduccionDepartamento(); // ⬅️ nuevo helper

        try {
            // 1️⃣ Iniciar la consulta base con relaciones
            $query = Planilla::with(['user', 'elementos', 'cliente', 'obra']);
            // Filtro “solo mis planillas” salvo admins
            if (! $esAdmin) {
                $query->where('users_id', $user->id);    // Ajusta el nombre de columna
            }

            // 2️⃣ Aplicar filtros desde el formulario (usando método personalizado)
            $query = $this->aplicarFiltros($query, $request);

            // 3️⃣ Definir columnas ordenables para la vista (cabecera de la tabla)
            $ordenables = [
                'codigo' => $this->getOrdenamiento('codigo', 'Código'),
                'codigo_cliente' => $this->getOrdenamiento('codigo_cliente', 'Código Cliente'),
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
                'nombre_completo' => $this->getOrdenamiento('nombre_completo', 'Usuario'),
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
            $planillasOmitidas   = [];
            $planillasImportadas = 0;
            $advertencias        = [];

            $file = $request->file('file');
            $importedData = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
            $firstSheet = $importedData[0] ?? [];

            if (empty($firstSheet)) {
                throw new \Exception('El archivo está vacío o no contiene datos válidos.');
            }

            $headers = $firstSheet[0] ?? [];
            $data = array_slice($firstSheet, 1);
            $filteredData = array_filter($data, fn($row) => array_filter($row));

            if (empty($filteredData)) {
                throw new \Exception('El archivo no contiene filas válidas después de las cabeceras.');
            }

            $groupedByCodigo = [];
            foreach ($filteredData as $row) {
                $codigo = $row[10] ?? 'Sin código';
                $groupedByCodigo[$codigo][] = $row;
            }
            $primerRow = $filteredData[0] ?? null;

            if (!$primerRow) {
                DB::rollBack();
                return redirect()->route('planillas.index')->with('error', 'No se encontraron datos válidos para determinar cliente y obra.');
            }

            $codigoCliente = trim($primerRow[0] ?? null);
            $nombreCliente = trim($primerRow[1] ?? 'Cliente sin nombre');

            $codigoObra = trim($primerRow[2] ?? null);
            $nombreObra = trim($primerRow[3] ?? 'Obra sin nombre');

            if (!$codigoCliente || !$codigoObra) {
                DB::rollBack();
                return redirect()->route('planillas.index')->with('error', 'Faltan códigos de cliente u obra en el archivo.');
            }

            $cliente = Cliente::where('codigo', $codigoCliente)->first();
            if (!$cliente) {
                $cliente = Cliente::create([
                    'codigo' => $codigoCliente,
                    'empresa' => $nombreCliente,
                ]);
            }

            $obra = Obra::where('cod_obra', $codigoObra)->first();
            if (!$obra) {
                $obra = Obra::create([
                    'cod_obra' => $codigoObra,
                    'cliente_id' => $cliente->id,
                    'obra' => $nombreObra,
                ]);
            }


            foreach ($groupedByCodigo as $codigo => $rows) {

                // 👉 Verifica si ya existe una planilla con este código
                if (Planilla::where('codigo', $codigo)->exists()) {
                    $planillasOmitidas[] = $codigo;
                    continue;
                }
                $pesoTotal = array_reduce($rows, fn($carry, $row) => $carry + (float)($row[34] ?? 0), 0);

                $codigoObra = trim($rows[0][2] ?? ''); // Ajusta el índice si el código está en otra columna

                $obra = Obra::where('cod_obra', $codigoObra)->first();

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
                $planillasImportadas++;

                $filasAgrupadasPorEtiqueta = [];
                foreach ($rows as $row) {
                    $numeroEtiqueta = $row[21] ?? null;
                    if ($numeroEtiqueta) {
                        $filasAgrupadasPorEtiqueta[$numeroEtiqueta][] = $row;
                    }
                }

                // Reemplaza tu bloque actual dentro del importador por esto

                // Reemplaza tu bloque actual dentro del importador por esto

                foreach ($filasAgrupadasPorEtiqueta as $numeroEtiqueta => $filas) {
                    $codigoPadre = Etiqueta::generarCodigoEtiqueta();

                    $etiquetaPadre = Etiqueta::create([
                        'codigo'          => $codigoPadre,
                        'planilla_id'     => $planilla->id,
                        'nombre'          => $filas[0][22] ?? 'Sin nombre',
                        'peso'            => 0,
                        'marca'           => null,
                        'etiqueta_sub_id' => null,
                    ]);

                    // Obtener el sufijo máximo ya existente (por si ya existen etiquetas con este padre)
                    $maxSufijo = Etiqueta::where('codigo', $codigoPadre)
                        ->where('etiqueta_sub_id', 'like', $codigoPadre . '.%')
                        ->selectRaw("COALESCE(MAX(CAST(SUBSTRING_INDEX(etiqueta_sub_id, '.', -1) AS UNSIGNED)), 0) AS max_sub")
                        ->value('max_sub');

                    $contadorSub = $maxSufijo + 1;

                    $gruposPorMaquina = [];
                    foreach ($filas as $row) {
                        $diametro = $row[25] ?? 0;
                        $longitud = $row[27] ?? 0;
                        $figura = $row[26] ?? null;
                        $doblesPorBarra = $row[33] ?? 0;
                        $barras = $row[32] ?? 0;
                        $ensamblado = $row[4] ?? null;

                        $maquina_id = $this->asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planilla->id);
                        if (!$maquina_id) {
                            $advertencias[] = "Fila {$row[21]} sin máquina compatible (planilla {$codigo}).";
                            continue;
                        }

                        $gruposPorMaquina[$maquina_id][] = ['row' => $row, 'maquina_id' => $maquina_id];
                    }

                    foreach ($gruposPorMaquina as $maquina_id => $grupo) {
                        $codigoSub = sprintf('%s.%02d', $codigoPadre, $contadorSub);

                        $subEtiqueta = Etiqueta::create([
                            'codigo'          => $codigoPadre,
                            'planilla_id'     => $planilla->id,
                            'nombre'          => $grupo[0]['row'][22] ?? 'Sin nombre',
                            'peso'            => 0,
                            'marca'           => null,
                            'etiqueta_sub_id' => $codigoSub,
                        ]);

                        $ultimaPosicion = OrdenPlanilla::where('maquina_id', $maquina_id)
                            ->max('posicion') ?? 0;
                        OrdenPlanilla::create([
                            'planilla_id' => $planilla->id,
                            'maquina_id'  => $maquina_id,
                            'posicion'    => $ultimaPosicion + 1,
                        ]);

                        $agrupados = [];
                        foreach ($grupo as $item) {
                            $row = $item['row'];
                            $clave = implode('|', [
                                $row[26],
                                $row[21],
                                $row[23],
                                $row[25],
                                $row[27],
                                $row[33] ?? 0,
                                $row[47] ?? '',
                            ]);

                            if (!isset($agrupados[$clave])) {
                                $agrupados[$clave] = [
                                    'row'   => $row,
                                    'peso'  => (float)($row[34] ?? 0),
                                    'barras' => (int)($row[32] ?? 0),
                                ];
                            } else {
                                $agrupados[$clave]['peso']  += (float)($row[34] ?? 0);
                                $agrupados[$clave]['barras'] += (int)($row[32] ?? 0);
                            }
                        }

                        foreach ($agrupados as $item) {
                            $row = $item['row'];
                            $tiempos = $this->calcularTiemposElemento($row);
                            $codigoElemento = Elemento::generarCodigo();
                            Elemento::create([
                                'codigo'              => $codigoElemento,
                                'planilla_id'         => $planilla->id,
                                'etiqueta_id'         => $subEtiqueta->id,
                                'etiqueta_sub_id'     => $codigoSub,
                                'maquina_id'          => $maquina_id,
                                'figura'              => $row[26],
                                'fila'                => $row[21],
                                'marca'               => $row[23],
                                'etiqueta'            => $row[30],
                                'diametro'            => $row[25],
                                'longitud'            => $row[27],
                                'barras'              => $item['barras'],
                                'dobles_barra'        => $row[33] ?? 0,
                                'peso'                => $item['peso'],
                                'dimensiones'         => $row[47] ?? null,
                                'tiempo_fabricacion'  => $tiempos['tiempo_fabricacion'],
                            ]);
                        }

                        $subEtiqueta->peso = $subEtiqueta->elementos()->sum('peso');
                        $subEtiqueta->marca = $subEtiqueta->elementos()
                            ->select('marca', DB::raw('COUNT(*) as total'))
                            ->groupBy('marca')
                            ->orderByDesc('total')
                            ->value('marca');
                        $subEtiqueta->save();

                        $contadorSub++;   // siguiente sufijo
                    }
                }




                $elementos = $planilla->elementos;
                $tiempoBase = $elementos->sum('tiempo_fabricacion');
                $tiempoAdicional = $elementos->count() * 1200; // 20 minutos por elemento

                $planilla->update([
                    'tiempo_fabricacion' => $tiempoBase + $tiempoAdicional,
                ]);
            }

            DB::commit();
            $mensaje = "✅ Se importaron {$planillasImportadas} planilla(s).";

            if ($planillasOmitidas) {
                $mensaje .= ' ⚠️ Omitidas por duplicado: ' . implode(', ', $planillasOmitidas) . '.';
            }

            if ($advertencias) {   // <-- now it’s always defined, maybe still empty
                $mensaje .= ' ⚠️ Advertencias: ' . implode(' | ', $advertencias);
            }


            return redirect()->route('planillas.index')->with('success', $mensaje);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('⚠️ Validación fallida al importar planillas.', [
                'errores' => $e->errors(),
            ]);

            return redirect()->route('planillas.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {          // \Throwable = Exception + Error
            DB::rollBack();

            // 1) Regístralo con todo el detalle
            Log::error('❌ Error al importar planillas', [
                'mensaje'  => $e->getMessage(),
                'linea'    => $e->getLine(),
                'archivo'  => $e->getFile(),
                'trace'    => $e->getTraceAsString(),
            ]);

            // 2) Construye un texto claro para el usuario
            //    (muestra el tipo de excepción y sólo el mensaje)
            $msg = class_basename($e) . ': ' . $e->getMessage();

            //    ⚠️  Si el mensaje es muy largo, limítalo:
            // use Illuminate\Support\Str;
            // $msg = Str::limit($msg, 180);

            return redirect()
                ->route('planillas.index')
                ->with('error', $msg);     // En la vista => session('error')
        }
    }

    public function reimportar(Request $request, Planilla $planilla)
    {
        // 1. Autorización
        if (auth()->user()->rol !== 'oficina') {
            return back()->with('abort', 'No tienes los permisos necesarios.');
        }

        // 2. Validación
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.mimes'    => 'El archivo debe ser .xlsx o .xls',
        ]);

        DB::beginTransaction();

        try {
            /* ────────────────────────────
         | 3. Leer y parsear el Excel |
         ────────────────────────────*/
            $file         = $request->file('archivo');
            $importedData = Excel::toArray([], $file);
            $firstSheet   = $importedData[0] ?? [];

            if (empty($firstSheet)) {
                throw new \Exception('El archivo está vacío o no contiene datos válidos.');
            }

            // Separar cabecera y filas
            $headers      = $firstSheet[0] ?? [];
            $rows         = array_filter(array_slice($firstSheet, 1), fn($r) => array_filter($r));

            if (!$rows) {
                throw new \Exception('El archivo no tiene filas de datos.');
            }

            /* ─────────────────────────────
         | 4. Limpiar elementos viejos |
         ─────────────────────────────*/
            $pendientes = $planilla->elementos()->where('estado', 'pendiente')->get();

            //   a) Elimina los elementos pendientes (o cámbialos a «reemplazado» si quieres histórico)
            foreach ($pendientes as $el) {
                $el->delete();                 // <─ usa soft-deletes si los tienes habilitados
            }

            //   b) Elimina etiquetas sin elementos
            Etiqueta::where('planilla_id', $planilla->id)
                ->whereDoesntHave('elementos')
                ->delete();

            /* ──────────────────────────────
         | 5. Re-insertar nuevos datos  |
         ──────────────────────────────*/
            // Agrupar por número de etiqueta (columna 21)
            $agrupadasPorEtiqueta = [];
            foreach ($rows as $row) {
                $nEtiqueta = $row[21] ?? null;
                if ($nEtiqueta) {
                    $agrupadasPorEtiqueta[$nEtiqueta][] = $row;
                }
            }

            $advertencias = [];

            foreach ($agrupadasPorEtiqueta as $numeroEtiqueta => $filasEtiqueta) {
                // 5.1 Crear etiqueta padre
                $codigoPadre   = Etiqueta::generarCodigoEtiqueta();
                $etiquetaPadre = Etiqueta::create([
                    'codigo'          => $codigoPadre,
                    'planilla_id'     => $planilla->id,
                    'nombre'          => $filasEtiqueta[0][22] ?? 'Sin nombre',
                    'peso'            => 0,
                    'marca'           => null,
                    'etiqueta_sub_id' => null,
                ]);

                $contadorSub = 1;

                // Agrupar por máquina
                $gruposPorMaquina = [];
                foreach ($filasEtiqueta as $row) {
                    $diam   = $row[25] ?? 0;
                    $lon    = $row[27] ?? 0;
                    $fig    = $row[26] ?? null;
                    $dobles = $row[33] ?? 0;
                    $barras = $row[32] ?? 0;
                    $ensamb = $row[4]  ?? null;

                    $maquinaId = $this->asignarMaquina(
                        $diam,
                        $lon,
                        $fig,
                        $dobles,
                        $barras,
                        $ensamb,
                        $planilla->id
                    );

                    if (!$maquinaId) {
                        $advertencias[] = "Fila {$row[21]} sin máquina compatible.";
                        continue;
                    }
                    $gruposPorMaquina[$maquinaId][] = $row;
                }

                foreach ($gruposPorMaquina as $maquinaId => $filasMaquina) {
                    // 5.2 Sub-etiqueta
                    $codigoSub = sprintf('%s.%02d', $codigoPadre, $contadorSub++);
                    $subEtiqueta = Etiqueta::create([
                        'codigo'          => $codigoPadre,
                        'planilla_id'     => $planilla->id,
                        'nombre'          => $filasMaquina[0][22] ?? 'Sin nombre',
                        'peso'            => 0,
                        'marca'           => null,
                        'etiqueta_sub_id' => $codigoSub,
                    ]);

                    // 5.3 Posicionamiento en la cola de la máquina
                    $ultimaPos = OrdenPlanilla::where('maquina_id', $maquinaId)->max('posicion') ?? 0;
                    OrdenPlanilla::create([
                        'planilla_id' => $planilla->id,
                        'maquina_id'  => $maquinaId,
                        'posicion'    => $ultimaPos + 1,
                    ]);

                    // 5.4 Agrupar filas idénticas y crear elementos
                    $agrupados = [];
                    foreach ($filasMaquina as $row) {
                        $clave = implode('|', [
                            $row[26],
                            $row[21],
                            $row[23],
                            $row[25],
                            $row[27],
                            $row[33] ?? 0,
                            $row[47] ?? ''
                        ]);

                        if (!isset($agrupados[$clave])) {
                            $agrupados[$clave] = [
                                'row'    => $row,
                                'peso'   => (float)($row[34] ?? 0),
                                'barras' => (int)($row[32] ?? 0),
                            ];
                        } else {
                            $agrupados[$clave]['peso']   += (float)($row[34] ?? 0);
                            $agrupados[$clave]['barras'] += (int)($row[32] ?? 0);
                        }
                    }

                    foreach ($agrupados as $item) {
                        $row     = $item['row'];
                        $tiempos = $this->calcularTiemposElemento($row);

                        Elemento::create([
                            'codigo'             => Elemento::generarCodigo(),
                            'planilla_id'        => $planilla->id,
                            'etiqueta_id'        => $subEtiqueta->id,
                            'etiqueta_sub_id'    => $codigoSub,
                            'maquina_id'         => $maquinaId,
                            'figura'             => $row[26],
                            'fila'               => $row[21],
                            'marca'              => $row[23],
                            'etiqueta'           => $row[30],
                            'diametro'           => $row[25],
                            'longitud'           => $row[27],
                            'barras'             => $item['barras'],
                            'dobles_barra'       => $row[33] ?? 0,
                            'peso'               => $item['peso'],
                            'dimensiones'        => $row[47] ?? null,
                            'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                            'estado'             => 'pendiente', // nuevo
                        ]);
                    }

                    // 5.5 Peso y marca de la sub-etiqueta
                    $subEtiqueta->peso = $subEtiqueta->elementos()->sum('peso');
                    $subEtiqueta->marca = $subEtiqueta->elementos()
                        ->select('marca', DB::raw('COUNT(*) as tot'))
                        ->groupBy('marca')->orderByDesc('tot')->value('marca');
                    $subEtiqueta->save();
                }
            }

            /* ───────────────────────────────
         | 6. Recalcular planilla global |
         ───────────────────────────────*/
            $pesoTotal          = $planilla->elementos()->sum('peso');
            $tiempoBase         = $planilla->elementos()->sum('tiempo_fabricacion');
            $tiempoAdicional    = $planilla->elementos()->count() * 1200; // 20 min/el
            $planilla->peso_total         = $pesoTotal;
            $planilla->tiempo_fabricacion = $tiempoBase + $tiempoAdicional;
            $planilla->save();

            DB::commit();

            $msg = "🔄 Reimportación completada. Peso total: {$pesoTotal} kg.";
            if ($advertencias) {
                $msg .= ' ⚠️ ' . implode(' | ', $advertencias);
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Error al reimportar planilla', [
                'planilla' => $planilla->codigo,
                'msg'      => $e->getMessage(),
                'line'     => $e->getLine(),
                'file'     => $e->getFile(),
                'trace'    => $e->getTraceAsString(),
            ]);
            return back()->with('error', $e->getMessage());
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
