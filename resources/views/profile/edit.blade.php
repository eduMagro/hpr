<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Perfil') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Categoría del perfil') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Actualiza la categoría de tu perfil.') }}
                            </p>
                        </header>

                        <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('patch')

                            <div>
                                <x-input-label for="categoria" :value="__('Categoría')" />
                                <select id="categoria" name="categoria"
                                    class="block mt-3 mb-3 p-3 w-full border-gray-300 rounded-lg shadow-sm hover:border-indigo-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-200"
                                    required>
                                    <option value="" disabled>Selecciona una categoría</option>
                                    <option value="administracion"
                                        {{ old('categoria', $user->categoria) == 'administracion' ? 'selected' : '' }}>
                                        Administración</option>
                                    <option value="gruista"
                                        {{ old('categoria', $user->categoria) == 'gruista' ? 'selected' : '' }}>
                                        Gruista</option>
                                    <option value="operario"
                                        {{ old('categoria', $user->categoria) == 'operario' ? 'selected' : '' }}>
                                        Operario</option>
                                    <option value="mecanico"
                                        {{ old('categoria', $user->categoria) == 'mecanico' ? 'selected' : '' }}>
                                        Mecánico</option>
                                    <option value="visitante"
                                        {{ old('categoria', $user->categoria) == 'visitante' ? 'selected' : '' }}>
                                        Visitante</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('categoria')" />

                                <x-input-error class="mt-2" :messages="$errors->get('categoria')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Guardar') }}</x-primary-button>

                                @if (session('status') === 'profile-updated')
                                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600">{{ __('Guardado.') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
