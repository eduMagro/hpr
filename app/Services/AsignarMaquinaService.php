<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Maquina;
use App\Models\Elemento;
use App\Models\Planilla;

class AsignarMaquinaService
{
    public function repartirPlanilla(int $planillaId): void
    {
        $planilla = Planilla::findOrFail($planillaId);

        $elementos = Elemento::where('planilla_id', $planillaId)
            ->whereNull('maquina_id')
            ->get();

        // Log::info("🔄 Repartiendo planilla {$planilla->id}, elementos sin máquina: {$elementos->count()}");
        if ($elementos->isEmpty()) return;

        $grupos = [
            'estribos' => $elementos->filter(fn($e) => (int)$e->dobles_barra >= 4),
            'resto'    => $elementos->reject(fn($e) => (int)$e->dobles_barra >= 4),
        ];

        $maquinas = Maquina::naveA()->get()->keyBy('id');
        $cargas   = $this->cargasPendientesPorMaquina();

        // ⚙️ Cortadoras automáticas (excluye CM si su tipo no es 'cortadora_dobladora')
        $cortadoras = $maquinas->filter(fn($m) => $m->tipo === 'cortadora_dobladora');

        // 🪚 Cortadora manual por código (ajusta si en tu BD se identifica de otra forma)
        $cortadoraManual = $maquinas->first(fn($m) => $m->codigo === 'CM');

        // Estribos (igual que ya tenías)
        $diametrosEstribos = $grupos['estribos']->pluck('diametro')->unique()->map(fn($d) => (int)$d);
        $codigosBase = ['F12', 'PS12'];
        if ($diametrosEstribos->max() >= 16) $codigosBase[] = 'MS16';
        $candidatasEstribos = $maquinas->filter(fn($m) => in_array($m->codigo, $codigosBase));

        $this->repartirEstribos($planilla, $grupos['estribos'], $candidatasEstribos, $cargas);

        // 👉 Resto con regla especial para CM (Ø32 + dobles_barra = 0)
        $this->repartirResto($planilla, $grupos['resto'], $cortadoras, $cargas, $cortadoraManual);
    }

    protected function repartirEstribos(Planilla $planilla, $estribos, $candidatas, &$cargas): void
    {
        if ($estribos->isEmpty()) return;

        $diametros = $estribos->pluck('diametro')->unique()->map(fn($d) => (int)$d);

        $candidataUnica = null;
        foreach ($candidatas->groupBy('codigo') as $codigo => $grupo) {
            $soportaTodos = $diametros->every(fn($d) => $grupo->contains(fn($m) => $this->soportaDiametro($m, $d)));
            if ($soportaTodos) {
                $candidataUnica = $codigo;
                break;
            }
        }

        if ($candidataUnica) {
            foreach ($estribos as $e) {
                $m = $this->mejorMaquinaPorCodigoYDiametro($candidatas, $candidataUnica, (int)$e->diametro, $cargas);
                if ($m) {
                    $e->maquina_id = $m->id;
                    $e->save();
                    $this->sumarCarga($cargas, $m->id, (float)$e->peso);
                } else {
                    Log::warning("Estribo sin candidata ({$candidataUnica}) Ø{$e->diametro} en planilla {$planilla->id}");
                }
            }
            return;
        }

        foreach ($estribos as $e) {
            $m = $this->mejorMaquinaPorCodigoYDiametro($candidatas, null, (int)$e->diametro, $cargas);
            if ($m) {
                $e->maquina_id = $m->id;
                $e->save();
                $this->sumarCarga($cargas, $m->id, (float)$e->peso);
            } else {
                Log::warning("Estribo sin máquina compatible Ø{$e->diametro} planilla {$planilla->id}");
            }
        }
    }

    protected function repartirResto(
        Planilla $planilla,
        $resto,
        $cortadoras,
        array &$cargas,
        ?Maquina $cortadoraManual = null
    ): void {
        if ($resto->isEmpty()) return;

        // 🎯 Primero, enviar a CM: Ø32 y dobles_barra = 0
        $vaParaCM = $resto->filter(fn($e) => (int)$e->diametro === 32 && (int)$e->dobles_barra === 0);

        if ($vaParaCM->isNotEmpty()) {
            if (!$cortadoraManual) {
                foreach ($vaParaCM as $e) {
                    Log::warning("CM no disponible para Ø32 con dobles_barra=0 (Elemento {$e->id}) en planilla {$planilla->id}");
                }
            } else {
                foreach ($vaParaCM as $e) {
                    $e->maquina_id = $cortadoraManual->id;
                    $e->save();
                    $this->sumarCarga($cargas, $cortadoraManual->id, (float)$e->peso);
                }
            }

            // El resto continúa por el flujo normal
            $resto = $resto->reject(fn($e) => (int)$e->diametro === 32 && (int)$e->dobles_barra === 0);
            if ($resto->isEmpty()) return;
        }

        // 🧠 Lógica existente para repartir el resto entre cortadoras automáticas
        $diametros = $resto->pluck('diametro')->unique()->map(fn($d) => (int)$d);
        $maquinaUnica = $cortadoras->first(fn($m) => $diametros->every(fn($d) => $this->soportaDiametro($m, $d)));

        if ($maquinaUnica) {
            foreach ($resto as $e) {
                $e->maquina_id = $maquinaUnica->id;
                $e->save();
                $this->sumarCarga($cargas, $maquinaUnica->id, (float)$e->peso);
            }
            return;
        }

        foreach ($resto as $e) {
            $m = $this->mejorMaquinaCompatible($cortadoras, (int)$e->diametro, $cargas);
            if ($m) {
                $e->maquina_id = $m->id;
                $e->save();
                $this->sumarCarga($cargas, $m->id, (float)$e->peso);
            } else {
                Log::warning("Sin cortadora_dobladora compatible Ø{$e->diametro} planilla {$planilla->id}");
            }
        }
    }


    protected function mejorMaquinaPorCodigoYDiametro($candidatas, ?string $codigoPreferido, int $diametro, array $cargas)
    {
        $pool = $codigoPreferido ? $candidatas->where('codigo', $codigoPreferido) : $candidatas;
        $pool = $pool->filter(fn($m) => $this->soportaDiametro($m, $diametro));

        return $pool->isEmpty() ? null : $this->menosCargada($pool, $cargas);
    }

    protected function mejorMaquinaCompatible($candidatas, int $diametro, array $cargas)
    {
        $pool = $candidatas->filter(fn($m) => $this->soportaDiametro($m, $diametro));
        return $pool->isEmpty() ? null : $this->menosCargada($pool, $cargas);
    }

    protected function menosCargada($pool, array $cargas)
    {
        $mejor = null;
        $mejorIdx = INF;

        foreach ($pool as $m) {
            $c = $cargas[$m->id] ?? ['kilos' => 0.0, 'num' => 0];
            $idx = ($c['kilos'] * 0.7) + ($c['num'] * 0.3);
            if ($idx < $mejorIdx) {
                $mejorIdx = $idx;
                $mejor = $m;
            }
        }

        return $mejor;
    }

    protected function soportaDiametro(Maquina $m, int $diametro): bool
    {
        $minOk = is_null($m->diametro_min) || $diametro >= (int)$m->diametro_min;
        $maxOk = is_null($m->diametro_max) || $diametro <= (int)$m->diametro_max;
        return $minOk && $maxOk;
    }

    protected function cargasPendientesPorMaquina(): array
    {
        return Elemento::selectRaw('maquina_id, COALESCE(SUM(peso),0) as kilos, COUNT(*) as num')
            ->whereNotNull('maquina_id')
            ->where('estado', 'pendiente')
            ->groupBy('maquina_id')
            ->get()
            ->mapWithKeys(fn($r) => [
                (int)$r->maquina_id => ['kilos' => (float)$r->kilos, 'num' => (int)$r->num]
            ])
            ->toArray();
    }

    protected function sumarCarga(array &$cargas, int $maquinaId, float $kilos): void
    {
        if (!isset($cargas[$maquinaId])) {
            $cargas[$maquinaId] = ['kilos' => 0.0, 'num' => 0];
        }
        $cargas[$maquinaId]['kilos'] += $kilos;
        $cargas[$maquinaId]['num']   += 1;
    }
}
