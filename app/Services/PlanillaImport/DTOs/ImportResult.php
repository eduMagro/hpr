<?php

namespace App\Services\PlanillaImport\DTOs;

/**
 * Resultado final de una importación completa.
 */
class ImportResult
{
    protected function __construct(
        protected bool $success,
        protected array $exitosas = [],
        protected array $fallidas = [],
        protected array $advertencias = [],
        protected array $estadisticas = []
    ) {}

    public static function success(
        array $exitosas,
        array $fallidas,
        array $advertencias,
        array $estadisticas
    ): self {
        return new self(
            success: true,
            exitosas: $exitosas,
            fallidas: $fallidas,
            advertencias: $advertencias,
            estadisticas: $estadisticas
        );
    }

    public static function error(array $errores, array $advertencias = []): self
    {
        return new self(
            success: false,
            fallidas: $errores,
            advertencias: $advertencias
        );
    }


    public function esExitoso(): bool
    {
        return $this->success;
    }

    public function exitosas(): array
    {
        return $this->exitosas;
    }

    public function fallidas(): array
    {
        return $this->fallidas;
    }

    public function advertencias(): array
    {
        return $this->advertencias;
    }

    public function estadisticas(): array
    {
        return $this->estadisticas;
    }

    public function totalProcesadas(): int
    {
        return count($this->exitosas) + count($this->fallidas);
    }

    /**
     * Genera un mensaje legible para mostrar al usuario.
     *
     * @return string
     */
    public function mensaje(): string
    {
        if (!$this->success) {
            $errores = is_array($this->fallidas) && isset($this->fallidas[0]) && is_string($this->fallidas[0])
                ? $this->fallidas
                : array_column($this->fallidas, 'error');

            return implode(' ', $errores);
        }

        $partes = [];

        $partes[] = sprintf("✅ Importadas: %d", count($this->exitosas));

        if (!empty($this->fallidas)) {
            $partes[] = sprintf("❌ Fallidas: %d", count($this->fallidas));

            $detalles = collect($this->fallidas)
                ->map(fn($f) => is_array($f) ? "{$f['codigo']}: {$f['error']}" : $f)
                ->implode('; ');

            $partes[] = "| Errores: {$detalles}";
        }

        if (!empty($this->advertencias)) {
            $partes[] = "⚠️ " . implode(' ⚠️ ', $this->advertencias);
        }

        if (!empty($this->estadisticas)) {
            $stats = $this->estadisticas;

            if (isset($stats['elementos_creados'])) {
                $partes[] = sprintf("| Elementos: %d", $stats['elementos_creados']);
            }

            if (isset($stats['tiempo_total'])) {
                $partes[] = sprintf("| Tiempo: %.2fs", $stats['tiempo_total']);
            }
        }

        return implode(' ', $partes);
    }

    /**
     * Convierte el resultado a un array para JSON.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'exitosas' => $this->exitosas,
            'fallidas' => $this->fallidas,
            'advertencias' => $this->advertencias,
            'estadisticas' => $this->estadisticas,
            'mensaje' => $this->mensaje(),
        ];
    }
}
