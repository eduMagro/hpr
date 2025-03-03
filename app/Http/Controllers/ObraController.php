<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obra;

class ObraController extends Controller
{
    public function index(Request $request)
    {
        $query = Obra::query();

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('obra', 'like', '%' . $request->buscar . '%')
                    ->orWhere('cod_obra', 'like', '%' . $request->buscar . '%')
                    ->orWhere('cliente', 'like', '%' . $request->buscar . '%')
                    ->orWhere('cod_cliente', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('cod_obra')) {
            $query->where('cod_obra', 'like', '%' . $request->cod_obra . '%');
        }

        if ($request->filled('cliente')) {
            $query->where('cliente', 'like', '%' . $request->cliente . '%');
        }

        if ($request->filled('cod_cliente')) {
            $query->where('cod_cliente', 'like', '%' . $request->cod_cliente . '%');
        }

        if ($request->filled('completada')) {
            $query->where('completada', $request->completada);
        }

        if ($request->filled('sort_by') && in_array($request->sort_by, ['created_at', 'cod_obra', 'cliente', 'cod_cliente'])) {
            $order = $request->order === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort_by, $order);
        }

        $obras = $query->paginate($request->get('per_page', 10));

        return view('obras.index', compact('obras'));
    }

    public function create()
    {
        return view('obras.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'obra' => 'required|string|max:255',
            'cod_obra' => 'required|string|max:50|unique:obras,cod_obra',
            'cliente' => 'required|string|max:255',
            'cod_cliente' => 'nullable|string|max:50',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'distancia' => 'nullable|integer',
        ], [
            'obra.required' => 'El nombre de la obra es obligatorio.',
            'obra.string' => 'El nombre de la obra debe ser un texto.',
            'obra.max' => 'El nombre de la obra no puede tener más de 255 caracteres.',

            'cod_obra.required' => 'El código de obra es obligatorio.',
            'cod_obra.string' => 'El código de obra debe ser un texto.',
            'cod_obra.max' => 'El código de obra no puede tener más de 50 caracteres.',
            'cod_obra.unique' => 'El código de obra ya está en uso.',

            'cliente.required' => 'El nombre del cliente es obligatorio.',
            'cliente.string' => 'El nombre del cliente debe ser un texto.',
            'cliente.max' => 'El nombre del cliente no puede tener más de 255 caracteres.',

            'cod_cliente.string' => 'El código del cliente debe ser un texto.',
            'cod_cliente.max' => 'El código del cliente no puede tener más de 50 caracteres.',

            'latitud.numeric' => 'La latitud debe ser un valor numérico.',
            'longitud.numeric' => 'La longitud debe ser un valor numérico.',

            'distancia.integer' => 'La distancia debe ser un número entero.',
        ]);


        Obra::create($request->all());
        return redirect()->route('obras.index')->with('success', 'Obra creada correctamente.');
    }

    public function edit(Obra $obra)
    {
        return view('obras.edit', compact('obra'));
    }

    public function update(Request $request, Obra $obra)
    {
        // Modificar el request para cambiar comas por puntos antes de validar
        $request->merge([
            'latitud' => $request->has('latitud') ? str_replace(',', '.', $request->latitud) : null,
            'longitud' => $request->has('longitud') ? str_replace(',', '.', $request->longitud) : null,
        ]);

        // Validar los datos con los valores corregidos
        $request->validate([
            'obra' => 'required|string|max:255',
            'cod_obra' => 'required|string|max:50',
            'cliente' => 'required|string|max:255',
            'cod_cliente' => 'nullable|string|max:50',
            'distancia' => 'nullable|integer',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
        ], [
            'obra.required' => 'El nombre de la obra es obligatorio.',
            'obra.string' => 'El nombre de la obra debe ser un texto.',
            'obra.max' => 'El nombre de la obra no puede tener más de 255 caracteres.',

            'cod_obra.required' => 'El código de obra es obligatorio.',
            'cod_obra.string' => 'El código de obra debe ser un texto.',
            'cod_obra.max' => 'El código de obra no puede tener más de 50 caracteres.',

            'cliente.required' => 'El nombre del cliente es obligatorio.',
            'cliente.string' => 'El nombre del cliente debe ser un texto.',
            'cliente.max' => 'El nombre del cliente no puede tener más de 255 caracteres.',

            'cod_cliente.string' => 'El código del cliente debe ser un texto.',
            'cod_cliente.max' => 'El código del cliente no puede tener más de 50 caracteres.',
            'distancia.integer' => 'La distancia debe ser un número entero.',
            'latitud.numeric' => 'La latitud debe ser un número.',
            'longitud.numeric' => 'La longitud debe ser un número.',
        ]);

        // Actualizar la obra con los datos validados
        $obra->update($request->all());

        return response()->json(['success' => true, 'message' => 'Obra actualizada correctamente.', 'obra' => $obra]);
    }



    public function destroy(Obra $obra)
    {
        $obra->delete();
        return redirect()->route('obras.index')->with('success', 'Obra eliminada correctamente.');
    }
}
