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
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    // ===== USUARIOS =====
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/users/{id}', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('users', ProfileController::class)->middleware('acceso.seccion:users.index');
    Route::resource('vacaciones', VacacionesController::class)->middleware('acceso.seccion:vacaciones.index');
    Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store'])->name('vacaciones.solicitar');
    Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar'])->name('vacaciones.aprobar');
    Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar'])->name('vacaciones.denegar');
    Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');
    Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento'])->name('vacaciones.eliminarEvento');
    Route::get('/users/{user}/resumen-asistencia', [ProfileController::class, 'resumenAsistencia']);
    Route::get('/users/{user}/eventos-turnos', [ProfileController::class, 'eventosTurnos']);
    Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');

    // ===== PRODUCTOS =====
    Route::resource('fabricantes', FabricanteController::class)->middleware('acceso.seccion:fabricantes.index');
    Route::resource('productos-base', ProductoBaseController::class)->middleware('acceso.seccion:productos-base.index');
    Route::resource('productos', ProductoController::class)->middleware('acceso.seccion:productos.index');
    Route::post('/productos/crear-desde-recepcion', [PedidoController::class, 'crearDesdeRecepcion'])->name('productos.crear.desde.recepcion');
    Route::get('productos/{id}/consumir', [ProductoController::class, 'consumir']);
    Route::post('/productos/generar-exportar', [ProductoController::class, 'generarYExportar'])->name('productos.generar.exportar');

    // ===== PEDIDOS =====
    Route::resource('entradas', EntradaController::class)->middleware('acceso.seccion:entradas.index');
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');
    Route::resource('pedidos_globales', PedidoGlobalController::class)->middleware('acceso.seccion:pedidos-globales.index');
    Route::resource('pedidos', PedidoController::class)->middleware('acceso.seccion:pedidos.index');
    Route::post('/pedidos/confirmar', [PedidoController::class, 'confirmar'])->name('pedidos.confirmar');
    Route::get('pedidos/{pedido}/recepcion', [PedidoController::class, 'recepcion'])->name('pedidos.recepcion');
    Route::post('pedidos/{pedido}/recepcion', [PedidoController::class, 'procesarRecepcion'])->name('pedidos.recepcion.guardar');
    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.enviarCorreo');
    Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.preview');
    Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar'])->name('pedidos.activar');

    // ===== UBICACIONES =====
    Route::resource('ubicaciones', UbicacionController::class)->middleware('acceso.seccion:ubicaciones.index');
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    // ===== PRODUCCION =====
    Route::resource('maquinas', MaquinaController::class)->middleware('acceso.seccion:maquinas.index');
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');
    Route::resource('etiquetas', EtiquetaController::class)->middleware('acceso.seccion:etiquetas.index');
    Route::resource('planillas', PlanillaController::class)->middleware('acceso.seccion:planillas.index');
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.import');
    Route::post('/planillas/reordenar', [PlanillaController::class, 'reordenarPlanillas'])->name('planillas.reordenar');
    Route::resource('elementos', ElementoController::class)->middleware('acceso.seccion:elementos.index');
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas']);
    Route::post('/elementos/dividir', [ElementoController::class, 'dividirElemento'])->name('elementos.dividir');
    Route::post('/elementos/{elementoId}/solicitar-cambio-maquina', [ElementoController::class, 'solicitarCambioMaquina']);
    Route::put('/elementos/{id}/cambio-maquina', [ElementoController::class, 'cambioMaquina'])->name('elementos.cambioMaquina');
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion']);
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');
    Route::get('/produccion/maquinas', [ProduccionController::class, 'maquinas'])->name('produccion.maquinas');

    // ===== MOVIMIENTOS =====
    Route::resource('movimientos', MovimientoController::class)->middleware('acceso.seccion:movimientos.index');
    Route::post('/movimientos/crear', [MovimientoController::class, 'crearMovimiento'])->name('movimientos.crear');

    // ===== ASIGNACIONES =====
    Route::resource('asignaciones-turnos', AsignacionTurnoController::class)->middleware('acceso.seccion:asignaciones-turnos.index');
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy']);
    Route::post('/fichar', [AsignacionTurnoController::class, 'fichar'])->name('fichar');

    // ===== LOGISTICA =====
    Route::resource('salidas', SalidaController::class)->middleware('acceso.seccion:salidas.index');
    Route::delete('/salidas/{salida}/quitar-paquete/{paquete}', [SalidaController::class, 'quitarPaquete']);
    Route::resource('planificacion', PlanificacionController::class)->middleware('acceso.seccion:planificacion.index');
    Route::put('/planificacion/comentario/{id}', [PlanificacionController::class, 'guardarComentario']);
    Route::get('/salidas/export/{mes}', [\App\Http\Controllers\SalidaController::class, 'export'])->name('salidas.export');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaController::class, 'actualizarEstado']);
    Route::resource('camiones', CamionController::class)->middleware('acceso.seccion:camiones.index');
    Route::resource('empresas-transporte', EmpresaTransporteController::class)->middleware('acceso.seccion:empresas-transporte.index');

    // ===== CLIENTES Y PROVEEDORES =====
    Route::resource('clientes', ClienteController::class)->middleware('acceso.seccion:clientes.index');
    Route::resource('proveedores', ProveedorController::class)->middleware('acceso.seccion:proveedores.index');
    Route::resource('empresas', EmpresaController::class)->middleware('acceso.seccion:empresas.index');

    // ===== ADMINISTRACION =====
    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');
    Route::resource('nominas', NominaController::class)->except(['destroy']);
    Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales'])->name('generar.nominas');
    Route::delete('/nominas/borrar-todas', [NominaController::class, 'borrarTodas'])->name('nominas.borrarTodas');
    Route::resource('irpf-tramos', IrpfTramoController::class)->middleware('acceso.seccion:irpf-tramos.index');
    Route::resource('porcentajes-ss', SeguridadSocialController::class)->middleware('acceso.seccion:porcentajes-ss.index');

    // ===== OTROS =====
    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.marcarLeidas');
    Route::post('/alertas/store', [AlertaController::class, 'store'])->name('alertas.store');
    Route::get('/alertas/sin-leer', [AlertaController::class, 'sinLeer'])->name('alertas.sinLeer');
    Route::get('/politica-privacidad', 'mostrarPrivacidad')->name('politica.privacidad');
    Route::get('/politica-cookies', 'mostrarCookies')->name('politica.cookies');
    Route::resource('departamentos', DepartamentoController::class)->middleware('acceso.seccion:departamentos.index');
    Route::post('/departamentos/{departamento}/asignar-usuarios', [DepartamentoController::class, 'asignarUsuarios']);
    Route::post('/departamentos/{departamento}/asignar-secciones', [DepartamentoController::class, 'asignarSecciones']);
    Route::resource('secciones', SeccionController::class)->middleware('acceso.seccion:secciones.index');
    Route::get('/ayuda', [AyudaController::class, 'index'])->name('ayuda.index');

    // ===== SIN_CLASIFICAR =====
});



require __DIR__ . '/auth.php';
