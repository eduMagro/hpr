<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirebaseNotificationService
{
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Obtiene un token de acceso OAuth2 para la API de FCM v1
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('firebase_access_token', 3500, function () {
            $credentialsPath = config('firebase.credentials_path');

            if (!file_exists($credentialsPath)) {
                Log::error('Firebase: No se encontró el archivo de credenciales', [
                    'path' => $credentialsPath
                ]);
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (!$credentials) {
                Log::error('Firebase: Error al leer las credenciales');
                return null;
            }

            $jwt = $this->createJwt($credentials);

            $response = Http::timeout(30)->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Firebase: Error al obtener access token', [
                'response' => $response->body()
            ]);

            return null;
        });
    }

    /**
     * Crea un JWT para autenticación con Google OAuth2
     */
    private function createJwt(array $credentials): string
    {
        $header = json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]);

        $now = time();
        $payload = json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);
        $signatureInput = $base64Header . '.' . $base64Payload;

        openssl_sign($signatureInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        return $signatureInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Envía una notificación a un usuario específico
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = $user->activeFcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'El usuario no tiene tokens FCM registrados',
            ];
        }

        $results = [];
        foreach ($tokens as $token) {
            $results[] = $this->sendToToken($token, $title, $body, $data);
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Envía una notificación a un token específico
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'No se pudo obtener el access token',
            ];
        }

        $projectId = config('firebase.project_id');
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'webpush' => [
                    'notification' => [
                        'icon' => '/imagenes/ico/android-chrome-192x192.png',
                        'badge' => '/imagenes/ico/favicon-32x32.png',
                        'requireInteraction' => true,
                        'sticky' => true,
                    ],
                    'fcm_options' => [
                        'link' => $data['url'] ?? config('app.url'),
                    ],
                ],
            ],
        ];

        if (!empty($data)) {
            $message['message']['data'] = array_map('strval', $data);
        }

        $response = Http::timeout(30)->withToken($this->accessToken)
            ->post($url, $message);

        if ($response->successful()) {
            UserFcmToken::where('token', $token)->first()?->markAsUsed();

            return [
                'success' => true,
                'message_id' => $response->json('name'),
            ];
        }

        $error = $response->json();

        // Si el token es inválido, lo desactivamos
        if (isset($error['error']['details'])) {
            foreach ($error['error']['details'] as $detail) {
                if (isset($detail['errorCode']) &&
                    in_array($detail['errorCode'], ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                    UserFcmToken::where('token', $token)->update(['is_active' => false]);
                    Log::info('Firebase: Token desactivado por error', ['token' => substr($token, 0, 20) . '...']);
                }
            }
        }

        Log::error('Firebase: Error al enviar notificación', [
            'token' => substr($token, 0, 20) . '...',
            'error' => $error,
        ]);

        return [
            'success' => false,
            'error' => $error,
        ];
    }

    /**
     * Envía una notificación a múltiples usuarios
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        $tokens = UserFcmToken::whereIn('user_id', $userIds)
            ->active()
            ->pluck('token')
            ->toArray();

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Envía una notificación a múltiples tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'responses' => [],
        ];

        foreach ($tokens as $token) {
            $result = $this->sendToToken($token, $title, $body, $data);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failure']++;
            }

            $results['responses'][] = $result;
        }

        return $results;
    }

    /**
     * Envía una notificación a todos los usuarios con un rol específico
     */
    public function sendToRole(string $role, string $title, string $body, array $data = []): array
    {
        $userIds = User::where('rol', $role)->pluck('id')->toArray();
        return $this->sendToUsers($userIds, $title, $body, $data);
    }

    /**
     * Envía una notificación a todos los usuarios de un departamento
     */
    public function sendToDepartamento(int $departamentoId, string $title, string $body, array $data = []): array
    {
        $userIds = User::whereHas('departamentos', function ($query) use ($departamentoId) {
            $query->where('departamento_id', $departamentoId);
        })->pluck('id')->toArray();

        return $this->sendToUsers($userIds, $title, $body, $data);
    }

    /**
     * Registra un nuevo token FCM para un usuario
     */
    public function registerToken(User $user, string $token, ?string $deviceType = 'web', ?string $deviceName = null): UserFcmToken
    {
        // Desactivar tokens antiguos del mismo dispositivo si existe
        UserFcmToken::where('token', $token)->delete();

        return UserFcmToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'device_type' => $deviceType,
            'device_name' => $deviceName,
            'is_active' => true,
        ]);
    }

    /**
     * Elimina un token FCM
     */
    public function removeToken(string $token): bool
    {
        return UserFcmToken::where('token', $token)->delete() > 0;
    }

    /**
     * Limpia tokens inactivos o antiguos
     */
    public function cleanupOldTokens(int $daysOld = 30): int
    {
        return UserFcmToken::where(function ($query) use ($daysOld) {
            $query->where('is_active', false)
                ->orWhere(function ($q) use ($daysOld) {
                    $q->whereNotNull('last_used_at')
                        ->where('last_used_at', '<', now()->subDays($daysOld));
                })
                ->orWhere(function ($q) use ($daysOld) {
                    $q->whereNull('last_used_at')
                        ->where('created_at', '<', now()->subDays($daysOld));
                });
        })->delete();
    }
}
