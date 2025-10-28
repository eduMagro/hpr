<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PlanillaImport\PlanillaImportService;
use App\Services\PlanillaImport\ExcelValidator;
use App\Services\PlanillaImport\ExcelReader;
use App\Services\PlanillaImport\PlanillaProcessor;
use App\Services\AsignarMaquinaService;
use App\Services\OrdenPlanillaService;

/**
 * Service Provider para los servicios de importación de planillas.
 */
class ImportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar servicios como singletons
        $this->app->singleton(ExcelValidator::class, function ($app) {
            return new ExcelValidator();
        });

        $this->app->singleton(ExcelReader::class, function ($app) {
            return new ExcelReader();
        });

        $this->app->singleton(PlanillaProcessor::class, function ($app) {
            return new PlanillaProcessor();
        });

        // Registrar el servicio principal
        $this->app->singleton(PlanillaImportService::class, function ($app) {
            return new PlanillaImportService(
                $app->make(ExcelValidator::class),
                $app->make(ExcelReader::class),
                $app->make(PlanillaProcessor::class),
                $app->make(AsignarMaquinaService::class),
                $app->make(OrdenPlanillaService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
