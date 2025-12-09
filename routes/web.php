<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PapeleraController;
use App\Http\Controllers\VacacionesController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\ColadaController;
use App\Http\Controllers\ProductoBaseController;
use App\Http\Controllers\PedidoGlobalController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\LocalizacionController;
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
use App\Http\Controllers\SalidaFerrallaController;
use App\Http\Controllers\SalidaAlmacenController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\AsignacionTurnoController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DistribuidorController;
use App\Http\Controllers\PoliticaController;
use App\Http\Controllers\AyudaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\IrpfTramoController;
use App\Http\Controllers\SeguridadSocialController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\FestivoController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\ClaveSeccionController;
use App\Http\Controllers\PedidoAlmacenVentaController;
use App\Http\Controllers\ClienteAlmacenController;
use App\Http\Controllers\FabricacionLogController;
use App\Http\Controllers\AtajosController;
use App\Http\Controllers\FcmTokenController;
use App\Services\PlanillaService;
use Illuminate\Support\Facades\Log;

Route::get('/', [PageController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Rutas de secciones principales
Route::get('/produccion', [PageController::class, 'produccion'])->middleware(['auth', 'verified'])->name('secciones.produccion');
Route::get('/seccion-planificacion', [PageController::class, 'planificacionSeccion'])->middleware(['auth', 'verified'])->name('secciones.planificacion');
Route::get('/logistica', [PageController::class, 'logistica'])->middleware(['auth', 'verified'])->name('secciones.logistica');
Route::get('/recursos-humanos', [PageController::class, 'recursosHumanos'])->middleware(['auth', 'verified'])->name('secciones.recursos-humanos');
Route::get('/incorporaciones', function () {
    return redirect()->route('secciones.recursos-humanos');
})->middleware(['auth', 'verified'])->name('incorporaciones.index');
Route::get('/atajos', [PageController::class, 'atajos'])->middleware(['auth', 'verified'])->name('atajos.index');
Route::get('/comercial', [PageController::class, 'comercial'])->middleware(['auth', 'verified'])->name('secciones.comercial');
Route::get('/sistema', [PageController::class, 'sistema'])->middleware(['auth', 'verified'])->name('secciones.sistema');

// Atajos de teclado
Route::get('/atajos', [AtajosController::class, 'index'])->middleware(['auth', 'verified'])->name('atajos.index');

// Rutas antiguas redirigidas (compatibilidad)
Route::get('/inventario', function() {
    return redirect()->route('secciones.produccion');
})->middleware(['auth', 'verified'])->name('secciones.inventario');

Route::get('/compras', function() {
    return redirect()->route('secciones.logistica');
})->middleware(['auth', 'verified'])->name('secciones.compras');

// Ruta del Asistente Virtual
use App\Http\Controllers\AsistenteVirtualController;

Route::get('/asistente', [AsistenteVirtualController::class, 'index'])
    ->middleware(['auth', 'puede.asistente'])
    ->name('asistente.index');

// Administración de permisos del Asistente (solo admins)
Route::get('/asistente/permisos', [AsistenteVirtualController::class, 'administrarPermisos'])
    ->middleware(['auth'])
    ->name('asistente.permisos');

// API del Asistente Virtual
// Rate limiting: 60 requests por minuto general, 15 para envío de mensajes
Route::middleware(['auth', 'puede.asistente', 'throttle:60,1'])
    ->prefix('api/asistente')->group(function () {
        Route::get('/conversaciones', [AsistenteVirtualController::class, 'obtenerConversaciones']);
        Route::post('/conversaciones', [AsistenteVirtualController::class, 'crearConversacion']);
        Route::get('/conversaciones/{conversacionId}/mensajes', [AsistenteVirtualController::class, 'obtenerMensajes']);
        Route::delete('/conversaciones/{conversacionId}', [AsistenteVirtualController::class, 'eliminarConversacion']);
        Route::get('/sugerencias', [AsistenteVirtualController::class, 'sugerencias'])->name('asistente.sugerencias');
        Route::post('/preguntar', [AsistenteVirtualController::class, 'preguntar'])->name('asistente.preguntar');
        Route::post('/permisos/{userId}', [AsistenteVirtualController::class, 'actualizarPermisos']);

        // Ruta de envío de mensajes con rate limiting más estricto
        Route::post('/mensaje', [AsistenteVirtualController::class, 'enviarMensaje'])
            ->middleware('throttle:15,1'); // Solo 15 mensajes por minuto
    });

// === FCM (Firebase Cloud Messaging) ===
Route::middleware(['auth'])->prefix('api/fcm')->group(function () {
    Route::post('/token', [FcmTokenController::class, 'store'])->name('fcm.token.store');
    Route::delete('/token', [FcmTokenController::class, 'destroy'])->name('fcm.token.destroy');
    Route::get('/config', [FcmTokenController::class, 'config'])->name('fcm.config');
    Route::post('/test', [FcmTokenController::class, 'sendTest'])->name('fcm.test');
});

Route::middleware(['auth', 'acceso.seccion'])->group(function () {
    // === PERFIL DE USUARIO ===
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/users/{id}', [ProfileController::class, 'update']) ->name('profile.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/exportar-usuarios', [ProfileController::class, 'exportarUsuarios'])->name('users.verExportar');

    // === CLIENTES Y PROVEEDORES ===
    Route::resource('clientes', ClienteController::class)->names('clientes');
    Route::resource('fabricantes', FabricanteController::class)->names('fabricantes');
    Route::resource('distribuidores', DistribuidorController::class)->names('distribuidores');

    // === ENTRADAS Y PEDIDOS ===
    Route::get('/entradas/pdf/filtrados', [EntradaController::class, 'descargarPdfFiltrados'])
        ->name('entradas.descargarPdfFiltrados');
    Route::resource('entradas', EntradaController::class)->names('entradas');
    Route::get('/entradas/{id}/verificar-discrepancias', [EntradaController::class, 'verificarDiscrepancias'])->name('entradas.verificarDiscrepancias');
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');
    Route::post('/entradas/importar-albaran', [EntradaController::class, 'subirPdf'])
        ->name('entradas.crearImportarAlbaranPdf');
    Route::get('/entradas/pdf/{id}', [EntradaController::class, 'descargarPdf'])->name('entradas.crearDescargarPdf');
    Route::get('/pedidos/stock-html', [PedidoController::class, 'obtenerStockHtml'])->name('pedidos.verStockHtml');
    Route::resource('pedidos_globales', PedidoGlobalController::class);
    Route::resource('pedidos', PedidoController::class);
    Route::get('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'recepcion'])->name('pedidos.recepcion');
    Route::post('/pedidos/sugerir-pedido-global', [PedidoController::class, 'sugerirPedidoGlobal'])->name('pedidos.verSugerir-pedido-global');

    Route::post('/pedidos/{pedido}/actualizar-linea', [PedidoController::class, 'actualizarLinea'])
        ->name('pedidos.actualizarLinea');
    Route::patch('/pedidos/{id}/observaciones', [PedidoController::class, 'actualizarObservaciones'])
        ->name('pedidos.actualizarObservaciones');
    // Procesar la recepción del producto base
    Route::post('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'procesarRecepcion'])->name('pedidos.recepcion.guardar');

    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.crearEnviarCorreo');
    // Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.verPreview');
    // Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar']) ->name('pedidos.activar');
    // Activar una línea concreta del pedido
    Route::put('/pedidos/{pedido}/lineas/{linea}/activar', [PedidoController::class, 'activar'])->name('pedidos.lineas.editarActivar');
    Route::post('/pedidos/{pedido}/lineas/{linea}/activar-con-coladas', [PedidoController::class, 'activarConColadas'])->name('pedidos.lineas.activarConColadas');

    // Desactivar una línea concreta del pedido
    Route::delete('/pedidos/{pedido}/lineas/{linea}/desactivar', [PedidoController::class, 'desactivar'])->name('pedidos.lineas.editarDesactivar');

    // Completar manualmente un pedido que no es para una nave de paco reyes

    Route::post('pedidos/{pedido}/lineas/{linea}/completar', [PedidoController::class, 'completarLineaManual'])->name('pedidos.editarCompletarLineaManual');

    // === CANCELAR PEDIDO COMPLETO ===
    Route::put('/pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelarPedido'])->name('pedidos.cancelar');

    // === LINEAS DEL PEDIDO ===
    Route::put('/pedidos/{pedido}/lineas/{linea}/cancelar', [PedidoController::class, 'cancelarLinea'])->name('pedidos.lineas.editarCancelar');

    // === PRODUCTOS Y UBICACIONES ===
    Route::resource('fabricantes', FabricanteController::class);
    Route::resource('productos-base', ProductoBaseController::class);

    // Rutas específicas de productos (antes del resource)
    Route::get('productos/{id}/edit-data', [ProductoController::class, 'getEditData'])->name('productos.getEditData');
    Route::get('productos/{id}/consumir', [ProductoController::class, 'consumir'])->name('productos.editarConsumir');
    Route::post('productos/consumir-lote', [ProductoController::class, 'consumirLoteAjax'])->name('productos.consumirLote');

    Route::resource('productos', ProductoController::class);
    // Route::post('/productos/crear-desde-recepcion', [PedidoController::class, 'crearDesdeRecepcion'])->name('productos.crear.desde.recepcion');
    // Route::post('/solicitar-stock', [ProductoController::class, 'solicitarStock'])->name('solicitar.stock');
    Route::post('productos/generar-exportar', [ProductoController::class, 'GenerarYExportar'])->name('productos.generar.crearExportar');
    Route::post('productos/generar-datos', [ProductoController::class, 'GenerarYObtenerDatos'])->name('productos.generar.datos');
    Route::post('/productos/{codigo}/reasignar', [ProductoController::class, 'editarUbicacionInventario'])
        ->name('productos.editarUbicacionInventario');
    Route::post('/productos/{codigo}/restablecer', [ProductoController::class, 'restablecerDesdeInventario'])
        ->name('productos.restablecerInventario');
    Route::post('/productos/{codigo}/liberar-maquina', [ProductoController::class, 'liberarFabricandoInventario'])
        ->name('productos.liberarMaquinaInventario');

    // === COLADAS ===
    Route::get('coladas', [ColadaController::class, 'index'])->name('coladas.index');
    Route::post('coladas', [ColadaController::class, 'store'])->name('coladas.store');
    Route::put('coladas/{colada}', [ColadaController::class, 'update'])->name('coladas.update');
    Route::delete('coladas/{colada}', [ColadaController::class, 'destroy'])->name('coladas.destroy');
    Route::get('coladas/{colada}/descargar', [ColadaController::class, 'descargarDocumento'])->name('coladas.descargar');

    Route::get('/ubicaciones/inventario', [UbicacionController::class, 'inventario'])->name('ubicaciones.verInventario');
    Route::resource('ubicaciones', UbicacionController::class);
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    // === LOCALIZACIONES ===
    Route::resource('localizaciones', LocalizacionController::class);
    Route::get('/localizaciones/editar-mapa', [LocalizacionController::class, 'editarMapa'])->name('localizaciones.editarMapa');
    Route::post('/localizaciones/verificar', [LocalizacionController::class, 'verificar'])->name('localizaciones.verificar');

    Route::post('/localizaciones-paquetes/{codigo}', [PaqueteController::class, 'update'])->name('localizaciones_paquetes.update');
    Route::post('/localizaciones/store-paquete', [LocalizacionController::class, 'storePaquete'])->name('localizaciones.storePaquete');
    Route::get('/api/mapa-nave/{naveId}', [LocalizacionController::class, 'obtenerDatosMapaNave'])->name('api.mapaNave');
    // === USUARIOS Y VACACIONES ===

    Route::resource('users', ProfileController::class)->except(['create', 'store']);
    Route::get('/users/create', function () {
        return redirect()->route('register');
    })->name('users.create');
    Route::put('/actualizar-usuario/{id}', [ProfileController::class, 'actualizarUsuario'])->name('usuarios.updateActualizar');
    Route::get('/users/{user}/resumen-asistencia', [ProfileController::class, 'resumenAsistencia'])->name('users.verResumen-asistencia');
    Route::get('/users/{user}/eventos-turnos', [ProfileController::class, 'eventosTurnos'])->name('users.verEventos-turnos');
    Route::post('/usuarios/{user}/cerrar-sesiones', [ProfileController::class, 'cerrarSesionesDeUsuario'])->name('usuarios.cerrarSesiones');
    Route::post('/usuarios/{user}/despedir', [ProfileController::class, 'despedirUsuario'])->name('usuarios.editarDespedir');
    Route::post('/usuario/subir-imagen', [ProfileController::class, 'subirImagen'])->name('usuarios.editarSubirImagen');
    Route::get('/perfil/imagen/{nombre}', function ($nombre) {
        $path = storage_path("app/public/perfiles/{$nombre}");

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path); // envía con Content-Type correcto
    })->name('usuarios.imagen');
    Route::get('/mi-perfil/{user}', [PerfilController::class, 'show'])->name('usuarios.show');

    // Rutas específicas de vacaciones (DEBEN ir ANTES del resource)
    Route::get('/vacaciones/usuarios-con-vacaciones', [VacacionesController::class, 'usuariosConVacaciones'])->name('vacaciones.usuariosConVacaciones');
    Route::get('/vacaciones/eventos', [VacacionesController::class, 'eventos'])->name('vacaciones.eventos');
    Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store'])->name('vacaciones.solicitar');
    Route::post('/vacaciones/asignar-directo', [VacacionesController::class, 'asignarDirecto'])->name('vacaciones.asignarDirecto');
    Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');
    Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento'])->name('vacaciones.eliminarEvento');
    Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar'])->name('vacaciones.editarAprobar');
    Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar'])->name('vacaciones.editarDenegar');

    // Resource de vacaciones (al final para que no capture las rutas específicas)
    Route::resource('vacaciones', VacacionesController::class);

    // === TURNOS ===
    Route::resource('turnos', TurnoController::class);
    Route::patch('turnos/{turno}/toggle', [TurnoController::class, 'toggleActivo'])->name('turnos.toggle');
    Route::resource('asignaciones-turnos', AsignacionTurnoController::class);
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy'])->name('asignaciones-turnos.destroy');
    Route::post('/asignaciones-turno/{id}/actualizar-puesto', [ProduccionController::class, 'actualizarPuesto']);
    Route::post('/fichar', [AsignacionTurnoController::class, 'fichar'])->name('users.fichar');
    Route::post('/generar-turnos', function (Request $request) {
        Artisan::call('turnos:generar-anuales');
        return back()->with('success', 'Turnos generados correctamente.');
    })->name('generar-turnos');
    Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');
    Route::post('/profile/generar-turnos-calendario', [ProfileController::class, 'generarTurnosCalendario'])->name('profile.generar.turnos.calendario');
    Route::get('/api/usuarios/operarios', [ProfileController::class, 'getOperarios'])->name('api.usuarios.operarios');
    Route::get('/api/usuarios/operarios-agrupados', [ProfileController::class, 'getOperariosAgrupados'])->name('api.usuarios.operarios.agrupados');
    Route::post('/festivos/editar', [VacacionesController::class, 'moverFestivo'])->name('festivos.mover');
    Route::put('/festivos/{festivo}/fecha', [FestivoController::class, 'actualizarFecha'])->name('festivos.actualizarFecha');
    Route::delete('/festivos/{festivo}', [FestivoController::class, 'destroy'])->name('festivos.eliminar');
    Route::post('/festivos', [FestivoController::class, 'store'])->name('festivos.store');
    Route::post('/asignaciones-turno/asignar-obra', [AsignacionTurnoController::class, 'asignarObra'])->name('asignaciones-turnos.asignarObra');
    Route::post('/asignaciones-turno/asignar-multiple', [AsignacionTurnoController::class, 'asignarObraMultiple'])->name('asignaciones-turnos.asignarObraMultiple');
    Route::put('/asignaciones-turno/{id}/quitar-obra', [AsignacionTurnoController::class, 'quitarObra'])->name('asignaciones-turnos.quitarObra');
    Route::put('/asignaciones-turnos/{id}/update-obra', [AsignacionTurnoController::class, 'updateObra'])->name('asignaciones-turnos.update-obra');
    Route::post('/asignaciones-turno/repetir-semana', [AsignacionTurnoController::class, 'repetirSemana'])->name('asignaciones-turnos.repetirSemana');
    Route::post('/asignaciones-turno/repetir-semana-obra', [AsignacionTurnoController::class, 'repetirSemanaObra'])->name('asignaciones-turnos.repetirSemanaObra');
    Route::post('/asignaciones-turno/mover-eventos', [AsignacionTurnoController::class, 'moverEventosAObra'])->name('asignaciones-turnos.moverEventos');
    Route::post('/asignaciones-turno/{id}/actualizar-horas', [AsignacionTurnoController::class, 'actualizarHoras'])->name('asignaciones-turnos.actualizar-horas');
    Route::get('/asignaciones-turno/exportar', [AsignacionTurnoController::class, 'export'])->name('asignaciones-turnos.verExportar');

    // === MAQUINAS Y PRODUCCIÓN ===
    Route::resource('maquinas', MaquinaController::class);
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');
    Route::get('/maquinas/{id}/elementos-pendientes', [MaquinaController::class, 'elementosPendientes'])->name('maquinas.elementosPendientes');
    Route::post('/maquinas/{id}/redistribuir', [MaquinaController::class, 'redistribuir'])->name('maquinas.redistribuir');
    Route::post('/maquinas/{id}/completar-planilla', [MaquinaController::class, 'completarPlanillaManual'])->name('maquinas.completar-planilla');
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])->name('maquinas.sesion.guardar');
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');
    Route::post('/turnos/cambiar-maquina', [Maquinacontroller::class, 'cambiarMaquina'])->name('turno.cambiarMaquina');
    Route::put('/maquinas/{maquina}/imagen', [MaquinaController::class, 'actualizarImagen'])->name('maquinas.imagen');
    Route::get('/planillas/eventos', [ProduccionController::class, 'eventosPlanillas'])
        ->name('planillas.eventos');

    Route::get('/produccion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('produccion.verTrabajadores');
    Route::get('/produccion/trabajadores-obra', [ProduccionController::class, 'trabajadoresObra'])->name('produccion.verTrabajadoresObra');
    Route::get('/produccion/maquinas', [ProduccionController::class, 'maquinas'])->name('produccion.verMaquinas');
    Route::get('/produccion/cargas-maquinas', [ProduccionController::class, 'cargasMaquinas'])->name('produccion.cargasMaquinas');

    // Endpoints dinámicos para el calendario de máquinas
    Route::get('/api/produccion/maquinas/recursos', [ProduccionController::class, 'obtenerRecursos'])->name('api.produccion.recursos');
    Route::get('/api/produccion/maquinas/eventos', [ProduccionController::class, 'obtenerEventos'])->name('api.produccion.eventos');

    // Endpoints de optimización de planillas
    Route::get('/api/produccion/optimizar-analisis', [ProduccionController::class, 'optimizarAnalisis'])->name('api.produccion.optimizar.analisis');
    Route::post('/api/produccion/optimizar-aplicar', [ProduccionController::class, 'optimizarAplicar'])->name('api.produccion.optimizar.aplicar');

    // Endpoints de balanceo de carga entre máquinas
    Route::get('/api/produccion/balancear-carga-analisis', [ProduccionController::class, 'balancearCargaAnalisis'])->name('api.produccion.balancear.analisis');
    Route::post('/api/produccion/balancear-carga-aplicar', [ProduccionController::class, 'aplicarBalanceoCarga'])->name('api.produccion.balancear.aplicar');

    // Endpoint de resumen del calendario
    Route::get('/api/produccion/resumen', [ProduccionController::class, 'obtenerResumen'])->name('api.produccion.resumen');

    // Endpoints de priorización de obras
    Route::get('/api/produccion/obras-activas', [ProduccionController::class, 'obrasConPlanillasActivas'])->name('api.produccion.obras-activas');
    Route::post('/api/produccion/priorizar-obra', [ProduccionController::class, 'priorizarObra'])->name('api.produccion.priorizar-obra');
    Route::post('/api/produccion/priorizar-obras', [ProduccionController::class, 'priorizarObras'])->name('api.produccion.priorizar-obras');

    //MSR20 BVBS
    Route::get('/maquinas/{maquina}/exportar-bvbs', [MaquinaController::class, 'exportarBVBS'])
        ->name('maquinas.exportar-bvbs');

    // === MOVIMIENTOS ===
    Route::resource('movimientos', MovimientoController::class);
    Route::post('/movimientos/crear', [MovimientoController::class, 'crearMovimiento'])->name('movimientos.crear');
    Route::put('/movimientos/{id}/completar-preparacion', [MovimientoController::class, 'completarPreparacion'])->name('movimientos.completar-preparacion');
    Route::put('/movimientos/{id}/completar', [MovimientoController::class, 'completar'])->name('movimientos.completar');
    Route::get('/movimientos/{id}/etiquetas-paquete', [MovimientoController::class, 'getEtiquetasPaquete'])->name('movimientos.etiquetas-paquete');
    Route::get('/movimientos/{id}/etiquetas-elementos-sin-elaborar', [MovimientoController::class, 'getEtiquetasElementosSinElaborar'])->name('movimientos.etiquetas-elementos-sin-elaborar');

    // === PAQUETES ETIQUETAS Y ELEMENTOS ===

    Route::post('/elementos/dividir', [ElementoController::class, 'dividirElemento'])->name('elementos.dividir');
    Route::post('/elementos/{elementoId}/solicitar-cambio-maquina', [ElementoController::class, 'solicitarCambioMaquina']);
    Route::put('/elementos/{id}/cambio-maquina', [ElementoController::class, 'cambioMaquina'])->name('elementos.cambioMaquina');
    Route::post('/subetiquetas/crear', [ElementoController::class, 'crearSubEtiqueta'])->name('subetiquetas.crear');
    Route::post('/subetiquetas/mover-todo', [ElementoController::class, 'moverTodoANuevaSubEtiqueta'])->name('subetiquetas.moverTodo');
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])->name('elementosEtiquetas');
    Route::put('/actualizar-etiqueta/{id}/maquina/{maquina_id}', [EtiquetaController::class, 'actualizarEtiqueta'])->where('id', '.*')->name('etiquetas.actualizarMaquina');
    Route::post('/etiquetas/fabricacion-optimizada', [EtiquetaController::class, 'fabricacionSyntaxLine28'])->name('etiquetas.fabricacion-optimizada');
    Route::post('/elementos/{elemento}/actualizar-campo', [ElementoController::class, 'actualizarMaquina'])->name('elementos.editarMaquina');
    Route::post('/etiquetas/{etiqueta}/patron-corte-simple', [EtiquetaController::class, 'calcularPatronCorteSimple'])->name('etiquetas.calcularPatronCorteSimple');
    Route::post('/etiquetas/{etiqueta}/patron-corte-optimizado', [EtiquetaController::class, 'calcularPatronCorteOptimizado'])->name('etiquetas.calcularPatronCorteOptimizado');
    // ruta para renderizar una etiqueta en HTML
    Route::post('/etiquetas/render', [EtiquetaController::class, 'render'])
        ->name('etiquetas.render');

    Route::get('/elementos/por-ids', [ProduccionController::class, 'porIds'])->name('elementos.verPorIds');
    Route::get(
        '/etiquetas/{etiquetaSubId}/validar-para-paquete',
        [PaqueteController::class, 'validarParaPaquete']
    )->name('etiquetas.validar-para-paquete');

    // === tratamos PAQUETES en maquinas.show ===

    Route::resource('paquetes', PaqueteController::class);

    // API: Obtener elementos de un paquete
    Route::get('/paquetes/{paqueteId}/elementos', [PaqueteController::class, 'getElementos'])
        ->name('paquetes.getElementos');

    // 🔥 RUTA MIGRADA A LIVEWIRE - index ahora usa Livewire (sin recargar página)
    Route::get('/etiquetas', [EtiquetaController::class, 'index'])->name('etiquetas.index');

    // Resto de rutas del resource (show, create, edit, update, destroy)
    Route::resource('etiquetas', EtiquetaController::class)->except(['index']);

    // 🔥 RUTA MIGRADA A LIVEWIRE - index ahora usa Livewire (sin recargar página)
    Route::get('/elementos', [ElementoController::class, 'index'])->name('elementos.index');

    // Resto de rutas del resource (show, create, edit, update, destroy)
    Route::resource('elementos', ElementoController::class)->except(['index']);

    // RUTAS PROVISIONALES
    Route::post('/etiquetas/fabricar-lote', [EtiquetaController::class, 'fabricarLote'])->name('maquinas.fabricarLote');
    Route::post('/etiquetas/completar-lote', [EtiquetaController::class, 'completarLote'])->name('maquinas.completarLote');

    Route::get('/planillas/informacion', [PlanillaController::class, 'informacionMasiva'])->name('planillas.editarInformacionMasiva');

    Route::put('/planillas/fechas', [PlanillaController::class, 'actualizarFechasMasiva'])->name('planillas.editarActualizarFechasMasiva');
    Route::post('/paquetes/tamaño', [PaqueteController::class, 'tamaño'])
        ->name('paquetes.tamaño');

    // === PLANILLAS Y PLANIFICACIÓN ===
    Route::post('/planillas/{planilla}/marcar-revisada', [PlanillaController::class, 'marcarRevisada'])->name('planillas.marcarRevisada');
    Route::resource('planillas', PlanillaController::class);
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.crearImport');
    Route::post('/planillas/reordenar', [ProduccionController::class, 'reordenarPlanillas'])->name('planillas.editarReordenar');
    Route::resource('planificacion', PlanificacionController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::put('/planificacion/comentario/{id}', [PlanificacionController::class, 'guardarComentario']);
    Route::put('/planificacion/empresa-transporte/{id}', [PlanificacionController::class, 'actualizarEmpresaTransporte'])->name('planificacion.actualizarEmpresaTransporte');
    Route::post('/planillas/{planilla}/reimportar', [PlanillaController::class, 'reimportar'])->name('planillas.crearReimportar');
    Route::post('/planillas/completar', [PlanillaController::class, 'completar'])->name('planillas.completar');
    Route::get('/planificacion/index', [PlanificacionController::class, 'index'])->name('planificacion.index');
    Route::get('/planificacion/totales', [PlanificacionController::class, 'getTotalesAjax']);
    Route::post('/planificacion/simular-adelanto', [PlanificacionController::class, 'simularAdelanto'])->name('planificacion.simularAdelanto');
    Route::post('/planificacion/ejecutar-adelanto', [PlanificacionController::class, 'ejecutarAdelanto'])->name('planificacion.ejecutarAdelanto');
    Route::post('/planillas/completar-todas', [PlanillaController::class, 'completarTodas'])
        ->name('planillas.completarTodas');
    // === EMPRESAS TRANSPORTE ===
    Route::resource('empresas-transporte', EmpresaTransporteController::class);
    Route::resource('camiones', CamionController::class);

    Route::post('/update-field', [EmpresaTransporteController::class, 'updateField'])->name('empresas-transporte.editarField');

    // === SALIDAS FERRALLA ===
    // Rutas específicas ANTES del resource (para evitar conflictos)
    Route::get('/salidas-ferralla/gestionar-salidas', [SalidaFerrallaController::class, 'gestionarSalidas'])->name('salidas-ferralla.gestionar-salidas');
    Route::get('/salidas/export/{mes}', [SalidaFerrallaController::class, 'export'])->name('salidas.export');

    Route::resource('salidas-ferralla', SalidaFerrallaController::class);
    Route::delete('/salidas/{salida}/quitar-paquete/{paquete}', [SalidaFerrallaController::class, 'quitarPaquete'])->name('salidas.editarQuitarPaquete');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaFerrallaController::class, 'editarActualizarEstado']);
    Route::post('/actualizar-fecha-salida', [SalidaFerrallaController::class, 'actualizarFechaSalida'])->name('salidas.actualizarFechaSalida');
    Route::post('/escaneo', [SalidaFerrallaController::class, 'marcarSubido'])->name('escaneo.marcarSubido');
    Route::post('/planificacion/crear-salida-desde-calendario', [SalidaFerrallaController::class, 'crearSalidaDesdeCalendario'])->name('planificacion.crearSalidaDesdeCalendario');
    Route::post('/planificacion/guardar-asignaciones-paquetes', [SalidaFerrallaController::class, 'guardarAsignacionesPaquetes'])->name('planificacion.guardarAsignacionesPaquetes');
    Route::get('/planificacion/informacion-paquetes-salida', [SalidaFerrallaController::class, 'informacionPaquetesSalida'])->name('planificacion.informacionPaquetesSalida');
    Route::post('/planificacion/guardar-paquetes-salida', [SalidaFerrallaController::class, 'guardarPaquetesSalida'])->name('planificacion.guardarPaquetesSalida');
    Route::post('/salidas/crear-salidas-vacias-masivo', [SalidaFerrallaController::class, 'crearSalidasVaciasMasivo'])->name('salidas.crearSalidasVaciasMasivo');
    Route::put('/salidas/completar-desde-movimiento/{movimientoId}', [SalidaFerrallaController::class, 'completarDesdeMovimiento']);
    Route::get('/salidas/{salidaId}/paquetes', [SalidaFerrallaController::class, 'paquetesPorSalida'])->name('salidas.paquetes');
    Route::get('/salidas/{salidaId}/mapa/{naveId}', [SalidaFerrallaController::class, 'obtenerMapaNave'])->name('salidas.mapaNave');
    Route::post('/salidas/validar-subetiqueta', [SalidaFerrallaController::class, 'validarSubetiquetaParaSalida'])->name('salidas.validarSubetiqueta');
    Route::put('/salidas/{salida}/codigo-sage', [SalidaFerrallaController::class, 'actualizarCodigoSage'])->name('salidas.editarCodigoSage');

    // === SALIDAS ALMACEN ===
    Route::get('salidas-almacen/disponibilidad', [SalidaAlmacenController::class, 'disponibilidad'])
        ->name('salidas-almacen.verDisponibilidad');

    Route::resource('salidas-almacen', SalidaAlmacenController::class);
    Route::post('/salidas-almacen/crear-desde-lineas', [SalidaAlmacenController::class, 'crearDesdeLineas'])
        ->name('salidas-almacen.crear-desde-lineas');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaAlmacenController::class, 'editarActualizarEstado'])
        ->name('salidas.editarActualizarEstado');

    Route::put('/salidas/{salida}/codigo-sage', [SalidaAlmacenController::class, 'actualizarCodigoSage'])
        ->name('salidas.editarCodigoSage');
    Route::post('/salidas-almacen/{salida}/lineas/{linea}/activar', [SalidaAlmacenController::class, 'activarLinea'])->name('salidas-almacen.linea.editarActivar');
    Route::post('/salidas-almacen/{salida}/lineas/{linea}/cancelar', [SalidaAlmacenController::class, 'cancelarLinea'])->name('salidas-almacen.linea.editarCancelar');
    Route::post('/salidas-almacen/{salida}/lineas/{linea}/desactivar', [SalidaAlmacenController::class, 'desactivarLinea'])->name('salidas-almacen.linea.editarDesactivar');

    Route::get('/salidas-eventos', [SalidaAlmacenController::class, 'eventos'])
        ->name('api.salidas.eventos');
    Route::put('/salidas-eventos/{salida}', [SalidaAlmacenController::class, 'actualizarFecha'])
        ->name('salidas-eventos.update');
    // Rutas con name bien formado (ver/editar en el name, no en el método)
    Route::get(
        '/salidas-almacen/{salida}/asignados',
        [SalidaAlmacenController::class, 'productosAsignados']
    )->name('salidas-almacen.verAsignados');

    Route::get(
        '/salidas-almacen/{movimiento}/productos',
        [SalidaAlmacenController::class, 'productosPorMovimiento']
    )->name('salidas-almacen.verProductosPorMovimiento');

    Route::post(
        '/productos/validar-para-salida',
        [SalidaAlmacenController::class, 'validarProductoEscaneado']
    )->name('productos.verValidarParaSalida');

    Route::delete(
        '/salidas-almacen/{salida}/detalle/{codigo}',
        [SalidaAlmacenController::class, 'eliminarProductoEscaneado']
    )->name('salidas-almacen.editarEliminarProductoEscaneado');

    Route::put(
        '/salidas-almacen/completar-desde-movimiento/{movimiento}',
        [SalidaAlmacenController::class, 'completarDesdeMovimiento']
    )->name('salidas-almacen.editarCompletarDesdeMovimiento');


    // === PEDIDOS ALMACEN ===
    Route::resource('pedidos-almacen-venta', PedidoAlmacenVentaController::class);
    Route::post('pedidos-almacen-venta/{id}/confirmar', [PedidoAlmacenVentaController::class, 'confirmar'])->name('pedidos-almacen-venta.confirmar');
    Route::put(
        'pedidos-almacen-venta/{pedido}/lineas/{linea}/cancelar',
        [PedidoAlmacenVentaController::class, 'cancelarLinea']
    )
        ->name('pedidos-almacen-venta.lineas.cancelar');
    Route::post('/pedidos-almacen-venta/lineas/detalles', [PedidoAlmacenVentaController::class, 'detallesLineas'])
        ->name('pedidos-almacen-venta.lineas.detalles');
    // === CLIENTES ALMACEN ===
    Route::resource('clientes-almacen', ClienteAlmacenController::class);
    Route::get('/clientes-almacen/buscar', [ClienteAlmacenController::class, 'buscar'])
        ->name('clientes-almacen.verBuscar');

    // === OBRAS ===
    Route::resource('obras', ObraController::class);
    Route::post('/obras/actualizar-tipo', [ObraController::class, 'updateTipo'])->name('obras.updateTipo');
    Route::get('/asignaciones-turno/eventos-obra', [ProduccionController::class, 'eventosObra'])->name('asignaciones-turnos.verEventosObra');

    // === NOMINAS Y FISCALIDAD ===
    Route::resource('empresas', EmpresaController::class)->names('empresas');
    Route::post('/categorias/update-field', [EmpresaController::class, 'updateCategoriaField'])->name('categorias.updateField');
    Route::post('/categorias/store', [EmpresaController::class, 'storeCategoria'])->name('categorias.store');
    Route::post('/categorias/destroy', [EmpresaController::class, 'destroyCategoria'])->name('categorias.destroy');
    Route::resource('nominas', NominaController::class)->except(['destroy']);
    Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales'])->name('generar.nominas');
    Route::delete('/nominas/borrar-todas', [NominaController::class, 'borrarTodas'])->name('nominas.borrarTodas');
    Route::resource('irpf-tramos', IrpfTramoController::class)->names('irpf-tramos');
    Route::resource('porcentajes-ss', SeguridadSocialController::class)->names('porcentajes-ss');
    Route::get('/simulacion-irpf', [NominaController::class, 'formularioSimulacion'])->name('nomina.simulacion');
    Route::post('/simulacion-irpf', [NominaController::class, 'simular'])->name('nomina.simular');
    Route::get('/simulacion-inversa', [NominaController::class, 'formularioInverso'])->name('nomina.inversa');
    Route::post('/simulacion-inversa', [NominaController::class, 'simularDesdeNeto'])->name('nomina.inversa.calcular');
    Route::post('/nominas/dividir', [NominaController::class, 'dividirNominas'])->name('nominas.dividir');
    Route::post('/mis-nominas/enviar', [NominaController::class, 'descargarNominasMes'])->name('nominas.crearDescargarMes');

    // === ALERTAS Y ESTADISTICAS ===
    Route::prefix('estadisticas')->group(function () {
        Route::get('stock', [EstadisticasController::class, 'stock'])->name('estadisticas.verStock');
        Route::get('obras', [EstadisticasController::class, 'obras'])->name('estadisticas.verObras');
        Route::get('tecnicos-despiece', [EstadisticasController::class, 'tecnicosDespiece'])->name('estadisticas.verTecnicosDespiece');
        Route::get('consumo-maquinas', [EstadisticasController::class, 'consumoMaquinas'])->name('estadisticas.verConsumo-maquinas');
    });

    Route::resource('alertas', AlertaController::class)->only(['index', 'store', 'update', 'destroy'])->names('alertas');
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.verMarcarLeidas');
    Route::get('/alertas/{id}/hilo', [AlertaController::class, 'obtenerHilo'])->name('alertas.verHilo');
    Route::get('/alertas/sin-leer', [AlertaController::class, 'sinLeer'])->name('alertas.verSinLeer');
    Route::get('/estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas.index');

    // === POLÍTICAS Y AYUDA ===
    Route::controller(PoliticaController::class)->group(function () {
        Route::get('/politica-privacidad', 'mostrarPrivacidad')->name('politica.privacidad');
        Route::get('/politica-cookies', 'mostrarCookies')->name('politica.cookies');
        Route::post('/aceptar-politicas', 'aceptar')->name('politicas.aceptar');
    });
    Route::get('/ayuda', [AyudaController::class, 'index'])->name('ayuda.index');

    // === DEPARTAMENTOS Y SECCIONES ===
    Route::resource('departamentos', DepartamentoController::class)->names('departamentos');
    Route::post('/departamentos/{departamento}/asignar-usuarios', [DepartamentoController::class, 'asignarUsuarios'])->name('departamentos.asignar.usuarios');
    Route::post('/departamentos/{departamento}/asignar-secciones', [DepartamentoController::class, 'asignarSecciones'])->name('departamentos.asignarSecciones');
    Route::post('/departamentos/{departamento}/permisos', [DepartamentoController::class, 'actualizarPermiso']);
    Route::resource('secciones', SeccionController::class)->names('secciones');

    // === PAPELERA ===
    Route::get('/papelera', [PapeleraController::class, 'index'])->name('papelera.index');
    Route::put('/papelera/restore/{model}/{id}', [PapeleraController::class, 'restore'])->name('papelera.restore');

    // === CLAVE === 

    Route::get('/verificar-seccion/{seccion}', function ($seccion) {
        if (!session()->get("clave_validada_$seccion")) {
            abort(403);
        }
        return response()->json(['ok' => true]);
    });
    Route::post('/proteger/{seccion}', [ClaveSeccionController::class, 'verificar'])->name('proteger.seccion');



    // === ORDENES PLANILLAS ===
    Route::get('/produccion/OrdenesPlanillas', [App\Http\Controllers\ProduccionController::class, 'verOrdenesPlanillas'])
        ->name('produccion.verOrdenesPlanillas');

    // 🔄 Endpoint para actualizaciones en tiempo real
    Route::get('/produccion/maquinas/actualizaciones', [App\Http\Controllers\ProduccionController::class, 'obtenerActualizaciones'])
        ->name('produccion.actualizaciones');

    // obtener elementos con js
    Route::get('/api/elementos', [ElementoController::class, 'filtrar']);

    Route::post('/produccion/planillas/guardar', [\App\Http\Controllers\ProduccionController::class, 'guardar'])
        ->name('produccion.planillas.guardar');

    Route::post('/planillas/import', [PlanillaController::class, 'import'])
        ->name('planillas.crearImport');

    /**
     * RUTAS PARA EL MÓDULO DE LOCALIZACIONES
     * 
     * Este archivo contiene todas las rutas relacionadas con el mapa de localizaciones
     * Agregar estas rutas al archivo routes/web.php
     */
    // Vista principal del índice de localizaciones
    // Vista principal del índice de localizaciones
    Route::get('/localizaciones', [LocalizacionController::class, 'index'])
        ->name('localizaciones.index');

    // Vista del mapa de localizaciones de paquetes (NUEVA)
    Route::get('/mapa-paquetes', [LocalizacionController::class, 'mapaLocalizaciones'])
        ->name('mapa.paquetes');

    // ========== LOGS DE PRODUCCIÓN ==========
    // Vista de espionaje de producción en tiempo real
    Route::get('/production-logs', [App\Http\Controllers\ProductionLogController::class, 'index'])
        ->name('production.logs.index');

    // API: Obtener últimos registros en tiempo real
    Route::get('/api/production-logs/latest', [App\Http\Controllers\ProductionLogController::class, 'getLatestLogs'])
        ->name('production.logs.latest');

    // Descargar archivo CSV completo
    Route::get('/production-logs/download/{fileName}', [App\Http\Controllers\ProductionLogController::class, 'downloadLog'])
        ->name('production.logs.download');

    // ========== TRAZABILIDAD DE FABRICACIÓN (COLADAS) ==========
    // Vista principal de trazabilidad
    Route::get('/fabricacion/trazabilidad', [App\Http\Controllers\FabricacionLogController::class, 'index'])
        ->name('fabricacion.trazabilidad.index');

    // API: Obtener detalles de fabricación de una etiqueta
    Route::get('/api/fabricacion/detalles-etiqueta', [App\Http\Controllers\FabricacionLogController::class, 'getDetallesEtiqueta'])
        ->name('api.fabricacion.detalles');

    // API: Buscar elementos por colada
    Route::get('/api/fabricacion/buscar-colada', [App\Http\Controllers\FabricacionLogController::class, 'buscarPorColada'])
        ->name('api.fabricacion.buscar.colada');

    // API: Obtener estadísticas del mes
    Route::get('/api/fabricacion/estadisticas', [App\Http\Controllers\FabricacionLogController::class, 'getEstadisticas'])
        ->name('api.fabricacion.estadisticas');

    // API: Obtener meses disponibles
    Route::get('/api/fabricacion/meses-disponibles', [App\Http\Controllers\FabricacionLogController::class, 'getMesesDisponibles'])
        ->name('api.fabricacion.meses');

    // Vista para editar el mapa
    Route::get('/localizaciones/editar-mapa', [LocalizacionController::class, 'editarMapa'])
        ->name('localizaciones.editarMapa');

    // Vista para crear nueva localización
    Route::get('/localizaciones/create', [LocalizacionController::class, 'create'])
        ->name('localizaciones.create');

    // API: Verificar si existe localización en coordenadas
    Route::post('/localizaciones/verificar', [LocalizacionController::class, 'verificar'])
        ->name('localizaciones.verificar');

    // API: Guardar nueva localización
    Route::post('/localizaciones', [LocalizacionController::class, 'store'])
        ->name('localizaciones.store');

    // API: Guardar localización de paquete (NUEVA)
    Route::post('/localizaciones/paquete', [LocalizacionController::class, 'guardarLocalizacionPaquete'])
        ->name('localizaciones.guardarPaquete');

    // API: Obtener detalles de una localización específica
    Route::get('/localizaciones/{id}', [LocalizacionController::class, 'show'])
        ->name('localizaciones.show');

    // API: Actualizar localización existente
    Route::put('/localizaciones/{id}', [LocalizacionController::class, 'update'])
        ->name('localizaciones.update');

    // API: Actualizar posición de paquete en el mapa
    Route::put('/localizaciones/paquete/{paqueteId}', [LocalizacionController::class, 'updatePaquetePosicion'])
        ->name('localizaciones.updatePaquetePosicion');

    // API: Obtener lista de naves (obras HPR)
    Route::get('/api/naves', [LocalizacionController::class, 'getNavesApi'])
        ->name('api.naves.index');

    // API: Obtener datos del mapa de una nave
    Route::get('/api/naves/{naveId}/mapa-data', [LocalizacionController::class, 'getMapaDataApi'])
        ->name('api.naves.mapaData');

    // API: Renderizar componente de mapa (HTML)
    Route::get('/api/naves/{naveId}/mapa-component', [LocalizacionController::class, 'renderMapaComponente'])
        ->name('api.naves.mapaComponent');

    // API: Eliminar localización
    Route::delete('/localizaciones/{id}', [LocalizacionController::class, 'destroy'])
        ->name('localizaciones.destroy');


    Route::post('/paquetes/desde-maquina', [PaqueteController::class, 'store'])
        ->name('paquetes.store');

    // === INCORPORACIONES DE TRABAJADORES ===
    Route::resource('incorporaciones', \App\Http\Controllers\IncorporacionController::class)
        ->parameters(['incorporaciones' => 'incorporacion']);
    Route::post('/incorporaciones/{incorporacion}/subir-documento', [\App\Http\Controllers\IncorporacionController::class, 'subirDocumento'])
        ->name('incorporaciones.subir-documento');
    Route::delete('/incorporaciones/{incorporacion}/documento/{tipo}', [\App\Http\Controllers\IncorporacionController::class, 'eliminarDocumento'])
        ->name('incorporaciones.eliminar-documento');
    Route::post('/incorporaciones/{incorporacion}/cambiar-estado', [\App\Http\Controllers\IncorporacionController::class, 'cambiarEstado'])
        ->name('incorporaciones.cambiar-estado');
    Route::post('/incorporaciones/{incorporacion}/marcar-enviado', [\App\Http\Controllers\IncorporacionController::class, 'marcarEnlaceEnviado'])
        ->name('incorporaciones.marcar-enviado');
    Route::get('/incorporaciones/{incorporacion}/archivo/{archivo}', [\App\Http\Controllers\IncorporacionController::class, 'verArchivo'])
        ->name('incorporaciones.ver-archivo');
    Route::get('/incorporaciones/{incorporacion}/descargar/{archivo}', [\App\Http\Controllers\IncorporacionController::class, 'descargarArchivo'])
        ->name('incorporaciones.descargar-archivo');
    Route::get('/incorporaciones/{incorporacion}/descargar-zip', [\App\Http\Controllers\IncorporacionController::class, 'descargarZip'])
        ->name('incorporaciones.descargar-zip');
    Route::post('/incorporaciones/{incorporacion}/aprobar-rrhh', [\App\Http\Controllers\IncorporacionController::class, 'aprobarRrhh'])
        ->name('incorporaciones.aprobar-rrhh');
    Route::post('/incorporaciones/{incorporacion}/revocar-rrhh', [\App\Http\Controllers\IncorporacionController::class, 'revocarRrhh'])
        ->name('incorporaciones.revocar-rrhh');
    Route::post('/incorporaciones/{incorporacion}/aprobar-ceo', [\App\Http\Controllers\IncorporacionController::class, 'aprobarCeo'])
        ->name('incorporaciones.aprobar-ceo');
    Route::post('/incorporaciones/{incorporacion}/revocar-ceo', [\App\Http\Controllers\IncorporacionController::class, 'revocarCeo'])
        ->name('incorporaciones.revocar-ceo');
    Route::delete('/incorporaciones/{incorporacion}/eliminar-archivo', [\App\Http\Controllers\IncorporacionController::class, 'eliminarArchivo'])
        ->name('incorporaciones.eliminar-archivo');
    Route::post('/incorporaciones/{incorporacion}/resubir-archivo', [\App\Http\Controllers\IncorporacionController::class, 'resubirArchivo'])
        ->name('incorporaciones.resubir-archivo');
    Route::post('/incorporaciones/{incorporacion}/actualizar-campo', [\App\Http\Controllers\IncorporacionController::class, 'actualizarCampo'])
        ->name('incorporaciones.actualizar-campo');
});

// === RUTAS PÚBLICAS - FORMULARIO INCORPORACIÓN (sin autenticación) ===
Route::get('/incorporacion/{token}', [\App\Http\Controllers\IncorporacionPublicaController::class, 'show'])
    ->name('incorporacion.publica');
Route::post('/incorporacion/{token}', [\App\Http\Controllers\IncorporacionPublicaController::class, 'store'])
    ->name('incorporacion.publica.store');


require __DIR__ . '/auth.php';

// DEBUG STOCK
require __DIR__.'/debug-stock.php';
