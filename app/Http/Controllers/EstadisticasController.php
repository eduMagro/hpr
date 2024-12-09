<?php

namespace App\Http\Controllers;
use App\Models\Producto;  // O el modelo de tu base de datos
use Illuminate\Http\Request;

class EstadisticasController extends Controller
{

    public function index()
    {
       
        // Obtener los productos por estado
        $productosPorEstado = Producto::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->get();
    

        // Pasar los datos a la vista
        return view('estadisticas.index', compact('productosPorEstado'));
    }
    
}
