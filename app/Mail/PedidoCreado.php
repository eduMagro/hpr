<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Pedido;

class PedidoCreado extends Mailable
{
    use Queueable, SerializesModels;

    public $pedido;
    protected $fromAddress;
    protected $fromName;
    protected $ccList;

    public function __construct(Pedido $pedido, $fromAddress, $fromName, array $ccList = [])
    {
        $this->pedido = $pedido;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->ccList = $ccList;
    }

    public function build()
    {
        return $this->from($this->fromAddress, $this->fromName)
            ->subject('Nuevo Pedido Generado')
            ->cc($this->ccList)
            ->view('emails.pedidos.pedido_creado')
            ->with([
                'pedido' => $this->pedido,
            ]);
    }
}
