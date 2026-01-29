<?php

namespace App\Helpers;

use App\Models\Festivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FechaEntregaHelper
{
    /**
     * Ajusta una fecha de entrega para que no caiga en fin de semana ni festivo.
     * - Sábado → Viernes (día anterior)
     * - Domingo → Lunes (día siguiente)
     * - Festivo → Aplica la misma lógica recursivamente
     *
     * @param Carbon|string|null $fecha
     * @return Carbon|null
     */
    public static function ajustar($fecha): ?Carbon
    {
        if (!$fecha) {
            return null;
        }

        $fecha = $fecha instanceof Carbon ? $fecha->copy() : Carbon::parse($fecha);

        // Obtener festivos del año (con cache de 1 hora)
        $festivos = self::obtenerFestivos($fecha->year);

        // Máximo 10 iteraciones para evitar bucles infinitos
        $intentos = 0;
        while ($intentos < 10) {
            $intentos++;
            $fechaStr = $fecha->toDateString();

            // Sábado (6) → Viernes
            if ($fecha->dayOfWeek === Carbon::SATURDAY) {
                $fecha->subDay();
                continue;
            }

            // Domingo (0) → Lunes
            if ($fecha->dayOfWeek === Carbon::SUNDAY) {
                $fecha->addDay();
                continue;
            }

            // Festivo → Aplicar misma lógica
            if (in_array($fechaStr, $festivos)) {
                // Si es viernes festivo → jueves
                if ($fecha->dayOfWeek === Carbon::FRIDAY) {
                    $fecha->subDay();
                }
                // Si es lunes festivo → martes
                elseif ($fecha->dayOfWeek === Carbon::MONDAY) {
                    $fecha->addDay();
                }
                // Otro día entre semana festivo → día anterior
                else {
                    $fecha->subDay();
                }
                continue;
            }

            // Si llegamos aquí, la fecha es válida (día hábil)
            break;
        }

        return $fecha;
    }

    /**
     * Calcula la fecha de entrega a partir de una fecha base + días.
     * Aplica el ajuste automáticamente.
     *
     * @param Carbon|string|null $fechaBase
     * @param int $dias
     * @param int $hora
     * @param int $minutos
     * @return Carbon|null
     */
    public static function calcular($fechaBase, int $dias = 7, int $hora = 10, int $minutos = 0): ?Carbon
    {
        if (!$fechaBase) {
            return null;
        }

        $fecha = $fechaBase instanceof Carbon ? $fechaBase->copy() : Carbon::parse($fechaBase);
        $fecha->addDays($dias)->setTime($hora, $minutos, 0);

        return self::ajustar($fecha);
    }

    /**
     * Obtiene los festivos de un año como array de fechas string.
     * Usa cache para evitar consultas repetidas.
     *
     * @param int $year
     * @return array
     */
    private static function obtenerFestivos(int $year): array
    {
        return Cache::remember("festivos_{$year}", 3600, function () use ($year) {
            return Festivo::whereYear('fecha', $year)
                ->pluck('fecha')
                ->map(fn($f) => Carbon::parse($f)->toDateString())
                ->toArray();
        });
    }

    /**
     * Verifica si una fecha es día hábil (no fin de semana ni festivo).
     *
     * @param Carbon|string $fecha
     * @return bool
     */
    public static function esDiaHabil($fecha): bool
    {
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        // Fin de semana
        if ($fecha->isWeekend()) {
            return false;
        }

        // Festivo
        $festivos = self::obtenerFestivos($fecha->year);
        if (in_array($fecha->toDateString(), $festivos)) {
            return false;
        }

        return true;
    }
}
