<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\Configuracion;
use App\Models\Planilla;
use App\Models\User;
use Illuminate\Support\Collection;

class PlanillaAprobacionAlertaService
{
    const CONFIG_KEY = 'planilla_aprobacion_destinatarios';

    /**
     * Notifica la aprobación de planillas a los destinatarios configurados
     */
    public function notificarAprobacion(Collection $planillas, User $aprobador): int
    {
        $destinatarios = $this->getDestinatarios();

        if (empty($destinatarios)) {
            return 0;
        }

        $count = $planillas->count();
        $codigos = $planillas->pluck('codigo')->take(3)->implode(', ');

        $mensaje = $count === 1
            ? "Se ha aprobado la planilla {$codigos}"
            : "Se han aprobado {$count} planillas: {$codigos}" . ($count > 3 ? '...' : '');

        // Crear alerta
        $alerta = Alerta::create([
            'user_id_1' => $aprobador->id,
            'mensaje' => $mensaje,
            'tipo' => 'aprobacion_planilla',
        ]);

        $notificados = 0;

        // Crear entrada en alertas_users para cada destinatario
        foreach ($destinatarios as $userId) {
            if ($userId != $aprobador->id) { // No notificar al que aprobó
                AlertaLeida::create([
                    'alerta_id' => $alerta->id,
                    'user_id' => $userId,
                    'leida_en' => null,
                ]);
                $notificados++;
            }
        }

        return $notificados;
    }

    /**
     * Obtiene los IDs de usuarios configurados como destinatarios
     */
    public function getDestinatarios(): array
    {
        $config = Configuracion::where('clave', self::CONFIG_KEY)->first();
        return $config ? json_decode($config->valor, true) ?? [] : [];
    }

    /**
     * Guarda los IDs de usuarios como destinatarios
     */
    public function setDestinatarios(array $userIds): void
    {
        Configuracion::updateOrCreate(
            ['clave' => self::CONFIG_KEY],
            [
                'valor' => json_encode(array_values(array_unique(array_map('intval', $userIds)))),
                'descripcion' => 'IDs de usuarios que reciben alertas de aprobación de planillas'
            ]
        );
    }
}
