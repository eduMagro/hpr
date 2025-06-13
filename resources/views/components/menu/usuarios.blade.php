@php
    $rutaActual = request()->route()->getName();
@endphp

@if (Auth::check() && Auth::user()->rol == 'oficina')
    <div class="w-full" x-data="{ open: false }">
        <!-- Menú móvil -->
        <div class="sm:hidden relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                Opciones
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                x-cloak>

                <a href="{{ route('users.index') }}"
                    class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                    📋 Usuarios
                </a>

                <a href="{{ route('register') }}"
                    class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('register') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                    📋 Registrar Usuario
                </a>

                <a href="{{ route('vacaciones.index') }}"
                    class="relative block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('vacaciones.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                    🌴 Vacaciones
                    @isset($totalSolicitudesPendientes)
                        @if ($totalSolicitudesPendientes > 0)
                            <span
                                class="absolute top-2 right-4 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $totalSolicitudesPendientes }}
                            </span>
                        @endif
                    @endisset
                </a>

                <a href="{{ route('asignaciones-turnos.index') }}"
                    class="block px-2 py-3 transition text-sm font-medium
                    {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                    ⏱️ Registros
                </a>
            </div>
        </div>

        <!-- Menú escritorio -->
        <div class="hidden sm:flex sm:mt-0 w-full">
            <a href="{{ route('users.index') }}"
                class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                📋 Usuarios
            </a>

            <a href="{{ route('register') }}"
                class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('register') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                📋 Registrar Usuario
            </a>

            <a href="{{ route('vacaciones.index') }}"
                class="relative flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ request()->routeIs('vacaciones.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                🌴 Vacaciones
                @isset($totalSolicitudesPendientes)
                    @if ($totalSolicitudesPendientes > 0)
                        <span
                            class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow">
                            {{ $totalSolicitudesPendientes }}
                        </span>
                    @endif
                @endisset
            </a>

            <a href="{{ route('asignaciones-turnos.index') }}"
                class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ request()->routeIs('asignaciones-turnos.*') ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                ⏱️ Registros Entrada y Salida
            </a>
        </div>
    </div>
@endif
