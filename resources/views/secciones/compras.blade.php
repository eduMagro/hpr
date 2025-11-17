<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-orange-700 flex items-center">
                    <span class="text-4xl mr-3">🛒</span>
                    Compras
                </h1>
                <p class="mt-2 text-sm text-gray-600">Gestión de pedidos y compras</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('pedidos.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 text-center">
                        <span class="text-6xl">🛒</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-orange-600 transition">Pedidos</h3>
                        <p class="text-xs text-gray-500 mt-1">Pedidos de compra</p>
                    </div>
                </a>

                <a href="{{ route('pedidos_globales.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 text-center">
                        <span class="text-6xl">🌐</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-orange-600 transition">Pedidos Globales</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión global de pedidos</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
