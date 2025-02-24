<x-app-layout>
    <x-slot name="title">Crear Usuario - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Crear Usuario') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg mt-6">
        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-gray-700 font-semibold">Nombre</label>
                <input id="name" type="text" name="name"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    value="{{ old('name') }}" required autofocus autocomplete="name">
                <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label for="email" class="block text-gray-700 font-semibold">Correo Electrónico</label>
                <input id="email" type="email" name="email"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    value="{{ old('email') }}" required autocomplete="name">
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Rol -->
            <div>
                <label for="rol" class="block text-gray-700 font-semibold">Rol</label>
                <select id="rol" name="rol"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    required>
                    <option value="" disabled selected>Selecciona un rol</option>
                    <option value="operario">Operario</option>
                    <option value="oficina">Oficina</option>
                    <option value="visitante">Visitante</option>
                </select>
                <x-input-error :messages="$errors->get('rol')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Categoría -->
            <div>
                <label for="categoria" class="block text-gray-700 font-semibold">Categoría</label>
                <select id="categoria" name="categoria"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    required>
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
                <x-input-error :messages="$errors->get('categoria')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Turno -->
            <div>
                <label for="turno" class="block text-gray-700 font-semibold">Turno</label>
                <select id="turno" name="turno"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    required>
                    <option value="" disabled selected>Selecciona un turno</option>
                    <option value="diurno">Diurno</option>
                    <option value="nocturno">Nocturno</option>
                    <option value="flexible">Flexible</option>
                </select>
                <x-input-error :messages="$errors->get('turno')" class="mt-2 text-red-500 text-sm" />
            </div>
            <!-- Turno Actual -->
            <div>
                <label for="turno_actual" class="block text-gray-700 font-semibold">Turno Actual</label>
                <select id="turno_actual" name="turno_actual"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    required>
                    <option value="" disabled selected>Selecciona un turno si tiene horario diurno</option>
                    <option value="mañana">Mañana</option>
                    <option value="tarde">Tarde</option>
                </select>
                <x-input-error :messages="$errors->get('turno_actual')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-gray-700 font-semibold">Contraseña</label>
                <input id="password" type="password" name="password"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    required autocomplete="new-password">
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Confirmar Contraseña -->
            <div>
                <label for="password_confirmation" class="block text-gray-700 font-semibold">Confirmar Contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 py-3 px-4 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 transition duration-200"
                    required autocomplete="new-password">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-sm" />
            </div>

            <!-- Botón de Registro -->
            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300">
                    Registrar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
