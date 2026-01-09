<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Maquina;
use App\Models\Elemento;
use App\Models\Planilla;
use App\Models\ProductoBase;

class AsignarMaquinaService
{
    /**
     * Longitudes de productos base válidas para grúa (en mm).
     * Excluye 6m porque requiere corte en la mayoría de casos.
     */
    protected const LONGITUDES_GRUA_MM = [12000, 14000, 15000, 16000];

    /**
     * Tolerancia en mm para comparar longitudes.
     */
    protected const TOLERANCIA_LONGITUD_MM = 10;

    /**
     * Determina si un elemento debe ir a la grúa (no requiere elaboración).
     *
     * Va a grúa si:
     * - dobles_barra = 0 (barra recta)
     * - longitud coincide con producto base (12, 14, 15, 16m), excluyendo 6m
     *
     * @param Elemento $elemento
     * @return bool true si debe ir a grúa
     */
    protected function debeIrAGrua(Elemento $elemento): bool
    {
        $dobles = (int)$elemento->dobles_barra;
        $longitud = (float)$elemento->longitud;

        // Si tiene dobleces, no va a grúa
        if ($dobles > 0) {
            return false;
        }

        // Verificar si la longitud coincide con alguna longitud base para grúa
        foreach (self::LONGITUDES_GRUA_MM as $longitudBase) {
            if (abs($longitud - $longitudBase) <= self::TOLERANCIA_LONGITUD_MM) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza las dimensiones para comparación consistente.
     * Mismo método que usa ResumenEtiquetaService para garantizar consistencia.
     *
     * @param string|null $dimensiones
     * @return string
     */
    protected function normalizarDimensiones(?string $dimensiones): string
    {
        if (empty($dimensiones)) {
            return 'barra';
        }

        // Normalizar: minúsculas, quitar espacios múltiples, trim
        $normalizado = mb_strtolower(trim($dimensiones));
        $normalizado = preg_replace('/\s+/', ' ', $normalizado);

        return $normalizado;
    }

    /**
     * Agrupa elementos por diámetro + dimensiones normalizadas.
     * Esto permite que elementos susceptibles de resumen vayan a la misma máquina.
     *
     * @param \Illuminate\Support\Collection $elementos
     * @return array Array de grupos, cada grupo contiene elementos con mismo diámetro+dimensiones
     */
    protected function agruparPorResumen($elementos): array
    {
        $grupos = [];

        foreach ($elementos as $elemento) {
            $diametro = (int)$elemento->diametro;
            $dimensiones = $this->normalizarDimensiones($elemento->dimensiones);
            $key = "{$diametro}|{$dimensiones}";

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'diametro' => $diametro,
                    'dimensiones' => $dimensiones,
                    'dimensiones_original' => $elemento->dimensiones,
                    'elementos' => collect(),
                ];
            }

            $grupos[$key]['elementos']->push($elemento);
        }

        // Ordenar grupos por peso total descendente para mejor balanceo
        uasort($grupos, function ($a, $b) {
            $pesoA = $a['elementos']->sum(fn($e) => (float)$e->peso);
            $pesoB = $b['elementos']->sum(fn($e) => (float)$e->peso);
            return $pesoB <=> $pesoA;
        });

        return $grupos;
    }

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

        // Detectar si es "ensamblado taller" para usar Nave B
        $esEnsambladoTaller = $this->esEnsambladoTaller($planilla);

        if ($esEnsambladoTaller) {
            Log::channel('planilla_import')->info("🏭 [AsignarMaquina] Planilla {$planillaId}: ENSAMBLADO TALLER detectado → Asignando a Nave B");
            $this->repartirEnNaveB($planilla, $elementos);
            return;
        }

        // Lógica normal para Nave A
        Log::channel('planilla_import')->info("🏭 [AsignarMaquina] Planilla {$planillaId}: Asignando a Nave A (normal)");

        // Clasificar elementos
        $estribos = $elementos->filter(
            fn($e) => (int)$e->dobles_barra >= 4 && (int)$e->diametro <= 16
        );


        $grupos = [
            // Solo elementos con dobles >= 4 Y diámetro <= 16 son "estribos"
            'estribos' => $estribos,
            // Resto = TODOS los que NO son estribos (incluye dobles >= 4 con diámetro > 16)
            'resto' => $elementos->reject(fn($e) => $estribos->contains($e)),
        ];

        Log::channel('planilla_import')->info("📋 [AsignarMaquina] Planilla {$planillaId} - Clasificación: {$grupos['estribos']->count()} estribos, {$grupos['resto']->count()} resto");

        // Obtener máquinas disponibles (solo activas)

        $maquinas = Maquina::naveA()
            ->where(function ($query) {
                $query->where('estado', 'activa')
                    ->orWhereNull('estado');
            })
            ->get()
            ->keyBy('id');
        Log::channel('planilla_import')->debug("🏭 [AsignarMaquina] Máquinas activas disponibles en Nave A: {$maquinas->count()}");

        // Calcular cargas actuales
        $cargas = $this->cargasPendientesPorMaquina();

        // 📦 PASO 1: Elementos para grúa (barra recta con longitud de producto base, excluye 6m)
        $paraGrua = $elementos->filter(fn($e) => $this->debeIrAGrua($e));

        if ($paraGrua->isNotEmpty()) {
            Log::channel('planilla_import')->info("🏗️ [AsignarMaquina] {$paraGrua->count()} elementos van a grúa (longitud 12/14/15/16m, sin dobleces)");
            $this->asignarElementosAGrua($planilla, $paraGrua, 'A', $cargas);
        }

        // 🔧 PASO 2: Elementos que SÍ requieren elaboración (corte/doblado)
        $elementosAElaborar = $elementos->reject(fn($e) => $this->debeIrAGrua($e));

        if ($elementosAElaborar->isEmpty()) return;

        $grupos = [
            'estribos' => $elementosAElaborar->filter(fn($e) => (int)$e->dobles_barra >= 4),
            'resto'    => $elementosAElaborar->reject(fn($e) => (int)$e->dobles_barra >= 4),
        ];

        // 🪚 Cortadora manual por código (buscar primero para excluirla de cortadoras automáticas)
        $cortadoraManual = $maquinas->first(fn($m) => $m->codigo === 'CM');
        if ($cortadoraManual) {
            Log::channel('planilla_import')->info("🪚 [AsignarMaquina] Cortadora manual CM encontrada: ID {$cortadoraManual->id} - SOLO recibirá elementos con dobles_barra=0");
        } else {
            Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Cortadora manual CM no encontrada");
        }

        // ⚙️ Cortadoras automáticas (EXCLUIR EXPLÍCITAMENTE LA CM)
        $cortadoras = $maquinas->filter(function ($m) use ($cortadoraManual) {
            // Solo tipo cortadora_dobladora Y que NO sea la cortadora manual CM
            return $m->tipo === 'cortadora_dobladora' && (!$cortadoraManual || $m->id !== $cortadoraManual->id);
        });
        Log::channel('planilla_import')->info("⚙️ [AsignarMaquina] Cortadoras automáticas (sin CM): {$cortadoras->count()} máquinas - Códigos: " . json_encode($cortadoras->pluck('codigo')->toArray()));

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

        // Mostrar resumen de balanceo final
        $this->mostrarResumenBalanceo($cargas, $maquinas);

        Log::channel('planilla_import')->info("✅ [AsignarMaquina] Reparto completado para planilla {$planillaId}");
    }

    protected function repartirEstribos(Planilla $planilla, $estribos, $candidatas, &$cargas): void
    {
        Log::channel('planilla_import')->info("🔩 [AsignarMaquina] Iniciando reparto de {$estribos->count()} estribos para planilla {$planilla->id}");

        if ($estribos->isEmpty()) {
            Log::channel('planilla_import')->debug("ℹ️ [AsignarMaquina] No hay estribos para repartir");
            return;
        }

        $pesoTotal = $estribos->sum(fn($e) => (float)$e->peso);
        Log::channel('planilla_import')->info("⚖️ [Balanceo] Total estribos: {$estribos->count()} elementos, {$pesoTotal}kg");

        // 🎯 AGRUPAR POR RESUMEN: elementos con mismo diámetro+dimensiones van a la misma máquina
        $gruposResumen = $this->agruparPorResumen($estribos);
        $totalGrupos = count($gruposResumen);
        $gruposMultiples = collect($gruposResumen)->filter(fn($g) => $g['elementos']->count() > 1)->count();

        Log::channel('planilla_import')->info("📦 [RESUMEN] Estribos agrupados en {$totalGrupos} grupos por Ø+dimensiones ({$gruposMultiples} grupos con múltiples elementos)");

        foreach ($gruposResumen as $key => $grupo) {
            $elementos = $grupo['elementos'];
            $diametro = $grupo['diametro'];
            $dimensiones = $grupo['dimensiones_original'] ?: 'barra';
            $pesoGrupo = $elementos->sum(fn($e) => (float)$e->peso);

            Log::channel('planilla_import')->info("📦 [RESUMEN] Grupo '{$key}': Ø{$diametro}, dim='{$dimensiones}', {$elementos->count()} elem, {$pesoGrupo}kg");

            // Buscar máquina compatible para este grupo (todos tienen mismo diámetro)
            $poolCandidatas = $candidatas->filter(fn($m) => $this->soportaDiametro($m, $diametro));

            if ($poolCandidatas->isEmpty()) {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Sin máquina compatible para grupo Ø{$diametro} en planilla {$planilla->id}");
                continue;
            }

            // Seleccionar la menos cargada para TODO el grupo
            $maquinaDestino = $this->menosCargada($poolCandidatas, $cargas);

            if (!$maquinaDestino) {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] No se pudo seleccionar máquina para grupo Ø{$diametro}");
                continue;
            }

            Log::channel('planilla_import')->info("🎯 [RESUMEN] Grupo '{$key}' → Máquina {$maquinaDestino->id} ({$maquinaDestino->codigo}) - {$elementos->count()} elementos a misma máquina");

            // Asignar TODOS los elementos del grupo a la MISMA máquina
            $asignados = 0;
            foreach ($elementos as $e) {
                // VALIDACIÓN: No permitir asignar a CM si no cumple requisitos
                if (!$this->puedeIrACM($e, $maquinaDestino)) {
                    Log::channel('planilla_import')->error("⚠️ [AsignarMaquina] Estribo {$e->id} BLOQUEADO para {$maquinaDestino->codigo} (validación CM fallida)");
                    continue;
                }

                $e->maquina_id = $maquinaDestino->id;
                $e->save();
                $this->sumarCarga($cargas, $maquinaDestino->id, (float)$e->peso, (int)($e->tiempo_fabricacion ?? 0));
                $asignados++;
            }

            Log::channel('planilla_import')->debug("✓ [RESUMEN] Grupo '{$key}': {$asignados} de {$elementos->count()} estribos asignados a {$maquinaDestino->codigo}");
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina] Estribos repartidos por grupos de resumen: {$totalGrupos} grupos procesados");
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

        // Log de diagnóstico: mostrar distribución de dobles_barra en el resto
        $distribucionDobles = $resto->groupBy(fn($e) => (int)$e->dobles_barra)->map->count();
        Log::channel('planilla_import')->debug("🔍 [AsignarMaquina] Distribución dobles_barra en resto: " . json_encode($distribucionDobles->toArray()));

        // 🧠 Incluir CM en el pool de máquinas disponibles para elementos rectos
        $todasMaquinas = $cortadoras->toBase();
        if ($cortadoraManual) {
            $todasMaquinas = $todasMaquinas->push($cortadoraManual);
            Log::channel('planilla_import')->info("🪚 [AsignarMaquina] CM incluida en pool de balanceo para elementos con dobles_barra=0");
        }

        // Separar elementos rectos (dobles=0) de elementos con dobleces
        $elementosRectos = $resto->filter(fn($e) => (int)$e->dobles_barra === 0);
        $elementosConDobleces = $resto->filter(fn($e) => (int)$e->dobles_barra > 0);

        Log::channel('planilla_import')->info("📊 [AsignarMaquina] Clasificación resto: {$elementosRectos->count()} rectos, {$elementosConDobleces->count()} con dobleces");

        $totalAsignados = 0;

        // 🎯 PROCESAR ELEMENTOS RECTOS (pueden ir a CM o cortadoras automáticas)
        if ($elementosRectos->isNotEmpty()) {
            $gruposRectos = $this->agruparPorResumen($elementosRectos);
            $totalGruposRectos = count($gruposRectos);
            $gruposMultiplesRectos = collect($gruposRectos)->filter(fn($g) => $g['elementos']->count() > 1)->count();

            Log::channel('planilla_import')->info("📦 [RESUMEN] Elementos rectos agrupados en {$totalGruposRectos} grupos por Ø+dimensiones ({$gruposMultiplesRectos} grupos con múltiples elementos)");

            foreach ($gruposRectos as $key => $grupo) {
                $elementos = $grupo['elementos'];
                $diametro = $grupo['diametro'];
                $dimensiones = $grupo['dimensiones_original'] ?: 'barra';
                $pesoGrupo = $elementos->sum(fn($e) => (float)$e->peso);

                Log::channel('planilla_import')->info("📦 [RESUMEN] Grupo rectos '{$key}': Ø{$diametro}, dim='{$dimensiones}', {$elementos->count()} elem, {$pesoGrupo}kg");

                // Buscar máquina compatible para este grupo
                $poolCandidatas = $todasMaquinas->filter(fn($m) => $this->soportaDiametro($m, $diametro));

                if ($poolCandidatas->isEmpty()) {
                    Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Sin máquina compatible para grupo rectos Ø{$diametro}");
                    continue;
                }

                // Seleccionar la menos cargada para TODO el grupo
                $maquinaDestino = $this->menosCargada($poolCandidatas, $cargas);

                if (!$maquinaDestino) {
                    Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] No se pudo seleccionar máquina para grupo rectos Ø{$diametro}");
                    continue;
                }

                Log::channel('planilla_import')->info("🎯 [RESUMEN] Grupo rectos '{$key}' → Máquina {$maquinaDestino->id} ({$maquinaDestino->codigo}) - {$elementos->count()} elementos a misma máquina");

                // Asignar TODOS los elementos del grupo a la MISMA máquina
                foreach ($elementos as $e) {
                    if (!$this->puedeIrACM($e, $maquinaDestino)) {
                        Log::channel('planilla_import')->error("⚠️ [AsignarMaquina] Elemento {$e->id} BLOQUEADO para {$maquinaDestino->codigo}");
                        continue;
                    }

                    $e->maquina_id = $maquinaDestino->id;
                    $e->save();
                    $this->sumarCarga($cargas, $maquinaDestino->id, (float)$e->peso, (int)($e->tiempo_fabricacion ?? 0));
                    $totalAsignados++;
                }
            }
        }

        // 🎯 PROCESAR ELEMENTOS CON DOBLECES (SOLO cortadoras automáticas, nunca CM)
        if ($elementosConDobleces->isNotEmpty()) {
            $gruposDobleces = $this->agruparPorResumen($elementosConDobleces);
            $totalGruposDobleces = count($gruposDobleces);
            $gruposMultiplesDobleces = collect($gruposDobleces)->filter(fn($g) => $g['elementos']->count() > 1)->count();

            Log::channel('planilla_import')->info("📦 [RESUMEN] Elementos con dobleces agrupados en {$totalGruposDobleces} grupos por Ø+dimensiones ({$gruposMultiplesDobleces} grupos con múltiples elementos)");

            foreach ($gruposDobleces as $key => $grupo) {
                $elementos = $grupo['elementos'];
                $diametro = $grupo['diametro'];
                $dimensiones = $grupo['dimensiones_original'] ?: 'barra';
                $pesoGrupo = $elementos->sum(fn($e) => (float)$e->peso);

                Log::channel('planilla_import')->info("📦 [RESUMEN] Grupo dobleces '{$key}': Ø{$diametro}, dim='{$dimensiones}', {$elementos->count()} elem, {$pesoGrupo}kg");

                // Solo cortadoras automáticas (nunca CM)
                $poolCandidatas = $cortadoras->filter(fn($m) => $this->soportaDiametro($m, $diametro));

                if ($poolCandidatas->isEmpty()) {
                    Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] Sin cortadora automática compatible para grupo dobleces Ø{$diametro}");
                    continue;
                }

                // Seleccionar la menos cargada para TODO el grupo
                $maquinaDestino = $this->menosCargada($poolCandidatas, $cargas);

                if (!$maquinaDestino) {
                    Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina] No se pudo seleccionar máquina para grupo dobleces Ø{$diametro}");
                    continue;
                }

                Log::channel('planilla_import')->info("🎯 [RESUMEN] Grupo dobleces '{$key}' → Máquina {$maquinaDestino->id} ({$maquinaDestino->codigo}) - {$elementos->count()} elementos a misma máquina");

                // Asignar TODOS los elementos del grupo a la MISMA máquina
                foreach ($elementos as $e) {
                    $e->maquina_id = $maquinaDestino->id;
                    $e->save();
                    $this->sumarCarga($cargas, $maquinaDestino->id, (float)$e->peso, (int)($e->tiempo_fabricacion ?? 0));
                    $totalAsignados++;
                }
            }
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina] Elementos del resto asignados por grupos de resumen: {$totalAsignados} de {$resto->count()}");
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
        if ($pool->isEmpty()) {
            return null;
        }

        $mejor = null;
        $menorCarga = INF;

        // Buscar la máquina con MENOS CARGA (tiempo 70% + peso 30%)
        foreach ($pool as $m) {
            $c = $cargas[$m->id] ?? ['kilos' => 0.0, 'segundos' => 0, 'num' => 0];

            // Normalizar tiempo a horas para que sea comparable
            $horas = $c['segundos'] / 3600;

            // Índice de carga: Priorizar TIEMPO (70%) + PESO (30%)
            // El tiempo es el factor más importante porque determina cuándo estará libre la máquina
            $indiceCarga = ($horas * 0.7) + ($c['kilos'] * 0.3);

            // Si hay empate en carga, usar el número de elementos como desempate
            if ($indiceCarga < $menorCarga || ($indiceCarga == $menorCarga && $c['num'] < ($cargas[$mejor->id]['num'] ?? 0))) {
                $menorCarga = $indiceCarga;
                $mejor = $m;
            }
        }

        if ($mejor) {
            $c = $cargas[$mejor->id] ?? ['kilos' => 0.0, 'segundos' => 0, 'num' => 0];
            $horas = round($c['segundos'] / 3600, 2);
            Log::channel('planilla_import')->debug("⚖️ [Balanceo] Máquina {$mejor->id} ({$mejor->codigo}) seleccionada: {$c['kilos']}kg, {$horas}h, {$c['num']} elem (índice: " . number_format($menorCarga, 2) . ")");
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
        Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Calculando cargas pendientes por máquina (peso + tiempo)");

        $cargas = Elemento::selectRaw('maquina_id, COALESCE(SUM(peso),0) as kilos, COALESCE(SUM(tiempo_fabricacion),0) as segundos, COUNT(*) as num')
            ->whereNotNull('maquina_id')
            ->where('estado', 'pendiente')
            ->groupBy('maquina_id')
            ->get()
            ->mapWithKeys(fn($r) => [
                (int)$r->maquina_id => [
                    'kilos' => (float)$r->kilos,
                    'segundos' => (int)$r->segundos,
                    'num' => (int)$r->num
                ]
            ])
            ->toArray();

        $totalMaquinas = count($cargas);
        $totalKilos = array_sum(array_column($cargas, 'kilos'));
        $totalSegundos = array_sum(array_column($cargas, 'segundos'));
        $totalElementos = array_sum(array_column($cargas, 'num'));

        $horasTotales = round($totalSegundos / 3600, 2);
        Log::channel('planilla_import')->debug("📊 [AsignarMaquina] Cargas calculadas: {$totalMaquinas} máquinas con carga, {$totalKilos}kg, {$horasTotales}h, {$totalElementos} elementos pendientes");

        return $cargas;
    }

    protected function sumarCarga(array &$cargas, int $maquinaId, float $kilos, int $segundos = 0): void
    {
        if (!isset($cargas[$maquinaId])) {
            $cargas[$maquinaId] = ['kilos' => 0.0, 'segundos' => 0, 'num' => 0];
        }

        $cargas[$maquinaId]['kilos'] += $kilos;
        $cargas[$maquinaId]['segundos'] += $segundos;
        $cargas[$maquinaId]['num'] += 1;

        $horas = round($cargas[$maquinaId]['segundos'] / 3600, 2);
        Log::channel('planilla_import')->debug("➕ [AsignarMaquina] Máquina {$maquinaId}: carga actualizada → {$cargas[$maquinaId]['kilos']}kg, {$horas}h, {$cargas[$maquinaId]['num']} elementos");
    }

    /**
     * Valida si un elemento puede ser asignado a la cortadora manual CM
     * REGLA: Solo elementos con dobles_barra = 0 pueden ir a CM
     *
     * @param Elemento $elemento
     * @param Maquina $maquina
     * @return bool
     */
    protected function puedeIrACM(Elemento $elemento, Maquina $maquina): bool
    {
        // Si no es la cortadora manual, siempre puede asignar
        if ($maquina->codigo !== 'CM') {
            return true;
        }

        // Si ES la cortadora manual, SOLO si dobles_barra = 0
        $dobles = (int)$elemento->dobles_barra;

        if ($dobles !== 0) {
            Log::channel('planilla_import')->error("🚨🚨🚨 [VALIDACIÓN CRÍTICA] BLOQUEADO: Elemento {$elemento->id} (dobles_barra={$dobles}) NO puede ir a CM (solo dobles=0)");
            return false;
        }

        return true;
    }

    /**
     * Optimiza la asignación de elementos sin elaborar basándose en el desperdicio de material.
     * Prioriza elementos cuya longitud sea divisor o se aproxime por debajo a las longitudes base.
     *
     * @param \Illuminate\Support\Collection $elementos
     * @return \Illuminate\Support\Collection
     */
    protected function optimizarPorDesperdicio($elementos)
    {
        Log::channel('planilla_import')->info("🎯 [Optimización] Iniciando optimización por desperdicio para {$elementos->count()} elementos");

        // Obtener longitudes base disponibles de productos
        $longitudesBase = ProductoBase::whereNotNull('longitud')
            ->where('longitud', '>', 0)
            ->pluck('longitud')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (empty($longitudesBase)) {
            Log::channel('planilla_import')->warning("⚠️ [Optimización] No hay longitudes base disponibles, retornando elementos sin optimizar");
            return $elementos;
        }

        Log::channel('planilla_import')->debug("📏 [Optimización] Longitudes base disponibles: " . json_encode($longitudesBase) . " metros");

        // Calcular el índice de desperdicio para cada elemento
        $elementosConDesperdicio = $elementos->map(function ($elemento) use ($longitudesBase) {
            $longitudElemento = (float)$elemento->longitud; // Longitud en cm

            if ($longitudElemento <= 0) {
                Log::channel('planilla_import')->debug("⚠️ [Optimización] Elemento {$elemento->id} tiene longitud inválida: {$longitudElemento}cm");
                return [
                    'elemento' => $elemento,
                    'desperdicio_porcentaje' => 100, // Máximo desperdicio para elementos sin longitud
                    'longitud_base_optima' => null,
                ];
            }

            // Convertir longitud del elemento de cm a metros para comparar con productos base
            $longitudElementoMetros = $longitudElemento / 100;

            $mejorDesperdicio = INF;
            $mejorLongitudBase = null;

            // Buscar la longitud base que minimiza el desperdicio
            foreach ($longitudesBase as $longitudBase) {
                if ($longitudBase >= $longitudElementoMetros) {
                    // Calcular cuántas piezas del elemento caben en la barra base
                    $piezasPorBarra = floor($longitudBase / $longitudElementoMetros);

                    if ($piezasPorBarra > 0) {
                        // Calcular el material aprovechado y el desperdicio
                        $longitudAprovechada = $piezasPorBarra * $longitudElementoMetros;
                        $desperdicio = $longitudBase - $longitudAprovechada;
                        $desperdicioPorcentaje = ($desperdicio / $longitudBase) * 100;

                        if ($desperdicioPorcentaje < $mejorDesperdicio) {
                            $mejorDesperdicio = $desperdicioPorcentaje;
                            $mejorLongitudBase = $longitudBase;
                        }
                    }
                }
            }

            // Si no se encontró una longitud base adecuada, usar 100% de desperdicio
            if ($mejorLongitudBase === null) {
                $mejorDesperdicio = 100;
                Log::channel('planilla_import')->debug("⚠️ [Optimización] Elemento {$elemento->id}: longitud {$longitudElemento}cm ({$longitudElementoMetros}m) no cabe en ninguna longitud base disponible");
            } else {
                $piezas = floor($mejorLongitudBase / $longitudElementoMetros);
                Log::channel('planilla_import')->debug("✓ [Optimización] Elemento {$elemento->id}: L={$longitudElemento}cm ({$longitudElementoMetros}m) → Base óptima={$mejorLongitudBase}m, {$piezas} piezas/barra, desperdicio={$mejorDesperdicio}%");
            }

            return [
                'elemento' => $elemento,
                'desperdicio_porcentaje' => $mejorDesperdicio,
                'longitud_base_optima' => $mejorLongitudBase,
                'peso' => (float)$elemento->peso,
            ];
        });

        // Ordenar por:
        // 1. Desperdicio ascendente (menor desperdicio = mayor prioridad)
        // 2. Peso descendente (elementos pesados primero para mejor balanceo)
        $elementosOrdenados = $elementosConDesperdicio
            ->sortBy([
                ['desperdicio_porcentaje', 'asc'],
                ['peso', 'desc']
            ])
            ->pluck('elemento');

        // Logging de estadísticas
        $desperdicioPromedio = $elementosConDesperdicio->avg('desperdicio_porcentaje');
        $elementosOptimos = $elementosConDesperdicio->filter(fn($e) => $e['desperdicio_porcentaje'] < 5)->count();
        $elementosAceptables = $elementosConDesperdicio->filter(fn($e) => $e['desperdicio_porcentaje'] >= 5 && $e['desperdicio_porcentaje'] < 15)->count();
        $elementosAltos = $elementosConDesperdicio->filter(fn($e) => $e['desperdicio_porcentaje'] >= 15)->count();

        Log::channel('planilla_import')->info("📊 [Optimización] Desperdicio promedio: " . number_format($desperdicioPromedio, 2) . "%");
        Log::channel('planilla_import')->info("📊 [Optimización] Distribución: {$elementosOptimos} óptimos (<5%), {$elementosAceptables} aceptables (5-15%), {$elementosAltos} altos (>15%)");

        return $elementosOrdenados;
    }

    /**
     * Muestra un resumen del balanceo de cargas entre máquinas
     *
     * @param array $cargas
     * @param \Illuminate\Support\Collection $maquinas
     * @return void
     */
    protected function mostrarResumenBalanceo(array $cargas, $maquinas): void
    {
        if (empty($cargas)) {
            Log::channel('planilla_import')->debug("ℹ️ [Balanceo] No hay cargas asignadas en esta planilla");
            return;
        }

        Log::channel('planilla_import')->info("📊 ============ RESUMEN DE BALANCEO DE CARGAS ============");

        $cargasConMaquina = [];
        $totalKilos = 0;
        $totalSegundos = 0;
        $totalElementos = 0;

        foreach ($cargas as $maquinaId => $carga) {
            $maquina = $maquinas->get($maquinaId);
            if ($maquina) {
                $cargasConMaquina[] = [
                    'id' => $maquinaId,
                    'codigo' => $maquina->codigo,
                    'tipo' => $maquina->tipo,
                    'kilos' => $carga['kilos'],
                    'segundos' => $carga['segundos'],
                    'num' => $carga['num'],
                ];
                $totalKilos += $carga['kilos'];
                $totalSegundos += $carga['segundos'];
                $totalElementos += $carga['num'];
            }
        }

        // Ordenar por tiempo descendente (factor más importante)
        usort($cargasConMaquina, fn($a, $b) => $b['segundos'] <=> $a['segundos']);

        $promedioHorasPorMaquina = count($cargasConMaquina) > 0 ? ($totalSegundos / 3600) / count($cargasConMaquina) : 0;

        foreach ($cargasConMaquina as $carga) {
            $horas = $carga['segundos'] / 3600;
            $porcentajeTiempo = $totalSegundos > 0 ? ($carga['segundos'] / $totalSegundos) * 100 : 0;
            $desviacionTiempo = $promedioHorasPorMaquina > 0 ? (($horas - $promedioHorasPorMaquina) / $promedioHorasPorMaquina) * 100 : 0;
            $indicador = abs($desviacionTiempo) < 10 ? '✅' : (abs($desviacionTiempo) < 25 ? '⚠️' : '🔴');

            Log::channel('planilla_import')->info(sprintf(
                "%s [Balanceo] %s (ID:%d): %.2fkg | %.2fh (%d elem) - %.1f%% del tiempo | Desv: %+.1f%%",
                $indicador,
                $carga['codigo'],
                $carga['id'],
                $carga['kilos'],
                $horas,
                $carga['num'],
                $porcentajeTiempo,
                $desviacionTiempo
            ));
        }

        $horasTotales = round($totalSegundos / 3600, 2);
        Log::channel('planilla_import')->info("📊 [Balanceo] TOTAL: {$totalKilos}kg, {$horasTotales}h en {$totalElementos} elementos - " . count($cargasConMaquina) . " máquinas");
        Log::channel('planilla_import')->info("📊 [Balanceo] PROMEDIO por máquina: " . number_format($promedioHorasPorMaquina, 2) . "h");

        // Calcular desviación estándar del TIEMPO (factor más importante)
        if (count($cargasConMaquina) > 1) {
            $varianza = 0;
            foreach ($cargasConMaquina as $carga) {
                $horas = $carga['segundos'] / 3600;
                $varianza += pow($horas - $promedioHorasPorMaquina, 2);
            }
            $varianza /= count($cargasConMaquina);
            $desviacionEstandar = sqrt($varianza);
            $coeficienteVariacion = $promedioHorasPorMaquina > 0 ? ($desviacionEstandar / $promedioHorasPorMaquina) * 100 : 0;

            Log::channel('planilla_import')->info(sprintf(
                "📊 [Balanceo] Desviación estándar tiempo: %.2fh | Coeficiente de variación: %.1f%% %s",
                $desviacionEstandar,
                $coeficienteVariacion,
                $coeficienteVariacion < 15 ? '(Excelente ✅)' : ($coeficienteVariacion < 30 ? '(Aceptable ⚠️)' : '(Mejorable 🔴)')
            ));
        }

        Log::channel('planilla_import')->info("📊 ========================================================");
    }

    /**
     * Detecta si la planilla es de tipo "ensamblado taller"
     * Las planillas con ensamblado taller van a máquinas de Nave B
     */
    protected function esEnsambladoTaller(Planilla $planilla): bool
    {
        $ensamblado = strtolower(trim($planilla->ensamblado ?? ''));
        return str_contains($ensamblado, 'taller');
    }

    /**
     * Reparte los elementos de una planilla "ensamblado taller" en máquinas de Nave B
     * Solo usa cortadoras_dobladoras de Nave B, sin lógica de estriberas ni CM
     * Agrupa elementos por diámetro+dimensiones para evitar duplicación de trabajo
     */
    protected function repartirEnNaveB(Planilla $planilla, $elementos): void
    {
        Log::channel('planilla_import')->info("🏭 [AsignarMaquina/NaveB] Iniciando reparto de {$elementos->count()} elementos para planilla {$planilla->id} en Nave B");

        // Calcular cargas actuales
        $cargas = $this->cargasPendientesPorMaquina();

        // 📦 PASO 1: Elementos para grúa (barra recta con longitud de producto base, excluye 6m)
        $paraGrua = $elementos->filter(fn($e) => $this->debeIrAGrua($e));

        if ($paraGrua->isNotEmpty()) {
            Log::channel('planilla_import')->info("🏗️ [AsignarMaquina/NaveB] {$paraGrua->count()} elementos van a grúa (longitud 12/14/15/16m, sin dobleces)");
            $this->asignarElementosAGrua($planilla, $paraGrua, 'B', $cargas);
        }

        // 🔧 PASO 2: Elementos que SÍ requieren elaboración
        $elementosAElaborar = $elementos->reject(fn($e) => $this->debeIrAGrua($e));

        if ($elementosAElaborar->isEmpty()) {
            Log::channel('planilla_import')->info("✅ [AsignarMaquina/NaveB] Solo había elementos sin elaborar, reparto completado");
            return;
        }

        // Obtener máquinas de Nave B tipo cortadora_dobladora (activas)
        $maquinasNaveB = Maquina::naveB()
            ->where('tipo', 'cortadora_dobladora')
            ->where(function ($query) {
                $query->where('estado', 'activa')
                    ->orWhereNull('estado');
            })
            ->get()
            ->keyBy('id');

        Log::channel('planilla_import')->info("🏭 [AsignarMaquina/NaveB] Máquinas disponibles en Nave B: {$maquinasNaveB->count()} - Códigos: " . json_encode($maquinasNaveB->pluck('codigo')->toArray()));

        if ($maquinasNaveB->isEmpty()) {
            Log::channel('planilla_import')->error("❌ [AsignarMaquina/NaveB] No hay máquinas cortadora_dobladora activas en Nave B para planilla {$planilla->id}");
            return;
        }

        $pesoTotal = $elementosAElaborar->sum(fn($e) => (float)$e->peso);
        Log::channel('planilla_import')->info("⚖️ [AsignarMaquina/NaveB] Total elementos a elaborar: {$elementosAElaborar->count()}, {$pesoTotal}kg");

        // 🎯 AGRUPAR POR RESUMEN: elementos con mismo diámetro+dimensiones van a la misma máquina
        $gruposResumen = $this->agruparPorResumen($elementosAElaborar);
        $totalGrupos = count($gruposResumen);
        $gruposMultiples = collect($gruposResumen)->filter(fn($g) => $g['elementos']->count() > 1)->count();

        Log::channel('planilla_import')->info("📦 [RESUMEN/NaveB] Elementos agrupados en {$totalGrupos} grupos por Ø+dimensiones ({$gruposMultiples} grupos con múltiples elementos)");

        $asignados = 0;

        foreach ($gruposResumen as $key => $grupo) {
            $elementosGrupo = $grupo['elementos'];
            $diametro = $grupo['diametro'];
            $dimensiones = $grupo['dimensiones_original'] ?: 'barra';
            $pesoGrupo = $elementosGrupo->sum(fn($e) => (float)$e->peso);

            Log::channel('planilla_import')->info("📦 [RESUMEN/NaveB] Grupo '{$key}': Ø{$diametro}, dim='{$dimensiones}', {$elementosGrupo->count()} elem, {$pesoGrupo}kg");

            // Buscar máquinas que soporten el diámetro
            $candidatas = $maquinasNaveB->filter(fn($m) => $this->soportaDiametro($m, $diametro));

            if ($candidatas->isEmpty()) {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina/NaveB] Sin máquina compatible para grupo Ø{$diametro} en Nave B");
                continue;
            }

            // Seleccionar la menos cargada para TODO el grupo
            $maquinaDestino = $this->menosCargada($candidatas, $cargas);

            if (!$maquinaDestino) {
                Log::channel('planilla_import')->warning("⚠️ [AsignarMaquina/NaveB] No se pudo seleccionar máquina para grupo Ø{$diametro}");
                continue;
            }

            Log::channel('planilla_import')->info("🎯 [RESUMEN/NaveB] Grupo '{$key}' → Máquina {$maquinaDestino->id} ({$maquinaDestino->codigo}) - {$elementosGrupo->count()} elementos a misma máquina");

            // Asignar TODOS los elementos del grupo a la MISMA máquina
            foreach ($elementosGrupo as $elemento) {
                $elemento->maquina_id = $maquinaDestino->id;
                $elemento->save();
                $this->sumarCarga($cargas, $maquinaDestino->id, (float)$elemento->peso, (int)($elemento->tiempo_fabricacion ?? 0));
                $asignados++;
            }
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina/NaveB] Asignados {$asignados} de {$elementosAElaborar->count()} elementos a Nave B por grupos de resumen");

        // Mostrar resumen de balanceo
        $this->mostrarResumenBalanceo($cargas, $maquinasNaveB);
    }

    /**
     * Reasigna un elemento a una máquina específica, validando compatibilidad
     * Usado por el sistema de balanceo de cargas
     *
     * @param Elemento $elemento
     * @param Maquina $maquinaDestino
     * @return array ['success' => bool, 'message' => string]
     */
    public function reasignarElemento(Elemento $elemento, Maquina $maquinaDestino): array
    {
        $dobles = (int)$elemento->dobles_barra;
        $diametro = (int)$elemento->diametro;

        // 1. Elementos que deben ir a grúa (barra recta con longitud base 12/14/15/16m)
        if ($this->debeIrAGrua($elemento)) {
            if ($maquinaDestino->tipo !== 'grua') {
                return [
                    'success' => false,
                    'message' => "Elemento {$elemento->codigo} (longitud " . ($elemento->longitud / 1000) . "m, sin dobleces) solo puede ir a grúas, no a {$maquinaDestino->codigo}"
                ];
            }
        }

        // 2. Validar diámetro
        if (!$this->soportaDiametro($maquinaDestino, $diametro)) {
            return [
                'success' => false,
                'message' => "Máquina {$maquinaDestino->codigo} no soporta Ø{$diametro} (rango: {$maquinaDestino->diametro_min}-{$maquinaDestino->diametro_max})"
            ];
        }

        // 3. Validar CM: solo elementos con dobles_barra = 0
        if ($maquinaDestino->codigo === 'CM' && $dobles !== 0) {
            return [
                'success' => false,
                'message' => "Elemento {$elemento->codigo} tiene dobles_barra={$dobles}, no puede ir a cortadora manual CM"
            ];
        }

        // 4. Estribos (dobles >= 4 Y diámetro <= 16) solo van a estriberas
        $esEstribo = $dobles >= 4 && $diametro <= 16;
        $codigosEstriberas = ['F12', 'PS12', 'MS16'];

        if ($esEstribo && !in_array($maquinaDestino->codigo, $codigosEstriberas)) {
            return [
                'success' => false,
                'message' => "Elemento {$elemento->codigo} es estribo (dobles={$dobles}, Ø{$diametro}), solo puede ir a estriberas (F12, PS12, MS16)"
            ];
        }

        // 5. Elementos con dobleces (dobles > 0) no pueden ir a CM
        if ($dobles > 0 && $maquinaDestino->codigo === 'CM') {
            return [
                'success' => false,
                'message' => "Elemento {$elemento->codigo} tiene dobleces (dobles={$dobles}), no puede ir a cortadora manual CM"
            ];
        }

        // 6. Elementos con dobleces solo van a cortadoras_dobladoras o estribadoras
        if ($dobles > 0 && !in_array($maquinaDestino->tipo, ['cortadora_dobladora', 'estribera', 'estribadora'])) {
            return [
                'success' => false,
                'message' => "Elemento {$elemento->codigo} con dobleces solo puede ir a cortadora_dobladora o estribadora, no a {$maquinaDestino->tipo}"
            ];
        }

        return ['success' => true, 'message' => 'OK'];
    }

    /**
     * Asigna elementos sin elaborar (única dimensión) a la primera grúa de la nave
     * Los movimientos de preparación se crean cuando el gruista entra en la vista de grúa
     * y hay salidas programadas para mañana con estos elementos
     */
    protected function asignarElementosAGrua(Planilla $planilla, $elementos, string $nave, array &$cargas): void
    {
        $naveLabel = "Nave {$nave}";
        Log::channel('planilla_import')->info("🏗️ [AsignarMaquina/Grúa] Asignando {$elementos->count()} elementos sin elaborar a grúa de {$naveLabel}");

        // Obtener la primera grúa de la nave correspondiente
        $grua = $nave === 'A'
            ? Maquina::naveA()->where('tipo', 'grua')->orderBy('id')->first()
            : Maquina::naveB()->where('tipo', 'grua')->orderBy('id')->first();

        if (!$grua) {
            Log::channel('planilla_import')->error("❌ [AsignarMaquina/Grúa] No hay grúa disponible en {$naveLabel} para planilla {$planilla->id}");
            return;
        }

        Log::channel('planilla_import')->info("🏗️ [AsignarMaquina/Grúa] Grúa seleccionada: {$grua->codigo} (ID: {$grua->id}) en {$naveLabel}");

        $asignados = 0;

        foreach ($elementos as $elemento) {
            // Asignar elemento a la grúa
            $elemento->maquina_id = $grua->id;
            $elemento->save();

            $this->sumarCarga($cargas, $grua->id, (float)$elemento->peso, (int)($elemento->tiempo_fabricacion ?? 0));
            $asignados++;

            Log::channel('planilla_import')->debug("✓ [AsignarMaquina/Grúa] Elemento {$elemento->id} (Ø{$elemento->diametro}, {$elemento->peso}kg) → Grúa {$grua->codigo}");
        }

        Log::channel('planilla_import')->info("✅ [AsignarMaquina/Grúa] {$asignados} elementos asignados a grúa {$grua->codigo}");
    }
}
