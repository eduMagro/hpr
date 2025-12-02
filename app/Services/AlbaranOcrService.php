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
            'user_id'         => $userId,
            'file_path'       => $storedPath,
            'raw_text'        => $rawText,
            'parsed_payload'  => $parsed,
            'status'          => 'parsed',
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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
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

        $data = [
            'albaran'       => $this->firstMatch('/(?:N[º\\.o]?\\s*documento\\s*[:\\-]?\\s*)([\\w\\/-]+)/i', $clean),
            'fecha'         => $this->parseDate($clean),
            'pedido_codigo' => $this->firstMatch('/Pedido\\s+(\\d{4}\\/[A-Z0-9]+)/i', $clean),
            'pedido_cliente'=> $this->firstMatch('/Pedido\\s+cliente\\s*([A-Z0-9\\-\\/]+)/i', $clean),
            'piezas'        => null,
            'bultos'        => null,
            'peso_total'    => $this->parseNumber($this->firstMatch('/Peso\\s+neto\\s+TOTAL\\s*([\\d\\.,]+)/i', $clean)
                ?: $this->firstMatch('/Net\\s*KG\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
            'producto' => [
                'descripcion' => $this->firstMatch('/(REDONDO[^\\n]+?CALIDAD[^\\n]+)/i', $text) ?? $this->firstMatch('/(CORRUGADO[^\\n]+)/i', $text),
                'diametro'    => $this->parseNumber($this->firstMatch('/Di[aá]metro\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'longitud'    => $this->parseNumber($this->firstMatch('/L\\.\\s*Barra\\s*[:\\-]?\\s*([\\d\\.,]+)/i', $clean)),
                'calidad'     => $calidadCapturada,
            ],
            'ubicacion_texto' => $this->firstMatch('/LUGAR DE ENTREGA\\s*([A-Z0-9 ,\\.\\-]+)/i', $clean),
            'line_items'      => $this->parseLineItems($text),
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
            'id'   => $obra->id,
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
            'id'                 => $pedido->id,
            'codigo'             => $pedido->codigo,
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
                    'colada'  => trim($m[1]),
                    'piezas'  => (int) $m[2],
                    'bultos'  => (int) $m[3],
                    'peso_kg' => $this->parseNumber($m[4]),
                ];
            }
        }

        return $lines;
    }

    protected function promptSegunProveedor(?string $proveedor): string
    {
        $basePrompt = <<<PROMPT
Devuelve SOLO un JSON (sin Markdown ni texto extra) con esta estructura:
{
  "proveedor": "",
  "albaran": "",
  "fecha": "YYYY-MM-DD",
  "pedido_codigo": "",
  "pedido_cliente": "",
  "peso_total": null,
  "piezas": null,
  "bultos": null,
  "producto": {
    "descripcion": "",
    "diametro": null,
    "longitud": null,
    "calidad": ""
  },
  "ubicacion_texto": "",
  "line_items": [
    {
      "colada": "",
      "piezas": null,
      "bultos": null,
      "peso_kg": null
    }
  ]
}
Reglas generales:
- Usa números (no strings) para piezas/bultos/peso.
- Si hay varias filas en la tabla, devuelve cada una en line_items con su colada y peso.
- Si no encuentras un dato, usa null.
- No devuelvas texto adicional fuera del JSON.
PROMPT;

        $mapa = [
            'siderurgica' => $basePrompt . "\nNotas proveedor Siderúrgica Sevillana (SISE):\n- El albarán se llama 'N.º documento'.\n- Pedido cliente: es el código que sigue al texto 'Pedido cliente', NO la fecha siguiente.\n- Las coladas están listadas en la descripción; la tabla a la derecha muestra Piezas/Bultos/Net KG por fila, pero normalmente solo se usa Bultos. Deja piezas en null y usa Bultos como cantidad de bultos por colada.\n- Crea un line_items por cada colada y usa el peso de la tabla Net KG por fila.\n- Si ves un peso con formato '10.188', interpreta que son decimales. Convierte a kg multiplicando por 1000 si el contexto es toneladas (p. ej., Peso neto TOTAL 25.120 -> 25120 kg). Si faltan decimales tras un punto, rellena con ceros a la derecha.\n- El peso total está en la línea 'Peso neto TOTAL': a la derecha suelen aparecer bultos totales y peso total. Usa ese peso_total (en kg) y bultos_totales, ignorando 'Viaje', 'Peso bruto', 'Tara'.\n- Diametro y Longitud pueden tener decimales (.,) y están en la descripción: \"Diámetro\", \"L. barra\"; no los escales a enteros.\n- La calidad está en la línea \"Calidad: ...\" justo encima de las coladas; úsala en producto.calidad.\n- Deja piezas/bultos totales en null si solo se dan por línea.",
            'megasa' => $basePrompt . "\nNotas proveedor Megasa:\n- La imagen puede estar rotada; interpreta el texto aunque esté en vertical.\n- Usa 'Código de albarán' como albaran. 'Código de pedido' como pedido_codigo. Fecha de descarga como fecha.\n- Tabla central con N.º Paquetes/Bultos/Bastones y peso bruto/neto. Total recibido/Total TM está abajo; convierte TM a kg multiplicando por 1000 y colócalo en peso_total.\n- Si ves la tabla con columna 'Coladas' a la izquierda y 'N.º de Paquete' a la derecha (sin líneas), cada grupo de paquetes pertenece a la colada de su fila (ej: 22598 -> [0225..0236], 222599 -> [0211]). Devuelve en line_items cada colada con array `paquetes` y `bultos` igual al número de paquetes. Deja peso_kg y piezas en null si no hay detalle por colada.\n- Si no hay coladas y solo un peso total en TM, crea un único line_item con peso_kg=peso_total y paquetes null.\n- Ignora QR/códigos de barras.",
            'balboa' => $basePrompt . "\nNotas proveedor Balboa:\n- Formato Carta de Porte/Waybill. Usa 'N.º Expedición' o 'N.º Pedido' como albaran.\n- Tabla de rollos: cada fila tiene colada y 'Cantidad neta' o similar. Crea un line_item por fila con peso_kg.\n- Si hay códigos QR o barras, ignóralos para el JSON.\n- Interpreta la imagen aunque esté rotada.",
        ];

        return $mapa[$proveedor] ?? $basePrompt;
    }
}
