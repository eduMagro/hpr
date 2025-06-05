<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\User;
use App\Observers\UserObserver;
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
        // 👀 Observador del modelo User
        User::observe(UserObserver::class);

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
