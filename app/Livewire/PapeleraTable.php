<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class PapeleraTable extends Component
{
    use WithPagination;

    public string $modelKey;
    public string $modelClass;
    public string $nombre;
    public string $icono;
    public array $campos;
    public array $relaciones;
    public int $perPage = 10;

    public function mount(
        string $modelKey,
        string $modelClass,
        string $nombre,
        string $icono,
        array $campos,
        array $relaciones = []
    ) {
        $this->modelKey = $modelKey;
        $this->modelClass = $modelClass;
        $this->nombre = $nombre;
        $this->icono = $icono;
        $this->campos = $campos;
        $this->relaciones = $relaciones;
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function resetear()
    {
        $this->perPage = 10;
        $this->resetPage();
    }

    public function restaurar(int $id)
    {
        $model = $this->modelClass::onlyTrashed()->find($id);

        if ($model) {
            $model->restore();
            $this->dispatch('registro-restaurado');
        }
    }

    public function render()
    {
        $query = $this->modelClass::onlyTrashed();

        if (!empty($this->relaciones)) {
            $query->with($this->relaciones);
        }

        $registros = $query->orderBy('deleted_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.papelera-table', [
            'registros' => $registros,
            'total' => $registros->total(),
        ]);
    }
}
