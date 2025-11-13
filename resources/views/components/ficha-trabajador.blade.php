@props(['user', 'resumen'])

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-4 sm:py-6 lg:py-8">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">

        {{-- Header con banner degradado --}}
        <div class="bg-gray-900 dark:bg-gray-950 rounded-xl sm:rounded-2xl shadow-2xl mb-4 sm:mb-6 overflow-hidden">
            <div class="relative bg-gradient-to-br from-gray-800/50 to-gray-900/50 backdrop-blur-sm">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative p-4 sm:p-6 md:p-8">
                    <div class="flex flex-col items-center text-center gap-4 sm:flex-row sm:items-center sm:text-left sm:gap-6">
                        {{-- Avatar grande --}}
                        <div class="relative z-10 flex-shrink-0">
                            @if ($user->ruta_imagen)
                                <div class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 rounded-xl sm:rounded-2xl ring-4 ring-gray-700 shadow-2xl overflow-hidden bg-white">
                                    <img src="{{ $user->ruta_imagen }}" alt="Foto de perfil"
                                        class="w-full h-full object-cover">
                                </div>
                            @else
                                <div class="w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 bg-gradient-to-br from-gray-700 to-gray-800 rounded-xl sm:rounded-2xl flex items-center justify-center text-4xl sm:text-5xl font-bold text-white shadow-2xl ring-4 ring-gray-700">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif

                            {{-- Botón cambiar foto --}}
                            <form method="POST" action="{{ route('usuarios.editarSubirImagen') }}" enctype="multipart/form-data"
                                class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 z-20">
                                @csrf
                                <label class="flex items-center justify-center bg-white rounded-full p-2 sm:p-2.5 shadow-lg cursor-pointer hover:bg-gray-100 transition-all hover:scale-110 border-2 border-gray-700 active:scale-95">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <input type="file" name="imagen" accept="image/*" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>

                        {{-- Nombre en el banner --}}
                        <div class="flex-1 w-full sm:w-auto">
                            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-white drop-shadow-lg break-words">{{ $user->nombre_completo }}</h1>
                            <p class="text-sm sm:text-base text-gray-300 mt-1">{{ $user->categoria->nombre ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid de contenido --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

            {{-- Columna izquierda: Información personal --}}
            <div class="lg:col-span-1 space-y-4 sm:space-y-6">

                {{-- Card de contacto --}}
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gray-900 dark:bg-gray-950 p-3 sm:p-4">
                        <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="truncate">Información de contacto</span>
                        </h3>
                    </div>
                    <div class="p-4 sm:p-5 md:p-6">

                    <div class="space-y-2 sm:space-y-3">
                        <div class="flex items-start gap-2 sm:gap-3 p-2.5 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] sm:text-xs text-gray-500 mb-0.5 sm:mb-1">Email</p>
                                <p class="text-xs sm:text-sm text-gray-900 break-all">{{ $user->email }}</p>
                            </div>
                        </div>

                        @if ($user->movil_empresa)
                            <div class="flex items-start gap-2 sm:gap-3 p-2.5 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] sm:text-xs text-gray-500 mb-0.5 sm:mb-1">Teléfono empresa</p>
                                    <p class="text-xs sm:text-sm text-gray-900 break-all">{{ $user->movil_empresa }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($user->movil_personal)
                            <div class="flex items-start gap-2 sm:gap-3 p-2.5 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] sm:text-xs text-gray-500 mb-0.5 sm:mb-1">Teléfono personal</p>
                                    <p class="text-xs sm:text-sm text-gray-900 break-all">{{ $user->movil_personal }}</p>
                                </div>
                            </div>
                        @endif

                        @if (!$user->movil_empresa && !$user->movil_personal)
                            <p class="text-xs sm:text-sm text-gray-500 italic text-center py-2">Sin teléfonos registrados</p>
                        @endif
                    </div>
                    </div>
                </div>

                {{-- Card de información laboral --}}
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                    <div class="bg-gray-900 dark:bg-gray-950 p-3 sm:p-4">
                        <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="truncate">Información laboral</span>
                        </h3>
                    </div>
                    <div class="p-4 sm:p-5 md:p-6">

                    <div class="space-y-2 sm:space-y-3">
                        <div class="p-2.5 sm:p-3 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border-l-4 border-blue-500 hover:shadow-md transition-shadow duration-200">
                            <p class="text-[10px] sm:text-xs text-gray-600 mb-0.5 sm:mb-1">Empresa</p>
                            <p class="text-xs sm:text-sm font-semibold text-gray-900 break-words">{{ $user->empresa->nombre ?? 'N/A' }}</p>
                        </div>

                        <div class="p-2.5 sm:p-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border-l-4 border-purple-500 hover:shadow-md transition-shadow duration-200">
                            <p class="text-[10px] sm:text-xs text-gray-600 mb-0.5 sm:mb-1">Categoría</p>
                            <p class="text-xs sm:text-sm font-semibold text-gray-900 break-words">{{ $user->categoria->nombre ?? 'N/A' }}</p>
                        </div>

                        @if ($user->rol == 'operario')
                            <div class="p-2.5 sm:p-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border-l-4 border-green-500 hover:shadow-md transition-shadow duration-200">
                                <p class="text-[10px] sm:text-xs text-gray-600 mb-0.5 sm:mb-1">Especialidad</p>
                                <p class="text-xs sm:text-sm font-semibold text-gray-900 break-words">{{ optional($user->maquina)->nombre ?? 'N/A' }}</p>
                            </div>
                        @endif
                    </div>
                    </div>
                </div>

                {{-- Departamentos --}}
                @if ($user->rol == 'oficina' && $user->departamentos->count() > 0)
                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gray-900 dark:bg-gray-950 p-3 sm:p-4">
                            <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="truncate">Departamentos</span>
                            </h3>
                        </div>
                        <div class="p-4 sm:p-5 md:p-6">

                        <div class="flex flex-wrap gap-1.5 sm:gap-2">
                            @foreach($user->departamentos as $dep)
                                <span class="inline-flex items-center px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-full text-[10px] sm:text-xs font-medium bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-md hover:shadow-lg transition-shadow duration-200">
                                    <span class="truncate max-w-[120px] sm:max-w-none">{{ $dep->nombre }}</span>
                                    @if ($dep->pivot && $dep->pivot->rol_departamental)
                                        <span class="ml-1 opacity-75 text-[9px] sm:text-[10px]">({{ $dep->pivot->rol_departamental }})</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Columna derecha: Estadísticas y acciones --}}
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">

                {{-- Cards de estadísticas --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-3 sm:p-4 md:p-6 border-t-4 border-green-500 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start sm:justify-between gap-2">
                            <div class="text-center sm:text-left flex-1">
                                <p class="text-xs sm:text-sm text-gray-600 mb-1">Vacaciones</p>
                                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $resumen['diasVacaciones'] }}</p>
                            </div>
                            <div class="bg-green-100 rounded-full p-2 sm:p-2.5 md:p-3 flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-3 sm:p-4 md:p-6 border-t-4 border-red-500 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start sm:justify-between gap-2">
                            <div class="text-center sm:text-left flex-1">
                                <p class="text-xs sm:text-sm text-gray-600 mb-1">F. Injustificadas</p>
                                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $resumen['faltasInjustificadas'] }}</p>
                            </div>
                            <div class="bg-red-100 rounded-full p-2 sm:p-2.5 md:p-3 flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-3 sm:p-4 md:p-6 border-t-4 border-yellow-500 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start sm:justify-between gap-2">
                            <div class="text-center sm:text-left flex-1">
                                <p class="text-xs sm:text-sm text-gray-600 mb-1">F. Justificadas</p>
                                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $resumen['faltasJustificadas'] }}</p>
                            </div>
                            <div class="bg-yellow-100 rounded-full p-2 sm:p-2.5 md:p-3 flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-3 sm:p-4 md:p-6 border-t-4 border-blue-500 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start sm:justify-between gap-2">
                            <div class="text-center sm:text-left flex-1">
                                <p class="text-xs sm:text-sm text-gray-600 mb-1">Días de baja</p>
                                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $resumen['diasBaja'] }}</p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-2 sm:p-2.5 md:p-3 flex-shrink-0">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card de nóminas --}}
                @if (auth()->check() && auth()->id() === $user->id)
                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-5 md:p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4 sm:mb-6">
                            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg sm:rounded-xl p-2.5 sm:p-3 flex-shrink-0">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Descargar nóminas</h3>
                                <p class="text-xs sm:text-sm text-gray-500">Consulta tus nóminas mensuales</p>
                            </div>
                        </div>

                        <form action="{{ route('nominas.crearDescargarMes') }}" method="GET" x-data="{ cargando: false }"
                            x-on:submit="cargando = true; setTimeout(() => cargando = false, 1000)"
                            x-init="$watch('cargando', value => document.body.style.cursor = value ? 'wait' : 'default')"
                            class="flex flex-col sm:flex-row gap-2 sm:gap-3">

                            <div class="flex-1">
                                <input type="month" name="mes_anio" required
                                    class="w-full rounded-lg sm:rounded-xl border-2 border-gray-200 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700
                                           focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all
                                           hover:border-gray-300">
                            </div>

                            <button type="submit"
                                class="w-full sm:w-auto inline-flex justify-center items-center gap-2 rounded-lg sm:rounded-xl px-4 sm:px-6 py-2.5 sm:py-3 text-sm sm:text-base font-semibold text-white shadow-lg
                                       bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700
                                       focus:outline-none focus:ring-4 focus:ring-green-200 transition-all transform hover:scale-105 active:scale-95
                                       disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                x-bind:disabled="cargando">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!cargando">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24" x-show="cargando" x-cloak>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="!cargando">Descargar</span>
                                <span x-show="cargando" x-cloak>Generando...</span>
                            </button>

                            {{-- Overlay bloqueante --}}
                            <div x-show="cargando" x-transition.opacity class="fixed inset-0 bg-black/0 z-50" style="cursor: wait" x-cloak></div>
                        </form>
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>
