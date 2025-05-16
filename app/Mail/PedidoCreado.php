<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Pedido;


class PedidoCreado extends Mailable
{
    use Queueable, SerializesModels;

    public $pedido;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido;
    }

    public function build()
    {
        return $this->subject('Nuevo Pedido Generado')
            ->cc(['sebastian.duran@pacoreyes.com', 'indiana.tirado@pacoreyes.com', 'alberto.mayo@pacoreyes.com', 'josemanuel.amuedo@pacoreyes.com'])
            ->view('emails.pedidos.pedido_creado')
            ->with([
                'pedido' => $this->pedido,
            ]);
    }
}
