<?php

namespace App\Services;

use App\Models\User;
use App\Models\Seccion;
use App\Models\Departamento;
use App\Models\PermisoAcceso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio centralizado de permisos.
 *
 * Este servicio es la ÚNICA fuente de verdad para determinar
 * si un usuario puede acceder a una ruta o sección.
 */
class PermissionService
{
    /**
     * Tiempo de caché en segundos
     */
    protected const CACHE_TTL_LONG = 86400;   // 24 horas (datos estáticos)
    protected const CACHE_TTL_SHORT = 300;    // 5 minutos (datos de permisos)

    /**
     * Verifica si un usuario puede acceder a una ruta.
     * Este es el método principal que deben usar middleware, controllers, etc.
     */
    public function canAccessRoute(?User $user, string $routeName): bool
    {
        if (!$user) {
            return false;
        }

        // 1. Acceso total (emails especiales o administrador)
        if ($this->hasFullAccess($user)) {
            return true;
        }

        // 2. Verificar si la ruta existe
        if (!Route::has($routeName)) {
            return false;
        }

        // 3. Rutas universales (accesibles para CUALQUIER usuario autenticado, sin importar empresa)
        if ($this->isRutaUniversal($routeName)) {
            return true;
        }

        // 4. Rutas libres (accesibles para cualquier usuario autenticado de empresas permitidas)
        if ($this->isRutaLibre($routeName) && $this->isEmpresaConAcceso($user)) {
            return true;
        }

        // 5. Rutas especiales (register solo para Programador)
        if ($routeName === 'register') {
            return $this->belongsToDepartment($user, 'programador');
        }

        // 6. Verificar según rol
        return match (strtolower($user->rol ?? '')) {
            'operario' => $this->checkOperarioAccess($user, $routeName),
            'transportista' => $this->checkTransportistaAccess($routeName),
            'oficina' => $this->checkOficinaAccess($user, $routeName),
            default => false,
        };
    }

    /**
     * Verifica si puede realizar una acción específica (ver/crear/editar).
     * Usado por el middleware para verificar permisos granulares.
     */
    public function canPerformAction(?User $user, string $routeName, string $action = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->hasFullAccess($user)) {
            return true;
        }

        // Rutas universales son permitidas para cualquier usuario autenticado
        if ($this->isRutaUniversal($routeName)) {
            return true;
        }

        // Rutas libres son permitidas también en verificación de acción
        if ($this->isRutaLibre($routeName) && $this->isEmpresaConAcceso($user)) {
            return true;
        }

        // Determinar acción desde la ruta si no se especifica
        if (!$action) {
            $action = strtolower(Str::afterLast($routeName, '.'));
        }

        // Solo oficina tiene permisos granulares
        if (strtolower($user->rol ?? '') !== 'oficina') {
            return $this->canAccessRoute($user, $routeName);
        }

        $seccionBase = Str::before($routeName, '.');
        $seccion = $this->getSeccionByBase($seccionBase);

        if (!$seccion) {
            return false;
        }

        // Verificar permisos directos
        $permisos = PermisoAcceso::where('user_id', $user->id)
            ->where('seccion_id', $seccion->id)
            ->first();

        if ($permisos) {
            return $this->checkActionPermission($permisos, $action);
        }

        // Verificar permisos por departamento (acceso completo si tiene)
        if ($this->hasAccessByDepartment($user, $seccion->id)) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene las secciones accesibles para mostrar en el dashboard.
     */
    public function getAccessibleSections(?User $user): Collection
    {
        if (!$user) {
            return collect([]);
        }

        $secciones = Seccion::with('departamentos')
            ->where('mostrar_en_dashboard', true)
            ->orderBy('orden')
            ->get();

        // Acceso total: todas las secciones
        if ($this->hasFullAccess($user)) {
            return $secciones;
        }

        $rol = strtolower($user->rol ?? '');

        return match ($rol) {
            'operario' => $this->filterSeccionesOperario($secciones),
            'transportista' => $this->filterSeccionesTransportista($secciones),
            'oficina' => $this->filterSeccionesOficina($user, $secciones),
            default => collect([]),
        };
    }

    /**
     * Verifica si el usuario tiene acceso total al sistema.
     */
    public function hasFullAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Emails con acceso total
        $email = strtolower(trim($user->email ?? ''));
        $emailsAccesoTotal = config('acceso.correos_acceso_total', []);

        if (in_array($email, $emailsAccesoTotal, true)) {
            return true;
        }

        // Departamento Administrador
        return $this->belongsToDepartment($user, 'administrador');
    }

    /**
     * Verifica si el usuario pertenece a un departamento.
     */
    public function belongsToDepartment(?User $user, string $departmentName): bool
    {
        if (!$user) {
            return false;
        }

        $cacheKey = "user_{$user->id}_dept_" . Str::slug($departmentName);

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($user, $departmentName) {
            return $user->departamentos()
                ->whereRaw('LOWER(nombre) = ?', [strtolower($departmentName)])
                ->exists();
        });
    }

    /**
     * Limpia la caché de permisos de un usuario.
     */
    public function clearUserCache(int $userId): void
    {
        Cache::forget("menu_user_{$userId}");
        // Limpiar otras cachés relacionadas con el usuario
        $patterns = [
            "user_{$userId}_dept_*",
            "permisos_user_{$userId}_*",
        ];
        // Nota: Cache::forget no soporta wildcards,
        // se necesitaría implementar según el driver
    }

    // =========================================================================
    // MÉTODOS PRIVADOS - VERIFICACIÓN POR ROL
    // =========================================================================

    /**
     * Verifica acceso para rol Operario.
     */
    private function checkOperarioAccess(User $user, string $routeName): bool
    {
        $departamentoId = $this->getDepartamentoId('operario');

        if (!$departamentoId) {
            return false;
        }

        // 1. Verificar rutas específicas (departamento_ruta)
        if ($this->checkDepartamentoRutas($departamentoId, $routeName)) {
            return true;
        }

        // 2. Verificar secciones asignadas (departamento_seccion)
        return $this->checkDepartamentoSecciones($departamentoId, $routeName);
    }

    /**
     * Verifica acceso para rol Transportista.
     */
    private function checkTransportistaAccess(string $routeName): bool
    {
        $prefijos = config('acceso.prefijos_transportista', []);

        foreach ($prefijos as $prefijo) {
            if ($routeName === $prefijo || str_starts_with($routeName, $prefijo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica acceso para rol Oficina.
     */
    private function checkOficinaAccess(User $user, string $routeName): bool
    {
        // Verificar que sea de una empresa con acceso
        if (!$this->isEmpresaConAcceso($user)) {
            return false;
        }

        $seccionBase = Str::before($routeName, '.');
        $seccion = $this->getSeccionByBase($seccionBase);

        if (!$seccion) {
            // Permitir rutas de secciones principales (ej: secciones.produccion)
            return str_starts_with($routeName, 'secciones.');
        }

        // Permiso directo del usuario
        $tienePermisoDirecto = PermisoAcceso::where('user_id', $user->id)
            ->where('seccion_id', $seccion->id)
            ->where('puede_ver', true)
            ->exists();

        if ($tienePermisoDirecto) {
            return true;
        }

        // Permiso heredado por departamento
        return $this->hasAccessByDepartment($user, $seccion->id);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS - FILTRADO DE SECCIONES PARA DASHBOARD
    // =========================================================================

    /**
     * Filtra secciones para operarios.
     */
    private function filterSeccionesOperario(Collection $secciones): Collection
    {
        $departamentoId = $this->getDepartamentoId('operario');

        if (!$departamentoId) {
            return collect([]);
        }

        $seccionesIds = $this->getSeccionesDepartamento($departamentoId);

        return $secciones->filter(fn($s) => in_array($s->id, $seccionesIds));
    }

    /**
     * Filtra secciones para transportistas.
     */
    private function filterSeccionesTransportista(Collection $secciones): Collection
    {
        $prefijos = config('acceso.prefijos_transportista', []);

        return $secciones->filter(function ($s) use ($prefijos) {
            return in_array($s->ruta, $prefijos, true);
        });
    }

    /**
     * Filtra secciones para oficina.
     */
    private function filterSeccionesOficina(User $user, Collection $secciones): Collection
    {
        return $secciones->filter(function ($seccion) use ($user) {
            // Permiso directo
            $tienePermisoDirecto = PermisoAcceso::where('user_id', $user->id)
                ->where('seccion_id', $seccion->id)
                ->exists();

            if ($tienePermisoDirecto) {
                return true;
            }

            // Permiso por departamento
            return $this->hasAccessByDepartment($user, $seccion->id);
        });
    }

    // =========================================================================
    // MÉTODOS PRIVADOS - UTILIDADES
    // =========================================================================

    /**
     * Verifica si una ruta es universal (accesible para CUALQUIER usuario autenticado).
     */
    private function isRutaUniversal(string $routeName): bool
    {
        $rutasUniversales = config('acceso.rutas_universales', []);
        return in_array($routeName, $rutasUniversales, true);
    }

    /**
     * Verifica si una ruta es libre (accesible para usuarios autenticados de empresas permitidas).
     */
    private function isRutaLibre(string $routeName): bool
    {
        $rutasLibres = config('acceso.rutas_libres', []);
        return in_array($routeName, $rutasLibres, true);
    }

    /**
     * Verifica si el usuario es de una empresa con acceso al sistema.
     */
    private function isEmpresaConAcceso(User $user): bool
    {
        $empresasIds = $this->getEmpresasConAcceso();
        return in_array($user->empresa_id, $empresasIds, true);
    }

    /**
     * Obtiene los IDs de empresas con acceso al sistema.
     */
    private function getEmpresasConAcceso(): array
    {
        return Cache::remember('empresas_con_acceso', self::CACHE_TTL_LONG, function () {
            return DB::table('empresas')
                ->whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])
                ->orWhereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])
                ->orWhereRaw("LOWER(nombre) LIKE ?", ['%reyes tejero%'])
                ->pluck('id')
                ->toArray();
        });
    }

    /**
     * Obtiene el ID de un departamento por nombre.
     */
    private function getDepartamentoId(string $nombre): ?int
    {
        $cacheKey = "departamento_id_" . Str::slug($nombre);

        return Cache::remember($cacheKey, self::CACHE_TTL_LONG, function () use ($nombre) {
            return Departamento::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])->value('id');
        });
    }

    /**
     * Obtiene las secciones asignadas a un departamento.
     */
    private function getSeccionesDepartamento(int $departamentoId): array
    {
        $cacheKey = "secciones_departamento_{$departamentoId}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($departamentoId) {
            return DB::table('departamento_seccion')
                ->where('departamento_id', $departamentoId)
                ->pluck('seccion_id')
                ->toArray();
        });
    }

    /**
     * Verifica si un departamento tiene acceso a una ruta específica.
     */
    private function checkDepartamentoRutas(int $departamentoId, string $routeName): bool
    {
        $cacheKey = "rutas_departamento_{$departamentoId}";

        $rutas = Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($departamentoId) {
            return DB::table('departamento_ruta')
                ->where('departamento_id', $departamentoId)
                ->pluck('ruta')
                ->toArray();
        });

        foreach ($rutas as $ruta) {
            if (str_ends_with($ruta, '.*')) {
                $prefijo = substr($ruta, 0, -2);
                if ($routeName === $prefijo || str_starts_with($routeName, $prefijo . '.')) {
                    return true;
                }
            } elseif ($routeName === $ruta) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si un departamento tiene acceso a la sección de una ruta.
     */
    private function checkDepartamentoSecciones(int $departamentoId, string $routeName): bool
    {
        $cacheKey = "secciones_rutas_departamento_{$departamentoId}";

        $rutasSecciones = Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($departamentoId) {
            return DB::table('departamento_seccion')
                ->join('secciones', 'departamento_seccion.seccion_id', '=', 'secciones.id')
                ->where('departamento_seccion.departamento_id', $departamentoId)
                ->pluck('secciones.ruta')
                ->toArray();
        });

        $seccionBase = Str::before($routeName, '.');

        foreach ($rutasSecciones as $rutaSeccion) {
            if (Str::before($rutaSeccion, '.') === $seccionBase) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene una sección por su ruta base.
     */
    private function getSeccionByBase(string $baseRoute): ?Seccion
    {
        return Seccion::where('ruta', 'LIKE', "{$baseRoute}.%")
            ->orWhere('ruta', $baseRoute)
            ->first();
    }

    /**
     * Verifica si el usuario tiene acceso a una sección por departamento.
     */
    private function hasAccessByDepartment(User $user, int $seccionId): bool
    {
        $departamentosUsuario = $user->departamentos->pluck('id')->toArray();

        if (empty($departamentosUsuario)) {
            return false;
        }

        return DB::table('departamento_seccion')
            ->whereIn('departamento_id', $departamentosUsuario)
            ->where('seccion_id', $seccionId)
            ->exists();
    }

    /**
     * Verifica el permiso de acción específica.
     */
    private function checkActionPermission($permisos, string $action): bool
    {
        // Acciones de VER
        if (
            in_array($action, ['index', 'show']) ||
            Str::startsWith($action, ['ver', 'show', 'get', 'list'])
        ) {
            return (bool) $permisos->puede_ver;
        }

        // Acciones de CREAR
        if (
            in_array($action, ['create', 'store']) ||
            Str::startsWith($action, ['crear', 'store', 'new', 'add'])
        ) {
            return (bool) $permisos->puede_crear;
        }

        // Acciones de EDITAR/ELIMINAR
        if (
            in_array($action, ['edit', 'update', 'destroy', 'delete']) ||
            Str::startsWith($action, ['editar', 'actualizar', 'update', 'destroy', 'delete', 'eliminar', 'activar', 'toggle'])
        ) {
            return (bool) $permisos->puede_editar;
        }

        // Por defecto, requerir permiso de ver
        return (bool) $permisos->puede_ver;
    }
}
