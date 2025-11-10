<?php

/**
 * Script de Testing del Asistente Virtual
 * 
 * Ejecutar: php artisan test:asistente
 * 
 * Para crear el comando Artisan:
 * php artisan make:command TestAsistente
 * Luego copia este contenido al método handle()
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AsistenteVirtualService;
use Illuminate\Support\Facades\DB;

class TestAsistente extends Command
{
    protected $signature = 'test:asistente';
    protected $description = 'Prueba automática del Asistente Virtual';

    public function handle()
    {
        $this->info('🤖 === TEST DEL ASISTENTE VIRTUAL ===');
        $this->newLine();

        // Test 1: Verificar configuración
        $this->info('1️⃣ Verificando configuración...');
        if (!config('services.anthropic.api_key')) {
            $this->error('❌ API Key de Anthropic no configurada en .env');
            return 1;
        }
        $this->info('✅ API Key configurada');
        $this->newLine();

        // Test 2: Verificar tabla
        $this->info('2️⃣ Verificando base de datos...');
        try {
            DB::table('asistente_logs')->limit(1)->get();
            $this->info('✅ Tabla asistente_logs existe');
        } catch (\Exception $e) {
            $this->error('❌ Tabla asistente_logs no existe. Ejecuta: php artisan migrate');
            return 1;
        }
        $this->newLine();

        // Test 3: Verificar servicio
        $this->info('3️⃣ Instanciando servicio...');
        try {
            $asistente = app(AsistenteVirtualService::class);
            $this->info('✅ Servicio creado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error creando servicio: ' . $e->getMessage());
            return 1;
        }
        $this->newLine();

        // Test 4: Preguntas de prueba
        $this->info('4️⃣ Ejecutando preguntas de prueba...');
        $this->newLine();

        $preguntasPrueba = [
            [
                'pregunta' => '¿Qué pedidos hay pendientes?',
                'esperado' => 'pedido'
            ],
            [
                'pregunta' => '¿Cuánto stock hay de Ø12mm?',
                'esperado' => 'stock'
            ],
            [
                'pregunta' => 'Muestra un resumen general',
                'esperado' => 'general'
            ]
        ];

        $exitosas = 0;
        $fallidas = 0;
        $costoTotal = 0;

        foreach ($preguntasPrueba as $index => $test) {
            $numero = $index + 1;
            $this->info("  Test #{$numero}: {$test['pregunta']}");

            try {
                $inicio = microtime(true);
                $resultado = $asistente->responder($test['pregunta'], 1);
                $duracion = microtime(true) - $inicio;

                $this->line("  📤 Respuesta: " . substr($resultado['respuesta'], 0, 100) . '...');
                $this->line("  ⏱️  Tiempo: " . round($duracion, 2) . "s");
                $this->line("  💵 Coste: $" . number_format($resultado['coste_estimado'], 4));

                $costoTotal += $resultado['coste_estimado'];
                $exitosas++;
                $this->info("  ✅ Test exitoso");
            } catch (\Exception $e) {
                $this->error("  ❌ Test fallido: " . $e->getMessage());
                $fallidas++;
            }

            $this->newLine();
        }

        // Resumen
        $this->info('📊 === RESUMEN ===');
        $this->line("Tests exitosos: {$exitosas}");
        $this->line("Tests fallidos: {$fallidas}");
        $this->line("Coste total: $" . number_format($costoTotal, 4));

        if ($fallidas === 0) {
            $this->info('🎉 ¡Todos los tests pasaron correctamente!');
            $this->newLine();
            $this->info('✅ El asistente está listo para usar');
            $this->info('💡 Accede desde tu frontend o prueba más consultas desde tinker');
            return 0;
        } else {
            $this->error('⚠️  Algunos tests fallaron. Revisa los errores arriba.');
            return 1;
        }
    }
}
