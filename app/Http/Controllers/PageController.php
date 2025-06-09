<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;

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
        $esOficina = $user->rol === 'oficina';

        // Por compatibilidad con la vista
        $departamentosUsuario = $esOficina ? $user->departamentos->pluck('id')->toArray() : [];

        // Por compatibilidad con la vista
        $permitidosOperario = [
            'maquinas.index',
            'productos.index',
            'pedidos.index',
            'users.index',
            'alertas.index',
            'entradas.index',
            'ayuda.index',
        ];

        // Obtener secciones visibles
        $secciones = Seccion::with('departamentos')
            ->where('mostrar_en_dashboard', true)
            ->get();

        // Filtrar según permisos reales
        $items = $secciones->filter(function ($seccion) {
            return usuarioTieneAcceso($seccion->ruta);
        })->map(function ($seccion) {
            return [
                'route' => $seccion->ruta,
                'label' => $seccion->nombre,
                'icon' => asset($seccion->icono ?? 'imagenes/iconos/default.png'),
                'departamentos' => $seccion->departamentos->pluck('id')->toArray(),
            ];
        });

        return view('dashboard', compact('items', 'esOperario', 'esOficina', 'departamentosUsuario', 'permitidosOperario'));
    }
}
