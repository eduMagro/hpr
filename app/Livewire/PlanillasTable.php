<?php

namespace App\Livewire;

use App\Models\Planilla;
use App\Models\Cliente;
use App\Models\Obra;
use App\Services\OrdenPlanillaService;
use App\Services\PlanillaAprobacionAlertaService;
use App\Helpers\FechaEntregaHelper;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class PlanillasTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url(keep: true)]
    public $id = '';

    #[Url(keep: true)]
    public $codigo = '';

    #[Url(keep: true)]
    public $codigo_cliente = '';

    #[Url(keep: true)]
    public $cliente = '';

    #[Url(keep: true)]
    public $cod_obra = '';

    #[Url(keep: true)]
    public $nom_obra = '';

    #[Url(keep: true)]
    public $secciones = [];

    #[Url(keep: true)]
    public $seccionTextoLibre = '';

    #[Url(keep: true)]
    public $descripcion = '';

    #[Url(keep: true)]
    public $ensamblado = '';

    #[Url(keep: true)]
    public $comentario = '';

    #[Url(keep: true)]
    public $estado = '';

    #[Url(keep: true)]
    public $fecha_inicio = '';

    #[Url(keep: true)]
    public $fecha_finalizacion = '';

    #[Url(keep: true)]
    public $fecha_importacion = '';

    #[Url(keep: true)]
    public $fecha_estimada_entrega = '';

    #[Url(keep: true)]
    public $usuario = '';

    #[Url(keep: true)]
    public $revisada = '';

    #[Url(keep: true)]
    public $aprobada = '';

    #[Url(keep: true)]
    public $sort = 'created_at';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 10;

    // Sistema de selección múltiple para aprobación en masa
    public $modoSeleccion = false;
    public $planillasSeleccionadas = [];

    // Cuando cambia cualquier filtro, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }

        // Si se escribe en texto libre, limpiar checkboxes
        if ($property === 'seccionTextoLibre' && !empty($this->seccionTextoLibre)) {
            $this->secciones = [];
        }
    }

    public function aplicarFiltros($query)
    {
        // ID exacto
        if (!empty($this->id)) {
            $query->where('id', (int) $this->id);
        }

        // Código (permite múltiples valores separados por coma o punto)
        if (!empty($this->codigo)) {
            $input = trim($this->codigo);

            // Separar por coma o punto
            $codigos = preg_split('/[,.]/', $input);
            $codigos = array_map('trim', $codigos);
            $codigos = array_filter($codigos, fn($c) => $c !== '');

            if (count($codigos) > 1) {
                // Múltiples códigos: OR entre ellos
                $query->where(function ($q) use ($codigos) {
                    foreach ($codigos as $codigo) {
                        $q->orWhere('codigo', 'like', "%{$codigo}%");
                    }
                });
            } elseif (count($codigos) === 1) {
                $codigo = $codigos[0];
                // Formato completo tipo 2025-004512
                if (preg_match('/^(\d{4})-(\d{1,6})$/', $codigo, $m)) {
                    $anio = $m[1];
                    $num  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
                    $codigoFormateado = "{$anio}-{$num}";
                    $query->where('codigo', 'like', "%{$codigoFormateado}%");
                }
                // Solo número final (ej. "4512")
                elseif (preg_match('/^\d{1,6}$/', $codigo)) {
                    $query->where('codigo', 'like', "%{$codigo}%");
                }
                // Búsqueda genérica
                else {
                    $query->where('codigo', 'like', "%{$codigo}%");
                }
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

        // Sección (múltiples checkboxes O texto libre)
        if (!empty($this->secciones) && is_array($this->secciones)) {
            $query->whereIn('seccion', $this->secciones);
        } elseif (!empty($this->seccionTextoLibre)) {
            $query->where('seccion', 'like', '%' . trim($this->seccionTextoLibre) . '%');
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

        // Aprobada (filtro especial)
        // Por defecto (vacío) solo muestra aprobadas, excepto si se filtra explícitamente
        if ($this->aprobada === '') {
            // Por defecto: solo aprobadas
            $query->where('aprobada', true);
        } elseif ($this->aprobada === 'todas') {
            // Mostrar todas (aprobadas y no aprobadas)
            // No aplicar filtro
        } else {
            // Filtro explícito: 1 = aprobadas, 0 = no aprobadas
            $query->where('aprobada', (bool) $this->aprobada);
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $columnasPermitidas = [
            'id',
            'codigo',
            'seccion',
            'descripcion',
            'ensamblado',
            'comentario',
            'peso_total',
            'estado',
            'fecha_inicio',
            'fecha_finalizacion',
            'fecha_estimada_entrega',
            'revisada',
            'revisada_at',
            'aprobada',
            'aprobada_at',
            'fecha_creacion_ferrawin',
            'created_at',
        ];

        // Ordenamiento especial para relaciones
        if ($this->sort === 'codigo_cliente') {
            return $query->join('clientes', 'planillas.cliente_id', '=', 'clientes.id')
                        ->orderBy('clientes.codigo', $this->order)
                        ->select('planillas.*');
        }

        if ($this->sort === 'cliente_id') {
            return $query->join('clientes', 'planillas.cliente_id', '=', 'clientes.id')
                        ->orderBy('clientes.empresa', $this->order)
                        ->select('planillas.*');
        }

        if ($this->sort === 'codigo_obra') {
            return $query->join('obras', 'planillas.obra_id', '=', 'obras.id')
                        ->orderBy('obras.cod_obra', $this->order)
                        ->select('planillas.*');
        }

        if ($this->sort === 'obra_id') {
            return $query->join('obras', 'planillas.obra_id', '=', 'obras.id')
                        ->orderBy('obras.obra', $this->order)
                        ->select('planillas.*');
        }

        if ($this->sort === 'usuario_id') {
            return $query->join('users', 'planillas.users_id', '=', 'users.id')
                        ->orderBy('users.name', $this->order)
                        ->select('planillas.*');
        }

        if ($this->sort === 'revisor_id') {
            return $query->leftJoin('users as revisores', 'planillas.revisada_por_id', '=', 'revisores.id')
                        ->orderBy('revisores.name', $this->order)
                        ->select('planillas.*');
        }

        $sortBy = in_array($this->sort, $columnasPermitidas) ? $this->sort : 'created_at';
        $order = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

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
            'secciones',
            'seccionTextoLibre',
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
            'aprobada',
            'sort',
            'order'
        ]);
        $this->resetPage();
    }

    /**
     * Toggle para seleccionar/deseleccionar una sección
     */
    public function toggleSeccion($seccion)
    {
        // Limpiar texto libre al usar checkboxes
        $this->seccionTextoLibre = '';

        if (in_array($seccion, $this->secciones)) {
            $this->secciones = array_values(array_diff($this->secciones, [$seccion]));
        } else {
            $this->secciones[] = $seccion;
        }
        $this->resetPage();
    }

    /**
     * Limpiar todas las secciones seleccionadas y texto libre
     */
    public function limpiarSecciones()
    {
        $this->secciones = [];
        $this->seccionTextoLibre = '';
        $this->resetPage();
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->id)) {
            $filtros[] = "<strong>ID:</strong> {$this->id}";
        }
        if (!empty($this->codigo)) {
            $filtros[] = "<strong>Código:</strong> {$this->codigo}";
        }
        if (!empty($this->codigo_cliente)) {
            $filtros[] = "<strong>Cód. Cliente:</strong> {$this->codigo_cliente}";
        }
        if (!empty($this->cliente)) {
            $filtros[] = "<strong>Cliente:</strong> {$this->cliente}";
        }
        if (!empty($this->cod_obra)) {
            $filtros[] = "<strong>Cód. Obra:</strong> {$this->cod_obra}";
        }
        if (!empty($this->nom_obra)) {
            $filtros[] = "<strong>Obra:</strong> {$this->nom_obra}";
        }
        if (!empty($this->secciones)) {
            $filtros[] = "<strong>Secciones:</strong> " . implode(', ', $this->secciones);
        } elseif (!empty($this->seccionTextoLibre)) {
            $filtros[] = "<strong>Sección:</strong> {$this->seccionTextoLibre}";
        }
        if (!empty($this->descripcion)) {
            $filtros[] = "<strong>Descripción:</strong> {$this->descripcion}";
        }
        if (!empty($this->ensamblado)) {
            $filtros[] = "<strong>Ensamblado:</strong> {$this->ensamblado}";
        }
        if (!empty($this->comentario)) {
            $filtros[] = "<strong>Comentario:</strong> {$this->comentario}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }
        if (!empty($this->fecha_inicio)) {
            $filtros[] = "<strong>Fecha Inicio:</strong> {$this->fecha_inicio}";
        }
        if (!empty($this->fecha_finalizacion)) {
            $filtros[] = "<strong>Fecha Finalización:</strong> {$this->fecha_finalizacion}";
        }
        if (!empty($this->fecha_importacion)) {
            $filtros[] = "<strong>Fecha Importación:</strong> {$this->fecha_importacion}";
        }
        if (!empty($this->fecha_estimada_entrega)) {
            $filtros[] = "<strong>Fecha Estimada Entrega:</strong> {$this->fecha_estimada_entrega}";
        }
        if (!empty($this->usuario)) {
            $filtros[] = "<strong>Usuario:</strong> {$this->usuario}";
        }
        if ($this->revisada !== '') {
            $filtros[] = "<strong>Revisada:</strong> " . ($this->revisada ? 'Sí' : 'No');
        }
        if ($this->aprobada === 'todas') {
            $filtros[] = "<strong>Aprobada:</strong> Todas";
        } elseif ($this->aprobada === '0') {
            $filtros[] = "<strong>Aprobada:</strong> No";
        }
        // No mostrar filtro cuando es '' porque es el estado por defecto (solo aprobadas)

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'codigo' => 'Código',
                'codigo_cliente' => 'Código Cliente',
                'cliente_id' => 'Cliente',
                'codigo_obra' => 'Código Obra',
                'obra_id' => 'Obra',
                'seccion' => 'Sección',
                'descripcion' => 'Descripción',
                'ensamblado' => 'Ensamblado',
                'comentario' => 'Comentario',
                'peso_total' => 'Peso Total',
                'estado' => 'Estado',
                'fecha_inicio' => 'Fecha Inicio',
                'fecha_finalizacion' => 'Fecha Finalización',
                'created_at' => 'Fecha Importación',
                'fecha_estimada_entrega' => 'Fecha Entrega',
                'usuario_id' => 'Usuario',
                'revisada' => 'Revisada',
                'revisor_id' => 'Revisada Por',
                'revisada_at' => 'Fecha Revisión',
                'aprobada' => 'Aprobada',
                'aprobada_at' => 'Fecha Aprobación',
                'fecha_creacion_ferrawin' => 'Fecha Ferrawin',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
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
            $planilla->revisada_por_id = auth()->id();
            $planilla->revisada_at = now();
        }

        $planilla->save();

        // Mensaje de éxito
        $this->dispatch('planilla-actualizada', [
            'message' => $planilla->revisada ? 'Planilla marcada como revisada' : 'Planilla marcada como no revisada'
        ]);
    }

    public function aprobarPlanilla($planillaId)
    {
        $planilla = Planilla::findOrFail($planillaId);

        // Solo se puede aprobar si no está aprobada ya
        if ($planilla->aprobada) {
            $this->dispatch('planilla-actualizada', [
                'message' => 'Esta planilla ya está aprobada',
                'type' => 'warning'
            ]);
            return;
        }

        $planilla->aprobada = true;
        $planilla->aprobada_por_id = auth()->id();
        $planilla->aprobada_at = now();
        // Fecha de entrega = 7 días después de la aprobación (ajustada para evitar fines de semana y festivos)
        $planilla->fecha_estimada_entrega = FechaEntregaHelper::calcular(now(), 7);
        $planilla->save();

        // Crear posición en orden_planillas al aprobar
        app(OrdenPlanillaService::class)->crearOrdenParaPlanilla($planillaId);

        // Notificar aprobación a destinatarios configurados
        app(PlanillaAprobacionAlertaService::class)
            ->notificarAprobacion(collect([$planilla]), auth()->user());

        $this->dispatch('planilla-actualizada', [
            'message' => 'Planilla aprobada. Fecha de entrega: ' . $planilla->fecha_estimada_entrega,
            'type' => 'success'
        ]);
    }

    public function verSinAprobar()
    {
        $this->aprobada = '0';
        $this->resetPage();
    }

    /**
     * Activa/desactiva el modo de selección múltiple
     */
    public function toggleModoSeleccion()
    {
        $this->modoSeleccion = !$this->modoSeleccion;
        if (!$this->modoSeleccion) {
            $this->planillasSeleccionadas = [];
        }
    }

    /**
     * Añade o quita una planilla de la selección
     */
    public function toggleSeleccion($planillaId)
    {
        if (in_array($planillaId, $this->planillasSeleccionadas)) {
            $this->planillasSeleccionadas = array_values(array_diff($this->planillasSeleccionadas, [$planillaId]));
        } else {
            $this->planillasSeleccionadas[] = $planillaId;
        }
    }

    /**
     * Selecciona todas las planillas sin aprobar de la página actual
     */
    public function seleccionarTodasPagina($ids)
    {
        $idsArray = is_array($ids) ? $ids : json_decode($ids, true);
        foreach ($idsArray as $id) {
            if (!in_array($id, $this->planillasSeleccionadas)) {
                $this->planillasSeleccionadas[] = (int) $id;
            }
        }
    }

    /**
     * Deselecciona todas las planillas
     */
    public function deseleccionarTodas()
    {
        $this->planillasSeleccionadas = [];
    }

    /**
     * Aprueba todas las planillas seleccionadas
     */
    public function aprobarSeleccionadas()
    {
        if (empty($this->planillasSeleccionadas)) {
            $this->dispatch('planilla-actualizada', [
                'message' => 'No hay planillas seleccionadas',
                'type' => 'warning'
            ]);
            return;
        }

        $planillas = Planilla::whereIn('id', $this->planillasSeleccionadas)
            ->where('aprobada', false)
            ->get();

        $ordenService = app(OrdenPlanillaService::class);

        $count = 0;
        foreach ($planillas as $planilla) {
            $planilla->aprobada = true;
            $planilla->aprobada_por_id = auth()->id();
            $planilla->aprobada_at = now();
            $planilla->fecha_estimada_entrega = FechaEntregaHelper::calcular(now(), 7);
            $planilla->save();

            // Crear posición en orden_planillas al aprobar
            $ordenService->crearOrdenParaPlanilla($planilla->id);

            $count++;
        }

        // Notificar aprobación a destinatarios configurados
        if ($planillas->isNotEmpty()) {
            app(PlanillaAprobacionAlertaService::class)
                ->notificarAprobacion($planillas, auth()->user());
        }

        // Resetear selección y modo
        $this->planillasSeleccionadas = [];
        $this->modoSeleccion = false;

        $this->dispatch('planilla-actualizada', [
            'message' => "Se han aprobado {$count} planillas correctamente",
            'type' => 'success'
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
        return $this->redirect(route('elementos.index', $params));
    }

    public function render()
    {
        $user = auth()->user();
        $esAdmin = $user->esAdminDepartamento() || $user->esProduccionDepartamento();

        $query = Planilla::with([
            'user',
            'cliente',
            'obra',
            'revisor',
            'aprobador'
        ])->withSum([
            'elementos as suma_peso_completados' => function ($query) {
                $query->where('elaborado', 1);
            }
        ], 'peso')
        ->withCount('entidades');

        // Filtro "solo mis planillas" salvo admins
        if (!$esAdmin) {
            $query->where('users_id', $user->id);
        }

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $totalPesoFiltrado = (clone $query)->sum('peso_total');
        $planillas = $query->paginate($this->perPage);

        // Contador de planillas sin revisar
        $planillasSinRevisar = Planilla::where('revisada', false)
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->count();

        // Contador de planillas sin aprobar
        $planillasSinAprobar = Planilla::where('aprobada', false)
            ->whereIn('estado', ['pendiente', 'fabricando'])
            ->count();

        $clientes = Cliente::select('id', 'codigo', 'empresa')->get();
        $obras = Obra::select('id', 'cod_obra', 'obra')->get();

        // Obtener secciones únicas disponibles (de los registros ya filtrados, excepto el filtro de secciones)
        $queryParaSecciones = Planilla::query();
        if (!$esAdmin) {
            $queryParaSecciones->where('users_id', $user->id);
        }
        // Aplicar todos los filtros EXCEPTO el de secciones
        $seccionesOriginales = $this->secciones;
        $this->secciones = [];
        $queryParaSecciones = $this->aplicarFiltros($queryParaSecciones);
        $this->secciones = $seccionesOriginales;

        $seccionesDisponibles = $queryParaSecciones
            ->whereNotNull('seccion')
            ->where('seccion', '!=', '')
            ->distinct()
            ->orderBy('seccion')
            ->pluck('seccion')
            ->toArray();

        return view('livewire.planillas-table', [
            'planillas' => $planillas,
            'clientes' => $clientes,
            'obras' => $obras,
            'totalPesoFiltrado' => $totalPesoFiltrado,
            'planillasSinRevisar' => $planillasSinRevisar,
            'planillasSinAprobar' => $planillasSinAprobar,
            'filtrosActivos' => $this->getFiltrosActivos(),
            'seccionesDisponibles' => $seccionesDisponibles,
        ]);
    }
}
