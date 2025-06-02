<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Mensajes') }}
        </h2>
    </x-slot>

    <div class="w-full px-2 sm:px-4 md:px-6 py-4">

        <div x-data="{ mostrarModal: false }">
            <button @click="mostrarModal = true"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 mb-2 rounded-lg">
                ➕ Enviar mensaje a oficina
            </button>

            <!-- Modal de creación de alerta -->
            <div x-show="mostrarModal"
                class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
                x-transition.opacity>
                <div class="bg-white rounded-lg shadow-lg p-6 w-96 relative">
                    <button @click="mostrarModal = false"
                        class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">✖</button>
                    <h2 class="text-lg font-semibold mb-4">📢 Enviar Mensaje</h2>

                    <form method="POST" action="{{ route('alertas.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="mensaje" class="block text-sm font-semibold">Mensaje:</label>
                            <textarea id="mensaje" name="mensaje" rows="3"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" required>{{ old('mensaje') }}</textarea>
                        </div>

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
                                        <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <!-- Para usuarios que no son de oficina: ocultamos los campos -->
                            <input type="hidden" name="enviar_a_departamentos" value="rrhh,producción">
                        @endif

                        <div class="flex justify-end space-x-2">
                            <button type="button" @click="mostrarModal = false"
                                class="bg-gray-400 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg">
                                Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-500 text-white">
                        <tr>
                            @if (auth()->user()->rol === 'oficina')
                                <th class="px-4 py-2 text-xs font-medium text-center uppercase tracking-wider">Enviado
                                    por</th>
                            @endif
                            <th class="px-4 py-2 text-xs font-medium text-center uppercase tracking-wider">Mensaje</th>
                            <th
                                class="px-4 py-2 text-xs font-medium text-center uppercase tracking-wider hidden sm:table-cell">
                                Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                        @forelse ($alertas as $alerta)
                            @php
                                $esEntrante = $alerta->tipo === 'entrante';
                                $noLeida = $esEntrante && empty($alertasLeidas[$alerta->id]);
                            @endphp


                            <tr class="{{ $noLeida ? 'bg-yellow-100' : 'bg-white' }}"
                                data-alerta-id="{{ $alerta->id }}">

                                @if (auth()->user()->rol === 'oficina')
                                    <td class="px-4 py-2 text-center">
                                        {{ $alerta->usuario1?->name ?? 'Desconocido' }}
                                    </td>
                                @endif
                                <td class="px-4 py-2 break-words">
                                    @php
                                        $esCambioMaquina =
                                            strpos($alerta->mensaje, 'Solicitud de cambio de máquina') === 0;
                                        $esEntrante = $alerta->tipo === 'entrante';
                                    @endphp

                                    @if ($alerta->tipo === 'entrante')
                                        <span class="inline-block text-green-600 font-bold mr-1">📩</span>
                                        {{-- Mensaje recibido --}}
                                    @else
                                        <span class="inline-block text-blue-600 font-bold mr-1">📤</span>
                                        {{-- Mensaje enviado --}}
                                    @endif



                                    @if ($esCambioMaquina && $esAlertaEntrante && isset($elementoId, $origen, $destino, $maquinaDestinoId))
                                        <a href="#"
                                            onclick="abrirModalAceptarCambio('{{ $elementoId }}', '{{ $origen }}', '{{ $destino }}', '{{ $maquinaDestinoId }}', '{{ $alerta->id }}')"
                                            class="text-green-700 hover:underline font-semibold">
                                            {{ $alerta->mensaje }}
                                        </a>
                                    @else
                                        <span class="text-gray-700">{{ $alerta->mensaje }}</span>
                                    @endif

                                </td>

                                <td class="px-4 py-2 hidden sm:table-cell">{{ $alerta->created_at->diffForHumans() }}
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">No hay alertas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            <!-- Paginación -->
            <div class="mt-4 flex justify-center">
                {{ $alertas->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Modal Confirmación Cambio de Máquina -->
    <div id="modalConfirmacionCambio"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <!-- Título -->
            <h2 class="text-xl font-bold mb-4 text-center">¿Aceptar cambio de máquina?</h2>

            <!-- Contenido dinámico -->
            <p class="mb-4 text-center">
                Solicitud para <strong>elemento <span id="elementoModal" class="font-semibold"></span></strong><br>
                De <span id="origenModal" class="font-semibold text-red-600"></span>
                a <span id="destinoModal" class="font-semibold text-green-600"></span>
            </p>

            <!-- Formulario de acción -->
            <form id="formAceptarCambio" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="maquina_id" id="nueva_maquina_id">

                <div class="flex justify-center gap-4 mt-4">
                    <button type="button" onclick="cerrarModalConfirmacion()"
                        class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                        Cancelar
                    </button>

                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Aceptar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        let nuevasAlertas = [];

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("tr.bg-yellow-100").forEach(row => {
                const id = row.dataset.alertaId;
                if (id) nuevasAlertas.push(id);
            });

            console.log("Nuevas alertas detectadas:", nuevasAlertas); // Depuración
        });

        window.addEventListener("beforeunload", function() {
            if (nuevasAlertas.length > 0) {
                const data = new FormData();
                data.append('_token', '{{ csrf_token() }}');
                nuevasAlertas.forEach(id => data.append('alerta_ids[]', id));
                navigator.sendBeacon("{{ route('alertas.marcarLeidas') }}", data);
            }
        });

        function abrirModalAceptarCambio(elementoId, origen, destino, maquinaDestinoId = null, alertaId = null) {
            // Asigna los valores al modal
            document.getElementById('elementoModal').textContent = elementoId;
            document.getElementById('origenModal').textContent = origen;
            document.getElementById('destinoModal').textContent = destino;
            document.getElementById('nueva_maquina_id').value = maquinaDestinoId;

            if (!maquinaDestinoId) {
                alert("ID de máquina destino no válido");
                return;
            }

            const form = document.getElementById('formAceptarCambio');
            form.action = `/elementos/${elementoId}/cambio-maquina?alerta_id=${alertaId}`; // importante

            document.getElementById('modalConfirmacionCambio').classList.remove('hidden');
        }
    </script>


</x-app-layout>
