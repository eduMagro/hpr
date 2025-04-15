<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Nomina;
use App\Models\Empresa;
use App\Models\Modelo145;
use App\Models\SeguridadSocial;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NominaController extends Controller
{

    // --------------------- GENERACION NOMINA
    public function index()
    {
        $empresas = Empresa::All();
        $nominas = Nomina::with('empleado')->get()->sortBy(function ($nomina) {
            return $nomina->empleado->name ?? '';
        });
        $trabajadores = User::with('categoria')->orderBy('name')->get();

        return view('nominas.index', compact('nominas', 'empresas', 'trabajadores'));
    }

    public function generarNominasMensuales(Request $request, ?int $mes = null, ?int $anio = null)
    {
        // Obtener mes y año desde el request o usar fecha actual
        if ($request->filled('fecha')) {
            [$anio, $mes] = explode('-', $request->input('fecha'));
        } else {
            $mes = $mes ?? now()->month;
            $anio = $anio ?? now()->year;
        }

        DB::beginTransaction();

        try {
            // Cargar todos los trabajadores con sus relaciones necesarias
            $trabajadores = User::with(['convenio', 'modelo145'])->get();
            $idsConIncentivo = $request->input('incentivados', []);

            foreach ($trabajadores as $trabajador) {
                $modelo = $trabajador->modelo145;
                $convenio = $trabajador->convenio;

                if (!$convenio || !$modelo) continue;

                // Datos base del trabajador
                $salario_base = $convenio->salario_base / 12;
                $pluses = [
                    'actividad' => $convenio->plus_actividad / 12,
                    'asistencia' => $convenio->plus_asistencia / 12,
                    'transporte' => $convenio->plus_transporte / 12,
                    'dieta' => $convenio->plus_dieta / 12,
                    'turnicidad' => $convenio->plus_turnicidad / 12,
                    'prorrateo' => $convenio->prorrateo_pagasextras / 12,
                ];

                $liquidoDeseado = $convenio->liquido_minimo_pactado;
                $aplicaIncentivo = in_array($trabajador->id, $idsConIncentivo);
                $porcentajeSS_trabajador = $this->obtenerTotalCotizacionTrabajador();
                $porcentajeSS_empresa = $this->obtenerTotalCotizacionEmpresa();
                $minimos = $this->calcularMinimosDesdeModelo($modelo);

                // Calcular IRPF acumulado hasta la fecha
                $nominasPrevias = Nomina::where('empleado_id', $trabajador->id)
                    ->whereYear('fecha', $anio)
                    ->whereMonth('fecha', '<', $mes)
                    ->get();
                $acumuladoIRPF = $nominasPrevias->sum('irpf_mensual');

                // Primer cálculo del IRPF estimado
                $brutoBase = $this->calcularBruto($salario_base, $pluses, $liquidoDeseado);
                $brutoAnualEstimado = $brutoBase * 12;
                $ssAnual = $brutoAnualEstimado * ($porcentajeSS_trabajador / 100);
                $baseIRPF = max($brutoAnualEstimado - $ssAnual - $minimos, 0);
                $cuotaIRPFAnual = $this->calcularCuotaIRPF($baseIRPF);
                $mesesRestantes = 12 - count($nominasPrevias);
                $irpfMensual = $mesesRestantes > 0 ? ($cuotaIRPFAnual - $acumuladoIRPF) / $mesesRestantes : 0;

                // Ajustar pluses hasta alcanzar el mínimo líquido pactado (sin incentivo)
                $ajusteNecesario = $this->ajustarPlusesHastaMinimoLiquido($pluses, $salario_base, $liquidoDeseado, $irpfMensual, $porcentajeSS_trabajador);
                $brutoBase = $this->calcularBruto($salario_base, $pluses, $liquidoDeseado);
                // Recalcular IRPF con los nuevos pluses ajustados
                $brutoAnualEstimado = $brutoBase * 12;
                $ssAnual = $brutoAnualEstimado * ($porcentajeSS_trabajador / 100);
                $baseIRPF = max($brutoAnualEstimado - $ssAnual - $minimos, 0);
                $cuotaIRPFAnual = $this->calcularCuotaIRPF($baseIRPF);
                $irpfMensual = $mesesRestantes > 0 ? ($cuotaIRPFAnual - $acumuladoIRPF) / $mesesRestantes : 0;
                $netoBase = $this->calcularNeto($brutoBase, $irpfMensual, $porcentajeSS_trabajador);

                $plus_productividad = 0;
                if ($aplicaIncentivo) {
                    // Si hay incentivo, calcular bruto necesario para alcanzar el nuevo neto
                    $liquidoDeseadoConIncentivo = $netoBase + ($convenio->plus_productividad / 12);
                    $plus_productividad = $this->ajustarBrutoParaProductividad(
                        $brutoBase,
                        $netoBase,
                        $liquidoDeseadoConIncentivo,
                        $irpfMensual,
                        $porcentajeSS_trabajador
                    );

                    // Recalcular IRPF base excluyendo el incentivo
                    $brutoFinal = $brutoBase + $plus_productividad;
                    $brutoAnualEstimado = $brutoBase * 12; // ¡sin incluir incentivo!
                    $ssAnual = $brutoAnualEstimado * ($porcentajeSS_trabajador / 100);
                    $baseIRPF = max($brutoAnualEstimado - $ssAnual - $minimos, 0);
                    $cuotaIRPFAnual = $this->calcularCuotaIRPF($baseIRPF);
                    $irpf_base_mensual = $mesesRestantes > 0 ? ($cuotaIRPFAnual - $acumuladoIRPF) / $mesesRestantes : 0;

                    // Calcular IRPF separado para el incentivo
                    $tipo_irpf_incentivo = 0.04; // 4% fijo para este tipo de incentivo ocasional
                    $irpf_incentivo = $plus_productividad * $tipo_irpf_incentivo;

                    // Sumar ambos para el IRPF total
                    $irpfMensual = $irpf_base_mensual + $irpf_incentivo;
                }

                // Cálculo final de la nómina
                $brutoFinal = $brutoBase + $plus_productividad;
                $ss = $brutoFinal * ($porcentajeSS_trabajador / 100);
                $netoFinal = $brutoFinal - $ss - $irpfMensual;
                $costeEmpresa = $brutoFinal + ($brutoFinal * ($porcentajeSS_empresa / 100));
                $porcentajeIRPF = $brutoFinal > 0 ? round(($irpfMensual / $brutoFinal) * 100, 2) : 0;

                // Guardar la nómina
                Nomina::create([
                    'empleado_id' => $trabajador->id,
                    'categoria_id' => $trabajador->categoria_id,
                    'dias_trabajados' => 30,
                    'salario_base' => round($salario_base, 2),
                    'plus_ajustado' => $ajusteNecesario,
                    'plus_actividad' => round($pluses['actividad'], 2),
                    'plus_asistencia' => round($pluses['asistencia'], 2),
                    'plus_transporte' => round($pluses['transporte'], 2),
                    'plus_dieta' => round($pluses['dieta'], 2),
                    'plus_turnicidad' => round($pluses['turnicidad'], 2),
                    'plus_productividad' => round($plus_productividad, 2),
                    'prorrateo' => round($pluses['prorrateo'], 2),
                    'horas_extra' => null,
                    'valor_hora_extra' => null,
                    'total_devengado' => round($brutoFinal, 2),
                    'total_deducciones_ss' => round($ss, 2),
                    'irpf_mensual' => round($irpfMensual, 2),
                    'irpf_porcentaje' => $porcentajeIRPF,
                    'liquido' => round($netoFinal, 2),
                    'coste_empresa' => round($costeEmpresa, 2),
                    'bruto_anual_estimado' => round($brutoAnualEstimado, 2),
                    'base_irpf_previa' => round($baseIRPF, 2),
                    'cuota_irpf_anual_sin_minimo' => round($this->calcularCuotaIRPF($brutoAnualEstimado - $ssAnual), 2),
                    'cuota_minimo_personal' => $minimos,
                    'cuota_irpf_anual' => round($cuotaIRPFAnual, 2),
                    'fecha' => Carbon::createFromDate($anio, $mes, 1)->toDateString(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Nóminas generadas correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Error generando nóminas.', 'error' => $e->getMessage()], 500)
                : redirect()->back()->with('error', 'Error generando nóminas: ' . $e->getMessage());
        }
    }

    private function calcularBruto(float $salario_base, array $pluses, float $minimo_pactado): float
    {
        $bruto = $salario_base + array_sum($pluses);

        return max($bruto, $minimo_pactado);
    }

    private function calcularNeto(float $bruto, float $irpf, float $ss): float
    {
        return $bruto - ($bruto * ($ss / 100)) - $irpf;
    }

    private function ajustarPlusesHastaMinimoLiquido(array &$pluses, float $salario_base, float $liquidoObjetivo, float $irpfEstimado, float $porcentajeSS): bool
    {
        $limites = [
            'actividad' => 300,
            'asistencia' => 300,
            'transporte' => 200,
            'dieta' => 200,
            'turnicidad' => 200,
        ];

        $maxIntentos = 1000;
        $intento = 0;
        $ajustado = false;

        do {
            $intento++;
            if ($intento > $maxIntentos) break;

            $bruto = $this->calcularBruto($salario_base, $pluses, $liquidoObjetivo);
            $brutoAnual = $bruto * 12;
            $ss = $bruto * ($porcentajeSS / 100);
            $ssAnual = $brutoAnual * ($porcentajeSS / 100);

            // Recalcular IRPF
            $baseIRPF = max($brutoAnual - $ssAnual - 5550, 0); // usará los mínimos al final
            $cuotaIRPF = $this->calcularCuotaIRPF($baseIRPF);
            $irpf = $cuotaIRPF / 12;

            // Calcular neto
            $neto = $this->calcularNeto($bruto, $irpf, $porcentajeSS);
            $diferencia = $liquidoObjetivo - $neto;

            // Comprobar si el neto es menor que el salario base (neto sin pluses)
            $netoBaseSinPluses = $salario_base - ($salario_base * ($porcentajeSS / 100)) - $irpfEstimado;
            if ($neto < $netoBaseSinPluses) {
                $ajustado = true;
            }

            if ($diferencia <= 1) break;

            foreach ($limites as $clave => $max) {
                if ($pluses[$clave] < $max) {
                    $pluses[$clave] += 1;
                    $ajustado = true;
                    break;
                }
            }
        } while ($diferencia > 0.5);

        return $ajustado;
    }


    private function ajustarBrutoParaProductividad(float $brutoBase, float $netoBase, float $liquidoObjetivo, float $irpf, float $ss): float
    {
        $bruto = $brutoBase;

        while (true) {
            $neto = $this->calcularNeto($bruto, $irpf, $ss);
            if ($neto >= $liquidoObjetivo) break;
            $bruto += 1;
            if ($bruto > 10000) break;
        }

        return round($bruto - $brutoBase, 2);
    }

    private function obtenerTotalCotizacionTrabajador(): float
    {
        return SeguridadSocial::where('aplica', 'trabajador')->sum('porcentaje');
    }

    private function obtenerTotalCotizacionEmpresa(): float
    {
        return SeguridadSocial::where('aplica', 'empresa')->sum('porcentaje');
    }

    public function calcularMinimosDesdeModelo(Modelo145 $modelo): float
    {
        $minimos = 5550;

        if ($modelo->edad >= 65) $minimos += 1150;
        if ($modelo->edad >= 75) $minimos += 1400;

        $hijos = $modelo->hijos_a_cargo;
        if ($hijos >= 1) $minimos += 2400;
        if ($hijos >= 2) $minimos += 2700;
        if ($hijos >= 3) $minimos += 4000;
        if ($hijos >= 4) $minimos += 4500;

        $minimos += $modelo->hijos_menores_3 * 2800;

        if ($modelo->ascendientes_mayores_65) $minimos += 1150;
        if ($modelo->ascendientes_mayores_75) $minimos += 1400;

        $grado = $modelo->discapacidad_porcentaje;
        if ($grado >= 33 && $grado < 66) $minimos += 3000;
        if ($grado >= 66) $minimos += 9000;

        if ($modelo->discapacidad_familiares) $minimos += 3000;

        return $minimos;
    }

    public function calcularCuotaIRPF(float $base): float
    {
        $cuota = 0;
        $tramos = [
            [0, 12450, 0.19],
            [12450, 20200, 0.24],
            [20200, 35200, 0.30],
            [35200, 60000, 0.37],
            [60000, 300000, 0.45],
            [300000, null, 0.47],
        ];

        foreach ($tramos as [$desde, $hasta, $porcentaje]) {
            if ($base > $desde) {
                $limite = $hasta ?? $base;
                $importe = min($base, $limite) - $desde;
                $cuota += $importe * $porcentaje;
            }
        }

        return $cuota;
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
        $aportacionesEmpresa = SeguridadSocial::where('aplica', 'empresa')->get();

        return view('nominas.show', compact('nomina', 'empresas', 'aportacionesEmpresa'));
    }
    // --------------------- SIMULACION NOMINA DESDE EL BRUTO
    public function formularioSimulacion()
    {
        return view('nominas.simulacion');
    }

    public function simular(Request $request)
    {
        $data = $request->all();

        $sueldoAnual = floatval($data['sueldo_bruto_anual']);
        $acumulado = floatval($data['acumulado_actual'] ?? 0);
        $mesActual = now()->month;

        // Seguridad Social aproximada (6,35 %)
        $ss = $sueldoAnual * 0.0635;

        // Calcular mínimos personales y familiares
        $minimos = 5550;
        $edad = intval($data['edad']);
        if ($edad >= 65) $minimos += 1150;
        if ($edad >= 75) $minimos += 1400;

        $minimos += min(intval($data['hijos_a_cargo']), 1) * 2400;
        if (intval($data['hijos_a_cargo']) >= 2) $minimos += 2700;
        if (intval($data['hijos_a_cargo']) >= 3) $minimos += 4000;
        if (intval($data['hijos_a_cargo']) >= 4) $minimos += 4500;

        $minimos += intval($data['hijos_menores_3']) * 2800;

        if ($request->boolean('ascendientes_mayores_65')) $minimos += 1150;
        if ($request->boolean('ascendientes_mayores_75')) $minimos += 1400;

        $discapacidad = intval($data['discapacidad_porcentaje']);
        if ($discapacidad >= 33 && $discapacidad < 66) $minimos += 3000;
        if ($discapacidad >= 66) $minimos += 9000;

        // Estimar el total ganado en el año
        $sueldoMensualBruto = $sueldoAnual / 12;
        $restoMeses = 12 - $mesActual;
        $estimadoRestoAno = $sueldoMensualBruto * $restoMeses;
        $estimacionAnual = $acumulado + $estimadoRestoAno;

        // Calcular SS e IRPF según estimación
        $ssEstimado = $estimacionAnual * 0.0635;
        $baseIrpf = max($estimacionAnual - $ssEstimado - $minimos, 0);

        $cuota = 0;
        $tramos = [
            [0, 12450, 0.19],
            [12450, 20200, 0.24],
            [20200, 35200, 0.30],
            [35200, 60000, 0.37],
            [60000, 300000, 0.45],
            [300000, null, 0.47],
        ];

        foreach ($tramos as [$desde, $hasta, $porcentaje]) {
            if ($baseIrpf > $desde) {
                $limite = $hasta ?? $baseIrpf;
                $importe = min($baseIrpf, $limite) - $desde;
                $cuota += $importe * $porcentaje;
            }
        }

        $retencionMensual = $cuota / 12;
        $ssMensual = $ss / 12;
        $netoMensual = $sueldoMensualBruto - $ssMensual - $retencionMensual;

        return response()->json([
            'bruto_mensual' => round($sueldoMensualBruto, 2),
            'ss_mensual' => round($ssMensual, 2),
            'retencion_mensual' => round($retencionMensual, 2),
            'neto_mensual' => round($netoMensual, 2),
            'mes_actual' => ucfirst(now()->locale('es')->monthName),
            'acumulado_bruto' => round($acumulado, 2),
        ]);
    }


    // --------------------- SIMULACION NOMINA DESDE EL NETO
    public function formularioInverso()
    {
        return view('nominas.simulacion_inversa');
    }

    public function simularDesdeNeto(Request $request)
    {
        $data = $request->all();

        $netoDeseado = floatval($data['neto_deseado']);
        $acumulado = floatval($data['acumulado_actual'] ?? 0);
        $mesActual = now()->month;

        // Datos personales
        $minimos = 5550;
        $edad = intval($data['edad']);
        if ($edad >= 65) $minimos += 1150;
        if ($edad >= 75) $minimos += 1400;

        $minimos += min(intval($data['hijos_a_cargo']), 1) * 2400;
        if (intval($data['hijos_a_cargo']) >= 2) $minimos += 2700;
        if (intval($data['hijos_a_cargo']) >= 3) $minimos += 4000;
        if (intval($data['hijos_a_cargo']) >= 4) $minimos += 4500;

        $minimos += intval($data['hijos_menores_3']) * 2800;

        if ($request->boolean('ascendientes_mayores_65')) $minimos += 1150;
        if ($request->boolean('ascendientes_mayores_75')) $minimos += 1400;

        $discapacidad = intval($data['discapacidad_porcentaje']);
        if ($discapacidad >= 33 && $discapacidad < 66) $minimos += 3000;
        if ($discapacidad >= 66) $minimos += 9000;

        // Simulación inversa
        $brutoAnual = 10000;
        $maxIntentos = 100;
        $errorAceptable = 1.00;

        while ($maxIntentos--) {
            $brutoMensual = $brutoAnual / 12;
            $estimadoBrutoRestante = $brutoMensual * (12 - $mesActual);
            $estimacionAnual = $acumulado + $estimadoBrutoRestante;

            $ss = $estimacionAnual * 0.0635;
            $baseIrpf = max($estimacionAnual - $ss - $minimos, 0);

            $cuota = 0;
            $tramos = [
                [0, 12450, 0.19],
                [12450, 20200, 0.24],
                [20200, 35200, 0.30],
                [35200, 60000, 0.37],
                [60000, 300000, 0.45],
                [300000, null, 0.47],
            ];

            foreach ($tramos as [$desde, $hasta, $porcentaje]) {
                if ($baseIrpf > $desde) {
                    $limite = $hasta ?? $baseIrpf;
                    $importe = min($baseIrpf, $limite) - $desde;
                    $cuota += $importe * $porcentaje;
                }
            }

            $netoMensualCalculado = ($brutoMensual - ($ss / 12) - ($cuota / 12));

            if (abs($netoMensualCalculado - $netoDeseado) <= $errorAceptable) {
                break;
            }

            $brutoAnual += ($netoDeseado - $netoMensualCalculado) * 20;
        }

        return response()->json([
            'bruto_anual' => round($brutoAnual, 2),
            'bruto_mensual' => round($brutoAnual / 12, 2),
            'ss_mensual' => round($ss / 12, 2),
            'retencion_mensual' => round($cuota / 12, 2),
            'neto_mensual' => round($netoMensualCalculado, 2),
            'mes_actual' => ucfirst(now()->locale('es')->monthName),
            'acumulado_bruto' => round($acumulado, 2),
        ]);
    }
}
