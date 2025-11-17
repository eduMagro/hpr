<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-green-700 flex items-center">
                    <span class="text-4xl mr-3">📦</span>
                    Inventario
                </h1>
                <p class="mt-2 text-sm text-gray-600">Gestión de productos, movimientos y ubicaciones</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('productos.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-center">
                        <span class="text-6xl">🧱</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-600 transition">Materia Prima</h3>
                        <p class="text-xs text-gray-500 mt-1">Materiales en stock</p>
                    </div>
                </a>

                <a href="{{ route('ubicaciones.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-center">
                        <span class="text-6xl">📍</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-600 transition">Ubicaciones</h3>
                        <p class="text-xs text-gray-500 mt-1">Ubicaciones de almacén</p>
                    </div>
                </a>

                <a href="{{ route('movimientos.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-center">
                        <span class="text-6xl">🔄</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-600 transition">Movimientos</h3>
                        <p class="text-xs text-gray-500 mt-1">Movimientos de inventario</p>
                    </div>
                </a>

                <a href="{{ route('entradas.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-center">
                        <span class="text-6xl">⬇️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-600 transition">Entradas</h3>
                        <p class="text-xs text-gray-500 mt-1">Entradas de material</p>
                    </div>
                </a>

                <a href="{{ route('salidas-ferralla.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-center">
                        <span class="text-6xl">➡️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-600 transition">Salidas Ferralla</h3>
                        <p class="text-xs text-gray-500 mt-1">Salidas de ferralla</p>
                    </div>
                </a>

                <a href="{{ route('salidas-almacen.index') }}" wire:navigate class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-center">
                        <span class="text-6xl">📤</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-600 transition">Salidas Almacén</h3>
                        <p class="text-xs text-gray-500 mt-1">Salidas de almacén</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
