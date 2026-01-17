<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Departamento;
use App\Models\User;

class SyncOperariosDepartamento extends Command
{
    protected $signature = 'operarios:sync-departamento
                            {--check : Solo muestra el estado sin hacer cambios}';

    protected $description = 'Sincroniza usuarios con rol operario al departamento Operario';

    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║      SINCRONIZAR OPERARIOS CON DEPARTAMENTO                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Buscar departamento Operario
        $departamento = Departamento::whereRaw('LOWER(nombre) = ?', ['operario'])->first();

        if (!$departamento) {
            $this->error('❌ No existe el departamento "Operario"');
            $this->info('   Ejecuta primero: php artisan permisos:migrar-operarios');
            return Command::FAILURE;
        }

        $this->info("✅ Departamento encontrado: {$departamento->nombre} (ID: {$departamento->id})");
        $this->info('');

        // Obtener usuarios con rol operario
        $operarios = User::whereRaw('LOWER(rol) = ?', ['operario'])->get();
        $this->info("📋 Usuarios con rol 'operario': {$operarios->count()}");

        // Obtener usuarios ya asignados al departamento
        $yaAsignados = $departamento->usuarios()->pluck('users.id')->toArray();
        $this->info("   Ya asignados al departamento: " . count($yaAsignados));

        $porAsignar = $operarios->filter(fn($u) => !in_array($u->id, $yaAsignados));
        $this->info("   Por asignar: {$porAsignar->count()}");
        $this->info('');

        if ($porAsignar->isEmpty()) {
            $this->info('✅ Todos los operarios ya están asignados al departamento.');
            return Command::SUCCESS;
        }

        // Mostrar usuarios que se asignarán
        $this->info('👥 USUARIOS QUE SE ASIGNARÁN:');
        foreach ($porAsignar as $user) {
            $this->info("   • {$user->name} ({$user->email})");
        }
        $this->info('');

        if ($this->option('check')) {
            $this->warn('ℹ️  Ejecuta sin --check para asignar los usuarios.');
            return Command::SUCCESS;
        }

        // Asignar usuarios
        foreach ($porAsignar as $user) {
            $departamento->usuarios()->attach($user->id);
        }

        $this->info("🎉 Se asignaron {$porAsignar->count()} usuarios al departamento '{$departamento->nombre}'");
        $this->info('');

        return Command::SUCCESS;
    }
}
