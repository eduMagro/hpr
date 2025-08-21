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
use App\Http\Controllers\FestivoController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\ClaveSeccionController;
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
    Route::resource('clientes', ClienteController::class);
    Route::resource('fabricantes', FabricanteController::class);
    Route::resource('distribuidores', DistribuidorController::class);

    // === ENTRADAS Y PEDIDOS ===
    Route::resource('entradas', EntradaController::class);
    Route::patch('/entradas/{id}/cerrar', [EntradaController::class, 'cerrar'])->name('entradas.cerrar');
    Route::post('/entradas/importar-albaran', [EntradaController::class, 'subirPdf'])
        ->name('entradas.crearImportarAlbaranPdf');
    Route::get('/entradas/pdf/{id}', [EntradaController::class, 'descargarPdf'])->name('entradas.crearDescargarPdf');

    Route::resource('pedidos_globales', PedidoGlobalController::class);
    Route::resource('pedidos', PedidoController::class);
    Route::get('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'recepcion'])->name('pedidos.recepcion');

    // Procesar la recepción del producto base
    Route::post('pedidos/{pedido}/recepcion/{producto_base}', [PedidoController::class, 'procesarRecepcion'])->name('pedidos.recepcion.guardar');

    Route::post('/pedidos/{pedido}/enviar-correo', [PedidoController::class, 'enviarCorreo'])->name('pedidos.crearEnviarCorreo');
    // Route::get('/pedidos/preview', [PedidoController::class, 'preview'])->name('pedidos.verPreview');
    // Route::put('/pedidos/{pedido}/activar', [PedidoController::class, 'activar']) ->name('pedidos.activar');
    // Activar una línea concreta del pedido
    Route::put('/pedidos/{pedido}/lineas/{linea}/activar', [PedidoController::class, 'activar'])->name('pedidos.lineas.editarActivar');

    // Desactivar una línea concreta del pedido
    Route::delete('/pedidos/{pedido}/lineas/{linea}/desactivar', [PedidoController::class, 'desactivar'])->name('pedidos.lineas.editarDesactivar');


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

    Route::get('/ubicaciones/inventario', [UbicacionController::class, 'inventario'])->name('ubicaciones.verInventario');
    Route::resource('ubicaciones', UbicacionController::class);
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show'])->name('ubicaciones.show');

    // === LOCALIZACIONES ===
    Route::resource('localizaciones', LocalizacionController::class);
    Route::get('/localizaciones/editar-mapa', [LocalizacionController::class, 'editarMapa'])->name('localizaciones.editarMapa');
    Route::get('/localizaciones/verificar', [LocalizacionController::class, 'verificar'])->name('localizaciones.verificar');
    Route::post('/localizaciones-paquetes/{codigo}', [PaqueteController::class, 'update'])->name('localizaciones_paquetes.update');

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
    Route::post('/asignaciones-turno/asignar-obra', [AsignacionTurnoController::class, 'asignarObra'])->name('asignaciones-turno.asignarObra');
    Route::post('/asignaciones-turno/asignar-multiple', [AsignacionTurnoController::class, 'asignarObraMultiple'])->name('asignaciones-turno.asignarObraMultiple');
    Route::put('/asignaciones-turno/{id}/quitar-obra', [AsignacionTurnoController::class, 'quitarObra'])->name('asignaciones-turno.quitarObra');
    Route::put('/asignaciones-turnos/{id}/update-obra', [AsignacionTurnoController::class, 'updateObra']);
    Route::post('/asignaciones-turno/repetir-semana', [AsignacionTurnoController::class, 'repetirSemana'])->name('asignaciones-turno.repetirSemana');
    Route::post('/asignaciones-turno/{id}/actualizar-horas', [AsignacionTurnoController::class, 'actualizarHoras'])->name('asignaciones-turno.actualizar-horas');
    Route::get('/asignaciones-turno/exportar', [AsignacionTurnoController::class, 'export'])->name('asignaciones-turno.exportar');

    // === MAQUINAS Y PRODUCCIÓN ===
    Route::resource('maquinas', MaquinaController::class);
    Route::post('/maquinas/{id}/cambiar-estado', [MaquinaController::class, 'cambiarEstado'])->name('maquinas.cambiarEstado');
    Route::post('/maquinas/sesion/guardar', [MaquinaController::class, 'guardarSesion'])->name('maquinas.sesion.guardar');
    Route::get('/maquinas/{id}/json', [MaquinaController::class, 'showJson'])->name('maquinas.json');
    Route::post('/turnos/cambiar-maquina', [Maquinacontroller::class, 'cambiarMaquina'])->name('turno.cambiarMaquina');
    Route::put('/maquinas/{maquina}/imagen', [MaquinaController::class, 'actualizarImagen'])->name('maquinas.imagen');

    Route::get('/produccion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('produccion.verTrabajadores');
    Route::get('/produccion/trabajadores-obra', [ProduccionController::class, 'trabajadoresObra'])->name('produccion.verTrabajadoresObra');
    Route::get('/produccion/maquinas', [ProduccionController::class, 'maquinas'])->name('produccion.verMaquinas');

    // === MOVIMIENTOS ===
    Route::resource('movimientos', MovimientoController::class);
    Route::post('/movimientos/crear', [MovimientoController::class, 'crearMovimiento'])->name('movimientos.crear');

    // === PAQUETES Y ELEMENTOS ===
    Route::resource('paquetes', PaqueteController::class);
    Route::resource('etiquetas', EtiquetaController::class);
    Route::resource('elementos', ElementoController::class);
    Route::post('/elementos/dividir', [ElementoController::class, 'dividirElemento'])->name('elementos.dividir');
    Route::post('/elementos/{elementoId}/solicitar-cambio-maquina', [ElementoController::class, 'solicitarCambioMaquina']);
    Route::put('/elementos/{id}/cambio-maquina', [ElementoController::class, 'cambioMaquina'])->name('elementos.cambioMaquina');
    Route::post('/subetiquetas/crear', [ElementoController::class, 'crearSubEtiqueta'])->name('subetiquetas.crear');
    Route::get('/planillas/{planilla}/etiquetas', [ElementoController::class, 'showByEtiquetas'])->name('elementosEtiquetas');
    Route::put('/actualizar-etiqueta/{id}/maquina/{maquina_id}', [EtiquetaController::class, 'actualizarEtiqueta'])->where('id', '.*');
    // RUTAS PROVISIONALES
    Route::post('/etiquetas/fabricar-lote', [EtiquetaController::class, 'fabricarLote'])->name('maquinas.fabricarLote');
    Route::post('/etiquetas/completar-lote', [EtiquetaController::class, 'completarLote'])->name('maquinas.completarLote');


    Route::get('/planillas/informacion', [PlanillaController::class, 'informacionMasiva'])->name('planillas.editarInformacionMasiva');

    Route::put('/planillas/fechas', [PlanillaController::class, 'actualizarFechasMasiva'])->name('planillas.editarActualizarFechasMasiva');
    Route::post('/paquetes/tamaño', function (Request $request) {
        $paquete = App\Models\Paquete::where('codigo', $request->codigo)->first();

        if (!$paquete) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        // devuelve directamente el accessor
        return response()->json([
            'codigo'   => $paquete->codigo,
            'ancho'    => $paquete->tamaño['ancho'],
            'longitud' => $paquete->tamaño['longitud'],
        ]);
    })->name('paquetes.tamaño');

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

    // === SALIDAS Y ESCANEO ===
    Route::resource('salidas', SalidaController::class);
    Route::delete('/salidas/{salida}/quitar-paquete/{paquete}', [SalidaController::class, 'quitarPaquete'])->name('salidas.editarQuitarPaquete');
    Route::put('/salidas/{salida}/actualizar-estado', [SalidaController::class, 'editarActualizarEstado']);
    Route::post('/actualizar-fecha-salida', [SalidaController::class, 'actualizarFechaSalida']);
    Route::post('/escaneo', [SalidaController::class, 'marcarSubido'])->name('escaneo.marcarSubido');
    Route::get('/salidas/export/{mes}', [SalidaController::class, 'export'])->name('salidas.export');
    Route::post('/planificacion/crear-salida-desde-calendario', [SalidaController::class, 'crearSalidaDesdeCalendario'])->name('planificacion.crearSalidaDesdeCalendario');
    Route::put('/salidas/completar-desde-movimiento/{movimientoId}', [SalidaController::class, 'completarDesdeMovimiento']);
    Route::put('/salidas/{salida}/codigo-sage', [SalidaController::class, 'actualizarCodigoSage'])->name('salidas.editarCodigoSage');

    // === OBRAS ===
    Route::resource('obras', ObraController::class);
    Route::post('/obras/actualizar-tipo', [ObraController::class, 'updateTipo'])->name('obras.updateTipo');
    Route::get('/asignaciones-turno/eventos-obra', [ProduccionController::class, 'eventosObra'])->name('asignaciones-turnos.verEventosObra');

    // === NOMINAS Y FISCALIDAD ===
    Route::resource('empresas', EmpresaController::class);
    Route::resource('nominas', NominaController::class)->except(['destroy']);
    Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales'])->name('generar.nominas');
    Route::delete('/nominas/borrar-todas', [NominaController::class, 'borrarTodas'])->name('nominas.borrarTodas');
    Route::resource('irpf-tramos', IrpfTramoController::class);
    Route::resource('porcentajes-ss', SeguridadSocialController::class);
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

    Route::resource('alertas', AlertaController::class)->only(['index', 'store', 'update', 'destroy']);
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
    Route::resource('departamentos', DepartamentoController::class);
    Route::post('/departamentos/{departamento}/asignar-usuarios', [DepartamentoController::class, 'asignarUsuarios'])->name('departamentos.asignar.usuarios');
    Route::post('/departamentos/{departamento}/asignar-secciones', [DepartamentoController::class, 'asignarSecciones'])->name('departamentos.asignarSecciones');
    Route::post('/departamentos/{departamento}/permisos', [DepartamentoController::class, 'actualizarPermiso']);
    Route::resource('secciones', SeccionController::class);

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
});


require __DIR__ . '/auth.php';
