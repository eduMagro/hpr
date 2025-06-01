<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Models\ProductoCodigo;
use App\Models\ProductoBase;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductoController extends Controller
{
    //------------------------------------------------------------------------------------ FILTROS
    public function aplicarFiltros($query, Request $request)
    {
        // Aplicar filtros si están presentes en la solicitud
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        if ($request->filled('codigo')) {
            $query->where('codigo', $request->codigo);
        }
        if ($request->filled('fabricante')) {
            $query->where('fabricante', $request->fabricante);
        }
        if ($request->filled('tipo')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                $q->where('tipo', $request->tipo);
            });
        }

        if ($request->filled('diametro')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                $q->where('diametro', $request->diametro);
            });
        }

        if ($request->filled('longitud')) {
            $query->whereHas('productoBase', function ($q) use ($request) {
                $q->where('longitud', $request->longitud);
            });
        }

        if ($request->filled('n_colada')) {
            $query->where('n_colada', $request->n_colada);
        }
        if ($request->filled('n_paquete')) {
            $query->where('n_paquete', $request->n_paquete);
        }
        if ($request->filled('estado')) {
            $query->where('estado', 'LIKE', '%' . $request->estado . '%');
        }

        return $query;
    }


    //------------------------------------------------------------------------------------ INDEX
    public function index(Request $request)
    {
        // Inicializa la consulta de productos dados de alta (paquetes)
        $query = Producto::with('productoBase', 'ubicacion', 'maquina');

        // Aplica los filtros si tienes una función para eso
        $query = $this->aplicarFiltros($query, $request);

        // Orden dinámico
        $sortBy = $request->input('sort_by', 'id');
        $order = $request->input('order', 'asc');
        $query->orderBy($sortBy, $order);




        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosProductos = $query->paginate($perPage)->appends($request->except('page'));

        // Obtener el catálogo de productos base
        $productosBase = ProductoBase::orderBy('tipo')->orderBy('diametro')->orderBy('longitud')->get();


        // Devolver ambos conjuntos a la vista
        return view('productos.index', compact(
            'registrosProductos',
            'productosBase'
        ));
    }

    public function generarYExportar(Request $request)
    {
        $cantidad = intval($request->input('cantidad', 1));
        $anio = now()->format('y');
        $tipo = 'MP'; // fijo

        DB::beginTransaction();

        try {
            // Obtener o crear el contador del tipo y año
            $contador = ProductoCodigo::lockForUpdate()->firstOrCreate(
                ['tipo' => $tipo, 'anio' => $anio],
                ['ultimo_numero' => 0]
            );

            $desde = $contador->ultimo_numero + 1;
            $hasta = $contador->ultimo_numero + $cantidad;

            // Generar los códigos en memoria
            $nuevosCodigos = [];
            for ($i = $desde; $i <= $hasta; $i++) {
                $numero = str_pad($i, 4, '0', STR_PAD_LEFT);
                $codigo = "$tipo$anio$numero";
                $nuevosCodigos[] = (object)['codigo' => $codigo];
            }

            // Actualizar el contador
            $contador->ultimo_numero = $hasta;
            $contador->save();

            DB::commit();
            $fecha = now()->format('Ymd_His'); // Ej: 20250529_211523
            return Excel::download(new ProductosExport(collect($nuevosCodigos)), "codigos_MP_$fecha.xlsx");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al generar los códigos: ' . $e->getMessage());
        }
    }
    //------------------------------------------------------------------------------------ SHOW
    public function show($id)
    {
        $detalles_producto = Producto::findOrFail($id);
        return view('productos.show', compact('detalles_producto'));
    }

    //------------------------------------------------------------------------------------ CREATE
    public function create()
    {
        return view('productos.create');
    }

    //------------------------------------------------------------------------------------ STORE
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        Producto::create($request->all());

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    //------------------------------------------------------------------------------------ EDIT
    public function edit(Producto $producto)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('productos.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $ubicaciones = Ubicacion::all();
        $usuarios = User::all();
        $productosBase = ProductoBase::orderBy('tipo')->orderBy('diametro')->orderBy('longitud')->get();
        $proveedores = Proveedor::orderBy('nombre')->get();
        return view('productos.edit', compact('producto', 'usuarios', 'productosBase', 'proveedores'));
    }

    public function update(Request $request, Producto $producto)
    {
        DB::beginTransaction();

        try {
            // Mensajes personalizados
            $messages = [
                'fabricante.in' => 'El fabricante debe ser MEGASA, GETAFE, SIDERURGICA SEVILLANA o NERVADUCTIL.',
                'nombre.string' => 'El nombre debe ser una cadena de texto.',
                'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
                'tipo.in' => 'El tipo debe ser "ENCARRETADO" o "BARRA".',
                'diametro.in' => 'El diámetro debe ser 8, 10, 12, 16, 20, 25 o 32.',
                'longitud.in' => 'La longitud debe ser 6, 12, 14, 15 o 16.',
                'n_colada.string' => 'El número de colada debe ser una cadena de texto.',
                'n_colada.max' => 'El número de colada no puede tener más de 255 caracteres.',
                'n_paquete.string' => 'El número de paquete debe ser una cadena de texto.',
                'n_paquete.max' => 'El número de paquete no puede tener más de 255 caracteres.',
                'peso_inicial.required' => 'El peso inicial es obligatorio.',
                'peso_inicial.numeric' => 'El peso inicial debe ser un número decimal.',
                'peso_stock.required' => 'El peso en stock es obligatorio.',
                'peso_stock.numeric' => 'El peso en stock debe ser un número decimal.',
                'ubicacion_id.integer' => 'La ubicación debe ser un identificador válido.',
                'ubicacion_id.exists' => 'La ubicación seleccionada no es válida.',
                'maquina_id.integer' => 'La máquina debe ser un identificador válido.',
                'maquina_id.exists' => 'La máquina seleccionada no es válida.',
                'estado.string' => 'El estado debe ser una cadena de texto.',
                'estado.max' => 'El estado no puede tener más de 50 caracteres.',
                'otros.string' => 'El campo "Otros" debe ser una cadena de texto.',
            ];

            // Validación
            $validator = Validator::make($request->all(), [
                'fabricante'    => 'required|in:MEGASA,GETAFE,SIDERURGICA SEVILLANA,NERVADUCTIL',
                'nombre'        => 'nullable|string|max:255',
                'tipo'          => 'required|in:ENCARRETADO,BARRA',
                'diametro'      => 'required|in:8,10,12,16,20,25,32',
                'longitud'      => 'nullable|in:6,12,14,15,16',
                'n_colada'      => 'required|string|max:255',
                'n_paquete'     => 'required|string|max:255',
                'peso_inicial'  => 'required|numeric|between:0,9999999.99',
                'peso_stock'    => 'required|numeric|between:0,9999999.99',
                'ubicacion_id'  => 'nullable|integer|exists:ubicaciones,id',
                'maquina_id'    => 'nullable|integer|exists:maquinas,id',
                'estado'        => 'nullable|string|max:50',
                'otros'         => 'nullable|string',
            ], $messages);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = $validator->validated();

            // Normaliza algunos campos si quieres asegurar consistencia en la base de datos
            $data['fabricante'] = strtoupper($data['fabricante']);
            $data['tipo'] = strtoupper($data['tipo']);
            $data['estado'] = strtoupper($data['estado'] ?? '');

            $producto->update($data);

            DB::commit();
            return redirect()->route('productos.index')->with('success', 'Producto actualizado con éxito.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar el producto.')->withInput();
        }
    }

    public function solicitarStock(Request $request)
    {
        // Aquí puedes obtener el diámetro o cualquier otro dato necesario de la solicitud
        $diametro = $request->input('diametro');

        // Lógica para manejar la solicitud (por ejemplo, registrar la solicitud, enviar un correo, etc.)
        // Ejemplo: registrar la solicitud
        // Solicitud::create(['diametro' => $diametro, 'estado' => 'pendiente']);

        // Redirigir con un mensaje de éxito
        return redirect()->back()->with('exito', 'La solicitud ha sido registrada exitosamente.');
    }

    public function consumir($id)
    {
        // 1. Buscar el producto
        $producto = Producto::findOrFail($id);

        // 2. Actualizar estado y limpiar ubicaciones
        $producto->estado        = 'consumido';
        $producto->ubicacion_id  = null;
        $producto->maquina_id    = null;

        // 3. Guardar cambios
        $producto->save();

        // 4. Regresar o redirigir con un mensaje (opcional)
        return redirect()->back()->with('success', 'Producto marcado como consumido');
    }

    //------------------------------------------------------------------------------------ DESTROY
    public function destroy(Producto $producto)
    {
        if (auth()->user()->rol !== 'oficina') {
            return redirect()->route('productos.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
