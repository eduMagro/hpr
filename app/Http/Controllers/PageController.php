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

        $departamentosUsuario = $esOficina ? $user->departamentos->pluck('id')->toArray() : [];

        $permitidosOperario = [
            'maquinas.index',
            'productos.index',
            'users.index',
            'alertas.index',
        ];

        $secciones = Seccion::with('departamentos')
            ->where('mostrar_en_dashboard', true)
            ->get();


        $items = $secciones->filter(function ($seccion) use ($esOperario, $esOficina, $departamentosUsuario, $permitidosOperario) {
            if ($esOperario) {
                return in_array($seccion->ruta, $permitidosOperario);
            }

            if ($esOficina) {
                $idsDepSeccion = $seccion->departamentos->pluck('id')->toArray();
                return count(array_intersect($departamentosUsuario, $idsDepSeccion)) > 0;
            }

            return false;
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
