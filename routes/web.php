<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PapeleraController;
use App\Http\Controllers\VacacionesController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\ProductoBaseController;
use App\Http\Controllers\PedidoGlobalController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\LocalizacionController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\EmpresaTransportecontroller;
use App\Http\Controllers\PlanificacionController;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ElementoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\AsignacionTurnoController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\PoliticaController;
use App\Http\Controllers\AyudaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\IrpfTramoController;
use App\Http\Controllers\SeguridadSocialController;
use App\Http\Controllers\NominaController;
use App\Models\VacacionesSolicitud;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/users/{id}', [ProfileController::class, 'update'])->name('profile.update');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/actualizar-especialidad/{operario}', [ProfileController::class, 'actualizarEspecialidad']);
    Route::resource('clientes', ClienteController::class);
    Route::resource('proveedores', ProveedorController::class);

    // Rutas para el controlador de entradas
    Route::resource('entradas', EntradaController::class);
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');

    Route::resource('pedidos_globales', PedidoGlobalController::class);
    Route::resource('pedidos', PedidoController::class);
    Route::post('/pedidos/confirmar', [PedidoController::class, 'confirmar'])->name('pedidos.confirmar');
    Route::get('pedidos/{pedido}/recepcion', [PedidoController::class, 'recepcion'])->name('pedidos.recepcion');
    Route::post('pedidos/{pedido}/recepcion', [PedidoController::class, 'procesarRecepcion'])->name('pedidos.recepcion.guardar');
    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.enviarCorreo');
    Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.preview');
    Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar'])->name('pedidos.activar');


    Route::resource('fabricantes', FabricanteController::class);
    Route::resource('productos-base', ProductoBaseController::class);

    Route::resource('productos', ProductoController::class);
    Route::post('/productos/crear-desde-recepcion', [PedidoController::class, 'crearDesdeRecepcion'])->name('productos.crear.desde.recepcion');

    Route::post('/solicitar-stock', [ProductoController::class, 'solicitarStock'])->name('solicitar.stock');

    // // ✅ Primero las rutas específicas
    // Route::post('/localizaciones/verificar', [LocalizacionController::class, 'verificar'])->name('localizaciones.verificar');
    // Route::get('/localizaciones/editar-mapa', [LocalizacionController::class, 'editarMapa'])->name('localizaciones.editarMapa');

    // // ✅ Después la ruta resource
    // Route::resource('localizaciones', LocalizacionController::class)->parameters([
    //     'localizaciones' => 'localizacion'
    // ]);

    Route::resource('ubicaciones', UbicacionController::class);
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    //USUARIOS
    Route::resource('users', ProfileController::class);
    Route::put('/actualizar-usuario/{id}', [ProfileController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
    Route::resource('vacaciones', VacacionesController::class);
    Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store'])->name('vacaciones.solicitar');
    Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar'])->name('vacaciones.aprobar');
    Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar'])->name('vacaciones.denegar');
    Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');
    Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento'])->name('vacaciones.eliminarEvento');


    Route::resource('asignaciones-turnos', AsignacionTurnoController::class);
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy'])
        ->name('asignaciones-turnos.destroy');
    Route::post('/asignaciones-turno/{id}/actualizar-puesto', [ProduccionController::class, 'actualizarPuesto']);
    Route::post('/fichar', [AsignacionTurnoController::class, 'fichar'])->name('fichar');

    Route::post('/generar-turnos', function (Request $request) {
        Artisan::call('turnos:generar-anuales');
        return back()->with('success', '✅ Turnos generados correctamente.');
    })->name('generar-turnos');
    Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');
    Route::post('/festivos/editar', [VacacionesController::class, 'moverFestivo'])->name('festivos.mover');

    Route::resource('maquinas', MaquinaController::class);
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');

    Route::resource('movimientos', MovimientoController::class);
    Route::post('/movimientos/crear', [MovimientoController::class, 'crearMovimiento'])->name('movimientos.crear');

    Route::resource('paquetes', PaqueteController::class);
    Route::resource('etiquetas', EtiquetaController::class);

    Route::resource('obras', ObraController::class);

    Route::get('productos/{id}/consumir', [ProductoController::class, 'consumir'])
        ->name('productos.consumir');
    Route::post('/productos/generar-exportar', [ProductoController::class, 'generarYExportar'])->name('productos.generar.exportar');


    Route::resource('planillas', PlanillaController::class);
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.import');
    Route::post('/planillas/reordenar', [PlanillaController::class, 'reordenarPlanillas'])->name('planillas.reordenar');

    Route::resource('elementos', ElementoController::class);
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])
        ->name('elementosEtiquetas');
    Route::post('/elementos/dividir', [ElementoController::class, 'dividirElemento'])->name('elementos.dividir');
    Route::post('/elementos/{elementoId}/solicitar-cambio-maquina', [ElementoController::class, 'solicitarCambioMaquina']);
    Route::put('/elementos/{id}/cambio-maquina', [ElementoController::class, 'cambioMaquina'])->name('elementos.cambioMaquina');

    Route::post('/subetiquetas/crear', [ElementoController::class, 'crearSubEtiqueta'])->name('subetiquetas.crear');

    //Actualizar estado de etiquetas
    //Route::post('/actualizarEstado', [ElementoController::class, 'actualizarEstado'])->name('elementos.actualizarEstado');
    //Route::post('/actualizar-etiqueta/{id}', [EtiquetaController::class, 'actualizarEtiqueta']);
    Route::put('/actualizar-etiqueta/{id}/maquina/{maquina_id}', [EtiquetaController::class, 'actualizarEtiqueta']);

    Route::post('/verificar-items', [PaqueteController::class, 'verificarItems']);
    // Para elegir un peon en maquinas

    // Para elegir un peon en maquinas
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])
        ->name('maquinas.sesion.guardar');
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');

    Route::resource('salidas', SalidaController::class);
    Route::delete('/salidas/{salida}/quitar-paquete/{paquete}', [SalidaController::class, 'quitarPaquete'])
        ->name('salidas.quitarPaquete');
    Route::resource('planificacion', PlanificacionController::class);
    Route::put('/planificacion/comentario/{id}', [PlanificacionController::class, 'guardarComentario']);
    Route::get('/salidas/export/{mes}', [\App\Http\Controllers\SalidaController::class, 'export'])->name('salidas.export');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaController::class, 'actualizarEstado']);
    Route::post('/actualizar-fecha-salida', [SalidaController::class, 'actualizarFechaSalida']);


    //Route::resource('produccion', ProduccionController::class);
    Route::get('/produccion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('produccion.trabajadores');

    Route::get('/produccion/maquinas', [ProduccionController::class, 'maquinas'])->name('produccion.maquinas');
    // Rutas para la gestión de camiones
    Route::resource('camiones', CamionController::class);

    // Rutas para la gestión de empresas de transporte
    Route::resource('empresas-transporte', EmpresaTransporteController::class);
    Route::post('/update-field', [EmpresaTransporteController::class, 'updateField'])->name('update.field');

    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.marcarLeidas');
    Route::post('/alertas/store', [AlertaController::class, 'store'])->name('alertas.store');
    Route::get('/alertas/sin-leer', [AlertaController::class, 'sinLeer'])->name('alertas.sinLeer');

    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');

    Route::post('/escaneo', [SalidaController::class, 'marcarSubido'])->name('escaneo.marcarSubido');

    Route::get('/papelera', [PapeleraController::class, 'index'])->name('papelera.index');
    Route::put('/papelera/restore/{model}/{id}', [PapeleraController::class, 'restore'])->name('papelera.restore');

    //GENERAR NOMINAS
    Route::resource('empresas', EmpresaController::class);
    Route::resource('nominas', NominaController::class)->except(['destroy']);
    Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales'])->name('generar.nominas');
    Route::delete('/nominas/borrar-todas', [NominaController::class, 'borrarTodas'])->name('nominas.borrarTodas');
    Route::resource('irpf-tramos', IrpfTramoController::class);
    Route::resource('porcentajes-ss', SeguridadSocialController::class);
    //SIMULACION NOMINAS
    Route::get('/simulacion-irpf', [NominaController::class, 'formularioSimulacion'])->name('nomina.simulacion');
    Route::post('/simulacion-irpf', [NominaController::class, 'simular'])->name('nomina.simular');
    //SIMULACION INVERSA
    Route::get('/simulacion-inversa', [NominaController::class, 'formularioInverso'])->name('nomina.inversa');
    Route::post('/simulacion-inversa', [NominaController::class, 'simularDesdeNeto'])->name('nomina.inversa.calcular');

    Route::controller(PoliticaController::class)->group(function () {
        Route::get('/politica-privacidad', 'mostrarPrivacidad')->name('politica.privacidad');
        Route::get('/politica-cookies', 'mostrarCookies')->name('politica.cookies');
        Route::post('/aceptar-politicas', 'aceptar')->name('aceptar.politicas');
    });

    Route::get('/ayuda', [AyudaController::class, 'index'])->name('ayuda.index');
});



require __DIR__ . '/auth.php';
