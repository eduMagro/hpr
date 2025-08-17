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
                ➕ Enviar mensaje
            </button>

            <!-- Modal de creación de alerta -->
            <div x-show="mostrarModal"
                class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
                x-transition.opacity>
                <div class="bg-white rounded-lg shadow-lg p-6 w-96 relative">
                    <button @click="mostrarModal = false"
                        class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">✖</button>
                    <h2 class="text-lg font-semibold mb-4">📢 Enviar Mensaje</h2>
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

            <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-500 text-white">
                        <tr>

                            <th class="px-4 py-2 text-xs font-medium text-center uppercase tracking-wider">Enviado
                                por</th>

                            <th class="px-4 py-2 text-xs font-medium text-center uppercase tracking-wider">Mensaje</th>
                            <th class="px-4 py-2 text-xs font-medium text-center uppercase tracking-wider">
                                Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                        @forelse ($alertas as $alerta)
                            @php
                                $esParaListado = $alerta->leidas->contains('user_id', $user->id);
                                $esEntrante = $alerta->tipo === 'entrante' || $esParaListado;
                                $esSaliente = $alerta->user_id_1 === $user->id && !$esEntrante;
                                $noLeida = $esEntrante && empty($alertasLeidas[$alerta->id]);
                            @endphp


                            <tr class="cursor-pointer hover:bg-gray-100 {{ $noLeida ? 'bg-yellow-100' : 'bg-white' }}"
                                data-alerta-id="{{ $alerta->id }}"
                                onclick="marcarAlertaLeida({{ $alerta->id }}, this, @js($alerta->mensaje_completo), {{ $esSaliente ? 'true' : 'false' }})">

                                <td class="px-4 py-2 text-center">
                                    @php $usuario = $alerta->usuario1; @endphp

                                    @if ($usuario)
                                        @if ($usuario->rol === 'oficina')
                                            {{ $usuario->email === 'eduardo.magro@pacoreyes.com' ? 'Dpto. Informática' : 'Dpto. RRHH' }}
                                        @else
                                            {{ $usuario->nombre_completo }}
                                        @endif
                                    @else
                                        Desconocido
                                    @endif
                                </td>

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
                                            onclick="marcarAlertaLeida({{ $alerta->id }}, this.closest('tr')); abrirModalAceptarCambio('{{ $elementoId }}', '{{ $origen }}', '{{ $destino }}', '{{ $maquinaDestinoId }}', '{{ $alerta->id }}')"
                                            class="text-blue-700 hover:underline">
                                            {{ $alerta->mensaje }}
                                        </a>
                                    @else
                                        <span class="text-gray-700">{{ $alerta->mensaje_corto }}</span>
                                    @endif


                                </td>

                                <td class="px-4 py-2 text-center">
                                    {{ $alerta->created_at->diffForHumans() }}
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
            <x-tabla.paginacion :paginador="$alertas" />
        </div>
    </div>

    @if ($esAdministrador)
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <h2 class="text-2xl font-bold text-blue-900 mt-6 mb-4">📋 Todas las alertas</h2>

        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-4">
                    <tr class="text-center text-xs uppercase">

                        <th class="p-2 border">{!! $ordenablesAlertas['user_id_1'] !!}</th>
                        <th class="p-2 border">{!! $ordenablesAlertas['user_id_2'] !!}</th>
                        <th class="p-2 border">{!! $ordenablesAlertas['destino'] !!}</th>
                        <th class="p-2 border">{!! $ordenablesAlertas['destinatario'] !!}</th>
                        <th class="p-2 border">Mensaje</th>
                        <th class="p-2 border">{!! $ordenablesAlertas['tipo'] !!}</th>
                        <th class="p-2 border">{!! $ordenablesAlertas['created_at'] !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('alertas.index') }}">
                            {{-- mantenemos paginación actual --}}

                            <th class="p-1 border">
                                <x-tabla.input name="emisor" value="{{ request('emisor') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="receptor" value="{{ request('receptor') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="destino" value="{{ request('destino') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="destinatario" value="{{ request('destinatario') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="mensaje" value="{{ request('mensaje') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="tipo" :options="$tiposAlerta" :selected="request('tipo')" empty="-- Todos --"
                                    class="text-xs" />
                            </th>



                            <th class="p-1 border">
                                <x-tabla.input type="date" name="fecha_creada"
                                    value="{{ request('fecha_creada') }}" />
                            </th>


                            <x-tabla.botones-filtro ruta="alertas.index" />
                        </form>
                    </tr>
                </thead>

                <tbody class="text-gray-700">
                    @forelse ($todasLasAlertas as $alerta)
                        <tr
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 text-xs leading-none uppercase">

                            <td class="p-2 text-center border">{{ $alerta->usuario1?->nombre_completo ?? '—' }}</td>
                            <td class="p-2 text-center border">
                                {{ $alerta->usuario2?->nombre_completo ?? '—' }}
                            </td>
                            <td class="p-2 text-center border">{{ $alerta->destino ?? '—' }}</td>
                            <td class="p-2 text-center border">{{ $alerta->destinatarioUser->nombre_completo ?? '—' }}
                            </td>
                            <td class="p-2 border text-left truncate max-w-xs" title="{{ $alerta->mensaje }}">
                                {{ $alerta->mensaje }}
                            </td>
                            <td class="p-2 text-center border">
                                {{ $alerta->tipo }}
                            </td>
                            <td class="p-2 text-center border">{{ $alerta->created_at?->format('d/m/Y H:i') }}</td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-gray-500">No hay alertas disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabla.paginacion :paginador="$todasLasAlertas" perPageName="per_page_todas" />
    @endif


    <!-- Modal de mensaje -->
    <div id="modalVerMensaje"
        class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg relative">
            <button onclick="cerrarModalMensaje()"
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">✖</button>

            <h2 class="text-xl font-bold mb-4">📩 Mensaje</h2>

            <!-- Mensaje en modo lectura -->
            <p id="contenidoMensaje" class="text-gray-800 whitespace-pre-wrap"></p>

            <!-- Mensaje en modo edición (inicialmente oculto) -->
            <textarea id="textareaMensaje" class="w-full mt-2 p-2 border rounded-lg hidden text-gray-800" rows="4"></textarea>

            <div class="flex justify-center gap-4 mt-6 hidden" id="botonesEdicion">
                <button onclick="iniciarEdicionAlerta()"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg" id="botonEditar">
                    ✏️ Editar
                </button>

                <div id="botonesGuardarCancelar" class="hidden flex gap-2">
                    <button onclick="guardarEdicionAlerta()"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                        💾 Guardar
                    </button>
                    <button onclick="cancelarEdicionAlerta()"
                        class="px-4 py-2 bg-gray-400 hover:bg-gray-500 text-white rounded-lg">
                        Cancelar
                    </button>
                </div>

                {{-- <button onclick="eliminarAlerta()"
                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">
                    🗑 Eliminar
                </button> --}}
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

        let alertaActualId = null;
        let alertaActualEsSaliente = false;

        function marcarAlertaLeida(id, fila, mensaje = '', esSaliente = false) {
            alertaActualId = id;
            alertaActualEsSaliente = esSaliente;

            if (fila.classList.contains('bg-yellow-100')) {
                fila.classList.remove('bg-yellow-100');
                fila.classList.add('bg-white');

                const data = new FormData();
                data.append('_token', '{{ csrf_token() }}');
                data.append('alerta_ids[]', id);

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
                    })
                    .catch(err => {
                        console.error('Error en fetch marcarLeida:', err);
                    });

            }

            document.getElementById('contenidoMensaje').textContent = mensaje;
            document.getElementById('textareaMensaje').value = mensaje;

            // Mostrar u ocultar los controles según tipo
            document.getElementById('botonesEdicion').classList.toggle('hidden', !esSaliente);
            document.getElementById('textareaMensaje').classList.add('hidden');
            document.getElementById('contenidoMensaje').classList.remove('hidden');
            document.getElementById('botonEditar').classList.remove('hidden');
            document.getElementById('botonesGuardarCancelar').classList.add('hidden');

            document.getElementById('modalVerMensaje').classList.remove('hidden');
        }

        function cerrarModalMensaje() {
            document.getElementById('modalVerMensaje').classList.add('hidden');
        }

        function eliminarAlerta() {
            Swal.fire({
                title: '¿Eliminar alerta?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/alertas/${alertaActualId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(response => {
                        if (response.ok) {
                            Swal.fire('Eliminado', 'La alerta fue eliminada.', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', 'No se pudo eliminar la alerta.', 'error');
                        }
                    });
                }
            });
        }

        function iniciarEdicionAlerta() {
            document.getElementById('contenidoMensaje').classList.add('hidden');
            document.getElementById('textareaMensaje').classList.remove('hidden');
            document.getElementById('botonEditar').classList.add('hidden');
            document.getElementById('botonesGuardarCancelar').classList.remove('hidden');
        }

        function cancelarEdicionAlerta() {
            document.getElementById('textareaMensaje').classList.add('hidden');
            document.getElementById('contenidoMensaje').classList.remove('hidden');
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
    </script>
</x-app-layout>
