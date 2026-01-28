<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Turno;
use App\Models\Obra;
use App\Models\AsignacionTurno;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Test completo del sistema de fichaje con turno partido
 *
 * Escenarios cubiertos:
 * - Fichaje de entrada en diferentes turnos (mañana, tarde)
 * - Fichaje de salida normal
 * - Fichaje de salida a las 22:00 con turno noche desactivado
 * - Turno partido completo (entrada, salida, entrada2, salida2)
 * - Casos de error (salida sin entrada, entrada duplicada, etc.)
 *
 * NOTA: Usa DatabaseTransactions para usar BD existente y hacer rollback
 */
class FichajeTest extends TestCase
{
    use DatabaseTransactions;

    protected User $operario;
    protected Obra $obra;
    protected Turno $turnoManana;
    protected Turno $turnoTarde;
    protected Turno $turnoNoche;
    protected bool $turnoNocheOriginalActivo;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar cache entre tests
        Cache::flush();

        // Usar turnos existentes
        $this->turnoManana = Turno::where('nombre', 'mañana')->first();
        $this->turnoTarde = Turno::where('nombre', 'tarde')->first();
        $this->turnoNoche = Turno::where('nombre', 'noche')->first();

        // Guardar estado original del turno noche
        $this->turnoNocheOriginalActivo = $this->turnoNoche->activo;

        // Asegurar que noche está desactivado para los tests
        $this->turnoNoche->update(['activo' => false]);

        // Usar obra existente activa
        $this->obra = Obra::where('estado', 'activa')->first();

        // Crear operario de test
        $this->operario = User::create([
            'name' => 'Test Operario Fichaje',
            'email' => 'test.fichaje.' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'rol' => 'operario',
        ]);
    }

    /**
     * Helper para hacer petición de fichaje
     */
    protected function fichar(string $tipo, ?Carbon $hora = null, bool $confirmarTurnoPartido = false): \Illuminate\Testing\TestResponse
    {
        if ($hora) {
            Carbon::setTestNow($hora);
        }

        Cache::flush(); // Limpiar cache de protección duplicados

        $payload = [
            'user_id' => $this->operario->id,
            'tipo' => $tipo,
            'latitud' => $this->obra->latitud,
            'longitud' => $this->obra->longitud,
        ];

        if ($confirmarTurnoPartido) {
            $payload['confirmar_turno_partido'] = true;
        }

        return $this->actingAs($this->operario)
            ->postJson('/fichar', $payload);
    }

    /**
     * Helper para mostrar estado actual de asignación
     */
    protected function getEstadoAsignacion(?string $fecha = null): ?AsignacionTurno
    {
        $fecha = $fecha ?? Carbon::now()->toDateString();
        return AsignacionTurno::where('user_id', $this->operario->id)
            ->whereDate('fecha', $fecha)
            ->first();
    }

    // =====================================================================
    // TESTS DE ENTRADA - TURNO MAÑANA (06:00 - 14:00)
    // =====================================================================

    /** @test */
    public function entrada_turno_manana_a_las_06_00()
    {
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Entrada registrada.']);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertNotNull($asignacion);
        $this->assertEquals('06:00:00', $asignacion->entrada);
        $this->assertNull($asignacion->salida);
        $this->assertEquals($this->turnoManana->id, $asignacion->turno_id);
    }

    /** @test */
    public function entrada_turno_manana_con_anticipacion_a_las_05_30()
    {
        // 30 minutos antes del turno (dentro del margen de 2 horas)
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 05:30:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Entrada registrada.']);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals($this->turnoManana->id, $asignacion->turno_id);
    }

    /** @test */
    public function entrada_turno_manana_a_las_10_00()
    {
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 10:00:00'));

        $response->assertStatus(200);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals($this->turnoManana->id, $asignacion->turno_id);
    }

    // =====================================================================
    // TESTS DE ENTRADA - TURNO TARDE (14:00 - 22:00)
    // =====================================================================

    /** @test */
    public function entrada_turno_tarde_a_las_14_00()
    {
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Entrada registrada.']);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals($this->turnoTarde->id, $asignacion->turno_id);
    }

    /** @test */
    public function entrada_turno_tarde_con_anticipacion_a_las_13_00()
    {
        // 1 hora antes del turno de tarde
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 13:00:00'));

        $response->assertStatus(200);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals($this->turnoTarde->id, $asignacion->turno_id);
    }

    /** @test */
    public function entrada_turno_tarde_a_las_18_00()
    {
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 18:00:00'));

        $response->assertStatus(200);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals($this->turnoTarde->id, $asignacion->turno_id);
    }

    // =====================================================================
    // TESTS DE SALIDA NORMAL
    // =====================================================================

    /** @test */
    public function salida_turno_manana_a_las_14_00()
    {
        // Primero fichar entrada
        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));

        // Luego fichar salida
        $response = $this->fichar('salida', Carbon::parse('2024-01-15 14:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Salida registrada.']);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals('06:00:00', $asignacion->entrada);
        $this->assertEquals('14:00:00', $asignacion->salida);
    }

    /** @test */
    public function salida_turno_tarde_a_las_22_00_con_noche_desactivado()
    {
        // CASO CRÍTICO: Salida a las 22:00 con turno noche desactivado

        // Fichar entrada de tarde
        $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'));

        // Fichar salida a las 22:00 (hora límite del turno tarde)
        $response = $this->fichar('salida', Carbon::parse('2024-01-15 22:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Salida registrada.']);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals('22:00:00', $asignacion->salida);
    }

    /** @test */
    public function salida_turno_tarde_a_las_22_30_con_noche_desactivado()
    {
        // Salida media hora después del fin del turno tarde

        $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'));

        $response = $this->fichar('salida', Carbon::parse('2024-01-15 22:30:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Salida registrada.']);
    }

    /** @test */
    public function salida_turno_tarde_a_las_23_00_con_noche_desactivado()
    {
        // Salida 1 hora después del fin del turno tarde

        $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'));

        $response = $this->fichar('salida', Carbon::parse('2024-01-15 23:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Salida registrada.']);
    }

    // =====================================================================
    // TESTS DE TURNO PARTIDO
    // =====================================================================

    /** @test */
    public function turno_partido_completo()
    {
        // 1. Primera entrada (06:00)
        $response1 = $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));
        $response1->assertStatus(200)->assertJson(['success' => 'Entrada registrada.']);

        // 2. Primera salida (10:00)
        $response2 = $this->fichar('salida', Carbon::parse('2024-01-15 10:00:00'));
        $response2->assertStatus(200)->assertJson(['success' => 'Salida registrada.']);

        // 3. Segunda entrada (14:00) - requiere confirmación
        $response3 = $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'));
        $response3->assertStatus(200)
            ->assertJson(['requiere_confirmacion_turno_partido' => true]);

        // 4. Confirmar segunda entrada
        $response4 = $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'), true);
        $response4->assertStatus(200)
            ->assertJson(['success' => 'Segunda entrada registrada (turno partido).']);

        // 5. Segunda salida (18:00)
        $response5 = $this->fichar('salida', Carbon::parse('2024-01-15 18:00:00'));
        $response5->assertStatus(200)
            ->assertJson(['success' => 'Segunda salida registrada. Turno partido completado.']);

        // Verificar estado final
        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals('06:00:00', $asignacion->entrada);
        $this->assertEquals('10:00:00', $asignacion->salida);
        $this->assertEquals('14:00:00', $asignacion->entrada2);
        $this->assertEquals('18:00:00', $asignacion->salida2);
    }

    /** @test */
    public function turno_partido_pregunta_confirmacion()
    {
        // Entrada + salida
        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));
        $this->fichar('salida', Carbon::parse('2024-01-15 10:00:00'));

        // Intentar segunda entrada SIN confirmación
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'));

        $response->assertStatus(200)
            ->assertJson([
                'requiere_confirmacion_turno_partido' => true,
                'mensaje' => 'Ya tienes un fichaje de entrada y salida hoy. ¿Quieres hacer turno partido?',
            ]);

        // Verificar que NO se registró entrada2
        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertNull($asignacion->entrada2);
    }

    /** @test */
    public function turno_partido_segunda_salida_a_las_22_00()
    {
        // Turno partido que termina a las 22:00 con noche desactivado

        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));
        $this->fichar('salida', Carbon::parse('2024-01-15 10:00:00'));
        $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'), true);

        // Segunda salida a las 22:00
        $response = $this->fichar('salida', Carbon::parse('2024-01-15 22:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Segunda salida registrada. Turno partido completado.']);
    }

    // =====================================================================
    // TESTS DE ERRORES Y CASOS EDGE
    // =====================================================================

    /** @test */
    public function error_salida_sin_entrada()
    {
        $response = $this->fichar('salida', Carbon::parse('2024-01-15 14:00:00'));

        $response->assertStatus(403)
            ->assertJson(['error' => 'No tienes una asignación de turno para hoy. Debes fichar entrada primero.']);
    }

    /** @test */
    public function error_entrada_duplicada_sin_salida()
    {
        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));

        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 08:00:00'));

        $response->assertStatus(403)
            ->assertJson(['error' => 'Ya tienes fichada la entrada. Debes fichar salida primero.']);
    }

    /** @test */
    public function error_tercera_entrada_despues_de_turno_partido()
    {
        // Completar turno partido
        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));
        $this->fichar('salida', Carbon::parse('2024-01-15 10:00:00'));
        $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'), true);
        $this->fichar('salida', Carbon::parse('2024-01-15 18:00:00'));

        // Intentar tercera entrada
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 19:00:00'));

        $response->assertStatus(403)
            ->assertJson(['error' => 'Ya has completado tu turno partido (2 entradas y 2 salidas). No puedes fichar más entradas hoy.']);
    }

    /** @test */
    public function error_tercera_salida_despues_de_turno_partido()
    {
        // Completar turno partido
        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));
        $this->fichar('salida', Carbon::parse('2024-01-15 10:00:00'));
        $this->fichar('entrada', Carbon::parse('2024-01-15 14:00:00'), true);
        $this->fichar('salida', Carbon::parse('2024-01-15 18:00:00'));

        // Intentar tercera salida
        $response = $this->fichar('salida', Carbon::parse('2024-01-15 20:00:00'));

        $response->assertStatus(403)
            ->assertJson(['error' => 'Ya has completado tu turno partido (2 entradas y 2 salidas). No puedes fichar más salidas hoy.']);
    }

    /** @test */
    public function error_salida_sin_entrada2_en_turno_partido()
    {
        // Entrada + salida + intentar otra salida sin entrada2
        $this->fichar('entrada', Carbon::parse('2024-01-15 06:00:00'));
        $this->fichar('salida', Carbon::parse('2024-01-15 10:00:00'));

        $response = $this->fichar('salida', Carbon::parse('2024-01-15 14:00:00'));

        $response->assertStatus(403)
            ->assertJson(['error' => 'Ya fichaste tu primera salida. Si vas a hacer turno partido, ficha entrada primero para iniciar la segunda jornada.']);
    }

    /** @test */
    public function error_entrada_sin_turno_detectado_a_las_04_00()
    {
        // 4:00 AM - fuera de cualquier turno y fuera del margen de anticipación
        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 04:00:00'));

        // Con turno noche desactivado, no debería detectar ningún turno
        $response->assertStatus(403)
            ->assertJson(['error' => 'No se pudo determinar el turno para esta hora.']);
    }

    // =====================================================================
    // TESTS CON TURNO NOCHE ACTIVO
    // =====================================================================

    /** @test */
    public function entrada_turno_noche_cuando_esta_activo()
    {
        // Activar turno noche para este test
        $this->turnoNoche->update(['activo' => true]);

        $response = $this->fichar('entrada', Carbon::parse('2024-01-15 22:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Entrada registrada.']);

        $asignacion = $this->getEstadoAsignacion('2024-01-15');
        $this->assertEquals($this->turnoNoche->id, $asignacion->turno_id);
    }

    /** @test */
    public function salida_turno_noche_al_dia_siguiente()
    {
        // Activar turno noche
        $this->turnoNoche->update(['activo' => true]);

        // Entrada a las 22:00 del día 15
        $this->fichar('entrada', Carbon::parse('2024-01-15 22:00:00'));

        // Salida a las 06:00 del día 16
        $response = $this->fichar('salida', Carbon::parse('2024-01-16 06:00:00'));

        $response->assertStatus(200)
            ->assertJson(['success' => 'Salida registrada.']);
    }

    // =====================================================================
    // TESTS DE UBICACIÓN
    // =====================================================================

    /** @test */
    public function error_fichaje_fuera_de_zona_trabajo()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15 08:00:00'));
        Cache::flush();

        // Coordenadas muy lejos de cualquier obra (Antártida)
        $response = $this->actingAs($this->operario)
            ->postJson('/fichar', [
                'user_id' => $this->operario->id,
                'tipo' => 'entrada',
                'latitud' => -82.8628,
                'longitud' => 135.0000,
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'No estás dentro de ninguna zona de trabajo.']);
    }

    // =====================================================================
    // TESTS DE PROTECCIÓN ANTI-DUPLICADOS
    // =====================================================================

    /** @test */
    public function proteccion_contra_fichajes_duplicados_rapidos()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15 08:00:00'));

        // Primer fichaje
        $this->actingAs($this->operario)
            ->postJson('/fichar', [
                'user_id' => $this->operario->id,
                'tipo' => 'entrada',
                'latitud' => $this->obra->latitud,
                'longitud' => $this->obra->longitud,
            ]);

        // Segundo fichaje inmediato (sin limpiar cache)
        $response = $this->actingAs($this->operario)
            ->postJson('/fichar', [
                'user_id' => $this->operario->id,
                'tipo' => 'entrada',
                'latitud' => $this->obra->latitud,
                'longitud' => $this->obra->longitud,
            ]);

        $response->assertStatus(429)
            ->assertJson(['error' => 'Ya tienes un fichaje en proceso. Espera unos segundos.']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time mock

        // Restaurar estado original del turno noche
        if (isset($this->turnoNoche) && isset($this->turnoNocheOriginalActivo)) {
            $this->turnoNoche->update(['activo' => $this->turnoNocheOriginalActivo]);
        }

        parent::tearDown();
    }
}
