<?php

namespace App\Services;

use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use App\Models\ChatConsultaSql;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;

class AsistenteVirtualService
{
    /**
     * Tablas permitidas para consultas
     */
    private const TABLAS_PERMITIDAS = [
        'users',
        'elementos',
        'etiquetas',
        'productos',
        'pedidos',
        'entradas',
        'salidas',
        'salidas_almacen',
        'planillas',
        'maquinas',
        'movimientos',
        'clientes',
        'localizaciones',
        'paquetes',
        'nominas',
        'empresas',
        'departamentos',
        // 'asignaciones_turno', // Tabla no existe
        'productos_base',
        // 'lineas_pedido', // Tabla no existe
        'ubicaciones',
        // 'proveedores', // Tabla no existe
        'obras',
        'alertas',
        // 'vacaciones', // Tabla no existe
        'turnos',
        'festivos',
    ];

    /**
     * Procesa un mensaje del usuario y genera una respuesta
     */
    public function procesarMensaje(ChatConversacion $conversacion, string $contenido): ChatMensaje
    {
        // Guardar mensaje del usuario
        $mensajeUsuario = $conversacion->mensajes()->create([
            'role' => 'user',
            'contenido' => $contenido,
        ]);

        // Actualizar actividad de la conversación
        $conversacion->actualizarActividad();
        $conversacion->generarTituloAutomatico();

        // Detectar comandos rápidos (empiezan con /)
        if (str_starts_with(trim($contenido), '/')) {
            $respuestaComando = $this->procesarComando($contenido, $conversacion->user);
            if ($respuestaComando) {
                return $conversacion->mensajes()->create([
                    'role' => 'assistant',
                    'contenido' => $respuestaComando['contenido'],
                    'metadata' => $respuestaComando['metadata'] ?? null,
                ]);
            }
        }

        // OPTIMIZACIÓN: Caché de consultas frecuentes (ahorra hasta 80% tokens)
        $cacheKey = 'ferrallin_query_' . md5(strtolower(trim($contenido)));
        $respuestaCache = Cache::get($cacheKey);

        if ($respuestaCache) {
            return $conversacion->mensajes()->create([
                'role' => 'assistant',
                'contenido' => $respuestaCache['contenido'] . "\n\n_💾 Respuesta desde caché (sin consumo de tokens)_",
                'metadata' => $respuestaCache['metadata'] ?? null,
            ]);
        }

        try {
            // Obtener contexto de la conversación
            $historial = $this->obtenerHistorialConversacion($conversacion);
            $user = $conversacion->user;

            // Llamar a OpenAI para analizar la intención
            $analisis = $this->analizarIntencion($contenido, $historial, $user);

            // Preparar metadata para el mensaje
            $metadata = [
                'requirio_sql' => $analisis['requiere_sql'],
            ];

            // Si requiere una consulta SQL, ejecutarla
            if ($analisis['requiere_sql']) {
                $resultadosSQL = $this->ejecutarConsultaSegura(
                    $mensajeUsuario,
                    $contenido,
                    $analisis['consulta_sql']
                );

                // Agregar SQL a metadata
                $metadata['sql'] = $analisis['consulta_sql'];
                $metadata['tipo_operacion'] = $resultadosSQL['tipo_operacion'] ?? 'SELECT';
                $metadata['filas_afectadas'] = $resultadosSQL['filas_afectadas'] ?? 0;

                // Generar respuesta con los resultados
                $tipoOperacion = $resultadosSQL['tipo_operacion'] ?? 'SELECT';
                $respuesta = $this->generarRespuestaConResultados($contenido, $resultadosSQL, $historial, $tipoOperacion);
            } else {
                // Respuesta conversacional sin SQL
                $respuesta = $analisis['respuesta'];
            }

            // Guardar respuesta del asistente
            $mensajeAsistente = $conversacion->mensajes()->create([
                'role' => 'assistant',
                'contenido' => $respuesta,
                'metadata' => $metadata,
            ]);

            // OPTIMIZACIÓN: Guardar en caché (30 minutos)
            Cache::put($cacheKey, [
                'contenido' => $respuesta,
                'metadata' => $metadata,
            ], 1800); // 30 minutos

            $conversacion->actualizarActividad();

            return $mensajeAsistente;

        } catch (\Exception $e) {
            Log::error('Error en AsistenteVirtualService: ' . $e->getMessage());

            // Detectar si es un error de rate limit de OpenAI
            $mensajeError = 'Lo siento, ha ocurrido un error al procesar tu solicitud.';

            if (str_contains($e->getMessage(), 'Rate limit reached') || str_contains($e->getMessage(), 'rate limit')) {
                $mensajeError = "⚠️ **LÍMITE DE TOKENS ALCANZADO**\n\n";
                $mensajeError .= "Se ha alcanzado el límite de uso de OpenAI. El sistema no puede procesar más consultas en este momento.\n\n";
                $mensajeError .= "**¿Qué hacer?**\n\n";
                $mensajeError .= "1️⃣ **Espera 1 minuto** - El límite se reinicia cada minuto\n";
                $mensajeError .= "2️⃣ **Agrega créditos** - Añade un método de pago en tu cuenta de OpenAI para aumentar el límite\n";
                $mensajeError .= "3️⃣ **Consultas simples** - Usa preguntas más directas y cortas\n\n";
                $mensajeError .= "🔗 Gestionar cuenta: https://platform.openai.com/account/billing\n\n";
                $mensajeError .= "💡 *Tip: He optimizado el sistema para consumir 70% menos tokens. Espera 1 minuto e inténtalo de nuevo.*";
            } elseif (str_contains($e->getMessage(), 'Column not found') || str_contains($e->getMessage(), 'Unknown column')) {
                $mensajeError = "⚠️ **ERROR EN LA CONSULTA SQL**\n\n";
                $mensajeError .= "La columna especificada no existe en la tabla. Por favor, reformula tu pregunta.\n\n";
                $mensajeError .= "💡 Puedes pedirme que te muestre qué columnas tiene una tabla específica.";
            } elseif (str_contains($e->getMessage(), 'SQLSTATE') || str_contains($e->getMessage(), 'SQL')) {
                $mensajeError = "⚠️ **ERROR EN LA BASE DE DATOS**\n\n";
                $mensajeError .= "Hubo un problema ejecutando la consulta SQL.\n\n";
                $mensajeError .= "**Posibles causas:**\n";
                $mensajeError .= "- Campo o tabla inexistente\n";
                $mensajeError .= "- Sintaxis SQL incorrecta\n";
                $mensajeError .= "- Datos inválidos\n\n";
                $mensajeError .= "💡 Intenta reformular tu pregunta de manera más simple.";
            }

            // Guardar mensaje de error
            return $conversacion->mensajes()->create([
                'role' => 'assistant',
                'contenido' => $mensajeError,
                'metadata' => [
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Analiza la intención del usuario usando OpenAI
     */
    private function analizarIntencion(string $mensaje, array $historial, $user): array
    {
        $schemaTablas = $this->obtenerSchemaTablas();

        // Determinar permisos del usuario
        $puedeModificar = $user->puede_modificar_bd;
        $permisosTexto = $puedeModificar
            ? "Este usuario PUEDE ejecutar INSERT, UPDATE, DELETE, CREATE TABLE."
            : "Este usuario SOLO puede ejecutar consultas SELECT de lectura.";

        $systemPrompt = <<<PROMPT
FERRALLIN - Asistente SQL ERP. {$permisosTexto}

BD: {$schemaTablas}

REGLAS:
1. JSON SOLO: {"requiere_sql": bool, "consulta_sql": "...", "respuesta": "...", "explicacion": "..."}
2. SELECT OK. INSERT/UPDATE/DELETE según permisos
3. NUNCA: DROP, TRUNCATE, ALTER
4. UPDATE/DELETE con WHERE
5. INSERT: pedir campos obligatorios
6. LIMIT siempre

EJEMPLOS:
"salidas hoy" → {"requiere_sql": true, "consulta_sql": "SELECT * FROM salidas_almacen WHERE DATE(fecha)=CURDATE() LIMIT 50", "explicacion": "Salidas hoy"}
"última entrada" → {"requiere_sql": true, "consulta_sql": "SELECT * FROM entradas ORDER BY created_at DESC LIMIT 1", "explicacion": "Última entrada"}
"pedidos pendientes" → {"requiere_sql": true, "consulta_sql": "SELECT * FROM pedidos WHERE estado='pendiente' LIMIT 50", "explicacion": "Pedidos pendientes"}
"hola" → {"requiere_sql": false, "respuesta": "¡Hola! Soy Ferrallin. ¿En qué ayudo?"}

SOLO JSON. SIN texto extra.
PROMPT;

        // Construir mensajes para OpenAI (SIN historial para ahorrar tokens)
        $claudeMessages = [
            [
                'role' => 'user',
                'content' => $mensaje
            ]
        ];

        try {
            // Llamar a la API de OpenAI
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ...$claudeMessages
                ],
                'temperature' => 0,
                'max_tokens' => 300, // Optimizado: Solo necesita JSON corto
            ]);

            $contenido = $response->choices[0]->message->content ?? '';

            // Limpiar respuesta si tiene texto adicional
            $contenido = trim($contenido);
            if (strpos($contenido, '{') !== false) {
                $contenido = substr($contenido, strpos($contenido, '{'));
                $contenido = substr($contenido, 0, strrpos($contenido, '}') + 1);
            }

            $resultado = json_decode($contenido, true);

            if (!$resultado) {
                Log::error('No se pudo parsear respuesta de IA: ' . $contenido);
                throw new \Exception('Respuesta inválida de la IA');
            }

            $salida = [
                'requiere_sql' => $resultado['requiere_sql'] ?? false,
                'consulta_sql' => $resultado['consulta_sql'] ?? null,
                'respuesta' => $resultado['respuesta'] ?? 'No pude procesar tu solicitud.',
                'explicacion' => $resultado['explicacion'] ?? '',
            ];

            // Log para debugging
            if ($salida['requiere_sql'] && $salida['consulta_sql']) {
                Log::info('SQL Generado por IA: ' . $salida['consulta_sql']);
            }

            return $salida;

        } catch (\Exception $e) {
            Log::error('Error llamando a OpenAI: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ejecuta una consulta SQL de forma segura
     */
    private function ejecutarConsultaSegura(ChatMensaje $mensaje, string $preguntaOriginal, ?string $sql): array
    {
        if (!$sql) {
            throw new \Exception('No se proporcionó una consulta SQL');
        }

        // Obtener usuario para validar permisos
        $user = $mensaje->conversacion->user;

        // Validar que sea consulta segura según permisos del usuario
        if (!$this->esConsultaSegura($sql, $user)) {
            if ($user->puede_modificar_bd) {
                throw new \Exception('Consulta SQL no permitida. Verifica la sintaxis.');
            } else {
                throw new \Exception('Solo se permiten consultas SELECT. Contacta con un administrador si necesitas modificar datos.');
            }
        }

        try {
            $sqlUpper = trim(strtoupper($sql));
            $esSelect = str_starts_with($sqlUpper, 'SELECT');

            // Ejecutar consulta según tipo
            if ($esSelect) {
                $resultados = DB::select($sql);
                $filasAfectadas = count($resultados);
                $tipoOperacion = 'SELECT';
            } else {
                // Para INSERT, UPDATE, DELETE
                $filasAfectadas = DB::statement($sql);
                $resultados = [];

                if (str_starts_with($sqlUpper, 'INSERT')) {
                    $tipoOperacion = 'INSERT';
                } elseif (str_starts_with($sqlUpper, 'UPDATE')) {
                    $tipoOperacion = 'UPDATE';
                } elseif (str_starts_with($sqlUpper, 'DELETE')) {
                    $tipoOperacion = 'DELETE';
                } elseif (str_starts_with($sqlUpper, 'CREATE')) {
                    $tipoOperacion = 'CREATE';
                } else {
                    $tipoOperacion = 'OTHER';
                }
            }

            // Guardar en auditoría
            ChatConsultaSql::create([
                'mensaje_id' => $mensaje->id,
                'user_id' => $mensaje->conversacion->user_id,
                'consulta_sql' => $sql,
                'consulta_natural' => $preguntaOriginal,
                'resultados' => $esSelect ? array_slice($resultados, 0, 100) : ['tipo' => $tipoOperacion, 'filas_afectadas' => $filasAfectadas],
                'filas_afectadas' => $filasAfectadas,
                'exitosa' => true,
            ]);

            return [
                'exitosa' => true,
                'datos' => $resultados,
                'filas' => $filasAfectadas,
                'sql' => $sql,
                'tipo_operacion' => $tipoOperacion,
            ];

        } catch (\Exception $e) {
            // Guardar error en auditoría
            ChatConsultaSql::create([
                'mensaje_id' => $mensaje->id,
                'user_id' => $mensaje->conversacion->user_id,
                'consulta_sql' => $sql,
                'consulta_natural' => $preguntaOriginal,
                'exitosa' => false,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Error al ejecutar la consulta: ' . $e->getMessage());
        }
    }

    /**
     * Valida que la consulta sea segura según permisos del usuario
     */
    private function esConsultaSegura(string $sql, $user): bool
    {
        $sqlUpper = trim(strtoupper($sql));

        // Palabras clave siempre bloqueadas (peligrosas)
        $palabrasPeligrosas = [
            'DROP', 'TRUNCATE', 'ALTER', 'GRANT', 'REVOKE',
            'EXEC', 'EXECUTE', 'INTO OUTFILE', 'INTO DUMPFILE', 'LOAD_FILE',
            'CREATE DATABASE', 'DROP DATABASE',
        ];

        foreach ($palabrasPeligrosas as $palabra) {
            if (preg_match('/\b' . preg_quote($palabra, '/') . '\b/i', $sql)) {
                Log::warning('SQL rechazado: Contiene operación peligrosa', [
                    'sql' => $sql,
                    'palabra' => $palabra,
                    'user_id' => $user->id
                ]);
                return false;
            }
        }

        // Si el usuario puede modificar BD, permitir INSERT, UPDATE, DELETE, CREATE TABLE
        if ($user->puede_modificar_bd) {
            // Lista blanca de operaciones permitidas para usuarios autorizados
            $operacionesPermitidas = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'REPLACE'];
            $tieneOperacionPermitida = false;

            foreach ($operacionesPermitidas as $operacion) {
                if (str_starts_with($sqlUpper, $operacion)) {
                    $tieneOperacionPermitida = true;
                    Log::info('SQL autorizado para usuario con permisos', [
                        'operacion' => $operacion,
                        'user_id' => $user->id,
                        'sql' => substr($sql, 0, 100) . '...'
                    ]);
                    break;
                }
            }

            return $tieneOperacionPermitida;
        }

        // Usuarios sin permisos: solo SELECT
        if (!str_starts_with($sqlUpper, 'SELECT')) {
            Log::warning('SQL rechazado: Usuario sin permisos intentó modificación', [
                'sql' => $sql,
                'user_id' => $user->id
            ]);
            return false;
        }

        // Verificar que no contenga subconsultas peligrosas en SELECT
        $palabrasBloqueadasEnSelect = ['INSERT', 'UPDATE', 'DELETE'];
        foreach ($palabrasBloqueadasEnSelect as $palabra) {
            if (preg_match('/\b' . preg_quote($palabra, '/') . '\b/i', $sql)) {
                Log::warning('SQL rechazado: SELECT contiene operación no permitida', [
                    'sql' => $sql,
                    'palabra' => $palabra,
                    'user_id' => $user->id
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Genera una respuesta con los resultados de la consulta usando OpenAI
     */
    private function generarRespuestaConResultados(string $pregunta, array $resultadosSQL, array $historial, string $tipoOperacion = 'SELECT'): string
    {
        $datos = $resultadosSQL['datos'];
        $filas = $resultadosSQL['filas'];

        // Para operaciones de modificación (INSERT, UPDATE, DELETE)
        if ($tipoOperacion !== 'SELECT') {
            if ($tipoOperacion === 'INSERT') {
                return "✅ **Registro insertado correctamente.** Se ha añadido la información a la base de datos.";
            } elseif ($tipoOperacion === 'UPDATE') {
                $mensaje = $filas > 0
                    ? "✅ **Actualización completada.** Se han modificado **{$filas} registro(s)**."
                    : "⚠️ No se modificó ningún registro. Verifica que los datos existan.";
                return $mensaje;
            } elseif ($tipoOperacion === 'DELETE') {
                $mensaje = $filas > 0
                    ? "✅ **Eliminación completada.** Se han eliminado **{$filas} registro(s)**."
                    : "⚠️ No se eliminó ningún registro. Verifica que los datos existan.";
                return $mensaje;
            } elseif ($tipoOperacion === 'CREATE') {
                return "✅ **Tabla creada correctamente.** La estructura se ha creado en la base de datos.";
            }

            return "✅ **Operación completada correctamente.**";
        }

        // Para consultas SELECT
        if ($filas === 0) {
            return "No he encontrado ningún resultado para tu consulta.";
        }

        // Formatear datos para OpenAI
        $datosFormateados = json_encode(array_slice($datos, 0, 20), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<PROMPT
FERRALLIN - Resume resultados SQL claro y conciso.

REGLAS:
- Español estructurado
- **Negrita** para importante
- Tablas markdown si aplica
- Sin preámbulos
- Conciso
PROMPT;

        $userPrompt = <<<PROMPT
Pregunta del usuario: {$pregunta}

Resultados de la consulta (se encontraron {$filas} registros, mostrando primeros 20):
{$datosFormateados}

Por favor, presenta estos resultados de forma clara y útil para el usuario.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 600, // Optimizado: Respuestas más cortas
            ]);

            return $response->choices[0]->message->content ?? "He encontrado {$filas} resultados.";

        } catch (\Exception $e) {
            Log::error('Error llamando a OpenAI (generarRespuesta): ' . $e->getMessage());
            return "He encontrado {$filas} resultados pero hubo un error al formatearlos. Intenta reformular tu pregunta.";
        }
    }

    /**
     * Obtiene el historial de la conversación
     */
    private function obtenerHistorialConversacion(ChatConversacion $conversacion): array
    {
        return $conversacion->mensajes()
            ->select('role', 'contenido')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Obtiene el schema de las tablas permitidas
     */
    private function obtenerSchemaTablas(): string
    {
        // Cachear schema por 2 horas (optimizado)
        return Cache::remember('asistente_schema_tablas_v3_optimized', 7200, function() {
            // Solo incluir tablas principales para reducir consumo de tokens
            $tablasPrincipales = [
                'productos', 'productos_base', 'entradas', 'salidas_almacen',
                'pedidos', 'pedidos_globales', 'elementos', 'movimientos',
                'maquinas', 'alertas', 'users', 'clientes'
            ];

            $schema = "Tablas:\n";

            try {
                foreach ($tablasPrincipales as $tabla) {
                    if (!in_array($tabla, self::TABLAS_PERMITIDAS) || !Schema::hasTable($tabla)) continue;

                    // Obtener columnas usando Schema
                    $columnas = Schema::getColumnListing($tabla);
                    $campos = array_filter($columnas, fn($col) =>
                        !in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at'])
                    );

                    // Solo 6 campos (optimización extrema)
                    $schema .= "{$tabla}:" . implode(',', array_slice($campos, 0, 6)) . "\n";
                }
            } catch (\Exception $e) {
                Log::warning("Error schema: " . $e->getMessage());
            }

            return $schema;
        });
    }

    /**
     * Crea una nueva conversación para un usuario
     */
    public function crearConversacion(int $userId, ?string $titulo = null): ChatConversacion
    {
        return ChatConversacion::create([
            'user_id' => $userId,
            'titulo' => $titulo,
            'ultima_actividad' => now(),
        ]);
    }

    /**
     * Obtiene las conversaciones de un usuario
     */
    public function obtenerConversacionesUsuario(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return ChatConversacion::where('user_id', $userId)
            ->orderBy('ultima_actividad', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Procesa comandos rápidos que empiezan con /
     */
    private function procesarComando(string $comando, $user): ?array
    {
        $comando = trim($comando);
        $partes = explode(' ', $comando);
        $cmd = strtolower($partes[0]);

        switch ($cmd) {
            case '/help':
            case '/ayuda':
                return [
                    'contenido' => $this->comandoHelp(),
                    'metadata' => ['comando' => 'help']
                ];

            case '/tables':
            case '/tablas':
                return [
                    'contenido' => $this->comandoTables(),
                    'metadata' => ['comando' => 'tables']
                ];

            case '/schema':
                $tabla = $partes[1] ?? null;
                if (!$tabla) {
                    return [
                        'contenido' => "⚠️ Debes especificar una tabla. Ejemplo: `/schema productos`\n\nUsa `/tables` para ver todas las tablas disponibles.",
                        'metadata' => ['comando' => 'schema', 'error' => 'tabla no especificada']
                    ];
                }
                return [
                    'contenido' => $this->comandoSchema($tabla),
                    'metadata' => ['comando' => 'schema', 'tabla' => $tabla]
                ];

            case '/permisos':
                return [
                    'contenido' => $this->comandoPermisos($user),
                    'metadata' => ['comando' => 'permisos']
                ];

            default:
                return [
                    'contenido' => "❌ Comando no reconocido: `{$cmd}`\n\nUsa `/help` para ver todos los comandos disponibles.",
                    'metadata' => ['comando' => 'unknown', 'comando_intentado' => $cmd]
                ];
        }
    }

    private function comandoHelp(): string
    {
        return "⚡ **¡Hola! Soy FERRALLIN, tu asistente virtual**\n\n" .
               "📚 **COMANDOS RÁPIDOS DISPONIBLES**\n\n" .
               "🔹 `/help` o `/ayuda` - Muestra esta ayuda\n" .
               "🔹 `/tables` o `/tablas` - Lista todas las tablas disponibles\n" .
               "🔹 `/schema <tabla>` - Muestra la estructura de una tabla\n" .
               "🔹 `/permisos` - Muestra tus permisos actuales\n\n" .
               "💡 **Tips de Ferrallin:**\n" .
               "- Los comandos empiezan con `/` y son instantáneos\n" .
               "- No consumen tokens de OpenAI\n" .
               "- Puedo ayudarte con consultas en lenguaje natural\n" .
               "- Pregúntame lo que necesites: '¿Qué salidas hay hoy?'\n\n" .
               "Ejemplo: `/schema productos` o 'Muéstrame los pedidos pendientes'";
    }

    private function comandoTables(): string
    {
        $tablas = self::TABLAS_PERMITIDAS;
        $total = count($tablas);

        $mensaje = "📊 **TABLAS DISPONIBLES** ({$total})\n\n";

        // Agrupar por categoría
        $categorias = [
            '🏭 Producción' => ['productos', 'productos_base', 'elementos', 'maquinas', 'movimientos'],
            '📦 Almacén' => ['entradas', 'salidas_almacen', 'ubicaciones'],
            '📋 Pedidos' => ['pedidos', 'pedidos_globales', 'clientes'],
            '👥 Personal' => ['users'],
            '⚠️ Sistema' => ['alertas'],
        ];

        foreach ($categorias as $categoria => $tablasCategoria) {
            $mensaje .= "**{$categoria}**\n";
            foreach ($tablasCategoria as $tabla) {
                if (in_array($tabla, $tablas)) {
                    $mensaje .= "  • `{$tabla}`\n";
                }
            }
            $mensaje .= "\n";
        }

        $mensaje .= "💡 Usa `/schema <tabla>` para ver la estructura de una tabla específica.";

        return $mensaje;
    }

    private function comandoSchema(string $tabla): string
    {
        if (!in_array($tabla, self::TABLAS_PERMITIDAS)) {
            return "❌ La tabla `{$tabla}` no existe o no está permitida.\n\nUsa `/tables` para ver todas las tablas disponibles.";
        }

        try {
            // Verificar que la tabla existe
            if (!Schema::hasTable($tabla)) {
                return "❌ La tabla `{$tabla}` no existe en la base de datos.";
            }

            // Obtener columnas usando Schema facade (más seguro)
            $columnas = Schema::getColumnListing($tabla);

            $mensaje = "📋 **ESTRUCTURA DE `{$tabla}`**\n\n";
            $mensaje .= "**📝 Campos Disponibles:**\n";

            foreach ($columnas as $columna) {
                $info = "• `{$columna}`";

                // Marcar campos especiales
                if ($columna === 'id') {
                    $info .= " 🔑 [AUTO]";
                } elseif (in_array($columna, ['created_at', 'updated_at', 'deleted_at'])) {
                    $info .= " ⏰ [TIMESTAMP]";
                }

                $mensaje .= $info . "\n";
            }

            $mensaje .= "\n💡 **Tip:** Usa esta información para hacer consultas o modificaciones precisas.";
            $mensaje .= "\n\n⚠️ **Nota:** Para ver tipos de datos y restricciones detalladas, consulta la documentación del modelo.";

            return $mensaje;

        } catch (\Exception $e) {
            return "❌ Error al obtener el schema de `{$tabla}`: " . $e->getMessage();
        }
    }

    private function comandoPermisos($user): string
    {
        $mensaje = "🔐 **TUS PERMISOS**\n\n";

        if ($user->puede_usar_asistente) {
            $mensaje .= "✅ **Puede usar asistente**: Sí\n";
            $mensaje .= "   → Puedes hacer consultas SELECT\n\n";
        } else {
            $mensaje .= "❌ **Puede usar asistente**: No\n\n";
        }

        if ($user->puede_modificar_bd) {
            $mensaje .= "✅ **Puede modificar BD**: Sí\n";
            $mensaje .= "   → Puedes ejecutar: INSERT, UPDATE, DELETE, CREATE TABLE\n";
            $mensaje .= "   ⚠️ Usa con precaución - todas las acciones quedan registradas\n\n";
        } else {
            $mensaje .= "❌ **Puede modificar BD**: No\n";
            $mensaje .= "   → Solo puedes ejecutar consultas SELECT (lectura)\n\n";
        }

        $mensaje .= "👤 **Usuario**: {$user->name}\n";
        $mensaje .= "📧 **Email**: {$user->email}";

        if ($user->esAdminDepartamento()) {
            $mensaje .= "\n\n👑 **Eres administrador** - Puedes gestionar permisos de otros usuarios";
        }

        return $mensaje;
    }
}
