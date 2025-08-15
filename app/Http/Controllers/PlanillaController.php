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
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\AsignacionMaquinaIAService;
use App\Models\OrdenPlanilla;
use App\Services\PlanillaService;
use ZipArchive;
use DOMDocument;
use App\Services\ColaPlanillasService;

class PlanillaController extends Controller
{
    public function asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planillaId)
    {
        $estribo = $doblesPorBarra >= 4 && $diametro < 20;
        $maquinas = collect();
        $diametrosPlanilla = Elemento::where('planilla_id', $planillaId)->distinct()->pluck('diametro')->toArray();
        $maquinaForzada = null;

        // ✅ 0. CASO BLOQUEANTE: Diámetro 32
        if ($diametro == 32) {
            return Maquina::where('codigo', 'CM')->value('id');
        }

        // ⚙️ 1. Si ya hay máquina en esta planilla (sin contar estribos y diámetro 32)
        $maquinaUsada = Elemento::where('planilla_id', $planillaId)
            ->whereNotNull('maquina_id')
            ->where('diametro', '!=', 32)
            ->where(function ($q) {
                $q->where('dobles_barra', '<', 5)->orWhere('diametro', '>=', 20);
            })
            ->value('maquina_id');

        if ($maquinaUsada) {
            $pesoMaquinaUsada = Elemento::where('maquina_id', $maquinaUsada)->sum('peso');
            $elementosMaquinaUsada = Elemento::where('maquina_id', $maquinaUsada)->count();

            $indiceCargaUsada = ($pesoMaquinaUsada * 0.6) + ($elementosMaquinaUsada * 0.4);

            // Si la carga está dentro de límites, se mantiene
            if ($indiceCargaUsada < 9000) { // Umbral a ajustar según tu realidad
                return $maquinaUsada;
            }

            // Si está saturada, NO se fuerza su uso, se continúa al reparto
        }


        // 🎯 2. Si hay varios diámetros, se fuerza máquina según el mayor (solo si no es 32)
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
            } elseif ($maxDiametro >= 32) {
                $maquinaForzada = ['CM'];
            }
        }

        // 🛠️ 3. Asignación lógica según tipo
        if ($diametro == 5) {
            $maquinas = Maquina::where('codigo', 'ID5')->get();
        } elseif ($estribo) {
            if ($diametro == 8) {
                $maquinas = Maquina::where('codigo', 'PS12')->get();
            } elseif (in_array($diametro, [10, 12])) {
                $maquinas = Maquina::whereIn('codigo', ['F12', 'PS12'])->get();
            } elseif ($diametro == 16) {
                $maquinas = Maquina::whereIn('codigo', ['PS12', 'F12', 'MS16'])->get();
            }
        } elseif (!$estribo && $diametro >= 10 && $diametro <= 16) {
            $maquinas = Maquina::where('codigo', 'MS16')->get();
        } elseif (!$estribo && $diametro >= 8 && $diametro <= 25) {
            $codigos = [];

            if ($diametro <= 20) {
                $codigos[] = 'MSR20';
            }

            if ($diametro >= 12) {
                $codigos[] = 'SL28';
            }

            $maquinas = Maquina::whereIn('codigo', $codigos)->get();
        } else {
            $maquinas = Maquina::where('codigo', 'MANUAL')->get();
        }

        // ❌ Verificación adicional: evitar máquinas que no soportan el diámetro
        $maquinas = $maquinas->filter(function ($m) use ($diametro) {
            return is_null($m->diametro_max) || $diametro <= $m->diametro_max;
        });

        if ($maquinas->isEmpty()) {
            Log::warning("❌ No se encontraron máquinas compatibles para diámetro $diametro.");
            return null;
        }

        // ⚖️ 4. Elegir la menos cargada (peso total)
        $maquinaSeleccionada = null;
        $indiceMinimo = PHP_INT_MAX;

        foreach ($maquinas as $maquina) {
            $pesoActual = Elemento::where('maquina_id', $maquina->id)->sum('peso');
            $elementosActuales = Elemento::where('maquina_id', $maquina->id)->count();

            // Cálculo del índice ponderado
            $indiceCarga = ($pesoActual * 0.6) + ($elementosActuales * 0.4);

            if ($indiceCarga < $indiceMinimo) {
                $indiceMinimo = $indiceCarga;
                $maquinaSeleccionada = $maquina;
            }
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

    private function aplicarOrdenamiento($query, Request $request)
    {
        $sortBy = $request->input('sort', 'created_at');
        $order  = $request->input('order', 'desc');

        $columnasPermitidas = [
            'codigo',
            'codigo_cliente',
            'cliente',
            'cod_obra',
            'nom_obra',
            'seccion',
            'descripcion',
            'ensamblado',
            'comentario',
            'peso_fabricado',
            'peso_total',
            'estado',
            'fecha_inicio',
            'fecha_finalizacion',
            'fecha_importacion',
            'fecha_entrega',
            'nombre_completo',
            'created_at', // por si quieres permitir también esta
        ];

        if (!in_array($sortBy, $columnasPermitidas, true)) {
            $sortBy = 'fecha_estimada_entrega'; // Fallback seguro
        }

        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $order);
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


        // Filtro por códigos parciales separados por comas
        if ($request->filled('codigo')) {
            $codigos = array_filter(
                array_map('trim', explode(',', $request->codigo))
            );

            $query->where(function ($q) use ($codigos) {
                foreach ($codigos as $codigo) {
                    $q->orWhere('codigo', 'like', '%' . $codigo . '%');
                }
            });
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
            $query->whereDate(
                'fecha_estimada_entrega',
                Carbon::parse($request->fecha_estimada_entrega)->format('Y-m-d')
            );
        }



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

            $query = $this->aplicarFiltros($query, $request);
            $query = $this->aplicarOrdenamiento($query, $request);


            $totalPesoFiltrado = (clone $query)->sum('peso_total');
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
                'filtrosActivos',
                'totalPesoFiltrado',
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

    /**
     * Valida si una cadena es numérica válida en XML de Excel (punto decimal y notación científica).
     */
    private function isNumericXmlValue(?string $v): bool
    {
        if ($v === null || $v === '') return true; // vacío se permite
        // número con punto opcional y exponente opcional. Excel en XML usa '.' como decimal.
        return (bool) preg_match('/^-?\d+(\.\d+)?([Ee][+-]?\d+)?$/', trim($v));
    }

    /**
     * Escanea xl/worksheets/sheet1.xml y devuelve celdas marcadas como numéricas con valor inválido.
     * Devuelve array de ['cell' => 'C27', 'value' => '12,3a'].
     */
    private function scanXlsxForInvalidNumeric(string $xlsxPath, int $maxFindings = 5): array
    {
        $bad = [];
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            return $bad; // no pudimos abrir; preferimos no bloquear
        }

        // Solo primera hoja: sheet1.xml
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($xml === false) {
            $zip->close();
            return $bad;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $cNodes = $dom->getElementsByTagName('c'); // c = cell
        foreach ($cNodes as $c) {
            /** @var DOMElement $c */
            $tAttr = $c->getAttribute('t'); // tipo
            $rAttr = $c->getAttribute('r'); // dirección (ej. C27)

            // Solo revisamos celdas numéricas: t=="n" o sin t (numérico por defecto)
            if ($tAttr === '' || $tAttr === 'n') {
                $vNode = $c->getElementsByTagName('v')->item(0);
                $val = $vNode ? $vNode->textContent : '';

                if (!$this->isNumericXmlValue($val)) {
                    $bad[] = [
                        'cell'  => $rAttr ?: '(sin ref)',
                        'value' => $val,
                    ];
                    if (count($bad) >= $maxFindings) break;
                }
            }
        }

        $zip->close();
        return $bad;
    }
    private function leerPrimeraHojaConFila(\Illuminate\Http\UploadedFile $file): array
    {
        // Crea reader tolerante y solo datos (sin estilos/formulas)
        $reader = IOFactory::createReaderForFile($file->getRealPath());

        // Si es Xlsx/Xls, estos métodos existen; si no, no pasa nada
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getSheet(0);

        $rows = [];
        foreach ($sheet->getRowIterator(1) as $row) {
            $rowIndex = $row->getRowIndex(); // nº de fila real en Excel (1-based)
            try {
                $cellIt = $row->getCellIterator();
                $cellIt->setIterateOnlyExistingCells(false); // incluye vacías

                $rowData = [];
                foreach ($cellIt as $cell) {
                    // getFormattedValue evita algunos casteos agresivos
                    $rowData[] = $cell ? $cell->getFormattedValue() : null;
                }
                $rows[] = $rowData;
            } catch (\Throwable $e) {
                // Aquí capturamos exactamente la fila que revienta al leer
                throw new \Exception("Error leyendo Excel en fila {$rowIndex}: " . $e->getMessage());
            }
        }

        return $rows;
    }
    private function assertNumeric($valor, $campo, $excelRow, $codigoPlanilla, &$advertencias = [])
    {
        if ($valor === null || $valor === '') return 0;

        $raw = trim((string)$valor);

        // Normaliza: "1.234,56" -> "1234.56", "1,23" -> "1.23"
        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $norm = str_replace('.', '', $raw);
            $norm = str_replace(',', '.', $norm);
        } elseif (strpos($raw, ',') !== false) {
            $norm = str_replace(',', '.', $raw);
        } else {
            $norm = $raw;
        }

        if (!preg_match('/^-?\d+(\.\d+)?$/', $norm)) {
            $advertencias[] = "⚠️ Fila omitida (planilla {$codigoPlanilla}, Excel {$excelRow}): {$campo}='{$valor}' no es numérico.";
            return false;
        }

        $num = (float)$norm;

        // Regla: barras no puede ser negativo
        if ($campo === 'barras' && $num < 0) {
            $advertencias[] = "⚠️ Fila omitida (planilla {$codigoPlanilla}, Excel {$excelRow}): {$campo} negativo ('{$valor}').";
            return false;
        }

        return $num;
    }

    /**
     * Detecta valores que "parecen" numéricos por nombre de campo, pero contienen caracteres no numéricos.
     */
    private function suspectFields(array $attrs): array
    {
        $suspects = [];
        foreach ($attrs as $k => $v) {
            if (is_string($v) && preg_match('/(peso|long|diam|barra|posicion|tiempo|id|cantidad|total|codigo)/i', $k)) {
                if (preg_match('/[^\d.,-]/', $v)) {
                    $suspects[] = "{$k}='{$v}'";
                }
            }
        }
        return $suspects;
    }

    /**
     * Crea un modelo y si la BD revienta (NUMERIC inválido, etc.),
     * re-lanza la excepción con contexto (planilla, fila Excel, attrs, últimos SQL).
     */
    private function safeCreate(string $modelClass, array $attrs, array $ctx)
    {
        try {
            return $modelClass::create($attrs);
        } catch (\Throwable $e) {
            $planilla = $ctx['planilla']  ?? 'N/D';
            $excelRow = $ctx['excel_row'] ?? 'N/D';
            $suspects = $this->suspectFields($attrs);

            // Última query registrada
            $log = DB::getQueryLog();
            $last = $log ? $log[array_key_last($log)] : null;

            Log::error("[IMPORT] Error creando {$modelClass}", [
                'planilla'   => $planilla,
                'excel_row'  => $excelRow,
                'attrs'      => $attrs,
                'suspects'   => $suspects,
                'error'      => $e->getMessage(),
                'last_query' => $last,
            ]);

            $susStr = $suspects ? (' | Sospechosos: ' . implode(', ', $suspects)) : '';
            throw new Exception("Fila {$excelRow} (Excel), Planilla {$planilla}: error al crear {$modelClass} → {$e->getMessage()}{$susStr}");
        }
    }

    public function import(Request $request)
    {
        // 1) Seguridad -----------------------------------------------------------------
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')
                ->with('abort', 'No tienes los permisos necesarios.');
        }

        // ⚠️ Contexto para warnings numéricos
        $__numCtx = ['planilla' => 'N/D', 'excel_row' => 0, 'campo' => 'N/D', 'valor' => null];

        // 2) Validación del archivo -----------------------------------------------------
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file'     => 'El archivo debe ser válido.',
            'file.mimes'    => 'Solo se permiten archivos .xlsx o .xls',
        ]);

        // Activar trazas SQL
        DB::enableQueryLog();

        DB::beginTransaction();
        try {
            /* -------------------------------------------------- */
            /* Preparativos                                       */
            /* -------------------------------------------------- */
            $planillasImportadas = 0;
            $planillasOmitidas   = [];
            $advertencias        = [];
            $file = $request->file('file');
            $DmPermitido = [5, 8, 10, 12, 16, 20, 25, 32];
            // NO lanzar excepción en avisos de no-numérico: solo añadir advertencia y continuar
            set_error_handler(function ($sev, $msg, $file, $line) use (&$advertencias, &$__numCtx) {
                if ($sev === E_WARNING && str_contains($msg, 'A non-numeric value encountered')) {
                    $advertencias[] = "⚠️ Valor no numérico detectado; se omitió la fila. "
                        . "Planilla {$__numCtx['planilla']}, Fila {$__numCtx['excel_row']}, Campo '{$__numCtx['campo']}', Valor '" . (string)($__numCtx['valor']) . "'";
                    return true; // ✅ warning manejado: NO propagar, NO lanzar
                }
                return false; // deja pasar otros warnings/errores al manejador por defecto
            });

            // Escaneo XML previo (detectar celdas mal tipadas)
            $invalids = $this->scanXlsxForInvalidNumeric($file->getRealPath());
            if (!empty($invalids)) {
                $detalles = collect($invalids)->map(fn($i) => "{$i['cell']} → '{$i['value']}'")->implode(', ');
                $nombreArchivo = $file->getClientOriginalName();
                throw new \Exception("{$nombreArchivo} - El Excel contiene celdas marcadas como numéricas con valor inválido: {$detalles}. Corrige esas celdas (pon número válido o cambia el tipo de celda a Texto) y vuelve a importar.");
            }

            // Lectura con fila-controlada
            $firstSheet = $this->leerPrimeraHojaConFila($file);

            // quitar cabecera, filtrar vacías, reindexar
            $body         = array_slice($firstSheet, 1);
            $filteredData = array_values(array_filter($body, fn($row) => array_filter($row)));

            if (!$filteredData) {
                throw new \Exception('El archivo está vacío o no contiene filas válidas.');
            }

            // anotar nº de fila de Excel (cabecera = 1 ⇒ +2)
            foreach ($filteredData as $i => &$row) {
                $row['_xl_row'] = $i + 2;
            }
            unset($row);

            /* -------------------------------------------------- */
            /* Datos fijos (cliente y obra)                       */
            /* -------------------------------------------------- */
            $primerRow     = $filteredData[0];
            $codigoCliente = trim($primerRow[0] ?? '');
            $nombreCliente = trim($primerRow[1] ?? 'Cliente sin nombre');
            $codigoObra    = trim($primerRow[2] ?? '');
            $nombreObra    = trim($primerRow[3] ?? 'Obra sin nombre');

            if (!$codigoCliente || !$codigoObra) {
                throw new \Exception('Faltan códigos de cliente u obra en el archivo.');
            }

            $cliente = Cliente::firstOrCreate(
                ['codigo' => $codigoCliente],
                ['empresa' => $nombreCliente]
            );

            $obra = Obra::firstOrCreate(
                ['cod_obra' => $codigoObra],
                ['cliente_id' => $cliente->id, 'obra' => $nombreObra]
            );

            /* -------------------------------------------------- */
            /* Agrupar por código de planilla                     */
            /* -------------------------------------------------- */
            $planillas = [];
            foreach ($filteredData as $row) {
                $codigoPlanilla = $row[10] ?? 'Sin código';
                $planillas[$codigoPlanilla][] = $row;
            }

            $fechaEntrega = now()->addDays(7)->setTime(10, 0, 0);

            /* ================================================== */
            /* Bucle principal : una iteración por planilla       */
            /* ================================================== */
            foreach ($planillas as $codigoPlanilla => $rows) {

                if (Planilla::where('codigo', $codigoPlanilla)->exists()) {
                    $planillasOmitidas[] = $codigoPlanilla;
                    continue;
                }

                // Calcular peso_total con validación explícita
                $pesoTotal = 0;
                foreach ($rows as $r) {
                    $excelRow = $r['_xl_row'] ?? 0;
                    $pesoTotal += $this->assertNumeric($r[34] ?? null, 'peso', $excelRow, $codigoPlanilla, $advertencias);
                }

                // Crear planilla
                $planilla = $this->safeCreate(Planilla::class, [
                    'users_id'               => auth()->id(),
                    'cliente_id'             => $cliente->id,
                    'obra_id'                => $obra->id,
                    'seccion'                => $rows[0][7]  ?? null,
                    'descripcion'            => $rows[0][12] ?? null,
                    'ensamblado'             => $rows[0][4]  ?? null,
                    'codigo'                 => $codigoPlanilla,
                    'peso_total'             => $pesoTotal,
                    'fecha_estimada_entrega' => $fechaEntrega,
                ], [
                    'planilla'  => $codigoPlanilla,
                    'excel_row' => $rows[0]['_xl_row'] ?? 0,
                ]);

                $planillasImportadas++;
                $maquinasUsadas = [];

                // Agrupar por nº de etiqueta
                $etiquetas = [];
                foreach ($rows as $row) {
                    $numEtiqueta = $row[21] ?? null;
                    if ($numEtiqueta) $etiquetas[$numEtiqueta][] = $row;
                }

                // Procesar etiquetas → sub-etiquetas → elementos
                foreach ($etiquetas as $filasEtiqueta) {
                    $codigoPadre   = Etiqueta::generarCodigoEtiqueta();

                    $etiquetaPadre = $this->safeCreate(Etiqueta::class, [
                        'codigo'      => $codigoPadre,
                        'planilla_id' => $planilla->id,
                        'nombre'      => $filasEtiqueta[0][22] ?? 'Sin nombre',
                    ], [
                        'planilla'  => $codigoPlanilla,
                        'excel_row' => $filasEtiqueta[0]['_xl_row'] ?? 0,
                    ]);

                    $contadorSub = 1;

                    // Agrupar por máquina
                    $gruposPorMaquina = [];
                    foreach ($filasEtiqueta as $row) {
                        $excelRow       = $row['_xl_row'] ?? 0;

                        $diametro       = $row[25] ?? null;
                        $longitud       = $row[27] ?? null;
                        $figura         = $row[26] ?? null;
                        $doblesPorBarra = $row[33] ?? null;
                        $barras         = $row[32] ?? null;
                        $ensamblado     = $row[4]  ?? null;


                        // Normaliza por si viene con coma o espacios
                        $rawDiametro = $row[25] ?? null;
                        $diametroStr = is_string($rawDiametro) ? trim($rawDiametro) : $rawDiametro;
                        $diametroNum = is_null($diametroStr) ? null : (float) str_replace(',', '.', $diametroStr);
                        $diametroInt = is_null($diametroNum) ? null : (int) round($diametroNum);

                        // ⛔ Si no es permitido, lanzamos advertencia y forzamos máquina nula
                        if (is_null($diametroInt) || !in_array($diametroInt, $DmPermitido, true)) {
                            $advertencias[] = sprintf(
                                "Diámetro no admitido (planilla %s) → diámetro:%s (fila %d)",
                                $codigoPlanilla,
                                $row[25] ?? 'N/A',
                                $excelRow
                            );
                            $maquina_id = null;   // evita que pase desapercibido
                        } else {
                            // Solo si es permitido, calculamos máquina
                            $maquina_id = $this->asignarMaquina(
                                $diametroInt,
                                $longitud,
                                $figura,
                                $doblesPorBarra,
                                $barras,
                                $ensamblado,
                                $planilla->id
                            );
                            if (!$maquina_id) {
                                $advertencias[] = sprintf(
                                    "Sin máquina compatible (planilla %s) → diámetro:%s | dimensiones:%s (fila %d)",
                                    $codigoPlanilla,
                                    $row[25] ?? 'N/A',
                                    $row[47] ?? 'N/A',
                                    $excelRow
                                );
                                $maquina_id = null;
                            }
                        }




                        $gruposPorMaquina[$maquina_id][] = $row;
                        if ($maquina_id !== null) $maquinasUsadas[$maquina_id] = true;
                    }

                    // Crear sub-etiquetas y elementos
                    foreach ($gruposPorMaquina as $maquina_id => $filasMaquina) {
                        $codigoSub = sprintf('%s.%02d', $codigoPadre, $contadorSub++);

                        $subEtiqueta = $this->safeCreate(Etiqueta::class, [
                            'codigo'          => $codigoPadre,
                            'planilla_id'     => $planilla->id,
                            'nombre'          => $filasMaquina[0][22] ?? 'Sin nombre',
                            'etiqueta_sub_id' => $codigoSub,
                        ], [
                            'planilla'  => $codigoPlanilla,
                            'excel_row' => $filasMaquina[0]['_xl_row'] ?? 0,
                        ]);

                        // Agrupar filas iguales
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
                            $agrupados[$clave]['row'] = $row;

                            $excelRow = $row['_xl_row'] ?? 0;

                            // contexto para warnings
                            $__numCtx['planilla']  = $codigoPlanilla;
                            $__numCtx['excel_row'] = $excelRow;

                            $__numCtx['campo'] = 'peso';
                            $__numCtx['valor'] = $row[34] ?? null;
                            $pesoNum  = $this->assertNumeric($row[34] ?? null, 'peso',   $excelRow, $codigoPlanilla);

                            $__numCtx['campo'] = 'barras';
                            $__numCtx['valor'] = $row[32] ?? null;
                            $bNum     = $this->assertNumeric($row[32] ?? null, 'barras', $excelRow, $codigoPlanilla);

                            $agrupados[$clave]['peso']   = ($agrupados[$clave]['peso']   ?? 0) + $pesoNum;
                            $agrupados[$clave]['barras'] = ($agrupados[$clave]['barras'] ?? 0) + (int) $bNum;
                        }

                        foreach ($agrupados as $item) {
                            $row      = $item['row'];
                            $excelRow = $row['_xl_row'] ?? 0;

                            // Validar todos los campos críticos
                            $diametroNum = $this->assertNumeric($row[25] ?? null, 'diametro', $excelRow, $codigoPlanilla, $advertencias);
                            $longNum     = $this->assertNumeric($row[27] ?? null, 'longitud', $excelRow, $codigoPlanilla, $advertencias);
                            $doblesNum   = $this->assertNumeric($row[33] ?? 0,    'dobles_barra', $excelRow, $codigoPlanilla, $advertencias);
                            $barrasNum   = $this->assertNumeric($item['barras'],  'barras', $excelRow, $codigoPlanilla, $advertencias);
                            $pesoNum     = $this->assertNumeric($item['peso'],    'peso', $excelRow, $codigoPlanilla, $advertencias);

                            // ⛔ Si alguno es false → saltamos fila
                            if ($diametroNum === false || $longNum === false || $doblesNum === false || $barrasNum === false || $pesoNum === false) {
                                continue;
                            }

                            // ✅ Solo si todo es válido, creamos el elemento
                            $tiempos = $this->calcularTiemposElemento($row);

                            $this->safeCreate(Elemento::class, [
                                'codigo'             => Elemento::generarCodigo(),
                                'planilla_id'        => $planilla->id,
                                'etiqueta_id'        => $subEtiqueta->id,
                                'etiqueta_sub_id'    => $codigoSub,
                                'maquina_id'         => $maquina_id ?: null,
                                'figura'             => $row[26] ?: null,
                                'fila'               => $row[21] ?: null,
                                'marca'              => $row[23] ?: null,
                                'etiqueta'           => $row[30] ?: null,
                                'diametro'           => $diametroNum,
                                'longitud'           => $longNum,
                                'barras'             => (int) $barrasNum,
                                'dobles_barra'       => (int) $doblesNum,
                                'peso'               => $pesoNum,
                                'dimensiones'        => $row[47] ?? null,
                                'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                            ], [
                                'planilla'  => $codigoPlanilla,
                                'excel_row' => $excelRow,
                            ]);
                        }


                        // Actualizar peso/marca de la sub-etiqueta
                        $subEtiqueta->update([
                            'peso'  => $subEtiqueta->elementos()->sum('peso'),
                            'marca' => $subEtiqueta->elementos()
                                ->whereNotNull('marca')
                                ->select('marca', DB::raw('COUNT(*) as total'))
                                ->groupBy('marca')
                                ->orderByDesc('total')
                                ->value('marca'),
                        ]);
                    }
                }

                /* -------------------------------------------------- */
                /*  Crear orden_planillas una sola vez por máquina    */
                /* -------------------------------------------------- */
                foreach (array_keys($maquinasUsadas ?? []) as $maquina_id) {
                    OrdenPlanilla::firstOrCreate(
                        ['planilla_id' => $planilla->id, 'maquina_id' => $maquina_id],
                        ['posicion'    => (OrdenPlanilla::where('maquina_id', $maquina_id)->max('posicion') ?? 0) + 1]
                    );
                }

                /* -------------------------------------------------- */
                /*  Tiempo total planilla                             */
                /* -------------------------------------------------- */
                $elementos   = $planilla->elementos;
                $tiempoTotal = $elementos->sum('tiempo_fabricacion') + $elementos->count() * 1200;
                $planilla->update(['tiempo_fabricacion' => $tiempoTotal]);
            } // fin foreach planillas

            /* ------------------------------------------------------ */
            /*  Fin: commit + mensaje                                 */
            /* ------------------------------------------------------ */
            DB::commit();

            $mensaje = "Se importaron {$planillasImportadas} planilla(s).";
            if (!empty($planillasOmitidas)) {
                $mensaje .= ' ⚠️ Omitidas: ' . implode(', ', $planillasOmitidas) . '.';
            }
            if (!empty($advertencias)) {
                $mensaje .= ' ⚠️ ' . implode('⚠️', $advertencias);
            }

            return redirect()->route('planillas.index')->with('success', $mensaje);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            $log = DB::getQueryLog();
            $last = $log ? $log[array_key_last($log)] : null;

            Log::error('❌ Error al importar planillas', [
                'mensaje'    => $e->getMessage(),
                'linea'      => $e->getLine(),
                'archivo'    => $e->getFile(),
                'last_query' => $last,
            ]);

            return back()->with('error', class_basename($e) . ': ' . $e->getMessage());
        } finally {
            // ✅ Siempre se restaura el handler, pase lo que pase
            restore_error_handler();
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
                $permitidos = [5, 8, 10, 12, 16, 20, 25, 32];

                foreach ($filasEtiqueta as $row) {
                    $excelRow = $row['_xl_row'] ?? 0;

                    // --- Normalizaciones ---
                    $diamRaw = $row[25] ?? null;
                    $diamStr = is_string($diamRaw) ? trim($diamRaw) : $diamRaw;
                    $diamNum = is_null($diamStr) ? null : (float) str_replace(',', '.', (string) $diamStr);
                    $diamInt = is_null($diamNum) ? null : (int) round($diamNum);

                    $lon     = $row[27] ?? 0;
                    $fig     = $row[26] ?? null;
                    $dobles  = $row[33] ?? 0;
                    $barras  = $row[32] ?? 0;
                    $ensamb  = $row[4]  ?? null;

                    // --- Filtro de diámetros no admitidos ---
                    if (is_null($diamInt) || !in_array($diamInt, $permitidos, true)) {
                        $advertencias[] = sprintf(
                            "Diámetro no admitido (planilla %s) → diámetro:%s (fila Excel %d, fila %s)",
                            $planilla->codigo,
                            $row[25] ?? 'N/A',
                            $excelRow,
                            $row[21] ?? 'N/A'
                        );
                        $maquinaId = null; // lo agrupamos sin máquina para que no se pierda el elemento
                    } else {
                        // Sólo si es permitido calculamos máquina
                        $maquinaId = $this->asignarMaquina(
                            $diamInt,   // usamos ya el int normalizado
                            $lon,
                            $fig,
                            $dobles,
                            $barras,
                            $ensamb,
                            $planilla->id
                        );
                        if (!$maquinaId) {
                            $advertencias[] = sprintf(
                                "Sin máquina compatible (planilla %s) → diámetro:%s | dimensiones:%s (fila Excel %d, fila %s)",
                                $planilla->codigo,
                                $row[25] ?? 'N/A',
                                $row[47] ?? 'N/A',
                                $excelRow,
                                $row[21] ?? 'N/A'
                            );
                        }
                    }

                    // Siempre agrupamos (aunque $maquinaId sea null) para que se creen elementos sin máquina
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
    public function destroyMultiple(Request $request)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        $ids = $request->input('seleccionados', []);
        // 🐛 Debug: ver qué IDs llegan
        Log::info('🗑️ IDs recibidos para eliminar múltiples:', $ids);

        if (empty($ids)) {
            return redirect()->route('planillas.index')->with('error', 'No seleccionaste ninguna planilla.');
        }

        DB::beginTransaction();
        try {
            // Buscar todas las planillas con sus elementos
            $planillas = Planilla::with('elementos')->whereIn('id', $ids)->get();

            foreach ($planillas as $planilla) {
                // Eliminar elementos asociados
                foreach ($planilla->elementos as $elemento) {
                    $elemento->delete();
                }

                // Eliminar la planilla
                $planilla->delete();
            }

            DB::commit();
            return redirect()->route('planillas.index')->with('success', 'Planillas eliminadas correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar las planillas: ' . $e->getMessage());
        }
    }
    //------------------------------------------------------------------------------------ COMPLETAR PLANILLA()
    public function completar(Request $request, PlanillaService $ordenPlanillaService)
    {
        // ✅ Validamos que exista la planilla
        $request->validate([
            'id' => 'required|integer|exists:planillas,id',
        ]);

        // ✅ Llamamos al service
        $resultado = $ordenPlanillaService->completarPlanilla($request->id);

        // ✅ Respondemos según resultado
        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'message' => $resultado['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['message'] ?? 'Error desconocido',
        ], 422);
    }

    public function completarTodas(PlanillaService $svc)
    {
        $resultado = $svc->completarTodasPlanillas();

        return back()->with(
            $resultado['success'] ? 'success' : 'error',
            "Procesadas OK: {$resultado['procesadas_ok']} | Omitidas por fecha: {$resultado['omitidas_fecha']} | Fallidas: {$resultado['fallidas']}"
        );
    }

    public function informacionMasiva(Request $request)
    {
        try {
            $ids = collect(explode(',', (string) $request->query('ids', '')))
                ->map(fn($x) => (int) trim($x))
                ->filter();

            Log::info('[informacionMasiva] ids=', $ids->toArray());

            if ($ids->isEmpty()) {
                return response()->json(['planillas' => []]);
            }

            // No incluimos codigo_limpio en el select porque es un accessor
            $planillas = Planilla::query()
                ->whereIn('id', $ids->all())
                ->select(['id', 'codigo', 'fecha_estimada_entrega', 'obra_id'])
                ->with(['obra:id,cod_obra,obra'])
                ->get();

            $resultado = $planillas->map(function ($p) {
                // Fecha robusta (aunque sea string en DB)
                $fecha = null;
                if (!empty($p->fecha_estimada_entrega)) {
                    try {
                        $fecha = \Carbon\Carbon::parse($p->fecha_estimada_entrega)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        // deja null si no se puede parsear
                    }
                }

                // Mapear obra usando los nombres reales
                $obra = null;
                if ($p->relationLoaded('obra') && $p->obra) {
                    $obra = [
                        'codigo' => $p->obra->cod_obra ?? null,
                        'nombre' => $p->obra->obra ?? null,
                    ];
                }

                return [
                    'id'                         => $p->id,
                    // Usamos accessor codigo_limpio
                    'codigo'                     => $p->codigo_limpio ?? ('Planilla ' . $p->id),
                    'fecha_estimada_entrega'     => $fecha,
                    'obra'                       => $obra,
                ];
            });

            return response()->json(['planillas' => $resultado]);
        } catch (\Throwable $e) {
            Log::error('[informacionMasiva] 500: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'Error interno'], 500);
        }
    }

    public function actualizarFechasMasiva(Request $request)
    {
        try {
            $data = $request->validate([
                'planillas' => ['required', 'array', 'min:1'],
                'planillas.*.id' => ['required', 'integer', 'exists:planillas,id'],
                'planillas.*.fecha_estimada_entrega' => ['nullable', 'date_format:Y-m-d'],
            ]);

            Log::info('[Planillas actualizarFechasMasiva] payload=', $data['planillas']);

            DB::transaction(function () use ($data) {
                foreach ($data['planillas'] as $fila) {
                    // 🚫 Si la fecha es null, saltamos esta planilla
                    if (empty($fila['fecha_estimada_entrega'])) {
                        continue;
                    }

                    $planilla = Planilla::find($fila['id']);
                    $planilla->fecha_estimada_entrega = Carbon::createFromFormat('Y-m-d', $fila['fecha_estimada_entrega'])->startOfDay();
                    $planilla->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Fechas de entrega actualizadas correctamente.',
            ]);
        } catch (ValidationException $ve) {
            Log::warning('[Planillas actualizarFechasMasiva] validation:', $ve->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[Planillas actualizarFechasMasiva] error: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno',
            ], 500);
        }
    }
}
