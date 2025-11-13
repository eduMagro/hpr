<?php

namespace App\Livewire;

use App\Models\Planilla;
use App\Models\Cliente;
use App\Models\Obra;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class PlanillasTable extends Component
{
    use WithPagination;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $codigo = '';

    #[Url]
    public $codigo_cliente = '';

    #[Url]
    public $cliente = '';

    #[Url]
    public $cod_obra = '';

    #[Url]
    public $nom_obra = '';

    #[Url]
    public $seccion = '';

    #[Url]
    public $descripcion = '';

    #[Url]
    public $ensamblado = '';

    #[Url]
    public $comentario = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $fecha_inicio = '';

    #[Url]
    public $fecha_finalizacion = '';

    #[Url]
    public $fecha_importacion = '';

    #[Url]
    public $fecha_estimada_entrega = '';

    #[Url]
    public $usuario = '';

    #[Url]
    public $revisada = '';

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

    public function aplicarFiltros($query)
    {
        // Código
        if (!empty($this->codigo)) {
            $input = trim($this->codigo);

            // Formato completo tipo 2025-004512
            if (preg_match('/^(\d{4})-(\d{1,6})$/', $input, $m)) {
                $anio = $m[1];
                $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                $codigoFormateado = "{$anio}-{$num}";
                $query->where('codigo', 'like', "%{$codigoFormateado}%");
            }
            // Solo número final (ej. "4512")
            elseif (preg_match('/^\d{1,6}$/', $input)) {
                $query->where('codigo', 'like', "%{$input}%");
            }
            // Búsqueda genérica
            else {
                $query->where('codigo', 'like', "%{$input}%");
            }
        }

        // Código Cliente
        if (!empty($this->codigo_cliente)) {
            $query->whereHas('cliente', function ($q) {
                $q->where('codigo', 'like', '%' . trim($this->codigo_cliente) . '%');
            });
        }

        // Cliente
        if (!empty($this->cliente)) {
            $query->whereHas('cliente', function ($q) {
                $q->where('empresa', 'like', '%' . trim($this->cliente) . '%');
            });
        }

        // Código Obra
        if (!empty($this->cod_obra)) {
            $query->whereHas('obra', function ($q) {
                $q->where('cod_obra', 'like', '%' . trim($this->cod_obra) . '%');
            });
        }

        // Nombre Obra
        if (!empty($this->nom_obra)) {
            $query->whereHas('obra', function ($q) {
                $q->where('obra', 'like', '%' . trim($this->nom_obra) . '%');
            });
        }

        // Sección
        if (!empty($this->seccion)) {
            $query->where('seccion', 'like', '%' . trim($this->seccion) . '%');
        }

        // Descripción
        if (!empty($this->descripcion)) {
            $query->where('descripcion', 'like', '%' . trim($this->descripcion) . '%');
        }

        // Ensamblado
        if (!empty($this->ensamblado)) {
            $query->where('ensamblado', 'like', '%' . trim($this->ensamblado) . '%');
        }

        // Comentario
        if (!empty($this->comentario)) {
            $query->where('comentario', 'like', '%' . trim($this->comentario) . '%');
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        // Fecha inicio
        if (!empty($this->fecha_inicio)) {
            $query->whereDate('fecha_inicio', Carbon::parse($this->fecha_inicio)->format('Y-m-d'));
        }

        // Fecha finalización
        if (!empty($this->fecha_finalizacion)) {
            $query->whereDate('fecha_finalizacion', Carbon::parse($this->fecha_finalizacion)->format('Y-m-d'));
        }

        // Fecha importación (usa created_at)
        if (!empty($this->fecha_importacion)) {
            $query->whereDate('created_at', Carbon::parse($this->fecha_importacion)->format('Y-m-d'));
        }

        // Fecha estimada entrega
        if (!empty($this->fecha_estimada_entrega)) {
            $query->whereDate('fecha_estimada_entrega', Carbon::parse($this->fecha_estimada_entrega)->format('Y-m-d'));
        }

        // Usuario
        if (!empty($this->usuario)) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . trim($this->usuario) . '%');
            });
        }

        // Revisada (filtro especial)
        if ($this->revisada !== '') {
            $query->where('revisada', (bool) $this->revisada);
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $columnasPermitidas = [
            'codigo',
            'seccion',
            'descripcion',
            'ensamblado',
            'comentario',
            'peso_fabricado',
            'peso_total',
            'estado',
            'fecha_inicio',
            'fecha_finalizacion',
            'fecha_estimada_entrega',
            'revisada',
            'created_at',
        ];

        $sortBy = in_array($this->sort, $columnasPermitidas) ? $this->sort : 'created_at';
        $order = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

        // Mapear fecha_importacion a created_at
        if ($sortBy === 'fecha_importacion') {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $order);
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
            'codigo_cliente',
            'cliente',
            'cod_obra',
            'nom_obra',
            'seccion',
            'descripcion',
            'ensamblado',
            'comentario',
            'estado',
            'fecha_inicio',
            'fecha_finalizacion',
            'fecha_importacion',
            'fecha_estimada_entrega',
            'usuario',
            'revisada',
            'sort',
            'order'
        ]);
        $this->resetPage();
    }

    public function verSinRevisar()
    {
        $this->revisada = '0';
        $this->resetPage();
    }

    public function toggleRevisada($planillaId)
    {
        $planilla = Planilla::findOrFail($planillaId);
        $planilla->revisada = !$planilla->revisada;

        // Si se está marcando como revisada, guardar quién y cuándo
        if ($planilla->revisada) {
            $planilla->revisor_id = auth()->id();
            $planilla->fecha_revision = now();
        }

        $planilla->save();

        // Mensaje de éxito
        $this->dispatch('planilla-actualizada', [
            'message' => $planilla->revisada ? 'Planilla marcada como revisada' : 'Planilla marcada como no revisada'
        ]);
    }

    public function verElementosFiltrados($planillaId)
    {
        // Obtener la planilla específica
        $planilla = Planilla::with(['cliente', 'obra'])->findOrFail($planillaId);

        // Construir query params con los datos de esta planilla específica
        $params = [
            'codigo_planilla' => $planilla->codigo,
        ];

        // Agregar filtros opcionales si existen en la planilla
        if ($planilla->cliente) {
            $params['codigo_cliente'] = $planilla->cliente->codigo;
            $params['cliente'] = $planilla->cliente->empresa;
        }

        if ($planilla->obra) {
            $params['cod_obra'] = $planilla->obra->cod_obra;
            $params['nom_obra'] = $planilla->obra->obra;
        }

        if ($planilla->estado) {
            $params['estado_planilla'] = $planilla->estado;
        }

        // Redirigir a elementos con los filtros de esta planilla
        return redirect()->route('elementos.index', $params);
    }

    public function render()
    {
        $user = auth()->user();
        $esAdmin = $user->esAdminDepartamento() || $user->esProduccionDepartamento();

        $query = Planilla::with(['user', 'elementos', 'cliente', 'obra', 'revisor']);

        // Filtro "solo mis planillas" salvo admins
        if (!$esAdmin) {
            $query->where('users_id', $user->id);
        }

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $totalPesoFiltrado = (clone $query)->sum('peso_total');
        $planillas = $query->paginate($this->perPage);

        // Cargar suma de pesos fabricados
        $planillas->loadSum([
            'elementos as suma_peso_completados' => function ($query) {
                $query->where('estado', 'fabricado');
            }
        ], 'peso');

        // Contador de planillas sin revisar
        $planillasSinRevisar = Planilla::where('revisada', false)
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->count();

        $clientes = Cliente::select('id', 'codigo', 'empresa')->get();
        $obras = Obra::select('id', 'cod_obra', 'obra')->get();

        return view('livewire.planillas-table', [
            'planillas' => $planillas,
            'clientes' => $clientes,
            'obras' => $obras,
            'totalPesoFiltrado' => $totalPesoFiltrado,
            'planillasSinRevisar' => $planillasSinRevisar,
        ]);
    }
}
