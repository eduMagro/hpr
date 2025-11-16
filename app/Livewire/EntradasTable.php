<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Entrada;
use App\Models\Fabricante;
use App\Models\Distribuidor;

class EntradasTable extends Component
{
    use WithPagination;

    // Filtros
    public $pedido_codigo = '';
    public $nave_id = '';
    public $fabricante_id = [];
    public $distribuidor_id = [];
    public $usuario = '';
    public $producto_tipo = '';
    public $producto_diametro = '';
    public $producto_longitud = '';

    // Ordenamiento
    public $sort = 'created_at';
    public $order = 'desc';

    // Paginación
    public $perPage = 10;

    protected $queryString = [
        'pedido_codigo' => ['except' => ''],
        'nave_id' => ['except' => ''],
        'fabricante_id' => ['except' => []],
        'distribuidor_id' => ['except' => []],
        'usuario' => ['except' => ''],
        'producto_tipo' => ['except' => ''],
        'producto_diametro' => ['except' => ''],
        'producto_longitud' => ['except' => ''],
        'sort' => ['except' => 'created_at'],
        'order' => ['except' => 'desc'],
        'perPage' => ['except' => 10],
    ];

    public function sortBy($column)
    {
        if ($this->sort === $column) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->order = 'asc';
        }
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingPedidoCodigo()
    {
        $this->resetPage();
    }

    public function updatingNaveId()
    {
        $this->resetPage();
    }

    public function updatingFabricanteId()
    {
        $this->resetPage();
    }

    public function updatingDistribuidorId()
    {
        $this->resetPage();
    }

    public function updatingUsuario()
    {
        $this->resetPage();
    }

    public function updatingProductoTipo()
    {
        $this->resetPage();
    }

    public function updatingProductoDiametro()
    {
        $this->resetPage();
    }

    public function updatingProductoLongitud()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'pedido_codigo',
            'nave_id',
            'fabricante_id',
            'distribuidor_id',
            'usuario',
            'producto_tipo',
            'producto_diametro',
            'producto_longitud',
            'sort',
            'order',
            'perPage'
        ]);
        $this->resetPage();
    }

    private function aplicarFiltros($query)
    {
        // Filtro por pedido
        if ($this->pedido_codigo) {
            $query->whereHas('pedido', function ($q) {
                $q->where('codigo', 'LIKE', '%' . $this->pedido_codigo . '%');
            });
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

        if ($this->usuario) {
            $filtros[] = 'Usuario: <strong>' . e($this->usuario) . '</strong>';
        }

        if ($this->sort) {
            $map = [
                'pedido_producto_id' => 'ID Línea Pedido',
                'albaran'            => 'Albarán',
                'codigo_sage'        => 'Código SAGE',
                'nave_id'            => 'Nave',
                'pedido_codigo'      => 'Pedido Compra',
                'usuario'            => 'Usuario',
                'created_at'         => 'Fecha',
            ];
            $orden = $this->order === 'asc' ? 'ascendente' : 'descendente';
            $filtros[] = 'Ordenado por <strong>' . ($map[$this->sort] ?? $this->sort) . '</strong> en orden <strong>' . $orden . '</strong>';
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
