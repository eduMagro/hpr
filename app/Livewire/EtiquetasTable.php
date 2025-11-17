<?php

namespace App\Livewire;

use App\Models\Etiqueta;
use App\Models\Paquete;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class EtiquetasTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url(keep: true)]
    public $etiqueta_id = '';

    #[Url(keep: true)]
    public $codigo = '';

    #[Url(keep: true)]
    public $etiqueta_sub_id = '';

    #[Url(keep: true)]
    public $codigo_planilla = '';

    #[Url(keep: true)]
    public $paquete = '';

    #[Url(keep: true)]
    public $numero_etiqueta = '';

    #[Url(keep: true)]
    public $nombre = '';

    #[Url(keep: true)]
    public $inicio_fabricacion = '';

    #[Url(keep: true)]
    public $final_fabricacion = '';

    #[Url(keep: true)]
    public $inicio_ensamblado = '';

    #[Url(keep: true)]
    public $final_ensamblado = '';

    #[Url(keep: true)]
    public $inicio_soldadura = '';

    #[Url(keep: true)]
    public $final_soldadura = '';

    #[Url(keep: true)]
    public $estado = '';

    #[Url(keep: true)]
    public $sort = '';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 10;

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function aplicarFiltros($query)
    {
        // ID
        if (!empty($this->etiqueta_id)) {
            $query->where('id', 'like', '%' . trim($this->etiqueta_id) . '%');
        }

        // Código
        if (!empty($this->codigo)) {
            $query->where('codigo', 'like', '%' . trim($this->codigo) . '%');
        }

        // Subetiqueta
        if (!empty($this->etiqueta_sub_id)) {
            $query->where('etiqueta_sub_id', 'like', '%' . trim($this->etiqueta_sub_id) . '%');
        }

        // Código planilla
        if (!empty($this->codigo_planilla)) {
            $input = trim($this->codigo_planilla);

            $query->whereHas('planilla', function ($q) use ($input) {
                if (preg_match('/^(\d{4})-(\d{1,6})$/', $input, $m)) {
                    $anio = $m[1];
                    $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                    $codigoFormateado = "{$anio}-{$num}";
                    $q->where('planillas.codigo', 'like', "%{$codigoFormateado}%");
                    return;
                }

                if (preg_match('/^\d{1,6}$/', $input)) {
                    $q->where('planillas.codigo', 'like', "%{$input}%");
                    return;
                }

                $q->where('planillas.codigo', 'like', "%{$input}%");
            });
        }

        // Paquete
        if (!empty($this->paquete)) {
            // Buscar paquetes que coincidan con el código (búsqueda parcial)
            $paquetesIds = Paquete::where('codigo', 'LIKE', '%' . $this->paquete . '%')
                ->pluck('id')
                ->toArray();

            if (!empty($paquetesIds)) {
                $query->whereIn('paquete_id', $paquetesIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Número de etiqueta
        if (!empty($this->numero_etiqueta)) {
            $query->where('id', $this->numero_etiqueta);
        }

        // Nombre
        if (!empty($this->nombre)) {
            $query->where('nombre', 'like', '%' . $this->nombre . '%');
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        // Fechas fabricación
        if (!empty($this->inicio_fabricacion)) {
            $query->where('fecha_inicio', '>=', Carbon::parse($this->inicio_fabricacion)->startOfDay());
        }
        if (!empty($this->final_fabricacion)) {
            $query->where('fecha_finalizacion', '<=', Carbon::parse($this->final_fabricacion)->endOfDay());
        }

        // Fechas ensamblado
        if (!empty($this->inicio_ensamblado)) {
            $query->where('fecha_inicio_ensamblado', '>=', Carbon::parse($this->inicio_ensamblado)->startOfDay());
        }
        if (!empty($this->final_ensamblado)) {
            $query->where('fecha_finalizacion_ensamblado', '<=', Carbon::parse($this->final_ensamblado)->endOfDay());
        }

        // Fechas soldadura
        if (!empty($this->inicio_soldadura)) {
            $query->where('fecha_inicio_soldadura', '>=', Carbon::parse($this->inicio_soldadura)->startOfDay());
        }
        if (!empty($this->final_soldadura)) {
            $query->where('fecha_finalizacion_soldadura', '<=', Carbon::parse($this->final_soldadura)->endOfDay());
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $map = [
            'id'              => 'etiquetas.id',
            'codigo'          => 'etiquetas.codigo',
            'codigo_planilla' => 'planillas.codigo',
            'etiqueta_sub_id' => 'etiquetas.etiqueta_sub_id',
            'paquete'         => 'etiquetas.paquete_id',
            'numero_etiqueta' => 'etiquetas.id',
            'nombre'          => 'etiquetas.nombre',
            'peso'            => 'etiquetas.peso',
            'estado'          => 'etiquetas.estado',
            'inicio_fabricacion' => 'etiquetas.fecha_inicio',
            'final_fabricacion' => 'etiquetas.fecha_finalizacion',
            'inicio_ensamblado' => 'etiquetas.fecha_inicio_ensamblado',
            'final_ensamblado' => 'etiquetas.fecha_finalizacion_ensamblado',
            'inicio_soldadura' => 'etiquetas.fecha_inicio_soldadura',
            'final_soldadura' => 'etiquetas.fecha_finalizacion_soldadura',
        ];

        if (!empty($this->sort) && isset($map[$this->sort])) {
            $column = $map[$this->sort];

            // Si ordenamos por una columna de planillas, añadimos el JOIN
            if (str_starts_with($column, 'planillas.')) {
                $query->leftJoin('planillas', 'planillas.id', '=', 'etiquetas.planilla_id')
                    ->select('etiquetas.*');
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
            'etiqueta_id', 'codigo', 'etiqueta_sub_id', 'codigo_planilla', 'paquete',
            'numero_etiqueta', 'nombre', 'inicio_fabricacion', 'final_fabricacion',
            'inicio_ensamblado', 'final_ensamblado', 'inicio_soldadura', 'final_soldadura',
            'estado', 'sort', 'order'
        ]);
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->etiqueta_id)) {
            $filtros[] = "<strong>ID:</strong> {$this->etiqueta_id}";
        }
        if (!empty($this->codigo)) {
            $filtros[] = "<strong>Código:</strong> {$this->codigo}";
        }
        if (!empty($this->etiqueta_sub_id)) {
            $filtros[] = "<strong>Subetiqueta:</strong> {$this->etiqueta_sub_id}";
        }
        if (!empty($this->codigo_planilla)) {
            $filtros[] = "<strong>Cód. Planilla:</strong> {$this->codigo_planilla}";
        }
        if (!empty($this->paquete)) {
            $filtros[] = "<strong>Paquete:</strong> {$this->paquete}";
        }
        if (!empty($this->numero_etiqueta)) {
            $filtros[] = "<strong>Nº Etiqueta:</strong> {$this->numero_etiqueta}";
        }
        if (!empty($this->nombre)) {
            $filtros[] = "<strong>Nombre:</strong> {$this->nombre}";
        }
        if (!empty($this->inicio_fabricacion)) {
            $filtros[] = "<strong>Inicio Fabricación:</strong> {$this->inicio_fabricacion}";
        }
        if (!empty($this->final_fabricacion)) {
            $filtros[] = "<strong>Final Fabricación:</strong> {$this->final_fabricacion}";
        }
        if (!empty($this->inicio_ensamblado)) {
            $filtros[] = "<strong>Inicio Ensamblado:</strong> {$this->inicio_ensamblado}";
        }
        if (!empty($this->final_ensamblado)) {
            $filtros[] = "<strong>Final Ensamblado:</strong> {$this->final_ensamblado}";
        }
        if (!empty($this->inicio_soldadura)) {
            $filtros[] = "<strong>Inicio Soldadura:</strong> {$this->inicio_soldadura}";
        }
        if (!empty($this->final_soldadura)) {
            $filtros[] = "<strong>Final Soldadura:</strong> {$this->final_soldadura}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'codigo' => 'Código',
                'etiqueta_sub_id' => 'Código SubEtiqueta',
                'codigo_planilla' => 'Planilla',
                'paquete' => 'Paquete',
                'numero_etiqueta' => 'Número de Etiqueta',
                'nombre' => 'Nombre',
                'peso' => 'Peso',
                'inicio_fabricacion' => 'Inicio Fabricación',
                'final_fabricacion' => 'Final Fabricación',
                'inicio_ensamblado' => 'Inicio Ensamblado',
                'final_ensamblado' => 'Final Ensamblado',
                'inicio_soldadura' => 'Inicio Soldadura',
                'final_soldadura' => 'Final Soldadura',
                'estado' => 'Estado',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
    }

    public function render()
    {
        $query = Etiqueta::with([
            'planilla:id,codigo,obra_id,cliente_id,seccion',
            'paquete:id,codigo',
            'producto:id,codigo,nombre',
            'producto2:id,codigo,nombre',
            'soldador1:id,name,primer_apellido',
            'soldador2:id,name,primer_apellido',
            'ensamblador1:id,name,primer_apellido',
            'ensamblador2:id,name,primer_apellido',
            'operario1:id,name,primer_apellido',
            'operario2:id,name,primer_apellido',
        ])->whereNotNull('etiqueta_sub_id');

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $etiquetas = $query->paginate($this->perPage);

        // Cargar datos para el modal
        $etiquetasJson = $etiquetas->load([
            'planilla.obra:id,obra',
            'planilla.cliente:id,empresa',
            'elementos:id,etiqueta_id,dimensiones,barras,diametro,peso',
        ])->keyBy('id');

        return view('livewire.etiquetas-table', [
            'etiquetas' => $etiquetas,
            'etiquetasJson' => $etiquetasJson,
            'filtrosActivos' => $this->getFiltrosActivos(),
        ]);
    }
}
