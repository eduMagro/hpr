<?php

namespace App\Http\Controllers;

use App\Models\ProductoBase;
use App\Models\Fabricante;
use Illuminate\Http\Request;

class ProductoBaseController extends Controller
{
    public function index()
    {
        $productos = ProductoBase::with('fabricante')->orderBy('tipo')->orderBy('diametro')->orderBy('longitud')->get();
        return view('productos_base.index', compact('productos'));
    }

    public function create()
    {
        $fabricantes = Fabricante::all();
        return view('productos_base.create', compact('fabricantes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fabricante_id' => 'nullable|exists:fabricantes,id',
            'tipo' => 'required|in:barra,encarretado',
            'diametro' => 'required|integer|min:1',
            'longitud' => 'nullable|integer|min:1',
            'descripcion' => 'nullable|string',
        ]);

        ProductoBase::create($request->all());

        return redirect()->route('productos-base.index')->with('success', 'Producto creado correctamente.');
    }

    public function show(ProductoBase $productoBase)
    {
        return view('productos_base.show', compact('productoBase'));
    }

    public function edit(ProductoBase $productoBase)
    {
        $fabricantes = Fabricante::all();
        return view('productos_base.edit', compact('productoBase', 'fabricantes'));
    }

    public function update(Request $request, ProductoBase $productoBase)
    {
        $request->validate([
            'fabricante_id' => 'nullable|exists:fabricantes,id',
            'tipo' => 'required|in:barra,encarretado',
            'diametro' => 'required|integer|min:1',
            'longitud' => 'nullable|integer|min:1',
            'descripcion' => 'nullable|string',
        ]);

        $productoBase->update($request->all());

        return redirect()->route('productos-base.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(ProductoBase $productoBase)
    {
        $productoBase->delete();

        return redirect()->route('productos-base.index')->with('success', 'Producto eliminado correctamente.');
    }
}
