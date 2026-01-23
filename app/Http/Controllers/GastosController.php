<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Gasto;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GastosController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $gastos = Gasto::with(['nave', 'obra', 'maquina', 'proveedor', 'motivo'])
            ->orderBy('fecha_pedido', 'desc')
            ->paginate($perPage);

        $oldestGastoDate = Gasto::whereNotNull('fecha_pedido')->min('fecha_pedido');

        // Placeholder for statistics
        $stats = [
            'global' => Gasto::sum('coste'),
            'mensual' => Gasto::whereMonth('fecha_pedido', now()->month)
                ->whereYear('fecha_pedido', now()->year)
                ->sum('coste'),
        ];

        $obras = \App\Models\Obra::whereNotIn('obra', ['Nave A', 'Nave B'])->orderBy('obra')->get();
        // Naves can be hardcoded or filtered from Obras if they exist there, 
        // but user specifically requested "Nave A" and "Nave B".
        // If these are IDs in the 'obras' table, we should find them. 
        // However, given the request "que solo salgan Nave A y Nave B", we can pass a specific list.
        // But since the DB expects an ID (nave_id constrained to obras), we must ensure 
        // these exist in the Obras table or handle them as specific logic.
        // Assuming current requirement is just to SHOW these options for selection.
        // If 'nave_id' is an FK to 'obras', we probably need to find the Obras named "Nave A" and "Nave B".
        // Let's filter the works to find those two, or if they are just strings, use strings.
        // But the model says: "nave_id constrained('obras')". So they MUST be in the Obras table.
        // Let's search for them.
        $naves = \App\Models\Obra::whereIn('obra', ['Nave A', 'Nave B'])->get();

        // If they don't exist by name, we might be in trouble, but assuming they do or user means these are the only choices.
        // If the user meant "Creating them if not exist", that's another step. 
        // For now, let's assume they are valid 'Obra' records.

        $maquinas = \App\Models\Maquina::orderBy('nombre')->get();

        $proveedoresLista = \App\Models\GastoProveedor::orderBy('nombre')->get();
        $motivosLista = \App\Models\GastoMotivo::orderBy('nombre')->get();

        return view('components.gastos.index', compact('gastos', 'perPage', 'stats', 'obras', 'naves', 'maquinas', 'proveedoresLista', 'motivosLista', 'oldestGastoDate'));
    }

    public function charts(Request $request)
    {
        $groupBy = $request->string('group_by')->lower()->value();
        if (!in_array($groupBy, ['day', 'month', 'year'], true)) {
            $groupBy = 'month';
        }

        $breakdownBy = $request->string('breakdown_by')->lower()->value();
        if (!in_array($breakdownBy, ['obra', 'proveedor', 'maquina', 'motivo'], true)) {
            $breakdownBy = 'proveedor';
        }

        $tipo = $request->string('tipo')->lower()->value();
        if (!in_array($tipo, ['all', 'gasto', 'obra'], true)) {
            $tipo = 'all';
        }

        $from = $request->input('from');
        $to = $request->input('to');

        $now = now();
        try {
            if ($from && preg_match('/^\d{4}-\d{2}$/', $from)) {
                $fromDate = Carbon::createFromFormat('Y-m', $from)->startOfMonth();
            } else {
                $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
            }
        } catch (\Throwable) {
            $fromDate = null;
        }

        try {
            if ($to && preg_match('/^\d{4}-\d{2}$/', $to)) {
                $toDate = Carbon::createFromFormat('Y-m', $to)->endOfMonth();
            } else {
                $toDate = $to ? Carbon::parse($to)->endOfDay() : null;
            }
        } catch (\Throwable) {
            $toDate = null;
        }

        if (!$fromDate || !$toDate) {
            if ($groupBy === 'day') {
                $toDate = $now->copy()->endOfDay();
                $fromDate = $now->copy()->subDays(29)->startOfDay();
            } elseif ($groupBy === 'year') {
                $toDate = $now->copy()->endOfYear();
                $fromDate = $now->copy()->subYears(4)->startOfYear();
            } else { // month
                $toDate = $now->copy()->endOfMonth();
                $fromDate = $now->copy()->subMonths(11)->startOfMonth();
            }
        }

        $obraId = $request->integer('obra_id') ?: null;
        $proveedorId = $request->integer('proveedor_id') ?: null;
        $maquinaId = $request->integer('maquina_id') ?: null;

        $base = Gasto::query()
            ->whereNotNull('fecha_pedido')
            ->whereBetween('fecha_pedido', [$fromDate->toDateString(), $toDate->toDateString()]);

        if ($tipo === 'obra') {
            $base->whereNotNull('obra_id');
        } elseif ($tipo === 'gasto') {
            $base->whereNull('obra_id');
        }

        if ($obraId) {
            $base->where('obra_id', $obraId);
        }
        if ($proveedorId) {
            $base->where('proveedor_id', $proveedorId);
        }
        if ($maquinaId) {
            $base->where('maquina_id', $maquinaId);
        }

        // === Series (time) ===
        $periodSql = match ($groupBy) {
            'day' => "DATE_FORMAT(fecha_pedido, '%Y-%m-%d')",
            'year' => "DATE_FORMAT(fecha_pedido, '%Y')",
            default => "DATE_FORMAT(fecha_pedido, '%Y-%m')",
        };

        $seriesRows = (clone $base)
            ->select([
                DB::raw("{$periodSql} as period"),
                DB::raw('COALESCE(SUM(coste), 0) as total'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $seriesLabels = $seriesRows->pluck('period')->values();
        $seriesData = $seriesRows->pluck('total')->map(fn($v) => (float) $v)->values();

        // === Breakdown (category) ===
        $limit = (int) $request->input('limit', 8);
        $limit = max(3, min($limit, 20));

        $breakdownQuery = (clone $base)->select([
            DB::raw('COALESCE(SUM(coste), 0) as total'),
        ]);

        $hideUnassigned = $request->boolean('hide_unassigned');

        if ($breakdownBy === 'obra') {
            if ($hideUnassigned) {
                $breakdownQuery->whereNotNull('gastos.obra_id');
            }
            $breakdownQuery
                ->leftJoin('obras as o', 'gastos.obra_id', '=', 'o.id')
                ->addSelect(DB::raw("IFNULL(o.obra, 'Sin obra') as label"))
                ->groupBy('label');
        } elseif ($breakdownBy === 'maquina') {
            if ($hideUnassigned) {
                $breakdownQuery->whereNotNull('gastos.maquina_id');
            }
            $breakdownQuery
                ->leftJoin('maquinas as m', 'gastos.maquina_id', '=', 'm.id')
                ->addSelect(DB::raw("IFNULL(m.nombre, 'Sin máquina') as label"))
                ->groupBy('label');
        } elseif ($breakdownBy === 'motivo') {
            if ($hideUnassigned) {
                $breakdownQuery->whereNotNull('gastos.motivo_id');
            }
            $breakdownQuery
                ->leftJoin('gastos_motivos as gm', 'gastos.motivo_id', '=', 'gm.id')
                ->addSelect(DB::raw("IFNULL(gm.nombre, 'Sin motivo') as label"))
                ->groupBy('label');
        } else { // proveedor
            if ($hideUnassigned) {
                $breakdownQuery->whereNotNull('gastos.proveedor_id');
            }
            $breakdownQuery
                ->leftJoin('gastos_proveedores as gp', 'gastos.proveedor_id', '=', 'gp.id')
                ->addSelect(DB::raw("IFNULL(gp.nombre, 'Sin proveedor') as label"))
                ->groupBy('label');
        }

        $breakdownRows = $breakdownQuery
            ->orderByDesc('total')
            ->get();

        $top = $breakdownRows->take($limit);
        $rest = $breakdownRows->slice($limit);

        $breakdownLabels = $top->pluck('label')->values();
        $breakdownData = $top->pluck('total')->map(fn($v) => (float) $v)->values();

        $othersTotal = (float) $rest->sum('total');
        if ($othersTotal > 0) {
            $breakdownLabels->push('Otros');
            $breakdownData->push($othersTotal);
        }

        return response()->json([
            'success' => true,
            'filters' => [
                'from' => $fromDate->toDateString(),
                'to' => $toDate->toDateString(),
                'group_by' => $groupBy,
                'breakdown_by' => $breakdownBy,
                'tipo' => $tipo,
                'obra_id' => $obraId,
                'proveedor_id' => $proveedorId,
                'maquina_id' => $maquinaId,
                'limit' => $limit,
            ],
            'series' => [
                'labels' => $seriesLabels,
                'data' => $seriesData,
            ],
            'breakdown' => [
                'labels' => $breakdownLabels,
                'data' => $breakdownData,
            ],
        ]);
    }

    public function importCsv(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:gasto,obra',
            'csv_file' => 'required|file|max:10240|mimes:csv,txt',
        ]);

        $tipo = $validated['tipo'];

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'No se pudo leer el archivo CSV.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                return back()->with('error', 'El CSV está vacío.');
            }

            $delimiter = $this->detectCsvDelimiter($firstLine);
            rewind($handle);

            $rawHeaders = fgetcsv($handle, 0, $delimiter);
            if (!is_array($rawHeaders) || count($rawHeaders) === 0) {
                fclose($handle);
                return back()->with('error', 'No se pudo leer la cabecera del CSV.');
            }

            $headerIndex = $this->buildCsvHeaderIndex($rawHeaders);

            $rowNumber = 1;
            $created = 0;
            $skipped = 0;
            $warnings = [];

            DB::beginTransaction();

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if (!is_array($row) || count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $fechaPedido = $this->csvGet($row, $headerIndex, ['fecha del pedido']);
                $fechaLlegada = $this->csvGet($row, $headerIndex, ['llegada']);
                $naveNombre = $this->csvGet($row, $headerIndex, ['nave']);
                $maquinaRaw = $this->csvGet($row, $headerIndex, ['máquina', 'maquina']);
                $obraRaw = $this->csvGet($row, $headerIndex, ['obra']);
                $proveedorNombre = $this->csvGet($row, $headerIndex, ['proveedor']);
                $motivoNombre = $this->csvGet($row, $headerIndex, ['motivo']);
                $costeRaw = $this->csvGet($row, $headerIndex, ['coste']);
                $codigoFactura = $this->csvGet($row, $headerIndex, ['factura', 'código factura', 'codigo factura', 'codigo_factura']);
                $observaciones = $this->csvGet($row, $headerIndex, ['observaciones']);

                $fechaPedido = $this->parseDateOrNull($fechaPedido);
                if (!$fechaPedido) {
                    $skipped++;
                    $warnings[] = "Línea {$rowNumber}: sin 'Fecha del pedido' válida, fila omitida.";
                    continue;
                }

                $fechaLlegada = $this->parseDateOrNull($fechaLlegada);
                $coste = $this->parseSpanishDecimalOrNull($costeRaw);
                $codigoFactura = $codigoFactura !== '' ? mb_substr($codigoFactura, 0, 120, 'UTF-8') : null;

                $proveedorId = null;
                if ($proveedorNombre !== '') {
                    $prov = \App\Models\GastoProveedor::firstOrCreate(['nombre' => $proveedorNombre]);
                    $proveedorId = $prov->id;
                }

                $motivoId = null;
                if ($motivoNombre !== '') {
                    $mot = \App\Models\GastoMotivo::firstOrCreate(['nombre' => $motivoNombre]);
                    $motivoId = $mot->id;
                }

                $obraId = null;
                $naveId = null;
                $maquinaId = null;

                if ($tipo === 'obra') {
                    $obraId = $this->resolveObraIdFromCsv($obraRaw, $rowNumber, $warnings, $observaciones);
                } else {
                    if ($naveNombre !== '') {
                        $nave = \App\Models\Obra::where('obra', $naveNombre)->first();
                        if ($nave) {
                            $naveId = $nave->id;
                        } else {
                            $warnings[] = "Línea {$rowNumber}: Nave '{$naveNombre}' no encontrada, se importa sin nave.";
                        }
                    }

                    $machine = $this->mapMaquinaCodigo($maquinaRaw);
                    if ($machine['codigo'] !== '') {
                        $maquinaId = $this->findMaquinaIdByCodigo($machine['codigo']);
                        if (!$maquinaId) {
                            $note = $machine['changed']
                                ? "Máquina: {$machine['raw']} → {$machine['codigo']}"
                                : "Máquina: {$machine['codigo']}";
                            $observaciones = $this->appendObservationNote($observaciones, $note);
                            $warnings[] = "Línea {$rowNumber}: {$note}.";
                        }
                    }

                    if ($obraRaw !== '') {
                        $obra = \App\Models\Obra::where('obra', $obraRaw)->first();
                        if ($obra) {
                            $obraId = $obra->id;
                        } else {
                            $warnings[] = "Línea {$rowNumber}: Obra '{$obraRaw}' no encontrada, se importa sin obra.";
                        }
                    }
                }

                Gasto::create([
                    'fecha_pedido' => $fechaPedido,
                    'fecha_llegada' => $fechaLlegada,
                    'nave_id' => $naveId,
                    'obra_id' => $obraId,
                    'proveedor_id' => $proveedorId,
                    'maquina_id' => $maquinaId,
                    'motivo_id' => $motivoId,
                    'coste' => $coste,
                    'codigo_factura' => $codigoFactura,
                    'observaciones' => $observaciones !== '' ? $observaciones : null,
                ]);
                $created++;
            }

            DB::commit();
            fclose($handle);
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Error al importar CSV: ' . $e->getMessage());
        }

        $message = "Importación completada: {$created} filas creadas";
        if ($skipped > 0)
            $message .= ", {$skipped} omitidas";
        $message .= '.';

        $warnings = array_values(array_unique($warnings));
        if (count($warnings) > 30) {
            $warnings = array_slice($warnings, 0, 30);
            $warnings[] = '... (más advertencias omitidas)';
        }

        $report = $message;
        if (count($warnings) > 0) {
            $report .= "\n\nAdvertencias:\n- " . implode("\n- ", $warnings);
        }

        return back()
            ->with('success', $report)
            ->with('import_report', true)
            ->with('tiene_advertencias', count($warnings) > 0)
            ->with('nombre_archivo', $file->getClientOriginalName());
    }

    private function detectCsvDelimiter(string $sample): string
    {
        $comma = substr_count($sample, ',');
        $semicolon = substr_count($sample, ';');
        $tab = substr_count($sample, "\t");

        if ($semicolon > $comma && $semicolon >= $tab)
            return ';';
        if ($tab > $comma && $tab > $semicolon)
            return "\t";
        return ',';
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header; // BOM
        $header = trim($header);
        $header = mb_strtolower($header, 'UTF-8');
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;
        return $header;
    }

    private function buildCsvHeaderIndex(array $rawHeaders): array
    {
        $index = [];
        foreach ($rawHeaders as $i => $h) {
            $normalized = $this->normalizeCsvHeader((string) $h);
            if ($normalized === '')
                continue;
            $index[$normalized] = $i;
        }
        return $index;
    }

    private function csvGet(array $row, array $headerIndex, array $keys): string
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeCsvHeader($key);
            if (array_key_exists($normalized, $headerIndex)) {
                $idx = $headerIndex[$normalized];
                $value = $row[$idx] ?? '';
                $value = is_string($value) ? $value : (string) $value;
                $value = trim($value);
                return $value;
            }
        }
        return '';
    }

    private function parseDateOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '')
            return null;
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseSpanishDecimalOrNull(string $value): ?float
    {
        $value = trim($value);
        if ($value === '')
            return null;

        $normalized = str_replace([' ', '€'], '', $value);
        // Convert "1.234,56" or "1234,56" to "1234.56"
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (!is_numeric($normalized))
            return null;
        return (float) $normalized;
    }

    private function mapMaquinaCodigo(string $value): array
    {
        $raw = trim($value);
        if ($raw === '') {
            return [
                'raw' => '',
                'codigo' => '',
                'changed' => false,
            ];
        }

        $normalized = preg_replace('/\s+/', ' ', $raw) ?? $raw;
        $normalizedUpper = mb_strtoupper($normalized, 'UTF-8');

        $map = [
            'IDEA5_1' => 'ID5',
            'IDEA5_2' => 'ID5 X',
            'TWIN20' => 'TWIN',
            'CORTADORA MANUAL' => 'CM',
            'PILOTERA' => 'PL16',
        ];

        $mapped = $map[$normalizedUpper] ?? $normalizedUpper;

        return [
            'raw' => $raw,
            'codigo' => $mapped,
            'changed' => $mapped !== $normalizedUpper,
        ];
    }

    private function findMaquinaIdByCodigo(string $codigo): ?int
    {
        $codigo = trim($codigo);
        if ($codigo === '')
            return null;

        $exact = \App\Models\Maquina::where('codigo', $codigo)->first();
        if ($exact)
            return $exact->id;

        $compact = str_replace(' ', '', mb_strtoupper($codigo, 'UTF-8'));
        if ($compact === '')
            return null;

        $match = \App\Models\Maquina::whereRaw("REPLACE(UPPER(codigo), ' ', '') = ?", [$compact])->first();
        return $match?->id;
    }

    private function appendObservationNote(?string $observaciones, string $note): string
    {
        $note = trim($note);
        $base = trim((string) $observaciones);
        if ($note === '')
            return $base;
        if ($base === '')
            return $note;
        return $base . ' | ' . $note;
    }

    private function resolveObraIdFromCsv(string $obraRaw, int $rowNumber, array &$warnings, string &$observaciones): ?int
    {
        $obraRaw = trim($obraRaw);
        if ($obraRaw === '') {
            return null;
        }

        if (!preg_match('/^OBRA\\s*-\\s*(.+)$/iu', $obraRaw, $m)) {
            $observaciones = $this->appendObservationNote($observaciones, "Obra: {$obraRaw}");
            return null;
        }

        $code = trim((string) ($m[1] ?? ''));
        if ($code === '') {
            $observaciones = $this->appendObservationNote($observaciones, "Obra: {$obraRaw}");
            return null;
        }

        $obra = \App\Models\Obra::where('cod_obra', $code)->first();
        if ($obra) {
            return $obra->id;
        }

        $note = "Obra no encontrada (cod_obra={$code})";
        $observaciones = $this->appendObservationNote($observaciones, $note);
        $warnings[] = "Línea {$rowNumber}: {$note}.";
        return null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date',
            'fecha_llegada' => 'nullable|date',
            'nave_id' => 'nullable|exists:obras,id',
            'obra_id' => 'nullable|exists:obras,id',
            'proveedor_id' => 'nullable|exists:gastos_proveedores,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
            'motivo_id' => 'nullable|exists:gastos_motivos,id',
            'coste' => 'nullable|numeric',
            'codigo_factura' => 'nullable|string|max:120',
            'observaciones' => 'nullable|string',
        ]);

        $gasto = Gasto::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto creado correctamente.',
                'gasto' => $gasto
            ]);
        }

        return redirect()->route('gastos.index')->with('success', 'Gasto creado correctamente.');
    }

    public function update(Request $request, Gasto $gasto)
    {
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date',
            'fecha_llegada' => 'nullable|date',
            'nave_id' => 'nullable|exists:obras,id',
            'obra_id' => 'nullable|exists:obras,id',
            'proveedor_id' => 'nullable|exists:gastos_proveedores,id',
            'maquina_id' => 'nullable|exists:maquinas,id',
            'motivo_id' => 'nullable|exists:gastos_motivos,id',
            'coste' => 'nullable|numeric',
            'codigo_factura' => 'nullable|string|max:120',
            'observaciones' => 'nullable|string',
        ]);

        $gasto->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Gasto actualizado correctamente.',
                'gasto' => $gasto
            ]);
        }

        return redirect()->route('gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Gasto $gasto)
    {
        $gasto->delete();
        return redirect()->route('gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }

    public function storeProveedor(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:gastos_proveedores,nombre|max:255',
        ]);

        $proveedor = \App\Models\GastoProveedor::create($validated);

        return response()->json([
            'success' => true,
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre
        ]);
    }

    public function storeMotivo(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:gastos_motivos,nombre|max:255',
        ]);

        $motivo = \App\Models\GastoMotivo::create($validated);

        return response()->json([
            'success' => true,
            'id' => $motivo->id,
            'nombre' => $motivo->nombre
        ]);
    }
}
