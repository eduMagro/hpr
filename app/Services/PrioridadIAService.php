<?php

namespace App\Services;

use App\Models\IAAprendizajePrioridad;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrioridadIAService
{
    /**
     * Re-prioriza los pedidos candidatos usando IA, teniendo en cuenta el aprendizaje previo.
     */
    public function recomendarPrioridades(array $datosOCR, array $candidatos): array
    {
        $memoria = $this->obtenerLeccionesAprendidas($datosOCR);
        $prompt = $this->construirPrompt($datosOCR, $candidatos, $memoria);

        try {
            $response = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un experto jefe de almacén y logística en una fábrica de ferralla. Tu tarea es analizar un albarán escaneado y decidir qué pedido de compra pendiente es el que se está recibiendo. Debes devolver una lista de los IDs de los pedidos ordenados por probabilidad (el 1 es el recomendado).'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0,
                ]);

            if (!$response->successful()) {
                Log::error('Error en PrioridadIAService: ' . $response->body());
                return $candidatos;
            }

            $resultado = $response->json();
            $rankingIds = $resultado['ranking_ids'] ?? [];
            $razonamiento = $resultado['razonamiento'] ?? '';

            // Reordenar candidatos según el ranking de la IA
            return $this->aplicarRanking($candidatos, $rankingIds, $razonamiento);

        } catch (\Exception $e) {
            Log::error('Excepción en PrioridadIAService: ' . $e->getMessage());
            return $candidatos;
        }
    }

    /**
     * Obtiene discrepancias pasadas del usuario para este proveedor o productos similares.
     */
    protected function obtenerLeccionesAprendidas(array $datosOCR): string
    {
        $proveedor = $datosOCR['proveedor_texto'] ?? '';

        $historial = IAAprendizajePrioridad::where('es_discrepancia', true)
            ->where(function ($q) use ($proveedor) {
                if ($proveedor) {
                    $q->where('payload_ocr->proveedor_texto', 'LIKE', "%$proveedor%");
                }
            })
            ->latest()
            ->limit(5)
            ->get();

        if ($historial->isEmpty()) {
            return "No hay lecciones aprendidas previas para este contexto.";
        }

        $textoMemoria = "Lecciones aprendidas de decisiones manuales anteriores del usuario:\n";
        foreach ($historial as $h) {
            $textoMemoria .= "- El usuario RECHAZÓ la recomendación de la IA y eligió el pedido ID: {$h->pedido_seleccionado_id}. Motivo: {$h->motivo_usuario}\n";
        }

        return $textoMemoria;
    }

    protected function construirPrompt(array $datosOCR, array $candidatos, string $memoria): string
    {
        $candidatosSimplificados = array_map(function ($c) {
            return [
                'id' => $c['id'],
                'pedido_codigo' => $c['pedido_codigo'],
                'fabricante' => $c['fabricante'],
                'distribuidor' => $c['distribuidor'],
                'obra' => $c['obra'],
                'producto' => $c['producto'],
                'diametro' => $c['diametro'],
                'cantidad_pendiente' => $c['cantidad_pendiente'],
                'fecha_creacion' => $c['fecha_creacion'],
                'coincide_codigo' => $c['coincide_codigo'] ?? false,
                'score_heuristico' => $c['score'],
            ];
        }, $candidatos);

        return json_encode([
            'instruccion' => 'Analiza el albarán y los pedidos candidatos. Devuelve un JSON con la clave "ranking_ids" (array de IDs ordenados por prioridad) y "razonamiento" (breve explicación). IMPORTANTE: Si un pedido candidato tiene "coincide_codigo": true, es casi seguro que es el correcto y debe ir el primero. PRIORIDAD SELECCIÓN: Si el albarán indica "tipo_compra": "directo", el pedido recomendado DEBE ser directamente del fabricante (sin distribuidor). Si indica "distribuidor", el pedido DEBE coincidir con el distribuidor indicado.',
            'albaran_escaneado' => [
                'proveedor_texto' => $datosOCR['proveedor_texto'] ?? null,
                'tipo_compra' => $datosOCR['tipo_compra'] ?? 'directo',
                'distribuidor_recomendado' => $datosOCR['distribuidor_recomendado'] ?? null,
                'pedido_cliente' => $datosOCR['pedido_cliente'] ?? null,
                'productos' => $datosOCR['productos'] ?? [],
            ],
            'pedidos_candidatos' => $candidatosSimplificados,
            'contexto_historico' => $memoria
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function aplicarRanking(array $candidatos, array $rankingIds, string $razonamiento): array
    {
        $candidatosById = collect($candidatos)->keyBy('id');
        $reordenados = [];

        foreach ($rankingIds as $id) {
            if (isset($candidatosById[$id])) {
                $candidato = $candidatosById[$id];
                $candidato['ia_razonamiento'] = $razonamiento;
                $reordenados[] = $candidato;
                $candidatosById->forget($id);
            }
        }

        // Añadir los que la IA no incluyó al final
        foreach ($candidatosById as $resto) {
            $reordenados[] = $resto;
        }

        return $reordenados;
    }
}
