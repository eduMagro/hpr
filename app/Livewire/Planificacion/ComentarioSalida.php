<?php

namespace App\Livewire\Planificacion;

use Livewire\Component;
use App\Models\Salida;

class ComentarioSalida extends Component
{
    public $salidaId;
    public $comentario = '';
    public $maxLength = 1000;
    public $isOpen = false;

    protected $listeners = ['abrirComentario'];

    protected $rules = [
        'comentario' => 'nullable|string|max:1000'
    ];

    public function abrirComentario($salidaId)
    {
        $this->salidaId = $salidaId;
        $salida = Salida::findOrFail($salidaId);
        $this->comentario = $salida->comentario ?? '';
        $this->isOpen = true;
    }

    public function guardar()
    {
        $this->validate();

        $salida = Salida::findOrFail($this->salidaId);
        $salida->comentario = $this->comentario;
        $salida->save();

        // Cerrar el modal primero
        $this->cerrar();

        // Emitir evento para actualizar el calendario
        // El evento será escuchado por JavaScript que mostrará la notificación
        $this->dispatch('comentarioGuardado',
            salidaId: $this->salidaId,
            comentario: $this->comentario
        );
    }

    public function cerrar()
    {
        $this->isOpen = false;
        $this->reset(['salidaId', 'comentario']);
    }

    public function render()
    {
        return view('livewire.planificacion.comentario-salida');
    }
}
