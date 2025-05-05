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

                            <th class="py-3 border text-center">Mensaje</th>
                            <th class="py-3 border text-center">Fecha</th>
                            <th class="py-3 border text-center">Tipo</th>
                            <th class="py-3 border text-center">Completada</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse ($alertas as $alerta)
                            <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">


                                @php

                                    // Extraer diámetro
                                    preg_match('/diámetro (\d+(\.\d+)?)/i', $alerta->mensaje, $matchDiametro);
                                    $diametro = $matchDiametro[1] ?? null;

                                    // Extraer ID de máquina desde la URL
                                    preg_match('/maquinas\/(\d+)/i', $alerta->mensaje, $matchMaquina);
                                    $maquinaId = $matchMaquina[1] ?? null;

                                    // Obtener tipo de material desde la base de datos
                                    $tipoMaterial = null;
                                    if ($maquinaId) {
                                        $maquina = \App\Models\Maquina::find($maquinaId);
                                        $tipoMaterial = $maquina->tipo_material ?? null;
                                    }

                                    // Construir la URL con filtros
                                    $url =
                                        $diametro && $tipoMaterial
                                            ? route('productos.index', [
                                                'diametro' => $diametro,
                                                'estado' => 'almacenado',
                                                'tipo' => $tipoMaterial,
                                            ])
                                            : null;
                                @endphp


                                <td class="px-2 py-3 text-center border">
                                    @if ($url)
                                        <a href="{{ $url }}" class="text-blue-600 hover:underline">
                                            {{ $alerta->mensaje }}
                                        </a>
                                    @else
                                        {{ $alerta->mensaje }}
                                    @endif
                                </td>


                                <td class="px-2 py-3 text-center border">{{ $alerta->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    @if ($alerta->user_id_1 == $user->id)
                                        <span class="text-red-600 font-bold">⬆ Saliente</span>
                                    @else
                                        <span class="text-green-600 font-bold">⬇ Entrante</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-center border">
                                    @if ($alerta->completada)
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
</x-app-layout>
