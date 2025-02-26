<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Alertas') }}
        </h2>
    </x-slot>

    <!-- Mensajes de Error y Éxito -->
    <div class="w-full px-6 py-4">
        <!-- Alertas no leídas -->
        @if ($alertasNoLeidas->isNotEmpty())
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-800 p-4 rounded-lg shadow">
                <h3 class="text-md font-semibold">📢 Alertas No Leídas</h3>
                <ul class="mt-2">
                    @foreach ($alertasNoLeidas as $alerta)
                        <li class="py-1 border-b last:border-b-0">
                            <span class="font-bold">{{ $alerta->created_at->format('d/m/Y H:i') }}:</span>
                            {{ $alerta->mensaje }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- Botón para mostrar filtros avanzados -->
        <div class="mb-4 flex items-center space-x-4">
            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosBusqueda">
                🔍 Filtros Avanzados
            </button>
        </div>

        <!-- FORMULARIO DE FILTROS -->
        <div id="filtrosBusqueda" class="collapse mb-4">
            <form method="GET" action="{{ route('alertas.index') }}" class="card card-body shadow-sm">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="per_page" class="font-semibold">Registros a mostrar:</label>
                        <select name="per_page" id="per_page" class="form-control">
                            <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_inicio" class="font-semibold">Desde:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
                            value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_fin" class="font-semibold">Hasta:</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                            value="{{ request('fecha_fin') }}">
                    </div>
                    @if (auth()->user()->categoria == 'administrador')
                        <div class="col-md-2">
                            <label for="destinatario" class="font-semibold">Filtrar por destinatario:</label>
                            <select name="destinatario" id="destinatario" class="form-control">
                                <option value="">-- Filtrar por destinatario --</option>
                                <option value="todos">Todos</option>
                                <option value="administracion"
                                    {{ request('destinatario') == 'administracion' ? 'selected' : '' }}>Administración
                                </option>
                                <option value="mecanico" {{ request('destinatario') == 'mecanico' ? 'selected' : '' }}>
                                    Mecánico</option>
                                <option value="desarrollador"
                                    {{ request('destinatario') == 'desarrollador' ? 'selected' : '' }}>Desarrollador
                                </option>
                            </select>
                        </div>
                    @endif
                    <!-- Botones -->
                    <div class="col-12 d-flex justify-content-between">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('alertas.index') }}" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Resetear Filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <!-- Tabla de Alertas -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="py-3 border text-center">ID
                            <form method="GET" action="{{ route('alertas.index') }}" class="mt-2">
                                <input type="text" name="alerta_id"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Usuario 1
                            <form method="GET" action="{{ route('alertas.index') }}" class="mt-2">
                                <input type="text" name="usuario1"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Usuario 2
                            <form method="GET" action="{{ route('alertas.index') }}" class="mt-2">
                                <input type="text" name="usuario2"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 border text-center">Destinatario
                        </th>
                        <th class="py-3 border text-center">Mensaje
                            <form method="GET" action="{{ route('alertas.index') }}" class="mt-2">
                                <input type="text" name="mensaje"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="py-3 px-2 border text-center">Fecha</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($alertas as $alerta)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                            <td class="px-2 py-3 text-center border">{{ $alerta->id }}</td>
                            <td class="px-2 py-3 text-center border">{{ $alerta->usuario1->name ?? 'N/A' }}</td>
                            <td class="px-2 py-3 text-center border">{{ $alerta->usuario2->name ?? 'N/A' }}</td>
                            <td class="px-2 py-3 text-center border">{{ ucfirst($alerta->destinatario) }}</td>
                            <td class="px-2 py-3 text-center border">{{ $alerta->mensaje }}</td>
                            <td class="px-2 py-3 text-center border">{{ $alerta->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">No hay alertas registradas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $alertas->links() }}
        </div>
    </div>
</x-app-layout>
