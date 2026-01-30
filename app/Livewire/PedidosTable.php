<?php

namespace App\Livewire;

use App\Models\PedidoProducto;
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

    protected $paginationTheme = 'tailwind';

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
            'codigo_linea',
            'codigo',
            'pedido_global_id',
            'fabricante_id',
            'distribuidor_id',
            'obra_id',
            'producto_tipo',
            'producto_diametro',
            'producto_longitud',
            'fecha_pedido',
            'fecha_entrega',
            'estado'
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
            $filtros[] = "<strong>Código Pedido:</strong> {$this->codigo}";
        }
        if (!empty($this->pedido_global_id)) {
            $filtros[] = "<strong>Pedido Global:</strong> {$this->pedido_global_id}";
        }
        if (!empty($this->fabricante_id)) {
            $filtros[] = "<strong>Fabricante:</strong> {$this->fabricante_id}";
        }
        if (!empty($this->distribuidor_id)) {
            $filtros[] = "<strong>Distribuidor:</strong> {$this->distribuidor_id}";
        }
        if (!empty($this->obra_id)) {
            $filtros[] = "<strong>Obra:</strong> {$this->obra_id}";
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

            if (str_starts_with($busqueda, '=')) {
                $busqueda = ltrim($busqueda, '=');
                $query->where('pedido_productos.codigo', $busqueda);
            } else {
                $query->where('pedido_productos.codigo', 'like', '%' . $busqueda . '%');
            }
        }

        // Código del pedido
        if (!empty($this->codigo)) {
            $query->whereHas('pedido', function ($q) {
                $q->where('codigo', 'like', '%' . trim($this->codigo) . '%');
            });
        }

        // Pedido Global de la línea
        if (!empty($this->pedido_global_id)) {
            $query->where('pedido_productos.pedido_global_id', $this->pedido_global_id);
        }

        // Fabricante (desde pedido)
        if (!empty($this->fabricante_id)) {
            $query->whereHas('pedido', function ($q) {
                $q->where('fabricante_id', $this->fabricante_id);
            });
        }

        // Distribuidor (desde pedido)
        if (!empty($this->distribuidor_id)) {
            $query->whereHas('pedido', function ($q) {
                $q->where('distribuidor_id', $this->distribuidor_id);
            });
        }

        // Obra
        if (!empty($this->obra_id)) {
            $query->where('pedido_productos.obra_id', $this->obra_id);
        }

        // Producto tipo
        if (!empty($this->producto_tipo)) {
            $tipo = trim($this->producto_tipo);
            $query->whereHas('productoBase', function ($pb) use ($tipo) {
                $pb->where('tipo', 'like', '%' . $tipo . '%');
            });
        }

        // Producto diámetro
        if (!empty($this->producto_diametro)) {
            $diametro = trim($this->producto_diametro);
            $query->whereHas('productoBase', function ($pb) use ($diametro) {
                $pb->where('diametro', (int) $diametro);
            });
        }

        // Producto longitud
        if (!empty($this->producto_longitud)) {
            $longitud = trim($this->producto_longitud);
            $query->whereHas('productoBase', function ($pb) use ($longitud) {
                $pb->where('longitud', (int) $longitud);
            });
        }

        // Fecha Pedido (desde pedido)
        if (!empty($this->fecha_pedido)) {
            $query->whereHas('pedido', function ($q) {
                $q->whereDate('fecha_pedido', Carbon::parse($this->fecha_pedido)->format('Y-m-d'));
            });
        }

        // Fecha Entrega de la línea
        if (!empty($this->fecha_entrega)) {
            $fechaEntrega = Carbon::parse($this->fecha_entrega)->format('Y-m-d');
            $query->whereDate('fecha_estimada_entrega', $fechaEntrega);
        }

        // Estado de la línea
        if (!empty($this->estado)) {
            $query->where('pedido_productos.estado', $this->estado);
        }

        return $query;
    }

    public function render()
    {
        // Consultar directamente las líneas de pedido
        $query = PedidoProducto::with([
            'pedido.pedidoGlobal',
            'pedido.fabricante',
            'pedido.distribuidor',
            'pedido.createdBy',
            'obra.cliente',
            'pedidoGlobal',
            'productoBase',
        ]);

        $query = $this->aplicarFiltros($query);

        // Aplicar ordenamiento
        $columnasPermitidas = [
            'codigo',
            'fecha_estimada_entrega',
            'estado',
            'created_at'
        ];

        $sortBy = in_array($this->sort, $columnasPermitidas) ? $this->sort : 'created_at';
        $orderDir = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

        // Para ordenar por campos del pedido padre
        if ($this->sort === 'fecha_pedido') {
            $query->join('pedidos', 'pedido_productos.pedido_id', '=', 'pedidos.id')
                ->orderBy('pedidos.fecha_pedido', $orderDir)
                ->select('pedido_productos.*');
        } else {
            $query->orderBy($sortBy, $orderDir);
        }

        $lineas = $query->paginate($this->perPage);

        // Agrupar líneas por pedido_id para la vista
        $lineasAgrupadas = $lineas->getCollection()->groupBy('pedido_id');

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
            'lineas' => $lineas,
            'lineasAgrupadas' => $lineasAgrupadas,
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
