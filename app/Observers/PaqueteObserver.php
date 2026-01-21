<?php

namespace App\Observers;

use App\Models\Paquete;
use App\Models\Salida;
use App\Models\SalidaCliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaqueteObserver
{
    /**
     * Límite de peso por defecto (28 toneladas en kg)
     */
    const LIMITE_PESO_DEFAULT = 28000;

    /**
     * Maneja el evento "created" del Paquete.
     * Asocia automáticamente el paquete a una salida según obra_id + fecha.
     */
    public function created(Paquete $paquete): void
    {
        $this->asociarASalidaAutomatica($paquete);
    }

    /**
     * Asocia el paquete a una salida automáticamente.
     * Busca una salida con la misma obra_id y fecha_salida.
     * Si no existe o está llena, crea una nueva.
     */
    private function asociarASalidaAutomatica(Paquete $paquete): void
    {
        // Cargar la planilla
        $planilla = $paquete->planilla;

        if (!$planilla) {
            return;
        }

        // Obtener obra_id de la planilla
        $obraId = $planilla->obra_id;

        if (!$obraId) {
            Log::warning("Paquete #{$paquete->id}: No se puede asociar, planilla #{$planilla->id} no tiene obra_id");
            return;
        }

        // Determinar la fecha de entrega (de elementos o de planilla)
        $fechaEntrega = $this->determinarFechaEntrega($paquete, $planilla);

        if (!$fechaEntrega) {
            Log::warning("Paquete #{$paquete->id}: No se puede asociar, no hay fecha de entrega");
            return;
        }

        $fechaEntregaDate = Carbon::parse($fechaEntrega)->toDateString();
        $pesoPaquete = $paquete->peso ?? 0;

        try {
            DB::transaction(function () use ($paquete, $planilla, $obraId, $fechaEntregaDate, $pesoPaquete) {
                // Buscar salida existente con misma obra_id y fecha_salida
                $salidaAsignada = $this->buscarSalidaDisponible($obraId, $fechaEntregaDate, $pesoPaquete);

                // Si no hay salida disponible, crear una nueva
                if (!$salidaAsignada) {
                    $salidaAsignada = $this->crearNuevaSalida($obraId, $fechaEntregaDate);
                    Log::info("Paquete #{$paquete->id}: Creada nueva salida #{$salidaAsignada->id} para obra #{$obraId}, fecha {$fechaEntregaDate}");
                }

                // Asociar el paquete a la salida
                $paquete->salidas()->syncWithoutDetaching([$salidaAsignada->id]);

                // Actualizar estado del paquete
                $paquete->estado = 'asignado_a_salida';
                $paquete->saveQuietly();

                // Asegurar que existe registro en salida_cliente
                $this->asegurarSalidaCliente($salidaAsignada, $planilla);

                Log::info("Paquete #{$paquete->id}: Asociado a salida #{$salidaAsignada->id}");
            });
        } catch (\Throwable $e) {
            Log::error("Error al asociar paquete #{$paquete->id} a salida: " . $e->getMessage());
        }
    }

    /**
     * Determina la fecha de entrega para un paquete.
     * Prioriza la fecha_entrega de los elementos; si no existe, usa la de la planilla.
     */
    private function determinarFechaEntrega(Paquete $paquete, $planilla): ?string
    {
        // Cargar etiquetas con sus elementos
        $paquete->load('etiquetas.elementos');

        // Buscar fecha_entrega en los elementos del paquete
        foreach ($paquete->etiquetas as $etiqueta) {
            foreach ($etiqueta->elementos as $elemento) {
                if ($elemento->fecha_entrega) {
                    return $elemento->getRawOriginal('fecha_entrega') ?? $elemento->fecha_entrega->toDateString();
                }
            }
        }

        // Si ningún elemento tiene fecha_entrega, usar la de la planilla
        return $planilla->getRawOriginal('fecha_estimada_entrega');
    }

    /**
     * Busca una salida disponible con la misma obra_id y fecha_salida que tenga espacio.
     */
    private function buscarSalidaDisponible(int $obraId, string $fechaEntrega, float $pesoPaquete): ?Salida
    {
        $salidas = Salida::where('obra_id', $obraId)
            ->whereDate('fecha_salida', $fechaEntrega)
            ->where('estado', '!=', 'completada')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($salidas as $salida) {
            $pesoActual = $this->calcularPesoSalida($salida);
            $limitePeso = $this->obtenerLimitePeso($salida);

            if (($pesoActual + $pesoPaquete) <= $limitePeso) {
                return $salida;
            }
        }

        return null;
    }

    /**
     * Calcula el peso total actual de una salida.
     */
    private function calcularPesoSalida(Salida $salida): float
    {
        return $salida->paquetes()->sum('peso') ?? 0;
    }

    /**
     * Obtiene el límite de peso para una salida.
     * Si tiene camión asignado, usa la capacidad del camión.
     * Si no, usa el límite por defecto de 28tn.
     */
    private function obtenerLimitePeso(Salida $salida): float
    {
        if ($salida->camion_id && $salida->camion) {
            return $salida->camion->capacidad ?? self::LIMITE_PESO_DEFAULT;
        }

        return self::LIMITE_PESO_DEFAULT;
    }

    /**
     * Crea una nueva salida para la obra y fecha indicadas.
     */
    private function crearNuevaSalida(int $obraId, string $fechaSalida): Salida
    {
        $salida = Salida::create([
            'obra_id' => $obraId,
            'fecha_salida' => $fechaSalida,
            'estado' => 'pendiente',
            'user_id' => auth()->id(),
        ]);

        // Generar código de salida (AS = Automática Salida)
        $codigoSalida = 'AS' . substr(date('Y'), 2) . '/' . str_pad($salida->id, 4, '0', STR_PAD_LEFT);
        $salida->codigo_salida = $codigoSalida;
        $salida->save();

        return $salida;
    }

    /**
     * Asegura que existe un registro en salida_cliente para la combinación salida/cliente/obra.
     */
    private function asegurarSalidaCliente(Salida $salida, $planilla): void
    {
        $clienteId = $planilla->cliente_id;
        $obraId = $planilla->obra_id;

        if (!$clienteId || !$obraId) {
            return;
        }

        SalidaCliente::firstOrCreate([
            'salida_id' => $salida->id,
            'cliente_id' => $clienteId,
            'obra_id' => $obraId,
        ], [
            'horas_paralizacion' => 0,
            'importe_paralizacion' => 0,
            'horas_grua' => 0,
            'importe_grua' => 0,
            'horas_almacen' => 0,
            'importe' => 0,
        ]);
    }
}
