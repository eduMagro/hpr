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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AlbaranOcrService
{
    /**
     * Procesa un albarán (imagen o PDF), ejecuta OCR vía OpenAI y persiste un log.
     */
    public function parseAndLog(UploadedFile $file, ?int $userId = null, ?string $proveedor = null): EntradaImportLog
    {
        $storedPath = $file->storeAs(
            'albaranes_entrada/ocr',
            'ocr_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension(),
            'private'
        );

        $absolute = Storage::disk('private')->path($storedPath);
        [$rawText, $parsed] = $this->extractWithOpenAi($absolute, strtolower($file->getClientOriginalExtension()), $proveedor);
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
     * Ejecuta OCR con OpenAI Vision y devuelve [rawText, parsedArray].
     */
    protected function extractWithOpenAi(string $path, string $extension, ?string $proveedor): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('Falta OPENAI_API_KEY en el .env');
        }

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
        $prompt = $this->promptSegunProveedor($proveedor);

        $model = env('OPENAI_MODEL', 'gpt-4.1-mini');

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
                    'max_tokens' => 1200,
                    'temperature' => 0,
                ]);

        if (!$response->successful()) {
            $msg = $response->json('error.message') ?? 'OpenAI devolvió un error';
            throw new \RuntimeException($msg);
        }

        $rawText = $response->json('choices.0.message.content') ?? '';
        $decoded = json_decode($rawText, true);

        $parsed = is_array($decoded) ? $decoded : $this->parseText($rawText);

        return [$rawText, $parsed];
    }

    /**
     * Heurísticas básicas de respaldo por si el JSON no se parsea.
     */
    protected function parseText(string $text): array
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
            'peso_total' => $this->parseNumber($this->firstMatch('/Peso\\s+neto\\s+TOTAL\\s*([\\d\\.,]+)/i', $clean)
                ?: $this->firstMatch('/Net\\s*KG\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
            'producto' => [
                'descripcion' => $productoTipo,
                'diametro' => $this->parseNumber($this->firstMatch('/Di[aá]metro\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'longitud' => $this->parseNumber($this->firstMatch('/L\\.\\s*Barra\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'calidad' => $calidadCapturada,
            ],
            'ubicacion_texto' => $this->firstMatch('/LUGAR DE ENTREGA\\s*([A-Z0-9 ,\\.\\-]+)/i', $clean),
            'line_items' => $this->parseLineItems($text),
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
    protected function parseLineItems(string $text): array
    {
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

    protected function determineProductoTipo(?string $texto): string
    {
        $valor = Str::lower($texto ?? '');
        if (Str::contains($valor, ['corrug', 'nervad'])) {
            return 'CORRUGADO';
        }
        if (Str::contains($valor, ['barra', 'liso', 'lisa'])) {
            return 'BARRA';
        }
        return 'CORRUGADO';
    }

    protected function promptSegunProveedor(?string $proveedor): string
    {
        $basePrompt = <<<PROMPT
Estás analizando un ALBARÁN DE ENTRADA de material ferralla de un proveedor.
La imagen puede estar rotada; interpreta el texto aunque esté en vertical.
Devuelve SOLO un JSON (sin Markdown ni texto extra) con esta estructura:
{
  "albaran": "",
  "fecha": "YYYY-MM-DD",
  "pedido_codigo": "",
  "peso_total": null,
  "bultos_total": null,
  "productos": [
    {
      "descripcion": "",
      "diametro": null,
      "longitud": null,
      "calidad": "",
      "line_items": [
        {
          "colada": "",
          "bultos": null,
          "peso_kg": null
        }
      ]
    }
  ]
}

CAMPOS IMPORTANTES:
- albaran: Número de albarán del proveedor
- fecha: Fecha del albarán (formato YYYY-MM-DD)
- pedido_codigo: Número de pedido o referencia (si aparece)
- peso_total: Peso total del albarán en kg
- bultos_total: Número total de bultos/paquetes
- productos: Array de productos, cada uno con:
  * descripcion: Debe ser EXACTAMENTE "CORRUGADO" o "BARRA" (usa mayúsculas) según el tipo de barra, sin inventar otras expresiones.
  * diametro: Diámetro en mm (solo número: 8, 10, 12, 16, 20, etc.)
  * longitud: Longitud en mm (si aparece)
  * calidad: Calidad del acero (ej: "B500SD", "EURASOL 500SD", etc.)
  * line_items: Array de coladas, cada una con:
    - colada: Número de colada (ej: "25/41324")
    - bultos: Número de bultos de ESA colada específica
    - peso_kg: Peso en kg de esa colada

REGLAS CRÍTICAS:
- Usa números (no strings) para bultos/peso/diámetro
- Cada producto diferente (diámetro, calidad) debe tener su propia entrada
- Los bultos en line_items son el número de bultos de ESA colada, NO del total
- Si no encuentras un dato, usa null
- No devuelvas texto adicional fuera del JSON
- La descripción de cada producto debe ser exactamente CORRUGADO o BARRA (mayúsculas).
PROMPT;

        $mapa = [
            'siderurgica' => $basePrompt . "\nNotas CRÍTICAS para Siderúrgica Sevillana (SISE):\n\n1. IDENTIFICACIÓN:\n   - El albarán es el 'N.º documento'\n   - Pedido cliente: código después de 'Pedido cliente' (NO la fecha)\n\n2. ESTRUCTURA DE PRODUCTOS:\n   - Cada sección numerada (001, 002, etc.) es un PRODUCTO DIFERENTE\n   - Cada producto tiene: descripción, diámetro, longitud (L. barra), calidad\n   - Crea una entrada en 'productos' por cada sección numerada\n   - Siempre devuelve CORRUGADO o BARRA (may?sculas) en 'descripcion' seg?n el tipo detectado\n\n3. BULTOS - MUY IMPORTANTE:\n   - En la tabla derecha, mira la columna 'Bultos' de cada fila\n   - Cada fila de la tabla representa UNA COLADA con SU número de bultos\n   - Ejemplo: si ves '25/41324' con 'Bultos: 3', esa colada tiene 3 bultos\n   - NO cuentes el número de coladas como bultos\n   - El 'bultos_total' es la SUMA de todos los bultos de todas las coladas\n\n4. LINE_ITEMS (coladas):\n   - Cada fila de la tabla es un line_item\n   - colada: número de colada (ej: '25/41324')\n   - bultos: número de bultos de ESA fila (mira columna 'Bultos')\n   - peso_kg: peso neto de esa fila en kg (columna 'Net KG')\n\n5. PESOS:\n   - Si dice '25.120' en peso neto TOTAL, son 25120 kg (multiplica por 1000)\n   - Cada line_item tiene su peso_kg individual de la columna 'Net KG'\n\n6. VALIDACIÓN:\n   - Suma todos los bultos de line_items, debe coincidir con el resumen superior\n   - Si no coincide, revisa la columna 'Bultos' nuevamente\n\nEjemplo correcto:\nSi ves:\n  Producto 001: Descripción A, Ø12, Calidad B500\n  Tabla:\n    Colada 25/41324 | Bultos: 3 | Net KG: 9340\n    Colada 25/41612 | Bultos: 1 | Net KG: 3113\n    Colada 25/41613 | Bultos: 1 | Net KG: 3113\n\nDebes devolver:\n{\n  \"bultos_total\": 5,\n  \"productos\": [\n    {\n      \"descripcion\": \"Descripción A\",\n      \"diametro\": 12,\n      \"calidad\": \"B500\",\n      \"line_items\": [\n        {\"colada\": \"25/41324\", \"bultos\": 3, \"peso_kg\": 9340},\n        {\"colada\": \"25/41612\", \"bultos\": 1, \"peso_kg\": 3113},\n        {\"colada\": \"25/41613\", \"bultos\": 1, \"peso_kg\": 3113}\n      ]\n    }\n  ]\n}",
            'megasa' => $basePrompt . "\nNotas proveedor Megasa:\n- Usa el codigo que se encuentra arriba a la dereha metido en un recuadro, justo a la derecha de 'CARTA PORTE/REF' o 'CARTA PORTE/ALBARÁN NUM.' como albaran. En la tabla, el número que hay debajo de 'Código' como pedido_codigo. \n- Tabla central con N.º Paquetes/Bultos/Bastones y peso bruto/neto. Total recibido/Total TM está justo debajo de la palabra 'Peso Tons.'; Aunque ponga TM son KG, por ejemplo 24,620 TM serían 24620 kg, da igual si pone '.' o ',' ignóralo y ponlo sin punto/coma.\n- IMPORTANTE: Megasa NO asigna pesos por coladas/bultos, solo un peso total. Por lo tanto, en line_items NO incluyas el campo peso_kg, déjalo como null.\n- Cada producto tiene su array de line_items con coladas, pero solo extrae colada y bultos (número de paquetes), NO peso_kg.\n- Si ves la tabla, hay una columna 'Coladas'/'Nº de Vazamento' a la izquierda y 'N.º de Paquete'/ 'Nº de Atado' a la derecha, cada grupo de paquetes pertenece a la colada de su fila.\n- La cantidad de bultos son la cantidad de 'Nº de paquete'/'Nº de Atado' de cada colada, y estan separados por un espacio ' ', sería nº codada, seguir la mismaz\n- Ignora QR/códigos de barras.",
            'balboa' => $basePrompt . "\nNotas proveedor Balboa:\n- Formato Carta de Porte/Waybill. Usa 'N.º Expedición' o 'N.º Pedido' como albaran.\n- Tabla de rollos: cada fila tiene colada y 'Cantidad neta' o similar.\n- Crea un line_item por fila con peso_kg y bultos correspondientes.\n- Si hay códigos QR o barras, ignóralos para el JSON.\n- Interpreta la imagen aunque esté rotada.",
        ];

        return $mapa[$proveedor] ?? $basePrompt;
    }
}
