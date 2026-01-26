<?php

namespace App\Services\Asistente;

use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ContextService - Gestión inteligente del contexto de conversación
 *
 * Responsabilidades:
 * - Resumir conversaciones largas para reducir tokens
 * - Rastrear entidades mencionadas (planillas, máquinas, clientes)
 * - Gestionar memoria de sesión (decisiones, preferencias)
 * - Optimizar contexto enviado a la IA
 */
class ContextService
{
    protected IAService $iaService;
    protected $log;

    // Configuración de optimización
    protected const MENSAJES_ANTES_RESUMIR = 10;
    protected const MENSAJES_RECIENTES_MANTENER = 5;
    protected const MAX_ENTIDADES_POR_TIPO = 3;

    // Patrones para detectar entidades
    protected const PATRONES_ENTIDADES = [
        'planilla' => [
            '/\b(?:planilla\s*)?(?:P-?)?(\d{4,6})\b/i',
            '/\bplanilla\s+(?:n[uú]mero\s+)?(\d+)\b/i',
        ],
        'maquina' => [
            '/\b(syntax|robomaster|schnell|ms-?\d+|cortadora|plegadora|soldadora)\b/i',
            '/\bm[aá]quina\s+([a-z0-9\-]+)\b/i',
        ],
        'cliente' => [
            '/\bcliente\s+([A-Z][a-záéíóú]+(?:\s+[A-Z][a-záéíóú]+)*)/i',
        ],
        'obra' => [
            '/\bobra\s+([A-Z][a-záéíóú0-9\s]+)/i',
        ],
        'diametro' => [
            '/\b[øoOØ]?\s*(\d{1,2}(?:[.,]\d)?)\s*(?:mm)?\b/',
            '/\bdi[aá]metro\s*(\d{1,2})\b/i',
        ],
    ];

    public function __construct(?string $modelo = null)
    {
        $this->iaService = new IAService($modelo);
        $this->log = Log::channel('asistente-virtual');
    }

    /**
     * Obtiene el contexto optimizado para enviar a la IA
     * Combina: resumen de mensajes antiguos + mensajes recientes + entidades + memoria
     */
    public function getContextoOptimizado(ChatConversacion $conversacion): array
    {
        $mensajes = $conversacion->mensajes()->orderBy('created_at', 'asc')->get();
        $totalMensajes = $mensajes->count();

        // Si hay pocos mensajes, devolver todo el historial
        if ($totalMensajes <= self::MENSAJES_ANTES_RESUMIR) {
            return [
                'historial' => $mensajes->map(fn($m) => [
                    'role' => $m->role,
                    'content' => $m->contenido,
                ])->toArray(),
                'entidades' => $this->getEntidadesActivas($conversacion->id),
                'memoria' => $this->getMemoriaSesion($conversacion->id),
                'resumen_usado' => false,
            ];
        }

        // Obtener o generar resumen de mensajes antiguos
        $resumen = $this->obtenerOGenerarResumen($conversacion, $mensajes);

        // Obtener mensajes recientes
        $mensajesRecientes = $mensajes->slice(-self::MENSAJES_RECIENTES_MANTENER);

        // Construir contexto con resumen + recientes
        $historial = [];

        if ($resumen) {
            $historial[] = [
                'role' => 'system',
                'content' => "CONTEXTO ANTERIOR DE LA CONVERSACIÓN:\n{$resumen}",
            ];
        }

        foreach ($mensajesRecientes as $m) {
            $historial[] = [
                'role' => $m->role,
                'content' => $m->contenido,
            ];
        }

        return [
            'historial' => $historial,
            'entidades' => $this->getEntidadesActivas($conversacion->id),
            'memoria' => $this->getMemoriaSesion($conversacion->id),
            'resumen_usado' => true,
            'tokens_ahorrados' => $this->calcularTokensAhorrados($mensajes, $resumen),
        ];
    }

    /**
     * Obtiene un resumen existente o genera uno nuevo si es necesario
     */
    protected function obtenerOGenerarResumen(ChatConversacion $conversacion, $mensajes): ?string
    {
        $totalMensajes = $mensajes->count();
        $mensajesAResumir = $totalMensajes - self::MENSAJES_RECIENTES_MANTENER;

        // Buscar resumen existente que cubra los mensajes
        $resumenExistente = DB::table('chat_resumen_contexto')
            ->where('conversacion_id', $conversacion->id)
            ->where('mensajes_hasta', '>=', $mensajesAResumir)
            ->orderByDesc('mensajes_hasta')
            ->first();

        if ($resumenExistente) {
            return $resumenExistente->resumen;
        }

        // Generar nuevo resumen
        return $this->generarResumen($conversacion, $mensajes->take($mensajesAResumir));
    }

    /**
     * Genera un resumen de los mensajes antiguos usando IA
     */
    protected function generarResumen(ChatConversacion $conversacion, $mensajes): ?string
    {
        if ($mensajes->isEmpty()) {
            return null;
        }

        $textoConversacion = $mensajes->map(function ($m) {
            $role = $m->role === 'user' ? 'Usuario' : 'Asistente';
            return "{$role}: {$m->contenido}";
        })->implode("\n\n");

        $prompt = <<<PROMPT
Resume la siguiente conversación de forma concisa, manteniendo:
1. Temas principales discutidos
2. Decisiones o acciones tomadas
3. Datos importantes mencionados (planillas, clientes, máquinas, etc.)
4. Preguntas sin resolver

Conversación:
{$textoConversacion}

Genera un resumen de máximo 300 palabras que capture la esencia de la conversación.
PROMPT;

        try {
            $resultado = $this->iaService->llamarAPI($prompt);

            if ($resultado['success']) {
                $resumen = $resultado['content'];

                // Guardar resumen
                DB::table('chat_resumen_contexto')->insert([
                    'conversacion_id' => $conversacion->id,
                    'resumen' => $resumen,
                    'mensajes_desde' => 1,
                    'mensajes_hasta' => $mensajes->count(),
                    'tokens_original' => $this->estimarTokens($textoConversacion),
                    'tokens_resumen' => $this->estimarTokens($resumen),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $resumen;
            }
        } catch (\Exception $e) {
            $this->log->error('ContextService: Error generando resumen', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Detecta y registra entidades mencionadas en un mensaje
     */
    public function detectarEntidades(int $conversacionId, string $mensaje): array
    {
        $entidadesDetectadas = [];

        foreach (self::PATRONES_ENTIDADES as $tipo => $patrones) {
            foreach ($patrones as $patron) {
                if (preg_match_all($patron, $mensaje, $matches)) {
                    foreach ($matches[1] as $match) {
                        $entidad = $this->resolverEntidad($tipo, $match);
                        if ($entidad) {
                            $entidadesDetectadas[] = $entidad;
                            $this->registrarEntidad($conversacionId, $entidad, $match);
                        }
                    }
                }
            }
        }

        return $entidadesDetectadas;
    }

    /**
     * Resuelve una referencia textual a una entidad de la base de datos
     */
    protected function resolverEntidad(string $tipo, string $referencia): ?array
    {
        $referencia = trim($referencia);

        switch ($tipo) {
            case 'planilla':
                $planilla = DB::table('planillas')
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($referencia) {
                        $q->where('codigo', 'LIKE', "%{$referencia}%")
                          ->orWhere('numero_planilla', $referencia);
                    })
                    ->first();

                if ($planilla) {
                    return [
                        'tipo' => 'planilla',
                        'id' => $planilla->id,
                        'codigo' => $planilla->codigo,
                        'contexto' => [
                            'estado' => $planilla->estado,
                            'peso' => $planilla->peso_total,
                        ],
                    ];
                }
                break;

            case 'maquina':
                $maquina = DB::table('maquinas')
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($referencia) {
                        $q->where('nombre', 'LIKE', "%{$referencia}%")
                          ->orWhere('codigo', 'LIKE', "%{$referencia}%");
                    })
                    ->first();

                if ($maquina) {
                    return [
                        'tipo' => 'maquina',
                        'id' => $maquina->id,
                        'nombre' => $maquina->nombre,
                        'contexto' => [
                            'activa' => $maquina->activa,
                            'tipo' => $maquina->tipo,
                        ],
                    ];
                }
                break;

            case 'cliente':
                $cliente = DB::table('clientes')
                    ->whereNull('deleted_at')
                    ->where('empresa', 'LIKE', "%{$referencia}%")
                    ->first();

                if ($cliente) {
                    return [
                        'tipo' => 'cliente',
                        'id' => $cliente->id,
                        'nombre' => $cliente->empresa,
                        'contexto' => [],
                    ];
                }
                break;

            case 'obra':
                $obra = DB::table('obras')
                    ->whereNull('deleted_at')
                    ->where('obra', 'LIKE', "%{$referencia}%")
                    ->first();

                if ($obra) {
                    return [
                        'tipo' => 'obra',
                        'id' => $obra->id,
                        'nombre' => $obra->obra,
                        'contexto' => [],
                    ];
                }
                break;

            case 'diametro':
                // No es una entidad de BD, pero es útil rastrearla
                return [
                    'tipo' => 'diametro',
                    'id' => (int)$referencia,
                    'nombre' => "Ø{$referencia}",
                    'contexto' => [],
                ];
        }

        return null;
    }

    /**
     * Registra una entidad mencionada en la conversación
     */
    protected function registrarEntidad(int $conversacionId, array $entidad, string $referencia): void
    {
        // Incrementar orden de menciones anteriores
        DB::table('chat_estado_entidades')
            ->where('conversacion_id', $conversacionId)
            ->where('tipo_entidad', $entidad['tipo'])
            ->increment('orden_mencion');

        // Verificar si ya existe esta entidad
        $existe = DB::table('chat_estado_entidades')
            ->where('conversacion_id', $conversacionId)
            ->where('tipo_entidad', $entidad['tipo'])
            ->where('entidad_id', $entidad['id'])
            ->exists();

        if ($existe) {
            // Actualizar posición y última mención
            DB::table('chat_estado_entidades')
                ->where('conversacion_id', $conversacionId)
                ->where('tipo_entidad', $entidad['tipo'])
                ->where('entidad_id', $entidad['id'])
                ->update([
                    'orden_mencion' => 1,
                    'ultima_mencion' => now(),
                    'contexto' => json_encode($entidad['contexto']),
                ]);
        } else {
            // Insertar nueva entidad
            DB::table('chat_estado_entidades')->insert([
                'conversacion_id' => $conversacionId,
                'tipo_entidad' => $entidad['tipo'],
                'entidad_id' => $entidad['id'],
                'referencia' => $referencia,
                'orden_mencion' => 1,
                'contexto' => json_encode($entidad['contexto']),
                'ultima_mencion' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Limpiar entidades antiguas (mantener solo las más recientes por tipo)
        $this->limpiarEntidadesAntiguas($conversacionId, $entidad['tipo']);
    }

    /**
     * Elimina entidades antiguas para no acumular demasiadas
     */
    protected function limpiarEntidadesAntiguas(int $conversacionId, string $tipo): void
    {
        $idsAMantener = DB::table('chat_estado_entidades')
            ->where('conversacion_id', $conversacionId)
            ->where('tipo_entidad', $tipo)
            ->orderBy('orden_mencion')
            ->limit(self::MAX_ENTIDADES_POR_TIPO)
            ->pluck('id');

        DB::table('chat_estado_entidades')
            ->where('conversacion_id', $conversacionId)
            ->where('tipo_entidad', $tipo)
            ->whereNotIn('id', $idsAMantener)
            ->delete();
    }

    /**
     * Obtiene las entidades activas de la conversación
     */
    public function getEntidadesActivas(int $conversacionId): array
    {
        return DB::table('chat_estado_entidades')
            ->where('conversacion_id', $conversacionId)
            ->orderBy('tipo_entidad')
            ->orderBy('orden_mencion')
            ->get()
            ->groupBy('tipo_entidad')
            ->map(function ($entidades) {
                return $entidades->map(function ($e) {
                    return [
                        'id' => $e->entidad_id,
                        'referencia' => $e->referencia,
                        'orden' => $e->orden_mencion,
                        'contexto' => json_decode($e->contexto, true),
                    ];
                })->toArray();
            })
            ->toArray();
    }

    /**
     * Resuelve una referencia ambigua ("esa planilla", "la otra máquina")
     */
    public function resolverReferencia(int $conversacionId, string $tipo, int $orden = 1): ?array
    {
        $entidad = DB::table('chat_estado_entidades')
            ->where('conversacion_id', $conversacionId)
            ->where('tipo_entidad', $tipo)
            ->where('orden_mencion', $orden)
            ->first();

        if (!$entidad) {
            return null;
        }

        return [
            'tipo' => $tipo,
            'id' => $entidad->entidad_id,
            'referencia' => $entidad->referencia,
            'contexto' => json_decode($entidad->contexto, true),
        ];
    }

    /**
     * Guarda información en la memoria de sesión
     */
    public function guardarMemoria(int $conversacionId, string $tipo, string $clave, string $valor, float $confianza = 0.8): void
    {
        DB::table('chat_memoria_sesion')->updateOrInsert(
            ['conversacion_id' => $conversacionId, 'clave' => $clave],
            [
                'tipo' => $tipo,
                'valor' => $valor,
                'confianza' => $confianza,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Obtiene la memoria de sesión
     */
    public function getMemoriaSesion(int $conversacionId): array
    {
        return DB::table('chat_memoria_sesion')
            ->where('conversacion_id', $conversacionId)
            ->orderByDesc('confianza')
            ->get()
            ->map(function ($m) {
                return [
                    'tipo' => $m->tipo,
                    'clave' => $m->clave,
                    'valor' => $m->valor,
                    'confianza' => $m->confianza,
                ];
            })
            ->toArray();
    }

    /**
     * Guarda o actualiza preferencia de usuario
     */
    public function guardarPreferencia(int $userId, string $clave, string $valor): void
    {
        DB::table('chat_preferencias_usuario')->updateOrInsert(
            ['user_id' => $userId, 'clave' => $clave],
            ['valor' => $valor, 'updated_at' => now()]
        );
    }

    /**
     * Obtiene preferencias del usuario
     */
    public function getPreferenciasUsuario(int $userId): array
    {
        return DB::table('chat_preferencias_usuario')
            ->where('user_id', $userId)
            ->pluck('valor', 'clave')
            ->toArray();
    }

    /**
     * Estima tokens de un texto (aproximación simple)
     */
    protected function estimarTokens(string $texto): int
    {
        // Aproximación: ~4 caracteres por token en español
        return (int)ceil(mb_strlen($texto) / 4);
    }

    /**
     * Calcula tokens ahorrados al usar resumen
     */
    protected function calcularTokensAhorrados($mensajes, ?string $resumen): int
    {
        $tokensOriginales = $mensajes->sum(fn($m) => $this->estimarTokens($m->contenido));
        $tokensResumen = $resumen ? $this->estimarTokens($resumen) : 0;
        $tokensRecientes = $mensajes->slice(-self::MENSAJES_RECIENTES_MANTENER)
            ->sum(fn($m) => $this->estimarTokens($m->contenido));

        return max(0, $tokensOriginales - $tokensResumen - $tokensRecientes);
    }

    /**
     * Formatea el contexto para incluir en el prompt del sistema
     */
    public function formatearContextoParaPrompt(array $contexto): string
    {
        $partes = [];

        // Agregar entidades activas
        if (!empty($contexto['entidades'])) {
            $entidadesTexto = [];
            foreach ($contexto['entidades'] as $tipo => $entidades) {
                $nombres = array_column($entidades, 'referencia');
                $tipoPlural = $this->pluralizarTipo($tipo);
                $entidadesTexto[] = "{$tipoPlural}: " . implode(', ', $nombres);
            }
            if ($entidadesTexto) {
                $partes[] = "ENTIDADES MENCIONADAS RECIENTEMENTE:\n" . implode("\n", $entidadesTexto);
            }
        }

        // Agregar memoria de sesión
        if (!empty($contexto['memoria'])) {
            $memoriaTexto = [];
            foreach ($contexto['memoria'] as $m) {
                if ($m['confianza'] >= 0.7) {
                    $memoriaTexto[] = "- {$m['clave']}: {$m['valor']}";
                }
            }
            if ($memoriaTexto) {
                $partes[] = "INFORMACIÓN CLAVE DE LA CONVERSACIÓN:\n" . implode("\n", $memoriaTexto);
            }
        }

        return implode("\n\n", $partes);
    }

    /**
     * Pluraliza el tipo de entidad para presentación
     */
    protected function pluralizarTipo(string $tipo): string
    {
        return match ($tipo) {
            'planilla' => 'Planillas',
            'maquina' => 'Máquinas',
            'cliente' => 'Clientes',
            'obra' => 'Obras',
            'diametro' => 'Diámetros',
            default => ucfirst($tipo) . 's',
        };
    }
}
