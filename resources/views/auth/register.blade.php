<x-app-layout>
    <x-slot name="title">Crear Usuario - {{ config('app.name') }}</x-slot>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Crear Nuevo Usuario</h1>
                <p class="text-gray-600">Complete el formulario con los datos del usuario</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h2 class="text-xl font-semibold text-white">Información del Usuario</h2>
                </div>

                <div class="p-8 sm:p-10">
                    <form method="POST" action="{{ route('register') }}" x-data="{ cargando: false }">
                        @csrf

                        {{-- INPUT CLASSES --}}
                        @php
                            $inputClass = 'w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-800
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                          transition duration-200 ease-in-out hover:border-gray-400 bg-gray-50 focus:bg-white';
                            $selectClass = 'w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-800
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                           transition duration-200 ease-in-out hover:border-gray-400 bg-gray-50 focus:bg-white cursor-pointer';
                            $labelClass = 'block text-sm font-semibold text-gray-700 mb-2';
                            $errorClass = 'mt-1.5 text-xs text-red-600';
                        @endphp

                        {{-- SECCIÓN: DATOS PERSONALES --}}
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500 inline-block">
                                Datos Personales
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                {{-- NOMBRE --}}
                                <div class="group">
                                    <label for="name" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Nombre <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input id="name" name="name" type="text" class="{{ $inputClass }}"
                                        value="{{ old('name') }}" required autofocus>
                                    <x-input-error :messages="$errors->get('name')" class="{{ $errorClass }}" />
                                </div>

                                {{-- PRIMER APELLIDO --}}
                                <div class="group">
                                    <label for="primer_apellido" class="{{ $labelClass }}">
                                        Primer Apellido <span class="text-red-500">*</span>
                                    </label>
                                    <input id="primer_apellido" name="primer_apellido" type="text" class="{{ $inputClass }}"
                                        value="{{ old('primer_apellido') }}" required>
                                    <x-input-error :messages="$errors->get('primer_apellido')" class="{{ $errorClass }}" />
                                </div>

                                {{-- SEGUNDO APELLIDO --}}
                                <div class="group">
                                    <label for="segundo_apellido" class="{{ $labelClass }}">Segundo Apellido</label>
                                    <input id="segundo_apellido" name="segundo_apellido" type="text" class="{{ $inputClass }}"
                                        value="{{ old('segundo_apellido') }}">
                                    <x-input-error :messages="$errors->get('segundo_apellido')" class="{{ $errorClass }}" />
                                </div>

                                {{-- DNI --}}
                                <div class="group">
                                    <label for="dni" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                            DNI <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input id="dni" name="dni" type="text" class="{{ $inputClass }}"
                                        value="{{ old('dni') }}" required>
                                    <x-input-error :messages="$errors->get('dni')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN: CONTACTO --}}
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-green-500 inline-block">
                                Información de Contacto
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                {{-- EMAIL --}}
                                <div class="group">
                                    <label for="email" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Correo Electrónico <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input id="email" name="email" type="email" class="{{ $inputClass }}"
                                        value="{{ old('email') }}" required>
                                    <x-input-error :messages="$errors->get('email')" class="{{ $errorClass }}" />
                                </div>

                                {{-- MÓVIL PERSONAL --}}
                                <div class="group">
                                    <label for="movil_personal" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            Móvil Personal
                                        </span>
                                    </label>
                                    <input id="movil_personal" name="movil_personal" type="text" class="{{ $inputClass }}"
                                        value="{{ old('movil_personal') }}">
                                    <x-input-error :messages="$errors->get('movil_personal')" class="{{ $errorClass }}" />
                                </div>

                                {{-- MÓVIL EMPRESA --}}
                                <div class="group">
                                    <label for="movil_empresa" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            Móvil de Empresa
                                        </span>
                                    </label>
                                    <input id="movil_empresa" name="movil_empresa" type="text" class="{{ $inputClass }}"
                                        value="{{ old('movil_empresa') }}">
                                    <x-input-error :messages="$errors->get('movil_empresa')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN: INFORMACIÓN LABORAL --}}
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-purple-500 inline-block">
                                Información Laboral
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                                {{-- EMPRESA --}}
                                <div class="group">
                                    <label for="empresa_id" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            Empresa <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select id="empresa_id" name="empresa_id" class="{{ $selectClass }}" required>
                                        <option value="" disabled selected>Selecciona una empresa</option>
                                        @foreach ($empresas as $empresa)
                                            <option value="{{ $empresa->id }}"
                                                {{ old('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                                {{ ucfirst($empresa->nombre) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('empresa_id')" class="{{ $errorClass }}" />
                                </div>

                                {{-- ROL --}}
                                <div class="group">
                                    <label for="rol" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Rol <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select id="rol" name="rol" class="{{ $selectClass }}" required>
                                        <option value="" disabled selected>Selecciona un rol</option>
                                        <option value="operario">Operario</option>
                                        <option value="oficina">Oficina</option>
                                        <option value="transportista">Transportista</option>
                                        <option value="visitante">Visitante</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('rol')" class="{{ $errorClass }}" />
                                </div>

                                {{-- CATEGORÍA --}}
                                <div class="group">
                                    <label for="categoria_id" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                            Categoría <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <select id="categoria_id" name="categoria_id" class="{{ $selectClass }}" required>
                                        <option value="" disabled selected>Selecciona una categoría</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}"
                                                {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                                {{ ucfirst($categoria->nombre) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('categoria_id')" class="{{ $errorClass }}" />
                                </div>

                                {{-- TURNO --}}
                                <div class="group">
                                    <label for="turno" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Turno
                                        </span>
                                    </label>
                                    <select id="turno" name="turno" class="{{ $selectClass }}">
                                        <option value="" disabled selected>Selecciona un turno</option>
                                        <option value="nocturno">Nocturno</option>
                                        <option value="diurno">Diurno</option>
                                        <option value="mañana">Mañana</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('turno')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN: SEGURIDAD --}}
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-red-500 inline-block">
                                Seguridad
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                {{-- CONTRASEÑA --}}
                                <div class="group">
                                    <label for="password" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Contraseña <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input id="password" name="password" type="password" class="{{ $inputClass }}" required>
                                    <x-input-error :messages="$errors->get('password')" class="{{ $errorClass }}" />
                                </div>

                                {{-- CONFIRMAR CONTRASEÑA --}}
                                <div class="group">
                                    <label for="password_confirmation" class="{{ $labelClass }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Confirmar Contraseña <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                    <input id="password_confirmation" name="password_confirmation" type="password"
                                        class="{{ $inputClass }}" required>
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="{{ $errorClass }}" />
                                </div>
                            </div>
                        </div>

                        {{-- BOTÓN DE ENVÍO --}}
                        <div class="flex justify-center pt-6 border-t border-gray-200">
                            <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold
                                       rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5
                                       transition-all duration-200 ease-in-out focus:outline-none focus:ring-2
                                       focus:ring-blue-500 focus:ring-offset-2"
                                x-bind:disabled="cargando">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span x-show="!cargando">Registrar Usuario</span>
                                    <span x-show="cargando" class="flex items-center gap-2">
                                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Registrando...
                                    </span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
