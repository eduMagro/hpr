<?php

namespace App\Livewire;

use App\Models\Colada;
use App\Models\ProductoBase;
use App\Models\Fabricante;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ColadasTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url(keep: true)]
    public $colada_id = '';

    #[Url(keep: true)]
    public $numero_colada = '';

    #[Url(keep: true)]
    public $producto_base = '';

    #[Url(keep: true)]
    public $fabricante = '';

    #[Url(keep: true)]
    public $codigo_adherencia = '';

    #[Url(keep: true)]
    public $sort = '';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 25;

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function aplicarFiltros($query)
    {
        if (!empty($this->colada_id)) {
            $query->where('id', 'like', '%' . trim($this->colada_id) . '%');
        }

        if (!empty($this->numero_colada)) {
            $query->where('numero_colada', 'like', '%' . trim($this->numero_colada) . '%');
        }

        if (!empty($this->codigo_adherencia)) {
            $query->where('codigo_adherencia', 'like', '%' . trim($this->codigo_adherencia) . '%');
        }

        // Filtro por producto base (tipo o diámetro)
        if (!empty($this->producto_base)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('tipo', 'like', '%' . $this->producto_base . '%')
                    ->orWhere('diametro', 'like', '%' . $this->producto_base . '%');
            });
        }

        // Filtro por fabricante
        if (!empty($this->fabricante)) {
            $query->whereHas('fabricante', function ($q) {
                $q->where('nombre', 'like', '%' . $this->fabricante . '%');
            });
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $map = [
            'id' => 'coladas.id',
            'numero_colada' => 'coladas.numero_colada',
            'producto_base' => 'productos_base.tipo',
            'fabricante' => 'fabricantes.nombre',
            'codigo_adherencia' => 'coladas.codigo_adherencia',
            'created_at' => 'coladas.created_at',
        ];

        if (!empty($this->sort) && isset($map[$this->sort])) {
            $column = $map[$this->sort];

            // Si ordenamos por una columna de relación, añadimos el JOIN correspondiente
            if (str_starts_with($column, 'productos_base.')) {
                $query->leftJoin('productos_base', 'productos_base.id', '=', 'coladas.producto_base_id')
                    ->select('coladas.*');
            } elseif (str_starts_with($column, 'fabricantes.')) {
                $query->leftJoin('fabricantes', 'fabricantes.id', '=', 'coladas.fabricante_id')
                    ->select('coladas.*');
            }

            $query->orderBy($column, $this->order);
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
            'colada_id',
            'numero_colada',
            'producto_base',
            'fabricante',
            'codigo_adherencia',
            'sort',
            'order'
        ]);
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->colada_id)) {
            $filtros[] = "<strong>ID:</strong> {$this->colada_id}";
        }
        if (!empty($this->numero_colada)) {
            $filtros[] = "<strong>N Colada:</strong> {$this->numero_colada}";
        }
        if (!empty($this->producto_base)) {
            $filtros[] = "<strong>Producto Base:</strong> {$this->producto_base}";
        }
        if (!empty($this->fabricante)) {
            $filtros[] = "<strong>Fabricante:</strong> {$this->fabricante}";
        }
        if (!empty($this->codigo_adherencia)) {
            $filtros[] = "<strong>Cod. Adherencia:</strong> {$this->codigo_adherencia}";
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'numero_colada' => 'N Colada',
                'producto_base' => 'Producto Base',
                'fabricante' => 'Fabricante',
                'codigo_adherencia' => 'Cod. Adherencia',
                'created_at' => 'Fecha Creación',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
    }

    public function render()
    {
        $query = Colada::with(['productoBase', 'fabricante', 'dioDeAltaPor', 'ultimoModificadoPor']);

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $coladas = $query->paginate($this->perPage);

        $productosBase = ProductoBase::orderBy('tipo')->orderBy('diametro')->get();
        $fabricantes = Fabricante::orderBy('nombre')->get();

        return view('livewire.coladas-table', [
            'coladas' => $coladas,
            'productosBase' => $productosBase,
            'fabricantes' => $fabricantes,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
