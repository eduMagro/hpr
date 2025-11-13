@props(['user', 'resumen'])

<div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl mx-auto mb-6 border border-gray-200">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">Mi Perfil</h2>

    <div class="flex flex-col md:flex-row gap-4 md:gap-6 items-center border-b pb-4 mb-4">
        {{-- Avatar --}}
        <div class="relative flex-shrink-0 mx-auto md:mx-0">
            @if ($user->ruta_imagen)
                <img src="{{ $user->ruta_imagen }}" alt="Foto de perfil"
                    class="w-24 h-24 rounded-full object-cover ring-4 ring-blue-500 shadow-lg">
            @else
                <div
                    class="w-24 h-24 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center text-3xl font-bold text-gray-700 shadow-inner ring-4 ring-blue-500">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif

            <!-- Botón cambiar foto sobre la imagen -->
            <form method="POST" action="{{ route('usuarios.editarSubirImagen') }}" enctype="multipart/form-data"
                class="absolute bottom-0 right-0">
                @csrf
                <label
                    class="flex items-center justify-center bg-white border border-gray-300 rounded-full p-1 shadow-md cursor-pointer hover:bg-gray-50">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M4 3a2 2 0 00-2 2v3.586A1.5 1.5 0 003.5 10H4v6a2 2 0 002 2h8a2 2 0 002-2v-6h.5A1.5 1.5 0 0018 8.586V5a2 2 0 00-2-2H4zm3 3a1 1 0 112 0 1 1 0 01-2 0zm2 4a2 2 0 114 0 2 2 0 01-4 0z" />
                    </svg>
                    <input type="file" name="imagen" accept="image/*" class="hidden" onchange="this.form.submit()">
                </label>
            </form>
        </div>

        {{-- Datos principales --}}
        <div class="text-center md:text-left max-w-full overflow-hidden">
            <p class="text-lg font-semibold break-words">{{ $user->nombre_completo }}</p>

            @if ($user->rol == 'oficina')
                {{-- Departamentos --}}
                <div class="mt-2 flex flex-wrap justify-center md:justify-start gap-2">
                    @forelse($user->departamentos as $dep)
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 break-words">
                            {{ $dep->nombre }}
                            @if ($dep->pivot && $dep->pivot->rol_departamental)
                                <span
                                    class="ml-1 text-gray-500 text-[10px]">({{ $dep->pivot->rol_departamental }})</span>
                            @endif
                        </span>
                    @empty
                        <span class="text-sm text-gray-500 italic">Sin departamentos asignados</span>
                    @endforelse
                </div>
            @endif
            {{-- Contactos --}}
            <p class="mt-2 break-all text-sm md:text-base">📧 {{ $user->email }}</p>
            @if ($user->movil_empresa)
                <p class="break-all text-sm md:text-base">📞 <span class="font-semibold">Empresa:</span>
                    {{ $user->movil_empresa }}</p>
            @endif
            @if ($user->movil_personal)
                <p class="break-all text-sm md:text-base">📱 <span class="font-semibold">Personal:</span>
                    {{ $user->movil_personal }}</p>
            @endif
            @if (!$user->movil_empresa && !$user->movil_personal)
                <p class="italic text-gray-500 text-sm">Sin teléfonos registrados</p>
            @endif
        </div>
    </div>


    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <p><strong>Empresa:</strong> {{ $user->empresa->nombre ?? 'N/A' }}</p>
        <p><strong>Categoría:</strong> {{ $user->categoria->nombre ?? 'N/A' }}</p>
        @if ($user->rol == 'operario')
            <p><strong>Especialidad: </strong>{{ optional($user->maquina)->nombre ?? 'N/A' }}</p>
        @endif
    </div>

    <div class="bg-gray-100 p-3 rounded-lg mb-4">
        <p><strong>Vacaciones asignadas:</strong> {{ $resumen['diasVacaciones'] }}</p>
        <p><strong>Faltas injustificadas:</strong> {{ $resumen['faltasInjustificadas'] }}</p>
        <p><strong>Faltas justificadas:</strong> {{ $resumen['faltasJustificadas'] }}</p>
        <p><strong>Días de baja:</strong> {{ $resumen['diasBaja'] }}</p>
    </div>


    {{-- Solicitar nóminas por email --}}
    @if (auth()->check() && auth()->id() === $user->id)
        <div class="mt-6 border-t pt-6">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-5 border border-blue-200">
                <div class="flex items-start gap-3 mb-4">
                    <div class="flex-shrink-0 mt-1">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Solicitar Nómina</h3>
                        <p class="text-sm text-gray-600">
                            Selecciona el mes y recibirás tu nómina en el correo:
                            <span class="font-semibold text-blue-700">{{ $user->email }}</span>
                        </p>
                    </div>
                </div>

                @if ($errors->has('mes_anio'))
                    <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ $errors->first('mes_anio') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('nominas.crearDescargarMes') }}" method="POST" x-data="{ cargando: false }"
                    x-on:submit="cargando = true; setTimeout(() => cargando = false, 3000)"
                    x-init="$watch('cargando', value => document.body.style.cursor = value ? 'wait' : 'default')"
                    class="flex flex-col sm:flex-row sm:items-end gap-3 relative">
                    @csrf

                    <div class="flex-1">
                        <label for="mes_anio" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Mes y Año <span class="text-red-500">*</span>
                        </label>
                        <input type="month" id="mes_anio" name="mes_anio" required
                            value="{{ old('mes_anio') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300 transition px-4 py-2.5 text-gray-700 @error('mes_anio') border-red-500 @enderror"
                            x-bind:class="{ 'opacity-50 cursor-not-allowed': cargando }"
                            x-bind:readonly="cargando">
                        @error('mes_anio')
                            <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="sm:w-auto w-full inline-flex justify-center items-center gap-2.5 rounded-lg px-6 py-2.5 font-semibold text-white shadow-md
                               bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                               disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105 active:scale-95"
                        x-bind:disabled="cargando">
                        <svg x-show="!cargando" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        <svg x-show="cargando" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-show="!cargando" class="font-bold">Enviar a mi correo</span>
                        <span x-show="cargando" class="font-bold">Enviando...</span>
                    </button>

                    {{-- Overlay bloqueante --}}
                    <div x-show="cargando" x-transition.opacity class="fixed inset-0 bg-black/0 z-50"
                        style="cursor: wait" x-cloak>
                    </div>
                </form>

                <div class="mt-3 flex items-start gap-2 text-xs text-gray-500">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <p>
                        Por seguridad, tu nómina se enviará únicamente al correo electrónico registrado en el sistema.
                        Este documento es confidencial y de uso personal.
                    </p>
                </div>
            </div>
        </div>
    @endif


</div>
