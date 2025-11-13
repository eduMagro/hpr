<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportProgress
{
    // estado: waiting|running|done|error
    public static function init(string $id, int $total, string $message = 'Preparando...'): void
    {
        Cache::put(self::key($id), [
            'status'   => 'running',
            'total'    => max(1, $total),
            'current'  => 0,
            'percent'  => 0,
            'message'  => $message,
            'updated'  => now()->toIso8601String(),
        ], now()->addHours(2));
    }

    public static function advance(string $id, int $step = 1, ?string $message = null): void
    {
        Cache::lock('iprog:lock:' . $id, 2)->block(2, function () use ($id, $step, $message) {
            $data = Cache::get(self::key($id));
            if (!$data) return;
            $data['current'] = max(0, ($data['current'] ?? 0) + $step);
            $total = max(1, (int)($data['total'] ?? 1));
            $data['percent'] = min(100, (int) floor(($data['current'] / $total) * 100));
            if ($message !== null) $data['message'] = $message;
            $data['updated'] = now()->toIso8601String();
            Cache::put(self::key($id), $data, now()->addHours(2));
        });
    }

    public static function setDone(string $id, ?string $message = 'Completado.'): void
    {
        $data = Cache::get(self::key($id), []);
        $data['status']  = 'done';
        $data['percent'] = 100;
        $data['message'] = $message ?? $data['message'] ?? '';
        $data['updated'] = now()->toIso8601String();
        Cache::put(self::key($id), $data, now()->addHours(2));
    }

    public static function setError(string $id, string $message): void
    {
        $data = Cache::get(self::key($id), []);
        $data['status']  = 'error';
        $data['message'] = $message;
        $data['updated'] = now()->toIso8601String();
        Cache::put(self::key($id), $data, now()->addHours(2));
    }

    public static function get(string $id): array
    {
        return Cache::get(self::key($id), [
            'status' => 'waiting',
            'percent' => 0,
            'message' => 'Esperando...',
        ]);
    }

    private static function key(string $id): string
    {
        return "iprog:{$id}";
    }
}
