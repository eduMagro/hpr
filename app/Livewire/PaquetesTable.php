<?php

namespace App\Livewire;

use App\Models\Paquete;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class PaquetesTable extends Component
{
    use WithPagination;

    // Filtros - usando #[Url] para mantenerlos en la URL
    #[Url]
    public $paquete_id = '';

    #[Url]
    public $codigo = '';

    #[Url]
    public $planilla = '';

    #[Url]
    public $cod_obra = '';

    #[Url]
    public $nom_obra = '';

    #[Url]
    public $codigo_cliente = '';

    #[Url]
    public $cliente = '';

    #[Url]
    public $nave = '';

    #[Url]
    public $ubicacion = '';

    #[Url]
    public $estado = '';

    #[Url]
    public $created_at = '';

    #[Url]
    public $fecha_limite = '';

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
        // ID
        if (!empty($this->paquete_id)) {
            $query->where('id', 'like', '%' . trim($this->paquete_id) . '%');
        }

        // Código
        if (!empty($this->codigo)) {
            $query->where('codigo', 'like', '%' . trim($this->codigo) . '%');
        }

        // Planilla
        if (!empty($this->planilla)) {
            $query->whereHas('planilla', function ($q) {
                $q->where('codigo', 'like', '%' . trim($this->planilla) . '%');
            });
        }

        // Código Obra
        if (!empty($this->cod_obra)) {
            $query->whereHas('planilla.obra', function ($q) {
                $q->where('cod_obra', 'like', '%' . trim($this->cod_obra) . '%');
            });
        }

        // Nombre Obra
        if (!empty($this->nom_obra)) {
            $query->whereHas('planilla.obra', function ($q) {
                $q->where('obra', 'like', '%' . trim($this->nom_obra) . '%');
            });
        }

        // Código Cliente
        if (!empty($this->codigo_cliente)) {
            $query->whereHas('planilla.cliente', function ($q) {
                $q->where('codigo', 'like', '%' . trim($this->codigo_cliente) . '%');
            });
        }

        // Nombre Cliente
        if (!empty($this->cliente)) {
            $query->whereHas('planilla.cliente', function ($q) {
                $q->where('empresa', 'like', '%' . trim($this->cliente) . '%');
            });
        }

        // Nave
        if (!empty($this->nave)) {
            $query->whereHas('nave', function ($q) {
                $q->where('obra', 'like', '%' . trim($this->nave) . '%');
            });
        }

        // Ubicación
        if (!empty($this->ubicacion)) {
            $query->whereHas('ubicacion', function ($q) {
                $q->where('nombre', 'like', '%' . trim($this->ubicacion) . '%');
            });
        }

        // Estado
        if (!empty($this->estado)) {
            $query->where('estado', $this->estado);
        }

        // Fecha de creación
        if (!empty($this->created_at)) {
            $query->whereDate('created_at', Carbon::parse($this->created_at)->format('Y-m-d'));
        }

        // Fecha límite
        if (!empty($this->fecha_limite)) {
            $query->whereHas('planilla', function ($q) {
                $q->whereDate('fecha_estimada_reparto', Carbon::parse($this->fecha_limite)->format('Y-m-d'));
            });
        }

        return $query;
    }

    public function aplicarOrdenamiento($query)
    {
        $columnasPermitidas = [
            'id',
            'codigo',
            'planilla_id',
            'peso',
            'estado',
            'created_at',
        ];

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
            'paquete_id',
            'codigo',
            'planilla',
            'cod_obra',
            'nom_obra',
            'codigo_cliente',
            'cliente',
            'nave',
            'ubicacion',
            'estado',
            'created_at',
            'fecha_limite',
            'sort',
            'order'
        ]);
        $this->resetPage();
    }

    public function render()
    {
        $query = Paquete::with([
            'planilla.obra',
            'planilla.cliente',
            'nave',
            'ubicacion',
            'etiquetas'
        ]);

        $query = $this->aplicarFiltros($query);
        $query = $this->aplicarOrdenamiento($query);

        $paquetes = $query->paginate($this->perPage);

        // Datos para el modal de dibujo
        // Incluir los paquetes de la página actual + últimos 100 para el modal
        $paquetesActualesIds = $paquetes->pluck('id');

        // Cargar paquetes SIN select restrictions para que las relaciones funcionen correctamente
        // Combinar IDs de página actual con últimos 100
        $ultimosPaquetesIds = Paquete::orderBy('created_at', 'desc')
            ->limit(100)
            ->pluck('id');

        $todosLosIds = $paquetesActualesIds->merge($ultimosPaquetesIds)->unique();

        \Log::info('📦 Cargando paquetes para modal', [
            'ids_pagina_actual' => $paquetesActualesIds->toArray(),
            'total_ids_a_cargar' => $todosLosIds->count(),
        ]);

        $paquetesAll = Paquete::with(['etiquetas.elementos'])
            ->whereIn('id', $todosLosIds)
            ->get();

        \Log::info('📦 Paquetes cargados:', [
            'total' => $paquetesAll->count(),
            'primer_paquete_id' => $paquetesAll->first()->id ?? 'N/A',
            'tiene_etiquetas' => $paquetesAll->first()->etiquetas->count() ?? 0,
            'tiene_elementos' => $paquetesAll->first()->etiquetas->first()->elementos->count() ?? 0,
        ]);

        $paquetesJson = $paquetesAll->map(function($p) {
            $etiquetas = $p->etiquetas->map(function($e) {
                $elementos = $e->elementos->map(function($el) {
                    // Debug: Log TODOS los elementos para ver qué pasa
                    \Log::debug('🔍 Mapeando elemento', [
                        'elemento_id' => $el->id,
                        'dimensiones_raw' => $el->dimensiones,
                        'dimensiones_isset' => isset($el->dimensiones),
                        'dimensiones_empty' => empty($el->dimensiones),
                        'attributes' => $el->getAttributes(),
                    ]);

                    return [
                        'id'           => $el->id,
                        'dimensiones'  => $el->dimensiones ?? '',
                    ];
                });

                return [
                    'id'             => $e->id,
                    'etiqueta_sub_id' => $e->etiqueta_sub_id,
                    'nombre'         => $e->nombre,
                    'codigo'         => $e->codigo,
                    'peso_kg'        => $e->peso_kg,
                    'elementos'      => $elementos,
                ];
            });

            return [
                'id'     => $p->id,
                'codigo' => $p->codigo,
                'etiquetas' => $etiquetas,
            ];
        });

        return view('livewire.paquetes-table', [
            'paquetes' => $paquetes,
            'paquetesJson' => $paquetesJson,
        ]);
    }
}
