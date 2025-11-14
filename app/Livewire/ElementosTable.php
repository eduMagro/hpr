<?php

namespace App\Livewire;

use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Planilla;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ElementosTable extends Component
{
    use WithPagination;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $elemento_id = '';

    #[Url]
    public $codigo = '';

    #[Url]
    public $codigo_planilla = '';

    #[Url]
    public $etiqueta = '';

    #[Url]
    public $subetiqueta = '';

    #[Url]
    public $dimensiones = '';

    #[Url]
    public $diametro = '';

    #[Url]
    public $barras = '';

    #[Url]
    public $maquina = '';

    #[Url]
    public $maquina_2 = '';

    #[Url]
    public $maquina3 = '';

    #[Url]
    public $producto1 = '';

    #[Url]
    public $producto2 = '';

    #[Url]
    public $producto3 = '';

    #[Url]
    public $figura = '';

    #[Url]
    public $peso = '';

    #[Url]
    public $longitud = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $planilla_id = '';

    #[Url]
    public $sort = '';

    #[Url]
    public $order = 'asc';

    public $perPage = 10;

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function aplicarFiltros($query)
    {
        // Filtros específicos
        $filters = [
            'elemento_id' => 'id',
            'figura' => 'figura',
            'dimensiones' => 'dimensiones',
            'planilla_id' => 'planilla_id',
            'barras' => 'barras'
        ];

        foreach ($filters as $property => $column) {
            if (!empty($this->$property)) {
                $query->where($column, 'like', '%' . trim($this->$property) . '%');
            }
        }

        // Código (puede ser múltiple separado por comas)
        if (!empty($this->codigo)) {
            $codigos = explode(',', $this->codigo);
            if (count($codigos) > 1) {
                $query->whereIn('codigo', $codigos);
            } else {
                $query->where('codigo', 'like', '%' . $codigos[0] . '%');
            }
        }

        // Código planilla
        if (!empty($this->codigo_planilla)) {
            $input = trim($this->codigo_planilla);

            $query->whereHas('planilla', function ($q) use ($input) {
                if (preg_match('/^(\d{4})-(\d{1,6})$/', $input, $m)) {
                    $anio = $m[1];
                    $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                    $codigoFormateado = "{$anio}-{$num}";
                    $q->where('planillas.codigo', 'like', "%{$codigoFormateado}%");
                    return;
                }

                if (preg_match('/^\d{1,6}$/', $input)) {
                    $q->where('planillas.codigo', 'like', "%{$input}%");
                    return;
                }

                $q->where('planillas.codigo', 'like', "%{$input}%");
            });
        }

        // Etiqueta
        if (!empty($this->etiqueta)) {
            $query->whereHas('etiquetaRelacion', function ($q) {
                $q->where('id', 'like', '%' . $this->etiqueta . '%');
            });
        }

        if (!empty($this->subetiqueta)) {
            $query->where('etiqueta_sub_id', 'like', '%' . $this->subetiqueta . '%');
        }

        // Máquinas
        if (!empty($this->maquina)) {
            $query->whereHas('maquina', function ($q) {
                $q->where('nombre', 'like', "%{$this->maquina}%");
            });
        }

        if (!empty($this->maquina_2)) {
            $query->whereHas('maquina_2', function ($q) {
                $q->where('nombre', 'like', "%{$this->maquina_2}%");
            });
        }

        if (!empty($this->maquina3)) {
            $query->whereHas('maquina_3', function ($q) {
                $q->where('nombre', 'like', "%{$this->maquina3}%");
            });
        }

        // Productos
        if (!empty($this->producto1)) {
            $query->whereHas('producto', function ($q) {
                $q->where('nombre', 'like', "%{$this->producto1}%");
            });
        }

        if (!empty($this->producto2)) {
            $query->whereHas('producto2', function ($q) {
                $q->where('nombre', 'like', "%{$this->producto2}%");
            });
        }

        if (!empty($this->producto3)) {
            $query->whereHas('producto3', function ($q) {
                $q->where('nombre', 'like', "%{$this->producto3}%");
            });
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', 'like', "%{$this->estado}%");
        }

        if (!empty($this->peso)) {
            $query->where('peso', 'like', "%{$this->peso}%");
        }

        if (!empty($this->diametro)) {
            $query->where('diametro', 'like', "%{$this->diametro}%");
        }

        if (!empty($this->longitud)) {
            $query->where('longitud', 'like', "%{$this->longitud}%");
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $ordenamientos = [
            'id' => 'id',
            'codigo' => 'codigo',
            'figura' => 'figura',
            'peso' => 'peso',
            'diametro' => 'diametro',
            'longitud' => 'longitud',
            'estado' => 'estado',
        ];

        if (!empty($this->sort) && isset($ordenamientos[$this->sort])) {
            $query->orderBy($ordenamientos[$this->sort], $this->order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function sortBy($column)
    {
        if ($this->sort === $column) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->order = 'asc';
        }
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'elemento_id', 'codigo', 'codigo_planilla', 'etiqueta', 'subetiqueta',
            'dimensiones', 'diametro', 'barras', 'maquina', 'maquina_2',
            'maquina3', 'producto1', 'producto2', 'producto3', 'figura',
            'peso', 'longitud', 'estado', 'planilla_id', 'sort', 'order'
        ]);
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->elemento_id)) {
            $filtros[] = "<strong>ID:</strong> {$this->elemento_id}";
        }
        if (!empty($this->codigo)) {
            $filtros[] = "<strong>Código:</strong> {$this->codigo}";
        }
        if (!empty($this->codigo_planilla)) {
            $filtros[] = "<strong>Cód. Planilla:</strong> {$this->codigo_planilla}";
        }
        if (!empty($this->etiqueta)) {
            $filtros[] = "<strong>Etiqueta:</strong> {$this->etiqueta}";
        }
        if (!empty($this->subetiqueta)) {
            $filtros[] = "<strong>Subetiqueta:</strong> {$this->subetiqueta}";
        }
        if (!empty($this->dimensiones)) {
            $filtros[] = "<strong>Dimensiones:</strong> {$this->dimensiones}";
        }
        if (!empty($this->diametro)) {
            $filtros[] = "<strong>Diámetro:</strong> {$this->diametro}";
        }
        if (!empty($this->barras)) {
            $filtros[] = "<strong>Barras:</strong> {$this->barras}";
        }
        if (!empty($this->maquina)) {
            $filtros[] = "<strong>Máquina 1:</strong> {$this->maquina}";
        }
        if (!empty($this->maquina_2)) {
            $filtros[] = "<strong>Máquina 2:</strong> {$this->maquina_2}";
        }
        if (!empty($this->maquina3)) {
            $filtros[] = "<strong>Máquina 3:</strong> {$this->maquina3}";
        }
        if (!empty($this->producto1)) {
            $filtros[] = "<strong>Producto 1:</strong> {$this->producto1}";
        }
        if (!empty($this->producto2)) {
            $filtros[] = "<strong>Producto 2:</strong> {$this->producto2}";
        }
        if (!empty($this->producto3)) {
            $filtros[] = "<strong>Producto 3:</strong> {$this->producto3}";
        }
        if (!empty($this->figura)) {
            $filtros[] = "<strong>Figura:</strong> {$this->figura}";
        }
        if (!empty($this->peso)) {
            $filtros[] = "<strong>Peso:</strong> {$this->peso}";
        }
        if (!empty($this->longitud)) {
            $filtros[] = "<strong>Longitud:</strong> {$this->longitud}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> {$this->estado}";
        }
        if (!empty($this->planilla_id)) {
            $filtros[] = "<strong>Planilla ID:</strong> {$this->planilla_id}";
        }

        return $filtros;
    }

    public function render()
    {
        $query = Elemento::with([
            'planilla',
            'etiquetaRelacion',
            'maquina',
            'maquina_2',
            'maquina_3',
            'producto',
            'producto2',
            'producto3',
        ]);

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $totalPesoFiltrado = (clone $query)->sum('peso');

        $elementos = $query->paginate($this->perPage);

        // Asegurar relación etiqueta
        $elementos->getCollection()->transform(function ($elemento) {
            $elemento->etiquetaRelacion = $elemento->etiquetaRelacion ?? (object) ['id' => '', 'nombre' => ''];
            return $elemento;
        });

        $maquinas = Maquina::all();

        // Detectar si se está viendo elementos de una planilla específica
        $planilla = null;
        if (!empty($this->planilla_id)) {
            $planilla = Planilla::with(['cliente', 'obra', 'revisor'])->find($this->planilla_id);
        } elseif (!empty($this->codigo_planilla)) {
            // Buscar por código de planilla
            $planilla = Planilla::with(['cliente', 'obra', 'revisor'])
                ->where('codigo', 'like', '%' . trim($this->codigo_planilla) . '%')
                ->first();
        }

        return view('livewire.elementos-table', [
            'elementos' => $elementos,
            'maquinas' => $maquinas,
            'totalPesoFiltrado' => $totalPesoFiltrado,
            'planilla' => $planilla,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ])->layout('layouts.app');
    }
}
