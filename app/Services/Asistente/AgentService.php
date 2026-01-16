<?php

namespace App\Services\Asistente;

use App\Models\User;
use Illuminate\Support\Facades\Log;
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
    protected User $user;
    protected array $herramientas = [];
    protected array $historialAcciones = [];

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

        // === CORRECCIONES ===
        'correccion_revertir' => [
            'nombre' => 'Revertir cambio',
            'descripcion' => 'Revierte un cambio reciente (estado, asignación, recepción)',
            'categoria' => 'correcciones',
            'nivel_permiso' => 2,
            'requiere_confirmacion' => true,
        ],
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
        // 1. Analizar el mensaje con IA para entender la intención
        $analisis = $this->analizarIntencion($mensaje);

        Log::debug('AgentService: Análisis de intención', [
            'mensaje' => substr($mensaje, 0, 100),
            'herramienta' => $analisis['herramienta'] ?? 'ninguna',
            'confianza' => $analisis['confianza'] ?? 0,
        ]);

        // 2. Si no se detectó ninguna herramienta, devolver respuesta genérica
        if (empty($analisis['herramienta']) || $analisis['herramienta'] === 'ninguna') {
            return [
                'tipo' => 'respuesta',
                'contenido' => $analisis['respuesta_sugerida'] ?? 'No entendí qué acción quieres realizar. ¿Puedes ser más específico?',
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
     * Analiza la intención del usuario usando IA
     */
    protected function analizarIntencion(string $mensaje): array
    {
        $prompt = $this->construirPromptIntencion($mensaje);

        try {
            // Usar el método de análisis de la IA
            $respuestaIA = $this->iaService->analizarProblema($mensaje);

            // Si es un problema/error, delegar al DiagnosticoService
            if ($respuestaIA['es_problema'] ?? false) {
                return [
                    'tipo' => 'diagnostico',
                    'herramienta' => 'correccion_revertir',
                    'parametros' => $respuestaIA,
                    'confianza' => $respuestaIA['confianza'] ?? 70,
                ];
            }

            // Hacer una segunda llamada para detectar herramientas específicas
            return $this->detectarHerramienta($mensaje);

        } catch (\Exception $e) {
            Log::error('AgentService: Error analizando intención', ['error' => $e->getMessage()]);
            return [
                'herramienta' => null,
                'respuesta_sugerida' => 'Hubo un error procesando tu solicitud. ¿Puedes intentarlo de nuevo?',
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
Analiza el siguiente mensaje de un usuario de un sistema de gestión de ferralla y determina qué herramienta debe usarse.

## HERRAMIENTAS DISPONIBLES
{$herramientasJson}

## MENSAJE DEL USUARIO
"{$mensaje}"

## RESPUESTA
Responde ÚNICAMENTE con un JSON válido:
```json
{
  "herramienta": "id_de_la_herramienta o 'ninguna' si no aplica",
  "confianza": 0-100,
  "parametros": {
    "codigo": "si menciona un código específico",
    "cantidad": "si menciona una cantidad",
    "estado": "si menciona un estado",
    "destino": "si menciona una nave/destino",
    "otros": "cualquier otro parámetro relevante"
  },
  "respuesta_sugerida": "Si no hay herramienta, qué responder al usuario"
}
```
PROMPT;

        try {
            $response = $this->llamarIA($prompt);
            return $this->parsearRespuestaIA($response);
        } catch (\Exception $e) {
            Log::error('AgentService: Error detectando herramienta', ['error' => $e->getMessage()]);
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

                // Producción
                'produccion_resumen' => $this->ejecutarProduccionResumen($parametros),
                'produccion_maquinas' => $this->ejecutarProduccionMaquinas($parametros),
                'produccion_cola' => $this->ejecutarProduccionCola($parametros),

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
            Log::error("AgentService: Error ejecutando {$herramienta}", [
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
     * Registra una acción en el historial
     */
    protected function registrarAccion(string $herramienta, array $parametros, array $resultado): void
    {
        try {
            DB::table('acciones_asistente')->insert([
                'user_id' => $this->user->id,
                'tipo' => $herramienta,
                'parametros' => json_encode($parametros),
                'resultado' => json_encode($resultado),
                'exito' => $resultado['exito'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('AgentService: Error registrando acción', ['error' => $e->getMessage()]);
        }
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
            ->select('planillas.*', 'clientes.empresa as cliente', 'obras.obra as obra_nombre')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->leftJoin('obras', 'planillas.obra_id', '=', 'obras.id')
            ->whereNull('planillas.deleted_at');

        if (!empty($params['estado'])) {
            $query->where('planillas.estado', $params['estado']);
        }

        if (!empty($params['cliente'])) {
            $query->where('clientes.empresa', 'LIKE', "%{$params['cliente']}%");
        }

        $planillas = $query->orderByDesc('planillas.created_at')->limit(20)->get();

        if ($planillas->isEmpty()) {
            return [
                'exito' => true,
                'contenido' => 'No se encontraron planillas con los filtros especificados.',
                'datos' => [],
            ];
        }

        $contenido = "**Encontré {$planillas->count()} planillas:**\n\n";
        $contenido .= "| Código | Cliente | Obra | Estado | Peso |\n";
        $contenido .= "|--------|---------|------|--------|------|\n";

        foreach ($planillas as $p) {
            $contenido .= "| {$p->codigo} | " . ($p->cliente ?? 'N/A') . " | " . ($p->obra_nombre ?? 'N/A') . " | {$p->estado} | " . number_format($p->peso_total ?? 0, 0) . " kg |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $planillas->toArray(),
        ];
    }

    protected function ejecutarPlanillaVer(array $params): array
    {
        $codigo = $params['codigo'] ?? null;

        if (!$codigo) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el código de la planilla para mostrar sus detalles.',
            ];
        }

        $planilla = DB::table('planillas')
            ->select('planillas.*', 'clientes.empresa as cliente', 'obras.obra as obra_nombre')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->leftJoin('obras', 'planillas.obra_id', '=', 'obras.id')
            ->where('planillas.codigo', 'LIKE', "%{$codigo}%")
            ->first();

        if (!$planilla) {
            return [
                'exito' => false,
                'contenido' => "No encontré ninguna planilla con código '{$codigo}'.",
            ];
        }

        $elementos = DB::table('elementos')
            ->where('planilla_id', $planilla->id)
            ->whereNull('deleted_at')
            ->get();

        $contenido = "**Planilla {$planilla->codigo}**\n\n";
        $contenido .= "- **Cliente:** " . ($planilla->cliente ?? 'N/A') . "\n";
        $contenido .= "- **Obra:** " . ($planilla->obra_nombre ?? 'N/A') . "\n";
        $contenido .= "- **Estado:** {$planilla->estado}\n";
        $contenido .= "- **Peso total:** " . number_format($planilla->peso_total ?? 0, 0) . " kg\n";
        $contenido .= "- **Elementos:** {$elementos->count()}\n";

        $porEstado = $elementos->groupBy('estado');
        $contenido .= "\n**Elementos por estado:**\n";
        foreach ($porEstado as $estado => $grupo) {
            $contenido .= "- {$estado}: {$grupo->count()}\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [
                'planilla' => $planilla,
                'elementos' => $elementos,
            ],
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

        $estadosValidos = ['pendiente', 'fabricando', 'completada', 'pausada'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return [
                'exito' => false,
                'contenido' => "Estado no válido. Estados disponibles: " . implode(', ', $estadosValidos),
            ];
        }

        $planilla = DB::table('planillas')->where('codigo', 'LIKE', "%{$codigo}%")->first();

        if (!$planilla) {
            return [
                'exito' => false,
                'contenido' => "No encontré ninguna planilla con código '{$codigo}'.",
            ];
        }

        $estadoAnterior = $planilla->estado;

        DB::table('planillas')
            ->where('id', $planilla->id)
            ->update([
                'estado' => $nuevoEstado,
                'updated_at' => now(),
            ]);

        return [
            'exito' => true,
            'contenido' => "✅ Planilla **{$planilla->codigo}** actualizada: estado cambiado de **{$estadoAnterior}** a **{$nuevoEstado}**.",
            'datos' => [
                'planilla_id' => $planilla->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
            ],
        ];
    }

    protected function ejecutarStockConsultar(array $params): array
    {
        $diametro = $params['diametro'] ?? null;
        $nave = $params['nave'] ?? null;

        $query = DB::table('stock')
            ->select(
                'stock.*',
                'productos_base.diametro',
                'productos_base.tipo',
                'naves.nombre as nave_nombre'
            )
            ->leftJoin('productos_base', 'stock.producto_base_id', '=', 'productos_base.id')
            ->leftJoin('naves', 'stock.nave_id', '=', 'naves.id')
            ->where('stock.cantidad', '>', 0);

        if ($diametro) {
            $query->where('productos_base.diametro', $diametro);
        }

        if ($nave) {
            $query->where('naves.nombre', 'LIKE', "%{$nave}%");
        }

        $stock = $query->orderBy('productos_base.diametro')->get();

        if ($stock->isEmpty()) {
            return [
                'exito' => true,
                'contenido' => 'No hay stock disponible con los filtros especificados.',
                'datos' => [],
            ];
        }

        // Agrupar por diámetro
        $porDiametro = $stock->groupBy('diametro');

        $contenido = "**Stock actual:**\n\n";
        $contenido .= "| Diámetro | Tipo | Nave | Cantidad |\n";
        $contenido .= "|----------|------|------|----------|\n";

        foreach ($stock as $s) {
            $contenido .= "| Ø{$s->diametro}mm | {$s->tipo} | " . ($s->nave_nombre ?? 'N/A') . " | " . number_format($s->cantidad, 0) . " kg |\n";
        }

        // Resumen por diámetro
        $contenido .= "\n**Resumen por diámetro:**\n";
        foreach ($porDiametro as $d => $items) {
            $total = $items->sum('cantidad');
            $contenido .= "- Ø{$d}mm: " . number_format($total, 0) . " kg\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $stock->toArray(),
        ];
    }

    protected function ejecutarProduccionResumen(array $params): array
    {
        $periodo = $params['periodo'] ?? 'hoy';

        $desde = match ($periodo) {
            'hoy' => today(),
            'semana' => now()->startOfWeek(),
            'mes' => now()->startOfMonth(),
            default => today(),
        };

        $elementos = DB::table('elementos')
            ->where('estado', 'fabricado')
            ->where('updated_at', '>=', $desde)
            ->get();

        $pesoTotal = $elementos->sum('peso');
        $cantidadTotal = $elementos->count();

        // Por máquina
        $porMaquina = DB::table('elementos')
            ->select('maquinas.nombre', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(elementos.peso) as peso'))
            ->leftJoin('maquinas', 'elementos.maquina_id', '=', 'maquinas.id')
            ->where('elementos.estado', 'fabricado')
            ->where('elementos.updated_at', '>=', $desde)
            ->groupBy('maquinas.id', 'maquinas.nombre')
            ->get();

        $contenido = "**Resumen de producción ({$periodo}):**\n\n";
        $contenido .= "- **Total fabricado:** " . number_format($pesoTotal, 0) . " kg\n";
        $contenido .= "- **Elementos:** {$cantidadTotal}\n\n";

        if ($porMaquina->isNotEmpty()) {
            $contenido .= "**Por máquina:**\n";
            $contenido .= "| Máquina | Elementos | Peso |\n";
            $contenido .= "|---------|-----------|------|\n";
            foreach ($porMaquina as $m) {
                $contenido .= "| " . ($m->nombre ?? 'Sin asignar') . " | {$m->cantidad} | " . number_format($m->peso, 0) . " kg |\n";
            }
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => [
                'peso_total' => $pesoTotal,
                'cantidad' => $cantidadTotal,
                'por_maquina' => $porMaquina->toArray(),
            ],
        ];
    }

    protected function ejecutarProduccionMaquinas(array $params): array
    {
        $maquinas = DB::table('maquinas')
            ->select('maquinas.*')
            ->whereNull('deleted_at')
            ->get();

        $contenido = "**Estado de máquinas:**\n\n";
        $contenido .= "| Máquina | Código | Estado | En cola |\n";
        $contenido .= "|---------|--------|--------|--------|\n";

        foreach ($maquinas as $m) {
            $enCola = DB::table('elementos')
                ->where('maquina_id', $m->id)
                ->where('estado', 'asignado')
                ->count();

            $contenido .= "| {$m->nombre} | {$m->codigo} | " . ($m->estado ?? 'activa') . " | {$enCola} |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $maquinas->toArray(),
            'navegacion' => '/produccion/maquinas',
        ];
    }

    protected function ejecutarProduccionCola(array $params): array
    {
        $planillas = DB::table('planillas')
            ->select('planillas.*', 'clientes.empresa as cliente')
            ->leftJoin('clientes', 'planillas.cliente_id', '=', 'clientes.id')
            ->whereIn('planillas.estado', ['pendiente', 'fabricando'])
            ->whereNull('planillas.deleted_at')
            ->orderBy('planillas.prioridad', 'desc')
            ->orderBy('planillas.created_at')
            ->limit(15)
            ->get();

        if ($planillas->isEmpty()) {
            return [
                'exito' => true,
                'contenido' => 'No hay planillas en cola de fabricación.',
                'datos' => [],
            ];
        }

        $contenido = "**Cola de fabricación:**\n\n";
        $contenido .= "| # | Código | Cliente | Estado | Peso |\n";
        $contenido .= "|---|--------|---------|--------|------|\n";

        foreach ($planillas as $i => $p) {
            $pos = $i + 1;
            $contenido .= "| {$pos} | {$p->codigo} | " . ($p->cliente ?? 'N/A') . " | {$p->estado} | " . number_format($p->peso_total ?? 0, 0) . " kg |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $planillas->toArray(),
        ];
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
        $clientes = DB::table('clientes')
            ->select('clientes.*', DB::raw('COUNT(obras.id) as obras_activas'))
            ->leftJoin('obras', function ($join) {
                $join->on('clientes.id', '=', 'obras.cliente_id')
                    ->whereNull('obras.deleted_at');
            })
            ->whereNull('clientes.deleted_at')
            ->groupBy('clientes.id')
            ->orderBy('clientes.empresa')
            ->limit(20)
            ->get();

        $contenido = "**Clientes:**\n\n";
        $contenido .= "| Empresa | CIF | Obras activas |\n";
        $contenido .= "|---------|-----|---------------|\n";

        foreach ($clientes as $c) {
            $contenido .= "| {$c->empresa} | " . ($c->cif ?? 'N/A') . " | {$c->obras_activas} |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $clientes->toArray(),
        ];
    }

    protected function ejecutarObraListar(array $params): array
    {
        $cliente = $params['cliente'] ?? null;

        $query = DB::table('obras')
            ->select('obras.*', 'clientes.empresa as cliente')
            ->leftJoin('clientes', 'obras.cliente_id', '=', 'clientes.id')
            ->whereNull('obras.deleted_at');

        if ($cliente) {
            $query->where('clientes.empresa', 'LIKE', "%{$cliente}%");
        }

        $obras = $query->orderByDesc('obras.created_at')->limit(20)->get();

        $contenido = "**Obras" . ($cliente ? " de {$cliente}" : "") . ":**\n\n";
        $contenido .= "| Obra | Cliente | Dirección |\n";
        $contenido .= "|------|---------|----------|\n";

        foreach ($obras as $o) {
            $contenido .= "| {$o->obra} | " . ($o->cliente ?? 'N/A') . " | " . ($o->direccion ?? 'N/A') . " |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $obras->toArray(),
        ];
    }

    protected function ejecutarAlertaEnviar(array $params): array
    {
        $mensaje = $params['mensaje'] ?? null;
        $destinatarios = $params['destinatarios'] ?? null;

        if (!$mensaje) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el mensaje de la alerta. ¿Qué quieres comunicar?',
            ];
        }

        // Crear alerta
        $alertaId = DB::table('alertas')->insertGetId([
            'user_id' => $this->user->id,
            'titulo' => 'Alerta del Asistente',
            'mensaje' => $mensaje,
            'tipo' => 'info',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'exito' => true,
            'contenido' => "✅ Alerta enviada correctamente.\n\n**Mensaje:** {$mensaje}",
            'datos' => ['alerta_id' => $alertaId],
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

    // Herramientas pendientes de implementación completa
    protected function ejecutarPlanillaAdelantar(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    protected function ejecutarPlanillaAsignarMaquina(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    protected function ejecutarElementoListar(array $params): array
    {
        $planillaCodigo = $params['codigo'] ?? $params['planilla'] ?? null;

        if (!$planillaCodigo) {
            return [
                'exito' => false,
                'contenido' => 'Necesito el código de la planilla para listar sus elementos.',
            ];
        }

        $planilla = DB::table('planillas')
            ->where('codigo', 'LIKE', "%{$planillaCodigo}%")
            ->first();

        if (!$planilla) {
            return [
                'exito' => false,
                'contenido' => "No encontré la planilla '{$planillaCodigo}'.",
            ];
        }

        $elementos = DB::table('elementos')
            ->where('planilla_id', $planilla->id)
            ->whereNull('deleted_at')
            ->get();

        $porEstado = $elementos->groupBy('estado');

        $contenido = "**Elementos de planilla {$planilla->codigo}:**\n\n";
        $contenido .= "Total: {$elementos->count()} elementos\n\n";

        foreach ($porEstado as $estado => $grupo) {
            $peso = $grupo->sum('peso');
            $contenido .= "- **{$estado}:** {$grupo->count()} elementos (" . number_format($peso, 0) . " kg)\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $elementos->toArray(),
        ];
    }

    protected function ejecutarElementoCambiarEstado(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    protected function ejecutarPedidoListar(array $params): array
    {
        $pedidos = DB::table('pedidos')
            ->select('pedidos.*', 'proveedores.nombre as proveedor')
            ->leftJoin('proveedores', 'pedidos.proveedor_id', '=', 'proveedores.id')
            ->whereNull('pedidos.deleted_at')
            ->orderByDesc('pedidos.created_at')
            ->limit(15)
            ->get();

        $contenido = "**Pedidos recientes:**\n\n";
        $contenido .= "| Código | Proveedor | Estado | Fecha |\n";
        $contenido .= "|--------|-----------|--------|-------|\n";

        foreach ($pedidos as $p) {
            $fecha = \Carbon\Carbon::parse($p->created_at)->format('d/m/Y');
            $contenido .= "| {$p->codigo} | " . ($p->proveedor ?? 'N/A') . " | " . ($p->estado ?? 'N/A') . " | {$fecha} |\n";
        }

        return [
            'exito' => true,
            'contenido' => $contenido,
            'datos' => $pedidos->toArray(),
        ];
    }

    protected function ejecutarPedidoVer(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    protected function ejecutarPedidoRecepcionar(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    protected function ejecutarStockMover(array $params): array
    {
        return ['exito' => false, 'contenido' => 'Función en desarrollo'];
    }

    /**
     * Construye el prompt para análisis de intención
     */
    protected function construirPromptIntencion(string $mensaje): string
    {
        return "Analiza la intención del usuario: {$mensaje}";
    }
}
