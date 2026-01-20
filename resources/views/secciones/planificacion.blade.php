<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-purple-700 flex items-center">
                    <span class="text-4xl mr-3">📅</span>
                    Planificación
                </h1>
                <p class="mt-2 text-sm text-gray-600">Gestión de planificación de trabajadores, máquinas y portes</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('produccion.verMaquinas') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">⚙️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Planificacion Maquinas</h3>
                        <p class="text-xs text-gray-500 mt-1">Planificacion de maquinas</p>
                    </div>
                </a>

                <a href="{{ route('produccion.ordenesPlanillasTabla') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-center">
                        <span class="text-6xl">📋</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 transition">Ordenes Planillas</h3>
                        <p class="text-xs text-gray-500 mt-1">Vista tabla de ordenes</p>
                    </div>
                </a>

                <a href="{{ route('produccion.maquinasEnsamblaje') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 text-center">
                        <span class="text-6xl">🔧</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-orange-600 transition">Planificación Ensamblaje</h3>
                        <p class="text-xs text-gray-500 mt-1">Control de ensambladoras</p>
                    </div>
                </a>

                <a href="{{ route('planificacion.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">🚚</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Planificación Portes</h3>
                        <p class="text-xs text-gray-500 mt-1">Calendario de transportes</p>
                    </div>
                </a>

                <a href="{{ route('produccion.verTrabajadores') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">👷</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Trabajadores</h3>
                        <p class="text-xs text-gray-500 mt-1">Planificación de trabajadores</p>
                    </div>
                </a>

                <a href="{{ route('produccion.verTrabajadoresObra') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-center">
                        <span class="text-6xl">🏗️</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition">Trabajadores Obra</h3>
                        <p class="text-xs text-gray-500 mt-1">Asignaciones a obras</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
