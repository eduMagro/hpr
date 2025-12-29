<?php

namespace App\Livewire;

use App\Models\Movimiento;
use App\Models\Obra;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class MovimientosTable extends Component
{
    use WithPagination;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $id = '';

    #[Url]
    public $tipo = '';

    #[Url]
    public $pedido_producto_id = '';

    #[Url]
    public $producto_tipo = '';

    #[Url]
    public $producto_diametro = '';

    #[Url]
    public $producto_longitud = '';

    #[Url]
    public $descripcion = '';

    #[Url]
    public $nave_id = '';

    #[Url]
    public $prioridad = '';

    #[Url]
    public $solicitado_por = '';

    #[Url]
    public $ejecutado_por = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $fecha_solicitud = '';

    #[Url]
    public $fecha_ejecucion = '';

    #[Url]
    public $origen = '';

    #[Url]
    public $destino = '';

    #[Url]
    public $producto_paquete = '';

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
            'id', 'tipo', 'pedido_producto_id', 'producto_tipo', 'producto_diametro',
            'producto_longitud', 'descripcion', 'nave_id', 'prioridad', 'solicitado_por',
            'ejecutado_por', 'estado', 'fecha_solicitud', 'fecha_ejecucion', 'origen',
            'destino', 'producto_paquete'
        ]);
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->id)) {
            $filtros[] = "<strong>ID:</strong> {$this->id}";
        }
        if (!empty($this->tipo)) {
            $filtros[] = "<strong>Tipo:</strong> {$this->tipo}";
        }
        if (!empty($this->pedido_producto_id)) {
            $filtros[] = "<strong>Pedido Producto:</strong> {$this->pedido_producto_id}";
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
        if (!empty($this->descripcion)) {
            $filtros[] = "<strong>Descripción:</strong> {$this->descripcion}";
        }
        if (!empty($this->nave_id)) {
            $filtros[] = "<strong>Nave ID:</strong> {$this->nave_id}";
        }
        if (!empty($this->prioridad)) {
            $filtros[] = "<strong>Prioridad:</strong> " . ucfirst($this->prioridad);
        }
        if (!empty($this->solicitado_por)) {
            $filtros[] = "<strong>Solicitado por:</strong> {$this->solicitado_por}";
        }
        if (!empty($this->ejecutado_por)) {
            $filtros[] = "<strong>Ejecutado por:</strong> {$this->ejecutado_por}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }
        if (!empty($this->fecha_solicitud)) {
            $filtros[] = "<strong>Fecha Solicitud:</strong> {$this->fecha_solicitud}";
        }
        if (!empty($this->fecha_ejecucion)) {
            $filtros[] = "<strong>Fecha Ejecución:</strong> {$this->fecha_ejecucion}";
        }
        if (!empty($this->origen)) {
            $filtros[] = "<strong>Origen:</strong> {$this->origen}";
        }
        if (!empty($this->destino)) {
            $filtros[] = "<strong>Destino:</strong> {$this->destino}";
        }
        if (!empty($this->producto_paquete)) {
            $filtros[] = "<strong>Producto/Paquete:</strong> {$this->producto_paquete}";
        }

        return $filtros;
    }

    public function aplicarFiltros($query)
    {
        // ID
        if (!empty($this->id)) {
            $query->where('id', 'like', '%' . trim($this->id) . '%');
        }

        // Tipo
        if (!empty($this->tipo)) {
            $query->where('tipo', 'like', '%' . trim($this->tipo) . '%');
        }

        // Pedido Producto ID
        if (!empty($this->pedido_producto_id)) {
            $query->where('pedido_producto_id', 'like', '%' . trim($this->pedido_producto_id) . '%');
        }

        // Producto Base (tipo, diámetro, longitud)
        if (!empty($this->producto_tipo)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('tipo', 'like', '%' . trim($this->producto_tipo) . '%');
            });
        }

        if (!empty($this->producto_diametro)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('diametro', 'like', '%' . trim($this->producto_diametro) . '%');
            });
        }

        if (!empty($this->producto_longitud)) {
            $query->whereHas('productoBase', function ($q) {
                $q->where('longitud', 'like', '%' . trim($this->producto_longitud) . '%');
            });
        }

        // Descripción
        if (!empty($this->descripcion)) {
            $query->where('descripcion', 'like', '%' . trim($this->descripcion) . '%');
        }

        // Nave
        if (!empty($this->nave_id)) {
            $query->where('nave_id', $this->nave_id);
        }

        // Prioridad
        if (!empty($this->prioridad)) {
            $query->where('prioridad', $this->prioridad);
        }

        // Solicitado por
        if (!empty($this->solicitado_por)) {
            $query->whereHas('solicitadoPor', function ($q) {
                $q->where('name', 'like', '%' . trim($this->solicitado_por) . '%')
                  ->orWhere('primer_apellido', 'like', '%' . trim($this->solicitado_por) . '%');
            });
        }

        // Ejecutado por
        if (!empty($this->ejecutado_por)) {
            $query->whereHas('ejecutadoPor', function ($q) {
                $q->where('name', 'like', '%' . trim($this->ejecutado_por) . '%')
                  ->orWhere('primer_apellido', 'like', '%' . trim($this->ejecutado_por) . '%');
            });
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        // Fecha Solicitud
        if (!empty($this->fecha_solicitud)) {
            $query->whereDate('fecha_solicitud', Carbon::parse($this->fecha_solicitud)->format('Y-m-d'));
        }

        // Fecha Ejecución
        if (!empty($this->fecha_ejecucion)) {
            $query->whereDate('fecha_ejecucion', Carbon::parse($this->fecha_ejecucion)->format('Y-m-d'));
        }

        // Origen
        if (!empty($this->origen)) {
            $query->where(function ($q) {
                $q->whereHas('ubicacionOrigen', function ($subQ) {
                    $subQ->where('nombre', 'like', '%' . trim($this->origen) . '%');
                })
                ->orWhereHas('maquinaOrigen', function ($subQ) {
                    $subQ->where('nombre', 'like', '%' . trim($this->origen) . '%');
                });
            });
        }

        // Destino
        if (!empty($this->destino)) {
            $query->where(function ($q) {
                $q->whereHas('ubicacionDestino', function ($subQ) {
                    $subQ->where('nombre', 'like', '%' . trim($this->destino) . '%');
                })
                ->orWhereHas('maquinaDestino', function ($subQ) {
                    $subQ->where('nombre', 'like', '%' . trim($this->destino) . '%');
                });
            });
        }

        // Producto/Paquete
        if (!empty($this->producto_paquete)) {
            $query->where(function ($q) {
                $q->whereHas('producto', function ($subQ) {
                    $subQ->where('codigo', 'like', '%' . trim($this->producto_paquete) . '%');
                })
                ->orWhereHas('paquete', function ($subQ) {
                    $subQ->where('codigo', 'like', '%' . trim($this->producto_paquete) . '%');
                });
            });
        }

        return $query;
    }

    public function render()
    {
        $query = Movimiento::with([
            'pedido_producto',
            'productoBase',
            'nave',
            'solicitadoPor',
            'ejecutadoPor',
            'ubicacionOrigen',
            'ubicacionDestino',
            'maquinaOrigen',
            'maquinaDestino',
            'producto',
            'paquete'
        ]);

        $query = $this->aplicarFiltros($query);

        // Aplicar ordenamiento
        $columnasDirectas = [
            'id', 'tipo', 'pedido_producto_id', 'descripcion', 'nave_id', 'prioridad',
            'estado', 'fecha_solicitud', 'fecha_ejecucion', 'created_at'
        ];

        $orderDir = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

        if (in_array($this->sort, $columnasDirectas)) {
            $query->orderBy($this->sort, $orderDir);
        } elseif ($this->sort === 'producto_base') {
            // Ordenar por producto base (tipo + diámetro)
            $query->leftJoin('productos_base', 'movimientos.producto_base_id', '=', 'productos_base.id')
                  ->orderBy('productos_base.tipo', $orderDir)
                  ->orderBy('productos_base.diametro', $orderDir)
                  ->select('movimientos.*');
        } elseif ($this->sort === 'solicitado_por') {
            // Ordenar por nombre del usuario que solicitó
            $query->leftJoin('users as solicitante', 'movimientos.solicitado_por', '=', 'solicitante.id')
                  ->orderBy('solicitante.name', $orderDir)
                  ->select('movimientos.*');
        } elseif ($this->sort === 'ejecutado_por') {
            // Ordenar por nombre del usuario que ejecutó
            $query->leftJoin('users as ejecutor', 'movimientos.ejecutado_por', '=', 'ejecutor.id')
                  ->orderBy('ejecutor.name', $orderDir)
                  ->select('movimientos.*');
        } elseif ($this->sort === 'origen') {
            // Ordenar por origen (ubicación o máquina)
            $query->leftJoin('ubicaciones as uo', 'movimientos.ubicacion_origen_id', '=', 'uo.id')
                  ->leftJoin('maquinas as mo', 'movimientos.maquina_origen_id', '=', 'mo.id')
                  ->orderByRaw("COALESCE(uo.nombre, mo.nombre) {$orderDir}")
                  ->select('movimientos.*');
        } elseif ($this->sort === 'destino') {
            // Ordenar por destino (ubicación o máquina)
            $query->leftJoin('ubicaciones as ud', 'movimientos.ubicacion_destino_id', '=', 'ud.id')
                  ->leftJoin('maquinas as md', 'movimientos.maquina_destino_id', '=', 'md.id')
                  ->orderByRaw("COALESCE(ud.nombre, md.nombre) {$orderDir}")
                  ->select('movimientos.*');
        } elseif ($this->sort === 'producto_paquete') {
            // Ordenar por código de producto o paquete
            $query->leftJoin('productos as prod', 'movimientos.producto_id', '=', 'prod.id')
                  ->leftJoin('paquetes as paq', 'movimientos.paquete_id', '=', 'paq.id')
                  ->orderByRaw("COALESCE(prod.codigo, paq.codigo) {$orderDir}")
                  ->select('movimientos.*');
        } else {
            $query->orderBy('created_at', $orderDir);
        }

        $movimientos = $query->paginate($this->perPage);

        // Obtener naves para el select
        $naves = Obra::pluck('obra', 'id')->toArray();

        return view('livewire.movimientos-table', [
            'movimientos' => $movimientos,
            'naves' => $naves,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
