<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;
use App\Models\Empresa;
use App\Models\PermisoAcceso;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $email = strtolower(trim($user->email));

        $esOperario = $user->rol === 'operario';
        $esTransportista = $user->rol === 'transportista';
        $esOficina = $user->rol === 'oficina';

        // 📌 Correos con acceso total (ven todas las secciones visibles)
        $emailsAccesoTotal = config('acceso.correos_acceso_total', []);

        // 🏢 Empresas
        $empresaReyesTejeroId = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%reyes tejero%'])->value('id');
        $empresaHPRId         = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])->value('id');
        $empresaServiciosId   = Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])->value('id');
        $empresaId = $user->empresa_id;

        // 📌 Secciones visibles
        $secciones = Seccion::with('departamentos')
            ->where('mostrar_en_dashboard', true)
            ->get();

        // ✅ Caso 1: Acceso total → todas las secciones visibles
        if (in_array($email, $emailsAccesoTotal)) {
            $items = $this->mapSecciones($secciones);
            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
        }

        // 🟣 Caso 2: Reyes Tejero + Oficina → solo ayuda y alertas
        if ($empresaId === $empresaReyesTejeroId && $esOficina) {
            $items = $this->mapSecciones(
                $secciones->whereIn('ruta', ['ayuda.index', 'alertas.index'])
            );
            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
        }

        // 🟣 Caso 3: Reyes Tejero + Operario → solo ayuda y alertas
        if ($empresaId === $empresaReyesTejeroId && $esOperario) {
            $items = $this->mapSecciones(
                $secciones->whereIn('ruta', ['ayuda.index', 'alertas.index'])
            );
            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
        }

        // 🟢 Caso 4: HPR / HPR Servicios + Oficina → permisos de usuario y departamentos
        if (in_array($empresaId, [$empresaHPRId, $empresaServiciosId]) && $esOficina) {
            $items = $this->mapSecciones(
                $secciones->filter(fn($s) => $this->usuarioTieneAcceso($user, $s->id, $s->ruta))
            );
            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
        }

        // 🔧 Caso 5: HPR / HPR Servicios + Operario → visibilidad en dashboard
        if (in_array($empresaId, [$empresaHPRId, $empresaServiciosId]) && $esOperario) {
            $prefijosOperarioDashboard = config('acceso.prefijos_operario_dashboard', []);
            $items = $this->mapSecciones(
                $secciones->filter(
                    fn($s) => collect($prefijosOperarioDashboard)->contains(
                        fn($prefijo) => $s->ruta === $prefijo || str_starts_with($s->ruta, $prefijo)
                    )
                )
            );

            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
        }


        // 🚛 Caso 6: HPR / HPR Servicios + Transportista → prefijos transportista
        if (in_array($empresaId, [$empresaHPRId, $empresaServiciosId]) && $esTransportista) {
            $prefijosTransportista = config('acceso.prefijos_transportista', []);
            $items = $this->mapSecciones(
                $secciones->filter(
                    fn($s) =>
                    in_array($s->ruta, $prefijosTransportista, true)
                )
            );
            return view('dashboard', compact('items', 'esOperario', 'esTransportista', 'esOficina'));
        }

        Log::warning('❌ Usuario sin acceso al dashboard', [
            'user' => $user->email,
            'empresa_id' => $empresaId,
            'rol' => $user->rol,
        ]);

        abort(403, 'No tienes acceso. Contacta con administración');
    }

    /**
     * Mapear secciones a formato de item
     */
    private function mapSecciones($secciones)
    {
        return $secciones->map(fn($s) => [
            'route' => $s->ruta,
            'label' => $s->nombre,
            'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
            'departamentos' => $s->departamentos->pluck('id')->toArray(),
        ]);
    }

    /**
     * Determina si un usuario tiene acceso a una sección
     * considerando permisos directos y de departamentos
     */
    private function usuarioTieneAcceso($user, $seccionId, $ruta)
    {
        // Permisos directos
        $tienePermisoDirecto = PermisoAcceso::where('user_id', $user->id)
            ->where('seccion_id', $seccionId)
            ->exists();

        if ($tienePermisoDirecto) {
            return true;
        }

        // Permisos heredados de departamentos
        $departamentosUsuario = $user->departamentos->pluck('id')->toArray();
        $tienePermisoPorDept = \DB::table('departamento_seccion')
            ->whereIn('departamento_id', $departamentosUsuario)
            ->where('seccion_id', $seccionId)
            ->exists();

        return $tienePermisoPorDept;
    }
}
