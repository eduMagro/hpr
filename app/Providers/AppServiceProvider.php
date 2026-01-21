<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\User;
use App\Models\Producto;
use App\Observers\UserObserver;
use App\Observers\ProductoObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
    public function boot(): void
    {
        // 👀 Observadores de modelos
        User::observe(UserObserver::class);
        Producto::observe(ProductoObserver::class);

        // 🔐 Limitador de intentos de login
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(15)->by($request->email . '|' . $request->ip());
        });

        // ⏰ Tareas programadas (si las usas)
        $this->app->booted(function () {
            // $schedule = app(Schedule::class);
            // $schedule->command(...)->daily();
        });
    }
}
