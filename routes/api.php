<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PaqueteController;

Route::get('/codigos/info', [MovimientoController::class, 'infoCodigo'])
    ->name('api.codigos.info');   // ← este será el nombre exacto
Route::get('/planillas/{planillaId}/paquetes', [PaqueteController::class, 'obtenerPaquetesPorPlanilla'])
    ->name('api.planillas.paquetes');
Route::post('/paquetes/{paqueteId}/añadir-etiqueta', [PaqueteController::class, 'añadirEtiquetaAPaquete'])
    ->name('api.paquetes.añadir-etiqueta');
Route::delete('/paquetes/{paqueteId}/eliminar-etiqueta', [PaqueteController::class, 'eliminarEtiquetaDePaquete'])
    ->name('api.paquetes.eliminar-etiqueta');
Route::delete('/paquetes/{paqueteId}', [PaqueteController::class, 'eliminarPaquete'])
    ->name('api.paquetes.eliminar');

use App\Http\Controllers\PlanillaController;

Route::get('/planillas/import/progress/{id}', [PlanillaController::class, 'importProgress'])
    ->name('planillas.import.progress');
