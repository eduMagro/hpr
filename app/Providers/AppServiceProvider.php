<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            // Ejecuta la rotación de turnos cada día a medianoche
            $schedule->command('turnos:generar-anuales')->yearly();
            // Restablece las vacaciones cada 1 de enero a la medianoche
            $schedule->command('vacaciones:reset')->yearly();
        });
    }
}
