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


class ClienteController extends Controller
{
    public function index(Request $request)
    {
        // Obtener clientes aplicando filtros
        $clientes = $this->aplicarFiltros($request);
        // Agregar campo 'activo' a cada cliente
        foreach ($clientes as $cliente) {
            $cliente->activo = $cliente->obras()->where('estado', 'activa')->exists();
        }
        return view('clientes.index', compact('clientes'));
    }

    /**
     * Aplica filtros a la consulta de clientes según los parámetros de la solicitud.
     */
    private function aplicarFiltros(Request $request)
    {
        // Iniciar la consulta con las relaciones necesarias
        $query = Cliente::with('obras');

        // Aplicar filtros si están presentes en la solicitud
        if ($request->filled('empresa')) {
            $query->where('empresa', 'like', '%' . $request->empresa . '%');
        }
        // Filtrar por obra (buscando en la relación 'obras')
        if ($request->filled('obra')) {
            $query->whereHas('obras', function ($q) use ($request) {
                $q->where('obra', 'like', '%' . $request->obra . '%');
            });
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
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Obtener número de registros por página, por defecto 10
        $perPage = $request->get('per_page', 10);

        // Devolver clientes paginados con los filtros aplicados
        return $query->paginate($perPage);
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

        return view('clientes.show', compact('cliente', 'obras'));
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
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
