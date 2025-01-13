<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
<<<<<<< HEAD
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
=======
            <x-input-label for="email" :value="'Correo Electrónico'" />
            <x-text-input 
                id="email" 
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="email" 
                name="email" 
                :value="old('email', $request->email)" 
                required 
                autofocus 
                autocomplete="username" 
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
>>>>>>> 6fea693 (primercommit)
        </div>

        <!-- Password -->
        <div class="mt-4">
<<<<<<< HEAD
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
=======
            <x-input-label for="password" :value="'Contraseña'" />
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
<<<<<<< HEAD
            <x-input-label for="password_confirmation" :value="__('Confirmar Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
=======
            <x-input-label for="password_confirmation" :value="'Confirmar Contraseña'" />
            <x-text-input 
                id="password_confirmation" 
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="password" 
                name="password_confirmation" 
                required 
                autocomplete="new-password" 
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500" />
>>>>>>> 6fea693 (primercommit)
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
<<<<<<< HEAD
                {{ __('Cambiar Password') }}
=======
                {{ __('Cambiar Contraseña') }}
>>>>>>> 6fea693 (primercommit)
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
