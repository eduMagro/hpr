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
        $pdfPath = storage_path("app/public/trazabilidad/salida_{$this->salida->id}.pdf");

        return $this->subject('Salida completada')
            ->view('emails.salidas.salida-completada-trazabilidad-enviada')
            ->attach($pdfPath, [
                'as' => 'trazabilidad.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
