<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obra;
use App\Models\Cliente;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
        // Validación base
        $request->validate([
            'obra'       => 'required|string|max:255',
            'cod_obra'   => 'required|string|max:50|unique:obras,cod_obra',
            'cliente_id' => 'required|exists:clientes,id',
            'ciudad'     => 'nullable|string|max:255',
            'direccion'  => 'nullable|string|max:255',
            'latitud'    => 'nullable|numeric',
            'longitud'   => 'nullable|numeric',
            'distancia'  => 'nullable|integer',
            'presupuesto_estimado' => 'nullable|numeric|min:0',
            'tipo'       => 'required|in:montaje,suministro',
        ], [
            'obra.required' => 'El nombre de la obra es obligatorio.',
            'obra.string'   => 'El nombre de la obra debe ser un texto.',
            'obra.max'      => 'El nombre de la obra no puede tener más de 255 caracteres.',

            'cod_obra.required' => 'El código de obra es obligatorio.',
            'cod_obra.string'   => 'El código de obra debe ser un texto.',
            'cod_obra.max'      => 'El código de obra no puede tener más de 50 caracteres.',
            'cod_obra.unique'   => 'El código de obra ya está en uso.',

            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists'   => 'El cliente seleccionado no existe.',

            'ciudad.string'   => 'La ciudad debe ser un texto.',
            'ciudad.max'      => 'La ciudad no puede tener más de 255 caracteres.',
            'direccion.string' => 'La dirección debe ser un texto.',
            'direccion.max'   => 'La dirección no puede tener más de 255 caracteres.',

            'latitud.numeric'   => 'La latitud debe ser un valor numérico.',
            'longitud.numeric'  => 'La longitud debe ser un valor numérico.',
            'distancia.integer' => 'La distancia debe ser un número entero.',
            'tipo.required'     => 'El tipo de obra es obligatorio.',
            'tipo.in'           => 'El tipo de obra debe ser montaje o suministro.',
        ]);

        // Detectar si el cliente es "Hierros Paco Reyes" (LIKE %hierros paco reyes%)
        $cliente = Cliente::findOrFail($request->cliente_id);
        $esPacoReyes = Str::contains(Str::lower($cliente->empresa ?? ''), 'hierros paco reyes');

        // Normalizar comas → puntos
        $input = $request->all();
        foreach (['latitud', 'longitud', 'ancho_m', 'largo_m'] as $numField) {
            if (isset($input[$numField]) && $input[$numField] !== '') {
                $input[$numField] = str_replace(',', '.', $input[$numField]);
            }
        }

        // Validación condicional de ancho/largo para Paco Reyes
        if ($esPacoReyes) {
            $request->validate([
                'ancho_m' => 'nullable|numeric|min:0',
                'largo_m' => 'nullable|numeric|min:0',
            ], [
                'ancho_m.numeric' => 'El ancho debe ser numérico.',
                'largo_m.numeric' => 'El largo debe ser numérico.',
            ]);
        }

        // Datos a crear (solo incluimos ancho/largo si aplica)
        $data = [
            'obra'       => $input['obra'],
            'cod_obra'   => $input['cod_obra'],
            'cliente_id' => $input['cliente_id'],
            'ciudad'     => $input['ciudad'] ?? null,
            'direccion'  => $input['direccion'] ?? null,
            'latitud'    => $input['latitud'] ?? null,
            'longitud'   => $input['longitud'] ?? null,
            'distancia'  => $input['distancia'] ?? null,
            'estado'     => 'activa',
            'tipo'       => $input['tipo'],
        ];

        if ($esPacoReyes) {
            $data['ancho_m'] = $input['ancho_m'] ?? null;
            $data['largo_m'] = $input['largo_m'] ?? null;
        }

        Obra::create($data);

        return redirect()
            ->route('clientes.show', $request->cliente_id)
            ->with('success', 'Obra creada correctamente.');
    }


    public function edit(Obra $obra)
    {
        return view('obras.edit', compact('obra'));
    }

    public function update(Request $request, Obra $obra)
    {
        try {

            // Reglas base
            $rules = [
                'obra'      => 'required|string|max:255',
                'cod_obra'  => 'required|string|max:50|unique:obras,cod_obra,' . $obra->id,
                'distancia' => 'nullable|integer',
                'latitud'   => 'nullable|numeric',
                'longitud'  => 'nullable|numeric',
                'estado'    => 'nullable|string',
                'tipo'      => 'required|in:montaje,suministro',
                'ciudad'    => 'nullable|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'presupuesto_estimado' => 'nullable|numeric|min:0',
            ];

            $messages = [
                'obra.required' => 'El nombre de la obra es obligatorio.',
                'obra.string'   => 'El nombre de la obra debe ser un texto.',
                'obra.max'      => 'El nombre de la obra no puede tener más de 255 caracteres.',

                'cod_obra.required' => 'El código de obra es obligatorio.',
                'cod_obra.string'   => 'El código de obra debe ser un texto.',
                'cod_obra.max'      => 'El código de obra no puede tener más de 50 caracteres.',
                'cod_obra.unique'   => 'El código de obra ya está en uso.',

                'distancia.integer' => 'La distancia debe ser un número entero.',
                'latitud.numeric'   => 'La latitud debe ser un número.',
                'longitud.numeric'  => 'La longitud debe ser un número.',

                'ciudad.string'     => 'La ciudad debe ser un texto.',
                'ciudad.max'        => 'La ciudad no puede tener más de 255 caracteres.',
                'direccion.string'  => 'La dirección debe ser un texto.',
                'direccion.max'     => 'La dirección no puede tener más de 255 caracteres.',

                'tipo.required'     => 'El tipo de obra es obligatorio.',
                'tipo.in'           => 'El tipo de obra debe ser montaje o suministro.',
            ];

            // ¿El cliente de esta obra es Paco Reyes?
            $cliente = $obra->cliente ?? Cliente::find($obra->cliente_id);
            $esPacoReyes = Str::contains(Str::lower($cliente->empresa ?? ''), 'hierros paco reyes');

            // Normalizar comas → puntos
            $input = $request->all();
            foreach (['latitud', 'longitud', 'ancho_m', 'largo_m'] as $numField) {
                if (isset($input[$numField]) && $input[$numField] !== '') {
                    $input[$numField] = str_replace(',', '.', $input[$numField]);
                }
            }

            // Si es Paco Reyes, añadimos validación de ancho/largo
            if ($esPacoReyes) {
                $rules['ancho_m'] = 'nullable|numeric|min:0';
                $rules['largo_m'] = 'nullable|numeric|min:0';
                $messages['ancho_m.numeric'] = 'El ancho debe ser numérico.';
                $messages['largo_m.numeric'] = 'El largo debe ser numérico.';
            }

            $validator = Validator::make($input, $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Si NO es Paco Reyes, no permitimos actualizar ancho/largo aunque vengan en el request
            if (!$esPacoReyes) {
                unset($data['ancho_m'], $data['largo_m']);
            }

            $obra->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Obra actualizada correctamente.',
                'obra'    => $obra
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // 👈 aquí mostramos el error real
                'debug'   => class_basename($e) . ' en línea ' . $e->getLine(), // opcional
            ], 500);
        }
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

        return back();
    }

    public function destroy(Obra $obra)
    {
        $obra->delete();
        return redirect()->back()->with('success', 'Obra eliminada correctamente.');
    }
}
