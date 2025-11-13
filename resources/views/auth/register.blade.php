<x-app-layout>
    <x-slot name="title">Crear Usuario - {{ config('app.name') }}</x-slot>
    <div class="max-w-6xl mx-auto mt-8 mb-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-10 border border-gray-200">
            <form method="POST" action="{{ route('register') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4"
                x-data="{ cargando: false }">
                @csrf

                {{-- INPUT --}}
                @php
                    $inputClass =
                        'w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500';
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
                {{-- PRIMER APELLIDO --}}
                <div>
                    <label for="primer_apellido" class="{{ $labelClass }}">Primer Apellido</label>
                    <input id="primer_apellido" name="primer_apellido" type="text" class="{{ $inputClass }}"
                        value="{{ old('primer_apellido') }}" required>
                    <x-input-error :messages="$errors->get('primer_apellido')" class="{{ $errorClass }}" />
                </div>

                {{-- SEGUNDO APELLIDO --}}
                <div>
                    <label for="segundo_apellido" class="{{ $labelClass }}">Segundo Apellido</label>
                    <input id="segundo_apellido" name="segundo_apellido" type="text" class="{{ $inputClass }}"
                        value="{{ old('segundo_apellido') }}">
                    <x-input-error :messages="$errors->get('segundo_apellido')" class="{{ $errorClass }}" />
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

                {{-- EMPRESA --}}
                <div>
                    <label for="empresa_id" class="{{ $labelClass }}">Empresa</label>
                    <select id="empresa_id" name="empresa_id" class="{{ $inputClass }}" required>
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
                <div>
                    <label for="rol" class="{{ $labelClass }}">Rol</label>
                    <select id="rol" name="rol" class="{{ $inputClass }}" required>
                        <option value="" disabled selected>Selecciona un rol</option>
                        <option value="operario">Operario</option>
                        <option value="oficina">Oficina</option>
                        <option value="visitante">Transportista</option>
                        <option value="visitante">Visitante</option>
                    </select>
                    <x-input-error :messages="$errors->get('rol')" class="{{ $errorClass }}" />
                </div>

                {{-- CATEGORÍA --}}
                <div>
                    <label for="categoria_id" class="{{ $labelClass }}">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="{{ $inputClass }}" required>
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
                <div class="md:col-span-3 flex justify-center mt-2">
                    <x-boton-submit texto="Registrar usuario" color="blue" />
                </div>
            </form>
        </div>
    </div>



</x-app-layout>
