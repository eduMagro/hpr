<?php

use App\Database\IdempotentSqlMigration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

return new class extends IdempotentSqlMigration
{
    public function up(): void
    {
        $columnDefinitions = [
            'albaranes_venta' => [
                'cliente_id' => 'bigint(20) UNSIGNED NOT NULL',
                'created_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'salida_id' => 'bigint(20) UNSIGNED NOT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'albaranes_venta_lineas' => [
                'albaran_id' => 'bigint(20) UNSIGNED NOT NULL',
                'pedido_linea_id' => 'bigint(20) UNSIGNED NOT NULL',
                'producto_base_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'albaranes_venta_productos' => [
                'albaran_linea_id' => 'bigint(20) UNSIGNED NOT NULL',
                'producto_id' => 'bigint(20) UNSIGNED NOT NULL',
                'salida_almacen_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'alertas' => [
                'user_id_1' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'user_id_2' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'parent_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'destinatario_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'alertas_users' => [
                'alerta_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'asignaciones_turnos' => [
                'user_id' => 'bigint(20) UNSIGNED NOT NULL',
                'turno_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'maquina_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'obra_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'camiones' => [
                'empresa_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'chat_consultas_sql' => [
                'mensaje_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'chat_conversaciones' => [
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'chat_mensajes' => [
                'conversacion_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'coladas' => [
                'fabricante_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_base_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'convenio' => [
                'categoria_id' => 'bigint(20) DEFAULT NULL'
            ],
            'departamento_seccion' => [
                'departamento_id' => 'bigint(20) UNSIGNED NOT NULL',
                'seccion_id' => 'int(10) UNSIGNED NOT NULL'
            ],
            'departamento_user' => [
                'departamento_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'elementos' => [
                'maquina_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'etiqueta_id' => 'bigint(20) DEFAULT NULL',
                'maquina_id_2' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'maquina_id_3' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'paquete_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'planilla_id' => 'bigint(20) NOT NULL',
                'producto_id_2' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_id_3' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'users_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'users_id_2' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'entrada_producto' => [
                'entrada_id' => 'bigint(20) UNSIGNED NOT NULL',
                'producto_id' => 'bigint(20) UNSIGNED NOT NULL',
                'ubicacion_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'users_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'entradas' => [
                'pedido_producto_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'nave_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'usuario_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'epi_compra_items' => [
                'compra_id' => 'bigint(20) UNSIGNED NOT NULL',
                'epi_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'epi_compras' => [
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'epis_usuario' => [
                'epi_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'etiquetas' => [
                'ensamblador1_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ensamblador2_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'planilla_id' => 'bigint(20) NOT NULL',
                'paquete_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'soldador1_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'soldador2_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ubicacion_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'operario1_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'operario2_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_id_2' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'eventos_ficticios_obra' => [
                'obra_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'trabajador_ficticio_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'incorporacion_documentos' => [
                'incorporacion_id' => 'bigint(20) UNSIGNED NOT NULL',
                'subido_por' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'incorporacion_formaciones' => [
                'incorporacion_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'incorporacion_logs' => [
                'incorporacion_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'incorporaciones' => [
                'created_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'localizaciones' => [
                'maquina_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'nave_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'maquinas' => [
                'obra_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'modelo_145' => [
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'movimientos' => [
                'pedido_producto_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'paquete_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ubicacion_origen' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ubicacion_destino' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'maquina_origen' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'solicitado_por' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ejecutado_por' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'maquina_destino' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'nave_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_base_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'salida_almacen_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'salida_id' => 'bigint(20) DEFAULT NULL'
            ],
            'nominas' => [
                'categoria_id' => 'bigint(20) DEFAULT NULL',
                'empleado_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'obras' => [
                'cliente_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'orden_planillas' => [
                'planilla_id' => 'bigint(20) NOT NULL',
                'maquina_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'paquetes' => [
                'nave_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ubicacion_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'planilla_id' => 'bigint(20) DEFAULT NULL'
            ],
            'pedido_producto_coladas' => [
                'colada_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'pedido_producto_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'pedido_productos' => [
                'obra_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'pedido_global_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'pedido_id' => 'bigint(20) UNSIGNED NOT NULL',
                'producto_base_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'pedidos' => [
                'pedido_global_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'created_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'distribuidor_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'fabricante_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'pedidos_almacen_venta' => [
                'cliente_id' => 'bigint(20) UNSIGNED NOT NULL',
                'created_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'pedidos_almacen_venta_lineas' => [
                'pedido_almacen_venta_id' => 'bigint(20) UNSIGNED NOT NULL',
                'producto_base_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'pedidos_globales' => [
                'distribuidor_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'fabricante_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'permisos_acceso' => [
                'user_id' => 'bigint(20) UNSIGNED NOT NULL',
                'departamento_id' => 'bigint(20) UNSIGNED NOT NULL',
                'seccion_id' => 'int(10) UNSIGNED NOT NULL'
            ],
            'planillas' => [
                'cliente_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'obra_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'users_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'revisada_por_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'productos' => [
                'entrada_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'obra_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'producto_base_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'colada_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'distribuidor_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'fabricante_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'ubicacion_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'maquina_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'consumido_by' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'respuestas_no_fichaje' => [
                'asignacion_turno_id' => 'bigint(20) UNSIGNED NOT NULL',
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'salida_cliente' => [
                'cliente_id' => 'bigint(20) UNSIGNED NOT NULL',
                'obra_id' => 'bigint(20) UNSIGNED NOT NULL',
                'salida_id' => 'bigint(20) NOT NULL'
            ],
            'salidas' => [
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'empresa_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'camion_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'salidas_almacen' => [
                'camionero_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'created_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'salidas_paquetes' => [
                'salida_id' => 'bigint(20) NOT NULL',
                'paquete_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'snapshots_produccion' => [
                'user_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'solicitudes_vacaciones' => [
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'subpaquetes' => [
                'elemento_id' => 'bigint(20) NOT NULL',
                'planilla_id' => 'bigint(20) NOT NULL',
                'paquete_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ],
            'user_fcm_tokens' => [
                'user_id' => 'bigint(20) UNSIGNED NOT NULL'
            ],
            'users' => [
                'categoria_id' => 'bigint(20) DEFAULT NULL',
                'updated_by' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'empresa_id' => 'bigint(20) UNSIGNED DEFAULT NULL',
                'maquina_id' => 'bigint(20) UNSIGNED DEFAULT NULL'
            ]
        ];

        $statements = [
            <<<"SQL"
ALTER TABLE `albaranes_venta`
  ADD CONSTRAINT `albaranes_venta_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_salida_id_foreign` FOREIGN KEY (`salida_id`) REFERENCES `salidas_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `albaranes_venta_lineas`
  ADD CONSTRAINT `albaranes_venta_lineas_albaran_id_foreign` FOREIGN KEY (`albaran_id`) REFERENCES `albaranes_venta` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_lineas_pedido_linea_id_foreign` FOREIGN KEY (`pedido_linea_id`) REFERENCES `pedidos_almacen_venta_lineas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_lineas_producto_base_id_foreign` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `albaranes_venta_productos`
  ADD CONSTRAINT `fk_avp_linea` FOREIGN KEY (`albaran_linea_id`) REFERENCES `albaranes_venta_lineas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avp_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avp_salida` FOREIGN KEY (`salida_almacen_id`) REFERENCES `salidas_almacen` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`user_id_1`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertas_ibfk_2` FOREIGN KEY (`user_id_2`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertas_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `alertas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_destinatario_id` FOREIGN KEY (`destinatario_id`) REFERENCES `users` (`id`)
SQL
            ,
            <<<"SQL"
ALTER TABLE `alertas_users`
  ADD CONSTRAINT `alertas_users_ibfk_1` FOREIGN KEY (`alerta_id`) REFERENCES `alertas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertas_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `asignaciones_turnos`
  ADD CONSTRAINT `asignaciones_turnos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignaciones_turnos_ibfk_2` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asignaciones_turnos_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asignaciones_turnos_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `camiones`
  ADD CONSTRAINT `camiones_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_transporte` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `chat_consultas_sql`
  ADD CONSTRAINT `chat_consultas_sql_mensaje_id_foreign` FOREIGN KEY (`mensaje_id`) REFERENCES `chat_mensajes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_consultas_sql_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `chat_conversaciones`
  ADD CONSTRAINT `chat_conversaciones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `chat_mensajes`
  ADD CONSTRAINT `chat_mensajes_conversacion_id_foreign` FOREIGN KEY (`conversacion_id`) REFERENCES `chat_conversaciones` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `coladas`
  ADD CONSTRAINT `coladas_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coladas_producto_base_id_foreign` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`)
SQL
            ,
            <<<"SQL"
ALTER TABLE `convenio`
  ADD CONSTRAINT `fk_convenio_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `departamento_seccion`
  ADD CONSTRAINT `fk_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `departamento_user`
  ADD CONSTRAINT `fk_du_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_du_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `elementos`
  ADD CONSTRAINT `elementos_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elementos_etiqueta` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elementos_maquina2` FOREIGN KEY (`maquina_id_2`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elementos_maquina3` FOREIGN KEY (`maquina_id_3`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elementos_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elementos_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elementos_producto2` FOREIGN KEY (`producto_id_2`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elementos_producto_3` FOREIGN KEY (`producto_id_3`) REFERENCES `productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elementos_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_2` FOREIGN KEY (`users_id_2`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `entradas`
  ADD CONSTRAINT `fk_entrada_linea` FOREIGN KEY (`pedido_producto_id`) REFERENCES `pedido_productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_entradas_nave` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entradas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `entrada_producto`
  ADD CONSTRAINT `entrada_producto_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrada_producto_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrada_producto_ibfk_3` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrada_producto_ibfk_4` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `epis_usuario`
  ADD CONSTRAINT `epis_usuario_epi_id_foreign` FOREIGN KEY (`epi_id`) REFERENCES `epis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `epis_usuario_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `epi_compras`
  ADD CONSTRAINT `epi_compras_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `epi_compra_items`
  ADD CONSTRAINT `epi_compra_items_compra_id_foreign` FOREIGN KEY (`compra_id`) REFERENCES `epi_compras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `epi_compra_items_epi_id_foreign` FOREIGN KEY (`epi_id`) REFERENCES `epis` (`id`)
SQL
            ,
            <<<"SQL"
ALTER TABLE `etiquetas`
  ADD CONSTRAINT `etiquetas_ensamblador1_foreign` FOREIGN KEY (`ensamblador1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_ensamblador2_foreign` FOREIGN KEY (`ensamblador2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_ibfk_1` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `etiquetas_paquete_id_foreign` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_soldador1_foreign` FOREIGN KEY (`soldador1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_soldador2_foreign` FOREIGN KEY (`soldador2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_ubicacion_id_foreign` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_etiquetas_operario1` FOREIGN KEY (`operario1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etiquetas_operario2` FOREIGN KEY (`operario2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etiquetas_producto_id` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_etiquetas_producto_id_2` FOREIGN KEY (`producto_id_2`) REFERENCES `productos` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `eventos_ficticios_obra`
  ADD CONSTRAINT `eventos_ficticios_obra_obra_id_foreign` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `eventos_ficticios_obra_trabajador_ficticio_id_foreign` FOREIGN KEY (`trabajador_ficticio_id`) REFERENCES `trabajadores_ficticios` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `incorporaciones`
  ADD CONSTRAINT `fk_incorporaciones_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incorporaciones_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incorporaciones_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `incorporacion_documentos`
  ADD CONSTRAINT `fk_documentos_incorporacion` FOREIGN KEY (`incorporacion_id`) REFERENCES `incorporaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_documentos_subido_por` FOREIGN KEY (`subido_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `incorporacion_formaciones`
  ADD CONSTRAINT `fk_formaciones_incorporacion` FOREIGN KEY (`incorporacion_id`) REFERENCES `incorporaciones` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `incorporacion_logs`
  ADD CONSTRAINT `fk_logs_incorporacion` FOREIGN KEY (`incorporacion_id`) REFERENCES `incorporaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `localizaciones`
  ADD CONSTRAINT `fk_localizaciones_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_localizaciones_nave` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `maquinas`
  ADD CONSTRAINT `maquinas_obra_id_foreign` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `modelo_145`
  ADD CONSTRAINT `modelo_145_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `movimientos`
  ADD CONSTRAINT `fk_movimientos_pedido_producto` FOREIGN KEY (`pedido_producto_id`) REFERENCES `pedido_productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_paquete_id` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`ubicacion_origen`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_3` FOREIGN KEY (`ubicacion_destino`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_4` FOREIGN KEY (`maquina_origen`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_7` FOREIGN KEY (`solicitado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_8` FOREIGN KEY (`ejecutado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_maquina_destino_foreign` FOREIGN KEY (`maquina_destino`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_nave_id_foreign` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `movimientos_producto_base_id_foreign` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_salida_almacen_id_foreign` FOREIGN KEY (`salida_almacen_id`) REFERENCES `salidas_almacen` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `movimientos_salida_id_foreign` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `nominas`
  ADD CONSTRAINT `fk_nominas_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nominas_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `obras`
  ADD CONSTRAINT `fk_obras_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `orden_planillas`
  ADD CONSTRAINT `FK_ORDEN_PLANILLA_PLANILLA` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orden_planilla_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `paquetes`
  ADD CONSTRAINT `fk_paquetes_nave` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `paquetes_ibfk_1` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `paquetes_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_compras_pedido_global` FOREIGN KEY (`pedido_global_id`) REFERENCES `pedidos_globales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedidos_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pedidos_distribuidor_id_foreign` FOREIGN KEY (`distribuidor_id`) REFERENCES `distribuidores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `pedidos_almacen_venta`
  ADD CONSTRAINT `pedidos_almacen_venta_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_almacen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_almacen_venta_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_almacen_venta_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `pedidos_almacen_venta_lineas`
  ADD CONSTRAINT `pedidos_almacen_venta_lineas_ibfk_1` FOREIGN KEY (`pedido_almacen_venta_id`) REFERENCES `pedidos_almacen_venta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_almacen_venta_lineas_ibfk_2` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`)
SQL
            ,
            <<<"SQL"
ALTER TABLE `pedidos_globales`
  ADD CONSTRAINT `fk_pedidos_globales_distribuidor` FOREIGN KEY (`distribuidor_id`) REFERENCES `distribuidores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_globales_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `pedido_productos`
  ADD CONSTRAINT `fk_pedido_producto_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedido_productos_pedido_global` FOREIGN KEY (`pedido_global_id`) REFERENCES `pedidos_globales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_productos_pedido_id` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pedido_productos_ibfk_2` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `pedido_producto_coladas`
  ADD CONSTRAINT `pedido_producto_coladas_colada_id_foreign` FOREIGN KEY (`colada_id`) REFERENCES `coladas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedido_producto_coladas_pedido_producto_id_foreign` FOREIGN KEY (`pedido_producto_id`) REFERENCES `pedido_productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_producto_coladas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `permisos_acceso`
  ADD CONSTRAINT `permisos_acceso_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_acceso_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_acceso_ibfk_3` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `planillas`
  ADD CONSTRAINT `fk_cliente_id` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_planillas_obra_id` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_planillas_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `planillas_revisada_por_id_fk` FOREIGN KEY (`revisada_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_entrada_id` FOREIGN KEY (`entrada_id`) REFERENCES `entradas` (`id`),
  ADD CONSTRAINT `fk_productos_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_productos_productos_base` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_updated_by_productos` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_colada_id_foreign` FOREIGN KEY (`colada_id`) REFERENCES `coladas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_distribuidor_id_foreign` FOREIGN KEY (`distribuidor_id`) REFERENCES `distribuidores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_ibfk_3` FOREIGN KEY (`consumido_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `respuestas_no_fichaje`
  ADD CONSTRAINT `fk_respuestas_asignacion` FOREIGN KEY (`asignacion_turno_id`) REFERENCES `asignaciones_turnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_respuestas_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `salidas`
  ADD CONSTRAINT `fk_salidas_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salidas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_transporte` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salidas_ibfk_2` FOREIGN KEY (`camion_id`) REFERENCES `camiones` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `salidas_almacen`
  ADD CONSTRAINT `salidas_almacen_camionero_id_foreign` FOREIGN KEY (`camionero_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `salidas_almacen_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `salidas_almacen_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `salidas_paquetes`
  ADD CONSTRAINT `fk_salidas_paquetes_salida` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salidas_paquetes_ibfk_2` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `salida_cliente`
  ADD CONSTRAINT `fk_salida_cliente_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_salida_cliente_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_salida_cliente_salida` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `snapshots_produccion`
  ADD CONSTRAINT `snapshots_produccion_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `solicitudes_vacaciones`
  ADD CONSTRAINT `solicitudes_vacaciones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `subpaquetes`
  ADD CONSTRAINT `subpaquetes_ibfk_1` FOREIGN KEY (`elemento_id`) REFERENCES `elementos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subpaquetes_ibfk_2` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subpaquetes_paquete_id_foreign` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL
SQL
            ,
            <<<"SQL"
ALTER TABLE `users`
  ADD CONSTRAINT `fk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_updated_by_users` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_especialidad` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
SQL
            ,
            <<<"SQL"
ALTER TABLE `user_fcm_tokens`
  ADD CONSTRAINT `user_fcm_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
SQL
            ,
        ];

        foreach ($statements as $statement) {
            $this->applyForeignKeyStatement($statement, $columnDefinitions);
        }
    }

    public function down(): void
    {
    }

    /** @param array<string, array<string, string>> $columnDefinitions */
    private function applyForeignKeyStatement(string $statement, array $columnDefinitions): void
    {
        $statement = trim($statement);
        if ($statement === '') {
            return;
        }

        if (preg_match('/^ALTER TABLE `([^`]+)`/i', $statement, $m) !== 1) {
            $this->runSql($statement);
            return;
        }

        $table = $m[1];

        if (preg_match_all('/FOREIGN KEY \\(`([^`]+)`\\)/i', $statement, $matches) > 0) {
            foreach ($matches[1] as $column) {
                if ($this->columnExists($table, $column)) {
                    continue;
                }

                $definition = $columnDefinitions[$table][$column] ?? null;
                if ($definition === null) {
                    continue;
                }

                $definition = $this->makeNullableDefinitionSafe($definition);
                $this->runSql('ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$definition);
            }
        }

        try {
            DB::unprepared($statement.';');
        } catch (Throwable $e) {
            if ($this->isForeignKeyIgnorableError($e)) {
                return;
            }

            throw $e;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function makeNullableDefinitionSafe(string $definition): string
    {
        $definition = preg_replace('/\bNOT NULL\b/i', 'NULL', $definition) ?? $definition;
        if (preg_match('/\bDEFAULT\b/i', $definition) !== 1) {
            $definition .= ' DEFAULT NULL';
        }

        return $definition;
    }

    private function isForeignKeyIgnorableError(Throwable $e): bool
    {
        $errorCode = null;

        if ($e instanceof QueryException && is_array($e->errorInfo) && isset($e->errorInfo[1])) {
            $errorCode = (int) $e->errorInfo[1];
        } elseif ($e instanceof PDOException && is_array($e->errorInfo ?? null) && isset($e->errorInfo[1])) {
            $errorCode = (int) $e->errorInfo[1];
        }

        if (in_array($errorCode, [1005, 1072, 1215, 1452, 1826], true)) {
            return true;
        }

        $message = $e->getMessage();

        return str_contains($message, 'Key column')
            || str_contains($message, 'Cannot add foreign key constraint')
            || str_contains($message, 'Cannot add or update a child row')
            || str_contains($message, 'Duplicate key on write or update')
            || str_contains($message, 'errno: 121');
    }
};
