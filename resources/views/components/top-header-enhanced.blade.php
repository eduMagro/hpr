<nav x-data="{
    open: false,
    notificationsOpen: false,
    userMenuOpen: false,
    quickActionsOpen: false
}" x-cloak class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm flex-shrink-0 transition-colors">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">
            <!-- Logo & Title -->
            <div class="flex items-center space-x-4">
                <!-- Botón hamburguesa para móvil -->
                <button @click="$dispatch('toggle-sidebar')"
                        class="md:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                    <x-application-logo class="block h-8 w-auto fill-current text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition" />
                </a>
            </div>

            <!-- Center - Quick Actions -->
            <div class="hidden lg:flex items-center space-x-2">
                <!-- Acciones Rápidas Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            @click.away="open = false"
                            class="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Acciones Rápidas</span>
                        <svg class="w-4 h-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div x-show="open"
                         x-transition
                         x-cloak
                         class="absolute left-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                        <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Acciones Rápidas</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Accede rápidamente a funciones comunes</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 p-3">
                            <!-- Nueva Planilla -->
                            <a href="{{ route('planillas.create') }}"
                               class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-center group">
                                <span class="text-2xl mb-1">📄</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Nueva Planilla</span>
                            </a>

                            <!-- Nueva Entrada -->
                            <a href="{{ route('entradas.create') }}"
                               class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-center group">
                                <span class="text-2xl mb-1">⬇️</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Nueva Entrada</span>
                            </a>

                            <!-- Nueva Salida -->
                            <a href="{{ route('salidas-ferralla.create') }}"
                               class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-center group">
                                <span class="text-2xl mb-1">➡️</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Nueva Salida</span>
                            </a>

                            <!-- Nuevo Pedido -->
                            <a href="{{ route('pedidos.create') }}"
                               class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-center group">
                                <span class="text-2xl mb-1">🛒</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Nuevo Pedido</span>
                            </a>

                            <!-- Nuevo Cliente -->
                            <a href="{{ route('clientes.create') }}"
                               class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-center group">
                                <span class="text-2xl mb-1">👥</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Nuevo Cliente</span>
                            </a>

                            <!-- Estadísticas -->
                            <a href="{{ route('estadisticas.index') }}"
                               class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-center group">
                                <span class="text-2xl mb-1">📊</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">Estadísticas</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - User Actions -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- Notificaciones -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            @click.away="open = false"
                            class="relative p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <!-- Badge de notificaciones -->
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    <!-- Dropdown Notificaciones -->
                    <div x-show="open"
                         x-transition
                         x-cloak
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notificaciones</h3>
                            <a href="{{ route('alertas.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Ver todas</a>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <!-- Ejemplo de notificaciones -->
                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-start space-x-3">
                                    <span class="text-2xl">📦</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Nuevo paquete creado</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Paquete #1234 ha sido agregado</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Hace 5 minutos</p>
                                    </div>
                                    <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-1.5"></span>
                                </div>
                            </a>

                            <a href="#" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-start space-x-3">
                                    <span class="text-2xl">✅</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Pedido completado</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Pedido #456 entregado</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Hace 1 hora</p>
                                    </div>
                                </div>
                            </a>

                            <div class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                                <p>No hay más notificaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            @click.away="open = false"
                            class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr(Auth::user()->nombre_completo, 0, 1)) }}
                        </div>
                        <span class="hidden sm:block">{{ Auth::user()->nombre_completo }}</span>
                        <svg class="w-4 h-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div x-show="open"
                         x-transition
                         x-cloak
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ Auth::user()->nombre_completo }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <div class="py-2">
                            <a href="{{ route('usuarios.show', auth()->id()) }}"
                               class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Mi Perfil</span>
                            </a>

                            <a href="{{ route('dashboard') }}"
                               class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('ayuda.index') }}"
                               class="flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Ayuda</span>
                            </a>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 py-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center space-x-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition text-left">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
