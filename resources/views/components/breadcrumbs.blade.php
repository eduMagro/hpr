@php
    use App\Services\MenuBuilder;
    $breadcrumbs = MenuBuilder::getBreadcrumbs();
    // Ocultar breadcrumbs si solo hay Dashboard o Dashboard + Sección principal
    $shouldShowBreadcrumbs = count($breadcrumbs) > 2;

    // Detectar si estamos en alguna ruta de planificación
    $currentRoute = request()->route()->getName();
    $isPlanificacionRoute = in_array($currentRoute, ['planificacion.index', 'produccion.verMaquinas']);
@endphp

@if($shouldShowBreadcrumbs)
<nav class="flex items-center space-x-2 text-sm text-gray-600 mb-6">
    @foreach($breadcrumbs as $index => $crumb)
        @if($index > 0)
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        @endif

        @if($crumb['route'] && $index < count($breadcrumbs) - 1)
            <a href="{{ route($crumb['route']) }}"
               class="hover:text-blue-600 transition">
                {{ $crumb['label'] }}
            </a>
        @else
            {{-- Si es el último elemento y estamos en planificación, mostrar pestañas --}}
            @if($isPlanificacionRoute && $index === count($breadcrumbs) - 1)
                <div class="flex items-center space-x-1">
                    <a href="{{ route('produccion.verMaquinas') }}"
                       class="px-3 py-1 rounded {{ $currentRoute === 'produccion.verMaquinas' ? 'bg-purple-100 text-purple-700 font-semibold' : 'text-gray-600 hover:text-purple-600 hover:bg-purple-50' }} transition">
                        Planificación Máquinas
                    </a>
                    <span class="text-gray-400">|</span>
                    <a href="{{ route('planificacion.index') }}"
                       class="px-3 py-1 rounded {{ $currentRoute === 'planificacion.index' ? 'bg-purple-100 text-purple-700 font-semibold' : 'text-gray-600 hover:text-purple-600 hover:bg-purple-50' }} transition">
                        Planificación Portes
                    </a>
                </div>
            @else
                <span class="font-medium text-gray-900">{{ $crumb['label'] }}</span>
            @endif
        @endif
    @endforeach
</nav>
@endif
