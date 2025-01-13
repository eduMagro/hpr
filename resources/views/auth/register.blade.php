<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="'Nombre'" />
<<<<<<< HEAD
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
=======
            <x-text-input 
                id="name" 
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="text" 
                name="name" 
                :value="old('name')" 
                required 
                autofocus 
                autocomplete="name" 
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500" />
>>>>>>> 6fea693 (primercommit)
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="'Correo Electrónico'" />
<<<<<<< HEAD
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
=======
            <x-text-input 
                id="email" 
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="email" 
                name="email" 
                :value="old('email')" 
                required 
                autocomplete="username" 
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
>>>>>>> 6fea693 (primercommit)
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="'Contraseña'" />
<<<<<<< HEAD

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
=======
            <x-text-input 
                id="password" 
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="password" 
                name="password" 
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />
>>>>>>> 6fea693 (primercommit)
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Confirmar Contraseña'" />
<<<<<<< HEAD

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('¿Ya estás registrado?') }}
            </a>

            <x-primary-button class="ms-4">
=======
            <x-text-input 
                id="password_confirmation" 
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="password" 
                name="password_confirmation" 
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a 
                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-auto" 
                href="{{ route('login') }}"
            >
                {{ __('¿Ya estás registrado?') }}
            </a>

            <x-primary-button>
>>>>>>> 6fea693 (primercommit)
                {{ __('Registrar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
