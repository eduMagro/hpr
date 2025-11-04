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
        Log::channel('planilla_import')->info("🎯 [AsignarMaquina] Iniciando reparto de planilla {$planillaId}");

        $planilla = Planilla::findOrFail($planillaId);

        $elementos = Elemento::where('planilla_id', $planillaId)
            ->whereNull('maquina_id')
            ->get();

        Log::channel('planilla_import')->info("📊 [AsignarMaquina] Planilla {$planillaId}: {$elementos->count()} elementos sin máquina asignada");

        if ($elementos->isEmpty()) {
            Log::channel('planilla_import')->info("✓ [AsignarMaquina] Planilla {$planillaId}: no hay elementos por asignar");
            return;
        }

        // Clasificar elementos
        $estribos = $elementos->filter(
            fn($e) => (int)$e->dobles_barra >= 4 && (int)$e->diametro <= 16
        );

<<<<<<< Updated upstream
        $grupos = [
            // Solo elementos con dobles >= 4 Y diÃ¡metro <= 16 son "estribos"
            'estribos' => $estribos,
            // âœ… CORREGIDO: Resto = TODOS los que NO son estribos
            // (incluye dobles >= 4 con diÃ¡metro > 16)
            'resto' => $elementos->reject(fn($e) => $estribos->contains($e)),
        ];

        Log::channel('planilla_import')->info("📋 [AsignarMaquina] Planilla {$planillaId} - Clasificación: {$grupos['estribos']->count()} estribos, {$grupos['resto']->count()} resto");

        // Obtener máquinas disponibles
=======
>>>>>>> Stashed changes
        $maquinas = Maquina::naveA()->get()->keyBy('id');
        Log::channel('planilla_import')->debug("🏭 [AsignarMaquina] Máquinas disponibles en Nave A: {$maquinas->count()}");

        // Calcular cargas actuales
        $cargas = $this->cargasPendientesPorMaquina();

        // 📦 PASO 1: Elementos sin elaboración → Syntax Line 28
        $sinElaborar = $elementos->filter(fn($e) => (int)($e->elaborado ?? 1) === 0);
        $syntaxLine = $maquinas->first(fn($m) => $m->codigo === 'SL28');

        if ($sinElaborar->isNotEmpty()) {
            if (!$syntaxLine) {
                Log::warning("⚠️ Syntax Line 28 no disponible para elementos sin elaborar en planilla {$planilla->id}");
            } else {
                foreach ($sinElaborar as $e) {
                    $e->maquina_id = $syntaxLine->id;
                    $e->save();
                    $this->sumarCarga($cargas, $syntaxLine->id, (float)$e->peso);
                }
                // Log::info("📦 {$sinElaborar->count()} elementos sin elaborar → Syntax Line 28");
            }
        }

        // 🔧 PASO 2: Elementos que SÍ requieren elaboración (elaborado = 1)
        $elementosAElaborar = $elementos->reject(fn($e) => (int)($e->elaborado ?? 1) === 0);

        if ($elementosAElaborar->isEmpty()) return;

        $grupos = [
            'estribos' => $elementosAElaborar->filter(fn($e) => (int)$e->dobles_barra >= 4),
            'resto'    => $elementosAElaborar->reject(fn($e) => (int)$e->dobles_barra >= 4),
        ];

        // ⚙️ Cortadoras automáticas (excluye CM si su tipo no es 'cortadora_dobladora')
        $cortadoras = $maquinas->filter(fn($m) => $m->tipo === 'cortadora_dobladora');
        Log::channel('planilla_import')->debug("⚙️ [AsignarMaquina] Cortadoras automáticas disponibles: {$cortadoras->count()} - IDs: " . json_encode($cortadoras->pluck('id')->toArray()));

        // 🪚 Cortadora manual por código
        $cortadoraManual = $maquinas->first(fn($m) => $m->codigo === 'CM');
        if ($cortadoraManual) {
            Log::channel('planilla_import')->debug("🪚 [AsignarMaquina] Cortadora manual CM encontrada: ID {$cortadoraManual->id}");
        } else {
            Log::channel('planilla_import')->debug("⚠️ [AsignarMaquina] Cortadora manual CM no encontrada");
        }

        // Procesar estribos
        if ($grupos['estribos']->isNotEmpty()) {
            $diametrosEstribos = $grupos['estribos']->pluck('diametro')->unique()->map(fn($d) => (int)$d);
            Log::channel('planilla_import')->info("🔩 [AsignarMaquina] Procesando estribos - Diámetros presentes: " . json_encode($diametrosEstribos->toArray()));

            $codigosBase = ['F12', 'PS12'];
            if ($diametrosEstribos->max() >= 16) {
                $codigosBase[] = 'MS16';
                Log::channel('planilla_import')->debug("➕ [AsignarMaquina] Agregando MS16 por diámetro >= 16");
            }

            $candidatasEstribos = $maquinas->filter(fn($m) => in_array($m->codigo, $codigosBase));
            Log::channel('planilla_import')->debug("🎯 [AsignarMaquina] Candidatas para estribos (códigos: " . implode(', ', $codigosBase) . "): {$candidatasEstribos->count()} máquinas");

            $this->repartirEstribos($planilla, $grupos['estribos'], $candidatasEstribos, $cargas);
        }

        // Procesar resto
        if ($grupos['resto']->isNotEmpty()) {
            $this->repartirResto($planilla, $grupos['resto'], $cortadoras, $cargas, $cortadoraManual);
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina] Reparto completado para planilla {$planillaId}");
    }

    protected function repartirEstribos(Planilla $planilla, $estribos, $candidatas, &$cargas): void
    {
        Log::channel('planilla_import')->info("🔩 [AsignarMaquina] Iniciando reparto de {$estribos->count()} estribos para planilla {$planilla->id}");

        if ($estribos->isEmpty()) {
            Log::channel('planilla_import')->debug("ℹ️ [AsignarMaquina] No hay estribos para repartir");
            return;
        }

        $diametros = $estribos->pluck('diametro')->unique()->map(fn($d) => (int)$d);
        Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Diámetros en estribos: " . json_encode($diametros->toArray()));

        // Buscar una máquina única que soporte todos los diámetros
        $candidataUnica = null;
        foreach ($candidatas->groupBy('codigo') as $codigo => $grupo) {
            $soportaTodos = $diametros->every(fn($d) => $grupo->contains(fn($m) => $this->soportaDiametro($m, $d)));
            if ($soportaTodos) {
                $candidataUnica = $codigo;
                Log::channel('planilla_import')->info("🎯 [AsignarMaquina] Máquina única encontrada: {$codigo} soporta todos los diámetros");
                break;
            }
        }

        if ($candidataUnica) {
            Log::channel('planilla_import')->info("✓ [AsignarMaquina] Asignando todos los estribos a máquinas con código {$candidataUnica}");
            $asignados = 0;

            foreach ($estribos as $e) {
                $m = $this->mejorMaquinaPorCodigoYDiametro($candidatas, $candidataUnica, (int)$e->diametro, $cargas);
                if ($m) {
                    $e->maquina_id = $m->id;
                    $e->save();
                    $this->sumarCarga($cargas, $m->id, (float)$e->peso);
                    $asignados++;
                    Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Elemento {$e->id} (Ø{$e->diametro}, {$e->peso}kg) → Máquina {$m->id} ({$m->codigo})");
                } else {
                    Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Estribo sin candidata ({$candidataUnica}) Ø{$e->diametro} en planilla {$planilla->id}");
                }
            }

            Log::channel('planilla_import')->info("✅ [AsignarMaquina] Estribos asignados: {$asignados} de {$estribos->count()}");
            return;
        }

        // No hay máquina única, asignar individualmente
        Log::channel('planilla_import')->info("🔀 [AsignarMaquina] No hay máquina única, asignando estribos individualmente");
        $asignados = 0;

        foreach ($estribos as $e) {
            $m = $this->mejorMaquinaPorCodigoYDiametro($candidatas, null, (int)$e->diametro, $cargas);
            if ($m) {
                $e->maquina_id = $m->id;
                $e->save();
                $this->sumarCarga($cargas, $m->id, (float)$e->peso);
                $asignados++;
                Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Elemento {$e->id} (Ø{$e->diametro}, {$e->peso}kg) → Máquina {$m->id} ({$m->codigo})");
            } else {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Estribo sin máquina compatible Ø{$e->diametro} planilla {$planilla->id}");
            }
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina] Estribos asignados individualmente: {$asignados} de {$estribos->count()}");
    }

    protected function repartirResto(
        Planilla $planilla,
        $resto,
        $cortadoras,
        array &$cargas,
        ?Maquina $cortadoraManual = null
    ): void {
        Log::channel('planilla_import')->info("🔧 [AsignarMaquina] Iniciando reparto de {$resto->count()} elementos 'resto' para planilla {$planilla->id}");

        if ($resto->isEmpty()) {
            Log::channel('planilla_import')->debug("ℹ️ [AsignarMaquina] No hay elementos 'resto' para repartir");
            return;
        }

        // 🎯 Primero, enviar a CM: Ø32 y dobles_barra = 0
        $vaParaCM = $resto->filter(fn($e) => (int)$e->diametro === 32 && (int)$e->dobles_barra === 0);

        if ($vaParaCM->isNotEmpty()) {
            Log::channel('planilla_import')->info("🪚 [AsignarMaquina] Detectados {$vaParaCM->count()} elementos para CM (Ø32 con dobles_barra=0)");

            if (!$cortadoraManual) {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] CM no disponible para {$vaParaCM->count()} elementos Ø32 con dobles_barra=0 en planilla {$planilla->id}");
                foreach ($vaParaCM as $e) {
                    Log::channel('planilla_import')->warning("   ❌ Elemento {$e->id} sin asignar (requiere CM)");
                }
            } else {
                $asignadosCM = 0;
                foreach ($vaParaCM as $e) {
                    $e->maquina_id = $cortadoraManual->id;
                    $e->save();
                    $this->sumarCarga($cargas, $cortadoraManual->id, (float)$e->peso);
                    $asignadosCM++;
                    Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Elemento {$e->id} (Ø32, {$e->peso}kg, dobles=0) → CM (ID {$cortadoraManual->id})");
                }
                Log::channel('planilla_import')->info("✅ [AsignarMaquina] Asignados {$asignadosCM} elementos a CM");
            }

            // El resto continúa por el flujo normal
            $resto = $resto->reject(fn($e) => (int)$e->diametro === 32 && (int)$e->dobles_barra === 0);
            Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Elementos restantes después de CM: {$resto->count()}");

            if ($resto->isEmpty()) {
                Log::channel('planilla_import')->info("✓ [AsignarMaquina] Todos los elementos 'resto' fueron asignados a CM");
                return;
            }
        }

        // 🧠 Lógica existente para repartir el resto entre cortadoras automáticas
        $diametros = $resto->pluck('diametro')->unique()->map(fn($d) => (int)$d);
        Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Diámetros en resto (excl. CM): " . json_encode($diametros->toArray()));

        $maquinaUnica = $cortadoras->first(fn($m) => $diametros->every(fn($d) => $this->soportaDiametro($m, $d)));

        if ($maquinaUnica) {
            Log::channel('planilla_import')->info("🎯 [AsignarMaquina] Máquina única encontrada: ID {$maquinaUnica->id} ({$maquinaUnica->codigo}) soporta todos los diámetros del resto");
            $asignados = 0;

            foreach ($resto as $e) {
                $e->maquina_id = $maquinaUnica->id;
                $e->save();
                $this->sumarCarga($cargas, $maquinaUnica->id, (float)$e->peso);
                $asignados++;
                Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Elemento {$e->id} (Ø{$e->diametro}, {$e->peso}kg) → Máquina {$maquinaUnica->id}");
            }

            Log::channel('planilla_import')->info("✅ [AsignarMaquina] Todos los elementos del resto asignados a máquina única: {$asignados} elementos");
            return;
        }

        // No hay máquina única, asignar individualmente
        Log::channel('planilla_import')->info("🔀 [AsignarMaquina] No hay máquina única para el resto, asignando individualmente");
        $asignados = 0;

        foreach ($resto as $e) {
            $m = $this->mejorMaquinaCompatible($cortadoras, (int)$e->diametro, $cargas);
            if ($m) {
                $e->maquina_id = $m->id;
                $e->save();
                $this->sumarCarga($cargas, $m->id, (float)$e->peso);
                $asignados++;
                Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Elemento {$e->id} (Ø{$e->diametro}, {$e->peso}kg) → Máquina {$m->id} ({$m->codigo})");
            } else {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Sin cortadora_dobladora compatible para elemento {$e->id} Ø{$e->diametro} planilla {$planilla->id}");
            }
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina] Elementos del resto asignados individualmente: {$asignados} de {$resto->count()}");
    }

    protected function mejorMaquinaPorCodigoYDiametro($candidatas, ?string $codigoPreferido, int $diametro, array $cargas)
    {
        Log::channel('planilla_import')->debug("🔍 [AsignarMaquina] Buscando mejor máquina" . ($codigoPreferido ? " con código {$codigoPreferido}" : "") . " para Ø{$diametro}");

        $pool = $codigoPreferido ? $candidatas->where('codigo', $codigoPreferido) : $candidatas;
        $pool = $pool->filter(fn($m) => $this->soportaDiametro($m, $diametro));

        if ($pool->isEmpty()) {
            Log::channel('planilla_import')->debug("❌ [AsignarMaquina] No hay máquinas compatibles para Ø{$diametro}" . ($codigoPreferido ? " con código {$codigoPreferido}" : ""));
            return null;
        }

        $mejor = $this->menosCargada($pool, $cargas);

        if ($mejor) {
            $carga = $cargas[$mejor->id] ?? ['kilos' => 0.0, 'num' => 0];
            Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Seleccionada máquina {$mejor->id} ({$mejor->codigo}) - Carga actual: {$carga['kilos']}kg, {$carga['num']} elementos");
        }

        return $mejor;
    }

    protected function mejorMaquinaCompatible($candidatas, int $diametro, array $cargas)
    {
        Log::channel('planilla_import')->debug("🔍 [AsignarMaquina] Buscando mejor máquina compatible para Ø{$diametro}");

        $pool = $candidatas->filter(fn($m) => $this->soportaDiametro($m, $diametro));

        if ($pool->isEmpty()) {
            Log::channel('planilla_import')->debug("❌ [AsignarMaquina] No hay máquinas compatibles para Ø{$diametro}");
            return null;
        }

        $mejor = $this->menosCargada($pool, $cargas);

        if ($mejor) {
            $carga = $cargas[$mejor->id] ?? ['kilos' => 0.0, 'num' => 0];
            Log::channel('planilla_import')->debug("✓ [AsignarMaquina] Seleccionada máquina {$mejor->id} ({$mejor->codigo}) - Carga actual: {$carga['kilos']}kg, {$carga['num']} elementos");
        }

        return $mejor;
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
        $soporta = $minOk && $maxOk;

        Log::channel('planilla_import')->debug("🔧 [AsignarMaquina] Máquina {$m->id} ({$m->codigo}) " . ($soporta ? "✓ soporta" : "✗ NO soporta") . " Ø{$diametro} (rango: {$m->diametro_min}-{$m->diametro_max})");

        return $soporta;
    }

    protected function cargasPendientesPorMaquina(): array
    {
        Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Calculando cargas pendientes por máquina");

        $cargas = Elemento::selectRaw('maquina_id, COALESCE(SUM(peso),0) as kilos, COUNT(*) as num')
            ->whereNotNull('maquina_id')
            ->where('estado', 'pendiente')
            ->groupBy('maquina_id')
            ->get()
            ->mapWithKeys(fn($r) => [
                (int)$r->maquina_id => ['kilos' => (float)$r->kilos, 'num' => (int)$r->num]
            ])
            ->toArray();

        $totalMaquinas = count($cargas);
        $totalKilos = array_sum(array_column($cargas, 'kilos'));
        $totalElementos = array_sum(array_column($cargas, 'num'));

        Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Cargas calculadas: {$totalMaquinas} máquinas con carga, {$totalKilos}kg total, {$totalElementos} elementos pendientes");

        return $cargas;
    }

    protected function sumarCarga(array &$cargas, int $maquinaId, float $kilos): void
    {
        if (!isset($cargas[$maquinaId])) {
            $cargas[$maquinaId] = ['kilos' => 0.0, 'num' => 0];
        }

        $cargas[$maquinaId]['kilos'] += $kilos;
        $cargas[$maquinaId]['num'] += 1;

        Log::channel('planilla_import')->debug("➕ [AsignarMaquina] Máquina {$maquinaId}: carga actualizada → {$cargas[$maquinaId]['kilos']}kg, {$cargas[$maquinaId]['num']} elementos");
    }
}
