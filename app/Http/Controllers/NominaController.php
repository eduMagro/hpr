<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Nomina;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NominaController extends Controller
{


    public function index()
    {
        $empresas = Empresa::All();
        $nominas = Nomina::with('empleado')->get()->sortBy(function ($nomina) {
            return $nomina->empleado->name ?? '';
        });


        return view('nominas.index', compact('nominas', 'empresas'));
    }

    public function generarNominasMensuales()
    {
        $mesActual = Carbon::now()->startOfMonth();

        DB::beginTransaction();

        try {
            $trabajadores = User::with('convenio')->get();

            foreach ($trabajadores as $trabajador) {
                $c = $trabajador->convenio;

                if (!$c) {
                    continue; // Saltar si no tiene convenio asignado
                }

                $salario_base = $c->salario_base;
                $plus_actividad = $c->plus_actividad;
                $prorrateo = $c->prorrateo_pagasextras;

                $total_devengado = $salario_base + $c->plus_asistencia + $plus_actividad + $c->plus_productividad + $c->plus_absentismo + $c->plus_transporte + $prorrateo;

                // Deducciones estimadas (cotizaciones fijas, IRPF se puede mejorar luego)
                $deducciones_ss = $total_devengado * 0.068; // ejemplo aproximado: 6.8%
                $irpf_mensual = $total_devengado * 0.07;     // ejemplo aproximado: 7%

                $liquido = $total_devengado - $deducciones_ss - $irpf_mensual;

                Nomina::create([
                    'empleado_id' => $trabajador->id,
                    'categoria_id' => $trabajador->categoria_id,
                    'dias_trabajados' => 30,
                    'salario_base' => $salario_base,
                    'plus_actividad' => $plus_actividad,
                    'prorrateo' => $prorrateo,
                    'plus_varios' => 0,
                    'horas_extra' => 0,
                    'valor_hora_extra' => 0,
                    'total_devengado' => $total_devengado,
                    'total_deducciones_ss' => $deducciones_ss,
                    'irpf_mensual' => $irpf_mensual,
                    'liquido' => $liquido,
                    'bruto_anual_estimado' => $total_devengado * 12,
                    'base_irpf_previa' => $total_devengado * 12,
                    'cuota_irpf_anual_sin_minimo' => 0,
                    'cuota_minimo_personal' => 0,
                    'cuota_irpf_anual' => $irpf_mensual * 12,
                    'fecha' => $mesActual->toDateString(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Nóminas generadas correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return 'Error generando nóminas: ' . $e->getMessage();
        }
    }

    public function borrarTodas()
    {
        Nomina::truncate(); // Elimina todas las filas de la tabla

        return redirect()->back()->with('success', 'Todas las nóminas han sido eliminadas.');
    }
    public function show($id)
    {
        $nomina = Nomina::findOrFail($id);
        $empresas = Empresa::All();
        return view('nominas.show', compact('nomina', 'empresas'));
    }
}
