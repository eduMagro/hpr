<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Producto;  // Asegúrate de importar Producto
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntradaController extends Controller
{
    // Mostrar el formulario de creación
    public function create()
    {
        $ubicaciones = Ubicacion::all();
        $usuarios = User::all();

        // Definir los nombres de los productos en el controlador
        $nombre_productos = [
            'Corrugado',
            'Tipo 2',
            'Tipo 3',
        ];

        return view('entradas.create', compact('ubicaciones', 'usuarios', 'nombre_productos'));
    }

    // Registrar una nueva entrada (y crear un producto)
    public function store(Request $request)
    {
        try {
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'fabricante' => 'required|string|max:255',
                'producto_nombre' => 'required|array|min:1',
                'producto_nombre.*' => 'required|string|max:255',
                'codigo_barras' => 'required|array|min:1',
                'codigo_barras.*' => 'required|string|max:255',
                'diametro' => 'nullable|array',
                'diametro.*' => 'nullable|string|max:255',
                'longitud' => 'nullable|array',
                'longitud.*' => 'nullable|numeric|min:0',
                'peso' => 'nullable|array',
                'peso.*' => 'nullable|numeric|min:0',
                'albaran' => 'nullable|string|max:255',
                'n_colada' => 'nullable|string|max:255',
                'n_paquete' => 'nullable|string|max:255',
                'ubicacion_id' => 'required|exists:ubicaciones,id',
                'otros' => 'nullable|string|max:255',
            ]);
    
            // Crear la entrada (albarán)
            $entrada = Entrada::create([
                'albaran' => $request->albaran,
                'descripcion_material' => 'Descripción general de la entrada',
                'ubicacion_id' => $request->ubicacion_id,
                'user_id' => Auth::id(), // Usuario logueado
                'otros' => $request->otros,
            ]);
    
            // Iterar sobre los productos y crearlos
            foreach ($request->producto_nombre as $index => $nombre) {
                // Dividir el código de barras en segmentos
                $segmentos = explode('?', $request->codigo_barras[$index]);
    
                if (count($segmentos) !== 7) {
                    return redirect()->back()->with('error', 'El código de barras del fabricante no tiene el formato esperado para el producto ' . ($index + 1));
                }
    
                // Extraer datos del código de barras
                $n_colada = $segmentos[1] ?? null;
                $n_paquete = $segmentos[2] ?? null;
                $diametro = isset($segmentos[4]) ? str_replace('D', '', $segmentos[4]) : null;
                $longitud = isset($segmentos[5]) ? str_replace('L', '', $segmentos[5]) : null;
    
                // Generar el código de barras final
                $qr = implode('_', [
                    "F" . $request->fabricante,
                    "N" . $nombre,
                    "D" . ($diametro ?? 'ND'),
                    "L" . ($longitud ?? 'NL'),
                    "P" . ($request->peso[$index] ?? 0) / 100,
                    "C" . $n_colada,
                    "P" . $n_paquete,
                    now()->timestamp,
                ]);
    
                // Verificar si el material ya existe
                $material = Producto::where('descripcion', $qr)->first();
                if ($material) {
                    return redirect()->route('materiales.show')->with('error', 'El material con código de barras ' . $qr . ' ya existe.');
                }
    
                // Crear el producto
                $producto = Producto::create([
                    'nombre' => $nombre,
                    'descripcion' => $qr,
                    'ubicacion_id' => $entrada->ubicacion_id, // Usamos la misma ubicación que la entrada
                    'peso' => $request->peso[$index] ?? null,
                    'otros' => $request->otros,
                ]);
    
                // Asociar el producto a la entrada
                $entrada->productos()->attach($producto->id); // Si la relación entre Entrada y Producto es muchos a muchos
            }
    
            // Redirigir a la lista de entradas con un mensaje de éxito
            return redirect()->route('entradas.index')->with('success', 'Entrada registrada correctamente con ' . count($request->producto_nombre) . ' productos.');
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            // Otros errores
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }
    

    // Mostrar todas las entradas
    public function index()
    {
        // Obtener las entradas paginadas con sus relaciones (ubicación, usuario y producto)
        $entradas = Entrada::with(['ubicacion', 'user', 'producto'])  // Cargamos las relaciones necesarias
                    ->orderBy('created_at', 'desc')  // Opcional: ordenar por fecha de creación
                    ->paginate(10);  // 10 entradas por página
    
        // Devolver la vista con las entradas
        return view('entradas.index', compact('entradas'));
    }
    
}
