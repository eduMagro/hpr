<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-purple-700 flex items-center">
                    <span class="text-4xl mr-3">🤝</span>
                    Comercial
                </h1>
                <p class="mt-2 text-sm text-gray-600">Gestión de clientes, empresas y proveedores</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('clientes.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">👥</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Clientes</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de clientes</p>
                    </div>
                </a>

                <a href="{{ route('empresas.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">🏢</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Empresas</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de empresas</p>
                    </div>
                </a>

                <a href="{{ route('fabricantes.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">🏭</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Proveedores</h3>
                        <p class="text-xs text-gray-500 mt-1">Fabricantes y proveedores</p>
                    </div>
                </a>

                <a href="{{ route('empresas-transporte.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">🚚</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Transporte</h3>
                        <p class="text-xs text-gray-500 mt-1">Empresas de transporte</p>
                    </div>
                </a>

                <a href="{{ route('planificacion.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-center">
                        <span class="text-6xl">📅</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 transition">Planificación Portes</h3>
                        <p class="text-xs text-gray-500 mt-1">Calendario de portes</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
