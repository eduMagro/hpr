<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BotonEliminar extends Component
{
    public $action;

    /**
     * Create a new component instance.
     *
     * @param string $action
     */
    public function __construct($action)
    {
        $this->action = $action;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.boton-eliminar');
    }
}
