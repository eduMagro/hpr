<?php

namespace App\Services\PlanillaImport;

use Illuminate\Http\UploadedFile;
use App\Services\PlanillaImport\DTOs\ValidacionResult;

/**
 * Validador simplificado para archivos Excel.
 * 
 * Como ahora leemos el XML directamente sin validación de tipos,
 * este validador solo verifica integridad básica del archivo.
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

        // 2. Validar tamaño de archivo
        $maxSize = config('planillas.importacion.max_file_size', 10240); // 10MB por defecto

        if ($file->getSize() > ($maxSize * 1024)) {
            $errores[] = "El archivo excede el tamaño máximo permitido de " . ($maxSize / 1024) . "MB";
            return new ValidacionResult(false, $errores, $advertencias);
        }

        // 3. Validar que el archivo se puede abrir como ZIP
        if (!$this->puedeAbrirseComoZip($file->getRealPath())) {
            $errores[] = "El archivo Excel está corrupto o no se puede leer";
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
            'application/zip', // Algunos navegadores reportan xlsx como zip
        ];

        return in_array($mimeType, $mimeTypesValidos);
    }

    /**
     * Verifica que el archivo se puede abrir como ZIP.
     *
     * @param string $path
     * @return bool
     */
    protected function puedeAbrirseComoZip(string $path): bool
    {
        $zip = new \ZipArchive();
        $result = $zip->open($path);

        if ($result === true) {
            $zip->close();
            return true;
        }

        return false;
    }
}
