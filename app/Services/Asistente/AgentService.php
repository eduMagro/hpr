<?php

namespace App\Services\Asistente;

use App\Models\User;
use App\Models\AccionAsistente;
use Illuminate\Support\Facades\DB;

/**
 * AgentService - Coordinador principal del agente inteligente
 *
 * Este servicio actúa como el "cerebro" de Ferrallin, coordinando:
 * - Comprensión de intenciones del usuario
 * - Selección de herramientas apropiadas
 * - Ejecución de acciones
 * - Gestión de confirmaciones
 */
class AgentService
{
    protected IAService $iaService;
    protected AccionService $accionService;
    protected User $user;
    protected array $herramientas = [];
    protected array $historialAcciones = [];
    protected $log;

    /**
     * Definición de todas las herramientas disponibles
     */
    protected const HERRAMIENTAS_DISPONIBLES = [
        // === PLANILLAS ===
        'planilla_listar' => [
            'nombre' => 'Listar planillas',
            'descripcion' => 'Lista planillas con filtros opcionales (estado, cliente, obra, fecha)',
            'categoria' => 'planillas',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'planilla_ver' => [
            'nombre' => 'Ver detalle de planilla',
            'descripcion' => 'Muestra información detallada de una planilla específica',
            'categoria' => 'planillas',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'planilla_cambiar_estado' => [
            'nombre' => 'Cambiar estado de planilla',
            'descripcion' => 'Cambia el estado de una planilla (pendiente, fabricando, completada, pausada)',
            'categoria' => 'planillas',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
        'planilla_adelantar' => [
            'nombre' => 'Adelantar planilla en cola',
            'descripcion' => 'Mueve una planilla a una posición anterior en la cola de fabricación',
            'categoria' => 'planillas',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
        'planilla_asignar_maquina' => [
            'nombre' => 'Asignar planilla a máquina',
            'descripcion' => 'Asigna los elementos de una planilla a una máquina específica',
            'categoria' => 'planillas',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
        'planilla_cambiar_prioridad' => [
            'nombre' => 'Cambiar prioridad de planilla',
            'descripcion' => 'Cambia la prioridad de una planilla (alta, normal, baja)',
            'categoria' => 'planillas',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],

        // === ELEMENTOS ===
        'elemento_listar' => [
            'nombre' => 'Listar elementos',
            'descripcion' => 'Lista elementos de una planilla o por filtros',
            'categoria' => 'elementos',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'elemento_cambiar_estado' => [
            'nombre' => 'Cambiar estado de elementos',
            'descripcion' => 'Cambia el estado de uno o varios elementos (pendiente, asignado, fabricado)',
            'categoria' => 'elementos',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
        'elemento_asignar_maquina' => [
            'nombre' => 'Asignar elemento a máquina',
            'descripcion' => 'Asigna un elemento específico a una máquina',
            'categoria' => 'elementos',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],

        // === PEDIDOS ===
        'pedido_listar' => [
            'nombre' => 'Listar pedidos',
            'descripcion' => 'Lista pedidos de compra con filtros opcionales',
            'categoria' => 'pedidos',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'pedido_ver' => [
            'nombre' => 'Ver detalle de pedido',
            'descripcion' => 'Muestra información detallada de un pedido y sus líneas',
            'categoria' => 'pedidos',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'pedido_recepcionar' => [
            'nombre' => 'Recepcionar pedido',
            'descripcion' => 'Registra la recepción de material de un pedido',
            'categoria' => 'pedidos',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
        'pedido_linea_activar' => [
            'nombre' => 'Activar/desactivar línea de pedido',
            'descripcion' => 'Activa o desactiva una línea de pedido',
            'categoria' => 'pedidos',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],

        // === STOCK ===
        'stock_consultar' => [
            'nombre' => 'Consultar stock',
            'descripcion' => 'Consulta el stock actual por diámetro, nave, o producto',
            'categoria' => 'stock',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'stock_mover' => [
            'nombre' => 'Mover stock entre naves',
            'descripcion' => 'Transfiere material de una nave a otra',
            'categoria' => 'stock',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
        'stock_ajustar' => [
            'nombre' => 'Ajustar stock',
            'descripcion' => 'Realiza un ajuste de inventario',
            'categoria' => 'stock',
            'nivel_permiso' => 3,
            'requiere_confirmacion' => true,
        ],

        // === PRODUCCIÓN ===
        'produccion_resumen' => [
            'nombre' => 'Resumen de producción',
            'descripcion' => 'Muestra resumen de producción del día/semana/mes',
            'categoria' => 'produccion',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'produccion_maquinas' => [
            'nombre' => 'Estado de máquinas',
            'descripcion' => 'Muestra el estado actual de las máquinas y su producción',
            'categoria' => 'produccion',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'produccion_cola' => [
            'nombre' => 'Cola de fabricación',
            'descripcion' => 'Muestra la cola de planillas pendientes de fabricar',
            'categoria' => 'produccion',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'produccion_maquina_planilla' => [
            'nombre' => 'Planilla actual en máquina',
            'descripcion' => 'Muestra qué planilla se está fabricando en una máquina específica',
            'categoria' => 'produccion',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],

        // === CLIENTES Y OBRAS ===
        'cliente_listar' => [
            'nombre' => 'Listar clientes',
            'descripcion' => 'Lista clientes con sus obras activas',
            'categoria' => 'clientes',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],
        'obra_listar' => [
            'nombre' => 'Listar obras',
            'descripcion' => 'Lista obras de un cliente o todas las activas',
            'categoria' => 'clientes',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],

        // === ALERTAS ===
        'alerta_enviar' => [
            'nombre' => 'Enviar alerta',
            'descripcion' => 'Envía una alerta a usuarios específicos o departamentos',
            'categoria' => 'alertas',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],

        // === NAVEGACIÓN ===
        'navegar' => [
            'nombre' => 'Navegar a sección',
            'descripcion' => 'Dirige al usuario a una sección específica de la aplicación',
            'categoria' => 'navegacion',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],

        // === REPORTES ===
        'reporte_generar' => [
            'nombre' => 'Generar reporte',
            'descripcion' => 'Genera un reporte en PDF de stock, producción, etc.',
            'categoria' => 'reportes',
            'nivel_permiso' => 1,
            'requiere_confirmacion' => false,
        ],

        // === CORRECCIONES === (deshabilitado temporalmente)
        // 'correccion_revertir' => [
        //     'nombre' => 'Revertir cambio',
        //     'descripcion' => 'Revierte un cambio reciente (estado, asignación, recepción)',
        //     'categoria' => 'correcciones',
        //     'nivel_permiso' => 2,
        //     'requiere_confirmacion' => true,
        // ],
    ];

    /**
     * Rutas de navegación disponibles
     */
    protected const RUTAS_NAVEGACION = [
        'planillas' => ['ruta' => '/planillas', 'nombre' => 'Planillas'],
        'produccion' => ['ruta' => '/produccion', 'nombre' => 'Producción'],
        'maquinas' => ['ruta' => '/produccion/maquinas', 'nombre' => 'Máquinas'],
        'stock' => ['ruta' => '/stock', 'nombre' => 'Stock'],
        'pedidos' => ['ruta' => '/pedidos', 'nombre' => 'Pedidos'],
        'clientes' => ['ruta' => '/clientes', 'nombre' => 'Clientes'],
        'obras' => ['ruta' => '/obras', 'nombre' => 'Obras'],
        'alertas' => ['ruta' => '/alertas', 'nombre' => 'Alertas'],
        'usuarios' => ['ruta' => '/usuarios', 'nombre' => 'Usuarios'],
        'reportes' => ['ruta' => '/reportes', 'nombre' => 'Reportes'],
        'dashboard' => ['ruta' => '/dashboard', 'nombre' => 'Dashboard'],
    ];

    public function __construct(?User $user = null, ?string $modelo = null)
    {
        $this->iaService = new IAService($modelo);
        $this->accionService = new AccionService();
        $this->log = \Illuminate\Support\Facades\Log::channel('asistente-virtual');
        if ($user) {
            $this->user = $user;
        }
    }

    /**
     * Establece el usuario actual
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Procesa un mensaje del usuario y determina qué acción tomar
     */
    public function procesar(string $mensaje): array
    {
        // 0. PRIMERO: Detectar si es una pregunta informativa (no una acción)
        // En ese caso, devolver null para que el flujo normal de OpenAI se encargue
        if ($this->esPreguntaInformativa($mensaje)) {
            $this->log->debug('AgentService: Pregunta informativa detectada, delegando a OpenAI');
            return [
                'tipo' => 'respuesta',
                'contenido' => null, // null indica que debe pasar al flujo normal
                'herramienta' => null,
            ];
        }

        // 1. Analizar el mensaje con IA para entender la intención
        $analisis = $this->analizarIntencion($mensaje);

        $this->log->debug('AgentService: Análisis de intención', [
            'mensaje' => substr($mensaje, 0, 100),
            'herramienta' => $analisis['herramienta'] ?? 'ninguna',
            'confianza' => $analisis['confianza'] ?? 0,
        ]);

        // 2. Si no se detectó ninguna herramienta o confianza baja, dejar que OpenAI responda
        if (empty($analisis['herramienta']) || $analisis['herramienta'] === 'ninguna') {
            return [
                'tipo' => 'respuesta',
                'contenido' => null, // null indica que debe pasar al flujo normal
                'herramienta' => null,
            ];
        }

        // 2.5 Si la confianza es menor a 70%, delegar a OpenAI
        if (($analisis['confianza'] ?? 0) < 70) {
            $this->log->debug('AgentService: Confianza baja, delegando a OpenAI');
            return [
                'tipo' => 'respuesta',
                'contenido' => null,
                'herramienta' => null,
            ];
        }

        // 3. Verificar permisos
        $herramienta = $analisis['herramienta'];
        if (!$this->tienePermiso($herramienta)) {
            return [
                'tipo' => 'error',
                'contenido' => "No tienes permisos para realizar esta acción: " . (self::HERRAMIENTAS_DISPONIBLES[$herramienta]['nombre'] ?? $herramienta),
                'herramienta' => $herramienta,
            ];
        }

        // 4. Si requiere confirmación, preparar la solicitud
        $configHerramienta = self::HERRAMIENTAS_DISPONIBLES[$herramienta] ?? [];
        if ($configHerramienta['requiere_confirmacion'] ?? false) {
            return $this->prepararConfirmacion($herramienta, $analisis);
        }

        // 5. Ejecutar la herramienta directamente
        return $this->ejecutarHerramienta($herramienta, $analisis['parametros'] ?? []);
    }

    /**
     * Detecta si el mensaje es una pregunta informativa (no una solicitud de acción)
     */
    protected function esPreguntaInformativa(string $mensaje): bool
    {
        $mensajeLower = mb_strtolower($mensaje);

        // Patrones de preguntas informativas
        $patronesInformativos = [
            '/^(cómo|como)\s+(se\s+)?(hace|hago|puedo|debo|tengo que)/',  // "cómo hago...", "cómo puedo..."
            '/^(qué|que)\s+(pasos|debo|tengo que|hay que)/',              // "qué pasos...", "qué debo..."
            '/^(cuáles|cuales)\s+(son\s+)?(los\s+)?pasos/',               // "cuáles son los pasos"
            '/^explíca(me)?|explica(me)?/',                                // "explícame..."
            '/^(dime|me puedes decir)\s+(cómo|como|qué|que)/',            // "dime cómo..."
            '/necesito\s+(saber|entender|que me expliques)/',             // "necesito saber..."
            '/^(por qué|porque|porqué)\s/',                               // "por qué..."
            '/^(cuál|cual)\s+es\s+(el|la)\s+(proceso|forma|manera)/',     // "cuál es el proceso..."
            '/^(ayuda|ayúdame)\s+(a\s+)?(entender|saber)/',               // "ayúdame a entender..."
            '/(si|soy)\s+administrador/',                                  // "si soy administrador"
            '/permisos/',                                                  // preguntas sobre permisos
        ];

        foreach ($patronesInformativos as $patron) {
            if (preg_match($patron, $mensajeLower)) {
                return true;
            }
        }

        // También es informativa si es una pregunta general (termina en ?)
        // y NO contiene verbos de acción directa
        if (str_contains($mensaje, '?')) {
            $verbosAccion = ['cambia', 'adelanta', 'mueve', 'asigna', 'envía', 'envia', 'crea', 'elimina', 'borra'];
            foreach ($verbosAccion as $verbo) {
                if (str_contains($mensajeLower, $verbo)) {
                    return false; // Es una solicitud de acción
                }
            }
            return true; // Es una pregunta informativa
        }

        return false;
    }

    /**
     * Analiza la intención del usuario usando IA
     */
    protected function analizarIntencion(string $mensaje): array
    {
        try {
            // Detectar herramientas específicas directamente
            // Ya no usamos analizarProblema porque causa falsos positivos
            return $this->detectarHerramienta($mensaje);

        } catch (\Exception $e) {
            $this->log->error('AgentService: Error analizando intención', ['error' => $e->getMessage()]);
            return [
                'herramienta' => null,
                'respuesta_sugerida' => null,
            ];
        }
    }

    /**
     * Detecta qué herramienta usar basándose en el mensaje
     */
    protected function detectarHerramienta(string $mensaje): array
    {
        $herramientasJson = json_encode(
            collect(self::HERRAMIENTAS_DISPONIBLES)
                ->map(fn($h, $id) => ['id' => $id, 'nombre' => $h['nombre'], 'descripcion' => $h['descripcion']])
                ->values()
                ->toArray(),
            JSON_UNESCAPED_UNICODE
        );

        $prompt = <<<PROMPT
Analiza el siguiente mensaje de un usuario de un sistema de gestión de ferralla.

## IMPORTANTE - DISTINGUIR TIPOS DE MENSAJE
1. **PREGUNTA INFORMATIVA** = El usuario quiere SABER cómo hacer algo, pide explicaciones, pregunta sobre procesos
   - Ejemplos: "¿Cómo elimino una línea?", "¿Qué pasos debo seguir?", "Explícame el proceso"
   - En estos casos: herramienta = "ninguna"

2. **SOLICITUD DE ACCIÓN** = El usuario quiere que YO EJECUTE algo ahora mismo
   - Ejemplos: "Cambia el estado de planilla 123", "Muéstrame las planillas pendientes", "Adelanta la planilla X"
   - En estos casos: seleccionar la herramienta apropiada

## HERRAMIENTAS DISPONIBLES (solo para SOLICITUDES DE ACCIÓN)
{$herramientasJson}

## MENSAJE DEL USUARIO
"{$mensaje}"

## REGLAS
- Si es pregunta informativa (cómo, qué pasos, explícame, por qué, etc.) → herramienta: "ninguna"
- Si pide que le muestres datos específicos (listar, ver, consultar) → seleccionar herramienta de consulta
- Si pide modificar algo (cambiar, mover, adelantar) → seleccionar herramienta de modificación
- Si pide mover/intercambiar/pasar planillas de posición en una máquina → planilla_adelantar
- Si no estás seguro → herramienta: "ninguna", confianza: 0

## RESPUESTA (solo JSON válido)
```json
{
  "herramienta": "id_herramienta o 'ninguna'",
  "confianza": 0-100,
  "es_pregunta_informativa": true/false,
  "parametros": {
    "codigo": "si menciona código de planilla",
    "estado": "si menciona estado",
    "periodo": "hoy/semana/mes si aplica",
    "maquina": "nombre de máquina si pregunta por producción en máquina específica (ej: syntax, robomaster, schnell, ms16, ms20, etc.)",
    "por_usuario": "true si pide producción por usuario/trabajador/operario/persona",
    "posicion_origen": "posición actual de la planilla a mover (número)",
    "posicion_destino": "posición destino donde mover la planilla (número)",
    "nueva_posicion": "sinónimo de posicion_destino"
  }
}
```
PROMPT;

        try {
            $response = $this->llamarIA($prompt);
            return $this->parsearRespuestaIA($response);
        } catch (\Exception $e) {
            $this->log->error('AgentService: Error detectando herramienta', ['error' => $e->getMessage()]);
            return ['herramienta' => null];
        }
    }

    /**
     * Llama a la IA con un prompt específico
     */
    protected function llamarIA(string $prompt): string
    {
        $resultado = $this->iaService->llamarAPI($prompt);

        if ($resultado['success']) {
            return $resultado['content'];
        }

        throw new \Exception($resultado['error'] ?? 'Error desconocido');
    }

    /**
     * Parsea la respuesta JSON de la IA
     */
    protected function parsearRespuestaIA(string $content): array
    {
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = $matches[0];
        } else {
            return ['herramienta' => null];
        }

        return json_decode($json, true) ?? ['herramienta' => null];
    }

    /**
     * Verifica si el usuario tiene permiso para usar una herramienta
     */
    protected function tienePermiso(string $herramienta): bool
    {
        if (!isset($this->user)) {
            return false;
        }

        $config = self::HERRAMIENTAS_DISPONIBLES[$herramienta] ?? null;
        if (!$config) {
            return false;
        }

        $nivelRequerido = $config['nivel_permiso'] ?? 1;

        // Nivel 1: Cualquier usuario con acceso al asistente
        if ($nivelRequerido === 1) {
            return $this->user->puede_usar_asistente ?? false;
        }

        // Nivel 2: Usuario con permiso de modificación
        if ($nivelRequerido === 2) {
            return $this->user->puede_modificar_bd ?? false;
        }

        // Nivel 3: Solo administradores
        if ($nivelRequerido === 3) {
            return $this->user->esAdmin() || $this->user->esAdminDepartamento();
        }

        return false;
    }

    /**
     * Prepara una solicitud de confirmación para acciones críticas
     */
    protected function prepararConfirmacion(string $herramienta, array $analisis): array
    {
        $config = self::HERRAMIENTAS_DISPONIBLES[$herramienta] ?? [];
        $parametros = $analisis['parametros'] ?? [];

        // Simular la acción para mostrar qué se va a hacer
        $simulacion = $this->simularAccion($herramienta, $parametros);

        // Generar token de confirmación
        $token = bin2hex(random_bytes(16));
        $expiracion = now()->addMinutes(5);

        // Guardar en caché para validar después
        cache()->put("agente_confirmacion_{$token}", [
            'herramienta' => $herramienta,
            'parametros' => $parametros,
            'user_id' => $this->user->id,
            'simulacion' => $simulacion,
        ], $expiracion);

        return [
            'tipo' => 'confirmacion',
            'herramienta' => $herramienta,
            'nombre_herramienta' => $config['nombre'] ?? $herramienta,
            'simulacion' => $simulacion,
            'token' => $token,
            'expira' => $expiracion->toIso8601String(),
            'contenido' => $this->formatearConfirmacion($config, $simulacion),
        ];
    }

    /**
     * Simula una acción para mostrar qué se va a hacer
     */
    protected function simularAccion(string $herramienta, array $parametros): array
    {
        // Ejecutar la herramienta en modo simulación
        return match ($herramienta) {
            'planilla_cambiar_estado' => $this->simularCambioEstadoPlanilla($parametros),
            'elemento_cambiar_estado' => $this->simularCambioEstadoElemento($parametros),
            'stock_mover' => $this->simularMovimientoStock($parametros),
            'pedido_recepcionar' => $this->simularRecepcion($parametros),
            default => [
                'descripcion' => "Se ejecutará: " . (self::HERRAMIENTAS_DISPONIBLES[$herramienta]['nombre'] ?? $herramienta),
                'parametros' => $parametros,
            ],
        };
    }

    /**
     * Confirma y ejecuta una acción pendiente
     */
    public function confirmarAccion(string $token): array
    {
        $datos = cache()->get("agente_confirmacion_{$token}");

        if (!$datos) {
            return [
                'tipo' => 'error',
                'contenido' => 'La confirmación ha expirado o no es válida. Por favor, vuelve a solicitar la acción.',
            ];
        }

        // Verificar que sea el mismo usuario
        if ($datos['user_id'] !== $this->user->id) {
            return [
                'tipo' => 'error',
                'contenido' => 'No tienes permiso para confirmar esta acción.',
            ];
        }

        // Eliminar la confirmación usada
        cache()->forget("agente_confirmacion_{$token}");

        // Ejecutar la herramienta
        return $this->ejecutarHerramienta($datos['herramienta'], $datos['parametros']);
    }

    /**
     * Cancela una acción pendiente
     */
    public function cancelarAccion(string $token): array
    {
        cache()->forget("agente_confirmacion_{$token}");

        return [
            'tipo' => 'respuesta',
            'contenido' => 'Acción cancelada.',
        ];
    }

    /**
     * Ejecuta una herramienta específica
     */
    public function ejecutarHerramienta(string $herramienta, array $parametros): array
    {
        try {
            DB::beginTransaction();

            $resultado = match ($herramienta) {
                // Planillas
                'planilla_listar' => $this->ejecutarPlanillaListar($parametros),
                'planilla_ver' => $this->ejecutarPlanillaVer($parametros),
                'planilla_cambiar_estado' => $this->ejecutarPlanillaCambiarEstado($parametros),
                'planilla_adelantar' => $this->ejecutarPlanillaAdelantar($parametros),
                'planilla_asignar_maquina' => $this->ejecutarPlanillaAsignarMaquina($parametros),
                'planilla_cambiar_prioridad' => $this->ejecutarPlanillaCambiarPrioridad($parametros),

                // Elementos
                'elemento_listar' => $this->ejecutarElementoListar($parametros),
                'elemento_cambiar_estado' => $this->ejecutarElementoCambiarEstado($parametros),

                // Pedidos
                'pedido_listar' => $this->ejecutarPedidoListar($parametros),
                'pedido_ver' => $this->ejecutarPedidoVer($parametros),
                'pedido_recepcionar' => $this->ejecutarPedidoRecepcionar($parametros),

                // Stock
                'stock_consultar' => $this->ejecutarStockConsultar($parametros),
                'stock_mover' => $this->ejecutarStockMover($parametros),
                'stock_ajustar' => $this->ejecutarStockAjustar($parametros),

                // Producción
                'produccion_resumen' => $this->ejecutarProduccionResumen($parametros),
                'produccion_maquinas' => $this->ejecutarProduccionMaquinas($parametros),
                'produccion_cola' => $this->ejecutarProduccionCola($parametros),
                'produccion_maquina_planilla' => $this->ejecutarProduccionMaquinaPlanilla($parametros),

                // Clientes
                'cliente_listar' => $this->ejecutarClienteListar($parametros),
                'obra_listar' => $this->ejecutarObraListar($parametros),

                // Alertas
                'alerta_enviar' => $this->ejecutarAlertaEnviar($parametros),

                // Navegación
                'navegar' => $this->ejecutarNavegar($parametros),

                // Reportes
                'reporte_generar' => $this->ejecutarReporteGenerar($parametros),

                default => [
                    'exito' => false,
                    'contenido' => "Herramienta no implementada: {$herramienta}",
                ],
            };

            // Registrar la acción
            $this->registrarAccion($herramienta, $parametros, $resultado);

            if ($resultado['exito'] ?? false) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            return [
                'tipo' => $resultado['exito'] ? 'exito' : 'error',
                'herramienta' => $herramienta,
                'contenido' => $resultado['contenido'] ?? '',
                'datos' => $resultado['datos'] ?? null,
                'navegacion' => $resultado['navegacion'] ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->log->error("AgentService: Error ejecutando {$herramienta}", [
                'error' => $e->getMessage(),
                'parametros' => $parametros,
            ]);

            return [
                'tipo' => 'error',
                'herramienta' => $herramienta,
                'contenido' => "Error al ejecutar la acción: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Registra una acción en el historial con información completa
     */
    protected function registrarAccion(string $herramienta, array $parametros, array $resultado): void
    {
        try {
            AccionAsistente::create([
                'user_id' => $this->user->id,
                'accion' => $herramienta,
                'parametros' => $parametros,
                'resultado' => [
                    'exito' => $resultado['exito'] ?? false,
                    'success' => $resultado['exito'] ?? false, // compatibilidad con AccionService
                    'contenido' => mb_substr($resultado['contenido'] ?? '', 0, 500),
                    'navegacion' => $resultado['navegacion'] ?? null,
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            $this->log->warning('AgentService: Error registrando acción', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene el historial de acciones del usuario actual
     */
    public function obtenerHistorialAcciones(int $limite = 20): array
    {
        return AccionAsistente::where('user_id', $this->user->id)
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'accion' => self::HERRAMIENTAS_DISPONIBLES[$a->accion]['nombre'] ?? $a->accion,
                'parametros' => $a->parametros,
                'exito' => $a->fueExitosa(),
                'fecha' => $a->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Formatea el mensaje de confirmación
     */
    protected function formatearConfirmacion(array $config, array $simulacion): string
    {
        $html = "<div class='confirmacion-agente'>";
        $html .= "<div style='background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;margin:8px 0;'>";
        $html .= "<h4 style='margin:0 0 12px 0;color:#92400e;'>⚠️ Confirmar acción: {$config['nombre']}</h4>";

        if (!empty($simulacion['descripcion'])) {
            $html .= "<p style='margin:8px 0;color:#78350f;'>{$simulacion['descripcion']}</p>";
        }

        if (!empty($simulacion['cambios'])) {
            $html .= "<div style='background:white;padding:12px;border-radius:4px;margin:12px 0;'>";
            $html .= "<strong>Cambios a realizar:</strong><ul style='margin:8px 0;padding-left:20px;'>";
            foreach ($simulacion['cambios'] as $cambio) {
                $html .= "<li>{$cambio}</li>";
            }
            $html .= "</ul></div>";
        }

        if (!empty($simulacion['advertencia'])) {
            $html .= "<p style='color:#dc2626;font-weight:600;'>⚠️ {$simulacion['advertencia']}</p>";
        }

        $html .= "<p style='font-size:0.875rem;color:#78350f;margin-top:12px;'>Esta acción requiere confirmación. ¿Deseas continuar?</p>";
        $html .= "</div></div>";

        return $html;
    }

    /**
     * Obtiene las herramientas disponibles para el usuario actual
     */
    public function getHerramientasDisponibles(): array
    {
        $disponibles = [];

        foreach (self::HERRAMIENTAS_DISPONIBLES as $id => $config) {
            if ($this->tienePermiso($id)) {
                $disponibles[$id] = $config;
            }
        }

        return $disponibles;
    }

    /**
     * Obtiene las rutas de navegación
     */
    public static function getRutasNavegacion(): array
    {
        return self::RUTAS_NAVEGACION;
    }

    /**
     * Obtiene la definición de todas las herramientas (para mostrar ayuda)
     */
    public static function getHerramientasDefinidas(): array
    {
        return self::HERRAMIENTAS_DISPONIBLES;
    }

    // ========================================================================
    // IMPLEMENTACIONES DE HERRAMIENTAS
    // ========================================================================

    protected function ejecutarPlanillaListar(array $params): array
    {
        $query = DB::table('planillas')
            ->select('planillas.*', 'clientes.empresa as cliente', 'maquinas.nombre as maquina')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->leftJoin('orden_planillas', 'planillas.id', '=', 'orden_planillas.planilla_id')
            ->leftJoin('maquinas', 'orden_planillas.maquina_id', '=', 'maquinas.id')
            ->whereNull('planillas.deleted_at');

        if (!empty($params['estado'])) {
            $query->where('planillas.estado', $params['estado']);
        }
        if (!empty($params['cliente'])) {
            $query->where('clientes.empresa', 'LIKE', "%{$params['cliente']}%");
        }

        $planillas = $query->orderByDesc('planillas.created_at')->limit(20)->get();

        if ($planillas->isEmpty()) {
            return ['exito' => true, 'contenido' => 'No se encontraron planillas.', 'datos' => []];
        }

        // Stats de elementos
        $stats = DB::table('elementos')
            ->select('planilla_id', DB::raw('COUNT(*) as t'), DB::raw('SUM(elaborado) as f'))
            ->whereIn('planilla_id', $planillas->pluck('id'))
            ->whereNull('deleted_at')
            ->groupBy('planilla_id')
            ->get()->keyBy('planilla_id');

        // Resumen compacto por estado
        $porEstado = $planillas->groupBy('estado');
        $resumen = $porEstado->map(fn($g, $e) => "{$e}:{$g->count()}")->implode(' | ');

        $contenido = "**{$planillas->count()} planillas** (" . number_format($planillas->sum('peso_total'), 0, ',', '.') . " kg) → {$resumen}\n\n";
        $contenido .= "| Código | Cliente | Progreso | Peso | Máq |\n|--------|---------|----------|------|-----|\n";

        foreach ($planillas as $p) {
            $s = $stats[$p->id] ?? null;
            $prog = $s ? round(($s->f / max($s->t, 1)) * 100) . "%" : '-';
            $cli = mb_substr($p->cliente ?? '-', 0, 15);
            $maq = $p->maquina ? mb_substr($p->maquina, 0, 8) : '-';
            $contenido .= "| {$p->codigo} | {$cli} | {$prog} | " . number_format($p->peso_total ?? 0, 0) . " | {$maq} |\n";
        }

        return ['exito' => true, 'contenido' => $contenido, 'datos' => $planillas->toArray()];
    }

    protected function ejecutarPlanillaVer(array $params): array
    {
        $codigo = $params['codigo'] ?? null;
        if (!$codigo) {
            return ['exito' => false, 'contenido' => 'Necesito el código de la planilla.'];
        }

        $planilla = DB::table('planillas')
            ->select('planillas.*', 'clientes.empresa as cliente', 'obras.obra as obra', 'maquinas.nombre as maquina')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->leftJoin('obras', 'planillas.obra_id', '=', 'obras.id')
            ->leftJoin('orden_planillas', 'planillas.id', '=', 'orden_planillas.planilla_id')
            ->leftJoin('maquinas', 'orden_planillas.maquina_id', '=', 'maquinas.id')
            ->where('planillas.codigo', 'LIKE', "%{$codigo}%")
            ->first();

        if (!$planilla) {
            return ['exito' => false, 'contenido' => "No encontré planilla '{$codigo}'."];
        }

        // Elementos con stats por diámetro
        $elementos = DB::table('elementos')
            ->select('diametro', DB::raw('COUNT(*) as total'), DB::raw('SUM(elaborado) as fab'), DB::raw('SUM(peso) as peso'))
            ->where('planilla_id', $planilla->id)
            ->whereNull('deleted_at')
            ->groupBy('diametro')
            ->orderBy('diametro')
            ->get();

        $totalElem = $elementos->sum('total');
        $totalFab = $elementos->sum('fab');
        $totalPeso = $elementos->sum('peso');
        $progreso = $totalElem > 0 ? round(($totalFab / $totalElem) * 100) : 0;

        $contenido = "### 📋 {$planilla->codigo}\n";
        $contenido .= "**{$planilla->cliente}** → {$planilla->obra}\n";
        $contenido .= "Estado: **{$planilla->estado}** | Máquina: **" . ($planilla->maquina ?? 'Sin asignar') . "**\n\n";

        $contenido .= "**Progreso: {$progreso}%** ({$totalFab}/{$totalElem} elementos | " . number_format($totalPeso, 0, ',', '.') . " kg)\n\n";

        // Desglose por diámetro compacto
        if ($elementos->isNotEmpty()) {
            $contenido .= "**Por diámetro:**\n";
            $contenido .= "| Ø | Fab/Total | Peso |\n|---|-----------|------|\n";
            foreach ($elementos as $e) {
                $pct = $e->total > 0 ? round(($e->fab / $e->total) * 100) : 0;
                $contenido .= "| Ø{$e->diametro} | {$e->fab}/{$e->total} ({$pct}%) | " . number_format($e->peso, 0) . " kg |\n";
            }
        }

        // Fechas relevantes
        if ($planilla->fecha_estimada_entrega) {
            $fechaEntrega = \Carbon\Carbon::parse($planilla->fecha_estimada_entrega);
            $diasRestantes = now()->diffInDays($fechaEntrega, false);
            $urgencia = $diasRestantes < 0 ? "⚠️ **VENCIDA**" : ($diasRestantes <= 2 ? "⏰ {$diasRestantes}d" : "{$diasRestantes}d");
            $contenido .= "\n📅 Entrega: {$fechaEntrega->format('d/m/Y')} ({$urgencia})";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => ['planilla' => $planilla, 'elementos' => $elementos],
            'navegacion' => "/planillas/{$planilla->id}",
        ];
    }

    protected function ejecutarPlanillaCambiarEstado(array $params): array
    {
        $codigo = $params['codigo'] ?? null;
        $nuevoEstado = $params['estado'] ?? null;

        if (!$codigo || !$nuevoEstado) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el código de la planilla y el nuevo estado.',
            ];
        }

        // Delegar a AccionService para ejecución con validación y auditoría
        $resultado = $this->accionService->ejecutarAccion(
            'cambiar_estado_planilla',
            ['codigo_planilla' => $codigo, 'estado' => $nuevoEstado],
            $this->user
        );

        if ($resultado['success']) {
            return [
                'exito' => true,
                'contenido' => "✅ " . $resultado['mensaje'],
                'datos' => $resultado,
            ];
        }

        return [
            'exito' => false,
            'contenido' => $resultado['error'] ?? 'Error al cambiar estado',
        ];
    }

    protected function ejecutarStockConsultar(array $params): array
    {
        $diametro = $params['diametro'] ?? null;
        $nave = $params['nave'] ?? null;

        $query = DB::table('stock')
            ->select('productos_base.diametro', 'productos_base.tipo', 'naves.nombre as nave', DB::raw('SUM(stock.cantidad) as cantidad'))
            ->leftJoin('productos_base', 'stock.producto_base_id', '=', 'productos_base.id')
            ->leftJoin('naves', 'stock.nave_id', '=', 'naves.id')
            ->where('stock.cantidad', '>', 0)
            ->groupBy('productos_base.diametro', 'productos_base.tipo', 'naves.nombre')
            ->orderBy('productos_base.diametro');

        if ($diametro) $query->where('productos_base.diametro', $diametro);
        if ($nave) $query->where('naves.nombre', 'LIKE', "%{$nave}%");

        $stock = $query->get();

        if ($stock->isEmpty()) {
            return ['exito' => true, 'contenido' => 'No hay stock con esos filtros.', 'datos' => []];
        }

        $totalGeneral = $stock->sum('cantidad');
        $porDiametro = $stock->groupBy('diametro');
        $porNave = $stock->groupBy('nave');

        $contenido = "### 📦 Stock actual\n";
        $contenido .= "**Total:** " . number_format($totalGeneral, 0, ',', '.') . " kg en " . $porNave->count() . " nave(s)\n\n";

        // Resumen por diámetro (compacto)
        $contenido .= "**Por diámetro:** ";
        $contenido .= $porDiametro->map(fn($items, $d) => "Ø{$d}:" . number_format($items->sum('cantidad'), 0) . "kg")->implode(' | ');
        $contenido .= "\n\n";

        // Desglose por nave
        $contenido .= "**Por nave:**\n| Nave | Ø | Tipo | Cantidad |\n|------|---|------|----------|\n";
        foreach ($porNave as $nave => $items) {
            $primeraFila = true;
            $totalNave = $items->sum('cantidad');
            foreach ($items->sortBy('diametro') as $s) {
                $naveCol = $primeraFila ? "**" . ($nave ?? 'Sin nave') . "**" : "";
                $contenido .= "| {$naveCol} | Ø{$s->diametro} | {$s->tipo} | " . number_format($s->cantidad, 0, ',', '.') . " kg |\n";
                $primeraFila = false;
            }
            if ($items->count() > 1) {
                $contenido .= "| | | _Total nave_ | _" . number_format($totalNave, 0, ',', '.') . " kg_ |\n";
            }
        }

        return ['exito' => true, 'contenido' => $contenido, 'datos' => $stock->toArray(), 'navegacion' => '/stock'];
    }

    protected function ejecutarProduccionResumen(array $params): array
    {
        $periodo = $params['periodo'] ?? 'hoy';
        $porUsuario = $params['por_usuario'] ?? $params['usuario'] ?? $params['trabajador'] ?? $params['operario'] ?? false;

        $desde = match ($periodo) {
            'hoy' => today(),
            'semana' => now()->startOfWeek(),
            'mes' => now()->startOfMonth(),
            default => today(),
        };

        // Obtener elementos fabricados con información de máquina, planilla y usuario
        $elementosFabricados = DB::table('elementos')
            ->select(
                'elementos.*',
                'maquinas.nombre as maquina_nombre',
                'maquinas.id as maquina_id',
                'planillas.codigo as planilla_codigo',
                'planillas.id as planilla_id',
                'clientes.empresa as cliente_nombre',
                'users.name as usuario_nombre',
                'users.id as usuario_id'
            )
            ->leftJoin('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->leftJoin('planillas', 'elementos.planilla_id', '=', 'planillas.id')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->leftJoin('users', 'elementos.users_id', '=', 'users.id')
            ->where('elementos.elaborado', 1)
            ->where('elementos.updated_at', '>=', $desde)
            ->whereNull('elementos.deleted_at')
            ->orderBy('maquinas.nombre')
            ->orderBy('users.name')
            ->orderBy('planillas.codigo')
            ->get();

        $pesoTotal = $elementosFabricados->sum('peso');
        $cantidadTotal = $elementosFabricados->count();

        $contenido = "**Resumen de producción ({$periodo}):**\n\n";
        $contenido .= "- **Total fabricado:** " . number_format($pesoTotal, 0, ',', '.') . " kg\n";
        $contenido .= "- **Etiquetas fabricadas:** {$cantidadTotal}\n\n";

        if ($elementosFabricados->isEmpty()) {
            $contenido .= "_No hay producción registrada en este período._";
            return [
                'exito' => true,
                'contenido' => $contenido,
                'datos' => [],
            ];
        }

        // Si se pide por usuario, mostrar esa vista
        if ($porUsuario) {
            return $this->formatearProduccionPorUsuario($elementosFabricados, $contenido, $pesoTotal, $cantidadTotal);
        }

        // Vista estándar por máquina y planilla
        return $this->formatearProduccionPorMaquina($elementosFabricados, $contenido, $pesoTotal, $cantidadTotal);
    }

    /**
     * Formatea el resumen de producción agrupado por usuario
     */
    protected function formatearProduccionPorUsuario($elementosFabricados, string $contenido, float $pesoTotal, int $cantidadTotal): array
    {
        // Agrupar por usuario
        $porUsuario = $elementosFabricados->groupBy('usuario_nombre');

        foreach ($porUsuario as $usuarioNombre => $elementosUsuario) {
            $usuarioNombre = $usuarioNombre ?: 'Sin asignar';
            $pesoUsuario = $elementosUsuario->sum('peso');
            $cantidadUsuario = $elementosUsuario->count();

            $contenido .= "---\n";
            $contenido .= "### 👤 {$usuarioNombre}\n";
            $contenido .= "**{$cantidadUsuario} etiquetas** | **" . number_format($pesoUsuario, 0, ',', '.') . " kg**\n\n";

            // Agrupar por máquina dentro del usuario
            $porMaquina = $elementosUsuario->groupBy('maquina_nombre');

            $contenido .= "| Máquina | Planilla | Etiquetas | Peso |\n";
            $contenido .= "|---------|----------|-----------|------|\n";

            foreach ($porMaquina as $maquinaNombre => $elementosMaquina) {
                $maquinaNombre = $maquinaNombre ?: 'Sin asignar';
                $pesoMaquina = $elementosMaquina->sum('peso');
                $cantidadMaquina = $elementosMaquina->count();

                // Agrupar por planilla dentro de la máquina
                $porPlanilla = $elementosMaquina->groupBy('planilla_codigo');
                $primeraFila = true;

                foreach ($porPlanilla as $planillaCodigo => $elementosPlanilla) {
                    $planillaCodigo = $planillaCodigo ?: 'Sin planilla';
                    $pesoPlanilla = $elementosPlanilla->sum('peso');
                    $cantidadPlanilla = $elementosPlanilla->count();

                    if ($primeraFila) {
                        $contenido .= "| **{$maquinaNombre}** | {$planillaCodigo} | {$cantidadPlanilla} | " . number_format($pesoPlanilla, 0, ',', '.') . " kg |\n";
                        $primeraFila = false;
                    } else {
                        $contenido .= "| | {$planillaCodigo} | {$cantidadPlanilla} | " . number_format($pesoPlanilla, 0, ',', '.') . " kg |\n";
                    }
                }

                // Subtotal por máquina si hay múltiples planillas
                if ($porPlanilla->count() > 1) {
                    $contenido .= "| | _Subtotal {$maquinaNombre}_ | _{$cantidadMaquina}_ | _" . number_format($pesoMaquina, 0, ',', '.') . " kg_ |\n";
                }
            }

            $contenido .= "\n";
        }

        // Resumen rápido por usuario al final
        $contenido .= "---\n";
        $contenido .= "**Resumen por trabajador:**\n";
        foreach ($porUsuario as $usuarioNombre => $elementosUsuario) {
            $usuarioNombre = $usuarioNombre ?: 'Sin asignar';
            $pesoUsuario = $elementosUsuario->sum('peso');
            $cantidadUsuario = $elementosUsuario->count();
            $maquinasCount = $elementosUsuario->unique('maquina_id')->count();
            $contenido .= "- **{$usuarioNombre}:** {$cantidadUsuario} etiquetas en {$maquinasCount} máquina(s) → " . number_format($pesoUsuario, 0, ',', '.') . " kg\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [
                'peso_total' => $pesoTotal,
                'cantidad' => $cantidadTotal,
                'por_usuario' => $porUsuario->map(function ($elementos, $usuario) {
                    return [
                        'usuario' => $usuario ?: 'Sin asignar',
                        'peso' => $elementos->sum('peso'),
                        'cantidad' => $elementos->count(),
                        'por_maquina' => $elementos->groupBy('maquina_nombre')->map(function ($elems, $maquina) {
                            return [
                                'maquina' => $maquina ?: 'Sin asignar',
                                'peso' => $elems->sum('peso'),
                                'cantidad' => $elems->count(),
                            ];
                        })->values()->toArray(),
                    ];
                })->values()->toArray(),
            ],
        ];
    }

    /**
     * Formatea el resumen de producción agrupado por máquina (vista estándar)
     */
    protected function formatearProduccionPorMaquina($elementosFabricados, string $contenido, float $pesoTotal, int $cantidadTotal): array
    {
        // Agrupar por máquina
        $porMaquina = $elementosFabricados->groupBy('maquina_nombre');

        foreach ($porMaquina as $maquinaNombre => $elementosMaquina) {
            $maquinaNombre = $maquinaNombre ?: 'Sin asignar';
            $pesoMaquina = $elementosMaquina->sum('peso');
            $cantidadMaquina = $elementosMaquina->count();

            $contenido .= "---\n";
            $contenido .= "### 🏭 {$maquinaNombre}\n";
            $contenido .= "**{$cantidadMaquina} etiquetas** | **" . number_format($pesoMaquina, 0, ',', '.') . " kg**\n\n";

            // Agrupar por planilla dentro de la máquina
            $porPlanilla = $elementosMaquina->groupBy('planilla_codigo');

            $contenido .= "| Planilla | Cliente | Etiquetas | Peso |\n";
            $contenido .= "|----------|---------|-----------|------|\n";

            foreach ($porPlanilla as $planillaCodigo => $elementosPlanilla) {
                $planillaCodigo = $planillaCodigo ?: 'Sin planilla';
                $clienteNombre = $elementosPlanilla->first()->cliente_nombre ?? 'N/A';
                $pesoPlanilla = $elementosPlanilla->sum('peso');
                $cantidadPlanilla = $elementosPlanilla->count();

                // Obtener detalle de etiquetas (diámetros)
                $diametros = $elementosPlanilla->groupBy('diametro')
                    ->map(fn($items) => $items->count())
                    ->filter()
                    ->sortKeys();

                $detalleEtiquetas = $diametros->map(fn($count, $d) => "Ø{$d}:{$count}")->implode(' ');

                $contenido .= "| {$planillaCodigo} | {$clienteNombre} | {$cantidadPlanilla} | " . number_format($pesoPlanilla, 0, ',', '.') . " kg |\n";

                // Mostrar detalle de diámetros si hay más de un tipo
                if ($diametros->count() > 1) {
                    $contenido .= "| | _Detalle:_ | {$detalleEtiquetas} | |\n";
                }
            }

            $contenido .= "\n";
        }

        // Resumen rápido por máquina al final
        $contenido .= "---\n";
        $contenido .= "**Resumen por máquina:**\n";
        foreach ($porMaquina as $maquinaNombre => $elementosMaquina) {
            $maquinaNombre = $maquinaNombre ?: 'Sin asignar';
            $pesoMaquina = $elementosMaquina->sum('peso');
            $cantidadMaquina = $elementosMaquina->count();
            $planillasCount = $elementosMaquina->unique('planilla_id')->count();
            $contenido .= "- **{$maquinaNombre}:** {$cantidadMaquina} etiquetas en {$planillasCount} planilla(s) → " . number_format($pesoMaquina, 0, ',', '.') . " kg\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [
                'peso_total' => $pesoTotal,
                'cantidad' => $cantidadTotal,
                'por_maquina' => $porMaquina->map(function ($elementos, $maquina) {
                    return [
                        'maquina' => $maquina ?: 'Sin asignar',
                        'peso' => $elementos->sum('peso'),
                        'cantidad' => $elementos->count(),
                        'planillas' => $elementos->groupBy('planilla_codigo')->map(function ($elems, $planilla) {
                            return [
                                'codigo' => $planilla,
                                'peso' => $elems->sum('peso'),
                                'cantidad' => $elems->count(),
                            ];
                        })->values()->toArray(),
                    ];
                })->values()->toArray(),
            ],
        ];
    }

    protected function ejecutarProduccionMaquinas(array $params): array
    {
        $maquinas = DB::table('maquinas')->whereNull('deleted_at')->where('activa', 1)->get();

        // Producción de hoy por máquina
        $produccionHoy = DB::table('elementos')
            ->select('maquina_id', DB::raw('COUNT(*) as cant'), DB::raw('SUM(peso) as peso'))
            ->where('elaborado', 1)
            ->where('updated_at', '>=', today())
            ->whereNull('deleted_at')
            ->groupBy('maquina_id')
            ->get()->keyBy('maquina_id');

        // Planilla actual por máquina
        $planillasActuales = DB::table('orden_planillas')
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->where('planillas.revisada', 1)
            ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
            ->whereNull('planillas.deleted_at')
            ->orderBy('orden_planillas.posicion')
            ->get()
            ->groupBy('maquina_id')
            ->map(fn($g) => $g->first());

        // Pendientes por máquina
        $pendientes = DB::table('elementos')
            ->select('maquina_id', DB::raw('COUNT(*) as cant'))
            ->where('elaborado', 0)
            ->whereNull('deleted_at')
            ->groupBy('maquina_id')
            ->get()->keyBy('maquina_id');

        $totalHoy = $produccionHoy->sum('peso');
        $contenido = "### 🏭 Estado de máquinas\n";
        $contenido .= "**Producción hoy:** " . number_format($totalHoy, 0, ',', '.') . " kg\n\n";

        $contenido .= "| Máquina | Hoy | Pendiente | Planilla actual |\n";
        $contenido .= "|---------|-----|-----------|----------------|\n";

        foreach ($maquinas as $m) {
            $prod = $produccionHoy[$m->id] ?? null;
            $hoy = $prod ? number_format($prod->peso, 0) . "kg ({$prod->cant})" : '-';
            $pend = $pendientes[$m->id]->cant ?? 0;
            $planActual = $planillasActuales[$m->id]->codigo ?? '-';
            $contenido .= "| **{$m->nombre}** | {$hoy} | {$pend} elem | {$planActual} |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $maquinas->toArray(),
            'navegacion' => '/maquinas',
        ];
    }

    protected function ejecutarProduccionCola(array $params): array
    {
        // Cola por máquina desde orden_planillas
        $cola = DB::table('orden_planillas')
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->join('maquinas', 'orden_planillas.maquina_id', '=', 'maquinas.id')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->where('planillas.revisada', 1)
            ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
            ->whereNull('planillas.deleted_at')
            ->orderBy('maquinas.nombre')
            ->orderBy('orden_planillas.posicion')
            ->select('planillas.*', 'clientes.empresa as cliente', 'maquinas.nombre as maquina', 'orden_planillas.posicion')
            ->get();

        if ($cola->isEmpty()) {
            return ['exito' => true, 'contenido' => 'No hay planillas en cola.', 'datos' => []];
        }

        // Stats de progreso
        $stats = DB::table('elementos')
            ->select('planilla_id', DB::raw('COUNT(*) as t'), DB::raw('SUM(elaborado) as f'))
            ->whereIn('planilla_id', $cola->pluck('id'))
            ->whereNull('deleted_at')
            ->groupBy('planilla_id')
            ->get()->keyBy('planilla_id');

        $porMaquina = $cola->groupBy('maquina');
        $totalPeso = $cola->sum('peso_total');

        $contenido = "### 📋 Cola de fabricación\n";
        $contenido .= "**{$cola->count()} planillas** en " . $porMaquina->count() . " máquinas (" . number_format($totalPeso, 0, ',', '.') . " kg)\n\n";

        foreach ($porMaquina as $maquina => $planillas) {
            $pesoMaq = $planillas->sum('peso_total');
            $contenido .= "**🏭 {$maquina}** ({$planillas->count()} planillas | " . number_format($pesoMaq, 0, ',', '.') . " kg)\n";
            $contenido .= "| Pos | Código | Cliente | Progreso | Peso |\n|-----|--------|---------|----------|------|\n";

            foreach ($planillas->take(5) as $p) {
                $s = $stats[$p->id] ?? null;
                $prog = $s ? round(($s->f / max($s->t, 1)) * 100) . "%" : '-';
                $cli = mb_substr($p->cliente ?? '-', 0, 12);
                $contenido .= "| {$p->posicion} | {$p->codigo} | {$cli} | {$prog} | " . number_format($p->peso_total ?? 0, 0) . " |\n";
            }
            if ($planillas->count() > 5) {
                $contenido .= "| | _...y " . ($planillas->count() - 5) . " más_ | | | |\n";
            }
            $contenido .= "\n";
        }

        return ['exito' => true, 'contenido' => $contenido, 'datos' => $cola->toArray()];
    }

    /**
     * Muestra qué planilla se está fabricando en una máquina específica
     * Lógica: De orden_planillas, buscar planillas revisadas ordenadas por posición ASC
     */
    protected function ejecutarProduccionMaquinaPlanilla(array $params): array
    {
        $nombreMaquina = $params['maquina'] ?? $params['nombre_maquina'] ?? $params['nombre'] ?? null;

        if (!$nombreMaquina) {
            // Si no se especifica máquina, listar todas con su planilla actual
            return $this->listarPlanillasActualesEnMaquinas();
        }

        // Buscar la máquina por nombre (búsqueda flexible)
        $maquina = DB::table('maquinas')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($nombreMaquina) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($nombreMaquina) . '%'])
                  ->orWhereRaw('LOWER(codigo) LIKE ?', ['%' . strtolower($nombreMaquina) . '%']);
            })
            ->first();

        if (!$maquina) {
            return [
                'exito' => false,
                'contenido' => "No encontré ninguna máquina con el nombre **{$nombreMaquina}**.\n\n" .
                              "Las máquinas disponibles son:\n" . $this->listarNombresMaquinas(),
                'datos' => [],
            ];
        }

        // Buscar la planilla actual: la de menor posición en orden_planillas que esté revisada
        $planillaActual = DB::table('orden_planillas')
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->leftJoin('obras', 'planillas.obra_id', '=', 'obras.id')
            ->where('orden_planillas.maquina_id', $maquina->id)
            ->where('planillas.revisada', 1)
            ->whereNull('planillas.deleted_at')
            ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
            ->orderBy('orden_planillas.posicion', 'asc')
            ->select(
                'planillas.*',
                'clientes.empresa as cliente_nombre',
                'obras.obra as obra_nombre',
                'orden_planillas.posicion'
            )
            ->first();

        if (!$planillaActual) {
            // Buscar si hay planillas asignadas pero no revisadas
            $sinRevisar = DB::table('orden_planillas')
                ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
                ->where('orden_planillas.maquina_id', $maquina->id)
                ->where('planillas.revisada', 0)
                ->whereNull('planillas.deleted_at')
                ->count();

            $msg = "No hay planillas **revisadas** en fabricación en **{$maquina->nombre}**.";
            if ($sinRevisar > 0) {
                $msg .= "\n\n⚠️ Hay **{$sinRevisar} planilla(s) sin revisar** asignadas a esta máquina.";
            }

            return [
                'exito' => true,
                'contenido' => $msg,
                'datos' => [],
                'navegacion' => '/produccion/maquinas/' . $maquina->id,
            ];
        }

        // Contar elementos pendientes y fabricados
        $elementos = DB::table('elementos')
            ->where('planilla_id', $planillaActual->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN elaborado = 1 THEN 1 ELSE 0 END) as fabricados,
                SUM(CASE WHEN elaborado = 0 THEN 1 ELSE 0 END) as pendientes,
                0 as asignados
            ")
            ->first();

        $progreso = $elementos->total > 0
            ? round(($elementos->fabricados / $elementos->total) * 100, 1)
            : 0;

        $contenido = "**Fabricando en {$maquina->nombre}:**\n\n";
        $contenido .= "📋 **Planilla:** {$planillaActual->codigo}\n";
        $contenido .= "🏢 **Cliente:** " . ($planillaActual->cliente_nombre ?? 'N/A') . "\n";
        $contenido .= "🏗️ **Obra:** " . ($planillaActual->obra_nombre ?? 'N/A') . "\n";
        $contenido .= "📊 **Estado:** {$planillaActual->estado}\n";
        $contenido .= "⚖️ **Peso total:** " . number_format($planillaActual->peso_total ?? 0, 0, ',', '.') . " kg\n\n";

        $contenido .= "**Progreso:** {$progreso}%\n";
        $contenido .= "- ✅ Fabricados: {$elementos->fabricados}\n";
        $contenido .= "- 🔄 Asignados: {$elementos->asignados}\n";
        $contenido .= "- ⏳ Pendientes: {$elementos->pendientes}\n";
        $contenido .= "- 📦 Total elementos: {$elementos->total}\n";

        // Ver si hay más planillas en cola
        $enCola = DB::table('orden_planillas')
            ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
            ->where('orden_planillas.maquina_id', $maquina->id)
            ->where('planillas.revisada', 1)
            ->whereNull('planillas.deleted_at')
            ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
            ->where('orden_planillas.posicion', '>', $planillaActual->posicion)
            ->count();

        if ($enCola > 0) {
            $contenido .= "\n📌 **{$enCola} planilla(s) más en cola** para esta máquina.";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [
                'planilla' => $planillaActual,
                'elementos' => $elementos,
                'maquina' => $maquina,
            ],
            'navegacion' => '/produccion/maquinas/' . $maquina->id,
        ];
    }

    /**
     * Lista las planillas actuales en todas las máquinas
     */
    protected function listarPlanillasActualesEnMaquinas(): array
    {
        $maquinas = DB::table('maquinas')
            ->whereNull('deleted_at')
            ->where('activa', 1)
            ->get();

        $contenido = "**Planillas en fabricación por máquina:**\n\n";
        $contenido .= "| Máquina | Planilla | Cliente | Progreso |\n";
        $contenido .= "|---------|----------|---------|----------|\n";

        foreach ($maquinas as $maquina) {
            $planilla = DB::table('orden_planillas')
                ->join('planillas', 'orden_planillas.planilla_id', '=', 'planillas.id')
                ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
                ->where('orden_planillas.maquina_id', $maquina->id)
                ->where('planillas.revisada', 1)
                ->whereNull('planillas.deleted_at')
                ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
                ->orderBy('orden_planillas.posicion', 'asc')
                ->select('planillas.codigo', 'clientes.empresa as cliente', 'planillas.id')
                ->first();

            if ($planilla) {
                $elementos = DB::table('elementos')
                    ->where('planilla_id', $planilla->id)
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN elaborado = 1 THEN 1 ELSE 0 END) as fabricados")
                    ->first();
                $progreso = $elementos->total > 0 ? round(($elementos->fabricados / $elementos->total) * 100) . '%' : '0%';
                $contenido .= "| {$maquina->nombre} | {$planilla->codigo} | " . ($planilla->cliente ?? 'N/A') . " | {$progreso} |\n";
            } else {
                $contenido .= "| {$maquina->nombre} | - | - | - |\n";
            }
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [],
        ];
    }

    /**
     * Helper: Lista los nombres de las máquinas disponibles
     */
    protected function listarNombresMaquinas(): string
    {
        $maquinas = DB::table('maquinas')
            ->whereNull('deleted_at')
            ->pluck('nombre');

        return $maquinas->map(fn($n) => "- {$n}")->implode("\n");
    }

    protected function ejecutarNavegar(array $params): array
    {
        $destino = $params['destino'] ?? $params['seccion'] ?? null;

        if (!$destino) {
            // Listar secciones disponibles
            $contenido = "**Secciones disponibles:**\n\n";
            foreach (self::RUTAS_NAVEGACION as $id => $info) {
                $contenido .= "- **{$info['nombre']}**: `{$info['ruta']}`\n";
            }
            $contenido .= "\nDime a qué sección quieres ir.";

            return [
                'exito' => true,
                'contenido' => $contenido,
            ];
        }

        // Buscar la ruta
        $destinoLower = strtolower($destino);
        $ruta = null;

        foreach (self::RUTAS_NAVEGACION as $id => $info) {
            if ($id === $destinoLower || strtolower($info['nombre']) === $destinoLower) {
                $ruta = $info;
                break;
            }
        }

        if (!$ruta) {
            return [
                'exito' => false,
                'contenido' => "No encontré la sección '{$destino}'. Usa `/navegar` para ver las secciones disponibles.",
            ];
        }

        return [
            'exito' => true,
            'contenido' => "Te llevo a **{$ruta['nombre']}**...",
            'navegacion' => $ruta['ruta'],
        ];
    }

    protected function ejecutarClienteListar(array $params): array
    {
        $busqueda = $params['busqueda'] ?? $params['nombre'] ?? null;

        $query = DB::table('clientes')
            ->select(
                'clientes.*',
                DB::raw('COUNT(DISTINCT obras.id) as obras'),
                DB::raw('COUNT(DISTINCT planillas.id) as planillas'),
                DB::raw('SUM(planillas.peso_total) as peso_total')
            )
            ->leftJoin('obras', function ($join) {
                $join->on('clientes.id', '=', 'obras.cliente_id')->whereNull('obras.deleted_at');
            })
            ->leftJoin('planillas', function ($join) {
                $join->on('clientes.id', '=', 'planillas.cliente_id')
                    ->whereNull('planillas.deleted_at')
                    ->whereIn('planillas.estado', ['pendiente', 'fabricando']);
            })
            ->whereNull('clientes.deleted_at')
            ->groupBy('clientes.id')
            ->orderByDesc('peso_total')
            ->limit(15);

        if ($busqueda) $query->where('clientes.empresa', 'LIKE', "%{$busqueda}%");

        $clientes = $query->get();

        if ($clientes->isEmpty()) {
            return ['exito' => true, 'contenido' => 'No se encontraron clientes.', 'datos' => []];
        }

        $totalPeso = $clientes->sum('peso_total');
        $totalPlanillas = $clientes->sum('planillas');

        $contenido = "### 👥 Clientes\n";
        $contenido .= "**{$clientes->count()} clientes** con {$totalPlanillas} planillas activas (" . number_format($totalPeso, 0, ',', '.') . " kg)\n\n";

        $contenido .= "| Cliente | Obras | Planillas | Peso pend |\n";
        $contenido .= "|---------|-------|-----------|----------|\n";

        foreach ($clientes as $c) {
            $nombre = mb_substr($c->empresa, 0, 20);
            $peso = $c->peso_total ? number_format($c->peso_total, 0, ',', '.') . " kg" : '-';
            $contenido .= "| {$nombre} | {$c->obras} | {$c->planillas} | {$peso} |\n";
        }

        return ['exito' => true, 'contenido' => $contenido, 'datos' => $clientes->toArray(), 'navegacion' => '/clientes'];
    }

    protected function ejecutarObraListar(array $params): array
    {
        $cliente = $params['cliente'] ?? null;

        $query = DB::table('obras')
            ->select(
                'obras.*',
                'clientes.empresa as cliente',
                DB::raw('COUNT(DISTINCT planillas.id) as planillas'),
                DB::raw('SUM(planillas.peso_total) as peso_total'),
                DB::raw('SUM(CASE WHEN planillas.estado IN ("pendiente","fabricando") THEN planillas.peso_total ELSE 0 END) as peso_pend')
            )
            ->leftJoin('clientes', 'obras.cliente_id', '=', 'clientes.id')
            ->leftJoin('planillas', function ($join) {
                $join->on('obras.id', '=', 'planillas.obra_id')->whereNull('planillas.deleted_at');
            })
            ->whereNull('obras.deleted_at')
            ->groupBy('obras.id')
            ->orderByDesc('peso_pend')
            ->limit(15);

        if ($cliente) $query->where('clientes.empresa', 'LIKE', "%{$cliente}%");

        $obras = $query->get();

        if ($obras->isEmpty()) {
            return ['exito' => true, 'contenido' => 'No se encontraron obras.', 'datos' => []];
        }

        $totalPeso = $obras->sum('peso_pend');
        $titulo = $cliente ? "Obras de {$cliente}" : "Obras";

        $contenido = "### 🏗️ {$titulo}\n";
        $contenido .= "**{$obras->count()} obras** con " . number_format($totalPeso, 0, ',', '.') . " kg pendientes\n\n";

        $contenido .= "| Obra | Cliente | Planillas | Pend |\n";
        $contenido .= "|------|---------|-----------|------|\n";

        foreach ($obras as $o) {
            $nombre = mb_substr($o->obra, 0, 18);
            $cli = mb_substr($o->cliente ?? '-', 0, 12);
            $peso = $o->peso_pend ? number_format($o->peso_pend, 0) . "kg" : '-';
            $contenido .= "| {$nombre} | {$cli} | {$o->planillas} | {$peso} |\n";
        }

        return ['exito' => true, 'contenido' => $contenido, 'datos' => $obras->toArray(), 'navegacion' => '/obras'];
    }

    protected function ejecutarAlertaEnviar(array $params): array
    {
        $mensaje = $params['mensaje'] ?? $params['mensaje_alerta'] ?? null;
        $destinatarios = $params['destinatarios'] ?? null;

        if (!$mensaje) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el mensaje de la alerta. ¿Qué quieres comunicar?',
            ];
        }

        // Delegar a AccionService para ejecución con auditoría
        $resultado = $this->accionService->ejecutarAccion(
            'enviar_alerta',
            [
                'destinatarios' => $destinatarios ? (is_array($destinatarios) ? $destinatarios : [$destinatarios]) : [$this->user->name],
                'mensaje_alerta' => $mensaje,
            ],
            $this->user
        );

        if ($resultado['success']) {
            return [
                'exito' => true,
                'contenido' => "✅ " . $resultado['mensaje'],
                'datos' => $resultado,
            ];
        }

        return [
            'exito' => false,
            'contenido' => $resultado['error'] ?? 'Error al enviar alerta',
        ];
    }

    protected function ejecutarReporteGenerar(array $params): array
    {
        $tipo = $params['tipo'] ?? 'stock';

        return [
            'exito' => true,
            'contenido' => "Para generar un reporte de **{$tipo}**, usa el comando:\n\n`Genera un informe de {$tipo}`\n\nO ve a la sección de Informes.",
            'navegacion' => '/asistente',
        ];
    }

    // Métodos de simulación para confirmaciones
    protected function simularCambioEstadoPlanilla(array $params): array
    {
        $codigo = $params['codigo'] ?? 'desconocido';
        $estado = $params['estado'] ?? 'desconocido';

        return [
            'descripcion' => "Cambiar estado de planilla {$codigo} a {$estado}",
            'cambios' => [
                "La planilla pasará al estado '{$estado}'",
                "Se actualizará la fecha de modificación",
            ],
        ];
    }

    protected function simularCambioEstadoElemento(array $params): array
    {
        return [
            'descripcion' => "Cambiar estado de elementos",
            'cambios' => [
                "Los elementos seleccionados cambiarán de estado",
            ],
        ];
    }

    protected function simularMovimientoStock(array $params): array
    {
        return [
            'descripcion' => "Mover stock entre naves",
            'cambios' => [
                "Se reducirá stock en la nave origen",
                "Se aumentará stock en la nave destino",
                "Se registrará el movimiento",
            ],
            'advertencia' => 'Esta acción afecta al inventario real',
        ];
    }

    protected function simularRecepcion(array $params): array
    {
        return [
            'descripcion' => "Recepcionar material de pedido",
            'cambios' => [
                "Se actualizará la cantidad recepcionada",
                "Se añadirá stock al almacén",
            ],
        ];
    }

    /**
     * Mueve una planilla a una nueva posición en la cola de una máquina
     */
    protected function ejecutarPlanillaAdelantar(array $params): array
    {
        // Parámetros: maquina, planilla (código o posición actual), nueva_posicion
        $maquinaNombre = $params['maquina'] ?? null;
        $planillaRef = $params['planilla'] ?? $params['codigo'] ?? $params['posicion_actual'] ?? null;
        $nuevaPosicion = (int) ($params['nueva_posicion'] ?? $params['posicion'] ?? 1);

        // También puede venir como "intercambiar posicion X con Y"
        $posicionOrigen = $params['posicion_origen'] ?? null;
        $posicionDestino = $params['posicion_destino'] ?? $nuevaPosicion;

        if (!$maquinaNombre) {
            return [
                'exito' => false,
                'contenido' => 'Necesito saber en qué máquina quieres mover la planilla.',
            ];
        }

        // Buscar máquina
        $maquina = DB::table('maquinas')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($maquinaNombre) {
                $q->where('nombre', 'LIKE', "%{$maquinaNombre}%")
                  ->orWhere('codigo', 'LIKE', "%{$maquinaNombre}%");
            })
            ->first();

        if (!$maquina) {
            return [
                'exito' => false,
                'contenido' => "No encontré la máquina '{$maquinaNombre}'.",
            ];
        }

        // Si nos dan posiciones directas para intercambiar
        if ($posicionOrigen && $posicionDestino) {
            return $this->intercambiarPosiciones($maquina, (int) $posicionOrigen, (int) $posicionDestino);
        }

        // Buscar la planilla por código o por posición
        $ordenPlanilla = null;

        // Si es numérico y pequeño, probablemente es una posición
        if (is_numeric($planillaRef) && (int) $planillaRef <= 50) {
            $ordenPlanilla = DB::table('orden_planillas')
                ->where('maquina_id', $maquina->id)
                ->where('posicion', (int) $planillaRef)
                ->first();
        }

        // Si no encontramos por posición, buscar por código de planilla
        if (!$ordenPlanilla && $planillaRef) {
            $planilla = DB::table('planillas')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($planillaRef) {
                    $q->where('codigo', 'LIKE', "%{$planillaRef}%")
                      ->orWhere('numero_planilla', 'LIKE', "%{$planillaRef}%");
                })
                ->first();

            if ($planilla) {
                $ordenPlanilla = DB::table('orden_planillas')
                    ->where('maquina_id', $maquina->id)
                    ->where('planilla_id', $planilla->id)
                    ->first();
            }
        }

        if (!$ordenPlanilla) {
            return [
                'exito' => false,
                'contenido' => "No encontré la planilla '{$planillaRef}' en la cola de **{$maquina->nombre}**.",
            ];
        }

        $posActual = (int) $ordenPlanilla->posicion;
        $posNueva = (int) $posicionDestino;

        if ($posNueva === $posActual) {
            return [
                'exito' => true,
                'contenido' => "La planilla ya está en la posición {$posActual}.",
            ];
        }

        // Obtener máxima posición
        $maxPos = (int) DB::table('orden_planillas')
            ->where('maquina_id', $maquina->id)
            ->max('posicion');

        if ($posNueva < 1) $posNueva = 1;
        if ($posNueva > $maxPos) $posNueva = $maxPos;

        // Ejecutar reordenamiento
        DB::transaction(function () use ($maquina, $ordenPlanilla, $posActual, $posNueva) {
            if ($posNueva < $posActual) {
                // Mover hacia arriba: incrementar posiciones intermedias
                DB::statement(
                    'UPDATE orden_planillas SET posicion = posicion + 1 WHERE maquina_id = ? AND posicion >= ? AND posicion < ?',
                    [$maquina->id, $posNueva, $posActual]
                );
            } else {
                // Mover hacia abajo: decrementar posiciones intermedias
                DB::statement(
                    'UPDATE orden_planillas SET posicion = posicion - 1 WHERE maquina_id = ? AND posicion > ? AND posicion <= ?',
                    [$maquina->id, $posActual, $posNueva]
                );
            }

            DB::table('orden_planillas')
                ->where('id', $ordenPlanilla->id)
                ->update(['posicion' => $posNueva]);
        }, 3);

        // Obtener código de planilla para el mensaje
        $planillaInfo = DB::table('planillas')->find($ordenPlanilla->planilla_id);
        $codigoPlanilla = $planillaInfo->codigo ?? "ID:{$ordenPlanilla->planilla_id}";

        return [
            'exito' => true,
            'contenido' => "Planilla **{$codigoPlanilla}** movida de posición **{$posActual}** a **{$posNueva}** en **{$maquina->nombre}**.",
            'datos' => [
                'planilla' => $codigoPlanilla,
                'maquina' => $maquina->nombre,
                'posicion_anterior' => $posActual,
                'posicion_nueva' => $posNueva,
            ],
            'navegacion' => '/maquinas/' . $maquina->id,
        ];
    }

    /**
     * Intercambia dos posiciones directamente
     */
    protected function intercambiarPosiciones($maquina, int $pos1, int $pos2): array
    {
        $orden1 = DB::table('orden_planillas')
            ->where('maquina_id', $maquina->id)
            ->where('posicion', $pos1)
            ->first();

        $orden2 = DB::table('orden_planillas')
            ->where('maquina_id', $maquina->id)
            ->where('posicion', $pos2)
            ->first();

        if (!$orden1 || !$orden2) {
            $faltante = !$orden1 ? $pos1 : $pos2;
            return [
                'exito' => false,
                'contenido' => "No hay planilla en la posición **{$faltante}** de **{$maquina->nombre}**.",
            ];
        }

        // Intercambiar posiciones
        DB::transaction(function () use ($orden1, $orden2, $pos1, $pos2) {
            // Usar posición temporal para evitar conflictos de unique
            DB::table('orden_planillas')->where('id', $orden1->id)->update(['posicion' => -1]);
            DB::table('orden_planillas')->where('id', $orden2->id)->update(['posicion' => $pos1]);
            DB::table('orden_planillas')->where('id', $orden1->id)->update(['posicion' => $pos2]);
        }, 3);

        // Obtener códigos de planillas
        $planilla1 = DB::table('planillas')->find($orden1->planilla_id);
        $planilla2 = DB::table('planillas')->find($orden2->planilla_id);

        return [
            'exito' => true,
            'contenido' => "Intercambiadas las posiciones en **{$maquina->nombre}**:\n" .
                "- **{$planilla1->codigo}**: {$pos1} → {$pos2}\n" .
                "- **{$planilla2->codigo}**: {$pos2} → {$pos1}",
            'datos' => [
                'maquina' => $maquina->nombre,
                'intercambio' => [
                    ['planilla' => $planilla1->codigo, 'de' => $pos1, 'a' => $pos2],
                    ['planilla' => $planilla2->codigo, 'de' => $pos2, 'a' => $pos1],
                ],
            ],
            'navegacion' => '/maquinas/' . $maquina->id,
        ];
    }

    protected function ejecutarPlanillaAsignarMaquina(array $params): array
    {
        $codigo = $params['codigo'] ?? $params['planilla'] ?? null;
        $maquina = $params['maquina'] ?? null;

        if (!$codigo || !$maquina) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el código de la planilla y el nombre de la máquina.',
            ];
        }

        // Delegar a AccionService
        $resultado = $this->accionService->ejecutarAccion(
            'asignar_maquina',
            ['codigo_planilla' => $codigo, 'maquina' => $maquina],
            $this->user
        );

        if ($resultado['success']) {
            return [
                'exito' => true,
                'contenido' => "✅ " . $resultado['mensaje'],
                'datos' => $resultado,
            ];
        }

        return [
            'exito' => false,
            'contenido' => $resultado['error'] ?? 'Error al asignar máquina',
        ];
    }

    protected function ejecutarPlanillaCambiarPrioridad(array $params): array
    {
        $codigo = $params['codigo'] ?? $params['planilla'] ?? null;
        $prioridad = $params['prioridad'] ?? null;

        if (!$codigo || !$prioridad) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el código de la planilla y la nueva prioridad (alta, normal, baja).',
            ];
        }

        // Delegar a AccionService
        $resultado = $this->accionService->ejecutarAccion(
            'cambiar_prioridad',
            ['codigo_planilla' => $codigo, 'prioridad' => $prioridad],
            $this->user
        );

        if ($resultado['success']) {
            return [
                'exito' => true,
                'contenido' => "✅ " . $resultado['mensaje'],
                'datos' => $resultado,
            ];
        }

        return [
            'exito' => false,
            'contenido' => $resultado['error'] ?? 'Error al cambiar prioridad',
        ];
    }

    protected function ejecutarElementoListar(array $params): array
    {
        $planillaCodigo = $params['codigo'] ?? $params['planilla'] ?? null;
        if (!$planillaCodigo) {
            return ['exito' => false, 'contenido' => 'Necesito el código de la planilla.'];
        }

        $planilla = DB::table('planillas')->where('codigo', 'LIKE', "%{$planillaCodigo}%")->first();
        if (!$planilla) {
            return ['exito' => false, 'contenido' => "No encontré planilla '{$planillaCodigo}'."];
        }

        // Stats por diámetro y estado
        $porDiametro = DB::table('elementos')
            ->select(
                'diametro',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(elaborado) as fab'),
                DB::raw('SUM(peso) as peso'),
                DB::raw('SUM(CASE WHEN elaborado = 0 THEN peso ELSE 0 END) as peso_pend')
            )
            ->where('planilla_id', $planilla->id)
            ->whereNull('deleted_at')
            ->groupBy('diametro')
            ->orderBy('diametro')
            ->get();

        // Stats por máquina
        $porMaquina = DB::table('elementos')
            ->select('maquinas.nombre', DB::raw('COUNT(*) as total'), DB::raw('SUM(elementos.elaborado) as fab'))
            ->leftJoin('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->where('elementos.planilla_id', $planilla->id)
            ->whereNull('elementos.deleted_at')
            ->groupBy('maquinas.id', 'maquinas.nombre')
            ->get();

        $totalElem = $porDiametro->sum('total');
        $totalFab = $porDiametro->sum('fab');
        $totalPeso = $porDiametro->sum('peso');
        $pesoPend = $porDiametro->sum('peso_pend');
        $progreso = $totalElem > 0 ? round(($totalFab / $totalElem) * 100) : 0;

        $contenido = "### 📦 Elementos de {$planilla->codigo}\n";
        $contenido .= "**{$totalFab}/{$totalElem}** elementos ({$progreso}%) | ";
        $contenido .= "✅ " . number_format($totalPeso - $pesoPend, 0, ',', '.') . " kg fab | ";
        $contenido .= "⏳ " . number_format($pesoPend, 0, ',', '.') . " kg pend\n\n";

        // Por diámetro
        $contenido .= "**Por diámetro:**\n| Ø | Fab/Total | Peso pend |\n|---|-----------|----------|\n";
        foreach ($porDiametro as $d) {
            $pct = $d->total > 0 ? round(($d->fab / $d->total) * 100) : 0;
            $contenido .= "| Ø{$d->diametro} | {$d->fab}/{$d->total} ({$pct}%) | " . number_format($d->peso_pend, 0) . " kg |\n";
        }

        // Por máquina si hay varias
        if ($porMaquina->count() > 1) {
            $contenido .= "\n**Por máquina:** ";
            $contenido .= $porMaquina->map(fn($m) => ($m->nombre ?? 'Sin asignar') . ":{$m->fab}/{$m->total}")->implode(' | ');
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => ['planilla' => $planilla, 'por_diametro' => $porDiametro],
            'navegacion' => "/planillas/{$planilla->id}",
        ];
    }

    protected function ejecutarElementoCambiarEstado(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    protected function ejecutarPedidoListar(array $params): array
    {
        $estado = $params['estado'] ?? null;

        $query = DB::table('pedidos')
            ->select('pedidos.*', 'proveedores.nombre as proveedor')
            ->leftJoin('proveedores', 'pedidos.proveedor_id', '=', 'proveedores.id')
            ->whereNull('pedidos.deleted_at')
            ->orderByDesc('pedidos.created_at')
            ->limit(15);

        if ($estado) $query->where('pedidos.estado', $estado);

        $pedidos = $query->get();

        if ($pedidos->isEmpty()) {
            return ['exito' => true, 'contenido' => 'No hay pedidos.', 'datos' => []];
        }

        // Líneas por pedido
        $lineas = DB::table('lineas_pedido')
            ->select('pedido_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(cantidad) as cant'), DB::raw('SUM(cantidad_recibida) as recib'))
            ->whereIn('pedido_id', $pedidos->pluck('id'))
            ->groupBy('pedido_id')
            ->get()->keyBy('pedido_id');

        $porEstado = $pedidos->groupBy('estado');
        $resumen = $porEstado->map(fn($g, $e) => "{$e}:{$g->count()}")->implode(' | ');

        $contenido = "### 📦 Pedidos recientes\n";
        $contenido .= "**{$pedidos->count()} pedidos** → {$resumen}\n\n";

        $contenido .= "| Código | Proveedor | Estado | Recepción | Fecha |\n";
        $contenido .= "|--------|-----------|--------|-----------|-------|\n";

        foreach ($pedidos as $p) {
            $l = $lineas[$p->id] ?? null;
            $recep = '-';
            if ($l && $l->cant > 0) {
                $pct = round(($l->recib / $l->cant) * 100);
                $recep = "{$pct}%";
            }
            $prov = mb_substr($p->proveedor ?? '-', 0, 15);
            $fecha = \Carbon\Carbon::parse($p->created_at)->format('d/m');
            $contenido .= "| {$p->codigo} | {$prov} | {$p->estado} | {$recep} | {$fecha} |\n";
        }

        // Pedidos pendientes de recibir
        $pendientesRecibir = $pedidos->where('estado', 'pendiente')->count();
        if ($pendientesRecibir > 0) {
            $contenido .= "\n⏳ **{$pendientesRecibir} pedido(s) pendientes de recibir**";
        }

        return ['exito' => true, 'contenido' => $contenido, 'datos' => $pedidos->toArray(), 'navegacion' => '/pedidos'];
    }

    protected function ejecutarPedidoVer(array $params): array
    {
        $codigo = $params['codigo'] ?? $params['pedido'] ?? null;

        if (!$codigo) {
            return ['exito' => false, 'contenido' => 'Necesito el código del pedido.'];
        }

        $pedido = DB::table('pedidos')
            ->select('pedidos.*', 'proveedores.nombre as proveedor')
            ->leftJoin('proveedores', 'pedidos.proveedor_id', '=', 'proveedores.id')
            ->where('pedidos.codigo', 'LIKE', "%{$codigo}%")
            ->whereNull('pedidos.deleted_at')
            ->first();

        if (!$pedido) {
            return ['exito' => false, 'contenido' => "No encontré pedido '{$codigo}'."];
        }

        // Líneas del pedido
        $lineas = DB::table('lineas_pedido')
            ->select('lineas_pedido.*', 'productos_base.diametro', 'productos_base.tipo')
            ->leftJoin('productos_base', 'lineas_pedido.producto_base_id', '=', 'productos_base.id')
            ->where('lineas_pedido.pedido_id', $pedido->id)
            ->orderBy('productos_base.diametro')
            ->get();

        $totalCant = $lineas->sum('cantidad');
        $totalRecib = $lineas->sum('cantidad_recibida');
        $progreso = $totalCant > 0 ? round(($totalRecib / $totalCant) * 100) : 0;

        $contenido = "### 📦 Pedido {$pedido->codigo}\n";
        $contenido .= "**Proveedor:** " . ($pedido->proveedor ?? 'N/A') . "\n";
        $contenido .= "**Estado:** {$pedido->estado} | **Recepción:** {$progreso}%\n\n";

        if ($pedido->fecha_pedido) {
            $contenido .= "📅 Pedido: " . \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y') . "\n";
        }
        if ($pedido->fecha_entrega_prevista) {
            $fechaEntrega = \Carbon\Carbon::parse($pedido->fecha_entrega_prevista);
            $diasRestantes = now()->diffInDays($fechaEntrega, false);
            $urgencia = $diasRestantes < 0 ? "⚠️ VENCIDO" : "{$diasRestantes}d";
            $contenido .= "📅 Entrega prevista: {$fechaEntrega->format('d/m/Y')} ({$urgencia})\n";
        }

        if ($lineas->isNotEmpty()) {
            $contenido .= "\n**Líneas del pedido:**\n";
            $contenido .= "| Ø | Tipo | Pedido | Recibido | % |\n";
            $contenido .= "|---|------|--------|----------|---|\n";

            foreach ($lineas as $l) {
                $pct = $l->cantidad > 0 ? round(($l->cantidad_recibida / $l->cantidad) * 100) : 0;
                $estado = $pct >= 100 ? "✅" : ($pct > 0 ? "🔄" : "⏳");
                $contenido .= "| Ø{$l->diametro} | {$l->tipo} | " . number_format($l->cantidad, 0, ',', '.') . " | " . number_format($l->cantidad_recibida, 0, ',', '.') . " | {$pct}% {$estado} |\n";
            }
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => ['pedido' => $pedido, 'lineas' => $lineas],
            'navegacion' => "/pedidos/{$pedido->id}",
        ];
    }

    protected function ejecutarPedidoRecepcionar(array $params): array
    {
        $codigo = $params['codigo'] ?? $params['pedido'] ?? null;
        $cantidad = $params['cantidad'] ?? null;
        $diametro = $params['diametro'] ?? null;

        if (!$codigo) {
            return ['exito' => false, 'contenido' => 'Necesito el código del pedido a recepcionar.'];
        }

        $pedido = DB::table('pedidos')
            ->where('codigo', 'LIKE', "%{$codigo}%")
            ->whereNull('deleted_at')
            ->first();

        if (!$pedido) {
            return ['exito' => false, 'contenido' => "No encontré pedido '{$codigo}'."];
        }

        // Si no se especifica cantidad, mostrar lo pendiente de recepcionar
        if (!$cantidad) {
            $lineasPendientes = DB::table('lineas_pedido')
                ->select('lineas_pedido.*', 'productos_base.diametro', 'productos_base.tipo')
                ->leftJoin('productos_base', 'lineas_pedido.producto_base_id', '=', 'productos_base.id')
                ->where('lineas_pedido.pedido_id', $pedido->id)
                ->whereRaw('lineas_pedido.cantidad_recibida < lineas_pedido.cantidad')
                ->get();

            if ($lineasPendientes->isEmpty()) {
                return [
                    'exito' => true,
                    'contenido' => "El pedido **{$pedido->codigo}** ya está completamente recepcionado.",
                ];
            }

            $contenido = "**Líneas pendientes de recepcionar en {$pedido->codigo}:**\n\n";
            $contenido .= "| Ø | Tipo | Pendiente |\n|---|------|----------|\n";

            foreach ($lineasPendientes as $l) {
                $pendiente = $l->cantidad - $l->cantidad_recibida;
                $contenido .= "| Ø{$l->diametro} | {$l->tipo} | " . number_format($pendiente, 0, ',', '.') . " kg |\n";
            }

            $contenido .= "\nPara recepcionar, indica la cantidad y opcionalmente el diámetro.";
            $contenido .= "\nEjemplo: *recepciona 5000 kg del diámetro 12*";

            return ['exito' => true, 'contenido' => $contenido, 'datos' => $lineasPendientes];
        }

        // Si se especifica cantidad, buscar la línea correspondiente
        $query = DB::table('lineas_pedido')
            ->select('lineas_pedido.*', 'productos_base.diametro')
            ->leftJoin('productos_base', 'lineas_pedido.producto_base_id', '=', 'productos_base.id')
            ->where('lineas_pedido.pedido_id', $pedido->id)
            ->whereRaw('lineas_pedido.cantidad_recibida < lineas_pedido.cantidad');

        if ($diametro) {
            $query->where('productos_base.diametro', $diametro);
        }

        $linea = $query->first();

        if (!$linea) {
            return ['exito' => false, 'contenido' => 'No hay líneas pendientes de recepcionar con esos criterios.'];
        }

        $pendiente = $linea->cantidad - $linea->cantidad_recibida;
        $cantidadRecepcionar = min($cantidad, $pendiente);

        // Actualizar la línea
        DB::table('lineas_pedido')
            ->where('id', $linea->id)
            ->update([
                'cantidad_recibida' => $linea->cantidad_recibida + $cantidadRecepcionar,
                'updated_at' => now(),
            ]);

        // Verificar si el pedido está completo
        $pendienteTotal = DB::table('lineas_pedido')
            ->where('pedido_id', $pedido->id)
            ->whereRaw('cantidad_recibida < cantidad')
            ->count();

        if ($pendienteTotal === 0) {
            DB::table('pedidos')
                ->where('id', $pedido->id)
                ->update(['estado' => 'completado', 'updated_at' => now()]);
        }

        $contenido = "✅ Recepcionados **" . number_format($cantidadRecepcionar, 0, ',', '.') . " kg** de Ø{$linea->diametro}";
        if ($pendienteTotal === 0) {
            $contenido .= "\n\n🎉 **Pedido completado**";
        } else {
            $contenido .= "\n\n📦 Quedan **{$pendienteTotal} línea(s)** pendientes";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [
                'cantidad_recepcionada' => $cantidadRecepcionar,
                'linea_id' => $linea->id,
                'pedido_completado' => $pendienteTotal === 0,
            ],
        ];
    }

    protected function ejecutarStockMover(array $params): array
    {
        $diametro = $params['diametro'] ?? null;
        $cantidad = $params['cantidad'] ?? null;
        $naveOrigen = $params['nave_origen'] ?? $params['origen'] ?? null;
        $naveDestino = $params['nave_destino'] ?? $params['destino'] ?? null;

        // Validar parámetros
        if (!$diametro || !$cantidad || !$naveOrigen || !$naveDestino) {
            $faltantes = [];
            if (!$diametro) $faltantes[] = 'diámetro';
            if (!$cantidad) $faltantes[] = 'cantidad';
            if (!$naveOrigen) $faltantes[] = 'nave origen';
            if (!$naveDestino) $faltantes[] = 'nave destino';

            return [
                'exito' => false,
                'contenido' => "Faltan datos para mover stock: " . implode(', ', $faltantes) . ".\n\nEjemplo: *mueve 5000 kg de Ø12 de nave 1 a nave 2*",
            ];
        }

        // Buscar naves
        $origen = DB::table('naves')
            ->where(function ($q) use ($naveOrigen) {
                $q->where('nombre', 'LIKE', "%{$naveOrigen}%")
                  ->orWhere('codigo', 'LIKE', "%{$naveOrigen}%");
            })
            ->first();

        $destino = DB::table('naves')
            ->where(function ($q) use ($naveDestino) {
                $q->where('nombre', 'LIKE', "%{$naveDestino}%")
                  ->orWhere('codigo', 'LIKE', "%{$naveDestino}%");
            })
            ->first();

        if (!$origen) {
            return ['exito' => false, 'contenido' => "No encontré la nave origen '{$naveOrigen}'."];
        }
        if (!$destino) {
            return ['exito' => false, 'contenido' => "No encontré la nave destino '{$naveDestino}'."];
        }

        // Buscar stock disponible en origen
        $stockOrigen = DB::table('stock')
            ->select('stock.*', 'productos_base.diametro')
            ->leftJoin('productos_base', 'stock.producto_base_id', '=', 'productos_base.id')
            ->where('stock.nave_id', $origen->id)
            ->where('productos_base.diametro', $diametro)
            ->where('stock.cantidad', '>', 0)
            ->first();

        if (!$stockOrigen) {
            return [
                'exito' => false,
                'contenido' => "No hay stock de Ø{$diametro} en {$origen->nombre}.",
            ];
        }

        if ($stockOrigen->cantidad < $cantidad) {
            return [
                'exito' => false,
                'contenido' => "Solo hay " . number_format($stockOrigen->cantidad, 0, ',', '.') . " kg de Ø{$diametro} en {$origen->nombre}. No es posible mover " . number_format($cantidad, 0, ',', '.') . " kg.",
            ];
        }

        // Reducir stock en origen
        DB::table('stock')
            ->where('id', $stockOrigen->id)
            ->decrement('cantidad', $cantidad);

        // Aumentar stock en destino (o crear si no existe)
        $stockDestino = DB::table('stock')
            ->where('nave_id', $destino->id)
            ->where('producto_base_id', $stockOrigen->producto_base_id)
            ->first();

        if ($stockDestino) {
            DB::table('stock')
                ->where('id', $stockDestino->id)
                ->increment('cantidad', $cantidad);
        } else {
            DB::table('stock')->insert([
                'nave_id' => $destino->id,
                'producto_base_id' => $stockOrigen->producto_base_id,
                'cantidad' => $cantidad,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Registrar movimiento
        DB::table('movimientos_stock')->insert([
            'tipo' => 'transferencia',
            'producto_base_id' => $stockOrigen->producto_base_id,
            'cantidad' => $cantidad,
            'nave_origen_id' => $origen->id,
            'nave_destino_id' => $destino->id,
            'user_id' => $this->user->id,
            'motivo' => 'Transferencia via asistente virtual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nuevoStockOrigen = $stockOrigen->cantidad - $cantidad;

        return [
            'exito' => true,
            'contenido' => "✅ Movidos **" . number_format($cantidad, 0, ',', '.') . " kg** de Ø{$diametro}:\n" .
                "- **{$origen->nombre}** → " . number_format($nuevoStockOrigen, 0, ',', '.') . " kg restantes\n" .
                "- **{$destino->nombre}** ← +" . number_format($cantidad, 0, ',', '.') . " kg",
            'datos' => [
                'cantidad' => $cantidad,
                'diametro' => $diametro,
                'nave_origen' => $origen->nombre,
                'nave_destino' => $destino->nombre,
            ],
        ];
    }

    protected function ejecutarStockAjustar(array $params): array
    {
        $diametro = $params['diametro'] ?? null;
        $cantidad = $params['cantidad'] ?? null;
        $nave = $params['nave'] ?? null;
        $tipo = $params['tipo'] ?? 'entrada'; // entrada o salida
        $motivo = $params['motivo'] ?? 'Ajuste de inventario';

        // Validar parámetros
        if (!$diametro || $cantidad === null || !$nave) {
            return [
                'exito' => false,
                'contenido' => "Necesito: diámetro, cantidad y nave para ajustar stock.\n\nEjemplo: *ajusta +2000 kg de Ø12 en nave 1*",
            ];
        }

        // Buscar nave
        $naveObj = DB::table('naves')
            ->where(function ($q) use ($nave) {
                $q->where('nombre', 'LIKE', "%{$nave}%")
                  ->orWhere('codigo', 'LIKE', "%{$nave}%");
            })
            ->first();

        if (!$naveObj) {
            return ['exito' => false, 'contenido' => "No encontré la nave '{$nave}'."];
        }

        // Buscar producto base
        $productoBase = DB::table('productos_base')
            ->where('diametro', $diametro)
            ->first();

        if (!$productoBase) {
            return ['exito' => false, 'contenido' => "No encontré producto con diámetro Ø{$diametro}."];
        }

        // Determinar si es entrada o salida
        $esEntrada = $cantidad >= 0 || $tipo === 'entrada';
        $cantidadAbs = abs($cantidad);

        // Buscar stock actual
        $stockActual = DB::table('stock')
            ->where('nave_id', $naveObj->id)
            ->where('producto_base_id', $productoBase->id)
            ->first();

        $stockAnterior = $stockActual->cantidad ?? 0;

        if (!$esEntrada && $stockAnterior < $cantidadAbs) {
            return [
                'exito' => false,
                'contenido' => "No hay suficiente stock. Actual: " . number_format($stockAnterior, 0, ',', '.') . " kg",
            ];
        }

        // Realizar ajuste
        if ($stockActual) {
            $nuevoStock = $esEntrada ? $stockAnterior + $cantidadAbs : $stockAnterior - $cantidadAbs;
            DB::table('stock')
                ->where('id', $stockActual->id)
                ->update(['cantidad' => $nuevoStock, 'updated_at' => now()]);
        } else {
            $nuevoStock = $cantidadAbs;
            DB::table('stock')->insert([
                'nave_id' => $naveObj->id,
                'producto_base_id' => $productoBase->id,
                'cantidad' => $nuevoStock,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Registrar movimiento
        DB::table('movimientos_stock')->insert([
            'tipo' => 'ajuste',
            'producto_base_id' => $productoBase->id,
            'cantidad' => $esEntrada ? $cantidadAbs : -$cantidadAbs,
            'nave_destino_id' => $naveObj->id,
            'user_id' => $this->user->id,
            'motivo' => $motivo . ' (via asistente)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $signo = $esEntrada ? '+' : '-';
        return [
            'exito' => true,
            'contenido' => "✅ Ajuste de stock realizado:\n" .
                "- **Ø{$diametro}** en **{$naveObj->nombre}**\n" .
                "- Anterior: " . number_format($stockAnterior, 0, ',', '.') . " kg\n" .
                "- Ajuste: {$signo}" . number_format($cantidadAbs, 0, ',', '.') . " kg\n" .
                "- **Nuevo:** " . number_format($nuevoStock, 0, ',', '.') . " kg",
            'datos' => [
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevoStock,
                'ajuste' => $esEntrada ? $cantidadAbs : -$cantidadAbs,
            ],
        ];
    }

    /**
     * Construye el prompt para análisis de intención
     */
    protected function construirPromptIntencion(string $mensaje): string
    {
        return "Analiza la intención del usuario: {$mensaje}";
    }
}
