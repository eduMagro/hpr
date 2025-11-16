<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\PedidoGlobal;
use App\Models\Fabricante;
use App\Models\Distribuidor;

class PedidosGlobalesTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    #[Url(keep: true)]
    public $codigo = '';

    #[Url(keep: true)]
    public $fabricante = '';

    #[Url(keep: true)]
    public $distribuidor = '';

    #[Url(keep: true)]
    public $estado = '';

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
