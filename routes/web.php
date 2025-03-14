<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistroFichajeController;
use App\Http\Controllers\VacacionesController;

use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ProductoController;

use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ElementoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\SubpaqueteController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\AsignacionTurnoController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\EmpresaTransporteController;
use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/users/{id}', [ProfileController::class, 'update'])->name('profile.update');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');



    Route::resource('clientes', ClienteController::class);


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


    Route::post('/solicitar-stock', [ProductoController::class, 'solicitarStock'])->name('solicitar.stock');

    Route::resource('ubicaciones', UbicacionController::class);
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    //USUARIOS
    Route::resource('users', ProfileController::class);
    Route::put('/actualizar-usuario/{id}', [ProfileController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
    Route::resource('vacaciones', VacacionesController::class);
    Route::resource('registros-fichaje', RegistroFichajeController::class);
    Route::resource('asignaciones-turnos', AsignacionTurnoController::class);
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy'])
        ->name('asignaciones-turnos.destroy');

    Route::post('/generar-turnos', function (Request $request) {
        Artisan::call('turnos:generar-anuales');
        return back()->with('success', '✅ Turnos generados correctamente.');
    })->name('generar-turnos');
    Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');

    Route::resource('maquinas', MaquinaController::class);
    Route::resource('movimientos', MovimientoController::class);
    Route::resource('paquetes', PaqueteController::class);
    Route::resource('etiquetas', EtiquetaController::class);
    Route::resource('obras', ObraController::class);

    Route::get('productos/{id}/consumir', [ProductoController::class, 'consumir'])
        ->name('productos.consumir');

    Route::resource('planillas', PlanillaController::class);
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.import');

    Route::resource('elementos', ElementoController::class);
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])
        ->name('elementosEtiquetas');
    Route::resource('subpaquetes', SubpaqueteController::class);
    //Actualizar estado de etiquetas
    //Route::post('/actualizarEstado', [ElementoController::class, 'actualizarEstado'])->name('elementos.actualizarEstado');
    //Route::post('/actualizar-etiqueta/{id}', [EtiquetaController::class, 'actualizarEtiqueta']);
    Route::put('/actualizar-etiqueta/{id}/maquina/{maquina_id}', [EtiquetaController::class, 'actualizarEtiqueta']);
    Route::put('/actualizar-elemento/{id}/maquina/{maquina_id}', [ElementoController::class, 'actualizarElemento']);

    Route::post('/verificar-items', [PaqueteController::class, 'verificarItems']);
    // Para elegir un peon en maquinas

    // Para elegir un peon en maquinas
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])
        ->name('maquinas.sesion.guardar');

    Route::resource('salidas', SalidaController::class);
    Route::get('/salidas/export/{mes}', [\App\Http\Controllers\SalidaController::class, 'export'])->name('salidas.export');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaController::class, 'actualizarEstado']);
    // Rutas para la gestión de camiones
    Route::resource('camiones', CamionController::class);

    // Rutas para la gestión de empresas de transporte
    Route::resource('empresas-transporte', EmpresaTransporteController::class);

    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/alertas/sin-leer', [AlertaController::class, 'alertasSinLeer'])->name('alertas.sinLeer');
    Route::post('/alertas/store', [AlertaController::class, 'store'])->name('alertas.store');

    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');

    Route::post('/escaneo', [SalidaController::class, 'marcarSubido'])->name('escaneo.marcarSubido');
});



require __DIR__ . '/auth.php';
