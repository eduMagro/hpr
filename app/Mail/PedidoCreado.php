<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf; // ← IMPORTANTE

class PedidoCreado extends Mailable
{
    use Queueable, SerializesModels;

    public $pedido;
    protected $fromAddress;
    protected $fromName;
    protected $ccList;
    protected $replyToAddress;
    protected $replyToName;

    public function __construct(
        Pedido $pedido,
        $fromAddress,
        $fromName,
        array $ccList = [],
        $replyToAddress = null,
        $replyToName = null
    ) {
        $this->pedido          = $pedido;
        $this->fromAddress     = $fromAddress;
        $this->fromName        = $fromName;
        $this->ccList          = $ccList;
        $this->replyToAddress  = $replyToAddress;
        $this->replyToName     = $replyToName;
    }

    public function build()
    {
        // ===== Cuerpo del email (simple y fijo) =====
        $fechaPedido = $this->pedido->created_at
            ->setTimezone(new \DateTimeZone('Europe/Madrid'))
            ->format('d/m/Y');

        $bodyHtml = "<!DOCTYPE html>
<html lang=\"es\">
<head><meta charset=\"UTF-8\"><title>Confirmación de pedido</title></head>
<body style=\"margin:0;padding:0;background:#ffffff;font-family:Segoe UI, Arial, sans-serif;color:#111827;\">
  <p style=\"margin:0 0 12px;font-size:14px;\">Buenos días,</p>
  <p style=\"margin:0 0 12px;font-size:14px;\">
    Le informamos que se ha generado un nuevo pedido con fecha <strong>{$fechaPedido}</strong>.
    Adjuntamos el documento en PDF con todos los detalles.
  </p>
  <p style=\"margin:0;font-size:12px;color:#6b7280;\">Departamento de Compras · Hierros Paco Reyes</p>
</body>
</html>";

        $this->html($bodyHtml);

        $logoPath = public_path('imagenes/ico/android-chrome-192x192.png');
        $logoDataUri = null;
        if (file_exists($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // generar PDF usando SOLO la vista de PDF
        $pdf = Pdf::loadView('emails.pedidos.pedido_creado', [
            'pedido' => $this->pedido,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4'); // por si acaso


        $mail = $this->from($this->fromAddress, $this->fromName)
            ->subject('Confirmación de pedido ' . $this->pedido->codigo)
            ->cc($this->ccList)
            ->attachData($pdf->output(), 'Pedido-' . $this->pedido->codigo . '.pdf', [
                'mime' => 'application/pdf',
            ]);

        if ($this->replyToAddress) {
            $mail->replyTo($this->replyToAddress, $this->replyToName ?: $this->fromName);
        }

        return $mail;
    }
}
