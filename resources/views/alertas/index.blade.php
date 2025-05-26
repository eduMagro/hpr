<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Alertas') }}
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">
        <div x-data="{ mostrarModal: false }">
            @if (auth()->user()->rol == 'oficina')
                <button @click="mostrarModal = true"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 mb-2 rounded-lg">
                    ➕ Nueva Alerta
                </button>

                <!-- Modal de creación de alerta -->
                <div x-show="mostrarModal"
                    class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
                    x-transition.opacity>
                    <div class="bg-white rounded-lg shadow-lg p-6 w-96 relative">
                        <button @click="mostrarModal = false"
                            class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
                            ✖
                        </button>
                        <h2 class="text-lg font-semibold mb-4">📢 Crear Nueva Alerta</h2>

                        <form method="POST" action="{{ route('alertas.store') }}">
                            @csrf
                            <div class="mb-4">
                                <label for="mensaje" class="block text-sm font-semibold">Mensaje:</label>
                                <textarea id="mensaje" name="mensaje" rows="3"
                                    class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" required>{{ old('mensaje') }}</textarea>
                            </div>

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

                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="mostrarModal = false"
                                    class="bg-gray-400 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Tabla de Alertas -->
            <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="w-full border border-gray-300 rounded-lg">
                    <thead class="bg-blue-500 text-white">
                        <tr class="text-left text-sm uppercase">
                            <th class="py-3 border text-center">Enviado por</th>
                            <th class="py-3 border text-center">Mensaje</th>
                            <th class="py-3 border text-center">Fecha</th>
                            <th class="py-3 border text-center">Tipo</th>
                            <th class="py-3 border text-center">Leida</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">

                        @forelse ($alertas as $alerta)
                            @php
                                // Determinar si la alerta es entrante para el usuario
                                $esEntrante =
                                    ($alerta->destinatario_id && $alerta->destinatario_id == $user->id) ||
                                    ($alerta->destino && $alerta->destino == $user->rol) ||
                                    ($alerta->destinatario && $alerta->destinatario == $user->categoria);

                                // Determinar si está no leída
                                $noLeida = !isset($alertasLeidas[$alerta->id]) || is_null($alertasLeidas[$alerta->id]);

                                // Pintar solo si es entrante y no leída
                                $nueva = $esEntrante && $noLeida;
                            @endphp

                            <tr class="{{ $nueva ? 'bg-yellow-100' : 'bg-white' }}"
                                data-alerta-id="{{ $alerta->id }}">

                                {{-- Es un asolicitud de cambio de maquina? --}}
                                @php
                                    $esCambioMaquina = strpos($alerta->mensaje, 'Solicitud de cambio de máquina') === 0;

                                    $elementoId = $etiquetaSubId = $origen = $destino = $maquinaDestinoId = null;

                                    if ($esCambioMaquina) {
                                        preg_match('/elemento\s#(\d+)/i', $alerta->mensaje, $matchElemento);
                                        preg_match('/etiqueta\s([\d\.]+)\)/i', $alerta->mensaje, $matchEtiqueta);
                                        preg_match('/Origen:\s([^,]+),/i', $alerta->mensaje, $matchOrigen);
                                        preg_match('/Destino:\s(.+)$/i', $alerta->mensaje, $matchDestino);

                                        $elementoId = $matchElemento[1] ?? null;
                                        $etiquetaSubId = $matchEtiqueta[1] ?? null;
                                        $origen = $matchOrigen[1] ?? null;
                                        $destino = $matchDestino[1] ?? null;

                                        // Buscar la máquina destino por nombre
                                        $maquinaDestino = \App\Models\Maquina::where('nombre', trim($destino))->first();
                                        $maquinaDestinoId = $maquinaDestino->id ?? 'null';
                                    }
                                @endphp
                                <td class="px-2 py-3 text-center border">
                                    @php
                                        $autor1 = $alerta->usuario1?->name;
                                        $autor2 = $alerta->usuario2?->name;
                                    @endphp

                                    @if ($autor1 && $autor2)
                                        <span title="Usuario 1 y Usuario 2">{{ $autor1 }} y
                                            {{ $autor2 }}</span>
                                    @elseif ($autor1)
                                        <span title="Usuario 1">{{ $autor1 }}</span>
                                    @elseif ($autor2)
                                        <span title="Usuario 2">{{ $autor2 }}</span>
                                    @else
                                        <span class="text-gray-400 italic">Desconocido</span>
                                    @endif
                                </td>

                                <td class="px-2 py-3 text-center border">
                                    @php
                                        $esEntrante =
                                            ($alerta->destinatario_id && $alerta->destinatario_id == $user->id) ||
                                            ($alerta->destino && $alerta->destino == $user->rol) ||
                                            ($alerta->destinatario && $alerta->destinatario == $user->categoria);
                                    @endphp

                                    @if ($esCambioMaquina && $etiquetaSubId && $origen && $destino && $esEntrante)
                                        <a href="#"
                                            onclick="abrirModalAceptarCambio('{{ $elementoId }}', '{{ $origen }}', '{{ $destino }}', '{{ $maquinaDestinoId }}', '{{ $alerta->id }}')"
                                            class="text-green-700 hover:underline font-semibold">
                                            {{ $alerta->mensaje }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">
                                            {{ $alerta->mensaje }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-2 py-3 text-center border">{{ $alerta->created_at->diffForHumans() }}
                                </td>
                                <td class="px-2 py-3 text-center border">

                                    @if ($esEntrante)
                                        <span class="text-green-600 font-bold">⬇ Entrante</span>
                                    @else
                                        <span class="text-red-600 font-bold">⬆ Saliente</span>
                                    @endif

                                </td>
                                <td class="px-2 py-3 text-center border">
                                    @php
                                        $leida =
                                            isset($alertasLeidas[$alerta->id]) && !is_null($alertasLeidas[$alerta->id]);
                                    @endphp

                                    @if ($leida)
                                        <span class="text-green-600 font-bold">✔ Sí</span>
                                    @else
                                        <span class="text-red-600 font-bold">✘ No</span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-gray-500">No hay alertas registradas
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
