<x-app-layout>

    <div class="w-full px-2 sm:px-4 md:px-6 py-4">

        <div x-data="{ mostrarModal: false }">
            <button @click="mostrarModal = true"
                class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white font-bold py-3 md:py-2 px-6 md:px-4 mb-4 md:mb-2 rounded-lg shadow-md hover:shadow-lg transition-all duration-150 flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                </svg>
                <span>Enviar Mensaje</span>
            </button>

            <!-- Modal de creación de alerta -->
            <div x-show="mostrarModal" x-cloak
                class="fixed inset-0 flex items-end md:items-center justify-center bg-gray-900 bg-opacity-50 z-50 p-0 md:p-4"
                style="display: none;" x-transition.opacity @click.self="mostrarModal = false">
                <div class="bg-white rounded-t-2xl md:rounded-xl shadow-2xl p-6 w-full md:w-96 max-h-[90vh] md:max-h-[85vh] overflow-y-auto relative"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95">

                    <!-- Indicador visual de modal móvil -->
                    <div class="md:hidden w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>

                    <button @click="mostrarModal = false"
                        class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full p-2 transition-colors duration-150">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <h2 class="text-xl font-bold mb-6 text-gray-900 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        <span>Enviar Mensaje</span>
                    </h2>
                    <form method="POST" action="{{ route('alertas.store') }}" enctype="multipart/form-data"
                        x-data="{ cargando: false }" @submit="cargando = true">
                        @csrf
                        <div class="mb-4">
                            <label for="mensaje" class="block text-sm font-semibold">Mensaje:</label>
                            <textarea id="mensaje" name="mensaje" rows="3"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" required>{{ old('mensaje') }}</textarea>
                        </div>
                        {{-- <div class="mb-4">
                            <label for="imagen" class="block text-sm font-semibold">Imagen (opcional):</label>
                            <input type="file" id="imagen" name="imagen"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" accept="image/*">
                        </div> --}}

                        @if (auth()->user()->rol === 'oficina')
                            <div class="mb-4">
                                <label for="rol" class="block text-sm font-semibold">Rol</label>
                                <select id="rol" name="rol" class="w-full border rounded-lg p-2">
                                    <option value="">-- Seleccionar un Rol --</option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol }}">{{ ucfirst($rol) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="categoria" class="block text-sm font-semibold">Categoría</label>
                                <select id="categoria" name="categoria" class="w-full border rounded-lg p-2">
                                    <option value="">-- Seleccionar una Categoría --</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria }}">{{ ucfirst($categoria) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="destinatario_id" class="block text-sm font-semibold">Destinatario
                                    Personal</label>
                                <select id="destinatario_id" name="destinatario_id"
                                    class="w-full border rounded-lg p-2">
                                    <option value="">-- Seleccionar un Usuario --</option>
                                    @foreach ($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}">{{ $usuario->nombre_completo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <!-- Para usuarios que no son de oficina: ocultamos los campos -->
                            <input type="hidden" name="enviar_a_departamentos" value="Programador">
                        @endif

                        <div class="flex justify-end space-x-2">
                            <button type="button" @click="mostrarModal = false"
                                class="bg-gray-400 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">
                                Cancelar
                            </button>
                            <x-boton-submit texto="Enviar" color="blue" />

                        </div>
                    </form>
                </div>
            </div>

            <!-- Vista Desktop (Tabla) -->
            <div class="hidden md:block w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-blue-600 to-blue-500 text-white">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Enviado por
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider">
                                Mensaje
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                        @forelse ($alertas as $alerta)
                            @php
                                $esParaListado = $alerta->leidas->contains('user_id', $user->id);
                                $esEntrante = $alerta->tipo === 'entrante' || $esParaListado;
                                $esSaliente = $alerta->user_id_1 === $user->id && !$esEntrante;
                                $noLeida = $esEntrante && empty($alertasLeidas[$alerta->id]);
                                // Mostrar badge "Nuevo" si no está leída O si tiene respuestas nuevas
                                $mostrarBadge = $noLeida || ($alerta->tiene_respuestas_nuevas ?? false);
                            @endphp

                            <tr class="cursor-pointer transition-all duration-200 hover:bg-blue-50 hover:shadow-md {{ $mostrarBadge ? 'bg-yellow-50 border-l-4 border-yellow-400' : 'bg-white' }}"
                                data-alerta-id="{{ $alerta->id }}"
                                onclick="marcarAlertaLeida({{ $alerta->id }}, this, @js($alerta->mensaje_completo), {{ $esSaliente ? 'true' : 'false' }})">

                                <!-- Columna Estado -->
                                <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                    @if ($alerta->tipo === 'entrante')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                            </svg>
                                            Recibido
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                            </svg>
                                            Enviado
                                        </span>
                                    @endif
                                    @if ($mostrarBadge)
                                        <div class="mt-1">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-400 text-yellow-900 animate-pulse">
                                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <circle cx="10" cy="10" r="8" />
                                                </svg>
                                                Nuevo
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Columna Enviado por -->
                                <td class="px-4 py-3 text-center">
                                    @php $usuario = $alerta->usuario1; @endphp
                                    @if ($usuario)
                                        <div class="flex flex-col items-center">
                                            <span class="font-medium text-gray-900">
                                                @if ($usuario->rol === 'oficina')
                                                    {{ $usuario->email === 'eduardo.magro@pacoreyes.com' ? 'Dpto. Informática' : 'Administrador' }}
                                                @else
                                                    {{ $usuario->nombre_completo }}
                                                @endif
                                            </span>
                                            @if ($usuario->rol === 'oficina')
                                                <span class="text-xs text-gray-500 mt-0.5">
                                                    <svg class="w-3 h-3 inline mr-0.5" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Oficina
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">Desconocido</span>
                                    @endif
                                </td>

                                <!-- Columna Mensaje -->
                                <td class="px-4 py-3">
                                    <div class="flex items-start justify-between space-x-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-gray-800 line-clamp-2"
                                                title="{{ $alerta->mensaje_completo }}">
                                                {{ $alerta->mensaje_corto }}
                                            </p>
                                            @if (strlen($alerta->mensaje_completo) > strlen($alerta->mensaje_corto))
                                                <span class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                    Clic para ver completo...
                                                </span>
                                            @endif
                                        </div>
                                        @if ($alerta->total_respuestas > 0)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200 whitespace-nowrap">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $alerta->total_respuestas }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Columna Fecha -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="text-gray-900 font-medium">{{ $alerta->created_at->diffForHumans() }}</span>
                                        <span
                                            class="text-xs text-gray-500 mt-0.5">{{ $alerta->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </td>

                                <!-- Columna Acciones -->
                                <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                    <div class="flex justify-center space-x-2">
                                        @if ($esSaliente)
                                            <button onclick="eliminarAlerta({{ $alerta->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                                title="Eliminar mensaje">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Sin acciones</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="font-medium">No hay mensajes registrados</p>
                                    <p class="text-sm mt-1">Los mensajes que recibas o envíes aparecerán aquí</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Vista Mobile (Cards) -->
            <div class="md:hidden space-y-3">
                @forelse ($alertas as $alerta)
                    @php
                        $esParaListado = $alerta->leidas->contains('user_id', $user->id);
                        $esEntrante = $alerta->tipo === 'entrante' || $esParaListado;
                        $esSaliente = $alerta->user_id_1 === $user->id && !$esEntrante;
                        $noLeida = $esEntrante && empty($alertasLeidas[$alerta->id]);
                        $mostrarBadge = $noLeida || ($alerta->tiene_respuestas_nuevas ?? false);
                        $usuario = $alerta->usuario1;
                    @endphp

                    <div class="bg-white rounded-lg shadow-md overflow-hidden {{ $mostrarBadge ? 'ring-2 ring-yellow-400 bg-yellow-50' : '' }}"
                        data-alerta-id="{{ $alerta->id }}">

                        <!-- Header de la card -->
                        <div
                            class="bg-gradient-to-r {{ $alerta->tipo === 'entrante' ? 'from-green-500 to-green-400' : 'from-blue-500 to-blue-400' }} px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    @if ($alerta->tipo === 'entrante')
                                        <path
                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    @else
                                        <path
                                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    @endif
                                </svg>
                                <span class="text-white font-semibold text-sm">
                                    {{ $alerta->tipo === 'entrante' ? 'Recibido' : 'Enviado' }}
                                </span>
                                @if ($mostrarBadge)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-yellow-400 text-yellow-900 animate-pulse">
                                        NUEVO
                                    </span>
                                @endif
                            </div>
                            <span class="text-white text-xs">{{ $alerta->created_at->diffForHumans() }}</span>
                        </div>

                        <!-- Contenido de la card -->
                        <div class="p-4 space-y-3"
                            onclick="marcarAlertaLeida({{ $alerta->id }}, this.closest('[data-alerta-id]'), @js($alerta->mensaje_completo), {{ $esSaliente ? 'true' : 'false' }})">

                            <!-- Emisor -->
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        @if ($usuario)
                                            @if ($usuario->rol === 'oficina')
                                                {{ $usuario->email === 'eduardo.magro@pacoreyes.com' ? 'Dpto. Informática' : 'Administrador' }}
                                            @else
                                                {{ $usuario->nombre_completo }}
                                            @endif
                                        @else
                                            Desconocido
                                        @endif
                                    </p>
                                    @if ($usuario && $usuario->rol === 'oficina')
                                        <p class="text-xs text-gray-500">Oficina</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Mensaje -->
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                <p class="text-sm text-gray-800 line-clamp-3">
                                    {{ $alerta->mensaje_corto }}
                                </p>

                                <div class="flex items-center justify-between mt-2">
                                    @if (strlen($alerta->mensaje_completo) > strlen($alerta->mensaje_corto))
                                        <button class="text-xs text-blue-600 font-medium flex items-center">
                                            <span>Ver mensaje completo</span>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    @endif

                                    @if ($alerta->total_respuestas > 0)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $alerta->total_respuestas }}
                                            {{ $alerta->total_respuestas == 1 ? 'respuesta' : 'respuestas' }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                <span class="text-xs text-gray-500">
                                    {{ $alerta->created_at->format('d/m/Y H:i') }} wire:navigate
                                </span>
                                @if ($esSaliente)
                                    <button onclick="event.stopPropagation(); eliminarAlerta({{ $alerta->id }})"
                                        class="inline-flex items-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg transition-colors duration-150 active:scale-95">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 bg-white rounded-lg shadow-md">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="font-medium text-gray-900 text-lg">No hay mensajes</p>
                        <p class="text-sm text-gray-500 mt-1">Los mensajes que recibas o envíes aparecerán aquí</p>
                    </div>
                @endforelse
            </div>


            <!-- Paginación -->
            <x-tabla.paginacion :paginador="$alertas" />
        </div>
    </div>

    @if ($esAdministrador)
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mt-6 md:mt-8 mb-4 gap-3">
            <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-blue-900 flex items-center">
                <svg class="w-6 h-6 md:w-7 md:h-7 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                    <path fill-rule="evenodd"
                        d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                        clip-rule="evenodd" />
                </svg>
                <span class="hidden sm:inline">Panel de Administración</span>
                <span class="sm:hidden">Panel Admin</span>
            </h2>
            <span
                class="text-xs sm:text-sm text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full font-medium whitespace-nowrap">
                Total: {{ $todasLasAlertas->total() }} wire:navigate
            </span>
        </div>

        <!-- Formulario de filtros para móvil -->
        <div class="md:hidden bg-white rounded-lg shadow-md p-4 mb-4">
            <form method="GET" action="{{ route('alertas.index') }}" class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Emisor</label>
                    <x-tabla.input name="emisor" value="{{ request('emisor') }}" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Mensaje</label>
                    <x-tabla.input name="mensaje" value="{{ request('mensaje') }}" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Tipo</label>
                    <x-tabla.select name="tipo" :options="$tiposAlerta" :selected="request('tipo')" empty="-- Todos --" />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-700 block mb-1">Fecha</label>
                    <x-tabla.input type="date" name="fecha_creada" value="{{ request('fecha_creada') }}" />
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium text-sm">
                        Filtrar
                    </button>
                    <a href="{{ route('alertas.index') }}" wire:navigate
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium text-sm text-center">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Vista Desktop (Tabla) -->
        <div class="hidden md:block w-full overflow-x-auto bg-white shadow-xl rounded-lg border border-gray-200">
            <table class="w-full min-w-[1000px]">
                <thead class="bg-gradient-to-r from-blue-600 to-blue-500 text-white">
                    <tr class="text-center text-xs font-semibold">
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['user_id_1'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['user_id_2'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['destino'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['destinatario'] !!}</th>
                        <th class="p-3 border-r border-blue-400">Mensaje</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['tipo'] !!}</th>
                        <th class="p-3 border-r border-blue-400">{!! $ordenablesAlertas['created_at'] !!}</th>
                        <th class="p-3">Acciones</th>
                    </tr>
                    <tr class="text-center text-xs bg-blue-400">
                        <form method="GET" action="{{ route('alertas.index') }}">
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="emisor" value="{{ request('emisor') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="receptor" value="{{ request('receptor') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="destino" value="{{ request('destino') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="destinatario" value="{{ request('destinatario') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input name="mensaje" value="{{ request('mensaje') }}" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.select name="tipo" :options="$tiposAlerta" :selected="request('tipo')" empty="-- Todos --"
                                    class="text-xs" />
                            </th>
                            <th class="p-2 border-r border-blue-300">
                                <x-tabla.input type="date" name="fecha_creada"
                                    value="{{ request('fecha_creada') }}" />
                            </th>
                            <x-tabla.botones-filtro ruta="alertas.index" />
                        </form>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($todasLasAlertas as $alerta)
                        <tr
                            class="border-b border-gray-200 odd:bg-gray-50 even:bg-white hover:bg-blue-50 transition-colors duration-150">
                            <td class="p-3 text-center border-r border-gray-200">
                                <div class="flex flex-col items-center">
                                    <span class="font-medium text-gray-900">
                                        {{ $alerta->usuario1?->nombre_completo ?? '—' }}
                                    </span>
                                    @if ($alerta->usuario1?->rol)
                                        <span
                                            class="text-xs text-gray-500 mt-0.5">{{ ucfirst($alerta->usuario1->rol) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                <span class="text-gray-700">
                                    {{ $alerta->usuario2?->nombre_completo ?? '—' }}
                                </span>
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                @if ($alerta->destino)
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                        {{ ucfirst($alerta->destino) }} wire:navigate
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                <span class="text-gray-700">
                                    {{ $alerta->destinatarioUser?->nombre_completo ?? '—' }}
                                </span>
                            </td>
                            <td class="p-3 border-r border-gray-200">
                                <div class="max-w-md">
                                    <p class="text-gray-800 line-clamp-2 leading-relaxed"
                                        title="{{ $alerta->mensaje }}">
                                        {{ $alerta->mensaje }}
                                    </p>
                                    @if (strlen($alerta->mensaje) > 100)
                                        <button onclick="verMensajeCompleto(@js($alerta->mensaje))"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-1">
                                            Ver completo →
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                @if ($alerta->tipo === 'entrante')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                        Recibido
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                        </svg>
                                        Enviado
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center border-r border-gray-200">
                                <div class="flex flex-col items-center">
                                    <span class="text-gray-900 font-medium text-xs">
                                        {{ $alerta->created_at?->format('d/m/Y') }} wire:navigate
                                    </span>
                                    <span class="text-gray-500 text-xs">
                                        {{ $alerta->created_at?->format('H:i') }} wire:navigate
                                    </span>
                                    <span class="text-gray-400 text-xs mt-0.5">
                                        {{ $alerta->created_at?->diffForHumans() }} wire:navigate
                                    </span>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick="verMensajeCompleto(@js($alerta->mensaje))"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                        title="Ver mensaje completo">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd"
                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Ver
                                    </button>
                                    <button onclick="eliminarAlerta({{ $alerta->id }})"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-md transition-colors duration-150 shadow-sm hover:shadow-md"
                                        title="Eliminar alerta">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="font-medium">No hay alertas disponibles</p>
                                <p class="text-sm mt-1">Ajusta los filtros para ver más resultados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Vista Mobile (Cards) -->
        <div class="md:hidden space-y-4">
            @forelse ($todasLasAlertas as $alerta)
                <div
                    class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 transform transition-all duration-200 hover:shadow-xl">

                    <!-- Header de la card con degradado -->
                    <div
                        class="bg-gradient-to-r {{ $alerta->tipo === 'entrante' ? 'from-green-500 to-emerald-600' : 'from-blue-500 to-indigo-600' }} px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    @if ($alerta->tipo === 'entrante')
                                        <path
                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    @else
                                        <path
                                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                    @endif
                                </svg>
                                <span class="text-white font-bold text-sm">
                                    {{ $alerta->tipo === 'entrante' ? 'RECIBIDO' : 'ENVIADO' }}
                                </span>
                            </div>
                            <span class="text-white text-xs font-medium">
                                {{ $alerta->created_at?->format('d/m/Y') }} wire:navigate
                            </span>
                        </div>
                    </div>

                    <!-- Contenido de la card -->
                    <div class="p-4 space-y-3">

                        <!-- Información del emisor -->
                        <div class="flex items-start space-x-3 pb-3 border-b border-gray-100">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $alerta->usuario1?->nombre_completo ?? 'Desconocido' }}
                                </p>
                                @if ($alerta->usuario1?->rol)
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        <span class="inline-block w-2 h-2 rounded-full bg-blue-400 mr-1"></span>
                                        {{ ucfirst($alerta->usuario1->rol) }} wire:navigate
                                    </p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $alerta->created_at?->format('H:i') }} ·
                                    {{ $alerta->created_at?->diffForHumans() }} wire:navigate
                                </p>
                            </div>
                        </div>

                        <!-- Información adicional en chips -->
                        <div class="flex flex-wrap gap-2">
                            @if ($alerta->destino)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Rol: {{ ucfirst($alerta->destino) }} wire:navigate
                                </span>
                            @endif
                            @if ($alerta->destinatarioUser)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                    </svg>
                                    Para: {{ $alerta->destinatarioUser->nombre_completo }}
                                </span>
                            @endif
                        </div>

                        <!-- Mensaje -->
                        <div
                            class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 shadow-inner">
                            <p class="text-sm text-gray-800 leading-relaxed">
                                {{ $alerta->mensaje }}
                            </p>
                        </div>

                        <!-- Acciones -->
                        <div class="flex gap-2 pt-2">
                            <button onclick="verMensajeCompleto(@js($alerta->mensaje))"
                                class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-sm font-semibold rounded-lg transition-all duration-150 shadow-md hover:shadow-lg active:scale-95">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Ver Completo
                            </button>
                            <button onclick="eliminarAlerta({{ $alerta->id }})"
                                class="px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg transition-all duration-150 shadow-md hover:shadow-lg active:scale-95"
                                title="Eliminar">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-xl shadow-md">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <p class="font-bold text-gray-900 text-lg">No hay alertas</p>
                    <p class="text-sm text-gray-500 mt-2">Ajusta los filtros para ver más resultados</p>
                </div>
            @endforelse
        </div>

        <x-tabla.paginacion :paginador="$todasLasAlertas" perPageName="per_page_todas" />
    @endif


    <!-- Modal de mensaje -->
    <div id="modalVerMensaje"
        class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-end md:items-center justify-center z-50 transition-opacity duration-300 p-0 md:p-4"
        onclick="cerrarModalMensaje()">
        <div class="bg-white rounded-t-2xl md:rounded-xl shadow-2xl w-full md:max-w-2xl max-h-[90vh] md:max-h-[85vh] overflow-y-auto transform transition-all duration-300"
            onclick="event.stopPropagation()">

            <!-- Indicador visual de modal móvil -->
            <div class="md:hidden w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>

            <!-- Header del modal -->
            <div
                class="bg-gradient-to-r from-blue-600 to-blue-500 px-4 md:px-6 py-4 md:rounded-t-xl flex items-center justify-between sticky top-0 z-10">
                <div class="flex items-center space-x-2 md:space-x-3">
                    <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    <h2 class="text-lg md:text-xl font-bold text-white">Mensaje</h2>
                </div>
                <button onclick="cerrarModalMensaje()"
                    class="text-white hover:text-gray-200 transition-colors duration-150 p-2 hover:bg-blue-700 rounded-lg active:scale-95">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Contenido del modal -->
            <div class="p-4 md:p-6">
                <!-- Mensaje padre (con estilo similar al hilo) -->
                <div id="contenidoMensajePadre" class="mb-4">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>

                <!-- Mensaje en modo edición (inicialmente oculto) -->
                <textarea id="textareaMensaje"
                    class="w-full mt-2 p-3 md:p-4 border-2 border-blue-300 rounded-lg hidden text-sm md:text-base text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    rows="6" placeholder="Escribe tu mensaje aquí..."></textarea>

                <!-- Hilo de conversación -->
                <div id="hiloConversacion" class="mt-4 hidden">
                    <div
                        class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-lg px-4 py-2 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                    clip-rule="evenodd" />
                            </svg>
                            Historial de conversación
                        </h3>
                        <span id="contadorRespuestas"
                            class="text-xs text-white bg-white bg-opacity-20 px-2 py-0.5 rounded-full font-medium"></span>
                    </div>
                    <div id="hiloContenido"
                        class="space-y-2 max-h-96 overflow-y-auto bg-gradient-to-b from-gray-50 to-white rounded-b-lg p-4 border-x border-b border-gray-200">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>

                <!-- Formulario de respuesta (inicialmente oculto) -->
                <div id="seccionRespuesta" class="mt-4 hidden">
                    <div class="border-t border-gray-200 pt-4">
                        <label for="textoRespuesta" class="block text-sm font-semibold text-gray-700 mb-2">
                            Tu respuesta:
                        </label>
                        <textarea id="textoRespuesta"
                            class="w-full p-3 border-2 border-green-300 rounded-lg text-sm text-gray-800 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                            rows="4" placeholder="Escribe tu respuesta..."></textarea>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row justify-end gap-2 md:gap-3 mt-4 md:mt-6 hidden"
                    id="botonesEdicion">
                    <button onclick="iniciarEdicionAlerta()"
                        class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-2.5 bg-yellow-500 hover:bg-yellow-600 active:bg-yellow-700 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95"
                        id="botonEditar">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Editar
                    </button>

                    <div id="botonesGuardarCancelar" class="hidden flex flex-col sm:flex-row gap-2 md:gap-3">
                        <button onclick="guardarEdicionAlerta()"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                            </svg>
                            Guardar
                        </button>
                        <button onclick="cancelarEdicionAlerta()"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-2.5 bg-gray-500 hover:bg-gray-600 active:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Cancelar
                        </button>
                    </div>
                </div>

                <!-- Botones de respuesta (para mensajes entrantes) -->
                <div id="botonesRespuesta"
                    class="hidden flex flex-col sm:flex-row justify-end gap-2 md:gap-3 mt-4 md:mt-6">
                    <button onclick="activarRespuesta()"
                        class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-2.5 bg-green-500 hover:bg-green-600 active:bg-green-700 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95"
                        id="botonContestar">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        Contestar
                    </button>

                    <div id="botonesEnviarCancelarRespuesta" class="hidden flex flex-col sm:flex-row gap-2 md:gap-3">
                        <button onclick="cancelarRespuesta()"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-2.5 bg-gray-500 hover:bg-gray-600 active:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Cancelar
                        </button>
                        <button onclick="enviarRespuesta()"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                            Enviar Respuesta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmación Cambio de Máquina -->
    <div id="modalConfirmacionCambio"
        class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-end md:items-center justify-center z-50 transition-opacity duration-300 p-0 md:p-4"
        onclick="cerrarModalConfirmacion()">
        <div class="bg-white rounded-t-2xl md:rounded-xl shadow-2xl w-full md:max-w-md transform transition-all duration-300"
            onclick="event.stopPropagation()">

            <!-- Indicador visual de modal móvil -->
            <div class="md:hidden w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>

            <!-- Header del modal -->
            <div
                class="bg-gradient-to-r from-orange-500 to-orange-400 px-4 md:px-6 py-4 md:rounded-t-xl sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 md:space-x-3">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <h2 class="text-lg md:text-xl font-bold text-white">Confirmación</h2>
                    </div>
                    <button onclick="cerrarModalConfirmacion()"
                        class="md:hidden text-white hover:text-gray-200 p-2 rounded-lg active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenido dinámico -->
            <div class="p-4 md:p-6">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 md:p-4 rounded-r-lg mb-4">
                    <p class="text-sm md:text-base text-gray-800 mb-3">
                        Solicitud para elemento: <strong class="text-blue-700 text-base md:text-lg"><span
                                id="elementoModal"></span></strong>
                    </p>
                    <div
                        class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-4 text-sm">
                        <div class="flex items-center bg-red-100 px-3 py-2 rounded-lg w-full sm:w-auto justify-center">
                            <span class="text-red-700 font-semibold">De: <span id="origenModal"></span></span>
                        </div>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400 rotate-90 sm:rotate-0" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <div
                            class="flex items-center bg-green-100 px-3 py-2 rounded-lg w-full sm:w-auto justify-center">
                            <span class="text-green-700 font-semibold">A: <span id="destinoModal"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Formulario de acción -->
                <form id="formAceptarCambio" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="maquina_id" id="nueva_maquina_id">

                    <div class="flex flex-col sm:flex-row justify-end gap-2 md:gap-3 mt-4 md:mt-6">
                        <button type="button" onclick="cerrarModalConfirmacion()"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 bg-gray-500 hover:bg-gray-600 active:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            Cancelar
                        </button>

                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 md:px-5 py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-medium rounded-lg transition-colors duration-150 shadow-md active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            Aceptar Cambio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        let nuevasAlertas = [];

        document.addEventListener("DOMContentLoaded", function() {
            // Detectar nuevas alertas tanto en tabla (tr) como en cards (div)
            document.querySelectorAll("tr.bg-yellow-50, div.ring-2.ring-yellow-400").forEach(elemento => {
                const id = elemento.dataset.alertaId;
                if (id) nuevasAlertas.push(id);
            });
            console.log("Nuevas alertas detectadas:", nuevasAlertas);

            // Cerrar modales con tecla ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modalMensaje = document.getElementById('modalVerMensaje');
                    const modalConfirmacion = document.getElementById('modalConfirmacionCambio');

                    if (modalMensaje && !modalMensaje.classList.contains('hidden')) {
                        cerrarModalMensaje();
                    }
                    if (modalConfirmacion && !modalConfirmacion.classList.contains('hidden')) {
                        cerrarModalConfirmacion();
                    }
                }
            });
        });

        let alertaActualId = null;
        let alertaActualEsSaliente = false;

        function cerrarModalMensaje() {
            document.getElementById('modalVerMensaje').classList.add('hidden');
            document.getElementById('seccionRespuesta').classList.add('hidden');
            document.getElementById('textoRespuesta').value = '';
        }

        function eliminarAlerta(alertaId = null) {
            // Usar el ID pasado como parámetro o el ID actual
            const idAEliminar = alertaId || alertaActualId;

            if (!idAEliminar) {
                Swal.fire('Error', 'No se pudo identificar la alerta a eliminar.', 'error');
                return;
            }

            Swal.fire({
                title: '¿Eliminar mensaje?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/alertas/${idAEliminar}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(response => {
                        if (response.ok) {
                            Swal.fire({
                                title: 'Eliminado',
                                text: 'El mensaje fue eliminado correctamente.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar el mensaje.', 'error');
                        }
                    }).catch(error => {
                        Swal.fire('Error', 'Error de conexión al eliminar el mensaje.', 'error');
                    });
                }
            });
        }

        function iniciarEdicionAlerta() {
            document.getElementById('contenidoMensajePadre').classList.add('hidden');
            document.getElementById('textareaMensaje').classList.remove('hidden');
            document.getElementById('botonEditar').classList.add('hidden');
            document.getElementById('botonesGuardarCancelar').classList.remove('hidden');
        }

        function cancelarEdicionAlerta() {
            document.getElementById('textareaMensaje').classList.add('hidden');
            document.getElementById('contenidoMensajePadre').classList.remove('hidden');
            document.getElementById('botonEditar').classList.remove('hidden');
            document.getElementById('botonesGuardarCancelar').classList.add('hidden');
        }

        function guardarEdicionAlerta() {
            const nuevoMensaje = document.getElementById('textareaMensaje').value.trim();

            if (!nuevoMensaje) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Mensaje vacío',
                    text: 'Debes escribir un mensaje antes de guardar.'
                });
                return;
            }

            guardarAlerta({
                id: alertaActualId,
                mensaje: nuevoMensaje
            });
        }

        function guardarAlerta(alerta) {
            fetch(`/alertas/${alerta.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mensaje: alerta.mensaje
                    })
                })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (response.ok && data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Mensaje actualizado',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        let mensaje = data.error || 'Error inesperado';
                        if (typeof mensaje === 'object') {
                            mensaje = Object.values(mensaje).flat().join('\n');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: mensaje
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: error.message || 'No se pudo actualizar la alerta.'
                    });
                });
        }

        function abrirModalAceptarCambio(elementoId, origen, destino, maquinaDestinoId = null, alertaId = null) {
            document.getElementById('elementoModal').textContent = elementoId;
            document.getElementById('origenModal').textContent = origen;
            document.getElementById('destinoModal').textContent = destino;
            document.getElementById('nueva_maquina_id').value = maquinaDestinoId;

            if (!maquinaDestinoId) {
                alert("ID de máquina destino no válido");
                return;
            }

            const form = document.getElementById('formAceptarCambio');
            form.action = `/elementos/${elementoId}/cambio-maquina?alerta_id=${alertaId}`;

            document.getElementById('modalConfirmacionCambio').classList.remove('hidden');
        }

        // Función para ver mensaje completo en modal (desde tabla admin)
        function verMensajeCompleto(mensaje) {
            Swal.fire({
                title: 'Mensaje Completo',
                html: `<div class="text-left whitespace-pre-wrap text-gray-700 p-4">${mensaje}</div>`,
                icon: 'info',
                confirmButtonColor: '#3B82F6',
                confirmButtonText: 'Cerrar',
                width: '600px'
            });
        }

        function cerrarModalConfirmacion() {
            document.getElementById('modalConfirmacionCambio').classList.add('hidden');
        }

        // Variables globales para el modal
        let esMensajeEntrante = false;

        function marcarAlertaLeida(alertaId, elemento, mensaje, esSaliente) {
            esMensajeEntrante = !esSaliente;
            alertaActualId = alertaId;

            // Siempre intentar marcar como leída si es un mensaje entrante
            // Esto incluye mensajes no leídos y mensajes que fueron marcados como no leídos por nuevas respuestas
            if (!esSaliente) {
                const data = new FormData();
                data.append('_token', '{{ csrf_token() }}');
                data.append('alerta_ids[]', alertaId);

                fetch("{{ route('alertas.verMarcarLeidas') }}", {
                        method: 'POST',
                        body: data
                    })
                    .then(res => {
                        if (!res.ok) {
                            console.error('Error marcando alerta como leída');
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('Respuesta del servidor:', data);

                        // Quitar visualmente el badge de "Nuevo" sin recargar
                        elemento.classList.remove('bg-yellow-50', 'border-l-4', 'border-yellow-400');
                        elemento.classList.add('bg-white');
                        const badgeNuevo = elemento.querySelector('.animate-pulse');
                        if (badgeNuevo) {
                            badgeNuevo.remove();
                        }

                        // Actualizar el contador de alertas sin leer
                        actualizarContadorAlertas();
                    })
                    .catch(err => {
                        console.error('Error en fetch marcarLeida:', err);
                    });
            }

            document.getElementById('textareaMensaje').value = mensaje;

            // Cargar el hilo de conversación (esto también cargará el mensaje padre)
            cargarHiloConversacion(alertaId);

            // Mostrar u ocultar los controles según tipo
            document.getElementById('botonesEdicion').classList.toggle('hidden', !esSaliente);
            document.getElementById('botonesRespuesta').classList.toggle('hidden', esSaliente);
            document.getElementById('textareaMensaje').classList.add('hidden');
            document.getElementById('botonEditar').classList.remove('hidden');
            document.getElementById('botonesGuardarCancelar').classList.add('hidden');

            // Ocultar sección de respuesta al abrir
            document.getElementById('seccionRespuesta').classList.add('hidden');
            document.getElementById('botonContestar').classList.remove('hidden');
            document.getElementById('botonesEnviarCancelarRespuesta').classList.add('hidden');

            document.getElementById('modalVerMensaje').classList.remove('hidden');
        }

        // Función para actualizar el contador de alertas en la campanita
        function actualizarContadorAlertas() {
            fetch("{{ route('alertas.verSinLeer') }}")
                .then(res => res.json())
                .then(data => {
                    // Actualizar el badge de la campanita en el header
                    const badge = document.getElementById('alerta-count');
                    if (badge) {
                        if (data.cantidad > 0) {
                            badge.textContent = data.cantidad;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                })
                .catch(err => {
                    console.error('Error actualizando contador:', err);
                });
        }

        function cargarHiloConversacion(alertaId) {
            fetch(`/alertas/${alertaId}/hilo`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.hilo) {
                        // Mostrar el mensaje padre con estilo de hilo
                        mostrarMensajePadre(data.hilo);

                        const hiloContenido = document.getElementById('hiloContenido');
                        hiloContenido.innerHTML = '';

                        // Si hay respuestas, mostrar el historial
                        if (data.hilo.respuestas && data.hilo.respuestas.length > 0) {
                            // Contar total de respuestas (recursivo)
                            const totalRespuestas = contarRespuestas(data.hilo.respuestas);
                            document.getElementById('contadorRespuestas').textContent =
                                `${totalRespuestas} ${totalRespuestas === 1 ? 'respuesta' : 'respuestas'}`;

                            // Mostrar las respuestas
                            mostrarHilo(data.hilo.respuestas, 0);
                            document.getElementById('hiloConversacion').classList.remove('hidden');
                        } else {
                            document.getElementById('hiloConversacion').classList.add('hidden');
                        }
                    } else {
                        document.getElementById('hiloConversacion').classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar hilo:', error);
                    document.getElementById('hiloConversacion').classList.add('hidden');
                });
        }

        function mostrarMensajePadre(mensaje) {
            const contenedor = document.getElementById('contenidoMensajePadre');
            const esPropio = mensaje.es_propio;
            const colorBorde = esPropio ? 'border-blue-500' : 'border-green-500';
            const colorFondo = esPropio ? 'bg-blue-50' : 'bg-green-50';

            contenedor.innerHTML = `
                <div class="border-l-4 ${colorBorde} ${colorFondo} rounded-r-lg p-4 shadow-sm">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <p class="text-sm font-bold ${esPropio ? 'text-blue-700' : 'text-green-700'}">
                                ${mensaje.emisor}
                            </p>
                            <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full font-medium">
                                Mensaje original
                            </span>
                        </div>
                        <span class="text-xs text-gray-500">${mensaje.created_at}</span>
                    </div>
                    <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">${mensaje.mensaje}</p>
                </div>
            `;
        }

        function contarRespuestas(respuestas) {
            let total = respuestas.length;
            respuestas.forEach(respuesta => {
                if (respuesta.respuestas && respuesta.respuestas.length > 0) {
                    total += contarRespuestas(respuesta.respuestas);
                }
            });
            return total;
        }

        function mostrarMensajeEnHilo(mensaje, nivel, esRaiz = false) {
            const hiloContenido = document.getElementById('hiloContenido');
            const margenIzquierdo = nivel * 20;
            const esPropio = mensaje.es_propio;
            const colorBorde = esPropio ? 'border-blue-500' : 'border-green-500';
            const colorFondo = esPropio ? 'bg-blue-50' : 'bg-green-50';
            const etiquetaRaiz = esRaiz ?
                '<span class="ml-2 px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full font-medium">Mensaje original</span>' :
                '';

            const mensajeDiv = document.createElement('div');
            mensajeDiv.className = `border-l-4 ${colorBorde} ${colorFondo} rounded-r-lg p-3 mb-2 shadow-sm`;
            mensajeDiv.style.marginLeft = `${margenIzquierdo}px`;
            mensajeDiv.innerHTML = `
                <div class="flex items-start justify-between mb-1">
                    <p class="text-xs font-bold ${esPropio ? 'text-blue-700' : 'text-green-700'}">
                        ${mensaje.emisor}${etiquetaRaiz}
                    </p>
                    <span class="text-xs text-gray-500">${mensaje.created_at}</span>
                </div>
                <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">${mensaje.mensaje}</p>
            `;

            hiloContenido.appendChild(mensajeDiv);
        }

        function mostrarHilo(respuestas, nivel = 0) {
            const hiloContenido = document.getElementById('hiloContenido');

            respuestas.forEach(respuesta => {
                const margenIzquierdo = nivel * 20;
                const esPropio = respuesta.es_propio;
                const colorBorde = esPropio ? 'border-blue-500' : 'border-green-500';
                const colorFondo = esPropio ? 'bg-blue-50' : 'bg-green-50';
                const iconoRespuesta = '<svg class="w-3 h-3 mr-1 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';

                const respuestaDiv = document.createElement('div');
                respuestaDiv.className =
                    `border-l-4 ${colorBorde} ${colorFondo} rounded-r-lg p-3 mb-2 shadow-sm hover:shadow-md transition-shadow duration-200`;
                respuestaDiv.style.marginLeft = `${margenIzquierdo}px`;
                respuestaDiv.innerHTML = `
                    <div class="flex items-start justify-between mb-1">
                        <p class="text-xs font-bold ${esPropio ? 'text-blue-700' : 'text-green-700'}">
                            ${iconoRespuesta}${respuesta.emisor}
                        </p>
                        <span class="text-xs text-gray-500">${respuesta.created_at}</span>
                    </div>
                    <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">${respuesta.mensaje}</p>
                `;

                hiloContenido.appendChild(respuestaDiv);

                // Recursivamente mostrar respuestas anidadas
                if (respuesta.respuestas && respuesta.respuestas.length > 0) {
                    mostrarHilo(respuesta.respuestas, nivel + 1);
                }
            });
        }

        function activarRespuesta() {
            document.getElementById('seccionRespuesta').classList.remove('hidden');
            document.getElementById('botonContestar').classList.add('hidden');
            document.getElementById('botonesEnviarCancelarRespuesta').classList.remove('hidden');
            document.getElementById('textoRespuesta').focus();
        }

        function cancelarRespuesta() {
            document.getElementById('seccionRespuesta').classList.add('hidden');
            document.getElementById('botonContestar').classList.remove('hidden');
            document.getElementById('botonesEnviarCancelarRespuesta').classList.add('hidden');
            document.getElementById('textoRespuesta').value = '';
        }

        function enviarRespuesta() {
            const mensajeRespuesta = document.getElementById('textoRespuesta').value.trim();

            if (!mensajeRespuesta) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Mensaje vacío',
                    text: 'Debes escribir una respuesta antes de enviar.'
                });
                return;
            }

            const datos = {
                mensaje: mensajeRespuesta,
                parent_id: alertaActualId
            };

            fetch("{{ route('alertas.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(datos)
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Respuesta no es JSON:', text.substring(0, 500));
                        throw new Error('La respuesta del servidor no es JSON válido');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Respuesta enviada',
                            text: data.message || 'Tu respuesta ha sido enviada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            cerrarModalMensaje();
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo enviar la respuesta'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: error.message || 'No se pudo enviar la respuesta. Intenta de nuevo.'
                    });
                });
        }
    </script>
</x-app-layout>
