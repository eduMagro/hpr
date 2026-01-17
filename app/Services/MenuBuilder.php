<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    /**
     * Construye el menú filtrado para el usuario actual
     */
    public static function buildForUser($user)
    {
        if (!$user) {
            return [];
        }

        // Cachear el menú por usuario durante 30 minutos
        return Cache::remember("menu_user_{$user->id}", 1800, function () use ($user) {
            $menu = config('menu.main');

            if (!$menu || !is_array($menu)) {
                return [];
            }

            $permissions = app(PermissionService::class);
            $filteredMenu = [];

            foreach ($menu as $section) {
                $filteredSection = self::filterSection($section, $user, $permissions);
                if ($filteredSection) {
                    $filteredMenu[] = $filteredSection;
                }
            }

            return $filteredMenu;
        });
    }

    /**
     * Filtra una sección del menú según permisos del usuario
     * Los items sin permiso se marcan como 'disabled' en lugar de ocultarse
     */
    private static function filterSection($section, $user, PermissionService $permissions)
    {
        $filteredSubmenu = [];
        $hasAccessibleItems = false;

        if (isset($section['submenu'])) {
            foreach ($section['submenu'] as $item) {
                $filteredItem = $item;
                $canAccess = $permissions->canAccessRoute($user, $item['route']);

                // Marcar como disabled si no tiene acceso
                $filteredItem['disabled'] = !$canAccess;

                if ($canAccess) {
                    $hasAccessibleItems = true;

                    // Filtrar acciones según permisos
                    if (isset($item['actions'])) {
                        $filteredItem['actions'] = array_filter($item['actions'], function ($action) use ($user, $permissions) {
                            return $permissions->canAccessRoute($user, $action['route']);
                        });
                    }
                }

                $filteredSubmenu[] = $filteredItem;
            }
        }

        // Si no tiene ningún submenú accesible, no mostrar la sección
        if (!$hasAccessibleItems) {
            return null;
        }

        $section['submenu'] = $filteredSubmenu;
        return $section;
    }

    /**
     * Obtiene la ruta actual y genera breadcrumbs
     */
    public static function getBreadcrumbs()
    {
        $currentRoute = Route::currentRouteName();
        $breadcrumbs = [
            ['label' => 'Dashboard', 'route' => 'dashboard']
        ];

        $menu = config('menu.main');

        if (!$menu) {
            return $breadcrumbs;
        }

        foreach ($menu as $section) {
            if ($section['route'] === $currentRoute) {
                $breadcrumbs[] = ['label' => $section['label'], 'route' => $section['route']];
                return $breadcrumbs;
            }

            if (isset($section['submenu'])) {
                foreach ($section['submenu'] as $item) {
                    if ($item['route'] === $currentRoute) {
                        $breadcrumbs[] = ['label' => $section['label'], 'route' => $section['route']];
                        $breadcrumbs[] = ['label' => $item['label'], 'route' => $item['route']];
                        return $breadcrumbs;
                    }

                    if (isset($item['actions'])) {
                        foreach ($item['actions'] as $action) {
                            if ($action['route'] === $currentRoute) {
                                $breadcrumbs[] = ['label' => $section['label'], 'route' => $section['route']];
                                $breadcrumbs[] = ['label' => $item['label'], 'route' => $item['route']];
                                $breadcrumbs[] = ['label' => $action['label'], 'route' => null];
                                return $breadcrumbs;
                            }
                        }
                    }
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Limpia el caché del menú de un usuario
     */
    public static function clearUserCache($userId)
    {
        Cache::forget("menu_user_{$userId}");
    }

    /**
     * Limpia el caché del menú de todos los usuarios
     */
    public static function clearAllCache()
    {
        Cache::flush();
    }
}
