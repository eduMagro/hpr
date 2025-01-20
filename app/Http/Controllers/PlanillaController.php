<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;

use App\Models\Elemento;
use App\Models\Maquina;
use App\Models\Etiqueta;
use Illuminate\Support\Facades\DB;
use App\Imports\PlanillaImport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Validation\ValidationException;

class PlanillaController extends Controller
{

    private function asignarMaquina($diametro, $longitud)
    {
        // Lógica para determinar la máquina
        if ($diametro >= 8 && $diametro <= 12) {
            $maquina = Maquina::where('codigo', 'MSR20')->first();
            return $maquina->id ?? null;
        } elseif ($diametro > 12 && $diametro <= 25 && $longitud <= 12000) {
            $maquina = Maquina::where('codigo', 'SL28')->first();
            return $maquina->id ?? null;
        }

        return null; // Retornar null si no hay máquina asignada
    }


    //------------------------------------------------------------------------------------ FILTROS
    private function aplicarFiltros($query, Request $request)
    {
        // 🔍 Búsqueda global en múltiples campos
        if ($request->has('buscar') && $request->buscar) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%$buscar%")
                    ->orWhere('cliente', 'like', "%$buscar%")
                    ->orWhere('nom_obra', 'like', "%$buscar%")
                    ->orWhere('cod_obra', 'like', "%$buscar%")
                    ->orWhereHas('user', function ($q) use ($buscar) {
                        $q->where('name', 'like', "%$buscar%");
                    });
            });
        }

        // 🔢 Filtros específicos
        $filters = [
            'codigo' => 'codigo',
            'cod_obra' => 'cod_obra',
            'nom_obra' => 'nom_obra',
            'cliente' => 'cliente',
            'ensamblado' => 'ensamblado',
        ];

        foreach ($filters as $requestKey => $column) {
            if ($request->has($requestKey) && $request->$requestKey) {
                $query->where($column, 'like', "%{$request->$requestKey}%");
            }
        }

        // 📅 Filtrado por rango de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_finalizacion') && $request->fecha_finalizacion) {
            $query->whereDate('created_at', '<=', $request->fecha_finalizacion);
        }

        // 🏗️ Filtrar por usuario
        if ($request->has('name') && $request->name) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name}%");
            });
        }

        return $query;
    }



    public function index(Request $request)
    {
        try {
            $query = Planilla::with(['user', 'elementos']);

            // Aplicar filtros
            $query = $this->aplicarFiltros($query, $request);

            // 📌 Ordenación segura
            $allowedSortColumns = ['created_at', 'codigo', 'cliente', 'nom_obra'];
            $sortBy = in_array($request->input('sort_by'), $allowedSortColumns) ? $request->input('sort_by') : 'created_at';
            $order = in_array($request->input('order'), ['asc', 'desc']) ? $request->input('order') : 'desc';

            $query->orderBy($sortBy, $order);

            // 📌 Paginación
            $perPage = $request->input('per_page', 10);
            $planillas = $query->paginate($perPage)->appends($request->except('page'));

            // Retornar vista con los datos
            return view('planillas.index', compact('planillas'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }



    public function show($id)
    {
        // Encuentra la planilla por ID
        $planilla = Planilla::findOrFail($id);

        // Obtiene los elementos relacionados y carga la relación con máquina
        $elementos = $planilla->elementos()->with('maquina')->get();

        // Retorna la vista con la planilla y sus elementos
        return view('planillas.elementos', compact('planilla', 'elementos'));
    }


    public function create()
    {
        return view('planillas.create');
    }


    public function import(Request $request)
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        // Validar el archivo
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.mimes' => 'El archivo debe tener uno de los siguientes formatos: xlsx o xls',
        ]);


        DB::beginTransaction(); // Iniciar la transacción

        try {
            $file = $request->file('file');

            // Convertir el archivo a un arreglo
            $importedData = Excel::toArray([], $file);

            // Procesar la primera hoja del archivo
            $firstSheet = $importedData[0] ?? [];

            if (empty($firstSheet)) {
                throw new Exception('El archivo está vacío o no contiene datos válidos.');
            }

            // Tomar los encabezados y las filas de datos
            $headers = $firstSheet[0] ?? [];
            $data = array_slice($firstSheet, 1);

            // Filtrar filas vacías
            $filteredData = array_filter($data, function ($row) {
                return array_filter($row); // Ignorar filas completamente vacías
            });

            if (empty($filteredData)) {
                throw new Exception('El archivo no contiene filas válidas después de las cabeceras.');
            }

            // Agrupar los datos por el código (columna K, índice 8)
            $groupedByCodigo = [];
            foreach ($filteredData as $row) {
                $codigo = $row[10] ?? 'Sin código';
                $groupedByCodigo[$codigo][] = $row;
            }

            foreach ($groupedByCodigo as $codigo => $rows) {
                // Sumar todos los pesos de las filas con este código
                $pesoTotal = array_reduce($rows, function ($carry, $row) {
                    return $carry + (float)($row[34] ?? 0);
                }, 0);

                // Crear el registro de planilla
                $planilla = Planilla::create([
                    'users_id' => auth()->id(),
                    'cod_obra' => $rows[0][2] ?? null,
                    'cliente' => $rows[0][1] ?? null,
                    'nom_obra' => $rows[0][3] ?? null,
                    'seccion' => $rows[0][7] ?? null,
                    'descripcion' => $rows[0][12] ?? null,
                    'ensamblado' => $rows[0][4] ?? null,
                    'codigo' => $codigo,
                    'peso_total' => $pesoTotal,
                    'fecha_inicio' => null,
                    'fecha_finalizacion' => null, // Actualizaremos más adelante
                    'tiempo_fabricacion' => 0, // Inicialmente en 0, lo actualizamos después
                ]);

                $tiempoEstimadoGlobalMinutos = 0;
                // Array para almacenar etiquetas ya registradas en esta ejecución
                $etiquetasRegistradas = [];
                foreach ($rows as $row) {
                    // Obtener diámetro y longitud
                    $diametro = $row[25] ?? 0;
                    $longitud = $row[27] ?? 0;

                    // Llamar al método asignarMaquina
                    $maquina_id = $this->asignarMaquina($diametro, $longitud);
                    $tiempos = $this->calcularTiemposElemento($row);

                    // Verificar si la etiqueta ya existe antes de crearla
                    $numeroEtiqueta = $row[30] ?? null;
                    // Verificar si ya registramos esta etiqueta en esta ejecución
                    if (!isset($etiquetasRegistradas[$numeroEtiqueta])) {
                        // Buscar en la base de datos si existe la etiqueta
                        $etiqueta = Etiqueta::create(
                            [
                                'numero_etiqueta' => $numeroEtiqueta,
                                'planilla_id' => $planilla->id,
                                'nombre' => $row[22] ?? 'Sin nombre'
                            ]
                        );

                        // Marcar la etiqueta como registrada para evitar consultas repetidas
                        $etiquetasRegistradas[$numeroEtiqueta] = $etiqueta->id;
                    }

                    // Crear el registro de elemento
                    $elemento = Elemento::create([
                        'planilla_id' => $planilla->id,
                        'etiqueta_id' => $etiqueta->id, // Relación con etiqueta

                        'maquina_id' => $maquina_id,
                        'figura' => $row[26],
                        'fila' => $row[21],

                        'marca' => $row[23],
                        'etiqueta' => $row[30],
                        'diametro' => $diametro,
                        'longitud' => $longitud,
                        'barras' => $row[32],
                        'dobles_barra' => $row[33] ?? 0,
                        'peso' => $row[34],
                        'dimensiones' => $row[47] ?? null,
                        'fecha_inicio' => $tiempos['fecha_inicio'],
                        'fecha_finalizacion' => $tiempos['fecha_finalizacion'],
                        'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                    ]);
                }

                // Actualizar el registro de planilla con el tiempo global
                $planilla->update([
                    'tiempo_fabricacion' => $tiempoEstimadoGlobalMinutos ?? 'No calculado',
                ]);
            }

            DB::commit(); // Confirmar la transacción

            return redirect()->route('planillas.index')->with('success', 'Planillas y elementos importados correctamente por código.');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return redirect()->route('planillas.index')->with('error', 'Hubo un problema al importar las planillas: ' . $e->getMessage());
        }
    }

    private function calcularTiemposElemento(array $row)
    {
        $barras = $row[32] ?? 0;
        $doblesBarra = $row[33] ?? 0;

        // Calcular el tiempo estimado para el elemento
        $tiempoFabricacion = ($doblesBarra > 0)
            ? ($barras * $doblesBarra * 1.5) // Cálculo para barras con dobles
            : ($barras * 2); // Cálculo para barras rectas

        // Calcular las fechas de inicio y finalización del elemento
        $fechaInicio = now(); // Puedes ajustar la lógica según tus necesidades
        $fechaFinalizacion = $fechaInicio->copy()->addMinutes($tiempoFabricacion);

        return [
            'fecha_inicio' => $fechaInicio,
            'fecha_finalizacion' => $fechaFinalizacion,
            'tiempo_fabricacion' => $tiempoFabricacion,
        ];
    }


    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'cod_obra' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'nom_obra' => 'required|string|max:255',
                'seccion' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'poblacion' => 'required|string|max:255',
                'codigo' => 'required|string|max:255',
                'peso_total' => 'required|numeric|min:0',
            ]);

            Planilla::create($validated);

            return redirect()->route('planillas.index')->with('success', 'Planilla creada exitosamente.');
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
    public function edit($id)
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        $planilla = Planilla::findOrFail($id);  // Encuentra la planilla por su ID

        return view('planillas.edit', compact('planilla'));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();  // Usamos una transacción para asegurar la integridad de los datos.
        try {
            $planilla = Planilla::findOrFail($id);

            // Validar los datos

            $request->validate([
                'cod_obra' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'nom_obra' => 'required|string|max:255',
                'seccion' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'poblacion' => 'required|string|max:255',
                'codigo' => 'required|string|max:255',
                // 'peso_total' => 'required|numeric|min:0',
            ]);

            // Actualizar la ubicación
            $planilla->update([
                'cod_obra' => $request->codigo,
                'cliente' => $request->descripcion,
                'nom_obra' => $request->nom_obra,
                'seccion' => $request->seccion,
                'descripcion' => $request->descripcion,
                'poblacion' => $request->poblacion,
                'codigo' => $request->codigo,
                // 'peso_total' => $request->peso_total,
            ]);

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('planillas.index')->with('success', 'Planilla actualizada');
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


    // Eliminar una planilla y sus elementos asociados
    public function destroy($id)
    {
        if (auth()->user()->role !== 'administrador') {
            return redirect()->route('planillas.index')->with('abort', 'No tienes los permisos necesarios.');
        }
        DB::beginTransaction();
        try {
            // Buscar la planilla a eliminar
            $planilla = Planilla::with('elementos')->findOrFail($id);

            // Eliminar los elementos asociados directamente
            foreach ($planilla->elementos as $elemento) {
                $elemento->delete();
            }

            // Eliminar la planilla
            $planilla->delete();

            DB::commit(); // Confirmamos la transacción
            return redirect()->route('planillas.index')->with('success', 'Planilla eliminada correctamente.');
        } catch (Exception $e) {
            DB::rollBack(); // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la planilla: ' . $e->getMessage());
        }
    }
}
