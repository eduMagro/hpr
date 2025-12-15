<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\AsistenteVirtualController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ResumenEtiquetaController;

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

// Obtener longitud del producto asignado a una etiqueta (para segundo clic en cortadora)
Route::get('/etiquetas/{etiquetaSubId}/longitud-asignada', [App\Http\Controllers\EtiquetaController::class, 'longitudAsignada'])
    ->name('api.etiquetas.longitud-asignada');

// Rutas para el sistema de resumen de etiquetas
Route::prefix('etiquetas/resumir')->group(function () {
    // Vista previa de grupos que se crearían
    Route::get('/preview', [ResumenEtiquetaController::class, 'preview'])
        ->name('api.etiquetas.resumir.preview');

    // Ejecutar resumen
    Route::post('/', [ResumenEtiquetaController::class, 'resumir'])
        ->name('api.etiquetas.resumir');

    // Obtener grupos activos
    Route::get('/grupos', [ResumenEtiquetaController::class, 'grupos'])
        ->name('api.etiquetas.resumir.grupos');

    // Desagrupar todos los grupos de una planilla
    Route::post('/desagrupar-todos', [ResumenEtiquetaController::class, 'desagruparTodos'])
        ->name('api.etiquetas.resumir.desagrupar-todos');

    // Desagrupar un grupo específico
    Route::post('/{grupo}/desagrupar', [ResumenEtiquetaController::class, 'desagrupar'])
        ->name('api.etiquetas.resumir.desagrupar');

    // Obtener etiquetas de un grupo para imprimir
    Route::get('/{grupo}/imprimir', [ResumenEtiquetaController::class, 'etiquetasParaImprimir'])
        ->name('api.etiquetas.resumir.imprimir');

    // Cambiar estado de todas las etiquetas del grupo (fabricando/completada)
    Route::put('/{grupo}/estado', [ResumenEtiquetaController::class, 'cambiarEstado'])
        ->name('api.etiquetas.resumir.estado');
});
