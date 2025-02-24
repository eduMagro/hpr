<x-app-layout>
    <x-slot name="title">Crear Usuario - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Usuario') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-lg mt-6">
            <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="'Nombre'" />
            <x-text-input id="name"
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="'Correo Electrónico'" />
            <x-text-input id="email"
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
        </div>

        <!-- Rol -->
        <div class="mt-4">
            <x-input-label for="rol" :value="'Rol'" />
            <select id="rol" name="rol"
                class="block mt-3 mb-3 p-3 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                required>
                <option value="" disabled selected>Selecciona un rol</option>
                <option value="operario">Operario</option>
                <option value="oficina">Oficina</option>
                <option value="visitante">Visitante</option>
            </select>
            <x-input-error :messages="$errors->get('rol')" class="mt-2 text-red-500" />
        </div>

        <!-- Categoría -->
        <div class="mt-4">
            <x-input-label for="categoria" :value="'Categoría'" />
            <select id="categoria" name="categoria"
                class="block mt-3 mb-3 p-3 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                required>
                <option value="" disabled selected>Selecciona una categoría</option>
                <option value="administracion">Administración</option>
                <option value="gruista">Gruista</option>
                <option value="operario">Operario</option>
                <option value="mecanico">Mecánico</option>
                <option value="visitante">Visitante</option>
            </select>
            <x-input-error :messages="$errors->get('categoria')" class="mt-2 text-red-500" />
        </div>

        <!-- Turno -->
        <div class="mt-4">
            <x-input-label for="turno" :value="'Turno'" />
            <select id="turno" name="turno"
                class="block mt-3 mb-3 p-3 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                required>
                <option value="" disabled selected>Selecciona un turno</option>
                <option value="diurno">Diurno</option>
                <option value="nocturno">Nocturno</option>
                <option value="flexible">Flexible</option>
            </select>
            <x-input-error :messages="$errors->get('turno')" class="mt-2 text-red-500" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="'Contraseña'" />
            <x-text-input id="password"
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Confirmar Contraseña'" />
            <x-text-input id="password_confirmation"
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500" />
        </div>

        <div class="flex items-center justify-between mt-4">
            {{-- <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-auto"
                href="{{ route('login') }}">
                {{ __('¿Ya estás registrado?') }}
            </a> --}}

            <x-primary-button>
                {{ __('Registrar') }}
            </x-primary-button>
        </div>
    </form>
    </div>
</x-app-layout>
