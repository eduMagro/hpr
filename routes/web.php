<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntradaController;




Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
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
        
});

require __DIR__ . '/auth.php';
