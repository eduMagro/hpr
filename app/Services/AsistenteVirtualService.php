<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoProducto;
use App\Models\ProductoBase;
use App\Models\Planilla;
use App\Models\Entrada;
use App\Models\Salida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Asistente Virtual
 * 
 * Procesa preguntas del usuario, busca contexto relevante en la BD
 * y usa Claude API para generar respuestas inteligentes
 */
class AsistenteVirtualService
{
    /**
     * Responder a una pregunta del usuario
     * 
     * @param string $pregunta
     * @param int $userId
     * @return array
     */
    public function responder(string $pregunta, int $userId): array
    {
        $inicio = microtime(true);

        try {
            // 1. IDENTIFICAR TIPO DE CONSULTA
            $tipo = $this->identificarTipoConsulta($pregunta);

            // 2. BUSCAR CONTEXTO RELEVANTE
            $contexto = $this->buscarContexto($pregunta, $tipo);

            // 3. LLAMAR A CLAUDE API
            $respuesta = $this->llamarClaude($pregunta, $contexto);

            // 4. CALCULAR TIEMPO
            $duracion = round(microtime(true) - $inicio, 2);

            // 5. GUARDAR LOG (opcional)
            $this->guardarLog([
                'user_id' => $userId,
                'pregunta' => $pregunta,
                'respuesta' => $respuesta['texto'],
                'tipo_consulta' => $tipo,
                'coste' => $respuesta['coste'] ?? 0,
                'duracion_segundos' => $duracion,
            ]);

            return [
                'respuesta' => $respuesta['texto'],
                'tipo' => $tipo,
                'duracion' => $duracion,
                'coste' => $respuesta['coste'] ?? 0,
                'tokens_input' => $respuesta['tokens_input'] ?? 0,
                'tokens_output' => $respuesta['tokens_output'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error en AsistenteVirtualService: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Identificar el tipo de consulta según palabras clave
     */
    private function identificarTipoConsulta(string $pregunta): string
    {
        $pregunta = strtolower($pregunta);

        if (preg_match('/\b(pedido|pc\d+|orden)\b/i', $pregunta)) {
            return 'pedidos';
        }
        if (preg_match('/\b(stock|material|disponible|inventario|ø\d+)\b/i', $pregunta)) {
            return 'stock';
        }
        if (preg_match('/\b(planilla|pl\d+|produccion|fabricar)\b/i', $pregunta)) {
            return 'planillas';
        }
        if (preg_match('/\b(entrada|recibido|llegado|albaran)\b/i', $pregunta)) {
            return 'entradas';
        }
        if (preg_match('/\b(salida|entrega|enviado|movimiento)\b/i', $pregunta)) {
            return 'salidas';
        }

        return 'general';
    }

    /**
     * Buscar contexto relevante en la base de datos
     */
    private function buscarContexto(string $pregunta, string $tipo): array
    {
        $contexto = [
            'tipo' => $tipo,
            'texto' => ''
        ];

        try {
            switch ($tipo) {
                case 'pedidos':
                    $contexto['texto'] = $this->buscarPedidos($pregunta);
                    break;

                case 'stock':
                    $contexto['texto'] = $this->buscarStock($pregunta);
                    break;

                case 'planillas':
                    $contexto['texto'] = $this->buscarPlanillas($pregunta);
                    break;

                case 'entradas':
                    $contexto['texto'] = $this->buscarEntradas($pregunta);
                    break;

                case 'salidas':
                    $contexto['texto'] = $this->buscarSalidas($pregunta);
                    break;

                default:
                    $contexto['texto'] = $this->buscarGeneral();
            }
        } catch (\Exception $e) {
            Log::warning('Error buscando contexto: ' . $e->getMessage());
            $contexto['texto'] = 'No se pudo obtener información del sistema en este momento.';
        }

        return $contexto;
    }

    /**
     * Buscar información de pedidos
     */
    private function buscarPedidos(string $pregunta): string
    {
        // Buscar código específico (PC25/0001)
        if (preg_match('/PC\d+\/\d+/i', $pregunta, $matches)) {
            $codigo = $matches[0];
            $pedido = Pedido::where('codigo', 'LIKE', "%{$codigo}%")
                ->with(['cliente', 'pedidoProductos.productoBase'])
                ->first();

            if ($pedido) {
                return $this->formatearPedido($pedido);
            }
        }

        // Buscar pedidos recientes o pendientes
        $pedidos = Pedido::with(['cliente', 'pedidoProductos'])
            ->where('estado', '!=', 'completado')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        if ($pedidos->isEmpty()) {
            return "No hay pedidos pendientes en este momento.";
        }

        $info = "PEDIDOS PENDIENTES:\n\n";
        foreach ($pedidos as $p) {
            $clienteNombre = $p->cliente->nombre ?? 'N/A';
            $info .= "- {$p->codigo} | Cliente: {$clienteNombre} | Estado: {$p->estado}\n";
            $info .= "  Líneas: {$p->pedidoProductos->count()} | Creado: {$p->created_at->format('d/m/Y')}\n\n";
        }

        return $info;
    }

    /**
     * Formatear información de un pedido
     */
    private function formatearPedido($pedido): string
    {
        $clienteNombre = $pedido->cliente->nombre ?? 'N/A';
        $info = "PEDIDO {$pedido->codigo}\n\n";
        $info .= "Cliente: {$clienteNombre}\n";
        $info .= "Estado: {$pedido->estado}\n";
        $info .= "Fecha: {$pedido->created_at->format('d/m/Y')}\n\n";

        $info .= "LÍNEAS DEL PEDIDO:\n";
        foreach ($pedido->pedidoProductos as $linea) {
            $producto = $linea->productoBase->nombre ?? 'Producto sin nombre';
            $info .= "- {$producto} | Cantidad: {$linea->cantidad} | Estado: {$linea->estado}\n";
        }

        return $info;
    }

    /**
     * Buscar información de stock
     */
    private function buscarStock(string $pregunta): string
    {
        // Buscar diámetro específico (Ø12, 16mm, etc)
        if (preg_match('/ø?(\d+)\s*mm?/i', $pregunta, $matches)) {
            $diametro = $matches[1];

            $productos = ProductoBase::where('nombre', 'LIKE', "%{$diametro}%")
                ->orWhere('nombre', 'LIKE', "%Ø{$diametro}%")
                ->limit(5)
                ->get();

            if ($productos->isEmpty()) {
                return "No se encontraron productos con diámetro Ø{$diametro}mm";
            }

            $info = "STOCK DISPONIBLE - Ø{$diametro}mm:\n\n";
            foreach ($productos as $prod) {
                $info .= "- {$prod->nombre} | Stock: {$prod->stock_actual}\n";
            }

            return $info;
        }

        // Stock general
        $productos = ProductoBase::whereNotNull('stock_actual')
            ->where('stock_actual', '>', 0)
            ->orderBy('stock_actual', 'DESC')
            ->limit(10)
            ->get();

        if ($productos->isEmpty()) {
            return "No hay información de stock disponible.";
        }

        $info = "PRODUCTOS CON STOCK:\n\n";
        foreach ($productos as $prod) {
            $info .= "- {$prod->nombre} | Stock: {$prod->stock_actual}\n";
        }

        return $info;
    }

    /**
     * Buscar información de planillas
     */
    private function buscarPlanillas(string $pregunta): string
    {
        $planillas = Planilla::with(['pedido', 'elementos'])
            ->where('estado', '!=', 'completado')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        if ($planillas->isEmpty()) {
            return "No hay planillas pendientes.";
        }

        $info = "PLANILLAS PENDIENTES:\n\n";
        foreach ($planillas as $pl) {
            $pedidoCodigo = $pl->pedido->codigo ?? 'N/A';
            $info .= "- {$pl->codigo} | Pedido: {$pedidoCodigo}\n";
            $info .= "  Estado: {$pl->estado} | Elementos: {$pl->elementos->count()}\n\n";
        }

        return $info;
    }

    /**
     * Buscar información de entradas
     */
    private function buscarEntradas(string $pregunta): string
    {
        $entradas = Entrada::with(['pedido', 'distribuidor'])
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        if ($entradas->isEmpty()) {
            return "No hay entradas registradas recientemente.";
        }

        $info = "ENTRADAS RECIENTES:\n\n";
        foreach ($entradas as $entrada) {
            $pedidoCodigo = $entrada->pedido->codigo ?? 'N/A';
            $distribuidorNombre = $entrada->distribuidor->nombre ?? 'N/A';
            $info .= "- Pedido: {$pedidoCodigo}\n";
            $info .= "  Distribuidor: {$distribuidorNombre}\n";
            $info .= "  Fecha: {$entrada->created_at->format('d/m/Y')}\n\n";
        }

        return $info;
    }

    /**
     * Buscar información de salidas
     */
    private function buscarSalidas(string $pregunta): string
    {
        $salidas = Salida::with(['cliente', 'camion'])
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        if ($salidas->isEmpty()) {
            return "No hay salidas registradas recientemente.";
        }

        $info = "SALIDAS RECIENTES:\n\n";
        foreach ($salidas as $salida) {
            $clienteNombre = $salida->cliente->nombre ?? 'N/A';
            $camionMatricula = $salida->camion->matricula ?? 'N/A';
            $info .= "- Cliente: {$clienteNombre}\n";
            $info .= "  Camión: {$camionMatricula}\n";
            $info .= "  Fecha: {$salida->created_at->format('d/m/Y')}\n\n";
        }

        return $info;
    }

    /**
     * Búsqueda general
     */
    private function buscarGeneral(): string
    {
        $stats = [
            'pedidos_pendientes' => Pedido::where('estado', '!=', 'completado')->count(),
            'planillas_activas' => Planilla::where('estado', '!=', 'completado')->count(),
            'productos_stock' => ProductoBase::where('stock_actual', '>', 0)->count(),
        ];

        $info = "RESUMEN DEL SISTEMA:\n\n";
        $info .= "- Pedidos pendientes: {$stats['pedidos_pendientes']}\n";
        $info .= "- Planillas activas: {$stats['planillas_activas']}\n";
        $info .= "- Productos en stock: {$stats['productos_stock']}\n";

        return $info;
    }

    /**
     * Llamar a Claude API
     */
    private function llamarClaude(string $pregunta, array $contexto): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (!$apiKey) {
            throw new \Exception("API Key de Anthropic no configurada en .env");
        }

        $prompt = $this->construirPrompt($pregunta, $contexto['texto']);

        // Contar tokens aproximadamente (1 token ≈ 4 caracteres)
        $inputTokens = strlen($prompt) / 4;

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 1000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception("Error en API de Claude: " . $response->body());
        }

        $data = $response->json();
        $respuesta = $data['content'][0]['text'] ?? 'No pude generar una respuesta';

        $outputTokens = strlen($respuesta) / 4;

        // Calcular coste (precios de Claude Sonnet 4)
        $costeInput = ($inputTokens / 1000000) * 3;    // $3 por millón tokens
        $costeOutput = ($outputTokens / 1000000) * 15; // $15 por millón tokens
        $costeTotal = $costeInput + $costeOutput;

        return [
            'texto' => $respuesta,
            'coste' => round($costeTotal, 6),
            'tokens_input' => round($inputTokens),
            'tokens_output' => round($outputTokens)
        ];
    }

    /**
     * Construir prompt para Claude
     */
    private function construirPrompt(string $pregunta, string $contexto): string
    {
        return <<<PROMPT
Eres un asistente virtual inteligente de una empresa de fabricación y gestión de productos de acero.

Tu función es ayudar a los empleados a encontrar información sobre:
- Pedidos de clientes y su estado
- Stock de materiales (productos base)
- Planillas de producción
- Entradas de mercancía
- Salidas y entregas

CONTEXTO RELEVANTE DEL SISTEMA:
{$contexto}

PREGUNTA DEL EMPLEADO:
{$pregunta}

INSTRUCCIONES:
1. Responde de forma clara, concisa y profesional en español
2. Si la información está en el contexto, úsala directamente
3. Si NO está en el contexto, di "No tengo esa información disponible en este momento"
4. Si hay múltiples resultados, resume solo los más relevantes (máximo 5)
5. Usa formato claro con bullets o listas cuando sea apropiado
6. Si detectas un código de pedido (PC25/0001), planilla (PL0567) o similar, enfócate en ese específico
7. Sé amable y profesional, pero directo
8. Si la pregunta no está clara, pide aclaraciones

IMPORTANTE: NO inventes datos. Solo usa información del contexto proporcionado.

RESPUESTA:
PROMPT;
    }

    /**
     * Guardar log de consulta (opcional)
     */
    private function guardarLog(array $datos): void
    {
        try {
            // Solo si existe la tabla asistente_logs
            if (DB::getSchemaBuilder()->hasTable('asistente_logs')) {
                DB::table('asistente_logs')->insert([
                    'user_id' => $datos['user_id'],
                    'pregunta' => $datos['pregunta'],
                    'respuesta' => $datos['respuesta'],
                    'tipo_consulta' => $datos['tipo_consulta'],
                    'coste' => $datos['coste'],
                    'duracion_segundos' => $datos['duracion_segundos'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo guardar log de asistente: ' . $e->getMessage());
        }
    }
}
