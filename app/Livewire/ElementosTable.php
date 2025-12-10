<?php

namespace App\Livewire;

use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Planilla;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ElementosTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Infinite scroll
    public $registrosPorCarga = 20;
    public $hayMas = true;
    public $paginaActual = 1;
    public $cargando = false;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url(keep: true)]
    public $elemento_id = '';

    #[Url(keep: true)]
    public $codigo = '';

    #[Url(keep: true)]
    public $codigo_planilla = '';

    #[Url(keep: true)]
    public $etiqueta = '';

    #[Url(keep: true)]
    public $subetiqueta = '';

    #[Url(keep: true)]
    public $dimensiones = '';

    #[Url(keep: true)]
    public $diametro = '';

    #[Url(keep: true)]
    public $barras = '';

    #[Url(keep: true)]
    public $maquina = '';

    #[Url(keep: true)]
    public $maquina_2 = '';

    #[Url(keep: true)]
    public $maquina3 = '';

    #[Url(keep: true)]
    public $producto1 = '';

    #[Url(keep: true)]
    public $producto2 = '';

    #[Url(keep: true)]
    public $producto3 = '';

    #[Url(keep: true)]
    public $figura = '';

    #[Url(keep: true)]
    public $peso = '';

    #[Url(keep: true)]
    public $longitud = '';

    #[Url(keep: true)]
    public $estado = '';

    #[Url(keep: true)]
    public $planilla_id = '';

    #[Url(keep: true)]
    public $sort = '';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 10;

    // Cuando cambia cualquier filtro, resetear elementos
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetearElementos();
        }
    }

    // Resetear para nueva búsqueda
    public function resetearElementos()
    {
        $this->elementos = [];
        $this->paginaActual = 1;
        $this->hayMas = true;
    }

    // Cargar más elementos (llamado desde el frontend)
    public function cargarMas()
    {
        if (!$this->hayMas || $this->cargando) {
            return;
        }

        $this->cargando = true;
        $this->paginaActual++;
        $this->cargando = false;
    }

    public function aplicarFiltros($query)
    {
        // Filtros específicos
        $filters = [
            'elemento_id' => 'id',
            'figura' => 'figura',
            'dimensiones' => 'dimensiones',
            'planilla_id' => 'planilla_id',
            'barras' => 'barras'
        ];

        foreach ($filters as $property => $column) {
            if (!empty($this->$property)) {
                $query->where($column, 'like', '%' . trim($this->$property) . '%');
            }
        }

        // Código (puede ser múltiple separado por comas)
        if (!empty($this->codigo)) {
            $codigos = explode(',', $this->codigo);
            if (count($codigos) > 1) {
                $query->whereIn('codigo', $codigos);
            } else {
                $query->where('codigo', 'like', '%' . $codigos[0] . '%');
            }
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

        // Etiqueta
        if (!empty($this->etiqueta)) {
            $query->whereHas('etiquetaRelacion', function ($q) {
                $q->where('id', 'like', '%' . $this->etiqueta . '%');
            });
        }

        if (!empty($this->subetiqueta)) {
            $query->where('etiqueta_sub_id', 'like', '%' . $this->subetiqueta . '%');
        }

        // Máquinas
        if (!empty($this->maquina)) {
            $query->whereHas('maquina', function ($q) {
                $q->where('nombre', 'like', "%{$this->maquina}%");
            });
        }

        if (!empty($this->maquina_2)) {
            $query->whereHas('maquina_2', function ($q) {
                $q->where('nombre', 'like', "%{$this->maquina_2}%");
            });
        }

        if (!empty($this->maquina3)) {
            $query->whereHas('maquina_3', function ($q) {
                $q->where('nombre', 'like', "%{$this->maquina3}%");
            });
        }

        // Productos
        if (!empty($this->producto1)) {
            $query->whereHas('producto', function ($q) {
                $q->where('codigo', 'like', "%{$this->producto1}%");
            });
        }

        if (!empty($this->producto2)) {
            $query->whereHas('producto2', function ($q) {
                $q->where('codigo', 'like', "%{$this->producto2}%");
            });
        }

        if (!empty($this->producto3)) {
            $query->whereHas('producto3', function ($q) {
                $q->where('codigo', 'like', "%{$this->producto3}%");
            });
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', 'like', "%{$this->estado}%");
        }

        if (!empty($this->peso)) {
            $query->where('peso', 'like', "%{$this->peso}%");
        }

        if (!empty($this->diametro)) {
            $query->where('diametro', 'like', "%{$this->diametro}%");
        }

        if (!empty($this->longitud)) {
            $query->where('longitud', 'like', "%{$this->longitud}%");
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $map = [
            'id' => 'elementos.id',
            'codigo' => 'elementos.codigo',
            'codigo_planilla' => 'planillas.codigo',
            'etiqueta' => 'etiquetas.id',
            'subetiqueta' => 'elementos.etiqueta_sub_id',
            'dimensiones' => 'elementos.dimensiones',
            'diametro' => 'elementos.diametro',
            'barras' => 'elementos.barras',
            'maquina' => 'maquinas.nombre',
            'maquina_2' => 'maquinas_2.nombre',
            'maquina3' => 'maquinas_3.nombre',
            'producto1' => 'productos.codigo',
            'producto2' => 'productos_2.codigo',
            'producto3' => 'productos_3.codigo',
            'figura' => 'elementos.figura',
            'peso' => 'elementos.peso',
            'longitud' => 'elementos.longitud',
            'estado' => 'elementos.estado',
        ];

        if (!empty($this->sort) && isset($map[$this->sort])) {
            $column = $map[$this->sort];

            // Si ordenamos por una columna de relación, añadimos el JOIN correspondiente
            if (str_starts_with($column, 'planillas.')) {
                $query->leftJoin('planillas', 'planillas.id', '=', 'elementos.planilla_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'etiquetas.')) {
                $query->leftJoin('etiquetas', 'etiquetas.id', '=', 'elementos.etiqueta_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'maquinas.')) {
                $query->leftJoin('maquinas', 'maquinas.id', '=', 'elementos.maquina_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'maquinas_2.')) {
                $query->leftJoin('maquinas as maquinas_2', 'maquinas_2.id', '=', 'elementos.maquina_2_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'maquinas_3.')) {
                $query->leftJoin('maquinas as maquinas_3', 'maquinas_3.id', '=', 'elementos.maquina_3_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'productos.')) {
                $query->leftJoin('productos', 'productos.id', '=', 'elementos.producto_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'productos_2.')) {
                $query->leftJoin('productos as productos_2', 'productos_2.id', '=', 'elementos.producto2_id')
                    ->select('elementos.*');
            } elseif (str_starts_with($column, 'productos_3.')) {
                $query->leftJoin('productos as productos_3', 'productos_3.id', '=', 'elementos.producto3_id')
                    ->select('elementos.*');
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
            'elemento_id', 'codigo', 'codigo_planilla', 'etiqueta', 'subetiqueta',
            'dimensiones', 'diametro', 'barras', 'maquina', 'maquina_2',
            'maquina3', 'producto1', 'producto2', 'producto3', 'figura',
            'peso', 'longitud', 'estado', 'planilla_id', 'sort', 'order'
        ]);
        $this->resetearElementos();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->elemento_id)) {
            $filtros[] = "<strong>ID:</strong> {$this->elemento_id}";
        }
        if (!empty($this->codigo)) {
            $filtros[] = "<strong>Código:</strong> {$this->codigo}";
        }
        if (!empty($this->codigo_planilla)) {
            $filtros[] = "<strong>Cód. Planilla:</strong> {$this->codigo_planilla}";
        }
        if (!empty($this->etiqueta)) {
            $filtros[] = "<strong>Etiqueta:</strong> {$this->etiqueta}";
        }
        if (!empty($this->subetiqueta)) {
            $filtros[] = "<strong>Subetiqueta:</strong> {$this->subetiqueta}";
        }
        if (!empty($this->dimensiones)) {
            $filtros[] = "<strong>Dimensiones:</strong> {$this->dimensiones}";
        }
        if (!empty($this->diametro)) {
            $filtros[] = "<strong>Diámetro:</strong> {$this->diametro}";
        }
        if (!empty($this->barras)) {
            $filtros[] = "<strong>Barras:</strong> {$this->barras}";
        }
        if (!empty($this->maquina)) {
            $filtros[] = "<strong>Máquina 1:</strong> {$this->maquina}";
        }
        if (!empty($this->maquina_2)) {
            $filtros[] = "<strong>Máquina 2:</strong> {$this->maquina_2}";
        }
        if (!empty($this->maquina3)) {
            $filtros[] = "<strong>Máquina 3:</strong> {$this->maquina3}";
        }
        if (!empty($this->producto1)) {
            $filtros[] = "<strong>Producto 1:</strong> {$this->producto1}";
        }
        if (!empty($this->producto2)) {
            $filtros[] = "<strong>Producto 2:</strong> {$this->producto2}";
        }
        if (!empty($this->producto3)) {
            $filtros[] = "<strong>Producto 3:</strong> {$this->producto3}";
        }
        if (!empty($this->figura)) {
            $filtros[] = "<strong>Figura:</strong> {$this->figura}";
        }
        if (!empty($this->peso)) {
            $filtros[] = "<strong>Peso:</strong> {$this->peso}";
        }
        if (!empty($this->longitud)) {
            $filtros[] = "<strong>Longitud:</strong> {$this->longitud}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> {$this->estado}";
        }
        if (!empty($this->planilla_id)) {
            $filtros[] = "<strong>Planilla ID:</strong> {$this->planilla_id}";
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'codigo' => 'Código',
                'codigo_planilla' => 'Planilla',
                'etiqueta' => 'Etiqueta',
                'subetiqueta' => 'Subetiqueta',
                'dimensiones' => 'Dimensiones',
                'diametro' => 'Diámetro',
                'barras' => 'Barras',
                'maquina' => 'Máquina 1',
                'maquina_2' => 'Máquina 2',
                'maquina3' => 'Máquina 3',
                'producto1' => 'M. Prima 1',
                'producto2' => 'M. Prima 2',
                'producto3' => 'M. Prima 3',
                'figura' => 'Figura',
                'peso' => 'Peso',
                'longitud' => 'Longitud',
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
        // Iniciar medición de tiempo
        $startTime = microtime(true);
        $queryCount = count(\DB::getQueryLog());
        \DB::enableQueryLog();

        $query = Elemento::with([
            'planilla',
            'etiquetaRelacion',
            'maquina',
            'maquina_2',
            'maquina_3',
            'producto',
            'producto2',
            'producto3',
        ]);

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        // Contar total para saber si hay más
        $totalRegistros = (clone $query)->count();
        $totalPesoFiltrado = (clone $query)->sum('peso');

        // Infinite scroll: cargar registros hasta la página actual
        $offset = 0;
        $limit = $this->paginaActual * $this->registrosPorCarga;

        $elementos = $query->skip($offset)->take($limit)->get();

        // Verificar si hay más registros
        $this->hayMas = count($elementos) < $totalRegistros;

        // Asegurar relación etiqueta
        $elementos->transform(function ($elemento) {
            $elemento->etiquetaRelacion = $elemento->etiquetaRelacion ?? (object) ['id' => '', 'nombre' => ''];
            return $elemento;
        });

        $maquinas = Maquina::all();

        // Detectar si se está viendo elementos de una planilla específica
        $planilla = null;
        if (!empty($this->planilla_id)) {
            $planilla = Planilla::with(['cliente', 'obra', 'revisor'])->find($this->planilla_id);
        } elseif (!empty($this->codigo_planilla)) {
            $planilla = Planilla::with(['cliente', 'obra', 'revisor'])
                ->where('codigo', 'like', '%' . trim($this->codigo_planilla) . '%')
                ->first();
        }

        // Calcular métricas de rendimiento
        $endTime = microtime(true);
        $loadTime = round(($endTime - $startTime) * 1000, 2);
        $queries = \DB::getQueryLog();
        $queryCountFinal = count($queries) - $queryCount;
        $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2);

        return view('livewire.elementos-table', [
            'elementos' => $elementos,
            'maquinas' => $maquinas,
            'totalPesoFiltrado' => $totalPesoFiltrado,
            'totalRegistros' => $totalRegistros,
            'planilla' => $planilla,
            'filtrosActivos' => $this->getFiltrosActivos(),
            'loadTime' => $loadTime,
            'queryCount' => $queryCountFinal,
            'memoryUsage' => $memoryUsage,
        ]);
    }
}
