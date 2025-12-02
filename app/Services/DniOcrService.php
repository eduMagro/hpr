<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class DniOcrService
{
    /**
     * API Key de OCR.space (gratuita hasta 25,000 peticiones/mes)
     * Puedes obtener una en: https://ocr.space/ocrapi/freekey
     */
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.ocr_space.api_key', 'K88888888888957'); // Key de prueba gratuita
    }

    /**
     * Extraer el DNI/NIE de las imágenes del documento
     *
     * @param UploadedFile $imagenFrontal Imagen del frente del DNI
     * @param UploadedFile|null $imagenTrasera Imagen del reverso del DNI (opcional)
     * @return array ['dni' => string|null, 'confianza' => float, 'texto_raw' => string]
     */
    public function extraerDni(UploadedFile $imagenFrontal, ?UploadedFile $imagenTrasera = null): array
    {
        $resultado = [
            'dni' => null,
            'confianza' => 0,
            'texto_raw' => '',
        ];

        // Intentar extraer del frente
        $textoFrontal = $this->procesarImagenConApi($imagenFrontal);
        $resultado['texto_raw'] .= "FRONTAL:\n" . $textoFrontal . "\n";

        $dni = $this->buscarDniEnTexto($textoFrontal);

        // Si no encontramos en el frente y tenemos reverso, intentar ahí
        if (!$dni && $imagenTrasera) {
            $textoTrasero = $this->procesarImagenConApi($imagenTrasera);
            $resultado['texto_raw'] .= "TRASERA:\n" . $textoTrasero . "\n";
            $dni = $this->buscarDniEnTexto($textoTrasero);
        }

        if ($dni) {
            $resultado['dni'] = strtoupper($dni);
            $resultado['confianza'] = $this->validarFormatoDni($dni) ? 1.0 : 0.5;
        }

        Log::info('OCR DNI - Resultado final', [
            'dni_encontrado' => $resultado['dni'],
            'confianza' => $resultado['confianza'],
        ]);

        return $resultado;
    }

    /**
     * Procesar una imagen o PDF usando OCR.space API
     */
    private function procesarImagenConApi(UploadedFile $archivo): string
    {
        try {
            // Convertir archivo a base64
            $fileData = base64_encode(file_get_contents($archivo->getPathname()));
            $mimeType = $archivo->getMimeType();
            $extension = strtolower($archivo->getClientOriginalExtension());

            // Determinar el tipo de archivo para la API
            $isPdf = $extension === 'pdf' || $mimeType === 'application/pdf';

            // Construir base64 con el prefijo correcto
            if ($isPdf) {
                $base64File = "data:application/pdf;base64,{$fileData}";
            } else {
                $base64File = "data:{$mimeType};base64,{$fileData}";
            }

            $postData = [
                ['name' => 'apikey', 'contents' => $this->apiKey],
                ['name' => 'base64Image', 'contents' => $base64File],
                ['name' => 'language', 'contents' => 'spa'],
                ['name' => 'isOverlayRequired', 'contents' => 'false'],
                ['name' => 'detectOrientation', 'contents' => 'true'],
                ['name' => 'scale', 'contents' => 'true'],
                ['name' => 'OCREngine', 'contents' => '2'], // Engine 2 es mejor para documentos
            ];

            // Si es PDF, añadir opción para procesar todas las páginas
            if ($isPdf) {
                $postData[] = ['name' => 'isTable', 'contents' => 'true'];
            }

            $response = Http::timeout(60) // Más tiempo para PDFs
                ->asMultipart()
                ->post('https://api.ocr.space/parse/image', $postData);

            if ($response->successful()) {
                $data = $response->json();

                // Combinar texto de todas las páginas (para PDFs multipágina)
                $textoCompleto = '';
                if (isset($data['ParsedResults']) && is_array($data['ParsedResults'])) {
                    foreach ($data['ParsedResults'] as $result) {
                        if (isset($result['ParsedText'])) {
                            $textoCompleto .= $result['ParsedText'] . "\n";
                        }
                    }
                }

                if (!empty($textoCompleto)) {
                    Log::info('OCR DNI - Texto extraído (' . ($isPdf ? 'PDF' : 'imagen') . '): ' . substr($textoCompleto, 0, 500));
                    return $textoCompleto;
                }

                if (isset($data['ErrorMessage'])) {
                    Log::error('OCR API Error: ' . implode(', ', (array)$data['ErrorMessage']));
                }

                if (isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing']) {
                    Log::error('OCR API Processing Error', $data);
                }
            } else {
                Log::error('OCR API HTTP Error: ' . $response->status() . ' - ' . $response->body());
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Error en OCR API: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Buscar patrón de DNI/NIE en el texto extraído
     */
    private function buscarDniEnTexto(string $texto): ?string
    {
        // Guardar texto original para log
        $textoOriginal = $texto;

        // Limpiar el texto
        $texto = strtoupper($texto);

        // Reemplazar caracteres que el OCR confunde frecuentemente
        $texto = str_replace(['O', 'o', 'I', 'l', '|'], ['0', '0', '1', '1', '1'], $texto);

        // Normalizar espacios
        $texto = preg_replace('/\s+/', ' ', $texto);

        Log::debug('OCR DNI - Texto procesado: ' . substr($texto, 0, 300));

        // ============================================
        // PATRONES PARA DNI ESPAÑOL (8 números + letra)
        // ============================================
        $patronesDni = [
            // Zona MRZ del reverso del DNI: IDESP12345678A<<<<<
            '/IDESP(\d{8})([A-Z])/i',
            '/ID.?ESP.?(\d{8})([A-Z])/i',

            // DNI estándar: 12345678A
            '/\b(\d{8})\s*([A-HJ-NP-TV-Z])\b/',

            // Con separadores: 12.345.678-A o 12 345 678 A
            '/\b(\d{2})[.\s-]?(\d{3})[.\s-]?(\d{3})[.\s-]?([A-HJ-NP-TV-Z])\b/',

            // Después de texto "DNI" o "NIF" o "NUM"
            '/(?:DNI|NIF|NUM|N[UÚ]MERO)[:\s.-]*(\d{8})\s*([A-HJ-NP-TV-Z])/i',

            // Formato con guión: 12345678-A
            '/(\d{8})[-\s]([A-HJ-NP-TV-Z])\b/',
        ];

        // ============================================
        // PATRONES PARA NIE (X/Y/Z + 7 números + letra)
        // ============================================
        $patronesNie = [
            // NIE estándar: X1234567A
            '/\b([XYZ])[-.\s]?(\d{7})[-.\s]?([A-HJ-NP-TV-Z])\b/i',

            // Después de texto "NIE"
            '/NIE[:\s.-]*([XYZ])[-.\s]?(\d{7})[-.\s]?([A-HJ-NP-TV-Z])/i',

            // Zona MRZ para NIE
            '/IDESP([XYZ])(\d{7})([A-Z])/i',
        ];

        // Buscar DNI
        foreach ($patronesDni as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                Log::debug('OCR DNI - Patrón coincidente: ' . $patron, $matches);

                // Reconstruir el DNI según el patrón
                if (count($matches) == 3) {
                    $candidato = $matches[1] . $matches[2];
                } elseif (count($matches) == 5) {
                    $candidato = $matches[1] . $matches[2] . $matches[3] . $matches[4];
                } else {
                    continue;
                }

                // Validar que la letra sea correcta
                if ($this->validarFormatoDni($candidato)) {
                    return $candidato;
                }

                Log::debug('OCR DNI - Candidato rechazado por letra incorrecta: ' . $candidato);
            }
        }

        // Buscar NIE
        foreach ($patronesNie as $patron) {
            if (preg_match($patron, $texto, $matches)) {
                Log::debug('OCR DNI - Patrón NIE coincidente: ' . $patron, $matches);
                $candidato = strtoupper($matches[1]) . $matches[2] . strtoupper($matches[3]);

                if ($this->validarFormatoDni($candidato)) {
                    return $candidato;
                }
            }
        }

        // ============================================
        // BÚSQUEDA AGRESIVA (último recurso)
        // ============================================

        // Eliminar todo excepto números y letras
        $textoLimpio = preg_replace('/[^0-9A-Z]/', '', $texto);

        // Buscar cualquier secuencia de 8 dígitos + letra válida
        if (preg_match_all('/(\d{8})([A-HJ-NP-TV-Z])/', $textoLimpio, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $candidato = $match[1] . $match[2];
                if ($this->validarFormatoDni($candidato)) {
                    Log::debug('OCR DNI - Encontrado en búsqueda agresiva: ' . $candidato);
                    return $candidato;
                }
            }
        }

        // Buscar NIE en texto limpio
        if (preg_match_all('/([XYZ])(\d{7})([A-HJ-NP-TV-Z])/', $textoLimpio, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $candidato = $match[1] . $match[2] . $match[3];
                if ($this->validarFormatoDni($candidato)) {
                    Log::debug('OCR DNI - NIE encontrado en búsqueda agresiva: ' . $candidato);
                    return $candidato;
                }
            }
        }

        Log::warning('OCR DNI - No se encontró DNI válido en el texto');
        return null;
    }

    /**
     * Validar que el DNI tiene el formato correcto y la letra es válida
     */
    public function validarFormatoDni(string $dni): bool
    {
        $dni = strtoupper(trim($dni));

        // Formato DNI: 8 números + letra
        if (preg_match('/^(\d{8})([A-Z])$/', $dni, $matches)) {
            return $this->verificarLetraDni($matches[1], $matches[2]);
        }

        // Formato NIE: X/Y/Z + 7 números + letra
        if (preg_match('/^([XYZ])(\d{7})([A-Z])$/', $dni, $matches)) {
            return $this->verificarLetraNie($matches[1], $matches[2], $matches[3]);
        }

        return false;
    }

    /**
     * Verificar la letra de control del DNI
     */
    private function verificarLetraDni(string $numeros, string $letra): bool
    {
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $indice = intval($numeros) % 23;
        return $letras[$indice] === $letra;
    }

    /**
     * Verificar la letra de control del NIE
     */
    private function verificarLetraNie(string $prefijo, string $numeros, string $letra): bool
    {
        // Convertir prefijo a número: X=0, Y=1, Z=2
        $prefijoNum = ['X' => '0', 'Y' => '1', 'Z' => '2'];
        $numeroCompleto = $prefijoNum[$prefijo] . $numeros;

        return $this->verificarLetraDni($numeroCompleto, $letra);
    }

    /**
     * Extraer los números del DNI para usar como contraseña
     */
    public function extraerNumerosParaPassword(string $dni): string
    {
        return preg_replace('/[^0-9]/', '', $dni);
    }
}
