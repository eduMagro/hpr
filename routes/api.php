<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\AsistenteVirtualController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ResumenEtiquetaController;
use App\Http\Controllers\PlanillaController;

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

// Buscar planillas para autocompletado (completar planillas)
Route::get('/planillas/buscar', [PlanillaController::class, 'buscarParaCompletar'])
    ->name('api.planillas.buscar');
Route::post('/paquetes/{paqueteId}/añadir-etiqueta', [PaqueteController::class, 'añadirEtiquetaAPaquete'])
    ->name('api.paquetes.añadir-etiqueta');
Route::delete('/paquetes/{paqueteId}/eliminar-etiqueta', [PaqueteController::class, 'eliminarEtiquetaDePaquete'])
    ->name('api.paquetes.eliminar-etiqueta');
Route::delete('/paquetes/{paqueteId}', [PaqueteController::class, 'eliminarPaquete'])
    ->name('api.paquetes.eliminar');

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

    // Deshacer estado de un grupo (completada -> fabricando -> pendiente)
    Route::post('/{grupo}/deshacer-estado', [ResumenEtiquetaController::class, 'deshacerEstado'])
        ->name('api.etiquetas.resumir.deshacer-estado');

    // Obtener etiquetas de un grupo para imprimir
    Route::get('/{grupo}/imprimir', [ResumenEtiquetaController::class, 'etiquetasParaImprimir'])
        ->name('api.etiquetas.resumir.imprimir');

    // Cambiar estado de todas las etiquetas del grupo (fabricando/completada)
    Route::put('/{grupo}/estado', [ResumenEtiquetaController::class, 'cambiarEstado'])
        ->name('api.etiquetas.resumir.estado');

    // === RUTAS MULTI-PLANILLA ===
    // Vista previa de resumen entre planillas revisadas
    Route::get('/multiplanilla/preview', [ResumenEtiquetaController::class, 'previewMultiplanilla'])
        ->name('api.etiquetas.resumir.multiplanilla.preview');

    // Ejecutar resumen multi-planilla
    Route::post('/multiplanilla', [ResumenEtiquetaController::class, 'resumirMultiplanilla'])
        ->name('api.etiquetas.resumir.multiplanilla');

    // Obtener grupos multi-planilla activos
    Route::get('/multiplanilla/grupos', [ResumenEtiquetaController::class, 'gruposMultiplanilla'])
        ->name('api.etiquetas.resumir.multiplanilla.grupos');

    // Desagrupar todos los grupos multi-planilla de una máquina
    Route::post('/multiplanilla/desagrupar-todos', [ResumenEtiquetaController::class, 'desagruparTodosMultiplanilla'])
        ->name('api.etiquetas.resumir.multiplanilla.desagrupar-todos');
});

// =====================================================================
// FERRAWIN SYNC API
// =====================================================================
use App\Http\Controllers\Api\FerrawinSyncController;

Route::prefix('ferrawin')->group(function () {
    // Status público (para verificar conectividad)
    Route::get('/status', [FerrawinSyncController::class, 'status'])
        ->name('api.ferrawin.status');

    // Sync protegido con token
    Route::post('/sync', [FerrawinSyncController::class, 'sync'])
        ->middleware('ferrawin.api')
        ->name('api.ferrawin.sync');

    // Backfill descripcion_fila para elementos existentes
    Route::post('/backfill-descripcion-fila', [FerrawinSyncController::class, 'backfillDescripcionFila'])
        ->middleware('ferrawin.api')
        ->name('api.ferrawin.backfill-descripcion-fila');

    // Obtener códigos de planillas existentes (para sync incremental)
    Route::get('/codigos-existentes', [FerrawinSyncController::class, 'codigosExistentes'])
        ->middleware('ferrawin.api')
        ->name('api.ferrawin.codigos-existentes');

    // Obtener elementos de una planilla para matching con FerraWin
    Route::get('/elementos-para-matching/{codigo}', [FerrawinSyncController::class, 'elementosParaMatching'])
        ->middleware('ferrawin.api')
        ->name('api.ferrawin.elementos-para-matching');

    // Actualizar ferrawin_id de elementos existentes
    Route::post('/actualizar-ferrawin-ids', [FerrawinSyncController::class, 'actualizarFerrawinIds'])
        ->middleware('ferrawin.api')
        ->name('api.ferrawin.actualizar-ferrawin-ids');

    // Estado de sincronización remota (enviado desde Windows, leído desde producción)
    Route::post('/sync-status', [FerrawinSyncController::class, 'syncStatus'])
        ->middleware('ferrawin.api')
        ->name('api.ferrawin.sync-status');

    Route::get('/sync-status', [FerrawinSyncController::class, 'getSyncStatus'])
        ->name('api.ferrawin.sync-status.get');
});
