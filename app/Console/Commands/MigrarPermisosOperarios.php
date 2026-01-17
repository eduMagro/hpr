<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Departamento;
use App\Models\DepartamentoRuta;

class MigrarPermisosOperarios extends Command
{
    protected $signature = 'permisos:migrar-operarios
                            {--check : Solo muestra el estado sin hacer cambios}
                            {--departamento= : Nombre del departamento (por defecto: Operario)}';

    protected $description = 'Migra las rutas de operarios del config a la tabla departamento_ruta';

    /**
     * Rutas que los operarios necesitan acceder
     * Usa .* para indicar todas las rutas de un prefijo
     */
    private array $rutasOperario = [
        // Prefijos completos (todas las rutas)
        'albaranes.*' => 'Acceso a albaranes',
        'produccion.trabajadores.*' => 'Producción - trabajadores',
        'users.*' => 'Perfil de usuario',
        'alertas.*' => 'Sistema de alertas/mensajes',
        'productos.*' => 'Consulta de productos/materiales',
        'pedidos.*' => 'Consulta de pedidos',
        'ayuda.*' => 'Sección de ayuda',
        'maquinas.*' => 'Consulta de máquinas',
        'etiquetas.*' => 'Gestión de etiquetas',
        'elementos.*' => 'Gestión de elementos',
        'subetiquetas.*' => 'Gestión de sub-etiquetas',
        'paquetes.*' => 'Gestión de paquetes',
        'localizaciones.*' => 'Ubicación en mapa',
        'api.*' => 'Rutas API',
        'entradas.*' => 'Entradas de material',
        'movimientos.*' => 'Movimientos de material',
        'ubicaciones.*' => 'Ubicaciones de almacén',
        'inventario-backups.*' => 'Backups de inventario',

        // Rutas específicas
        'incorporaciones.descargarMiContrato' => 'Descargar mi contrato',
        'usuarios.getVacationData' => 'Ver datos de vacaciones propios',
        'usuarios.fichajes-rango' => 'Ver fichajes propios en calendario',
        'vacaciones.verMisSolicitudesPendientes' => 'Ver solicitudes pendientes en calendario',
        'vacaciones.verSolicitudesPendientesUsuario' => 'Ver mis solicitudes pendientes',
        'vacaciones.eliminarSolicitud' => 'Eliminar solicitud de vacaciones',
        'vacaciones.eliminarDiasSolicitud' => 'Eliminar días de solicitud',
    ];

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║      MIGRACIÓN DE RUTAS OPERARIOS A DEPARTAMENTO             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $nombreDepartamento = $this->option('departamento') ?? 'Operario';

        // 1) Buscar o crear el departamento
        $departamento = Departamento::whereRaw('LOWER(nombre) = ?', [strtolower($nombreDepartamento)])->first();

        if (!$departamento) {
            if ($this->option('check')) {
                $this->warn("⚠️  No existe el departamento '{$nombreDepartamento}'");
                $this->info("   Se creará automáticamente al ejecutar sin --check");
                return Command::SUCCESS;
            }

            $departamento = Departamento::create([
                'nombre' => $nombreDepartamento,
                'descripcion' => 'Departamento virtual para usuarios con rol operario',
            ]);
            $this->info("✅ Departamento '{$nombreDepartamento}' creado (ID: {$departamento->id})");
        } else {
            $this->info("✅ Departamento '{$departamento->nombre}' encontrado (ID: {$departamento->id})");
        }

        // 2) Obtener rutas ya asignadas
        $rutasExistentes = DepartamentoRuta::where('departamento_id', $departamento->id)
            ->pluck('ruta')
            ->toArray();

        $this->info('');
        $this->info('📋 RUTAS A ASIGNAR:');
        $this->info('');

        $nuevas = 0;
        $existentes = 0;

        foreach ($this->rutasOperario as $ruta => $descripcion) {
            if (in_array($ruta, $rutasExistentes)) {
                $this->line("   • {$ruta} (ya existe)");
                $existentes++;
            } else {
                if ($this->option('check')) {
                    $this->info("   ✚ {$ruta} - {$descripcion}");
                } else {
                    DepartamentoRuta::create([
                        'departamento_id' => $departamento->id,
                        'ruta' => $ruta,
                        'descripcion' => $descripcion,
                    ]);
                    $this->info("   ✅ {$ruta} - {$descripcion}");
                }
                $nuevas++;
            }
        }

        $this->info('');
        if ($this->option('check')) {
            $this->warn("ℹ️  Se añadirían {$nuevas} rutas nuevas. Ejecuta sin --check para aplicar.");
        } else {
            $this->info("🎉 Se añadieron {$nuevas} rutas al departamento '{$departamento->nombre}'");
            $this->info("   ({$existentes} ya existían)");
        }

        $this->info('');
        $this->info('📝 NOTA: Los usuarios con rol "operario" usarán automáticamente');
        $this->info('   estas rutas sin necesidad de asignarlos al departamento.');
        $this->info('');

        return Command::SUCCESS;
    }
}
