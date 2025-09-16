<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use App\Models\Obra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Exports\InventarioComparadoExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class UbicacionController extends Controller
{


    //------------------------------------------------------------------------------------ INDEX()
    public function index(Request $request)
    {
        try {
            // 1) Obtener todas las ubicaciones con productos y paquetes
            $ubicaciones = Ubicacion::with(['productos', 'paquetes'])
                ->where('almacen', '0A')
                ->orderBy('sector', 'desc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            // 2) Agrupar por sector
            $ubicacionesPorSector = $ubicaciones->groupBy('sector');

            // 3) Obtener todas las obras del cliente "Hierros Paco Reyes"
            $obras = Obra::with('cliente')
                ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
                ->orderBy('obra')
                ->get();

            // 4) Obtener obra activa desde el query (?obra=ID) o la primera
            $obraActualId = request('obra');
            $obraActiva   = $obras->firstWhere('id', $obraActualId) ?? $obras->first();
            $cliente      = $obraActiva?->cliente;

            // 5) Pasar todos los datos necesarios a la vista
            return view('ubicaciones.index', [
                'ubicacionesPorSector' => $ubicacionesPorSector,
                'obras' => $obras,
                'obraActualId' => $obraActiva?->id,
                'nombreAlmacen' => $obraActiva?->obra,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }
    public function naveA(Request $request)
    {
        try {
            $obra = Obra::where('obra', 'Nave A')->firstOrFail();

            $ubicaciones = Ubicacion::with(['productos', 'paquetes'])
                ->where('almacen', '0A')
                ->orderBy('sector', 'desc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            $ubicacionesPorSector = $ubicaciones->groupBy('sector');

            $obras = Obra::with('cliente')
                ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
                ->orderBy('obra')
                ->get();

            return view('ubicaciones.nave-a', [
                'ubicacionesPorSector' => $ubicacionesPorSector,
                'obras' => $obras,
                'obraActualId' => $obra->id,
                'nombreAlmacen' => $obra?->obra,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error en Nave A: ' . $e->getMessage());
        }
    }
    public function naveB(Request $request)
    {
        try {
            $obra = Obra::where('obra', 'Nave B')->firstOrFail();

            $ubicaciones = Ubicacion::with(['productos', 'paquetes'])
                ->where('almacen', '0B')
                ->orderBy('sector', 'desc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            $ubicacionesPorSector = $ubicaciones->groupBy('sector');

            $obras = Obra::with('cliente')
                ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
                ->orderBy('obra')
                ->get();

            return view('ubicaciones.nave-b', [
                'ubicacionesPorSector' => $ubicacionesPorSector,
                'obras' => $obras,
                'obraActualId' => $obra->id,
                'nombreAlmacen' => $obra?->obra,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error en Nave B: ' . $e->getMessage());
        }
    }
    public function almacen(Request $request)
    {
        try {
            $obra = Obra::where('obra', 'Almacén')->firstOrFail();

            $ubicaciones = Ubicacion::with(['productos', 'paquetes'])
                ->where('almacen', 'AL')
                ->orderBy('sector', 'desc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            $ubicacionesPorSector = $ubicaciones->groupBy('sector');

            $obras = Obra::with('cliente')
                ->whereHas('cliente', fn($q) => $q->where('empresa', 'LIKE', '%hierros paco reyes%'))
                ->orderBy('obra')
                ->get();

            return view('ubicaciones.almacen', [
                'ubicacionesPorSector' => $ubicacionesPorSector,
                'obras' => $obras,
                'obraActualId' => $obra->id,
                'nombreAlmacen' => $obra?->obra,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error en Almacén: ' . $e->getMessage());
        }
    }

    public function inventario(Request $request)
    {
        try {
            // 1. Recoger el ID de la obra desde la URL
            $obraId = $request->input('almacen'); // sigue llamándose 'almacen' en la URL, pero es el ID de la obra

            // 2. Buscar la obra
            $obra = Obra::with('cliente')->find($obraId);
            if (!$obra) {
                return redirect()->back()->with('error', 'Obra no encontrada.');
            }

            // 3. Comprobación de seguridad
            if (!Str::contains(Str::lower($obra->cliente->empresa), 'hierros paco reyes')) {
                return redirect()->back()->with('error', 'Acceso no autorizado a esta obra.');
            }

            // 4. Traducir el nombre de la obra a código de almacén
            $almacen = match (Str::lower($obra->obra)) {
                'nave a'   => '0A',
                'nave b'   => '0B',
                'almacén'  => 'AL',
                default    => null,
            };

            if (!$almacen) {
                return redirect()->back()->with('error', 'No se pudo determinar el almacén asociado a la obra.');
            }

            // 5. Obtener ubicaciones de ese almacén
            $ubicaciones = Ubicacion::with(['productos.productoBase', 'paquetes'])
                ->where('almacen', $almacen)
                ->orderBy('sector', 'desc')
                ->orderBy('ubicacion', 'asc')
                ->get();

            $ubicacionesPorSector = $ubicaciones->groupBy('sector');

            // 6. Obtener todas las obras válidas
            $obras = Obra::with('cliente')
                ->whereHas('cliente', fn($q) => $q->where('empresa', 'like', '%hierros paco reyes%'))
                ->orderBy('obra')
                ->get();

            // 7. Mostrar vista
            return view('ubicaciones.inventario', [
                'ubicacionesPorSector' => $ubicacionesPorSector,
                'almacen'              => $almacen,
                'obraActualId'         => $obra->id,
                'obras'                => $obras,
            ]);
        } catch (Exception $e) {
            Log::error('Error en inventario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }


    public function comparar()
    {
        $ubicaciones = Ubicacion::with('productos')->get();

        $esperadosPorSector = $ubicaciones->groupBy('sector')->map(function ($grupo) {
            return $grupo->mapWithKeys(function ($ubicacion) {
                return [
                    $ubicacion->id => $ubicacion->productos->pluck('codigo')->toArray(),
                ];
            });
        });

        $nombresUbicaciones = $ubicaciones->pluck('nombre', 'id')->toArray();

        return view('ubicaciones.compararInventario', [
            'esperadosPorSector' => $esperadosPorSector,
            'nombresUbicaciones' => $nombresUbicaciones,
        ]);
    }


    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        return view('ubicaciones.create');
    }
    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validación de los datos del formulario
            $request->validate([
                'almacen'     => 'required|string|max:2',
                'sector'      => 'required|string|max:2',
                'ubicacion'   => 'required|string|max:2',
                'descripcion' => 'nullable|string|max:255',
            ], [
                'almacen.required'   => 'El campo "almacén" es obligatorio.',
                'almacen.string'     => 'El campo "almacén" debe ser una cadena de texto.',
                'almacen.max'        => 'El campo "almacén" no puede tener más de 2 caracteres.',
                'sector.required'    => 'El campo "sector" es obligatorio.',
                'sector.string'      => 'El campo "sector" debe ser una cadena de texto.',
                'sector.max'         => 'El campo "sector" no puede tener más de 2 caracteres.',
                'ubicacion.required' => 'El campo "ubicación" es obligatorio.',
                'ubicacion.string'   => 'El campo "ubicación" debe ser una cadena de texto.',
                'ubicacion.max'      => 'El campo "ubicación" no puede tener más de 2 caracteres.',
                'descripcion.string' => 'El campo "descripción" debe ser una cadena de texto.',
                'descripcion.max'    => 'El campo "descripción" no puede tener más de 255 caracteres.',
            ]);

            // Generar código único
            $codigo = $request->almacen . $request->sector . $request->ubicacion;

            // Generar nombre legible
            $nombre = 'Almacén ' . $request->almacen . ', Sector ' . (int) $request->sector . ', Ubicación ' . (int) $request->ubicacion;
            if (!empty($request->descripcion)) {
                $nombre .= ', ' . $request->descripcion;
            }

            // Verificar duplicado
            if (Ubicacion::where('codigo', $codigo)->exists()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Esta ubicación ya existe.'])->withInput();
            }

            // Crear nueva ubicación
            Ubicacion::create([
                'codigo'      => $codigo,
                'nombre'      => $nombre,
                'almacen'     => $request->almacen,
                'sector'      => $request->sector,
                'ubicacion'   => $request->ubicacion,
                'descripcion' => $request->descripcion,
            ]);

            DB::commit();

            // 🔁 Redirigir según la nave (almacen)
            $ruta = match ($request->almacen) {
                '0A' => 'ubicaciones.verNave-a',
                '0B' => 'ubicaciones.verNave-b',
                'AL' => 'ubicaciones.verAlmacen',
                default => 'ubicaciones.index', // fallback general
            };

            return redirect()->route($ruta)->with('success', 'Ubicación creada con éxito.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ubicación: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Hubo un problema al guardar la ubicación.'])->withInput();
        }
    }

    //------------------------------------------------------------------------------------ SHOW()
    public function show($id)
    {
        $ubicacion = Ubicacion::with([
            'productos.fabricante',     // 👈 añade el fabricante
            'productos.productoBase',   // 👈 ya tenías esta
            'paquetes'                  // 👈 también los paquetes
        ])->findOrFail($id);

        return view('ubicaciones.show', compact('ubicacion'));
    }




    // Mostrar el formulario para editar una ubicación existente
    public function edit($id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        return view('ubicaciones.edit', compact('ubicacion'));
    }

    //------------------------------------------------------------------------------------ UPDATE()
    public function update(Request $request, $id)
    {
        try {

            $ubicacion = Ubicacion::findOrFail($id);

            // Validar los datos
            $request->validate([
                'codigo' => 'required|string|max:255|unique:ubicaciones,codigo,' . $ubicacion->id,
                'descripcion' => 'nullable|string',
            ]);

            // Actualizar la ubicación
            $ubicacion->update([
                'codigo' => $request->codigo,
                'descripcion' => $request->descripcion,
            ]);

            // Redirigir a la lista de ubicaciones con un mensaje de éxito
            return redirect()->route('ubicaciones.index')->with('success', 'Ubicación actualizada con éxito.');
        } catch (ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ DESTROY()
    public function destroy($id)
    {
        try {
            // Buscar la ubicación por ID
            $ubicacion = Ubicacion::findOrFail($id);

            // Verificar si la ubicación tiene productos asociados
            if ($ubicacion->productos()->count() > 0) {
                return redirect()->route('ubicaciones.index')->with('error', 'No se puede eliminar la ubicación porque tiene productos asociados.');
            }

            // Si no tiene productos, proceder a eliminarla
            $ubicacion->delete();

            // Redirigir con éxito
            return redirect()->route('ubicaciones.index')->with('success', 'Ubicación eliminada exitosamente.');
        } catch (ValidationException $e) {
            // Mostrar todos los errores de validación
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Mostrar errores generales
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }
}
