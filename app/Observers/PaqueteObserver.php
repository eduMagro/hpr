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
     * Handle the Paquete "created" event.
     * Si la planilla tiene automatización activa, asocia el paquete a una salida.
     */
    public function created(Paquete $paquete): void
    {
        $this->asociarASalidaAutomatica($paquete);
    }

    /**
     * Asocia el paquete a una salida automáticamente si corresponde.
     */
    private function asociarASalidaAutomatica(Paquete $paquete): void
    {
        // Cargar la planilla si no está cargada
        $planilla = $paquete->planilla;

        if (!$planilla) {
            return;
        }

        // Verificar si la planilla tiene automatización activa
        if (!$planilla->automatizacion_salidas_activa) {
            return;
        }

        // Obtener obra_id y fecha_estimada_entrega de la planilla
        $obraId = $planilla->obra_id;
        $fechaEntrega = $planilla->getRawOriginal('fecha_estimada_entrega');

        if (!$obraId || !$fechaEntrega) {
            Log::warning("Paquete #{$paquete->id}: No se puede asociar automáticamente, falta obra_id o fecha_estimada_entrega en planilla #{$planilla->id}");
            return;
        }

        $fechaEntregaDate = Carbon::parse($fechaEntrega)->toDateString();
        $pesoPaquete = $paquete->peso ?? 0;

        try {
            DB::transaction(function () use ($paquete, $planilla, $obraId, $fechaEntregaDate, $pesoPaquete) {
                // Buscar salidas candidatas que tengan paquetes de planillas con misma obra y fecha
                $salidasCandidatas = $this->buscarSalidasCandidatas($obraId, $fechaEntregaDate);

                $salidaAsignada = null;

                foreach ($salidasCandidatas as $salida) {
                    $pesoActual = $this->calcularPesoSalida($salida);
                    $limitePeso = $this->obtenerLimitePeso($salida);

                    if (($pesoActual + $pesoPaquete) <= $limitePeso) {
                        $salidaAsignada = $salida;
                        break;
                    }
                }

                // Si no hay salida con espacio, crear una nueva
                if (!$salidaAsignada) {
                    $salidaAsignada = $this->crearNuevaSalida($fechaEntregaDate);
                    Log::info("Paquete #{$paquete->id}: Creada nueva salida #{$salidaAsignada->id} por automatización");
                }

                // Asociar el paquete a la salida
                $paquete->salidas()->syncWithoutDetaching([$salidaAsignada->id]);

                // Actualizar estado del paquete
                $paquete->estado = 'asignado_a_salida';
                $paquete->saveQuietly();

                // Asegurar que existe registro en salida_cliente
                $this->asegurarSalidaCliente($salidaAsignada, $planilla);

                Log::info("Paquete #{$paquete->id}: Asociado automáticamente a salida #{$salidaAsignada->id}");
            });
        } catch (\Throwable $e) {
            Log::error("Error al asociar paquete #{$paquete->id} a salida automática: " . $e->getMessage());
        }
    }

    /**
     * Busca salidas que tengan paquetes de planillas con la misma obra y fecha.
     */
    private function buscarSalidasCandidatas(int $obraId, string $fechaEntrega): \Illuminate\Support\Collection
    {
        return Salida::whereHas('paquetes.planilla', function ($query) use ($obraId, $fechaEntrega) {
            $query->where('obra_id', $obraId)
                  ->whereDate('fecha_estimada_entrega', $fechaEntrega);
        })
        ->where('estado', '!=', 'completada')
        ->orderBy('created_at', 'asc')
        ->get();
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
     * Crea una nueva salida para la fecha indicada.
     */
    private function crearNuevaSalida(string $fechaSalida): Salida
    {
        $salida = Salida::create([
            'fecha_salida' => $fechaSalida,
            'estado' => 'pendiente',
            'user_id' => auth()->id(),
        ]);

        // Generar código de salida
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
