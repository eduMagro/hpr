<x-app-layout>
    <style>
        .ia-spinner {
            width: 16px;
            height: 16px;
            border: 3px solid #e5e7eb;
            border-top-color: #4f46e5;
            border-radius: 9999px;
            animation: ia-spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes ia-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .simulacion-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
    </style>
    <x-slot name="title">Revisión asistida de albaranes</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="bg-white shadow rounded-xl p-6 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Revisión asistida de albaranes</h1>
                    <p class="text-sm text-gray-600 mt-1">Sube una imagen del albarán para ver qué línea de pedido se
                        activaría y qué bultos se crearían</p>
                </div>
            </div>

            <form action="{{ route('openai.procesar') }}" method="POST" enctype="multipart/form-data" id="ocrForm"
                class="mt-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="text-sm text-gray-700">Proveedor
                        <select name="proveedor" id="proveedor" required
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">Selecciona proveedor</option>
                            <option value="siderurgica">Siderúrgica Sevillana (SISE)</option>
                            <option value="megasa">Megasa</option>
                            <option value="balboa">Balboa</option>
                            <option value="otro">Otro / No listado</option>
                        </select>
                    </label>
                </div>
                <div id="dropZone"
                    class="border-2 border-dashed border-indigo-200 bg-indigo-50/40 rounded-xl p-6 text-center transition hover:border-indigo-400 hover:bg-indigo-50">
                    <div class="flex flex-col items-center gap-3">
                        <div class="relative">
                            <input type="file" name="imagenes[]" id="imagenes" accept="image/*,application/pdf"
                                multiple class="hidden" onchange="handleFileSelect(event)">
                            <label for="imagenes"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700 cursor-pointer transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 8l-3-3m3 3l3-3" />
                                </svg>
                                Seleccionar archivos
                            </label>
                        </div>
                        <p class="text-sm text-gray-600">o arrastra aquí tus archivos</p>
                        <div id="fileList" class="w-full text-left text-sm text-gray-700 space-y-1"></div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" id="processBtn" disabled
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Procesar albarán
                    </button>
                    <div id="loading" class="hidden flex items-center gap-2 text-sm text-indigo-600">
                        <div class="ia-spinner"></div>
                        Procesando...
                    </div>
                </div>
            </form>
        </div>

        @if (isset($resultados) && count($resultados) > 0)
            @foreach ($resultados as $idx => $resultado)
                <div class="bg-white shadow rounded-xl border border-gray-100 overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <h2 class="text-lg font-semibold text-gray-900">{{ $resultado['nombre_archivo'] }}</h2>
                                @if ($resultado['error'])
                                    <p class="text-sm text-red-600 mt-1">{{ $resultado['error'] }}</p>
                                @else
                                    @php
                                        $sim = $resultado['simulacion'] ?? [];
                                        $parsed = $resultado['parsed'] ?? [];
                                    @endphp
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span class="simulacion-badge badge-info">
                                            🏭 {{ $sim['fabricante'] ?? 'Fabricante desconocido' }}
                                        </span>
                                        <span class="simulacion-badge badge-warning">
                                            📦 {{ $sim['bultos_albaran'] ?? 0 }} bultos
                                        </span>
                                        @if ($sim['peso_total'])
                                            <span class="simulacion-badge badge-info">
                                                ⚖️ {{ number_format($sim['peso_total'], 0, ',', '.') }} kg
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if ($resultado['preview'])
                                <div class="flex-shrink-0 w-32 h-32">
                                    <img src="{{ $resultado['preview'] }}" alt="{{ $resultado['nombre_archivo'] }}"
                                        class="rounded shadow-sm w-full h-full object-cover border border-gray-200">
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!$resultado['error'] && isset($sim))
                        <div id="confirmationPrompt-{{ $idx }}" class="px-6 pt-6 pb-4 space-y-4">
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Datos extraídos por IA</h3>
                                        <p class="text-xs text-gray-500">Revisa la información escaneada del albarán.</p>
                                    </div>
                                </div>

                                <!-- Vista de datos (modo lectura) -->
                                <div id="viewMode-{{ $idx }}" class="space-y-3">
                                    <!-- Datos generales del albarán -->
                                    <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-gray-700">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Albarán</span>
                                            <span class="font-semibold extracted-value" data-field="albaran">{{ $resultado['parsed']['albaran'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Fecha</span>
                                            <span class="font-semibold extracted-value" data-field="fecha">{{ $resultado['parsed']['fecha'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Pedido cliente</span>
                                            <span class="font-semibold extracted-value" data-field="pedido_cliente">{{ $resultado['parsed']['pedido_cliente'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Pedido código</span>
                                            <span class="font-semibold extracted-value" data-field="pedido_codigo">{{ $resultado['parsed']['pedido_codigo'] ?? '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Peso total (kg)</span>
                                            <span class="font-semibold extracted-value" data-field="peso_total">{{ isset($resultado['parsed']['peso_total']) ? number_format($resultado['parsed']['peso_total'], 2, ',', '.') : '—' }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-500">Bultos total</span>
                                            <span class="font-semibold extracted-value" data-field="bultos_total">{{ $resultado['parsed']['bultos_total'] ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <!-- Productos -->
                                    @php
                                        $productos = $resultado['parsed']['productos'] ?? [];
                                    @endphp
                                    @if(count($productos) > 0)
                                        <div class="mt-4">
                                            <h4 class="text-xs font-semibold text-gray-700 mb-2">Productos escaneados:</h4>
                                            <div class="space-y-2">
                                                @foreach($productos as $prodIdx => $producto)
                                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                                            <div>
                                                                <span class="text-gray-500">Descripción:</span>
                                                                <span class="font-semibold ml-1">{{ $producto['descripcion'] ?? '—' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-500">Diámetro:</span>
                                                                <span class="font-semibold ml-1">{{ $producto['diametro'] ?? '—' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-500">Longitud:</span>
                                                                <span class="font-semibold ml-1">{{ $producto['longitud'] ?? '—' }}</span>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-500">Calidad:</span>
                                                                <span class="font-semibold ml-1">{{ $producto['calidad'] ?? '—' }}</span>
                                                            </div>
                                                        </div>
                                                        @if(isset($producto['line_items']) && count($producto['line_items']) > 0)
                                                            <div class="mt-2 text-xs">
                                                                <span class="text-gray-500">Coladas:</span>
                                                                <div class="mt-1 flex flex-wrap gap-1">
                                                                    @foreach($producto['line_items'] as $item)
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-700">
                                                                            {{ $item['colada'] ?? '?' }}
                                                                            <span class="ml-1 text-blue-600">({{ $item['bultos'] ?? 1 }})</span>
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Botones de acción -->
                                    <div class="flex gap-3 mt-4 pt-4 border-t border-gray-200">
                                        <button type="button" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg font-semibold text-sm hover:bg-emerald-700 transition confirm-scanned" data-result="{{ $idx }}">
                                            ✓ Continuar con lo escaneado
                                        </button>
                                        <button type="button" class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg font-semibold text-sm hover:bg-amber-700 transition modify-scanned" data-result="{{ $idx }}">
                                            ✎ Modificar lo escaneado
                                        </button>
                                    </div>
                                </div>

                                <!-- Vista de edición (modo edición) -->
                                <div id="editMode-{{ $idx }}" class="hidden space-y-4">
                                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                        <p class="text-xs text-amber-800">
                                            <strong>Modo edición:</strong> Modifica, agrega o elimina datos según sea necesario.
                                        </p>
                                    </div>

                                    <!-- Datos generales editables -->
                                    <div>
                                        <h5 class="text-xs font-semibold text-gray-700 mb-2">Datos generales del albarán</h5>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Albarán
                                                <input type="text" class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-field="albaran"
                                                    value="{{ $resultado['parsed']['albaran'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Fecha
                                                <input type="date" class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-field="fecha"
                                                    value="{{ $resultado['parsed']['fecha'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Pedido cliente
                                                <input type="text" class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-field="pedido_cliente"
                                                    value="{{ $resultado['parsed']['pedido_cliente'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Pedido código
                                                <input type="text" class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-field="pedido_codigo"
                                                    value="{{ $resultado['parsed']['pedido_codigo'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Peso total (kg)
                                                <input type="number" step="0.01" class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-field="peso_total"
                                                    value="{{ $resultado['parsed']['peso_total'] ?? '' }}">
                                            </label>
                                            <label class="text-xs text-gray-700 font-medium flex flex-col gap-1">
                                                Bultos total
                                                <input type="number" class="general-edit-field rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-field="bultos_total"
                                                    value="{{ $resultado['parsed']['bultos_total'] ?? '' }}">
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Productos editables -->
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <h5 class="text-xs font-semibold text-gray-700">Productos</h5>
                                            <button type="button" class="add-producto-btn text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition" data-result="{{ $idx }}">
                                                + Agregar producto
                                            </button>
                                        </div>
                                        <div id="productosContainer-{{ $idx }}" class="space-y-3">
                                            @foreach(($resultado['parsed']['productos'] ?? []) as $prodIdx => $producto)
                                                <div class="producto-edit-block bg-gray-50 border border-gray-300 rounded-lg p-3" data-producto-index="{{ $prodIdx }}">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs font-semibold text-gray-700">Producto {{ $prodIdx + 1 }}</span>
                                                        <button type="button" class="remove-producto-btn text-xs px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Descripción
                                                            <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="descripcion"
                                                                value="{{ $producto['descripcion'] ?? '' }}">
                                                        </label>
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Diámetro
                                                            <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="diametro"
                                                                value="{{ $producto['diametro'] ?? '' }}">
                                                        </label>
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Longitud
                                                            <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="longitud"
                                                                value="{{ $producto['longitud'] ?? '' }}">
                                                        </label>
                                                        <label class="text-xs text-gray-600 flex flex-col gap-1">
                                                            Calidad
                                                            <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="calidad"
                                                                value="{{ $producto['calidad'] ?? '' }}">
                                                        </label>
                                                    </div>

                                                    <!-- Coladas del producto -->
                                                    <div>
                                                        <div class="flex items-center justify-between mb-1">
                                                            <span class="text-xs font-medium text-gray-600">Coladas</span>
                                                            <button type="button" class="add-colada-btn text-xs px-2 py-0.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                                                + Agregar colada
                                                            </button>
                                                        </div>
                                                        <div class="coladas-container space-y-1">
                                                            @foreach(($producto['line_items'] ?? []) as $coladaIdx => $colada)
                                                                <div class="colada-edit-row flex gap-2 items-center" data-colada-index="{{ $coladaIdx }}">
                                                                    <input type="text" placeholder="Colada" class="colada-field flex-1 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="colada"
                                                                        value="{{ $colada['colada'] ?? '' }}">
                                                                    <input type="number" placeholder="Bultos" class="colada-field w-20 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="bultos"
                                                                        value="{{ $colada['bultos'] ?? '' }}">
                                                                    <input type="number" step="0.01" placeholder="Peso (kg)" class="colada-field w-24 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="peso_kg"
                                                                        value="{{ $colada['peso_kg'] ?? '' }}">
                                                                    <button type="button" class="remove-colada-btn text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                                                        ✕
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-3 border-t border-gray-300">
                                        <button type="button" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold text-sm hover:bg-blue-700 transition confirm-edit" data-result="{{ $idx }}">
                                            Confirmar y seguir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="simulationSection-{{ $idx }}" class="p-6 space-y-6 hidden">
                            <!-- Simulación: Líneas Pendientes -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 mb-3">
                                    📋 Líneas de pedido pendientes para este proveedor
                                </h3>
                                @if ($sim['hay_coincidencias'])
                                    <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100 text-gray-700">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-medium">Línea</th>
                                                        <th class="px-4 py-2 text-left font-medium">Pedido</th>
                                                        <th class="px-4 py-2 text-left font-medium">Producto</th>
                                                        <th class="px-4 py-2 text-left font-medium">Obra</th>
                                                        <th class="px-4 py-2 text-center font-medium">Pendiente</th>
                                                        <th class="px-4 py-2 text-left font-medium">Estado</th>
                                                        <th class="px-4 py-2 text-center font-medium">Score</th>
                                                        <th class="px-4 py-2 text-left font-medium">Creada</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach ($sim['lineas_pendientes'] as $linea)
                                                        <tr
                                                            class="hover:bg-gray-50 {{ $loop->first ? 'bg-green-50' : '' }}">
                                                            <td class="px-4 py-3 font-medium text-gray-900">
                                                                #{{ $linea['id'] }}
                                                                @if ($loop->first)
                                                                    <span
                                                                        class="ml-2 text-xs bg-green-600 text-white px-2 py-0.5 rounded-full">PROPUESTA</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-700">
                                                                {{ $linea['pedido_codigo'] }}</td>
                                                            <td class="px-4 py-3 text-gray-700">
                                                                {{ $linea['producto'] }}</td>
                                                            <td class="px-4 py-3 text-gray-700">{{ $linea['obra'] }}
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                {{ number_format($linea['cantidad_pendiente'], 0, ',', '.') }} /
                                                                {{ number_format($linea['cantidad'], 0, ',', '.') }} kg
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span
                                                                    class="text-xs px-2 py-1 rounded-full {{ $linea['estado'] === 'activo' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                                                    {{ ucfirst($linea['estado']) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <span class="font-bold {{ $linea['es_viable'] ? 'text-green-600' : 'text-red-600' }}">
                                                                    {{ $linea['score'] }}
                                                                </span>
                                                                @if (count($linea['razones']) > 0 || count($linea['incompatibilidades']) > 0)
                                                                    <button type="button"
                                                                        onclick="this.nextElementSibling.classList.toggle('hidden')"
                                                                        class="ml-1 text-xs text-blue-600 hover:text-blue-800">
                                                                        ℹ️
                                                                    </button>
                                                                    <div class="hidden absolute z-10 bg-white border border-gray-300 rounded-lg shadow-lg p-3 text-xs max-w-xs">
                                                                        @if (count($linea['razones']) > 0)
                                                                            <div class="mb-2">
                                                                                <strong class="text-green-700">Razones:</strong>
                                                                                <ul class="list-disc list-inside text-green-600">
                                                                                    @foreach ($linea['razones'] as $razon)
                                                                                        <li>{{ $razon }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </div>
                                                                        @endif
                                                                        @if (count($linea['incompatibilidades']) > 0)
                                                                            <div>
                                                                                <strong class="text-red-700">Incompatibilidades:</strong>
                                                                                <ul class="list-disc list-inside text-red-600">
                                                                                    @foreach ($linea['incompatibilidades'] as $incomp)
                                                                                        <li>{{ $incomp }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-600">
                                                                {{ $linea['fecha_creacion'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    @if ($sim['linea_propuesta'])
                                        <div class="mt-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <p class="text-sm font-semibold text-green-900">
                                                        ✓ Línea propuesta: #{{ $sim['linea_propuesta']['id'] }}
                                                    </p>
                                                    <p class="text-xs text-green-700 mt-1">
                                                        <strong>Pedido:</strong> {{ $sim['linea_propuesta']['pedido_codigo'] }} |
                                                        <strong>Fabricante:</strong> {{ $sim['linea_propuesta']['fabricante'] }}
                                                    </p>
                                                    <p class="text-xs text-green-700">
                                                        <strong>Producto:</strong> {{ $sim['linea_propuesta']['producto'] }} |
                                                        <strong>Obra:</strong> {{ $sim['linea_propuesta']['obra'] }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-2xl font-bold text-green-700">{{ $sim['linea_propuesta']['score'] }}</span>
                                                    <p class="text-xs text-green-600">puntos</p>
                                                </div>
                                            </div>
                                            @if (count($sim['linea_propuesta']['razones']) > 0)
                                                <div class="mt-2 pt-2 border-t border-green-200">
                                                    <p class="text-xs font-semibold text-green-800 mb-1">Razones:</p>
                                                    <ul class="text-xs text-green-700 space-y-1 pl-4">
                                                        @foreach ($sim['linea_propuesta']['razones'] as $razon)
                                                            <li>{{ $razon }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            @if (count($sim['linea_propuesta']['incompatibilidades']) > 0)
                                                <div class="mt-2 pt-2 border-t border-green-200">
                                                    <p class="text-xs font-semibold text-red-700 mb-1">Advertencias:</p>
                                                    <ul class="text-xs text-red-700 space-y-1 pl-4">
                                                        @foreach ($sim['linea_propuesta']['incompatibilidades'] as $incomp)
                                                            <li>{{ $incomp }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <p class="text-sm text-yellow-800">
                                            ⚠️ No se encontraron líneas de pedido pendientes para este
                                            proveedor/producto
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Listado completo de TODOS los pedidos pendientes -->
                            @if (isset($sim['todas_las_lineas']) && count($sim['todas_las_lineas']) > 0)
                                <div class="border-t border-gray-200 pt-4">
                                    <details class="group">
                                        <summary class="cursor-pointer list-none">
                                            <div class="flex items-center justify-between p-3 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-5 h-5 text-gray-600 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="text-sm font-semibold text-gray-900">
                                                        Ver todos los pedidos pendientes ({{ count($sim['todas_las_lineas']) }})
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-600">Click para expandir</span>
                                            </div>
                                        </summary>
                                        <div class="mt-3 bg-white rounded-lg border border-gray-200 overflow-hidden">
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead class="bg-gray-100 text-gray-700">
                                                        <tr>
                                                            <th class="px-4 py-2 text-left font-medium">ID</th>
                                                            <th class="px-4 py-2 text-left font-medium">Pedido</th>
                                                            <th class="px-4 py-2 text-left font-medium">Fabricante</th>
                                                            <th class="px-4 py-2 text-left font-medium">Producto</th>
                                                            <th class="px-4 py-2 text-left font-medium">Obra</th>
                                                            <th class="px-4 py-2 text-center font-medium">Pendiente</th>
                                                            <th class="px-4 py-2 text-center font-medium">Score</th>
                                                            <th class="px-4 py-2 text-left font-medium">Estado</th>
                                                            <th class="px-4 py-2 text-center font-medium">Acción</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200">
                                                        @foreach ($sim['todas_las_lineas'] as $linea)
                                                            <tr class="hover:bg-gray-50 {{ $linea['coincide_diametro'] ? 'bg-green-50' : '' }}">
                                                                <td class="px-4 py-3 font-medium text-gray-900">
                                                                    #{{ $linea['id'] }}
                                                                    @if ($linea['coincide_diametro'])
                                                                        <span class="ml-1 text-xs text-green-600">✓</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-gray-700">{{ $linea['pedido_codigo'] }}</td>
                                                                <td class="px-4 py-3 text-gray-600 text-xs">{{ $linea['fabricante'] }}</td>
                                                                <td class="px-4 py-3 text-gray-700">{{ $linea['producto'] }}</td>
                                                                <td class="px-4 py-3 text-gray-700">{{ $linea['obra'] }}</td>
                                                                <td class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                    {{ number_format($linea['cantidad_pendiente'], 0, ',', '.') }} /
                                                                    {{ number_format($linea['cantidad'], 0, ',', '.') }} kg
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <span class="font-bold text-lg {{ $linea['score'] >= 50 ? 'text-green-600' : ($linea['score'] >= 30 ? 'text-yellow-600' : 'text-gray-600') }}">
                                                                        {{ $linea['score'] }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    <span class="text-xs px-2 py-1 rounded-full {{ $linea['estado'] === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700' }}">
                                                                        {{ ucfirst($linea['estado']) }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <button type="button"
                                                                            class="text-xs px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                                                            onclick="seleccionarLineaManual({{ $linea['id'] }}, '{{ $idx }}')">
                                                                        Seleccionar
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="p-3 bg-gray-50 border-t border-gray-200 text-xs text-gray-600">
                                                <span class="inline-flex items-center gap-1">
                                                    <span class="inline-block w-3 h-3 bg-green-50 border border-green-200 rounded"></span>
                                                    = Coincide con diámetro escaneado
                                                </span>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            @endif

                            <!-- Simulación: Productos a Crear -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 mb-3">
                                    📦 Coladas a recepcionar
                                </h3>
                                <p class="text-xs text-gray-600 mb-2">
                                    Selecciona las coladas que deseas recepcionar ahora. Puedes desmarcar las que NO quieras procesar en este momento.
                                </p>
                                @if (count($sim['bultos_simulados']) > 0)
                                    <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-sm">
                                                <thead class="bg-gray-100 text-gray-700">
                                                    <tr>
                                                        <th class="px-4 py-2 text-center font-medium">
                                                            <input type="checkbox"
                                                                   id="checkAll-{{ $idx }}"
                                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                                   checked
                                                                   onchange="document.querySelectorAll('.colada-checkbox-{{ $idx }}').forEach(cb => cb.checked = this.checked)">
                                                        </th>
                                                        <th class="px-4 py-2 text-left font-medium">Colada</th>
                                                        <th class="px-4 py-2 text-center font-medium">Bultos</th>
                                                        <th class="px-4 py-2 text-right font-medium">Peso (kg)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach ($sim['bultos_simulados'] as $bultoIdx => $bulto)
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-4 py-3 text-center">
                                                                <input type="checkbox"
                                                                       name="coladas_seleccionadas[{{ $idx }}][]"
                                                                       value="{{ $bultoIdx }}"
                                                                       class="colada-checkbox-{{ $idx }} rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                                       data-colada="{{ $bulto['colada'] }}"
                                                                       data-bultos="{{ $bulto['bultos'] ?? 1 }}"
                                                                       data-peso="{{ $bulto['peso_kg'] ?? 0 }}"
                                                                       checked>
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-700 font-mono font-semibold">
                                                                {{ $bulto['colada'] }}
                                                            </td>
                                                            <td class="px-4 py-3 text-center font-semibold text-gray-900">
                                                                {{ $bulto['bultos'] ?? 1 }}
                                                            </td>
                                                            <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                                                {{ $bulto['peso_kg'] ? number_format($bulto['peso_kg'], 0, ',', '.') : '—' }} kg
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                                                    <tr>
                                                        <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-700 text-right">
                                                            TOTALES SELECCIONADOS:
                                                        </td>
                                                        <td class="px-4 py-3 text-center text-sm font-bold text-blue-700" id="totalBultos-{{ $idx }}">
                                                            {{ collect($sim['bultos_simulados'])->sum('bultos') }}
                                                        </td>
                                                        <td class="px-4 py-3 text-right text-sm font-bold text-blue-700" id="totalPeso-{{ $idx }}">
                                                            {{ number_format(collect($sim['bultos_simulados'])->sum('peso_kg'), 0, ',', '.') }} kg
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600">No se detectaron productos en el albarán</p>
                                @endif
                            </div>

                            <!-- Simulación: Estado Final -->
                            @if ($sim['estado_final_simulado'])
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 mb-3">
                                        📊 Estado final simulado de la línea
                                    </h3>
                                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Cantidad recepcionada
                                                </p>
                                                <p class="text-2xl font-bold text-indigo-900">
                                                    {{ $sim['estado_final_simulado']['cantidad_recepcionada_nueva'] }}
                                                    <span class="text-sm text-indigo-600">/
                                                        {{ $sim['estado_final_simulado']['cantidad_total'] }}</span>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Progreso</p>
                                                <p class="text-2xl font-bold text-indigo-900">
                                                    {{ $sim['estado_final_simulado']['progreso'] }}%</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-indigo-600 font-medium">Nuevo estado</p>
                                                <p
                                                    class="text-lg font-bold {{ $sim['estado_final_simulado']['estado_nuevo'] === 'completado' ? 'text-green-600' : 'text-blue-600' }}">
                                                    {{ ucfirst($sim['estado_final_simulado']['estado_nuevo']) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Selector de línea alternativa -->
                            @if ($sim['hay_coincidencias'] && count($sim['lineas_pendientes']) > 1)
                                <div class="border-t border-gray-200 pt-4">
                                    <label class="text-sm font-medium text-gray-700">
                                        ¿Prefieres activar otra línea?
                                        <select class="mt-2 w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            @foreach ($sim['lineas_pendientes'] as $linea)
                                                <option value="{{ $linea['id'] }}"
                                                    {{ $loop->first ? 'selected' : '' }}>
                                                    Línea #{{ $linea['id'] }} - {{ $linea['producto'] }}
                                                    ({{ $linea['cantidad_pendiente'] }} kg pendientes - Score: {{ $linea['score'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            @endif

                            <!-- Aviso -->
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <p class="text-sm text-amber-800">
                                    <strong>⚠️ Esto es una simulación:</strong> No se ha modificado nada en la base de
                                    datos.
                                    Para aplicar estos cambios, deberás confirmar la acción en el sistema real.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <script>
        let selectedFiles = [];

        function handleFileSelect(event) {
            const files = Array.from(event.target.files);
            selectedFiles = files;
            displayFileList();
        }

        function displayFileList() {
            const fileList = document.getElementById('fileList');
            const processBtn = document.getElementById('processBtn');

            if (selectedFiles.length === 0) {
                fileList.innerHTML = '';
                processBtn.disabled = true;
                return;
            }

            processBtn.disabled = false;

            let html = '<div class="text-sm text-gray-700 font-semibold mb-1">Archivos seleccionados:</div>';
            selectedFiles.forEach((file) => {
                const sizeKB = (file.size / 1024).toFixed(1);
                html += `
                    <div class="flex items-center justify-between bg-white border border-gray-200 rounded-md px-3 py-2">
                        <span>${file.name}</span>
                        <span class="text-xs text-gray-500">${sizeKB} KB</span>
                    </div>
                `;
            });
            fileList.innerHTML = html;
        }

        const dropZone = document.getElementById('dropZone');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('ring-2', 'ring-indigo-300'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('ring-2', 'ring-indigo-300'),
                false);
        });
        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            document.getElementById('imagenes').files = dt.files;
            selectedFiles = Array.from(dt.files);
            displayFileList();
        }, false);

        document.getElementById('ocrForm').addEventListener('submit', (e) => {
            const proveedor = document.getElementById('proveedor').value;
            if (!proveedor) {
                e.preventDefault();
                alert('Selecciona un proveedor antes de procesar.');
                return;
            }
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('processBtn').disabled = true;
        });

        // Botón "Continuar con lo escaneado"
        document.querySelectorAll('.confirm-scanned').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = btn.dataset.result;
                document.getElementById(`confirmationPrompt-${idx}`).classList.add('hidden');
                document.getElementById(`simulationSection-${idx}`).classList.remove('hidden');
            });
        });

        // Botón "Modificar lo escaneado"
        document.querySelectorAll('.modify-scanned').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = btn.dataset.result;
                document.getElementById(`viewMode-${idx}`).classList.add('hidden');
                document.getElementById(`editMode-${idx}`).classList.remove('hidden');
            });
        });

        // Agregar producto
        document.querySelectorAll('.add-producto-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = btn.dataset.result;
                const container = document.getElementById(`productosContainer-${idx}`);
                const numProductos = container.querySelectorAll('.producto-edit-block').length;

                const newProductoHTML = `
                    <div class="producto-edit-block bg-gray-50 border border-gray-300 rounded-lg p-3" data-producto-index="${numProductos}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-700">Producto ${numProductos + 1}</span>
                            <button type="button" class="remove-producto-btn text-xs px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                Eliminar
                            </button>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                            <label class="text-xs text-gray-600 flex flex-col gap-1">
                                Descripción
                                <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="descripcion" value="">
                            </label>
                            <label class="text-xs text-gray-600 flex flex-col gap-1">
                                Diámetro
                                <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="diametro" value="">
                            </label>
                            <label class="text-xs text-gray-600 flex flex-col gap-1">
                                Longitud
                                <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="longitud" value="">
                            </label>
                            <label class="text-xs text-gray-600 flex flex-col gap-1">
                                Calidad
                                <input type="text" class="producto-field rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="calidad" value="">
                            </label>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium text-gray-600">Coladas</span>
                                <button type="button" class="add-colada-btn text-xs px-2 py-0.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                    + Agregar colada
                                </button>
                            </div>
                            <div class="coladas-container space-y-1"></div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', newProductoHTML);
                attachProductoListeners();
            });
        });

        // Agregar colada
        function attachProductoListeners() {
            document.querySelectorAll('.add-colada-btn').forEach((btn) => {
                btn.replaceWith(btn.cloneNode(true));
            });

            document.querySelectorAll('.add-colada-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    const container = e.target.closest('.producto-edit-block').querySelector('.coladas-container');
                    const numColadas = container.querySelectorAll('.colada-edit-row').length;

                    const newColadaHTML = `
                        <div class="colada-edit-row flex gap-2 items-center" data-colada-index="${numColadas}">
                            <input type="text" placeholder="Colada" class="colada-field flex-1 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="colada" value="">
                            <input type="number" placeholder="Bultos" class="colada-field w-20 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="bultos" value="">
                            <input type="number" step="0.01" placeholder="Peso (kg)" class="colada-field w-24 rounded border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-blue-500" data-field="peso_kg" value="">
                            <button type="button" class="remove-colada-btn text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                ✕
                            </button>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', newColadaHTML);
                    attachRemoveListeners();
                });
            });

            document.querySelectorAll('.remove-producto-btn').forEach((btn) => {
                btn.replaceWith(btn.cloneNode(true));
            });

            document.querySelectorAll('.remove-producto-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.producto-edit-block').remove();
                });
            });
        }

        function attachRemoveListeners() {
            document.querySelectorAll('.remove-colada-btn').forEach((btn) => {
                btn.replaceWith(btn.cloneNode(true));
            });

            document.querySelectorAll('.remove-colada-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.colada-edit-row').remove();
                });
            });
        }

        // Inicializar listeners
        attachProductoListeners();
        attachRemoveListeners();

        // Función para actualizar totales de coladas seleccionadas
        function updateColadaTotals(idx) {
            const checkboxes = document.querySelectorAll(`.colada-checkbox-${idx}:checked`);
            let totalBultos = 0;
            let totalPeso = 0;

            checkboxes.forEach(cb => {
                totalBultos += parseInt(cb.dataset.bultos) || 0;
                totalPeso += parseFloat(cb.dataset.peso) || 0;
            });

            document.getElementById(`totalBultos-${idx}`).textContent = totalBultos;
            document.getElementById(`totalPeso-${idx}`).textContent = new Intl.NumberFormat('es-ES').format(totalPeso) + ' kg';
        }

        // Añadir listeners a todos los checkboxes de coladas
        document.querySelectorAll('[class*="colada-checkbox-"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const idx = this.className.match(/colada-checkbox-(\d+)/)[1];
                updateColadaTotals(idx);
            });
        });

        // Función para seleccionar manualmente una línea de pedido
        window.seleccionarLineaManual = function(lineaId, resultadoIdx) {
            // Aquí puedes implementar la lógica para:
            // 1. Guardar la línea seleccionada
            // 2. Actualizar la vista para mostrar esta línea como propuesta
            // 3. Cerrar el acordeón
            console.log(`Línea seleccionada manualmente: #${lineaId} para resultado ${resultadoIdx}`);

            // Marcar visualmente la línea seleccionada
            const row = event.target.closest('tr');
            const tbody = row.parentElement;
            tbody.querySelectorAll('tr').forEach(tr => {
                tr.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50');
            });
            row.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50');

            // Mostrar mensaje de confirmación
            alert(`Línea de pedido #${lineaId} seleccionada. Cuando confirmes, se activará esta línea.`);
        };

        // Botón "Confirmar y seguir" (después de editar)
        document.querySelectorAll('.confirm-edit').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = btn.dataset.result;
                const editMode = document.getElementById(`editMode-${idx}`);

                // Recopilar datos editados
                const editedData = {
                    albaran: '',
                    fecha: '',
                    pedido_cliente: '',
                    pedido_codigo: '',
                    peso_total: null,
                    bultos_total: null,
                    productos: []
                };

                // Datos generales
                editMode.querySelectorAll('.general-edit-field').forEach((input) => {
                    const field = input.dataset.field;
                    editedData[field] = input.value || null;
                });

                // Productos
                editMode.querySelectorAll('.producto-edit-block').forEach((productoBlock) => {
                    const producto = {
                        descripcion: '',
                        diametro: null,
                        longitud: null,
                        calidad: '',
                        line_items: []
                    };

                    productoBlock.querySelectorAll('.producto-field').forEach((input) => {
                        const field = input.dataset.field;
                        producto[field] = input.value || null;
                    });

                    productoBlock.querySelectorAll('.colada-edit-row').forEach((coladaRow) => {
                        const colada = {};
                        coladaRow.querySelectorAll('.colada-field').forEach((input) => {
                            const field = input.dataset.field;
                            colada[field] = input.value || null;
                        });
                        producto.line_items.push(colada);
                    });

                    editedData.productos.push(producto);
                });

                // Guardar datos editados (podrías enviarlos al servidor aquí)
                console.log('Datos editados:', editedData);

                // Por ahora, solo ocultar y mostrar simulación
                document.getElementById(`confirmationPrompt-${idx}`).classList.add('hidden');
                document.getElementById(`simulationSection-${idx}`).classList.remove('hidden');
            });
        });
    </script>
</x-app-layout>
