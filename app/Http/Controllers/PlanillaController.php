<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planilla;
use Illuminate\Support\Facades\DB;
use App\Imports\PlanillaImport;
use Maatwebsite\Excel\Facades\Excel;

class PlanillaController extends Controller
{

    public function index(Request $request)
    {
        $planillas = Planilla::with('conjuntos.elementos')->get();

        $query = Planilla::query();
        // $query = $this->aplicarFiltros($query, $request);

        // Aplicar filtro por código si se pasa como parámetro en la solicitud
        if ($request->has('codigo')) {
            $codigo = $request->input('codigo');
            $query->where('nombre', 'like', '%' . $codigo . '%');
        }
        // Ordenar
        $sortBy = $request->input('sort_by', 'created_at');  // Primer criterio de ordenación (nombre)
        $order = $request->input('order', 'desc');        // Orden del primer criterio (asc o desc)

        // Aplicar ordenamiento por múltiples columnas
        $query->orderByRaw("CAST({$sortBy} AS CHAR) {$order}");

        // Paginación
        $perPage = $request->input('per_page', 10);
        $registrosMaquina = $query->paginate($perPage)->appends($request->except('page'));

        // Pasar las ubicaciones y productos a la vista
        return view('planillas.index', compact('planillas'));
    }

    public function show($id)
    {
        $planilla = Planilla::findOrFail($id);

        return view('planillas.show', compact('planilla'));
    }


    public function create()
    {
        return view('planillas.create');
    }

    public function import(Request $request)
{
    // Validar el archivo
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv',
    ]);

    try {
        $file = $request->file('file');

        // Convertir el archivo a un arreglo
        $importedData = Excel::toArray([], $file);

        // Procesar la primera hoja del archivo
        $firstSheet = $importedData[0]; // Primera hoja

        if (empty($firstSheet)) {
            return redirect()->route('planillas.index')->with('error', 'El archivo está vacío o no contiene datos válidos.');
        }

        // Tomar la primera línea de datos reales (después de las cabeceras)
        $headers = $firstSheet[0]; // Encabezados
        $data = array_slice($firstSheet, 1); // Todas las filas excepto la cabecera

        // Filtrar filas vacías
        $filteredData = array_filter($data, function ($row) {
            return array_filter($row); // Ignorar filas completamente nulas
        });

        if (empty($filteredData)) {
            return redirect()->route('planillas.index')->with('error', 'El archivo no contiene filas válidas después de las cabeceras.');
        }

        // Tomar la primera fila de datos como representativa
        $firstRow = $filteredData[0];

        // Sumar todos los pesos (columna índice 8)
        $pesoTotal = array_reduce($filteredData, function ($carry, $row) {
            return $carry + (float) $row[8];
        }, 0);
     
        // Guardar en la base de datos
        Planilla::create([
            'cod_obra'    => $firstRow[0],
            'cliente'     => $firstRow[1],
            'nom_obra'    => $firstRow[2],
            'seccion'     => $firstRow[3],
            'descripcion' => $firstRow[4] ?? null,
            'poblacion'   => $firstRow[6],
            'codigo'      => $firstRow[5] ?? null,
            'peso_total'  => $pesoTotal,
        ]);

        return redirect()->route('planillas.index')->with('success', 'Planillas importadas correctamente.');
    } catch (\Exception $e) {
        return redirect()->route('planillas.index')->with('error', 'Hubo un problema al importar las planillas: ' . $e->getMessage());
    }
}


    public function store(Request $request)
    {
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
dd($validated);
        Planilla::create($validated);

        return redirect()->route('planillas.index')->with('success', 'Planilla creada exitosamente.');
    }


}
