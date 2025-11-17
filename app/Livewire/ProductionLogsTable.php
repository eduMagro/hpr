<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Services\ProductionLogger;
use Illuminate\Support\Collection;

class ProductionLogsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Archivo seleccionado
    #[Url(keep: true)]
    public $selectedFile;

    // Para rastrear nuevos logs
    public $lastLogIds = [];
    public $newLogIds = [];

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url(keep: true)]
    public $fecha = '';

    #[Url(keep: true)]
    public $accion = '';

    #[Url(keep: true)]
    public $usuario = '';

    #[Url(keep: true)]
    public $usuario2 = '';

    #[Url(keep: true)]
    public $etiqueta = '';

    #[Url(keep: true)]
    public $planilla = '';

    #[Url(keep: true)]
    public $obra = '';

    #[Url(keep: true)]
    public $cliente = '';

    #[Url(keep: true)]
    public $nave = '';

    #[Url(keep: true)]
    public $maquina = '';

    #[Url(keep: true)]
    public $paquete = '';

    #[Url(keep: true)]
    public $sort = 'Fecha y Hora';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 50;

    public function mount($selectedFile = null)
    {
        $this->selectedFile = $selectedFile ?? basename(ProductionLogger::getCurrentLogPath());
    }

    // Cuando cambia cualquier filtro o archivo, resetear a la página 1
    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }

        // Cuando cambia el archivo seleccionado, también resetear filtros opcionales
        if ($property === 'selectedFile') {
            $this->resetPage();
        }
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'fecha',
            'accion',
            'usuario',
            'usuario2',
            'etiqueta',
            'planilla',
            'obra',
            'cliente',
            'nave',
            'maquina',
            'paquete',
        ]);
        $this->resetPage();
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

    private function readCSVLogs(): Collection
    {
        // Usar el archivo seleccionado o el actual por defecto
        $fileName = $this->selectedFile ?? basename(ProductionLogger::getCurrentLogPath());
        $logPath = storage_path('app/produccion_piezas/' . $fileName);

        if (!file_exists($logPath)) {
            return collect([]);
        }

        try {
            $file = fopen($logPath, 'r');
            $headers = fgetcsv($file, 0, ';');
            $rows = [];

            while (($row = fgetcsv($file, 0, ';')) !== false) {
                if (count($row) === count($headers)) {
                    $rows[] = array_combine($headers, $row);
                }
            }

            fclose($file);

            return collect($rows);
        } catch (\Exception $e) {
            \Log::error('Error leyendo CSV de producción: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function aplicarFiltros($logs)
    {
        if (!empty($this->fecha)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Fecha y Hora'], $this->fecha) !== false;
            });
        }

        if (!empty($this->accion)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Acción'], $this->accion) !== false;
            });
        }

        if (!empty($this->usuario)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Usuario'], $this->usuario) !== false;
            });
        }

        if (!empty($this->usuario2)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Usuario 2'] ?? '', $this->usuario2) !== false;
            });
        }

        if (!empty($this->etiqueta)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Etiqueta'], $this->etiqueta) !== false;
            });
        }

        if (!empty($this->planilla)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Planilla'], $this->planilla) !== false;
            });
        }

        if (!empty($this->obra)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Obra'], $this->obra) !== false;
            });
        }

        if (!empty($this->cliente)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Cliente'], $this->cliente) !== false;
            });
        }

        if (!empty($this->nave)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Nave'] ?? '', $this->nave) !== false;
            });
        }

        if (!empty($this->maquina)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Máquina'], $this->maquina) !== false;
            });
        }

        if (!empty($this->paquete)) {
            $logs = $logs->filter(function ($log) {
                return stripos($log['Paquete'], $this->paquete) !== false;
            });
        }

        return $logs;
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->fecha)) {
            $filtros[] = "<strong>Fecha:</strong> {$this->fecha}";
        }
        if (!empty($this->accion)) {
            $filtros[] = "<strong>Acción:</strong> {$this->accion}";
        }
        if (!empty($this->usuario)) {
            $filtros[] = "<strong>Usuario:</strong> {$this->usuario}";
        }
        if (!empty($this->usuario2)) {
            $filtros[] = "<strong>Usuario 2:</strong> {$this->usuario2}";
        }
        if (!empty($this->etiqueta)) {
            $filtros[] = "<strong>Etiqueta:</strong> {$this->etiqueta}";
        }
        if (!empty($this->planilla)) {
            $filtros[] = "<strong>Planilla:</strong> {$this->planilla}";
        }
        if (!empty($this->obra)) {
            $filtros[] = "<strong>Obra:</strong> {$this->obra}";
        }
        if (!empty($this->cliente)) {
            $filtros[] = "<strong>Cliente:</strong> {$this->cliente}";
        }
        if (!empty($this->nave)) {
            $filtros[] = "<strong>Nave:</strong> {$this->nave}";
        }
        if (!empty($this->maquina)) {
            $filtros[] = "<strong>Máquina:</strong> {$this->maquina}";
        }
        if (!empty($this->paquete)) {
            $filtros[] = "<strong>Paquete:</strong> {$this->paquete}";
        }

        // Añadir ordenamiento
        if (!empty($this->sort)) {
            $nombresCampos = [
                'Fecha y Hora' => 'Fecha y Hora',
                'Acción' => 'Acción',
                'Usuario' => 'Usuario',
                'Usuario 2' => 'Usuario 2',
                'Etiqueta' => 'Etiqueta',
                'Planilla' => 'Planilla',
                'Obra' => 'Obra',
                'Cliente' => 'Cliente',
                'Nave' => 'Nave',
                'Máquina' => 'Máquina',
                'Peso Estimado (kg)' => 'Peso',
                'Paquete' => 'Paquete',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
    }

    public function render()
    {
        $allLogs = $this->readCSVLogs();
        $filteredLogs = $this->aplicarFiltros($allLogs);

        // Ordenar - los registros más recientes primero
        if ($this->order === 'desc') {
            $sortedLogs = $filteredLogs->sortByDesc($this->sort);
        } else {
            $sortedLogs = $filteredLogs->sortBy($this->sort);
        }

        // Detectar nuevos logs comparando con la carga anterior
        $currentLogIds = $sortedLogs->pluck('Fecha y Hora')->toArray();

        if (!empty($this->lastLogIds)) {
            // Identificar los nuevos logs
            $this->newLogIds = array_diff($currentLogIds, $this->lastLogIds);
        }

        // Actualizar los IDs de logs para la próxima comparación
        $this->lastLogIds = $currentLogIds;

        // Paginar manualmente
        $currentPage = $this->getPage();
        $perPage = $this->perPage;
        $total = $sortedLogs->count();

        $paginatedLogs = $sortedLogs->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Crear paginador manual
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedLogs,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Filtrar archivos solo del año actual
        $currentYear = now()->format('Y');
        $logFiles = collect(ProductionLogger::listLogFiles())
            ->filter(function($file) use ($currentYear) {
                // Filtrar solo archivos que contengan el año actual en el nombre
                return str_contains(basename($file['path']), $currentYear);
            })
            ->values()
            ->toArray();

        return view('livewire.production-logs-table', [
            'logs' => $paginator,
            'total' => $total,
            'logFiles' => $logFiles,
            'newLogIds' => $this->newLogIds,
        ]);
    }
}
