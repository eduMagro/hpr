<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Seccion;
use App\Models\Departamento;
use App\Models\PermisoAcceso;
use Illuminate\Support\Facades\Log;
use App\Models\Empresa;

class VerificarAccesoSeccion
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();
        if (!$user) abort(403, 'No autenticado.');

        $email = strtolower(trim($user->email));
        $emailsAccesoTotal = [
            'eduardo.magro@pacoreyes.com',
            'sebastian.duran@pacoreyes.com',
            'juanjose.dorado@pacoreyes.com',
            'josemanuel.amuedo@pacoreyes.com',
            'jose.amuedo@pacoreyes.com', // ← Añade esta variante también por si acaso
            'manuel.reyes@pacoreyes.com',
            'alvarofaces@gruporeyestejero.com',
            'pabloperez@gruporeyestejero.com',
            'edumagrolemus@hotmail.com',
        ];

        // ✅ Atajo: si tiene acceso total por email, permitir todo sin más
        if (in_array($email, $emailsAccesoTotal)) {
            Log::debug('✅ Acceso total concedido por email', ['email' => $email, 'ruta' => $request->route()?->getName()]);
            return $next($request);
        }


        // ============================
        // 🔎 DATOS DEL USUARIO
        // ============================
        $rutaActual = $request->route()?->getName() ?? '';
        $userEmpresaId = $user->empresa_id;
        $esOperario = $user->rol === 'operario';
        $esTransportista = $user->rol === 'transportista';
        $esOficina  = $user->rol === 'oficina';


        // 🏢 Empresas
        $empresaReyesTejeroId = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%reyes tejero%'])->value('id');
        $empresaHPRId         = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])->value('id');
        $empresaServiciosId   = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])->value('id');

        // ============================
        // 🟢 EMPRESAS: HPR, HPR Servicios y G.E RT
        // ============================
        $empresasConAccesoCompleto = [$empresaHPRId, $empresaServiciosId];

        // 🔓 Rutas públicas para estas empresas
        $rutasLibres = [
            'politica.privacidad',
            'politica.cookies',
            'politicas.aceptar',
            'ayuda.index',
            'usuarios.show',
            'usuarios.index',
            'nominas.crearDescargarMes',
            'turno.cambiarMaquina',
            'salida.completarDesdeMovimiento',
            'alertas.index',
            'alertas.store',
            'alertas.update',
            'alertas.destroy',
            'alertas.verMarcarLeidas',
            'alertas.verSinLeer',
        ];

        if (
            (in_array($userEmpresaId, $empresasConAccesoCompleto) && in_array($rutaActual, $rutasLibres)) ||
            ($userEmpresaId === $empresaReyesTejeroId && in_array($rutaActual, $rutasLibres))
        ) {
            return $next($request);
        }


        // ============================
        // 🔧 OPERARIOS (HPR y Servicios)
        // ============================
        if ($esOperario) {
            $rutasPermitidas = [
                'maquinas.',
                'etiquetas.',
                'productos.',
                'users.',
                'alertas.',
                'entradas.',
                'pedidos.',
                'movimientos.',
                'maquinas.fabricarLote',
                'maquinas.completarLote',
                'vacaciones.solicitar',
                'salidas.editarActualizarEstado',
                'usuarios.editarSubirImagen',
                'usuarios.imagen',
                'nominas.crearDescargarMes',
            ];

            $permitido = collect($rutasPermitidas)->contains(function ($ruta) use ($rutaActual) {
                return Str::startsWith($rutaActual, $ruta) || $rutaActual === $ruta;
            });

            if (!$permitido) {
                Log::info('🚫 Ruta denegada para operario', [
                    'user' => $user->email,
                    'ruta' => $rutaActual,
                ]);
                abort(403, 'Operario sin acceso.');
            }



            return $next($request);
        }
        // ============================
        // 🔧 Transportistas (HPR y Servicios)
        // ============================
        if ($esTransportista) {
            $rutasPermitidas = [
                'users.',
                'alertas.',
                'vacaciones.solicitar',
                'planificacion.index',
                'usuarios.editarSubirImagen',
                'usuarios.imagen',
                'nominas.crearDescargarMes',
            ];

            $permitido = collect($rutasPermitidas)->contains(function ($ruta) use ($rutaActual) {
                return Str::startsWith($rutaActual, $ruta) || $rutaActual === $ruta;
            });

            if (!$permitido) {
                Log::info('🚫 Ruta denegada para operario', [
                    'user' => $user->email,
                    'ruta' => $rutaActual,
                ]);
                abort(403, 'Operario sin acceso.');
            }

            Log::debug('✅ Ruta permitida para operario', [
                'user' => $user->email,
                'ruta' => $rutaActual,
            ]);

            return $next($request);
        }

        // ============================
        // 🧩 OFICINA (HPR y Servicios)
        // ============================
        if ($esOficina && in_array($userEmpresaId, $empresasConAccesoCompleto)) {
            $accion = Str::afterLast($rutaActual, '.');
            $accion = strtolower($accion);

            $seccionBase = Str::before($rutaActual, '.');

            $seccion = Seccion::whereRaw('LOWER(ruta) LIKE ?', [strtolower($seccionBase) . '.%'])->first();
            if (!$seccion) {
                Log::warning('❌ Ruta sin sección registrada', ['ruta' => $rutaActual]);
                abort(403, 'Sección no registrada.');
            }

            $permisos = PermisoAcceso::where('user_id', $user->id)
                ->where('seccion_id', $seccion->id)
                ->get();

            if ($permisos->isEmpty()) {
                Log::debug('❌ Sin permisos para sección', [
                    'user' => $user->email,
                    'seccion' => $seccion->ruta,
                    'ruta' => $rutaActual,
                ]);
                abort(403, 'No tienes permisos asignados para esta sección.');
            }

            $autorizado = false;
            foreach ($permisos as $permiso) {
                if (
                    (in_array($accion, ['index', 'show']) || Str::startsWith($accion, 'ver')) && $permiso->puede_ver
                    || (in_array($accion, ['create', 'store']) || Str::startsWith($accion, 'crear')) && $permiso->puede_crear
                    || (in_array($accion, ['edit', 'update', 'destroy']) || Str::startsWith($accion, 'editar')) && $permiso->puede_editar
                ) {
                    $autorizado = true;
                    break;
                }
            }

            if (!$autorizado) {
                Log::warning('❌ Acción no autorizada', [
                    'user' => $user->email,
                    'ruta' => $rutaActual,
                    'accion' => $accion,
                    'seccion' => $seccionBase
                ]);
                abort(403, 'No tienes permisos suficientes para esta acción.');
            }

            Log::debug('✅ Acción autorizada por permisos', [
                'user' => $user->email,
                'ruta' => $rutaActual,
            ]);

            return $next($request);
        }

        // 🚨 Si llegó hasta aquí, denegamos por defecto
        Log::warning('🚫 Ruta denegada por defecto (sin coincidencias)', [
            'user' => $user->email,
            'ruta' => $rutaActual,
        ]);
        abort(403, 'Acceso denegado por configuración.');
    }
}
