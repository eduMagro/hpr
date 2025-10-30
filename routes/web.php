<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PerfilController;
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
use App\Services\PlanillaService;
use Illuminate\Support\Facades\Log;

Route::get('/', [PageController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::resource('entradas', EntradaController::class)->names('entradas');
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');
    Route::post('/entradas/importar-albaran', [EntradaController::class, 'subirPdf'])
        ->name('entradas.crearImportarAlbaranPdf');
    Route::get('/entradas/pdf/{id}', [EntradaController::class, 'descargarPdf'])->name('entradas.crearDescargarPdf');

    Route::resource('pedidos_globales', PedidoGlobalController::class);
    Route::resource('pedidos', PedidoController::class);
    Route::get('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'recepcion'])->name('pedidos.recepcion');
    Route::post('/pedidos/sugerir-pedido-global', [PedidoController::class, 'sugerirPedidoGlobal'])->name('pedidos.sugerir-pedido-global');

    // Procesar la recepción del producto base
    Route::post('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'procesarRecepcion'])->name('pedidos.recepcion.guardar');

    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.crearEnviarCorreo');
    // Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.verPreview');
    // Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar']) ->name('pedidos.activar');
    // Activar una línea concreta del pedido
    Route::put('/pedidos/{pedido}/lineas/{linea}/activar', [PedidoController::class, 'activar'])->name('pedidos.lineas.editarActivar');

    // Desactivar una línea concreta del pedido
    Route::delete('/pedidos/{pedido}/lineas/{linea}/desactivar', [PedidoController::class, 'desactivar'])->name('pedidos.lineas.editarDesactivar');

    // Completar manualmente un pedido que no es para una nave de paco reyes

    Route::post('pedidos/{pedido}/lineas/{linea}/completar', [PedidoController::class, 'completarLineaManual'])->name('pedidos.editarCompletarLineaManual');

    // === LINEAS DEL PEDIDO ===
    Route::put('/pedidos/{pedido}/lineas/{linea}/cancelar', [PedidoController::class, 'cancelarLinea'])->name('pedidos.lineas.editarCancelar');

    // === PRODUCTOS Y UBICACIONES ===
    Route::resource('fabricantes', FabricanteController::class);
    Route::resource('productos-base', ProductoBaseController::class);
    Route::resource('productos', ProductoController::class);
    // Route::post('/productos/crear-desde-recepcion', [PedidoController::class, 'crearDesdeRecepcion'])->name('productos.crear.desde.recepcion');
    // Route::post('/solicitar-stock', [ProductoController::class, 'solicitarStock'])->name('solicitar.stock');
    Route::get('productos/{id}/consumir', [ProductoController::class, 'consumir'])->name('productos.editarConsumir');
    Route::post('productos/generar-exportar', [ProductoController::class, 'GenerarYExportar'])->name('productos.generar.crearExportar');
    Route::post('/productos/{codigo}/reasignar', [ProductoController::class, 'editarUbicacionInventario'])
        ->name('productos.editarUbicacionInventario');

    Route::get('/ubicaciones/inventario', [UbicacionController::class, 'inventario'])->name('ubicaciones.verInventario');
    Route::resource('ubicaciones', UbicacionController::class);
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    // === LOCALIZACIONES ===
    Route::resource('localizaciones', LocalizacionController::class);
    Route::get('/localizaciones/editar-mapa', [LocalizacionController::class, 'editarMapa'])->name('localizaciones.editarMapa');
    Route::post('/localizaciones/verificar', [LocalizacionController::class, 'verificar'])->name('localizaciones.verificar');

    Route::post('/localizaciones-paquetes/{codigo}', [PaqueteController::class, 'update'])->name('localizaciones_paquetes.update');
    Route::post('/localizaciones/store-paquete', [LocalizacionController::class, 'storePaquete'])->name('localizaciones.storePaquete');
    // === USUARIOS Y VACACIONES ===

    Route::resource('users', ProfileController::class);
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
    Route::resource('vacaciones', VacacionesController::class);
    Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store'])->name('vacaciones.solicitar');
    Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar'])->name('vacaciones.editarAprobar');
    Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar'])->name('vacaciones.editarDenegar');
    // Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');
    // Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento'])->name('vacaciones.eliminarEvento');


    // === TURNOS ===
    Route::resource('turnos', TurnoController::class);
    Route::resource('asignaciones-turnos', AsignacionTurnoController::class);
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy'])->name('asignaciones-turnos.destroy');
    Route::post('/asignaciones-turno/{id}/actualizar-puesto', [ProduccionController::class, 'actualizarPuesto']);
    Route::post('/fichar', [AsignacionTurnoController::class, 'fichar'])->name('users.fichar');
    Route::post('/generar-turnos', function (Request $request) {
        Artisan::call('turnos:generar-anuales');
        return back()->with('success', 'Turnos generados correctamente.');
    })->name('generar-turnos');
    Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');
    Route::post('/festivos/editar', [VacacionesController::class, 'moverFestivo'])->name('festivos.mover');
    Route::put('/festivos/{festivo}/fecha', [FestivoController::class, 'actualizarFecha'])->name('festivos.actualizarFecha');
    Route::delete('/festivos/{festivo}', [FestivoController::class, 'destroy'])->name('festivos.eliminar');
    Route::post('/festivos', [FestivoController::class, 'store'])->name('festivos.store');
    Route::post('/asignaciones-turno/asignar-obra', [AsignacionTurnoController::class, 'asignarObra'])->name('asignaciones-turnos.asignarObra');
    Route::post('/asignaciones-turno/asignar-multiple', [AsignacionTurnoController::class, 'asignarObraMultiple'])->name('asignaciones-turnos.asignarObraMultiple');
    Route::put('/asignaciones-turno/{id}/quitar-obra', [AsignacionTurnoController::class, 'quitarObra'])->name('asignaciones-turnos.quitarObra');
    Route::put('/asignaciones-turnos/{id}/update-obra', [AsignacionTurnoController::class, 'updateObra'])->name('asignaciones-turnos.update-obra');
    Route::post('/asignaciones-turno/repetir-semana', [AsignacionTurnoController::class, 'repetirSemana'])->name('asignaciones-turnos.repetirSemana');
    Route::post('/asignaciones-turno/{id}/actualizar-horas', [AsignacionTurnoController::class, 'actualizarHoras'])->name('asignaciones-turnos.actualizar-horas');
    Route::get('/asignaciones-turno/exportar', [AsignacionTurnoController::class, 'export'])->name('asignaciones-turnos.verExportar');

    // === MAQUINAS Y PRODUCCIÓN ===
    Route::resource('maquinas', MaquinaController::class);
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])->name('maquinas.sesion.guardar');
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');
    Route::post('/turnos/cambiar-maquina', [Maquinacontroller::class, 'cambiarMaquina'])->name('turno.cambiarMaquina');
    Route::put('/maquinas/{maquina}/imagen', [MaquinaController::class, 'actualizarImagen'])->name('maquinas.imagen');
    Route::get('/planillas/eventos', [ProduccionController::class, 'eventosPlanillas'])
        ->name('planillas.eventos');

    Route::get('/produccion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('produccion.verTrabajadores');
    Route::get('/produccion/trabajadores-obra', [ProduccionController::class, 'trabajadoresObra'])->name('produccion.verTrabajadoresObra');
    Route::get('/produccion/maquinas', [ProduccionController::class, 'maquinas'])->name('produccion.verMaquinas');

    // === MOVIMIENTOS ===
    Route::resource('movimientos', MovimientoController::class);
    Route::post('/movimientos/crear', [MovimientoController::class, 'crearMovimiento'])->name('movimientos.crear');

    // === PAQUETES ETIQUETAS Y ELEMENTOS ===
    Route::resource('paquetes', PaqueteController::class);
    Route::resource('etiquetas', EtiquetaController::class);
    Route::resource('elementos', ElementoController::class);
    Route::post('/elementos/dividir', [ElementoController::class, 'dividirElemento'])->name('elementos.dividir');
    Route::post('/elementos/{elementoId}/solicitar-cambio-maquina', [ElementoController::class, 'solicitarCambioMaquina']);
    Route::put('/elementos/{id}/cambio-maquina', [ElementoController::class, 'cambioMaquina'])->name('elementos.cambioMaquina');
    Route::post('/subetiquetas/crear', [ElementoController::class, 'crearSubEtiqueta'])->name('subetiquetas.crear');
    Route::post('/subetiquetas/mover-todo', [ElementoController::class, 'moverTodoANuevaSubEtiqueta'])->name('subetiquetas.moverTodo');
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])->name('elementosEtiquetas');
    Route::put('/actualizar-etiqueta/{id}/maquina/{maquina_id}', [EtiquetaController::class, 'actualizarEtiqueta'])->where('id', '.*');
    Route::post('/etiquetas/fabricacion-optimizada', [EtiquetaController::class, 'fabricacionSyntaxLine28'])->name('etiquetas.fabricacion-optimizada');
    Route::post('/elementos/{elemento}/actualizar-campo', [ElementoController::class, 'actualizarMaquina'])->name('elementos.editarMaquina');

    Route::post('/etiquetas/{etiqueta}/patron-corte-simple', [EtiquetaController::class, 'calcularPatronCorteSimple'])->name('etiquetas.calcularPatronCorteSimple');
    Route::post('/etiquetas/{etiqueta}/patron-corte-optimizado', [EtiquetaController::class, 'calcularPatronCorteOptimizado'])->name('etiquetas.calcularPatronCorteOptimizado');
    // ruta para renderizar una etiqueta en HTML
    Route::post('/etiquetas/render', [App\Http\Controllers\EtiquetaController::class, 'render'])
        ->name('etiquetas.render');

    // RUTAS PROVISIONALES
    Route::post('/etiquetas/fabricar-lote', [EtiquetaController::class, 'fabricarLote'])->name('maquinas.fabricarLote');
    Route::post('/etiquetas/completar-lote', [EtiquetaController::class, 'completarLote'])->name('maquinas.completarLote');


    Route::get('/planillas/informacion', [PlanillaController::class, 'informacionMasiva'])->name('planillas.editarInformacionMasiva');

    Route::put('/planillas/fechas', [PlanillaController::class, 'actualizarFechasMasiva'])->name('planillas.editarActualizarFechasMasiva');
    Route::post('/paquetes/tamaño', [PaqueteController::class, 'tamaño'])
        ->name('paquetes.tamaño');

    // === PLANILLAS Y PLANIFICACIÓN ===
    Route::resource('planillas', PlanillaController::class);
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.crearImport');
    Route::post('/planillas/reordenar', [ProduccionController::class, 'reordenarPlanillas'])->name('planillas.editarReordenar');
    Route::resource('planificacion', PlanificacionController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::put('/planificacion/comentario/{id}', [PlanificacionController::class, 'editarGuardarComentario']);
    Route::post('/planillas/{planilla}/reimportar', [PlanillaController::class, 'reimportar'])->name('planillas.crearReimportar');
    Route::post('/planillas/completar', [PlanillaController::class, 'completar'])->name('planillas.completar');
    Route::get('/planificacion/index', [PlanificacionController::class, 'index'])->name('planificacion.index');
    Route::get('/planificacion/totales', [PlanificacionController::class, 'getTotalesAjax']);
    Route::post('/planillas/completar-todas', function (PlanillaService $svc) {
        $resultado = $svc->completarTodasPlanillas(); // llama al service
        return back()->with(
            $resultado['success'] ? 'success' : 'error',
            "Procesadas OK: {$resultado['procesadas_ok']} | Omitidas por fecha: {$resultado['omitidas_fecha']} | Fallidas: {$resultado['fallidas']}"
        );
    })->name('planillas.completarTodas');
    // === EMPRESAS TRANSPORTE ===
    Route::resource('empresas-transporte', EmpresaTransporteController::class);
    Route::resource('camiones', CamionController::class);

    Route::post('/update-field', [EmpresaTransporteController::class, 'updateField'])->name('empresas-transporte.editarField');

    // === SALIDAS FERRALLA ===
    Route::resource('salidas-ferralla', SalidaFerrallaController::class);
    Route::delete('/salidas/{salida}/quitar-paquete/{paquete}', [SalidaFerrallaController::class, 'quitarPaquete'])->name('salidas.editarQuitarPaquete');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaFerrallaController::class, 'editarActualizarEstado']);
    Route::post('/actualizar-fecha-salida', [SalidaFerrallaController::class, 'actualizarFechaSalida']);
    Route::post('/escaneo', [SalidaFerrallaController::class, 'marcarSubido'])->name('escaneo.marcarSubido');
    Route::get('/salidas/export/{mes}', [SalidaFerrallaController::class, 'export'])->name('salidas.export');
    Route::post('/planificacion/crear-salida-desde-calendario', [SalidaFerrallaController::class, 'crearSalidaDesdeCalendario'])->name('planificacion.crearSalidaDesdeCalendario');
    Route::put('/salidas/completar-desde-movimiento/{movimientoId}', [SalidaFerrallaController::class, 'completarDesdeMovimiento']);
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
    Route::get('/mis-nominas/descargar', [NominaController::class, 'descargarNominasMes'])->name('nominas.crearDescargarMes');

    // === ALERTAS Y ESTADISTICAS ===
    Route::prefix('estadisticas')->group(function () {
        Route::get('stock', [EstadisticasController::class, 'stock'])->name('estadisticas.verStock');
        Route::get('obras', [EstadisticasController::class, 'obras'])->name('estadisticas.verObras');
        Route::get('tecnicos-despiece', [EstadisticasController::class, 'tecnicosDespiece'])->name('estadisticas.verTecnicosDespiece');
        Route::get('consumo-maquinas', [EstadisticasController::class, 'consumoMaquinas'])->name('estadisticas.verConsumo-maquinas');
    });

    Route::resource('alertas', AlertaController::class)->only(['index', 'store', 'update', 'destroy'])->names('alertas');
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.verMarcarLeidas');

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

    // obtener elementos con js
    Route::get('/api/elementos', [ElementoController::class, 'filtrar']);

    Route::post('/produccion/planillas/guardar', [\App\Http\Controllers\ProduccionController::class, 'guardar'])
        ->name('produccion.planillas.guardar');
});


require __DIR__ . '/auth.php';
