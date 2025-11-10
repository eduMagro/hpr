<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\AsistenteVirtualController;

Route::get('/codigos/info', [MovimientoController::class, 'infoCodigo'])
    ->name('api.codigos.info');   // ← este será el nombre exacto
Route::get('/planillas/{planillaId}/paquetes', [PaqueteController::class, 'obtenerPaquetesPorPlanilla'])
    ->name('api.planillas.paquetes');
Route::post('/paquetes/{paqueteId}/añadir-etiqueta', [PaqueteController::class, 'añadirEtiquetaAPaquete'])
    ->name('api.paquetes.añadir-etiqueta');
Route::delete('/paquetes/{paqueteId}/eliminar-etiqueta', [PaqueteController::class, 'eliminarEtiquetaDePaquete'])
    ->name('api.paquetes.eliminar-etiqueta');

// Rutas para el asistente virtual

Route::prefix('asistente')->group(function () {

    // Enviar pregunta al asistente
    Route::post('/preguntar', [AsistenteVirtualController::class, 'preguntar'])->name('asistente.preguntar');

    // Obtener sugerencias de preguntas
    Route::get('/sugerencias', [AsistenteVirtualController::class, 'sugerencias'])->name('asistente.sugerencias');

    // Ver estadísticas de uso
    Route::get('/estadisticas', [AsistenteVirtualController::class, 'estadisticas'])->name('asistente.estadisticas');
});
