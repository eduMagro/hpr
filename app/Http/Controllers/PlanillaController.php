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
    //------------------------------------------------------------------------------------ ASIGNAR MAQUINA()
    public function asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planillaId)
    {
        // Determinar si el elemento es un estribo, lo cual depende de la cantidad de dobles por barra
        $estribo = $doblesPorBarra >= 4;

        // Obtener todos los diámetros de los elementos que pertenecen a la misma planilla
        $diametrosPlanilla = Elemento::where('planilla_id', $planillaId)->distinct()->pluck('diametro')->toArray();

        // Determinar si es necesario forzar la asignación de una máquina basada en los diámetros presentes en la planilla
        $maquinaForzada = null;
        if (count($diametrosPlanilla) > 1) {
            $maxDiametro = max($diametrosPlanilla);
            if ($maxDiametro <= 12) {
                $maquinaForzada = ['PS12', 'F12']; // Para diámetros pequeños
            } elseif ($maxDiametro <= 20) {
                $maquinaForzada = ['MSR20']; // Para diámetros medianos
            } elseif ($maxDiametro <= 25) {
                $maquinaForzada = ['SL28']; // Para diámetros grandes
            } else {
                $maquinaForzada = ['MANUAL']; // Para diámetros muy grandes
            }
        }

        // Caso especial: si el diámetro es exactamente 5 mm, se asigna directamente a la máquina ID5
        if ($diametro == 5) {
            $maquinas = Maquina::where('codigo', 'ID5')->get();
        }
        // Si es un estribo y su diámetro está entre 8 y 12 mm, solo puede asignarse a PS12 o F12
        elseif ($estribo && $diametro >= 8 && $diametro <= 12) {
            $maquinas = Maquina::whereIn('codigo', ['PS12', 'F12'])->get();
        }
        // Si la asignación de máquina está forzada debido a múltiples diámetros en la planilla
        elseif ($maquinaForzada) {
            $maquinas = Maquina::whereIn('codigo', $maquinaForzada)->get();
        }
        // Si el diámetro está entre 10 y 16 mm, se asigna a MSR20
        elseif ($diametro >= 10 && $diametro <= 16) {
            $maquinas = Maquina::where('codigo', 'MSR20')->get();
        }
        // Si el diámetro está entre 8 y 20 mm, se asigna a MSR20
        elseif ($diametro >= 8 && $diametro <= 20) {
            $maquinas = Maquina::where('codigo', 'MSR20')->get();
        }
        // Si el diámetro está entre 12 y 25 mm, se asigna a SL28
        elseif ($diametro >= 12 && $diametro <= 25) {
            $maquinas = Maquina::where('codigo', 'SL28')->get();
        } else {
            // Si no cumple con ningún otro criterio, se asigna a la máquina manual
            $maquinas = Maquina::where('codigo', 'MANUAL')->get();
        }

        // Si no hay máquinas disponibles, retornar null
        if ($maquinas->isEmpty()) {
            return null;
        }

        // Buscar la máquina con menos peso asignado y que aún esté dentro de su límite de producción
        $maquinaSeleccionada = null;
        $pesoMinimo = PHP_INT_MAX;

        foreach ($maquinas as $maquina) {
            // Calcular el peso total ya asignado a la máquina
            $pesoActual = Elemento::where('maquina_id', $maquina->id)->sum('peso');

            // Definir el límite de producción de cada máquina
            $limiteProduccion = match ($maquina->codigo) {
                'PS12', 'F12' => 10000, // Límite para máquinas pequeñas
                'MSR20' => 12000, // Límite para máquinas medianas
                'SL28' => 30000, // Límite para máquinas grandes
                default => PHP_INT_MAX // La máquina manual no tiene límite
            };

            // Seleccionar la máquina que tenga el menor peso total asignado y que aún tenga capacidad
            if ($pesoActual < $limiteProduccion && $pesoActual < $pesoMinimo) {
                $pesoMinimo = $pesoActual;
                $maquinaSeleccionada = $maquina;
            }
        }

        // Retornar el ID de la máquina seleccionada o null si no hay opción válida
        return $maquinaSeleccionada?->id ?? null;
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
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        // 📌 Filtrar por planilla_id si está presente
        if ($request->has('planilla_id')) {
            $query->where('id', $request->planilla_id);
        }
        return $query;
    }


    //------------------------------------------------------------------------------------ INDEX()
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

    //------------------------------------------------------------------------------------ SHOW()

    public function show($id)
    {
        // Encuentra la planilla por ID
        $planilla = Planilla::findOrFail($id);

        // Obtiene los elementos relacionados y carga la relación con máquina
        $elementos = $planilla->elementos()->with('maquina')->get();

        // Retorna la vista con la planilla y sus elementos
        return view('planillas.elementos', compact('planilla', 'elementos'));
    }

    //------------------------------------------------------------------------------------ CREATE()
    public function create()
    {
        return view('planillas.create');
    }

    //------------------------------------------------------------------------------------ IMPORT()
    public function import(Request $request)
    {
        if (auth()->user()->categoria !== 'administrador') {
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
                    'cod_cliente' => $rows[0][0] ?? null,
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

                // Array para almacenar etiquetas ya registradas en esta ejecución
                $etiquetasRegistradas = [];
                foreach ($rows as $row) {
                    $diametro = $row[25] ?? 0;
                    $longitud = $row[27] ?? 0;
                    $figura = $row[26] ?? null;
                    $doblesPorBarra = $row[33] ?? 0;
                    $barras = $row[32] ?? 0;
                    $ensamblado = $row[4] ?? null;
                    $planillaId = $planilla->id; // Asegúrate de definirlo antes de la llamada

                    // Llamar a asignarMaquina con los nuevos parámetros
                    $maquina_id = $this->asignarMaquina($diametro, $longitud, $figura, $doblesPorBarra, $barras, $ensamblado, $planillaId);
                    $tiempos = $this->calcularTiemposElemento($row);

                    // Verificar si la etiqueta ya existe antes de crearla
                    $numeroEtiqueta = $row[30] ?? null;

                    if (!isset($etiquetasRegistradas[$numeroEtiqueta])) {
                        $etiqueta = Etiqueta::where('numero_etiqueta', $numeroEtiqueta)
                            ->where('planilla_id', $planilla->id)
                            ->first();

                        if (!$etiqueta) {
                            $etiqueta = Etiqueta::create([
                                'numero_etiqueta' => $numeroEtiqueta,
                                'planilla_id' => $planilla->id,
                                'nombre' => $row[22] ?? 'Sin nombre',
                                'peso' => 0 // Inicialmente en 0, lo actualizamos después
                            ]);
                        }

                        $etiquetasRegistradas[$numeroEtiqueta] = $etiqueta;
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
                        'fecha_inicio' => null,
                        'fecha_finalizacion' => null,
                        'tiempo_fabricacion' => $tiempos['tiempo_fabricacion'],
                    ]);
                }
                foreach ($etiquetasRegistradas as $etiqueta) {
                    $etiqueta->peso = $etiqueta->elementos()->sum('peso');
                    $etiqueta->save();
                }
            }
            $planilla->update([
                'tiempo_fabricacion' => $planilla->elementos->sum('tiempo_fabricacion'),
            ]);

            DB::commit(); // Confirmar la transacción
            return redirect()->route('planillas.index')->with('success', 'Planillas y elementos importados correctamente por código.');
        } catch (Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return redirect()->route('planillas.index')->with('error', 'Hubo un problema al importar las planillas: ' . $e->getMessage());
        }
    }
    //------------------------------------------------------------------------------------ CALCULARTIEMPOSELEMENTO()
    private function calcularTiemposElemento(array $row)
    {
        $barras = $row[32] ?? 0;
        $doblesBarra = $row[33] ?? 0;

        // Calcular el tiempo estimado para el elemento
        $tiempoFabricacion = ($doblesBarra > 0)
            ? ($barras * $doblesBarra * 1.5) // Cálculo para barras con dobles
            : ($barras * 2); // Cálculo para barras rectas



        return [
            'tiempo_fabricacion' => $tiempoFabricacion,
        ];
    }

    //------------------------------------------------------------------------------------ STORE()
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'cod_obra' => 'required|string|max:255',
                'cod_cliente' => 'required|string|max:255',
                'cliente' => 'required|string|max:255',
                'nom_obra' => 'required|string|max:255',
                'seccion' => 'required|string|max:255',
                'descripcion' => 'required|string|max:255',
                'ensamblado' => 'required|string|max:255',
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
    //------------------------------------------------------------------------------------ EDIT()
    public function edit($id)
    {
        if (auth()->user()->categoria !== 'administrador') {
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

            // Actualizar la planilla con datos limpios
            $planilla->update([
                'cod_obra' => $request->cod_obra,
                'cod_cliente' => $request->cod_cliente,
                'cliente' => $request->cliente,
                'nom_obra' => $request->nom_obra,
                'seccion' => $request->seccion,
                'descripcion' => $request->descripcion,
                'ensamblado' => $request->ensamblado,
                'codigo' => $request->codigo,
                'peso_total' => $request->peso_total,
                'fecha_inicio' => NULL,
                'fecha_finalizacion' => NULL,
                'tiempo_fabricacion' => 0,
            ]);

            DB::commit();  // Confirmamos la transacción
            return redirect()->route('planillas.index')->with('success', 'Planilla actualizada');
        } catch (ValidationException $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();  // Si ocurre un error, revertimos la transacción
            return redirect()->back()->with('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    //------------------------------------------------------------------------------------ DESTROY()

    // Eliminar una planilla y sus elementos asociados
    public function destroy($id)
    {
        if (auth()->user()->categoria !== 'administrador') {
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
