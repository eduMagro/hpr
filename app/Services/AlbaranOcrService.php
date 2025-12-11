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
                    'max_tokens' => 1200,
                    'temperature' => 0,
                ]);

        if (!$response->successful()) {
            $msg = $response->json('error.message') ?? 'OpenAI devolvió un error';
            throw new \RuntimeException($msg);
        }

        $rawText = $response->json('choices.0.message.content') ?? '';
        $decoded = json_decode($rawText, true);

        $parsed = is_array($decoded) ? $decoded : $this->parseText($rawText, $proveedor);

        return [$rawText, $parsed];
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
            'producto' => [
                'descripcion' => $productoTipo,
                'diametro' => $this->parseNumber($this->firstMatch('/Di[aá]metro\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'longitud' => $this->parseNumber($this->firstMatch('/L\\.\\s*Barra\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'calidad' => $calidadCapturada,
            ],
            'ubicacion_texto' => $this->firstMatch('/LUGAR DE ENTREGA\\s*([A-Z0-9 ,\\.\\-]+)/i', $clean),
            'line_items' => $this->parseLineItems($text, $proveedor),
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

    protected function promptSegunProveedor(?string $proveedor): string
    {
        $basePrompt = <<<PROMPT
Estás analizando un ALBARÁN DE ENTRADA de material ferralla de un proveedor.

La imagen puede estar rotada; interpreta el texto aunque esté en vertical.

Si el usuario carga múltiples archivos adjuntos, interpreta que son páginas del mismo albarán y combina la información, no los trates como documentos independientes

Devuelve SOLO un JSON (sin Markdown ni texto extra) con esta estructura:
{
  "albaran": "",
  "tipo_compra": "",
  "fecha": "YYYY-MM-DD",
  "pedido_codigo": "",
  "pedido_cliente": null,
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
  * descripcion: Debe ser EXACTAMENTE "ENCARRETADO" o "BARRA" (usa mayúsculas) según el tipo de barra, sin inventar otras expresiones.
  * diametro: Diámetro en mm (solo número: 8, 10, 12, 16, 20, etc.)
  * longitud: Longitud en mm (si aparece)
  * calidad: Calidad del acero (ej: "B500SD", "EURASOL 500SD", etc.)
  * line_items: Array de coladas, cada una con:
    - colada: Número de colada (ej: "25/41324")
    - bultos: Número de bultos de ESA colada específica
    - peso_kg: Peso en kg de esa colada
- tipo_compra: Tipo de compra debe ser "directo" o "proveedor"

REGLAS CRÍTICAS:
- Usa números (no strings) para bultos/peso/diámetro
- Cada producto diferente (diámetro, calidad) debe tener su propia entrada
- Los bultos en line_items son el número de bultos de ESA colada, NO del total
- Si no encuentras un dato, usa null
- No devuelvas texto adicional fuera del JSON
- La descripción de cada producto debe ser exactamente ENCARRETADO o BARRA (mayúsculas).
PROMPT;

        $mapa = [
            'siderurgica' => $basePrompt . "\nNotas CRÍTICAS para Siderúrgica Sevillana (SISE):\n\n1. IDENTIFICACIÓN:\n   - El albarán es el 'N.º documento'\n   - Pedido cliente: código después de 'Pedido cliente' (NO la fecha)\n\n2. ESTRUCTURA DE PRODUCTOS:\n   - Cada sección numerada (001, 002, etc.) es un PRODUCTO DIFERENTE\n   - Cada producto tiene: descripción, diámetro, longitud (L. barra), calidad\n   - Crea una entrada en 'productos' por cada sección numerada\n   - Siempre devuelve ENCARRETADO o BARRA (may?sculas) en 'descripcion' seg?n el tipo detectado\n\n3. BULTOS - MUY IMPORTANTE:\n   - En la tabla derecha, mira la columna 'Bultos' de cada fila\n   - Cada fila de la tabla representa UNA COLADA con SU número de bultos\n   - Ejemplo: si ves '25/41324' con 'Bultos: 3', esa colada tiene 3 bultos\n   - NO cuentes el número de coladas como bultos\n   - El 'bultos_total' es la SUMA de todos los bultos de todas las coladas\n\n4. LINE_ITEMS (coladas):\n   - Cada fila de la tabla es un line_item\n   - colada: número de colada (ej: '25/41324')\n   - bultos: número de bultos de ESA fila (mira columna 'Bultos')\n   - peso_kg: peso neto de esa fila en kg (columna 'Net KG')\n\n5. PESOS:\n   - Si dice '25.120' en peso neto TOTAL, son 25120 kg (multiplica por 1000)\n   - Cada line_item tiene su peso_kg individual de la columna 'Net KG'\n\n6. VALIDACIÓN:\n   - Suma todos los bultos de line_items, debe coincidir con el resumen superior\n   - Si no coincide, revisa la columna 'Bultos' nuevamente\n\nEjemplo correcto:\nSi ves:\n  Producto 001: Descripción A, Ø12, Calidad B500\n  Tabla:\n    Colada 25/41324 | Bultos: 3 | Net KG: 9340\n    Colada 25/41612 | Bultos: 1 | Net KG: 3113\n    Colada 25/41613 | Bultos: 1 | Net KG: 3113\n\nDebes devolver:\n{\n  \"bultos_total\": 5,\n  \"productos\": [\n    {\n      \"descripcion\": \"Descripción A\",\n      \"diametro\": 12,\n      \"calidad\": \"B500\",\n      \"line_items\": [\n        {\"colada\": \"25/41324\", \"bultos\": 3, \"peso_kg\": 9340},\n        {\"colada\": \"25/41612\", \"bultos\": 1, \"peso_kg\": 3113},\n        {\"colada\": \"25/41613\", \"bultos\": 1, \"peso_kg\": 3113}\n      ]\n    }\n  ]\n} \n
            en cuanto al tipo de compra se especifica en la parte superior del documento, justo debajo de ENTREGA A: solo es directa cuando justo debajo pone HIERROS PACO REYES, si no lo pone hay que declararlo como proveedor
            a continuación te presento 3 enlaces de ejemplo de estos albaranes con su respuesta correcta:
            https://res.cloudinary.com/dkhxza94o/image/upload/v1765443351/al2_s4xmgy.jpg
            {
    'albaran': '28372',
    'tipo_compra': 'proveedor',
    'fecha': '2025-11-13',
    'pedido_codigo': 'HPC-0024944',
    'pedido_cliente': null,
    'peso_total': 25240,
    'bultos_total': 10,
    'productos': [
        {
            'descripcion': 'BARRA',
            'diametro': 16,
            'longitud': 12,
            'calidad': 'EURA 500 SD',
            'line_items': [
                {
                    'colada': '25\/93016',
                    'bultos': 4,
                    'peso_kg': 10188
                },
                {
                    'colada': '25\/92989',
                    'bultos': 6,
                    'peso_kg': 15052
                }
            ]
        }
    ],
    'proveedor': 'siderurgica'
}

https://res.cloudinary.com/dkhxza94o/image/upload/v1765443649/al3_ik3dgz.jpg
{
    'albaran': '28689',
    'tipo_compra': 'proveedor',
    'fecha': '2025-11-17',
    'pedido_codigo': '6600190973',
    'pedido_cliente': null,
    'peso_total': 25120,
    'bultos_total': 10,
    'productos': [
        {
            'descripcion': 'BARRA',
            'diametro': 12,
            'longitud': 12000,
            'calidad': 'EURA 500 SD',
            'line_items': [
                {
                    'colada': '25\/93034',
                    'bultos': 5,
                    'peso_kg': 12577
                },
                {
                    'colada': '25\/93066',
                    'bultos': 5,
                    'peso_kg': 12543
                }
            ]
        }
    ],
    'proveedor': 'siderurgica'
}

    https://res.cloudinary.com/dkhxza94o/image/upload/v1765444758/al1_pviem1.jpg
    {
    'albaran': '27944',
    'tipo_compra': 'directo',
    'fecha': '2025-11-10',
    'pedido_codigo': 'PC25\/1043',
    'pedido_cliente': 'PC25\/1043',
    'peso_total': 25320,
    'bultos_total': 10,
    'productos': [
        {
            'descripcion': 'BARRA',
            'diametro': 32,
            'longitud': 14000,
            'calidad': 'EURA 500 SD',
            'line_items': [
                {
                    'colada': '25\/41548',
                    'bultos': 10,
                    'peso_kg': 25320
                }
            ]
        }
    ],
    'proveedor': 'siderurgica'
}
            ",
            'megasa' => $basePrompt . "
Notas proveedor Megasa (MUY IMPORTANTE):

1. IDENTIFICACIÓN:
- 'albaran': el código que está arriba a la derecha en un recuadro, al lado de 'CARTA PORTE/REF' o 'CARTA PORTE/ALBARÁN NUM.' (ej: 14075SG25).
- 'pedido_codigo': el número que está en la tabla de cabecera debajo de 'Código' o similar (ej: 25/18778SG/1).
- S/Ref. (cuando aparece) va acompañado de un código tipo 'PCxx/xxxx'; ese valor está en nuestra BBDD y debe guardarse como 'pedido_cliente' para la simulación.
- Si no aparece, pon null.
- Este campo SIEMPRE debe aparecer en el JSON final.

2. PESO TOTAL:
- En la esquina inferior derecha aparece 'Peso Tons.' y un valor tipo '24,260 TM'.
- Ese valor siempre es el peso TOTAL en kg, sin puntos ni comas (24,260 TM → 24260).

3. ESTRUCTURA DE COLADAS Y BULTOS:
- En la tabla central hay dos columnas importantes:
  * 'Nº de Vazamento' (o 'Coladas'): número de colada (ej: 665252).
  * 'Nº de Atado' (o 'Nº de paquete'): códigos de 4 dígitos (ej: 0412 0413 0414...).

- CADA FILA de la tabla es una colada.
- Para cada fila:
  * 'colada' = número de 'Nº de Vazamento'.
  * 'bultos' = número de códigos de 4 dígitos que aparecen en esa MISMA línea en la columna 'Nº de Atado'.

- EJEMPLO REAL:
  Si ves algo como:
    Nº de Vazamento | Nº de Atado
    665252          | 0412 0413 0414 0415 0416
    665254          | 0402
    665238          | 0429

  Entonces debes devolver:
    line_items = [
      { \"colada\": \"665252\", \"bultos\": 5, \"peso_kg\": null },
      { \"colada\": \"665254\", \"bultos\": 1, \"peso_kg\": null },
      { \"colada\": \"665238\", \"bultos\": 1, \"peso_kg\": null }
    ]
  Y 'bultos_total' = 7.

4. PESO POR COLADA:
- Megasa NO da peso por colada, solo peso total.
- Por tanto, SIEMPRE pon \"peso_kg\": null en cada line_item de Megasa.

5. VALIDACIÓN:
- 'bultos_total' debe ser la suma de todos los 'bultos' de todos los line_items.
- Comprueba que coincide con el valor de resumen ('Nº Atados ... PQTS'). Si no coincide, revisa la tabla y corrige los bultos.

6. DESCRIPCIÓN DEL PRODUCTO:
- Si el texto contiene 'ENCARRET', pon \"descripcion\": \"ENCARRETADO\".
- Si es barra recta / estándar, pon \"descripcion\": \"BARRA\".
- Usa siempre mayúsculas y SOLO esos dos valores.

en cuanto al tipo de compra se especifica en la parte superior del documento, justo debajo de Cliente, será directa cuando diga HIERROS PACO REYES, si no lo pone hay que declararlo como proveedor

a continuación te presento 4 enlaces de ejemplo de estos albaranes con su respuesta correcta:
https://res.cloudinary.com/dkhxza94o/image/upload/v1765445776/me1_br6x9z.jpg
            {
    'albaran': '7758LG25',
    'tipo_compra': 'directo',
    'fecha': '2025-11-12',
    'pedido_codigo': '25\/17758\/2',
    'pedido_cliente': 'PC25\/0139',
    'peso_total': 28720,
    'bultos_total': 13,
    'productos': [
        {
            'descripcion': 'BARRA',
            'diametro': 12,
            'longitud': 12,
            'calidad': 'B500SD',
            'line_items': [
                {
                    'colada': '222598',
                    'bultos': 12,
                    'peso_kg': null
                },
                {
                    'colada': '222599',
                    'bultos': 1,
                    'peso_kg': null
                }
            ]
        }
    ],
    'proveedor': 'megasa'
}

https://res.cloudinary.com/dkhxza94o/image/upload/v1765445777/me2_gwix8a.jpg
{
    'albaran': '13613SG25',
    'tipo_compra': 'proveedor',
    'fecha': '2025-11-17',
    'pedido_codigo': '25\/18644SG\/1',
    'pedido_cliente': null,
    'peso_total': 24800,
    'bultos_total': 9,
    'productos': [
        {
            'descripcion': 'ENCARRETADO',
            'diametro': 20,
            'longitud': null,
            'calidad': 'B500SD',
            'line_items': [
                {
                    'colada': '663307',
                    'bultos': 9,
                    'peso_kg': null
                }
            ]
        }
    ],
    'proveedor': 'megasa'
}

https://res.cloudinary.com/dkhxza94o/image/upload/v1765445782/me3_geg146.jpg
{
    'albaran': '8486FG25',
    'tipo_compra': 'directo',
    'fecha': '2025-11-04',
    'pedido_codigo': '25\/17753\/3',
    'pedido_cliente': 'PC25\/0138',
    'peso_total': 24480,
    'bultos_total': 11,
    'productos': [
        {
            'descripcion': 'BARRA',
            'diametro': 12,
            'longitud': 12,
            'calidad': 'B500SD',
            'line_items': [
                {
                    'colada': '482346',
                    'bultos': 8,
                    'peso_kg': null
                },
                {
                    'colada': '482347',
                    'bultos': 3,
                    'peso_kg': null
                }
            ]
        }
    ],
    'proveedor': 'megasa'
}

https://res.cloudinary.com/dkhxza94o/image/upload/v1765446792/me4_l2wg8v.jpg
{
    'albaran': '8707FG25',
    'tipo_compra': 'proveedor',
    'fecha': '2025-11-07',
    'pedido_codigo': '25\/17267\/2',
    'pedido_cliente': null,
    'peso_total': 29060,
    'bultos_total': 13,
    'productos': [
        {
            'descripcion': 'BARRA',
            'diametro': 32,
            'longitud': 12,
            'calidad': 'B500SD',
            'line_items': [
                {
                    'colada': '482009',
                    'bultos': 9,
                    'peso_kg': null
                },
                {
                    'colada': '482008',
                    'bultos': 2,
                    'peso_kg': null
                },
                {
                    'colada': '482021',
                    'bultos': 2,
                    'peso_kg': null
                }
            ]
        }
    ],
    'proveedor': 'megasa'
}

            ",
            'balboa' => $basePrompt . "
Notas específicas para proveedor BALBOA (muy importante):

1. TABLA PRINCIPAL:
Solo debe usarse la tabla principal que contiene las columnas:
'(*) Descripción / Description', 'Colada Heat nr.', 'Ptes. Bundles',
y 'Cantidad / Quantity'. Cada fila de esa tabla corresponde a un único
line_item del JSON.

2. SECCIONES A IGNORAR:
Cualquier listado, tabla extendida, tabla secundaria o bloque que aparezca
debajo de la tabla principal (incluyendo secciones que comienzan por 'Prod.'
o columnas como 'Colada / Paq.') debe ignorarse completamente. Esa zona es
información interna de trazabilidad y NO debe generar line_items ni aportar
bultos ni pesos.

3. BULTOS:
El número de bultos por colada es exactamente el valor en la columna
'Ptes. Bundles'. No deben contarse filas adicionales ni paquetes listados
en otras zonas del documento.

4. PESO TOTAL:
El peso_total del albarán se toma exclusivamente del último valor visible
en la columna 'Cantidad / Quantity'. Debe convertirse a kg eliminando
formatos europeos (25.060 → 25060). No sumar otros valores.

5. PRODUCTOS:
Si la descripción contiene 'Barra', descripción = 'BARRA'.
diametro = número antes de la 'x'.
longitud = número después de la 'x', convertido a metros si es necesario.
calidad = solo 'B500SD'. No incluir normas UNE.

6. LINE_ITEMS:
Cada fila válida de la tabla principal genera un único line_item con:
- colada: valor de 'Colada Heat nr.'
- bultos: valor de 'Ptes. Bundles'
- peso_kg: null (Balboa no da peso por colada)

7. FORMATO:
Devolver JSON estricto. Si algún dato falta, devolver null.

https://res.cloudinary.com/dkhxza94o/image/upload/v1765452683/39b740ea-0de7-4243-8f3b-d505691b9248.png
Justo debajo de la cabecera de Descripción, aparecen los tipos de materiales, además vendrán tantos como numero de coladas. En la imagen se muestran 2 líneas, son barras 32 diametro y 12m calidad B500SD.

https://res.cloudinary.com/dkhxza94o/image/upload/v1765452858/bb8bea9c-ba2d-4f3a-bd62-fa6e64ab142d.png
Justo a la derecha tenemos la columna Colada Heat nr. aquí se especifica el número de colada.

https://res.cloudinary.com/dkhxza94o/image/upload/v1765452925/c28ddd46-c4d0-406c-94e4-e4c03d821255.png
y justo a la derecha tenemos el Ptes. Bundles, donde se especifican el numero de bultos por albarán, en este caso 4 y 9

Aqui unos cuantos ejemplos:
https://res.cloudinary.com/dkhxza94o/image/upload/v1765450688/bal1_qtzuy3.jpg
{
  'albaran': '255415511',
  'tipo_compra': 'proveedor',
  'fecha': '2025-11-04',
  'pedido_codigo': '255405365',
  'pedido_cliente': 'A/1071',
  'peso_total': 23760,
  'bultos_total': 10,
  'productos': [
    {
      'descripcion': 'ENCARRETADO',
      'diametro': 12,
      'longitud': null,
      'calidad': 'B500SD',
      'line_items': [
        {
          'colada': '2254596',
          'bultos': 10,
          'peso_kg': 23760
        }
      ]
    }
  ],
  'proveedor': 'balboa'
}

{
    'albaran': '255413776',
    'fecha': '2025-10-02',
    'pedido_codigo': '255404614',
    'pedido_cliente': '2\/10',
    'peso_total': 22560,
    'bultos_total': 10,
    'productos': [
        {
            'descripcion': 'ENCARRETADO',
            'diametro': 16,
            'longitud': null,
            'calidad': 'B500SD - UNE 36065:2011',
            'line_items': [
                {
                    'colada': '2253498',
                    'bultos': 2,
                    'peso_kg': 22560
                },
                {
                    'colada': '2253499',
                    'bultos': 4,
                    'peso_kg': 22560
                },
                {
                    'colada': '2253500',
                    'bultos': 4,
                    'peso_kg': 22560
                }
            ]
        }
    ],
    'proveedor': 'balboa'
}


",
        ];

        return $mapa[$proveedor] ?? $basePrompt;
    }
}
