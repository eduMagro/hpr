<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportProgress
{
    protected static function key(string $id): string
    {
        return "import:$id";
    }

    public static function init(string $id, int $total, string $message = ''): void
    {
        Cache::put(self::key($id), [
            'status'  => 'running',
            'current' => 0,
            'total'   => max(1, $total),
            'percent' => 0,
            'message' => $message,
            'report'  => null,            // 👈 nuevo
        ], now()->addMinutes(60));
    }

    public static function advance(string $id, int $step = 1, string $message = null): void
    {
        $st = Cache::get(self::key($id), []);
        $st['current'] = min(($st['current'] ?? 0) + max(0, $step), $st['total'] ?? 1);
        $st['percent'] = (int) round((($st['current'] ?? 0) / max(1, $st['total'] ?? 1)) * 100);
        if ($message !== null) $st['message'] = $message;
        Cache::put(self::key($id), $st, now()->addMinutes(60));
    }

    // 👇 acepta payload extra (p.ej. ['fallidas' => [...], 'exitosas' => [...]])
    public static function setDone(string $id, string $message = 'Done', array $report = null): void
    {
        $st = Cache::get(self::key($id), []);
        $st['status']  = 'done';
        $st['percent'] = 100;
        $st['message'] = $message;
        if ($report !== null) $st['report'] = $report;
        Cache::put(self::key($id), $st, now()->addMinutes(60));
    }

    public static function setError(string $id, string $message = 'Error', array $report = null): void
    {
        $st = Cache::get(self::key($id), []);
        $st['status']  = 'error';
        $st['message'] = $message;
        if ($report !== null) $st['report'] = $report;
        Cache::put(self::key($id), $st, now()->addMinutes(60));
    }

    public static function get(string $id): array
    {
        return Cache::get(self::key($id), [
            'status' => 'unknown',
            'current' => 0,
            'total' => 0,
            'percent' => 0,
            'message' => '',
            'report' => null,
        ]);
    }
}
