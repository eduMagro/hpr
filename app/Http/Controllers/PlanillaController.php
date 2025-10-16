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
use App\Services\AsignarMaquinaService;
use Illuminate\Support\Facades\Schema;


class PlanillaController extends Controller
{
    private AsignarMaquinaService $asignador;

    public function __construct(AsignarMaquinaService $asignador)
    {
        $this->asignador = $asignador;
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
            'revisada',
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
                    $codigo = trim($codigo);

                    // 1) Solo dígitos -> contains
                    if (preg_match('/^\d+$/', $codigo)) {
                        $q->orWhere('codigo', 'like', "%{$codigo}%");
                        continue;
                    }

                    // 2) Dígitos seguidos de guion (prefijo tipo "2025-") -> empieza por ese bloque + guion (pero con % por si hay prefijo como MP-)
                    if (preg_match('/^(\d+)-$/', $codigo, $m)) {
                        $izq = $m[1];
                        $q->orWhere('codigo', 'like', "%{$izq}-%");
                        continue;
                    }

                    // 3) Dígitos-guion-dígitos -> pad a 6 el bloque derecho
                    if (preg_match('/^(\d+)-(\d+)$/', $codigo, $m)) {
                        $izq = $m[1];
                        $derPadded = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                        // Usamos %...% para que matchee también códigos con prefijo: p.ej. MP-2024-008094
                        $q->orWhere('codigo', 'like', "%{$izq}-{$derPadded}%");
                        continue;
                    }

                    // 4) Cualquier otra cosa -> contains
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
            $query = Planilla::with(['user', 'elementos', 'cliente', 'obra', 'revisor']);
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
                'revisada' => $this->getOrdenamiento('revisada', 'Revisada'),
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
            'obra:id,obra',
            'cliente:id,empresa',
            // No restringimos columnas de etiquetas/elementos para evitar problemas con accessors
            'etiquetas',
            'elementos.maquina:id,nombre,tipo',
        ])->findOrFail($id);

        // ------ Color por estado (igual que antes)
        $getColor = fn($s) => match (strtolower(trim($s ?? ''))) {
            'fabricado'  => 'bg-green-200',
            'pendiente'  => 'bg-red-200',
            'fabricando' => 'bg-blue-200',
            default      => 'bg-gray-200',
        };

        // Clonamos elementos y añadimos color
        $elementos = $planilla->elementos->map(function ($e) use ($getColor) {
            $e->color = $getColor($e->estado);
            return $e;
        });

        // ------ Progreso
        $progreso = round(
            min(100, ($elementos->where('estado', 'fabricado')->sum('peso') / max(1, ($planilla->peso_total ?? 0))) * 100),
            2
        );

        // =========================================================
        // A) Agrupación para la vista: máquinas => etiquetas (sub_id)
        // =========================================================
        $porMaquina = $elementos->groupBy(fn($e) => $e->maquina_id ?? 'sin');

        $etiquetasPorMaquina = $porMaquina->map(function ($elemsDeMaquina) use ($planilla) {
            return $elemsDeMaquina
                ->groupBy('etiqueta_sub_id')
                ->map(function ($grupo, $subId) use ($planilla) {
                    // Buscamos la etiqueta real por sub_id
                    $etiqueta = $planilla->etiquetas->firstWhere('etiqueta_sub_id', $subId)
                        ?? (object)[
                            'id'              => null,              // en la vista se ignoran sin id
                            'etiqueta_sub_id' => $subId,
                            'estado'          => $grupo->first()?->estado ?? 'pendiente',
                            // NO es columna; solo info de ayuda si se usa:
                            'peso_kg'         => (float) $grupo->sum('peso'),
                            'nombre'          => null,
                            'codigo'          => null,
                        ];

                    return [
                        'etiqueta'  => $etiqueta,
                        'elementos' => $grupo->values(),
                    ];
                })
                ->values();
        });

        // Mapa de máquinas para render (incluye “Sin máquina” como cabecera)
        $maquinas = collect([
            'sin' => (object) ['id' => null, 'nombre' => 'Sin máquina', 'tipo' => 'normal'],
        ])->merge(
            $elementos->pluck('maquina')->filter()->keyBy('id') // solo máquinas que realmente aparecen
        );

        // =========================================================
        // B) Datasets “como máquinas” para canvasMaquina.js
        // =========================================================
        // Pesos por elemento (plano)
        $pesosElementos = $elementos
            ->map(fn($e) => ['id' => $e->id, 'peso' => $e->peso])
            ->values()
            ->toArray();

        // Orden natural por etiqueta_sub_id
        $ordenSub = function ($grupo, $subId) {
            if (preg_match('/^(.*?)[\.\-](\d+)$/', $subId, $m)) {
                return sprintf('%s-%010d', $m[1], (int) $m[2]);
            }
            return $subId . '-0000000000';
        };

        // Dataset compacto por subetiqueta (el que suelen leer tus scripts)
        $etiquetasData = $elementos
            ->filter(fn($e) => !empty($e->etiqueta_sub_id))
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub)
            ->map(fn($grupo, $subId) => [
                'codigo'    => (string) $subId,                  // subId (ej: ETQ25090001.01)
                'elementos' => $grupo->pluck('id')->toArray(),   // ids de elementos
                'pesoTotal' => $grupo->sum('peso'),              // sumatorio
            ])
            ->values();

        // Colección agrupada (por si la usas en Blade o en otros JS)
        // Colección agrupada (por si la usas en Blade o en otros JS)
        $elementosAgrupados = $elementos
            ->groupBy('etiqueta_sub_id')
            ->sortBy($ordenSub);

        // Payload “rico” para scripts (idéntico al de máquinas)
        $elementosAgrupadosScript = $elementosAgrupados->map(fn($grupo) => [
            'etiqueta'  => $planilla->etiquetas->firstWhere('etiqueta_sub_id', $grupo->first()?->etiqueta_sub_id),
            'planilla'  => $planilla,
            'elementos' => $grupo->map(fn($e) => [
                'id'          => $e->id,
                'codigo'      => $e->codigo ?? null,
                'dimensiones' => $e->dimensiones ?? null,
                'estado'      => $e->estado,
                'peso'        => $e->peso_kg ?? $e->peso,       // usa accessor si existe
                'diametro'    => $e->diametro_mm ?? $e->diametro,
                'longitud'    => $e->longitud_cm ?? ($e->longitud ?? null),
                'barras'      => $e->barras ?? null,
                'figura'      => $e->figura ?? null,
            ])->values(),
        ])->values();

        return view('planillas.show', compact(
            'planilla',
            'progreso',
            'maquinas',
            'etiquetasPorMaquina',
            // datasets para canvas/JS (mismo shape que en máquinas)
            'pesosElementos',
            'etiquetasData',
            'elementosAgrupados',
            'elementosAgrupadosScript',
        ));
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
        // 0) Seguridad + validación básica
        abort_unless(auth()->check() && auth()->user()->rol === 'oficina', 403);
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $file          = $request->file('file');
        $nombreArchivo = $file->getClientOriginalName();

        // 1) Sanidad del XLSX (numéricos inválidos)
        $invalids = $this->scanXlsxForInvalidNumeric($file->getRealPath());
        if (!empty($invalids)) {
            $det = collect($invalids)->map(fn($i) => "{$i['cell']}→'{$i['value']}'")->implode(', ');
            return back()->with('error', "{$nombreArchivo} contiene celdas numéricas inválidas: {$det}");
        }

        // 2) Leer primera hoja y preparar cuerpo
        $sheet = $this->leerPrimeraHojaConFila($file);
        if (!$sheet || count($sheet) < 2) {
            return back()->with('error', "{$nombreArchivo} no tiene filas de datos.");
        }
        $body = array_slice($sheet, 1); // sin cabecera

        // 3) Filtrado rápido + anotar nº de fila Excel
        $stats = ['error_peso' => 0, 'av_invalidas' => 0];
        $rows  = $this->filtrarYAnotar($body, $stats);

        if (empty($rows)) {
            return back()->with('error', "{$nombreArchivo} no contiene filas válidas tras filtrado.");
        }

        // 4) Autocompletar nº etiqueta vacíos por descripción dentro de cada planilla
        $this->completarEtiquetasPorDescripcion($rows);

        // 5) Agrupar por planilla (col 10)
        $porPlanilla = $this->groupByCol($rows, 10);

        $planillasImportadas = 0;
        $planillasOmitidas   = [];
        $advertencias        = [];

        DB::transaction(function () use ($porPlanilla, &$planillasImportadas, &$planillasOmitidas, &$advertencias) {
            foreach ($porPlanilla as $codigoPlanilla => $rowsPlanilla) {
                // 5.1) Saltar si ya existe
                if (Planilla::where('codigo', $codigoPlanilla)->exists()) {
                    $planillasOmitidas[] = $codigoPlanilla;
                    continue;
                }

                // 5.2) Resolver cliente/obra con la primera fila de la planilla
                [$cliente, $obra] = $this->resolverClienteObraDeFila($rowsPlanilla[0], $codigoPlanilla, $advertencias);
                if (!$cliente || !$obra) {
                    $planillasOmitidas[] = $codigoPlanilla;
                    continue;
                }

                // 5.3) Calcular peso total planilla
                $pesoTotal = array_reduce($rowsPlanilla, function ($acc, $r) use ($codigoPlanilla, &$advertencias) {
                    $excelRow = $r['_xl_row'] ?? 0;
                    $p        = $this->assertNumeric($r[34] ?? null, 'peso', $excelRow, $codigoPlanilla, $advertencias);
                    return $acc + (float)($p ?: 0);
                }, 0.0);

                // 5.4) Crear planilla base
                $planilla = $this->safeCreate(Planilla::class, [
                    'users_id'               => auth()->id(),
                    'cliente_id'             => $cliente->id,
                    'obra_id'                => $obra->id,
                    'seccion'                => $rowsPlanilla[0][7]  ?? null,
                    'descripcion'            => $rowsPlanilla[0][12] ?? null,
                    'ensamblado'             => $rowsPlanilla[0][4]  ?? null,
                    'codigo'                 => $codigoPlanilla,
                    'peso_total'             => $pesoTotal,
                    'fecha_estimada_entrega' => now()->addDays(7)->setTime(10, 0, 0),
                ], ['planilla' => $codigoPlanilla, 'excel_row' => $rowsPlanilla[0]['_xl_row'] ?? 0]);

                // 5.5) Crear etiquetas PADRE y elementos agregados
                $padres = $this->crearPadresYElementosAgregados($planilla, $codigoPlanilla, $rowsPlanilla, $advertencias);

                // 5.6) Asignar máquinas (service real)
                $this->asignador->repartirPlanilla($planilla->id);

                // 5.7) Crear subetiquetas por tipo de material/máquina y mover elementos
                $this->aplicarPoliticaSubetiquetas($planilla, $padres);

                // 5.8) Orden por máquina + tiempo total
                $this->crearOrdenPorMaquina($planilla->id);
                $this->guardarTiempoTotal($planilla);

                $planillasImportadas++;
            }
        });

        // 6) Mensaje final compacto
        $msg  = "Importadas: {$planillasImportadas}.";
        $msg .= $planillasOmitidas ? " Omitidas: " . implode(', ', $planillasOmitidas) . "." : "";
        $msg .= $advertencias ? " ⚠️ " . implode(' ⚠️ ', $advertencias) : "";
        $msg .= $stats['error_peso']   ? " Omitidas por 'error de peso': {$stats['error_peso']}." : "";
        $msg .= $stats['av_invalidas'] ? " Omitidas por AV inválida/vacía: {$stats['av_invalidas']}." : "";

        return redirect()->route('planillas.index')->with('success', $msg);
    }

    /* =========================
|  HELPERS SINTETIZADOS
|=========================*/

    private function filtrarYAnotar(array $body, array &$stats): array
    {
        $i = 0;
        $out = [];
        foreach ($body as $row) {
            if (!array_filter($row)) {
                continue;
            }
            if (stripos($row[29] ?? '', 'error de peso') !== false) {
                $stats['error_peso']++;
                continue;
            }
            $av = trim((string)($row[47] ?? '')); // AV
            if ($av === '' || str_starts_with($av, ';')) {
                $stats['av_invalidas']++;
                continue;
            }
            $row['_xl_row'] = $i + 2; // cabecera=1
            $out[] = $row;
            $i++;
        }
        return $out;
    }

    private function groupByCol(array $rows, int $col): array
    {
        $g = [];
        foreach ($rows as $r) {
            $key = (string)($r[$col] ?? 'Sin código');
            $g[$key][] = $r;
        }
        return $g;
    }

    private function completarEtiquetasPorDescripcion(array &$rows): void
    {
        $IDX_PLANILLA = 10;
        $IDX_DESC = 22;
        $IDX_ETIQ = 30;
        // Planilla → indices
        $porPlan = [];
        foreach ($rows as $i => $r) {
            $porPlan[(string)($r[$IDX_PLANILLA] ?? 'Sin código')][] = $i;
        }
        $norm = fn($t) => ($t = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string)$t)), 'UTF-8')) ?: '—SIN DESCRIPCION—';

        foreach ($porPlan as $cod => $idxs) {
            $desc2num = [];
            $usados = [];
            // primera pasada: respetar existentes
            foreach ($idxs as $i) {
                $etiq = $rows[$i][$IDX_ETIQ] ?? null;
                if (is_numeric($etiq) && (int)$etiq > 0) {
                    $num = (int)$etiq;
                    $usados[$num] = true;
                    $d = $norm($rows[$i][$IDX_DESC] ?? '');
                    $desc2num[$d] = $desc2num[$d] ?? $num;
                }
            }
            $next = 1;
            while (isset($usados[$next])) $next++;

            // segunda pasada: rellenar vacíos por descripción
            foreach ($idxs as $i) {
                if (!empty($rows[$i][$IDX_ETIQ])) continue;
                $d = $norm($rows[$i][$IDX_DESC] ?? '');
                if (!isset($desc2num[$d])) {
                    while (isset($usados[$next])) $next++;
                    $desc2num[$d] = $usados[$next] = $next++;
                }
                $rows[$i][$IDX_ETIQ] = $desc2num[$d];
            }
        }
    }

    private function resolverClienteObraDeFila(array $row, string $codigoPlanilla, array &$warn): array
    {
        $codCli = trim($row[0] ?? '');
        $nomCli = trim($row[1] ?? 'Cliente sin nombre');
        $codObr = trim($row[2] ?? '');
        $nomObr = trim($row[3] ?? 'Obra sin nombre');
        if (!$codCli || !$codObr) {
            $warn[] = "Planilla {$codigoPlanilla}: falta código de cliente u obra. Omitida.";
            return [null, null];
        }
        $cliente = Cliente::firstOrCreate(['codigo' => $codCli], ['empresa' => $nomCli]);
        $obra    = Obra::firstOrCreate(['cod_obra' => $codObr], ['cliente_id' => $cliente->id, 'obra' => $nomObr]);
        return [$cliente, $obra];
    }

    private function crearPadresYElementosAgregados(Planilla $planilla, string $codigoPlanilla, array $rowsPlanilla, array &$warn): array
    {
        // Agrupar por nº etiqueta Excel (col 30)
        $porEtiqueta = [];
        foreach ($rowsPlanilla as $r) {
            $n = $r[30] ?? null;
            if ($n) $porEtiqueta[$n][] = $r;
        }

        $padres  = []; // lista de Etiqueta padre
        $DM_OK   = [5, 8, 10, 12, 16, 20, 25, 32];

        foreach ($porEtiqueta as $numEt => $filas) {
            // Padre
            $codigoPadre = Etiqueta::generarCodigoEtiqueta();
            $padre = $this->safeCreate(Etiqueta::class, [
                'codigo'      => $codigoPadre,
                'planilla_id' => $planilla->id,
                'nombre'      => $filas[0][22] ?? 'Sin nombre',
            ], ['planilla' => $codigoPlanilla, 'excel_row' => $filas[0]['_xl_row'] ?? 0]);
            $padres[] = $padre;

            // Agregación por clave compuesta (figura|fila|marca|diametro|longitud|dobles|dimensiones)
            $agg = [];
            foreach ($filas as $r) {
                if (!array_filter($r)) continue;
                $k = implode('|', [$r[26], $r[21], $r[23], $r[25], $r[27], $r[33] ?? 0, $r[47] ?? '']);
                $agg[$k]['row']    = $r;
                $agg[$k]['peso']   = ($agg[$k]['peso']  ?? 0) + (float)($this->assertNumeric($r[34] ?? null, 'peso',   $r['_xl_row'] ?? 0, $codigoPlanilla, $warn) ?: 0);
                $agg[$k]['barras'] = ($agg[$k]['barras'] ?? 0) + (int)  ($this->assertNumeric($r[32] ?? null, 'barras', $r['_xl_row'] ?? 0, $codigoPlanilla, $warn) ?: 0);
            }

            // Elementos (aún sin máquina ni sub)
            foreach ($agg as $item) {
                $row = $item['row'];
                $er = $row['_xl_row'] ?? 0;
                $dm  = (int)($this->assertNumeric($row[25] ?? null, 'diametro', $er, $codigoPlanilla, $warn) ?: 0);
                if (!in_array($dm, $DM_OK, true)) {
                    $warn[] = "Planilla {$codigoPlanilla}: diámetro no admitido '{$row[25]}' (fila {$er}).";
                    continue;
                }

                $long = $this->assertNumeric($row[27] ?? null, 'longitud', $er, $codigoPlanilla, $warn);
                if ($long === false) continue;
                $dbl  = (int)($this->assertNumeric($row[33] ?? 0, 'dobles_barra', $er, $codigoPlanilla, $warn) ?: 0);

                $this->safeCreate(Elemento::class, [
                    'codigo'             => Elemento::generarCodigo(),
                    'planilla_id'        => $planilla->id,
                    'etiqueta_id'        => $padre->id,
                    'etiqueta_sub_id'    => null,
                    'maquina_id'         => null,
                    'figura'             => $row[26] ?: null,
                    'fila'               => $row[21] ?: null,
                    'marca'              => $row[23] ?: null,
                    'etiqueta'           => $row[30] ?: null,
                    'diametro'           => $dm,
                    'longitud'           => (float)$long,
                    'barras'             => (int)$item['barras'],
                    'dobles_barra'       => $dbl,
                    'peso'               => (float)$item['peso'],
                    'dimensiones'        => $row[47] ?? null,
                    'tiempo_fabricacion' => ($t = $this->calcularTiemposElemento($row))['tiempo_fabricacion'] ?? null,
                ], ['planilla' => $codigoPlanilla, 'excel_row' => $er]);
            }
        }
        return $padres;
    }

    private function aplicarPoliticaSubetiquetas(Planilla $planilla, array $padres): void
    {
        foreach ($padres as $padre) {
            $elems = Elemento::where('planilla_id', $planilla->id)->where('etiqueta_id', $padre->id)->get();
            if ($elems->isEmpty()) continue;

            $grupos = $elems->groupBy(fn($e) => $e->maquina_id ?? $e->maquina_id_2 ?? $e->maquina_id_3 ?? 0);

            foreach ($grupos as $maquinaId => $lote) {
                $maquinaId = (int)$maquinaId;
                if ($maquinaId === 0) {
                    // Sin máquina → sub nueva por elemento
                    foreach ($lote as $e) {
                        [$sid, $sidRow] = $this->crearSubSiguienteYObtenerId($padre);
                        $e->update(['etiqueta_id' => $sidRow, 'etiqueta_sub_id' => $sid]);
                    }
                    continue;
                }
                $tipo = strtolower((string)optional(Maquina::find($maquinaId))->tipo_material);
                if ($tipo === 'barra') {
                    // Barra → sub nueva por elemento
                    foreach ($lote as $e) {
                        [$sid, $sidRow] = $this->crearSubSiguienteYObtenerId($padre);
                        $e->update(['etiqueta_id' => $sidRow, 'etiqueta_sub_id' => $sid]);
                    }
                } else {
                    // Encarretado/u otro → sub canónica por máquina
                    $subsExist = collect($lote)->pluck('etiqueta_sub_id')->filter()->unique()->values();
                    if ($subsExist->isEmpty()) {
                        [$canon, $canonId] = $this->crearSubSiguienteYObtenerId($padre);
                    } else {
                        $canon = (string)$subsExist->sortBy(fn($sid) => (int)(preg_match('/\.(\d+)$/', (string)$sid, $m) ? $m[1] : 9999))->first();
                        $canonId = $this->asegurarFilaSubYObtenerId($canon, $padre);
                    }
                    foreach ($lote as $e) {
                        if ($e->etiqueta_sub_id !== $canon || $e->etiqueta_id !== $canonId) {
                            $e->update(['etiqueta_id' => $canonId, 'etiqueta_sub_id' => $canon]);
                        }
                    }
                }
            }

            // Recalcular pesos padre/subs (si existe columna 'peso')
            if (Schema::hasColumn('etiquetas', 'peso')) {
                $codigo = (string)$padre->codigo;
                $subs   = Etiqueta::where('codigo', $codigo)->whereNotNull('etiqueta_sub_id')->pluck('etiqueta_sub_id');
                foreach ($subs as $sid) {
                    $peso = (float)Elemento::where('etiqueta_sub_id', $sid)->sum('peso');
                    Etiqueta::where('etiqueta_sub_id', $sid)->update(['peso' => $peso]);
                }
                $pesoPadre = (float)Elemento::where('etiqueta_sub_id', 'like', $codigo . '.%')->sum('peso');
                Etiqueta::where('codigo', $codigo)->whereNull('etiqueta_sub_id')->update(['peso' => $pesoPadre]);
            }
        }
    }

    private function crearSubSiguienteYObtenerId(Etiqueta $padre): array
    {
        $subId  = Etiqueta::generarCodigoSubEtiqueta($padre->codigo);
        $subRow = Etiqueta::firstWhere('etiqueta_sub_id', $subId);
        if (!$subRow) {
            $data = [
                'codigo'          => $padre->codigo,
                'etiqueta_sub_id' => $subId,
                'planilla_id'     => $padre->planilla_id,
                'nombre'          => $padre->nombre,
                'estado'          => $padre->estado ?? 'pendiente',
                'peso'            => 0.0,
            ];
            foreach (['producto_id', 'producto_id_2', 'ubicacion_id', 'operario1_id', 'operario2_id', 'soldador1_id', 'soldador2_id', 'ensamblador1_id', 'ensamblador2_id', 'marca', 'paquete_id', 'numero_etiqueta', 'fecha_inicio', 'fecha_finalizacion', 'fecha_inicio_ensamblado', 'fecha_finalizacion_ensamblado', 'fecha_inicio_soldadura', 'fecha_finalizacion_soldadura'] as $c) {
                if (Schema::hasColumn('etiquetas', $c)) $data[$c] = $padre->$c;
            }
            $subRow = Etiqueta::create($data);
        }
        return [$subId, (int)$subRow->id];
    }

    private function asegurarFilaSubYObtenerId(string $subId, Etiqueta $padre): int
    {
        $row = Etiqueta::firstWhere('etiqueta_sub_id', $subId);
        if ($row) return (int)$row->id;
        $data = [
            'codigo'          => $padre->codigo,
            'etiqueta_sub_id' => $subId,
            'planilla_id'     => $padre->planilla_id,
            'nombre'          => $padre->nombre,
            'estado'          => $padre->estado ?? 'pendiente',
            'peso'            => 0.0,
        ];
        foreach (['producto_id', 'producto_id_2', 'ubicacion_id', 'operario1_id', 'operario2_id', 'soldador1_id', 'soldador2_id', 'ensamblador1_id', 'ensamblador2_id', 'marca', 'paquete_id', 'numero_etiqueta', 'fecha_inicio', 'fecha_finalizacion', 'fecha_inicio_ensamblado', 'fecha_finalizacion_ensamblado', 'fecha_inicio_soldadura', 'fecha_finalizacion_soldadura'] as $c) {
            if (Schema::hasColumn('etiquetas', $c)) $data[$c] = $padre->$c;
        }
        return (int)Etiqueta::create($data)->id;
    }

    private function crearOrdenPorMaquina(int $planillaId): void
    {
        $maquinas = Elemento::where('planilla_id', $planillaId)->whereNotNull('maquina_id')->distinct()->pluck('maquina_id');
        foreach ($maquinas as $mId) {
            OrdenPlanilla::firstOrCreate(
                ['planilla_id' => $planillaId, 'maquina_id' => $mId],
                ['posicion' => (OrdenPlanilla::where('maquina_id', $mId)->max('posicion') ?? 0) + 1]
            );
        }
    }

    private function guardarTiempoTotal(Planilla $planilla): void
    {
        $els   = $planilla->elementos()->get();
        $total = (float)$els->sum('tiempo_fabricacion') + ($els->count() * 1200);
        $planilla->update(['tiempo_fabricacion' => $total]);
    }

    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------------------
    public function reimportar(Request $request, Planilla $planilla)
    {
        // 1) Seguridad -----------------------------------------------------------------
        if (auth()->user()->rol !== 'oficina') {
            return back()->with('abort', 'No tienes los permisos necesarios.');
        }

        // 2) Validación del archivo -----------------------------------------------------
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.mimes'    => 'El archivo debe ser .xlsx o .xls',
        ]);

        // ⚠️ Contexto para warnings numéricos
        $__numCtx = ['planilla' => $planilla->codigo ?? 'N/D', 'excel_row' => 0, 'campo' => 'N/D', 'valor' => null];
        $advertencias = [];

        // NO lanzar excepción en avisos de no-numérico: solo añadir advertencia y continuar
        set_error_handler(function ($sev, $msg) use (&$advertencias, &$__numCtx) {
            if ($sev === E_WARNING && str_contains($msg, 'A non-numeric value encountered')) {
                $advertencias[] = "⚠️ Valor no numérico detectado; se omitió la fila. "
                    . "Planilla {$__numCtx['planilla']}, Fila {$__numCtx['excel_row']}, Campo '{$__numCtx['campo']}', Valor '" . (string)($__numCtx['valor']) . "'";
                return true; // warning manejado
            }
            return false;
        });

        DB::beginTransaction();

        try {
            /* -------------------------------------------------- */
            /* 3) Lectura + pre-scan del Excel                    */
            /* -------------------------------------------------- */
            $file          = $request->file('archivo');
            $nombreArchivo = $file->getClientOriginalName();

            // Escaneo XML previo (detectar celdas mal tipadas)
            $invalids = $this->scanXlsxForInvalidNumeric($file->getRealPath());
            if (!empty($invalids)) {
                $detalles = collect($invalids)->map(fn($i) => "{$i['cell']} → '{$i['value']}'")->implode(', ');
                throw new \Exception("{$nombreArchivo} - El Excel contiene celdas marcadas como numéricas con valor inválido: {$detalles}. Corrige esas celdas (pon número válido o cambia el tipo de celda a Texto) y vuelve a importar.");
            }

            // Lectura con fila-controlada (misma utilidad que en import)
            $firstSheet = $this->leerPrimeraHojaConFila($file);

            // Filtrado: quitar filas vacías, AD='error de peso', AV vacío o inválido
            $body = array_slice($firstSheet, 1);

            $filasErrorPesoOmitidas = 0;
            $filasAvInvalidas       = 0;

            $rows = array_values(array_filter($body, function ($row) use (&$filasErrorPesoOmitidas, &$filasAvInvalidas) {
                if (!array_filter($row)) return false;

                // Columna AD = 29
                $colAD = $row[29] ?? '';
                if (stripos($colAD, 'error de peso') !== false) {
                    $filasErrorPesoOmitidas++;
                    return false;
                }

                // Columna AV = 47
                $colAV = trim((string)($row[47] ?? ''));
                if ($colAV === '' || str_starts_with($colAV, ';')) {
                    $filasAvInvalidas++;
                    return false;
                }

                return true;
            }));

            // 👉 FILTRO EXTRA: SOLO LA PLANILLA SOLICITADA (columna 10 / índice 10)
            $rows = array_values(array_filter($rows, function ($row) use ($planilla) {
                $codigoFila = trim((string)($row[10] ?? '')); // Código de planilla en el Excel
                return $codigoFila === $planilla->codigo;
            }));

            if (!$rows) {
                throw new \Exception("El archivo no contiene filas válidas para la planilla {$planilla->codigo}.");
            }
            // anotar nº de fila de Excel (cabecera = 1 ⇒ +2)
            foreach ($rows as $i => &$r) {
                $r['_xl_row'] = $i + 2;
            }
            unset($r);

            /* -------------------------------------------------- */
            /* 4) Limpieza: solo elementos 'pendiente' + etiquetas huérfanas  */
            /* -------------------------------------------------- */
            $planilla->elementos()->where('estado', 'pendiente')->delete();

            Etiqueta::where('planilla_id', $planilla->id)
                ->whereDoesntHave('elementos')
                ->delete();

            /* -------------------------------------------------- */
            /* 5) Reconstrucción: etiqueta PADRE → elementos      */
            /*    (igual que import: sin subetiquetas aún)        */
            /* -------------------------------------------------- */

            // Agrupar por nº de etiqueta (columna 21 del Excel)
            $etiquetasExcel = [];
            foreach ($rows as $row) {
                $numEtiqueta = $row[21] ?? null;
                if ($numEtiqueta) $etiquetasExcel[$numEtiqueta][] = $row;
            }

            $DmPermitido = [5, 8, 10, 12, 16, 20, 25, 32];
            $etiquetasPadreCreadas = []; // [numEtiquetaExcel => ['padre' => Etiqueta, 'codigoPadre' => string]]

            foreach ($etiquetasExcel as $numEtiquetaExcel => $filasEtiqueta) {
                // Crear etiqueta PADRE (contenedor)
                $codigoPadre   = Etiqueta::generarCodigoEtiqueta();
                $etiquetaPadre = $this->safeCreate(Etiqueta::class, [
                    'codigo'          => $codigoPadre,
                    'planilla_id'     => $planilla->id,
                    'nombre'          => $filasEtiqueta[0][22] ?? 'Sin nombre',
                    'peso'            => 0,
                    'marca'           => null,
                    'etiqueta_sub_id' => null,
                ], [
                    'planilla'  => $planilla->codigo,
                    'excel_row' => $filasEtiqueta[0]['_xl_row'] ?? 0,
                ]);

                $etiquetasPadreCreadas[$numEtiquetaExcel] = [
                    'padre'       => $etiquetaPadre,
                    'codigoPadre' => $codigoPadre,
                ];

                // Agrupar filas “iguales” para sumar barras/peso (como import)
                $agrupados = [];
                foreach ($filasEtiqueta as $row) {
                    if (!array_filter($row)) continue;

                    $excelRow = $row['_xl_row'] ?? 0;

                    // contexto para warnings
                    $__numCtx['excel_row'] = $excelRow;
                    $__numCtx['planilla']  = $planilla->codigo;

                    $clave = implode('|', [
                        $row[26],           // figura
                        $row[21],           // nº etiqueta Excel
                        $row[23],           // marca
                        $row[25],           // diametro
                        $row[27],           // longitud
                        $row[33] ?? 0,      // dobles_por_barra
                        $row[47] ?? ''      // dimensiones
                    ]);

                    $__numCtx['campo'] = 'peso';
                    $__numCtx['valor'] = $row[34] ?? null;
                    $pesoNum = $this->assertNumeric($row[34] ?? null, 'peso', $excelRow, $planilla->codigo, $advertencias);

                    $__numCtx['campo'] = 'barras';
                    $__numCtx['valor'] = $row[32] ?? null;
                    $bNum = $this->assertNumeric($row[32] ?? null, 'barras', $excelRow, $planilla->codigo, $advertencias);

                    if ($pesoNum === false || $bNum === false) continue;

                    $agrupados[$clave]['row']    = $row;
                    $agrupados[$clave]['peso']   = ($agrupados[$clave]['peso']   ?? 0) + $pesoNum;
                    $agrupados[$clave]['barras'] = ($agrupados[$clave]['barras'] ?? 0) + (int)$bNum;
                }

                // Crear ELEMENTOS (aún sin subetiqueta ni maquina_id)
                foreach ($agrupados as $item) {
                    $row      = $item['row'];
                    $excelRow = $row['_xl_row'] ?? 0;

                    $diametroNum = $this->assertNumeric($row[25] ?? null, 'diametro', $excelRow, $planilla->codigo, $advertencias);
                    $longNum     = $this->assertNumeric($row[27] ?? null, 'longitud', $excelRow, $planilla->codigo, $advertencias);
                    $doblesNum   = $this->assertNumeric($row[33] ?? 0,    'dobles_barra', $excelRow, $planilla->codigo, $advertencias);
                    $barrasNum   = $this->assertNumeric($item['barras'],  'barras', $excelRow, $planilla->codigo, $advertencias);
                    $pesoNum     = $this->assertNumeric($item['peso'],    'peso', $excelRow, $planilla->codigo, $advertencias);

                    if ($diametroNum === false || $longNum === false || $doblesNum === false || $barrasNum === false || $pesoNum === false) {
                        continue;
                    }

                    if (!in_array((int)$diametroNum, $DmPermitido, true)) {
                        $advertencias[] = sprintf(
                            "Diámetro no admitido (planilla %s) → diámetro:%s (fila %d)",
                            $planilla->codigo,
                            $row[25] ?? 'N/A',
                            $excelRow
                        );
                        continue;
                    }

                    $tiempos = $this->calcularTiemposElemento($row);

                    $this->safeCreate(Elemento::class, [
                        'codigo'             => Elemento::generarCodigo(),
                        'planilla_id'        => $planilla->id,
                        'etiqueta_id'        => $etiquetaPadre->id, // al PADRE
                        'etiqueta_sub_id'    => null,                // se asignará en FASE 2
                        'maquina_id'         => null,                // lo pondrá el service
                        'figura'             => $row[26] ?: null,
                        'fila'               => $row[21] ?: null,
                        'marca'              => $row[23] ?: null,
                        'etiqueta'           => $row[30] ?: null,
                        'diametro'           => $diametroNum,
                        'longitud'           => $longNum,
                        'barras'             => (int)$barrasNum,
                        'dobles_barra'       => (int)$doblesNum,
                        'peso'               => $pesoNum,
                        'dimensiones'        => $row[47] ?? null,
                        'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                        'estado'             => 'pendiente',
                    ], [
                        'planilla'  => $planilla->codigo,
                        'excel_row' => $excelRow,
                    ]);
                }
            }

            /* -------------------------------------------------- */
            /* 6) Asignación de máquinas (service)                */
            /* -------------------------------------------------- */
            // 👉 Igual que import: delega a tu servicio real
            $this->asignador->repartirPlanilla($planilla->id);

            /* -------------------------------------------------- */
            /* 7) Crear subetiquetas por máquina y mover elementos */
            /* -------------------------------------------------- */
            foreach ($etiquetasPadreCreadas as $infoPadre) {
                /** @var Etiqueta $etiquetaPadre */
                $etiquetaPadre = $infoPadre['padre'];
                $codigoPadre   = $infoPadre['codigoPadre'];

                $elementosPadre = Elemento::where('planilla_id', $planilla->id)
                    ->where('etiqueta_id', $etiquetaPadre->id)
                    ->get();

                if ($elementosPadre->isEmpty()) {
                    // Si no quedó nada, elimina el padre
                    $etiquetaPadre->delete();
                    continue;
                }

                $gruposPorMaquina = $elementosPadre->groupBy(function ($e) {
                    return $e->maquina_id ?: 'sin_maquina';
                });

                foreach ($gruposPorMaquina as $grupoElems) {
                    $codigoSub = Etiqueta::generarCodigoSubEtiqueta($codigoPadre);

                    $subEtiqueta = $this->safeCreate(Etiqueta::class, [
                        'codigo'          => $codigoPadre,
                        'planilla_id'     => $planilla->id,
                        'nombre'          => $etiquetaPadre->nombre,
                        'etiqueta_sub_id' => $codigoSub,
                    ], [
                        'planilla'  => $planilla->codigo,
                        'excel_row' => $grupoElems->first()?->_xl_row ?? 0,
                    ]);

                    // mover elementos al sub
                    Elemento::whereIn('id', $grupoElems->pluck('id'))
                        ->update([
                            'etiqueta_id'     => $subEtiqueta->id,
                            'etiqueta_sub_id' => $codigoSub,
                        ]);

                    // actualizar agregados del sub
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

                // dejar el padre como contenedor sin peso/marca
                $etiquetaPadre->update(['peso' => 0, 'marca' => null]);
            }

            /* -------------------------------------------------- */
            /* 8) Crear entradas en orden_planillas como en import */
            /*    (no toca posiciones existentes)                 */
            /* -------------------------------------------------- */
            $maquinasUsadas = Elemento::where('planilla_id', $planilla->id)
                ->whereNotNull('maquina_id')
                ->distinct()
                ->pluck('maquina_id')
                ->all();

            foreach ($maquinasUsadas as $maquina_id) {
                OrdenPlanilla::firstOrCreate(
                    ['planilla_id' => $planilla->id, 'maquina_id' => $maquina_id],
                    ['posicion'    => (OrdenPlanilla::where('maquina_id', $maquina_id)->max('posicion') ?? 0) + 1]
                );
            }

            /* -------------------------------------------------- */
            /* 9) Recalcular totales de la planilla               */
            /*    (NO tocamos fecha_estimada_entrega)             */
            /* -------------------------------------------------- */
            $elementos   = $planilla->elementos()->get();
            $pesoTotal   = $elementos->sum('peso');
            $tiempoTotal = $elementos->sum('tiempo_fabricacion') + $elementos->count() * 1200; // 20 min/elemento

            $planilla->update([
                'peso_total'         => $pesoTotal,
                'tiempo_fabricacion' => $tiempoTotal,
                // 'fecha_estimada_entrega' => (no tocar)
            ]);

            DB::commit();

            $msg = "🔄 Reimportación completada para {$planilla->codigo}. Peso total: {$pesoTotal} kg.";
            if ($advertencias) {
                $msg .= ' ⚠️ ' . implode(' ⚠️ ', $advertencias);
            }
            if ($filasErrorPesoOmitidas > 0) {
                $msg .= " ⚠️ Filas omitidas por 'error de peso': {$filasErrorPesoOmitidas}.";
            }
            if ($filasAvInvalidas > 0) {
                $msg .= " ⚠️ Filas omitidas por columna AV vacía o inválida: {$filasAvInvalidas}.";
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Error al reimportar planilla', [
                'planilla' => $planilla->codigo,
                'archivo'  => $nombreArchivo ?? null,
                'msg'      => $e->getMessage(),
                'line'     => $e->getLine(),
                'file'     => $e->getFile(),
            ]);

            return back()->with('error', class_basename($e) . ': ' . $e->getMessage());
        } finally {
            // ✅ Siempre se restaura el handler, pase lo que pase
            restore_error_handler();
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
            $planilla = Planilla::findOrFail($id);

            // Fechas vacías -> null
            $request->merge([
                'fecha_inicio'           => $request->fecha_inicio ?: null,
                'fecha_estimada_entrega' => $request->fecha_estimada_entrega ?: null,
                'fecha_finalizacion'     => $request->fecha_finalizacion ?: null,
                'fecha_importacion'      => $request->fecha_importacion ?: null,
            ]);

            // ✅ Nuevo: normalizar checkbox "revisada" (acepta on/true/1)
            if ($request->has('revisada')) {
                $request->merge([
                    'revisada' => filter_var($request->input('revisada'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }

            $validatedData = $request->validate([
                'codigo'                 => 'required|string|max:50',
                'cliente_id'             => 'nullable|integer|exists:clientes,id',
                'obra_id'                => 'nullable|integer|exists:obras,id',
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
                'usuario'                => 'nullable|string|max:100',
                // ✅ Nuevo:
                'revisada'               => 'nullable|boolean',
            ], [
                'codigo.required'     => 'El campo Código es obligatorio.',
                'codigo.string'       => 'El campo Código debe ser una cadena de texto.',
                'codigo.max'          => 'El campo Código no debe exceder 50 caracteres.',
                'cliente_id.integer'  => 'El campo cliente_id debe ser un número entero.',
                'cliente_id.exists'   => 'El cliente especificaoa en cliente_id no existe.',
                'obra_id.integer'     => 'El campo obra_id debe ser un número entero.',
                'obra_id.exists'      => 'La obra especificada en obra_id no existe.',
                'seccion.string'      => 'El campo Sección debe ser una cadena de texto.',
                'seccion.max'         => 'El campo Sección no debe exceder 100 caracteres.',
                'descripcion.string'  => 'El campo Descripción debe ser una cadena de texto.',
                'ensamblado.string'   => 'El campo Ensamblado debe ser una cadena de texto.',
                'ensamblado.max'      => 'El campo Ensamblado no debe exceder 100 caracteres.',
                'comentario.string'   => 'El campo Comentario debe ser una cadena de texto.',
                'comentario.max'      => 'El campo Comentario no debe exceder 255 caracteres.',
                'peso_fabricado.numeric' => 'El campo Peso Fabricado debe ser un número.',
                'peso_total.numeric'     => 'El campo Peso Total debe ser un número.',
                'estado.in'              => 'El campo Estado debe ser: pendiente, fabricando o completada.',
                'fecha_inicio.date_format'           => 'El campo Fecha Inicio no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_finalizacion.date_format'     => 'El campo Fecha Finalización no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_estimada_entrega.date_format' => 'El campo Fecha Estimada de Entrega no corresponde al formato DD/MM/YYYY HH:mm.',
                'fecha_importacion.date_format'      => 'El campo Fecha de Importación no corresponde al formato DD/MM/YYYY.',
                'usuario.string'        => 'El campo Usuario debe ser una cadena de texto.',
                'usuario.max'           => 'El campo Usuario no debe exceder 100 caracteres.',
                // ✅ Nuevo:
                'revisada.boolean'      => 'El campo Revisada debe ser booleano.',
            ]);

            // Validación: obra pertenece al cliente
            if (!empty($validatedData['obra_id']) && !empty($validatedData['cliente_id'])) {
                $obra = Obra::find($validatedData['obra_id']);
                if ($obra && $obra->cliente_id != $validatedData['cliente_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La obra seleccionada no pertenece al cliente seleccionado.'
                    ], 422);
                }
            }

            // Fechas -> formato BD
            if (!empty($validatedData['fecha_inicio'])) {
                $validatedData['fecha_inicio'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_inicio'])->format('Y-m-d H:i:s');
            }
            if (!empty($validatedData['fecha_finalizacion'])) {
                $validatedData['fecha_finalizacion'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_finalizacion'])->format('Y-m-d H:i:s');
            }
            if (!empty($validatedData['fecha_estimada_entrega'])) {
                $validatedData['fecha_estimada_entrega'] = Carbon::createFromFormat('d/m/Y H:i', $validatedData['fecha_estimada_entrega'])->format('Y-m-d H:i:s');
            }

            // ✅ Lógica de revisión (quién/cuándo)
            if (array_key_exists('revisada', $validatedData)) {
                $revisada = (bool)$validatedData['revisada'];

                if ($revisada) {
                    $validatedData['revisada_por_id'] = auth()->id();
                    $validatedData['revisada_at']     = now();
                } else {
                    $validatedData['revisada_por_id'] = null;
                    $validatedData['revisada_at']     = null;
                }
            }

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

    // 🔽 Añade estos helpers PRIVADOS en tu PlanillaController

    /**
     * Devuelve los IDs de máquinas que tienen entradas en orden_planillas
     * para cualquiera de las planillas indicadas.
     */
    private function obtenerMaquinasAfectadasPorPlanillas(array $planillaIds): array
    {
        if (empty($planillaIds)) return [];
        return OrdenPlanilla::query()
            ->whereIn('planilla_id', $planillaIds)
            ->distinct()
            ->pluck('maquina_id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    /**
     * Recalcula la columna 'posicion' para una máquina, compactando a 1..N
     * respetando el orden actual.
     */
    private function recalcularOrdenParaMaquina(int $maquinaId): void
    {
        $ordenes = OrdenPlanilla::query()
            ->where('maquina_id', $maquinaId)
            ->orderBy('posicion', 'asc')
            ->get(['id', 'posicion']);

        $nuevaPosicion = 1;
        foreach ($ordenes as $fila) {
            if ((int) $fila->posicion !== $nuevaPosicion) {
                OrdenPlanilla::where('id', $fila->id)->update(['posicion' => $nuevaPosicion]);
            }
            $nuevaPosicion++;
        }
    }

    /**
     * Recalcula el orden para todas las máquinas indicadas.
     */
    private function recalcularOrdenParaMaquinas(array $maquinaIds): void
    {
        $maquinaIds = array_values(array_unique(array_filter($maquinaIds, fn($x) => !is_null($x))));
        foreach ($maquinaIds as $maquinaId) {
            $this->recalcularOrdenParaMaquina((int) $maquinaId);
        }
    }

    // Eliminar una planilla y sus elementos asociados
    public function destroy($id)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        DB::beginTransaction();
        try {
            $planilla = Planilla::with('elementos')->findOrFail($id);

            // 1) Máquinas afectadas antes de borrar ordenes
            $maquinasAfectadas = $this->obtenerMaquinasAfectadasPorPlanillas([$planilla->id]);

            // 2) Borrar orden_planillas de esta planilla
            OrdenPlanilla::where('planilla_id', $planilla->id)->delete();

            // 3) Borrar elementos asociados (si no hay ON DELETE CASCADE)
            $planilla->elementos()->delete();

            // (Opcional recomendado) borrar etiquetas huérfanas de la planilla
            if (method_exists($planilla, 'etiquetas')) {
                $planilla->etiquetas()->delete();
            }

            // 4) Borrar la planilla
            $planilla->delete();

            // 5) Recalcular posiciones por máquina afectada
            $this->recalcularOrdenParaMaquinas($maquinasAfectadas);

            DB::commit();
            return redirect()->route('planillas.index')->with('success', 'Planilla eliminada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la planilla: ' . $e->getMessage());
        }
    }
    // 🔽 Sustituye tu destroyMultiple por este

    public function destroyMultiple(Request $request)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }

        $ids = $request->input('seleccionados', []);
        Log::info('🗑️ IDs recibidos para eliminar múltiples:', $ids);

        if (empty($ids)) {
            return redirect()->route('planillas.index')->with('error', 'No seleccionaste ninguna planilla.');
        }

        DB::beginTransaction();
        try {
            $planillas = Planilla::with('elementos')->whereIn('id', $ids)->get();
            if ($planillas->isEmpty()) {
                DB::rollBack();
                return redirect()->route('planillas.index')->with('error', 'No se encontraron planillas para eliminar.');
            }

            // 1) Máquinas afectadas antes de borrar ordenes
            $maquinasAfectadas = $this->obtenerMaquinasAfectadasPorPlanillas($planillas->pluck('id')->all());

            // 2) Borrar orden_planillas de todas esas planillas
            OrdenPlanilla::whereIn('planilla_id', $planillas->pluck('id'))->delete();

            // 3) Borrar elementos en bloque
            Elemento::whereIn('planilla_id', $planillas->pluck('id'))->delete();

            // (Opcional recomendado) borrar etiquetas de esas planillas
            if (class_exists(\App\Models\Etiqueta::class)) {
                Etiqueta::whereIn('planilla_id', $planillas->pluck('id'))->delete();
            }

            // 4) Borrar las planillas
            Planilla::whereIn('id', $planillas->pluck('id'))->delete();

            // 5) Recalcular posiciones por máquina afectada
            $this->recalcularOrdenParaMaquinas($maquinasAfectadas);

            DB::commit();
            return redirect()->route('planillas.index')->with('success', 'Planillas eliminadas correctamente.');
        } catch (\Throwable $e) {
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

            if ($ids->isEmpty()) {
                return response()->json(['planillas' => []]);
            }

            // No incluimos codigo_limpio en el select porque es un accessor
            $planillas = Planilla::query()
                ->whereIn('id', $ids->all())
                ->select(['id', 'codigo', 'fecha_estimada_entrega', 'obra_id', 'seccion', 'descripcion', 'peso_total'])
                ->with(['obra:id,cod_obra,obra'])
                ->get();

            $resultado = $planillas->map(function ($p) {
                // Fecha robusta (aunque sea string en DB)
                $fecha = null;
                if (!empty($p->fecha_estimada_entrega)) {
                    try {
                        // Forzar interpretación como DD/MM/YYYY en lugar de MM/DD/YYYY
                        $carbon = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $p->fecha_estimada_entrega);
                        $fecha = $carbon->format('Y-m-d');
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
                    'seccion'                    => $p->seccion ?? null,
                    'descripcion'                => $p->descripcion ?? null,
                    'peso_total'                 => $p->peso_total ?? null,
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
