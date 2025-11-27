<?php

namespace App\Livewire;

use App\Models\Salida;
use App\Models\SalidaCliente;
use App\Models\EmpresaTransporte;
use App\Models\Camion;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exports\SalidasExport;
use Maatwebsite\Excel\Facades\Excel;

class SalidasFerrallaTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    #[Url(keep: true)]
    public $codigo_salida = '';

    #[Url(keep: true)]
    public $codigo_sage = '';

    #[Url(keep: true)]
    public $cliente = '';

    #[Url(keep: true)]
    public $obra = '';

    #[Url(keep: true)]
    public $empresa_transporte = '';

    #[Url(keep: true)]
    public $estado = '';

    #[Url(keep: true)]
    public $comentario = '';

    #[Url(keep: true)]
    public $fecha_desde = '';

    #[Url(keep: true)]
    public $fecha_hasta = '';

    #[Url(keep: true)]
    public $mes = '';

    #[Url(keep: true)]
    public $fecha = '';

    #[Url(keep: true)]
    public $sort = 'created_at';

    #[Url(keep: true)]
    public $order = 'desc';

    #[Url(keep: true)]
    public $perPage = 25;

    // Datos para selects
    public $empresasTransporte = [];
    public $camionesJson = [];
    public $mesesDisponibles = [];

    public function mount()
    {
        $this->empresasTransporte = EmpresaTransporte::orderBy('nombre')->get();
        $this->camionesJson = Camion::select('id', 'modelo', 'empresa_id')->get()->toArray();
        $this->cargarMesesDisponibles();
    }

    public function cargarMesesDisponibles()
    {
        $this->mesesDisponibles = Salida::selectRaw("DATE_FORMAT(fecha_salida, '%Y-%m') as mes")
            ->whereNotNull('fecha_salida')
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->pluck('mes')
            ->filter()
            ->map(function ($mes) {
                $fecha = Carbon::createFromFormat('Y-m', $mes);
                return [
                    'value' => $mes,
                    'label' => ucfirst($fecha->translatedFormat('F Y'))
                ];
            })
            ->toArray();
    }

    public function updated($property)
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function aplicarFiltros($query)
    {
        if (!empty($this->codigo_salida)) {
            $query->where('codigo_salida', 'like', '%' . trim($this->codigo_salida) . '%');
        }

        if (!empty($this->codigo_sage)) {
            $query->where('codigo_sage', 'like', '%' . trim($this->codigo_sage) . '%');
        }

        if (!empty($this->cliente)) {
            $query->whereHas('salidaClientes.cliente', function ($q) {
                $q->where('empresa', 'like', '%' . trim($this->cliente) . '%');
            });
        }

        if (!empty($this->obra)) {
            $query->whereHas('salidaClientes.obra', function ($q) {
                $q->where('obra', 'like', '%' . trim($this->obra) . '%');
            });
        }

        if (!empty($this->empresa_transporte)) {
            $query->whereHas('empresaTransporte', function ($q) {
                $q->where('nombre', 'like', '%' . trim($this->empresa_transporte) . '%');
            });
        }

        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        if (!empty($this->comentario)) {
            $query->where('comentario', 'like', '%' . trim($this->comentario) . '%');
        }

        // Filtro por mes
        if (!empty($this->mes)) {
            $query->whereRaw("DATE_FORMAT(fecha_salida, '%Y-%m') = ?", [$this->mes]);
        }

        // Filtro por día específico
        if (!empty($this->fecha)) {
            $query->whereDate('fecha_salida', Carbon::parse($this->fecha)->format('Y-m-d'));
        }

        // Filtro por rango de fechas
        if (!empty($this->fecha_desde)) {
            $query->whereDate('fecha_salida', '>=', Carbon::parse($this->fecha_desde)->format('Y-m-d'));
        }

        if (!empty($this->fecha_hasta)) {
            $query->whereDate('fecha_salida', '<=', Carbon::parse($this->fecha_hasta)->format('Y-m-d'));
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $columnasDirectas = [
            'id',
            'codigo_salida',
            'codigo_sage',
            'empresa_transporte_id',
            'camion_id',
            'estado',
            'fecha_salida',
            'created_at',
        ];

        $order = strtolower($this->order) === 'asc' ? 'asc' : 'desc';

        // Ordenación por cliente (relación)
        if ($this->sort === 'cliente') {
            return $query->orderBy(
                DB::table('salida_cliente')
                    ->join('clientes', 'salida_cliente.cliente_id', '=', 'clientes.id')
                    ->whereColumn('salida_cliente.salida_id', 'salidas.id')
                    ->select('clientes.empresa')
                    ->limit(1),
                $order
            );
        }

        // Ordenación por obra (relación)
        if ($this->sort === 'obra') {
            return $query->orderBy(
                DB::table('salida_cliente')
                    ->join('obras', 'salida_cliente.obra_id', '=', 'obras.id')
                    ->whereColumn('salida_cliente.salida_id', 'salidas.id')
                    ->select('obras.obra')
                    ->limit(1),
                $order
            );
        }

        // Ordenación por columnas directas
        $sortBy = in_array($this->sort, $columnasDirectas) ? $this->sort : 'created_at';
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
            'codigo_salida',
            'codigo_sage',
            'cliente',
            'obra',
            'empresa_transporte',
            'estado',
            'comentario',
            'fecha_desde',
            'fecha_hasta',
            'mes',
            'fecha',
            'sort',
            'order'
        ]);
        $this->resetPage();
    }

    public function exportar()
    {
        $query = Salida::with([
            'empresaTransporte',
            'camion',
            'salidaClientes.cliente',
            'salidaClientes.obra',
        ]);

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $salidas = $query->get();
        $salidaIds = $salidas->pluck('id')->toArray();
        $resumenEmpresa = $this->calcularResumenEmpresaTransporte($salidaIds);
        $resumenClienteObra = $this->calcularResumenClienteObra($salidaIds);

        // Generar nombre del archivo según filtros
        $nombreArchivo = 'salidas_ferralla';
        if (!empty($this->mes)) {
            $nombreArchivo .= '_' . $this->mes;
        } elseif (!empty($this->fecha_desde) || !empty($this->fecha_hasta)) {
            if (!empty($this->fecha_desde)) {
                $nombreArchivo .= '_desde_' . Carbon::parse($this->fecha_desde)->format('Y-m-d');
            }
            if (!empty($this->fecha_hasta)) {
                $nombreArchivo .= '_hasta_' . Carbon::parse($this->fecha_hasta)->format('Y-m-d');
            }
        }
        $nombreArchivo .= '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new SalidasExport($salidas, $resumenEmpresa, $resumenClienteObra), $nombreArchivo);
    }

    public function getFiltrosActivos()
    {
        $filtros = [];

        if (!empty($this->codigo_salida)) {
            $filtros[] = "<strong>Código Salida:</strong> {$this->codigo_salida}";
        }
        if (!empty($this->codigo_sage)) {
            $filtros[] = "<strong>Código Sage:</strong> {$this->codigo_sage}";
        }
        if (!empty($this->cliente)) {
            $filtros[] = "<strong>Cliente:</strong> {$this->cliente}";
        }
        if (!empty($this->obra)) {
            $filtros[] = "<strong>Obra:</strong> {$this->obra}";
        }
        if (!empty($this->empresa_transporte)) {
            $filtros[] = "<strong>E. Transporte:</strong> {$this->empresa_transporte}";
        }
        if (!empty($this->estado)) {
            $filtros[] = "<strong>Estado:</strong> " . ucfirst($this->estado);
        }
        if (!empty($this->comentario)) {
            $filtros[] = "<strong>Comentario:</strong> {$this->comentario}";
        }
        if (!empty($this->mes)) {
            $fecha = Carbon::createFromFormat('Y-m', $this->mes);
            $filtros[] = "<strong>Mes:</strong> " . ucfirst($fecha->translatedFormat('F Y'));
        }
        if (!empty($this->fecha)) {
            $filtros[] = "<strong>Día:</strong> " . Carbon::parse($this->fecha)->format('d/m/Y');
        }
        if (!empty($this->fecha_desde)) {
            $filtros[] = "<strong>Desde:</strong> " . Carbon::parse($this->fecha_desde)->format('d/m/Y');
        }
        if (!empty($this->fecha_hasta)) {
            $filtros[] = "<strong>Hasta:</strong> " . Carbon::parse($this->fecha_hasta)->format('d/m/Y');
        }

        if (!empty($this->sort)) {
            $nombresCampos = [
                'id' => 'ID',
                'codigo_salida' => 'Código Salida',
                'codigo_sage' => 'Código Sage',
                'cliente' => 'Cliente',
                'obra' => 'Obra',
                'empresa_transporte_id' => 'E. Transporte',
                'camion_id' => 'Camión',
                'estado' => 'Estado',
                'fecha_salida' => 'Fecha Salida',
                'created_at' => 'Fecha Creación',
            ];

            $nombreCampo = $nombresCampos[$this->sort] ?? ucfirst($this->sort);
            $direccion = $this->order === 'asc' ? '↑ Ascendente' : '↓ Descendente';
            $filtros[] = "<strong>Ordenado por:</strong> {$nombreCampo} ({$direccion})";
        }

        return $filtros;
    }

    /**
     * Calcular resumen por empresa de transporte
     */
    public function calcularResumenEmpresaTransporte($salidaIds)
    {
        if (empty($salidaIds)) {
            return [];
        }

        $resumen = [];

        $datos = SalidaCliente::whereIn('salida_id', $salidaIds)
            ->with(['salida.empresaTransporte'])
            ->get();

        foreach ($datos as $registro) {
            $empresaNombre = $registro->salida?->empresaTransporte?->nombre ?? 'Sin empresa';

            if (!isset($resumen[$empresaNombre])) {
                $resumen[$empresaNombre] = [
                    'horas_paralizacion' => 0,
                    'importe_paralizacion' => 0,
                    'horas_grua' => 0,
                    'importe_grua' => 0,
                    'horas_almacen' => 0,
                    'importe' => 0,
                    'total' => 0,
                ];
            }

            $resumen[$empresaNombre]['horas_paralizacion'] += $registro->horas_paralizacion ?? 0;
            $resumen[$empresaNombre]['importe_paralizacion'] += $registro->importe_paralizacion ?? 0;
            $resumen[$empresaNombre]['horas_grua'] += $registro->horas_grua ?? 0;
            $resumen[$empresaNombre]['importe_grua'] += $registro->importe_grua ?? 0;
            $resumen[$empresaNombre]['horas_almacen'] += $registro->horas_almacen ?? 0;
            $resumen[$empresaNombre]['importe'] += $registro->importe ?? 0;

            $resumen[$empresaNombre]['total'] =
                $resumen[$empresaNombre]['importe_paralizacion'] +
                $resumen[$empresaNombre]['importe_grua'] +
                $resumen[$empresaNombre]['importe'];
        }

        return $resumen;
    }

    /**
     * Calcular resumen por cliente y obra
     */
    public function calcularResumenClienteObra($salidaIds)
    {
        if (empty($salidaIds)) {
            return [];
        }

        $resumen = [];

        $datos = SalidaCliente::whereIn('salida_id', $salidaIds)
            ->with(['cliente', 'obra'])
            ->get();

        foreach ($datos as $registro) {
            $clienteNombre = $registro->cliente?->empresa ?? 'Sin cliente';
            $obraNombre = $registro->obra?->obra ?? 'Sin obra';
            $clave = "{$clienteNombre} - {$obraNombre}";

            if (!isset($resumen[$clave])) {
                $resumen[$clave] = [
                    'horas_paralizacion' => 0,
                    'importe_paralizacion' => 0,
                    'horas_grua' => 0,
                    'importe_grua' => 0,
                    'horas_almacen' => 0,
                    'importe' => 0,
                    'total' => 0,
                ];
            }

            $resumen[$clave]['horas_paralizacion'] += $registro->horas_paralizacion ?? 0;
            $resumen[$clave]['importe_paralizacion'] += $registro->importe_paralizacion ?? 0;
            $resumen[$clave]['horas_grua'] += $registro->horas_grua ?? 0;
            $resumen[$clave]['importe_grua'] += $registro->importe_grua ?? 0;
            $resumen[$clave]['horas_almacen'] += $registro->horas_almacen ?? 0;
            $resumen[$clave]['importe'] += $registro->importe ?? 0;

            $resumen[$clave]['total'] =
                $resumen[$clave]['importe_paralizacion'] +
                $resumen[$clave]['importe_grua'] +
                $resumen[$clave]['importe'];
        }

        return $resumen;
    }

    public function render()
    {
        $query = Salida::with([
            'empresaTransporte',
            'camion',
            'salidaClientes.cliente',
            'salidaClientes.obra',
            'paquetes.planilla',
        ]);

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        // Obtener IDs de todas las salidas filtradas (sin paginación) para los resúmenes
        $salidaIdsFiltradas = (clone $query)->pluck('id')->toArray();

        // Calcular resúmenes
        $resumenEmpresaTransporte = $this->calcularResumenEmpresaTransporte($salidaIdsFiltradas);
        $resumenClienteObra = $this->calcularResumenClienteObra($salidaIdsFiltradas);

        // Paginación para la tabla
        $salidas = $query->paginate($this->perPage);

        // Título del resumen según filtros
        $tituloResumen = 'Todos los registros';
        if (!empty($this->fecha)) {
            $tituloResumen = Carbon::parse($this->fecha)->translatedFormat('l, d F Y');
        } elseif (!empty($this->mes)) {
            $fecha = Carbon::createFromFormat('Y-m', $this->mes);
            $tituloResumen = ucfirst($fecha->translatedFormat('F Y'));
        } elseif (!empty($this->fecha_desde) || !empty($this->fecha_hasta)) {
            $desde = !empty($this->fecha_desde) ? Carbon::parse($this->fecha_desde)->format('d/m/Y') : 'inicio';
            $hasta = !empty($this->fecha_hasta) ? Carbon::parse($this->fecha_hasta)->format('d/m/Y') : 'hoy';
            $tituloResumen = "Desde {$desde} hasta {$hasta}";
        }

        return view('livewire.salidas-ferralla-table', [
            'salidas' => $salidas,
            'empresasTransporte' => $this->empresasTransporte,
            'camionesJson' => $this->camionesJson,
            'filtrosActivos' => $this->getFiltrosActivos(),
            'mesesDisponibles' => $this->mesesDisponibles,
            'resumenEmpresaTransporte' => $resumenEmpresaTransporte,
            'resumenClienteObra' => $resumenClienteObra,
            'tituloResumen' => $tituloResumen,
            'totalSalidasFiltradas' => count($salidaIdsFiltradas),
        ]);
    }
}
