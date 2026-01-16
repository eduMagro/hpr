<?php

namespace App\Services\Asistente;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

/**
 * Servicio de Inteligencia Artificial para comprensión de lenguaje natural
 * Soporta múltiples proveedores: Anthropic (Claude), OpenAI (GPT), y análisis local
 */
class IAService
{
    protected string $modeloActual;
    protected array $configModelo;
    protected array $configProveedor;

    public function __construct(?string $modelo = null)
    {
        $this->setModelo($modelo ?? config('asistente.modelo_defecto', 'claude-haiku'));
    }

    /**
     * Establece el modelo a usar
     */
    public function setModelo(string $modelo): self
    {
        $modelos = config('asistente.modelos', []);

        if (!isset($modelos[$modelo]) || !$modelos[$modelo]['activo']) {
            // Fallback al modelo por defecto
            $modelo = config('asistente.modelo_defecto', 'claude-haiku');
        }

        $this->modeloActual = $modelo;
        $this->configModelo = $modelos[$modelo] ?? [];
        $this->configProveedor = config('asistente.proveedores.' . ($this->configModelo['proveedor'] ?? 'local'), []);

        return $this;
    }

    /**
     * Obtiene el modelo actual
     */
    public function getModelo(): string
    {
        return $this->modeloActual;
    }

    /**
     * Obtiene información del modelo actual
     */
    public function getInfoModelo(): array
    {
        return $this->configModelo;
    }

    /**
     * Obtiene todos los modelos disponibles
     */
    public static function getModelosDisponibles(): array
    {
        $modelos = config('asistente.modelos', []);
        $disponibles = [];

        foreach ($modelos as $id => $config) {
            if ($config['activo'] ?? false) {
                // Verificar que el proveedor tenga API key configurada
                $proveedor = $config['proveedor'];
                if ($proveedor === 'local') {
                    $disponibles[$id] = $config;
                } else {
                    $proveedorConfig = config("asistente.proveedores.{$proveedor}", []);
                    $apiKey = env($proveedorConfig['api_key_env'] ?? '');
                    if (!empty($apiKey)) {
                        $disponibles[$id] = $config;
                    }
                }
            }
        }

        return $disponibles;
    }

    /**
     * Analiza un mensaje del usuario para entender qué problema tiene y qué necesita
     */
    public function analizarProblema(string $mensaje, array $contextoAdicional = []): array
    {
        // Si es modelo local, usar analizador semántico
        if ($this->configModelo['proveedor'] === 'local') {
            return $this->analizarLocal($mensaje);
        }

        $prompt = $this->construirPromptAnalisis($mensaje, $contextoAdicional);

        try {
            $respuesta = $this->llamarAPI($prompt);

            if (!$respuesta['success']) {
                Log::warning('IAService: Error en API', [
                    'modelo' => $this->modeloActual,
                    'error' => $respuesta['error']
                ]);
                return $this->respuestaFallback($mensaje);
            }

            $analisis = $this->parsearRespuesta($respuesta['content']);

            if (!$this->validarAnalisis($analisis)) {
                Log::warning('IAService: Análisis inválido', ['analisis' => $analisis]);
                return $this->respuestaFallback($mensaje);
            }

            // Añadir info del modelo usado
            $analisis['_modelo'] = $this->modeloActual;
            $analisis['_proveedor'] = $this->configModelo['proveedor'];

            return $analisis;

        } catch (\Exception $e) {
            Log::error('IAService: Excepción', ['error' => $e->getMessage()]);
            return $this->respuestaFallback($mensaje);
        }
    }

    /**
     * Análisis usando el analizador semántico local (sin API externa)
     */
    protected function analizarLocal(string $mensaje): array
    {
        $analizador = new AnalizadorSemanticoService();
        $analisis = $analizador->analizar($mensaje);

        $problema = $analisis['problema_detectado'] ?? null;

        return [
            'es_problema' => $problema !== null,
            'confianza' => $analisis['confianza']['general'] ?? 30,
            'comprension' => $problema['descripcion'] ?? 'Análisis local',
            'tipo_problema' => $problema['tipo'] ?? 'ninguno',
            'entidad_afectada' => $problema['entidad_principal'] ?? 'ninguno',
            'accion_realizada' => $problema['accion_detectada'] ?? 'ninguno',
            'detalles' => [
                'codigo_pedido' => $analisis['codigos']['pedido'][0] ?? null,
                'codigo_planilla' => $analisis['codigos']['planilla'][0] ?? null,
                'tiempo_transcurrido' => $analisis['temporal']['tipo'] ?? null,
            ],
            'intencion_usuario' => array_key_first($analisis['intenciones'] ?? []) ?? 'otro',
            'pregunta_clarificacion' => null,
            'gravedad' => 'media',
            '_modelo' => 'local',
            '_proveedor' => 'local',
        ];
    }

    /**
     * Llama a la API según el proveedor configurado
     */
    protected function llamarAPI(string $prompt): array
    {
        $proveedor = $this->configModelo['proveedor'];

        return match ($proveedor) {
            'anthropic' => $this->llamarAnthropic($prompt),
            'openai' => $this->llamarOpenAI($prompt),
            default => ['success' => false, 'error' => "Proveedor no soportado: {$proveedor}"],
        };
    }

    /**
     * Llama a la API de Anthropic (Claude)
     */
    protected function llamarAnthropic(string $prompt): array
    {
        $apiKey = env($this->configProveedor['api_key_env'] ?? 'ANTHROPIC_API_KEY');

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key de Anthropic no configurada'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => $this->configProveedor['version'] ?? '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(config('asistente.timeout', 30))->post($this->configProveedor['api_url'], [
                'model' => $this->configModelo['modelo_id'],
                'max_tokens' => $this->configModelo['max_tokens'] ?? 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'content' => $data['content'][0]['text'] ?? ''];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body(),
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Llama a la API de OpenAI (GPT)
     */
    protected function llamarOpenAI(string $prompt): array
    {
        $apiKey = env($this->configProveedor['api_key_env'] ?? 'OPENAI_API_KEY');

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key de OpenAI no configurada'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(config('asistente.timeout', 30))->post($this->configProveedor['api_url'], [
                'model' => $this->configModelo['modelo_id'],
                'max_tokens' => $this->configModelo['max_tokens'] ?? 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return ['success' => true, 'content' => $data['choices'][0]['message']['content'] ?? ''];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body(),
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Construye el prompt para análisis de problemas
     */
    protected function construirPromptAnalisis(string $mensaje, array $contexto): string
    {
        $sistemaInfo = $this->obtenerInfoSistema();

        return <<<PROMPT
Eres un asistente experto en analizar mensajes de usuarios de un sistema de gestión de producción de ferralla (acero para construcción).

## CONTEXTO DEL SISTEMA

El sistema gestiona:
- **Pedidos**: Compras de material (barras de acero) a proveedores
  - Tienen líneas de pedido con productos, cantidades
  - Se recepcionan cuando llega el material
- **Planillas**: Órdenes de fabricación para clientes
  - Contienen elementos (piezas) a fabricar
  - Estados: pendiente, fabricando, completada, pausada
- **Elementos**: Piezas individuales de una planilla
  - Se asignan a máquinas para fabricar
  - Estados: pendiente, asignado, fabricado
- **Máquinas**: Cortadoras, dobladoras, estribadoras
- **Stock**: Inventario de material en naves/almacenes
  - Se mueve entre naves
  - Se consume al fabricar

{$sistemaInfo}

## TU TAREA

Analiza el siguiente mensaje del usuario y determina:
1. Si está reportando un ERROR o PROBLEMA que necesita corregirse
2. Qué tipo de entidad está afectada
3. Qué acción se realizó incorrectamente
4. Qué información específica proporciona (códigos, cantidades, tiempos)
5. Qué quiere que se haga para solucionarlo

## MENSAJE DEL USUARIO

"{$mensaje}"

## RESPUESTA

Responde ÚNICAMENTE con un JSON válido (sin explicaciones adicionales) con esta estructura:

```json
{
  "es_problema": true/false,
  "confianza": 0-100,
  "comprension": "Resumen en 1 frase de lo que entendiste",
  "tipo_problema": "linea_pedido_activada|elemento_fabricado_error|planilla_estado_incorrecto|asignacion_maquina_error|recepcion_pedido_error|movimiento_stock_error|otro|ninguno",
  "entidad_afectada": "pedido|linea_pedido|elemento|planilla|maquina|stock|recepcion|otro|ninguno",
  "accion_realizada": "activar|desactivar|fabricar|asignar|recepcionar|mover|cambiar_estado|eliminar|otro|ninguno",
  "detalles": {
    "codigo_pedido": "si lo menciona o null",
    "codigo_planilla": "si lo menciona o null",
    "numero_linea": "si lo menciona o null",
    "diametro": "si lo menciona o null",
    "cantidad": "si lo menciona o null",
    "tiempo_transcurrido": "descripción temporal o null",
    "otros": "cualquier otro detalle relevante"
  },
  "intencion_usuario": "revertir|corregir|consultar|ayuda|otro",
  "pregunta_clarificacion": "Si necesitas más info para ayudar, qué preguntarías (o null si tienes suficiente)",
  "gravedad": "baja|media|alta"
}
```

IMPORTANTE:
- Si el usuario NO está reportando un problema (solo pregunta o saluda), pon es_problema: false
- Sé generoso con la confianza si entiendes claramente qué pasó
- El campo "comprension" debe demostrar que entendiste el mensaje
- Si falta información crítica, sugiere una pregunta de clarificación
PROMPT;
    }

    /**
     * Obtiene información del sistema para dar contexto a la IA
     */
    protected function obtenerInfoSistema(): string
    {
        return Cache::remember('ia_service_sistema_info', 300, function () {
            $info = [];
            try {
                $info[] = "Estados de planilla usados: pendiente, fabricando, completada, pausada";
                $info[] = "Estados de elemento: pendiente, asignado, fabricado";
                $info[] = "Diámetros comunes: 6, 8, 10, 12, 16, 20, 25, 32 mm";
            } catch (\Exception $e) {
                // Ignorar errores
            }
            return empty($info) ? '' : "## INFO ADICIONAL\n" . implode("\n", $info);
        });
    }

    /**
     * Parsea la respuesta JSON de la IA
     */
    protected function parsearRespuesta(string $content): array
    {
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = $matches[0];
        } else {
            $json = $content;
        }

        $parsed = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('IAService: Error parseando JSON', ['content' => $content]);
            return [];
        }

        return $parsed;
    }

    /**
     * Valida que el análisis tenga los campos necesarios
     */
    protected function validarAnalisis(array $analisis): bool
    {
        return isset($analisis['es_problema'], $analisis['confianza'], $analisis['comprension']);
    }

    /**
     * Respuesta de fallback cuando la IA falla
     */
    protected function respuestaFallback(string $mensaje): array
    {
        $resultado = $this->analizarLocal($mensaje);
        $resultado['_fallback'] = true;
        return $resultado;
    }

    /**
     * Verifica si el servicio está disponible
     */
    public function estaDisponible(): bool
    {
        if ($this->configModelo['proveedor'] === 'local') {
            return true;
        }

        $apiKey = env($this->configProveedor['api_key_env'] ?? '');
        return !empty($apiKey);
    }

    /**
     * Guarda la preferencia de modelo del usuario
     */
    public static function guardarPreferenciaUsuario(User $user, string $modelo): bool
    {
        $modelos = config('asistente.modelos', []);

        if (!isset($modelos[$modelo]) || !$modelos[$modelo]['activo']) {
            return false;
        }

        $user->update([
            'asistente_modelo' => $modelo,
        ]);

        return true;
    }

    /**
     * Obtiene la preferencia de modelo del usuario
     */
    public static function obtenerPreferenciaUsuario(User $user): string
    {
        return $user->asistente_modelo ?? config('asistente.modelo_defecto', 'claude-haiku');
    }
}
