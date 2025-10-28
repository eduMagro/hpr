<?php

namespace App\Services\PlanillaImport;

use Illuminate\Http\UploadedFile;
use App\Services\PlanillaImport\DTOs\DatosImportacion;
use ZipArchive;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * SOLUCIÓN RADICAL: Lee XML directamente sin PhpSpreadsheet
 * 
 * Esta versión:
 * - NO usa PhpOffice\PhpSpreadsheet para leer datos
 * - Lee el XML manualmente con DOMDocument
 * - NO valida tipos numéricos (todo es string)
 * - 100% inmune a "Invalid numeric value for datatype Numeric"
 */
class ExcelReader
{
    protected array $estadisticas = [
        'total_filas' => 0,
        'filas_validas' => 0,
        'omitidas_error_peso' => 0,
        'omitidas_av_invalido' => 0,
        'filas_vacias' => 0,
    ];

    protected array $sharedStrings = [];

    public function leer(UploadedFile $file): DatosImportacion
    {
        Log::info("📖 [EXCEL-XML] Iniciando lectura manual de Excel", [
            'archivo' => $file->getClientOriginalName(),
        ]);

        try {
            // 1. Abrir Excel como ZIP
            $zip = new ZipArchive();

            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception("No se pudo abrir el archivo Excel");
            }

            // 2. Leer shared strings (para textos)
            $this->cargarSharedStrings($zip);

            // 3. Leer sheet1.xml (los datos)
            $sheet = $this->leerSheetXML($zip);

            $zip->close();

            if (empty($sheet) || count($sheet) < 2) {
                return DatosImportacion::vacio(['El archivo no tiene filas de datos.']);
            }

            $this->estadisticas['total_filas'] = count($sheet) - 1;

            // 4. Filtrar y anotar filas
            $body = array_slice($sheet, 1);
            $filasFiltradas = $this->filtrarYAnotarFilas($body);

            $this->estadisticas['filas_validas'] = count($filasFiltradas);

            if (empty($filasFiltradas)) {
                return DatosImportacion::vacio(
                    ['No hay filas válidas después del filtrado.'],
                    $this->estadisticas
                );
            }

            // 5. Autocompletar etiquetas
            $this->autocompletarEtiquetas($filasFiltradas);

            Log::info("✅ [EXCEL-XML] Lectura completada", $this->estadisticas);

            return new DatosImportacion($filasFiltradas, $this->estadisticas);
        } catch (\Throwable $e) {
            Log::error("❌ [EXCEL-XML] Error fatal", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Carga el archivo de strings compartidos.
     */
    protected function cargarSharedStrings(ZipArchive $zip): void
    {
        $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');

        if ($xmlStrings === false) {
            Log::info("ℹ️ [EXCEL-XML] No hay sharedStrings.xml (sin textos compartidos)");
            return;
        }

        $dom = new DOMDocument();
        @$dom->loadXML($xmlStrings);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $siNodes = $xpath->query('//x:si');

        foreach ($siNodes as $index => $siNode) {
            // Extraer todo el texto del nodo (puede tener varios <t>)
            $text = '';
            $tNodes = $xpath->query('.//x:t', $siNode);

            foreach ($tNodes as $tNode) {
                $text .= $tNode->textContent;
            }

            $this->sharedStrings[$index] = $text;
        }

        Log::info("📚 [EXCEL-XML] Shared strings cargados", [
            'total' => count($this->sharedStrings),
        ]);
    }

    /**
     * Lee el XML de la hoja y extrae los datos.
     * 
     * CLAVE: Lee TODOS los valores como strings, sin validación de tipos.
     */
    protected function leerSheetXML(ZipArchive $zip): array
    {
        $xmlSheet = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($xmlSheet === false) {
            throw new \Exception("No se pudo leer xl/worksheets/sheet1.xml");
        }

        Log::info("📄 [EXCEL-XML] Parseando sheet1.xml...");

        $dom = new DOMDocument();
        @$dom->loadXML($xmlSheet);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        // Obtener dimensiones de la hoja
        $dimension = $xpath->query('//x:dimension/@ref')->item(0);
        $maxCol = 100; // Asumir hasta columna CV (100 columnas)

        if ($dimension) {
            // Extraer columna máxima de dimensión (ej: "A1:AV1000")
            if (preg_match('/:([A-Z]+)\d+$/', $dimension->value, $m)) {
                $maxCol = $this->columnaANumero($m[1]);
            }
        }

        Log::info("📐 [EXCEL-XML] Dimensiones detectadas", [
            'columnas' => $maxCol,
        ]);

        // Extraer todas las filas
        $rows = [];
        $rowNodes = $xpath->query('//x:sheetData/x:row');

        Log::info("🔢 [EXCEL-XML] Total filas XML", [
            'filas' => $rowNodes->length,
        ]);

        foreach ($rowNodes as $rowNode) {
            $rowIndex = (int)$rowNode->getAttribute('r');

            // Crear array vacío para la fila
            $rowData = array_fill(0, $maxCol, null);

            // Extraer celdas de esta fila
            $cellNodes = $xpath->query('.//x:c', $rowNode);

            foreach ($cellNodes as $cellNode) {
                $cellRef = $cellNode->getAttribute('r'); // Ej: "A1", "B5", "X27"
                $cellType = $cellNode->getAttribute('t'); // Tipo: s=string, n=numeric, str=formula string

                // Extraer índice de columna (A=0, B=1, ..., Z=25, AA=26, ...)
                if (preg_match('/^([A-Z]+)\d+$/', $cellRef, $m)) {
                    $colIndex = $this->columnaANumero($m[1]) - 1; // 0-indexed
                } else {
                    continue;
                }

                // Obtener valor
                $vNode = $xpath->query('.//x:v', $cellNode)->item(0);

                if (!$vNode) {
                    continue; // Sin valor
                }

                $valor = $vNode->textContent;

                // CLAVE: Interpretar según tipo, pero SIEMPRE devolver string
                if ($cellType === 's') {
                    // Referencia a shared string
                    $index = (int)$valor;
                    $rowData[$colIndex] = $this->sharedStrings[$index] ?? '';
                } else {
                    // Cualquier otro tipo: devolver tal cual como string
                    // NO validamos si es numérico válido - lo dejamos como está
                    $rowData[$colIndex] = $valor;
                }
            }

            $rows[] = $rowData;
        }

        Log::info("✅ [EXCEL-XML] Filas extraídas", [
            'total' => count($rows),
        ]);

        return $rows;
    }

    /**
     * Convierte referencia de columna a número.
     * A=1, B=2, ..., Z=26, AA=27, AB=28, ..., AV=48
     */
    protected function columnaANumero(string $col): int
    {
        $num = 0;
        $len = strlen($col);

        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($col[$i]) - ord('A') + 1);
        }

        return $num;
    }

    protected function filtrarYAnotarFilas(array $body): array
    {
        $filasValidas = [];

        foreach ($body as $index => $row) {
            // Fila vacía
            if (!array_filter($row)) {
                $this->estadisticas['filas_vacias']++;
                continue;
            }

            // Columna AD (índice 29): "error de peso"
            $colAD = $row[29] ?? '';
            if (stripos($colAD, 'error de peso') !== false) {
                $this->estadisticas['omitidas_error_peso']++;
                continue;
            }

            // Columna AV (índice 47): dimensiones
            $colAV = trim((string)($row[47] ?? ''));
            if ($colAV === '' || str_starts_with($colAV, ';')) {
                $this->estadisticas['omitidas_av_invalido']++;
                continue;
            }

            // Anotar número de fila Excel
            $row['_xl_row'] = $index + 2;
            $filasValidas[] = $row;
        }

        return $filasValidas;
    }

    protected function autocompletarEtiquetas(array &$rows): void
    {
        $IDX_PLANILLA = 10;
        $IDX_DESC     = 22;
        $IDX_ETIQ     = 30;

        $porPlanilla = [];

        foreach ($rows as $i => $r) {
            $codigoPlanilla = (string)($r[$IDX_PLANILLA] ?? 'Sin código');
            $porPlanilla[$codigoPlanilla][] = $i;
        }

        $normalizar = fn($t) => ($t = mb_strtoupper(
            preg_replace('/\s+/u', ' ', trim((string)$t)),
            'UTF-8'
        )) ?: '—SIN DESCRIPCION—';

        foreach ($porPlanilla as $codigoPlanilla => $indices) {
            $desc2num = [];
            $siguiente = 1;

            foreach ($indices as $i) {
                $descripcion = $normalizar($rows[$i][$IDX_DESC] ?? '');

                if (!isset($desc2num[$descripcion])) {
                    $desc2num[$descripcion] = $siguiente++;
                }

                $rows[$i][$IDX_ETIQ] = $desc2num[$descripcion];
            }
        }
    }
}
