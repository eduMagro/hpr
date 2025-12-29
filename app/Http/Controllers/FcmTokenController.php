<?php

namespace App\Http\Controllers;

use App\Services\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends Controller
{
    public function __construct(
        private FirebaseNotificationService $firebaseService
    ) {}

    /**
     * Registra un token FCM para el usuario autenticado
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:web,android,ios',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $fcmToken = $this->firebaseService->registerToken(
            $user,
            $request->token,
            $request->device_type ?? 'web',
            $request->device_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Token registrado correctamente',
            'token_id' => $fcmToken->id,
        ]);
    }

    /**
     * Elimina un token FCM
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $deleted = $this->firebaseService->removeToken($request->token);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Token eliminado correctamente' : 'Token no encontrado',
        ]);
    }

    /**
     * Devuelve la configuración de Firebase para el frontend
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'apiKey' => config('firebase.web.api_key'),
            'authDomain' => config('firebase.web.auth_domain'),
            'projectId' => config('firebase.web.project_id'),
            'storageBucket' => config('firebase.web.storage_bucket'),
            'messagingSenderId' => config('firebase.web.messaging_sender_id'),
            'appId' => config('firebase.web.app_id'),
            'vapidKey' => config('firebase.web.vapid_key'),
        ]);
    }

    /**
     * Envía una notificación de prueba al usuario autenticado
     */
    public function sendTest(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Solo permitir al usuario con ID 1
        if ($user->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $result = $this->firebaseService->sendToUser(
            $user,
            'HIERROS PACO REYES',
            $request->input('message', 'Notificación de prueba desde tu aplicación'),
            ['url' => '/']
        );

        return response()->json($result);
    }
}
