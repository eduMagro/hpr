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
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\EmpresaTransporteController;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // === PERFIL DE USUARIO ===
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/users/{id}', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/actualizar-especialidad/{operario}', [ProfileController::class, 'actualizarEspecialidad']);
    Route::get('/exportar-usuarios', [ProfileController::class, 'exportarUsuarios'])->name('usuarios.exportar');

    // === CLIENTES Y PROVEEDORES ===
    Route::resource('clientes', ClienteController::class)->middleware('acceso.seccion:clientes.index');
    Route::resource('proveedores', ProveedorController::class)->middleware('acceso.seccion:proveedores.index');

    // === ENTRADAS Y PEDIDOS ===
    Route::resource('entradas', EntradaController::class)->middleware('acceso.seccion');
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');

    Route::resource('pedidos_globales', PedidoGlobalController::class)->middleware('acceso.seccion:pedidos-globales.index');
    Route::resource('pedidos', PedidoController::class)->middleware('acceso.seccion:pedidos.index');
    Route::post('/pedidos/confirmar', [PedidoController::class, 'confirmar'])->name('pedidos.confirmar');
    Route::get('pedidos/{pedido}/recepcion', [PedidoController::class, 'recepcion'])->name('pedidos.recepcion');
    Route::post('pedidos/{pedido}/recepcion', [PedidoController::class, 'procesarRecepcion'])->name('pedidos.recepcion.guardar');
    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.enviarCorreo');
    Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.preview');
    Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar'])->name('pedidos.activar');

    // === PRODUCTOS Y UBICACIONES ===
    Route::resource('fabricantes', FabricanteController::class)->middleware('acceso.seccion:fabricantes.index');
    Route::resource('productos-base', ProductoBaseController::class)->middleware('acceso.seccion:productos-base.index');
    Route::resource('productos', ProductoController::class)->middleware('acceso.seccion:productos.index');
    Route::post('/productos/crear-desde-recepcion', [PedidoController::class, 'crearDesdeRecepcion'])->name('productos.crear.desde.recepcion');
    Route::post('/solicitar-stock', [ProductoController::class, 'solicitarStock'])->name('solicitar.stock');
    Route::middleware(['acceso.seccion:productos.index'])->group(function () {
        Route::get('productos/{id}/consumir', [ProductoController::class, 'consumir'])->name('productos.consumir');
        Route::post('/productos/generar-exportar', [ProductoController::class, 'generarYExportar'])->name('productos.generar.exportar');
    });
    Route::resource('ubicaciones', UbicacionController::class)->middleware('acceso.seccion:ubicaciones.index');
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    // === USUARIOS Y VACACIONES ===
    Route::resource('users', ProfileController::class)->middleware('acceso.seccion:users.index');
    Route::put('/actualizar-usuario/{id}', [ProfileController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
    Route::get('/users/{user}/resumen-asistencia', [ProfileController::class, 'resumenAsistencia'])->name('users.resumen-asistencia');
    Route::get('/users/{user}/eventos-turnos', [ProfileController::class, 'eventosTurnos'])->name('users.eventos-turnos');

    Route::resource('vacaciones', VacacionesController::class)->middleware('acceso.seccion:vacaciones.index');
    Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store'])->name('vacaciones.solicitar');
    Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar'])->name('vacaciones.aprobar');
    Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar'])->name('vacaciones.denegar');
    Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');
    Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento'])->name('vacaciones.eliminarEvento');

    // === TURNOS ===
    Route::resource('asignaciones-turnos', AsignacionTurnoController::class)->middleware('acceso.seccion:asignaciones-turnos.index');
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy'])->name('asignaciones-turnos.destroy');
    Route::post('/asignaciones-turno/{id}/actualizar-puesto', [ProduccionController::class, 'actualizarPuesto']);
    Route::post('/fichar', [AsignacionTurnoController::class, 'fichar'])->name('fichar');
    Route::post('/generar-turnos', function (Request $request) {
        Artisan::call('turnos:generar-anuales');
        return back()->with('success', '✅ Turnos generados correctamente.');
    })->name('generar-turnos');
    Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');
    Route::post('/festivos/editar', [VacacionesController::class, 'moverFestivo'])->name('festivos.mover');

    // === MAQUINAS Y PRODUCCIÓN ===
    Route::resource('maquinas', MaquinaController::class)->middleware('acceso.seccion:maquinas.index');
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])->name('maquinas.sesion.guardar');
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');

    Route::get('/produccion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('produccion.trabajadores');
    Route::get('/produccion/maquinas', [ProduccionController::class, 'maquinas'])->name('produccion.maquinas');

    // === MOVIMIENTOS ===
    Route::resource('movimientos', MovimientoController::class)->middleware('acceso.seccion:movimientos.index');
    Route::post('/movimientos/crear', [MovimientoController::class, 'crearMovimiento'])->name('movimientos.crear');

    // === PAQUETES Y ELEMENTOS ===
    Route::resource('paquetes', PaqueteController::class)->middleware('acceso.seccion:paquetes.index');
    Route::resource('etiquetas', EtiquetaController::class)->middleware('acceso.seccion:etiquetas.index');
    Route::resource('elementos', ElementoController::class)->middleware('acceso.seccion:elementos.index');
    Route::post('/elementos/dividir', [ElementoController::class, 'dividirElemento'])->name('elementos.dividir');
    Route::post('/elementos/{elementoId}/solicitar-cambio-maquina', [ElementoController::class, 'solicitarCambioMaquina']);
    Route::put('/elementos/{id}/cambio-maquina', [ElementoController::class, 'cambioMaquina'])->name('elementos.cambioMaquina');
    Route::post('/subetiquetas/crear', [ElementoController::class, 'crearSubEtiqueta'])->name('subetiquetas.crear');
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])->name('elementosEtiquetas');

    // === PLANILLAS Y PLANIFICACIÓN ===
    Route::resource('planillas', PlanillaController::class)->middleware('acceso.seccion:planillas.index');
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.import');
    Route::post('/planillas/reordenar', [PlanillaController::class, 'reordenarPlanillas'])->name('planillas.reordenar');
    Route::resource('planificacion', PlanificacionController::class)->middleware('acceso.seccion:planificacion.index');
    Route::put('/planificacion/comentario/{id}', [PlanificacionController::class, 'guardarComentario']);

    // === EMPRESAS TRANSPORTE ===
    Route::resource('empresas-transporte', EmpresaTransporteController::class)
        ->middleware('acceso.seccion:empresas-transporte.index');
    Route::resource('camiones', CamionController::class)
        ->middleware('acceso.seccion:camiones.index');

    Route::post('/update-field', [EmpresaTransporteController::class, 'updateField'])
        ->middleware('acceso.seccion:empresas-transporte.index')
        ->name('update.field');

    // === SALIDAS Y ESCANEO ===
    Route::resource('salidas', SalidaController::class)->middleware('acceso.seccion:salidas.index');
    Route::delete('/salidas/{salida}/quitar-paquete/{paquete}', [SalidaController::class, 'quitarPaquete'])->name('salidas.quitarPaquete');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaController::class, 'actualizarEstado']);
    Route::post('/actualizar-fecha-salida', [SalidaController::class, 'actualizarFechaSalida']);
    Route::post('/escaneo', [SalidaController::class, 'marcarSubido'])->name('escaneo.marcarSubido');
    Route::get('/salidas/export/{mes}', [SalidaController::class, 'export'])->name('salidas.export');

    // === OBRAS ===
    Route::resource('obras', ObraController::class)->middleware('acceso.seccion:obras.index');

    // === NOMINAS Y FISCALIDAD ===
    Route::resource('empresas', EmpresaController::class)->middleware('acceso.seccion:empresas.index');
    Route::resource('nominas', NominaController::class)->except(['destroy']);
    Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales'])->name('generar.nominas');
    Route::delete('/nominas/borrar-todas', [NominaController::class, 'borrarTodas'])->name('nominas.borrarTodas');
    Route::resource('irpf-tramos', IrpfTramoController::class)->middleware('acceso.seccion:irpf-tramos.index');
    Route::resource('porcentajes-ss', SeguridadSocialController::class)->middleware('acceso.seccion:porcentajes-ss.index');
    Route::get('/simulacion-irpf', [NominaController::class, 'formularioSimulacion'])->name('nomina.simulacion');
    Route::post('/simulacion-irpf', [NominaController::class, 'simular'])->name('nomina.simular');
    Route::get('/simulacion-inversa', [NominaController::class, 'formularioInverso'])->name('nomina.inversa');
    Route::post('/simulacion-inversa', [NominaController::class, 'simularDesdeNeto'])->name('nomina.inversa.calcular');

    // === ALERTAS Y ESTADISTICAS ===
    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.marcarLeidas');
    Route::post('/alertas/store', [AlertaController::class, 'store'])->name('alertas.store');
    Route::get('/alertas/sin-leer', [AlertaController::class, 'sinLeer'])->name('alertas.sinLeer');
    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');

    // === POLÍTICAS Y AYUDA ===
    Route::controller(PoliticaController::class)->group(function () {
        Route::get('/politica-privacidad', 'mostrarPrivacidad')->name('politica.privacidad');
        Route::get('/politica-cookies', 'mostrarCookies')->name('politica.cookies');
        Route::post('/aceptar-politicas', 'aceptar')->name('aceptar.politicas');
    });
    Route::get('/ayuda', [AyudaController::class, 'index'])->name('ayuda.index');

    // === DEPARTAMENTOS Y SECCIONES ===
    Route::resource('departamentos', DepartamentoController::class)->middleware('acceso.seccion');
    Route::post('/departamentos/{departamento}/asignar-usuarios', [DepartamentoController::class, 'asignarUsuarios'])->name('departamentos.asignar.usuarios');
    Route::post('/departamentos/{departamento}/asignar-secciones', [DepartamentoController::class, 'asignarSecciones'])->name('departamentos.asignarSecciones');
    Route::post('/departamentos/{departamento}/permisos', [DepartamentoController::class, 'actualizarPermiso']);


    Route::resource('secciones', SeccionController::class)->middleware('acceso.seccion:secciones.index');

    // === PAPELERA ===
    Route::get('/papelera', [PapeleraController::class, 'index'])->name('papelera.index');
    Route::put('/papelera/restore/{model}/{id}', [PapeleraController::class, 'restore'])->name('papelera.restore');
});


require __DIR__ . '/auth.php';
