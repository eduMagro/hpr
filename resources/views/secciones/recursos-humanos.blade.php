<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-indigo-700 flex items-center">
                    <span class="text-4xl mr-3">👥</span>
                    Recursos Humanos
                </h1>
                <p class="mt-2 text-sm text-gray-600">Gestión de usuarios, vacaciones y registros de entrada/salida</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <a href="{{ route('users.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-center">
                        <span class="text-6xl">👤</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 transition">Usuarios</h3>
                        <p class="text-xs text-gray-500 mt-1">Vista tabla usuarios</p>
                    </div>
                </a>

                <a href="{{ route('register') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-center">
                        <span class="text-6xl">➕</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 transition">Registrar Usuario</h3>
                        <p class="text-xs text-gray-500 mt-1">Crear nuevo usuario</p>
                    </div>
                </a>

                <a href="{{ route('vacaciones.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-center">
                        <span class="text-6xl">🌴</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 transition">Vacaciones</h3>
                        <p class="text-xs text-gray-500 mt-1">Gestión de vacaciones</p>
                    </div>
                </a>

                <a href="{{ route('asignaciones-turnos.index') }}" class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-6 text-center">
                        <span class="text-6xl">🕐</span>
                    </div>
                    <div class="p-4 text-center">
                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-600 transition">Registros Entrada/Salida</h3>
                        <p class="text-xs text-gray-500 mt-1">Control de horarios</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
