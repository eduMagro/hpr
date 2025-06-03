<x-app-layout>
    <x-slot name="title">Crear Usuario - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('users.index') }}" class="text-blue-600">
                {{ __('Usuarios') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Crear Usuario') }}
        </h2>
    </x-slot>

    <div class="max-w-6xl mx-auto mt-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-10 border border-gray-200">
            <form method="POST" action="{{ route('register') }}" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @csrf

                {{-- INPUT --}}
                @php
                    $inputClass =
                        'block w-full px-4 py-3 mt-1 rounded-xl border border-gray-300 bg-gray-50 text-gray-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all';
                    $labelClass = 'text-sm font-medium text-gray-700';
                    $errorClass = 'mt-2 text-xs text-red-500';
                @endphp

                {{-- NOMBRE --}}
                <div>
                    <label for="name" class="{{ $labelClass }}">Nombre</label>
                    <input id="name" name="name" type="text" class="{{ $inputClass }}"
                        value="{{ old('name') }}" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="{{ $errorClass }}" />
                </div>

                {{-- DNI --}}
                <div>
                    <label for="dni" class="{{ $labelClass }}">DNI</label>
                    <input id="dni" name="dni" type="text" class="{{ $inputClass }}"
                        value="{{ old('dni') }}" required>
                    <x-input-error :messages="$errors->get('dni')" class="{{ $errorClass }}" />
                </div>

                {{-- EMAIL --}}
                <div>
                    <label for="email" class="{{ $labelClass }}">Correo Electrónico</label>
                    <input id="email" name="email" type="email" class="{{ $inputClass }}"
                        value="{{ old('email') }}" required>
                    <x-input-error :messages="$errors->get('email')" class="{{ $errorClass }}" />
                </div>

                {{-- MÓVIL PERSONAL --}}
                <div>
                    <label for="movil_personal" class="{{ $labelClass }}">Móvil Personal</label>
                    <input id="movil_personal" name="movil_personal" type="text" class="{{ $inputClass }}"
                        value="{{ old('movil_personal') }}">
                    <x-input-error :messages="$errors->get('movil_personal')" class="{{ $errorClass }}" />
                </div>

                {{-- MÓVIL EMPRESA --}}
                <div>
                    <label for="movil_empresa" class="{{ $labelClass }}">Móvil de Empresa</label>
                    <input id="movil_empresa" name="movil_empresa" type="text" class="{{ $inputClass }}"
                        value="{{ old('movil_empresa') }}">
                    <x-input-error :messages="$errors->get('movil_empresa')" class="{{ $errorClass }}" />
                </div>

                {{-- ROL --}}
                <div>
                    <label for="rol" class="{{ $labelClass }}">Rol</label>
                    <select id="rol" name="rol" class="{{ $inputClass }}" required>
                        <option value="" disabled selected>Selecciona un rol</option>
                        <option value="operario">Operario</option>
                        <option value="oficina">Oficina</option>
                        <option value="visitante">Visitante</option>
                    </select>
                    <x-input-error :messages="$errors->get('rol')" class="{{ $errorClass }}" />
                </div>

                {{-- CATEGORÍA --}}
                <div>
                    <label for="categoria" class="{{ $labelClass }}">Categoría</label>
                    <select id="categoria" name="categoria" class="{{ $inputClass }}">
                        <option value="" disabled selected>Selecciona una categoría</option>
                        <option value="administracion">Administración</option>
                        <option value="gruista">Gruista</option>
                        <option value="oficial 1">Oficial 1ª</option>
                        <option value="oficial 2">Oficial 2ª</option>
                        <option value="oficial 3">Oficial 3ª</option>
                        <option value="operario">Operario</option>
                        <option value="mecanico">Mecánico</option>
                        <option value="visitante">Visitante</option>
                    </select>
                    <x-input-error :messages="$errors->get('categoria')" class="{{ $errorClass }}" />
                </div>

                {{-- TURNO --}}
                <div>
                    <label for="turno" class="{{ $labelClass }}">Turno</label>
                    <select id="turno" name="turno" class="{{ $inputClass }}">
                        <option value="" disabled selected>Selecciona un turno</option>
                        <option value="nocturno">Nocturno</option>
                        <option value="diurno">Diurno</option>
                        <option value="mañana">Mañana</option>
                    </select>
                    <x-input-error :messages="$errors->get('turno')" class="{{ $errorClass }}" />
                </div>

                {{-- CONTRASEÑA --}}
                <div>
                    <label for="password" class="{{ $labelClass }}">Contraseña</label>
                    <input id="password" name="password" type="password" class="{{ $inputClass }}" required>
                    <x-input-error :messages="$errors->get('password')" class="{{ $errorClass }}" />
                </div>

                {{-- CONFIRMAR CONTRASEÑA --}}
                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">Confirmar Contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="{{ $inputClass }}" required>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="{{ $errorClass }}" />
                </div>

                {{-- BOTÓN --}}
                <div class="md:col-span-3 flex justify-end mt-2">
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>



</x-app-layout>
