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
        $esOperario = $user->rol === 'operario';
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

        // 📌 Cargar todas las secciones visibles
        $secciones = Seccion::with('departamentos')
            ->where('mostrar_en_dashboard', true)
            ->get();

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
        // 🟣 Caso 1: G.E Reyes Tejero + Oficina → solo ayuda y mensajes
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

        // 🟢 Caso 2: HPR / HPR Servicios + Oficina → según permisos reales
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

            return view('dashboard', compact('items', 'esOperario', 'esOficina', 'departamentosUsuario', 'permitidosOperario'));
        }

        // 🔧 Caso 3: HPR / HPR Servicios + Operario → ítems permitidos
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

            return view('dashboard', compact('items', 'esOperario', 'esOficina', 'departamentosUsuario', 'permitidosOperario'));
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
        abort(403, 'No tienes acceso al dashboard.');
    }
}
