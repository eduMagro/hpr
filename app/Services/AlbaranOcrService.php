<?php

namespace App\Services;

use App\Models\EntradaImportLog;
use App\Models\Obra;
use App\Models\Pedido;
use App\Models\PedidoProducto;
use App\Models\ProductoBase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbaranOcrService
{
    protected array $lastStatusMessages = [];

    public function getLastStatusMessages(): array
    {
        return $this->lastStatusMessages;
    }

    /**
     * Procesa un albarán (imagen o PDF), ejecuta OCR vía OpenAI y persiste un log.
     */
    public function parseAndLog(UploadedFile $file, ?int $userId = null, ?string $proveedor = null): EntradaImportLog
    {
        // Nuevo flujo con consenso OpenAI + Gemini (feature flag OCR_CONSENSO_ENABLED)
        if (env('OCR_CONSENSO_ENABLED', true)) {
            return $this->parseAndLogWithConsensus($file, $userId, $proveedor);
        }

        // Flujo clásico (solo OpenAI)
        return $this->parseAndLogSingle($file, $userId, $proveedor);
    }

    /**
     * Flujo clásico: solo OpenAI (compatibilidad).
     */
    protected function parseAndLogSingle(UploadedFile $file, ?int $userId = null, ?string $proveedor = null): EntradaImportLog
    {
        $storedPath = $file->storeAs(
            'albaranes_entrada/ocr',
            'ocr_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension(),
            'private'
        );

        $absolute = Storage::disk('private')->path($storedPath);
        $extension = strtolower($file->getClientOriginalExtension());
        [$rawText, $parsed] = $this->extractWithDocupipe($absolute, $extension, $proveedor);
        if ($proveedor) {
            $parsed['proveedor'] = $proveedor;
        }

        return EntradaImportLog::create([
            'user_id' => $userId,
            'file_path' => $storedPath,
            'raw_text' => $rawText,
            'parsed_payload' => $parsed,
            'status' => 'parsed',
        ]);
    }

    /**
     * Ejecuta OCR con DocuPipe (Upload -> Standardize -> Retrieve).
     */
    protected function extractWithDocupipe(string $path, string $extension, ?string $proveedor): array
    {
        if (!config('docupipe.enabled')) {
            throw new \RuntimeException('Docupipe OCR está deshabilitado. Activa DOCUPIPE_ENABLED.');
        }

        $schema = $this->docupipeSchemaForProveedor($proveedor);
        if (!$schema) {
            throw new \RuntimeException('No se ha configurado un schema de Docupipe para el proveedor.');
        }

        $apiKey = config('docupipe.api_key');
        if (!$apiKey) {
            throw new \RuntimeException('Falta DOCUPIPE_API_KEY en el .env');
        }

        [$pathForProcessing, $mimeType, $tempPath] = $this->prepareFileForProcessing($path, $extension);

        $parsed = [];
        $rawText = '';

        try {
            // 1. Upload Document
            $uploadValues = $this->docupipeUpload($pathForProcessing, $apiKey);
            $docId = $uploadValues['documentId'] ?? null;
            $uploadJobId = $uploadValues['jobId'] ?? null;

            if (!$docId || !$uploadJobId) {
                throw new \RuntimeException('Docupipe no devolvió IDs de documento/job al subir.');
            }

            // 2. Poll for Upload Completion
            $this->docupipePoll($uploadJobId, $apiKey);

            // 3. Standardize
            $stdValues = $this->docupipeStandardize($docId, $schema, $apiKey);
            $stdJobId = $stdValues['jobId'] ?? null;
            $stdIds = $stdValues['standardizationIds'] ?? [];
            $stdId = $stdIds[0] ?? null;

            if (!$stdId || !$stdJobId) {
                throw new \RuntimeException('Docupipe no devolvió standardizationId/jobId.');
            }

            // 4. Poll for Standardization Completion
            $this->docupipePoll($stdJobId, $apiKey);

            // 5. Retrieve Result
            $parsed = $this->docupipeRetrieve($stdId, $apiKey);
            $rawText = json_encode($parsed);
        } catch (\Exception $e) {
            Log::error('Docupipe error', ['message' => $e->getMessage()]);
            // If API fails, maybe fallback to text parsing if we had text? 
            // But here we likely don't have text if DocuPipe failed early.
            // Rethrowing allows controller to handle it or show error.
            throw $e;
        } finally {
            $this->cleanupTempFile($tempPath);
        }

        if (empty($parsed)) {
            // Fallback: try to parse blank text? Unlikely to work but keeps structure.
            $parsed = $this->parseText('', $proveedor);
        }

        return [$rawText, $parsed];
    }

    protected function docupipeUpload(string $path, string $apiKey): array
    {
        $url = 'https://app.docupipe.ai/document';
        // Ensure file exists
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found for upload: $path");
        }

        $content = base64_encode(file_get_contents($path));
        $filename = basename($path);

        $payload = [
            'document' => [
                'file' => [
                    'contents' => $content,
                    'filename' => $filename,
                ]
            ]
        ];

        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(120)->post($url, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Error subiendo a Docupipe: ' . $response->body());
        }

        return $response->json();
    }

    protected function docupipeStandardize(string $docId, string $schemaId, string $apiKey): array
    {
        $url = 'https://app.docupipe.ai/v2/standardize/batch';
        $payload = [
            'schemaId' => $schemaId,
            'documentIds' => [$docId],
        ];

        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(60)->post($url, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Error iniciando estandarización Docupipe: ' . $response->body());
        }

        return $response->json();
    }

    protected function docupipePoll(string $jobId, string $apiKey): void
    {
        $url = "https://app.docupipe.ai/job/{$jobId}";
        $maxAttempts = 40;
        $attempt = 0;
        $wait = 2;

        while ($attempt < $maxAttempts) {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->successful()) {
                throw new \RuntimeException('Error consultando estado del job Docupipe: ' . $response->body());
            }

            $status = $response->json('status');

            if ($status === 'completed' || $status === 'success') {
                return;
            }

            if ($status === 'failed' || $status === 'error') {
                throw new \RuntimeException('El job de Docupipe falló: ' . $response->body());
            }

            // If processing/pending/etc, continue
            if ($status !== 'processing' && $status !== 'pending' && $status !== 'queued') {
                // If it's something else unknown but not error, assume success?
                // Does DocuPipe have other statuses?
                // Let's assume return if it is not these known "wait" statuses.
                return;
            }

            sleep($wait);
            $attempt++;

            if ($wait < 5) {
                $wait++;
            }
        }

        throw new \RuntimeException("Timeout polling Docupipe job {$jobId}");
    }

    protected function docupipeRetrieve(string $stdId, string $apiKey): array
    {
        $url = "https://app.docupipe.ai/standardization/{$stdId}";
        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Accept' => 'application/json',
        ])->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('Error recuperando resultado de Docupipe: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    protected function docupipeSchemaForProveedor(?string $proveedor): ?string
    {
        $map = config('docupipe.schema_map', []);
        $normalized = [];

        foreach ($map as $name => $schema) {
            if (!$schema) {
                continue;
            }

            $normalized[Str::lower($name)] = $schema;
        }

        if ($proveedor) {
            $candidate = $normalized[Str::lower($proveedor)] ?? null;
            if ($candidate) {
                return $candidate;
            }
        }

        return config('docupipe.default_schema');
    }

    protected function prepareFileForProcessing(string $path, string $extension): array
    {
        $pathForProcessing = $path;
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $tempPath = null;

        if ($extension === 'pdf') {
            if (!extension_loaded('imagick')) {
                throw new \RuntimeException('Para PDFs se necesita Imagick habilitado en PHP.');
            }

            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($path . '[0]');
            $imagick->setImageFormat('jpg');
            $tempPath = sys_get_temp_dir() . '/albaran_ocr_' . uniqid() . '.jpg';
            $imagick->writeImage($tempPath);
            $imagick->clear();
            $imagick->destroy();

            $pathForProcessing = $tempPath;
            $mimeType = 'image/jpeg';
        }

        return [$pathForProcessing, $mimeType, $tempPath];
    }

    protected function cleanupTempFile(?string $path): void
    {
        if ($path && file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Codifica un archivo (incluido PDF) para visión.
     */
    protected function encodeFileForVision(string $path, string $extension): array
    {
        $pathForVision = $path;
        $mimeType = mime_content_type($path);

        if ($extension === 'pdf') {
            if (!extension_loaded('imagick')) {
                throw new \RuntimeException('Para PDFs se necesita Imagick habilitado en PHP.');
            }
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($path . '[0]');
            $imagick->setImageFormat('jpg');
            $tempPath = sys_get_temp_dir() . '/albaran_ocr_' . uniqid() . '.jpg';
            $imagick->writeImage($tempPath);
            $imagick->clear();
            $imagick->destroy();
            $pathForVision = $tempPath;
            $mimeType = 'image/jpeg';
        }

        $base64 = base64_encode(file_get_contents($pathForVision));

        return [$base64, $mimeType, $extension];
    }

    /**
     * Llamada OpenAI Vision (nueva firma reutilizable).
     */
    protected function callOpenAiVision(string $base64, string $mimeType, string $prompt): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('Falta OPENAI_API_KEY en el .env');
        }

        $model = env('OPENAI_MODEL', 'gpt-4.1');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$base64}",
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ],
            'temperature' => 0,
        ]);

        if (!$response->successful()) {
            $msg = $response->json('error.message') ?? 'OpenAI devolvió un error';
            throw new \RuntimeException($msg);
        }

        $rawText = $response->json('choices.0.message.content') ?? '';
        $decoded = json_decode($rawText, true);
        $parsed = is_array($decoded) ? $decoded : $this->parseText($rawText, null);

        return [$rawText, $parsed];
    }

    /**
     * Llamada Gemini Vision (REST v1beta).
     */
    protected function callGeminiVision(string $base64, string $mimeType, string $prompt): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('Falta GEMINI_API_KEY en el .env');
        }

        $apiVersion = env('GEMINI_API_VERSION', 'v1beta'); // v1 por defecto; v1beta si usas el key antiguo
        $modelEnv = env('GEMINI_MODEL', 'gemini-flash-latest');
        $model = Str::startsWith($modelEnv, 'models/') ? $modelEnv : "models/{$modelEnv}";
        $endpoint = "https://generativelanguage.googleapis.com/{$apiVersion}/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(120)->post($endpoint, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
            ],
        ]);

        if (!$response->successful()) {
            $msg = $response->json('error.message') ?? 'Gemini devolvió un error';
            throw new \RuntimeException($msg);
        }

        $rawText = $response->json('candidates.0.content.parts.0.text') ?? '';
        $decoded = json_decode($rawText, true);
        $parsed = is_array($decoded) ? $decoded : $this->parseText($rawText, null);

        return [$rawText, $parsed];
    }

    /**
     * Revisión cuando hay discrepancias: se les muestra la foto y el JSON del otro modelo.
     */
    protected function reviewWithModel(string $provider, string $base64, string $mimeType, string $prompt, array $ownPayload, array $peerPayload): array
    {
        $reviewPrompt = $prompt . "\n\nHay discrepancias entre dos modelos. Relee la imagen y devuelve un ÚNICO JSON final (sin texto extra) siguiendo exactamente el esquema. Tienes tu JSON y el del otro modelo:\n\nTU JSON:\n" . json_encode($ownPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\nJSON DEL OTRO MODELO:\n" . json_encode($peerPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\nElige los valores que mejor encajen con la imagen. No inventes campos nuevos.";

        if ($provider === 'openai') {
            return $this->callOpenAiVision($base64, $mimeType, $reviewPrompt);
        }

        return $this->callGeminiVision($base64, $mimeType, $reviewPrompt);
    }

    /**
     * Construye consenso entre OpenAI y Gemini.
     */
    protected function buildConsensus(array $openaiPayload, array $geminiPayload, string $base64, string $mimeType, string $prompt): array
    {
        $status = [];
        $rounds = 0;
        $reviewRaw = [];

        // Coincidencia inicial
        if ($this->payloadsCoincide($openaiPayload, $geminiPayload)) {
            $status[] = 'Resultado coincidente. No se necesita debate.';
            return [
                'final' => $openaiPayload,
                'consensus' => true,
                'chosen' => 'match',
                'rounds' => $rounds,
                'status_messages' => $status,
            ];
        }

        // Revisión/segunda pasada
        $status[] = 'Revisando discrepancias, cada IA vuelve a mirar la imagen…';
        $rounds++;
        [$reviewOpenaiRaw, $reviewOpenai] = $this->reviewWithModel('openai', $base64, $mimeType, $prompt, $openaiPayload, $geminiPayload);
        [$reviewGeminiRaw, $reviewGemini] = $this->reviewWithModel('gemini', $base64, $mimeType, $prompt, $geminiPayload, $openaiPayload);

        $reviewRaw = [
            'openai' => $reviewOpenaiRaw,
            'gemini' => $reviewGeminiRaw,
        ];

        $reviewOpenai = $this->normalizeParsedPayload($reviewOpenai);
        $reviewGemini = $this->normalizeParsedPayload($reviewGemini);

        // Si tras revisión coinciden, listo
        if ($this->payloadsCoincide($reviewOpenai, $reviewGemini)) {
            $status[] = 'Ambas IA tomando decisión final…';
            return [
                'final' => $reviewOpenai,
                'consensus' => true,
                'chosen' => 'reviewed_match',
                'rounds' => $rounds,
                'status_messages' => $status,
                'review_raw' => $reviewRaw,
            ];
        }

        // Desempate: elegir el payload con mayor completitud
        $status[] = 'Aplicando desempate por completitud de datos…';
        $candidates = [
            'review_openai' => $reviewOpenai,
            'review_gemini' => $reviewGemini,
            'openai' => $openaiPayload,
            'gemini' => $geminiPayload,
        ];

        $bestKey = null;
        $bestScore = -1;
        foreach ($candidates as $key => $payload) {
            $score = $this->payloadScore($payload);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $key;
            }
        }

        $final = $candidates[$bestKey] ?? $openaiPayload;

        return [
            'final' => $final,
            'consensus' => false,
            'chosen' => $bestKey ?? 'openai',
            'rounds' => $rounds,
            'status_messages' => $status,
            'review_raw' => $reviewRaw,
        ];
    }

    /**
     * Normaliza payload para comparaciones (tipos numéricos y claves vacías).
     */
    protected function normalizeParsedPayload(array $payload): array
    {
        $payload['albaran'] = $payload['albaran'] ?? null;
        $payload['pedido_codigo'] = $payload['pedido_codigo'] ?? null;
        $payload['pedido_cliente'] = $payload['pedido_cliente'] ?? null;
        $payload['fecha'] = $payload['fecha'] ?? null;
        $payload['peso_total'] = isset($payload['peso_total']) ? (float) $payload['peso_total'] : null;
        $payload['bultos_total'] = isset($payload['bultos_total']) ? (int) $payload['bultos_total'] : null;
        $payload['tipo_compra'] = $payload['tipo_compra'] ?? null;
        $payload['proveedor_texto'] = $payload['proveedor_texto'] ?? null;

        $productos = $payload['productos'] ?? [];
        $productosNormalizados = [];
        foreach ($productos as $producto) {
            $lineItems = $producto['line_items'] ?? [];
            $lineItemsNormalizados = [];
            foreach ($lineItems as $item) {
                $lineItemsNormalizados[] = [
                    'colada' => $item['colada'] ?? null,
                    'bultos' => isset($item['bultos']) ? (int) $item['bultos'] : null,
                    'peso_kg' => isset($item['peso_kg']) ? (float) $item['peso_kg'] : (isset($item['peso']) ? (float) $item['peso'] : null),
                ];
            }

            $productosNormalizados[] = [
                'descripcion' => isset($producto['descripcion']) ? Str::upper($producto['descripcion']) : null,
                'diametro' => isset($producto['diametro']) ? (float) $producto['diametro'] : null,
                'longitud' => isset($producto['longitud']) ? (float) $producto['longitud'] : null,
                'calidad' => $producto['calidad'] ?? null,
                'line_items' => $lineItemsNormalizados,
            ];
        }

        $payload['productos'] = $productosNormalizados;

        return $payload;
    }

    protected function payloadsCoincide(array $a, array $b): bool
    {
        return $this->sortForComparison($a) === $this->sortForComparison($b);
    }

    protected function sortForComparison($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sortForComparison($v);
            }
            ksort($value);
        }
        return $value;
    }

    protected function payloadScore(array $payload): int
    {
        $score = 0;
        $keys = ['albaran', 'fecha', 'pedido_codigo', 'peso_total', 'bultos_total', 'tipo_compra', 'proveedor_texto'];
        foreach ($keys as $k) {
            if (!empty($payload[$k])) {
                $score += 5;
            }
        }

        $productos = $payload['productos'] ?? [];
        foreach ($productos as $producto) {
            $score += 3; // por producto detectado
            $score += !empty($producto['diametro']) ? 2 : 0;
            $score += !empty($producto['calidad']) ? 1 : 0;
            $lineItems = $producto['line_items'] ?? [];
            foreach ($lineItems as $item) {
                $score += !empty($item['colada']) ? 2 : 0;
                $score += isset($item['bultos']) ? 1 : 0;
                $score += isset($item['peso_kg']) ? 1 : 0;
            }
        }

        return $score;
    }

    /**
     * Heurísticas básicas de respaldo por si el JSON no se parsea.
     */
    protected function parseText(string $text, ?string $proveedor = null): array
    {
        $clean = preg_replace('/\s+/', ' ', $text);
        $lower = Str::lower($clean);

        $calidadCapturada = $this->firstMatch('/Calidad\\s*[:\\-]?\\s*([A-Z0-9 \\-\\.]{3,50})/i', $clean);
        if ($calidadCapturada) {
            $calidadCapturada = trim(rtrim($calidadCapturada, " -"));
        }

        $productoTipo = $this->determineProductoTipo($text);

        $data = [
            'albaran' => $this->firstMatch('/(?:N[º\\.o]?\\s*documento\\s*[:\\-]?\\s*)([\\w\\/-]+)/i', $clean),
            'fecha' => $this->parseDate($clean),
            'pedido_codigo' => $this->firstMatch('/Pedido\\s+(\\d{4}\\/[A-Z0-9]+)/i', $clean),
            'pedido_cliente' => $this->firstMatch('/Pedido\\s+cliente\\s*([A-Z0-9\\-\\/]+)/i', $clean),
            'piezas' => null,
            'bultos' => null,
            'peso_total' => $this->parsePesoTotalFromText($clean),
            'proveedor_texto' => null,
            'producto' => [
                'descripcion' => $productoTipo,
                'diametro' => $this->parseNumber($this->firstMatch('/Di[aá]metro\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'longitud' => $this->parseNumber($this->firstMatch('/L\\.\\s*Barra\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'calidad' => $calidadCapturada,
            ],
            'ubicacion_texto' => $this->firstMatch('/LUGAR DE ENTREGA\\s*([A-Z0-9 ,\\.\\-]+)/i', $clean),
            'line_items' => $this->parseLineItems($text, $proveedor),
            'tipo_compra' => 'distribuidor', // Fallback por defecto si falla OpenAI
        ];

        // Intentar cruzar con base de datos
        $data['obra'] = $this->matchObra($lower);
        $data['pedido'] = $this->matchPedido($data['pedido_codigo'] ?? null, $data['pedido_cliente'] ?? null);
        $data['producto']['producto_base_id'] = $this->matchProductoBase($data['producto']);

        return $data;
    }

    protected function parseDate(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        $patterns = [
            '/(\\d{1,2})\\/(\\d{1,2})\\/(\\d{4})/',
            '/(\\d{1,2})\\-(\\d{1,2})\\-(\\d{4})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                try {
                    return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        return null;
    }

    protected function parseNumber(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $val = trim($value);

        // Si hay más de un punto, probablemente son separadores de miles: quítalos todos.
        if (substr_count($val, '.') > 1) {
            $val = str_replace('.', '', $val);
        }

        // Si contiene coma, usarla como separador decimal.
        if (strpos($val, ',') !== false) {
            $val = str_replace('.', '', $val); // quitar miles con punto
            $val = str_replace(',', '.', $val);
        }

        if (!is_numeric($val)) {
            return null;
        }

        return (float) $val;
    }

    protected function parsePesoTotalFromText(string $clean): ?float
    {
        $patterns = [
            ['pattern' => '/Peso\\s+neto\\s+TOTAL\\s*([\\d\\.,]+)/i', 'type' => 'standard'],
            ['pattern' => '/Net\\s*KG\\s*[:\\-]?\\s*([\\d\\.,]+)/i', 'type' => 'standard'],
            ['pattern' => '/Peso\\s+Tons?\\s*[:\\-]?\\s*([\\d\\.,]+)/i', 'type' => 'tons'],
        ];

        foreach ($patterns as $entry) {
            $match = $this->firstMatch($entry['pattern'], $clean);
            if ($match === null) {
                continue;
            }

            if ($entry['type'] === 'tons') {
                $value = $this->parseNumberRemovingSeparators($match);
                if ($value !== null) {
                    return $value;
                }
                continue;
            }

            $value = $this->parseNumber($match);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    protected function parseNumberRemovingSeparators(string $value): ?float
    {
        $digits = preg_replace('/[^\\d]/', '', $value);
        return $digits === '' ? null : (float) $digits;
    }

    protected function firstMatch(string $pattern, string $text): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    protected function matchObra(string $textLower): ?array
    {
        $obra = Obra::all()->first(function ($obra) use ($textLower) {
            return Str::contains($textLower, Str::lower($obra->obra ?? ''));
        });

        if (!$obra) {
            return null;
        }

        return [
            'id' => $obra->id,
            'obra' => $obra->obra,
        ];
    }

    protected function matchPedido(?string $pedidoCodigo, ?string $pedidoCliente): ?array
    {
        $pedido = null;

        if ($pedidoCodigo) {
            $pedido = Pedido::where('codigo', 'like', '%' . $pedidoCodigo . '%')->first();
        }

        if (!$pedido && $pedidoCliente) {
            $pedido = Pedido::where('codigo', 'like', '%' . $pedidoCliente . '%')->first();
        }

        if (!$pedido) {
            return null;
        }

        // Seleccionar una línea abierta por defecto
        $linea = PedidoProducto::where('pedido_id', $pedido->id)
            ->whereNotIn('estado', ['completado', 'facturado', 'cancelado'])
            ->orderBy('fecha_estimada_entrega')
            ->first();

        return [
            'id' => $pedido->id,
            'codigo' => $pedido->codigo,
            'pedido_producto_id' => $linea?->id,
        ];
    }

    protected function matchProductoBase(array $producto): ?int
    {
        $diametro = $producto['diametro'] ?? null;
        $longitud = $producto['longitud'] ?? null;

        if (!$diametro) {
            return null;
        }

        $query = ProductoBase::query()
            ->where('diametro', (string) $diametro);

        if ($longitud) {
            $query->where('longitud', (string) $longitud);
        }

        if (!empty($producto['descripcion']) && Str::contains(Str::lower($producto['descripcion']), 'barra')) {
            $query->where('tipo', 'barra');
        }

        $coincidente = $query->first();

        return $coincidente?->id;
    }

    /**
     * Intenta extraer line items (colada, piezas/bultos, peso).
     */
    protected function parseLineItems(string $text, ?string $proveedor = null): array
    {
        if ($proveedor === 'megasa') {
            $megasaItems = $this->parseMegasaLineItems($text);
            if (!empty($megasaItems)) {
                return $megasaItems;
            }
        }

        $lines = [];

        $pattern = '/Colada[:\\s]+([\\w\\/]+).*?(\\d+)\\s+(\\d+)\\s+([\\d\\.,]+)/i';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $lines[] = [
                    'colada' => trim($m[1]),
                    'piezas' => (int) $m[2],
                    'bultos' => (int) $m[3],
                    'peso_kg' => $this->parseNumber($m[4]),
                ];
            }
        }

        return $lines;
    }

    protected function parseMegasaLineItems(string $text): array
    {
        $needles = [
            'coladas',
            'colada',
            'n.º de paquete',
            'nº de paquete',
            'n.º de paquetes',
            'nº de paquetes',
            'n.º de atado',
            'nº de atado',
        ];

        $positions = [];
        $lowerText = mb_strtolower($text);
        foreach ($needles as $needle) {
            $pos = mb_stripos($lowerText, $needle);
            if ($pos !== false) {
                $positions[] = $pos;
            }
        }

        $start = !empty($positions) ? min($positions) : 0;
        $segment = $start > 0 ? mb_substr($text, $start) : $text;
        $tokens = preg_split('/\\s+/', $segment);

        $lineItems = [];
        $currentIndex = null;

        foreach ($tokens as $token) {
            $clean = preg_replace('/[^\\d]/', '', $token);
            if ($this->looksLikeMegasaColadaToken($clean)) {
                $lineItems[] = [
                    'colada' => $clean,
                    'bultos' => 0,
                    'peso_kg' => null,
                ];
                $currentIndex = count($lineItems) - 1;
                continue;
            }

            if ($currentIndex !== null && $this->looksLikeMegasaPackageToken($clean)) {
                $lineItems[$currentIndex]['bultos']++;
            }
        }

        return array_values(array_filter($lineItems, function ($item) {
            return !empty($item['colada']) && ($item['bultos'] ?? 0) > 0;
        }));
    }

    protected function looksLikeMegasaColadaToken(string $token): bool
    {
        return $token !== '' && preg_match('/^\\d{5,7}$/', $token) === 1;
    }

    protected function looksLikeMegasaPackageToken(string $token): bool
    {
        return $token !== '' && preg_match('/^\\d{3,4}$/', $token) === 1;
    }

    protected function determineProductoTipo(?string $texto): string
    {
        $valor = Str::lower($texto ?? '');
        if (Str::contains($valor, ['encarretado', 'encarretada'])) {
            return 'ENCARRETADO';
        }
        if (Str::contains($valor, ['barra', 'liso', 'lisa'])) {
            return 'BARRA';
        }
        return 'BARRA';
    }
}
