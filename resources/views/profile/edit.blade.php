<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administración de Perfil') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            {{-- Cerrar sesiones en otros dispositivos --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl space-y-4">
                    <h2 class="text-lg font-medium text-gray-900">Cerrar sesiones</h2>
                    <form method="POST" action="{{ route('usuarios.cerrarSesiones', $user) }}">
                        @csrf
                        <x-tabla.boton-azul onclick="return confirm('¿Cerrar todas las sesiones de este usuario?')">
                            🛑 Cerrar sesiones activas
                        </x-tabla.boton-azul>
                    </form>
                    @if ($sesiones->isNotEmpty())
                        <div class="mt-4 space-y-2 text-sm text-gray-700">
                            <h3 class="font-semibold text-base text-gray-800">Sesiones activas de
                                {{ $user->nombre_completo }}:</h3>

                            @foreach ($sesiones as $sesion)
                                <div class="p-3 border rounded bg-gray-50">
                                    <p><strong>IP:</strong> {{ $sesion['ip_address'] ?? 'Desconocida' }}</p>
                                    <p><strong>Navegador:</strong> {{ Str::limit($sesion['user_agent'], 80) }}</p>
                                    <p><strong>Última actividad:</strong> {{ $sesion['ultima_actividad'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 mt-4">Este usuario no tiene sesiones activas.</p>
                    @endif

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
