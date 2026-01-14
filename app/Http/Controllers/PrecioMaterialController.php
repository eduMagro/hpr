<?php

namespace App\Http\Controllers;

use App\Models\PrecioMaterialDiametro;
use App\Models\PrecioMaterialFormato;
use App\Models\PrecioMaterialExcepcion;
use App\Models\PrecioMaterialConfig;
use App\Models\ProductoBase;
use App\Models\Fabricante;
use App\Models\Distribuidor;
use App\Services\PrecioMaterialService;
use Illuminate\Http\Request;

class PrecioMaterialController extends Controller
{
    protected PrecioMaterialService $service;

    public function __construct(PrecioMaterialService $service)
    {
        $this->service = $service;
    }

    /**
     * Vista principal de configuración de precios de material.
     */
    public function index()
    {
        $diametros = PrecioMaterialDiametro::orderBy('diametro')->get();
        $formatos = PrecioMaterialFormato::orderBy('codigo')->get();

        // Separar excepciones en dos grupos
        $excepcionesFabricante = PrecioMaterialExcepcion::with(['fabricante'])
            ->porFabricante()
            ->get();
        $excepcionesEspecificas = PrecioMaterialExcepcion::with(['distribuidor', 'fabricante'])
            ->especificas()
            ->get();

        $fabricantes = Fabricante::orderBy('nombre')->get();
        $distribuidores = Distribuidor::orderBy('nombre')->get();
        $productosBase = ProductoBase::orderBy('diametro')->orderBy('longitud')->get();

        // Configuración actual
        $productoBaseReferenciaId = PrecioMaterialConfig::get('producto_base_referencia_id');

        return view('logistica.precios-material.index', compact(
            'diametros',
            'formatos',
            'excepcionesFabricante',
            'excepcionesEspecificas',
            'fabricantes',
            'distribuidores',
            'productosBase',
            'productoBaseReferenciaId'
        ));
    }

    /**
     * Actualiza incrementos de diámetros.
     */
    public function updateDiametros(Request $request)
    {
        $request->validate([
            'diametros' => 'required|array',
            'diametros.*.id' => 'required|exists:precios_material_diametros,id',
            'diametros.*.incremento' => 'required|numeric|min:0',
            'diametros.*.activo' => 'boolean',
        ]);

        foreach ($request->diametros as $data) {
            PrecioMaterialDiametro::where('id', $data['id'])->update([
                'incremento' => $data['incremento'],
                'activo' => $data['activo'] ?? true,
            ]);
        }

        return back()->with('success', 'Incrementos por diámetro actualizados.');
    }

    /**
     * Actualiza incrementos de formatos.
     */
    public function updateFormatos(Request $request)
    {
        $request->validate([
            'formatos' => 'required|array',
            'formatos.*.id' => 'required|exists:precios_material_formatos,id',
            'formatos.*.incremento' => 'required|numeric|min:0',
            'formatos.*.activo' => 'boolean',
        ]);

        foreach ($request->formatos as $data) {
            PrecioMaterialFormato::where('id', $data['id'])->update([
                'incremento' => $data['incremento'],
                'activo' => $data['activo'] ?? true,
            ]);
        }

        return back()->with('success', 'Incrementos por formato actualizados.');
    }

    /**
     * Crea una nueva excepción.
     */
    public function storeExcepcion(Request $request)
    {
        $request->validate([
            'distribuidor_id' => 'nullable|exists:distribuidores,id',
            'fabricante_id' => 'required|exists:fabricantes,id',
            'formato_codigo' => 'required|exists:precios_material_formatos,codigo',
            'incremento' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ]);

        $distribuidorId = $request->distribuidor_id ?: null;

        // Verificar que no exista ya
        $query = PrecioMaterialExcepcion::where('fabricante_id', $request->fabricante_id)
            ->where('formato_codigo', $request->formato_codigo);

        if ($distribuidorId) {
            $query->where('distribuidor_id', $distribuidorId);
        } else {
            $query->whereNull('distribuidor_id');
        }

        if ($query->exists()) {
            return back()->withErrors(['excepcion' => 'Ya existe una excepción para esta combinación.']);
        }

        PrecioMaterialExcepcion::create([
            'distribuidor_id' => $distribuidorId,
            'fabricante_id' => $request->fabricante_id,
            'formato_codigo' => $request->formato_codigo,
            'incremento' => $request->incremento,
            'notas' => $request->notas,
            'activo' => true,
        ]);

        return back()->with('success', 'Excepción creada correctamente.');
    }

    /**
     * Actualiza una excepción.
     */
    public function updateExcepcion(Request $request, PrecioMaterialExcepcion $excepcion)
    {
        $request->validate([
            'incremento' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ]);

        $excepcion->update([
            'incremento' => $request->incremento,
            'notas' => $request->notas,
            'activo' => $request->activo ?? true,
        ]);

        return back()->with('success', 'Excepción actualizada.');
    }

    /**
     * Elimina una excepción.
     */
    public function destroyExcepcion(PrecioMaterialExcepcion $excepcion)
    {
        $excepcion->delete();
        return back()->with('success', 'Excepción eliminada.');
    }

    /**
     * Actualiza la configuración general.
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'producto_base_referencia_id' => 'nullable|exists:productos_base,id',
        ]);

        if ($request->producto_base_referencia_id) {
            $productoBase = ProductoBase::find($request->producto_base_referencia_id);
            PrecioMaterialConfig::set(
                'producto_base_referencia_id',
                $request->producto_base_referencia_id,
                "Producto base de referencia: Ø{$productoBase->diametro} a {$productoBase->longitud}m"
            );
        }

        return back()->with('success', 'Configuración actualizada.');
    }
}
