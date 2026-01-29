<x-app-layout>
    <x-slot name="title">Base de Datos</x-slot>

    <x-page-header
        title="Base de Datos"
        subtitle="Acceso centralizado a todas las tablas del sistema"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>'
    />

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Grid de categorías -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">

                <!-- Categoría: Producción -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-3">🏭</span>
                            Producción
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="{{ route('maquinas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-blue-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-blue-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🏭</span>
                                Máquinas
                            </span>
                        </a>
                        <a href="{{ route('elementos.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-blue-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-blue-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🔩</span>
                                Elementos
                            </span>
                        </a>
                        <a href="{{ route('etiquetas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-blue-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-blue-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🏷️</span>
                                Etiquetas
                            </span>
                        </a>
                        <a href="{{ route('paquetes.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-blue-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-blue-700 font-medium flex items-center">
                                <span class="text-xl mr-3">📦</span>
                                Paquetes
                            </span>
                        </a>
                        <a href="{{ route('planillas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-blue-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-blue-700 font-medium flex items-center">
                                <span class="text-xl mr-3">📄</span>
                                Planillas
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Categoría: Inventario -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-3">📦</span>
                            Inventario
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="{{ route('productos.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-green-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-green-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🧱</span>
                                Productos
                            </span>
                        </a>
                        <a href="{{ route('ubicaciones.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-green-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-green-700 font-medium flex items-center">
                                <span class="text-xl mr-3">📍</span>
                                Ubicaciones
                            </span>
                        </a>
                        <a href="{{ route('movimientos.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-green-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-green-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🔄</span>
                                Movimientos
                            </span>
                        </a>
                        <a href="{{ route('entradas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-green-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-green-700 font-medium flex items-center">
                                <span class="text-xl mr-3">⬇️</span>
                                Entradas
                            </span>
                        </a>
                        <a href="{{ route('salidas-ferralla.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-green-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-green-700 font-medium flex items-center">
                                <span class="text-xl mr-3">➡️</span>
                                Salidas Ferralla
                            </span>
                        </a>
                        <a href="{{ route('salidasAlmacen.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-green-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-green-700 font-medium flex items-center">
                                <span class="text-xl mr-3">📤</span>
                                Salidas Almacén
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Categoría: Comercial -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-3">🤝</span>
                            Comercial
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="{{ route('clientes.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-purple-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-purple-700 font-medium flex items-center">
                                <span class="text-xl mr-3">👥</span>
                                Clientes
                            </span>
                        </a>
                        <a href="{{ route('empresas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-purple-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-purple-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🏢</span>
                                Empresas
                            </span>
                        </a>
                        <a href="{{ route('fabricantes.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-purple-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-purple-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🏭</span>
                                Proveedores
                            </span>
                        </a>
                        <a href="{{ route('empresas-transporte.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-purple-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-purple-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🚚</span>
                                Empresas Transporte
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Categoría: Compras -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-3">🛒</span>
                            Compras
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="{{ route('pedidos.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-orange-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-orange-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🛒</span>
                                Pedidos
                            </span>
                        </a>
                        <a href="{{ route('pedidos_globales.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-orange-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-orange-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🌐</span>
                                Pedidos Globales
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Categoría: Recursos Humanos -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-3">👥</span>
                            Recursos Humanos
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="{{ route('user.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-indigo-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-indigo-700 font-medium flex items-center">
                                <span class="text-xl mr-3">👤</span>
                                Usuarios
                            </span>
                        </a>
                        <a href="{{ route('departamentos.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-indigo-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-indigo-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🏛️</span>
                                Departamentos
                            </span>
                        </a>
                        <a href="{{ route('vacaciones.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-indigo-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-indigo-700 font-medium flex items-center">
                                <span class="text-xl mr-3">🌴</span>
                                Vacaciones
                            </span>
                        </a>
                        <a href="{{ route('asignaciones-turnos.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-indigo-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-indigo-700 font-medium flex items-center">
                                <span class="text-xl mr-3">⏱️</span>
                                Turnos
                            </span>
                        </a>
                        <a href="{{ route('nominas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-indigo-50 transition-colors group">
                            <span class="text-gray-700 group-hover:text-indigo-700 font-medium flex items-center">
                                <span class="text-xl mr-3">💰</span>
                                Nóminas
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Categoría: Sistema -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-3">⚙️</span>
                            Sistema
                        </h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <a href="{{ route('alertas.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors group">
                            <span class="text-gray-700 group-hover:text-gray-800 font-medium flex items-center">
                                <span class="text-xl mr-3">🔔</span>
                                Alertas
                            </span>
                        </a>
                        <a href="{{ route('papelera.index') }}" class="block px-4 py-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors group">
                            <span class="text-gray-700 group-hover:text-gray-800 font-medium flex items-center">
                                <span class="text-xl mr-3">🗑️</span>
                                Papelera
                            </span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
