<?php
namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Producto;  // Añadimos el modelo Producto
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntradaController extends Controller
{
    // Mostrar el formulario de creación
    public function create()
    {
        $ubicaciones = Ubicacion::all();
        $usuarios = User::all();

        return view('entradas.create', compact('ubicaciones', 'usuarios'));
    }

    // Registrar una nueva entrada (y crear un producto)
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $request->validate([
            'nombre_material' => 'required|string|max:255',
            'descripcion_material' => 'nullable|string',
            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'producto_nombre' => 'required|string|max:255',  // Nombre del producto
            'producto_descripcion' => 'nullable|string',     // Descripción del producto
            'producto_precio' => 'required|numeric',         // Precio del producto
            'producto_stock' => 'required|integer',          // Stock del producto
        ]);

        // Crear el producto asociado a la entrada
        $producto = Producto::create([
            'nombre' => $request->producto_nombre,
            'descripcion' => $request->producto_descripcion,
            'precio' => $request->producto_precio,
            'stock' => $request->producto_stock,
        ]);

        // Crear la entrada
        $entrada = Entrada::create([
            'nombre_material' => $request->nombre_material,
            'descripcion_material' => $request->descripcion_material,
            'ubicacion_id' => $request->ubicacion_id,
            'user_id' => Auth::id(), // Usuario logueado
            'cantidad' => $request->cantidad,
            'producto_id' => $producto->id, // Asociamos el producto a la entrada
        ]);

        // Redirigir a la lista de entradas con un mensaje de éxito
        return redirect()->route('entradas.index')->with('success', 'Entrada y Producto registrados exitosamente.');
    }

    // Mostrar todas las entradas
    public function index()
    {
        // Obtener las entradas paginadas con sus relaciones (ubicación y usuario)
        $entradas = Entrada::with(['ubicacion', 'user', 'producto'])->paginate(10);  // 10 entradas por página
    
        return view('entradas.index', compact('entradas'));
    }
}
