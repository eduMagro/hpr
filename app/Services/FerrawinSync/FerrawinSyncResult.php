<?php

namespace App\Services\FerrawinSync;

/**
 * Resultado de una sincronización FerraWin.
 */
class FerrawinSyncResult
{
    public function __construct(
        public readonly string $estado,
        public readonly array $stats,
        public readonly float $duracion,
        public readonly ?string $error = null
    ) {}

    /**
     * Indica si la sincronización fue exitosa.
     */
    public function esExitosa(): bool
    {
        return in_array($this->estado, ['completado', 'sin_cambios', 'sin_datos']);
    }

    /**
     * Indica si hubo errores parciales.
     */
    public function tieneErrores(): bool
    {
        return !empty($this->stats['errores']) || $this->stats['planillas_fallidas'] > 0;
    }

    /**
     * Obtiene un resumen legible del resultado.
     */
    public function resumen(): string
    {
        $lineas = [];

        switch ($this->estado) {
            case 'completado':
                $lineas[] = "✅ Sincronización completada";
                break;
            case 'sin_cambios':
                $lineas[] = "✅ Sin cambios pendientes";
                break;
            case 'sin_datos':
                $lineas[] = "ℹ️ No se encontraron planillas en FerraWin";
                break;
            case 'error':
                $lineas[] = "❌ Error en sincronización: {$this->error}";
                break;
        }

        $lineas[] = "";
        $lineas[] = "📊 Estadísticas:";
        $lineas[] = "   - Planillas encontradas: {$this->stats['planillas_encontradas']}";
        $lineas[] = "   - Planillas nuevas: {$this->stats['planillas_nuevas']}";
        $lineas[] = "   - Planillas actualizadas: {$this->stats['planillas_actualizadas']}";
        $lineas[] = "   - Sincronizadas correctamente: {$this->stats['planillas_sincronizadas']}";
        $lineas[] = "   - Fallidas: {$this->stats['planillas_fallidas']}";
        $lineas[] = "   - Elementos creados: {$this->stats['elementos_creados']}";
        $lineas[] = "";
        $lineas[] = "⏱️ Duración: {$this->duracion} segundos";

        if (!empty($this->stats['errores'])) {
            $lineas[] = "";
            $lineas[] = "❌ Errores:";
            foreach ($this->stats['errores'] as $error) {
                $lineas[] = "   - {$error}";
            }
        }

        if (!empty($this->stats['advertencias'])) {
            $lineas[] = "";
            $lineas[] = "⚠️ Advertencias:";
            foreach ($this->stats['advertencias'] as $advertencia) {
                $lineas[] = "   - {$advertencia}";
            }
        }

        return implode("\n", $lineas);
    }

    /**
     * Convierte a array para serialización.
     */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado,
            'exitoso' => $this->esExitosa(),
            'tiene_errores' => $this->tieneErrores(),
            'stats' => $this->stats,
            'duracion' => $this->duracion,
            'error' => $this->error,
        ];
    }
}
