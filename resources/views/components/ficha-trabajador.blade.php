@props(['user', 'resumen', 'sesiones' => collect([])])

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<div x-data="{
    mostrarDetalles: false,
    seccionContacto: false,
    seccionLaboral: false,
    seccionDepartamentos: false,
    seccionNomina: false,
    seccionContrato: false,
    seccionJustificante: false,
    seccionSesiones: false,
    seccionTallas: false,
    faltanTallas: {{ !($user->tallas && $user->tallas->talla_guante && $user->tallas->talla_zapato && $user->tallas->talla_pantalon && $user->tallas->talla_chaqueta) ? 'true' : 'false' }},
}" @tallas-updated.window="faltanTallas = $event.detail.faltanTallas" @justificante-guardado-success.window="
    seccionJustificante = false;
    Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: $event.detail[0].mensaje + ' para el ' + $event.detail[0].fecha,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
">
    <div class="max-w-7xl mx-auto">

        {{-- Header con banner degradado --}}
        <div class="bg-gray-900 dark:bg-gray-950 rounded sm:rounded-3xl shadow-2xl mb-8 overflow-visible relative">
            <div
                class="relative bg-gradient-to-br from-gray-800/50 to-gray-900/50 backdrop-blur-sm rounded-2xl sm:rounded-3xl">
                <div class="absolute inset-0 bg-black/10 rounded-2xl sm:rounded-3xl"></div>
                <div class="relative p-4 sm:p-6">
                    <div
                        class="flex flex-col items-center text-center gap-4 sm:flex-row sm:items-center sm:text-left sm:gap-6">
                        {{-- Avatar --}}
                        <div class="relative z-10 flex-shrink-0">
                            @if ($user->ruta_imagen)
                                <div
                                    class="w-20 h-20 sm:w-24 h-24 rounded-2xl ring-4 ring-gray-700 shadow-2xl overflow-hidden bg-white">
                                    <img src="{{ $user->ruta_imagen }}" alt="Foto de perfil"
                                        class="w-full h-full object-cover">
                                </div>
                            @else
                                <div
                                    class="w-20 h-20 sm:w-24 h-24 bg-gradient-to-br from-gray-700 to-gray-800 rounded-2xl flex items-center justify-center text-3xl sm:text-4xl font-bold text-white shadow-2xl ring-4 ring-gray-700">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif

                            {{-- Botón cambiar foto --}}
                            <form method="POST" action="{{ route('usuarios.editarSubirImagen') }}"
                                enctype="multipart/form-data" class="absolute -bottom-1 -right-1 z-20">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <label
                                    class="flex items-center justify-center bg-white rounded-full p-1.5 shadow-lg cursor-pointer hover:bg-gray-100 transition-all hover:scale-110 border-2 border-gray-700 active:scale-95">
                                    <svg class="w-3.5 h-3.5 text-gray-900" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <input type="file" name="imagen" accept="image/*" class="hidden"
                                        onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>

                        {{-- Nombre y categoría --}}
                        <div class="flex-1 w-full sm:w-auto">
                            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-white drop-shadow-lg break-words">
                                {{ $user->nombre_completo }}
                            </h1>
                            <p class="text-xs sm:text-sm text-gray-300 mt-1">{{ $user->categoria->nombre ?? 'N/A' }}</p>
                        </div>

                        {{-- Estadísticas en el header --}}
                        <div class="flex gap-3 sm:gap-4 max-sm:w-full">
                            <div class="text-center px-3 py-1.5 bg-white/10 rounded-lg max-sm:w-full sm:min-w-[6rem]">
                                <p class="text-lg sm:text-xl font-bold text-green-400">
                                    {{ $resumen['diasVacaciones'] }}
                                </p>
                                <p class="text-[10px] text-gray-300">Vacaciones</p>
                            </div>
                            <div class="text-center px-3 py-1.5 bg-white/10 rounded-lg max-sm:w-full sm:min-w-[6rem]">
                                <p class="text-lg sm:text-xl font-bold text-red-400">
                                    {{ $resumen['faltasInjustificadas'] }}
                                </p>
                                <p class="text-[10px] text-gray-300">Injustif.</p>
                            </div>
                            <div class="text-center px-3 py-1.5 bg-white/10 rounded-lg max-sm:w-full sm:min-w-[6rem]">
                                <p class="text-lg sm:text-xl font-bold text-yellow-400">
                                    {{ $resumen['faltasJustificadas'] }}
                                </p>
                                <p class="text-[10px] text-gray-300">Justif.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botón toggle para mostrar/ocultar detalles --}}
            <div class="relative">
                <button @click="mostrarDetalles = !mostrarDetalles"
                    class="absolute left-1/2 -translate-x-1/2 -bottom-3 z-10 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-full shadow-md hover:shadow-lg transition-all duration-300 px-3 py-1 flex items-center gap-1 border border-gray-200 dark:border-gray-600">
                    <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300"
                        x-text="mostrarDetalles ? 'Ocultar' : 'Ver más'"></span>
                    <svg class="w-2.5 h-2.5 text-gray-500 dark:text-gray-400 transition-transform duration-300"
                        :class="{ 'rotate-180': mostrarDetalles }" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>

                    <svg x-show="faltanTallas" x-cloak xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round"
                        class="lucide lucide-badge-alert-icon lucide-badge-alert text-orange-400 w-5 h-5 absolute md:-top-7 -bottom-6 left-1/2 -translate-x-1/2">
                        <path
                            d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>

                </button>
            </div>
        </div>

        {{-- Contenido desplegable --}}
        <div x-cloak x-show="mostrarDetalles" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4" class="space-y-2 mt-6">

            {{-- Información de contacto --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button @click="seccionContacto = !seccionContacto"
                    class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center gap-2">
                        <div class="bg-blue-100 dark:bg-blue-900/50 rounded-lg p-1.5">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Información de contacto</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': seccionContacto }" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                        </path>
                    </svg>
                </button>
                <div x-cloak x-show="seccionContacto" x-collapse>
                    <div class="px-3 pb-3 space-y-2">
                        <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Email</p>
                                <p class="text-xs text-gray-900 dark:text-gray-100">{{ $user->email }}</p>
                            </div>
                        </div>
                        @if ($user->movil_empresa)
                            <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Teléfono empresa</p>
                                    <p class="text-xs text-gray-900 dark:text-gray-100">{{ $user->movil_empresa }}</p>
                                </div>
                            </div>
                        @endif
                        @if ($user->movil_personal)
                            <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Teléfono personal</p>
                                    <p class="text-xs text-gray-900 dark:text-gray-100">{{ $user->movil_personal }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Información laboral --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button @click="seccionLaboral = !seccionLaboral"
                    class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center gap-2">
                        <div class="bg-purple-100 dark:bg-purple-900/50 rounded-lg p-1.5">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Información laboral</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': seccionLaboral }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                        </path>
                    </svg>
                </button>
                <div x-cloak x-show="seccionLaboral" x-collapse>
                    <div class="px-3 pb-3 space-y-2">
                        <div
                            class="p-2 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-lg border-l-3 border-blue-500">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Empresa</p>
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $user->empresa->nombre ?? 'N/A' }}</p>
                        </div>
                        <div
                            class="p-2 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-lg border-l-3 border-purple-500">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Categoría</p>
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $user->categoria->nombre ?? 'N/A' }}</p>
                        </div>
                        @if ($user->rol == 'operario')
                            <div
                                class="p-2 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-lg border-l-3 border-green-500">
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Especialidad</p>
                                <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">
                                    {{ optional($user->maquina)->nombre ?? 'N/A' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sección de Tallas (Mis Tallas de EPIs) --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                x-data="tallasManager({{ $user->id }}, {{ $user->tallas ? $user->tallas->toJson() : '{}' }})">
                <button @click="seccionTallas = !seccionTallas"
                    class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center gap-2">
                        <div class="bg-pink-100 dark:bg-pink-900/50 rounded-lg p-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-pink-600 dark:text-pink-400" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Mis tallas de EPIs</span>

                        <svg x-show="faltanTallas" x-cloak xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-badge-alert-icon lucide-badge-alert text-orange-400 w-5 h-5">
                            <path
                                d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" />
                            <line x1="12" x2="12" y1="8" y2="12" />
                            <line x1="12" x2="12.01" y1="16" y2="16" />
                        </svg>

                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': seccionTallas }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                        </path>
                    </svg>
                </button>
                <div x-cloak x-show="seccionTallas" x-collapse>
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Guante</label>
                                <input type="text" x-model="tallas.talla_guante"
                                    class="w-full px-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    placeholder="Ej: 9, L...">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Zapato</label>
                                <input type="text" x-model="tallas.talla_zapato"
                                    class="w-full px-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    placeholder="Ej: 42, 43...">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Pantalón</label>
                                <input type="text" x-model="tallas.talla_pantalon"
                                    class="w-full px-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    placeholder="Ej: 44, XL...">
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Chaqueta</label>
                                <input type="text" x-model="tallas.talla_chaqueta"
                                    class="w-full px-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    placeholder="Ej: 52, L...">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button"
                                class="px-4 py-2 rounded-lg bg-gray-900 dark:bg-gray-700 text-white text-xs font-semibold hover:bg-gray-800 dark:hover:bg-gray-600 transition-all shadow-sm hover:shadow active:scale-95 flex items-center gap-2"
                                @click="saveTallas()" :disabled="saving">
                                <svg x-show="saving" class="animate-spin -ml-1 h-3 w-3 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span x-text="saving ? 'Guardando...' : 'Guardar Cambios'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Departamentos --}}
            @if ($user->rol == 'oficina' && $user->departamentos->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button @click="seccionDepartamentos = !seccionDepartamentos"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-indigo-100 dark:bg-indigo-900/50 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Departamentos</span>
                            <span
                                class="text-xs bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded-full">{{ $user->departamentos->count() }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionDepartamentos }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionDepartamentos" x-collapse>
                        <div class="px-3 pb-3 flex flex-wrap gap-1.5">
                            @foreach ($user->departamentos as $dep)
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-gradient-to-r from-blue-500 to-indigo-500 text-white">
                                    {{ $dep->nombre }}
                                    @if ($dep->pivot && $dep->pivot->rol_departamental)
                                        <span class="ml-1 opacity-75">({{ $dep->pivot->rol_departamental }})</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif


            {{-- Solicitar nómina --}}
            @if (auth()->check() && auth()->id() === $user->id)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button @click="seccionNomina = !seccionNomina"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-green-100 dark:bg-green-900/50 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Solicitar Nómina</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionNomina }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionNomina" x-collapse>
                        <div class="px-3 pb-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                                Selecciona el mes y recibirás tu nómina en: <span
                                    class="font-semibold text-blue-700 dark:text-blue-400">{{ $user->email }}</span>
                            </p>

                            @if ($errors->has('mes_anio'))
                                <div class="mb-3 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-2 rounded-r-lg">
                                    <p class="text-xs text-red-800 dark:text-red-300">{{ $errors->first('mes_anio') }}</p>
                                </div>
                            @endif

                            <form action="{{ route('nominas.crearDescargarMes') }}" method="POST"
                                x-data="{ cargando: false }" @submit="cargando = true" class="flex gap-2">
                                @csrf
                                <input type="month" name="mes_anio" required value="{{ old('mes_anio') }}"
                                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-300 text-xs py-2"
                                    :class="{ 'opacity-50': cargando }">
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors disabled:opacity-50"
                                    :disabled="cargando">
                                    <span x-show="!cargando">Enviar</span>
                                    <span x-show="cargando">...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            @endif

            {{-- Contrato --}}
            @if (auth()->check() && (auth()->user()->rol === 'oficina' || auth()->id() === $user->id))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button @click="seccionContrato = !seccionContrato"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-indigo-100 dark:bg-indigo-900/50 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Contrato</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionContrato }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionContrato" x-collapse>
                        <div class="px-3 pb-3 space-y-3">
                            @if ($user->fecha_incorporacion_efectiva)
                                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Fecha de incorporación: <strong
                                            class="text-gray-900 dark:text-white">{{ $user->fecha_incorporacion_efectiva->format('d/m/Y') }}</strong></span>
                                </div>
                            @endif
                            <a href="{{ route('incorporaciones.descargarMiContrato') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descargar Contrato
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Justificantes - visible para operarios y oficina --}}
            @if (in_array($user->rol, ['operario', 'oficina']))
                @php
                    $esOficinaViendoOtro = Auth::user()->rol === 'oficina' && Auth::id() !== $user->id;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button @click="seccionJustificante = !seccionJustificante"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-orange-100 dark:bg-orange-900/50 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $esOficinaViendoOtro ? 'Justificantes' : 'Subir Justificante' }}
                            </span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionJustificante }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionJustificante" x-collapse>
                        <div class="p-3 pt-0">
                            @livewire('subir-justificante', ['userId' => $user->id])
                        </div>
                    </div>
                </div>
            @endif

            {{-- Sesiones activas - solo visible en mi propio perfil --}}
            @if (auth()->check() && auth()->id() === $user->id && $sesiones->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button @click="seccionSesiones = !seccionSesiones"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-red-100 dark:bg-red-900/50 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Sesiones activas</span>
                            <span
                                class="text-xs bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 px-1.5 py-0.5 rounded-full">{{ $sesiones->count() }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionSesiones }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionSesiones" x-collapse>
                        <div class="px-3 pb-3 space-y-2">
                            @foreach ($sesiones as $sesion)
                                <div
                                    class="p-3 rounded-lg {{ $sesion['actual'] ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-700 border border-gray-100 dark:border-gray-600' }}">
                                    <div class="flex items-center gap-3">
                                        {{-- Icono del dispositivo --}}
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-full {{ $sesion['actual'] ? 'bg-green-100 dark:bg-green-800' : 'bg-gray-200 dark:bg-gray-600' }} flex items-center justify-center">
                                            @if (($sesion['dispositivo']['icono'] ?? 'desktop') === 'mobile')
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            @elseif (($sesion['dispositivo']['icono'] ?? 'desktop') === 'tablet')
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            @elseif (($sesion['dispositivo']['icono'] ?? 'desktop') === 'bot')
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                        {{-- Info del dispositivo --}}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                    {{ $sesion['dispositivo']['navegador'] ?? 'Navegador' }}
                                                </p>
                                                @if ($sesion['actual'])
                                                    <span
                                                        class="text-[9px] bg-green-500 text-white px-1.5 py-0.5 rounded-full font-medium">ACTUAL</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ $sesion['dispositivo']['sistema'] ?? 'Sistema' }}
                                                @if (!empty($sesion['dispositivo']['dispositivo']))
                                                    <span class="text-gray-400 dark:text-gray-500">·</span>
                                                    {{ $sesion['dispositivo']['dispositivo'] }}
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    class="text-[10px] text-gray-400 dark:text-gray-500">{{ $sesion['ip_address'] ?? 'IP desconocida' }}</span>
                                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                                <span
                                                    class="text-[10px] {{ $sesion['actual'] ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400 dark:text-gray-500' }}">
                                                    {{ $sesion['actual'] ? 'Activa ahora' : $sesion['tiempo_relativo'] ?? $sesion['ultima_actividad'] }}
                                                </span>
                                            </div>
                                        </div>
                                        {{-- Botón cerrar sesión individual --}}
                                        @if (!$sesion['actual'])
                                            <form method="POST" action="{{ route('perfil.cerrarSesion', $sesion['id']) }}"
                                                onsubmit="return confirm('¿Cerrar esta sesión?')" class="flex-shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors"
                                                    title="Cerrar esta sesión">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if ($sesiones->where('actual', false)->count() > 0)
                                <form method="POST" action="{{ route('perfil.cerrarMisSesiones') }}"
                                    onsubmit="return confirm('¿Cerrar todas las sesiones en otros dispositivos?')" class="pt-2">
                                    @csrf
                                    <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-xs transition-colors">
                                        Cerrar sesiones en otros dispositivos
                                    </button>
                                </form>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-2">Solo tienes esta sesión activa.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

<script>
    function tallasManager(userId, initialTallas) {
        return {
            userId: userId,
            tallas: {
                talla_guante: initialTallas?.talla_guante || '',
                talla_zapato: initialTallas?.talla_zapato || '',
                talla_pantalon: initialTallas?.talla_pantalon || '',
                talla_chaqueta: initialTallas?.talla_chaqueta || ''
            },
            saving: false,
            async saveTallas() {
                this.saving = true;
                try {
                    const response = await fetch(`/epis/usuarios/${this.userId}/tallas`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify(this.tallas)
                    });

                    const data = await response.json();

                    if (response.ok && data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Guardado',
                            text: 'Tallas actualizadas correctamente',
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });

                        // Check if all tallas are filled
                        const faltan = !(this.tallas.talla_guante && this.tallas.talla_zapato && this.tallas
                            .talla_pantalon && this.tallas.talla_chaqueta);

                        // Dispatch event to update parent component
                        window.dispatchEvent(new CustomEvent('tallas-updated', {
                            detail: {
                                faltanTallas: faltan
                            }
                        }));

                    } else {
                        throw new Error(data.message || 'Error al guardar');
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron guardar las tallas: ' + error.message
                    });
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>