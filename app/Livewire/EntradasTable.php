<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Entrada;
use App\Models\Fabricante;
use App\Models\Distribuidor;

class EntradasTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    #[Url(keep: true)]
    public $pedido_producto_id = '';

    #[Url(keep: true)]
    public $pedido_codigo = '';

    #[Url(keep: true)]
    public $nave_id = '';

    #[Url(keep: true)]
    public $fabricante_id = [];

    #[Url(keep: true)]
    public $distribuidor_id = [];

    #[Url(keep: true)]
    public $usuario = '';

    #[Url(keep: true)]
    public $producto_tipo = '';

    #[Url(keep: true)]
    public $producto_diametro = '';

    #[Url(keep: true)]
    public $producto_longitud = '';

    #[Url(keep: true)]
    public $codigo_sage = '';

    // Ordenamiento
    #[Url(keep: true)]
    public $sort = 'created_at';

    #[Url(keep: true)]
    public $order = 'desc';

    // Paginación
    #[Url(keep: true)]
    public $perPage = 10;

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
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
            'pedido_producto_id',
            'pedido_codigo',
            'nave_id',
            'fabricante_id',
            'distribuidor_id',
            'usuario',
            'producto_tipo',
            'producto_diametro',
            'producto_longitud',
            'codigo_sage',
            'sort',
            'order',
            'perPage'
        ]);
        $this->resetPage();
    }

    private function aplicarFiltros($query)
    {
        // Filtro por línea de pedido (pedido_producto_id)
        if ($this->pedido_producto_id) {
            $query->where('pedido_producto_id', $this->pedido_producto_id);
        }

        // Filtro por pedido
        if ($this->pedido_codigo) {
            $query->whereHas('pedido', function ($q) {
                $q->where('codigo', 'LIKE', '%' . $this->pedido_codigo . '%');
            });
        }

        // Filtro por código SAGE
        if ($this->codigo_sage) {
            $query->where('codigo_sage', 'LIKE', '%' . $this->codigo_sage . '%');
        }

        // Filtro por nave (busca por campo obra)
        if ($this->nave_id) {
            $query->whereHas('nave', function ($q) {
                $q->where('obra', 'LIKE', '%' . $this->nave_id . '%');
            });
        }

        // Filtro por fabricante
        if (!empty($this->fabricante_id)) {
            $query->whereHas('productos', function ($q) {
                $q->whereIn('fabricante_id', $this->fabricante_id);
            });
        }

        // Filtro por distribuidor
        if (!empty($this->distribuidor_id)) {
            $query->whereHas('productos', function ($q) {
                $q->whereIn('distribuidor_id', $this->distribuidor_id);
            });
        }

        // Filtro por usuario
        if ($this->usuario) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'LIKE', '%' . $this->usuario . '%');
            });
        }

        // Filtro por producto base (tipo, diámetro, longitud)
        if ($this->producto_tipo || $this->producto_diametro || $this->producto_longitud) {
            $query->whereHas('pedidoProducto.productoBase', function ($q) {
                if ($this->producto_tipo) {
                    $q->where('tipo', 'LIKE', '%' . $this->producto_tipo . '%');
                }
                if ($this->producto_diametro) {
                    $q->where('diametro', 'LIKE', '%' . $this->producto_diametro . '%');
                }
                if ($this->producto_longitud) {
                    $q->where('longitud', 'LIKE', '%' . $this->producto_longitud . '%');
                }
            });
        }

        // Ordenamiento
        $query->orderBy($this->sort, $this->order);
    }

    private function getFiltrosActivos()
    {
        $filtros = [];

        if ($this->pedido_producto_id) {
            $linea = \App\Models\PedidoProducto::find($this->pedido_producto_id);
            $codigoLinea = $linea?->codigo ?? $this->pedido_producto_id;
            $filtros[] = 'Línea de pedido: <strong>' . e($codigoLinea) . '</strong>';
        }

        if ($this->nave_id) {
            $filtros[] = 'Nave: <strong>' . e($this->nave_id) . '</strong>';
        }

        if (!empty($this->fabricante_id)) {
            $nombres = Fabricante::whereIn('id', $this->fabricante_id)->pluck('nombre')->toArray();
            $filtros[] = 'Fabricante: <strong>' . implode(' | ', $nombres) . '</strong>';
        }

        if (!empty($this->distribuidor_id)) {
            $nombres = Distribuidor::whereIn('id', $this->distribuidor_id)->pluck('nombre')->toArray();
            $filtros[] = 'Distribuidor: <strong>' . implode(' | ', $nombres) . '</strong>';
        }

        if ($this->pedido_codigo) {
            $filtros[] = 'Pedido compra: <strong>' . e($this->pedido_codigo) . '</strong>';
        }

        if ($this->codigo_sage) {
            $filtros[] = 'Código SAGE: <strong>' . e($this->codigo_sage) . '</strong>';
        }

        if ($this->usuario) {
            $filtros[] = 'Usuario: <strong>' . e($this->usuario) . '</strong>';
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'pedido_producto_id' => 'Código Línea',
                'albaran' => 'Albarán',
                'codigo_sage' => 'Código SAGE',
                'nave_id' => 'Nave',
                'created_at' => 'Fecha',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        if ($this->perPage != 10) {
            $filtros[] = 'Mostrando <strong>' . $this->perPage . '</strong> por página';
        }

        return $filtros;
    }

    public function render()
    {
        $query = Entrada::with([
            'ubicacion',
            'user:id,name,primer_apellido,segundo_apellido',
            'productos.productoBase',
            'productos.fabricante',
            'pedido:id,codigo',
            'nave',
            'pedidoProducto.productoBase',
        ])->withCount('productos');

        $this->aplicarFiltros($query);

        $entradas = $query->paginate($this->perPage);
        $fabricantes = Fabricante::select('id', 'nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->get();
        $filtrosActivos = $this->getFiltrosActivos();

        return view('livewire.entradas-table', [
            'registrosEntradas' => $entradas,
            'fabricantes' => $fabricantes,
            'distribuidores' => $distribuidores,
            'filtrosActivos' => $filtrosActivos,
        ]);
    }
}
