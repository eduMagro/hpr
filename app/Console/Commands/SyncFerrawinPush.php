<?php

namespace App\Console\Commands;

use App\Services\FerrawinSync\FerrawinQueryBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Comando para sincronizar FerraWin → Producción (via API).
 *
 * Este comando se ejecuta en el PC LOCAL que tiene acceso a FerraWin,
 * consulta los datos y los envía al servidor de producción via HTTP.
 */
class SyncFerrawinPush extends Command
{
    protected $signature = 'sync:ferrawin-push
                            {--dias=7 : Días hacia atrás para buscar planillas}
                            {--url= : URL del servidor de producción (override)}
                            {--token= : Token de API (override)}
                            {--dry-run : Solo mostrar qué se enviaría, sin enviar}
                            {--compress : Comprimir datos con gzip (recomendado)}';

    protected $description = 'Consulta FerraWin y envía datos al servidor de producción via API';

    public function handle(FerrawinQueryBuilder $queryBuilder): int
    {
        $dias = (int) $this->option('dias');
        $dryRun = (bool) $this->option('dry-run');
        $compress = (bool) $this->option('compress');

        // URL y token
        $url = $this->option('url') ?: config('ferrawin.api.production_url');
        $token = $this->option('token') ?: config('ferrawin.api.token');

        if (!$url) {
            $this->error('❌ URL de producción no configurada. Usa --url o configura FERRAWIN_PRODUCTION_URL');
            return Command::FAILURE;
        }

        if (!$token) {
            $this->error('❌ Token de API no configurado. Usa --token o configura FERRAWIN_API_TOKEN');
            return Command::FAILURE;
        }

        $apiUrl = rtrim($url, '/') . '/api/ferrawin/sync';

        $this->info("=== Sincronización FerraWin → Producción ===");
        $this->info("Servidor: {$url}");
        $this->info("Buscando planillas de los últimos {$dias} días...");
        $this->newLine();

        try {
            // 1. Verificar conexión a FerraWin
            $this->info("🔌 Conectando a FerraWin...");

            if (!$this->verificarConexionFerrawin()) {
                $this->error('❌ No se pudo conectar a FerraWin');
                return Command::FAILURE;
            }

            $this->info("✅ Conexión a FerraWin establecida");

            // 2. Obtener códigos de planillas
            $this->info("🔍 Buscando planillas...");
            $codigos = $queryBuilder->obtenerCodigosPlanillas($dias);

            if (empty($codigos)) {
                $this->info("ℹ️ No se encontraron planillas en los últimos {$dias} días");
                return Command::SUCCESS;
            }

            $this->info("📋 Encontradas " . count($codigos) . " planillas");

            // 3. Obtener datos de cada planilla
            $this->info("📥 Obteniendo datos de planillas...");
            $planillasData = [];
            $totalElementos = 0;

            $bar = $this->output->createProgressBar(count($codigos));
            $bar->start();

            foreach ($codigos as $codigo) {
                $datos = $queryBuilder->obtenerDatosPlanilla($codigo);

                if (!empty($datos)) {
                    $elementos = $this->formatearElementos($datos);
                    $planillasData[] = [
                        'codigo' => $codigo,
                        'descripcion' => $datos[0]->ZNOMBRE_PLANILLA ?? null,
                        'seccion' => $datos[0]->ZSECCION ?? null,
                        'ensamblado' => $datos[0]->ZMODULO ?? null,
                        'elementos' => $elementos,
                    ];
                    $totalElementos += count($elementos);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("📊 Total: " . count($planillasData) . " planillas con {$totalElementos} elementos");

            if (empty($planillasData)) {
                $this->warn("⚠️ No hay datos para enviar");
                return Command::SUCCESS;
            }

            // 4. Preparar payload
            $payload = [
                'planillas' => $planillasData,
                'metadata' => [
                    'origen' => 'sync:ferrawin-push',
                    'fecha' => now()->toIso8601String(),
                    'dias_consultados' => $dias,
                    'total_planillas' => count($planillasData),
                    'total_elementos' => $totalElementos,
                ],
            ];

            $jsonPayload = json_encode($payload);
            $sizeKB = round(strlen($jsonPayload) / 1024, 2);

            $this->info("📦 Tamaño del payload: {$sizeKB} KB");

            if ($dryRun) {
                $this->warn("🔍 [DRY-RUN] No se enviaron datos. Mostrando resumen:");
                $this->table(
                    ['Planilla', 'Elementos'],
                    collect($planillasData)->map(fn($p) => [$p['codigo'], count($p['elementos'])])->toArray()
                );
                return Command::SUCCESS;
            }

            // 5. Enviar a producción
            $this->info("📤 Enviando a producción...");

            $request = Http::timeout(120)
                ->withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                ]);

            if ($compress && function_exists('gzencode')) {
                $compressedPayload = gzencode($jsonPayload, 9);
                $compressedSizeKB = round(strlen($compressedPayload) / 1024, 2);
                $this->info("📦 Tamaño comprimido: {$compressedSizeKB} KB (ahorro: " . round((1 - $compressedSizeKB / $sizeKB) * 100) . "%)");

                $request = $request->withBody($compressedPayload, 'application/json')
                    ->withHeaders(['Content-Encoding' => 'gzip']);
            }

            $response = $compress && function_exists('gzencode')
                ? $request->post($apiUrl)
                : $request->post($apiUrl, $payload);

            // 6. Procesar respuesta
            if ($response->successful()) {
                $data = $response->json();

                $this->newLine();
                $this->info("✅ " . ($data['message'] ?? 'Sincronización completada'));
                $this->newLine();

                if (isset($data['data'])) {
                    $this->table(
                        ['Métrica', 'Valor'],
                        [
                            ['Planillas recibidas', $data['data']['planillas_recibidas'] ?? 0],
                            ['Planillas creadas', $data['data']['planillas_creadas'] ?? 0],
                            ['Planillas actualizadas', $data['data']['planillas_actualizadas'] ?? 0],
                            ['Planillas omitidas', $data['data']['planillas_omitidas'] ?? 0],
                            ['Elementos creados', $data['data']['elementos_creados'] ?? 0],
                            ['Duración servidor', ($data['data']['duracion_segundos'] ?? 0) . ' seg'],
                        ]
                    );
                }

                if (!empty($data['advertencias'])) {
                    $this->newLine();
                    $this->warn("⚠️ Advertencias:");
                    foreach ($data['advertencias'] as $adv) {
                        $this->line("   - {$adv}");
                    }
                }

                return Command::SUCCESS;

            } else {
                $this->error("❌ Error del servidor: " . $response->status());
                $this->error($response->body());

                Log::channel('ferrawin_sync')->error("Error en push a producción", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return Command::FAILURE;
            }

        } catch (\Throwable $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            Log::channel('ferrawin_sync')->error("Error en sync:ferrawin-push", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Verifica la conexión a FerraWin.
     */
    protected function verificarConexionFerrawin(): bool
    {
        try {
            \DB::connection('ferrawin')->getPdo();
            return true;
        } catch (\Throwable $e) {
            $this->error("Error de conexión: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Formatea los datos de FerraWin al formato esperado por la API.
     */
    protected function formatearElementos(array $datos): array
    {
        $elementos = [];

        foreach ($datos as $row) {
            $elementos[] = [
                'codigo_cliente' => $row->ZCODCLI ?? '',
                'nombre_cliente' => $row->ZCLIENTE ?? '',
                'codigo_obra' => $row->ZCODIGO_OBRA ?? '',
                'nombre_obra' => $row->ZNOMBRE_OBRA ?? '',
                'ensamblado' => $row->ZMODULO ?? '',
                'seccion' => $row->ZSECCION ?? '',
                'descripcion_planilla' => $row->ZNOMBRE_PLANILLA ?? '',
                'fila' => $row->ZCODLIN ?? '',
                'descripcion_fila' => $row->ZDESCRIPCION_FILA ?? '',
                'marca' => $row->ZMARCA ?? '',
                'diametro' => (int)($row->ZDIAMETRO ?? 0),
                'figura' => $row->ZCODMODELO ?? '',
                'longitud' => (float)($row->ZLONGTESTD ?? 0),
                'dobles_barra' => (int)($row->ZNUMBEND ?? 0),
                'barras' => (int)($row->ZCANTIDAD ?? 0),
                'peso' => (float)($row->ZPESOTESTD ?? 0),
                'dimensiones' => $this->construirDimensiones($row),
                'etiqueta' => $row->ZETIQUETA ?? '',
            ];
        }

        return $elementos;
    }

    /**
     * Construye el campo dimensiones.
     */
    protected function construirDimensiones($row): string
    {
        $numDobleces = (int)($row->ZNUMBEND ?? 0);

        if ($numDobleces === 0) {
            return (string)($row->ZLONGTESTD ?? '');
        }

        $zfigura = $row->ZFIGURA ?? '';

        if (!empty($zfigura) && strpos($zfigura, "\t") !== false) {
            return trim($zfigura);
        }

        return $zfigura ?: (string)($row->ZLONGTESTD ?? '');
    }
}
