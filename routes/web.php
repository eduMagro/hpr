<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ConjuntoController;
use App\Http\Controllers\ElementoController;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/{id}', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas para el controlador de entradas
    Route::resource('entradas', EntradaController::class);
    /* Las siguientes rutas se generan automáticamente:

        1. GET /entradas         --> EntradaController@index   (Muestra todas las entradas)
        2. GET /entradas/create  --> EntradaController@create  (Muestra el formulario para crear una nueva entrada)
        3. POST /entradas        --> EntradaController@store   (Guarda una nueva entrada)
        4. GET /entradas/{id}/edit --> EntradaController@edit   (Muestra el formulario para editar una entrada existente)
        5. PUT/PATCH /entradas/{id} --> EntradaController@update  (Actualiza una entrada existente)
        6. DELETE /entradas/{id}  --> EntradaController@destroy (Elimina una entrada) */
    Route::resource('productos', ProductoController::class);
    Route::resource('ubicaciones', UbicacionController::class);
    Route::resource('users', ProfileController::class);
    Route::resource('maquinas', MaquinaController::class);
    Route::resource('movimientos', MovimientoController::class);

    Route::get('/productos/{id}/origen', [ProductoController::class, 'obtenerOrigen'])->name('productos.obtenerOrigen');



    Route::resource('planillas', PlanillaController::class);
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.import');
    Route::resource('conjuntos', ConjuntoController::class);
    Route::resource('elementos', ElementoController::class);
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])
        ->name('elementosEtiquetas');

    //Actualizar estado de elementos
    //Route::post('/actualizarEstado', [ElementoController::class, 'actualizarEstado'])->name('elementos.actualizarEstado');
    Route::post('/actualizar-elemento/{id}', [ElementoController::class, 'actualizarElemento']);
    // Para elegir un peon en maquinas

    // Para elegir un peon en maquinas
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])
        ->name('maquinas.sesion.guardar');

    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');
});



require __DIR__ . '/auth.php';
