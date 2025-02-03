<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subpaquete;

class SubpaqueteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'elemento_id' => 'required|exists:elementos,id',
            'peso' => 'nullable|numeric',
            'cantidad' => 'nullable|integer|min:1',
            'descripcion' => 'nullable|string',
        ]);

        Subpaquete::create($request->all());

        return back()->with('success', 'Subpaquete creado correctamente.');
    }
}
