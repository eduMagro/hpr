<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Maquina;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Máquinas')]
class MaquinasIndex extends Component
{
    public function render()
    {
        $registrosMaquina = Maquina::orderBy('codigo')->get();

        return view('livewire.maquinas-index', [
            'registrosMaquina' => $registrosMaquina
        ]);
    }
}
