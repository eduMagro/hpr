<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\SalidaPaquete;
use Illuminate\Support\Collection;

use App\Models\Obra;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClienteController extends Controller
{
    /**
     * Aplica filtros a la consulta de clientes según los parámetros de la solicitud.
     */
    private function aplicarFiltros(Request $request)
    {
        // Iniciar la consulta con las relaciones necesarias
        $query = Cliente::query();

        // Aplicar filtros si están presentes en la solicitud
        if ($request->filled('empresa')) {
            $query->where('empresa', 'like', '%' . $request->empresa . '%');
        }

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }
        if ($request->filled('telefono')) {
            $query->where('contacto1_telefono', 'like', '%' . $request->telefono . '%');
        }
        if ($request->filled('email')) {
            $query->where('contacto1_email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('ciudad')) {
            $query->where('ciudad', 'like', '%' . $request->ciudad . '%');
        }
        if ($request->filled('provincia')) {
            $query->where('provincia', 'like', '%' . $request->provincia . '%');
        }
        if ($request->filled('pais')) {
            $query->where('pais', 'like', '%' . $request->pais . '%');
        }
        if ($request->filled('cif_nif')) {
            $query->where('cif_nif', 'like', '%' . $request->cif_nif . '%');
        }
        // Buscar por obras asociadas
        if ($request->filled('obra') || $request->filled('codigo_obra')) {
            $obraQuery = \App\Models\Obra::query();

            if ($request->filled('obra')) {
                $obraQuery->where('obra', 'like', '%' . $request->obra . '%');
            }

            if ($request->filled('cod_obra')) {
                $obraQuery->where('cod_obra', $request->cod_obra);
            }


            $clienteIds = $obraQuery->pluck('cliente_id')->unique()->toArray();

            // Si no hay resultados, evitar mostrar todos
            if (count($clienteIds) > 0) {
                $query->whereIn('id', $clienteIds);
            } else {
                // Si no se encuentra ninguna obra, forzamos resultado vacío
                $query->whereRaw('0 = 1');
            }
        }

        if ($request->filled('activo')) {
            if ($request->activo == '1') {
                $query->whereHas('obras', function ($q) {
                    $q->where('estado', 'activa');
                });
            } elseif ($request->activo == '0') {
                $query->whereDoesntHave('obras', function ($q) {
                    $q->where('estado', 'activa');
                });
            }
        }

        // Obtener número de registros por página, por defecto 10
        $perPage = $request->get('per_page', 10);

        // Devolver clientes paginados con los filtros aplicados
        return $query->with('obras')->paginate($perPage);
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];


        if ($request->filled('empresa')) {
            $filtros[] = 'Empresa: <strong>' . $request->empresa . '</strong>';
        }

        if ($request->filled('codigo')) {
            $filtros[] = 'Código Planilla: <strong>' . $request->codigo . '</strong>';
        }

        if ($request->filled('codigo_cliente')) {
            $filtros[] = 'Código cliente: <strong>' . $request->codigo_cliente . '</strong>';
        }

        if ($request->filled('cliente')) {
            $filtros[] = 'Cliente: <strong>' . $request->cliente . '</strong>';
        }

        if ($request->filled('cod_obra')) {
            $filtros[] = 'Código obra: <strong>' . $request->cod_obra . '</strong>';
        }

        if ($request->filled('nom_obra')) {
            $filtros[] = 'Obra: <strong>' . $request->nom_obra . '</strong>';
        }


        if ($request->filled('seccion')) {
            $filtros[] = 'Sección: <strong>' . $request->seccion . '</strong>';
        }

        if ($request->filled('descripcion')) {
            $filtros[] = 'Descripción: <strong>' . $request->descripcion . '</strong>';
        }

        if ($request->filled('ensamblado')) {
            $filtros[] = 'Ensamblado: <strong>' . $request->ensamblado . '</strong>';
        }


        if ($request->filled('activo')) {
            $estadoTexto = $request->activo == '1' ? 'Sí' : 'No';
            $filtros[] = 'Activo: <strong>' . $estadoTexto . '</strong>';
        }

        if ($request->filled('fecha_finalizacion')) {
            $filtros[] = 'Fecha finalización: <strong>' . $request->fecha_finalizacion . '</strong>';
        }
        if ($request->filled('fecha_importacion')) {
            $filtros[] = 'Fecha importación: <strong>' . $request->fecha_importacion . '</strong>';
        }
        if ($request->filled('fecha_estimada_entrega')) {
            $filtros[] = 'Fecha estimada entrega: <strong>' . $request->fecha_estimada_entrega . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'fecha_estimada_entrega' => 'Entrega estimada',
                'estado' => 'Estado',
                'seccion' => 'Sección',
                'peso_total' => 'Peso total',
            ];
            $orden = $request->order == 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . ($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . $request->per_page . '</strong> registros por página';
        }
        if ($request->filled('codigo_obra')) {
            $filtros[] = 'Código de Obra: <strong>' . $request->codigo_obra . '</strong>';
        }

        if ($request->filled('obra')) {
            $filtros[] = 'Nombre de Obra: <strong>' . $request->obra . '</strong>';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = '';
        if ($isSorted) {
            $icon = $currentOrder === 'asc'
                ? '▲' // flecha hacia arriba
                : '▼'; // flecha hacia abajo
        } else {
            $icon = '⇅'; // símbolo de orden genérico
        }

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . $url . '" class="inline-flex items-center space-x-1">' .
            '<span>' . $titulo . '</span><span class="text-xs">' . $icon . '</span></a>';
    }

    public function index(Request $request)
    {
        $clientes = $this->aplicarFiltros($request);

        foreach ($clientes as $cliente) {
            $cliente->activo = $cliente->obras()->where('estado', 'activa')->exists();
        }

        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'empresa' => $this->getOrdenamiento('empresa', 'Empresa'),
            'codigo' => $this->getOrdenamiento('codigo', 'Código'),
            'contacto1_telefono' => $this->getOrdenamiento('contacto1_telefono', 'Teléfono'),
            'contacto1_email' => $this->getOrdenamiento('contacto1_email', 'Email'),
            'ciudad' => $this->getOrdenamiento('ciudad', 'Ciudad'),
            'provincia' => $this->getOrdenamiento('provincia', 'Provincia'),
            'pais' => $this->getOrdenamiento('pais', 'País'),
            'cif_nif' => $this->getOrdenamiento('cif_nif', 'CIF/NIF'),
            'activo' => $this->getOrdenamiento('activo', 'Activo'),
        ];

        $filtrosActivos = $this->filtrosActivos($request);

        return view('clientes.index', compact('clientes', 'ordenables', 'filtrosActivos'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'empresa' => 'required|unique:clientes|max:255',
            'codigo' => 'required|unique:clientes|max:50',
        ]);

        Cliente::create($request->all());

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    private function getPesoEntregadoPorObra($clienteId): Collection
    {
        return SalidaPaquete::whereHas('paquete.planilla.obra', function ($query) use ($clienteId) {
            $query->where('cliente_id', $clienteId);
        })
            ->with('paquete.planilla.obra') // Cargar la relación hasta obra
            ->get()
            ->groupBy('paquete.planilla.obra_id') // Agrupar por obra
            ->map(fn($salidas) => $salidas->sum('peso_total')); // Sumar el peso entregado por obra
    }

    public function show(Cliente $cliente)
    {
        // Obtener el peso total entregado por cada obra del cliente
        $pesoPorObra = $this->getPesoEntregadoPorObra($cliente->id);

        // Cargar las obras del cliente y añadir el peso entregado
        $obras = $cliente->obras->map(function ($obra) use ($pesoPorObra) {
            $obra->peso_entregado = $pesoPorObra[$obra->id] ?? 0;
            return $obra;
        });
        // Detectamos si el cliente es Hierros Paco Reyes (LIKE %hierros paco reyes%)
        $esPacoReyes = Str::contains(strtolower($cliente->empresa), 'hierros paco reyes');

        return view('clientes.show', compact('cliente', 'obras', 'esPacoReyes'));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        try {
            $request->validate([
                'empresa' => 'required|max:255|unique:clientes,empresa,' . $cliente->id,
                'codigo' => 'required|max:50|unique:clientes,codigo,' . $cliente->id,
                'contacto1_nombre' => 'nullable|max:255',
                'contacto1_telefono' => 'nullable|max:20',
                'contacto1_email' => 'nullable|email|max:255',
                'contacto2_nombre' => 'nullable|max:255',
                'contacto2_telefono' => 'nullable|max:20',
                'contacto2_email' => 'nullable|email|max:255',
                'direccion' => 'nullable|max:255',
                'ciudad' => 'nullable|max:100',
                'provincia' => 'nullable|max:100',
                'pais' => 'nullable|max:100',
                'cif_nif' => 'nullable|max:50|unique:clientes,cif_nif,' . $cliente->id,
                'activo' => 'required|boolean',
            ]);

            // Actualizar los datos del cliente
            $cliente->update($request->all());

            return response()->json(['success' => 'Usuario actualizado correctamente.']);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Cliente $cliente)
    {
        \Log::info('Borrando cliente ' . ($cliente->empresa ?? ('ID ' . $cliente->id)) . ' por el usuario ' . (auth()->user()->nombre_completo ?? 'desconocido'));
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
