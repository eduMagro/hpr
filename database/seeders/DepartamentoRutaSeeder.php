<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentoRutaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el ID del departamento "Operario"
        $operarioId = DB::table('departamentos')->where('nombre', 'Operario')->value('id');

        if (!$operarioId) {
            $this->command->warn('No se encontró el departamento "Operario". Saltando seeder.');
            return;
        }

        $rutas = [
            ['ruta' => 'albaranes.*', 'descripcion' => 'Acceso a albaranes'],
            ['ruta' => 'produccion.trabajadores.*', 'descripcion' => 'Producción - trabajadores'],
            ['ruta' => 'users.*', 'descripcion' => 'Perfil de usuario'],
            ['ruta' => 'alertas.*', 'descripcion' => 'Sistema de alertas/mensajes'],
            ['ruta' => 'productos.*', 'descripcion' => 'Consulta de productos/materiales'],
            ['ruta' => 'pedidos.*', 'descripcion' => 'Consulta de pedidos'],
            ['ruta' => 'ayuda.*', 'descripcion' => 'Sección de ayuda'],
            ['ruta' => 'maquinas.*', 'descripcion' => 'Consulta de máquinas'],
            ['ruta' => 'etiquetas.*', 'descripcion' => 'Gestión de etiquetas'],
            ['ruta' => 'elementos.*', 'descripcion' => 'Gestión de elementos'],
            ['ruta' => 'subetiquetas.*', 'descripcion' => 'Gestión de sub-etiquetas'],
            ['ruta' => 'paquetes.*', 'descripcion' => 'Gestión de paquetes'],
            ['ruta' => 'localizaciones.*', 'descripcion' => 'Ubicación en mapa'],
            ['ruta' => 'api.*', 'descripcion' => 'Rutas API'],
            ['ruta' => 'entradas.*', 'descripcion' => 'Entradas de material'],
            ['ruta' => 'movimientos.*', 'descripcion' => 'Movimientos de material'],
            ['ruta' => 'ubicaciones.*', 'descripcion' => 'Ubicaciones de almacén'],
            ['ruta' => 'inventario-backups.*', 'descripcion' => 'Backups de inventario'],
            ['ruta' => 'incorporaciones.descargarMiContrato', 'descripcion' => 'Descargar mi contrato'],
            ['ruta' => 'usuarios.getVacationData', 'descripcion' => 'Ver datos de vacaciones propios'],
            ['ruta' => 'usuarios.fichajes-rango', 'descripcion' => 'Ver fichajes propios en calendario'],
            ['ruta' => 'vacaciones.verMisSolicitudesPendientes', 'descripcion' => 'Ver solicitudes pendientes en calendario'],
            ['ruta' => 'vacaciones.verSolicitudesPendientesUsuario', 'descripcion' => 'Ver mis solicitudes pendientes'],
            ['ruta' => 'vacaciones.eliminarSolicitud', 'descripcion' => 'Eliminar solicitud de vacaciones'],
            ['ruta' => 'vacaciones.eliminarDiasSolicitud', 'descripcion' => 'Eliminar días de solicitud'],
            ['ruta' => 'materiales.*', 'descripcion' => 'Acceso a materiales'],
            ['ruta' => 'revision-fichaje.*', 'descripcion' => 'Solicitar revision de fichajes'],
        ];

        $now = now();

        foreach ($rutas as $ruta) {
            DB::table('departamento_ruta')->updateOrInsert(
                [
                    'departamento_id' => $operarioId,
                    'ruta' => $ruta['ruta'],
                ],
                [
                    'descripcion' => $ruta['descripcion'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Seeder DepartamentoRutaSeeder ejecutado correctamente. ' . count($rutas) . ' rutas procesadas.');
    }
}
