<?php

namespace App\Http\Controllers;

use App\Models\Elemento;
<<<<<<< HEAD
use App\Models\Conjunto;
=======
use App\Models\Planilla;
>>>>>>> 6fea693 (primercommit)
use Illuminate\Http\Request;

class ElementoController extends Controller
{
    /**
     * Muestra una lista de todos los elementos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $elementos = Elemento::with('conjunto.planilla')->paginate(10);
        return view('elementos.index', compact('elementos'));
    }

    /**
     * Muestra el formulario para crear un nuevo elemento.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $conjuntos = Conjunto::with('planilla')->get();
        return view('elementos.create', compact('conjuntos'));
    }

    /**
     * Almacena un nuevo elemento en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'conjunto_id' => 'required|exists:conjuntos,id',
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'diametro' => 'required|numeric|min:0',
            'longitud' => 'required|numeric|min:0',
            'peso' => 'required|numeric|min:0',
        ]);

        Elemento::create($validated);

        return redirect()->route('elementos.index')->with('success', 'Elemento creado exitosamente.');
    }

    /**
     * Muestra un elemento específico.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
<<<<<<< HEAD
    public function show(Elemento $elemento)
    {
        $elemento->load('conjunto.planilla');
        return view('elementos.show', compact('elemento'));
    }
=======
public function show($id)
{
    // Encuentra la planilla por ID y carga las relaciones necesarias (elementos y sus máquinas)
    $planilla = Planilla::with('elementos.maquina')->findOrFail($id);

    // Obtiene los elementos relacionados con la planilla
    $elementos = $planilla->elementos;

    // Retorna la vista con la planilla y sus elementos
    return view('elementos.show', compact('planilla', 'elementos'));
}

>>>>>>> 6fea693 (primercommit)

    /**
     * Muestra el formulario para editar un elemento existente.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
    public function edit(Elemento $elemento)
    {
        $conjuntos = Conjunto::with('planilla')->get();
        return view('elementos.edit', compact('elemento', 'conjuntos'));
    }

    /**
     * Actualiza un elemento existente en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
<<<<<<< HEAD
    public function update(Request $request, Elemento $elemento)
    {
        $validated = $request->validate([
            'conjunto_id' => 'required|exists:conjuntos,id',
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'diametro' => 'required|numeric|min:0',
            'longitud' => 'required|numeric|min:0',
            'peso' => 'required|numeric|min:0',
        ]);

        $elemento->update($validated);

        return redirect()->route('elementos.index')->with('success', 'Elemento actualizado exitosamente.');
    }

=======
public function actualizarEstado(Request $request)
{
    // Validar los datos enviados por el formulario
    $validated = $request->validate([
        'elemento_id' => 'required|exists:elementos,id',
        'planilla_id' => 'required|exists:planillas,id',
        'accion' => 'required|in:completar,descompletar',
    ]);

    // Buscar el elemento, la planilla y la máquina asociados
    $elemento = Elemento::findOrFail($validated['elemento_id']);
    $planilla = Planilla::findOrFail($validated['planilla_id']);
    $maquina = $elemento->maquina;

    if (!$maquina) {
        return redirect()->route('elementos.show', $planilla->id)
            ->with('error', 'La máquina asociada al elemento no existe.');
    }

    if ($validated['accion'] === 'completar') {
        // Obtener todos los productos con el diámetro especificado
        $productos = $maquina->productos()->where('diametro', $elemento->diametro)->orderBy('id')->get();

        if ($productos->isEmpty()) {
            return redirect()->route('elementos.show', $planilla->id)
                ->with('error', 'No se encontraron productos asociados con ese diámetro en la máquina.');
        }

        $pesoRequerido = $elemento->peso; // Peso que se necesita restar

        foreach ($productos as $producto) {
            if ($pesoRequerido <= 0) {
                break; // Si ya se cubrió el peso requerido, detener el bucle
            }

            // Verificar cuánto peso se puede restar de este producto
            $pesoDisponible = $producto->peso_stock;

            if ($pesoDisponible > 0) {
                // Restar el peso posible del producto
                $resta = min($pesoDisponible, $pesoRequerido);
                $producto->peso_stock -= $resta;
                $producto->save();

                // Reducir el peso requerido
                $pesoRequerido -= $resta;
            }
        }

        // Si no se pudo cubrir todo el peso, devolver un error
        if ($pesoRequerido > 0) {
			DB::rollback();
            return redirect()->route('elementos.show', $planilla->id)
                ->with('error', 'No hay suficientes kilos disponibles en los productos de la máquina.');
        }

        // Actualizar el estado del elemento
        $elemento->estado = 'completado';
        $elemento->save();

        return redirect()->route('elementos.show', $planilla->id)
            ->with('success', 'Elemento completado y kilos actualizados en los productos.');
    }

    if ($validated['accion'] === 'descompletar') {
        // Obtener el primer producto con el diámetro especificado
        $producto = $maquina->productos()->where('diametro', $elemento->diametro)->first();

        if (!$producto) {
            return redirect()->route('elementos.show', $planilla->id)
                ->with('error', 'No se encontró un producto asociado con ese diámetro en la máquina.');
        }

        // Revertir los kilos al producto
        $producto->peso_stock += $elemento->peso;
        $producto->save();

        // Actualizar el estado del elemento
        $elemento->estado = 'pendiente';
        $elemento->save();

        return redirect()->route('elementos.show', $planilla->id)
            ->with('success', 'Elemento descompletado y kilos revertidos al producto.');
    }

    return redirect()->route('elementos.show', $planilla->id)
        ->with('error', 'Acción no válida.');
}


>>>>>>> 6fea693 (primercommit)
    /**
     * Elimina un elemento existente de la base de datos.
     *
     * @param  \App\Models\Elemento  $elemento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Elemento $elemento)
    {
        $elemento->delete();
        return redirect()->route('elementos.index')->with('success', 'Elemento eliminado exitosamente.');
    }
}
