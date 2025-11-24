@php
use App\Services\MenuBuilder;
$menuItems = MenuBuilder::buildForUser(auth()->user());
$currentRoute = request()->route()->getName();
@endphp

@if(auth()->user()->esOficina())
<div data-navigate-once x-data="{
    open: window.innerWidth >= 768 ? (localStorage.getItem('sidebar_open') !== 'false') : false,
    activeSections: JSON.parse(localStorage.getItem('sidebar_active_sections') || '[]'),
    currentSectionId: null,
    currentPath: window.location.pathname,
    searchOpen: false,
    searchQuery: '',
    searchResults: [],
    selectedIndex: 0,
    favorites: JSON.parse(localStorage.getItem('nav_favorites') || '[]'),
    recentPages: JSON.parse(localStorage.getItem('nav_recent') || '[]'),
    darkMode: localStorage.getItem('dark_mode') === 'true',
    showFavorites: false,
    showRecent: false,
    isToggling: false,

    // Cerrar paneles desplegables en navegación
    closePanels() {
        this.showFavorites = false;
        this.showRecent = false;
        this.searchOpen = false;
    },

    init() {
        // Migrar favoritos y recientes antiguos: eliminar los que no tienen URL
        const favorites = JSON.parse(localStorage.getItem('nav_favorites') || '[]');
        const favoritesMigrados = favorites.filter(f => f.url);
        if (favoritesMigrados.length !== favorites.length) {
            localStorage.setItem('nav_favorites', JSON.stringify(favoritesMigrados));
            this.favorites = favoritesMigrados;
        }

        const recents = JSON.parse(localStorage.getItem('nav_recent') || '[]');
        const recentsMigrados = recents.filter(r => r.url);
        if (recentsMigrados.length !== recents.length) {
            localStorage.setItem('nav_recent', JSON.stringify(recentsMigrados));
            this.recentPages = recentsMigrados;
        }

        // Guardar estado inicial sin cambios visuales
        if (window.innerWidth < 768) {
            localStorage.setItem('sidebar_open', 'false');
        }

        // Restaurar secciones activas desde localStorage (multi-acordeón)
        const savedActiveSections = JSON.parse(localStorage.getItem('sidebar_active_sections') || '[]');
        if (Array.isArray(savedActiveSections) && savedActiveSections.length) {
            this.activeSections = savedActiveSections;
        } else {
            // Migración desde versión antigua con solo una sección
            const legacySection = localStorage.getItem('sidebar_active_section');
            if (legacySection) {
                this.activeSections = [legacySection];
                localStorage.setItem('sidebar_active_sections', JSON.stringify(this.activeSections));
            }
        }

        // Detectar sección activa inicial
        this.updateActiveSection();

        // Escuchar evento del botón hamburguesa
        window.addEventListener('toggle-sidebar', () => {
            this.toggleSidebar();
        });

        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            // Cmd/Ctrl + K: Búsqueda
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.searchOpen = !this.searchOpen;
                if (this.searchOpen) {
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }
            }

            // Cmd/Ctrl + B: Toggle sidebar
            if ((e.metaKey || e.ctrlKey) && e.key === 'b') {
                e.preventDefault();
                this.toggleSidebar();
            }

            // Cmd/Ctrl + H: Historial
            if ((e.metaKey || e.ctrlKey) && e.key === 'h') {
                e.preventDefault();
                this.showRecent = !this.showRecent;
            }

            // Navegación en búsqueda con flechas
            if (this.searchOpen) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.searchResults.length - 1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                } else if (e.key === 'Enter' && this.searchResults[this.selectedIndex]) {
                    e.preventDefault();
                    window.location.href = this.searchResults[this.selectedIndex].url;
                }
            }
        });

        // Aplicar modo oscuro
        this.applyDarkMode();

        // Actualizar sección activa cuando Livewire navega
        document.addEventListener('livewire:navigated', () => {
            // Cerrar paneles desplegables
            this.closePanels();

            // Pequeño delay para asegurar que la URL se actualizó
            setTimeout(() => {
                this.updateActiveSection();
            }, 50);
        });
    },

    updateActiveSection() {
        // Actualizar la ruta actual
        this.currentPath = window.location.pathname;

        // Resetear sección actual asociada a la ruta
        this.currentSectionId = null;

        // Detectar sección activa basándose en la ruta
        @foreach($menuItems as $index => $section)
        @if(isset($section['submenu']))
        @foreach($section['submenu'] as $item)
        if (this.isRouteActive('{{ route($item['route']) }}')) {
            const id = '{{ $section['id'] }}';

            // Sección asociada a la ruta actual (para resaltar visualmente)
            this.currentSectionId = id;

            // Asegurar que esa sección esté en el conjunto de acordeones abiertos
            if (!this.activeSections.includes(id)) {
                this.activeSections = [...this.activeSections, id];
                localStorage.setItem('sidebar_active_sections', JSON.stringify(this.activeSections));
            }

            // Agregar a recientes cuando navegamos
            this.addToRecent('{{ $item['route'] }}', '{{ $item['label'] }}', '{{ $section['label'] }}', '{{ $item['icon'] }}', '{{ route($item['route']) }}');
        }
        @endforeach
        @endif
        @endforeach

    },

    isRouteActive(routeUrl) {
        const url = new URL(routeUrl, window.location.origin);
        return window.location.pathname === url.pathname;
    },

    toggleSidebar() {
        // Activar estado de transición
        this.isToggling = true;

        // Disparar evento para iniciar animación de salida de logos
        window.dispatchEvent(new CustomEvent('logos-exit', {}));

        // Después de la animación de salida (200ms), cambiar el estado
        setTimeout(() => {
            this.open = !this.open;
            localStorage.setItem('sidebar_open', this.open);

            // Disparar evento con el nuevo estado
            window.dispatchEvent(new CustomEvent('sidebar-toggled', {
                detail: { isOpen: this.open }
            }));

            // Desactivar estado de transición después de que entre el nuevo logo (200ms)
            setTimeout(() => {
                this.isToggling = false;
            }, 200);
        }, 200);
    },

    toggleFavorite(route, label, section, icon, url = null) {
        const index = this.favorites.findIndex(f => f.route === route);
        if (index >= 0) {
            this.favorites.splice(index, 1);
        } else {
            this.favorites.push({ route, label, section, icon, url: url || this.getRouteUrl(route), addedAt: Date.now() });
        }
        localStorage.setItem('nav_favorites', JSON.stringify(this.favorites));
    },

    isFavorite(route) {
        return this.favorites.some(f => f.route === route);
    },

    getRouteUrl(routeName) {
        // Convertir nombre de ruta a URL
        // Ejemplo: 'pedidos.index' -> '/pedidos'
        //          'obras.edit' -> '/obras/edit'
        const parts = routeName.split('.');

        // Si termina en .index, solo usar la primera parte
        if (parts[parts.length - 1] === 'index') {
            return '/' + parts.slice(0, -1).join('/');
        }

        // Para otras rutas, convertir los puntos en barras
        return '/' + parts.join('/');
    },

    addToRecent(route, label, section, icon, url = null) {
        // Evitar duplicados
        this.recentPages = this.recentPages.filter(p => p.route !== route);

        // Agregar al inicio
        this.recentPages.unshift({ route, label, section, icon, url: url || this.getRouteUrl(route), visitedAt: Date.now() });

        // Mantener solo últimos 10
        if (this.recentPages.length > 10) {
            this.recentPages = this.recentPages.slice(0, 10);
        }

        localStorage.setItem('nav_recent', JSON.stringify(this.recentPages));
    },

    clearRecent() {
        if (confirm('¿Limpiar el historial de navegación?')) {
            this.recentPages = [];
            localStorage.removeItem('nav_recent');
        }
    },

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('dark_mode', this.darkMode);
        this.applyDarkMode();
    },

    applyDarkMode() {
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },

    performSearch() {
        const allItems = [];
        @foreach($menuItems as $section)
        @if(isset($section['submenu']))
        @foreach($section['submenu'] as $item)
        allItems.push({
            route: '{{ $item['route'] }}',
            label: '{{ $item['label'] }}',
            section: '{{ $section['label'] }}',
            icon: '{{ $item['icon'] }}',
            url: '{{ route($item['route']) }}'
        });
        @endforeach
        @endif
        @endforeach

        if (this.searchQuery === '') {
            this.searchResults = allItems.slice(0, 8);
        } else {
            const query = this.searchQuery.toLowerCase();
            this.searchResults = allItems.filter(item =>
                item.label.toLowerCase().includes(query) ||
                item.section.toLowerCase().includes(query)
            ).slice(0, 8);
        }
        this.selectedIndex = 0;
    }
}" @dark-mode-changed.window="darkMode = $event.detail; applyDarkMode()"
    :class="darkMode ? 'dark' : ''" class="flex h-screen">

    <!-- Overlay para móvil -->
    <div x-show="open"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-[9998] md:hidden" x-cloak>
    </div>

    <!-- Sidebar -->
    <div x-cloak x-show="true"
        :class="open ? 'w-64 translate-x-0' : 'w-16 -translate-x-full md:translate-x-0'"
        class="bg-gray-900 dark:bg-gray-950 text-white flex-shrink-0 flex flex-col fixed md:static inset-y-0 left-0 z-[9999] md:z-auto"
        style="transition: width 0.3s ease-in-out, transform 0.3s ease-in-out;">

        <!-- Header del Sidebar -->
        <div class="px-4 h-14 flex items-center justify-around border-b border-gray-800 border-r-0 overflow-hidden">

            <!-- Logo del Sidebar (visible cuando sidebar está abierto) -->
            <a x-show="open && !isToggling"
                x-transition:enter="transition-all duration-200 delay-200"
                x-transition:enter-start="opacity-0 transform -translate-y-16"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition-all duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-16"
                href="{{ route('dashboard') }}" wire:navigate
                wire:navigate
                class="flex items-center space-x-3 group relative z-50">
                <x-application-logo
                    class="block h-8 w-auto fill-current text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition" />
            </a>

            <button @click="toggleSidebar()" id="tooglesidebarbtn"
                class="p-2 hover:bg-gray-800 rounded-lg transition-all duration-300"
                title="Toggle Sidebar (Ctrl+B)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2"
                        :d="open ? 'M11 19l-7-7 7-7m8 14l-7-7 7-7' :
                            'M13 5l7 7-7 7M5 5l7 7-7 7'">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Botones de Acción Rápida -->
        <div class="p-3 space-y-2 border-b border-gray-800">
            <!-- Búsqueda -->
            <div class="relative">
                <button @click="searchOpen = true"
                    class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 transition text-sm group">
                    <svg class="w-5 h-5 flex-shrink-0 text-gray-400 group-hover:text-white transition"
                        fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                        </path>
                    </svg>
                    <span x-show="open"
                        class="text-gray-400 group-hover:text-white transition">Buscar
                        (Ctrl+K)</span>
                </button>
            </div>

            <!-- Favoritos -->
            <div class="relative">
                <button
                    @click="showFavorites = !showFavorites; showRecent = false"
                    class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-800 transition text-sm group relative"
                    :class="showFavorites ? 'bg-gray-800' : ''">
                    <svg class="w-5 h-5 flex-shrink-0 text-yellow-500"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z">
                        </path>
                    </svg>
                    <span x-show="open"
                        class="text-gray-400 group-hover:text-white transition">Favoritos</span>
                    <span x-show="open && favorites.length > 0"
                        class="ml-auto bg-yellow-600 text-xs px-2 py-0.5 rounded-full"
                        x-text="favorites.length"></span>
                    <span x-show="!open && favorites.length > 0"
                        class="absolute -top-1 -right-1 bg-yellow-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full"
                        x-text="favorites.length"></span>
                </button>

                <!-- Panel de Favoritos -->
                <div x-show="showFavorites" x-transition class="mt-2 space-y-1"
                    :class="open ? '' :
                        'absolute left-full ml-2 top-0 bg-gray-800 rounded-lg shadow-xl border border-gray-700 p-3 w-64 z-30'">
                    <div class="flex items-center justify-between mb-2"
                        x-show="open || favorites.length > 0">
                        <span
                            class="text-xs text-gray-500 uppercase font-semibold">Favoritos</span>
                    </div>
                    <template x-if="favorites.length === 0">
                        <p class="text-xs text-gray-500 italic px-3 py-2">No hay
                            favoritos aún</p>
                    </template>
                    <template x-for="fav in favorites" :key="fav.route">
                        <div
                            class="flex items-center group px-3 py-2 rounded-lg hover:bg-gray-700 transition">
                            <a :href="fav.url || `{{ url('/') }}${getRouteUrl(fav.route)}`"
                                wire:navigate
                                class="flex items-center space-x-2 flex-1 min-w-0">
                                <span x-text="fav.icon"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-gray-300 group-hover:text-white truncate text-sm"
                                        x-text="fav.label"></div>
                                    <div class="text-xs text-gray-500"
                                        x-text="fav.section"></div>
                                </div>
                            </a>
                            <button
                                @click.stop="toggleFavorite(fav.route, fav.label, fav.section, fav.icon)"
                                class="ml-2 p-1 rounded hover:bg-gray-600 transition opacity-0 group-hover:opacity-100"
                                title="Quitar de favoritos">
                                <svg class="w-4 h-4 text-red-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Historial -->
            <div class="relative">
                <button @click="showRecent = !showRecent; showFavorites = false"
                    class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-800 transition text-sm group"
                    :class="showRecent ? 'bg-gray-800' : ''">
                    <svg class="w-5 h-5 flex-shrink-0 text-blue-500"
                        fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    <span x-show="open"
                        class="text-gray-400 group-hover:text-white transition">Recientes
                        (Ctrl+H)</span>
                </button>

                <!-- Panel de Recientes -->
                <div x-show="showRecent" x-transition class="mt-2 space-y-1"
                    :class="open ? '' :
                        'absolute left-full ml-2 top-0 bg-gray-800 rounded-lg shadow-xl border border-gray-700 p-3 w-64 z-30'">
                    <div class="flex items-center justify-between mb-2">
                        <span
                            class="text-xs text-gray-500 uppercase font-semibold">Recientes</span>
                        <button @click="clearRecent()"
                            x-show="recentPages.length > 0"
                            class="text-xs text-gray-500 hover:text-white">Limpiar</button>
                    </div>
                    <template x-if="recentPages.length === 0">
                        <p class="text-xs text-gray-500 italic px-3 py-2">Sin
                            historial</p>
                    </template>
                    <template x-for="page in recentPages.slice(0, 5)"
                        :key="page.route">
                        <a :href="page.url || `{{ url('/') }}${getRouteUrl(page.route)}`"
                            wire:navigate
                            class="flex items-center space-x-2 px-3 py-2 rounded-lg text-sm hover:bg-gray-700 transition group">
                            <span x-text="page.icon"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-gray-300 group-hover:text-white truncate"
                                    x-text="page.label"></div>
                                <div class="text-xs text-gray-500"
                                    x-text="page.section"></div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <!-- Separador si hay favoritos o recientes abiertos -->
        <div x-show="open && (showFavorites || showRecent)"
            class="border-b border-gray-800"></div>

        <!-- Navegación principal -->
        <nav
            class="flex-1 overflow-y-auto px-2 py-4 space-y-1 scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-transparent">
            @foreach ($menuItems as $section)
            <div class="mb-2">
                <!-- Sección Principal -->
                <div class="relative">
                    <button
                        @click="if (open) {
                                const id = '{{ $section['id'] }}';
                                if (activeSections.includes(id)) {
                                    activeSections = activeSections.filter(s => s !== id);
                                } else {
                                    activeSections = [...activeSections, id];
                                }
                                localStorage.setItem('sidebar_active_sections', JSON.stringify(activeSections));
                            } else {
                                // Cuando está cerrado, abrir el sidebar y expandir la sección
                                // Usar toggleSidebar() para mantener la misma animación del logo
                                toggleSidebar();
                                // Después de la animación, expandir la sección sin cerrar otras
                                setTimeout(() => {
                                    const id = '{{ $section['id'] }}';
                                    if (!activeSections.includes(id)) {
                                        activeSections = [...activeSections, id];
                                        localStorage.setItem('sidebar_active_sections', JSON.stringify(activeSections));
                                    }
                                }, 400);
                            }"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition group text-gray-300 hover:bg-gray-800 hover:text-white"
                        :class="currentSectionId === '{{ $section['id'] }}'
                                ? 'bg-{{ $section['color'] }}-600 text-white shadow-lg'
                                : ''">
                        <div class="flex items-center space-x-3">
                            <span
                                class="text-xl flex-shrink-0">{{ $section['icon'] }}</span>
                            <span x-show="open" x-transition
                                class="font-medium">{{ $section['label'] }}</span>
                        </div>
                        <svg x-show="open"
                            :class="activeSections.includes('{{ $section['id'] }}') ?
                                    'rotate-180' : ''"
                            class="w-4 h-4 flex-shrink-0 transition-transform"
                            fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Submenú -->
                @if (isset($section['submenu']))
                <div x-show="open && activeSections.includes('{{ $section['id'] }}')"
                    x-transition
                    class="mt-2 ml-4 space-y-1 border-l-2 border-gray-700 pl-4">
                    @foreach ($section['submenu'] as $item)
                    <div class="flex items-center group">
                        <a href="{{ route($item['route']) }}" wire:navigate
                            wire:navigate
                            @click="if (window.innerWidth < 768) { open = false; localStorage.setItem('sidebar_open', 'false'); }"
                            :class="isRouteActive('{{ route($item['route']) }}')
                                            ? 'bg-{{ $section['color'] }}-500 text-white font-medium'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800'"
                            class="flex-1 flex items-center justify-between px-3 py-2 rounded-lg text-sm transition">
                            <div
                                class="flex items-center space-x-2">
                                <span>{{ $item['icon'] }}</span>
                                <span>{{ $item['label'] }}</span>
                            </div>
                            @if (isset($item['badge']))
                            <span
                                class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">3</span>
                            @endif
                        </a>

                        <!-- Botón de favorito -->
                        <button
                            @click="toggleFavorite('{{ $item['route'] }}', '{{ $item['label'] }}', '{{ $section['label'] }}', '{{ $item['icon'] }}', '{{ route($item['route']) }}')"
                            class="ml-2 p-1.5 rounded hover:bg-gray-800 transition opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4 transition"
                                :class="isFavorite(
                                                    '{{ $item['route'] }}') ?
                                                'text-yellow-500 fill-current' :
                                                'text-gray-500'"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                </path>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </nav>

        <!-- Footer del Sidebar -->
        <div class="p-3 border-t border-gray-800 space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" wire:navigate
                class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-800 transition text-sm group">
                <svg class="w-5 h-5 flex-shrink-0 text-gray-400 group-hover:text-white"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                <span x-show="open"
                    class="text-gray-400 group-hover:text-white">Dashboard</span>
            </a>

            <!-- Modo Oscuro -->
            <button @click="toggleDarkMode()"
                class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-800 transition text-sm group">
                <svg x-show="!darkMode"
                    class="w-5 h-5 flex-shrink-0 text-yellow-500"
                    fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                <svg x-show="darkMode"
                    class="w-5 h-5 flex-shrink-0 text-blue-400"
                    fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z">
                    </path>
                </svg>
                <span x-show="open"
                    class="text-gray-400 group-hover:text-white"
                    x-text="darkMode ? 'Modo Claro' : 'Modo Oscuro'"></span>
            </button>
        </div>
    </div>

    <!-- Modal de Búsqueda Global Mejorado -->
    <div x-show="searchOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click.self="searchOpen = false"
        x-cloak
        class="fixed inset-0 z-[10000] flex items-start justify-center pt-20 px-4 bg-black bg-opacity-60 backdrop-blur-sm">

        <div @click.away="searchOpen = false"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden">
            <!-- Input de búsqueda -->
            <div class="p-4 border-b dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                        </path>
                    </svg>
                    <input x-ref="searchInput" x-model="searchQuery"
                        @input="performSearch()" type="text"
                        placeholder="Buscar módulos, acciones..."
                        class="flex-1 text-lg border-0 focus:ring-0 focus:outline-none dark:bg-gray-800 dark:text-white"
                        @keydown.escape="searchOpen = false">
                </div>
            </div>

            <!-- Resultados -->
            <div class="max-h-96 overflow-y-auto">
                <template x-if="searchResults.length === 0">
                    <div
                        class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50"
                            fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01">
                            </path>
                        </svg>
                        <p>No se encontraron resultados</p>
                    </div>
                </template>
                <template x-for="(result, index) in searchResults"
                    :key="result.route">
                    <a :href="result.url"
                        class="block px-4 py-3 transition"
                        :class="index === selectedIndex ?
                            'bg-blue-50 dark:bg-gray-700' :
                            'hover:bg-gray-50 dark:hover:bg-gray-700'">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl"
                                x-text="result.icon"></span>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white"
                                    x-text="result.label"></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="result.section"></div>
                            </div>
                            <kbd class="hidden sm:block px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded"
                                x-text="'Ctrl+' + (index + 1)"></kbd>
                        </div>
                    </a>
                </template>
            </div>

            <!-- Footer del modal -->
            <div
                class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center">
                        <kbd
                            class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded mr-1">↑↓</kbd>
                        Navegar
                    </span>
                    <span class="flex items-center">
                        <kbd
                            class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded mr-1">Enter</kbd>
                        Seleccionar
                    </span>
                </div>
                <span class="flex items-center">
                    <kbd
                        class="px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded mr-1">ESC</kbd>
                    Cerrar
                </span>
            </div>
        </div>
    </div>
</div>
@endif

<style>
    /* Scrollbar personalizado */
    .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
    }

    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #4B5563;
        border-radius: 3px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: #6B7280;
    }

    /* Animaciones suaves */
    [x-cloak] {
        display: none !important;
    }
</style>