<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <x-volver />{{ __('Administración de Perfil') }}
        </h2>

    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            {{-- Sesiones activas del usuario --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800">Sesiones activas de {{ $user->nombre_completo }}</h3>

                    @if ($sesiones->isNotEmpty())
                        <div class="space-y-2">
                            @foreach ($sesiones as $sesion)
                                <div class="p-3 rounded-lg {{ $sesion['actual'] ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50 border border-gray-100' }}">
                                    <div class="flex items-center gap-3">
                                        {{-- Icono del dispositivo --}}
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full {{ $sesion['actual'] ? 'bg-blue-100' : 'bg-gray-200' }} flex items-center justify-center">
                                            @if (($sesion['dispositivo']['icono'] ?? 'desktop') === 'mobile')
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            @elseif (($sesion['dispositivo']['icono'] ?? 'desktop') === 'tablet')
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 {{ $sesion['actual'] ? 'text-blue-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                        {{-- Info del dispositivo --}}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ $sesion['dispositivo']['navegador'] ?? 'Navegador' }}
                                                </p>
                                                @if ($sesion['actual'])
                                                    <span class="text-[9px] bg-blue-500 text-white px-1.5 py-0.5 rounded-full font-medium">TU SESIÓN</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-600">
                                                {{ $sesion['dispositivo']['sistema'] ?? 'Sistema' }}
                                                @if (!empty($sesion['dispositivo']['dispositivo']))
                                                    <span class="text-gray-400">·</span> {{ $sesion['dispositivo']['dispositivo'] }}
                                                @endif
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] text-gray-400">{{ $sesion['ip_address'] ?? 'IP desconocida' }}</span>
                                                <span class="text-gray-300">·</span>
                                                <span class="text-[10px] text-gray-400">
                                                    {{ $sesion['tiempo_relativo'] ?? $sesion['ultima_actividad'] }}
                                                </span>
                                            </div>
                                        </div>
                                        {{-- Botón cerrar sesión individual --}}
                                        <form method="POST" action="{{ route('usuarios.cerrarSesion', [$user, $sesion['id']]) }}"
                                              onsubmit="return confirm('¿Cerrar esta sesión?')"
                                              class="flex-shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                                                title="Cerrar esta sesión">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($sesiones->count() > 1)
                            <form method="POST" action="{{ route('usuarios.cerrarSesiones', $user) }}"
                                  onsubmit="return confirm('¿Cerrar TODAS las sesiones de {{ $user->nombre_completo }}?')"
                                  class="pt-2">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">
                                    Cerrar todas las sesiones ({{ $sesiones->count() }})
                                </button>
                            </form>
                        @endif
                    @else
                        <p class="text-sm text-gray-500">Este usuario no tiene sesiones activas.</p>
                    @endif
                </div>
            </div>
            {{-- 🧑‍💼 Despedir al trabajador --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl space-y-4">

                    @if ($user->estado === 'despedido')
                        <div class="text-red-700 bg-red-50 border border-red-200 p-3 rounded mt-4 text-sm">
                            Este usuario ha sido <strong>despedido</strong> digitalmente el
                            {{ $user->fecha_baja ?? 'fecha desconocida' }}.
                        </div>
                    @else
                        <div class="text-yellow-800 bg-yellow-50 border border-yellow-200 p-3 rounded text-sm">
                            <strong>⚠️ ¿Qué implica despedir digitalmente a un usuario?</strong><br>
                            Esta acción realiza los siguientes cambios de forma automática:
                            <ul class="list-disc pl-5 mt-2">
                                <li>Revoca su acceso a la plataforma</li>
                                <li>Cierra sus sesiones activas</li>
                                <li>Cancela sus turnos futuros</li>
                                <li>Anula movimientos pendientes asignados</li>
                                <li>Guarda la fecha de baja y lo marca como despedido</li>
                            </ul>
                            <span class="text-xs text-gray-500">* Esta acción es irreversible desde la interfaz.</span>
                        </div>

                        <form id="form-despedir-usuario" method="POST"
                            action="{{ route('usuarios.editarDespedir', $user) }}">
                            @csrf
                            <button type="button" onclick="confirmarDespido()"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded mt-3">
                                ⚠️ Despedir trabajador
                            </button>
                        </form>
                    @endif

                </div>
            </div>

            {{-- Script para SweetAlert --}}
            <script>
                function confirmarDespido() {
                    Swal.fire({
                        title: '¿Despedir al trabajador?',
                        text: "Esta acción desactivará su cuenta y cancelará sus tareas.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, despedir',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('form-despedir-usuario').submit();
                        }
                    });
                }
            </script>


            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
