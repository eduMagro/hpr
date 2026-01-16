<?php

namespace App\Services;

use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use App\Models\ChatConsultaSql;
use App\Models\AsistenteInforme;
use App\Models\User;
use App\Services\Asistente\InformeService;
use App\Services\Asistente\ReportePdfService;
use App\Services\Asistente\InteligenciaService;
use App\Services\Asistente\AccionService;
use App\Services\Asistente\DiagnosticoService;
use App\Services\Asistente\AgentService;
use App\Services\Asistente as Asistente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;

class AsistenteVirtualService
{
    protected ?InformeService $informeService = null;
    protected ?ReportePdfService $pdfService = null;
    protected ?InteligenciaService $inteligenciaService = null;
    protected ?AccionService $accionService = null;
    protected ?DiagnosticoService $diagnosticoService = null;
    protected ?AgentService $agentService = null;

    public function __construct(
        ?InformeService $informeService = null,
        ?ReportePdfService $pdfService = null,
        ?InteligenciaService $inteligenciaService = null,
        ?AccionService $accionService = null,
        ?DiagnosticoService $diagnosticoService = null,
        ?AgentService $agentService = null
    ) {
        $this->informeService = $informeService;
        $this->pdfService = $pdfService;
        $this->inteligenciaService = $inteligenciaService;
        $this->accionService = $accionService;
        $this->diagnosticoService = $diagnosticoService;
        $this->agentService = $agentService;
    }

    /**
     * Tablas permitidas para consultas
     */
    private const TABLAS_PERMITIDAS = [
        // Personal
        'users',
        'departamentos',
        'turnos',
        'nominas',

        // Clientes y Obras
        'clientes',
        'obras',

        // Producción
        'planillas',
        'elementos',
        'etiquetas',
        'paquetes',
        'maquinas',

        // Almacén
        'productos',
        'productos_base',
        'entradas',
        'salidas',
        'salidas_almacen',
        'movimientos',
        'ubicaciones',
        'localizaciones',

        // Pedidos
        'pedidos',
        'pedidos_globales',

        // Empresas y proveedores
        'empresas',
        'fabricantes',

        // Sistema
        'alertas',
        'festivos',

        // Asignaciones
        'asignaciones_turno',
        'orden_planillas',
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

        // AGENTE INTELIGENTE: Procesar a través del AgentService
        $respuestaAgente = $this->procesarConAgente($conversacion, $contenido);
        if ($respuestaAgente) {
            return $respuestaAgente;
        }

        // SISTEMA EXPERTO: Verificar si hay una confirmación pendiente
        if ($this->accionService) {
            $confirmacion = $this->accionService->verificarConfirmacion($contenido, $conversacion->user_id);
            if ($confirmacion) {
                return $this->procesarConfirmacionAccion($conversacion, $confirmacion);
            }

            // Detectar solicitud de acción
            $solicitudAccion = $this->accionService->detectarAccion($contenido);
            if ($solicitudAccion) {
                return $this->procesarSolicitudAccion($conversacion, $solicitudAccion);
            }
        }

        // SISTEMA EXPERTO: Detectar problemas/errores que necesitan diagnóstico
        if ($this->diagnosticoService) {
            // Configurar el modelo de IA preferido del usuario
            $modeloUsuario = Asistente\IAService::obtenerPreferenciaUsuario($conversacion->user);
            $this->diagnosticoService->setModelo($modeloUsuario);

            $problema = $this->diagnosticoService->detectarProblema($contenido);
            if ($problema) {
                return $this->procesarDiagnostico($conversacion, $problema);
            }
        }

        // SISTEMA EXPERTO: Detectar solicitud de informe/reporte
        $solicitudInforme = $this->detectarSolicitudInforme($contenido);
        if ($solicitudInforme && $this->informeService) {
            try {
                $informe = $this->informeService->generarInforme(
                    $solicitudInforme['tipo'],
                    $conversacion->user_id,
                    $solicitudInforme['parametros'] ?? [],
                    $mensajeUsuario->id
                );

                // Generar PDF automáticamente
                if ($this->pdfService) {
                    $this->pdfService->generarPdf($informe);
                    $informe->refresh();
                }

                // Formatear respuesta para el chat
                $respuestaFormateada = $this->informeService->formatearParaChat($informe);

                $metadata = [
                    'tipo' => 'informe',
                    'informe_id' => $informe->id,
                    'informe_tipo' => $informe->tipo,
                    'tiene_pdf' => $informe->tienePdf(),
                    'url_pdf' => $informe->tienePdf() ? route('asistente.informes.pdf', $informe->id) : null,
                ];

                return $conversacion->mensajes()->create([
                    'role' => 'assistant',
                    'contenido' => $respuestaFormateada,
                    'metadata' => $metadata,
                ]);
            } catch (\Exception $e) {
                Log::error('Error generando informe: ' . $e->getMessage());
                // Continuar con el flujo normal si falla la generación del informe
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
                'necesita_clarificacion' => $analisis['necesita_clarificacion'] ?? false,
            ];

            // Determinar qué hacer según el análisis
            if ($analisis['necesita_clarificacion'] ?? false) {
                // El asistente necesita más información del usuario
                $respuesta = $analisis['respuesta'];
                $metadata['tipo'] = 'clarificacion';

            } elseif ($analisis['requiere_sql']) {
                // Ejecutar consulta SQL
                $resultadosSQL = $this->ejecutarConsultaSegura(
                    $mensajeUsuario,
                    $contenido,
                    $analisis['consulta_sql']
                );

                // Agregar SQL a metadata
                $metadata['sql'] = $analisis['consulta_sql'];
                $metadata['tipo_operacion'] = $resultadosSQL['tipo_operacion'] ?? 'SELECT';
                $metadata['filas_afectadas'] = $resultadosSQL['filas_afectadas'] ?? 0;
                $metadata['tipo'] = 'sql';

                // Generar respuesta con los resultados
                $tipoOperacion = $resultadosSQL['tipo_operacion'] ?? 'SELECT';
                $respuesta = $this->generarRespuestaConResultados($contenido, $resultadosSQL, $historial, $tipoOperacion);
            } else {
                // Respuesta conversacional sin SQL
                $respuesta = $analisis['respuesta'];
                $metadata['tipo'] = 'conversacional';
            }

            // Guardar respuesta del asistente
            $mensajeAsistente = $conversacion->mensajes()->create([
                'role' => 'assistant',
                'contenido' => $respuesta,
                'metadata' => $metadata,
            ]);

            // OPTIMIZACIÓN: Solo cachear si NO es clarificación (las clarificaciones son contextuales)
            if (!($analisis['necesita_clarificacion'] ?? false)) {
                Cache::put($cacheKey, [
                    'contenido' => $respuesta,
                    'metadata' => $metadata,
                ], 1800); // 30 minutos
            }

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
        $diccionarioNegocio = $this->obtenerDiccionarioNegocio();
        $ejemplosConsultas = $this->obtenerEjemplosConsultas();
        $guiaFuncionalidades = $this->obtenerGuiaFuncionalidades();

        // Determinar permisos del usuario
        $puedeModificar = $user->puede_modificar_bd;
        $permisosTexto = $puedeModificar
            ? "Este usuario PUEDE ejecutar INSERT, UPDATE, DELETE, CREATE TABLE."
            : "Este usuario SOLO puede ejecutar consultas SELECT de lectura.";

        $systemPrompt = <<<PROMPT
FERRALLIN - Asistente EXPERTO e INTELIGENTE para ERP de FERRALLA.

Eres el CENTRO DE CONTROL de toda la aplicación. Los usuarios confían en ti para:
1. **CONSULTAR DATOS** - Ejecutar SQL para obtener información
2. **EXPLICAR PROCESOS** - Cómo hacer las cosas en la aplicación
3. **RESOLVER DUDAS** - Sobre cualquier aspecto del sistema

{$permisosTexto}

## TU PERSONALIDAD
- Eres EXPERTO: conoces TODA la aplicación y sus procesos
- Eres PROACTIVO: si hay ambigüedad, PREGUNTAS antes de actuar
- Eres INTELIGENTE: entiendes el contexto y el lenguaje coloquial
- Eres ÚTIL: das instrucciones claras paso a paso cuando te preguntan cómo hacer algo
- Eres PRECISO: nunca ejecutas SQL si no estás seguro de qué quiere el usuario

## CONTEXTO DEL NEGOCIO - FERRALLA
Empresa de fabricación de armaduras de acero para construcción:
1. CLIENTES (constructoras) hacen PEDIDOS
2. Se crean PLANILLAS (órdenes de fabricación) para cada OBRA
3. Las planillas tienen ELEMENTOS (piezas: barras, estribos, mallas)
4. Los elementos se fabrican en MÁQUINAS (MSR, cortadoras, dobladoras, ensambladoras)
5. Los elementos se agrupan en ETIQUETAS
6. Las etiquetas se empaquetan en PAQUETES
7. Los paquetes salen en SALIDAS/PORTES hacia las obras
8. La materia prima llega como ENTRADAS al almacén

## ESTADOS IMPORTANTES
- Planillas: pendiente → fabricando → completada
- Elementos: pendiente → fabricando → fabricado
- Etiquetas: pendiente → fabricando → completada
- Paquetes: pendiente → preparado → despachado
- Salidas: pendiente → en_transito → entregada

## BASE DE DATOS
{$schemaTablas}

## DICCIONARIO - LENGUAJE COLOQUIAL
{$diccionarioNegocio}

## GUÍA DE LA APLICACIÓN - CÓMO HACER LAS COSAS
{$guiaFuncionalidades}

## TIPOS DE RESPUESTA - SOLO JSON

### 1. CONSULTA SQL (pide datos):
{"requiere_sql": true, "consulta_sql": "SELECT...", "explicacion": "qué hace"}

### 2. EXPLICACIÓN DE PROCESO (cómo hacer algo):
{"requiere_sql": false, "tipo": "guia", "respuesta": "Explicación paso a paso con formato markdown"}

### 3. NECESITA CLARIFICACIÓN:
{"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "Pregunta con opciones"}

### 4. CONVERSACIONAL:
{"requiere_sql": false, "respuesta": "Respuesta amigable"}

## DETECTAR TIPO DE PREGUNTA

**PREGUNTAS DE "CÓMO HACER" (responder con guía):**
- "¿Cómo ficho?" / "¿Cómo marco entrada?"
- "¿Cómo solicito vacaciones?"
- "¿Cómo recepciono un pedido?" / "¿Cómo doy entrada al material?"
- "¿Cómo importo una planilla?"
- "¿Cómo creo un paquete?"
- "¿Cómo preparo una salida?" / "¿Cómo hago un porte?"
- "¿Dónde veo el stock?" / "¿Dónde están las planillas?"
- "¿Cuáles son los pasos para...?"
- "Explícame cómo..."
- "¿Qué tengo que hacer para...?"

**PREGUNTAS DE DATOS (responder con SQL):**
- "¿Cuántos kilos...?" / "¿Cuántas planillas...?"
- "Dame los pedidos..." / "Muéstrame las salidas..."
- "Stock del diámetro..."
- "Planillas pendientes de..."
- "¿Qué sale hoy?"

## EJEMPLOS DE EXPLICACIONES (tipo guía)

Usuario: "¿Cómo ficho entrada?"
→ {"requiere_sql": false, "tipo": "guia", "respuesta": "## 📍 Fichar Entrada\\n\\n**Pasos:**\\n1. Haz clic en **tu nombre** (esquina superior derecha)\\n2. Selecciona **Mi Perfil**\\n3. Verás dos botones grandes:\\n   - 🟢 **Botón verde** = Fichar Entrada\\n   - 🔴 **Botón rojo** = Fichar Salida\\n4. Clic en el **botón verde**\\n5. Acepta los **permisos de ubicación** (GPS)\\n6. Confirma en el modal\\n\\n**Requisitos:**\\n- Debes estar dentro de la zona de la obra\\n- El sistema detecta tu turno automáticamente"}

Usuario: "¿Cómo solicito vacaciones?"
→ {"requiere_sql": false, "tipo": "guia", "respuesta": "## 🏖️ Solicitar Vacaciones\\n\\n**Pasos:**\\n1. Clic en **tu nombre** → **Mi Perfil**\\n2. Verás un **calendario** con tus turnos\\n3. Sistema **clic-clic**:\\n   - **Primer clic:** En el día de inicio (se pone azul)\\n   - **Segundo clic:** En el día final (o mismo día si es solo uno)\\n4. Aparece modal con las fechas seleccionadas\\n5. Clic en **Enviar solicitud**\\n\\n**Tips:**\\n- Presiona **ESC** para cancelar\\n- La solicitud queda pendiente hasta que RRHH apruebe"}

Usuario: "¿Cómo recepciono material?" / "¿Cómo doy entrada a un pedido?"
→ {"requiere_sql": false, "tipo": "guia", "respuesta": "## 📦 Recepcionar Material (3 pasos)\\n\\n**PASO 1 - Activar línea:**\\n1. Ve a **Logística → Pedidos**\\n2. Busca el pedido y haz clic\\n3. Clic en **Activar línea** (botón amarillo)\\n\\n**PASO 2 - Ir a máquina GRÚA:**\\n1. Ve a **Producción → Máquinas**\\n2. Selecciona una **máquina tipo GRÚA**\\n3. En 'Movimientos Pendientes' clic en **Entrada**\\n\\n**PASO 3 - Registrar material:**\\n1. Clic en **Registrar nuevo paquete**\\n2. Sigue el wizard:\\n   - Cantidad de paquetes\\n   - Código (empieza por MP)\\n   - Número de colada\\n   - Peso total\\n   - Ubicación\\n3. Cuando termines todo: **Cerrar Albarán**"}

Usuario: "¿Cómo creo un paquete?"
→ {"requiere_sql": false, "tipo": "guia", "respuesta": "## 📦 Crear Paquete\\n\\n**Pasos:**\\n1. Ve a **Producción → Máquinas → [Tu máquina]**\\n2. Cuando tengas etiquetas terminadas\\n3. Clic en **Crear Paquete**\\n4. Selecciona las **etiquetas** que van juntas\\n5. El sistema genera:\\n   - Código único\\n   - Código QR\\n6. Clic en **Imprimir Etiqueta**\\n7. Pega la etiqueta en el paquete físico\\n8. Asigna **ubicación** en el mapa"}

Usuario: "¿Dónde veo el stock?"
→ {"requiere_sql": false, "tipo": "guia", "respuesta": "## 📊 Ver Stock\\n\\n**3 opciones:**\\n\\n**1. Productos base:**\\n- **Logística → Productos**\\n- Filtra por diámetro, tipo, ubicación\\n\\n**2. Ver en mapa:**\\n- **Logística → Ubicaciones**\\n- Mapa visual de la nave\\n- Clic en ubicación para ver contenido\\n\\n**3. Paquetes fabricados:**\\n- **Producción → Paquetes**\\n- Filtra por planilla, obra, estado\\n\\n¿Quieres que te muestre el stock actual de algún diámetro?"}

## EJEMPLOS DE SQL (cuando pide datos)

Usuario: "Kilos pendientes de la MSR20"
→ {"requiere_sql": true, "consulta_sql": "SELECT SUM(e.peso) as kilos_pendientes, COUNT(*) as elementos FROM elementos e JOIN maquinas m ON e.maquina_id = m.id WHERE (LOWER(m.nombre) LIKE '%msr20%' OR LOWER(m.codigo) LIKE '%msr20%') AND e.estado = 'pendiente'", "explicacion": "Kilos y elementos pendientes en MSR20"}

Usuario: "¿Qué sale hoy?"
→ {"requiere_sql": true, "consulta_sql": "SELECT sa.id, sa.codigo, sa.fecha, sa.estado, u.name as camionero FROM salidas_almacen sa LEFT JOIN users u ON sa.camionero_id = u.id WHERE DATE(sa.fecha) = CURDATE() ORDER BY sa.fecha LIMIT 50", "explicacion": "Salidas de hoy"}

## CUÁNDO PREGUNTAR (CLARIFICACIÓN)

Solo pregunta cuando hay ambigüedad en consultas de DATOS:
- "Los elementos" → ¿Pendientes, fabricados, de qué máquina?
- "Stock" → ¿Con existencias, por diámetro, todo?
- "Planillas" → ¿Pendientes, en fabricación, de qué cliente?

NO preguntes cuando es una pregunta de "cómo hacer" - responde directamente con la guía.

## REGLAS SQL
1. SELECT con LIMIT (máx 100)
2. Búsquedas: LOWER(campo) LIKE '%texto%'
3. Fechas: CURDATE(), DATE_SUB(), YEARWEEK()
4. Estados entre comillas: estado = 'pendiente'

## CONVERSACIONAL

Usuario: "Hola"
→ {"requiere_sql": false, "respuesta": "¡Hola! 👋 Soy **Ferrallin**, tu asistente experto.\\n\\nPuedo ayudarte con:\\n- 📊 **Consultar datos** - kilos, planillas, stock, salidas...\\n- 📖 **Explicarte cómo hacer las cosas** - fichar, recepcionar, crear paquetes...\\n- ❓ **Resolver dudas** - sobre cualquier parte de la aplicación\\n\\n¿Qué necesitas?"}

Usuario: "Gracias"
→ {"requiere_sql": false, "respuesta": "¡De nada! 😊 Aquí estaré para lo que necesites."}

SOLO JSON VÁLIDO. SIN texto adicional.
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
                'temperature' => 0.1, // Un poco de variabilidad para respuestas más naturales
                'max_tokens' => 500, // Más tokens para clarificaciones detalladas
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
                'necesita_clarificacion' => $resultado['necesita_clarificacion'] ?? false,
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
        // Cachear schema por 2 horas
        return Cache::remember('asistente_schema_tablas_v6_salidas', 7200, function() {
            // Tablas principales con sus campos REALES
            $tablasConCampos = [
                'users' => ['id', 'name', 'primer_apellido', 'segundo_apellido', 'email', 'rol', 'maquina_id', 'estado', 'empresa_id'],
                'clientes' => ['id', 'empresa (nombre)', 'codigo', 'cif_nif', 'contacto1_nombre', 'contacto1_telefono', 'direccion', 'ciudad'],
                'obras' => ['id', 'obra (nombre)', 'cod_obra', 'cliente_id', 'ciudad', 'direccion', 'estado', 'tipo'],
                'planillas' => ['id', 'codigo', 'obra_id', 'cliente_id', 'estado', 'peso_total', 'fecha_estimada_entrega', 'revisada', 'aprobada', 'fecha_inicio', 'fecha_finalizacion'],
                'elementos' => ['id', 'planilla_id', 'maquina_id', 'etiqueta_id', 'paquete_id', 'estado', 'peso', 'diametro', 'longitud', 'barras (cantidad)', 'figura', 'marca'],
                'etiquetas' => ['id', 'codigo', 'planilla_id', 'paquete_id', 'estado', 'peso', 'nombre', 'marca', 'numero_etiqueta'],
                'paquetes' => ['id', 'codigo', 'planilla_id', 'peso', 'estado', 'ubicacion_id', 'nave_id', 'user_id'],
                'maquinas' => ['id', 'codigo', 'nombre', 'tipo', 'estado', 'obra_id', 'diametro_min', 'diametro_max'],
                'productos' => ['id', 'codigo', 'producto_base_id', 'fabricante_id', 'n_colada', 'peso_inicial', 'peso_stock', 'estado', 'ubicacion_id', 'maquina_id'],
                'productos_base' => ['id', 'codigo', 'nombre', 'diametro', 'peso_metro', 'tipo'],
                'entradas' => ['id', 'albaran', 'nave_id', 'pedido_id', 'peso_total', 'estado', 'created_at'],
                'salidas_almacen' => ['id', 'codigo', 'fecha', 'estado', 'camionero_id', 'created_at', '-- SALIDAS DE MATERIA PRIMA'],
                'salidas' => ['id', 'codigo_salida', 'fecha_salida', 'estado', 'camion_id', 'importe', 'empresa_id', 'horas_grua', '-- PORTES DE FERRALLA'],
                'pedidos' => ['id', 'codigo', 'pedido_global_id', 'fabricante_id', 'peso_total', 'estado', 'fecha_pedido', 'fecha_entrega'],
                'alertas' => ['id', 'tipo', 'mensaje', 'user_id', 'leida', 'created_at'],
            ];

            $schema = "";

            foreach ($tablasConCampos as $tabla => $campos) {
                if (!in_array($tabla, self::TABLAS_PERMITIDAS)) continue;

                try {
                    if (Schema::hasTable($tabla)) {
                        $schema .= "{$tabla}: " . implode(', ', $campos) . "\n";
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $schema;
        });
    }

    /**
     * Diccionario de términos de negocio - mapea lenguaje coloquial a conceptos del sistema
     */
    private function obtenerDiccionarioNegocio(): string
    {
        return <<<DICT
### PRODUCCIÓN Y FABRICACIÓN
- "kilos/kg/peso a fabricar/por hacer" → SUM(peso) FROM elementos WHERE estado='pendiente'
- "lo que hay que hacer/lo pendiente/la faena" → elementos con estado='pendiente'
- "lo que queda/lo que falta" → elementos WHERE estado != 'fabricado'
- "terminado/acabado/hecho/fabricado" → estado='fabricado'
- "en proceso/fabricando/haciéndose" → estado='fabricando'
- "la máquina X/la X" → buscar en maquinas WHERE LOWER(nombre) LIKE '%x%'
- "la MSR/cortadora/dobladora/ensambladora/soldadora" → tipos de máquinas
- "cuánto llevo/he hecho/hemos hecho" → elementos WHERE estado='fabricado' AND fecha hoy
- "ritmo/producción del día" → SUM(peso) fabricado hoy

### MÁQUINAS - NOMBRES COMUNES
- "MSR" / "la eme ese erre" → maquinas WHERE nombre LIKE '%msr%'
- "cortadora" / "la corta" → maquinas WHERE tipo='corte'
- "dobladora" / "la dobla" → maquinas WHERE tipo='dobladora'
- "ensambladora" / "ensambla" → maquinas WHERE tipo='ensambladora'
- "soldadora" / "la solda" → maquinas WHERE tipo='soldadora'

### CLIENTES Y OBRAS
- "el cliente X/los de X/la empresa X" → clientes WHERE LOWER(nombre) LIKE '%x%'
- "la obra de X/el proyecto X" → obras WHERE LOWER(nombre) LIKE '%x%'
- "constructora/construcciones X" → cliente con ese nombre
- "lo de X/los trabajos de X" → planillas del cliente X

### PEDIDOS Y PLANILLAS
- "pedidos/encargos de X" → pedidos WHERE cliente LIKE X
- "planillas/órdenes/trabajos" → tabla planillas
- "lo urgente/prioritario" → planillas ORDER BY fecha_estimada_entrega ASC
- "para entregar/entregas" → planillas con fecha_estimada_entrega próxima
- "atrasado/retrasado" → planillas WHERE fecha_estimada_entrega < CURDATE() AND estado != 'completada'
- "qué hay para mañana/pasado" → fecha_estimada_entrega = mañana/pasado

### ALMACÉN Y STOCK
- "material/stock/existencias/inventario" → productos con peso_stock
- "qué hay/qué tenemos" → productos WHERE peso_stock > 0
- "ha llegado/entró/recibimos" → entradas recientes
- "diámetro X/Ø X/del X/fierro del X" → productos WHERE diametro = X
- "corrugado/liso/malla" → productos WHERE tipo = X
- "dónde está/ubicación de" → productos con ubicacion_id

### SALIDAS Y PORTES
- "salidas/portes/cargas/envíos" → salidas_almacen
- "qué hay que cargar/preparar" → salidas_almacen WHERE estado='pendiente'
- "qué sale/va/llevamos" → salidas_almacen de hoy
- "camión/transporte/furgón" → salidas con info de transporte

### PAQUETES
- "paquetes/bultos" → tabla paquetes
- "preparados/listos para enviar" → paquetes WHERE estado='preparado'
- "dónde está el paquete X" → paquetes WHERE codigo LIKE '%X%'

### PERSONAL Y USUARIOS
- "operarios/trabajadores/obreros" → users WHERE rol='operario'
- "oficina/administrativos" → users WHERE rol='oficina'
- "quién está/activos/disponibles" → users WHERE activo=1
- "mi equipo/mi gente/los míos" → users del mismo departamento

### ALERTAS
- "avisos/alertas/notificaciones/mensajes" → alertas
- "sin leer/pendientes/nuevas" → alertas WHERE leida=0

### TIEMPO - EXPRESIONES NATURALES
- "hoy/del día/de hoy" → DATE(campo) = CURDATE()
- "ayer" → DATE(campo) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
- "mañana" → DATE(campo) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
- "esta semana/semanal" → YEARWEEK(campo, 1) = YEARWEEK(CURDATE(), 1)
- "semana pasada/anterior" → YEARWEEK(campo, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)
- "este mes/mensual" → MONTH(campo) = MONTH(CURDATE()) AND YEAR(campo) = YEAR(CURDATE())
- "mes pasado/anterior" → MONTH(campo) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
- "últimos X días" → campo >= DATE_SUB(CURDATE(), INTERVAL X DAY)
- "desde el día X" → campo >= 'fecha'
- "entre X y Y" → campo BETWEEN 'fecha1' AND 'fecha2'

### CANTIDADES Y MÉTRICAS
- "cuánto/cuántos" → COUNT(*) o SUM(peso)
- "total/suma" → SUM(campo)
- "promedio/media" → AVG(campo)
- "máximo/más alto" → MAX(campo)
- "mínimo/más bajo" → MIN(campo)
DICT;
    }

    /**
     * Ejemplos de consultas naturales y su traducción a SQL
     */
    private function obtenerEjemplosConsultas(): string
    {
        return <<<EXAMPLES
## EJEMPLOS DE CLARIFICACIÓN (cuando hay ambigüedad)

"Dame los elementos" / "Los elementos" / "Elementos"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Qué elementos quieres ver?\\n\\n1️⃣ **Pendientes** - Lo que falta por fabricar\\n2️⃣ **En fabricación** - En proceso ahora mismo\\n3️⃣ **Fabricados hoy** - Completados hoy\\n4️⃣ **De una máquina específica** - Dime cuál\\n5️⃣ **De una planilla** - Dime el código\\n6️⃣ **Todos** - Sin filtrar\\n\\n¿Cuál prefieres?"}

"Las planillas" / "Planillas" / "Dame las planillas"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Qué planillas te muestro?\\n\\n1️⃣ **Pendientes** - Sin empezar a fabricar\\n2️⃣ **En fabricación** - En proceso\\n3️⃣ **Para entregar esta semana** - Ordenadas por urgencia\\n4️⃣ **Atrasadas** - Fuera de fecha\\n5️⃣ **De un cliente** - Dime el nombre\\n6️⃣ **Todas** - Listado completo\\n\\n¿Qué necesitas?"}

"Stock" / "El stock" / "Existencias"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Cómo quieres ver el stock?\\n\\n1️⃣ **Con existencias** - Solo productos con stock > 0\\n2️⃣ **Por diámetro** - Dime cuál (Ø8, Ø10, Ø12, Ø16...)\\n3️⃣ **Stock bajo/crítico** - Productos con pocas existencias\\n4️⃣ **Sin stock** - Productos agotados\\n5️⃣ **Todo el inventario** - Completo"}

"Pedidos" / "Los pedidos" / "Dame pedidos"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Qué pedidos necesitas?\\n\\n1️⃣ **Pendientes** - Sin completar\\n2️⃣ **De hoy** - Creados hoy\\n3️⃣ **De un cliente** - Dime el nombre del cliente\\n4️⃣ **Últimos 10** - Los más recientes\\n5️⃣ **Todos** - Sin filtros"}

"Salidas" / "Los portes" / "¿Qué sale?"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Qué tipo de salidas necesitas?\\n\\n🚛 **SALIDAS DE FERRALLA** (producto fabricado):\\n1️⃣ Portes de hoy\\n2️⃣ Portes pendientes\\n3️⃣ Portes de esta semana\\n\\n📦 **SALIDAS DE ALMACÉN** (materia prima):\\n4️⃣ Salidas de almacén de hoy\\n5️⃣ Salidas de almacén pendientes\\n\\nDime el número o especifica qué tipo de salida buscas."}

"Entradas" / "Material que ha llegado"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Qué entradas quieres ver?\\n\\n1️⃣ **De hoy** - Llegadas hoy\\n2️⃣ **De esta semana** - Últimos 7 días\\n3️⃣ **De un diámetro** - Dime cuál\\n4️⃣ **Últimas 20** - Las más recientes\\n5️⃣ **De un proveedor** - Dime el nombre"}

"Usuarios" / "Los trabajadores"
→ {"requiere_sql": false, "necesita_clarificacion": true, "respuesta": "¿Qué usuarios te muestro?\\n\\n1️⃣ **Operarios activos** - Personal de producción\\n2️⃣ **Oficina** - Personal administrativo\\n3️⃣ **Todos los activos** - Cualquier rol\\n4️⃣ **Por departamento** - Dime cuál\\n5️⃣ **Inactivos/bajas** - Usuarios dados de baja"}

## EJEMPLOS DE SQL DIRECTO (consulta clara y específica)

### PRODUCCIÓN
"¿Cuántos kilos hay pendientes en la MSR20?"
→ {"requiere_sql": true, "consulta_sql": "SELECT SUM(e.peso) as kilos_pendientes, COUNT(*) as num_elementos FROM elementos e JOIN maquinas m ON e.maquina_id = m.id WHERE (LOWER(m.nombre) LIKE '%msr20%' OR LOWER(m.codigo) LIKE '%msr20%') AND e.estado = 'pendiente'", "explicacion": "Kilos y elementos pendientes en MSR20"}

"Kilos fabricados hoy"
→ {"requiere_sql": true, "consulta_sql": "SELECT SUM(peso) as kilos_fabricados, COUNT(*) as elementos FROM elementos WHERE estado = 'fabricado' AND DATE(updated_at) = CURDATE()", "explicacion": "Producción de hoy"}

"¿Qué tiene pendiente la cortadora?"
→ {"requiere_sql": true, "consulta_sql": "SELECT e.*, p.codigo as planilla FROM elementos e JOIN maquinas m ON e.maquina_id = m.id LEFT JOIN planillas p ON e.planilla_id = p.id WHERE LOWER(m.tipo) LIKE '%corte%' AND e.estado = 'pendiente' ORDER BY p.fecha_estimada_entrega LIMIT 50", "explicacion": "Elementos pendientes en cortadoras"}

"Producción de esta semana por máquina"
→ {"requiere_sql": true, "consulta_sql": "SELECT m.nombre as maquina, SUM(e.peso) as kilos, COUNT(*) as elementos FROM elementos e JOIN maquinas m ON e.maquina_id = m.id WHERE e.estado = 'fabricado' AND YEARWEEK(e.updated_at, 1) = YEARWEEK(CURDATE(), 1) GROUP BY m.id, m.nombre ORDER BY kilos DESC", "explicacion": "Resumen semanal por máquina"}

### PLANILLAS ESPECÍFICAS
"Planillas pendientes ordenadas por urgencia"
→ {"requiere_sql": true, "consulta_sql": "SELECT p.*, c.empresa as cliente, o.obra as nombre_obra FROM planillas p LEFT JOIN clientes c ON p.cliente_id = c.id LEFT JOIN obras o ON p.obra_id = o.id WHERE p.estado = 'pendiente' ORDER BY p.fecha_estimada_entrega ASC LIMIT 50", "explicacion": "Planillas pendientes por fecha de entrega"}

"Planillas atrasadas"
→ {"requiere_sql": true, "consulta_sql": "SELECT p.*, c.empresa as cliente, o.obra as nombre_obra, DATEDIFF(CURDATE(), p.fecha_estimada_entrega) as dias_retraso FROM planillas p LEFT JOIN clientes c ON p.cliente_id = c.id LEFT JOIN obras o ON p.obra_id = o.id WHERE p.estado != 'completada' AND p.fecha_estimada_entrega < CURDATE() ORDER BY dias_retraso DESC LIMIT 50", "explicacion": "Planillas fuera de fecha"}

"Lo de Construcciones García" / "Planillas de Construcciones García"
→ {"requiere_sql": true, "consulta_sql": "SELECT p.*, o.obra as nombre_obra FROM planillas p JOIN clientes c ON p.cliente_id = c.id LEFT JOIN obras o ON p.obra_id = o.id WHERE LOWER(c.empresa) LIKE '%construcciones garcia%' ORDER BY p.fecha_estimada_entrega LIMIT 50", "explicacion": "Planillas del cliente"}

### STOCK Y ALMACÉN
"Stock del diámetro 12 con existencias"
→ {"requiere_sql": true, "consulta_sql": "SELECT p.codigo, pb.nombre, p.peso_stock, p.ubicacion_id FROM productos p JOIN productos_base pb ON p.producto_base_id = pb.id WHERE pb.diametro = 12 AND p.peso_stock > 0 ORDER BY p.peso_stock DESC LIMIT 50", "explicacion": "Stock de Ø12"}

"¿Ha llegado material del 16 esta semana?"
→ {"requiere_sql": true, "consulta_sql": "SELECT e.id, e.albaran, e.peso_total, e.estado, e.created_at, pb.nombre, pb.diametro FROM entradas e LEFT JOIN productos p ON e.id = p.entrada_id LEFT JOIN productos_base pb ON p.producto_base_id = pb.id WHERE pb.diametro = 16 AND YEARWEEK(e.created_at, 1) = YEARWEEK(CURDATE(), 1) ORDER BY e.created_at DESC LIMIT 50", "explicacion": "Entradas de Ø16 esta semana"}

"Productos sin stock"
→ {"requiere_sql": true, "consulta_sql": "SELECT p.codigo, pb.nombre, pb.diametro, p.peso_stock FROM productos p JOIN productos_base pb ON p.producto_base_id = pb.id WHERE p.peso_stock <= 0 ORDER BY pb.diametro LIMIT 100", "explicacion": "Productos agotados"}

### SALIDAS DE FERRALLA (producto fabricado) - tabla: salidas
"Portes de ferralla de hoy" / "¿Qué portes salen hoy?"
→ {"requiere_sql": true, "consulta_sql": "SELECT s.id, s.codigo_salida, s.fecha_salida, s.estado, s.importe, e.nombre as empresa FROM salidas s LEFT JOIN empresas e ON s.empresa_id = e.id WHERE DATE(s.fecha_salida) = CURDATE() ORDER BY s.fecha_salida LIMIT 50", "explicacion": "Portes de ferralla de hoy"}

"Portes pendientes" / "Portes de ferralla pendientes"
→ {"requiere_sql": true, "consulta_sql": "SELECT s.id, s.codigo_salida, s.fecha_salida, s.estado, s.importe FROM salidas s WHERE s.estado = 'pendiente' ORDER BY s.fecha_salida LIMIT 50", "explicacion": "Portes de ferralla pendientes"}

### SALIDAS DE ALMACÉN (materia prima) - tabla: salidas_almacen
"Salidas de almacén de hoy" / "¿Qué sale del almacén hoy?"
→ {"requiere_sql": true, "consulta_sql": "SELECT sa.id, sa.codigo, sa.fecha, sa.estado, u.name as camionero FROM salidas_almacen sa LEFT JOIN users u ON sa.camionero_id = u.id WHERE DATE(sa.fecha) = CURDATE() ORDER BY sa.fecha LIMIT 50", "explicacion": "Salidas de almacén de hoy"}

"Salidas de almacén pendientes"
→ {"requiere_sql": true, "consulta_sql": "SELECT sa.id, sa.codigo, sa.fecha, sa.estado FROM salidas_almacen sa WHERE sa.estado = 'pendiente' ORDER BY sa.fecha LIMIT 50", "explicacion": "Salidas de almacén pendientes"}

### CLIENTES Y OBRAS
"Obras activas de Ferrovial"
→ {"requiere_sql": true, "consulta_sql": "SELECT o.* FROM obras o JOIN clientes c ON o.cliente_id = c.id WHERE LOWER(c.empresa) LIKE '%ferrovial%' AND o.estado = 'activa' LIMIT 50", "explicacion": "Obras activas del cliente"}

"Clientes con pedidos este mes"
→ {"requiere_sql": true, "consulta_sql": "SELECT DISTINCT c.* FROM clientes c JOIN pedidos p ON c.id = p.cliente_id WHERE MONTH(p.fecha_pedido) = MONTH(CURDATE()) AND YEAR(p.fecha_pedido) = YEAR(CURDATE()) LIMIT 50", "explicacion": "Clientes activos este mes"}

### RESPUESTAS A CLARIFICACIONES DEL USUARIO
"Los pendientes" (después de preguntar sobre elementos)
→ {"requiere_sql": true, "consulta_sql": "SELECT e.*, m.nombre as maquina, p.codigo as planilla FROM elementos e LEFT JOIN maquinas m ON e.maquina_id = m.id LEFT JOIN planillas p ON e.planilla_id = p.id WHERE e.estado = 'pendiente' ORDER BY p.fecha_estimada_entrega LIMIT 100", "explicacion": "Elementos pendientes"}

"Solo con existencias" (después de preguntar sobre stock)
→ {"requiere_sql": true, "consulta_sql": "SELECT p.codigo, pb.nombre, pb.diametro, p.peso_stock, pb.tipo FROM productos p JOIN productos_base pb ON p.producto_base_id = pb.id WHERE p.peso_stock > 0 ORDER BY pb.diametro, pb.nombre LIMIT 100", "explicacion": "Productos con stock"}

"De hoy" (respuesta genérica a cuándo)
→ {"requiere_sql": true, "consulta_sql": "SELECT * FROM [tabla_contexto] WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 50", "explicacion": "Registros de hoy"}

## CONVERSACIONAL

"Hola" / "Buenos días" / "Buenas"
→ {"requiere_sql": false, "respuesta": "¡Hola! 👋 Soy **Ferrallin**, tu asistente de gestión.\\n\\nPuedo ayudarte con:\\n- 📊 **Producción** - kilos pendientes, fabricados, por máquina\\n- 📋 **Planillas** - pendientes, urgentes, por cliente\\n- 📦 **Stock** - existencias, entradas, por diámetro\\n- 🚚 **Salidas** - portes de hoy, pendientes\\n- 👥 **Clientes** - obras, pedidos\\n\\n¿Qué necesitas?"}

"¿Qué puedes hacer?" / "Ayuda"
→ {"requiere_sql": false, "respuesta": "Soy tu **centro de control**. Pregúntame en lenguaje natural:\\n\\n**Ejemplos:**\\n- *¿Cuántos kilos tiene la MSR20 para hoy?*\\n- *Planillas pendientes de Construcciones García*\\n- *¿Ha llegado material del 12?*\\n- *¿Qué sale mañana?*\\n- *Stock del diámetro 16*\\n\\nSi no tengo claro qué quieres, te preguntaré para darte exactamente lo que necesitas. 🎯"}

"Gracias" / "Perfecto" / "Ok"
→ {"requiere_sql": false, "respuesta": "¡De nada! 😊 Aquí estaré para lo que necesites."}
EXAMPLES;
    }

    /**
     * Guía completa de funcionalidades de la aplicación
     * Ferrallin usa esto para explicar CÓMO hacer las cosas
     */
    private function obtenerGuiaFuncionalidades(): string
    {
        return <<<GUIDE
## GUÍA DE LA APLICACIÓN - CÓMO HACER LAS COSAS

### 📍 FICHAR ENTRADA/SALIDA (Solo operarios)
**Ruta:** Clic en tu nombre (esquina superior derecha) → Mi Perfil
**Pasos:**
1. Entra a tu perfil haciendo clic en tu nombre
2. Verás dos botones grandes:
   - 🟢 **Botón verde** = Fichar Entrada
   - 🔴 **Botón rojo** = Fichar Salida
3. Haz clic en el botón correspondiente
4. Acepta los **permisos de ubicación** (GPS)
5. Confirma en el modal "Sí, fichar"

**Requisitos:**
- Debes estar dentro de la zona de la obra configurada
- El sistema detecta automáticamente tu turno
- Si fichas fuera de horario, recibirás un aviso

**Ver fichajes:** Recursos Humanos → Registros Entrada/Salida

---

### 🏖️ SOLICITAR VACACIONES (Solo operarios)
**Ruta:** Tu nombre → Mi Perfil → Calendario
**Pasos:**
1. Haz clic en tu nombre → "Mi Perfil"
2. Verás un calendario con tus turnos asignados
3. Sistema de selección **clic-clic**:
   - **PRIMER CLIC:** En el día de inicio (se resalta en azul)
   - **SEGUNDO CLIC:**
     - Mismo día = solicitas solo ese día
     - Día diferente = creas un rango de fechas
4. Aparece modal "Solicitar vacaciones"
5. Clic en "Enviar solicitud"
6. La solicitud queda **pendiente** hasta aprobación de RRHH

**Tips:**
- Presiona **ESC** para cancelar antes del segundo clic
- Puedes ver el resaltado visual mientras mueves el ratón
- RRHH gestiona solicitudes en: Recursos Humanos → Vacaciones

---

### 💰 SOLICITAR NÓMINA
**Ruta:** Tu nombre → Mi Perfil → Sección "Mis Nóminas"
**Pasos:**
1. Haz clic en tu nombre (esquina superior derecha)
2. Baja hasta la sección "Mis Nóminas"
3. Selecciona el **mes y año**
4. Clic en "Descargar Nómina"
5. El sistema **envía la nómina a tu correo electrónico**
6. Revisa tu email - recibirás un **PDF adjunto**

**Importante:**
- Las nóminas deben estar generadas por RRHH previamente
- Debes tener un email configurado en tu perfil
- El PDF se envía por email, NO se descarga directamente

---

### 📦 RECEPCIONAR UN PEDIDO (Entrada de material)
**Este proceso tiene 3 pasos obligatorios:**

**PASO 1 - Activar línea de pedido:**
1. Ve a **Logística → Pedidos**
2. Busca y haz clic en el pedido
3. En la tabla de productos, clic en botón **"Activar línea"** (amarillo)
   - Solo se pueden activar si la nave es válida

**PASO 2 - Ir a máquina GRÚA:**
1. Ve a **Producción → Máquinas**
2. Selecciona una **máquina tipo GRÚA**
3. En "Movimientos Pendientes" verás la entrada activada
4. Clic en botón **"Entrada"** (naranja)

**PASO 3 - Recepcionar el material (wizard):**
1. Clic en "➕ Registrar nuevo paquete"
2. El sistema te guía paso a paso:
   - 1️⃣ Cantidad de paquetes (1 o 2)
   - 2️⃣ Fabricante (si aplica)
   - 3️⃣ Código del paquete (escanear o escribir, empieza por MP)
   - 4️⃣ Número de colada
   - 5️⃣ Número de paquete
   - 6️⃣ Si son 2 paquetes, repetir 3-5
   - 7️⃣ Peso total (kg)
   - 8️⃣ Ubicación (Sector → Ubicación, o escanear)
   - 9️⃣ Revisar y confirmar
3. Repite si hay más productos
4. Cuando termines TODO, clic en **"Cerrar Albarán"**

**Tip:** Los datos se guardan automáticamente si sales

---

### 📋 IMPORTAR UNA PLANILLA
**Ruta:** Producción → Planillas → Importar Planilla
**Formatos aceptados:** Excel o BVBS

**Pasos:**
1. Ve a **Producción → Planillas**
2. Clic en **"Importar Planilla"**
3. Selecciona el archivo:
   - **Excel:** Columnas: Posicion, Nombre, Ø, L, NºBarras, kg/ud
   - **BVBS:** Formato estándar de la industria
4. Completa el formulario:
   - **Cliente** (obligatorio)
   - **Obra** (obligatorio)
   - **Fecha de aprobación** (entrega = aprobación + 7 días)
5. Clic en **"Importar"**
6. Espera a que termine la barra de progreso

**Nota:** La importación puede tardar si el archivo es grande

---

### 🏭 ASIGNAR PLANILLA A MÁQUINA
**Ruta:** Producción → Máquinas (vista planificación)
**Pasos:**
1. Ve a **Producción → Máquinas**
2. En el panel lateral verás planillas **sin asignar**
3. **Arrastra** la planilla hacia la máquina deseada
4. La planilla aparece en la cola de trabajo de esa máquina

---

### ⚙️ FABRICAR ELEMENTOS (Operarios)
**Ruta:** Producción → Máquinas → [Tu máquina]
**Pasos:**
1. Ve a **Producción → Máquinas**
2. Selecciona **tu máquina** (verás las planillas asignadas)
3. Clic en la planilla que vas a fabricar
4. Verás todos los **elementos/etiquetas**
5. Clic en el elemento a fabricar → Vista de fabricación

**Durante la fabricación:**
- Ver parámetros: Ø, longitud, kg, etc.
- Marcar etiquetas como "en proceso" o "completadas"
- Añadir observaciones si necesario

---

### 📦 CREAR UN PAQUETE
**Ruta:** Producción → Máquinas → [Máquina] → Crear Paquete
**Pasos:**
1. Cuando tengas varias etiquetas terminadas
2. Clic en **"Crear Paquete"**
3. Selecciona las **etiquetas** que van en el paquete
4. El sistema genera:
   - Código único para el paquete
   - Código QR imprimible
5. Clic en **"Imprimir Etiqueta"**
6. Pega la etiqueta en el paquete físico
7. Asigna una **ubicación** en el mapa de la nave

**Tip:** El código QR sirve para rastrear el paquete en salidas y stock

---

### 🚚 PREPARAR UNA SALIDA/PORTE

**Opción 1 - Salida planificada:**
1. Ve a **Planificación → Portes**
2. Clic en el **calendario** en la fecha deseada
3. Rellena: Obra, Fecha/hora, Transportista
4. Clic en **"Crear Porte"**

**Opción 2 - Salida directa:**
1. Ve a **Logística → Salidas**
2. Clic en **"Nueva Salida"**
3. Selecciona la **obra** y los **paquetes** a enviar
4. Durante la carga:
   - **Escanea los códigos QR** de cada paquete
   - O selecciónalos manualmente
5. Cuando todo esté cargado: **"Confirmar Salida"**
6. El sistema genera el **albarán** automáticamente
7. Clic en **"Imprimir Albarán"**

**Importante:** Los paquetes salen del stock automáticamente al confirmar

---

### 📊 CONSULTAR STOCK

**Opción 1 - Productos base:**
- **Logística → Productos** o **Almacén → Productos**
- Filtros: diámetro, tipo, ubicación
- Columna "Stock" muestra unidades/kg disponibles

**Opción 2 - Ver ubicaciones:**
- **Logística → Ubicaciones**
- Mapa de la nave con ubicaciones
- Clic en ubicación para ver contenido

**Opción 3 - Paquetes fabricados:**
- **Producción → Paquetes** o **Stock → Paquetes**
- Filtros: planilla, obra, estado

---

### 👤 GESTIONAR USUARIOS (Solo Admin)

**Crear usuario:**
1. Ve a **Recursos Humanos → Registrar Usuario**
2. Completa: Nombre, Email, Contraseña, Rol, Departamento, Categoría, Turno, Máquina
3. Clic en **"Crear Usuario"**

**Ver/Editar usuarios:**
1. Ve a **Recursos Humanos → Usuarios**
2. Doble clic en celda para editar inline
3. O botón "Ver" para detalles completos

---

### 🔐 CAMBIAR CONTRASEÑA

**Si la olvidaste:**
1. Página de login → "¿Olvidaste tu contraseña?"
2. Introduce tu email
3. Revisa email y sigue el enlace

**Si la recuerdas:**
- Contacta con administración para que te la cambien

---

### 📱 MENÚ PRINCIPAL - SECCIONES

**Producción:**
- Máquinas - Vista de producción por máquina
- Planillas - Listado y gestión de planillas
- Paquetes - Paquetes fabricados

**Logística:**
- Pedidos - Gestión de pedidos
- Salidas - Preparar envíos/portes
- Productos - Stock de materiales
- Ubicaciones - Mapa de almacén

**Planificación:**
- Calendario - Vista calendario de planillas
- Portes - Planificación de salidas

**Recursos Humanos:**
- Usuarios - Gestión de personal
- Registros Entrada/Salida - Fichajes
- Vacaciones - Gestión de vacaciones
- Nóminas - Generación de nóminas

**Alertas:**
- Notificaciones del sistema
- Avisos de producción
- Alertas de stock

---

### 🎯 ATAJOS Y TIPS

- **Búsqueda rápida:** Ctrl+K o clic en buscador superior
- **Notificaciones:** Campanita en la esquina superior
- **Perfil:** Clic en tu nombre arriba a la derecha
- **Tema oscuro:** Disponible en configuración
- **Móvil:** La app es responsive, funciona en tablets y móviles
GUIDE;
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

            case '/acciones':
                return [
                    'contenido' => $this->comandoAcciones(),
                    'metadata' => ['comando' => 'acciones']
                ];

            case '/historial':
                return [
                    'contenido' => $this->comandoHistorialAcciones($user),
                    'metadata' => ['comando' => 'historial']
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
               "📚 **COMANDOS RÁPIDOS**\n\n" .
               "🔹 `/help` - Muestra esta ayuda\n" .
               "🔹 `/tables` - Lista tablas disponibles\n" .
               "🔹 `/schema <tabla>` - Estructura de una tabla\n" .
               "🔹 `/permisos` - Tus permisos actuales\n" .
               "🔹 `/acciones` - Acciones que puedo ejecutar\n" .
               "🔹 `/historial` - Historial de acciones ejecutadas\n\n" .
               "📊 **INFORMES** - Pídeme informes como:\n" .
               "- _\"Informe de stock\"_\n" .
               "- _\"Producción de hoy\"_\n" .
               "- _\"Planillas pendientes\"_\n\n" .
               "🎯 **ACCIONES** - Puedo ejecutar:\n" .
               "- _\"Envía una alerta a Juan diciendo...\"_\n" .
               "- _\"Adelanta la planilla 1234\"_\n" .
               "- _\"Cambia el estado de planilla 5678 a fabricando\"_\n\n" .
               "💡 Las acciones de modificación requieren confirmación.";
    }

    private function comandoAcciones(): string
    {
        $acciones = AccionService::ACCIONES;

        $mensaje = "🎯 **ACCIONES DISPONIBLES**\n\n";

        // Agrupar por nivel
        $nivel1 = array_filter($acciones, fn($a) => $a['nivel'] === 1);
        $nivel2 = array_filter($acciones, fn($a) => $a['nivel'] === 2);

        if (!empty($nivel1)) {
            $mensaje .= "**📤 Acciones Rápidas** (sin confirmación)\n";
            foreach ($nivel1 as $key => $accion) {
                $mensaje .= "• **{$accion['nombre']}** - {$accion['descripcion']}\n";
            }
            $mensaje .= "\n";
        }

        if (!empty($nivel2)) {
            $mensaje .= "**⚠️ Acciones con Confirmación** (modifican datos)\n";
            foreach ($nivel2 as $key => $accion) {
                $mensaje .= "• **{$accion['nombre']}** - {$accion['descripcion']}\n";
            }
        }

        $mensaje .= "\n💡 **Ejemplos de uso:**\n";
        $mensaje .= "- _\"Envía alerta a María diciendo: Revisar planilla urgente\"_\n";
        $mensaje .= "- _\"Adelanta la planilla 12345\"_\n";
        $mensaje .= "- _\"Cambia estado de planilla 6789 a fabricando\"_\n";
        $mensaje .= "- _\"Asigna planilla 1111 a máquina EST-1\"_";

        return $mensaje;
    }

    private function comandoHistorialAcciones($user): string
    {
        if (!$this->accionService) {
            return "❌ El servicio de acciones no está disponible.";
        }

        $historial = $this->accionService->obtenerHistorialAcciones($user->id, 10);

        if (empty($historial)) {
            return "📋 **HISTORIAL DE ACCIONES**\n\nNo has ejecutado ninguna acción todavía.";
        }

        $mensaje = "📋 **HISTORIAL DE ACCIONES** (últimas 10)\n\n";

        foreach ($historial as $accion) {
            $mensaje .= "• **{$accion['accion']}** - {$accion['resultado']} ({$accion['fecha']})\n";
        }

        return $mensaje;
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

    /**
     * Detecta si el mensaje solicita un informe/reporte
     */
    private function detectarSolicitudInforme(string $mensaje): ?array
    {
        // Si el servicio de informes no está disponible, delegar al servicio
        if ($this->informeService) {
            $resultado = $this->informeService->detectarSolicitudInforme($mensaje);
            if ($resultado) {
                return [
                    'tipo' => $resultado['tipo'],
                    'nombre' => $resultado['nombre'],
                    'parametros' => $this->extraerParametrosInforme($mensaje, $resultado['tipo']),
                ];
            }
        }

        return null;
    }

    /**
     * Extrae parámetros adicionales del mensaje para el informe
     */
    private function extraerParametrosInforme(string $mensaje, string $tipo): array
    {
        $parametros = [];
        $mensaje = strtolower($mensaje);

        // Detectar fechas
        if (preg_match('/hoy/i', $mensaje)) {
            $parametros['fecha'] = today()->format('Y-m-d');
        } elseif (preg_match('/ayer/i', $mensaje)) {
            $parametros['fecha'] = today()->subDay()->format('Y-m-d');
        } elseif (preg_match('/esta\s*semana/i', $mensaje)) {
            $parametros['fecha_inicio'] = today()->startOfWeek()->format('Y-m-d');
            $parametros['fecha_fin'] = today()->format('Y-m-d');
        } elseif (preg_match('/este\s*mes/i', $mensaje)) {
            $parametros['fecha_inicio'] = today()->startOfMonth()->format('Y-m-d');
            $parametros['fecha_fin'] = today()->format('Y-m-d');
        }

        // Detectar diámetros específicos
        if (preg_match('/(?:di[áa]metro|ø)\s*(\d+)/i', $mensaje, $matches)) {
            $parametros['diametro'] = (int) $matches[1];
        }

        // Detectar nave específica
        if (preg_match('/nave\s*(\d+)/i', $mensaje, $matches)) {
            $parametros['nave_id'] = (int) $matches[1];
        }

        return $parametros;
    }

    /**
     * Obtiene sugerencias proactivas para mostrar al usuario
     */
    public function obtenerSugerenciasProactivas(int $userId): array
    {
        if (!$this->inteligenciaService) {
            return [];
        }

        try {
            return $this->inteligenciaService->obtenerSugerenciasProactivas($userId);
        } catch (\Exception $e) {
            Log::error('Error obteniendo sugerencias proactivas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Analiza tendencias de producción
     */
    public function analizarTendencias(string $periodo = 'semana'): array
    {
        if (!$this->inteligenciaService) {
            return ['error' => 'Servicio de inteligencia no disponible'];
        }

        try {
            return $this->inteligenciaService->analizarTendencias($periodo);
        } catch (\Exception $e) {
            Log::error('Error analizando tendencias: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Compara periodos de tiempo
     */
    public function compararPeriodos(string $periodoActual = 'mes', string $periodoAnterior = 'mes_anterior'): array
    {
        if (!$this->inteligenciaService) {
            return ['error' => 'Servicio de inteligencia no disponible'];
        }

        try {
            return $this->inteligenciaService->compararPeriodos($periodoActual, $periodoAnterior);
        } catch (\Exception $e) {
            Log::error('Error comparando periodos: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene los tipos de informes disponibles
     */
    public function obtenerTiposInforme(): array
    {
        return AsistenteInforme::TIPOS;
    }

    /**
     * Procesa una solicitud de acción desde el chat
     */
    protected function procesarSolicitudAccion(ChatConversacion $conversacion, array $solicitud): ChatMensaje
    {
        $user = $conversacion->user;
        $accion = $solicitud['accion'];
        $config = $solicitud['config'];
        $parametros = $solicitud['parametros'];

        // Preparar la acción (validar y simular)
        $preparacion = $this->accionService->prepararAccion($accion, $parametros, $user);

        // Si no requiere confirmación, ejecutar directamente
        if ($preparacion['success'] && !$config['requiere_confirmacion']) {
            $resultado = $this->accionService->ejecutarAccion($accion, $parametros, $user);
            $contenido = $this->accionService->formatearResultado($resultado);

            return $conversacion->mensajes()->create([
                'role' => 'assistant',
                'contenido' => $contenido,
                'metadata' => [
                    'tipo' => 'accion',
                    'accion' => $accion,
                    'resultado' => $resultado,
                ],
            ]);
        }

        // Requiere confirmación - mostrar preview
        $contenido = $this->accionService->formatearPreparacion($preparacion);

        return $conversacion->mensajes()->create([
            'role' => 'assistant',
            'contenido' => $contenido,
            'metadata' => [
                'tipo' => 'accion_pendiente',
                'accion' => $accion,
                'requiere_confirmacion' => true,
                'token' => $preparacion['token'] ?? null,
            ],
        ]);
    }

    /**
     * Procesa una confirmación de acción
     */
    protected function procesarConfirmacionAccion(ChatConversacion $conversacion, array $confirmacion): ChatMensaje
    {
        $user = $conversacion->user;

        switch ($confirmacion['tipo']) {
            case 'confirmada':
                // Ejecutar la acción confirmada
                $resultado = $this->accionService->ejecutarAccion(
                    $confirmacion['accion'],
                    $confirmacion['parametros'],
                    $user,
                    $confirmacion['token']
                );
                $contenido = $this->accionService->formatearResultado($resultado);

                return $conversacion->mensajes()->create([
                    'role' => 'assistant',
                    'contenido' => $contenido,
                    'metadata' => [
                        'tipo' => 'accion_ejecutada',
                        'accion' => $confirmacion['accion'],
                        'resultado' => $resultado,
                    ],
                ]);

            case 'cancelada':
                return $conversacion->mensajes()->create([
                    'role' => 'assistant',
                    'contenido' => "✅ Acción cancelada. No se realizaron cambios.",
                    'metadata' => [
                        'tipo' => 'accion_cancelada',
                    ],
                ]);

            case 'expirada':
                return $conversacion->mensajes()->create([
                    'role' => 'assistant',
                    'contenido' => "⏱️ **La confirmación ha expirado**\n\nLa solicitud de confirmación superó el tiempo límite de 5 minutos.\n\nPor favor, vuelve a solicitar la acción si deseas continuar.",
                    'metadata' => [
                        'tipo' => 'accion_expirada',
                    ],
                ]);

            default:
                return $conversacion->mensajes()->create([
                    'role' => 'assistant',
                    'contenido' => "No entendí tu respuesta. Por favor, escribe **\"SI CONFIRMO\"** para ejecutar o **\"cancelar\"** para anular.",
                    'metadata' => [
                        'tipo' => 'accion_pendiente',
                    ],
                ]);
        }
    }

    /**
     * Obtiene las acciones disponibles
     */
    public function obtenerAccionesDisponibles(): array
    {
        if (!$this->accionService) {
            return [];
        }

        return AccionService::ACCIONES;
    }

    /**
     * Obtiene el historial de acciones del usuario
     */
    public function obtenerHistorialAcciones(int $userId, int $limite = 20): array
    {
        if (!$this->accionService) {
            return [];
        }

        return $this->accionService->obtenerHistorialAcciones($userId, $limite);
    }

    /**
     * Procesa un diagnóstico de problema
     */
    protected function procesarDiagnostico(ChatConversacion $conversacion, array $problema): ChatMensaje
    {
        $user = $conversacion->user;

        // Realizar diagnóstico
        $diagnostico = $this->diagnosticoService->diagnosticar($problema, $user);

        // Formatear respuesta con el análisis semántico completo
        $contenido = $this->diagnosticoService->formatearDiagnostico($diagnostico, $problema);

        // Guardar metadata con información del análisis
        $metadata = [
            'tipo' => 'diagnostico',
            'problema_tipo' => $problema['tipo'],
            'encontrado' => $diagnostico['encontrado'],
            'soluciones' => $diagnostico['soluciones'] ?? [],
            'datos' => $diagnostico['datos'] ?? [],
            'confianza' => $problema['confianza'] ?? null,
            'gravedad' => $problema['gravedad'] ?? 'media',
        ];

        // Incluir resumen del análisis de IA si está disponible
        if (!empty($problema['analisis_ia'])) {
            $analisisIA = $problema['analisis_ia'];
            $metadata['analisis'] = [
                'comprension' => $analisisIA['comprension'] ?? null,
                'tipo_problema' => $analisisIA['tipo_problema'] ?? null,
                'entidad_afectada' => $analisisIA['entidad_afectada'] ?? null,
                'accion_realizada' => $analisisIA['accion_realizada'] ?? null,
                'intencion' => $analisisIA['intencion_usuario'] ?? null,
                'confianza' => $analisisIA['confianza'] ?? 0,
            ];
        }
        // Fallback para análisis local
        elseif (!empty($problema['analisis_completo'])) {
            $analisis = $problema['analisis_completo'];
            $metadata['analisis'] = [
                'intenciones' => array_keys($analisis['intenciones'] ?? []),
                'entidades' => array_keys($analisis['entidades'] ?? []),
                'acciones' => array_keys($analisis['acciones'] ?? []),
                'confianza_general' => $analisis['confianza']['general'] ?? 0,
            ];
        }

        return $conversacion->mensajes()->create([
            'role' => 'assistant',
            'contenido' => $contenido,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Ejecuta una corrección basada en diagnóstico
     */
    public function ejecutarCorreccion(string $accion, array $parametros, User $user): array
    {
        if (!$this->diagnosticoService) {
            return ['success' => false, 'mensaje' => 'Servicio de diagnóstico no disponible'];
        }

        return $this->diagnosticoService->ejecutarCorreccion($accion, $parametros, $user);
    }
}
