<?php

namespace App\Jobs;

use App\Models\Etiqueta;
use App\Models\Maquina;
use App\Services\ProductionLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogProduccionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tipo,
        public array $data
    ) {}

    public function handle(): void
    {
        try {
            match ($this->tipo) {
                'coladas' => $this->logColadas(),
                'consumo' => $this->logConsumo(),
                'inicio' => $this->logInicio(),
                'cambio_estado' => $this->logCambioEstado(),
                default => null,
            };
        } catch (\Exception $e) {
            Log::warning('Error en LogProduccionJob', [
                'tipo' => $this->tipo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logColadas(): void
    {
        $etiqueta = Etiqueta::find($this->data['etiqueta_id']);
        $maquina = Maquina::find($this->data['maquina_id']);

        if ($etiqueta && $maquina) {
            ProductionLogger::logAsignacionColadas(
                $etiqueta,
                $maquina,
                $this->data['elementos_coladas'] ?? [],
                $this->data['warnings'] ?? []
            );
        }
    }

    private function logConsumo(): void
    {
        $etiqueta = Etiqueta::find($this->data['etiqueta_id']);
        $maquina = Maquina::find($this->data['maquina_id']);

        if ($etiqueta && $maquina) {
            ProductionLogger::logConsumoStockPorDiametro(
                $etiqueta,
                $maquina,
                $this->data['consumos'] ?? []
            );
        }
    }

    private function logInicio(): void
    {
        $etiqueta = Etiqueta::find($this->data['etiqueta_id']);
        $maquina = Maquina::find($this->data['maquina_id']);

        if ($etiqueta && $maquina) {
            ProductionLogger::logInicioFabricacion(
                $etiqueta,
                $maquina,
                $this->data['operario1'] ?? null,
                $this->data['operario2'] ?? null
            );
        }
    }

    private function logCambioEstado(): void
    {
        $etiqueta = Etiqueta::find($this->data['etiqueta_id']);
        $maquina = Maquina::find($this->data['maquina_id']);

        if ($etiqueta && $maquina) {
            ProductionLogger::logCambioEstadoFabricacion(
                $etiqueta,
                $maquina,
                $this->data['estado_anterior'] ?? '',
                $this->data['estado_nuevo'] ?? ''
            );
        }
    }
}
