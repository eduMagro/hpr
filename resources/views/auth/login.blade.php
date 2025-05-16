<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />

            <x-text-input id="email"
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />

        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password"
                class="block mt-1 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />

        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember"
                    checked>
                <span class="ms-2 text-sm text-gray-600">{{ __('Recuerdame') }}</span>
            </label>
        </div>
        <div class="block mt-4">
            <label for="recordar_correo" class="inline-flex items-center">
                <input id="recordar_correo" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                <span class="ms-2 text-sm text-gray-600">Recordar correo</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-auto"
                    href="{{ route('password.request') }}">

                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
            <x-primary-button class="ms-3">
                {{ __('Iniciar Sesión') }}
            </x-primary-button>
        </div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const recordarCheckbox = document.getElementById('recordar_correo');

            // Al cargar, si hay un correo guardado en localStorage, lo rellenamos
            const correoGuardado = localStorage.getItem('correoRecordado');
            if (correoGuardado) {
                emailInput.value = correoGuardado;
                recordarCheckbox.checked = true;
            } else {
                recordarCheckbox.checked = true; // ✅ Activado por defecto aunque no haya correo aún
            }


            // Al enviar el formulario, guardamos o borramos el correo según el checkbox
            document.querySelector('form').addEventListener('submit', function() {
                if (recordarCheckbox.checked) {
                    localStorage.setItem('correoRecordado', emailInput.value);
                } else {
                    localStorage.removeItem('correoRecordado');
                }
            });
        });
    </script>

</x-guest-layout>
