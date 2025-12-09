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
     * Extraer el DNI/NIE y datos personales de las imágenes del documento
     *
     * @param UploadedFile|null $imagenFrontal Imagen del frente del DNI
     * @param UploadedFile|null $imagenTrasera Imagen del reverso del DNI (opcional)
     * @param string|null $rutaFrontalTmp Ruta de archivo temporal frontal (si no hay UploadedFile)
     * @param string|null $rutaTraseraTmp Ruta de archivo temporal trasero (si no hay UploadedFile)
     * @return array ['dni' => string|null, 'nombre' => string|null, 'primer_apellido' => string|null, 'segundo_apellido' => string|null, 'confianza' => float, 'texto_raw' => string]
     */
    public function extraerDni(?UploadedFile $imagenFrontal, ?UploadedFile $imagenTrasera = null, ?string $rutaFrontalTmp = null, ?string $rutaTraseraTmp = null): array
    {
        $resultado = [
            'dni' => null,
            'nombre' => null,
            'primer_apellido' => null,
            'segundo_apellido' => null,
            'confianza' => 0,
            'texto_raw' => '',
        ];

        // Intentar extraer del frente (desde UploadedFile o desde ruta temporal)
        $textoFrontal = '';
        if ($imagenFrontal) {
            $textoFrontal = $this->procesarImagenConApi($imagenFrontal);
        } elseif ($rutaFrontalTmp && file_exists($rutaFrontalTmp)) {
            $textoFrontal = $this->procesarImagenConRuta($rutaFrontalTmp);
        }
        $resultado['texto_raw'] .= "FRONTAL:\n" . $textoFrontal . "\n";

        $dni = $this->buscarDniEnTexto($textoFrontal);
        $datosPersonales = $this->extraerDatosPersonales($textoFrontal);

        // Si tenemos reverso, procesar también (MRZ suele estar en el reverso)
        $textoTrasero = '';
        if ($imagenTrasera) {
            $textoTrasero = $this->procesarImagenConApi($imagenTrasera);
        } elseif ($rutaTraseraTmp && file_exists($rutaTraseraTmp)) {
            $textoTrasero = $this->procesarImagenConRuta($rutaTraseraTmp);
        }

        if (!empty($textoTrasero)) {
            $resultado['texto_raw'] .= "TRASERA:\n" . $textoTrasero . "\n";

            // Si no encontramos DNI en el frente, intentar en el reverso
            if (!$dni) {
                $dni = $this->buscarDniEnTexto($textoTrasero);
            }

            // Intentar extraer datos personales del reverso (MRZ)
            $datosPersonalesTrasero = $this->extraerDatosPersonales($textoTrasero);

            // Priorizar datos del reverso (MRZ es más fiable)
            if (!empty($datosPersonalesTrasero['nombre'])) {
                $datosPersonales = $datosPersonalesTrasero;
            }
        }

        if ($dni) {
            $resultado['dni'] = strtoupper($dni);
            $resultado['confianza'] = $this->validarFormatoDni($dni) ? 1.0 : 0.5;
        }

        // Asignar datos personales al resultado
        $resultado['nombre'] = $datosPersonales['nombre'] ?? null;
        $resultado['primer_apellido'] = $datosPersonales['primer_apellido'] ?? null;
        $resultado['segundo_apellido'] = $datosPersonales['segundo_apellido'] ?? null;

        Log::info('OCR DNI - Resultado final', [
            'dni_encontrado' => $resultado['dni'],
            'nombre' => $resultado['nombre'],
            'primer_apellido' => $resultado['primer_apellido'],
            'segundo_apellido' => $resultado['segundo_apellido'],
            'confianza' => $resultado['confianza'],
        ]);

        return $resultado;
    }

    /**
     * Extraer nombre y apellidos del texto OCR (zona MRZ o texto visible)
     */
    private function extraerDatosPersonales(string $texto): array
    {
        $datos = [
            'nombre' => null,
            'primer_apellido' => null,
            'segundo_apellido' => null,
        ];

        $textoOriginal = $texto;
        $texto = strtoupper($texto);

        // ============================================
        // MÉTODO 1: Buscar en zona MRZ del DNI/NIE español
        // Formato MRZ línea 1: IDESP[DNI/NIE]<[APELLIDO1]<[APELLIDO2]<<[NOMBRE]<<<<
        // Formato MRZ línea 2: Datos de nacimiento, sexo, etc.
        // ============================================

        // Patrón MRZ del DNI español (línea superior con apellidos y nombre)
        // Ejemplo DNI: IDESP12345678A<GARCIA<LOPEZ<<MARIA<CARMEN<<<<<<
        // Ejemplo NIE: IDESPX1234567A<GARCIA<LOPEZ<<MARIA<<<<<<
        if (preg_match('/IDESP[0-9XYZ][0-9A-Z]{7,8}<+([A-Z]+)<([A-Z]*)<{2,}([A-Z<]+)/i', $texto, $matches)) {
            $datos['primer_apellido'] = $this->limpiarTextoMrz($matches[1]);
            $datos['segundo_apellido'] = $this->limpiarTextoMrz($matches[2]);
            $datos['nombre'] = $this->limpiarTextoMrz($matches[3]);

            Log::info('OCR DNI/NIE - Datos extraídos de MRZ formato 1', $datos);
            return $datos;
        }

        // Patrón MRZ alternativo (con espacios o errores OCR)
        if (preg_match('/ID\s*ESP[0-9XYZ][0-9A-Z\s]{6,9}[<\s]+([A-Z]+)[<\s]+([A-Z]*)[<\s]{2,}([A-Z\s<]+)/i', $texto, $matches)) {
            $datos['primer_apellido'] = $this->limpiarTextoMrz($matches[1]);
            $datos['segundo_apellido'] = $this->limpiarTextoMrz($matches[2]);
            $datos['nombre'] = $this->limpiarTextoMrz($matches[3]);

            Log::info('OCR DNI/NIE - Datos extraídos de MRZ formato 2', $datos);
            return $datos;
        }

        // ============================================
        // MÉTODO 1.5: MRZ de Tarjeta de Residencia (NIE)
        // Formato: INESP[NIE]<[APELLIDO1]<[APELLIDO2]<<[NOMBRE]
        // ============================================
        if (preg_match('/I[DN]ESP[XYZ][0-9]{7}[A-Z]<+([A-Z]+)<([A-Z]*)<{2,}([A-Z<]+)/i', $texto, $matches)) {
            $datos['primer_apellido'] = $this->limpiarTextoMrz($matches[1]);
            $datos['segundo_apellido'] = $this->limpiarTextoMrz($matches[2]);
            $datos['nombre'] = $this->limpiarTextoMrz($matches[3]);

            Log::info('OCR NIE - Datos extraídos de MRZ tarjeta residencia', $datos);
            return $datos;
        }

        // ============================================
        // MÉTODO 2: Buscar etiquetas en el texto visible del DNI
        // El frente del DNI tiene: APELLIDOS / NOMBRE
        // ============================================

        // Buscar "APELLIDOS" seguido del valor
        if (preg_match('/APELLIDOS?\s*[:\-]?\s*([A-ZÁÉÍÓÚÑÜ]+(?:\s+[A-ZÁÉÍÓÚÑÜ]+)?)/i', $textoOriginal, $matches)) {
            $apellidos = trim($matches[1]);
            $partesApellidos = preg_split('/\s+/', $apellidos);

            if (count($partesApellidos) >= 1) {
                $datos['primer_apellido'] = $this->capitalizarNombre($partesApellidos[0]);
            }
            if (count($partesApellidos) >= 2) {
                $datos['segundo_apellido'] = $this->capitalizarNombre($partesApellidos[1]);
            }
        }

        // Buscar "NOMBRE" seguido del valor
        if (preg_match('/NOMBRE\s*[:\-]?\s*([A-ZÁÉÍÓÚÑÜ]+(?:\s+[A-ZÁÉÍÓÚÑÜ]+)*)/i', $textoOriginal, $matches)) {
            $datos['nombre'] = $this->capitalizarNombre(trim($matches[1]));
        }

        // ============================================
        // MÉTODO 3: Buscar patrón de líneas del DNI frontal
        // A veces el OCR lee las líneas en orden
        // ============================================

        // Si aún no tenemos datos, intentar buscar patrones más flexibles
        if (empty($datos['nombre']) && empty($datos['primer_apellido'])) {
            // Buscar dos líneas consecutivas que parezcan apellidos y nombre
            // Patrón: línea con solo letras mayúsculas (apellidos)
            // Seguida de otra línea con solo letras mayúsculas (nombre)
            $lineas = preg_split('/[\r\n]+/', $texto);
            $lineasValidas = [];

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                // Línea que solo contiene letras y espacios (posible nombre/apellido)
                if (preg_match('/^[A-ZÁÉÍÓÚÑÜ\s]{2,50}$/', $linea) && strlen($linea) > 2) {
                    $lineasValidas[] = $linea;
                }
            }

            // Si encontramos al menos 2 líneas válidas, podrían ser apellidos y nombre
            if (count($lineasValidas) >= 2) {
                // Heurística: los apellidos suelen ir antes que el nombre
                $posibleApellidos = $lineasValidas[0];
                $posibleNombre = $lineasValidas[1];

                $partesApellidos = preg_split('/\s+/', $posibleApellidos);
                if (count($partesApellidos) >= 1 && empty($datos['primer_apellido'])) {
                    $datos['primer_apellido'] = $this->capitalizarNombre($partesApellidos[0]);
                }
                if (count($partesApellidos) >= 2 && empty($datos['segundo_apellido'])) {
                    $datos['segundo_apellido'] = $this->capitalizarNombre($partesApellidos[1]);
                }
                if (empty($datos['nombre'])) {
                    $datos['nombre'] = $this->capitalizarNombre(trim($posibleNombre));
                }
            }
        }

        if (!empty($datos['nombre']) || !empty($datos['primer_apellido'])) {
            Log::info('OCR DNI - Datos extraídos del texto visible', $datos);
        }

        return $datos;
    }

    /**
     * Limpiar texto de zona MRZ (quitar < y formatear)
     */
    private function limpiarTextoMrz(string $texto): ?string
    {
        // Reemplazar < por espacios
        $texto = str_replace('<', ' ', $texto);
        // Quitar espacios múltiples
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = trim($texto);

        if (empty($texto)) {
            return null;
        }

        return $this->capitalizarNombre($texto);
    }

    /**
     * Capitalizar nombre correctamente (Primera letra mayúscula, resto minúscula)
     */
    private function capitalizarNombre(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $palabras = explode(' ', $texto);
        $palabras = array_map(function($palabra) {
            return mb_convert_case($palabra, MB_CASE_TITLE, 'UTF-8');
        }, $palabras);
        return implode(' ', $palabras);
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
     * Procesar una imagen o PDF desde una ruta de archivo usando OCR.space API
     */
    private function procesarImagenConRuta(string $rutaArchivo): string
    {
        try {
            if (!file_exists($rutaArchivo)) {
                Log::warning('OCR: Archivo no encontrado en ruta: ' . $rutaArchivo);
                return '';
            }

            // Convertir archivo a base64
            $fileData = base64_encode(file_get_contents($rutaArchivo));
            $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));
            $mimeType = mime_content_type($rutaArchivo);

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
                ['name' => 'OCREngine', 'contents' => '2'],
            ];

            if ($isPdf) {
                $postData[] = ['name' => 'isTable', 'contents' => 'true'];
            }

            $response = Http::timeout(60)
                ->asMultipart()
                ->post('https://api.ocr.space/parse/image', $postData);

            if ($response->successful()) {
                $data = $response->json();

                $textoCompleto = '';
                if (isset($data['ParsedResults']) && is_array($data['ParsedResults'])) {
                    foreach ($data['ParsedResults'] as $result) {
                        if (isset($result['ParsedText'])) {
                            $textoCompleto .= $result['ParsedText'] . "\n";
                        }
                    }
                }

                if (!empty($textoCompleto)) {
                    Log::info('OCR DNI (desde ruta) - Texto extraído: ' . substr($textoCompleto, 0, 500));
                    return $textoCompleto;
                }

                if (isset($data['ErrorMessage'])) {
                    Log::error('OCR API Error (desde ruta): ' . implode(', ', (array)$data['ErrorMessage']));
                }
            } else {
                Log::error('OCR API HTTP Error (desde ruta): ' . $response->status());
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Error en OCR API (desde ruta): ' . $e->getMessage());
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
