<x-app-layout>
    <x-slot name="title">Atajos de Teclado</x-slot>

    <div class="py-6 px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-4xl">⌨️</span>
                    Atajos de Teclado
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Guía completa de atajos para navegar rápidamente por el sistema
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Atajos Globales -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-900 dark:bg-gray-950 text-white px-6 py-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span class="text-2xl">🌐</span>
                            Atajos Globales
                        </h2>
                        <p class="text-gray-400 text-sm mt-1">Funcionan en todas las páginas</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Navegación -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Navegación</h3>
                                <div class="space-y-2">
                                    <x-shortcut-row keys="Ctrl+B" description="Abrir/cerrar menú lateral" />
                                    <x-shortcut-row keys="Ctrl+K" description="Búsqueda rápida global" />
                                    <x-shortcut-row keys="Ctrl+H" description="Ver historial de páginas recientes" />
                                    <x-shortcut-row keys="?" description="Abrir esta página de atajos" />
                                </div>
                            </div>

                            <!-- Menú Lateral -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Menú Lateral</h3>
                                <div class="space-y-2">
                                    <x-shortcut-row keys="↑ ↓" description="Navegar entre secciones e items" />
                                    <x-shortcut-row keys="← →" description="Colapsar/expandir sección" />
                                    <x-shortcut-row keys="Enter" description="Abrir página o expandir sección" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Planificación de Salidas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-purple-600 text-white px-6 py-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span class="text-2xl">📅</span>
                            Planificación de Salidas
                        </h2>
                        <p class="text-purple-200 text-sm mt-1">Calendario de planificación</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Modo Días -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded font-medium">MODO DÍAS</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">(por defecto)</span>
                                </div>
                                <div class="space-y-2">
                                    <x-shortcut-row keys="← →" description="Día anterior/siguiente" />
                                    <x-shortcut-row keys="↑ ↓" description="Semana anterior/siguiente" />
                                    <x-shortcut-row keys="Enter" description="Ir a vista diaria" />
                                    <x-shortcut-row keys="T" description="Ir a hoy" />
                                    <x-shortcut-row keys="Home" description="Primer día del mes" />
                                    <x-shortcut-row keys="End" description="Último día del mes" />
                                    <x-shortcut-row keys="PgUp / PgDn" description="Mes anterior/siguiente" />
                                </div>
                            </div>

                            <!-- Modo Eventos -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded font-medium">MODO EVENTOS</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">(Tab para cambiar)</span>
                                </div>
                                <div class="space-y-2">
                                    <x-shortcut-row keys="↑ ↓" description="Evento anterior/siguiente" />
                                    <x-shortcut-row keys="Enter" description="Abrir evento" />
                                    <x-shortcut-row keys="E" description="Abrir menú contextual" />
                                    <x-shortcut-row keys="I" description="Ver información del evento" />
                                    <x-shortcut-row keys="Home / End" description="Primer/último evento" />
                                    <x-shortcut-row keys="Esc" description="Volver a modo días" />
                                </div>
                            </div>

                            <!-- General -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">General</h3>
                                <div class="space-y-2">
                                    <x-shortcut-row keys="Tab" description="Alternar entre modo días/eventos" />
                                    <x-shortcut-row keys="F11" description="Pantalla completa" />
                                    <x-shortcut-row keys="Esc" description="Salir de pantalla completa" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Máquinas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-orange-500 text-white px-6 py-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span class="text-2xl">🏭</span>
                            Vista de Máquinas
                        </h2>
                        <p class="text-orange-200 text-sm mt-1">Gestión de producción</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <x-shortcut-row keys="← →" description="Navegar entre posiciones" />
                            <x-shortcut-row keys="Enter" description="Seleccionar posición" />
                            <x-shortcut-row keys="Esc" description="Cerrar modal/popup" />
                        </div>
                    </div>
                </div>

                <!-- Tablas y Listados -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-teal-600 text-white px-6 py-4">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span class="text-2xl">📋</span>
                            Tablas y Listados
                        </h2>
                        <p class="text-teal-200 text-sm mt-1">Navegación en tablas de datos</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <x-shortcut-row keys="↑ ↓" description="Navegar entre filas" />
                            <x-shortcut-row keys="Enter" description="Ver detalle del elemento" />
                            <x-shortcut-row keys="/" description="Enfocar búsqueda" />
                            <x-shortcut-row keys="Esc" description="Limpiar búsqueda/selección" />
                        </div>
                    </div>
                </div>

            </div>

            <!-- Leyenda -->
            <div class="mt-8 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Leyenda de teclas</h3>
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <kbd class="px-2 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm text-gray-700 dark:text-gray-300 font-mono text-xs">Ctrl</kbd>
                        <span class="text-gray-600 dark:text-gray-400">= Control (Windows) / Command (Mac)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="px-2 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm text-gray-700 dark:text-gray-300 font-mono text-xs">PgUp/Dn</kbd>
                        <span class="text-gray-600 dark:text-gray-400">= Re Pág / Av Pág</span>
                    </div>
                </div>
            </div>

            <!-- Tips -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Consejos
                </h3>
                <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                    <li>• Los atajos no funcionan cuando estás escribiendo en un campo de texto</li>
                    <li>• En cada vista con atajos específicos verás un botón <span class="inline-flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs">?</span> en la esquina inferior izquierda</li>
                    <li>• Presiona <kbd class="px-1.5 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">Esc</kbd> para cancelar cualquier operación en curso</li>
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
