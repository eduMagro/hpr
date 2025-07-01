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
use App\Http\Controllers\DistribuidorController;
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
use App\Http\Controllers\ClaveSeccionController;

Route::get('/', [PageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // === PERFIL DE USUARIO ===
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/users/{id}', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/actualizar-especialidad/{operario}', [ProfileController::class, 'actualizarEspecialidad']);
    Route::get('/exportar-usuarios', [ProfileController::class, 'exportarUsuarios'])->name('usuarios.exportar');

    // === CLIENTES Y PROVEEDORES ===
    Route::resource('clientes', ClienteController::class)->middleware('acceso.seccion:clientes.index');
    Route::resource('fabricantes', FabricanteController::class)->middleware('acceso.seccion:fabricantes.index');
    Route::resource('distribuidores', DistribuidorController::class)->middleware('acceso.seccion:distribuidores.index');

    // === ENTRADAS Y PEDIDOS ===
    Route::resource('entradas', EntradaController::class)->middleware('acceso.seccion');
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');

    Route::resource('pedidos_globales', PedidoGlobalController::class)->middleware('acceso.seccion:pedidos-globales.index');
    Route::resource('pedidos', PedidoController::class)->middleware('acceso.seccion:pedidos.index');
    Route::post('/pedidos/confirmar', [PedidoController::class, 'confirmar'])->name('pedidos.confirmar');
    // Mostrar el formulario de recepción para un producto base concreto
    Route::get('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'recepcion'])
        ->name('pedidos.recepcion');

    // Procesar la recepción del producto base
    Route::post('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'procesarRecepcion'])
        ->name('pedidos.recepcion.guardar');

    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.enviarCorreo');
    Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.preview');
    // Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar'])->name('pedidos.activar');
    Route::put('/pedidos/{pedido}/activar-producto/{producto}', [PedidoController::class, 'activar'])
        ->name('pedidos.activar');
    Route::delete('/pedidos/{pedido}/desactivar-producto/{producto_base}', [PedidoController::class, 'desactivar'])->name('pedidos.desactivar');

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
    Route::get('/ubicaciones/inventario', [UbicacionController::class, 'inventario'])->name('ubicaciones.inventario');
    Route::resource('ubicaciones', UbicacionController::class)->middleware('acceso.seccion:ubicaciones.index');
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');


    // === USUARIOS Y VACACIONES ===
    Route::resource('users', ProfileController::class)->middleware('acceso.seccion:users.index');
    Route::put('/actualizar-usuario/{id}', [ProfileController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
    Route::get('/users/{user}/resumen-asistencia', [ProfileController::class, 'resumenAsistencia'])->name('users.resumen-asistencia');
    Route::get('/users/{user}/eventos-turnos', [ProfileController::class, 'eventosTurnos'])->name('users.eventos-turnos');
    Route::post('/usuarios/{user}/cerrar-sesiones', [ProfileController::class, 'cerrarSesionesDeUsuario'])
        ->name('usuarios.cerrarSesiones');
    Route::post(
        '/usuarios/{user}/despedir',
        [ProfileController::class, 'despedirUsuario']
    )
        ->name('usuarios.despedir');

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
    Route::post('/asignaciones-turno/asignar-obra', [AsignacionTurnoController::class, 'asignarObra'])->name('asignaciones-turno.asignarObra');
    Route::put('/asignaciones-turno/{id}/quitar-obra', [AsignacionTurnoController::class, 'quitarObra'])->name('asignaciones-turno.quitarObra');

    // === MAQUINAS Y PRODUCCIÓN ===
    Route::resource('maquinas', MaquinaController::class)->middleware('acceso.seccion:maquinas.index');
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])->name('maquinas.sesion.guardar');
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');
    Route::post('/turnos/cambiar-maquina', [Maquinacontroller::class, 'cambiarMaquina'])->name('turno.cambiarMaquina');
    Route::put('/maquinas/{maquina}/imagen', [MaquinaController::class, 'actualizarImagen'])->name('maquinas.imagen');

    Route::get('/produccion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('produccion.trabajadores');
    Route::get('/produccion/trabajadores-obra', [ProduccionController::class, 'trabajadoresObra'])->name('produccion.trabajadoresObra');
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
    Route::put('/actualizar-etiqueta/{id}/maquina/{maquina_id}', [EtiquetaController::class, 'actualizarEtiqueta'])
        ->where('id', '.*');
    // === PLANILLAS Y PLANIFICACIÓN ===
    Route::resource('planillas', PlanillaController::class)->middleware('acceso.seccion:planillas.index');
    Route::post('planillas/import', [PlanillaController::class, 'import'])->name('planillas.import');
    Route::post('/planillas/reordenar', [ProduccionController::class, 'reordenarPlanillas'])->name('planillas.reordenar');
    Route::resource('planificacion', PlanificacionController::class)->middleware('acceso.seccion:planificacion.index');
    Route::put('/planificacion/comentario/{id}', [PlanificacionController::class, 'guardarComentario']);
    Route::post('/planillas/{planilla}/reimportar', [PlanillaController::class, 'reimportar'])
        ->name('planillas.reimportar');

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
    Route::prefix('estadisticas')->group(function () {
        Route::get('stock', [EstadisticasController::class, 'stock'])->name('estadisticas.stock');
        Route::get('obras', [EstadisticasController::class, 'obras'])->name('estadisticas.obras');
        Route::get('tecnicos-despiece', [EstadisticasController::class, 'tecnicosDespiece'])->name('estadisticas.tecnicosDespiece');
        Route::get('consumo-maquinas', [EstadisticasController::class, 'consumoMaquinas'])->name('estadisticas.consumo-maquinas');
    });

    Route::resource('alertas', AlertaController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.marcarLeidas');

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

    // === CLAVE === 

    Route::get('/verificar-seccion/{seccion}', function ($seccion) {
        if (!session()->get("clave_validada_$seccion")) {
            abort(403);
        }
        return response()->json(['ok' => true]);
    });
    Route::post('/proteger/{seccion}', [ClaveSeccionController::class, 'verificar'])
        ->name('proteger.seccion');
});


require __DIR__ . '/auth.php';
