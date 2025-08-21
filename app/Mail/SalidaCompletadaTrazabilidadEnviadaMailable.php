<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Salida;

class SalidaCompletadaTrazabilidadEnviadaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $salida;

    public function __construct(Salida $salida)
    {
        $this->salida = $salida;
    }

    public function build()
    {
        return $this->subject('Salida completada')
            ->view('emails.salidas.salida-completada-trazabilidad-enviada');
    }
}
