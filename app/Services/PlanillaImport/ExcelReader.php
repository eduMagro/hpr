<?php

namespace App\Services\PlanillaImport;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\PlanillaImport\DTOs\DatosImportacion;

/**
 * Lee y procesa archivos Excel para importación de planillas.
 * 
 * Responsabilidades:
 * - Leer archivo Excel
 * - Filtrar filas inválidas
 * - Anotar números de fila para trazabilidad
 * - Autocompletar etiquetas
 * - Preparar datos para procesamiento
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

    /**
     * Lee un archivo Excel y retorna los datos preparados.
     *
     * @param UploadedFile $file
     * @return DatosImportacion
     */
    public function leer(UploadedFile $file): DatosImportacion
    {
        // 1. Leer primera hoja con control de filas
        $sheet = $this->leerPrimeraHoja($file);

        if (!$sheet || count($sheet) < 2) {
            return DatosImportacion::vacio([
                'El archivo no tiene filas de datos.'
            ]);
        }

        $this->estadisticas['total_filas'] = count($sheet) - 1; // Sin cabecera

        // 2. Filtrar y anotar filas
        $body = array_slice($sheet, 1); // Quitar cabecera
        $filasFiltradas = $this->filtrarYAnotarFilas($body);

        $this->estadisticas['filas_validas'] = count($filasFiltradas);

        if (empty($filasFiltradas)) {
            return DatosImportacion::vacio([
                'No hay filas válidas después del filtrado.'
            ], $this->estadisticas);
        }

        // 3. Autocompletar etiquetas por descripción
        $this->autocompletarEtiquetas($filasFiltradas);

        // 4. Crear objeto de datos
        return new DatosImportacion(
            $filasFiltradas,
            $this->estadisticas
        );
    }

    /**
     * Lee la primera hoja del Excel con manejo robusto de errores.
     *
     * @param UploadedFile $file
     * @return array
     * @throws \Exception Si hay error leyendo una fila específica
     */
    protected function leerPrimeraHoja(UploadedFile $file): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());

        // Configuración para lectura rápida
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
            $rowIndex = $row->getRowIndex(); // Número de fila real en Excel (1-based)

            try {
                $cellIt = $row->getCellIterator();
                $cellIt->setIterateOnlyExistingCells(false); // Incluir vacías

                $rowData = [];

                foreach ($cellIt as $cell) {
                    // getFormattedValue evita algunos casteos agresivos
                    $rowData[] = $cell ? $cell->getFormattedValue() : null;
                }

                $rows[] = $rowData;
            } catch (\Throwable $e) {
                // Capturamos exactamente la fila que revienta al leer
                throw new \Exception("Error leyendo Excel en fila {$rowIndex}: " . $e->getMessage());
            }
        }

        return $rows;
    }

    /**
     * Filtra filas inválidas y anota números de fila Excel.
     *
     * @param array $body
     * @return array
     */
    protected function filtrarYAnotarFilas(array $body): array
    {
        $filasValidas = [];

        foreach ($body as $index => $row) {
            // Fila completamente vacía
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

            // Anotar número de fila Excel (cabecera = 1, primera fila de datos = 2)
            $row['_xl_row'] = $index + 2;

            $filasValidas[] = $row;
        }

        return $filasValidas;
    }

    /**
     * Autocompleta números de etiqueta vacíos por descripción.
     * 
     * Dentro de cada planilla, asigna números secuenciales (1..N) a elementos
     * con la misma descripción.
     *
     * @param array &$rows Referencia al array de filas (se modifica in-place)
     * @return void
     */
    protected function autocompletarEtiquetas(array &$rows): void
    {
        $IDX_PLANILLA = 10; // Código planilla
        $IDX_DESC     = 22; // Descripción
        $IDX_ETIQ     = 30; // AE (número etiqueta)

        // Agrupar índices de filas por planilla
        $porPlanilla = [];

        foreach ($rows as $i => $r) {
            $codigoPlanilla = (string)($r[$IDX_PLANILLA] ?? 'Sin código');
            $porPlanilla[$codigoPlanilla][] = $i;
        }

        // Función para normalizar descripciones
        $normalizar = fn($t) => ($t = mb_strtoupper(
            preg_replace('/\s+/u', ' ', trim((string)$t)),
            'UTF-8'
        )) ?: '—SIN DESCRIPCION—';

        // Procesar cada planilla
        foreach ($porPlanilla as $codigoPlanilla => $indices) {
            $desc2num = [];
            $siguiente = 1;

            // Recorremos en orden de aparición y ASIGNAMOS SIEMPRE 1..N
            foreach ($indices as $i) {
                $descripcion = $normalizar($rows[$i][$IDX_DESC] ?? '');

                if (!isset($desc2num[$descripcion])) {
                    $desc2num[$descripcion] = $siguiente++;
                }

                // Siempre sobrescribimos AE con la numeración compacta
                $rows[$i][$IDX_ETIQ] = $desc2num[$descripcion];
            }
        }
    }
}
