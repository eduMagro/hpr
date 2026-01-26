<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Colada;
use App\Models\ProductoBase;
use App\Models\Fabricante;

class ColadaController extends Controller
{
    public function index()
    {
        return view('coladas.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_colada' => 'required|string|max:100',
            'producto_base_id' => 'required|exists:productos_base,id',
            'fabricante_id' => 'nullable|exists:fabricantes,id',
            'documento' => 'nullable|file|mimes:pdf|max:10240',
            'codigo_adherencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ], [
            'numero_colada.required' => 'El numero de colada es obligatorio.',
            'producto_base_id.required' => 'Debe seleccionar un producto base.',
            'producto_base_id.exists' => 'El producto base seleccionado no existe.',
            'fabricante_id.exists' => 'El fabricante seleccionado no existe.',
            'documento.mimes' => 'El documento debe ser un archivo PDF.',
            'documento.max' => 'El documento no puede superar los 10MB.',
        ]);

        // Verificar que no exista la combinación
        $existe = Colada::where('numero_colada', $validated['numero_colada'])
            ->where('producto_base_id', $validated['producto_base_id'])
            ->exists();

        if ($existe) {
            return back()->withErrors(['numero_colada' => 'Ya existe una colada con ese numero para este producto base.'])->withInput();
        }

        $colada = new Colada();
        $colada->numero_colada = $validated['numero_colada'];
        $colada->producto_base_id = $validated['producto_base_id'];
        $colada->fabricante_id = $validated['fabricante_id'] ?? null;
        $colada->codigo_adherencia = $validated['codigo_adherencia'] ?? null;
        $colada->observaciones = $validated['observaciones'] ?? null;
        $colada->dio_de_alta = auth()->id();

        if ($request->hasFile('documento')) {
            $archivo = $request->file('documento');
            $nombreArchivo = 'colada_' . $validated['numero_colada'] . '_' . time() . '.pdf';
            $ruta = $archivo->storeAs('coladas', $nombreArchivo, 'public');
            $colada->documento = $ruta;
        }

        $colada->save();

        return redirect()->route('coladas.index')->with('success', 'Colada creada correctamente.');
    }

    public function update(Request $request, Colada $colada)
    {
        $rules = [
            'numero_colada' => 'required|string|max:100',
            'producto_base_id' => 'required|exists:productos_base,id',
            'fabricante_id' => 'nullable|exists:fabricantes,id',
            'documento' => 'nullable|file|mimes:pdf|max:10240',
            'codigo_adherencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ];

        $messages = [
            'numero_colada.required' => 'El numero de colada es obligatorio.',
            'producto_base_id.required' => 'Debe seleccionar un producto base.',
            'documento.mimes' => 'El documento debe ser un archivo PDF.',
            'documento.max' => 'El documento no puede superar los 10MB.',
        ];

        // Si es AJAX, validar y devolver JSON en caso de error
        if ($request->ajax() || $request->wantsJson()) {
            $validator = \Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
        } else {
            $validated = $request->validate($rules, $messages);
        }

        // Verificar que no exista la combinación (excluyendo la actual)
        $existe = Colada::where('numero_colada', $validated['numero_colada'])
            ->where('producto_base_id', $validated['producto_base_id'])
            ->where('id', '!=', $colada->id)
            ->exists();

        if ($existe) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['numero_colada' => ['Ya existe una colada con ese numero para este producto base.']]
                ], 422);
            }
            return back()->withErrors(['numero_colada' => 'Ya existe una colada con ese numero para este producto base.'])->withInput();
        }

        $colada->numero_colada = $validated['numero_colada'];
        $colada->producto_base_id = $validated['producto_base_id'];
        $colada->fabricante_id = $validated['fabricante_id'] ?? null;
        $colada->codigo_adherencia = $validated['codigo_adherencia'] ?? null;
        $colada->observaciones = $validated['observaciones'] ?? null;
        $colada->ultima_modificacion = auth()->id();

        if ($request->hasFile('documento')) {
            // Eliminar documento anterior si existe
            if ($colada->documento && \Storage::disk('public')->exists($colada->documento)) {
                \Storage::disk('public')->delete($colada->documento);
            }

            $archivo = $request->file('documento');
            $nombreArchivo = 'colada_' . $validated['numero_colada'] . '_' . time() . '.pdf';
            $ruta = $archivo->storeAs('coladas', $nombreArchivo, 'public');
            $colada->documento = $ruta;
        }

        $colada->save();

        // Cargar relaciones para devolver datos completos
        $colada->load(['productoBase', 'fabricante']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Colada actualizada correctamente.',
                'colada' => $colada
            ]);
        }

        return redirect()->route('coladas.index')->with('success', 'Colada actualizada correctamente.');
    }

    public function destroy(Colada $colada)
    {
        // Eliminar documento si existe
        if ($colada->documento && \Storage::disk('public')->exists($colada->documento)) {
            \Storage::disk('public')->delete($colada->documento);
        }

        $colada->delete();

        return redirect()->route('coladas.index')->with('success', 'Colada eliminada correctamente.');
    }

    public function descargarDocumento(Colada $colada)
    {
        if (!$colada->documento || !\Storage::disk('public')->exists($colada->documento)) {
            return back()->with('error', 'No hay documento disponible para esta colada.');
        }

        return \Storage::disk('public')->download($colada->documento, 'colada_' . $colada->numero_colada . '.pdf');
    }
}
