<x-app-layout>
    <div class="w-full px-4 sm:px-6 py-6">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">🗑️ Papelera de Reciclaje</h1>
            <p class="text-sm text-gray-600 mt-1">Gestiona y restaura elementos eliminados del sistema</p>
        </div>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @php
            $categorias = [
                'Producción' => [
                    'productos' => ['nombre' => 'Productos', 'icono' => '📦', 'campos' => ['codigo', 'obra.obra', 'productoBase.tipo']],
                    'planillas' => ['nombre' => 'Planillas', 'icono' => '📋', 'campos' => ['codigo', 'cliente.empresa', 'obra.obra']],
                    'etiquetas' => ['nombre' => 'Etiquetas', 'icono' => '🏷️', 'campos' => ['codigo', 'planilla.codigo']],
                    'paquetes' => ['nombre' => 'Paquetes', 'icono' => '📦', 'campos' => ['codigo', 'planilla.codigo', 'estado']],
                    'elementos' => ['nombre' => 'Elementos', 'icono' => '🔧', 'campos' => ['id', 'etiqueta.codigo', 'dimensiones']],
                ],
                'Pedidos y Compras' => [
                    'pedidos' => ['nombre' => 'Pedidos', 'icono' => '🛒', 'campos' => ['codigo', 'fabricante.nombre']],
                    'pedidos_globales' => ['nombre' => 'Pedidos Globales', 'icono' => '🌐', 'campos' => ['codigo']],
                ],
                'Logística' => [
                    'movimientos' => ['nombre' => 'Movimientos', 'icono' => '🚚', 'campos' => ['tipo', 'fecha']],
                    'entradas' => ['nombre' => 'Entradas', 'icono' => '📥', 'campos' => ['codigo', 'fecha']],
                    'salidas' => ['nombre' => 'Salidas', 'icono' => '📤', 'campos' => ['codigo_salida', 'codigo_sage', 'fecha_salida']],
                    'camiones' => ['nombre' => 'Camiones', 'icono' => '🚛', 'campos' => ['modelo', 'capacidad', 'empresaTransporte.nombre']],
                    'empresas_transporte' => ['nombre' => 'Empresas Transporte', 'icono' => '🏢', 'campos' => ['nombre', 'telefono', 'email']],
                ],
                'Recursos Humanos' => [
                    'asignaciones_turnos' => ['nombre' => 'Asignaciones Turno', 'icono' => '⏰', 'campos' => ['user.nombre_completo', 'turno.nombre', 'maquina.nombre', 'fecha']],
                    'users' => ['nombre' => 'Usuarios', 'icono' => '👤', 'campos' => ['nombre_completo', 'email', 'rol']],
                    'turnos' => ['nombre' => 'Turnos', 'icono' => '🕐', 'campos' => ['nombre', 'hora_entrada', 'hora_salida']],
                ],
                'Maestros' => [
                    'clientes' => ['nombre' => 'Clientes', 'icono' => '👥', 'campos' => ['empresa', 'telefono', 'email']],
                    'obras' => ['nombre' => 'Obras', 'icono' => '🏗️', 'campos' => ['obra', 'direccion']],
                    'maquinas' => ['nombre' => 'Máquinas', 'icono' => '⚙️', 'campos' => ['codigo', 'nombre', 'tipo', 'estado']],
                    'productos_base' => ['nombre' => 'Productos Base', 'icono' => '📊', 'campos' => ['tipo', 'diametro', 'longitud']],
                    'ubicaciones' => ['nombre' => 'Ubicaciones', 'icono' => '📍', 'campos' => ['codigo', 'nombre']],
                    'distribuidores' => ['nombre' => 'Distribuidores', 'icono' => '🚚', 'campos' => ['nombre', 'telefono']],
                    'fabricantes' => ['nombre' => 'Fabricantes', 'icono' => '🏭', 'campos' => ['nombre', 'telefono', 'email']],
                    'localizaciones' => ['nombre' => 'Localizaciones', 'icono' => '🗺️', 'campos' => ['nombre']],
                ],
                'Sistema' => [
                    'alertas' => ['nombre' => 'Alertas', 'icono' => '🔔', 'campos' => ['tipo', 'mensaje']],
                    'departamentos' => ['nombre' => 'Departamentos', 'icono' => '🏛️', 'campos' => ['nombre']],
                    'secciones' => ['nombre' => 'Secciones', 'icono' => '📑', 'campos' => ['nombre', 'ruta']],
                ],
            ];
        @endphp

        <div class="space-y-6">
            @foreach ($categorias as $nombreCategoria => $tablas)
                @php
                    $tieneRegistros = false;
                    foreach ($tablas as $key => $info) {
                        if ($deletedData[$key]->count() > 0) {
                            $tieneRegistros = true;
                            break;
                        }
                    }
                @endphp

                @if ($tieneRegistros)
                    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-800 to-gray-900 px-6 py-4">
                            <h2 class="text-xl font-bold text-white">{{ $nombreCategoria }}</h2>
                        </div>

                        <div class="p-6 space-y-6">
                            @foreach ($tablas as $key => $info)
                                @if ($deletedData[$key]->count() > 0)
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                {{ $info['icono'] }} {{ $info['nombre'] }}
                                                <span
                                                    class="ml-2 px-3 py-1 bg-red-100 text-red-700 text-sm rounded-full">
                                                    {{ $deletedData[$key]->count() }}
                                                </span>
                                            </h3>
                                        </div>

                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th
                                                            class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                                            ID
                                                        </th>
                                                        @foreach ($info['campos'] as $campo)
                                                            <th
                                                                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                                                {{ ucfirst(str_replace('.', ' ', str_replace('_', ' ', $campo))) }}
                                                            </th>
                                                        @endforeach
                                                        <th
                                                            class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                                            Eliminado
                                                        </th>
                                                        <th
                                                            class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">
                                                            Acciones
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach ($deletedData[$key] as $registro)
                                                        <tr class="hover:bg-gray-50 transition">
                                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                                {{ $registro->id }}
                                                            </td>
                                                            @foreach ($info['campos'] as $campo)
                                                                <td class="px-4 py-3 text-sm text-gray-700">
                                                                    @php
                                                                        $valor = $registro;
                                                                        foreach (explode('.', $campo) as $part) {
                                                                            $valor = $valor->{$part} ?? 'N/A';
                                                                            if ($valor === 'N/A') {
                                                                                break;
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    {{ $valor ?? 'N/A' }}
                                                                </td>
                                                            @endforeach
                                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                                {{ $registro->deleted_at->format('d/m/Y H:i') }}
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <form
                                                                    action="{{ route('papelera.restore', ['model' => $key, 'id' => $registro->id]) }}"
                                                                    method="POST" class="inline">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <button type="submit"
                                                                        class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition duration-150"
                                                                        onclick="return confirm('¿Estás seguro de que deseas restaurar este registro?')">
                                                                        <svg class="w-4 h-4 mr-1" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                                            </path>
                                                                        </svg>
                                                                        Restaurar
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @php
            $totalEliminados = 0;
            foreach ($deletedData as $registros) {
                $totalEliminados += $registros->count();
            }
        @endphp

        @if ($totalEliminados === 0)
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-12 text-center">
                <div class="text-6xl mb-4">🗑️</div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">La papelera está vacía</h3>
                <p class="text-gray-500">No hay registros eliminados para mostrar</p>
            </div>
        @endif
    </div>
</x-app-layout>
