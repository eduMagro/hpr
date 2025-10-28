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
        protected array $estadisticas = [],
        protected ?string $nombreArchivo = null
    ) {}

    public static function success(
        array $exitosas,
        array $fallidas,
        array $advertencias,
        array $estadisticas,
        ?string $nombreArchivo = null
    ): self {
        return new self(
            success: true,
            exitosas: $exitosas,
            fallidas: $fallidas,
            advertencias: $advertencias,
            estadisticas: $estadisticas,
            nombreArchivo: $nombreArchivo
        );
    }

    public static function error(array $errores, array $advertencias = [], ?string $nombreArchivo = null): self
    {
        return new self(
            success: false,
            fallidas: $errores,
            advertencias: $advertencias,
            nombreArchivo: $nombreArchivo
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

    public function nombreArchivo(): ?string
    {
        return $this->nombreArchivo;
    }

    public function totalProcesadas(): int
    {
        return count($this->exitosas) + count($this->fallidas);
    }

    public function tieneAdvertencias(): bool
    {
        return !empty($this->advertencias);
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

            $lineas = [];

            if ($this->nombreArchivo) {
                $lineas[] = "📄 Archivo: {$this->nombreArchivo}";
                $lineas[] = "";
            }

            $lineas = array_merge($lineas, $errores);

            return implode("\n", $lineas);
        }

        $lineas = [];

        // Detectar si es reimportación (solo 1 planilla procesada + elementos_eliminados en stats)
        $esReimportacion = count($this->exitosas) === 1 &&
            isset($this->estadisticas['elementos_eliminados']);

        // Título según tipo de operación
        if ($esReimportacion) {
            $lineas[] = "🔄 REIMPORTACIÓN DE PLANILLA";
            $lineas[] = "📋 Planilla: " . ($this->exitosas[0] ?? 'N/A');
        } else {
            $lineas[] = "📋 IMPORTACIÓN DE PLANILLAS";
        }

        // Nombre del archivo
        if ($this->nombreArchivo) {
            $lineas[] = "📄 Archivo: {$this->nombreArchivo}";
        }

        $lineas[] = ""; // Línea en blanco

        // Estadísticas según tipo
        if ($esReimportacion) {
            // Mensaje específico para reimportación
            $stats = $this->estadisticas;

            if (isset($stats['elementos_eliminados'])) {
                $lineas[] = sprintf("🗑️ Elementos pendientes eliminados: %d", $stats['elementos_eliminados']);
            }

            if (isset($stats['elementos_creados'])) {
                $lineas[] = sprintf("📦 Nuevos elementos creados: %d", $stats['elementos_creados']);
            }

            if (isset($stats['etiquetas_creadas'])) {
                $lineas[] = sprintf("🏷️ Etiquetas creadas: %d", $stats['etiquetas_creadas']);
            }

            $lineas[] = "";
            $lineas[] = "✅ La planilla se actualizó correctamente";
            $lineas[] = "ℹ️ Los datos originales (fecha de entrega, cliente, obra) se mantuvieron sin cambios";
        } else {
            // Mensaje para importación normal
            $lineas[] = sprintf("✅ Planillas importadas exitosamente: %d", count($this->exitosas));

            if (!empty($this->estadisticas)) {
                $stats = $this->estadisticas;

                if (isset($stats['elementos_creados'])) {
                    $lineas[] = sprintf("📦 Elementos creados: %d", $stats['elementos_creados']);
                }

                if (isset($stats['etiquetas_creadas'])) {
                    $lineas[] = sprintf("🏷️ Etiquetas creadas: %d", $stats['etiquetas_creadas']);
                }

                if (isset($stats['tiempo_total'])) {
                    $lineas[] = sprintf("⏱️ Tiempo total: %.2f segundos", $stats['tiempo_total']);
                }
            }
        }

        // Planillas fallidas
        if (!empty($this->fallidas)) {
            $lineas[] = "";
            $lineas[] = sprintf("❌ Planillas con errores: %d", count($this->fallidas));

            foreach ($this->fallidas as $fallida) {
                $codigo = is_array($fallida) ? ($fallida['codigo'] ?? 'Desconocido') : 'Desconocido';
                $error = is_array($fallida) ? ($fallida['error'] ?? $fallida) : $fallida;
                $lineas[] = sprintf("   • %s: %s", $codigo, $error);
            }
        }

        // Advertencias
        if (!empty($this->advertencias)) {
            $lineas[] = "";
            $lineas[] = "⚠️ ADVERTENCIAS:";

            foreach ($this->advertencias as $advertencia) {
                $lineas[] = sprintf("   • %s", $advertencia);
            }
        }

        return implode("\n", $lineas);
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
            'tiene_advertencias' => $this->tieneAdvertencias(),
            'nombre_archivo' => $this->nombreArchivo,
        ];
    }
}
