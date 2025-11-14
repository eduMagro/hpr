@php
    use App\Services\MenuBuilder;
    $breadcrumbs = MenuBuilder::getBreadcrumbs();
    // Ocultar breadcrumbs si solo hay Dashboard o Dashboard + Sección principal
    $shouldShowBreadcrumbs = count($breadcrumbs) > 2;

    // Obtener la ruta actual y el menú completo
    $currentRoute = request()->route()->getName();
    $menu = config('menu.main');

    // Buscar la sección actual y sus pestañas
    $currentSection = null;
    $sectionTabs = [];

    foreach ($menu as $section) {
        if (isset($section['submenu'])) {
            foreach ($section['submenu'] as $item) {
                if ($item['route'] === $currentRoute || str_starts_with($currentRoute, str_replace('.index', '', $item['route']))) {
                    $currentSection = $section;
                    $sectionTabs = $section['submenu'];
                    break 2;
                }
            }
        }
    }
@endphp

@if($shouldShowBreadcrumbs)
<!-- Indicador de carga para wire:navigate -->
<div wire:loading class="fixed top-0 left-0 right-0 h-1 bg-blue-500 z-50 animate-pulse"></div>

<nav class="hidden md:flex items-center space-x-2 text-sm text-gray-600 mb-6">
    @foreach($breadcrumbs as $index => $crumb)
        @if($index > 0)
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        @endif

        @if($crumb['route'] && $index < count($breadcrumbs) - 1)
            <a href="{{ route($crumb['route']) }}"
               wire:navigate
               class="hover:text-blue-600 transition">
                {{ $crumb['label'] }}
            </a>
        @else
            {{-- Si es el último elemento y tenemos pestañas de sección, mostrarlas --}}
            @if(count($sectionTabs) > 0 && $index === count($breadcrumbs) - 1)
                <div class="flex items-center space-x-1 flex-wrap gap-y-1">
                    @foreach($sectionTabs as $tabIndex => $tab)
                        @if($tabIndex > 0)
                            <span class="text-gray-400">|</span>
                        @endif
                        <a href="{{ route($tab['route']) }}"
                           wire:navigate
                           class="px-3 py-1 rounded whitespace-nowrap {{ $currentRoute === $tab['route'] || str_starts_with($currentRoute, str_replace('.index', '', $tab['route'])) ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }} transition">
                            {{ $tab['icon'] ?? '' }} {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            @else
                <span class="font-medium text-gray-900">{{ $crumb['label'] }}</span>
            @endif
        @endif
    @endforeach
</nav>
@endif
