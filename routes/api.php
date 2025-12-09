<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\AsistenteVirtualController;
use App\Http\Controllers\ProductoController;

Route::get('/codigos/info', [MovimientoController::class, 'infoCodigo'])
    ->name('api.codigos.info');   // ← este será el nombre exacto

// Buscar producto por código (para escaneo QR en grúa)
Route::get('/productos/buscar-por-codigo', [ProductoController::class, 'buscarPorCodigo'])
    ->name('api.productos.buscar-por-codigo');

// Sugerencias de productos por diámetro/longitud (para modal de grúa)
Route::get('/productos/sugerencias', [ProductoController::class, 'sugerenciasPorDiametroLongitud'])
    ->name('api.productos.sugerencias');
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

// Rutas para el asistente virtual
Route::prefix('asistente')->group(function () {
    // Enviar pregunta al asistente
    Route::post('/preguntar', [AsistenteVirtualController::class, 'preguntar'])->name('asistente.preguntar');

    // Obtener sugerencias de preguntas
    Route::get('/sugerencias', [AsistenteVirtualController::class, 'sugerencias'])->name('asistente.sugerencias');

    // Ver estadísticas de uso
    Route::get('/estadisticas', [AsistenteVirtualController::class, 'estadisticas'])->name('asistente.estadisticas');
});
