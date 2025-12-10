<?php

namespace App\Livewire;

use App\Models\Producto;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ProductosTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url(keep: true)]
    public $id = '';

    #[Url(keep: true)]
    public $entrada_id = '';

    #[Url(keep: true)]
    public $albaran = '';

    #[Url(keep: true)]
    public $codigo = '';

    #[Url(keep: true)]
    public $nave_id = '';

    #[Url(keep: true)]
    public $fabricante = '';

    #[Url(keep: true)]
    public $tipo = '';

    #[Url(keep: true)]
    public $diametro = '';

    #[Url(keep: true)]
    public $longitud = '';

    #[Url(keep: true)]
    public $n_colada = '';

    #[Url(keep: true)]
    public $n_paquete = '';

    #[Url(keep: true)]
    public $estado = '';

    #[Url(keep: true)]
    public $ubicacion = '';

    #[Url(keep: true)]
    public $sort = 'created_at';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 15;

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
            'id',
            'entrada_id',
            'albaran',
            'codigo',
            'nave_id',
            'fabricante',
            'tipo',
            'diametro',
            'longitud',
            'n_colada',
            'n_paquete',
            'estado',
            'ubicacion'
        ]);
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->id)) {
            $filtros[] = "<strong>ID:</strong> {$this->id}";
        }
        if (!empty($this->entrada_id)) {
            $filtros[] = "<strong>Entrada ID:</strong> {$this->entrada_id}";
        }
        if (!empty($this->albaran)) {
            $filtros[] = "<strong>Albarán:</strong> {$this->albaran}";
        }
        if (!empty($this->codigo)) {
            $filtros[] = "<strong>Código:</strong> {$this->codigo}";
        }
        if (!empty($this->nave_id)) {
            $filtros[] = "<strong>Nave ID:</strong> {$this->nave_id}";
        }
        if (!empty($this->fabricante)) {
            $filtros[] = "<strong>Fabricante:</strong> {$this->fabricante}";
        }
        if (!empty($this->tipo)) {
            $filtros[] = "<strong>Tipo:</strong> {$this->tipo}";
        }
        if (!empty($this->diametro)) {
            $filtros[] = "<strong>Diámetro:</strong> {$this->diametro}";
        }
        if (!empty($this->longitud)) {
            $filtros[] = "<strong>Longitud:</strong> {$this->longitud}";
        }
        if (!empty($this->n_colada)) {
            $filtros[] = "<strong>Nº Colada:</strong> {$this->n_colada}";
        }
        if (!empty($this->n_paquete)) {
            $filtros[] = "<strong>Nº Paquete:</strong> {$this->n_paquete}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }
        if (!empty($this->ubicacion)) {
            $filtros[] = "<strong>Ubicación:</strong> {$this->ubicacion}";
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'entrada_id' => 'Albarán',
                'codigo' => 'Código',
                'nave' => 'Nave',
                'fabricante' => 'Fabricante',
                'tipo' => 'Tipo',
                'diametro' => 'Diámetro',
                'longitud' => 'Longitud',
                'n_colada' => 'Nº Colada',
                'n_paquete' => 'Nº Paquete',
                'peso_inicial' => 'Peso Inicial',
                'peso_stock' => 'Peso Stock',
                'estado' => 'Estado',
                'ubicacion' => 'Ubicación',
                'created_at' => 'Fecha de Creación',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
    }

    public function aplicarFiltros($query)
    {
        // ID
        if (!empty($this->id)) {
            $query->where('id', 'like', '%' . trim($this->id) . '%');
        }

        // Entrada ID
        if (!empty($this->entrada_id)) {
            $query->where('entrada_id', $this->entrada_id);
        }

        // Código
        if (!empty($this->codigo)) {
            $query->where('codigo', 'like', '%' . trim($this->codigo) . '%');
        }

        // Albarán (entrada)
        if (!empty($this->albaran)) {
            $query->whereHas('entrada', function ($q) {
                $q->where('albaran', 'like', '%' . trim($this->albaran) . '%');
            });
        }

        // Nave
        if (!empty($this->nave_id)) {
            $query->where('obra_id', $this->nave_id);
        }

        // Fabricante
        if (!empty($this->fabricante)) {
            $query->whereHas('fabricante', function ($q) {
                $q->where('nombre', 'like', '%' . trim($this->fabricante) . '%');
            });
        }

        // Tipo (desde productoBase)
        if (!empty($this->tipo)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('tipo', 'like', '%' . trim($this->tipo) . '%');
            });
        }

        // Diámetro (desde productoBase)
        if (!empty($this->diametro)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('diametro', 'like', '%' . trim($this->diametro) . '%');
            });
        }

        // Longitud (desde productoBase)
        if (!empty($this->longitud)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('longitud', 'like', '%' . trim($this->longitud) . '%');
            });
        }

        // N° Colada
        if (!empty($this->n_colada)) {
            $query->where('n_colada', 'like', '%' . trim($this->n_colada) . '%');
        }

        // N° Paquete
        if (!empty($this->n_paquete)) {
            $query->where('n_paquete', 'like', '%' . trim($this->n_paquete) . '%');
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        // Ubicación
        if (!empty($this->ubicacion)) {
            $query->where(function ($q) {
                $q->whereHas('ubicacion', function ($subQ) {
                    $subQ->where('nombre', 'like', '%' . trim($this->ubicacion) . '%');
                })
                ->orWhereHas('maquina', function ($subQ) {
                    $subQ->where('nombre', 'like', '%' . trim($this->ubicacion) . '%');
                });
            });
        }
    }

    public function render()
    {
        $query = Producto::with(['entrada', 'fabricante', 'productoBase', 'ubicacion', 'maquina', 'obra', 'consumidoPor']);

        // Aplicar filtros
        $this->aplicarFiltros($query);

        // Aplicar ordenamiento
        if ($this->sort === 'nave') {
            $query->join('obras', 'productos.obra_id', '=', 'obras.id')
                  ->orderBy('obras.obra', $this->order)
                  ->select('productos.*');
        } elseif ($this->sort === 'fabricante') {
            $query->join('fabricantes', 'productos.fabricante_id', '=', 'fabricantes.id')
                  ->orderBy('fabricantes.nombre', $this->order)
                  ->select('productos.*');
        } elseif ($this->sort === 'tipo' || $this->sort === 'diametro' || $this->sort === 'longitud') {
            $query->join('productos_base', 'productos.producto_base_id', '=', 'productos_base.id')
                  ->orderBy('productos_base.' . $this->sort, $this->order)
                  ->select('productos.*');
        } elseif ($this->sort === 'ubicacion') {
            $query->leftJoin('ubicaciones', 'productos.ubicacion_id', '=', 'ubicaciones.id')
                  ->orderBy('ubicaciones.nombre', $this->order)
                  ->select('productos.*');
        } elseif ($this->sort === 'entrada_id') {
            $query->leftJoin('entradas', 'productos.entrada_id', '=', 'entradas.id')
                  ->orderBy('entradas.albaran', $this->order)
                  ->select('productos.*');
        } else {
            $query->orderBy($this->sort, $this->order);
        }

        $productos = $query->paginate($this->perPage);

        // Calcular total de peso filtrado
        $queryTotal = Producto::query();
        $this->aplicarFiltros($queryTotal);

        // DEBUG: Contar antes del sum
        $countBeforeSum = $queryTotal->count();
        $totalPesoInicial = $queryTotal->sum('peso_inicial');

        // DEBUG: Log para verificar
        \Log::info('ProductosTable Total Debug', [
            'filtros' => [
                'nave_id' => $this->nave_id,
                'tipo' => $this->tipo,
                'diametro' => $this->diametro,
                'estado' => $this->estado,
            ],
            'count_productos_paginados' => $productos->total(),
            'count_productos_query_total_before_sum' => $countBeforeSum,
            'sum_peso_inicial' => $totalPesoInicial,
            'query_sql' => $queryTotal->toSql(),
            'query_bindings' => $queryTotal->getBindings(),
        ]);

        // Obtener datos para los selects de edición
        $naves = \App\Models\Obra::pluck('obra', 'id')->toArray();
        $fabricantes = \App\Models\Fabricante::orderBy('nombre')->get(['id', 'nombre']);
        $productosBase = \App\Models\ProductoBase::orderBy('tipo')->orderBy('diametro')->get(['id', 'tipo', 'diametro', 'longitud']);
        $ubicaciones = \App\Models\Ubicacion::orderBy('nombre')->get(['id', 'nombre']);
        $maquinas = \App\Models\Maquina::orderBy('nombre')->get(['id', 'nombre']);

        return view('livewire.productos-table', [
            'productos' => $productos,
            'totalPesoInicial' => $totalPesoInicial,
            'naves' => $naves,
            'fabricantes' => $fabricantes,
            'productosBase' => $productosBase,
            'ubicaciones' => $ubicaciones,
            'maquinas' => $maquinas,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
