<?php

namespace App\Livewire;

use App\Models\Producto;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ProductosTable extends Component
{
    use WithPagination;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $id = '';

    #[Url]
    public $albaran = '';

    #[Url]
    public $codigo = '';

    #[Url]
    public $nave_id = '';

    #[Url]
    public $fabricante = '';

    #[Url]
    public $tipo = '';

    #[Url]
    public $diametro = '';

    #[Url]
    public $longitud = '';

    #[Url]
    public $n_colada = '';

    #[Url]
    public $n_paquete = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $ubicacion = '';

    #[Url]
    public $sort = 'created_at';

    #[Url]
    public $order = 'desc';

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

        return $filtros;
    }

    public function aplicarFiltros($query)
    {
        // ID
        if (!empty($this->id)) {
            $query->where('id', 'like', '%' . trim($this->id) . '%');
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
        $totalPesoInicial = $queryTotal->sum('peso_inicial');

        // Obtener naves para el select
        $naves = \App\Models\Obra::pluck('obra', 'id')->toArray();

        return view('livewire.productos-table', [
            'productos' => $productos,
            'totalPesoInicial' => $totalPesoInicial,
            'naves' => $naves,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
