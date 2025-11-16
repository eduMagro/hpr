<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PedidoGlobal;
use App\Models\Fabricante;
use App\Models\Distribuidor;

class PedidosGlobalesTable extends Component
{
    use WithPagination;

    // Filtros
    public $codigo = '';
    public $fabricante = '';
    public $distribuidor = '';
    public $estado = '';

    // Ordenamiento
    public $sort = 'created_at';
    public $order = 'desc';

    // Paginación
    public $perPage = 10;

    protected $queryString = [
        'codigo' => ['except' => ''],
        'fabricante' => ['except' => ''],
        'distribuidor' => ['except' => ''],
        'estado' => ['except' => ''],
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

    public function updatingCodigo()
    {
        $this->resetPage();
    }

    public function updatingFabricante()
    {
        $this->resetPage();
    }

    public function updatingDistribuidor()
    {
        $this->resetPage();
    }

    public function updatingEstado()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'codigo',
            'fabricante',
            'distribuidor',
            'estado',
            'sort',
            'order',
            'perPage'
        ]);
        $this->resetPage();
    }

    private function aplicarFiltros($query)
    {
        // Filtro por código
        if ($this->codigo) {
            $query->where('codigo', 'LIKE', '%' . $this->codigo . '%');
        }

        // Filtro por fabricante
        if ($this->fabricante) {
            $query->whereHas('fabricante', function ($q) {
                $q->where('nombre', 'LIKE', '%' . $this->fabricante . '%');
            });
        }

        // Filtro por distribuidor
        if ($this->distribuidor) {
            $query->whereHas('distribuidor', function ($q) {
                $q->where('nombre', 'LIKE', '%' . $this->distribuidor . '%');
            });
        }

        // Filtro por estado
        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        // Ordenamiento
        $query->orderBy($this->sort, $this->order);
    }

    private function getFiltrosActivos()
    {
        $filtros = [];

        if ($this->codigo) {
            $filtros[] = 'Código: <strong>' . e($this->codigo) . '</strong>';
        }

        if ($this->fabricante) {
            $filtros[] = 'Fabricante: <strong>' . e($this->fabricante) . '</strong>';
        }

        if ($this->distribuidor) {
            $filtros[] = 'Distribuidor: <strong>' . e($this->distribuidor) . '</strong>';
        }

        if ($this->estado) {
            $estados = [
                'pendiente' => 'Pendiente',
                'en curso' => 'En curso',
                'completado' => 'Completado',
                'cancelado' => 'Cancelado'
            ];
            $filtros[] = 'Estado: <strong>' . ($estados[$this->estado] ?? $this->estado) . '</strong>';
        }

        if ($this->sort) {
            $map = [
                'codigo' => 'Código',
                'fabricante' => 'Fabricante',
                'distribuidor' => 'Distribuidor',
                'precio_referencia' => 'Precio Ref.',
                'cantidad_total' => 'Cantidad Total',
                'estado' => 'Estado',
                'created_at' => 'Fecha Creación',
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
        // Códigos de pedidos de maquila (hardcoded igual que en el controlador)
        $codigosPedidosMaquila = ['PCG25/0005'];

        // Consulta para pedidos principales (sin maquila)
        $queryPrincipal = PedidoGlobal::with(['fabricante', 'distribuidor'])
            ->whereNotIn('codigo', $codigosPedidosMaquila);

        $this->aplicarFiltros($queryPrincipal);
        $pedidosGlobales = $queryPrincipal->paginate($this->perPage);

        // Consulta para pedidos de maquila
        $queryMaquila = PedidoGlobal::with(['fabricante', 'distribuidor'])
            ->whereIn('codigo', $codigosPedidosMaquila);

        $this->aplicarFiltros($queryMaquila);
        $pedidosMaquila = $queryMaquila->get();

        // Calcular totales para tabla principal
        $totalesPrincipal = [
            'cantidad_total' => $pedidosGlobales->sum('cantidad_total'),
            'cantidad_restante' => $pedidosGlobales->sum('cantidad_restante'),
        ];

        // Calcular totales para tabla de maquila
        $totalesMaquila = [
            'cantidad_total' => $pedidosMaquila->sum('cantidad_total'),
            'cantidad_restante' => $pedidosMaquila->sum('cantidad_restante'),
        ];

        $fabricantes = Fabricante::select('id', 'nombre')->orderBy('nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->orderBy('nombre')->get();
        $filtrosActivos = $this->getFiltrosActivos();

        return view('livewire.pedidos-globales-table', [
            'pedidosGlobales' => $pedidosGlobales,
            'pedidosMaquila' => $pedidosMaquila,
            'totalesPrincipal' => $totalesPrincipal,
            'totalesMaquila' => $totalesMaquila,
            'fabricantes' => $fabricantes,
            'distribuidores' => $distribuidores,
            'filtrosActivos' => $filtrosActivos,
        ]);
    }
}
