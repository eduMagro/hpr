<?php

namespace App\Services\PlanillaImport;

use Illuminate\Http\UploadedFile;
use ZipArchive;
use DOMDocument;
use App\Services\PlanillaImport\DTOs\ValidacionResult;

/**
 * Valida archivos Excel antes de procesarlos.
 * 
 * Realiza validaciones de:
 * - Integridad del archivo
 * - Celdas numéricas válidas
 * - Estructura esperada
 */
class ExcelValidator
{
    /**
     * Valida un archivo Excel.
     *
     * @param UploadedFile $file
     * @return ValidacionResult
     */
    public function validar(UploadedFile $file): ValidacionResult
    {
        $errores = [];
        $advertencias = [];

        // 1. Validar que es un archivo Excel válido
        if (!$this->esArchivoExcelValido($file)) {
            $errores[] = "El archivo no es un Excel válido (.xlsx o .xls)";
            return new ValidacionResult(false, $errores, $advertencias);
        }

        // 2. Escanear XML interno para detectar celdas numéricas inválidas
        $celdasInvalidas = $this->escanearCeldasNumericas($file->getRealPath());

        if (!empty($celdasInvalidas)) {
            $detalles = $this->formatearCeldasInvalidas($celdasInvalidas);
            $errores[] = "El Excel contiene celdas marcadas como numéricas con valores inválidos: {$detalles}. " .
                "Corrige esas celdas (pon número válido o cambia el tipo de celda a Texto) y vuelve a importar.";

            return new ValidacionResult(false, $errores, $advertencias);
        }

        // 3. Validar tamaño de archivo
        $maxSize = config('planillas.importacion.max_file_size', 10240); // 10MB por defecto

        if ($file->getSize() > ($maxSize * 1024)) {
            $errores[] = "El archivo excede el tamaño máximo permitido de " . ($maxSize / 1024) . "MB";
            return new ValidacionResult(false, $errores, $advertencias);
        }

        return new ValidacionResult(true, $errores, $advertencias);
    }

    /**
     * Verifica que el archivo sea un Excel válido.
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function esArchivoExcelValido(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return false;
        }

        // Validar MIME type
        $mimeType = $file->getMimeType();
        $mimeTypesValidos = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/excel',
            'application/x-excel',
        ];

        return in_array($mimeType, $mimeTypesValidos);
    }

    /**
     * Escanea el XML interno del Excel para detectar celdas numéricas con valores inválidos.
     *
     * @param string $xlsxPath Ruta al archivo Excel
     * @param int $maxFindings Máximo de hallazgos a reportar
     * @return array Array de ['cell' => 'C27', 'value' => '12,3a']
     */
    protected function escanearCeldasNumericas(string $xlsxPath, int $maxFindings = 5): array
    {
        $bad = [];
        $zip = new ZipArchive();

        if ($zip->open($xlsxPath) !== true) {
            return $bad; // No pudimos abrir; preferimos no bloquear
        }

        // Solo primera hoja: sheet1.xml
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($xml === false) {
            $zip->close();
            return $bad;
        }

        $dom = new DOMDocument();
        @$dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $cNodes = $dom->getElementsByTagName('c'); // c = cell

        foreach ($cNodes as $c) {
            /** @var \DOMElement $c */
            $tAttr = $c->getAttribute('t'); // tipo
            $rAttr = $c->getAttribute('r'); // dirección (ej. C27)

            // Solo revisamos celdas numéricas: t=="n" o sin t (numérico por defecto)
            if ($tAttr === '' || $tAttr === 'n') {
                $vNode = $c->getElementsByTagName('v')->item(0);
                $val = $vNode ? $vNode->textContent : '';

                if (!$this->esNumericoXmlValido($val)) {
                    $bad[] = [
                        'cell'  => $rAttr ?: '(sin ref)',
                        'value' => $val,
                    ];

                    if (count($bad) >= $maxFindings) {
                        break;
                    }
                }
            }
        }

        $zip->close();

        return $bad;
    }

    /**
     * Valida si una cadena es numérica válida en XML de Excel.
     *
     * @param string|null $v
     * @return bool
     */
    protected function esNumericoXmlValido(?string $v): bool
    {
        if ($v === null || $v === '') {
            return true; // vacío se permite
        }

        // Número con punto opcional y exponente opcional. Excel en XML usa '.' como decimal.
        return (bool) preg_match('/^-?\d+(\.\d+)?([Ee][+-]?\d+)?$/', trim($v));
    }

    /**
     * Formatea la lista de celdas inválidas para mostrar al usuario.
     *
     * @param array $celdas
     * @return string
     */
    protected function formatearCeldasInvalidas(array $celdas): string
    {
        return collect($celdas)
            ->map(fn($i) => "{$i['cell']}→'{$i['value']}'")
            ->implode(', ');
    }
}
