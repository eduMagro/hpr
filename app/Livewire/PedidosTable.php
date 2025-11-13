<?php

namespace App\Livewire;

use App\Models\Pedido;
use App\Models\PedidoGlobal;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use App\Models\Obra;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class PedidosTable extends Component
{
    use WithPagination;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $codigo_linea = '';

    #[Url]
    public $codigo = '';

    #[Url]
    public $pedido_global_id = '';

    #[Url]
    public $fabricante_id = '';

    #[Url]
    public $distribuidor_id = '';

    #[Url]
    public $obra_id = '';

    #[Url]
    public $producto_tipo = '';

    #[Url]
    public $producto_diametro = '';

    #[Url]
    public $producto_longitud = '';

    #[Url]
    public $fecha_pedido = '';

    #[Url]
    public $fecha_entrega = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $sort = 'created_at';

    #[Url]
    public $order = 'desc';

    public $perPage = 10;

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        if ($this->sort === $field) {
            $this->order = $this->order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->order = 'asc';
        }
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'codigo_linea', 'codigo', 'pedido_global_id', 'fabricante_id', 'distribuidor_id',
            'obra_id', 'producto_tipo', 'producto_diametro', 'producto_longitud',
            'fecha_pedido', 'fecha_entrega', 'estado'
        ]);
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->codigo_linea)) {
            $filtros[] = "<strong>Código Línea:</strong> {$this->codigo_linea}";
        }
        if (!empty($this->codigo)) {
            $filtros[] = "<strong>Código:</strong> {$this->codigo}";
        }
        if (!empty($this->pedido_global_id)) {
            $filtros[] = "<strong>Pedido Global:</strong> {$this->pedido_global_id}";
        }
        if (!empty($this->fabricante_id)) {
            $filtros[] = "<strong>Fabricante ID:</strong> {$this->fabricante_id}";
        }
        if (!empty($this->distribuidor_id)) {
            $filtros[] = "<strong>Distribuidor ID:</strong> {$this->distribuidor_id}";
        }
        if (!empty($this->obra_id)) {
            $filtros[] = "<strong>Obra ID:</strong> {$this->obra_id}";
        }
        if (!empty($this->producto_tipo)) {
            $filtros[] = "<strong>Tipo Producto:</strong> {$this->producto_tipo}";
        }
        if (!empty($this->producto_diametro)) {
            $filtros[] = "<strong>Diámetro:</strong> {$this->producto_diametro}";
        }
        if (!empty($this->producto_longitud)) {
            $filtros[] = "<strong>Longitud:</strong> {$this->producto_longitud}";
        }
        if (!empty($this->fecha_pedido)) {
            $filtros[] = "<strong>Fecha Pedido:</strong> {$this->fecha_pedido}";
        }
        if (!empty($this->fecha_entrega)) {
            $filtros[] = "<strong>Fecha Entrega:</strong> {$this->fecha_entrega}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }

        return $filtros;
    }

    public function aplicarFiltros($query)
    {
        // Código línea (búsqueda flexible)
        if (!empty($this->codigo_linea)) {
            $busqueda = trim($this->codigo_linea);

            // Búsqueda exacta si empieza con "="
            if (str_starts_with($busqueda, '=')) {
                $busqueda = ltrim($busqueda, '=');
                $query->whereHas('lineas', function ($q) use ($busqueda) {
                    $q->where('codigo', $busqueda);
                });
            } else {
                // Búsqueda flexible
                $query->whereHas('lineas', function ($q) use ($busqueda) {
                    $q->where('codigo', 'like', '%' . $busqueda . '%');
                });
            }
        }

        // Código
        if (!empty($this->codigo)) {
            $query->where('codigo', 'like', '%' . trim($this->codigo) . '%');
        }

        // Pedido Global
        if (!empty($this->pedido_global_id)) {
            $query->where('pedido_global_id', $this->pedido_global_id);
        }

        // Fabricante
        if (!empty($this->fabricante_id)) {
            $query->where('fabricante_id', $this->fabricante_id);
        }

        // Distribuidor
        if (!empty($this->distribuidor_id)) {
            $query->where('distribuidor_id', $this->distribuidor_id);
        }

        // Obra (desde líneas)
        if (!empty($this->obra_id)) {
            $query->whereHas('lineas', function ($q) {
                $q->where('obra_id', $this->obra_id);
            });
        }

        // Producto Base (tipo, diámetro, longitud) - desde líneas
        if (!empty($this->producto_tipo)) {
            $query->whereHas('lineas', function ($q) {
                $q->where('tipo', 'like', '%' . trim($this->producto_tipo) . '%');
            });
        }

        if (!empty($this->producto_diametro)) {
            $query->whereHas('lineas', function ($q) {
                $q->where('diametro', 'like', '%' . trim($this->producto_diametro) . '%');
            });
        }

        if (!empty($this->producto_longitud)) {
            $query->whereHas('lineas', function ($q) {
                $q->where('longitud', 'like', '%' . trim($this->producto_longitud) . '%');
            });
        }

        // Fecha Pedido
        if (!empty($this->fecha_pedido)) {
            $query->whereDate('fecha_pedido', Carbon::parse($this->fecha_pedido)->format('Y-m-d'));
        }

        // Fecha Entrega
        if (!empty($this->fecha_entrega)) {
            $query->whereDate('fecha_entrega', Carbon::parse($this->fecha_entrega)->format('Y-m-d'));
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        return $query;
    }

    public function render()
    {
        $query = Pedido::with([
            'pedidoGlobal',
            'fabricante',
            'distribuidor',
            'lineas.obra',
            'lineas.pedidoGlobal',
            'createdBy'
        ]);

        $query = $this->aplicarFiltros($query);

        // Aplicar ordenamiento
        $columnasPermitidas = [
            'codigo', 'fecha_pedido', 'fecha_entrega', 'estado', 'created_at'
        ];

        $sortBy = in_array($this->sort, $columnasPermitidas) ? $this->sort : 'created_at';
        $orderDir = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $orderDir);

        $pedidos = $query->paginate($this->perPage);

        // Cargar datos para selects
        $pedidosGlobales = PedidoGlobal::select('id', 'codigo')->get();
        $fabricantes = Fabricante::select('id', 'nombre')->get();
        $distribuidores = Distribuidor::select('id', 'nombre')->get();
        $obras = Obra::pluck('obra', 'id')->toArray();

        // Cargar obras separadas (HPR y Externas)
        $navesHpr = Obra::getNavesPacoReyes();
        $obrasExternas = Obra::whereDoesntHave('cliente', function ($q) {
            $q->where('empresa', 'like', '%PACO REYES%');
        })->get();

        // Cargar productos base para edición
        $productosBase = \App\Models\ProductoBase::all();

        return view('livewire.pedidos-table', [
            'pedidos' => $pedidos,
            'pedidosGlobales' => $pedidosGlobales,
            'fabricantes' => $fabricantes,
            'distribuidores' => $distribuidores,
            'obras' => $obras,
            'navesHpr' => $navesHpr,
            'obrasExternas' => $obrasExternas,
            'productosBase' => $productosBase,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
