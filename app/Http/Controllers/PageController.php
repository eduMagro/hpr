<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;
use App\Models\Empresa;

class PageController extends Controller
{
    /**
     * Handle the root route.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {


        $user = auth()->user();
        $email = strtolower(trim($user->email));
        $emailsAccesoTotal = [
            'eduardo.magro@pacoreyes.com',
            'sebastian.duran@pacoreyes.com',
            'juanjose.dorado@pacoreyes.com',
            'josemanuel.amuedo@pacoreyes.com',
            'manuel.reyes@pacoreyes.com',
            'alvarofaces@gruporeyestejero.com',
            'pabloperez@gruporeyestejero.com',
            'edumagrolemus@hotmail.com',
        ];
        $esOperario = $user->rol === 'operario';
        $esTransportista = $user->rol === 'transportista';
        $esOficina  = $user->rol === 'oficina';

        // 🏢 Empresas
        $empresaReyesTejeroId = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%reyes tejero%'])->value('id');
        $empresaHPRId         = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])->value('id');
        $empresaServiciosId   = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])->value('id');

        $empresaId = $user->empresa_id;

        // 📌 Departamentos del usuario
        $departamentosUsuario = $esOficina ? $user->departamentos->pluck('id')->toArray() : [];

        // 📌 Ítems permitidos para operarios
        $permitidosOperario = [
            'maquinas.index',
            'productos.index',
            'pedidos.index',
            'users.index',
            'alertas.index',
            'entradas.index',
            'ayuda.index',
        ];
        $permitidosTransportista = [
            'users.index',
            'planificacion.index',
            'alertas.index',
            'ayuda.index',
        ];

        // 📌 Cargar todas las secciones visibles
        $secciones = Seccion::with('departamentos')
            ->where('mostrar_en_dashboard', true)
            ->get();
        // ✅ Acceso total → ver todas las secciones visibles
        if (in_array($email, $emailsAccesoTotal)) {
            $items = $secciones->map(fn($s) => [
                'route' => $s->ruta,
                'label' => $s->nombre,
                'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $s->departamentos->pluck('id')->toArray(),
            ]);

            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina', 'departamentosUsuario', 'permitidosOperario', 'permitidosTransportista'));
        }

        // 🟣 Caso 1: G.E Reyes Tejero + Oficina → solo ayuda y mensajes
        if ($empresaId === $empresaReyesTejeroId && $esOficina) {
            $items = $secciones->filter(
                fn($s) =>
                in_array($s->ruta, ['ayuda.index', 'alertas.index'])
            )->map(fn($s) => [
                'route' => $s->ruta,
                'label' => $s->nombre,
                'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $s->departamentos->pluck('id')->toArray(),
            ]);

            return view('dashboard', compact('items', 'esOperario', 'esOficina', 'departamentosUsuario', 'permitidosOperario'));
        }
        // 🟣 Caso 2: G.E Reyes Tejero + Oficina → solo ayuda y mensajes
        if ($empresaId === $empresaReyesTejeroId && $esOperario) {
            $items = $secciones->filter(
                fn($s) =>
                in_array($s->ruta, ['ayuda.index', 'alertas.index'])
            )->map(fn($s) => [
                'route' => $s->ruta,
                'label' => $s->nombre,
                'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $s->departamentos->pluck('id')->toArray(),
            ]);

            return view('dashboard', compact('items', 'esOperario', 'esOficina', 'departamentosUsuario', 'permitidosOperario'));
        }

        // 🟢 Caso 3: HPR / HPR Servicios + Oficina → según permisos reales
        if (in_array($empresaId, [$empresaHPRId, $empresaServiciosId]) && $esOficina) {
            $items = $secciones->filter(
                fn($s) =>
                usuarioTieneAcceso($s->ruta)
            )->map(fn($s) => [
                'route' => $s->ruta,
                'label' => $s->nombre,
                'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $s->departamentos->pluck('id')->toArray(),
            ]);

            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina', 'departamentosUsuario', 'permitidosOperario', 'permitidosTransportista'));
        }

        // 🔧 Caso 4: HPR / HPR Servicios + Operario → ítems permitidos operario
        if (in_array($empresaId, [$empresaHPRId, $empresaServiciosId]) && $esOperario) {
            $items = $secciones->filter(
                fn($s) =>
                in_array($s->ruta, $permitidosOperario)
            )->map(fn($s) => [
                'route' => $s->ruta,
                'label' => $s->nombre,
                'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $s->departamentos->pluck('id')->toArray(),
            ]);

            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina', 'departamentosUsuario', 'permitidosOperario', 'permitidosTransportista'));
        }

        // 🚛 Caso 5: HPR / HPR Servicios + Transportista → ítems permitidos transportista
        if (in_array($empresaId, [$empresaHPRId, $empresaServiciosId]) && $esTransportista) {
            $items = $secciones->filter(
                fn($s) =>
                in_array($s->ruta, $permitidosTransportista)
            )->map(fn($s) => [
                'route' => $s->ruta,
                'label' => $s->nombre,
                'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $s->departamentos->pluck('id')->toArray(),
            ]);

            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina', 'departamentosUsuario', 'permitidosOperario', 'permitidosTransportista'));
        }
        \Log::debug('🧪 DEBUG DASHBOARD', [
            'user' => $user->email,
            'empresa_id' => $empresaId,
            'empresa_reyes_tejero_id' => $empresaReyesTejeroId,
            'empresa_hpr_id' => $empresaHPRId,
            'empresa_servicios_id' => $empresaServiciosId,
            'rol' => $user->rol,
        ]);

        // ❌ Cualquier otro caso (no autorizado)
        abort(403, 'No tienes acceso. Contacta con administración');
    }
}
