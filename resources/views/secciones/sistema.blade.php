<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-700 flex items-center">
                    <span class="text-4xl mr-3">⚙️</span>
                    Sistema
                </h1>
                <p class="mt-2 text-sm text-gray-600">Configuración, alertas y herramientas del sistema</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('alertas.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-gray-600 to-gray-700 p-6 text-center">
                        <span class="text-6xl">🔔</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-gray-700 transition">Alertas</h3>
                        <p class="text-xs text-gray-500 mt-1">Mensajes y notificaciones</p>
                    </div>
                </a>

                <a href="{{ route('papelera.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-gray-600 to-gray-700 p-6 text-center">
                        <span class="text-6xl">🗑️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-gray-700 transition">Papelera</h3>
                        <p class="text-xs text-gray-500 mt-1">Elementos eliminados</p>
                    </div>
                </a>

                <a href="{{ route('ayuda.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-gray-600 to-gray-700 p-6 text-center">
                        <span class="text-6xl">❓</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-gray-700 transition">Ayuda</h3>
                        <p class="text-xs text-gray-500 mt-1">Centro de ayuda</p>
                    </div>
                </a>

                <a href="{{ route('estadisticas.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-gray-600 to-gray-700 p-6 text-center">
                        <span class="text-6xl">📊</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-gray-700 transition">Estadísticas</h3>
                        <p class="text-xs text-gray-500 mt-1">Informes y métricas</p>
                    </div>
                </a>

                <a href="{{ route('asistente.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 text-center">
                        <span class="text-6xl">⚡</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-gray-700 transition">FERRALLIN</h3>
                        <p class="text-xs text-gray-500 mt-1">Asistente virtual IA</p>
                    </div>
                </a>

                <a href="{{ route('departamentos.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-gray-600 to-gray-700 p-6 text-center">
                        <span class="text-6xl">🔐</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-gray-700 transition">Permisos</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de departamentos</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
