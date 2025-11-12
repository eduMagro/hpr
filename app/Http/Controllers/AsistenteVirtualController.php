<?php

namespace App\Http\Controllers;

use App\Models\ChatConversacion;
use App\Models\User;
use App\Services\AsistenteVirtualService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
