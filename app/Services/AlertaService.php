<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\AlertaLeida;
use Illuminate\Support\Facades\Log;

class AlertaService
{
    /**
     * Crea una alerta y su registro de leída inicial.
     *
     * @param  int         $emisorId
     * @param  int         $destinatarioId
     * @param  string      $mensaje
     * @param  string      $tipo              // 👈 siempre debes indicarlo al llamar
     * @param  string|null $destino
     * @param  string|null $destinatarioTxt
     * @return Alerta|null
     */
    public function crearAlerta(
        int $emisorId,
        int $destinatarioId,
        string $mensaje,
        string $tipo,  // 👈 sin valor por defecto, obligatorio
        ?string $destino = null,
        ?string $destinatarioTxt = null
    ): ?Alerta {
        try {
            $alerta = Alerta::create([
                'user_id_1'       => $emisorId,
                'user_id_2'       => null,
                'destino'         => $destino,
                'destinatario'    => $destinatarioTxt,
                'destinatario_id' => $destinatarioId,
                'mensaje'         => $mensaje,
                'tipo'            => $tipo, // 👈 tipo siempre pasado desde fuera
            ]);

            AlertaLeida::create([
                'alerta_id' => $alerta->id,
                'user_id'   => $destinatarioId,
                'leida_en'  => null,
            ]);

            return $alerta;

        } catch (\Exception $e) {
            Log::error('❌ Error creando alerta: ' . $e->getMessage());
            return null;
        }
    }
    // EJEMPLO PARA LLAMAR AL SERVICE
    //  $alertaService = app(AlertaService::class);

    //   $alertaService->crearAlerta(
    //         emisorId: $user->id,
    //         destinatarioId: $user->id,
    //         mensaje: 'Te has descargado ' . $nombreArchivo,
    //         tipo: 'usuario'
    //     );
}
