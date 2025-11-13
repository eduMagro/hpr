<?php

namespace App\Services;

class MenuService
{
    /**
     * Obtiene el menú contextual para una sección específica
     *
     * @param string $section Nombre de la sección
     * @param array $badges Array de badges por ruta ['route.name' => count]
     * @return array
     */
    public static function getContextMenu(string $section, array $badges = []): array
    {
        $menus = config('menu.context_menus');

        if (!isset($menus[$section])) {
            return [
                'items' => [],
                'config' => self::getDefaultConfig()
            ];
        }

        $menu = $menus[$section];

        return [
            'items' => $menu['items'],
            'config' => array_merge(self::getDefaultConfig(), $menu['config'] ?? []),
            'badges' => $badges,
        ];
    }

    /**
     * Obtiene la configuración por defecto para los menús contextuales
     */
    private static function getDefaultConfig(): array
    {
        return [
            'colorBase' => 'blue',
            'style' => 'tabs',
            'size' => 'md',
            'checkRole' => null,
            'mobileLabel' => 'Menú',
        ];
    }

    /**
     * Obtiene los items del menú principal para una sección
     */
    public static function getSectionMenu(string $sectionId): array
    {
        $menu = config('menu.main');

        foreach ($menu as $section) {
            if ($section['id'] === $sectionId) {
                return $section;
            }
        }

        return [];
    }

    /**
     * Genera breadcrumbs para una ruta específica
     */
    public static function getBreadcrumbs(string $routeName): array
    {
        $menu = config('menu.main');
        $breadcrumbs = [
            ['label' => 'Dashboard', 'route' => 'dashboard']
        ];

        foreach ($menu as $section) {
            // Verificar si la ruta está en el primer nivel
            if (isset($section['route']) && $section['route'] === $routeName) {
                $breadcrumbs[] = [
                    'label' => $section['label'],
                    'route' => $section['route']
                ];
                return $breadcrumbs;
            }

            // Buscar en el submenu
            if (isset($section['submenu'])) {
                foreach ($section['submenu'] as $item) {
                    if ($item['route'] === $routeName || str_starts_with($routeName, $item['route'])) {
                        $breadcrumbs[] = [
                            'label' => $section['label'],
                            'route' => $section['route']
                        ];
                        $breadcrumbs[] = [
                            'label' => $item['label'],
                            'route' => $item['route']
                        ];
                        return $breadcrumbs;
                    }

                    // Buscar en las acciones
                    if (isset($item['actions'])) {
                        foreach ($item['actions'] as $action) {
                            if ($action['route'] === $routeName) {
                                $breadcrumbs[] = [
                                    'label' => $section['label'],
                                    'route' => $section['route']
                                ];
                                $breadcrumbs[] = [
                                    'label' => $item['label'],
                                    'route' => $item['route']
                                ];
                                $breadcrumbs[] = [
                                    'label' => $action['label'],
                                    'route' => $action['route']
                                ];
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
     * Verifica si una ruta debe mostrar menú contextual
     */
    public static function hasContextMenu(string $routeName): bool
    {
        $menus = config('menu.context_menus');

        foreach ($menus as $menu) {
            foreach ($menu['items'] as $item) {
                if ($item['route'] === $routeName || str_starts_with($routeName, $item['route'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtiene el nombre de la sección para una ruta específica
     */
    public static function getSectionForRoute(string $routeName): ?string
    {
        $menus = config('menu.context_menus');

        foreach ($menus as $sectionName => $menu) {
            foreach ($menu['items'] as $item) {
                if ($item['route'] === $routeName || str_starts_with($routeName, $item['route'])) {
                    return $sectionName;
                }
            }
        }

        return null;
    }
}
