<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-blue-700 flex items-center">
                    <span class="text-4xl mr-3">🏭</span>
                    Producción
                </h1>
                <p class="mt-2 text-sm text-gray-600">Máquinas, productos, planillas, etiquetas, elementos, paquetes, ubicaciones y movimientos</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('maquinas.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">⚙️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Máquinas</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de maquinaria</p>
                    </div>
                </a>

                <a href="{{ route('productos.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">🧱</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Productos</h3>
                        <p class="text-xs text-gray-500 mt-1">Materiales en stock</p>
                    </div>
                </a>

                <a href="{{ route('planillas.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">📄</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Planillas</h3>
                        <p class="text-xs text-gray-500 mt-1">Órdenes de producción</p>
                    </div>
                </a>

                <a href="{{ route('etiquetas.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">🏷️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Etiquetas</h3>
                        <p class="text-xs text-gray-500 mt-1">Etiquetas y subetiquetas</p>
                    </div>
                </a>

                <a href="{{ route('elementos.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">🔩</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Elementos</h3>
                        <p class="text-xs text-gray-500 mt-1">Elementos de producción</p>
                    </div>
                </a>

                <a href="{{ route('paquetes.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">📦</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Paquetes</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de paquetes</p>
                    </div>
                </a>

                <a href="{{ route('ubicaciones.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">📍</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Ubicaciones</h3>
                        <p class="text-xs text-gray-500 mt-1">Ubicaciones de almacén</p>
                    </div>
                </a>

                <a href="{{ route('movimientos.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <span class="text-6xl">🔄</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Movimientos</h3>
                        <p class="text-xs text-gray-500 mt-1">Movimientos de inventario</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
