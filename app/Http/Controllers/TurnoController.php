<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TurnoController extends Controller
{
    /**
     * Mostrar listado de turnos
     */
    public function index()
    {
        $turnos = Turno::ordenados()->get();

        return view('configuracion.turnos.index', compact('turnos'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('configuracion.turnos.create');
    }

    /**
     * Guardar nuevo turno
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
            'offset_dias_inicio' => 'required|integer|min:-1|max:1',
            'offset_dias_fin' => 'required|integer|min:-1|max:1',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Procesar dias_semana: si está vacío o tiene los 5 días laborables, guardar null
        $diasSemana = $request->dias_semana;
        if (empty($diasSemana) || $diasSemana === Turno::DIAS_DEFAULT ||
            (is_array($diasSemana) && count($diasSemana) === 5 &&
             empty(array_diff($diasSemana, Turno::DIAS_DEFAULT)) &&
             empty(array_diff(Turno::DIAS_DEFAULT, $diasSemana)))) {
            $diasSemana = null;
        }

        Turno::create([
            'nombre' => $request->nombre,
            'hora_inicio' => $request->hora_inicio . ':00',
            'hora_fin' => $request->hora_fin . ':00',
            'offset_dias_inicio' => $request->offset_dias_inicio,
            'offset_dias_fin' => $request->offset_dias_fin,
            'activo' => $request->has('activo') ? 1 : 0,
            'orden' => $request->orden ?? 999,
            'color' => $request->color,
            'dias_semana' => $diasSemana,
        ]);

        return redirect()->route('turnos.index')
            ->with('success', 'Turno creado exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Turno $turno)
    {
        return view('configuracion.turnos.edit', compact('turno'));
    }

    /**
     * Actualizar turno
     */
    public function update(Request $request, Turno $turno)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
            'offset_dias_inicio' => 'required|integer|min:-1|max:1',
            'offset_dias_fin' => 'required|integer|min:-1|max:1',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Procesar dias_semana: si está vacío o tiene los 5 días laborables, guardar null
        $diasSemana = $request->dias_semana;
        if (empty($diasSemana) || $diasSemana === Turno::DIAS_DEFAULT ||
            (is_array($diasSemana) && count($diasSemana) === 5 &&
             empty(array_diff($diasSemana, Turno::DIAS_DEFAULT)) &&
             empty(array_diff(Turno::DIAS_DEFAULT, $diasSemana)))) {
            $diasSemana = null;
        }

        $turno->update([
            'nombre' => $request->nombre,
            'hora_inicio' => $request->hora_inicio . ':00',
            'hora_fin' => $request->hora_fin . ':00',
            'offset_dias_inicio' => $request->offset_dias_inicio,
            'offset_dias_fin' => $request->offset_dias_fin,
            'activo' => $request->has('activo') ? 1 : 0,
            'orden' => $request->orden ?? 999,
            'color' => $request->color,
            'dias_semana' => $diasSemana,
        ]);

        return redirect()->route('turnos.index')
            ->with('success', 'Turno actualizado exitosamente');
    }

    /**
     * Eliminar turno (soft delete)
     */
    public function destroy(Turno $turno)
    {
        $turno->delete();

        return redirect()->route('turnos.index')
            ->with('success', 'Turno eliminado exitosamente');
    }

    /**
     * Activar/desactivar turno
     */
    public function toggleActivo(Request $request, Turno $turno)
    {
        $turno->update(['activo' => !$turno->activo]);

        $estado = $turno->activo ? 'activado' : 'desactivado';

        // Si es una petición AJAX, retornar JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Turno {$estado} exitosamente",
                'turno' => $turno
            ]);
        }

        return redirect()->route('turnos.index')
            ->with('success', "Turno {$estado} exitosamente");
    }
}
