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
    protected $replyToAddress;
    protected $replyToName;

    public function __construct(Pedido $pedido, $fromAddress, $fromName, array $ccList = [], $replyToAddress = null, $replyToName = null)
    {
        $this->pedido = $pedido;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->ccList = $ccList;
        $this->replyToAddress = $replyToAddress;
        $this->replyToName = $replyToName;
    }


    public function build()
    {
        $mail = $this->from($this->fromAddress, $this->fromName)
            ->subject('Nuevo Pedido Generado')
            ->cc($this->ccList)
            ->view('emails.pedidos.pedido_creado')
            ->with([
                'pedido' => $this->pedido,
            ]);

        if ($this->replyToAddress) {
            $mail->replyTo($this->replyToAddress, $this->replyToName);
        }

        return $mail;
    }
}
