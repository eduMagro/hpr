<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Atajos de teclado
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-900 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Esta página enumera los atajos de teclado disponibles para navegar más rápido por la aplicación.
                </p>
            </div>
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <p class="text-gray-700 dark:text-gray-300 text-sm">
                    Los atajos pueden variar según la sección, pero a continuación tienes los más comunes:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Búsqueda rápida</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">K</kbd>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Abre el buscador global para saltar a cualquier sección.
                        </p>
                    </div>
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Crear nuevo registro</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">N</kbd>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            En algunas secciones sirve para abrir modales de nuevo elemento o formulario.
                        </p>
                    </div>
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Guardar formulario</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">Ctrl</kbd> +
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">Enter</kbd>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Activa el envío rápido en formularios que lo soportan.
                        </p>
                    </div>
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cerrar panel o modal</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">Esc</kbd>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Sale de la vista activa o cierra modales sin usar el ratón.
                        </p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Para más atajos específicos de cada módulo consulta los documentos de navegación o la ayuda integrada.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
