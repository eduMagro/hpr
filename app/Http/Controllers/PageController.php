<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PermissionService;

class PageController extends Controller
{
    public function __construct(
        protected PermissionService $permissions
    ) {}

    public function index()
    {
        $user = auth()->user();

        $esOperario = $user->rol === 'operario';
        $esTransportista = $user->rol === 'transportista';
        $esOficina = $user->rol === 'oficina';

        // Obtener secciones accesibles usando el servicio centralizado
        $secciones = $this->permissions->getAccessibleSections($user);

        $items = $secciones->map(fn($s) => [
            'route' => $s->ruta,
            'label' => $s->nombre,
            'icon' => asset($s->icono ?? 'imagenes/iconos/noimagen.png'),
            'departamentos' => $s->departamentos->pluck('id')->toArray(),
        ]);

        if ($items->isEmpty()) {
            session()->flash('error', 'No tienes acceso a ninguna sección. Contacta con administración.');
        }

        return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
    }

    /**
     * Sección de Producción
     */
    public function produccion()
    {
        return view('secciones.produccion');
    }

    /**
     * Sección de Inventario
     */
    public function inventario()
    {
        return view('secciones.inventario');
    }

    /**
     * Sección de Comercial
     */
    public function comercial()
    {
        return view('secciones.comercial');
    }

    /**
     * Sección de Compras
     */
    public function compras()
    {
        return view('secciones.compras');
    }

    /**
     * Sección de Recursos Humanos
     */
    public function recursosHumanos()
    {
        return view('secciones.recursos-humanos');
    }

    /**
     * Sección de Sistema
     */
    public function sistema()
    {
        return view('secciones.sistema');
    }

    /**
     * Sección de Planificación
     */
    public function planificacionSeccion()
    {
        return view('secciones.planificacion');
    }

    /**
     * Sección de Logística
     */
    public function logistica()
    {
        return view('secciones.logistica');
    }
}
