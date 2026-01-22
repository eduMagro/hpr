<?php

namespace App\Services;

use App\Models\DocumentoAyuda;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AyudaRAGService
{
    // Configuración
    private const EMBEDDING_MODEL = 'text-embedding-3-small';  // Más barato y suficiente
    private const LLM_MODEL = 'gpt-4o-mini';  // Bajo costo, buena calidad
    private const MAX_DOCUMENTOS = 4;
    private const MIN_SIMILITUD = 0.35;
    private const CACHE_EMBEDDING_TTL = 3600;  // 1 hora cache de embeddings

    /**
     * Procesa una pregunta del usuario y retorna la respuesta
     * Funciona con o sin API de OpenAI (fallback a keywords)
     */
    public function procesarPregunta(string $pregunta): array
    {
        $startTime = microtime(true);

        try {
            // Intentar búsqueda con embeddings primero
            $documentosRelevantes = collect();
            $metodo = 'keywords';

            try {
                // 1. Generar embedding de la pregunta
                $queryEmbedding = $this->generarEmbedding($pregunta);

                // 2. Buscar documentos relevantes por similitud
                $documentosRelevantes = DocumentoAyuda::buscarSimilares(
                    $queryEmbedding,
                    self::MAX_DOCUMENTOS,
                    self::MIN_SIMILITUD
                );

                if ($documentosRelevantes->isNotEmpty()) {
                    $metodo = 'rag';
                }
            } catch (\Exception $e) {
                // API no disponible - usar fallback
                Log::info('OpenAI no disponible, usando fallback: ' . $e->getMessage());
            }

            // 3. Si no hay resultados por embedding, buscar por keywords
            if ($documentosRelevantes->isEmpty()) {
                $documentosRelevantes = DocumentoAyuda::buscarPorKeywords($pregunta, self::MAX_DOCUMENTOS);
                $metodo = 'keywords';
            }

            // 4. Si aún no hay documentos, respuesta genérica
            if ($documentosRelevantes->isEmpty()) {
                return $this->respuestaFallback($pregunta);
            }

            // 5. Intentar generar respuesta con LLM, si no, mostrar docs directamente
            try {
                $respuesta = $this->generarRespuesta($pregunta, $documentosRelevantes);
            } catch (\Exception $e) {
                // LLM no disponible - mostrar documentos directamente
                Log::info('LLM no disponible, mostrando docs: ' . $e->getMessage());
                return $this->respuestaDirectaDocumentos($pregunta, $documentosRelevantes);
            }

            $duracion = round(microtime(true) - $startTime, 2);

            // Loggear la pregunta
            $this->logPregunta($pregunta, $metodo, $documentosRelevantes->pluck('titulo')->toArray(), $duracion);

            return [
                'success' => true,
                'respuesta' => $respuesta,
                'documentos_usados' => $documentosRelevantes->pluck('titulo')->toArray(),
                'metodo' => $metodo,
                'duracion' => $duracion,
            ];

        } catch (\Exception $e) {
            Log::error('Error en AyudaRAGService: ' . $e->getMessage());

            // Loggear el error
            $this->logPregunta($pregunta, 'error', [], null, $e->getMessage());

            // Último fallback: búsqueda simple por keywords
            return $this->respuestaFallbackKeywords($pregunta);
        }
    }

    /**
     * Muestra los documentos directamente sin procesar con LLM
     */
    private function respuestaDirectaDocumentos(string $pregunta, $documentos): array
    {
        $respuesta = "**Encontré esta información que puede ayudarte:**\n\n";

        foreach ($documentos as $doc) {
            $respuesta .= "### {$doc->titulo}\n";
            $respuesta .= $doc->contenido . "\n\n---\n\n";
        }

        // Loggear respuesta directa (sin LLM)
        $this->logPregunta($pregunta, 'directo_sin_llm', $documentos->pluck('titulo')->toArray());

        return [
            'success' => true,
            'respuesta' => $respuesta,
            'documentos_usados' => $documentos->pluck('titulo')->toArray(),
            'metodo' => 'directo',
        ];
    }

    /**
     * Genera el embedding de un texto usando OpenAI
     */
    public function generarEmbedding(string $texto): array
    {
        // Cache para evitar regenerar embeddings de preguntas repetidas
        $cacheKey = 'embedding_' . md5($texto);

        return Cache::remember($cacheKey, self::CACHE_EMBEDDING_TTL, function () use ($texto) {
            $response = OpenAI::embeddings()->create([
                'model' => self::EMBEDDING_MODEL,
                'input' => $texto,
            ]);

            return $response->embeddings[0]->embedding;
        });
    }

    /**
     * Busca documentos relevantes combinando búsqueda vectorial y fulltext
     */
    private function buscarDocumentosRelevantes(array $queryEmbedding, string $pregunta): Collection
    {
        // Búsqueda vectorial (principal)
        $documentosVector = DocumentoAyuda::buscarSimilares(
            $queryEmbedding,
            self::MAX_DOCUMENTOS,
            self::MIN_SIMILITUD
        );

        // Si tenemos buenos resultados vectoriales, usarlos
        if ($documentosVector->isNotEmpty()) {
            return $documentosVector;
        }

        // Fallback: búsqueda por keywords
        return DocumentoAyuda::buscarPorKeywords($pregunta, self::MAX_DOCUMENTOS);
    }

    /**
     * Genera la respuesta usando el LLM con el contexto de los documentos
     */
    private function generarRespuesta(string $pregunta, Collection $documentos): string
    {
        // Construir el contexto con los documentos relevantes
        $contexto = $documentos->map(function ($doc) {
            $similitud = isset($doc->similitud) ? " (relevancia: " . round($doc->similitud * 100) . "%)" : "";
            return "### {$doc->titulo}{$similitud}\n{$doc->contenido}";
        })->join("\n\n---\n\n");

        $systemPrompt = <<<PROMPT
Eres FERRALLIN, el asistente de ayuda del sistema ERP Manager. Tu trabajo es ayudar a los usuarios a entender cómo usar el sistema.

REGLAS:
1. SOLO responde basándote en la DOCUMENTACIÓN proporcionada abajo
2. Si la documentación no cubre la pregunta, di honestamente que no tienes esa información
3. Responde en español con formato Markdown
4. Usa pasos numerados para instrucciones
5. Sé conciso pero completo
6. Usa emojis para hacer la respuesta más visual (📍, ✅, ⚠️, 💡)
7. NUNCA menciones bases de datos, SQL, código o aspectos técnicos internos
8. Si hay múltiples formas de hacer algo, menciona la más común primero

DOCUMENTACIÓN DISPONIBLE:
{$contexto}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::LLM_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $pregunta],
            ],
            'temperature' => 0.3,
            'max_tokens' => 800,
        ]);

        return $response->choices[0]->message->content
            ?? 'Lo siento, no pude generar una respuesta. Intenta reformular tu pregunta.';
    }

    /**
     * Respuesta cuando no hay documentos relevantes
     */
    private function respuestaFallback(string $pregunta): array
    {
        $categorias = DocumentoAyuda::categorias();

        $mensaje = "**No encontré información específica sobre tu pregunta.**\n\n";
        $mensaje .= "Puedo ayudarte con estos temas:\n\n";

        foreach ($categorias as $cat) {
            $emoji = $this->emojiCategoria($cat);
            $mensaje .= "• {$emoji} **" . ucfirst($cat) . "**\n";
        }

        $mensaje .= "\n💡 Intenta reformular tu pregunta o pregunta algo más específico como:\n";
        $mensaje .= "- \"¿Cómo ficho entrada?\"\n";
        $mensaje .= "- \"¿Cómo solicito vacaciones?\"\n";
        $mensaje .= "- \"¿Cómo recepciono un pedido?\"";

        // Loggear pregunta sin resultados
        $this->logPregunta($pregunta, 'sin_resultados', []);

        return [
            'success' => true,
            'respuesta' => $mensaje,
            'documentos_usados' => [],
            'metodo' => 'fallback_sin_docs',
        ];
    }

    /**
     * Fallback usando solo keywords (cuando falla OpenAI)
     */
    private function respuestaFallbackKeywords(string $pregunta): array
    {
        $documentos = DocumentoAyuda::buscarPorKeywords($pregunta, 3);

        if ($documentos->isEmpty()) {
            return $this->respuestaFallback($pregunta);
        }

        // Construir respuesta simple sin LLM
        $respuesta = "📚 **Encontré esta información que puede ayudarte:**\n\n";

        foreach ($documentos as $doc) {
            $respuesta .= "### {$doc->titulo}\n";
            $respuesta .= $doc->contenido . "\n\n";
        }

        // Loggear búsqueda por keywords
        $this->logPregunta($pregunta, 'fallback_keywords', $documentos->pluck('titulo')->toArray());

        return [
            'success' => true,
            'respuesta' => $respuesta,
            'documentos_usados' => $documentos->pluck('titulo')->toArray(),
            'metodo' => 'fallback_keywords',
        ];
    }

    /**
     * Emoji por categoría
     */
    private function emojiCategoria(string $categoria): string
    {
        return match (strtolower($categoria)) {
            'fichajes' => '📍',
            'vacaciones' => '🏖️',
            'nominas', 'nóminas' => '💰',
            'pedidos' => '📦',
            'planillas' => '📋',
            'produccion', 'producción' => '⚙️',
            'salidas' => '🚚',
            'stock' => '📊',
            'usuarios' => '👤',
            'contraseñas', 'contrasenas' => '🔐',
            default => '📄',
        };
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTODOS PARA GESTIÓN DE DOCUMENTOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Crea un nuevo documento (sin embedding - se genera después manualmente)
     */
    public function crearDocumento(array $datos, bool $generarEmbedding = false): DocumentoAyuda
    {
        // Solo generar embedding si se solicita explícitamente
        if ($generarEmbedding) {
            try {
                $textoParaEmbedding = $datos['titulo'] . ' ' . $datos['contenido'];
                $datos['embedding'] = $this->generarEmbedding($textoParaEmbedding);
            } catch (\Exception $e) {
                Log::warning('No se pudo generar embedding: ' . $e->getMessage());
                // Continúa sin embedding - usará fallback de keywords
            }
        }

        return DocumentoAyuda::create($datos);
    }

    /**
     * Actualiza un documento (NO regenera embedding automáticamente)
     */
    public function actualizarDocumento(DocumentoAyuda $documento, array $datos): DocumentoAyuda
    {
        // Ya NO regeneramos embedding automáticamente
        // El usuario puede hacerlo manualmente desde el botón "Regenerar"
        $documento->update($datos);

        return $documento->fresh();
    }

    /**
     * Regenera todos los embeddings (útil si cambias de modelo)
     */
    public function regenerarTodosLosEmbeddings(): int
    {
        $documentos = DocumentoAyuda::all();
        $count = 0;

        foreach ($documentos as $doc) {
            try {
                $texto = $doc->titulo . ' ' . $doc->contenido;
                $doc->embedding = $this->generarEmbedding($texto);
                $doc->save();
                $count++;

                // Rate limiting para no exceder límites de API
                usleep(100000); // 100ms entre llamadas
            } catch (\Exception $e) {
                Log::warning("Error regenerando embedding para documento {$doc->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Registra una pregunta en el canal ferrallin
     */
    private function logPregunta(
        string $pregunta,
        string $metodo,
        array $documentos = [],
        ?float $duracion = null,
        ?string $error = null
    ): void {
        try {
            $user = Auth::user();

            $logData = [
                'usuario' => $user ? "{$user->name} (ID: {$user->id})" : 'Anónimo',
                'pregunta' => $pregunta,
                'metodo' => $metodo,
                'documentos' => $documentos,
                'duracion_seg' => $duracion,
            ];

            if ($error) {
                $logData['error'] = $error;
            }

            Log::channel('ferrallin')->info('Pregunta a Ferrallin', $logData);
        } catch (\Exception $e) {
            // Si falla el logging, no interrumpir el flujo
            Log::warning('Error al loggear pregunta de Ferrallin: ' . $e->getMessage());
        }
    }
}
