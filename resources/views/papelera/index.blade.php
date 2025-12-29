<x-app-layout>
    <div class="w-full px-4 sm:px-6 py-6">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Papelera de Reciclaje</h1>
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
                    'productos' => [
                        'nombre' => 'Productos',
                        'icono' => '📦',
                        'campos' => ['codigo', 'obra.obra', 'productoBase.tipo'],
                        'modelo' => \App\Models\Producto::class,
                        'relaciones' => ['obra', 'productoBase'],
                    ],
                    'planillas' => [
                        'nombre' => 'Planillas',
                        'icono' => '📋',
                        'campos' => ['codigo', 'cliente.empresa', 'obra.obra'],
                        'modelo' => \App\Models\Planilla::class,
                        'relaciones' => ['cliente', 'obra'],
                    ],
                    'etiquetas' => [
                        'nombre' => 'Etiquetas',
                        'icono' => '🏷️',
                        'campos' => ['codigo', 'planilla.codigo'],
                        'modelo' => \App\Models\Etiqueta::class,
                        'relaciones' => ['planilla'],
                    ],
                    'paquetes' => [
                        'nombre' => 'Paquetes',
                        'icono' => '📦',
                        'campos' => ['codigo', 'planilla.codigo', 'estado'],
                        'modelo' => \App\Models\Paquete::class,
                        'relaciones' => ['planilla'],
                    ],
                    'elementos' => [
                        'nombre' => 'Elementos',
                        'icono' => '🔧',
                        'campos' => ['id', 'etiquetaRelacion.codigo', 'dimensiones'],
                        'modelo' => \App\Models\Elemento::class,
                        'relaciones' => ['etiquetaRelacion'],
                    ],
                ],
                'Pedidos y Compras' => [
                    'pedidos' => [
                        'nombre' => 'Pedidos',
                        'icono' => '🛒',
                        'campos' => ['codigo', 'fabricante.nombre'],
                        'modelo' => \App\Models\Pedido::class,
                        'relaciones' => ['fabricante'],
                    ],
                    'pedidos_globales' => [
                        'nombre' => 'Pedidos Globales',
                        'icono' => '🌐',
                        'campos' => ['codigo'],
                        'modelo' => \App\Models\PedidoGlobal::class,
                        'relaciones' => [],
                    ],
                ],
                'Logística' => [
                    'movimientos' => [
                        'nombre' => 'Movimientos',
                        'icono' => '🚚',
                        'campos' => ['tipo', 'descripcion'],
                        'modelo' => \App\Models\Movimiento::class,
                        'relaciones' => [],
                    ],
                    'entradas' => [
                        'nombre' => 'Entradas',
                        'icono' => '📥',
                        'campos' => ['codigo', 'fecha'],
                        'modelo' => \App\Models\Entrada::class,
                        'relaciones' => [],
                    ],
                    'salidas' => [
                        'nombre' => 'Salidas',
                        'icono' => '📤',
                        'campos' => ['codigo_salida', 'codigo_sage', 'fecha_salida'],
                        'modelo' => \App\Models\Salida::class,
                        'relaciones' => [],
                    ],
                    'camiones' => [
                        'nombre' => 'Camiones',
                        'icono' => '🚛',
                        'campos' => ['modelo', 'capacidad', 'empresaTransporte.nombre'],
                        'modelo' => \App\Models\Camion::class,
                        'relaciones' => ['empresaTransporte'],
                    ],
                    'empresas_transporte' => [
                        'nombre' => 'Empresas Transporte',
                        'icono' => '🏢',
                        'campos' => ['nombre', 'telefono', 'email'],
                        'modelo' => \App\Models\EmpresaTransporte::class,
                        'relaciones' => [],
                    ],
                ],
                'Recursos Humanos' => [
                    'asignaciones_turnos' => [
                        'nombre' => 'Asignaciones Turno',
                        'icono' => '⏰',
                        'campos' => ['user.nombre_completo', 'turno.nombre', 'maquina.nombre', 'fecha'],
                        'modelo' => \App\Models\AsignacionTurno::class,
                        'relaciones' => ['user', 'turno', 'maquina'],
                    ],
                    'users' => [
                        'nombre' => 'Usuarios',
                        'icono' => '👤',
                        'campos' => ['nombre_completo', 'email', 'rol'],
                        'modelo' => \App\Models\User::class,
                        'relaciones' => [],
                    ],
                    'turnos' => [
                        'nombre' => 'Turnos',
                        'icono' => '🕐',
                        'campos' => ['nombre', 'hora_entrada', 'hora_salida'],
                        'modelo' => \App\Models\Turno::class,
                        'relaciones' => [],
                    ],
                ],
                'Maestros' => [
                    'clientes' => [
                        'nombre' => 'Clientes',
                        'icono' => '👥',
                        'campos' => ['empresa', 'telefono', 'email'],
                        'modelo' => \App\Models\Cliente::class,
                        'relaciones' => [],
                    ],
                    'obras' => [
                        'nombre' => 'Obras',
                        'icono' => '🏗️',
                        'campos' => ['obra', 'direccion'],
                        'modelo' => \App\Models\Obra::class,
                        'relaciones' => [],
                    ],
                    'maquinas' => [
                        'nombre' => 'Máquinas',
                        'icono' => '⚙️',
                        'campos' => ['codigo', 'nombre', 'tipo', 'estado'],
                        'modelo' => \App\Models\Maquina::class,
                        'relaciones' => [],
                    ],
                    'productos_base' => [
                        'nombre' => 'Productos Base',
                        'icono' => '📊',
                        'campos' => ['tipo', 'diametro', 'longitud'],
                        'modelo' => \App\Models\ProductoBase::class,
                        'relaciones' => [],
                    ],
                    'ubicaciones' => [
                        'nombre' => 'Ubicaciones',
                        'icono' => '📍',
                        'campos' => ['codigo', 'nombre'],
                        'modelo' => \App\Models\Ubicacion::class,
                        'relaciones' => [],
                    ],
                    'distribuidores' => [
                        'nombre' => 'Distribuidores',
                        'icono' => '🚚',
                        'campos' => ['nombre', 'telefono'],
                        'modelo' => \App\Models\Distribuidor::class,
                        'relaciones' => [],
                    ],
                    'fabricantes' => [
                        'nombre' => 'Fabricantes',
                        'icono' => '🏭',
                        'campos' => ['nombre', 'telefono', 'email'],
                        'modelo' => \App\Models\Fabricante::class,
                        'relaciones' => [],
                    ],
                    'localizaciones' => [
                        'nombre' => 'Localizaciones',
                        'icono' => '🗺️',
                        'campos' => ['nombre'],
                        'modelo' => \App\Models\Localizacion::class,
                        'relaciones' => [],
                    ],
                ],
                'Sistema' => [
                    'alertas' => [
                        'nombre' => 'Alertas',
                        'icono' => '🔔',
                        'campos' => ['tipo', 'mensaje'],
                        'modelo' => \App\Models\Alerta::class,
                        'relaciones' => [],
                    ],
                    'departamentos' => [
                        'nombre' => 'Departamentos',
                        'icono' => '🏛️',
                        'campos' => ['nombre'],
                        'modelo' => \App\Models\Departamento::class,
                        'relaciones' => [],
                    ],
                    'secciones' => [
                        'nombre' => 'Secciones',
                        'icono' => '📑',
                        'campos' => ['nombre', 'ruta'],
                        'modelo' => \App\Models\Seccion::class,
                        'relaciones' => [],
                    ],
                ],
            ];
        @endphp

        <div class="space-y-6">
            @foreach ($categorias as $nombreCategoria => $tablas)
                @php
                    $tieneRegistros = false;
                    foreach ($tablas as $key => $info) {
                        if ($info['modelo']::onlyTrashed()->exists()) {
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
                                <livewire:papelera-table
                                    :wire:key="'papelera-' . $key"
                                    :model-key="$key"
                                    :model-class="$info['modelo']"
                                    :nombre="$info['nombre']"
                                    :icono="$info['icono']"
                                    :campos="$info['campos']"
                                    :relaciones="$info['relaciones']"
                                />
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @php
            $totalEliminados = 0;
            foreach ($categorias as $tablas) {
                foreach ($tablas as $info) {
                    $totalEliminados += $info['modelo']::onlyTrashed()->count();
                }
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
