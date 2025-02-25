<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BotonCargando extends Component
{
    public $href;
    public $type;
    public $text;

    public function __construct($text = "Enviar", $href = null, $type = "button")
    {
        $this->href = $href;
        $this->type = $type;
        $this->text = $text;
    }

    public function render()
    {
        return view('components.boton-cargando');
    }
}
