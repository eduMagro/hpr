<?php

namespace App\Http\Controllers;

use App\Models\ChatConversacion;
use App\Models\User;
use App\Services\AsistenteVirtualService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AsistenteVirtualController extends Controller
{
    private AsistenteVirtualService $asistenteService;

    public function __construct(AsistenteVirtualService $asistenteService)
    {
        $this->asistenteService = $asistenteService;
    }

    /**
     * Muestra la vista principal del chat
     */
    public function index()
    {
        return view('asistente.index');
    }

    /**
     * Obtiene las conversaciones del usuario
     */
    public function obtenerConversaciones(): JsonResponse
    {
        $conversaciones = $this->asistenteService->obtenerConversacionesUsuario(
            Auth::id(),
            20
        );

        return response()->json([
            'success' => true,
            'conversaciones' => $conversaciones->map(fn($conv) => [
                'id' => $conv->id,
                'titulo' => $conv->titulo ?? 'Nueva conversación',
                'ultima_actividad' => $conv->ultima_actividad->diffForHumans(),
                'created_at' => $conv->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    /**
     * Crea una nueva conversación
     */
    public function crearConversacion(Request $request): JsonResponse
    {
        $conversacion = $this->asistenteService->crearConversacion(
            Auth::id(),
            $request->input('titulo')
        );

        return response()->json([
            'success' => true,
            'conversacion' => [
                'id' => $conversacion->id,
                'titulo' => $conversacion->titulo ?? 'Nueva conversación',
                'ultima_actividad' => $conversacion->ultima_actividad->diffForHumans(),
            ],
        ]);
    }

    /**
     * Obtiene los mensajes de una conversación
     */
    public function obtenerMensajes(int $conversacionId): JsonResponse
    {
        $conversacion = ChatConversacion::where('id', $conversacionId)
            ->where('user_id', Auth::id())
            ->with('mensajes')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'mensajes' => $conversacion->mensajes->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'contenido' => $msg->contenido,
                'created_at' => $msg->created_at->format('d/m/Y H:i:s'),
                'metadata' => $msg->metadata,
            ]),
        ]);
    }

    /**
     * Envía un mensaje y recibe respuesta del asistente
     */
    public function enviarMensaje(Request $request): JsonResponse
    {
        $request->validate([
            'conversacion_id' => 'required|exists:chat_conversaciones,id',
            'mensaje' => 'required|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // Obtener conversación con lock para prevenir race conditions
            $conversacion = ChatConversacion::where('id', $request->conversacion_id)
                ->where('user_id', Auth::id())
                ->lockForUpdate() // Bloquear fila durante la transacción
                ->firstOrFail();

            // Procesar mensaje
            $respuesta = $this->asistenteService->procesarMensaje(
                $conversacion,
                $request->mensaje
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => [
                    'id' => $respuesta->id,
                    'role' => $respuesta->role,
                    'contenido' => $respuesta->contenido,
                    'created_at' => $respuesta->created_at->format('d/m/Y H:i:s'),
                    'metadata' => $respuesta->metadata,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el mensaje: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina una conversación
     */
    public function eliminarConversacion(int $conversacionId): JsonResponse
    {
        $conversacion = ChatConversacion::where('id', $conversacionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $conversacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversación eliminada correctamente',
        ]);
    }

    /**
     * Obtiene sugerencias de preguntas
     */
    public function obtenerSugerencias(): JsonResponse
    {
        $sugerencias = [
            '⚡ ¿Qué salidas tengo programadas para hoy?',
            '📦 Muéstrame los pedidos pendientes',
            '🏭 ¿Cuántos elementos en producción hay?',
            '📥 Lista las últimas 10 entradas de almacén',
            '👥 ¿Qué usuarios están activos?',
            '🔧 ¿Cuáles son las máquinas disponibles?',
            '⚠️ Muéstrame las alertas activas',
            '📋 ¿Qué planillas se completaron esta semana?',
            '🏢 Lista los clientes con pedidos este mes',
            '📊 ¿Cuál es el stock actual de productos?',
            '❓ ¿Quién eres, Ferrallin?',
            '💡 Ayúdame con el sistema ERP',
        ];

        return response()->json([
            'success' => true,
            'sugerencias' => $sugerencias,
        ]);
    }

    /**
     * Método para la vista de ayuda - Obtiene sugerencias categorizadas
     */
    public function sugerencias(): JsonResponse
    {
        $sugerencias = [
            [
                'categoria' => 'Pedidos',
                'ejemplos' => [
                    '¿Dónde está el pedido PC25/0001?',
                    '¿Cuáles son los pedidos pendientes?',
                    'Muestra los últimos pedidos',
                    '¿Qué pedidos hay para completar?'
                ]
            ],
            [
                'categoria' => 'Stock',
                'ejemplos' => [
                    '¿Cuánto stock hay de Ø12mm?',
                    'Muestra el stock de diámetro 16',
                    '¿Hay material disponible?',
                    '¿Qué productos tienen stock bajo?'
                ]
            ],
            [
                'categoria' => 'Planillas',
                'ejemplos' => [
                    '¿Qué planillas hay pendientes?',
                    'Información de la planilla PL0567',
                    '¿Cuál es la próxima entrega?',
                    '¿Cuántas planillas activas hay?'
                ]
            ],
            [
                'categoria' => 'Entradas',
                'ejemplos' => [
                    '¿Qué entradas hay recientes?',
                    'Muestra las últimas entregas',
                    '¿Ha llegado material nuevo?'
                ]
            ],
            [
                'categoria' => 'General',
                'ejemplos' => [
                    '¿Cómo está el sistema hoy?',
                    'Dame un resumen general',
                    '¿Qué hay pendiente?'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $sugerencias
        ]);
    }

    /**
     * Método para la vista de ayuda - Procesa una pregunta del usuario usando IA
     */
    public function preguntar(Request $request): JsonResponse
    {
        // Validación
        $request->validate([
            'pregunta' => 'required|string|min:3|max:500'
        ]);

        try {
            $pregunta = trim($request->pregunta);

            // Usar IA para entender la pregunta y generar respuesta inteligente
            $respuesta = $this->generarRespuestaConIA($pregunta);

            return response()->json([
                'success' => true,
                'data' => [
                    'respuesta' => $respuesta
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en asistente de ayuda: ' . $e->getMessage());

            // Fallback al sistema de palabras clave si falla la IA
            try {
                $respuesta = $this->obtenerRespuestaAyuda(strtolower($pregunta));
                return response()->json([
                    'success' => true,
                    'data' => [
                        'respuesta' => $respuesta
                    ]
                ]);
            } catch (\Exception $e2) {
                return response()->json([
                    'success' => false,
                    'error' => 'No pude procesar tu pregunta. Por favor, intenta con algo más específico.'
                ], 500);
            }
        }
    }

    /**
     * Genera respuesta usando IA (OpenAI) para entender mejor la pregunta
     */
    private function generarRespuestaConIA(string $pregunta): string
    {
        // Base de conocimiento con información real del sistema
        $baseConocimiento = $this->obtenerBaseConocimiento();

        // Llamar a OpenAI para procesar la pregunta
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres un asistente de ayuda para un sistema ERP de gestión empresarial.
Tu trabajo es ayudar a los usuarios a entender cómo usar el sistema respondiendo con instrucciones paso a paso CLARAS y PRECISAS.

REGLAS IMPORTANTES:
1. SOLO usa información de la BASE DE CONOCIMIENTO proporcionada - NUNCA inventes pasos o rutas
2. Responde en español con formato Markdown
3. Da instrucciones paso a paso numeradas
4. Usa emojis para hacer la respuesta más visual
5. Si no encuentras información en la base de conocimiento, di que no tienes esa información
6. Sé conciso pero completo
7. NUNCA menciones SQL, bases de datos o código técnico al usuario

BASE DE CONOCIMIENTO:
{$baseConocimiento}"
                ],
                [
                    'role' => 'user',
                    'content' => $pregunta
                ]
            ],
            'temperature' => 0.3, // Baja temperatura para respuestas consistentes
            'max_tokens' => 800
        ]);

        return $response->choices[0]->message->content ??
               'Lo siento, no pude procesar tu pregunta. Intenta reformularla.';
    }

    /**
     * Obtiene la base de conocimiento del sistema
     */
    private function obtenerBaseConocimiento(): string
    {
        return "
## FICHAJES (Entrada/Salida)
**Ruta:** Hacer clic en tu nombre (esquina superior derecha) → Botones Fichar Entrada/Salida
**Detalles:**
- Solo disponible para operarios
- Botón VERDE = Fichar Entrada
- Botón ROJO = Fichar Salida
- Requiere permisos de ubicación GPS
- Debes estar dentro de la zona de obra configurada
- El sistema detecta automáticamente tu turno según la hora
- Ver fichajes: Recursos Humanos → Registros Entrada/Salida

## VACACIONES
**Cómo solicitar vacaciones (solo operarios):**
1. Haz clic en tu nombre (esquina superior derecha) → Mi Perfil
2. Verás un calendario con tus turnos asignados
3. Sistema de selección clic-clic:
   - PRIMER CLIC: Haz clic en el día de inicio (se resalta en azul)
   - SEGUNDO CLIC:
     * Si haces clic en el MISMO día = solicitas solo ese día
     * Si haces clic en un DÍA DIFERENTE = creas un rango desde el primer día hasta el segundo
   - Mientras mueves el ratón verás el resaltado visual del rango
4. Aparecerá modal Solicitar vacaciones mostrando las fechas
5. Haz clic en Enviar solicitud
6. La solicitud queda como pendiente hasta aprobación de RRHH

**Cancelar selección:** Presiona tecla ESC antes del segundo clic

**Gestión RRHH:** Recursos Humanos → Vacaciones
- Ver calendarios por departamento (Maquinistas, Ferrallas, Oficina)
- Aprobar/Denegar solicitudes pendientes
- Asignar vacaciones directamente (solo personal oficina)

## NÓMINAS
**Solicitar nómina por email:**
1. Clic en tu nombre (esquina superior derecha)
2. Baja a sección 'Mis Nóminas'
3. Selecciona mes y año
4. Clic en 'Descargar Nómina' (botón)
5. El sistema ENVÍA la nómina a tu correo electrónico
6. Revisa tu email - recibirás un PDF adjunto con: salario bruto, deducciones, IRPF, SS

**Importante:**
- Las nóminas deben estar generadas por RRHH previamente
- Debes tener un email configurado en tu perfil
- El PDF se envía por email, NO se descarga directamente

## CONTRASEÑAS
**Si la olvidaste:**
1. Página de login → '¿Olvidaste tu contraseña?'
2. Introduce email
3. Revisa email y sigue enlace

**Si la recuerdas:** Contacta con administración

## PEDIDOS - RECEPCIÓN
**IMPORTANTE:** El proceso tiene 3 pasos obligatorios:

**Paso 1 - Activar línea de pedido:**
- Ruta: Logística → Pedidos → [Seleccionar pedido]
- En la tabla de productos del pedido, haz clic en botón 'Activar línea' (amarillo)
- Solo se pueden activar líneas cuando la nave es válida

**Paso 2 - Ir a máquina tipo GRUA:**
- Ruta: Producción → Máquinas → [Seleccionar máquina tipo GRUA]
- En sección 'Movimientos Pendientes' verás la entrada activada
- Haz clic en botón 'Entrada' (naranja)

**Paso 3 - Recepcionar el material:**
El sistema te guiará paso a paso:
1. **Cantidad de paquetes**: ¿1 o 2 paquetes?
2. **Fabricante**: Selecciona el fabricante (si aplica)
3. **Código del paquete**: Escanea o escribe código (debe empezar por MP)
4. **Número de colada**: Introduce el número de colada
5. **Número de paquete**: Número del paquete
6. Si son 2 paquetes, repite pasos 3-5 para el segundo
7. **Peso total (kg)**: Peso en kilogramos
8. **Ubicación**:
   - Selecciona Sector
   - Selecciona Ubicación dentro del sector
   - O marca checkbox para escanear ubicación
9. **Revisar y confirmar** todos los datos
10. El sistema registra y puedes **'Cerrar Albarán'** cuando termines

**Importante:** Los datos se guardan automáticamente si sales, puedes continuar después

## PLANILLAS
**Importar planilla:**
- Ruta: Producción → Planillas → Importar Planilla
- Formatos: Excel (columnas: Posicion, Nombre, Ø, L, NºBarras, kg/ud) o BVBS
- Campos obligatorios: Cliente, Obra, Fecha de aprobación
- Sistema calcula: fecha_entrega = fecha_aprobacion + 7 días
- Procesamiento en background con barra de progreso

**Asignar a máquina:**
- Ruta: Producción → Máquinas
- Arrastra planilla desde panel lateral a la máquina deseada

## PRODUCCIÓN - FABRICACIÓN
**Ruta:** Producción → Máquinas → [Seleccionar máquina] → [Seleccionar planilla]
**Proceso:**
1. Ver elementos/etiquetas de la planilla
2. Clic en elemento a fabricar
3. Ver parámetros (Ø, longitud, kg)
4. Marcar como 'en proceso' o 'completadas'

**Crear paquete:**
1. 'Crear Paquete' → Seleccionar etiquetas
2. Sistema genera código único + código QR
3. 'Imprimir Etiqueta' y pegar en paquete físico
4. Asignar ubicación en mapa de nave

## SALIDAS - PORTES
**Opción 1 - Planificada:**
- Planificación → Portes → Clic en calendario → Obra, fecha, transportista → Crear Porte

**Opción 2 - Directa:**
1. Logística → Salidas → Nueva Salida
2. Seleccionar obra y paquetes
3. Escanear códigos QR o seleccionar manualmente
4. 'Confirmar Salida'
5. Sistema genera albarán automáticamente
6. 'Imprimir Albarán'

**Importante:** Los paquetes salen del stock automáticamente

## STOCK - INVENTARIO
**Opción 1 - Productos base:**
- Logística → Productos o Almacén → Productos
- Filtros: diámetro, tipo, ubicación
- Columna 'Stock' muestra unidades/kg disponibles

**Opción 2 - Ubicaciones:**
- Logística → Ubicaciones
- Mapa de nave con ubicaciones
- Clic en ubicación para ver contenido

**Opción 3 - Paquetes fabricados:**
- Producción → Paquetes o Stock → Paquetes
- Filtros: planilla, obra, estado

## USUARIOS (Solo Admin)
**Crear usuario:**
- Recursos Humanos → Registrar Usuario
- Datos: Nombre, email, contraseña, rol (Operario/Oficina/Admin), departamento, categoría, turno, máquina
- 'Crear Usuario'

**Ver/Editar:**
- Recursos Humanos → Usuarios
- Tabla Livewire: doble clic para editar inline o botón 'Ver' para detalles
";
    }

    /**
     * Obtiene respuesta basada en palabras clave (FALLBACK)
     */
    private function obtenerRespuestaAyuda(string $pregunta): string
    {
        // Detectar tema por palabras clave
        if (preg_match('/(fichar|fichaje|entrada|salida|horario)/i', $pregunta)) {
            return "**📍 Para fichar entrada/salida (solo operarios):**\n\n" .
                   "1. Entra a **tu perfil** (haz clic en tu nombre en la esquina superior derecha)\n" .
                   "2. Verás dos botones grandes:\n" .
                   "   • Botón **verde**: Fichar Entrada\n" .
                   "   • Botón **rojo**: Fichar Salida\n" .
                   "3. Haz clic en el botón que corresponda\n" .
                   "4. El sistema te pedirá **permisos de ubicación** → Acepta\n" .
                   "5. Espera a que aparezca el modal de confirmación\n" .
                   "6. Haz clic en **\"Sí, fichar\"**\n\n" .
                   "⚠️ **Importante:**\n" .
                   "• Debes estar **dentro de la zona de la obra** configurada\n" .
                   "• El sistema detecta automáticamente tu turno según la hora\n" .
                   "• Si fichas fuera de horario, recibirás un aviso\n\n" .
                   "📊 **Ver tus fichajes:** Recursos Humanos → Registros Entrada/Salida";
        }

        if (preg_match('/(vacaciones|solicitar|días|festivos)/i', $pregunta)) {
            return "**🏖️ Para solicitar vacaciones (solo operarios):**\n\n" .
                   "1. Haz clic en **tu nombre** en la esquina superior derecha → **\"Mi Perfil\"**\n" .
                   "2. Verás un **calendario** con tus turnos asignados\n" .
                   "3. Usa el sistema de selección **\"clic-clic\"**:\n\n" .
                   "   **PRIMER CLIC:**\n" .
                   "   • Haz clic en el **día de inicio** de tus vacaciones\n" .
                   "   • El día se resaltará en **azul**\n\n" .
                   "   **SEGUNDO CLIC:**\n" .
                   "   • Si haces clic en el **mismo día** = solicitas solo ese día\n" .
                   "   • Si haces clic en un **día diferente** = creas un rango completo\n" .
                   "   • Mientras mueves el ratón verás el **resaltado visual** del rango\n\n" .
                   "4. Aparecerá un modal **\"Solicitar vacaciones\"** mostrando:\n" .
                   "   • Las fechas seleccionadas (desde/hasta)\n" .
                   "   • Mensaje: \"Se enviará una solicitud para revisión\"\n" .
                   "5. Haz clic en **\"Enviar solicitud\"** para confirmar\n" .
                   "6. Tu solicitud quedará como **\"pendiente\"** hasta que RRHH la apruebe\n\n" .
                   "💡 **Tip:** Presiona **ESC** para cancelar la selección antes del segundo clic\n\n" .
                   "⚠️ **Importante:**\n" .
                   "• Solo **operarios** pueden solicitar vacaciones de esta forma\n" .
                   "• Personal de **oficina** tiene acceso directo para asignar estados\n" .
                   "• Las solicitudes se gestionan desde: **Recursos Humanos → Vacaciones**";
        }

        if (preg_match('/(contraseña|password|clave|recuperar|cambiar)/i', $pregunta)) {
            return "**🔐 Para cambiar tu contraseña:**\n\n" .
                   "**Opción 1 - Si la olvidaste:**\n" .
                   "1. En la página de login, haz clic en **\"¿Olvidaste tu contraseña?\"**\n" .
                   "2. Introduce tu **correo electrónico**\n" .
                   "3. Revisa tu email y sigue el enlace de recuperación\n\n" .
                   "**Opción 2 - Si la recuerdas:**\n" .
                   "1. Contacta con **administración** o tu supervisor\n" .
                   "2. Ellos pueden cambiártela desde el panel de usuarios\n\n" .
                   "⚠️ **Nota:** Por seguridad, no puedes cambiarla tú mismo desde el perfil.";
        }

        if (preg_match('/(pedido|recepcionar|material|entrada.*almacén|almacen)/i', $pregunta)) {
            return "**📦 Para recepcionar un pedido (3 pasos obligatorios):**\n\n" .
                   "**PASO 1 - Activar línea de pedido:**\n" .
                   "1. Ve a **Logística → Pedidos**\n" .
                   "2. Busca y **haz clic en el pedido**\n" .
                   "3. En la tabla de productos, haz clic en el botón **\"Activar línea\"** (amarillo)\n" .
                   "   ⚠️ Solo se pueden activar si la nave es válida\n\n" .
                   "**PASO 2 - Ir a máquina GRÚA:**\n" .
                   "4. Ve a **Producción → Máquinas**\n" .
                   "5. Selecciona una **máquina tipo GRÚA**\n" .
                   "6. En la sección **\"Movimientos Pendientes\"** verás la entrada activada\n" .
                   "7. Haz clic en el botón **\"Entrada\"** (naranja)\n\n" .
                   "**PASO 3 - Recepcionar el material (wizard paso a paso):**\n" .
                   "8. Haz clic en **\"➕ Registrar nuevo paquete\"**\n" .
                   "9. El sistema te guiará paso a paso:\n" .
                   "   1️⃣ **Cantidad de paquetes**: ¿1 o 2?\n" .
                   "   2️⃣ **Fabricante**: Selecciona (si aplica)\n" .
                   "   3️⃣ **Código paquete**: Escanea o escribe (debe empezar por MP)\n" .
                   "   4️⃣ **Número de colada**: Introduce número\n" .
                   "   5️⃣ **Número de paquete**: Introduce número\n" .
                   "   6️⃣ Si son 2 paquetes → Repite pasos 3-5 para el segundo\n" .
                   "   7️⃣ **Peso total (kg)**: Introduce peso\n" .
                   "   8️⃣ **Ubicación**: Selecciona Sector → Ubicación (o escanea)\n" .
                   "   9️⃣ **Revisar y confirmar** → Finalizar\n" .
                   "10. Repite si hay más productos\n" .
                   "11. Cuando termines TODO, haz clic en **\"Cerrar Albarán\"**\n\n" .
                   "💡 **Tip:** Puedes recepcionar parcialmente si no llega todo a la vez";
        }

        if (preg_match('/(planilla|importar|bvbs|asignar.*máquina|maquina)/i', $pregunta)) {
            return "**📋 Trabajar con planillas:**\n\n" .
                   "**Importar una planilla (Excel o BVBS):**\n" .
                   "1. Ve a **Producción → Planillas**\n" .
                   "2. Haz clic en **\"Importar Planilla\"**\n" .
                   "3. Selecciona el archivo desde tu ordenador:\n" .
                   "   • **Excel**: Columnas requeridas: Posicion, Nombre, Ø, L, NºBarras, kg/ud\n" .
                   "   • **BVBS**: Formato estándar de la industria\n" .
                   "4. Completa el formulario:\n" .
                   "   • **Cliente** (obligatorio)\n" .
                   "   • **Obra** (obligatorio)\n" .
                   "   • **Fecha de aprobación** (el sistema calcula entrega = aprobación + 7 días)\n" .
                   "5. Haz clic en **\"Importar\"** → El sistema procesa en background\n" .
                   "6. Verás una barra de progreso - espera a que termine\n\n" .
                   "**Asignar planilla a una máquina:**\n" .
                   "1. Ve a **Producción → Máquinas** (vista de planificación)\n" .
                   "2. En el panel lateral verás las planillas **sin asignar**\n" .
                   "3. **Arrastra** la planilla hacia la máquina deseada\n" .
                   "4. La planilla aparecerá en la cola de trabajo de esa máquina\n\n" .
                   "⚠️ **Importante:** La importación puede tardar varios minutos si el archivo es grande";
        }

        if (preg_match('/(fabricar|producir|operario|paquete|etiqueta)/i', $pregunta)) {
            return "**⚙️ Para fabricar (operarios):**\n\n" .
                   "1. Ve a **Producción → Máquinas**\n" .
                   "2. Selecciona **tu máquina** (verás las planillas asignadas)\n" .
                   "3. Haz clic en la planilla que vas a fabricar\n" .
                   "4. Verás todos los **elementos/etiquetas** de esa planilla\n" .
                   "5. Haz clic en el elemento que vas a fabricar → Se abre la vista de fabricación\n\n" .
                   "**Durante la fabricación:**\n" .
                   "• Puedes ver los **parámetros** del elemento (Ø, longitud, kg, etc.)\n" .
                   "• Marca las etiquetas como **\"en proceso\"** o **\"completadas\"**\n" .
                   "• Añade **observaciones** si es necesario\n\n" .
                   "**Crear un paquete:**\n" .
                   "1. Cuando termines varias etiquetas, haz clic en **\"Crear Paquete\"**\n" .
                   "2. Selecciona las **etiquetas** que van en el paquete (pueden ser múltiples)\n" .
                   "3. El sistema genera automáticamente:\n" .
                   "   • Un **código único** para el paquete\n" .
                   "   • Un **código QR** imprimible\n" .
                   "4. Haz clic en **\"Imprimir Etiqueta\"** y pégala en el paquete físico\n" .
                   "5. Asigna una **ubicación** en el mapa de la nave\n\n" .
                   "💡 **Tip:** El código QR sirve para rastrear el paquete en salidas y stock";
        }

        if (preg_match('/(salida|porte|camión|camion|albarán|albaran)/i', $pregunta)) {
            return "**🚚 Para preparar una salida/porte:**\n\n" .
                   "**Opción 1 - Crear salida planificada:**\n" .
                   "1. Ve a **Planificación → Portes**\n" .
                   "2. Haz clic en el **calendario** en la fecha deseada\n" .
                   "3. Rellena:\n" .
                   "   • **Obra** de destino\n" .
                   "   • **Fecha y hora** de salida\n" .
                   "   • **Transportista** (opcional)\n" .
                   "4. Haz clic en **\"Crear Porte\"**\n\n" .
                   "**Opción 2 - Salida directa:**\n" .
                   "1. Ve a **Logística → Salidas**\n" .
                   "2. Haz clic en **\"Nueva Salida\"**\n" .
                   "3. Selecciona la **obra** y los **paquetes** a enviar\n" .
                   "4. Durante la carga del camión:\n" .
                   "   • **Escanea los códigos QR** de cada paquete\n" .
                   "   • O selecciónalos manualmente de la lista\n" .
                   "5. Cuando todo esté cargado, haz clic en **\"Confirmar Salida\"**\n" .
                   "6. El sistema genera automáticamente el **albarán**\n" .
                   "7. Haz clic en **\"Imprimir Albarán\"** para el transportista\n\n" .
                   "📱 **Tip:** Usa el móvil para escanear QR durante la carga - es más rápido\n\n" .
                   "⚠️ **Importante:** Los paquetes salen del stock automáticamente al confirmar";
        }

        if (preg_match('/(stock|material|disponible|inventario)/i', $pregunta)) {
            return "**📊 Consultar stock y material disponible:**\n\n" .
                   "**Opción 1 - Stock de productos base:**\n" .
                   "1. Ve a **Logística → Productos** o **Almacén → Productos**\n" .
                   "2. Verás una tabla con todos los productos y su stock actual\n" .
                   "3. Usa los **filtros** para buscar:\n" .
                   "   • Por **diámetro** (Ø8, Ø10, Ø12, etc.)\n" .
                   "   • Por **tipo** (corrugado, liso, malla, etc.)\n" .
                   "   • Por **ubicación** o nave\n" .
                   "4. La columna **\"Stock\"** muestra las unidades/kg disponibles\n\n" .
                   "**Opción 2 - Ver ubicaciones específicas:**\n" .
                   "1. Ve a **Logística → Ubicaciones** o **Almacén → Ubicaciones**\n" .
                   "2. Puedes ver un **mapa de la nave** con todas las ubicaciones\n" .
                   "3. Haz clic en una ubicación para ver qué material contiene\n" .
                   "4. Filtra por nave si tienes varias\n\n" .
                   "**Opción 3 - Stock de paquetes fabricados:**\n" .
                   "1. Ve a **Producción → Paquetes** o **Stock → Paquetes**\n" .
                   "2. Verás todos los paquetes fabricados y su ubicación\n" .
                   "3. Puedes filtrar por planilla, obra o estado\n\n" .
                   "💡 **Tip:** Si buscas un producto específico, usa el buscador rápido en la esquina superior";
        }

        if (preg_match('/(nómina|nomina|sueldo|descargar.*nómina|mis.*nóminas)/i', $pregunta)) {
            return "**💰 Para solicitar tu nómina:**\n\n" .
                   "1. Haz clic en **tu nombre** (esquina superior derecha)\n" .
                   "2. Baja hasta la sección **\"Mis Nóminas\"**\n" .
                   "3. Selecciona el **mes y año** que quieres recibir\n" .
                   "4. Haz clic en **\"Descargar Nómina\"**\n" .
                   "5. El sistema **enviará la nómina a tu correo electrónico**\n" .
                   "6. Revisa tu email - recibirás un **PDF adjunto**\n\n" .
                   "⚠️ **Importante:**\n" .
                   "• Las nóminas deben estar generadas previamente por RRHH\n" .
                   "• **Debes tener un email configurado** en tu perfil\n" .
                   "• El PDF se envía por **email**, NO se descarga directamente desde el sistema\n" .
                   "• Si no recibes el email, revisa tu carpeta de spam\n\n" .
                   "📊 **Ver todas las nóminas (Admin):** Base de Datos → Nóminas";
        }

        if (preg_match('/(usuario|registrar|crear.*usuario|nuevo.*empleado)/i', $pregunta)) {
            return "**👤 Gestión de usuarios (solo administradores):**\n\n" .
                   "**Crear un nuevo usuario:**\n" .
                   "1. Ve a **Recursos Humanos** (desde el menú principal)\n" .
                   "2. Haz clic en **\"Registrar Usuario\"** (tarjeta con icono ➕)\n" .
                   "3. Completa el formulario de registro:\n" .
                   "   • **Nombre completo**\n" .
                   "   • **Email** (será su usuario de acceso)\n" .
                   "   • **Contraseña** y confirmación\n" .
                   "   • **Rol**: Operario, Oficina, o Admin\n" .
                   "   • **Departamento**\n" .
                   "   • **Categoría laboral**\n" .
                   "   • **Turno** (si es operario)\n" .
                   "   • **Máquina asignada** (si es operario de producción)\n" .
                   "4. Haz clic en **\"Crear Usuario\"**\n\n" .
                   "**Ver y editar usuarios:**\n" .
                   "1. Ve a **Recursos Humanos → Usuarios** (tarjeta con icono 👤)\n" .
                   "2. Verás una tabla Livewire con todos los usuarios\n" .
                   "3. Puedes:\n" .
                   "   • **Editar inline**: Haz doble clic en una celda\n" .
                   "   • **Ver detalles**: Haz clic en el botón \"Ver\"\n" .
                   "   • **Filtrar/buscar**: Usa los filtros superiores\n\n" .
                   "⚠️ **Importante:** Solo usuarios con rol Admin pueden crear/editar usuarios";
        }

        // Respuesta por defecto
        return "**💡 No encontré una respuesta específica para esa pregunta.**\n\n" .
               "Puedo ayudarte con:\n\n" .
               "• **Fichajes:** Cómo fichar entrada/salida\n" .
               "• **Vacaciones:** Solicitar y consultar días\n" .
               "• **Contraseñas:** Cambiar o recuperar\n" .
               "• **Pedidos:** Recepcionar material\n" .
               "• **Planillas:** Importar y asignar a máquinas\n" .
               "• **Producción:** Fabricar y crear paquetes\n" .
               "• **Salidas:** Preparar portes\n" .
               "• **Stock:** Consultar disponibilidad\n" .
               "• **Usuarios:** Gestionar empleados\n\n" .
               "Intenta preguntar algo más específico, por ejemplo:\n" .
               "- \"¿Cómo ficho entrada?\"\n" .
               "- \"¿Cómo solicito vacaciones?\"\n" .
               "- \"¿Cómo importo una planilla?\"";
    }

    /**
     * Estadísticas de uso del asistente
     */
    public function estadisticas(): JsonResponse
    {
        try {
            // Verificar si existe la tabla
            if (!DB::getSchemaBuilder()->hasTable('asistente_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'mensaje' => 'Tabla de logs no creada aún. Crea la migración para habilitar estadísticas.'
                    ]
                ]);
            }

            // Estadísticas por tipo de consulta
            $stats = DB::table('asistente_logs')
                ->selectRaw('
                    tipo_consulta,
                    COUNT(*) as cantidad,
                    AVG(coste) as coste_promedio,
                    AVG(duracion_segundos) as tiempo_promedio
                ')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('tipo_consulta')
                ->get();

            // Totales generales
            $totales = DB::table('asistente_logs')
                ->selectRaw('
                    COUNT(*) as total_consultas,
                    COUNT(DISTINCT user_id) as usuarios_unicos,
                    SUM(coste) as coste_total,
                    AVG(duracion_segundos) as tiempo_promedio
                ')
                ->where('created_at', '>=', now()->subDays(30))
                ->first();

            // Respuesta
            return response()->json([
                'success' => true,
                'data' => [
                    'totales' => $totales,
                    'por_tipo' => $stats,
                    'periodo' => 'Últimos 30 días'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra la vista de administración de permisos
     */
    public function administrarPermisos()
    {
        // Solo administradores pueden acceder
        if (!Auth::user()->esAdminDepartamento()) {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        $usuarios = User::orderBy('name')->get();

        return view('asistente.permisos', compact('usuarios'));
    }

    /**
     * Actualiza los permisos de un usuario
     */
    public function actualizarPermisos(Request $request, int $userId): JsonResponse
    {
        // Solo administradores pueden modificar permisos
        if (!Auth::user()->esAdminDepartamento()) {
            return response()->json([
                'success' => false,
                'error' => 'No tienes permisos para realizar esta acción',
            ], 403);
        }

        $request->validate([
            'puede_usar_asistente' => 'required|boolean',
            'puede_modificar_bd' => 'required|boolean',
        ]);

        $user = User::findOrFail($userId);
        $user->puede_usar_asistente = $request->puede_usar_asistente;
        $user->puede_modificar_bd = $request->puede_modificar_bd;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente',
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'puede_usar_asistente' => $user->puede_usar_asistente,
                'puede_modificar_bd' => $user->puede_modificar_bd,
            ],
        ]);
    }
}
