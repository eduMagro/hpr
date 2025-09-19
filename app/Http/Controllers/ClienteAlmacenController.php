<?php

namespace App\Http\Controllers;

use App\Models\ClienteAlmacen;

use Illuminate\Http\Request;

class ClienteAlmacenController extends Controller
{
    public function index()
    {
        $clientes = ClienteAlmacen::orderBy('nombre')->paginate(20);
        return view('clientes-almacen.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes-almacen.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'cif' => 'nullable|string|max:20',
        ]);

        ClienteAlmacen::create($request->only('nombre', 'cif'));

        return redirect()->route('clientes-almacen.index')->with('success', 'Cliente creado correctamente.');
    }

    public function show(ClienteAlmacen $cliente)
    {
        //return view('clientes-almacen.show', compact('cliente'));
    }

    public function buscar(Request $request)
    {
        $query = $request->get('query');
        log::info("Busqueda de cliente almacen");
        if (!$query) {
            return response()->json([]);
        }

        $clientes = ClienteAlmacen::query()
            ->where('nombre', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['id', 'nombre']);

        return response()->json($clientes);
    }


    public function edit(ClienteAlmacen $cliente)
    {
        return view('clientes-almacen.edit', compact('cliente'));
    }

    public function update(Request $request, ClienteAlmacen $cliente)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'cif' => 'nullable|string|max:20',
        ]);

        $cliente->update($request->only('nombre', 'cif'));

        return redirect()->route('clientes-almacen.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(ClienteAlmacen $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes-almacen.index')->with('success', 'Cliente eliminado.');
    }
}
