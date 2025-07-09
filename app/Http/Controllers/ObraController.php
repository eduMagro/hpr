<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obra;
use Illuminate\Support\Facades\Validator;

class ObraController extends Controller
{
    // public function index(Request $request)
    // {
    //     $query = Obra::query();

    //     if ($request->filled('buscar')) {
    //         $query->where(function ($q) use ($request) {
    //             $q->where('obra', 'like', '%' . $request->buscar . '%')
    //                 ->orWhere('cod_obra', 'like', '%' . $request->buscar . '%')
    //                 ->orWhere('cliente', 'like', '%' . $request->buscar . '%')
    //                 ->orWhere('cod_cliente', 'like', '%' . $request->buscar . '%');
    //         });
    //     }

    //     if ($request->filled('cod_obra')) {
    //         $query->where('cod_obra', 'like', '%' . $request->cod_obra . '%');
    //     }

    //     if ($request->filled('cliente')) {
    //         $query->where('cliente', 'like', '%' . $request->cliente . '%');
    //     }

    //     if ($request->filled('cod_cliente')) {
    //         $query->where('cod_cliente', 'like', '%' . $request->cod_cliente . '%');
    //     }

    //     if ($request->filled('completada')) {
    //         $query->where('completada', $request->completada);
    //     }

    //     if ($request->filled('sort_by') && in_array($request->sort_by, ['created_at', 'cod_obra', 'cliente', 'cod_cliente'])) {
    //         $order = $request->order === 'desc' ? 'desc' : 'asc';
    //         $query->orderBy($request->sort_by, $order);
    //     }

    //     $obras = $query->paginate($request->get('per_page', 10));

    //     return view('obras.index', compact('obras'));
    // }

    public function show(Obra $obra)
    {
        // Cargar la obra con sus planillas asociadas
        $planillas = $obra->planillas()->paginate(10); // Paginación de 10 planillas por página

        return view('obras.show', compact('obra', 'planillas'));
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
            'cliente_id' => 'required|exists:clientes,id', // Validamos que el cliente exista
            'ciudad' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'distancia' => 'nullable|integer',
            'tipo' => 'required|in:montaje,suministro',
        ], [
            'obra.required' => 'El nombre de la obra es obligatorio.',
            'obra.string' => 'El nombre de la obra debe ser un texto.',
            'obra.max' => 'El nombre de la obra no puede tener más de 255 caracteres.',

            'cod_obra.required' => 'El código de obra es obligatorio.',
            'cod_obra.string' => 'El código de obra debe ser un texto.',
            'cod_obra.max' => 'El código de obra no puede tener más de 50 caracteres.',
            'cod_obra.unique' => 'El código de obra ya está en uso.',

            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',

            'ciudad.string' => 'La ciudad debe ser un texto.',
            'ciudad.max' => 'La ciudad no puede tener más de 255 caracteres.',

            'direccion.string' => 'La dirección debe ser un texto.',
            'direccion.max' => 'La dirección no puede tener más de 255 caracteres.',

            'latitud.numeric' => 'La latitud debe ser un valor numérico.',
            'longitud.numeric' => 'La longitud debe ser un valor numérico.',

            'distancia.integer' => 'La distancia debe ser un número entero.',
            'tipo.required' => 'El tipo de obra es obligatorio.',
            'tipo.in' => 'El tipo de obra debe ser montaje o suministro.',

        ]);

        // Crear la obra con cliente_id desde el input oculto y completada por defecto en 0
        Obra::create([
            'obra' => $request->obra,
            'cod_obra' => $request->cod_obra,
            'cliente_id' => $request->cliente_id, // Se obtiene del input hidden en el formulario
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
            'distancia' => $request->distancia,
            'estado' => 'activa', // Siempre será 0 por defecto
            'tipo' => $request->tipo,
        ]);

        return redirect()->route('clientes.show', $request->cliente_id)->with('success', 'Obra creada correctamente.');
    }

    public function edit(Obra $obra)
    {
        return view('obras.edit', compact('obra'));
    }

    public function update(Request $request, Obra $obra)
    {
        $rules = [
            'obra' => 'required|string|max:255',
            'cod_obra' => 'required|string|max:50',
            'distancia' => 'nullable|integer',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'tipo' => 'required|in:montaje,suministro',
        ];

        $messages = [
            'obra.required' => 'El nombre de la obra es obligatorio.',
            'obra.string' => 'El nombre de la obra debe ser un texto.',
            'obra.max' => 'El nombre de la obra no puede tener más de 255 caracteres.',

            'cod_obra.required' => 'El código de obra es obligatorio.',
            'cod_obra.string' => 'El código de obra debe ser un texto.',
            'cod_obra.max' => 'El código de obra no puede tener más de 50 caracteres.',

            'distancia.integer' => 'La distancia debe ser un número entero.',
            'latitud.numeric' => 'La latitud debe ser un número.',
            'longitud.numeric' => 'La longitud debe ser un número.',

            'tipo.required' => 'El tipo de obra es obligatorio.',
            'tipo.in' => 'El tipo de obra debe ser montaje o suministro.',
        ];

        $input = $request->all();
        if (isset($input['latitud'])) {
            $input['latitud'] = str_replace(',', '.', $input['latitud']);
        }
        if (isset($input['longitud'])) {
            $input['longitud'] = str_replace(',', '.', $input['longitud']);
        }

        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $obra->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Obra actualizada correctamente.',
            'obra' => $obra
        ]);
    }
    public function updateTipo(Request $request)
    {
        $request->validate([
            'obra_id' => 'required|exists:obras,id',
            'tipo' => 'required|string|in:obra,montaje,mantenimiento',
        ]);

        $obra = Obra::findOrFail($request->obra_id);
        $obra->tipo = $request->tipo;
        $obra->save();

        return back()->with('success', '✅ Tipo de obra actualizado correctamente.');
    }

    public function destroy(Obra $obra)
    {
        $obra->delete();
        return redirect()->back()->with('success', 'Obra eliminada correctamente.');
    }
}
